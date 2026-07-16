<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'İstifadəçi autentifikasiyası tapılmadı.'
    ]);
    ob_end_flush();
    exit;
}

require_once '../db.php';
require_once __DIR__ . '/../user_credentials_helper.php';
app_ensure_plain_password_column($conn);
require_once __DIR__ . '/qr_helpers.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

function generateRandomUId() {
    $length = rand(7, 8);
    return substr(bin2hex(random_bytes(4)), 0, $length);
}

function generateRandomPassword() {
    return app_generate_random_password(8);
}

function sendCredentialsEmail($email, $username, $password, $fullName, $u_id, $photo = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.texnosoft.com.tr';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'account@texnosoft.com.tr';
        $mail->Password   = 'Kamran1962+++';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        // Recipients
        $mail->setFrom('account@texnosoft.com.tr', 'Magistratura AZ');
        $mail->addAddress($email, $fullName);
        
        // Add photo attachment if exists
        if (!empty($photo) && file_exists(MUELLIM_PROFILES_DIR . DIRECTORY_SEPARATOR . $photo)) {
            $mail->addAttachment(MUELLIM_PROFILES_DIR . DIRECTORY_SEPARATOR . $photo, 'teacher_photo.jpg');
        }
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Qeydiyyat Məlumatlarınız';
        
        $mail->Body = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; 
                    line-height: 1.7; 
                    background-color: #f5f5fa; 
                    margin: 0; 
                    padding: 0; 
                }
                .container { 
                    max-width: 640px; 
                    margin: 30px auto; 
                    background-color: #ffffff; 
                    border-radius: 12px; 
                    overflow: hidden; 
                    box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
                }
                .header { 
                    background: blueviolet; 
                    color: #ffffff; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 28px; 
                    font-weight: 700; 
                    color: #333;
                }
                .header p { 
                    margin: 8px 0 0; 
                    font-size: 16px; 
                    color: #333; 
                }
                .content { 
                    padding: 40px 30px; 
                }
                .content h2 { 
                    font-size: 22px; 
                    font-weight: 600; 
                    margin: 0 0 20px; 
                    color: #333;
                }
                .student-info { 
                    background-color: #fafafa; 
                    padding: 25px; 
                    border-radius: 8px; 
                    border: 1px solid #e8e8e8; 
                    margin: 25px 0; 
                }
                .student-info p { 
                    margin: 10px 0; 
                    font-size: 16px; 
                }
                .credentials { 
                    background-color: #e8f5e8; 
                    padding: 25px; 
                    border-radius: 8px; 
                    border-left: 5px solid #4CAF50; 
                    margin: 25px 0; 
                }
                .credentials p { 
                    margin: 10px 0; 
                    font-size: 16px; 
                }
                .important { 
                    color: #c62828; 
                    font-weight: 600; 
                    font-size: 16px; 
                    margin: 20px 0; 
                    padding: 12px; 
                    background-color: #fff3f3; 
                    border-radius: 4px; 
                }
                .photo { 
                    text-align: center; 
                    margin: 20px 0; 
                }
                .photo img { 
                    max-width: 160px; 
                    border-radius: 10px; 
                    border: 3px solid #4CAF50; 
                }
                .button { 
                    display: block; 
                    width: 90%; 
                    padding: 12px; 
                    background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%); 
                    color: #ffffff; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    font-weight: 600; 
                    font-size: 16px; 
                    text-align: center; 
                    transition: background 0.3s ease; 
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
                }
                .button:hover { 
                    background: linear-gradient(135deg, #66BB6A 0%, #4CAF50 100%); 
                }
                .footer { 
                    text-align: center; 
                    padding: 20px 30px; 
                    color: #666; 
                    font-size: 13px; 
                    background-color: #f5f5fa; 
                    border-top: 1px solid #e8e8e8; 
                }
                @media only screen and (max-width: 600px) { 
                    .container { 
                        margin: 10px; 
                        padding: 10px; 
                    }
                    .content { 
                        padding: 20px; 
                    }
                    .header h1 { 
                        font-size: 24px; 
                    }
                    .button { 
                        padding: 10px; 
                    }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Xoş gəlmisiniz!</h1>
                    <p>Magistratura AZ</p>
                </div>
                
                <div class='content'>
                    <h2>Hörmətli $fullName müəllim,</h2>
                    
                    <p>Magistratura AZ platformasında müəllim kimi qeydiyyatınız uğurla tamamlandı. Sizi komandamızda görməkdən məmnunuq! Aşağıda sistemə giriş məlumatlarınız və şəxsi məlumatlarınız verilmişdir:</p>
                    
                    <div class='student-info'>
                        <h3>Şəxsi Məlumatlar:</h3>
                        <p><strong>Ad və Soyad:</strong> $fullName</p>
                        <p><strong>E-Poçt:</strong> $email</p>
                        " . (!empty($photo) ? "<div class='photo'><p><strong>Şəkil:</strong> Əlavə edilmişdir</p></div>" : "") . "
                    </div>
                    
                    <div class='credentials'>
                        <h3>Giriş Məlumatları:</h3>
                        <p><strong>İstifadəçi adı:</strong> $username</p>
                        <p><strong>Şifrə:</strong> $password</p>
                    </div>
                    
                    <p class='important'>Diqqət: Bu məlumatları təhlükəsiz yerdə saxlayın və heç kimlə paylaşmayın.</p>
                    <p><a href='https://texnosoft.com.tr/TIS/src/All/Login.php' class='button'>Sistemə Daxil Ol</a></p>
                    <p>Təhsil sahəsində uğurlar diləyirik!</p>
                </div>
                
                <div class='footer'>
                    <p>Magistratura AZ Bütün hüquqlar qorunur.</p>
                    <p>Bu avtomatik göndərilən e-poçt mesajıdır.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "
        Hörmətli $fullName müəllim,
        
        Magistratura AZ platformasında müəllim kimi qeydiyyatınız uğurla tamamlandı. Sizi komandamızda görməkdən məmnunuq!
        
        Şəxsi Məlumatlar:
        Ad və Soyad: $fullName
        E-poçt: $email
        
        Giriş Məlumatları:
        İstifadəçi adı: $username
        Şifrə: $password
        
        Bu məlumatları təhlükəsiz yerdə saxlayın və heç kimlə paylaşmayın.
        
        Təhsil sahəsində uğurlar diləyirik!
        Magistratura AZ
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Yanlış sorğu metodu.'
    ]);
    ob_end_flush();
    exit;
}

