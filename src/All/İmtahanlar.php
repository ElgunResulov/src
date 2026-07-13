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

include('db.php');
include('navbar_sidebar.php');

// Fetch exams from imtahanlar table
$sql = "SELECT id, exam_name, fenn_adi, sinif, description, exam_date, duration, passing_score, groups, questions, status, movzular, sual_secimi, sual_sayi, cetinlik_seviyyesi, created_at 
        FROM imtahanlar_exam";
$result = $conn->query($sql);
$exams = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
    $result->free();
}

$totalExams = count($exams);
$thisMonthExams = 0;
$completedExams = 0;
$upcomingExams = 0;
$upcomingThisWeek = 0;
$passingScores = [];
$currentMonth = date('Y-m');
$currentWeek = date('o-W');

foreach ($exams as $exam) {
    $createdMonth = !empty($exam['created_at']) ? date('Y-m', strtotime($exam['created_at'])) : '';
    if ($createdMonth === $currentMonth) {
        $thisMonthExams++;
    }

    $status = strtolower((string) ($exam['status'] ?? ''));
    if ($status === 'completed') {
        $completedExams++;
    }
    if ($status === 'upcoming') {
        $upcomingExams++;
        $examWeek = !empty($exam['exam_date']) ? date('o-W', strtotime($exam['exam_date'])) : '';
        if ($examWeek === $currentWeek) {
            $upcomingThisWeek++;
        }
    }
    if (isset($exam['passing_score']) && is_numeric($exam['passing_score'])) {
        $passingScores[] = (float) $exam['passing_score'];
    }
}

$averagePassingScore = count($passingScores) > 0
    ? round(array_sum($passingScores) / count($passingScores), 1)
    : 0;

$conn->close();

