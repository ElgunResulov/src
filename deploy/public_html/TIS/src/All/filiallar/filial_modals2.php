<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<div class="modal fade" id="teacherModal" tabindex="-1" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog" role="document" style="max-width: 700px;">
        <form id="teacher-update-form">
            <div class="modal-content" style="min-height: 300px;">
                <div class="modal-header">
                    <h5 class="modal-title">Müəllim Məlumatı</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="teacher_id" id="modal-teacher-id">
                    <p style="margin-bottom: 1rem; font-weight: 500;">Seçilmiş Müəllim: <strong><span
                                id="modal-username"></span></strong></p>
                    <input type="hidden" name="username" id="modal-username-hidden">

                    <div class="form-group mb-3">
                        <label for="modal-filial-selects"
                            style="display: block; margin-bottom: 10px; font-weight: 500;">Filiallar:</label>
                        <div id="modal-filial-selects-container">
                            <!-- Филиалы будут загружены динамически -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Saxla
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="schedule-modal" class="modal">
    <div class="modal-content" style="max-width: 1200px; margin: 2% auto;">
        <div class="modal-header">
            <h3 id="schedule-modal-title">Cədvəl Təyini</h3>
            <button class="modal-close" type="button" onclick="closeScheduleModal()">&times;</button>
        </div>
        <br>

        <div style="width:90%;margin:0% auto;" class="form-group">
            <label>Filial seçin</label>
        </div>
        <div style="width:90%;margin:0% auto;margin-bottom:25px;" class="form-group">
            <select id="filial-select" onchange="handleFilialSelection()">
                <option value="">Filial seçin...</option>
                <!-- Options will be populated by JavaScript -->
            </select>
        </div>

        <div class="modal-body" id="schedule-modal-body">
            <div class="custom-time-section"
                style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 15px; color: #495057;">
                    <i class="fas fa-clock"></i> Xüsusi Vaxt Əlavə Et
                </h4>
                <div style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 120px;">
                        <label for="custom-time-input"
                            style="display: block; margin-bottom: 5px; font-weight: 500;">Vaxt:</label>
                        <input type="time" id="custom-time-input" class="form-control" style="padding: 8px;">
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <label for="custom-day-select"
                            style="display: block; margin-bottom: 5px; font-weight: 500;">Gün:</label>
                        <select id="custom-day-select" class="form-control" style="padding: 8px;">
                            <option value="">Gün seçin</option>
                            <option value="Bazar ertəsi">Bazar ertəsi</option>
                            <option value="Çərşənbə axşamı">Çərşənbə axşamı</option>
                            <option value="Çərşənbə">Çərşənbə</option>
                            <option value="Cümə axşamı">Cümə axşamı</option>
                            <option value="Cümə">Cümə</option>
                            <option value="Şənbə">Şənbə</option>
                            <option value="Bazar">Bazar</option>
                        </select>
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <label for="custom-note-input"
                            style="display: block; margin-bottom: 5px; font-weight: 500;">Qeyd (İstəyə bağlı):</label>
                        <input type="text" id="custom-note-input" class="form-control" placeholder="Qeyd əlavə edin"
                            style="padding: 8px;">
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" onclick="addCustomTimeSlot()"
                            style="padding: 8px 16px;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="schedule-loading" style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin"></i> Cədvəl yüklənir...
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-success yadda_saxla" onclick="saveSchedule()"><i class="fas fa-save"></i> Yadda Saxla</button>
            <button class="btn btn-warning temizle" onclick="clearSchedule()"><i class="fas fa-eraser"></i> Təmizlə</button>
            <button class="btn btn-secondary bagla" onclick="closeScheduleModal()"><i class="fas fa-times"></i> Bağla</button>
        </div>
    </div>
</div>

<div id="schedule-display-modal" class="modal">
    <div class="modal-content" style="max-width: 1200px; margin: 2% auto;">
        <div class="modal-header">
            <h3 id="schedule-display-modal-title">Müəllim Cədvəli</h3>
            <button class="modal-close" type="button" onclick="closeScheduleDisplayModal()">&times;</button>
        </div>
        <div class="modal-body" id="schedule-display-modal-body">
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeScheduleDisplayModal()">
                <i class="fas fa-times"></i> Bağla
            </button>
        </div>
    </div>
</div>

