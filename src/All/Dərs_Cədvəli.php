<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Include database connection first
include('db.php');

// Set flag to prevent premature connection closing
$dontCloseConnection = true;

// Include necessary files
include('navbar_sidebar.php');

// Get all teachers
$teachers_query = "SELECT * FROM muellimler_new WHERE active_status = 'active' ORDER BY username ASC";
$teachers_result = mysqli_query($conn, $teachers_query);
$teachers = [];
if ($teachers_result && mysqli_num_rows($teachers_result) > 0) {
    while ($row = mysqli_fetch_assoc($teachers_result)) {
        $teachers[] = $row;
    }
}

// Get all lessons
$lessons_query = "SELECT d.*, m.username as muellim_adi 
                FROM dersler d 
                LEFT JOIN muellimler_new m ON d.muellim_id = m.id 
                WHERE d.active_status = 1 
                ORDER BY d.tarix ASC, d.start_time ASC";
$lessons_result = mysqli_query($conn, $lessons_query);
$lessons = [];
if ($lessons_result && mysqli_num_rows($lessons_result) > 0) {
    while ($row = mysqli_fetch_assoc($lessons_result)) {
        $lessons[] = $row;
    }
}

// Get statistics
$total_lessons_query = "SELECT COUNT(*) as total FROM dersler WHERE active_status = 1";
$total_lessons_result = mysqli_query($conn, $total_lessons_query);
$total_lessons_row = mysqli_fetch_assoc($total_lessons_result);
$total_lessons = $total_lessons_row['total'];

$total_teachers_query = "SELECT COUNT(*) as total FROM muellimler_new WHERE active_status = 'active'";
$total_teachers_result = mysqli_query($conn, $total_teachers_query);
$total_teachers_row = mysqli_fetch_assoc($total_teachers_result);
$total_teachers = $total_teachers_row['total'];

$total_subjects_query = "SELECT COUNT(DISTINCT fenn) as total FROM dersler WHERE active_status = 1";
$total_subjects_result = mysqli_query($conn, $total_subjects_query);
$total_subjects_row = mysqli_fetch_assoc($total_subjects_result);
$total_subjects = $total_subjects_row['total'];

$total_rooms_query = "SELECT COUNT(DISTINCT otaq) as total FROM dersler WHERE active_status = 1";
$total_rooms_result = mysqli_query($conn, $total_rooms_query);
$total_rooms_row = mysqli_fetch_assoc($total_rooms_result);
$total_rooms = $total_rooms_row['total'];

// Get unique classes
$classes_query = "SELECT DISTINCT sinif FROM dersler WHERE active_status = 1 ORDER BY sinif ASC";
$classes_result = mysqli_query($conn, $classes_query);
$classes = [];
if ($classes_result && mysqli_num_rows($classes_result) > 0) {
    while ($row = mysqli_fetch_assoc($classes_result)) {
        $classes[] = $row['sinif'];
    }
}

// Get unique subjects
$subjects_query = "SELECT DISTINCT fenn FROM dersler WHERE active_status = 1 ORDER BY fenn ASC";
$subjects_result = mysqli_query($conn, $subjects_query);
$subjects = [];
if ($subjects_result && mysqli_num_rows($subjects_result) > 0) {
    while ($row = mysqli_fetch_assoc($subjects_result)) {
        $subjects[] = $row['fenn'];
    }
}

