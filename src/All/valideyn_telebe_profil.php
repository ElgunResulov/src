<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure session is valid
if (!isset($_SESSION['u_id']) || empty($_SESSION['u_id'])) {
    echo "<script>alert('Session expired or invalid user. Redirecting to login...'); window.location.href = 'Login.php';</script>";
    exit;
}

include('navbar_sidebar.php');
include('db.php');

// Initialize variables
$valideyn_info = [];
$telebe_info = [];
$error_message = "";

try {
    $session_u_id = $_SESSION['u_id'];
    error_log("Session u_id: " . $session_u_id);

    // Get parent information and associated students
    $valideyn_query = "SELECT v.id, v.u_id, v.telebe_name, v.parent_name, v.parent_type, v.created_at 
                       FROM valideyn v 
                       WHERE v.u_id = ?";
    
    $valideyn_stmt = $conn->prepare($valideyn_query);
    if (!$valideyn_stmt) {
        throw new Exception("Prepare failed for valideyn: " . $conn->error);
    }
    
    $valideyn_stmt->bind_param("s", $session_u_id);
    $valideyn_stmt->execute();
    $valideyn_result = $valideyn_stmt->get_result();

    if ($valideyn_result->num_rows > 0) {
        $valideyn_row = $valideyn_result->fetch_assoc();
        $valideyn_info = [
            'id' => htmlspecialchars($valideyn_row['id'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'u_id' => htmlspecialchars($valideyn_row['u_id'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'telebe_name' => htmlspecialchars($valideyn_row['telebe_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'parent_name' => htmlspecialchars($valideyn_row['parent_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'parent_type' => htmlspecialchars($valideyn_row['parent_type'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'created_at' => ($valideyn_row['created_at'] && $valideyn_row['created_at'] !== '0000-00-00 00:00:00')
                ? htmlspecialchars(date('d.m.Y H:i', strtotime($valideyn_row['created_at'])), ENT_QUOTES, 'UTF-8')
                : "Unknown"
        ];

        // Get detailed student information
        $telebe_query = "SELECT t.id, t.u_id, t.username, t.number, t.poct, t.active_status, 
                               t.dogum_tarixi, t.years, t.cins, t.unvan, t.sinif, t.vetandasliq, 
                               t.qebul_tarixi, t.ata, t.elaqe_nomre_ata, t.ana, t.elaqe_nomre_ana, 
                               t.photo, t.muellim_adi, t.ixtisas_adi, t.orta_bal, t.davamiyyet, 
                               t.status, t.cedvel, t.riyaziyyat, t.fizika, t.kimya, t.biologiya, 
                               t.tarix, t.edebiyyat, t.qeyd, t.created_at, t.updated_at
                        FROM telebeler t 
                        WHERE t.username = ?";
        
        $telebe_stmt = $conn->prepare($telebe_query);
        if (!$telebe_stmt) {
            throw new Exception("Prepare failed for telebeler: " . $conn->error);
        }
        
        $telebe_stmt->bind_param("s", $valideyn_info['telebe_name']);
        $telebe_stmt->execute();
        $telebe_result = $telebe_stmt->get_result();

        if ($telebe_result->num_rows > 0) {
            $telebe_row = $telebe_result->fetch_assoc();
            $telebe_info = [
                'id' => htmlspecialchars($telebe_row['id'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'u_id' => htmlspecialchars($telebe_row['u_id'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'username' => htmlspecialchars($telebe_row['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'number' => htmlspecialchars($telebe_row['number'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'poct' => htmlspecialchars($telebe_row['poct'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'active_status' => ($telebe_row['active_status'] == 1) ? "Aktiv" : "Deaktiv",
                'dogum_tarixi' => ($telebe_row['dogum_tarixi'] && $telebe_row['dogum_tarixi'] !== '0000-00-00')
                    ? htmlspecialchars(date('d.m.Y', strtotime($telebe_row['dogum_tarixi'])), ENT_QUOTES, 'UTF-8')
                    : "Unknown",
                'years' => htmlspecialchars($telebe_row['years'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'cins' => ($telebe_row['cins'] == 0) ? "Kişi" : ($telebe_row['cins'] == 1 ? "Qadın" : "Unknown"),
                'unvan' => htmlspecialchars($telebe_row['unvan'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'sinif' => htmlspecialchars($telebe_row['sinif'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'vetandasliq' => htmlspecialchars($telebe_row['vetandasliq'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'qebul_tarixi' => ($telebe_row['qebul_tarixi'] && $telebe_row['qebul_tarixi'] !== '0000-00-00')
                    ? htmlspecialchars(date('d.m.Y', strtotime($telebe_row['qebul_tarixi'])), ENT_QUOTES, 'UTF-8')
                    : "Unknown",
                'ata' => htmlspecialchars($telebe_row['ata'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'elaqe_nomre_ata' => htmlspecialchars($telebe_row['elaqe_nomre_ata'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'ana' => htmlspecialchars($telebe_row['ana'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'elaqe_nomre_ana' => htmlspecialchars($telebe_row['elaqe_nomre_ana'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'photo' => htmlspecialchars($telebe_row['photo'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'muellim_adi' => htmlspecialchars($telebe_row['muellim_adi'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'ixtisas_adi' => htmlspecialchars($telebe_row['ixtisas_adi'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'orta_bal' => htmlspecialchars($telebe_row['orta_bal'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'davamiyyat' => htmlspecialchars($telebe_row['davamiyyat'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'status' => htmlspecialchars($telebe_row['status'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'cedvel' => htmlspecialchars($telebe_row['cedvel'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                'riyaziyyat' => htmlspecialchars($telebe_row['riyaziyyat'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'fizika' => htmlspecialchars($telebe_row['fizika'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'kimya' => htmlspecialchars($telebe_row['kimya'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'biologiya' => htmlspecialchars($telebe_row['biologiya'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'tarix' => htmlspecialchars($telebe_row['tarix'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'edebiyyat' => htmlspecialchars($telebe_row['edebiyyat'] ?? '0', ENT_QUOTES, 'UTF-8'),
                'qeyd' => htmlspecialchars($telebe_row['qeyd'] ?? 'Qeyd yoxdur', ENT_QUOTES, 'UTF-8'),
                'created_at' => ($telebe_row['created_at'] && $telebe_row['created_at'] !== '0000-00-00 00:00:00')
                    ? htmlspecialchars(date('d.m.Y H:i', strtotime($telebe_row['created_at'])), ENT_QUOTES, 'UTF-8')
                    : "Unknown",
                'updated_at' => ($telebe_row['updated_at'] && $telebe_row['updated_at'] !== '0000-00-00 00:00:00')
                    ? htmlspecialchars(date('d.m.Y H:i', strtotime($telebe_row['updated_at'])), ENT_QUOTES, 'UTF-8')
                    : "Unknown"
            ];
        }
        $telebe_stmt->close();
    } else {
        $error_message = "Valideyn məlumatları tapılmadı.";
    }
    $valideyn_stmt->close();

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Database xətası: " . $e->getMessage();
} finally {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Valideyn Paneli</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet">
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style> 
        .main-content {
            margin-left: 0;
            margin-top: 90px;
            padding: 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            background-color: #f8fafc;
            border-radius: 12px;
            min-height: calc(100vh - 90px);
        }

        .main-content.open {
            margin-left: 260px;
        }

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

        .info-section {
            background-color: #fff;
            margin: 15px auto;
            padding: 0;
            width: 96%;
            max-width: 95%;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.09);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .section-header {
            color: white;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
        }

        .section-header h3 {
            margin: 0;
            color: black;
            font-size: 1.32rem;
            font-weight: 500;
        }

        .section-body {
            padding: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .info-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .info-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .info-group span {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background-color: #f7fafc;
            font-size: 0.95rem;
            color: #4a5568;
            margin: 5px 0px 0px 0px;
        }

        .student-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }

        .student-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .exam-table th,
        .exam-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .exam-table th {
            background-color: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }

        .exam-score {
            font-weight: 600;
        }

        .exam-score.high {
            color: #38a169;
        }

        .exam-score.medium {
            color: #d69e2e;
        }

        .exam-score.low {
            color: #e53e3e;
        }

        .error-message {
            background-color: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 8px;
            margin: 15px;
        }

        .no-data {
            text-align: center;
            color: #718096;
            padding: 20px;
            font-style: italic;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .info-section {
                width: 95%;
                margin: 15px auto;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .section-header h3 {
                font-size: 1.2rem;
            }

            .section-body {
                padding: 15px;
            }

            .exam-table {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .info-section {
                width: 98%;
                margin: 10px auto;
            }

            .section-body {
                padding: 10px;
            }

            .section-header {
                padding: 12px 15px;
            }

            .section-header h3 {
                font-size: 1.1rem;
            }
        }

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 260px;
            }
        }

        @media (max-width: 767px) {
            .main-content {
                margin-left: 0;
                margin-top: 80px;
                padding: 15px;
            }
            .main-content.open {
                margin-left: 0;
            }
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

    <div class="main-content">
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php else: ?>
            
            <!-- Parent Information Section -->
            <div class="info-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-friends"></i> Valideyn Məlumatları</h3>
                </div>
                <div class="section-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Tələbə Adı:</label>
                            <span><?php echo $valideyn_info['telebe_name']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Valideyn Adı:</label>
                            <span><?php echo $valideyn_info['parent_name']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Valideyn Tipi:</label>
                            <span><?php echo $valideyn_info['parent_type']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($telebe_info)): ?>
            <!-- Student Personal Information -->
            <div class="info-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-graduate"></i> Tələbə Şəxsi Məlumatları</h3>
                </div>
                <div class="section-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>İstifadəçi Adı:</label>
                            <span><?php echo $telebe_info['username']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Nömrə:</label>
                            <span><?php echo $telebe_info['number']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Email:</label>
                            <span><?php echo $telebe_info['poct']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Status:</label>
                            <span><?php echo $telebe_info['active_status']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Doğum Tarixi:</label>
                            <span><?php echo $telebe_info['dogum_tarixi']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Yaş:</label>
                            <span><?php echo $telebe_info['years']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Cinsi:</label>
                            <span><?php echo $telebe_info['cins']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Unvan:</label>
                            <span><?php echo $telebe_info['unvan']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Sinif:</label>
                            <span><?php echo $telebe_info['sinif']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Vətəndaşlıq:</label>
                            <span><?php echo $telebe_info['vetandasliq']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Qəbul Tarixi:</label>
                            <span><?php echo $telebe_info['qebul_tarixi']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="info-section">
                <div class="section-header">
                    <h3><i class="fas fa-graduation-cap"></i> Təhsil Məlumatları</h3>
                </div>
                <div class="section-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Müəllim:</label>
                            <span><?php echo $telebe_info['muellim_adi']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>İxtisas:</label>
                            <span><?php echo $telebe_info['ixtisas_adi']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Orta Bal:</label>
                            <span><?php echo $telebe_info['orta_bal']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Davamiyyat:</label>
                            <span><?php echo $telebe_info['davamiyyat']; ?>%</span>
                        </div>
                        <div class="info-group">
                            <label>Status:</label>
                            <span><?php echo $telebe_info['status']; ?></span>
                        </div>
                        <div class="info-group">
                            <label>Cədvəl:</label>
                            <span><?php echo $telebe_info['cedvel']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Grades -->
            <div class="info-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-bar"></i> Fənn Balları</h3>
                </div>
                <div class="section-body">
                    <div class="subjects-grid">
                        <div class="mb-1 subject-card">
                            <div class="subject-name">
                                <i class="fas fa-calculator"></i>
                                <span>Riyaziyyat</span>
                            </div>
                            <div class="subject-grade">
                                <span class="grade-value"><?php echo $telebe_info['riyaziyyat']; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-1 subject-card">
                            <div class="subject-name">
                                <i class="fas fa-atom"></i>
                                <span>Fizika</span>
                            </div>
                            <div class="subject-grade">
                                <span class="grade-value"><?php echo $telebe_info['fizika']; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-1 subject-card">
                            <div class="subject-name">
                                <i class="fas fa-flask"></i>
                                <span>Kimya</span>
                            </div>
                            <div class="subject-grade">
                                <span class="grade-value"><?php echo $telebe_info['kimya']; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-1 subject-card">
                            <div class="subject-name">
                                <i class="fas fa-dna"></i>
                                <span>Biologiya</span>
                            </div>
                            <div class="subject-grade">
                                <span class="grade-value"><?php echo $telebe_info['biologiya']; ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-1 subject-card">
                            <div class="subject-name">
                                <i class="fas fa-monument"></i>
                                <span>Tarix</span>
                            </div>
                            <div class="subject-grade">
                                <span class="grade-value"><?php echo $telebe_info['tarix']; ?></span>
                            </div>
                        </div>
                        
                        <div class="subject-card">
                            <div class="subject-name">
                                <i class="fas fa-book"></i>
                                <span>Ədəbiyyat</span>
                            </div>
                            <div class="subject-grade">
                                <span class="grade-value"><?php echo $telebe_info['edebiyyat']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="info-section">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i> Əlavə Məlumatlar</h3>
                </div>
                <div class="section-body">
                    <div class="info-grid">
                        <div class="info-group full-width">
                            <label>Qeyd:</label>
                            <span><?php echo $telebe_info['qeyd']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="info-section">
                <div class="section-body">
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i> Tələbə məlumatları tapılmadı.
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>

    <script>
        $(document).ready(function() {
            // Hide preloader
            $('.preloader').fadeOut();
            
            // Add grade color coding
            $('.grade-value').each(function() {
                var grade = parseInt($(this).text());
                var $card = $(this).closest('.subject-card');
                
                if (grade >= 80) {
                    $card.addClass('grade-high');
                } else if (grade >= 60) {
                    $card.addClass('grade-medium');
                } else if (grade > 0) {
                    $card.addClass('grade-low');
                } else {
                    $card.addClass('grade-none');
                }
            });
        });
    </script>

</body>
</html>