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

    // Include necessary files
    include('navbar_sidebar.php');
    include('db.php');

    // Get statistics
    // Initialize counts
    $totalLessons = $thisWeekLessons = $activeTeachers = $todayTeachers = $classCount = $avgStudents = $todayHours = 0;

    // Function to get current date in MySQL format
    function getCurrentDate() {
        return date('Y-m-d');
    }

    // Function to get the start date of the current week
    function getWeekStartDate() {
        return date('Y-m-d', strtotime('monday this week'));
    }

    // Get total lessons count
    $totalLessonsQuery = "SELECT COUNT(*) as total FROM dersler";
    $totalLessonsResult = $conn->query($totalLessonsQuery);
    if ($totalLessonsResult) {
        $totalLessons = $totalLessonsResult->fetch_assoc()['total'];
    }

    // Get this week's lessons count
    $weekStartDate = getWeekStartDate();
    $thisWeekLessonsQuery = "SELECT COUNT(*) as total FROM dersler WHERE tarix >= '$weekStartDate'";
    $thisWeekLessonsResult = $conn->query($thisWeekLessonsQuery);
    if ($thisWeekLessonsResult) {
        $thisWeekLessons = $thisWeekLessonsResult->fetch_assoc()['total'];
    }

    // Get active teachers count
    $activeTeachersQuery = "SELECT COUNT(DISTINCT muellim_id) as total FROM dersler WHERE active_status = 1";
    $activeTeachersResult = $conn->query($activeTeachersQuery);
    if ($activeTeachersResult) {
        $activeTeachers = $activeTeachersResult->fetch_assoc()['total'];
    }

    // Get today's teaching teachers count
    $todayDate = getCurrentDate();
    $todayTeachersQuery = "SELECT COUNT(DISTINCT muellim_id) as total FROM dersler 
                        WHERE tarix = '$todayDate' AND active_status = 1";
    $todayTeachersResult = $conn->query($todayTeachersQuery);
    if ($todayTeachersResult) {
        $todayTeachers = $todayTeachersResult->fetch_assoc()['total'];
    }

    // Get unique class count
    $classCountQuery = "SELECT COUNT(DISTINCT sinif) as total FROM dersler WHERE active_status = 1";
    $classCountResult = $conn->query($classCountQuery);
    if ($classCountResult) {
        $classCount = $classCountResult->fetch_assoc()['total'];
    }

    // Get average student count per class
    $avgStudentsQuery = "SELECT AVG(sagird_sayi) as average FROM dersler WHERE active_status = 1";
    $avgStudentsResult = $conn->query($avgStudentsQuery);
    if ($avgStudentsResult) {
        $avgStudents = round($avgStudentsResult->fetch_assoc()['average']);
    }

    // Get today's lesson hours count
    $todayHoursQuery = "SELECT COUNT(*) as total FROM dersler 
                    WHERE tarix = '$todayDate' AND active_status = 1";
    $todayHoursResult = $conn->query($todayHoursQuery);
    if ($todayHoursResult) {
        $todayHours = $todayHoursResult->fetch_assoc()['total'];
    }

    // Initialize filter variables
    $searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
    $filterSubject = isset($_GET['subject']) ? $_GET['subject'] : '';
    $filterClass = isset($_GET['class']) ? $_GET['class'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Dərslər</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .main-content {
            margin-left: 0;
            padding: 20px;
            flex: 1;
            margin-top: 70px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: margin-left 0.3s ease;
            background-color: #f5f5f5;
        }

        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 80%;
            color: #dc3545;
        }

        /* Material Design Variables */
        :root {
            --primary-color: #1d6a9d;
            --primary-light: #2479b1;
            --primary-dark: #0d5a8d;
            --accent-color: #ff4081;
            --text-primary: #212121;
            --text-secondary: #757575;
            --divider-color: #BDBDBD;
            --background: #f5f5f5;
            --surface: #ffffff;
            --error: #B00020;
            --success: #4CAF50;
            --warning: #FFC107;
            --info: #03A9F4;
        }

        /* Card Styles */
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            margin-left: 5px;
            margin-bottom: 10px;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .card-body {
            padding: 0.98rem;
        }

        /* Stat Cards */
        .stat-card {
            position: relative;
            overflow: hidden;
            color: white;
            height: 100%;
        }

        .stat-card .icon-box {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .stat-card .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .stat-card .stat-title {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .stat-card-clickable {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card-clickable:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card-clickable:focus {
            outline: 2px solid rgba(255, 255, 255, 0.8);
            outline-offset: 2px;
        }

        /* Table Styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table th {
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid #e0e0e0;
            padding: 12px 15px;
            background-color: #f9f9f9;
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #e0e0e0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(29, 106, 157, 0.05);
        }

        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge-success {
            background-color: rgba(124, 135, 152, 0.14);
            color: #7c8798;
        }

        .badge-warning {
            background-color: var(--warning);
            color: #212121;
        }

        .badge-danger {
            background-color: var(--error);
            color: white;
        }

        .badge-info {
            background-color: var(--info);
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        .badge-aktiv {
            background-color: #007bff;
            color: #fff;
        }

        /* Buttons */
        .btn {
            border-radius: 4px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* Form Controls */
        .form-control {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 8px 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(29, 106, 157, 0.25);
        }

        /* Modal */
        .modal-content {
            border-radius: 8px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid #e0e0e0;
            padding: 1.25rem 1.5rem;
            background-color: #f9f9f9;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #e0e0e0;
            padding: 1.25rem 1.5rem;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            font-weight: 500;
            position: relative;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
        }

        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
        }

        /* Today's Schedule */
        .schedule-item {
            border-left: 3px solid var(--primary-color);
            padding: 12px 15px;
            margin-bottom: 15px;
            background-color: #fff;
            border-radius: 0 4px 4px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }

        .schedule-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .schedule-item.active {
            border-left-color: var(--success);
        }

        .schedule-item.upcoming {
            border-left-color: var(--warning);
        }

        .schedule-item.canceled {
            border-left-color: var(--error);
        }

        .schedule-time {
            font-weight: 600;
            color: var(--primary-color);
        }

        .schedule-room {
            display: inline-block;
            background-color: #e0e0e0;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 5px;
        }

        /* Attendance Tracker */
        .attendance-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .attendance-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
        }

        .attendance-body {
            padding: 15px;
        }

        .attendance-student {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .attendance-student:last-child {
            border-bottom: none;
        }

        .attendance-student .student-name {
            flex: 1;
        }

        .attendance-actions {
            display: flex;
            gap: 10px;
        }

        .lds-ripple {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-ripple div {
            position: absolute;
            border: 4px solid var(--primary-color);
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
                top: 0px;
                left: 0px;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
            }
        }

        .custom-file {
            max-width: 250px;
            margin: 6px 0;
        }

        /* Hide the default file input */
        .custom-file-input {
            opacity: 0;
            position: absolute;
            z-index: -1;
        }

        .custom-file-label {
            cursor: pointer;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            transition: background 0.3s ease;
            display: block;
            text-align: center;
            border: 1px solid #ced4da;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .custom-file-label:hover {
            background-color: #e9ecef;
        }

        /* Calendar styles */
        .calendar-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: #f5f5f5;
        }
        .calendar-day-header {
            background-color: #f8f9fa;
            text-align: center;
            padding: 10px;
            font-weight: bold;
        }
        .calendar-day {
            background-color: #fff;
            min-height: 100px;
            padding: 5px;
            position: relative;
        }
        .calendar-day.today {
            background-color: #f0f8ff;
        }
        .calendar-day.other-month {
            background-color: #f9f9f9;
            color: #aaa;
        }
        .day-number {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 14px;
            color: #777;
        }
        .calendar-event {
            margin-top: 20px;
            margin-bottom: 5px;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .event-active {
            background-color: #28a745;
            color: white;
        }
        .event-planned {
            background-color: #ffc107;
            color: #212529;
        }
        .event-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .event-changed {
            background-color: #17a2b8;
            color: white;
        }
        .event-completed {
            background-color: #6c757d;
            color: white;
        }
        .calendar-event:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }
        .event-active:hover {
            background-color: #218838;
        }
        .event-planned:hover {
            background-color: #e0a800;
        }
        .event-cancelled:hover {
            background-color: #c82333;
        }
        .event-changed:hover {
            background-color: #138496;
        }
        .event-completed:hover {
            background-color: #5a6268;
        }
        
        /* Today's lessons styles */
        .today-lesson-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .today-lesson-card:hover {
            transform: translateY(-3px);
        }
        .today-lesson-card.active {
            border-left-color: #28a745;
        }
        .today-lesson-card.planned {
            border-left-color: #ffc107;
        }
        .today-lesson-card.cancelled {
            border-left-color: #dc3545;
        }
        .today-lesson-card.changed {
            border-left-color: #17a2b8;
        }
        .today-lesson-card.completed {
            border-left: 4px solid #6c757d;
            opacity: 0.7;
        }
        .lesson-time {
            font-weight: bold;
            color: #495057;
        }
        .lesson-room {
            background-color: #f8f9fa;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .no-lessons-today {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>

    <div class="main-content main">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-9 text-md-right">
                    <button type="button" class="btn btn-primary create-lesson" data-toggle="modal" data-target="#addLessonModal">
                        <i class="fas fa-plus-circle mr-1"></i> Dərs
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-primary text-white h-100" data-stat-type="lessons" role="button" tabindex="0" aria-label="Ümumi dərsləri göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-book fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Ümumi Dərslər</h6>
                        <h3 class="stat-number"><?php echo $totalLessons; ?></h3>
                        <p class="mb-0 small">Bu həftə: <?php echo $thisWeekLessons; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-success text-white h-100" data-stat-type="teachers" role="button" tabindex="0" aria-label="Aktiv müəllimləri göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-chalkboard-teacher fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Aktiv Müəllimlər</h6>
                        <h3 class="stat-number"><?php echo $activeTeachers; ?></h3>
                        <p class="mb-0 small">Bu gün dərsdə: <?php echo $todayTeachers; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-info text-white h-100" data-stat-type="classes" role="button" tabindex="0" aria-label="Sinifləri göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Sinif Sayı</h6>
                        <h3 class="stat-number"><?php echo $classCount; ?></h3>
                        <p class="mb-0 small">Orta şagird sayı: <?php echo $avgStudents; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-warning text-white h-100" data-stat-type="today" role="button" tabindex="0" aria-label="Bu günün dərslərini göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Bu Gün</h6>
                        <h3 class="stat-number"><?php echo $todayHours; ?></h3>
                        <p class="mb-0 small">Dərs saatı</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Panel -->
        <div class="mb-3 card">
            <div class="card-body">
                <div class="tab-pane fade show active" id="list" role="tabpanel">
                    <form id="dynamicSearchForm" method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Fənn və ya Müəllim axtar.." name="search" id="dynamicSearch" value="<?php echo htmlspecialchars($searchQuery); ?>">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <select class="form-control" name="subject" id="dynamicSubject">
                                                <option value="">Bütün Fənnlər</option>
                                                <option value="Riyaziyyat" <?php echo ($filterSubject == 'Riyaziyyat') ? 'selected' : ''; ?>>Riyaziyyat</option>
                                                <option value="Fizika" <?php echo ($filterSubject == 'Fizika') ? 'selected' : ''; ?>>Fizika</option>
                                                <option value="Kimya" <?php echo ($filterSubject == 'Kimya') ? 'selected' : ''; ?>>Kimya</option>
                                                <option value="Biologiya" <?php echo ($filterSubject == 'Biologiya') ? 'selected' : ''; ?>>Biologiya</option>
                                                <option value="Tarix" <?php echo ($filterSubject == 'Tarix') ? 'selected' : ''; ?>>Tarix</option>
                                                <option value="Ədəbiyyat" <?php echo ($filterSubject == 'Ədəbiyyat') ? 'selected' : ''; ?>>Ədəbiyyat</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <select class="form-control" name="class" id="dynamicClass">
                                                <option value="">Bütün Siniflər</option>
                                                <?php
                                                $sql = "SELECT DISTINCT sinif FROM dersler WHERE sinif != '' ORDER BY sinif";
                                                $result = $conn->query($sql);

                                                if ($result && $result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        $selected = ($filterClass == $row['sinif']) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($row['sinif']) . "' $selected>" . htmlspecialchars($row['sinif']) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-block">
                                                <i class="fas fa-redo-alt mr-1"></i> Sıfırla
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lessons Table -->
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="list-tab" data-toggle="tab" href="#list" role="tab">
                            <i class="fas fa-list mr-2"></i> Dərslər
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="calendar-tab" data-toggle="tab" href="#calendar" role="tab" aria-controls="calendar" aria-selected="false">
                            <i class="fas fa-calendar-alt mr-2"></i> Təqvim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="today-tab" data-toggle="tab" href="#today" role="tab" aria-controls="today" aria-selected="false">
                            <i class="fas fa-calendar-day mr-2"></i> Bu gün
                        </a>
                    </li>
                </ul>
                <div class="tab-content mt-4" id="lessonTabsContent">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fənn</th>
                                    <th>Sinif</th>
                                    <th>Müəllim</th>
                                    <th>Tarix</th>
                                    <th>Vaxt</th>
                                    <th>Otaq</th>
                                    <th hidden>Şagird sayı</th>
                                    <th>Status</th>
                                    <th class="text-center">Əməliyyatlar</th>
                                </tr>
                            </thead>
                            <tbody id="lessonTableBody">
                                <?php
                                // Build the base SQL query
                                $query = "SELECT id, fenn, sinif, muellim, tarix, otaq, sagird_sayi, status, movzu, tesvir, materiallar, 
                                                DATE_FORMAT(start_time, '%H:%i') AS start_time,
                                                DATE_FORMAT(end_time, '%H:%i') AS end_time 
                                        FROM dersler 
                                        WHERE active_status = 1";

                                // Add search filter if a search query is provided
                                if (!empty($searchQuery)) {
                                    $query .= " AND (fenn LIKE '%" . mysqli_real_escape_string($conn, $searchQuery) . "%' 
                                                OR sinif LIKE '%" . mysqli_real_escape_string($conn, $searchQuery) . "%'
                                                OR muellim LIKE '%" . mysqli_real_escape_string($conn, $searchQuery) . "%'
                                                OR movzu LIKE '%" . mysqli_real_escape_string($conn, $searchQuery) . "%'
                                                OR tesvir LIKE '%" . mysqli_real_escape_string($conn, $searchQuery) . "%')";
                                }

                                // Add subject filter if selected
                                if (!empty($filterSubject)) {
                                    $query .= " AND fenn = '" . mysqli_real_escape_string($conn, $filterSubject) . "'";
                                }

                                // Add class filter if selected
                                if (!empty($filterClass)) {
                                    $query .= " AND sinif = '" . mysqli_real_escape_string($conn, $filterClass) . "'";
                                }

                                // Order the results by ID in descending order
                                $query .= " ORDER BY id DESC";

                                // Execute the query
                                $result = mysqli_query($conn, $query);

                                // Check if any lessons are found
                                if (!$result || mysqli_num_rows($result) == 0) {
                                    echo "<tr><td colspan='11' class='text-center'>Heç bir dərs tapılmadı</td></tr>";
                                } else {
                                    // Initialize the counter
                                    $count = 1;

                                    while ($row = mysqli_fetch_assoc($result)) {
                                        // Determine badge class based on status
                                        $statusLower = strtolower($row['status']);
                                        $statusClass = 'secondary';
                                        
                                        if ($statusLower == 'planlaşdırılıb' || $statusLower == 'planlasdirilib') {
                                            $statusClass = 'warning';
                                        } elseif ($statusLower == 'keçirilib' || $statusLower == 'kecirilib') {
                                            $statusClass = 'success';
                                        } elseif ($statusLower == 'ləğv edilib' || $statusLower == 'legv edilib') {
                                            $statusClass = 'danger';
                                        } elseif ($statusLower == 'dəyişiklik var' || $statusLower == 'deyisiklik var') {
                                            $statusClass = 'info';
                                        } elseif ($statusLower == 'aktiv') {
                                            $statusClass = 'aktiv';
                                        }

                                        // Escape special characters for HTML attributes
                                        $id = htmlspecialchars($row['id'], ENT_QUOTES);
                                        $fenn = htmlspecialchars($row['fenn'], ENT_QUOTES);
                                        $sinif = htmlspecialchars($row['sinif'], ENT_QUOTES);
                                        $muellim = htmlspecialchars($row['muellim'], ENT_QUOTES);
                                        $tarix = htmlspecialchars($row['tarix'], ENT_QUOTES);
                                        $start_time = htmlspecialchars($row['start_time'], ENT_QUOTES);
                                        $end_time = htmlspecialchars($row['end_time'], ENT_QUOTES);
                                        $otaq = htmlspecialchars($row['otaq'], ENT_QUOTES);
                                        $sagird_sayi = htmlspecialchars($row['sagird_sayi'], ENT_QUOTES);
                                        $status = htmlspecialchars($row['status'], ENT_QUOTES);
                                        $movzu = htmlspecialchars($row['movzu'] ?? '', ENT_QUOTES);
                                        $tesvir = htmlspecialchars($row['tesvir'] ?? '', ENT_QUOTES);
                                        $materiallar = htmlspecialchars($row['materiallar'] ?? '', ENT_QUOTES);

                                        echo "<tr>
                                                <td>{$count}</td>
                                                <td>{$fenn}</td>
                                                <td>{$sinif}</td>
                                                <td>{$muellim}</td>
                                                <td>{$tarix}</td>
                                                <td>{$start_time} - {$end_time}</td>
                                                <td>{$otaq}</td>
                                                <td hidden>{$sagird_sayi}</td>
                                                <td><span class='badge badge-{$statusClass}'>{$status}</span></td>
                                                <td class='text-center'>
                                                    <div class='actions'>
                                                        <a style='margin-right:5px;' href='#' class='btn btn-sm btn-info mr-1 view-lesson' data-id='{$id}' data-toggle='tooltip' title='Bax'>
                                                            <i class='fas fa-eye'></i>
                                                        </a>
                                                        <a style='margin-right:5px;' href='#' class='btn btn-sm btn-primary edit-lesson' data-id='{$id}' title='Redaktə et'>
                                                            <i class='fas fa-edit'></i>
                                                        </a>
                                                        <a href='#' class='btn btn-sm btn-danger delete-lesson' data-id='{$id}' data-toggle='tooltip' title='Sil'>
                                                            <i class='fas fa-trash'></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>";

                                        // Increment the counter
                                        $count++;
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('dersler/dersler_modals.php'); ?>

    <script>
const statTitles = {
    lessons: 'Ümumi Dərslər',
    teachers: 'Aktiv Müəllimlər',
    classes: 'Siniflər',
    today: 'Bu Günün Dərsləri'
};

   document.addEventListener("DOMContentLoaded", () => {
    // Hide preloader when the page is fully loaded
    const preloader = document.querySelector(".preloader");
    if (preloader) {
        preloader.style.display = "none";
    }

    // Initialize event listeners
    initializeEventListeners();

    // Initialize dynamic search
    initializeDynamicSearch();

    // Initialize calendar
    initializeCalendar();
});

function initializeEventListeners() {
    document.querySelectorAll('.stat-card-clickable').forEach((card) => {
        card.addEventListener('click', () => {
            openStatDetailsModal(card.dataset.statType);
        });
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openStatDetailsModal(card.dataset.statType);
            }
        });
    });

    // Create Lesson button
    const createLessonBtn = document.querySelector(".create-lesson");
    if (createLessonBtn) {
        createLessonBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const modal = document.getElementById("addLessonModal");
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        });
    }

    // Close modal buttons
    const closeModalBtns = document.querySelectorAll(".close-modal");
    closeModalBtns.forEach((btn) => {
        btn.addEventListener("click", function () {
            const modal = this.closest(".modal");
            if (typeof bootstrap !== "undefined") {
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            } else {
                console.error("Bootstrap is not defined. Ensure it is properly loaded.");
            }
        });
    });

    // Handle "New Class" option in the class dropdown
    const classSelect = document.getElementById("class");
    if (classSelect) {
        classSelect.addEventListener("change", function () {
            if (this.value === "new") {
                const addLessonModal = document.getElementById("addLessonModal");
                if (typeof bootstrap !== "undefined") {
                    const bootstrapAddLessonModal = bootstrap.Modal.getInstance(addLessonModal);
                    if (bootstrapAddLessonModal) {
                        bootstrapAddLessonModal.hide();
                    }
                }
                setTimeout(() => {
                    const newClassModal = document.getElementById("newClassModal");
                    const bootstrapNewClassModal = new bootstrap.Modal(newClassModal);
                    bootstrapNewClassModal.show();
                }, 500);
            }
        });
    }

    // Handle "New Room" option in the room dropdown
    const roomSelect = document.getElementById("room");
    if (roomSelect) {
        roomSelect.addEventListener("change", function () {
            if (this.value === "new") {
                const addLessonModal = document.getElementById("addLessonModal");
                if (typeof bootstrap !== "undefined") {
                    const bootstrapAddLessonModal = bootstrap.Modal.getInstance(addLessonModal);
                    if (bootstrapAddLessonModal) {
                        bootstrapAddLessonModal.hide();
                    }
                }
                setTimeout(() => {
                    const newRoomModal = document.getElementById("newRoomModal");
                    const bootstrapNewRoomModal = new bootstrap.Modal(newRoomModal);
                    bootstrapNewRoomModal.show();
                }, 500);
            }
        });
    }

    // View Lesson button clicks
    const viewLessonBtns = document.querySelectorAll(".view-lesson");
    if (viewLessonBtns) {
        viewLessonBtns.forEach((btn) => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const lessonId = btn.getAttribute("data-id");
                if (!lessonId) {
                    console.error("Lesson ID not found on button");
                    alert("Dərs ID-si tapılmadı.");
                    return;
                }
                viewLesson(lessonId);
            });
        });
    }

    // Edit Lesson button clicks
    const editLessonBtns = document.querySelectorAll(".edit-lesson");
    if (editLessonBtns) {
        editLessonBtns.forEach((btn) => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const lessonId = btn.getAttribute("data-id");
                if (!lessonId) {
                    console.error("Lesson ID not found on button");
                    alert("Dərs ID-si tapılmadı.");
                    return;
                }
                editLesson(lessonId);
            });
        });
    }

    // Save Edit Lesson button click
    const saveEditLessonBtn = document.getElementById("saveEditLesson");
    if (saveEditLessonBtn) {
        saveEditLessonBtn.addEventListener("click", (e) => {
            e.preventDefault();
            saveEditedLesson();
        });
    }

    // Delete Lesson button clicks
    const deleteLessonBtns = document.querySelectorAll(".delete-lesson");
    if (deleteLessonBtns) {
        deleteLessonBtns.forEach((btn) => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const lessonId = btn.getAttribute("data-id");
                if (!lessonId) {
                    console.error("Lesson ID not found on button");
                    alert("Dərs ID-si tapılmadı.");
                    return;
                }
                showDeleteConfirmation(lessonId);
            });
        });
    }

    // Delete confirmation checkbox
    const deleteConfirmCheckbox = document.getElementById("deleteConfirm");
    const confirmDeleteBtn = document.getElementById("confirmDelete");
    if (deleteConfirmCheckbox && confirmDeleteBtn) {
        deleteConfirmCheckbox.addEventListener("change", () => {
            confirmDeleteBtn.disabled = !deleteConfirmCheckbox.checked;
        });
    }

    // Confirm Delete button click
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener("click", (e) => {
            e.preventDefault();
            deleteLesson();
        });
    }

    // Calendar tab click
    const calendarTab = document.getElementById("calendar-tab");
    if (calendarTab) {
        calendarTab.addEventListener("click", (e) => {
            e.preventDefault();
            const calendarModal = document.getElementById("calendarModal");
            const bootstrapModal = new bootstrap.Modal(calendarModal);
            bootstrapModal.show();
            fetchCalendarData(new Date().getFullYear(), new Date().getMonth() + 1);
        });
    }

    // Today tab click
    const todayTab = document.getElementById("today-tab");
    if (todayTab) {
        todayTab.addEventListener("click", (e) => {
            e.preventDefault();
            const todayModal = document.getElementById("todayLessonsModal");
            const bootstrapModal = new bootstrap.Modal(todayModal);
            bootstrapModal.show();
            loadTodayLessons();
        });
    }
}