// Get unique rooms
$rooms_query = "SELECT DISTINCT otaq FROM dersler WHERE active_status = 1 ORDER BY otaq ASC";
$rooms_result = mysqli_query($conn, $rooms_query);
$rooms = [];
if ($rooms_result && mysqli_num_rows($rooms_result) > 0) {
    while ($row = mysqli_fetch_assoc($rooms_result)) {
        $rooms[] = $row['otaq'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Dərs Cədvəli</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Dərs_Cədvəli/css.css">
    <style>
        
        .lds-ripple {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-ripple div {
            position: absolute;
            border: 4px solid #3182ce;
            opacity: 1;
            border-radius: 50%;
            animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }

        .lds-ripple div:nth-child(2) {
            animation-delay: -0.5s;
        }

        @keyframes lds-ripple {
            0% {
                top: 36px;
                left: 36px;
                width: 0;
                height: 0;
                opacity: 1;
            }
            100% {
                top: 0;
                left: 0;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

    </style>
</head>
<body>


    <div class="preloader">
        <div class="lds-ripple">
            <div></div> <div></div>
        </div>
    </div>

    <div class="main-content main">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-5 text-md-right">
                    <div class="btn-group">
                        <div class="form-group">
                        <button style="width:190px; height:45px;margin-right:5px;margin-bottom:5px;" type="button" class="btn btn-primary add-lesson" data-toggle="modal" data-target="#addLessonModal">
                            <i style="margin:3px;" class="fas fa-plus-circle mr-2"></i> Yeni Dərs
                        </button>
                        <button style="width:190px; height:45px;margin-right:5px;margin-bottom:5px;" type="button" class="btn btn-outline-primary ml-2" id="classScheduleBtn">
                            <i style="margin:3px;" class="fas fa-users"></i> Sinif Cədvəli
                        </button>
                        </div>
                        <button style="width:190px; height:45px;margin-right:5px;margin-bottom:5px;" type="button" class="btn btn-outline-primary ml-2" id="printSchedule">
                            <i style="margin:3px;" class="fas fa-print mr-2"></i> Çap et
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-calendar-alt fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Həftəlik Dərslər</h6>
                        <h3 class="stat-number"><?php echo $total_lessons; ?></h3>
                        <p class="mb-0 small"><?php echo count($classes); ?> sinif üzrə</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-chalkboard-teacher fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Müəllimlər</h6>
                        <h3 class="stat-number"><?php echo $total_teachers; ?></h3>
                        <p class="mb-0 small">Aktiv: <?php echo $total_teachers; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-book fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Fənnlər</h6>
                        <h3 class="stat-number"><?php echo $total_subjects; ?></h3>
                        <p class="mb-0 small">Əsas: <?php echo $total_subjects; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-door-open fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Otaqlar</h6>
                        <h3 class="stat-number"><?php echo $total_rooms; ?></h3>
                        <p class="mb-0 small">İstifadə: 85%</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterTeacher">Müəllim</label>
                        <select class="form-control" id="filterTeacher">
                            <option value="">Bütün Müəllimlər</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['username']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterClass">Sinif</label>
                        <select class="form-control" id="filterClass">
                            <option value="">Bütün Siniflər</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo htmlspecialchars($class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($class, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterSubject">Fənn</label>
                        <select class="form-control" id="filterSubject">
                            <option value="">Bütün Fənnlər</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label for="filterRoom">Otaq</label>
                        <select class="form-control" id="filterRoom">
                            <option value="">Bütün Otaqlar</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($room, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Calendar -->
        <div class="card">
            <div class="card-body">
                <div id="scheduleCalendar"></div>
                
                <!-- Legend -->
                <div class="schedule-legend mt-4">
                    <?php foreach ($subjects as $subject): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: var(--<?php echo htmlspecialchars(preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $subject))), ENT_QUOTES, 'UTF-8'); ?>-color);"></div>
                            <span><?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dərs Məlumatları</h5>
                </div>
                <div class="modal-body">
                    <div class="event-detail">
                        <div class="event-detail-label">Fənn</div>
                        <div class="event-detail-value" id="viewSubject"></div>
                    </div>
                    <div class="event-detail">
                        <div class="event-detail-label">Müəllim</div>
                        <div class="event-detail-value" id="viewTeacher"></div>
                    </div>
                    <div class="event-detail">
                        <div class="event-detail-label">Sinif</div>
                        <div class="event-detail-value" id="viewClass"></div>
                    </div>
                    <div class="event-detail">
                        <div class="event-detail-label">Otaq</div>
                        <div class="event-detail-value" id="viewRoom"></div>
                    </div>
                    <div class="event-detail">
                        <div class="event-detail-label">Tarix və Vaxt</div>
                        <div class="event-detail-value" id="viewDateTime"></div>
                    </div>
                    <div class="event-detail">
                        <div class="event-detail-label">Mövzu</div>
                        <div class="event-detail-value" id="viewTopic"></div>
                    </div>
                    <div class="event-detail">
                        <div class="event-detail-label">Qeydlər</div>
                        <div class="event-detail-value" id="viewNotes"></div>
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary edit-lesson">Redaktə et</button>
                    <button type="button" class="btn btn-danger delete-lesson">Sil</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Schedule Modal -->
    <div class="modal fade" id="teacherScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Müəllim Cədvəli</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="selectTeacher">Müəllim seçin</label>
                        <select class="form-control" id="selectTeacher">
                            <option value="">Seçin</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['username']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="teacherScheduleContent" class="mt-4">
                        <!-- Teacher schedule will be loaded here -->
                        <div class="text-center">
                            <p>Müəllim seçin</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary" id="printTeacherSchedule">Çap et</button>
                </div>
            </div>
        </div>
    </div>

    <!--  Yeni Dərs Əlavə Et -->
    <div class="modal fade" id="addLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Dərs Əlavə Et</h5>
                </div>
                <div class="modal-body">
                    <form id="addLessonForm" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subject">Fənn</label>
                                    <select class="form-control" id="subject" name="subject" required>
                                        <option value="">Seçin</option>
                                        <option value="1">Riyaziyyat</option>
                                        <option value="2">Fizika</option>
                                        <option value="3">Kimya</option>
                                        <option value="4">Biologiya</option>
                                        <option value="5">Tarix</option>
                                        <option value="6">Ədəbiyyat</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class">Sinif</label>
                                    <select class="form-control" id="class" name="class" required>
                                        <option value="">Seçin</option>
                                        <?php
                                        include('db.php');
                                        $sql = "SELECT id, sinif_number FROM sinifler";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["sinif_number"]) . "</option>";
                                            }
                                        }
                                        $conn->close();
                                        ?>
                                        <option value="new">+ Yeni Sinif Əlavə Et</option>
                                    </select>
                                    <div class="invalid-feedback">Sinif seçin</div>
                                </div>
                            </div>
                        </div>
                        <br>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="teacher">Müəllim</label>
                                    <select class="form-control" id="teacher" name="teacher" required>
                                        <option value="">Seçin</option>
                                        <?php
                                        include('db.php');
                                        $sql = "SELECT id, username FROM muellimler_new";
                                        $result = $conn->query($sql);
                                
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["username"]) . "</option>";
                                            }
                                        }
                                        $conn->close();
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room">Otaq</label>
                                    <select class="form-control" id="room" name="room" required>
                                        <option value="">Seçin</option>
                                        <?php
                                        include('db.php');
                                        $sql = "SELECT id, otaq_number FROM otaqlar";
                                        $result = $conn->query($sql);
                                
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["otaq_number"]) . "</option>";
                                            }
                                        }
                                        $conn->close();
                                        ?>
                                        <option value="new">+ Yeni Otaq Əlavə Et</option>
                                    </select>
                                    <div class="invalid-feedback">Otaq seçin</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">Tarix</label>
                                    <input type="date" class="form-control" id="date" name="date" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="startTime">Başlama vaxtı</label>
                                    <input type="time" class="form-control" id="startTime" name="startTime" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="endTime">Bitmə vaxtı</label>
                                    <input type="time" class="form-control" id="endTime" name="endTime" required>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="topic">Mövzu</label>
                            <input type="text" class="form-control" id="topic" name="topic" required>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="description">Təsvir</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="">Seçin</option>
                                        <option value="Planlaşdırılıb" selected>Planlaşdırılıb</option>
                                        <option value="Aktiv">Aktiv</option>
                                        <option value="Dəyişiklik var">Dəyişiklik var</option>
                                        <option value="Ləğv edilib">Ləğv edilib</option>
                                    </select>
                                    <div class="invalid-feedback">Status seçin</div>
                                </div>
                            </div>
                        </div>
                    <br>
                        <div hidden class="form-group">
                            <label for="materials">Materiallar</label>
                            <div class="custom-file">
            <input type="file" class="custom-file-input" id="materials" name="materials[]" multiple>
            <label class="custom-file-label" for="materials">Faylları seçin</label>
        </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                            <button type="button" class="btn btn-primary" id="saveLesson">Yadda saxla</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Dərsi Redaktə Et -->
    <div class="modal fade" id="editLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dərsi Redaktə Et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editLessonForm" method="post" enctype="multipart/form-data">
                        <input type="hidden" id="editLessonId" name="lessonId">
                        <div class="row">
                            <div class="col-md-6">
                            <div class="form-group">
                                <label for="editSubject">Fənn</label>
                                <select class="form-control" id="editSubject" name="subject" required>
                                    <option value="">Seçin</option>
                                    <option value="Riyaziyyat">Riyaziyyat</option>
                                    <option value="Fizika">Fizika</option>
                                    <option value="Kimya">Kimya</option>
                                    <option value="Biologiya">Biologiya</option>
                                    <option value="Tarix">Tarix</option>
                                    <option value="Ədəbiyyat">Ədəbiyyat</option>
                                </select>
                                <div class="invalid-feedback">Fənn seçin</div>
                            </div>
                            </div>
                            <div class="col-md-6">
                               <!-- For Sinif -->
                            <div class="form-group">
                                <label for="editClass">Sinif</label>
                                <select class="form-control" id="editClass" name="class" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    include('db.php');
                                    $sql = "SELECT sinif_number FROM sinifler";  // No need for ID
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row["sinif_number"]) . "'>" . htmlspecialchars($row["sinif_number"]) . "</option>";
                                        }
                                    }
                                    $conn->close();
                                    ?>
                                    <option value="new">+ Yeni Sinif Əlavə Et</option>
                                </select>
                                <div class="invalid-feedback">Sinif seçin</div>
                            </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editTeacher">Müəllim</label>
                                    <select class="form-control" id="editTeacher" name="teacher" required>
                                        <option value="">Seçin</option>
                                        <?php
                                        include('db.php');
                                        $sql = "SELECT id, username FROM muellimler_new";
                                        $result = $conn->query($sql);
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["username"]) . "</option>";
                                            }
                                        }
                                        $conn->close();
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Müəllim seçin</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                            <div class="form-group">
                                <label for="editRoom">Otaq</label>
                                <select class="form-control" id="editRoom" name="room" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    include('db.php');
                                    $sql = "SELECT otaq_number FROM otaqlar";  // No need for ID
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row["otaq_number"]) . "'>" . htmlspecialchars($row["otaq_number"]) . "</option>";
                                        }
                                    }
                                    $conn->close();
                                    ?>
                                    <option value="new">+ Yeni Otaq Əlavə Et</option>
                                </select>
                                <div class="invalid-feedback">Otaq seçin</div>
                            </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editDate">Tarix</label>
                                    <input type="date" class="form-control" id="editDate" name="date" required>
                                    <div class="invalid-feedback">Tarix seçin</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="editStartTime">Başlama vaxtı</label>
                                    <input type="time" class="form-control" id="editStartTime" name="startTime" required>
                                    <div class="invalid-feedback">Vaxt seçin</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="editEndTime">Bitmə vaxtı</label>
                                    <input type="time" class="form-control" id="editEndTime" name="endTime" required>
                                    <div class="invalid-feedback">Vaxt seçin</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="editTopic">Mövzu</label>
                            <input type="text" class="form-control" id="editTopic" name="topic" required>
                            <div class="invalid-feedback">Mövzu daxil edin</div>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="editDescription">Təsvir</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editStatus">Status</label>
                                    <select class="form-control" id="editStatus" name="status" required>
                                        <option value="">Seçin</option>
                                        <option value="Planlaşdırılıb">Planlaşdırılıb</option>
                                        <option value="Aktiv">Aktiv</option>
                                        <option value="Dəyişiklik var">Dəyişiklik var</option>
                                        <option value="Ləğv edilib">Ləğv edilib</option>
                                    </select>
                                    <div class="invalid-feedback">Status seçin</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div hidden class="form-group">
                            <label for="editMaterials">Materiallar</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="editMaterials" name="materials[]" multiple>
                                <label class="custom-file-label" for="editMaterials">Faylları seçin</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                            <button type="button" class="btn btn-primary" id="updateLesson">Yenilə</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Schedule Modal -->
    <div class="modal fade" id="classScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sinif Cədvəli</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="selectClass">Sinif seçin</label>
                        <select class="form-control" id="selectClass">
                            <option value="">Seçin</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo htmlspecialchars($class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($class, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="classScheduleContent" class="mt-4">
                        <!-- Class schedule will be loaded here -->
                        <div class="text-center">
                            <p>Sinif seçin</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary" id="printClassSchedule">Çap et</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Sinif Modal -->
    <div class="modal fade" id="newClassModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Sinif Əlavə Et</h5>
                </div>
                <div class="modal-body">
                    <form id="addClassForm">
                        <div class="form-group">
                            <label for="classNumber">Sinif Nömrəsi</label>
                            <input type="text" class="form-control" id="classNumber" name="sinif_number" required>
                        </div>
                        <div class="form-group">
                            <label for="classCapacity">Tutum (Nəfər)</label>
                            <input type="number" class="form-control" id="classCapacity" name="tutum" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-primary" id="saveNewClass">Yadda saxla</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Otaq Modal -->
    <div class="modal fade" id="newRoomModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Otaq Əlavə Et</h5>
                </div>
                <div class="modal-body">
                    <form id="addRoomForm">
                        <div class="form-group">
                            <label for="roomNumber">Otaq Nömrəsi</label>
                            <input type="text" class="form-control" id="roomNumber" name="otaq_number" required>
                        </div>
                        <div class="form-group">
                            <label for="roomCapacity">Tutum (Nəfər)</label>
                            <input type="number" class="form-control" id="roomCapacity" name="tutum" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-primary" id="saveNewRoom">Yadda saxla</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Schedule Modal -->
    <div class="modal fade" id="importScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cədvəli İdxal Et</h5>
                </div>
                <div class="modal-body">
                    <p>Dərs cədvəlini CSV və ya Excel formatında idxal edin.</p>
                    <div class="form-group">
                        <label for="importFile">Fayl seçin</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="importFile">
                            <label class="custom-file-label" for="importFile">Fayl seçin</label>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle mr-1"></i> Nümunə faylı <a href="#" class="alert-link">buradan</a> yükləyə bilərsiniz.
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary">İdxal et</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dərsi Sil</h5>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                        <h5>Bu dərsi silmək istədiyinizə əminsiniz?</h5>
                        <p class="text-muted">Bu əməliyyat geri qaytarıla bilməz.</p>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="deleteConfirm">
                            <label class="custom-control-label" for="deleteConfirm">Bəli, bu dərsi silmək istəyirəm</label>
                        </div>
                    </div>
                    <input type="hidden" id="deleteLessonId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete" disabled>Sil</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script src="../assets/extra-libs/c3/d3.min.js"></script>
    <script src="../assets/extra-libs/c3/c3.min.js"></script>
    <script src="../assets/libs/chartist/dist/chartist.min.js"></script>
    <script src="../assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
    <script src="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.min.js"></script>
    <script src="../assets/extra-libs/jvector/jquery-jvectormap-world-mill-en.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    
    <script>
        function getCsrfToken() {
            if (window.APP_CSRF_TOKEN) {
                return window.APP_CSRF_TOKEN;
            }
            var metaToken = document.querySelector('meta[name="csrf-token"]');
            return metaToken ? metaToken.getAttribute('content') : '';
        }

        function formatLessonDateTime(date, time) {
            if (!date || !time) {
                return date;
            }
            if (time.length === 5) {
                time += ':00';
            }
            return date + 'T' + time;
        }

        $(document).ready(function() {
            if (getCsrfToken() && typeof $.ajaxSetup === 'function') {
                $.ajaxSetup({
                    beforeSend: function(xhr, settings) {
                        if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type || 'GET')) {
                            xhr.setRequestHeader('X-CSRF-Token', getCsrfToken());
                        }
                    }
                });
            }

            // Hide preloader when page is loaded
            $(".preloader").fadeOut();
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Handle file input display
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
            
            // Initialize FullCalendar
            var calendarEl = document.getElementById('scheduleCalendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                allDaySlot: false,
                height: 'auto',
                locale: 'az',
                buttonText: {
                    today: 'Bu gün',
                    month: 'Ay',
                    week: 'Həftə',
                    day: 'Gün',
                    list: 'Siyahı'
                },
                events: function(info, successCallback, failureCallback) {
                    // Load events from the server
                    $.ajax({
                        url: 'Dərs_Cədvəli/lesson_operations.php',
                        type: 'POST',
                        data: {
                            action: 'get_lessons',
                            csrf_token: getCsrfToken()
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                var events = [];
                                response.data.forEach(function(lesson) {
                                    events.push({
                                        id: lesson.id,
                                        title: lesson.fenn + ' - ' + lesson.sinif,
                                        start: formatLessonDateTime(lesson.tarix, lesson.start_time),
                                        end: formatLessonDateTime(lesson.tarix, lesson.end_time),
                                        extendedProps: {
                                            fenn: lesson.fenn,
                                            sinif: lesson.sinif,
                                            muellim: lesson.muellim_adi,
                                            muellim_id: lesson.muellim_id,
                                            otaq: lesson.otaq,
                                            movzu: lesson.movzu,
                                            tesvir: lesson.tesvir
                                        },
                                        backgroundColor: getSubjectColor(lesson.fenn),
                                        borderColor: getSubjectColor(lesson.fenn)
                                    });
                                });
                                successCallback(events);
                            } else {
                                failureCallback(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            failureCallback(error);
                        }
                    });
                },
                eventClick: function(info) {
                    // Show event details in modal
                    $('#viewSubject').text(info.event.extendedProps.fenn);
                    $('#viewTeacher').text(info.event.extendedProps.muellim);
                    $('#viewClass').text(info.event.extendedProps.sinif);
                    $('#viewRoom').text(info.event.extendedProps.otaq);
                    
                    // Format date and time
                    var startDate = new Date(info.event.start);
                    var endDate = new Date(info.event.end);
                    var formattedDate = startDate.toLocaleDateString('az-AZ');
                    var formattedStartTime = startDate.toLocaleTimeString('az-AZ', { hour: '2-digit', minute: '2-digit' });
                    var formattedEndTime = endDate.toLocaleTimeString('az-AZ', { hour: '2-digit', minute: '2-digit' });
                    $('#viewDateTime').text(formattedDate + ', ' + formattedStartTime + ' - ' + formattedEndTime);
                    
                    $('#viewTopic').text(info.event.extendedProps.movzu || 'Təyin edilməyib');
                    $('#viewNotes').text(info.event.extendedProps.tesvir || 'Qeyd yoxdur');
                    
                    // Set lesson ID for edit and delete operations
                    $('.edit-lesson').data('id', info.event.id);
                    $('.delete-lesson').data('id', info.event.id);
                    
                    // Show the modal
                    $('#viewLessonModal').modal('show');
                }
            });
            calendar.render();
            
            // Function to get subject color
            function getSubjectColor(subject) {
                // Convert subject to lowercase and replace spaces with hyphens
                var subjectClass = subject.toLowerCase().replace(/\s+/g, '-');
                
                // Get the CSS variable value
                var color = getComputedStyle(document.documentElement).getPropertyValue('--' + subjectClass + '-color');
                
                // Return the color or a default color if not found
                return color || '#3788d8';
            }
            
            // Show/hide repeat until field based on checkbox
            $('#repeatWeekly').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#repeatUntilGroup').show();
                } else {
                    $('#repeatUntilGroup').hide();
                }
            });

            var nestedModalParent = null;
            var nestedSelectTarget = null;

            function openNestedModal(parentModal, childModal, selectTarget) {
                nestedModalParent = parentModal;
                nestedSelectTarget = selectTarget;
                $(parentModal).modal('hide');
                setTimeout(function() {
                    $(childModal).modal('show');
                }, 400);
            }

            function reopenParentModalIfNeeded() {
                if (!nestedModalParent) {
                    return;
                }
                var parent = nestedModalParent;
                nestedModalParent = null;
                setTimeout(function() {
                    $(parent).modal('show');
                }, 400);
            }

            function appendRoomOption(selectSelector, room, useId) {
                var $select = $(selectSelector);
                var value = useId ? String(room.id) : room.otaq_number;
                if ($select.find('option[value="' + value.replace(/"/g, '\\"') + '"]').length === 0) {
                    $select.find('option[value="new"]').before(
                        $('<option>', { value: value, text: room.otaq_number })
                    );
                }
                $select.val(value);
            }

            function appendClassOption(selectSelector, classItem, useId) {
                var $select = $(selectSelector);
                var value = useId ? String(classItem.id) : classItem.sinif_number;
                if ($select.find('option[value="' + value.replace(/"/g, '\\"') + '"]').length === 0) {
                    $select.find('option[value="new"]').before(
                        $('<option>', { value: value, text: classItem.sinif_number })
                    );
                }
                $select.val(value);
            }

            $('#room').on('change', function() {
                if ($(this).val() === 'new') {
                    $(this).val('');
                    openNestedModal('#addLessonModal', '#newRoomModal', '#room');
                }
            });

            $('#editRoom').on('change', function() {
                if ($(this).val() === 'new') {
                    $(this).val('');
                    openNestedModal('#editLessonModal', '#newRoomModal', '#editRoom');
                }
            });

            $('#class').on('change', function() {
                if ($(this).val() === 'new') {
                    $(this).val('');
                    openNestedModal('#addLessonModal', '#newClassModal', '#class');
                }
            });

            $('#editClass').on('change', function() {
                if ($(this).val() === 'new') {
                    $(this).val('');
                    openNestedModal('#editLessonModal', '#newClassModal', '#editClass');
                }
            });

            $('#newRoomModal, #newClassModal').on('hidden.bs.modal', function() {
                reopenParentModalIfNeeded();
            });

            $('#saveNewRoom').on('click', function() {
                if (!$('#addRoomForm')[0].checkValidity()) {
                    $('#addRoomForm')[0].reportValidity();
                    return;
                }

                $.ajax({
                    url: 'Dərs_Cədvəli/lesson_operations.php',
                    type: 'POST',
                    data: {
                        action: 'add_room',
                        otaq_number: $('#roomNumber').val(),
                        tutum: $('#roomCapacity').val(),
                        csrf_token: getCsrfToken()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var useId = nestedSelectTarget === '#room';
                            appendRoomOption(nestedSelectTarget || '#room', response.data, useId);
                            $('#addRoomForm')[0].reset();
                            $('#newRoomModal').modal('hide');
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Xəta baş verdi: ' + (xhr.responseText || error));
                    }
                });
            });

            $('#saveNewClass').on('click', function() {
                if (!$('#addClassForm')[0].checkValidity()) {
                    $('#addClassForm')[0].reportValidity();
                    return;
                }

                $.ajax({
                    url: 'Dərs_Cədvəli/lesson_operations.php',
                    type: 'POST',
                    data: {
                        action: 'add_class',
                        sinif_number: $('#classNumber').val(),
                        tutum: $('#classCapacity').val(),
                        csrf_token: getCsrfToken()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var useId = nestedSelectTarget === '#class';
                            appendClassOption(nestedSelectTarget || '#class', response.data, useId);
                            $('#addClassForm')[0].reset();
                            $('#newClassModal').modal('hide');
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Xəta baş verdi: ' + (xhr.responseText || error));
                    }
                });
            });
            
            // Filter events based on selections
            $('#filterTeacher, #filterClass, #filterSubject, #filterRoom').on('change', function() {
                var teacherId = $('#filterTeacher').val();
                var classValue = $('#filterClass').val();
                var subjectValue = $('#filterSubject').val();
                var roomValue = $('#filterRoom').val();
                
                calendar.getEvents().forEach(function(event) {
                    var show = true;
                    
                    if (teacherId && event.extendedProps.muellim_id != teacherId) {
                        show = false;
                    }
                    
                    if (classValue && event.extendedProps.sinif != classValue) {
                        show = false;
                    }
                    
                    if (subjectValue && event.extendedProps.fenn != subjectValue) {
                        show = false;
                    }
                    
                    if (roomValue && event.extendedProps.otaq != roomValue) {
                        show = false;
                    }
                    
                    if (show) {
                        event.setProp('display', 'auto');
                    } else {
                        event.setProp('display', 'none');
                    }
                });
            });
            
            $('#addLessonForm').on('submit', function(e) {
                e.preventDefault();
                $('#saveLesson').trigger('click');
            });

            $('#saveLesson').on('click', function() {
                if ($('#addLessonForm')[0].checkValidity()) {
                    var formData = {
                        action: 'add_lesson',
                        fenn: $('#subject').val(),
                        sinif: $('#class').val(),
                        start_time: $('#startTime').val(),
                        end_time: $('#endTime').val(),
                        otaq: $('#room').val(),
                        muellim: $('#teacher option:selected').text(),
                        muellim_id: $('#teacher').val(),
                        movzu: $('#topic').val(),
                        tesvir: $('#description').val() || '',
                        tarix: $('#date').val(),
                        status: $('#status').val(),
                        csrf_token: getCsrfToken()
                    };
                    
                    // Check if weekly repeat is enabled
                    if ($('#repeatWeekly').is(':checked')) {
                        formData.repeat = 'weekly';
                        formData.repeatUntil = $('#repeatUntil').val();
                    }
                    
                    // Send AJAX request
                    $.ajax({
                        url: 'Dərs_Cədvəli/lesson_operations.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                $('#addLessonModal').modal('hide');
                                if (formData.tarix) {
                                    calendar.gotoDate(formData.tarix);
                                }
                                calendar.refetchEvents();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Xəta baş verdi: ' + (xhr.responseText || error));
                        }
                    });
                } else {
                    $('#addLessonForm')[0].reportValidity();
                }
            });
            
            // Edit lesson button click handler
            $('.edit-lesson').on('click', function() {
                var lessonId = $(this).data('id');
                
                $.ajax({
                    url: 'Dərs_Cədvəli/lesson_operations.php',
                    type: 'POST',
                    data: {
                        action: 'get_lesson',
                        lessonId: lessonId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var lesson = response.data;
                            
                            // Fill edit form
                            $('#editLessonId').val(lesson.id);
                            $('#editSubject').val(lesson.fenn);
                            $('#editClass').val(lesson.sinif);
                            $('#editTeacher').val(lesson.muellim_id);
                            $('#editRoom').val(lesson.otaq);
                            $('#editDate').val(lesson.tarix);
                            $('#editStartTime').val(lesson.start_time);
                            $('#editEndTime').val(lesson.end_time);
                            $('#editTopic').val(lesson.movzu);
                            $('#editDescription').val(lesson.tesvir);
                            $('#editStatus').val(lesson.status);
                            
                            $('#editLessonModal').modal('show');
                            $('#viewLessonModal').modal('hide');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Xəta baş verdi: ' + error);
                    }
                });
            });

            // Update lesson handler
            $('#updateLesson').on('click', function(e) {
                e.preventDefault();
                const form = $('#editLessonForm');
                
                if (form[0].checkValidity()) {
                    var formData = {
                        action: 'edit_lesson',
                        lessonId: $('#editLessonId').val(),
                        fenn: $('#editSubject').val(),        // Text value like "Riyaziyyat"
                        sinif: $('#editClass').val(),         // Text value like "10A"
                        start_time: $('#editStartTime').val(),
                        end_time: $('#editEndTime').val(),
                        otaq: $('#editRoom').val(),           // Text value like "Room 101"
                        muellim: $('#editTeacher option:selected').text(),
                        muellim_id: $('#editTeacher').val(),
                        movzu: $('#editTopic').val(),
                        tesvir: $('#editDescription').val(),
                        tarix: $('#editDate').val(),
                        status: $('#editStatus').val()
                    };

                    $.ajax({
                        url: 'Dərs_Cədvəli/lesson_operations.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                $('#editLessonModal').modal('hide');
                                if (typeof calendar !== 'undefined') {
                                    calendar.refetchEvents();
                                }
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Xəta baş verdi: ' + (xhr.responseText || error));
                        }
                    });
                } else {
                    form[0].reportValidity();
                }
            });
                            
            // Delete lesson
            $('.delete-lesson').on('click', function() {
                var lessonId = $(this).data('id');
                $('#deleteLessonId').val(lessonId);  // Set the lesson ID in the hidden input field
                $('#viewLessonModal').modal('hide'); // Hide the view lesson modal
                $('#deleteLessonModal').modal('show'); // Show the delete confirmation modal
            });

            // Add lesson
            $('.add-lesson').on('click', function() {
                $('#addLessonModal').modal('show'); // Show the add lesson modal
            });

            // Enable delete button when checkbox is checked
            $('#deleteConfirm').on('change', function() {
                $('#confirmDelete').prop('disabled', !this.checked); // Enable the delete button only if the checkbox is checked
            });

            // Confirm delete action
            $('#confirmDelete').on('click', function() {
                var lessonId = $('#deleteLessonId').val();  // Get the lesson ID from the hidden input
                // No need for 'deleteOption' if it's not used, so we can ignore it
                var deleteOption = $('input[name="deleteOption"]:checked').val();  // (Optional) Get delete option if it's used in your system
                
                // Send AJAX request to delete the lesson
                $.ajax({
                    url: 'Dərs_Cədvəli/lesson_operations.php',  // The file that handles the delete action
                    type: 'POST',
                    data: {
                        action: 'delete_lesson',  // Action type
                        lessonId: lessonId,  // Pass the lesson ID to delete
                        // deleteOption: deleteOption,  // (Optional) If you need delete options
                    },
                    dataType: 'json',  // Expecting JSON response
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);  // Show success message
                            $('#deleteLessonModal').modal('hide');  // Hide the delete modal
                            calendar.refetchEvents();  // Refresh the calendar (or other elements if needed)
                        } else {
                            alert(response.message);  // Show error message if deletion fails
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Xəta baş verdi: ' + error);  // Display an error message if AJAX fails
                    }
                });
            });


            
            // Show teacher schedule modal
            $('#teacherScheduleBtn').on('click', function() {
                $('#teacherScheduleModal').modal('show');
            });
            
            // Load teacher schedule
            $('#selectTeacher').on('change', function() {
                var teacherId = $(this).val();
                
                if (teacherId) {
                    // Send AJAX request
                    $.ajax({
                        url: 'Dərs_Cədvəli/lesson_operations.php',
                        type: 'POST',
                        data: {
                            action: 'get_lessons_by_teacher',
                            teacherId: teacherId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                var lessons = response.data;
                                var teacherName = $('#selectTeacher option:selected').text();
                                
                                // Build the schedule table
                                var html = '<h4>' + teacherName + ' - Dərs Cədvəli</h4>';
                                html += '<div class="table-responsive">';
                                html += '<table class="table table-bordered">';
                                html += '<thead><tr><th>Gün</th><th>Vaxt</th><th>Fənn</th><th>Sinif</th><th>Otaq</th></tr></thead>';
                                html += '<tbody>';
                                
                                if (lessons.length > 0) {
                                    lessons.forEach(function(lesson) {
                                        var date = new Date(lesson.tarix);
                                        var dayName = date.toLocaleDateString('az-AZ', { weekday: 'long' });
                                        
                                        html += '<tr>';
                                        html += '<td>' + dayName + ' (' + lesson.tarix + ')</td>';
                                        html += '<td>' + lesson.start_time + ' - ' + lesson.end_time + '</td>';
                                        html += '<td>' + lesson.fenn + '</td>';
                                        html += '<td>' + lesson.sinif + '</td>';
                                        html += '<td>' + lesson.otaq + '</td>';
                                        html += '</tr>';
                                    });
                                } else {
                                    html += '<tr><td colspan="5" class="text-center">Bu müəllim üçün dərs tapılmadı</td></tr>';
                                }
                                
                                html += '</tbody></table></div>';
                                
                                // Update the content
                                $('#teacherScheduleContent').html(html);
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Xəta baş verdi: ' + (xhr.responseText || error));
                        }
                    });
                } else {
                    $('#teacherScheduleContent').html('<div class="text-center"><p>Müəllim seçin</p></div>');
                }
            });
            
            // Show class schedule modal
            $('#classScheduleBtn').on('click', function() {
                $('#classScheduleModal').modal('show');
            });
            

            // Load class schedule
            $("#selectClass").on("change", function () {
            var classValue = $(this).val()

            if (classValue) {
                // Send AJAX request
                $.ajax({
                url: "Dərs_Cədvəli/lesson_operations.php",
                type: "POST",
                data: {
                    action: "get_lessons_by_class",
                    class: classValue,
                },
                dataType: "json",
                success: (response) => {
                    if (response.success) {
                    var lessons = response.data
                    var isMobile = window.innerWidth <= 576

                    if (isMobile) {
                        // Build card-based layout for mobile
                        var htmlMobile = "<h4>" + classValue + " - Dərs Cədvəli</h4>"
                        htmlMobile += '<div class="mobile-schedule-cards">'

                        if (lessons.length > 0) {
                        lessons.forEach((lesson) => {
                            var date = new Date(lesson.tarix)
                            var dayName = date.toLocaleDateString("az-AZ", { weekday: "long" })

                            htmlMobile += '<div class="mobile-schedule-card">'
                            htmlMobile += '<div class="mobile-schedule-card-header">'
                            htmlMobile += "<div>" + dayName + "</div>"
                            htmlMobile += "<div>" + lesson.start_time + " - " + lesson.end_time + "</div>"
                            htmlMobile += "</div>"
                            htmlMobile += '<div class="mobile-schedule-card-body">'
                            htmlMobile += '<div class="mobile-schedule-card-label">Fənn:</div>'
                            htmlMobile += "<div>" + lesson.fenn + "</div>"
                            htmlMobile += '<div class="mobile-schedule-card-label">Müəllim:</div>'
                            htmlMobile += "<div>" + lesson.muellim_adi + "</div>"
                            htmlMobile += '<div class="mobile-schedule-card-label">Otaq:</div>'
                            htmlMobile += "<div>" + lesson.otaq + "</div>"
                            htmlMobile += '<div class="mobile-schedule-card-label">Tarix:</div>'
                            htmlMobile += "<div>" + lesson.tarix + "</div>"
                            htmlMobile += "</div>"
                            htmlMobile += "</div>"
                        })
                        } else {
                        htmlMobile += '<div class="text-center p-3">Bu sinif üçün dərs tapılmadı</div>'
                        }

                        htmlMobile += "</div>"
                        $("#classScheduleContent").html(htmlMobile)
                    } else {
                        // Build the responsive table for larger screens
                        var htmlTable = "<h4>" + classValue + " - Dərs Cədvəli</h4>"
                        htmlTable += '<div class="table-responsive">'
                        htmlTable += '<table class="table table-bordered mobile-schedule-table">'
                        htmlTable += "<thead><tr><th>Gün</th><th>Vaxt</th><th>Fənn</th><th>Müəllim</th><th>Otaq</th></tr></thead>"
                        htmlTable += "<tbody>"

                        if (lessons.length > 0) {
                        lessons.forEach((lesson) => {
                            var date = new Date(lesson.tarix)
                            var dayName = date.toLocaleDateString("az-AZ", { weekday: "long" })

                            htmlTable += "<tr>"
                            htmlTable +=
                            '<td class="date-column">' + dayName + ' <span class="date-full">(' + lesson.tarix + ")</span></td>"
                            htmlTable += '<td class="time-column">' + lesson.start_time + " - " + lesson.end_time + "</td>"
                            htmlTable += "<td>" + lesson.fenn + "</td>"
                            htmlTable += "<td>" + lesson.muellim_adi + "</td>"
                            htmlTable += "<td>" + lesson.otaq + "</td>"
                            htmlTable += "</tr>"
                        })
                        } else {
                        htmlTable += '<tr><td colspan="5" class="text-center">Bu sinif üçün dərs tapılmadı</td></tr>'
                        }

                        htmlTable += "</tbody></table></div>"
                        $("#classScheduleContent").html(htmlTable)
                    }

                    // Update the content
                    } else {
                    alert(response.message)
                    }
                },
                error: (xhr, status, error) => {
                    alert("Xəta baş verdi: " + error)
                },
                })
            } else {
                $("#classScheduleContent").html('<div class="text-center"><p>Sinif seçin</p></div>')
            }
            })

            // Check for screen size changes to update the view
            $(window).on("resize", () => {
            var classValue = $("#selectClass").val()
            if (classValue) {
                $("#selectClass").trigger("change")
            }
            })



            
            // Print schedule
            $('#printSchedule').on('click', function() {
                window.print();
            });
            
            // Print teacher schedule
            $('#printTeacherSchedule').on('click', function() {
                var content = document.getElementById('teacherScheduleContent').innerHTML;
                var printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Müəllim Cədvəli</title>');
                printWindow.document.write('<link rel="stylesheet" href="../dist/css/style.min.css">');
                printWindow.document.write('</head><body>');
                printWindow.document.write(content);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
            
            // Print class schedule
            $('#printClassSchedule').on('click', function() {
                var content = document.getElementById('classScheduleContent').innerHTML;
                var printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Sinif Cədvəli</title>');
                printWindow.document.write('<link rel="stylesheet" href="../dist/css/style.min.css">');
                printWindow.document.write('</head><body>');
                printWindow.document.write(content);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
            
            // Export schedule
            $('#exportSchedule').on('click', function() {
                // Send AJAX request to get all lessons
                $.ajax({
                    url: 'Dərs_Cədvəli/lesson_operations.php',
                    type: 'POST',
                    data: {
                        action: 'get_lessons'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var lessons = response.data;
                            var csv = 'Fənn,Sinif,Müəllim,Otaq,Tarix,Başlama vaxtı,Bitmə vaxtı,Mövzu\n';
                            
                            lessons.forEach(function(lesson) {
                                csv += '"' + lesson.fenn + '","' + lesson.sinif + '","' + lesson.muellim_adi + '","' + 
                                       lesson.otaq + '","' + lesson.tarix + '","' + lesson.start_time + '","' + 
                                       lesson.end_time + '","' + (lesson.movzu || '') + '"\n';
                            });
                            
                            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                            var link = document.createElement('a');
                            var url = URL.createObjectURL(blob);
                            link.setAttribute('href', url);
                            link.setAttribute('download', 'ders_cedveli.csv');
                            link.style.visibility = 'hidden';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Xəta baş verdi: ' + error);
                    }
                });
            });
        });

        const closeModalBtns = document.querySelectorAll('.close-modal');
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const modal = this.closest('.modal');
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            });
        });

    </script>
</body>
</html>