// Function to format JSON fields
function formatJsonField($field, $column = '') {
    if (empty($field) || $field === "[]") return "-";
    try {
        $parsed = json_decode($field, true);
        if (is_array($parsed)) {
            if (empty($parsed)) return "-";
            if ($column === 'movzular' && isset($parsed[0]) && is_array($parsed[0])) {
                // Handle movzular: display fenn_adi and movzu_adi without labels
                $output = [];
                foreach ($parsed as $item) {
                    $output[] = "{$item['fenn_adi']}, {$item['movzu_adi']}";
                }
                return implode("; ", $output);
            }
            if (isset($parsed[0]) && is_array($parsed[0])) {
                // Handle other array of objects
                $output = [];
                foreach ($parsed as $item) {
                    $output[] = "fenn: {$item['fenn_adi']}, movzu: {$item['movzu_adi']}";
                }
                return implode("; ", $output);
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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS İmtahanlar</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="İmtahanlar/css.css">
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

        /* Badge styling for status */
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
                <div class="col-md-6 text-md-right">
                    <button hidden type="button" class="btn btn-primary yeni_imtahan" data-toggle="modal" data-target="#addExamModal">
                        <i class="fas fa-plus-circle mr-1"></i> Yeni İmtahan
                    </button>
                </div>
            </div>
        </div>
        
    
                <!-- Statistics Cards -->
                <div class="row">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-primary text-white h-100" data-stat-type="all" role="button" tabindex="0" aria-label="Ümumi imtahanları göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-clipboard-list fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Ümumi İmtahanlar</h6>
                        <h3 class="stat-number"><?php echo $totalExams; ?></h3>
                        <p class="mb-0 small">Bu ay: <?php echo $thisMonthExams; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-success text-white h-100" data-stat-type="completed" role="button" tabindex="0" aria-label="Tamamlanmış imtahanları göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Tamamlanmış</h6>
                        <h3 class="stat-number"><?php echo $completedExams; ?></h3>
                        <p class="mb-0 small">Cəmi tamamlanmış</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-info text-white h-100" data-stat-type="average" role="button" tabindex="0" aria-label="Keçid ballarını göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Orta Bal</h6>
                        <h3 class="stat-number"><?php echo $averagePassingScore; ?></h3>
                        <p class="mb-0 small">Keçid balı orta</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card stat-card-clickable bg-warning text-white h-100" data-stat-type="upcoming" role="button" tabindex="0" aria-label="Gələcək imtahanları göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-calendar-alt fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Gələcək İmtahanlar</h6>
                        <h3 class="stat-number"><?php echo $upcomingExams; ?></h3>
                        <p class="mb-0 small">Bu həftə: <?php echo $upcomingThisWeek; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="card">
            <div class="card-body">
                <div class="tab-content" id="examTabsContent">
                    <div class="tab-pane fade show active" id="list" role="tabpanel">
                        <!-- Search and Filter -->
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="İmtahan axtar..." id="searchExam">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <select class="form-control" id="filterSubject">
                                            <option value="">Bütün Fənnlər</option>
                                            <option value="Riyaziyyat">Riyaziyyat</option>
                                            <option value="Fizika">Fizika</option>
                                            <option value="Kimya">Kimya</option>
                                            <option value="Biologiya">Biologiya</option>
                                            <option value="Tarix">Tarix</option>
                                            <option value="Ədəbiyyat">Ədəbiyyat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <select class="form-control" id="filterStatus">
                                            <option value="">Bütün Statuslar</option>
                                            <option value="Gələcək">Gələcək</option>
                                            <option value="Aktiv">Aktiv</option>
                                            <option value="Tamamlanmış">Tamamlanmış</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-outline-secondary btn-block" id="resetFilters">
                                            <i class="fas fa-redo-alt mr-1"></i> Sıfırla
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="examTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="list-tab" data-toggle="tab" href="#list" role="tab">
                            <i class="fas fa-list mr-2"></i> İmtahan Siyahısı
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="calendar-tab" data-toggle="tab" href="#calendar" role="tab">
                            <i class="fas fa-calendar-alt mr-2"></i> Təqvim
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="results-tab" data-toggle="tab" href="#results" role="tab">
                            <i class="fas fa-chart-bar mr-2"></i> Nəticələr
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="analytics-tab" data-toggle="tab" href="#analytics" role="tab">
                            <i class="fas fa-chart-pie mr-2"></i> Analitika
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="examTabsContent">
                    <!-- List View Tab -->
                    <div class="tab-pane fade show active" id="list" role="tabpanel">
                        <!-- Exams Table -->
                        <div class="table-responsive">
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
                                        <th hidden>Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody id="examTableBody">
                                    <?php foreach ($exams as $exam): ?>
                                        <tr>
                                            <td hidden><?php echo htmlspecialchars($exam['id']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars(formatJsonField($exam['fenn_adi'])); ?></td>
                                            <td hidden><?php echo htmlspecialchars($exam['sinif'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($exam['description'] ?: '-'); ?></td>
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
                                            <td hidden><?php echo htmlspecialchars(formatJsonField($exam['cetinlik_seviyyesi']) ?: '-'); ?></td>
                                            <td hidden><?php echo htmlspecialchars(formatDate($exam['created_at'])); ?></td>
                                            <td hidden>
                                                <button class="btn btn-sm btn-primary action-btn edit-btn" data-id="<?php echo $exam['id']; ?>">Изменить</button>
                                                <button class="btn btn-sm btn-info action-btn copy-btn" data-id="<?php echo $exam['id']; ?>">Копировать</button>
                                                <button class="btn btn-sm btn-danger action-btn delete-btn" data-id="<?php echo $exam['id']; ?>">Удалить</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addExamModal" tabindex="-1" aria-labelledby="addExamModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addExamModalLabel">Yeni İmtahan Əlavə Et</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="addExamForm">
                            <input type="hidden" id="examId" name="examId">
                            <div class="form-group">
                                <label for="examName">İmtahan Adı</label>
                                <input type="text" class="form-control" id="examName" required>
                            </div>
                            <div class="form-group">
                                <label for="examSubject">Fənn</label>
                                <select class="form-control" id="examSubject" required>
                                    <option value="">Seçin</option>
                                    <option value="Riyaziyyat">Riyaziyyat</option>
                                    <option value="Fizika">Fizika</option>
                                    <option value="Kimya">Kimya</option>
                                    <option value="Biologiya">Biologiya</option>
                                    <option value="Tarix">Tarix</option>
                                    <option value="Ədəbiyyat">Ədəbiyyat</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="examDate">Tarix</label>
                                <input type="datetime-local" class="form-control" id="examDate" required>
                            </div>
                            <div class="form-group">
                                <label for="examDuration">Müddət (dəqiqə)</label>
                                <input type="number" class="form-control" id="examDuration" min="1" value="45" required>
                            </div>
                            <div class="form-group">
                                <label for="examPassingScore">Keçid Balı (%)</label>
                                <input type="number" class="form-control" id="examPassingScore" min="0" max="100" value="60" required>
                            </div>
                            <div class="form-group">
                                <label for="examStatus">Status</label>
                                <select class="form-control" id="examStatus" required>
                                    <option value="">Seçin</option>
                                    <option value="upcoming">Gələcək</option>
                                    <option value="active">Aktiv</option>
                                    <option value="completed">Tamamlanmış</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Ləğv Et</button>
                        <button type="button" class="btn btn-primary" id="saveExamBtn">Yadda Saxla</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
    const examStatTitles = {
        all: 'Ümumi İmtahanlar',
        completed: 'Tamamlanmış İmtahanlar',
        average: 'Keçid Balları',
        upcoming: 'Gələcək İmtahanlar'
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.stat-card-clickable').forEach(function (card) {
            card.addEventListener('click', function () {
                openExamStatModal(card.dataset.statType);
            });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openExamStatModal(card.dataset.statType);
                }
            });
        });

        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                alert('Edit exam with ID: ' + btn.dataset.id);
            });
        });
    });

    function openExamStatModal(type) {
        const modalEl = document.getElementById('statDetailsModal');
        if (!modalEl) return;

        document.getElementById('statDetailsTitle').textContent = examStatTitles[type] || 'Məlumatlar';
        document.getElementById('statDetailsLoading').classList.remove('d-none');
        document.getElementById('statDetailsContent').classList.add('d-none');
        document.getElementById('statDetailsEmpty').classList.add('d-none');
        document.getElementById('statDetailsHead').innerHTML = '';
        document.getElementById('statDetailsBody').innerHTML = '';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        fetch('İmtahanlar/stat_operations.php?type=' + encodeURIComponent(type))
            .then(function (response) { return response.json(); })
            .then(function (data) {
                document.getElementById('statDetailsLoading').classList.add('d-none');
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    renderExamStatTable(data.columns, data.data);
                    document.getElementById('statDetailsContent').classList.remove('d-none');
                } else {
                    document.getElementById('statDetailsEmpty').classList.remove('d-none');
                }
            })
            .catch(function () {
                document.getElementById('statDetailsLoading').classList.add('d-none');
                document.getElementById('statDetailsEmpty').classList.remove('d-none');
            });
    }

    function renderExamStatTable(columns, rows) {
        const escapeHtml = function (value) {
            return String(value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        };

        let headHtml = '<tr>';
        columns.forEach(function (column) {
            headHtml += '<th>' + escapeHtml(column.label) + '</th>';
        });
        headHtml += '</tr>';
        document.getElementById('statDetailsHead').innerHTML = headHtml;

        let bodyHtml = '';
        rows.forEach(function (row) {
            bodyHtml += '<tr>';
            columns.forEach(function (column) {
                bodyHtml += '<td>' + escapeHtml(row[column.key] ?? '-') + '</td>';
            });
            bodyHtml += '</tr>';
        });
        document.getElementById('statDetailsBody').innerHTML = bodyHtml;
    }
    </script>

    <!-- Stat Details Modal -->
    <div class="modal fade" id="statDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statDetailsTitle">Məlumatlar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
                </div>
                <div class="modal-body">
                    <div id="statDetailsLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div class="table-responsive d-none" id="statDetailsContent">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light" id="statDetailsHead"></thead>
                            <tbody id="statDetailsBody"></tbody>
                        </table>
                    </div>
                    <div id="statDetailsEmpty" class="text-center py-4 text-muted d-none">Məlumat tapılmadı</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
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
</body>
</html>