$fenn_null = 'null';
$ad = isset($_POST['ad']) ? trim($_POST['ad']) : '';
$soyad = isset($_POST['soyad']) ? trim($_POST['soyad']) : '';
$fin_kod = isset($_POST['fin_kod']) ? app_normalize_fin_kod((string) $_POST['fin_kod']) : '';
$username = $ad . '.' . $soyad; // Siyahıda / sistemdə göstərilən ad
$loginUsername = $fin_kod; // users.username — FIN ilə giriş
$fenn = $fenn_null;
$active_status = isset($_POST['active_status']) ? trim($_POST['active_status']) : 'active';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefon = isset($_POST['telefon']) ? trim($_POST['telefon']) : null;
$tecrube = isset($_POST['tecrube']) ? trim($_POST['tecrube']) : null;
$ise_baslama_tarixi = isset($_POST['ise_baslama_tarixi']) ? trim($_POST['ise_baslama_tarixi']) : null;
$unvan = isset($_POST['unvan']) ? trim($_POST['unvan']) : null;
$tehsil_ve_ixtisas = isset($_POST['class']) ? trim($_POST['class']) : '';

if (empty($ad) || empty($soyad) || empty($fin_kod) || empty($email) || empty($tehsil_ve_ixtisas)) {
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

$dup_stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ? LIMIT 1');
if ($dup_stmt) {
    mysqli_stmt_bind_param($dup_stmt, 's', $loginUsername);
    mysqli_stmt_execute($dup_stmt);
    $dup_result = mysqli_stmt_get_result($dup_stmt);
    if ($dup_result && mysqli_num_rows($dup_result) > 0) {
        mysqli_stmt_close($dup_stmt);
        echo json_encode([
            'success' => false,
            'message' => 'Bu FIN kod artıq istifadə olunur.'
        ]);
        ob_end_flush();
        exit;
    }
    mysqli_stmt_close($dup_stmt);
}

$name_dup = mysqli_prepare($conn, 'SELECT id FROM muellimler_new WHERE username = ? LIMIT 1');
if ($name_dup) {
    mysqli_stmt_bind_param($name_dup, 's', $username);
    mysqli_stmt_execute($name_dup);
    $name_result = mysqli_stmt_get_result($name_dup);
    if ($name_result && mysqli_num_rows($name_result) > 0) {
        mysqli_stmt_close($name_dup);
        echo json_encode([
            'success' => false,
            'message' => 'Bu Ad.Soyad artıq mövcuddur.'
        ]);
        ob_end_flush();
        exit;
    }
    mysqli_stmt_close($name_dup);
}

$profile_image = null;
if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    ensureUploadDir(MUELLIM_PROFILES_DIR);
    
    $file_name = $_FILES['profileImage']['name'];
    $file_tmp = $_FILES['profileImage']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_ext, $allowed_extensions)) {
        $new_file_name = uniqid('profile_') . '.' . $file_ext;
        $upload_path = MUELLIM_PROFILES_DIR . DIRECTORY_SEPARATOR . $new_file_name;
        
        if (!move_uploaded_file($file_tmp, $upload_path)) {
            echo json_encode([
                'success' => false,
                'message' => 'Şəkil yükləmə zamanı xəta baş verdi.'
            ]);
            ob_end_flush();
            exit;
        }
        $profile_image = $new_file_name;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Yalnız JPG, JPEG, PNG və GIF formatları dəstəklənir.'
        ]);
        ob_end_flush();
        exit;
    }
}