function initializeDynamicSearch() {
    const searchInput = document.getElementById("dynamicSearch");
    const subjectSelect = document.getElementById("dynamicSubject");
    const classSelect = document.getElementById("dynamicClass");
    const searchForm = document.getElementById("dynamicSearchForm");

    let searchTimeout;
    function delayedSubmit() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchForm.submit();
        }, 600);
    }

    if (searchInput) {
        searchInput.addEventListener("input", delayedSubmit);
    }

    if (subjectSelect) {
        subjectSelect.addEventListener("change", () => {
            searchForm.submit();
        });
    }

    if (classSelect) {
        classSelect.addEventListener("change", () => {
            searchForm.submit();
        });
    }
}

// Global variables for calendar
let calendarEvents = [];
let currentDate = new Date();

function initializeCalendar() {
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            fetchCalendarData(currentDate.getFullYear(), currentDate.getMonth() + 1);
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            fetchCalendarData(currentDate.getFullYear(), currentDate.getMonth() + 1);
        });
    }
}

   // View Lesson function
    function viewLesson(lessonId) {
        $.ajax({
            url: `dersler/dersler_operations.php?action=view&id=${lessonId}`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.status === 'error') {
                    alert('Dərs məlumatları tapılmadı: ' + data.message);
                    return;
                }

                const lesson = data.data;
                
                $('#lesson-header').text(`${lesson.fenn} - ${lesson.sinif}`);
                $('#lesson-date-time-room').text(`${lesson.tarix} | ${lesson.start_time} - ${lesson.end_time} | Otaq: ${lesson.otaq}`);
                $('#lesson-teacher').text(lesson.muellim || 'Müəllim qeyd edilməyib');
                $('#lesson-topic').text(lesson.movzu || 'Mövzu qeyd edilməyib');
                $('#lesson-student-count').text(lesson.sagird_sayi || 'Məlumat yoxdur');
                $('#lesson-status-text').text(lesson.status || 'Status qeyd edilməyib');
                $('#lesson-description').text(lesson.tesvir || 'Təsvir yoxdur');

                const statusBadge = $('#lesson-status-badge');
                statusBadge.text(lesson.status || 'Bilinmir');
                statusBadge.removeClass().addClass(`badge badge-${getStatusClass(lesson.status)}`);

                const materialsContainer = $('#lesson-materials');
                materialsContainer.empty();
                if (lesson.materiallar && lesson.materiallar.length > 0) {
                    lesson.materiallar.split(',').forEach(material => {
                        const materialLink = $(`<a href="uploads/${material.trim()}" target="_blank" class="d-block">${material.trim()}</a>`);
                        materialsContainer.append(materialLink);
                    });
                } else {
                    materialsContainer.text('Material yoxdur');
                }

                $('#viewLessonModal').modal('show');
            },
            error: function(xhr, status, error) {
                alert('Dərs məlumatlarını əldə etmək mümkün olmadı: ' + error);
            }
        });
    }



    // Edit Lesson function
    function editLesson(lessonId) {
        $.ajax({
            url: `dersler/dersler_operations.php?action=view&id=${lessonId}`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.status === 'error') {
                    alert('Dərs məlumatları tapılmadı: ' + data.message);
                    return;
                }

                const lesson = data.data;
                
                $('#lessonId').val(lesson.id || '');
                $('#editSubject').val(mapSubjectToId(lesson.fenn) || '');
                $('#editClass').val(mapClassToId(lesson.sinif) || '');
                $('#editTeacher').val(mapTeacherToId(lesson.muellim) || '');
                $('#editRoom').val(mapRoomToId(lesson.otaq) || '');
                $('#editDate').val(lesson.tarix || '');
                $('#editStartTime').val(lesson.start_time || '');
                $('#editEndTime').val(lesson.end_time || '');
                $('#editTopic').val(lesson.movzu || '');
                $('#editDescription').val(lesson.tesvir || '');
                $('#editStatus').val(lesson.status || '');

                $('#editLessonModal').modal('show');
            },
            error: function(xhr, status, error) {
                alert('Dərs məlumatlarını əldə etmək mümkün olmadı: ' + error);
            }
        });
    }

    // Save Edited Lesson function
    function saveEditedLesson() {
        const formData = new FormData($('#editLessonForm')[0]);
        
        $.ajax({
            url: 'dersler/dersler_operations.php?action=edit',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    alert('Dərs uğurla yeniləndi!');
                    $('#editLessonModal').modal('hide');
                    location.reload();
                } else {
                    alert('Dərs yenilənərkən xəta baş verdi: ' + (data.error || 'Bilinməyən xəta'));
                }
            },
            error: function(xhr, status, error) {
                alert('Dərs yenilənərkən xəta baş verdi: ' + error);
            }
        });
    }
