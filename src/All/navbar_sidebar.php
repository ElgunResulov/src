<?php
include('db.php');

app_start_secure_session();
app_require_auth($conn);

$user_id = $_SESSION['user_id'];
$check_user = $conn->prepare("SELECT u_id, username FROM users WHERE id = ?");
$check_user->bind_param("i", $user_id);
$check_user->execute();
$result = $check_user->get_result();
$user_data = $result->fetch_assoc();
$check_user->close();

$roles_requiring_uid = ['student', 'teacher', 'parent', 'examiner', 'staff'];
$current_role = $_SESSION['role'] ?? '';

if (!$user_data || empty($user_data['username'])) {
    session_unset();
    session_destroy();
    echo '<script>window.location.href = "Login.php";</script>';
    exit();
}

if (in_array($current_role, $roles_requiring_uid, true) && empty($user_data['u_id'])) {
    session_unset();
    session_destroy();
    echo '<script>window.location.href = "Login.php";</script>';
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$check_role = $conn->prepare("SELECT role FROM users WHERE id = ?");
$check_role->bind_param("i", $user_id);
$check_role->execute();
$result = $check_role->get_result();
$user_data = $result->fetch_assoc();
$user_role = $user_data['role'] ?? '';
$check_role->close();

$permissions = [];

if ($user_role === 'super_admin' || $user_role === 'admin') {
    $permissions = [
        'Hesablar', 'Ümumi istifadəçilər', 'Əsas', 'Mövzular', 'Müəllimlər', 'Dərslər', 'Tələbələr',
        'İmtahanlar', 'Dərs Cədvəli', 'Statistika', 'İxtisas üzrə idarəetmə', 'Əməkdaşlar', 'Qeydiyyatar'
    ];
} elseif ($user_role === 'operator') {
    $permissions = app_operator_default_permissions();
} else {
    $query = $conn->prepare("SELECT permissions FROM user_permissions WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    $query->close();

    if (isset($row['permissions'])) {
        if (substr($row['permissions'], 0, 1) === '[' || substr($row['permissions'], 0, 1) === '{') {
            // It's JSON
            $permissions_data = json_decode($row['permissions'], true);
            $permissions = is_array($permissions_data) ? $permissions_data : [];
        } else {
            $permissions = explode(",", trim($row['permissions']));
            $permissions = array_map('trim', $permissions);
        }
    }
}

function hasPermission($item) {
    global $permissions, $user_role;
    
    if ($user_role === 'super_admin' || $user_role === 'admin') {
        return true;
    }
    
    return in_array($item, $permissions);
}

function isActive($page) {
    global $current_page;
    return ($current_page === $page) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(app_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="images/bg-2.jpeg" type="image/x-icon">
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dropdown-menu { display: none; position: absolute; z-index: 1000; }
        .dropdown-menu.show { display: block; }
        .dropdown-toggle { cursor: pointer; }
        .mobile-user-section .dropdown-menu { position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); min-width: 120px; padding: 0.5rem 0; margin-top: 0.25rem; }
        .mobile-user-section { position: relative; }
        .student-section-header { cursor: pointer; padding: 10px 15px; width: 100%; margin: 10px 0; transition: all 0.3s ease; display: flex; align-items: center; justify-content: space-between; }
        .student-section-header:hover { background: rgba(67,97,238,0.2); }
        .student-section-header h6 { margin: 0; font-weight: 600; color: #4361ee; }
        .student-section-icon { transition: transform 0.3s ease; color: #4361ee; }
        .student-section-header.collapsed .student-section-icon { transform: rotate(-90deg); }
        .sidebar-item.student-nav-collapsed { display: none; }
        .navbar-header { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .navbar-brand { display: flex; align-items: center; justify-content: center; }
        .navbar-brand img { max-height: 44px; transition: all 0.3s ease; }
        .muellim_profile { margin-right: 10px; color: #4361ee; transform: scale(1.25); opacity: 0.3; transition: 0.3s ease-in-out; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .muellim_profile:hover { transform: scale(1.45); color: #3a56d4; opacity: 0.7; }
        .muellim_profile i { font-size: 16px; }
        .mob-name { font-family: Arial; font-weight: 500; display: none; position: relative; opacity: 0.8; font-size: 14px; white-space: normal; overflow: visible; text-overflow: clip; max-width: none; margin-left: 8px; color: #333; }
        .nav-toggler { background: none; border: none; padding: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .nav-toggler i { font-size: 18px; color: #333; transition: all 0.3s ease; }
        .nav-toggler:hover i { color: #4361ee; transform: scale(1.1); }
        @media (max-width: 991px) { .navbar-brand img { max-height: 38px; } }
        @media (max-width: 768px) { .navbar-header { padding: 0 10px; } .navbar-brand { order: 2; flex: 0 0 auto; } .navbar-brand img { max-height: 34px; } .nav-toggler { order: 1; } .mob-name { display: block; order: 3; } .mobile-user-section { display: flex; align-items: center; order: 3; } .long-username .navbar-brand img { max-height: 30px; } .very-long-username .navbar-brand img { max-height: 26px; } }
        @media (max-width: 576px) { .navbar-brand img { max-height: 30px; } .mob-name { max-width: 100px; font-size: 13px; } .long-username .navbar-brand img { max-height: 26px; } .very-long-username .navbar-brand img { max-height: 22px; } }
        @media (max-width: 400px) { .navbar-brand img { max-height: 26px; } .mob-name { max-width: 250px; font-size: 12px; } .long-username .navbar-brand img { max-height: 35px; } .very-long-username .navbar-brand img { max-height: 20px; } }
        #sidebarnav .other { position: relative; transition: all 0.3s ease; }
        #sidebarnav .other:hover { transform: translateX(3px); }
        #sidebarnav .other:hover::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background-color: rgba(67,10,255,0.7); border-radius: 0 2px 2px 0; }
        #cixis:hover::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background-color: rgba(255,10,10,0.7); }
        #sidebarnav .other:hover a { color: rgba(67,10,255,0.9); }
        #sidebarnav .other:hover i { transform: scale(1.1); transition: transform 0.3s ease; }
        #sidebarnav .other a.active { background-color: rgba(67,10,255,0.14); border-radius: 0 45px 45px 0; color: rgba(67,10,255,0.9); font-weight: 500; }
        #sidebarnav { width: 96%; }
        #sidebarnav .other a.active::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; border-radius: 0 2px 2px 0; }
        #cixis { background-color: transparent; transition: all 0.3s ease; }
        #cixis:hover { background-color: rgba(255,10,10,0.14); transform: translateX(3px); }
        .sidebar-link, .feather-icon { transition: all 0.3s ease; }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 1040;
        }
        body.sidebar-open { overflow: hidden; }
        body.sidebar-open .sidebar-overlay { display: block; }
        @media (max-width: 1169.98px) {
            .left-sidebar {
                left: -260px;
                transition: left 0.3s ease;
                z-index: 1050;
            }
            #main-wrapper.show-sidebar .left-sidebar,
            .left-sidebar.show {
                left: 0;
            }
        }
        @media (min-width: 1170px) {
            #main-wrapper[data-sidebartype="mini-sidebar"] .left-sidebar {
                width: 65px;
            }
            #main-wrapper[data-sidebartype="mini-sidebar"] .left-sidebar .hide-menu,
            #main-wrapper[data-sidebartype="mini-sidebar"] .left-sidebar .student-section-header h6,
            #main-wrapper[data-sidebartype="mini-sidebar"] .left-sidebar h6 {
                display: none;
            }
            #main-wrapper[data-sidebartype="mini-sidebar"] .left-sidebar .sidebar-link {
                text-align: center;
                padding: 12px 10px;
            }
            #main-wrapper[data-sidebartype="mini-sidebar"] .left-sidebar .feather-icon {
                margin-right: 0;
            }
            #main-wrapper[data-sidebartype="mini-sidebar"] .page-wrapper,
            #main-wrapper[data-sidebartype="mini-sidebar"] .main-content {
                margin-left: 65px;
            }
            #main-wrapper[data-sidebartype="mini-sidebar"] .topbar .top-navbar .navbar-header {
                width: 65px;
            }
            #main-wrapper[data-sidebartype="mini-sidebar"] .topbar .top-navbar .navbar-collapse {
                margin-left: 65px;
            }
        }
    </style>
</head>
<body>

<?php

if (!isset($conn)) {
    include('db.php');
}

$user_id = $_SESSION['user_id'];
$role_query = "SELECT role FROM users WHERE id = ?";
$role_stmt = $conn->prepare($role_query);
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
$user_role = $role_result->fetch_assoc()['role'] ?? null;
$role_stmt->close();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Qonaq';
$username_length = mb_strlen($username);
$username_class = '';

if ($username_length > 10) {
    $username_class = 'long-username';
}
if ($username_length > 15) {
    $username_class = 'very-long-username';
}
?>

    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
    <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
                        ?>
                            <li class="sidebar-item other">
                                <a  class="sidebar-link" href="qr_muellim.php" aria-expanded="false">
                                    <i data-feather="users" class="feather-icon"></i>
                                    <span class="hide-menu">QR Davamiyyət</span>
                                </a>
                            </li>
                        <?php
                    } 
                    ?>
                    <?php if (hasPermission('Hesablar')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Hesablar.php') ?>" href="Hesablar.php" aria-expanded="false">
                            <i data-feather="users" class="feather-icon"></i>
                            <span class="hide-menu">Hesablar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Ümumi istifadəçilər')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Ümumi_istifadəçilər.php') ?>" href="Ümumi_istifadəçilər.php" aria-expanded="false">
                            <i data-feather="user" class="feather-icon"></i>
                            <span class="hide-menu">Ümumi istifadəçilər</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Əsas')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('index.php') ?>" href="index.php" aria-expanded="false">
                            <i data-feather="home" class="feather-icon"></i>
                            <span class="hide-menu">Əsas</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['super_admin', 'admin'])) {
                        ?>
                            <li class="sidebar-item other">
                                <a class="sidebar-link" href="filiallar.php" aria-expanded="false">
                                    <i data-feather="git-branch" class="feather-icon"></i>
                                    <span class="hide-menu">Filiallar</span>
                                </a>
                            </li>
                        <?php
                        } 
                    ?>
                    <?php if (hasPermission('Mövzular')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Mövzular.php') ?>" href="Mövzular.php" aria-expanded="false">
                            <i data-feather="book" class="feather-icon"></i>
                            <span class="hide-menu">Mövzular</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Müəllimlər')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Müəllimlər.php') ?>" href="Müəllimlər.php" aria-expanded="false">
                            <i data-feather="user-check" class="feather-icon"></i>
                            <span class="hide-menu">Müəllimlər</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Dərslər')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Dərslər.php') ?>" href="Dərslər.php" aria-expanded="false">
                            <i data-feather="book-open" class="feather-icon"></i>
                            <span class="hide-menu">Dərslər</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Tələbələr')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Tələbələr.php') ?>" href="Tələbələr.php" aria-expanded="false">
                            <i data-feather="user-plus" class="feather-icon"></i>
                            <span class="hide-menu">Tələbələr</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('İmtahanlar')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('İmtahanlar.php') ?>" href="İmtahanlar.php" aria-expanded="false">
                            <i data-feather="edit-3" class="feather-icon"></i>
                            <span class="hide-menu">İmtahanlar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Dərs Cədvəli')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Dərs_Cədvəli.php') ?>" href="Dərs_Cədvəli.php" aria-expanded="false">
                            <i data-feather="calendar" class="feather-icon"></i>
                            <span class="hide-menu">Dərs Cədvəli</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Statistika')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Statistika.php') ?>" href="Statistika.php" aria-expanded="false">
                            <i data-feather="bar-chart-2" class="feather-icon"></i>
                            <span class="hide-menu">Statistika</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('İxtisas üzrə idarəetmə')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('İxtisas_üzrə_idarəetmə.php') ?>" href="İxtisas_üzrə_idarəetmə.php" aria-expanded="false">
                            <i data-feather="settings" class="feather-icon"></i>
                            <span class="hide-menu">İxtisaslar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Əməkdaşlar')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Əməkdaşlar.php') ?>" href="Əməkdaşlar.php" aria-expanded="false">
                            <i data-feather="users" class="feather-icon"></i>
                            <span class="hide-menu">Əməkdaşlar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Qeydiyyatar')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Qeydiyyatar.php') ?>" href="Qeydiyyatar.php" aria-expanded="false">
                            <i data-feather="user-plus" class="feather-icon"></i>
                            <span class="hide-menu">Qeydiyyatlar</span>
                        </a>
                    </li>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Odenisler.php') ?>" href="Odenisler.php" aria-expanded="false">
                            <i data-feather="credit-card" class="feather-icon"></i>
                            <span class="hide-menu">Ödənişlər</span>
                        </a>
                    </li>
                    <?php endif; ?>



                    <?php if (hasPermission('super_admin') || hasPermission('admin')): ?>
                    <li class="sidebar-item sidebar-section-toggle">
                        <div class="student-section-header" id="studentSectionToggle" role="button" tabindex="0" aria-expanded="true">
                            <h6>TƏLƏBƏ SƏHİFƏLƏR</h6>
                            <i class="fas fa-chevron-down student-section-icon"></i>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Elanlar')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Elanlar.php') ?>" href="Elanlar.php" aria-expanded="false">
                            <i data-feather="bell" class="feather-icon"></i>
                            <span class="hide-menu">Elanlar <span id="elanlar-count" style="margin-left:5px; font-weight:bolder;">(0)</span></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Academic Calendar Telebe')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('academicCalendar.php') ?>" href="academicCalendar.php" aria-expanded="false">
                            <i data-feather="calendar" class="feather-icon"></i>
                            <span class="hide-menu">Akademik təqvim</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Dərs Cədvəli Telebe')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('schedule.php') ?>" href="schedule.php" aria-expanded="false">
                            <i data-feather="clock" class="feather-icon"></i>
                            <span class="hide-menu">Dərs Cədvəli</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Zoom cədvəli')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('zoomSchedule.php') ?>" href="zoomSchedule.php" aria-expanded="false">
                            <i data-feather="video" class="feather-icon"></i>
                            <span class="hide-menu">Zoom cədvəli</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('İmtahan cədvəli')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('elist.php') ?>" href="elist.php" aria-expanded="false">
                            <i data-feather="calendar" class="feather-icon"></i>
                            <span class="hide-menu">İmtahan cədvəli</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Examination')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Examination.php') ?>" href="Examination.php" aria-expanded="false">
                            <i data-feather="calendar" class="feather-icon"></i>
                            <span class="hide-menu">İmtahanlar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('İmtahan nəticələri')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('eresults.php') ?>" href="eresults.php" aria-expanded="false">
                            <i data-feather="award" class="feather-icon"></i>
                            <span class="hide-menu">İmtahan nəticələri</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Elektron jurnal')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('studentEvaluation.php') ?>" href="studentEvaluation.php" aria-expanded="false">
                            <i data-feather="book-open" class="feather-icon"></i>
                            <span class="hide-menu">Elektron jurnal</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Tədris materialları')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('files.php') ?>" href="files.php" aria-expanded="false">
                            <i data-feather="folder" class="feather-icon"></i>
                            <span class="hide-menu">Tədris materialları</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Məmnunluq anketi')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('evaluation.php') ?>" href="evaluation.php" aria-expanded="false">
                            <i data-feather="star" class="feather-icon"></i>
                            <span class="hide-menu">Məmnunluq anketi</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Apellyasiya')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('Apellyasiya.php') ?>" href="Apellyasiya.php" aria-expanded="false">
                            <i data-feather="alert-circle" class="feather-icon"></i>
                            <span class="hide-menu">Apellyasiya</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('İmtahan Sualları')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('tests.php') ?>" href="tests.php" aria-expanded="false">
                            <i data-feather="help-circle" class="feather-icon"></i>
                            <span class="hide-menu">İmtahan Sualları</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Sərbəst işlər')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('independentworks.php') ?>" href="independentworks.php" aria-expanded="false">
                            <i data-feather="pen-tool" class="feather-icon"></i>
                            <span class="hide-menu">Sərbəst işlər</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Ev tapşırığı')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('homeworks.php') ?>" href="homeworks.php" aria-expanded="false">
                            <i data-feather="check-square" class="feather-icon"></i>
                            <span class="hide-menu">Ev tapşırığı</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Distant təhsil')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('distantEducation.php') ?>" href="distantEducation.php" aria-expanded="false">
                            <i data-feather="monitor" class="feather-icon"></i>
                            <span class="hide-menu">Distant təhsil</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Distant imtahan')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('distantExamination.php') ?>" href="distantExamination.php" aria-expanded="false">
                            <i data-feather="monitor" class="feather-icon"></i>
                            <span class="hide-menu">Distant imtahan</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Sertifikatlaşdırma')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('certification.php') ?>" href="certification.php" aria-expanded="false">
                            <i data-feather="award" class="feather-icon"></i>
                            <span class="hide-menu">Sertifikatlaşdırma</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Fayllar')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('examFiles.php') ?>" href="examFiles.php" aria-expanded="false">
                            <i data-feather="file" class="feather-icon"></i>
                            <span class="hide-menu">Fayllar</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Bir pəncərə xidməti')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('xidmet.html') ?>" href="xidmet.html" aria-expanded="false">
                            <i data-feather="grid" class="feather-icon"></i>
                            <span class="hide-menu">Bir pəncərə xidməti</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('E-kitabxana')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('kitabxana.html') ?>" href="kitabxana.html" aria-expanded="false">
                            <i data-feather="book" class="feather-icon"></i>
                            <span class="hide-menu">E-kitabxana</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Təhsil Tələbə Kredit Fondu')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('tehsil_telebe_kredit_fondu.html') ?>" href="tehsil_telebe_kredit_fondu.html" aria-expanded="false">
                            <i data-feather="dollar-sign" class="feather-icon"></i>
                            <span class="hide-menu">Təhsil Tələbə Kredit Fondu</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Məsləhət-müzakirə paneli')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('discussionPanel.php') ?>" href="discussionPanel.php" aria-expanded="false">
                            <i data-feather="message-square" class="feather-icon"></i>
                            <span class="hide-menu">Məsləhət-müzakirə paneli</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('Minor programı')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('minorProgrami.html') ?>" href="minorProgrami.html" aria-expanded="false">
                            <i data-feather="book-open" class="feather-icon"></i>
                            <span class="hide-menu">Minor programı</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('uni programı')): ?>
                    <li class="sidebar-item other"> 
                        <a class="sidebar-link <?= isActive('UniProgrami.html') ?>" href="UniProgrami.html" aria-expanded="false">
                            <i data-feather="book-open" class="feather-icon"></i>
                            <span class="hide-menu">UNI programı</span>
                        </a>
                    </li>
                    <?php endif; ?>
                        <li style="margin-top:8px;" class="sidebar-item"> 
                            <a id="cixis" class="sidebar-link sidebar-link" href="logout.php" aria-expanded="false">
                                <i style="color:red;" data-feather="log-out" class="feather-icon"></i>
                                <span class="hide-menu">Çıxış et</span>
                            </a>
                        </li>
                        <br>
                    </ul>
                </nav>
            </div>
        </aside>
        
        <header class="topbar" data-navbarbg="skin6">
            <nav class="navbar top-navbar navbar-expand-lg">
                <div class="navbar-header <?= $username_class ?>" data-logobg="skin6">
                    <button class="nav-toggler waves-effect waves-light" type="button" aria-label="Menyunu aç/bağla">
                        <i class="ti-menu"></i>
                    </button>
                    
                    <a class="navbar-brand" href="Home.php">
                        <img src="../assets/images/tis logo.png" alt="TIS Logo" class="img-fluid">
                    </a>
                    
                    <div class="mobile-user-section d-flex d-lg-none">
                        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['teacher', 'super_admin', 'admin'])) { ?>
                            <a href="muellim_vesiqe.php" class="muellim_profile">
                                <i class="fas fa-user"></i>
                            </a>
                        <?php } ?>
                        <button class="nav-link dropdown-toggle border-0 bg-transparent" id="mobileUserDropdown">
                            <!-- <span class="mob-name"><?php echo htmlspecialchars($username); ?></span> -->
                        </button>
                        <div hidden class="dropdown-menu" id="mobileUserDropdownMenu">
                            <a class="dropdown-item" href="logout.php">
                                <i style="color:red;" data-feather="power" class="svg-icon me-2 ms-1"></i>
                                Çıxış et
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="navbar-collapse collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav float-left me-auto ms-3 ps-1">
                        <li class="nav-item d-none d-md-block">
                        </li>
                    </ul>
                    
                    <div class="d-none d-lg-flex align-items-center">
                        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['teacher', 'super_admin', 'admin'])) { ?>
                            <a href="muellim_vesiqe.php" class="muellim_profile">
                                <i class="fas fa-user"></i>
                            </a>
                        <?php } ?>
                        <ul class="navbar-nav float-end">
                            <li class="nav-item dropdown">
                                    <span class="d-block d-lg-inline-block">
                                        <!-- <span class="text-dark"><?php echo htmlspecialchars($username); ?></span> -->
                                    </span>
                                <div hidden style="min-width:120px; right:47px;" class="dropdown-menu dropdown-menu-end dropdown-menu-right user-dd animated flipInY" id="userDropdownMenu">
                                    <a style="margin-bottom:-8px;" id="cixis" class="dropdown-item" href="logout.php">
                                        <i style="color:red;" data-feather="power" class="svg-icon me-2 ms-1"></i>
                                        Çıxış et
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script>
        window.APP_CSRF_TOKEN = <?php echo json_encode(app_csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        if (window.APP_CSRF_TOKEN && typeof jQuery !== 'undefined') {
            jQuery.ajaxSetup({
                beforeSend: function (xhr, settings) {
                    if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type || 'GET')) {
                        xhr.setRequestHeader('X-CSRF-Token', window.APP_CSRF_TOKEN);
                    }
                }
            });
        }
        if (window.APP_CSRF_TOKEN && typeof window.fetch === 'function') {
            const originalFetch = window.fetch;
            window.fetch = function (input, init) {
                init = init || {};
                const method = ((init.method || 'GET') + '').toUpperCase();
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(method)) {
                    const headers = new Headers(init.headers || {});
                    if (!headers.has('X-CSRF-Token')) {
                        headers.set('X-CSRF-Token', window.APP_CSRF_TOKEN);
                    }
                    init.headers = headers;
                }
                return originalFetch(input, init);
            };
        }
    </script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script>
        if (typeof jQuery !== 'undefined') {
            jQuery(function($) {
                $('.nav-toggler').off('click');
            });
        }
    </script>
    <script src="../assets/extra-libs/c3/d3.min.js"></script>
    <script src="../assets/extra-libs/c3/c3.min.js"></script>
    <script src="../assets/libs/chartist/dist/chartist.min.js"></script>
    <script src="../assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
    <script src="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.min.js"></script>
    <script src="../assets/extra-libs/jvector/jquery-jvectormap-world-mill-en.js"></script>
    <script src="../dist/js/pages/dashboards/dashboard1.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const dropdownToggle = document.getElementById('userDropdown');
            const dropdownMenu = document.getElementById('userDropdownMenu');
            
            if (dropdownToggle && dropdownMenu) {
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }

            const mobileDropdownToggle = document.getElementById('mobileUserDropdown');
            const mobileDropdownMenu = document.getElementById('mobileUserDropdownMenu');    
            if (mobileDropdownToggle && mobileDropdownMenu) {
                mobileDropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    mobileDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!mobileDropdownToggle.contains(e.target) && !mobileDropdownMenu.contains(e.target)) {
                        mobileDropdownMenu.classList.remove('show');
                    }
                });
            }

            const navToggler = document.querySelector('.nav-toggler');
            const mainWrapper = document.getElementById('main-wrapper');
            const leftSidebar = document.querySelector('.left-sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mobileBreakpoint = 1170;

            function isMobileSidebar() {
                return window.innerWidth < mobileBreakpoint;
            }

            function isSidebarOpen() {
                return mainWrapper && mainWrapper.classList.contains('show-sidebar');
            }

            function openSidebar() {
                if (!mainWrapper) return;
                mainWrapper.classList.add('show-sidebar');
                if (leftSidebar) leftSidebar.classList.add('show');
                document.body.classList.add('sidebar-open');
            }

            function closeSidebar() {
                if (!mainWrapper) return;
                mainWrapper.classList.remove('show-sidebar');
                if (leftSidebar) leftSidebar.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }

            function toggleSidebar() {
                if (!mainWrapper) return;
                if (isMobileSidebar()) {
                    if (isSidebarOpen()) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                    return;
                }

                const currentType = mainWrapper.getAttribute('data-sidebartype') || 'full';
                mainWrapper.setAttribute('data-sidebartype', currentType === 'mini-sidebar' ? 'full' : 'mini-sidebar');
            }

            if (navToggler) {
                navToggler.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isSidebarOpen()) {
                    closeSidebar();
                }
            });

            if (leftSidebar) {
                leftSidebar.addEventListener('click', function(e) {
                    if (!isMobileSidebar()) return;
                    const link = e.target.closest('#sidebarnav a.sidebar-link');
                    if (link && link.getAttribute('href') && link.getAttribute('href') !== '#') {
                        closeSidebar();
                    }
                });
            }

            window.addEventListener('resize', function() {
                if (!isMobileSidebar()) {
                    closeSidebar();
                }
            });

            function getStudentNavItems() {
                const toggle = document.getElementById('studentSectionToggle');
                if (!toggle) return [];
                const items = [];
                let el = toggle.closest('li');
                if (!el) return items;
                el = el.nextElementSibling;
                while (el) {
                    if (el.querySelector('#cixis')) break;
                    if (el.classList.contains('sidebar-item')) {
                        items.push(el);
                    }
                    el = el.nextElementSibling;
                }
                return items;
            }

            const studentSectionToggle = document.getElementById('studentSectionToggle');
            if (studentSectionToggle) {
                const studentItems = getStudentNavItems();
                let studentSectionOpen = true;

                function setStudentSectionOpen(open) {
                    studentSectionOpen = open;
                    studentSectionToggle.classList.toggle('collapsed', !open);
                    studentSectionToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    studentItems.forEach(function(item) {
                        item.classList.toggle('student-nav-collapsed', !open);
                    });
                }

                studentSectionToggle.addEventListener('click', function() {
                    setStudentSectionOpen(!studentSectionOpen);
                });

                studentSectionToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        setStudentSectionOpen(!studentSectionOpen);
                    }
                });
            }

            function updateElanlarCount() {
                fetch('elanlar/get_elanlar_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const countElement = document.getElementById('elanlar-count');
                        if (countElement) {
                            countElement.textContent = `(${data.count})`;
                        }
                    })
                    .catch(error => console.error('Error fetching elanlar count:', error));
            }

            setInterval(updateElanlarCount, 5000);
            updateElanlarCount();
            
            function adjustLogoSize() {
                const username = document.querySelector('.mob-name');
                const navbarHeader = document.querySelector('.navbar-header');
                
                if (username && window.innerWidth <= 768) {
                    const usernameLength = username.textContent.trim().length;
                    
                    if (usernameLength > 15) {
                        navbarHeader.classList.add('very-long-username');
                    } else if (usernameLength > 10) {
                        navbarHeader.classList.add('long-username');
                    }
                }
            }
            
            adjustLogoSize();
            window.addEventListener('resize', adjustLogoSize);
        });
</script>
</body>
</html>