$u_id = generateRandomUId();
$raw_password = $fin_kod . '5';
$password_hash = app_hash_password($raw_password);
mysqli_begin_transaction($conn);

try {
    $user_sql = "INSERT INTO users (u_id, username, password, plain_password, role, created_at) VALUES (?, ?, ?, ?, 'teacher', NOW())";
    $stmt = mysqli_prepare($conn, $user_sql);
    if (!$stmt) {
        throw new Exception('İstifadəçi sorğusu hazırlanarkən xəta: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'ssss', $u_id, $loginUsername, $password_hash, $raw_password);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('İstifadəçi əlavə edilməsi zamanı xəta: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
    
    $teacher_sql = "INSERT INTO muellimler_new (u_id, username, fenn, active_status, email, telefon, tecrube, ise_baslama_tarixi, unvan, tehsil_ve_ixtisas, profile, qr_code, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', NOW())";
    $stmt = mysqli_prepare($conn, $teacher_sql);
    if (!$stmt) {
        throw new Exception('Müəllim sorğusu hazırlanarkən xəta: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param(
        $stmt,
        'sssssssssss',
        $u_id,
        $username,
        $fenn,
        $active_status,
        $email,
        $telefon,
        $tecrube,
        $ise_baslama_tarixi,
        $unvan,
        $tehsil_ve_ixtisas,
        $profile_image
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Müəllim əlavə edilməsi zamanı xəta: ' . mysqli_stmt_error($stmt));
    }
    $teacher_id = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $teacher_row = [
        'id' => $teacher_id,
        'u_id' => $u_id,
        'username' => $username,
        'qr_code' => '',
    ];
    $teacher_row = qr_activate_teacher($conn, $teacher_row);
    $qr_code_filename = $teacher_row['qr_code'];
    
    // Send email with credentials
    $fullName = $ad . ' ' . $soyad;
    $email_sent = sendCredentialsEmail($email, $loginUsername, $raw_password, $fullName, $u_id, $profile_image);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Müəllim və QR kod uğurla əlavə edildi.'
            . ' FIN/Giriş: ' . $loginUsername
            . ' · Şifrə: ' . $raw_password
            . ($email_sent ? ' E-poçt göndərildi.' : ' E-poçt göndərilmədi.'),
        'u_id' => $u_id,
        'username' => $username,
        'fin_kod' => $loginUsername,
        'password' => $raw_password,
        'qr_code' => $qr_code_filename
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Error in muellim_elave_et.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Xəta: ' . $e->getMessage()
    ]);
}

ob_end_flush();
mysqli_close($conn);
?>