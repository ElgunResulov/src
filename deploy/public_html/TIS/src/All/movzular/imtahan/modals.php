<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include('db.php');

$sql = "SELECT u_id, id, exam_name, fenn_adi, sinif, description, exam_date, duration, passing_score, groups, questions, status, movzular, sual_secimi, sual_sayi, cetinlik_seviyyesi, created_at 
        FROM imtahanlar_exam WHERE u_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $u_id);
$stmt->execute();
$result = $stmt->get_result();
$exams = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['questions']) && $row['questions'] !== "[]") {
            $question_ids = json_decode($row['questions'], true);
            if (is_array($question_ids) && !empty($question_ids)) {
                $placeholders = implode(', ', array_fill(0, count($question_ids), '?'));
                $sql_questions = "SELECT question_text FROM sual_banki WHERE id IN ($placeholders)";
                $stmt_questions = $conn->prepare($sql_questions);
                $stmt_questions->bind_param(str_repeat('i', count($question_ids)), ...array_map('intval', $question_ids));
                $stmt_questions->execute();
                $result_questions = $stmt_questions->get_result();
                $question_names = [];
                while ($q_row = $result_questions->fetch_assoc()) {
                    $clean_text = str_replace(['<p>', '</p>'], '', $q_row['question_text']);
                    $question_names[] = $clean_text;
                }
                $stmt_questions->close();
                $row['questions'] = implode(", ", $question_names) ?: "-";
            } else {
                $row['questions'] = "-";
            }
        } else {
            $row['questions'] = "-";
        }
        $exams[] = $row;
    }
    $result->free();
}
$stmt->close();

// Fetch subjects for filter
$sql = "SELECT fenn_adi FROM fennler_new";
$subjects_result = $conn->query($sql);
$subjects = [];
if ($subjects_result) {
    while ($row = $subjects_result->fetch_assoc()) {
        $subjects[] = $row['fenn_adi'];
    }
    $subjects_result->free();
}
$conn->close();

// Function to format JSON fields
function formatJsonField($field, $column = '') {
    if (empty($field) || $field === "[]") return "-";
    try {
        $parsed = json_decode($field, true);
        if (is_array($parsed)) {
            if (empty($parsed)) return "-";
            if ($column === 'movzular' && isset($parsed[0]) && is_array($parsed[0])) {
                $output = [];
                foreach ($parsed as $item) {
                    $output[] = $item['movzu_adi'];
                }
                return implode(", ", $output);
            }
            if (isset($parsed[0]) && is_array($parsed[0])) {
                $output = [];
                foreach ($parsed as $item) {
                    $output[] = "fenn: {$item['fenn_adi']}, movzu: {$item['movzu_adi']}";
                }
                return implode(", ", $output);
            }
            return implode(", ", $parsed);
        }
        return $field;
    } catch (Exception $e) {
        return $field;
    }
}

// Function to format dates
function formatDate($dateStr) {
    $date = new DateTime($dateStr);
    return $date->format('d-m-Y H:i');
}