// Show Delete Confirmation function
let lessonIdToDelete = null;
function showDeleteConfirmation(lessonId) {
    lessonIdToDelete = lessonId;

    // Reset the confirmation checkbox and disable the delete button
    const deleteConfirmCheckbox = document.getElementById("deleteConfirm");
    const confirmDeleteBtn = document.getElementById("confirmDelete");
    deleteConfirmCheckbox.checked = false;
    confirmDeleteBtn.disabled = true;

    // Show the modal
    const modal = document.getElementById("deleteLessonModal");
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// Delete Lesson function
function deleteLesson() {
    if (!lessonIdToDelete) {
        console.error("No lesson ID set for deletion");
        alert("Silinəcək dərs ID-si tapılmadı.");
        return;
    }

    fetch("dersler/dersler_operations.php?action=delete", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: lessonIdToDelete }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                alert("Dərs uğurla silindi!");
                // Close the modal
                const modal = document.getElementById("deleteLessonModal");
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                bootstrapModal.hide();
                // Refresh the page to reflect the deletion
                location.reload();
            } else {
                alert("Dərs silinərkən xəta baş verdi: " + (data.error || "Bilinməyən xəta"));
            }
        })
        .catch((error) => {
            console.error("Error deleting lesson:", error);
            alert("Dərs silinərkən xəta baş verdi: " + error.message);
        });
}

