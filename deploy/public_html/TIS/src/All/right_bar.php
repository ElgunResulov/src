<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('db.php');

// Initialize variables
$username = "N/A";
$sinif = "N/A";
$image = "";
$muellim_adi = "N/A";
$ixtisas_adi = "N/A";
$qebul_tarixi = "N/A";
$is_muellim = false; 
$is_valideyn = false;
$telebe_name = "N/A";
$parent_type = "N/A";
$filial_adi = "N/A";

// Helper function to safely parse JSON
function safeJsonDecode($jsonString, $default = []) {
    if (empty($jsonString)) {
        return $default;
    }
    
    // Clean the JSON string
    $jsonString = trim($jsonString);
    
    $decoded = json_decode($jsonString, true);
    return $decoded !== null ? $decoded : $default;
}

// Helper function to display array as comma-separated string
function displayArrayAsString($array) {
    if (is_array($array) && !empty($array)) {
        return implode(', ', $array);
    } elseif (is_string($array) && !empty($array)) {
        $parsed = safeJsonDecode($array, []);
        return !empty($parsed) ? implode(', ', $parsed) : $array;
    }
    return "N/A";
}

if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('Session expired or invalid user. Please log in.'); window.location.href='login.php';</script>";
    exit;
}

try {
    $session_u_id = $_SESSION['u_id'];
    
    // Get username from users table
    $user_query = "SELECT username FROM users WHERE u_id = ?";
    $user_stmt = $conn->prepare($user_query);
    if (!$user_stmt) {
        throw new Exception("Prepare failed for users: " . $conn->error);
    }
    $user_stmt->bind_param("s", $session_u_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows > 0) {
        $user_row = $user_result->fetch_assoc();
        $username = htmlspecialchars($user_row['username'], ENT_QUOTES, 'UTF-8');
    } else {
        echo "<script>alert('No user found in users table for this session.');</script>";
        $user_stmt->close();
        exit;
    }
    $user_stmt->close();

    // Check if user is a teacher
    $muellim_query = "SELECT id, username, tehsil_ve_ixtisas, filial_adi, profile FROM muellimler_new WHERE u_id = ?";
    $muellim_stmt = $conn->prepare($muellim_query);
    if (!$muellim_stmt) {
        throw new Exception("Prepare failed for muellimler_new: " . $conn->error);
    }
    $muellim_stmt->bind_param("s", $session_u_id);
    $muellim_stmt->execute();
    $muellim_result = $muellim_stmt->get_result();

    if ($muellim_result->num_rows > 0) {
        // User is a teacher
        $is_muellim = true;
        $muellim_row = $muellim_result->fetch_assoc();
        $username = htmlspecialchars($muellim_row['username'], ENT_QUOTES, 'UTF-8');
        
        // Parse filial_adi JSON
        $filial_adi_raw = $muellim_row['filial_adi'];
        $filial_array = safeJsonDecode($filial_adi_raw, []);
        $filial_adi = displayArrayAsString($filial_array);
        
        $ixtisas_adi = htmlspecialchars($muellim_row['tehsil_ve_ixtisas'], ENT_QUOTES, 'UTF-8');
        $image_filename = !empty($muellim_row['profile']) ? $muellim_row['profile'] : '';
        $image_path = "../Uploads/profiles/" . $image_filename;
        $image = file_exists($image_path) ? $image_path : "";
    } else {
        // Check if user is a parent
        $valideyn_query = "SELECT parent_name, telebe_name, parent_type FROM valideyn WHERE u_id = ?";
        $valideyn_stmt = $conn->prepare($valideyn_query);
        if (!$valideyn_stmt) {
            throw new Exception("Prepare failed for valideyn: " . $conn->error);
        }
        $valideyn_stmt->bind_param("s", $session_u_id);
        $valideyn_stmt->execute();
        $valideyn_result = $valideyn_stmt->get_result();

        if ($valideyn_result->num_rows > 0) {
            // User is a parent
            $is_valideyn = true;
            $valideyn_row = $valideyn_result->fetch_assoc();
            $username = htmlspecialchars($valideyn_row['parent_name'], ENT_QUOTES, 'UTF-8');
            $telebe_name = htmlspecialchars($valideyn_row['telebe_name'], ENT_QUOTES, 'UTF-8');
            $parent_type = htmlspecialchars($valideyn_row['parent_type'], ENT_QUOTES, 'UTF-8');
            $image = "";
        } else {
            // Check if user is a student
            $telebe_query = "SELECT username, reg_sinif_qeyd, reg_qebul_ili, reg_ixtisas, reg_universitet, sinif, photo, muellim_adi, ixtisas_adi, qebul_tarixi FROM telebeler WHERE u_id = ?";
            $telebe_stmt = $conn->prepare($telebe_query);
            if (!$telebe_stmt) {
                throw new Exception("Prepare failed for telebeler: " . $conn->error);
            }
            $telebe_stmt->bind_param("s", $session_u_id);
            $telebe_stmt->execute();
            $telebe_result = $telebe_stmt->get_result();

            if ($telebe_result->num_rows > 0) {
                $telebe_row = $telebe_result->fetch_assoc();
                $username = htmlspecialchars($telebe_row['username'], ENT_QUOTES, 'UTF-8');
                $sinif = htmlspecialchars($telebe_row['reg_sinif_qeyd'], ENT_QUOTES, 'UTF-8');
                $ixtisas = htmlspecialchars($telebe_row['reg_ixtisas'], ENT_QUOTES, 'UTF-8');
                $universitet = htmlspecialchars($telebe_row['reg_universitet'], ENT_QUOTES, 'UTF-8');

                // Parse muellim_adi JSON
                $muellim_adi_raw = $telebe_row['muellim_adi'];
                $muellim_array = safeJsonDecode($muellim_adi_raw, []);
                $muellim_adi = displayArrayAsString($muellim_array);
                
                $ixtisas_adi = htmlspecialchars($telebe_row['ixtisas_adi'], ENT_QUOTES, 'UTF-8');
                $qebul_tarixi = !empty($telebe_row['reg_qebul_ili'])
                    ? htmlspecialchars((new DateTime($telebe_row['reg_qebul_ili']))->format('Y'), ENT_QUOTES, 'UTF-8')
                    : "N/A";
                $image_filename = !empty($telebe_row['photo']) ? $telebe_row['photo'] : '';
                $image_path = "telebeler/" . $image_filename;
                $image = file_exists($image_path) ? $image_path : "";
            } else {
                echo "<script>alert('No user found in telebeler, muellimler_new, or valideyn tables.');</script>";
                $telebe_stmt->close();
                $valideyn_stmt->close();
                $muellim_stmt->close();
                exit;
            }
            $telebe_stmt->close();
        }
        $valideyn_stmt->close();
    }
    $muellim_stmt->close();
} catch (Exception $e) {
    echo "<script>alert('Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "');</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{font-family:roboto}
        @media (max-width:992px){#lessonsModal .modal-dialog{width:auto!important;max-width:100vw!important;margin:1rem}}
        .dersler:hover{cursor:pointer}
        :root{--sidebar-width:360px;--primary-color:rgba(98,74,255,0.73);--btn-toggle:rgba(98,74,255,0.47);--primary-hover:rgba(98,74,255,0.9);--text-color:#2a3547;--light-bg:#f8f9fa;--border-radius:12px;--transition:all 0.3s ease-out;--shadow:0 4px 20px rgba(0,0,0,0.08)}
        .right-sidebar{width:var(--sidebar-width);background:#ffffff;position:fixed;top:70px;right:0;transform:translateX(100%);height:calc(100vh - 70px);transition:transform 0.3s ease-out;z-index:100;overflow-y:auto;box-shadow:var(--shadow);display:flex;flex-direction:column;padding:0;will-change:transform}
        .right-sidebar.active{transform:translateX(0)}
        .sidebar-content{padding:20px;flex:1;overflow-y:auto}
        .profile-card{text-align:center;margin-bottom:20px;padding:20px 5px;background:white;border-radius:var(--border-radius);box-shadow:0 4px 12px rgba(0,0,0,0.1);position:relative;overflow:hidden;transition:transform 0.3s ease,box-shadow 0.3s ease}
        .profile-card:hover{transform:translateY(-5px);box-shadow:0 6px 20px rgba(0,0,0,0.15)}
        .profile-card::before{content:'';position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,var(--primary-color),#6c5ce7)}
        .profile-img{width:110px;height:110px;border-radius:50%;object-fit:cover;margin-bottom:15px;border:4px solid white;box-shadow:0 6px 18px rgba(0,0,0,0.12);transition:transform 0.3s ease}
        .profile-img:hover{transform:scale(1.08)}
        .profile-name{margin:0.8rem 0 1.2rem;font-size:1.25rem;color:var(--text-color);font-weight:700;letter-spacing:0.3px}
        .profile-info{width:100%;padding:0 10px}
        .profile-item{display:flex;justify-content:space-between;align-items:center;margin-bottom:-8px;padding:6px 0px;border-radius:8px;transition:background 0.2s ease}
        .profile-item .label{flex:1;text-align:left;font-weight:500;color:#6c757d;font-size:0.9rem}
        .profile-item .value{flex:1;text-align:right;font-size:0.85rem;font-weight:600}
        .profile-item .value.truncated{cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:150px}
        .profile-status{display:inline-block;padding:5px 14px;background:#e3f7ee;color:#28a745;border-radius:20px;font-size:0.85rem;margin-top:12px;font-weight:500}
        .nav-links .list-group{border-radius:var(--border-radius);overflow:hidden}
        .nav-links .list-group-item{border:none;padding:14px 20px;font-size:0.95rem;color:var(--text-color);background:white;transition:background 0.2s ease,padding-left 0.2s ease;display:flex;align-items:center;border-bottom:1px solid #f1f3f9}
        .nav-links .list-group-item:last-child{border-bottom:none}
        .nav-links .list-group-item i{margin-right:12px;color:var(--primary-color);font-size:1.1rem;width:20px;text-align:center}
        .nav-links .list-group-item:hover{background:rgba(230,234,251,0.64);padding-left:25px;text-decoration:none}
        .nav-links .list-group-item.active{background:rgba(99,93,214,0.1);color:var(--primary-color);font-weight:500}
        .notifications{margin-top:25px}
        .notifications h6{font-size:1rem;color:var(--text-color);margin-bottom:15px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center}
        .notifications h6 i{margin-right:8px;color:var(--primary-color)}
        .notification-item{display:flex;padding:12px 15px;margin-bottom:10px;background:white;border-radius:var(--border-radius);box-shadow:0 2px 5px rgba(0,0,0,0.03);transition:transform 0.2s ease}
        .notification-item:hover{transform:translateY(-2px)}
        .notification-icon{width:36px;height:36px;border-radius:50%;background:rgba(93,120,255,0.1);color:var(--primary-color);display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0}
        .notification-content{flex:1}
        .notification-title{font-size:0.9rem;font-weight:500;margin-bottom:3px;color:var(--text-color)}
        .notification-time{font-size:0.75rem;color:#adb5bd;display:block}
        .notification-badge{font-size:0.7rem;padding:3px 7px;background:var(--primary-color);border-radius:10px;color:white;font-weight:500;align-self:flex-start}
        .sidebar-toggle-container{position:fixed;top:18%;right:0;z-index:800;transition:transform 0.3s ease-out}
        .sidebar-toggle{background:var(--btn-toggle);color:white;border:none;padding:12px 6px;transition:0.3s ease-in-out;border-radius:15px 0 0 15px;font-size:1.1rem;display:flex;align-items:center;justify-content:center;width:35px;height:85px;cursor:pointer;box-shadow:-2px 0 10px rgba(0,0,0,0.1);will-change:transform}
        .sidebar-toggle:hover{transition:0.3s ease-in-out;background:var(--primary-hover);width:42px}
        .sidebar-toggle i{transition:transform 0.3s ease-out}
        .sidebar-toggle.active i{transform:rotate(180deg)}
        .right-sidebar.active ~ .sidebar-toggle-container{transform:translateX(-360px)}
        @media (max-width:768px){:root{--sidebar-width:100%}.right-sidebar{width:100%;height:70vh;top:auto;bottom:0;transform:translateY(100%);border-radius:20px 20px 0 0;transition:transform 0.3s ease-out}.right-sidebar.active{transform:translateY(0)}.sidebar-toggle-container{top:auto;bottom:0;right:50%;transform:translateX(50%);width:120px}.sidebar-toggle{width:120px;height:33px;border-radius:20px 20px 0 0;padding:0}.sidebar-toggle:hover{width:120px;height:42px}.right-sidebar.active ~ .sidebar-toggle-container{transform:translateX(50%) translateY(-70vh)}}
        @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        .notification-item{animation:fadeIn 0.3s ease forwards}
        .notification-item:nth-child(1){animation-delay:0.1s}
        .notification-item:nth-child(2){animation-delay:0.2s}
        .notification-item:nth-child(3){animation-delay:0.3s}
        .modal{z-index:9999999999999}
        /* Custom Schedule Styles */
        .custom-schedule-container{background:linear-gradient(135deg,#f8f9ff 0%,#e8f2ff 100%);border-radius:15px;overflow:hidden;box-shadow:0 8px 25px rgba(98,74,255,0.15)}
        .custom-schedule-header{background:linear-gradient(135deg,rgba(98,74,255,0.9) 0%,rgba(108,92,231,0.9) 100%);color:white;padding:20px;text-align:center;position:relative;overflow:hidden}
        .custom-schedule-header::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 70%);animation:shimmer 3s ease-in-out infinite}
        @keyframes shimmer{0%,100%{transform:translateX(-20px) translateY(-20px)}50%{transform:translateX(20px) translateY(20px)}}
        .custom-schedule-header h4{margin:0;font-weight:700;font-size:1.4rem;text-shadow:0 2px 4px rgba(0,0,0,0.2);position:relative;z-index:1}
        .custom-schedule-header .schedule-subtitle{margin:5px 0 0 0;opacity:0.9;font-size:0.9rem;position:relative;z-index:1}
        .custom-schedule-table{width:100%;margin:0;background:white}
        .custom-schedule-row{border-bottom:1px solid #e9ecef;transition:all 0.3s ease;position:relative}
        .custom-schedule-row:hover{background:rgba(98,74,255,0.04);transform:translateX(5px)}
        .custom-schedule-row:last-child{border-bottom:none}
        .custom-schedule-cell{padding:18px 15px;vertical-align:middle;border:none;position:relative}
        .custom-filial-badge{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:8px 14px;border-radius:20px;font-size:0.8rem;font-weight:600;display:inline-block;text-shadow:0 1px 2px rgba(0,0,0,0.2);box-shadow:0 2px 8px rgba(102,126,234,0.3)}
        .custom-time-display{background:linear-gradient(135deg,#ff6b6b 0%,#ee5a24 100%);color:white;padding:10px 16px;border-radius:25px;font-weight:700;font-size:0.9rem;display:inline-block;min-width:120px;text-align:center;text-shadow:0 1px 2px rgba(0,0,0,0.2);box-shadow:0 3px 10px rgba(255,107,107,0.3)}
        .custom-day-badge{background:linear-gradient(135deg,#10ac84 0%,#00d2d3 100%);color:white;padding:8px 16px;border-radius:18px;font-weight:600;font-size:0.85rem;display:inline-block;text-shadow:0 1px 2px rgba(0,0,0,0.2);box-shadow:0 2px 8px rgba(16,172,132,0.3)}
        .custom-subject-info{display:flex;flex-direction:column;gap:6px}
        .custom-subject-name{font-weight:700;color:#2c3e50;font-size:1rem}
        .custom-teacher-name{color:#7f8c8d;font-size:0.85rem;font-weight:500}
        .custom-classroom-info{background:#f8f9fa;padding:6px 12px;border-radius:12px;font-size:0.8rem;color:#495057;display:inline-block;border-left:3px solid rgba(98,74,255,0.5)}
        .custom-status-active{background:linear-gradient(135deg,#00b894 0%,#00cec9 100%);color:white;padding:4px 10px;border-radius:12px;font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px}
        .custom-no-schedule{text-align:center;padding:40px 20px;color:#6c757d;font-style:italic}
        .custom-no-schedule i{font-size:3rem;color:rgba(98,74,255,0.3);margin-bottom:15px;display:block}
        .custom-loading{text-align:center;padding:40px 20px;color:rgba(98,74,255,0.8)}
        .custom-loading i{font-size:2rem;margin-bottom:15px;animation:spin 1s linear infinite}
        @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
        .custom-error{text-align:center;padding:30px 20px;color:#dc3545;background:#fff5f5;border-left:4px solid #dc3545;margin:15px;border-radius:8px}
        .custom-date-filter{padding:15px 20px;background:#f8f9fa;border-bottom:1px solid #dee2e6}
        .custom-date-input{border:2px solid #e9ecef;border-radius:8px;padding:8px 12px;width:100%;transition:all 0.3s ease}
        .custom-date-input:focus{border-color:rgba(98,74,255,0.5);box-shadow:0 0 0 0.2rem rgba(98,74,255,0.15);outline:none}
        .custom-info-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
        .custom-info-badge{background:rgba(98,74,255,0.1);color:rgba(98,74,255,0.9);padding:4px 8px;border-radius:8px;font-size:0.75rem;font-weight:500}
    </style>
</head>
<body>
    <aside class="right-sidebar">
        <div class="sidebar-content">
            <?php if ($is_muellim): ?>
                <div class="profile-card">
                    <?php if (!empty($image)): ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile" class="profile-img">
                    <?php else: ?>
                        <div class="profile-img-placeholder">
                            <?php echo htmlspecialchars(substr($username, 0, 2), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <h5 class="profile-name"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></h5>
                    <div class="profile-info">
                        <p class="profile-item">
                            <span class="label">Vəzifə:</span>
                            <span class="value">Müəllim</span>
                        </p>
                        <p class="profile-item">
                            <span class="label">İxtisas:</span>
                            <span class="value truncated" data-toggle="modal" data-target="#ixtisasModal" data-ixtisas="<?php echo htmlspecialchars($ixtisas_adi, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php 
                                $ixtisas_truncated = strlen($ixtisas_adi) > 20 ? substr($ixtisas_adi, 0, 20) . '...' : $ixtisas_adi;
                                echo htmlspecialchars($ixtisas_truncated, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Filial:</span>
                            <span class="value"><?php echo htmlspecialchars($filial_adi, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                    </div>
                </div>
                <div class="nav-links">
                    <div class="list-group">
                        <a href="qr_muellim.php" class="qr_muellim list-group-item">
                            <i class="fas fa-qrcode"></i> QR Davamiyyət
                        </a>
                        <a href="muellimProfili.php" class="list-group-item">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        <a class="list-group-item dersler" onclick="openLessonsModal()">
                            <i class="fas fa-book"></i> Dərslər
                        </a>
                        <a href="muellimImtahanMelumat.php" class="list-group-item">
                            <i class="fas fa-graduation-cap"></i> İmtahan məlumatları
                        </a>
                        <a href="logout.php" class="list-group-item">
                            <i class="fas fa-sign-out-alt"></i> Çıxış et
                        </a>
                    </div>
                </div>
            <?php elseif ($is_valideyn): ?>
                <div class="profile-card">
                    <div class="profile-img-placeholder">
                        <?php echo htmlspecialchars(substr($username, 0, 2), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <h5 class="profile-name"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></h5>
                    <div class="profile-info">
                        <p class="profile-item">
                            <span class="label">Vəzifə:</span>
                            <span class="value">Valideyn</span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Telebe:</span>
                            <span class="value"><?php echo htmlspecialchars($telebe_name, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Valideyn Tipi:</span>
                            <span class="value"><?php echo htmlspecialchars($parent_type, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                    </div>
                </div>
                <div class="nav-links">
                    <div class="list-group">
                        <a href="valideynProfili.php" class="list-group-item">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        <a href="valideynTelebeMelumat.php" class="list-group-item">
                            <i class="fas fa-child"></i> Telebe Melumatlari
                        </a>
                        <a href="valideynImtahanMelumat.php" class="list-group-item">
                            <i class="fas fa-graduation-cap"></i> İmtahan məlumatları
                        </a>
                        <a href="logout.php" class="list-group-item">
                            <i class="fas fa-sign-out-alt"></i> Çıxış et
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="profile-card">
                    <?php if (!empty($image)): ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile" class="profile-img">
                    <?php else: ?>
                        <div class="profile-img-placeholder">
                            <?php echo htmlspecialchars(substr($username, 0, 2), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <h5 class="profile-name"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></h5>
                    <div class="profile-info">
                        <p class="profile-item">
                            <span class="label">Vəzifə:</span>
                            <span class="value">Telebe</span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Sinif:</span>
                            <span class="value"><?php echo htmlspecialchars($sinif, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <p class="profile-item">
                            <span class="label">İxtisas:</span>
                            <span class="value truncated" data-toggle="modal" data-target="#ixtisasModal" data-ixtisas="<?php echo htmlspecialchars($ixtisas, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php 
                                $ixtisas_truncated = strlen($ixtisas) > 20 ? substr($ixtisas, 0, 20) . '...' : $ixtisas;
                                echo htmlspecialchars($ixtisas_truncated, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Universitet:</span>
                            <span class="value truncated" data-toggle="modal" data-target="#ixtisasModal" data-ixtisas="<?php echo htmlspecialchars($universitet, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php 
                                $uni_truncated = strlen($universitet) > 20 ? substr($universitet, 0, 20) . '...' : $universitet;
                                echo htmlspecialchars($uni_truncated, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Qəbul ili:</span>
                            <span class="value"><?php echo htmlspecialchars($qebul_tarixi, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Müəllim:</span>
                            <span class="value"><?php echo htmlspecialchars($muellim_adi, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                    </div>
                </div>
                <div class="nav-links">
                    <div class="list-group">
                        <a href="qr_telebe.php" class="qr_telebe list-group-item">
                            <i class="fas fa-qrcode"></i> QR Davamiyyət
                        </a>
                        <a href="telebeProfili.php" class="list-group-item">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        <a class="list-group-item dersler" onclick="telebeopenLessonsModal()">
                            <i class="fas fa-book"></i> Dərslər
                        </a>
                        <a href="telebeImtahanMelumat.php" class="list-group-item">
                            <i class="fas fa-graduation-cap"></i> İmtahan məlumatları
                        </a>
                        <a href="logout.php" class="list-group-item">
                            <i class="fas fa-sign-out-alt"></i> Çıxış et
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Modal for displaying full ixtisas -->
    <div class="modal fade" id="ixtisasModal" tabindex="-1" aria-labelledby="ixtisasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ixtisasModalLabel">Tam İxtisas Adı</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="ixtisasFullName"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-toggle-container">
        <button class="sidebar-toggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <!-- Teacher Schedule Modal -->
    <div class="modal fade" id="lessonsModal" tabindex="-1" aria-labelledby="lessonsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lessonsModalLabel">Müəllim Cədvəli</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="filialFilter" class="font-weight-bold">Filial seçin:</label>
                            <select class="form-control" id="filialFilter" name="filialFilter">
                                <option value="">Bütün filiallar</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="telebeFilter" class="font-weight-bold">Tələbə seçin:</label>
                            <select class="form-control" id="telebeFilter" name="telebeFilter">
                                <option value="">Bütün tələbələr</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="dateFilter" class="font-weight-bold">Tarix üzrə filtrlə:</label>
                            <input type="date" class="form-control" id="dateFilter" name="dateFilter">
                        </div>
                    </div>
                    <div id="cedvelDisplay" class="mt-3">
                        <!-- Teacher's schedule will be loaded here via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Schedule Modal -->
    <div class="modal fade" id="telebelessonsModal" tabindex="-1" aria-labelledby="telebelessonsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="telebelessonsModalLabel">Mənim Cədvəlim</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="studentDateFilter" class="font-weight-bold">Tarix üzrə filtrlə:</label>
                            <input type="date" class="form-control" id="studentDateFilter" name="studentDateFilter">
                        </div>
                    </div>
                    <div id="telebeCedvelDisplay" class="mt-3">
                        <!-- Student schedule will be loaded here via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        var scheduleOptions = {};

        function openLessonsModal() {
            $('#lessonsModal').modal('show');
            loadTeacherCedvel();
        }

        function telebeopenLessonsModal() {
            $('#telebelessonsModal').modal('show');
            loadTelebeCedvel();
        }
        
        function displayScheduleTable(scheduleData, containerId, userType = 'student') {
            const container = $('#' + containerId);
            
            if (!Array.isArray(scheduleData) || scheduleData.length === 0) {
                container.html(`
                    <div class="alert alert-info text-center">
                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                        <h5>Cədvəl məlumatı yoxdur</h5>
                        <p>Hal-hazırda heç bir dərs cədvəli təyin edilməyib.</p>
                    </div>
                `);
                return;
            }

            let html = `
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="fas fa-calendar-alt mr-2"></i>Dərs Cədvəli</h4>
                        <small>${scheduleData.length} dərs tapıldı</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th><i class="fas fa-building mr-1"></i>Filial</th>
                                        <th><i class="fas fa-clock mr-1"></i>Vaxt</th>
                                        <th><i class="fas fa-calendar-day mr-1"></i>Gün</th>
                                        <th><i class="fas fa-info-circle mr-1"></i>Məlumatlar</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            scheduleData.forEach((item, index) => {
                const isTeacher = userType === 'teacher';
                
                html += `<tr>`;
                
                // Filial cell
                html += `<td>
                    <span class="badge badge-primary">
                        ${item.filial || 'Filial təyin edilməyib'}
                    </span>
                </td>`;
                
                // Time cell
                html += `<td>
                    <span class="badge badge-danger">
                        ${item.vaxt || 'Vaxt təyin edilməyib'}
                    </span>
                </td>`;
                
                // Day cell
                html += `<td>
                    <span class="badge badge-success">
                        ${item.gun || 'Gün təyin edilməyib'}
                    </span>
                </td>`;
                
                // Main info cell
                html += `<td>`;
                
                if (isTeacher && item.telebe) {
                    html += `<div class="mb-2">
                        <strong><i class="fas fa-user-graduate mr-1"></i>Tələbə:</strong> 
                        <span class="text-primary">${item.telebe}</span>
                    </div>`;
                }
                
                if (!isTeacher && item.muellim) {
                    html += `<div class="mb-2">
                        <strong><i class="fas fa-chalkboard-teacher mr-1"></i>Müəllim:</strong> 
                        <span class="text-primary">${item.muellim}</span>
                    </div>`;
                }
                
                html += `</td></tr>`;
            });
            
            html += `</tbody></table></div></div></div>`;
            container.html(html);
        }

        $(document).ready(function() {
            const sidebar = $('.right-sidebar');
            const toggle = $('.sidebar-toggle');

            function toggleSidebar() {
                sidebar.toggleClass('active');
                toggle.toggleClass('active');
            }

            toggle.on('click', toggleSidebar);

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.right-sidebar').length && 
                    !$(e.target).closest('.sidebar-toggle').length && 
                    sidebar.hasClass('active')) {
                    sidebar.removeClass('active');
                    toggle.removeClass('active');
                }
            });

            $('.profile-item .value.truncated').on('click', function() {
                const ixtisas = $(this).data('ixtisas');
                $('#ixtisasFullName').text(ixtisas);
            });

            // Filter change handlers for teacher
            $('#filialFilter, #telebeFilter, #dateFilter').on('change', function() {
                const selectedFilial = $('#filialFilter').val();
                const selectedTelebe = $('#telebeFilter').val();
                const selectedDate = $('#dateFilter').val();
                loadTeacherCedvel(selectedDate, selectedFilial, selectedTelebe);
            });

            // Date filter for student schedule
            $('#studentDateFilter').on('change', function() {
                const selectedDate = $(this).val();
                loadTelebeCedvel(selectedDate);
            });
        });

        function loadTeacherCedvel(date = null, filial = null, telebe = null) {
            const currentUid = <?php echo json_encode($session_u_id, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            const requestData = { 
                u_id: currentUid,
                action: 'get_schedule'
            };
            
            if (date) requestData.date = date;
            if (filial) requestData.filial = filial;
            if (telebe) requestData.telebe = telebe;
            
            $.ajax({
                url: 'get_schedule.php',
                type: 'GET',
                data: requestData,
                dataType: 'json',
                beforeSend: function() {
                    $('#cedvelDisplay').html(`
                        <div class="text-center p-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                            <h5>Cədvəl yüklənir...</h5>
                            <p class="text-muted">Zəhmət olmasa gözləyin</p>
                        </div>
                    `);
                },
                success: function(response) {
                    console.log('Teacher schedule response:', response);
                    
                    if (response && response.success === true) {
                        // Populate filter dropdowns if first load
                        if (!date && !filial && !telebe) {
                            if (response.filial_options) {
                                let filialOptions = '<option value="">Bütün filiallar</option>';
                                response.filial_options.forEach(function(f) {
                                    filialOptions += `<option value="${f}">${f}</option>`;
                                });
                                $('#filialFilter').html(filialOptions);
                            }
                            
                            if (response.telebe_options) {
                                let telebeOptions = '<option value="">Bütün tələbələr</option>';
                                response.telebe_options.forEach(function(t) {
                                    telebeOptions += `<option value="${t}">${t}</option>`;
                                });
                                $('#telebeFilter').html(telebeOptions);
                            }
                        }
                        
                        displayScheduleTable(response.cedvel, 'cedvelDisplay', 'teacher');
                    } else {
                        $('#cedvelDisplay').html(`
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <h5>Məlumat tapılmadı</h5>
                                <p>${response.message || 'Cədvəl məlumatı tapılmadı'}</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Teacher AJAX Error:', error);
                    console.log('Teacher Response Text:', xhr.responseText);
                    
                    $('#cedvelDisplay').html(`
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h5>Xəta baş verdi</h5>
                            <p>Cədvəl yüklənərkən problem yarandı. Zəhmət olmasa yenidən cəhd edin.</p>
                        </div>
                    `);
                }
            });
        }

        function loadTelebeCedvel(date = null) {
            const currentUid = <?php echo json_encode($session_u_id, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
            const requestData = { 
                u_id: currentUid,
                action: 'get_schedule'
            };
            
            if (date) {
                requestData.date = date;
            }
            
            $.ajax({
                url: 'get_schedule.php',
                type: 'GET',
                data: requestData,
                dataType: 'json',
                beforeSend: function() {
                    $('#telebeCedvelDisplay').html(`
                        <div class="text-center p-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                            <h5>Cədvəl yüklənir...</h5>
                            <p class="text-muted">Zəhmət olmasa gözləyin</p>
                        </div>
                    `);
                },
                success: function(response) {
                    console.log('Student schedule response:', response);
                    
                    if (response && response.success === true) {
                        displayScheduleTable(response.cedvel, 'telebeCedvelDisplay', 'student');
                    } else {
                        $('#telebeCedvelDisplay').html(`
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <h5>Məlumat tapılmadı</h5>
                                <p>${response.message || 'Cədvəl məlumatı tapılmadı'}</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Student AJAX Error:', error);
                    console.log('Student Response Text:', xhr.responseText);
                    
                    $('#telebeCedvelDisplay').html(`
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h5>Xəta baş verdi</h5>
                            <p>Cədvəl yüklənərkən problem yarandı. Zəhmət olmasa yenidən cəhd edin.</p>
                        </div>
                    `);
                }
            });
        }
    </script>
</body>
</html>