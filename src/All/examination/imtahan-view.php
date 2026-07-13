<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Validate exam ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: imtahan-list.php");
    exit();
}

$exam_id = intval($_GET['id']);
include('../db.php'); // Include database connection

$conn->set_charset("utf8mb4");

// Fetch exam details
$query = "SELECT * FROM imtahanlar_exam WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: imtahan-list.php");
    exit();
}

$exam = $result->fetch_assoc();

$sual_secimi = strtolower($exam['sual_secimi']);
if ($sual_secimi === 'random') {
    $actual_question_count = intval($exam['sual_sayi']);
} else if ($sual_secimi === 'manual') {
    $question_ids = json_decode($exam['questions'], true);
    $actual_question_count = is_array($question_ids) ? count($question_ids) : 0;
} else {
    $actual_question_count = 0;
}

$results_query = "SELECT * FROM imtahan_neticeler WHERE imtahan_id = ? AND u_id = ? ORDER BY created_at DESC";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("is", $exam_id, $_SESSION['user_id']);
$results_stmt->execute();
$results_result = $results_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>İmtahan Məlumatları - <?php echo htmlspecialchars($exam['exam_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
     <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #1db954;
            --warning-color: #f72585;
            --danger-color: #ff3a3a;
            --info-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --shadow-sm: 0 2px 5px rgba(0,0,0,0.1);
            --shadow-md: 0 8px 20px rgba(0,0,0,0.1);
            --shadow-lg: 0 15px 40px rgba(0,0,0,0.1);
            --border-radius: 12px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }

        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
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
                top: 0;
                left: 0;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

        .main-content {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: clamp(20px, 5vw, 26px);
            font-weight: 700;
            color: var(--dark-color);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            outline: none;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: rgba(67, 98, 238, 0.24);
            transform: translateY(-2px);
        }

        .btn-success {
            background: rgba(11, 178, 92, 0.62);
            color: white;
            box-shadow: 0 4px 15px rgba(29, 185, 84, 0.3);
        }

        .btn-success:hover {
            background: rgb(1, 187, 91);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 185, 84, 0.4);
        }

        .exam-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .exam-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .exam-title {
            font-size: clamp(18px, 4vw, 24px);
            font-weight: 600;
            color: var(--dark-color);
        }

        .exam-body {
            padding: 20px;
        }

        .exam-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .exam-description {
            margin-bottom: 30px;
        }

        .description-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .description-text {
            font-size: 16px;
            color: #495057;
            line-height: 1.6;
        }

        .exam-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            transition: all var(--transition-speed) ease;
        }

        .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-sm);
        }

        .detail-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-title i {
            color: var(--primary-color);
        }

        .detail-content {
            font-size: 15px;
            color: #495057;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tag {
            display: inline-block;
            padding: 5px 12px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .results-container {
            margin-top: 30px;
        }

        .results-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            background-color: white;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            min-width: 1000px;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            color: white;
        }
        .bg-primary { background-color: #0d6efd; }
        .bg-success { background-color: #198754; }
        .bg-warning { background-color: #ffc107; }
        .bg-secondary { background-color: #6c757d; }
        .badge-success { background: rgba(124, 135, 152, 0.14); color: #7c8798; }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    </style>
</head>
<body>
    <div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">İmtahan Məlumatları</h1>
            <div class="action-buttons">
                <a href="../Examination.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Geri
                </a>
                <a href="imtahan-start.php?id=<?php echo $exam_id; ?>" class="btn btn-success">
                    <i class="fas fa-play"></i> İmtahanı başlat
                </a>
            </div>
        </div>
        
        <div class="exam-container">
            <div class="exam-header">
                <h2 class="exam-title"><?php echo htmlspecialchars($exam['exam_name']); ?></h2>
                <?php
                $status_map = [
                    'aktiv' => 'active',
                    'gozlemde' => 'upcoming',
                    'bitmis' => 'completed',
                    'legv edilmis' => 'completed',
                ];
                $normalized_status = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $exam['status']));
                $status_key = isset($status_map[$normalized_status]) ? $status_map[$normalized_status] : $normalized_status;

                switch (strtolower($status_key)) {
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
            </div>
            
            <div class="exam-body">
                <div class="exam-info">
                    <div class="info-item">
                        <div class="info-label">Fənn</div>
                        <div class="info-value">
                            <?php
                            $fenn_decoded = json_decode($exam['fenn_adi'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($fenn_decoded)) {
                                echo htmlspecialchars(implode(', ', array_map('trim', $fenn_decoded)));
                            } else {
                                echo htmlspecialchars($exam['fenn_adi']);
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Sinif</div>
                        <div class="info-value"><?php echo htmlspecialchars($exam['sinif']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">İmtahan tarixi</div>
                        <div class="info-value"><?php echo htmlspecialchars(date('d.m.Y', strtotime($exam['exam_date']))); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Müddət</div>
                        <div class="info-value"><?php echo htmlspecialchars($exam['duration']); ?> dəqiqə</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Keçid balı</div>
                        <div class="info-value"><?php echo htmlspecialchars($exam['passing_score']); ?>%</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Sual sayı</div>
                        <div class="info-value"><?php echo htmlspecialchars($actual_question_count); ?></div>
                    </div>
                    
                   <div class="info-item" <?php echo (empty($exam['cetinlik_seviyyesi']) || $exam['cetinlik_seviyyesi'] === NULL) ? 'style="display: none;"' : ''; ?>>
                        <div class="info-label">Çətinlik səviyyəsi</div>
                        <div class="info-value">
                            <?php
                            $difficulty = json_decode($exam['cetinlik_seviyyesi'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($difficulty)) {
                                $mapped_difficulties = [];
                                foreach ($difficulty as $level) {
                                    switch (trim($level)) {
                                        case 'Easy':
                                            $mapped_difficulties[] = 'Asan';
                                            break;
                                        case 'Medium':
                                            $mapped_difficulties[] = 'Orta';
                                            break;
                                        case 'Hard':
                                            $mapped_difficulties[] = 'Çətin';
                                            break;
                                        default:
                                            $mapped_difficulties[] = htmlspecialchars($level);
                                    }
                                }
                                echo htmlspecialchars(implode(', ', $mapped_difficulties));
                            } else {
                                echo htmlspecialchars($exam['cetinlik_seviyyesi']);
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Sual seçimi</div>
                        <div class="info-value">
                            <?php
                            $sual_secimi = strtolower($exam['sual_secimi']);
                            echo htmlspecialchars($sual_secimi === 'manual' ? 'Əl ilə Seç' : ($sual_secimi === 'random' ? 'Təsadüfi Seç' : $exam['sual_secimi']));
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="exam-description">
                    <h3 class="description-title">Təsvir</h3>
                    <div class="description-text"><?php echo nl2br(htmlspecialchars($exam['description'])); ?></div>
                </div>
                
                <div class="exam-details">
                    <div class="detail-card">
                        <h4 class="detail-title"><i class="fas fa-users"></i> Qruplar</h4>
                        <div class="detail-content tag-list">
                            <?php
                            $groups = explode(',', $exam['groups']);
                            foreach ($groups as $group) {
                                echo '<span class="tag">' . htmlspecialchars(trim($group)) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <h4 class="detail-title"><i class="fas fa-book"></i> Mövzular</h4>
                        <div class="detail-content tag-list">
                            <?php
                            $topics = [];
                            $movzular_decoded = json_decode($exam['movzular'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($movzular_decoded)) {
                                foreach ($movzular_decoded as $item) {
                                    if (isset($item['movzu_adi'])) {
                                        $movzu_adi = json_decode('"' . $item['movzu_adi'] . '"');
                                        $topics[] = $movzu_adi;
                                    }
                                }
                            } else {
                                $topics = explode(',', $exam['movzular']);
                            }

                            foreach ($topics as $topic) {
                                $topic = trim($topic);
                                if (!empty($topic)) {
                                    echo '<span class="tag">' . htmlspecialchars($topic) . '</span>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <h4 class="detail-title"><i class="fas fa-calendar-alt"></i> Yaradılma tarixi</h4>
                        <div class="detail-content">
                            <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($exam['created_at']))); ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($results_result->num_rows > 0): ?>
                <div class="results-container">
                    <h3 class="results-title">İmtahan Nəticələri</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tələbə</th>
                                    <th>Doğru</th>
                                    <th>Səhv</th>
                                    <th>Faiz</th>
                                    <th>Status</th>
                                    <th>Tarix</th>
                                    <th>Əməliyyatlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($result = $results_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['telebe_adi']); ?></td>
                                    <td><?php echo htmlspecialchars($result['dogru_cavablar']); ?></td>
                                    <td><?php echo htmlspecialchars($result['sehv_cavablar']); ?></td>
                                    <td><?php echo htmlspecialchars($result['faiz']); ?>%</td>
                                    <td>
                                        <span class="badge <?php echo $result['kecid_statusu'] === 'Keçdi' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo htmlspecialchars($result['kecid_statusu']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($result['created_at']))); ?></td>
                                    <td>
                                        <a href="imtahan-netice.php?id=<?php echo $result['id']; ?>" class="action-icon" title="Nəticəyə bax">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php else: ?>
                    <div class="results-container">
                    <h3 class="results-title">İmtahan Nəticələri</h3>
                    <p>Heç bir nəticə tapılmadı.</p>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $(".preloader").fadeOut(500);
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>