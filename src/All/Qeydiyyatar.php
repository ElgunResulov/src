<?php
include('db.php');
require_once __DIR__ . '/qeydiyyatar/odenis_helpers.php';
odenis_ensure_columns($conn);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$isEmbed = (isset($_GET['embed']) && $_GET['embed'] === '1')
    || (isset($_POST['embed']) && $_POST['embed'] === '1');

if (!$isEmbed && (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']))) {
    header("Location: Login.php"); exit();
}

function generateUID($conn, $length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $uid = substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
        $check_query = "SELECT u_id FROM users WHERE u_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 's', $uid);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $exists = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($check_stmt);
    } while ($exists);
    return $uid;
}

function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
}

function sendCredentialsEmail($email, $username, $password, $fullName, $u_id, $photo = '') {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.texnosoft.com.tr';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'account@texnosoft.com.tr';
        $mail->Password   = 'Kamran1962+++';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->setFrom('account@texnosoft.com.tr', 'Magistratura AZ');
        $mail->addAddress($email, $fullName);
        if (!empty($photo) && file_exists('telebeler/' . $photo)) {
            $mail->addAttachment('telebeler/' . $photo, 'student_photo.jpg');
        }
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Qeydiyyat Məlumatlarınız';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #2d3748; padding: 20px 10px; }
                .email-wrapper { max-width: 600px; margin: 0 auto; }
                .container { background: #ffffff; overflow: hidden; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 50px 40px; text-align: center; position: relative; overflow: hidden; }
                .header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: pulse 15s ease-in-out infinite; }
                @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
                .header-icon { width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; backdrop-filter: blur(10px); border: 2px solid rgba(255,255,255,0.3); position: relative; z-index: 1; }
                .header-icon svg { width: 40px; height: 40px; fill: white; }
                .header h1 { color: white; font-size: 32px; font-weight: 700; margin-bottom: 8px; position: relative; z-index: 1; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
                .header p { color: rgba(255,255,255,0.95); font-size: 16px; position: relative; z-index: 1; }
                .content { padding: 45px 40px; }
                .greeting { font-size: 24px; color: #1a202c; font-weight: 600; margin-bottom: 20px; }
                .intro-text { font-size: 16px; color: #4a5568; margin-bottom: 30px; line-height: 1.8; }
                .info-card { background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border-radius: 12px; padding: 25px; margin: 25px 0; border: 1px solid #e2e8f0; position: relative; overflow: hidden; }
                .info-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); }
                .info-card h3 { color: #667eea; font-size: 18px; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; }
                .info-card h3::before { content: ''; width: 6px; height: 6px; background: #667eea; border-radius: 50%; margin-right: 10px; display: inline-block; }
                .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
                .info-row:last-child { border-bottom: none; }
                .info-label { font-weight: 600; color: #4a5568; min-width: 140px; font-size: 15px; }
                .info-value { color: #2d3748; font-size: 15px; word-break: break-all; }
                .credentials-card { background: linear-gradient(135deg, #fef5e7 0%, #fdebd0 100%); border-radius: 12px; padding: 25px; margin: 25px 0; border: 1px solid #f9e79f; position: relative; overflow: hidden; }
                .credentials-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #f39c12 0%, #e67e22 100%); }
                .credentials-card h3 { color: #d68910; font-size: 18px; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; }
                .credentials-card h3::before { content: '🔑'; margin-right: 10px; font-size: 20px; }
                .credentials-card .info-row { border-bottom: 1px solid #f9e79f; }
                .alert-box { background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%); border-left: 4px solid #ffc107; padding: 20px; margin: 25px 0; border-radius: 8px; display: flex; align-items: start; }
                .alert-icon { font-size: 24px; margin-right: 15px; }
                .alert-text { color: #856404; font-size: 14px; line-height: 1.6; font-weight: 500; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 40px; text-decoration: none; border-radius: 50px; margin: 30px 0 20px 0; font-weight: 600; font-size: 16px; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); transition: all 0.3s ease; text-align: center; }
                .button:hover { transform: translateY(-2px); box-shadow: 0 15px 35px rgba(102, 126, 234, 0.5); }
                .success-message { text-align: center; padding: 20px 0; }
                .success-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px; }
                .success-icon::after { content: '✓'; color: white; font-size: 30px; font-weight: bold; }
                .footer { background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); padding: 30px 40px; text-align: center; color: rgba(255,255,255,0.8); }
                .footer-logo { font-size: 20px; font-weight: 700; color: white; margin-bottom: 10px; }
                .footer p { font-size: 13px; margin: 8px 0; line-height: 1.6; }
                .divider { height: 1px; background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%); margin: 30px 0; }
                @media only screen and (max-width: 600px) {
                    body { padding: 20px 10px; }
                    .content { padding: 30px 25px; }
                    .header { padding: 40px 25px; }
                    .header h1 { font-size: 26px; }
                    .greeting { font-size: 20px; }
                    .info-row { flex-direction: column; }
                    .info-label { margin-bottom: 5px; }
                }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='container'>
                    <div class='header'>
                        <div class='header-icon'>
                            <svg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
                                <path d='M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V7.3l7-3.11v8.8z'/>
                            </svg>
                        </div>
                        <h1>Xoş gəlmisiniz!</h1>
                        <p>Magistratura AZ Platforması</p>
                    </div>
                    <div class='content'>
                        <div class='success-message'>
                            <div class='success-icon'></div>
                        </div>
                        <h2 class='greeting'>Hörmətli $fullName,</h2>
                        <p class='intro-text'>Magistratura AZ platformasında tələbə kimi qeydiyyatınız uğurla tamamlandı! Sizi tələbələrimiz arasında görməkdən məmnunuq və təhsil səyahətinizdə sizə dəstək olmağa hazırıq.</p>
                        <div class='info-card'>
                            <h3>Şəxsi Məlumatlarınız</h3>
                            <div class='info-row'>
                                <span class='info-label'>Ad və Soyad:</span>
                                <span class='info-value'>$fullName</span>
                            </div>
                            <div class='info-row'>
                                <span class='info-label'>E-Poçt Ünvanı:</span>
                                <span class='info-value'>$email</span>
                            </div>
                        </div>
                        <div class='credentials-card'>
                            <h3>Giriş Məlumatları</h3>
                            <div class='info-row'>
                                <span class='info-label'>İstifadəçi Adı:</span>
                                <span class='info-value'>$username</span>
                            </div>
                            <div class='info-row'>
                                <span class='info-label'>Şifrəniz:</span>
                                <span class='info-value'>$password</span>
                            </div>
                        </div>
                        <div class='alert-box'>
                            <span class='alert-icon'>⚠️</span>
                            <div class='alert-text'>
                                <strong>Təhlükəsizlik xatırlatması:</strong> Bu məlumatları təhlükəsiz yerdə saxlayın və heç kimlə paylaşmayın. İlk girişdən sonra şifrənizi dəyişdirməyiniz tövsiyə olunur.
                            </div>
                        </div>                      
                        <div style='text-align: center;'>
                            <a href='https://saleh.az/TIS/src/All/Login.php' class='button'>Sistemə Daxil Ol →</a>
                        </div>
                    </div>   
                    <div class='footer'>
                        <div class='footer-logo'>Magistratura AZ</div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        $mail->send();
        return true;       
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
$successMessage = '';
$errorMessage = '';
$form_data = [
    'ad' => '', 'soyad' => '', 'universitet' => '', 'ixtisas' => '', 'ata_adi' => '',
    'qebul_ili' => '', 'dogum_tarixi' => '', 'is_nomresi' => '', 'telefon' => '',
    'fin_kod' => '', 'email' => '', 'bakalavr_bali' => '', 'magistr_bali' => '',
    'bolme' => '', 'tedris' => '', 'vaxt' => [], 'services' => [], 'sinif_qeyd' => '',
    'menbe' => [], 'elave_qeyd_1' => '', 'elave_qeyd_2' => '', 'elave_qeyd_3' => '',
    'tarix' => '',
    'tedris_ili' => odenis_default_tedris_ili(),
    'odenis_novu' => 'ayliq',
    'tehsil_haqqi' => '',
    'baslama_tarixi' => date('Y-m-d'),
];

function form_val(string $key): string {
    global $form_data;
    return htmlspecialchars((string)($form_data[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

function form_checked(string $field, string $value): string {
    global $form_data;
    $items = $form_data[$field] ?? [];
    if (!is_array($items)) {
        $items = json_decode((string)$items, true) ?: [];
    }
    return in_array($value, $items, true) ? 'checked' : '';
}

function form_radio(string $name, string $value): string {
    global $form_data;
    return (($form_data[$name] ?? '') === $value) ? 'checked' : '';
}

function form_selected(string $name, string $value): string {
    global $form_data;
    return (($form_data[$name] ?? '') === $value) ? 'selected' : '';
}

if (isset($_GET['success']) && !empty($_SESSION['qeydiyyat_form_data'])) {
    $form_data = array_merge($form_data, $_SESSION['qeydiyyat_form_data']);
    $successMessage = 'Müraciətiniz uğurla qəbul edildi! Məlumatlar formda öz yerlərində göstərilir. Qeydiyyat formu və müqavilə avtomatik çap üçün açılır; istənilən vaxt "Çap Et" və ya "Müqavilə" düymələrindən istifadə edə bilərsiniz.';
    unset($_SESSION['qeydiyyat_form_data'], $_SESSION['qeydiyyat_success']);
}

$printId = 0;
if (!empty($_GET['print_id'])) {
    $printId = (int) $_GET['print_id'];
    $_SESSION['last_qeydiyyat_print_id'] = $printId;
} elseif (!empty($_SESSION['last_qeydiyyat_print_id'])) {
    $printId = (int) $_SESSION['last_qeydiyyat_print_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_registration'])) {
    try {
        $ad    = trim($_POST['ad'] ?? '');
        $soyad = trim($_POST['soyad'] ?? '');

        $ad_soyad     = $ad . ($ad && $soyad ? '.' : '') . $soyad;
        $ad_soyad_db  = mysqli_real_escape_string($conn, $ad_soyad);
        $fullName     = $ad . ($ad && $soyad ? ' ' : '') . $soyad;
        $username     = $ad_soyad;

        $ata_adi       = mysqli_real_escape_string($conn, trim($_POST['ata_adi'] ?? ''));
        $dogum_tarixi  = trim($_POST['dogum_tarixi'] ?? ''); // trim first
        $telefon       = mysqli_real_escape_string($conn, trim($_POST['telefon'] ?? ''));
        $email         = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $universitet   = mysqli_real_escape_string($conn, trim($_POST['universitet'] ?? ''));
        $ixtisas       = mysqli_real_escape_string($conn, trim($_POST['ixtisas'] ?? ''));
        $qebul_ili     = mysqli_real_escape_string($conn, trim($_POST['qebul_ili'] ?? ''));
        $is_nomresi    = mysqli_real_escape_string($conn, trim($_POST['is_nomresi'] ?? ''));
        $fin_kod       = mysqli_real_escape_string($conn, trim($_POST['fin_kod'] ?? ''));
        $bakalavr_bali = mysqli_real_escape_string($conn, trim($_POST['bakalavr_bali'] ?? ''));
        $magistr_bali  = mysqli_real_escape_string($conn, trim($_POST['magistr_bali'] ?? ''));

        $bolme         = mysqli_real_escape_string($conn, $_POST['bolme']    ?? '');
        $tedris        = mysqli_real_escape_string($conn, $_POST['tedris']   ?? '');
        $vaxt          = json_encode($_POST['vaxt']    ?? []);
        $services      = json_encode($_POST['services'] ?? []);
        $sinif_qeyd    = mysqli_real_escape_string($conn, trim($_POST['sinif_qeyd'] ?? ''));
        $menbe         = json_encode($_POST['menbe']   ?? []);

        $elave_qeyd_1  = mysqli_real_escape_string($conn, trim($_POST['elave_qeyd_1'] ?? ''));
        $elave_qeyd_2  = mysqli_real_escape_string($conn, trim($_POST['elave_qeyd_2'] ?? ''));
        $elave_qeyd_3  = mysqli_real_escape_string($conn, trim($_POST['elave_qeyd_3'] ?? ''));

        $tedris_ili    = trim($_POST['tedris_ili'] ?? odenis_default_tedris_ili());
        $odenis_novu   = in_array($_POST['odenis_novu'] ?? '', ['paket', 'ayliq'], true) ? $_POST['odenis_novu'] : 'ayliq';
        $tehsil_haqqi_raw = str_replace(',', '.', trim($_POST['tehsil_haqqi'] ?? ''));
        $baslama_tarixi   = trim($_POST['baslama_tarixi'] ?? date('Y-m-d'));

        if ($tedris_ili === '') {
            throw new Exception('Tədris ili daxil edilməlidir.');
        }
        if (!is_numeric($tehsil_haqqi_raw) || (float) $tehsil_haqqi_raw <= 0) {
            throw new Exception('Təhsil haqqı düzgün daxil edilməyib.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $baslama_tarixi)) {
            $baslama_tarixi = date('Y-m-d');
        }

        $tehsil_haqqi = (float) $tehsil_haqqi_raw;
        $ilkin_odenis = 0.0;
        $novbeti_odenis_tarixi = odenis_next_due_date($baslama_tarixi, $odenis_novu);
        $vetandasliq = 'Azərbaycan';
        $muellim_adi_db = 'Təyin edilməyib';
        $ixtisas_adi_db = $ixtisas !== '' ? $ixtisas : 'Naməlum';

        // Status - always active by default (no field in form)
        $active_status = 'active';

        $u_id = generateUID($conn);
        $password = generateRandomPassword();
        $password_hash = app_hash_password($password);
        $company_id = 0;

        // ──────────────────────────────
        // Fixed & more reliable age calculation
        // ──────────────────────────────
        $years_for_db = '0';

        if ($dogum_tarixi !== '' && $dogum_tarixi !== '0000-00-00') {
            // Check format first (basic protection)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dogum_tarixi)) {
                try {
                    $birth = new DateTime($dogum_tarixi);
                    $today = new DateTime('now');

                    // Only calculate if birth date is in the past
                    if ($birth < $today) {
                        $interval = $today->diff($birth);
                        $years_for_db = (string) $interval->y;
                    }
                } catch (Exception $e) {
                    // Log error but don't break registration
                    error_log("Age calculation failed: " . $e->getMessage() . " | Date: " . $dogum_tarixi);
                }
            }
        }

        mysqli_begin_transaction($conn);

        // users
        $user_query = "INSERT INTO users 
                       (username, password, role, u_id, created_at, updated_at) 
                       VALUES (?, ?, 'student', ?, NOW(), NOW())";
        $user_stmt = mysqli_prepare($conn, $user_query);
        mysqli_stmt_bind_param($user_stmt, 'sss', $username, $password_hash, $u_id);
        mysqli_stmt_execute($user_stmt) or throw new Exception("users insert failed");
        mysqli_stmt_close($user_stmt);

        // qeydiyyatar
        $qeyd_query = "INSERT INTO qeydiyyatar 
            (u_id, company_id, telebe_ad_soyad, baslama_tarixi, tehsil_haqqi, odenis_novu, ilkin_odenis,
             novbeti_odenis_tarixi, tedris_ili, vetandasliq, muellim_adi, ixtisas_adi,
             form_ata_adi, form_universitet, form_ixtisas, form_qebul_ili, form_dogum_tarixi, form_is_nomresi, 
             form_telefon, form_fin_kod, form_email, form_bakalavr_bali, 
             form_magistr_bali, form_bolme, form_tedris, form_vaxt, form_services, 
             form_sinif_qeyd, form_menbe, form_elave_qeyd_1, form_elave_qeyd_2, 
             form_elave_qeyd_3, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $qeyd_stmt = mysqli_prepare($conn, $qeyd_query);
        mysqli_stmt_bind_param(
            $qeyd_stmt,
            'sisssdsdssssssssssssssssssssssss',
            $u_id,
            $company_id,
            $ad_soyad_db,
            $baslama_tarixi,
            $tehsil_haqqi,
            $odenis_novu,
            $ilkin_odenis,
            $novbeti_odenis_tarixi,
            $tedris_ili,
            $vetandasliq,
            $muellim_adi_db,
            $ixtisas_adi_db,
            $ata_adi,
            $universitet,
            $ixtisas,
            $qebul_ili,
            $dogum_tarixi,
            $is_nomresi,
            $telefon,
            $fin_kod,
            $email,
            $bakalavr_bali,
            $magistr_bali,
            $bolme,
            $tedris,
            $vaxt,
            $services,
            $sinif_qeyd,
            $menbe,
            $elave_qeyd_1,
            $elave_qeyd_2,
            $elave_qeyd_3
        );
        mysqli_stmt_execute($qeyd_stmt) or throw new Exception("qeydiyyatar insert failed");
        $print_id = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($qeyd_stmt);

        // telebeler
        $telebe_query = "
            INSERT INTO telebeler 
            (u_id, company_id, username, reg_ata_adi, reg_universitet, 
             reg_ixtisas, reg_qebul_ili, reg_dogum_tarixi, reg_is_nomresi, 
             reg_telefon, reg_fin_kod, reg_email, reg_bakalavr_bali, 
             reg_magistr_bali, reg_bolme, reg_tedris, reg_vaxt, reg_services, 
             reg_sinif_qeyd, reg_menbe, reg_elave_qeyd_1, reg_elave_qeyd_2, 
             reg_elave_qeyd_3, reg_years, active_status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";

        $telebe_stmt = mysqli_prepare($conn, $telebe_query);
        mysqli_stmt_bind_param(
            $telebe_stmt,
            'sisssssssssssssssssssssss',
            $u_id,
            $company_id,
            $ad_soyad_db,
            $ata_adi,
            $universitet,
            $ixtisas,
            $qebul_ili,
            $dogum_tarixi,
            $is_nomresi,
            $telefon,
            $fin_kod,
            $email,
            $bakalavr_bali,
            $magistr_bali,
            $bolme,
            $tedris,
            $vaxt,
            $services,
            $sinif_qeyd,
            $menbe,
            $elave_qeyd_1,
            $elave_qeyd_2,
            $elave_qeyd_3,
            $years_for_db,
            $active_status
        );
        mysqli_stmt_execute($telebe_stmt) or throw new Exception("telebeler insert failed");
        mysqli_stmt_close($telebe_stmt);

        mysqli_commit($conn);

        sendCredentialsEmail($email, $username, $password, $fullName, $u_id);

        $_SESSION['qeydiyyat_form_data'] = [
            'ad' => $ad,
            'soyad' => $soyad,
            'universitet' => trim($_POST['universitet'] ?? ''),
            'ixtisas' => trim($_POST['ixtisas'] ?? ''),
            'ata_adi' => trim($_POST['ata_adi'] ?? ''),
            'qebul_ili' => trim($_POST['qebul_ili'] ?? ''),
            'dogum_tarixi' => $dogum_tarixi,
            'is_nomresi' => trim($_POST['is_nomresi'] ?? ''),
            'telefon' => trim($_POST['telefon'] ?? ''),
            'fin_kod' => trim($_POST['fin_kod'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'bakalavr_bali' => trim($_POST['bakalavr_bali'] ?? ''),
            'magistr_bali' => trim($_POST['magistr_bali'] ?? ''),
            'bolme' => $_POST['bolme'] ?? '',
            'tedris' => $_POST['tedris'] ?? '',
            'vaxt' => $_POST['vaxt'] ?? [],
            'services' => $_POST['services'] ?? [],
            'sinif_qeyd' => trim($_POST['sinif_qeyd'] ?? ''),
            'menbe' => $_POST['menbe'] ?? [],
            'elave_qeyd_1' => trim($_POST['elave_qeyd_1'] ?? ''),
            'elave_qeyd_2' => trim($_POST['elave_qeyd_2'] ?? ''),
            'elave_qeyd_3' => trim($_POST['elave_qeyd_3'] ?? ''),
            'tarix' => date('d.m.Y'),
            'tedris_ili' => $tedris_ili,
            'odenis_novu' => $odenis_novu,
            'tehsil_haqqi' => $tehsil_haqqi_raw,
            'baslama_tarixi' => $baslama_tarixi,
        ];
        $_SESSION['qeydiyyat_success'] = true;
        $_SESSION['last_qeydiyyat_print_id'] = $print_id;

        $redirect = 'Qeydiyyatar.php?' . ($isEmbed ? 'embed=1&' : '') . 'success=1&print_id=' . $print_id;
        header('Location: ' . $redirect);
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Registration error: " . $e->getMessage());
        $errorMessage = $e->getMessage() ?: 'Qeydiyyat zamanı xəta baş verdi. Zəhmət olmasa yenidən cəhd edin.';
        $form_data = array_merge($form_data, [
            'ad' => trim($_POST['ad'] ?? ''),
            'soyad' => trim($_POST['soyad'] ?? ''),
            'universitet' => trim($_POST['universitet'] ?? ''),
            'ixtisas' => trim($_POST['ixtisas'] ?? ''),
            'ata_adi' => trim($_POST['ata_adi'] ?? ''),
            'qebul_ili' => trim($_POST['qebul_ili'] ?? ''),
            'dogum_tarixi' => trim($_POST['dogum_tarixi'] ?? ''),
            'is_nomresi' => trim($_POST['is_nomresi'] ?? ''),
            'telefon' => trim($_POST['telefon'] ?? ''),
            'fin_kod' => trim($_POST['fin_kod'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'bakalavr_bali' => trim($_POST['bakalavr_bali'] ?? ''),
            'magistr_bali' => trim($_POST['magistr_bali'] ?? ''),
            'bolme' => $_POST['bolme'] ?? '',
            'tedris' => $_POST['tedris'] ?? '',
            'vaxt' => $_POST['vaxt'] ?? [],
            'services' => $_POST['services'] ?? [],
            'sinif_qeyd' => trim($_POST['sinif_qeyd'] ?? ''),
            'menbe' => $_POST['menbe'] ?? [],
            'elave_qeyd_1' => trim($_POST['elave_qeyd_1'] ?? ''),
            'elave_qeyd_2' => trim($_POST['elave_qeyd_2'] ?? ''),
            'elave_qeyd_3' => trim($_POST['elave_qeyd_3'] ?? ''),
            'tedris_ili' => trim($_POST['tedris_ili'] ?? odenis_default_tedris_ili()),
            'odenis_novu' => $_POST['odenis_novu'] ?? 'ayliq',
            'tehsil_haqqi' => trim($_POST['tehsil_haqqi'] ?? ''),
            'baslama_tarixi' => trim($_POST['baslama_tarixi'] ?? date('Y-m-d')),
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QEYDİYYAT FORMU</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="../dist/css/style.min.css" rel="stylesheet">
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(to bottom,#f9fafb,#e0f2fe);color:#1e3a8a;line-height:1.6}
    body.embed-mode{background:#f0f9ff}
    body.embed-mode .page-wrapper{min-height:auto}
    .page-wrapper{display:flex;min-height:100vh}
    .main-content{flex:1;margin-left:250px;margin-top:60px;padding:40px 25px}
    .main-content.embed-mode{margin-left:0;margin-top:0;padding:20px 15px}
    .main-content.embed-mode .content-container{border-radius:12px;padding:25px 20px}
    .content-container{max-width:99%;margin:0 auto;background:#fff;border-radius:18px;box-shadow:0 10px 35px rgba(30,58,138,.15);padding:40px}
    .header{display:flex;align-items:center;justify-content:space-between;margin-bottom:40px;padding-bottom:22px;border-bottom:1px solid #dbeafe}
    .header img{height:52px}
    .header h1{border-bottom:3px solid #215cff88; font-size:28px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color: #6e95ffff}
    .form-wrapper{display:grid;grid-template-columns:1fr 1fr;gap:22px 50px;margin-bottom:40px}
    .form-group{display:flex;flex-direction:column;gap:7px}
    .form-group label{font-size:15px;font-weight:600;color:#1e3a8a;margin-bottom:1px}
    .form-group input{padding:8px 8px;border:1px solid #bfdbfe;border-radius:10px;font-size:15px;background:#f0f9ff;transition:all .25s}
    .payment-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px 40px;margin-bottom:10px}
    .payment-summary{background:#fef9c3;border:1px solid #fde047;border-radius:12px;padding:16px 18px;margin-top:10px;font-size:14px;color:#713f12}
    .payment-summary strong{color:#854d0e}
    .form-group select{padding:8px;border:1px solid #bfdbfe;border-radius:10px;font-size:15px;background:#f0f9ff}
    .form-group-double{display:flex;gap:20px}
    .form-group-double .form-group{flex:1}
    .section-title{background:#dbeafe;color:#1d4ed8;text-align:center;font-size:18px;font-weight:700;padding:14px;margin:45px 0 30px;border-radius:12px;text-transform:uppercase;letter-spacing:1px;box-shadow:0 4px 10px rgba(59,130,246,.1)}
    .tedris-wrapper{display:grid;grid-template-columns:1fr 1fr 1fr;gap:30px;margin-top:20px;align-items:start}
    .tedris-box{padding:20px;border-radius:12px;}
    .tedris-box-title{padding:8px 16px;border-radius:8px;background:#e5efff;color:#1d4ed8;font-weight:700;display:inline-block;margin-bottom:16px}
    .radio-group label,.checkbox-group label{display:flex;align-items:center;gap:10px;margin:12px 0;font-size:15.5px;color:#374151;cursor:pointer;transition:color .2s}
    .radio-group label:hover,.checkbox-group label:hover{color:#1d4ed8}
    .checkbox-group input[type="radio"],.checkbox-group input[type="checkbox"],.radio-group input[type="radio"]{width:20px;height:20px;accent-color:#3b82f6;cursor:pointer}
    .services-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:25px 35px;margin-top:30px}
    .service-column{padding:20px;border-radius:14px}
    .service-column h4{text-align:center;font-size:15.5px;margin-bottom:16px;color:#1d4ed8;text-transform:uppercase;font-weight:700}
    .checkbox-item{display:flex;align-items:center;gap:12px;margin-bottom:10px;font-size:15px;color:#374151}
    .checkbox-item input[type="checkbox"]{width:18px;height:18px;accent-color:#3b82f6}
    .info-wrapper{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:50px;border-top:2px dashed #1d4ed8;padding-top:30px}
    .info-left strong{font-size:16.5px;margin-bottom:16px;display:block;color:#1e3a8a}
    .info-checkboxes{display:flex;flex-direction:column;gap:12px;font-size:15.5px;color:#374151}
    .info-checkboxes label{display:flex;align-items:center;gap:12px}
    .underline-input{width:100%;border:none;border-bottom:1px solid #374151;background:transparent;padding:8px 4px;font-size:15px;color:#1e3a8a;outline:none}
    .underline-input_sinif{width:30%;border:none;border-bottom:1px solid #374151;background:transparent;padding:8px 4px;font-size:15px;color:#1e3a8a;outline:none}
    .signature-section{display:grid;grid-template-columns:repeat(3,1fr);gap:35px;margin-top:70px;font-size:15.5px;color:#1e3a8a}
    .signature-box{text-align:center}
    .signature-line{width:100%;border:none;border-bottom:1px dashed #1d4ed8;margin-top:45px;padding:0}
    .indent { margin-left: 24px; }
    .pair { display: flex; gap: 28px; flex-wrap: wrap; }
    .divider { height: 1px; background: #bfdbfe; margin: 16px 0; }
    .underline { display: inline-block; width: 180px; border-bottom: 1px solid #374151; margin: 0 6px; vertical-align: middle; }
    .underline_ixtisas { margin-top: 22px; width: 180px; }
    .alert{padding:15px;margin:20px 0;border-radius:8px;font-size:15px}
    .alert-success{background:#d1f2eb;color:#0f5132;border:1px solid #badbcc}
    .alert-error{background:#f8d7da;color:#842029;border:1px solid #f5c2c7}
    @media (max-width:1024px){
        .main-content{margin-left:200px;padding:30px 20px;margin-top:70px}
        .content-container{padding:30px}
        .header h1{font-size:24px}
        .form-wrapper{gap:20px 30px}
        .tedris-wrapper{grid-template-columns:1fr 1fr;gap:25px}
        .services-grid{grid-template-columns:repeat(2,1fr);gap:20px}
        .info-wrapper{gap:30px}
        .signature-section{gap:25px}
    }
    @media (max-width:768px){
        .main-content{margin-left:0;padding:20px 15px;margin-top:60px}
        .content-container{padding:25px 20px;border-radius:12px}
        .header{flex-direction:column;gap:15px;text-align:center;margin-bottom:30px}
        .header h1{font-size:22px;letter-spacing:1px}
        .header img{height:45px}
        .form-wrapper{grid-template-columns:1fr;gap:18px}
        .form-group label{font-size:14px}
        .form-group input{padding:10px;font-size:14px}
        .form-group-double{flex-direction:column;gap:18px}
        .section-title{font-size:16px;padding:12px;margin:35px 0 25px;letter-spacing:0.5px}
        .tedris-wrapper{grid-template-columns:1fr;gap:20px}
        .tedris-box{padding:18px}
        .tedris-box-title{font-size:14px;padding:6px 14px}
        .radio-group label,.checkbox-group label{font-size:14px;margin:10px 0}
        .services-grid{grid-template-columns:1fr;gap:18px}
        .service-column{padding:18px}
        .service-column h4{font-size:14px;margin-bottom:14px}
        .checkbox-item{font-size:14px;gap:10px}
        .info-wrapper{grid-template-columns:1fr;gap:30px;padding-top:25px}
        .info-left strong,.info-right strong{font-size:15px}
        .info-checkboxes{gap:10px;font-size:14px}
        .underline-input{font-size:14px;padding:6px 4px}
        .signature-section{grid-template-columns:1fr;gap:30px;margin-top:50px;font-size:14px}
        .signature-line{margin-top:30px}
    }
    @media (max-width:600px){
        .main-content{padding:15px 10px;margin-top:80px}
        .content-container{padding:20px 15px;border-radius:10px;box-shadow:0 5px 20px rgba(30,58,138,.1)}
        .header h1{font-size:20px}
        .header img{height:40px}
        .form-group label{font-size:13px}
        .form-group input{padding:9px;font-size:13px;border-radius:8px}
        .section-title{font-size:15px;padding:10px;margin:30px 0 20px}
        .tedris-box{padding:15px;border-radius:10px}
        .tedris-box-title{font-size:13px;padding:5px 12px}
        .radio-group label,.checkbox-group label{font-size:13px;margin:8px 0;gap:8px}
        .checkbox-group input[type="radio"],.checkbox-group input[type="checkbox"],.radio-group input[type="radio"]{width:18px;height:18px}
        .service-column{padding:15px;border-radius:10px}
        .service-column h4{font-size:13px;margin-bottom:12px}
        .checkbox-item{font-size:13px}
        .checkbox-item input[type="checkbox"]{width:16px;height:16px}
        .info-wrapper{gap:25px;padding-top:20px;margin-top:40px}
        .info-left strong,.info-right strong{font-size:14px;margin-bottom:12px}
        .info-checkboxes{font-size:13px;gap:8px}
        .underline-input{font-size:13px}
        .signature-section{gap:25px;margin-top:40px;font-size:13px}
        .signature-box{font-size:13px}
        .signature-line{margin-top:25px}
        .inline-input{width:100px;font-size:12px;padding:3px 6px}
    }
    @media (max-width:400px){
        .content-container{padding:15px 12px; margin-top: 0px;}
        .header h1{font-size:18px}
        .form-group label{font-size:12px}
        .form-group input{padding:8px;font-size:12px}
        .section-title{font-size:14px;padding:8px;margin:25px 0 18px}
        .tedris-box{padding:12px}
        .radio-group label,.checkbox-group label{font-size:12px}
        .service-column{padding:12px}
        .service-column h4{font-size:12px}
        .checkbox-item{font-size:12px}
        .info-checkboxes{font-size:12px}
        .signature-section{font-size:12px}
    }
    @media print{
        .no-print{display:none!important}
        .main-content{margin-left:0!important;margin-top:0!important;padding:0}
        .content-container{box-shadow:none;border-radius:0;padding:20px;border:1px dashed #000}
        .form-group input,.underline-input,.underline-input_sinif,.signature-line{
            border:none!important;background:transparent!important;box-shadow:none!important;
            color:#1e3a8a!important;-webkit-print-color-adjust:exact;print-color-adjust:exact
        }
        input[type="checkbox"],input[type="radio"]{
            -webkit-print-color-adjust:exact;print-color-adjust:exact;
            opacity:1!important;position:relative!important
        }
        .checkbox-item input[type="checkbox"]:checked+label,
        .radio-group label:has(input:checked),
        .checkbox-group label:has(input:checked){font-weight:700;color:#1e3a8a!important}
        .section-title,.tedris-box-title{background:#dbeafe!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    }
</style>
</head>
<body<?= $isEmbed ? ' class="embed-mode"' : '' ?>>
<div class="page-wrapper">
    <?php if (!$isEmbed): ?>
    <aside class="no-print">
        <?php include('navbar_sidebar.php'); ?>
    </aside>
    <?php endif; ?>
    <div class="main-content<?= $isEmbed ? ' embed-mode' : '' ?>">
        <div class="content-container">
            <?php if ($successMessage): ?>
                <div class="alert alert-success no-print">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-error no-print">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?= $isEmbed ? '?embed=1' : '' ?>">
            <?php if ($isEmbed): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
            <div class="header">
                <h1>QEYDİYYAT FORMU</h1>
            </div>
            <div class="form-wrapper">
                <div class="form-group"><label>Ad: *</label><input type="text" name="ad" value="<?= form_val('ad') ?>" required></div>
                <div class="form-group"><label>Universitet:</label><input type="text" name="universitet" value="<?= form_val('universitet') ?>"></div>
                <div class="form-group"><label>Soyad: *</label><input type="text" name="soyad" value="<?= form_val('soyad') ?>" required></div>
                <div class="form-group"><label>İxtisas:</label><input type="text" name="ixtisas" value="<?= form_val('ixtisas') ?>"></div>
                <div class="form-group"><label>Ata adı:</label><input type="text" name="ata_adi" value="<?= form_val('ata_adi') ?>"></div>
                <div class="form-group"><label>Qəbul ili:</label><input type="text" name="qebul_ili" value="<?= form_val('qebul_ili') ?>"></div>
                <div class="form-group"><label>Doğum tarixi:</label><input type="date" name="dogum_tarixi" value="<?= form_val('dogum_tarixi') ?>"></div>
                <div class="form-group"><label>İş nömrəsi:</label><input type="text" name="is_nomresi" value="<?= form_val('is_nomresi') ?>"></div>
                <div class="form-group"><label>Telefon: *</label><input type="tel" name="telefon" value="<?= form_val('telefon') ?>" placeholder="+994 XX XXX XX XX" required></div>
                <div class="form-group"><label>FIN kod:</label><input type="text" name="fin_kod" value="<?= form_val('fin_kod') ?>" maxlength="7" placeholder="XXXXXXX"></div>
                <div class="form-group"><label>E-mail: *</label><input type="email" name="email" value="<?= form_val('email') ?>" required></div>
                <div class="form-group-double">
                    <div class="form-group"><label>Bakalavr balı:</label><input type="text" name="bakalavr_bali" value="<?= form_val('bakalavr_bali') ?>"></div>
                    <div class="form-group"><label>Magistr balı:</label><input type="text" name="magistr_bali" value="<?= form_val('magistr_bali') ?>"></div>
                </div>
            </div>
            <div class="section-title">TƏDRİS PROSESİ</div>
            <div class="tedris-wrapper">
                <div class="tedris-box">
                    <div class="tedris-box-title">Bölmə</div>
                    <div class="radio-group">
                        <label><input type="radio" name="bolme" value="azerbaycan" <?= form_radio('bolme', 'azerbaycan') ?>> Azərbaycan</label>
                        <label><input type="radio" name="bolme" value="rus" <?= form_radio('bolme', 'rus') ?>> Rus</label>
                    </div>
                </div>
                <div class="tedris-box">
                    <div class="tedris-box-title">Tədris</div>
                    <div class="radio-group">
                        <label><input type="radio" name="tedris" value="enenevi" <?= form_radio('tedris', 'enenevi') ?>> Ənənəvi</label>
                        <label><input type="radio" name="tedris" value="onlayn" <?= form_radio('tedris', 'onlayn') ?>> Onlayn</label>
                    </div>
                </div>
                <div class="tedris-box">
                    <div class="tedris-box-title">Arzu olunan vaxt</div>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="vaxt[]" value="seher" <?= form_checked('vaxt', 'seher') ?>> Səhər 08:20-13:10</label>
                        <label><input type="checkbox" name="vaxt[]" value="gunorta" <?= form_checked('vaxt', 'gunorta') ?>> Günorta 14:00-18:50</label>
                        <label><input type="checkbox" name="vaxt[]" value="axsam" <?= form_checked('vaxt', 'axsam') ?>> Axşam 19:10</label>
                    </div>
                </div>
            </div>
            <div class="section-title">XİDMƏTLƏRİMİZ</div>
            <div class="services-grid">
                <div class="service-column">
                    <h4>Magistratura</h4>
                    <div class="pair">
                        <div class="checkbox-item">
                            <input type="checkbox" id="mag1" name="services[]" value="Məntiq" <?= form_checked('services', 'Məntiq') ?>>
                            <label for="mag1">Məntiq</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="mag2" name="services[]" value="İnformatika">
                            <label for="mag2">İnformatika</label>
                        </div>
                    </div>
                    <div style="margin:12px 0 8px 0;">Xarici dil:</div>
                    <div class="pair indent">
                        <div class="checkbox-item"><input type="checkbox" id="xd1" name="services[]" value="İngilis"><label for="xd1">İngilis</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="xd2" name="services[]" value="Rus"><label for="xd2">Rus</label></div>
                    </div>
                    <div class="pair indent">
                        <div class="checkbox-item"><input type="checkbox" id="xd3" name="services[]" value="Alman"><label for="xd3">Alman</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="xd4" name="services[]" value="Fransız"><label for="xd4">Fransız</label></div>
                    </div>
                    <div class="divider"></div>
                    <h4>Dövlət qulluğu</h4>
                    <div class="indent">
                        <div class="pair">
                            <div class="checkbox-item"><input type="checkbox" id="dq1" name="services[]" value="DQ-Məntiq"><label for="dq1">Məntiq</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="dq2" name="services[]" value="Qanunvericilik"><label for="dq2">Qanunvericilik</label></div>
                        </div>
                        <div class="pair">
                            <div class="checkbox-item"><input type="checkbox" id="dq3" name="services[]" value="DQ-İnformatika"><label for="dq3">İnformatika</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="dq4" name="services[]" value="Azərbaycan dili"><label for="dq4">Azərbaycan dili</label></div>
                        </div>
                        <div class="checkbox-item"><input type="checkbox" id="dq5" name="services[]" value="Müsahibə"><label for="dq5">Müsahibə mərhələsi</label></div>
                        <div class="pair">
                            <div class="checkbox-item"><input type="checkbox" id="dq6" name="services[]" value="Prokurorluq"><label for="dq6">Prokurorluq</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="dq7" name="services[]" value="Vergi"><label for="dq7">Vergi orqanları</label></div>
                        </div>
                    </div>
                </div>
                <div class="service-column">
                    <h4>MİQ və SERTİFİKASIYA</h4>
                    <div class="checkbox-item">
                        <input type="checkbox" id="miq1" name="services[]" value="Kurikulum">
                        <label for="miq1">Kurikulum</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="miq2" name="services[]" value="İxtisas">
                        <label for="miq2">İxtisas: <span class="underline underline_ixtisas"></span></label>
                    </div>
                    <div class="divider"></div>
                    <h4>Xarici dil dərsləri</h4>
                    <div class="pair">
                        <div class="checkbox-item"><input type="checkbox" id="dil1" name="services[]" value="Beginner"><label for="dil1">Beginner</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="dil2" name="services[]" value="Elementary"><label for="dil2">Elementary</label></div>
                    </div>
                    <div class="pair">
                        <div class="checkbox-item"><input type="checkbox" id="dil3" name="services[]" value="Pre-Intermediate"><label for="dil3">Pre-Intermediate</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="dil4" name="services[]" value="Intermediate"><label for="dil4">Intermediate</label></div>
                    </div>
                    <div class="pair">
                        <div class="checkbox-item"><input type="checkbox" id="dil5" name="services[]" value="IELTS"><label for="dil5">IELTS</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="dil6" name="services[]" value="Rus dili"><label for="dil6">Rus dili</label></div>
                    </div>
                    <div class="divider"></div>
                    <h4>Doktorantura</h4>
                    <div class="pair">
                        <div class="checkbox-item"><input type="checkbox" id="dok1" name="services[]" value="Dok-İngilis"><label for="dok1">İngilis dili</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="dok2" name="services[]" value="Fəlsəfə"><label for="dok2">Fəlsəfə</label></div>
                    </div>
                </div>
                <div class="service-column">
                    <div class="checkbox-item"><input type="checkbox" id="c1" name="services[]" value="Robototexnika"><label for="c1">Robototexnika</label></div>
                    <div class="checkbox-item"><input type="checkbox" id="c2" name="services[]" value="Ofis proqramları"><label for="c2">Ofis proqramları</label></div>
                    <div class="checkbox-item"><input type="checkbox" id="c3" name="services[]" value="Sabah qrupları"><label for="c3">Sabah qrupları</label></div>
                    <div class="divider"></div>
                    <div class="checkbox-item"><input type="checkbox" id="c4" name="services[]" value="Məktəbəqədər"><label for="c4">Məktəbəqədər</label></div>
                    <div class="checkbox-item"><input type="checkbox" id="c5" name="services[]" value="İbtidai sinif"><label for="c5">İbtidai sinif</label></div>
                    <div class="checkbox-item"><input type="checkbox" id="c6" name="services[]" value="Təkmilləşdirmə"><label for="c6">Təkmilləşdirmə</label></div>
                    <div class="checkbox-item"><input type="checkbox" id="c7" name="services[]" value="Abituriyent"><label for="c7">Abituriyent</label></div>
                    <div class="divider"></div>
                    <div class="pair indent">
                        <div class="checkbox-item">
                            <input type="checkbox" id="ab1" name="services[]" value="Blok">
                            <label for="ab1">Blok</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="ab2" name="services[]" value="Buraxılış">
                            <label for="ab2">Buraxılış</label>
                        </div>
                    </div>
                    <div style="margin-top: 30px;">
                        <div class="checkbox-item">
                           Sinif:  <input type="text" name="sinif_qeyd" class="underline-input_sinif" value="<?= form_val('sinif_qeyd') ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-title">ÖDƏNİŞ MƏLUMATLARI</div>
            <div class="payment-grid">
                <div class="form-group">
                    <label>Tədris ili: *</label>
                    <input type="text" name="tedris_ili" value="<?= form_val('tedris_ili') ?>" placeholder="2025-2026" required>
                </div>
                <div class="form-group">
                    <label>Başlama tarixi: *</label>
                    <input type="date" name="baslama_tarixi" value="<?= form_val('baslama_tarixi') ?>" required>
                </div>
                <div class="form-group">
                    <label>Ödəniş növü: *</label>
                    <select name="odenis_novu" required>
                        <option value="ayliq" <?= form_selected('odenis_novu', 'ayliq') ?>>Aylıq</option>
                        <option value="paket" <?= form_selected('odenis_novu', 'paket') ?>>Paket</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Təhsil haqqı (AZN): *</label>
                    <input type="number" step="0.01" min="0.01" name="tehsil_haqqi" id="tehsil_haqqi" value="<?= form_val('tehsil_haqqi') ?>" required>
                </div>
            </div>
            <div class="payment-summary">
                <span id="ayliqOdenisInfo">Aylıq ödəniş seçildikdə hər ayın sonunda ödəniş xatırlatması e-poçtunuza göndəriləcək.</span>
            </div>
            <div class="info-wrapper">
                <div class="info-left">
                    <strong>Kurs barədə məlumatı necə əldə etmisiniz?</strong><br><br>
                    <div class="info-checkboxes">
                        <label><input type="checkbox" name="menbe[]" value="sosial" <?= form_checked('menbe', 'sosial') ?>> Sosial şəbəkə</label>
                        <label><input type="checkbox" name="menbe[]" value="dostlar" <?= form_checked('menbe', 'dostlar') ?>> Dostlar</label>
                        <label><input type="checkbox" name="menbe[]" value="telebeleden" <?= form_checked('menbe', 'telebeleden') ?>> Burada hazırlanan tələbələrdən</label>
                    </div>
                </div>
                <div class="info-right">
                    <strong>Əlavə qeyd:</strong><br><br>
                    <input type="text" name="elave_qeyd_1" class="underline-input" value="<?= form_val('elave_qeyd_1') ?>"><br><br>
                    <input type="text" name="elave_qeyd_2" class="underline-input" value="<?= form_val('elave_qeyd_2') ?>"><br><br>
                    <input type="text" name="elave_qeyd_3" class="underline-input" value="<?= form_val('elave_qeyd_3') ?>"><br>
                </div>
            </div>
            <div class="signature-section">
                <div class="signature-box">
                    Tarix:<input type="text" name="tarix" class="signature-line" value="<?= form_val('tarix') ?>">
                </div>
                <div class="signature-box">
                    Tələbənin imzası:<input type="text" class="signature-line">
                </div>
                <div class="signature-box">
                    Əməkdaşın imzası:<input type="text" class="signature-line">
                </div>
            </div>
            <br><br><br>
            <div class="no-print">
                <button type="button" class="mb-1 btn btn-secondary mr-2" onclick="openPrintPage()">
                    <i class="fas fa-print mr-1"></i> Çap Et
                </button>
                <button type="button" class="mb-1 btn btn-info mr-2" onclick="openContractPage()">
                    <i class="fas fa-file-contract mr-1"></i> Müqavilə
                </button>
                <button type="reset" class="mb-1 btn btn-danger">
                    <i class="fas fa-redo-alt mr-1"></i> Formu Təmizlə
                </button>
                <button type="submit" name="submit_registration" class="mb-1 btn btn-primary mr-2">
                    <i class="fas fa-paper-plane mr-1"></i> Müraciət et
                </button>
            </div>
            </form>
        </div>
    </div>
</div>
<?php if (!$isEmbed): ?>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/app-style-switcher.js"></script>
<script src="../dist/js/feather.min.js"></script>
<script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="../dist/js/sidebarmenu.js"></script>
<script src="../dist/js/custom.min.js"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const printId = <?= (int) $printId ?>;

    window.openPrintPage = function() {
        if (!printId) {
            alert('Çap üçün əvvəl qeydiyyatı tamamlayın.');
            return;
        }
        window.open('Qeydiyyatar_print.php?id=' + printId, '_blank');
    };

    window.openContractPage = function() {
        if (!printId) {
            alert('Müqavilə üçün əvvəl qeydiyyatı tamamlayın.');
            return;
        }
        window.open('Qeydiyyatar_muqavile.php?id=' + printId, '_blank');
    };

    const services = <?= json_encode($form_data['services'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    if (services.length) {
        document.querySelectorAll('input[name="services[]"]').forEach(function(cb) {
            if (services.indexOf(cb.value) !== -1) cb.checked = true;
        });
    }

    const odenisNovu = document.querySelector('select[name="odenis_novu"]');
    const aylikInfo = document.getElementById('ayliqOdenisInfo');
    function updateOdenisInfo() {
        if (!odenisNovu || !aylikInfo) return;
        aylikInfo.style.display = odenisNovu.value === 'ayliq' ? 'inline' : 'none';
    }
    if (odenisNovu) {
        odenisNovu.addEventListener('change', updateOdenisInfo);
        updateOdenisInfo();
    }

    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const tehsil = parseFloat(document.getElementById('tehsil_haqqi')?.value || '0');
            if (!(tehsil > 0)) {
                e.preventDefault();
                alert('Təhsil haqqı düzgün daxil edilməyib.');
            }
        });
    }

    if (window.location.search.indexOf('success=1') !== -1 && printId) {
        setTimeout(function() { openPrintPage(); }, 800);
        setTimeout(function() { openContractPage(); }, 1500);
        <?php if ($isEmbed): ?>
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ type: 'qeydiyyat-success' }, '*');
        }
        <?php endif; ?>
    }
});
</script>
</body>
</html>