// Function to translate sual_secimi
function formatSualSecimi($sual_secimi) {
    if (empty($sual_secimi)) return "-";
    switch (strtolower($sual_secimi)) {
        case 'random':
            return "Təsadüfi Seç";
        case 'manual':
            return "Əl ilə Seç";
        default:
            return "-";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">
    <style>
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .bg-primary { background-color: #0d6efd; }
        .bg-success { background-color: #198754; }
        .bg-warning { background-color: #ffc107; }
        .bg-secondary { background-color: #6c757d; }
        .form-group {
            width: 100%;
            margin-bottom: 15px;
        }
        .form-group .form-control,
        .form-group .form-select {
            width: 100%;
            box-sizing: border-box;
        }
        .d-flex.gap-10 {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
            align-items: center;
        }
        .d-flex.gap-10 .form-group {
            flex: 1 1 200px;
            min-width: 0;
        }
        .tab-content { width: 100%; }
        .table-container {
            width: 100%;
            overflow-x: auto;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        .modal-dialog-lg { max-width: 800px; }
        .modal-dialog-xl { max-width: 1200px; }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            border-bottom: 2px solid #007bff;
        }
        .ql-container {
            min-height: 150px;
            width: 100%;
        }
        .description-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .modal { z-index: 1060 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        .modal-body p { margin-bottom: 10px; }
        .modal-body strong { display: inline-block; width: 150px; }






        



        .custom-bg-primary {
            background-color: rgba(103, 74, 209, 0);
        }
        .custom-text-light {
            color: rgba(98, 98, 202, 0.8);
        }
        .custom-shadow-large {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .custom-padding-large {
            padding: 1.5rem;
        }
        .custom-bg-light {
            background-color: #f8f9fa;
        }
        .custom-card {
            border: 1px solid rgba(0, 0, 0, 0);
            border-radius: 0.45rem;
        }
        .custom-shadow-small {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0);
        }
        .custom-card-content {
            padding: 0rem;
        }
        .custom-list-group {
            display: flex;
            flex-direction: column;
        }
        .custom-list-item {
            display: flex;
            margin-bottom: 8px;
            padding: 0.75rem 1.25rem;
            border: 2px solid rgba(210, 210, 210, 0.37);
            background-color: #fff;
        }
        .custom-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        .custom-col-half {
            flex: 0 0 auto;
            width: 50%;
            padding-right: 15px;
            padding-left: 15px;
        }
        .custom-margin-bottom {
            margin-bottom: 1rem;
        }
        .custom-margin-bottom-small {
            margin-bottom: 0.5rem;
        }
        .custom-margin-right {
            margin-right: 0.4rem;
        }
        .custom-margin-right-small {
            margin-right: 0.25rem;
        }
        .custom-text-break {
            word-break: break-word;
        }
        .custom-btn {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        .custom-btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #fff;
        }
        .custom-btn-outline-secondary {
            background-color: transparent;
            border-color: #6c757d;
            color: #6c757d;
        }
        .custom-accordion-item {
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .custom-accordion-btn {
            padding: 1rem 1.25rem;
            font-size: 1rem;
            background-color: #fff;
            border: none;
            width: 100%;
            text-align: left;
        }
        .custom-accordion-content {
            padding: 1rem 1.25rem;
        }
        .custom-table-container {
            overflow-x: auto;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }
        .custom-table th,
        .custom-table td {
            padding: 0.75rem;
            border: 1px solid #dee2e6;
        }
        .custom-table th {
            background-color: #f8f9fa;
        }
        .custom-table tr:hover {
            background-color: #f1f1f1;
        }
        .custom-table-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .modal-dialog {
            min-width: 1000px;
            max-width: 90%;
            margin: 1rem auto;
        }
        @media (min-width: 577px) and (max-width: 992px) {

            .modal-dialog {
                min-width: 700px;
                max-width: 95%;
            }
            .custom-padding-large {
                padding: 1rem;
            }
            .custom-card-content {
                padding: 0.75rem;
            }
            .custom-list-item {
                padding: 0.235rem 1rem;
                font-size: 0.25rem;
            }
            .custom-list-item i {
                font-size: 0.69rem;
                margin-right: 6px;
            }
            .custom-list-item strong,
            .custom-list-item span {
                font-size: 0.6rem;
            }
            .custom-margin-right {
                margin-right: 0.1rem;
            }
            .custom-margin-right-small {
                margin-right: 0.1rem;
            }
            .custom-accordion-btn {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }
            .custom-accordion-content {
                padding: 0.75rem 1rem;
            }
            .custom-btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.9rem;
            }
            .custom-table {
                font-size: 0.5rem;
            }
            .custom-table th,
            .custom-table td {
                padding: 0.5rem;
            }
            .custom-table-btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
        }
        @media (max-width: 576px) {

            .modal-dialog {
                min-width: 300px;
                max-width: 100%;
                margin: 0.5rem auto;
            }
            .custom-padding-large {
                padding: 0.22rem;
            }
            .custom-card-content {
                padding: 0.22rem;
            }
            .custom-col-half {
                width: 100%;
                padding-right: 0px;
                padding-left: 0px;
            }
            .custom-list-item {
                display: flex;
                align-items: center;
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
                flex-direction: row;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .custom-list-item i {
                font-size: 0.75rem;
                margin-right: 0.32rem;
                flex-shrink: 0;
            }
            .custom-list-item strong,
            .custom-list-item span {
                font-size: 0.55rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .custom-text-break {
                word-break: normal;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .custom-margin-right {
                margin-right: 0rem;
            }
            .custom-margin-right-small {
                margin-right: -10px;
            }
            .custom-accordion-btn {
                padding: 0.75rem 0.85rem;
                font-size: 0.75rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .custom-accordion-content {
                padding: 0.85rem 0.75rem;
            }
            .custom-btn {
                display: inline-block;
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            .modal-header h4 {
                font-size: 1rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 0rem;
            }
        }

        .movzular{
           transform:scale(0.8);
        }
        .suallar{
            transform:scale(0.8);
        }
    </style>
</head>
<body>
    <!-- Exams Section -->
    <div class="section" id="exams">
        <div class="d-flex justify-content-between align-items-center mb-20">
            <h1></h1>
            <button class="btn btn-primary" onclick="openModalForInsertWithJs()"> 
                <i class="fas fa-plus"></i> Yeni İmtahan 
            </button>
        </div>
        
        <div class="card mb-20">
            <div class="card-header">
                <h3 class="card-title">İmtahanlar Siyahısı</h3>
            </div>
            <div class="card-body">
                <div class="form-group d-flex gap-10">
                    <select class="form-select" id="examSubjectFilter" onchange="filterExams()">
                        <option value="">Bütün Fənlər</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select" id="examStatusFilter" onchange="filterExams()">
                        <option value="">Bütün Statuslar</option>
                        <option value="upcoming">Gələcək</option>
                        <option value="active">Aktiv</option>
                        <option value="completed">Tamamlanmış</option>
                    </select>
                    <input type="text" class="form-control" id="examSearchInput" placeholder="Axtar..." oninput="filterExams()">
                </div>

                <div class="table-container">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th hidden>ID</th>
                                <th>İmtahan Adı</th>
                                <th>Fənn Adı</th>
                                <th hidden>Sinif</th>
                                <th>Təsvir</th>
                                <th>Tarix - Saat</th>
                                <th>Müddət (dəq)</th>
                                <th hidden>Keçid Balı (%)</th>
                                <th hidden>Qruplar</th>
                                <th hidden>Suallar</th>
                                <th hidden>Mövzular</th>
                                <th hidden>Sual Seçimi</th>
                                <th>Status</th>
                                <th hidden>Sual Sayı</th>
                                <th hidden>Çətinlik Səviyyəsi</th>
                                <th hidden>Yaradılma Tarixi</th>
                                <th>Əməliyyatlar</th>
                            </tr>
                        </thead>
                        <tbody id="examTableBody">
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td hidden><?php echo htmlspecialchars($exam['id']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                    <td><?php echo htmlspecialchars(formatJsonField($exam['fenn_adi'])); ?></td>
                                    <td hidden><?php echo htmlspecialchars($exam['sinif'] ?: '-'); ?></td>
                                    <td class="description-cell"><?php echo htmlspecialchars($exam['description'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars(formatDate($exam['exam_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($exam['duration']); ?></td>
                                    <td hidden><?php echo htmlspecialchars($exam['passing_score']); ?></td>
                                    <td hidden><?php echo htmlspecialchars($exam['groups'] ?: '-'); ?></td>
                                    <td hidden><?php echo htmlspecialchars(formatJsonField($exam['questions'])); ?></td>
                                    <td hidden><?php echo htmlspecialchars(formatJsonField($exam['movzular'], 'movzular')); ?></td>
                                    <td hidden><?php echo htmlspecialchars(formatSualSecimi($exam['sual_secimi'])); ?></td>
                                    <td>
                                        <?php
                                        switch (strtolower($exam['status'])) {
                                            case 'upcoming':
                                                echo '<span class="badge bg-primary">Gələcək</span>';
                                                break;
                                            case 'active':
                                                echo '<span class="badge bg-warning">Aktiv</span>';
                                                break;
                                            case 'completed':
                                                echo '<span class="badge bg-success">Tamamlanmış</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">Bilinməyən</span>';
                                        }
                                        ?>
                                    </td>
                                    <td hidden><?php echo htmlspecialchars($exam['sual_sayi'] ?: '-'); ?></td>
                                    <td hidden>
                                        <?php
                                        // Difficulty level translation
                                        $difficultyMap = [
                                            'easy' => 'Asan',
                                            'medium' => 'Orta',
                                            'hard' => 'Çətin'
                                        ];
                                        $difficulty = formatJsonField($exam['cetinlik_seviyyesi']) ?: '-';
                                        if ($difficulty !== '-') {
                                            // Decode JSON if formatJsonField doesn't already do it
                                            $difficultyArray = is_string($difficulty) ? json_decode($difficulty, true) : $difficulty;
                                            if (is_array($difficultyArray)) {
                                                $translatedDifficulties = array_map(function($level) use ($difficultyMap) {
                                                    $normalizedLevel = strtolower(trim($level));
                                                    return $difficultyMap[$normalizedLevel] ?? $level;
                                                }, $difficultyArray);
                                                $translatedDifficulty = implode(', ', $translatedDifficulties);
                                            } else {
                                                // Handle single value
                                                $normalizedDifficulty = strtolower(trim($difficulty));
                                                $translatedDifficulty = $difficultyMap[$normalizedDifficulty] ?? $difficulty;
                                            }
                                        } else {
                                            $translatedDifficulty = '-';
                                        }
                                        echo htmlspecialchars($translatedDifficulty);
                                        ?>
                                    </td>
                                    <td hidden><?php echo htmlspecialchars(formatDate($exam['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-info mb-1" onclick="viewExam(<?php echo $exam['id']; ?>)"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-primary mb-1" onclick="editExam(<?php echo $exam['id']; ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-primary mb-1" onclick="print(<?php echo $exam['id']; ?>)"><i class="fas fa-print"></i></button>
                                        <button hidden class="btn btn-sm btn-info mb-1" onclick="copy(<?php echo $exam['id']; ?>)"><i class="fas fa-copy"></i></button>
                                        <button class="btn btn-sm btn-danger mb-1" onclick="deleteExam(<?php echo $exam['id']; ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="questionsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="questionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content custom-shadow-large">
                <div class="modal-header custom-bg-primary custom-text-light">
                    <h4 class="modal-title" id="questionsModalLabel"><i hidden class="fas fa-question-circle custom-margin-right-small"></i>Sual Detalları</h4>
                </div>
                <div class="modal-body custom-padding-large custom-bg-light">
                    <div class="custom-card custom-shadow-small">
                        <div class="custom-card-content">
                            <div class="custom-list-group">
                                <div class="custom-list-item">
                                    <strong class="custom-margin-bottom-small d-block">Suallar:</strong>
                                    <ul id="modal-questions-details" class="custom-text-break list-group list-group-numbered"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="custom-btn custom-btn-secondary" data-bs-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="movzularModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="movzularModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content custom-shadow-large">
                <div class="modal-header custom-bg-primary custom-text-light">
                    <h4 class="modal-title" id="movzularModalLabel"><i hidden class="fas fa-tags custom-margin-right-small"></i>Mövzu Detalları</h4>
                </div>
                <div class="modal-body custom-padding-large custom-bg-light">
                    <div class="custom-card custom-shadow-small">
                        <div class="custom-card-content">
                            <div class="custom-list-group">
                                <div class="custom-list-item">
                                    <strong class="custom-margin-bottom-small d-block">Mövzular:</strong>
                                    <ul id="modal-movzular-details" class="custom-text-break list-group list-group-numbered"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="custom-btn custom-btn-secondary" data-bs-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php foreach ($exams as $exam): ?>
        <div class="modal fade" id="examModal-<?php echo $exam['id']; ?>" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="examModalLabel-<?php echo $exam['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content custom-shadow-large">
                    <div class="modal-header custom-bg-primary custom-text-light">
                        <h4 class="modal-title" id="examModalLabel-<?php echo $exam['id']; ?>"><i hidden class="fas fa-clipboard-list custom-margin-right-small"></i>İmtahan Detalları</h4>
                    </div>
                    <div class="modal-body custom-padding-large custom-bg-light">
                        <div class="accordion" id="examDetailsAccordion-<?php echo $exam['id']; ?>">
                            <!-- Əsas Məlumatlar -->
                            <div class="custom-accordion-item custom-margin-bottom">
                                <h2 class="accordion-header" id="headingBasicInfo-<?php echo $exam['id']; ?>">
                                    <button class="custom-text-light custom-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBasicInfo-<?php echo $exam['id']; ?>" aria-expanded="true" aria-controls="collapseBasicInfo-<?php echo $exam['id']; ?>">
                                        <i hidden class="fas fa-info-circle custom-margin-right-small"></i> Əsas Məlumatlar
                                    </button>
                                </h2>
                                <div id="collapseBasicInfo-<?php echo $exam['id']; ?>" class="accordion-collapse collapse show" aria-labelledby="headingBasicInfo-<?php echo $exam['id']; ?>" data-bs-parent="#examDetailsAccordion-<?php echo $exam['id']; ?>">
                                    <div class="custom-accordion-content">
                                        <div class="custom-card custom-shadow-small">
                                            <div class="custom-card-content">
                                                <div class="custom-row">
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <div>
                                                                <i hidden class="fas fa-id-badge custom-margin-right"></i>
                                                                <strong hidden class="custom-margin-right-small">ID:</strong>
                                                                <span hidden id="modal-exam-id-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-book custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">İmtahan Adı:</strong>
                                                                <span id="modal-exam-name-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-graduation-cap custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Fənn Adı:</strong>
                                                                <span id="modal-exam-fenn-adi-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-school custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Sinif:</strong>
                                                                <span id="modal-exam-sinif-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-info-circle custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Təsvir:</strong>
                                                                <span id="modal-exam-description-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- İmtahan Parametrləri -->
                            <div class="custom-accordion-item custom-margin-bottom">
                                <h2 class="accordion-header" id="headingExamParams-<?php echo $exam['id']; ?>">
                                    <button class="custom-text-light custom-accordion-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExamParams-<?php echo $exam['id']; ?>" aria-expanded="false" aria-controls="collapseExamParams-<?php echo $exam['id']; ?>">
                                        <i hidden class="fas fa-cog custom-margin-right-small"></i> İmtahan Parametrləri
                                    </button>
                                </h2>
                                <div id="collapseExamParams-<?php echo $exam['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingExamParams-<?php echo $exam['id']; ?>" data-bs-parent="#examDetailsAccordion-<?php echo $exam['id']; ?>">
                                    <div class="custom-accordion-content">
                                        <div class="custom-card custom-shadow-small">
                                            <div class="custom-card-content">
                                                <div class="custom-row">
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-calendar-alt custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Tarix - Saat:</strong>
                                                                <span id="modal-exam-date-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-clock custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Müddət (dəq):</strong>
                                                                <span id="modal-exam-duration-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-percentage custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Keçid Balı:</strong>
                                                                <span id="modal-exam-passing-score-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-users custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Qruplar:</strong>
                                                                <span id="modal-exam-groups-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Sual Konfiqurasiyası -->
                            <div class="custom-accordion-item custom-margin-bottom">
                                <h2 class="accordion-header" id="headingQuestionConfig-<?php echo $exam['id']; ?>">
                                    <button class="custom-text-light custom-accordion-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseQuestionConfig-<?php echo $exam['id']; ?>" aria-expanded="false" aria-controls="collapseQuestionConfig-<?php echo $exam['id']; ?>">
                                        <i hidden class="fas fa-question-circle custom-margin-right-small"></i> Sual Konfiqurasiyası
                                    </button>
                                </h2>
                                <div id="collapseQuestionConfig-<?php echo $exam['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingQuestionConfig-<?php echo $exam['id']; ?>" data-bs-parent="#examDetailsAccordion-<?php echo $exam['id']; ?>">
                                    <div class="custom-accordion-content">
                                        <div class="custom-card custom-shadow-small">
                                            <div class="custom-card-content">
                                                <div class="custom-row">
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <?php if (formatSualSecimi($exam['sual_secimi']) !== 'Təsadüfi Seç'): ?>
                                                                <!-- Suallar Section -->
                                                                <div id="suallar-section-<?php echo $exam['id']; ?>" class="custom-list-item d-flex align-items-center">
                                                                    <i hidden class="fas fa-question custom-margin-right"></i>
                                                                    <strong class="custom-margin-right-small">Suallar:</strong>
                                                                    <span id="modal-exam-questions-count-<?php echo $exam['id']; ?>" class="custom-margin-right-small"></span>
                                                                    <button style="margin-left:12px;" id="open-more-modal-<?php echo $exam['id']; ?>" class="suallar btn btn-primary custom-margin-left-small">Ətraflı</button>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-tags custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Mövzular:</strong>
                                                                <span id="modal-exam-movzular-count-<?php echo $exam['id']; ?>" class="custom-margin-right-small"></span>
                                                                <button style="margin-left:12px;" id="open-more-movzular-modal-<?php echo $exam['id']; ?>" class="movzular btn btn-primary custom-margin-left-small">Ətraflı</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-random custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Sual Seçimi:</strong>
                                                                <span id="modal-exam-sual-secimi-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-list-ol custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Sual Sayı:</strong>
                                                                <span id="modal-exam-sual-sayi-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                            <?php if (formatSualSecimi($exam['sual_secimi']) !== 'Əl ilə Seç'): ?>
                                                                <!-- Çətinlik Səviyyəsi Section -->
                                                                <div id="cetinlik-section-<?php echo $exam['id']; ?>" class="custom-list-item d-flex align-items-center">
                                                                    <i hidden class="fas fa-chart-bar custom-margin-right"></i>
                                                                    <strong class="custom-margin-right-small">Çətinlik Səviyyəsi:</strong>
                                                                    <span id="modal-exam-cetinlik-seviyyesi-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Status və Tarix -->
                            <div class="custom-accordion-item custom-margin-bottom">
                                <h2 class="accordion-header" id="headingStatusDate-<?php echo $exam['id']; ?>">
                                    <button class="custom-text-light custom-accordion-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatusDate-<?php echo $exam['id']; ?>" aria-expanded="false" aria-controls="collapseStatusDate-<?php echo $exam['id']; ?>">
                                        <i hidden class="fas fa-info custom-margin-right-small"></i> Status və Tarix
                                    </button>
                                </h2>
                                <div id="collapseStatusDate-<?php echo $exam['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingStatusDate-<?php echo $exam['id']; ?>" data-bs-parent="#examDetailsAccordion-<?php echo $exam['id']; ?>">
                                    <div class="custom-accordion-content">
                                        <div class="custom-card custom-shadow-small">
                                            <div class="custom-card-content">
                                                <div class="custom-row">
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-info custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Status:</strong>
                                                                <span id="modal-exam-status-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="custom-col-half">
                                                        <div class="custom-list-group">
                                                            <div class="custom-list-item d-flex align-items-center">
                                                                <i hidden class="fas fa-calendar-check custom-margin-right"></i>
                                                                <strong class="custom-margin-right-small">Yaradılma Tarixi:</strong>
                                                                <span id="modal-exam-created-at-<?php echo $exam['id']; ?>" class="custom-text-break"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button hidden type="button" class="custom-btn custom-btn-outline-secondary" onclick="alert('Print functionality not implemented')">Çap Et</button>
                        <button type="button" class="custom-btn custom-btn-secondary" data-bs-dismiss="modal">Bağla</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>


    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
   
    <script>
        function print(examId) {
            if (!examId) {
                alert("Xəta: İmtahan ID təmin edilməyib");
                return;
            }
           
            var timestamp = new Date().getTime();
            var printUrl = 'movzular/imtahan/get_exam_and_questions.php?id=' + examId + '&t=' + timestamp;
            
            var loadingMessage = document.createElement('div');
            loadingMessage.style.position = 'fixed';
            loadingMessage.style.top = '50%';
            loadingMessage.style.left = '50%';
            loadingMessage.style.transform = 'translate(-50%, -50%)';
            loadingMessage.style.padding = '20px';
            loadingMessage.style.background = 'rgba(0,0,0,0.7)';
            loadingMessage.style.color = 'white';
            loadingMessage.style.borderRadius = '5px';
            loadingMessage.style.zIndex = '9999';
            loadingMessage.textContent = 'İmtahan yüklənir...';
            document.body.appendChild(loadingMessage);
            var printWindow = window.open(printUrl, '_blank');
            
            if (!printWindow) {
                document.body.removeChild(loadingMessage);
                alert("Xəta: PDF açıla bilmədi, brauzerinizin pop-up bloklayıcısını yoxlayın");
                return;
            }
            
            setTimeout(function() {
                document.body.removeChild(loadingMessage);
            }, 1000);
            
            printWindow.addEventListener('load', function() {
                // Check if the page contains an error message
                if (printWindow.document.body.textContent.includes('Error:')) {
                    alert("Xəta: " + printWindow.document.body.textContent.trim());
                    printWindow.close();
                }
            });
        }

        function filterExams() {
            const search = $('#examSearchInput').val().toLowerCase();
            const subject = $('#examSubjectFilter').val();
            const status = $('#examStatusFilter').val();

            $('#examTableBody tr').each(function() {
                const examName = $(this).find('td').eq(1).text().toLowerCase();
                const fennAdi = $(this).find('td').eq(2).text().toLowerCase();
                const examStatus = $(this).find('td').eq(12).find('.badge').text().toLowerCase();

                const matchesSearch = examName.includes(search) || fennAdi.includes(search);
                const matchesSubject = !subject || fennAdi.includes(subject.toLowerCase());
                const matchesStatus = !status || examStatus === status.toLowerCase();

                $(this).toggle(matchesSearch && matchesSubject && matchesStatus);
            });
        }
    </script>
</body>
</html>