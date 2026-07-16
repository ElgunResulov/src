<?php
// Start output buffering to prevent premature output
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'İstifadəçi autentifikasiyası tapılmadı.'
    ]);
    ob_end_flush();
    exit;
}

// Include database connection
require_once '../db.php';
require_once __DIR__ . '/../user_credentials_helper.php';
app_ensure_plain_password_column($conn);
require_once __DIR__ . '/qr_helpers.php';

// Set header to return JSON response
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Yanlış sorğu metodu.'
    ]);
    ob_end_flush();
    exit;
}

// Get form data
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$ad = isset($_POST['ad']) ? trim($_POST['ad']) : '';
$soyad = isset($_POST['soyad']) ? trim($_POST['soyad']) : '';
$fin_kod = isset($_POST['fin_kod']) ? app_normalize_fin_kod((string) $_POST['fin_kod']) : '';
$username = $ad . '.' . $soyad;
$loginUsername = $fin_kod;
$fenn = isset($_POST['fenn']) ? trim($_POST['fenn']) : '';
$active_status = isset($_POST['active_status']) ? trim($_POST['active_status']) : 'active';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefon = isset($_POST['telefon']) ? trim($_POST['telefon']) : null;
$tecrube = isset($_POST['tecrube']) ? trim($_POST['tecrube']) : null;
$ise_baslama_tarixi = isset($_POST['ise_baslama_tarixi']) ? trim($_POST['ise_baslama_tarixi']) : null;
$unvan = isset($_POST['unvan']) ? trim($_POST['unvan']) : null;
$tehsil_ve_ixtisas = isset($_POST['class']) ? trim($_POST['class']) : '';

// Validate required fields
if ($id <= 0 || empty($ad) || empty($soyad) || empty($fin_kod) || empty($email) || empty($tehsil_ve_ixtisas)) {
    echo json_encode([
        'success' => false,
        'message' => 'Zəhmət olmasa bütün vacib xanaları doldurun (Ad, Soyad, FIN kod, Email, Təhsil və İxtisas).'
    ]);
    ob_end_flush();
    exit;
}

if (!app_is_valid_fin_kod($fin_kod)) {
    echo json_encode([
        'success' => false,
        'message' => 'FIN kod 7 simvol olmalıdır (yalnız A-Z və 0-9).'
    ]);
    ob_end_flush();
    exit;
}

// Eyni FIN başqa müəllimdə olmasın
$dup_sql = 'SELECT u.id FROM users u
            INNER JOIN muellimler_new m ON m.u_id = u.u_id
            WHERE u.username = ? AND m.id <> ?
            LIMIT 1';
$dup_stmt = mysqli_prepare($conn, $dup_sql);
if ($dup_stmt) {
    mysqli_stmt_bind_param($dup_stmt, 'si', $loginUsername, $id);
    mysqli_stmt_execute($dup_stmt);
    $dup_result = mysqli_stmt_get_result($dup_stmt);
    if ($dup_result && mysqli_num_rows($dup_result) > 0) {
        mysqli_stmt_close($dup_stmt);
        echo json_encode([
            'success' => false,
            'message' => 'Bu FIN kod artıq başqa hesabda istifadə olunur.'
        ]);
        ob_end_flush();
        exit;
    }
    mysqli_stmt_close($dup_stmt);
}

// Handle profile image upload
$profile_update = "";
$new_file_name = null;
if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../Uploads/profiles/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!is_writable($upload_dir)) {
        echo json_encode([
            'success' => false,
            'message' => 'Profil şəkili qovluğu yazmağa icazəli deyil.'
        ]);
        ob_end_flush();
        exit;
    }
    
    $file_name = $_FILES['profileImage']['name'];
    $file_tmp = $_FILES['profileImage']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_ext, $allowed_extensions)) {
        $new_file_name = uniqid('profile_') . '.' . $file_ext;
        $upload_path = $upload_dir . $new_file_name;
        
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Delete old profile image
            $sql = "SELECT profile FROM muellimler_new WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $old_profile = $row['profile'];
                if (!empty($old_profile) && file_exists($upload_dir . $old_profile)) {
                    @unlink($upload_dir . $old_profile);
                }
            }
            mysqli_stmt_close($stmt);
            
            $profile_update = ", profile = ?";
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Şəkil yükləmə zamanı xəta baş verdi.'
            ]);
            ob_end_flush();
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Yalnız JPG, JPEG, PNG və GIF formatları dəstəklənir.'
        ]);
        ob_end_flush();
        exit;
    }
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update muellimler_new table
    $sql = "UPDATE muellimler_new SET 
            username = ?, 
            fenn = ?, 
            active_status = ?, 
            email = ?, 
            telefon = ?, 
            tecrube = ?, 
            ise_baslama_tarixi = ?, 
            unvan = ?, 
            tehsil_ve_ixtisas = ?
            $profile_update 
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Sorğu hazırlanarkən xəta: ' . mysqli_error($conn));
    }

    if ($profile_update) {
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssi',
            $username,
            $fenn,
            $active_status,
            $email,
            $telefon,
            $tecrube,
            $ise_baslama_tarixi,
            $unvan,
            $tehsil_ve_ixtisas,
            $new_file_name,
            $id
        );
    } else {
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssi',
            $username,
            $fenn,
            $active_status,
            $email,
            $telefon,
            $tecrube,
            $ise_baslama_tarixi,
            $unvan,
            $tehsil_ve_ixtisas,
            $id
        );
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Müəllim məlumatları yenilənməsi zamanı xəta: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    // Update users: giriş adı = FIN, şifrə = FIN + 5
    $raw_password = $fin_kod . '5';
    $password_hash = app_hash_password($raw_password);
    $user_sql = "UPDATE users SET username = ?, password = ?, plain_password = ? WHERE u_id = (SELECT u_id FROM muellimler_new WHERE id = ?)";
    $stmt = mysqli_prepare($conn, $user_sql);
    if (!$stmt) {
        throw new Exception('İstifadəçi sorğusu hazırlanarkən xəta: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'sssi', $loginUsername, $password_hash, $raw_password, $id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('İstifadəçi yenilənməsi zamanı xəta: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    $teacher_sql = 'SELECT id, u_id, username, qr_code, active_status FROM muellimler_new WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($conn, $teacher_sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $teacher_result = mysqli_stmt_get_result($stmt);
    $teacher_row = mysqli_fetch_assoc($teacher_result);
    mysqli_stmt_close($stmt);

    if ($teacher_row) {
        qr_activate_teacher($conn, $teacher_row, true);
    }

    // Commit transaction
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Müəllim məlumatları uğurla yeniləndi. FIN/Giriş: ' . $loginUsername . ' · Şifrə: ' . $raw_password,
        'username' => $username,
        'fin_kod' => $loginUsername,
        'password' => $raw_password,
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Error in muellim_redakte_et.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Xəta: ' . $e->getMessage()
    ]);
}

ob_end_flush();
mysqli_close($conn);
?>