<div id="cedvel-schedule-modal" class="modal">
    <div class="modal-content" style="max-width: 1200px; margin: 2% auto;">
        <div class="modal-header">
            <h3 id="cedvel-schedule-modal-title">Cədvəl Təyini</h3>
            <button class="modal-close" type="button" onclick="closeCedvelScheduleModal()">&times;</button>
        </div>
        <div class="modal-body" id="cedvel-schedule-modal-body">
            <div class="custom-time-section"
                style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 15px; color: #495057;">
                    <i class="fas fa-clock"></i> Xüsusi Vaxt Əlavə Et
                </h4>
                <div style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 120px;">
                        <label for="cedvel-custom-time-input"
                            style="display: block; margin-bottom: 5px; font-weight: 500;">Vaxt:</label>
                        <input type="time" id="cedvel-custom-time-input" class="form-control" style="padding: 8px;">
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <label for="cedvel-custom-day-select"
                            style="display: block; margin-bottom: 5px; font-weight: 500;">Gün:</label>
                        <select id="cedvel-custom-day-select" class="form-control" style="padding: 8px;">
                            <option value="">Gün seçin</option>
                            <option value="Bazar ertəsi">Bazar ertəsi</option>
                            <option value="Çərşənbə axşamı">Çərşənbə axşamı</option>
                            <option value="Çərşənbə">Çərşənbə</option>
                            <option value="Cümə axşamı">Cümə axşamı</option>
                            <option value="Cümə">Cümə</option>
                            <option value="Şənbə">Şənbə</option>
                            <option value="Bazar">Bazar</option>
                        </select>
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <label for="cedvel-custom-note-input"
                            style="display: block; margin-bottom: 5px; font-weight: 500;">Qeyd (İstəyə bağlı):</label>
                        <input type="text" id="cedvel-custom-note-input" class="form-control"
                            placeholder="Qeyd əlavə edin" style="padding: 8px;">
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" onclick="addCedvelCustomTimeSlot()"
                            style="padding: 8px 16px;">
                            <i class="fas fa-plus"></i> Əlavə Et
                        </button>
                    </div>
                </div>
            </div>

            <div id="cedvel-schedule-loading" style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin"></i> Cədvəl yüklənir...
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-success" onclick="saveCedvelSchedule()">
                <i class="fas fa-save"></i> Yadda Saxla
            </button>
            <button class="btn btn-warning" onclick="clearCedvelSchedule()">
                <i class="fas fa-eraser"></i> Təmizlə
            </button>
            <button class="btn btn-secondary" onclick="closeCedvelScheduleModal()">
                <i class="fas fa-times"></i> Bağla
            </button>
        </div>
    </div>
</div>

<div id="filial-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="filial-modal-title">Filial Təfərrüatları</h3>
            <button class="modal-close" type="button" onclick="closeFilialModal()">&times;</button>
        </div>
        <div class="modal-body" id="filial-modal-body">
        </div>
    </div>
</div>

