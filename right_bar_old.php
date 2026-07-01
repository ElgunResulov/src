<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('db.php');
$username = "Unknown";
$sinif = "N/A";
$image = "";
$muellim_adi = "Unknown";
$ixtisas_adi = "Unknown";
$qebul_tarixi = "N/A";
$is_muellim = false; 
$is_valideyn = false;
$telebe_name = "N/A";
$parent_type = "N/A";

if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('Session expired or invalid user. Please log in.'); window.location.href='login.php';</script>";
    exit;
}

try {
    $session_u_id = $_SESSION['u_id'];
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
        $filial_adi_raw = $muellim_row['filial_adi'];
        if (!empty($filial_adi_raw)) {
            $filial_adi_clean = trim($filial_adi_raw, '[]');
            $filial_adi_array = array_filter(array_map('trim', explode(',', str_replace('"', '', $filial_adi_clean))));
            $filial_adi = htmlspecialchars(implode(', ', $filial_adi_array), ENT_QUOTES, 'UTF-8');
        } else {
            $filial_adi = "N/A";
        }
        $ixtisas_adi = htmlspecialchars($muellim_row['tehsil_ve_ixtisas'], ENT_QUOTES, 'UTF-8');
        $image_filename = !empty($muellim_row['profile']) ? $muellim_row['profile'] : '';
        $image_path = "../Uploads/profiles/" . $image_filename;
        $image = file_exists($image_path) ? $image_path : "";
    } else {
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
            $telebe_query = "SELECT username, sinif, photo, muellim_adi, ixtisas_adi, qebul_tarixi FROM telebeler WHERE u_id = ?";
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
                $sinif = htmlspecialchars($telebe_row['sinif'], ENT_QUOTES, 'UTF-8');
                
                $muellim_adi_raw = $telebe_row['muellim_adi'];
                if (!empty($muellim_adi_raw)) {
                    $muellim_adi_clean = trim($muellim_adi_raw, '[]');
                    $muellim_adi_array = array_filter(array_map('trim', explode(',', str_replace('"', '', $muellim_adi_clean))));
                    $muellim_adi = htmlspecialchars(implode(', ', $muellim_adi_array), ENT_QUOTES, 'UTF-8');
                } else {
                    $muellim_adi = "Unknown";
                }
                
                $ixtisas_adi = htmlspecialchars($telebe_row['ixtisas_adi'], ENT_QUOTES, 'UTF-8');
                $qebul_tarixi = !empty($telebe_row['qebul_tarixi'])
                    ? htmlspecialchars((new DateTime($telebe_row['qebul_tarixi']))->format('Y'), ENT_QUOTES, 'UTF-8')
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

        body{
            font-family: roboto;
        }

        @media (max-width: 992px) {
            #lessonsModal .modal-dialog {
                width: auto !important;
                max-width: 100vw !important;
                margin: 1rem;
            }
        }

        .dersler:hover{
         cursor: pointer;
        }

        :root {
            --sidebar-width: 360px;
            --primary-color: rgba(98, 74, 255, 0.73);
            --btn-toggle: rgba(98, 74, 255, 0.47);
            --primary-hover: rgba(98, 74, 255, 0.9);
            --text-color: #2a3547;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --transition: all 0.3s ease-out;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .right-sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            position: fixed;
            top: 70px;
            right: 0;
            transform: translateX(100%);
            height: calc(100vh - 70px);
            transition: transform 0.3s ease-out;
            z-index: 100;
            overflow-y: auto;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            padding: 0;
            will-change: transform;
        }

        .right-sidebar.active {
            transform: translateX(0);
        }

        .sidebar-content {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }

        .profile-card {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px 5px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), #6c5ce7);
        }

        .profile-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid white;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            transition: transform 0.3s ease;
        }

        .profile-img:hover {
            transform: scale(1.08);
        }

        .profile-name {
            margin: 0.8rem 0 1.2rem;
            font-size: 1.25rem;
            color: var(--text-color);
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .profile-info {
            width: 100%;
            padding: 0 10px;
        }

        .profile-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: -8px;
            padding: 6px 0px;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .profile-item .label {
            flex: 1;
            text-align: left;
            font-weight: 500;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .profile-item .value {
            flex: 1;
            text-align: right;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .profile-item .value.truncated {
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 150px;
        }

        .profile-status {
            display: inline-block;
            padding: 5px 14px;
            background: #e3f7ee;
            color: #28a745;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 12px;
            font-weight: 500;
        }

        .nav-links .list-group {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .nav-links .list-group-item {
            border: none;
            padding: 14px 20px;
            font-size: 0.95rem;
            color: var(--text-color);
            background: white;
            transition: background 0.2s ease, padding-left 0.2s ease;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #f1f3f9;
        }

        .nav-links .list-group-item:last-child {
            border-bottom: none;
        }

        .nav-links .list-group-item i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .nav-links .list-group-item:hover {
            background:rgba(230, 234, 251, 0.64);
            padding-left: 25px;
            text-decoration:none;
        }

        .nav-links .list-group-item.active {
            background: rgba(99, 93, 214, 0.1);
            color: var(--primary-color);
            font-weight: 500;
        }

        .notifications {
            margin-top: 25px;
        }

        .notifications h6 {
            font-size: 1rem;
            color: var(--text-color);
            margin-bottom: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        .notifications h6 i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .notification-item {
            display: flex;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease;
        }

        .notification-item:hover {
            transform: translateY(-2px);
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(93, 120, 255, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 3px;
            color: var(--text-color);
        }

        .notification-time {
            font-size: 0.75rem;
            color: #adb5bd;
            display: block;
        }

        .notification-badge {
            font-size: 0.7rem;
            padding: 3px 7px;
            background: var(--primary-color);
            border-radius: 10px;
            color: white;
            font-weight: 500;
            align-self: flex-start;
        }

        .sidebar-toggle-container {
            position: fixed;
            top: 18%;
            right: 0;
            z-index: 800;
            transition: transform 0.3s ease-out;
        }

        .sidebar-toggle {
            background: var(--btn-toggle);
            color: white;
            border: none;
            padding: 12px 6px;
            transition: 0.3s ease-in-out;
            border-radius: 15px 0 0 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 85px;
            cursor: pointer;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            will-change: transform;
        }

        .sidebar-toggle:hover {
            transition: 0.3s ease-in-out;
            background: var(--primary-hover);
            width: 42px;
        }

        .sidebar-toggle i {
            transition: transform 0.3s ease-out;
        }

        .sidebar-toggle.active i {
            transform: rotate(180deg);
        }

        .right-sidebar.active ~ .sidebar-toggle-container {
            transform: translateX(-360px);
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 100%;
            }

            .right-sidebar {
                width: 100%;
                height: 70vh;
                top: auto;
                bottom: 0;
                transform: translateY(100%);
                border-radius: 20px 20px 0 0;
                transition: transform 0.3s ease-out;
            }

            .right-sidebar.active {
                transform: translateY(0);
            }

            .sidebar-toggle-container {
                top: auto;
                bottom: 0;
                right: 50%;
                transform: translateX(50%);
                width: 120px;
            }

            .sidebar-toggle {
                width: 120px;
                height: 33px;
                border-radius: 20px 20px 0 0;
                padding: 0;
            }

            .sidebar-toggle:hover {
                width: 120px;
                height: 42px;
            }

            .right-sidebar.active ~ .sidebar-toggle-container {
                transform: translateX(50%) translateY(-70vh);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification-item {
            animation: fadeIn 0.3s ease forwards;
        }

        .notification-item:nth-child(1) { animation-delay: 0.1s; }
        .notification-item:nth-child(2) { animation-delay: 0.2s; }
        .notification-item:nth-child(3) { animation-delay: 0.3s; }

        .modal{
            z-index: 9999999999999;
        }
    </style>
</head>
<body>
    <aside class="right-sidebar">
        <div class="sidebar-content">
            <!-- Muellim Profile Section -->
            <?php if ($is_muellim): ?>
                <div class="profile-card muellim-profile">
                    <img src="<?php echo !empty($image) ? $image : '../assets/images/default_profile.png'; ?>" class="profile-img">
                    <h5 class="profile-name"><?php echo $username; ?></h5>
                    <div class="profile-info">
                         <p class="profile-item">
                            <span class="label">Filial:</span>
                            <span class="value" style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"><?php echo $filial_adi; ?></span>
                        </p>
                        <p class="profile-item">
                            <span class="label">İxtisas:</span>
                            <?php
                            $max_length = 12;
                            $ixtisas_truncated = strlen($ixtisas_adi) > $max_length ? substr($ixtisas_adi, 0, $max_length) . '...' : $ixtisas_adi;
                            $needs_modal = strlen($ixtisas_adi) > $max_length;
                            ?>
                            <span class="value <?php echo $needs_modal ? 'truncated' : ''; ?>" 
                                  style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"
                                  <?php if ($needs_modal): ?>data-toggle="modal" data-target="#ixtisasModal" data-ixtisas="<?php echo htmlspecialchars($ixtisas_adi, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
                                <?php echo htmlspecialchars($ixtisas_truncated, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </p>
                    </div>
                    <span hidden class="profile-status">
                        <i class="fas fa-circle"></i> Online
                    </span>
                </div>
                <div class="nav-links">
                    <div class="list-group">
                        <a href="telebeProfili.php" class="list-group-item">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        
                        <a class="list-group-item dersler" onclick="openLessonsModal()">
                            <i class="fas fa-book"></i> Dərslər
                        </a>
                        
                        <a href="logout.php" class="list-group-item" style="background-color: rgba(255, 0, 0, 0.73); color:white; font-weight:bold;">
                            <i style="color:white;" class="fas fa-sign-out-alt"></i> Çıxış et
                        </a>
                    </div>
                </div>
            <?php elseif ($is_valideyn): ?>
                <!-- Valideyn Profile Section -->
                <div class="profile-card valideyn-profile">
                    <h5 class="profile-name"><?php echo $username; ?></h5>
                    <div class="profile-info">
                        <p class="profile-item">
                            <span class="label">Tələbə:</span>
                            <span class="value" style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"><?php echo $telebe_name; ?></span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Valideyn tipi:</span>
                            <span class="value" style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"><?php echo $parent_type; ?></span>
                        </p>
                    </div>
                    <span hidden class="profile-status">
                        <i class="fas fa-circle"></i> Online
                    </span>
                </div>
                <div class="nav-links">
                    <div class="list-group">
                        <a href="valideyn_telebe_profil.php" class="list-group-item">
                            <i class="fas fa-user"></i> Ətraflı
                        </a>
                        <a href="logout.php" class="list-group-item" style="background-color: rgba(255, 0, 0, 0.73); color:white; font-weight:bold;">
                            <i style="color:white;" class="fas fa-sign-out-alt"></i> Çıxış et
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Telebe Profile Section -->
                <div class="profile-card telebe-profile">
                    <img src="<?php echo !empty($image) ? $image : ''; ?>" class="profile-img">
                    <h5 class="profile-name"><?php echo $username; ?></h5>
                    <div class="profile-info">
                        <p class="profile-item">
                            <span class="label">Qrup:</span>
                            <span class="value" style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"><?php echo $sinif; ?></span>
                        </p>
                        <p class="profile-item">
                            <span class="label">İxtisas:</span>
                            <?php
                            $max_length = 12;
                            $ixtisas_truncated = strlen($ixtisas_adi) > $max_length ? substr($ixtisas_adi, 0, $max_length) . '...' : $ixtisas_adi;
                            $needs_modal = strlen($ixtisas_adi) > $max_length;
                            ?>
                            <span class="value <?php echo $needs_modal ? 'truncated' : ''; ?>" 
                                  style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"
                                  <?php if ($needs_modal): ?>data-toggle="modal" data-target="#ixtisasModal" data-ixtisas="<?php echo htmlspecialchars($ixtisas_adi, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
                                <?php echo htmlspecialchars($ixtisas_truncated, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Qəbul ili:</span>
                            <span class="value" style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"><?php echo $qebul_tarixi; ?></span>
                        </p>
                        <p class="profile-item">
                            <span class="label">Müəllim:</span>
                            <span class="value" style="background-color:rgba(98, 74, 255, 0.73);color:white;padding:5px;border-radius:6px;font-size:12px;"><?php echo $muellim_adi; ?></span>
                        </p>
                    </div>
                    <span hidden class="profile-status">
                        <i class="fas fa-circle"></i> Online
                    </span>
                </div>
                <div class="nav-links">
                    <div class="list-group">
                        <a style="background-color: rgba(98, 74, 255, 0.73); color:white; font-weight:bold;" href="qr_telebe.php" class="qr_telebe list-group-item">
                            <i style="color:white; font-weight:bold;" class="fas fa-qrcode"></i> QR Davamiyyət
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
                        <a hidden href="uniMelumat.php" class="list-group-item">
                            <i class="fas fa-university"></i> Universitet məlumatları
                        </a>
                        <a href="logout.php" class="list-group-item" style="background-color: rgba(255, 0, 0, 0.73); color:white; font-weight:bold;">
                            <i style="color:white;" class="fas fa-sign-out-alt"></i> Çıxış et
                        </a>
                    </div>
                </div>
                <div hidden class="notifications">
                    <h6><i class="fas fa-bell"></i> Son Bildirişlər</h6>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Yeni qiymət əlavə edildi</div>
                            <span class="notification-time">10 dəqiqə əvvəl</span>
                        </div>
                        <span class="notification-badge">Yeni</span>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Dərs ləğv edildi</div>
                            <span class="notification-time">1 saat əvvəl</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Yoxlama işi yaxınlaşır</div>
                            <span class="notification-time">1 gün əvvəl</span>
                        </div>
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

    <div class="modal fade" id="lessonsModal" tabindex="-1" aria-labelledby="lessonsModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width:90vw;width:90%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lessonsModalLabel">Dərslər</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="telebeSelect">Tələbə seçin</label>
                        <select class="form-control" id="telebeSelect" name="telebeSelect">
                            <option value="">Seçin...</option>
                            <?php
                            // Fetch all telebe usernames from telebeler table
                            $telebe_query = "SELECT u_id, username FROM telebeler ORDER BY username ASC";
                            $telebe_result = $conn->query($telebe_query);
                            if ($telebe_result && $telebe_result->num_rows > 0) {
                                while ($row = $telebe_result->fetch_assoc()) {
                                    $uid = htmlspecialchars($row['u_id'], ENT_QUOTES, 'UTF-8');
                                    $uname = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
                                    echo "<option value=\"$uid\">$uname</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="telebelessonsModal" tabindex="-1" aria-labelledby="telebelessonsModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width:90vw;width:90%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="telebelessonsModalLabel">Dərslər</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                      <p>txt</p>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>

        function openLessonsModal() {
            var myModal = new bootstrap.Modal(document.getElementById('lessonsModal'), {
                keyboard: true
            });
            myModal.show();
        }

        function telebeopenLessonsModal() {
            var myModaltelebe = new bootstrap.Modal(document.getElementById('telebelessonsModal'), {
                keyboard: true
            });
            myModaltelebe.show();
        }
        
        $(document).ready(function() {
            const sidebar = $('.right-sidebar');
            const toggle = $('.sidebar-toggle');
            let isDragging = false;
            let startY;

            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            const toggleSidebar = debounce(function() {
                sidebar.toggleClass('active');
                toggle.toggleClass('active');
            }, 10);

            const closeSidebar = debounce(function() {
                sidebar.removeClass('active');
                toggle.removeClass('active');
            }, 10);

            toggle.on('click', toggleSidebar);

            toggle.on('touchstart', function(e) {
                if (window.innerWidth <= 768) {
                    startY = e.originalEvent.touches[0].clientY;
                    isDragging = true;
                }
            });

            $(document).on('touchmove', function(e) {
                if (!isDragging || window.innerWidth > 768) return;

                const currentY = e.originalEvent.touches[0].clientY;
                const diffY = startY - currentY; 

                if (diffY > 30 && !sidebar.hasClass('active')) {
                    toggleSidebar();
                    isDragging = false;
                } else if (diffY < -30 && sidebar.hasClass('active')) {
                    closeSidebar();
                    isDragging = false;
                }
            });

            $(document).on('touchend touchcancel', function() {
                isDragging = false;
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.right-sidebar').length && 
                    !$(e.target).closest('.sidebar-toggle').length && 
                    sidebar.hasClass('active')) {
                    closeSidebar();
                }
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.hasClass('active')) {
                    closeSidebar();
                }
            });

            $('.profile-item .value.truncated').on('click', function() {
                const ixtisas = $(this).data('ixtisas');
                $('#ixtisasFullName').text(ixtisas);
            });
        });
    </script>
</body>
</html>