// Fetch Calendar Data function
async function fetchCalendarData(year, month) {
    const calendarGrid = document.querySelector('.calendar-grid');
    if (!calendarGrid) return;

    showLoading(calendarGrid);

    try {
        const response = await fetch(`dersler/dersler_operations.php?action=calendar&il=${year}&ay=${month}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const data = await response.json();
        
        hideLoading(calendarGrid);

        if (data.error) {
            console.error('Error fetching calendar data:', data.error);
            showError(calendarGrid, data.error);
            return;
        }

        calendarEvents = data;
        generateCalendar();
    } catch (error) {
        console.error('Fetch error:', error);
        hideLoading(calendarGrid);
        showError(calendarGrid, 'Təqvim məlumatlarını yükləyərkən xəta baş verdi.');
    }
}

// Generate Calendar function
function generateCalendar() {
    const monthNames = ["Yanvar", "Fevral", "Mart", "Aprel", "May", "İyun", "İyul", "Avqust", "Sentyabr", "Oktyabr", "Noyabr", "Dekabr"];
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const calendarGrid = document.querySelector('.calendar-grid');
    
    if (!calendarGrid) return;

    document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    let firstDayOfWeek = firstDay.getDay() === 0 ? 7 : firstDay.getDay();

    calendarGrid.innerHTML = `
        <div class="calendar-day-header">B.</div>
        <div class="calendar-day-header">B.e</div>
        <div class="calendar-day-header">Ç.a</div>
        <div class="calendar-day-header">Ç.</div>
        <div class="calendar-day-header">C.a</div>
        <div class="calendar-day-header">C.</div>
        <div class="calendar-day-header">Ş.</div>
    `;

    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = 1; i < firstDayOfWeek; i++) {
        calendarGrid.appendChild(createDayElement('other-month', prevMonthLastDay - firstDayOfWeek + i + 1));
    }

    const today = new Date();
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const dayElement = createDayElement('',
            i,
            year === today.getFullYear() && month === today.getMonth() && i === today.getDate() ? 'today' : ''
        );
        
        const currentDateStr = formatDate(year, month + 1, i);
        const dayEvents = calendarEvents.filter(event => event.tarix.split(' ')[0] === currentDateStr);

        dayEvents.forEach(event => {
            const eventElement = createEventElement(event);
            dayElement.appendChild(eventElement);
        });

        calendarGrid.appendChild(dayElement);
    }

    const totalCells = Math.ceil((firstDayOfWeek - 1 + lastDay.getDate()) / 7) * 7;
    const remainingCells = totalCells - (firstDayOfWeek - 1 + lastDay.getDate());
    for (let i = 1; i <= remainingCells; i++) {
        calendarGrid.appendChild(createDayElement('other-month', i));
    }

    if (typeof $ !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }
}

// Load Today's Lessons function
async function loadTodayLessons() {
    const todayLessonsList = document.getElementById('todayLessonsList');
    if (!todayLessonsList) return;

    const today = new Date();
    document.getElementById('todayDate').textContent = formatDateAzerbaijani(today);
    todayLessonsList.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Yüklənir...</p></div>';

    try {
        const response = await fetch('dersler/dersler_operations.php?action=today');
        if (!response.ok) throw new Error('Network response was not ok');
        const lessons = await response.json();
        
        todayLessonsList.innerHTML = '';
        document.getElementById('todayLessonCount').textContent = lessons.length;

        if (lessons.length === 0) {
            todayLessonsList.innerHTML = `
                <div class="no-lessons-today">
                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                    <h5>Bu gün üçün dərs tapılmadı</h5>
                </div>
            `;
            return;
        }

        const now = new Date();
        const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

        lessons.forEach(lesson => {
            const lessonCard = createDetailedLessonCard(lesson, currentTime);
            todayLessonsList.appendChild(lessonCard);
        });
    } catch (error) {
        console.error('Error fetching today\'s lessons:', error);
        todayLessonsList.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Dərsləri yükləyərkən xəta baş verdi.
            </div>
        `;
    }
}

// Helper functions
function showLoading(container) {
    const loader = document.createElement('div');
    loader.id = 'calendar-loader';
    loader.className = 'text-center my-4';
    loader.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Yüklənir...</span></div>';
    container.appendChild(loader);
}

function hideLoading(container) {
    const loader = container.querySelector('#calendar-loader');
    if (loader) loader.remove();
}

function showError(container, message) {
    const errorMsg = document.createElement('div');
    errorMsg.className = 'alert alert-danger m-3';
    errorMsg.textContent = message;
    container.appendChild(errorMsg);
}

function createDayElement(className, dayNumber, additionalClass = '') {
    const dayElement = document.createElement('div');
    dayElement.className = `calendar-day ${className} ${additionalClass}`;
    dayElement.innerHTML = `<span class="day-number">${dayNumber}</span>`;
    return dayElement;
}

function createEventElement(event) {
    const statusClasses = {
        'Aktiv': 'event-active',
        'Planlaşdırılıb': 'event-planned',
        'Ləğv edilib': 'event-cancelled',
        'Dəyişiklik var': 'event-changed',
        'Bitib': 'event-completed'
    };

    const startTime = formatTime(event.start_time);
    const endTime = formatTime(event.end_time);
    const eventClass = `calendar-event ${statusClasses[event.status] || ''}`;
    
    const tooltipContent = `
        ${event.fenn} - ${event.sinif}
        ${startTime} - ${endTime}
        ${event.movzu ? 'Mövzu: ' + event.movzu : ''}
        Müəllim: ${event.muellim}
        Otaq: ${event.otaq}
        Status: ${event.status}
    `;

    const eventElement = document.createElement('div');
    eventElement.className = eventClass;
    eventElement.setAttribute('data-toggle', 'tooltip');
    eventElement.setAttribute('data-html', 'true');
    eventElement.title = tooltipContent;
    eventElement.textContent = `${startTime} - ${event.fenn} (${event.sinif})`;
    return eventElement;
}

function createDetailedLessonCard(lesson, currentTime) {
    const statusClasses = {
        'Aktiv': 'active',
        'Planlaşdırılıb': 'planned',
        'Ləğv edilib': 'cancelled',
        'Dəyişiklik var': 'changed',
        'Bitib': 'completed'
    };

    const badgeClasses = {
        'Aktiv': 'success',
        'Planlaşdırılıb': 'primary',
        'Ləğv edilib': 'danger',
        'Dəyişiklik var': 'blue',
        'Bitib': 'secondary'
    };

    const lessonCard = document.createElement('div');
    lessonCard.className = `card today-lesson-card ${statusClasses[lesson.status] || ''} mb-3`;
    
    let materialsHtml = '';
    if (lesson.materials && lesson.materials.length > 0) {
        materialsHtml = `
            <div class="mt-2">
                <small class="text-muted">Materiallar:</small>
                <ul class="list-unstyled mb-0">
                    ${lesson.materials.map(file => `<li><i class="fas fa-file mr-1"></i>${file}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    lessonCard.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">${lesson.subject} - ${lesson.class}</h5>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><i class="fas fa-user-tie mr-2"></i> ${lesson.teacher}</p>
                    <p class="mb-1 lesson-time"><i class="fas fa-clock mr-2"></i> ${lesson.startTime} - ${lesson.endTime}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><i class="fas fa-door-open mr-2"></i> Otaq ${lesson.room}</p>
                </div>
            </div>
            ${materialsHtml}
        </div>
    `;
    return lessonCard;
}

function formatDate(year, month, day) {
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

function formatTime(timeStr) {
    return timeStr ? timeStr.substring(0, 5) : '';
}

function formatDateAzerbaijani(date) {
    const day = date.getDate();
    const month = date.getMonth();
    const year = date.getFullYear();
    const azMonths = ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'İyun', 'İyul', 'Avqust', 'Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr'];
    return `${day} ${azMonths[month]} ${year}`;
}

function getStatusClass(status) {
    if (!status) return "secondary"; // Fallback if status is undefined or null
    status = status.toLowerCase();
    if (status === "planlaşdırılıb" || status === "planlasdirilib") {
        return "warning"; // Yellow (#FFC107)
    } else if (status === "keçirilib" || status === "kecirilib") {
        return "success"; // Green (Bootstrap default)
    } else if (status === "ləğv edilib" || status === "legv edilib") {
        return "danger"; // Red (#DC3545)
    } else if (status === "dəyişiklik var" || status === "deyisiklik var") {
        return "info"; // Blue (#007BFF)
    } else if (status === "aktiv") {
        return "aktiv"; // Custom class for Aktiv (Green #28A745)
    } else if (status === "bitib") {
        return "secondary"; // Gray (#6C757D)
    } else {
        return "secondary"; // Default fallback
    }
}

function mapSubjectToId(subject) {
    const subjectMap = {
        "Riyaziyyat": "1",
        "Fizika": "2",
        "Kimya": "3",
        "Biologiya": "4",
        "Tarix": "5",
        "Ədəbiyyat": "6"
    };
    return subjectMap[subject] || "";
}

function mapClassToId(className) {
    const classSelect = document.getElementById("editClass");
    for (let option of classSelect.options) {
        if (option.text === className) {
            return option.value;
        }
    }
    return "";
}

function mapTeacherToId(teacherName) {
    const teacherSelect = document.getElementById("editTeacher");
    for (let option of teacherSelect.options) {
        if (option.text === teacherName) {
            return option.value;
        }
    }
    return "";
}

function mapRoomToId(roomName) {
    const roomSelect = document.getElementById("editRoom");
    for (let option of roomSelect.options) {
        if (option.text === roomName) {
            return option.value;
        }
    }
    return "";
}

function openStatDetailsModal(type) {
    const titleEl = document.getElementById('statDetailsTitle');
    const loadingEl = document.getElementById('statDetailsLoading');
    const contentEl = document.getElementById('statDetailsContent');
    const emptyEl = document.getElementById('statDetailsEmpty');
    const headEl = document.getElementById('statDetailsHead');
    const bodyEl = document.getElementById('statDetailsBody');
    const modalEl = document.getElementById('statDetailsModal');

    if (!modalEl) {
        return;
    }

    if (titleEl) titleEl.textContent = statTitles[type] || 'Məlumatlar';
    if (loadingEl) loadingEl.classList.remove('d-none');
    if (contentEl) contentEl.classList.add('d-none');
    if (emptyEl) emptyEl.classList.add('d-none');
    if (headEl) headEl.innerHTML = '';
    if (bodyEl) bodyEl.innerHTML = '';

    bootstrap.Modal.getOrCreateInstance(modalEl).show();

    fetch('dersler/dersler_operations.php?action=stat_details&type=' + encodeURIComponent(type))
        .then((response) => response.json())
        .then((data) => {
            if (loadingEl) loadingEl.classList.add('d-none');

            if (data.status === 'success' && data.data && data.data.length > 0) {
                renderStatDetailsTable(data.columns, data.data);
                if (contentEl) contentEl.classList.remove('d-none');
            } else if (data.status === 'success') {
                if (emptyEl) emptyEl.classList.remove('d-none');
            } else {
                alert(data.message || 'Məlumat tapılmadı');
                if (emptyEl) emptyEl.classList.remove('d-none');
            }
        })
        .catch(() => {
            if (loadingEl) loadingEl.classList.add('d-none');
            if (emptyEl) emptyEl.classList.remove('d-none');
            alert('Məlumatları yükləmək mümkün olmadı');
        });
}

function renderStatDetailsTable(columns, rows) {
    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const headEl = document.getElementById('statDetailsHead');
    const bodyEl = document.getElementById('statDetailsBody');
    if (!headEl || !bodyEl) {
        return;
    }

    let headHtml = '<tr>';
    columns.forEach((column) => {
        headHtml += '<th>' + escapeHtml(column.label) + '</th>';
    });
    headHtml += '</tr>';
    headEl.innerHTML = headHtml;

    let bodyHtml = '';
    rows.forEach((row) => {
        bodyHtml += '<tr>';
        columns.forEach((column) => {
            bodyHtml += '<td>' + escapeHtml(row[column.key] ?? '-') + '</td>';
        });
        bodyHtml += '</tr>';
    });
    bodyEl.innerHTML = bodyHtml;
}
    </script>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
</body>
</html>