<div class="main-wrapper">
    <nav class="nav-container">
        <div class="nav-tabs">
            <button class="nav-tab active" data-tab="filials">
                <i class="fas fa-building"></i>
                <span>Filiallar</span>
            </button>
            <button class="nav-tab" data-tab="teachers">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Müəllimlər</span>
            </button>
            <button hidden class="nav-tab" data-tab="cedvel">
                <i class="fas fa-calendar-alt"></i>
                <span>Cədvəl</span>
            </button>
            <button class="nav-tab" data-tab="students">
                <i class="fas fa-user-graduate"></i>
                <span>Tələbələr</span>
            </button>
        </div>
    </nav>

    <main class="content">
        <!-- Filials Section -->
         <br><br>
        <section id="filials" class="tab-content active">
            <div class="page-header">
                <h2></h2>
                <p></p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Yeni Filial Əlavə Et</h3>
                </div>
                <div class="card-body">
                    <form id="filial-form" class="form">
                        <div class="form-grid">
                            <div class="form-group">
                                <input type="text" name="filial-name" id="filial-name"
                                    placeholder="Filial adını daxil edin" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="filial-address" id="filial-address"
                                    placeholder="Ünvanı daxil edin" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="filial-phone" id="filial-phone"
                                    placeholder="Telefon nömrəsini daxil edin" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Filial Əlavə Et
                        </button>
                    </form>
                </div>
            </div>
            <div class="grid" id="filials-grid">
                <!-- Loading state -->
                <div class="loading-grid">
                    <div class="loading-item">
                        <div class="loading-title"></div>
                        <div class="loading-line"></div>
                        <div class="loading-line"></div>
                        <div class="loading-line"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Teachers Section -->
        <section id="teachers" class="tab-content">
            <div class="page-header">
                <h2></h2>
                <p></p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Müəllimi Yeniləyin</h3>
                </div>
                <div class="card-body">
                    <!-- <h5>Filial seçin</h5> -->
                    <div class="teacher-grid" id="teacher-usernames-grid">
                        <!-- Teacher usernames will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h3>Müəllimlər | Cədvəl yaratmaq</h3>
                    <div class="teacher-grid new-teacher-grid" id="teacher-usernames-card">
                        <!-- Teacher cards will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="grid" id="teachers-grid"></div>
        </section>

        <!-- Cedvel Section -->
        <section id="cedvel" class="tab-content">
            <div class="page-header">
                <h2></h2>
                <p></p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Cədvəl Görüntüləmə</h3>
                </div>
                <div class="card-body">
                    <!-- Step 1: Filial Selection -->
                    <label for="filial_select" class="filial-select-label"
                        style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Filial Seçin:</label>
                    <div class="form-group mb-3">
                        <select name="filial" id="filial_select" class="form-control"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem;">
                            <option value="">Seçin...</option>
                        </select>
                    </div>
                    <!-- Step 2: Subject Selection (Initially Hidden) -->
                    <div id="fenn_selection_container" style="display: none;">
                        <label for="fenn_select" class="fenn-select-label"
                            style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Fənn Seçin:</label>
                        <div class="form-group mb-3">
                            <select name="fenn" id="fenn_select" class="form-control"
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem;">
                                <option value="">Seçin...</option>
                            </select>
                        </div>
                    </div>
                    <div id="teacher_selection_container" style="display: none;">
                        <label for="teacher_select" class="teacher-select-label"
                            style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Müəllim Seçin:</label>
                        <div class="form-group mb-3">
                            <select name="teacher" id="teacher_select" class="form-control"
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem;">
                                <option value="">Seçin...</option>
                            </select>
                        </div>
                    </div>
                    <div id="action_buttons_container" style="display: none;">
                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-primary" id="load-schedule-btn"
                                onclick="loadSelectedTeacherScheduleModal()" style="display: none;">
                                <i class="fas fa-calendar-check"></i> Cədvəl Təyin Et
                            </button>
                            <button class="btn btn-info btn-cedvel" id="view-schedule-btn"
                                onclick="viewSelectedTeacherSchedule()">
                                <i class="fas fa-eye"></i> Cədvəl Bax
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<section id="students" class="tab-content">
    <?php
        include('db.php'); // Assuming db.php contains your database connection ($conn)
        
        $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
        $results = [];
        
        // Get all students
        if ($search_query) {
            $stmt = $conn->prepare("SELECT * FROM telebeler WHERE username LIKE ? OR muellim_adi LIKE ? ORDER BY username");
            $search_param = '%' . $search_query . '%';
            $stmt->bind_param("ss", $search_param, $search_param);
        } else {
            $stmt = $conn->prepare("SELECT * FROM telebeler ORDER BY username");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $student_username = trim($row['username']);
                $schedule_data = [];
                
                // Get ALL schedules from muellimler_new table - NO LIMITS
                // Filter out empty/null telebeler JSON directly in the query for efficiency
                $schedule_stmt = $conn->prepare("SELECT username as teacher_name, telebeler FROM muellimler_new WHERE telebeler IS NOT NULL AND telebeler != '' AND telebeler != '[]' AND telebeler != 'null'");
                $schedule_stmt->execute();
                $schedule_result = $schedule_stmt->get_result();
                
                if ($schedule_result) {
                    while ($schedule_row = $schedule_result->fetch_assoc()) {
                        $teacher_name = $schedule_row['teacher_name'];
                        $telebeler_json = $schedule_row['telebeler'];
                        
                        // error_log("Processing teacher: " . $teacher_name . " JSON: " . $telebeler_json); // Uncomment for debugging
                        $decoded_data = json_decode($telebeler_json, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                            foreach ($decoded_data as $schedule_item) {
                                if (is_array($schedule_item) && count($schedule_item) >= 4) {
                                    $item_filial = isset($schedule_item[0]) ? trim($schedule_item[0]) : '';
                                    $item_time = isset($schedule_item[1]) ? trim($schedule_item[1]) : '';
                                    $item_day = isset($schedule_item[2]) ? trim($schedule_item[2]) : '';
                                    $item_student = isset($schedule_item[3]) ? trim($schedule_item[3]) : '';
                                    
                                    if (strcasecmp($item_student, $student_username) === 0) {
                                        $schedule_data[] = [
                                            'filial' => $item_filial,
                                            'time' => $item_time,
                                            'day' => $item_day,
                                            'student_username' => $item_student,
                                            'teacher_name' => $teacher_name
                                        ];
                                        // error_log("Found match for student: " . $student_username . " with teacher: " . $teacher_name); // Uncomment for debugging
                                    }
                                }
                            }
                        } else {
                            // error_log("JSON decode error for teacher " . $teacher_name . ": " . json_last_error_msg()); // Uncomment for debugging
                        }
                    }
                }
                $schedule_stmt->close();
                
                // error_log("Total schedule items found for " . $student_username . ": " . count($schedule_data)); // Uncomment for debugging
                $row['schedule'] = $schedule_data;
                $row['schedule_count'] = count($schedule_data);
                $results[] = $row;
            }
        }
        $stmt->close();
        
        $sample_usernames = [];
        $stmt_samples = $conn->prepare("SELECT DISTINCT username FROM telebeler ORDER BY username");
        $stmt_samples->execute();
        $result_samples = $stmt_samples->get_result();
        if ($result_samples) {
            while ($row = $result_samples->fetch_assoc()) {
                $sample_usernames[] = $row['username'];
            }
        }
        $stmt_samples->close();
        $total_students = count($results);
    ?>
    <div class="page-header">
        <h2> </h2>
        <p></p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Tələbə Axtarışı</h3>
            <small class="text-muted">Cəmi <?php echo $total_students; ?> tələbə tapıldı</small>
        </div>
        <div class="card-body">
            <form method="GET" action="" id="search-form">
                <div class="input-group">
                    <input type="text" class="form-control" 
                           name="search" 
                           id="student-search"
                           placeholder="Tələbə adı və ya müəllim adı ilə axtarın..."
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           list="username-suggestions">
                    <datalist id="username-suggestions">
                        <?php foreach ($sample_usernames as $username): ?>
                        <option value="<?php echo htmlspecialchars($username); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <div style="margin-left:8px;" class="input-group-append">
                        <button hidden class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Axtar
                        </button>
                        <?php if ($search_query): ?>
                        <a href="?" class="btn btn-secondary temizle">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>
                <?php if ($search_query): ?>
                "<?php echo htmlspecialchars($search_query); ?>" üçün nəticələr
                <?php else: ?>
                Bütün Tələbələr
                <?php endif; ?>
            </h3>
            <span style="font-size:16px;" class="text-muted badge badge-primary badge-lg"><?php echo $total_students; ?> tələbə</span>
        </div>
        <div class="card-body">
            <?php if (count($results) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tələbə Adı</th>
                            <th hidden>Dərs Sayı</th>
                            <th>Filiallar</th>
                            <th>Müəllimlər</th>
                            <th>Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $student): ?>
                        <tr>
                            <td>
                                <strong class="text-primary">
                                    <?php echo htmlspecialchars($student['username']); ?>
                                </strong>
                            </td>
                            <td hidden width="130px;">
                                <?php if (!empty($student['schedule'])): ?>
                                    <span class="text-muted badge badge-success badge-lg">
                                        <?php echo count($student['schedule']); ?> dərs
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Dərs yoxdur</span>
                                <?php endif; ?>
                            </td>
                            <td width="340px;">
                                <?php if (!empty($student['schedule'])): ?>
                                    <?php 
                                    $all_filials = array_unique(array_column($student['schedule'], 'filial'));
                                    foreach ($all_filials as $filial): ?>
                                        <span class="text-muted badge badge-info mr-1 mb-1">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($filial); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <small class="text-muted"><?php echo count($all_filials); ?> filial</small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td width="280px;">
                                <?php if (!empty($student['schedule'])): ?>
                                    <?php 
                                    $all_teachers = array_unique(array_column($student['schedule'], 'teacher_name'));
                                    $display_teachers = array_slice($all_teachers, 0, 3);
                                    foreach ($display_teachers as $teacher): ?>
                                        <span class="text-muted badge badge-warning mr-1 mb-1">
                                            <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($teacher); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($all_teachers) > 3): ?>
                                        <span class="badge badge-light">+<?php echo count($all_teachers) - 3; ?></span>
                                    <?php endif; ?>
                                    <small class="text-muted"><?php echo count($all_teachers); ?> müəllim</small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($student['schedule'])): ?>
                                <button type="button" 
                                        class="mb-2 btn btn-info btn-sm cedvel_telebe" 
                                        onclick="showCompleteSchedule(<?php echo htmlspecialchars(json_encode($student['schedule']), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars($student['username']); ?>')"
                                        title="Tam cədvəli göstər">
                                    <i class="fas fa-calendar-alt"></i> Cədvəl
                                </button>
                                <?php endif; ?>
                                <button type="button" 
                                        class="mb-2 btn btn-success btn-sm telebe_add" 
                                        onclick="schedule_openModal(this)" 
                                        data-username="<?php echo htmlspecialchars($student['username']); ?>"
                                        title="Cədvələ əlavə et">
                                    <i class="fas fa-plus"></i> Əlavə
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle fa-3x mt-2 mb-3"></i>
                <?php if ($search_query): ?>
                <p>"<?php echo htmlspecialchars($search_query); ?>" sorğusu üçün heç bir tələbə tapılmadı.</p>
                <a href="?" class="btn btn-primary">
                    <i class="fas fa-list"></i> Bütün tələbələri göstər
                </a>
                <?php else: ?>
                <p>Verilənlər bazasında tələbə məlumatı yoxdur.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php $conn->close(); ?>
    
    <!-- Custom Schedule Modal -->
    <div id="customScheduleModal" class="custom-modal-overlay" style="display: none;">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h5 id="customScheduleModalLabel" class="custom-modal-title"></h5>
                <button type="button" class="custom-modal-close-btn" onclick="closeCustomScheduleModal()">
                    &times;
                </button>
            </div>
            <div class="custom-modal-body">
                <div id="customScheduleContent">
                    <!-- Schedule content will be loaded here -->
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCustomScheduleModal()">
                    <i class="fas fa-times"></i> Bağla
                </button>
                <button type="button" class="btn btn-primary" onclick="printSchedule()">
                    <i class="fas fa-print"></i> Çap et
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Custom Modal Styles */
        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 14050; /* Higher than Bootstrap's default modal z-index */
        }

        .custom-modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 86%; /* Similar to Bootstrap modal-lg */
            max-height: 90vh; /* Limit height to prevent overflow */
            display: flex;
            flex-direction: column;
        }

        .custom-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }

        .custom-modal-title {
            margin-bottom: 0;
            line-height: 1.5;
            font-size: 1.25rem;
            color: #333;
        }

        .custom-modal-close-btn {
            padding: 0;
            background: none;
            border: none;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            opacity: .5;
            cursor: pointer;
            transition: opacity .15s ease-in-out;
        }

        .custom-modal-close-btn:hover {
            opacity: .75;
        }

        .custom-modal-body {
            padding: 20px;
            flex-grow: 1; /* Allow body to take available space */
            overflow-y: auto; /* Make body scrollable */
        }

        .custom-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background-color: #f8f9fa;
        }

        /* Prevent body scrolling when modal is open */
        body.modal-open {
            overflow: hidden;
        }

        /* Existing schedule styles (from previous response) */
        .student-header {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .filial-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .filial-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
        }
        
        .teacher-section {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .teacher-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .schedule-item {
            transition: all 0.2s ease;
        }
        
        .schedule-item:hover {
            transform: translateX(5px);
            background-color: #e8f5e8 !important;
        }
        
        .day-info, .time-info {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .schedule-container {
            /* max-height: 70vh; This will be handled by custom-modal-body overflow-y */
        }
        
        .schedule-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .schedule-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .schedule-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .schedule-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
    
    <script>
function showCompleteSchedule(scheduleData, studentName) {
    try {
        if (!scheduleData || scheduleData.length === 0) {
            alert('❌ Bu tələbə üçün cədvəl məlumatı tapılmadı.');
            return;
        }
        
        // Sort schedule data
        const dayOrder = ['Bazar ertəsi', 'Çərşənbə axşamı', 'Çərşənbə', 'Cümə axşamı', 'Cümə', 'Şənbə', 'Bazar'];
        
        scheduleData.sort((a, b) => {
            const dayA = dayOrder.indexOf(a.day) !== -1 ? dayOrder.indexOf(a.day) : 999;
            const dayB = dayOrder.indexOf(b.day) !== -1 ? dayOrder.indexOf(b.day) : 999;
            if (dayA !== dayB) return dayA - dayB;
            
            const timeA = a.time || '';
            const timeB = b.time || '';
            if (timeA !== timeB) return timeA.localeCompare(timeB);
            
            return 0;
        });

        // Group by filial first, then by teacher, then by day
        const filialGroups = {};
        scheduleData.forEach(session => {
            const filial = session.filial || 'Filial yoxdur';
            const teacher = session.teacher_name || 'Naməlum müəllim';
            const day = session.day || 'Gün yoxdur';
            
            if (!filialGroups[filial]) {
                filialGroups[filial] = {};
            }
            if (!filialGroups[filial][teacher]) {
                filialGroups[filial][teacher] = {};
            }
            if (!filialGroups[filial][teacher][day]) {
                filialGroups[filial][teacher][day] = [];
            }
            filialGroups[filial][teacher][day].push(session);
        });

        // Create modal content
        let modalContent = `
            <div class="text-center mb-4">
                <div class="student-header p-3 rounded" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
                    <h4 class="mb-2 text-primary"><i class="fas fa-user-graduate"></i> ${studentName}</h4>
                    <span class="text-muted badge badge-success badge-lg">Cəmi ${scheduleData.length} dərs</span>
                </div>
            </div>
            
            <div class="schedule-container">
                <div class="row">
        `;

        const filials = Object.keys(filialGroups);
        const colClass = filials.length === 1 ? 'col-12' : 
                        filials.length === 2 ? 'col-md-6' : 'col-md-4';

        filials.forEach((filial, filialIndex) => {
            const colors = [
                { bg: '#fff3cd', border: '#ffeaa7', text: '#856404' },
                { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' },
                { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
                { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
                { bg: '#e2e3e5', border: '#d6d8db', text: '#383d41' }
            ];
            const color = colors[filialIndex % colors.length];
            const filialId = `filial-${filialIndex}`;

            modalContent += `
                <div class="${colClass} mb-4">
                    <div class="filial-card h-100 rounded shadow-sm" style="border: 1px solid ${color.border};">
                        <div class="filial-header p-3 rounded-top" style="background-color: ${color.bg}; cursor: pointer;" data-toggle="collapse" data-target="#${filialId}">
                            <h5 class="mb-0" style="color: ${color.text};">
                                <i class="fas fa-folder mr-2"></i> ${filial}
                                <i class="fas fa-chevron-down float-right"></i>
                            </h5>
                        </div>
                        
                        <div id="${filialId}" class="collapse filial-content p-3" style="background-color: white;">
            `;

            // Display teachers and their schedules for this filial
            Object.keys(filialGroups[filial]).forEach((teacher, teacherIndex) => {
                const teacherDays = filialGroups[filial][teacher];
                const teacherId = `teacher-${filialIndex}-${teacherIndex}`;
                
                modalContent += `
                    <div class="teacher-section mb-0">
                        <div class="teacher-name p-2 rounded" style="background-color: #e3f2fd; border-left: 4px solid #2196f3; cursor: pointer;" data-toggle="collapse" data-target="#${teacherId}">
                            <strong style="color: #1976d2;">
                                <i class="fas fa-folder mr-2"></i> ${teacher}
                                <i class="fas fa-chevron-down float-right"></i>
                            </strong>
                            <small class="text-muted ml-2">(${Object.values(teacherDays).flat().length} dərs)</small>
                        </div>
                        
                        <div id="${teacherId}" class="collapse teacher-schedule mt-2">
                `;

                // Group by day and show times below each day
                Object.keys(teacherDays).forEach((day, dayIndex) => {
                    const daySessions = teacherDays[day];
                    const dayId = `day-${filialIndex}-${teacherIndex}-${dayIndex}`;
                    
                    modalContent += `
                        <div class="day-section mb-2">
                            <div class="day-header p-2 rounded" style="background-color: #f8f9fa; border-left: 3px solid #28a745; cursor: pointer;" data-toggle="collapse" data-target="#${dayId}">
                                <i class="fas fa-folder mr-2 text-info"></i>
                                <strong style="color: #495057; font-size: 0.9em;">${day}</strong>
                                <i class="fas fa-chevron-down float-right"></i>
                            </div>
                            <div id="${dayId}" class="collapse times-list pl-4 mt-1">
                    `;

                    daySessions.forEach(session => {
                        const time = session.time || 'Vaxt yoxdur';
                        modalContent += `
                            <div class="time-item mb-1">
                                <i class="fas fa-clock text-success"></i>
                                <span style="color: #28a745; font-weight: 500; font-size: 0.9em;">${time}</span>
                            </div>
                        `;
                    });

                    modalContent += `
                            </div>
                        </div>
                    `;
                });

                modalContent += `
                        </div>
                    </div>
                `;
            });

            modalContent += `
                        </div>
                    </div>
                </div>
            `;
        });

        modalContent += `
                </div>
            </div>
        `;

        // Set modal content and show
        document.getElementById('customScheduleContent').innerHTML = modalContent;
        document.getElementById('customScheduleModalLabel').innerHTML = 
            `<i class="fas fa-calendar-alt"></i> ${studentName} - Dərs Cədvəli`;
        
        // Store data for printing
        window.currentScheduleData = {
            studentName: studentName,
            scheduleData: scheduleData
        };
        
        // Show custom modal
        document.getElementById('customScheduleModal').style.display = 'flex';
        document.body.classList.add('modal-open'); // Prevent body scrolling
        
        // Add event listeners for chevron rotation
        document.querySelectorAll('[data-toggle="collapse"]').forEach(element => {
            element.addEventListener('click', function() {
                const chevron = this.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.classList.toggle('fa-rotate-180');
                }
            });
        });

    } catch (error) {
        console.error('Error in showCompleteSchedule:', error);
        alert(`❌ Cədvəl göstərilərkən xəta baş verdi:\n${error.message}\n\nKonsolu yoxlayın.`);
    }
}

        function closeCustomScheduleModal() {
            document.getElementById('customScheduleModal').style.display = 'none';
            document.body.classList.remove('modal-open'); // Re-enable body scrolling
        }
        
        // This function is a placeholder for your "add schedule" modal logic.
        function schedule_openModal(buttonElement) {
            const username = buttonElement.dataset.username;
            alert(`'Əlavə et' düyməsi basıldı. Tələbə: ${username}. Bu funksiyanı öz əlavə cədvəl modalınızla əvəz edin.`);
            console.log(`Opening schedule modal for student: ${username}`);
            // You would typically open another custom modal here for adding schedules.
            // Example: document.getElementById('addScheduleModal').style.display = 'flex';
        }

        function printSchedule() {
            if (!window.currentScheduleData) {
                alert('Çap etmək üçün məlumat yoxdur');
                return;
            }
            
            const printWindow = window.open('', '_blank');
            const { studentName, scheduleData } = window.currentScheduleData;
            
            let printContent = `
                <html>
                <head>
                    <title>${studentName} - Dərs Cədvəli</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { text-align: center; color: #333; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
                        .summary div { text-align: center; }
                        .filial-group-print { margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
                        .filial-group-print h4 { margin-top: 0; color: #555; }
                        .teacher-section-print { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
                        .teacher-section-print:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
                        .schedule-item-print { margin-bottom: 5px; }
                    </style>
                </head>
                <body>
                    <h1>${studentName} - Dərs Cədvəli</h1>
                    <p style="text-align: center;">Cəmi ${scheduleData.length} dərs</p>
            `;

            // Group by filial first, then by teacher for printing
            const filialGroupsPrint = {};
            scheduleData.forEach(session => {
                const filial = session.filial || 'Filial yoxdur';
                const teacher = session.teacher_name || 'Naməlum müəllim';
                
                if (!filialGroupsPrint[filial]) {
                    filialGroupsPrint[filial] = {};
                }
                if (!filialGroupsPrint[filial][teacher]) {
                    filialGroupsPrint[filial][teacher] = [];
                }
                filialGroupsPrint[filial][teacher].push(session);
            });

            Object.keys(filialGroupsPrint).forEach(filial => {
                printContent += `
                    <div class="filial-group-print">
                        <h4>🏢 ${filial} filialı</h4>
                `;
                Object.keys(filialGroupsPrint[filial]).forEach(teacher => {
                    printContent += `
                        <div class="teacher-section-print">
                            <strong>👨‍🏫 Müəllim: ${teacher}</strong> (${filialGroupsPrint[filial][teacher].length} dərs)<br>
                    `;
                    filialGroupsPrint[filial][teacher].forEach((session, index) => {
                        printContent += `
                            <div class="schedule-item-print">
                                ${index + 1}. 📅 ${session.day || 'Gün yoxdur'} - ⏰ ${session.time || 'Vaxt yoxdur'}
                            </div>
                        `;
                    });
                    printContent += `</div>`;
                });
                printContent += `</div>`;
            });

            printContent += `
                </body>
                </html>
            `;
            
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }
        
        document.getElementById('student-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const studentName = row.querySelector('td:first-child strong').textContent.toLowerCase();
                const visible = studentName.includes(searchTerm);
                row.style.display = visible ? '' : 'none';
            });
        });
        
        document.getElementById('student-search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('search-form').submit();
            }
        });
    </script>
</section>
    </main>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">
                    <input type="text" class="form-control" id="modalUsernameInput" readonly>
                </h5>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="mb-3">
                        <label for="filialSelect" class="form-label">Filial</label>
                        <select class="form-control" id="filialSelect">
                            <option value="">Filial seçin...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subjectSelect" class="form-label">Fənn</label>
                        <select class="form-control" id="subjectSelect" disabled>
                            <option value="">Fənn seçin...</option>
                        </select>
                    </div>
                    <div class="mb-3" id="teacherContainer">
                        <label class="form-label">Müəllim</label>
                        <select class="form-control" id="teacherSelect1" disabled>
                            <option value="">Müəllim seçin...</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button hidden type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="schedule_saveForm()">Təsdiq</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="d-none modal-title" id="scheduleModalLabel">Cədvəl | Müəllim <span id="scheduleTeacherName"></span>
                </h5>
                <input type="hidden" id="scheduleTeacherId">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="scheduleGrid"></div>
            </div>
            <div class="modal-footer">
                <button hidden type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Schedule Grid Styles */
    .schedule-grid {
        display: grid;
        grid-template-columns: 80px repeat(7, 1fr);
        gap: 5px;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        margin-top: 15px;
    }

    /* Schedule Display Grid Styles */
    .schedule-display-grid {
        display: grid;
        grid-template-columns: 80px repeat(7, 1fr);
        gap: 4px;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
    }

    .schedule-display-header {
        background: linear-gradient(135deg, rgb(98, 121, 223));
        color: white;
        padding: 0.75rem 0.5rem;
        text-align: center;
        font-weight: bold;
        border-radius: 0.5rem;
        font-size: 0.86rem;
        word-wrap: break-word;
        position: relative;
        top: 0rem;
        /* Align with grid padding-top */
        z-index: 10;
        /* Ensure headers stay above cells */
        background-clip: padding-box;
        /* Prevent background overflow issues */
    }

    .schedule-display-time {
        background: #e2e8f0;
        padding: 1rem 0.5rem;
        text-align: center;
        font-weight: 600;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        color: #2d3748;
        position: sticky;
        left: 0;
        z-index: 10;
    }

    .schedule-display-cell {
        background: white;
        border: 2px solid #e9ecef;
        padding: 12px 8px;
        text-align: center;
        border-radius: 4px;
        min-height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .schedule-display-cell.occupied {
        background: linear-gradient(135deg, rgb(34, 176, 136));
        border: 2px solid #e9ecef;
        padding: 12px 8px;
        text-align: center;
        border-radius: 8px;
        color: white;
        min-height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .schedule-display-cell.with-note {
        background: #17a2b8;
        color: white;
        border-color: #117a8b;
    }

    /* Custom Time Section Styles */
    .custom-time-section {
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .custom-time-section h4 {
        margin-bottom: 15px;
        font-size: 1.1rem;
    }

    .custom-time-section .form-control {
        border: 1px solid #ced4da;
        border-radius: 4px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .custom-time-section .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* No Data State */
    .no-data {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
        font-size: 1.1rem;
    }

    .no-data i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>