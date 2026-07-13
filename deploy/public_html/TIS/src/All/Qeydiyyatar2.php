<?php
include('db.php');
require_once __DIR__ . '/user_credentials_helper.php';
app_ensure_plain_password_column($conn);
require_once __DIR__ . '/qeydiyyatar/odenis_helpers.php';
odenis_ensure_columns($conn);

// Add PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Function to generate a unique u_id
function generateUID($conn, $length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $uid = substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
        // Check if u_id already exists in users table
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
                    <h2>Hörmətli $fullName tələbə,</h2>
                    
                    <p>Magistratura AZ platformasında tələbə kimi qeydiyyatınız uğurla tamamlandı. Sizi tələbələrimiz arasında görməkdən məmnunuq! Aşağıda sistemə giriş məlumatlarınız və şəxsi məlumatlarınız verilmişdir:</p>
                    
                    <div class='student-info'>
                        <h3>Şəxsi Məlumatlar:</h3>
                        <p><strong>Ad və Soyad:</strong> $fullName</p>
                        <p><strong>E-Poçt:</strong> $email</p>
                        " . (!empty($photo) ? "<div class='photo'><p><strong>Şəkil:</strong> Əlavə edilmişdir</p></div>" : "") . "
                    </div>
                    
                    <div class='credentials'>
                        <h3>Giriş Məlumatları:</h3>
                        <p><strong>İstifadəçi Adı:</strong> $username</p>
                        <p><strong>Şifrə:</strong> $password</p>
                    </div>
                    
                    <p class='important'>Diqqət: Bu məlumatları təhlükəsiz yerdə saxlayın və heç kimlə paylaşmayın.</p>
                    <p><a href='https://texnosoft.com.tr/TIS/src/All/Login.php' class='button'>Sistemə Daxil Ol</a></p>
                    <p>Təhsilinizdə uğurlar diləyirik!</p>
                </div>
                
                <div class='footer'>
                    <p>Magistratura AZ Bütün hüquqlar qorunur.</p>
                    <p>Bu avtomatik göndərilən e-poçt mesajıdır.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "
        Hörmətli $fullName tələbə,
        
        Magistratura AZ platformasında tələbə kimi qeydiyyatınız uğurla tamamlandı. Sizi tələbələrimiz arasında görməkdən məmnunuq!
        
        Şəxsi Məlumatlar:
        Ad və Soyad: $fullName
        E-poçt: $email
        
        Giriş Məlumatları:
        İstifadəçi Adı: $username
        Şifrə: $password
        
        Bu məlumatları təhlükəsiz yerdə saxlayın və heç kimlə paylaşmayın.
        
        Təhsilinizdə uğurlar diləyirik!
        Magistratura AZ
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

$success_message = '';
$error_message = '';
$email_status = '';
$form_data = [
    'telebe_ad_soyad' => '',
    'baslama_tarixi' => date('Y-m-d'),
    'tehsil_haqqi' => '',
    'odenis_novu' => 'paket',
    'ilkin_odenis' => '30.00',
    'ders_sayi' => '',
    'tedris_ili' => '2025-2026',
    'vetandasliq' => '',
    'ixtisas_adi' => '',
    'muellim_adi' => 'Naməlum'
];

$current_month = date('m');
$current_year = date('Y');

// Monthly statistics query
$monthly_stats = [];
$monthly_stats_query = "SELECT 
    MONTH(baslama_tarixi) as month,
    YEAR(baslama_tarixi) as year,
    COUNT(*) as count 
    FROM qeydiyyatar 
    WHERE YEAR(baslama_tarixi) = $current_year 
    GROUP BY MONTH(baslama_tarixi), YEAR(baslama_tarixi) 
    ORDER BY YEAR(baslama_tarixi) DESC, MONTH(baslama_tarixi) DESC";

$monthly_stats_result = mysqli_query($conn, $monthly_stats_query);
if ($monthly_stats_result) {
    while ($row = mysqli_fetch_assoc($monthly_stats_result)) {
        $month_name = date('F', mktime(0, 0, 0, $row['month'], 10));
        $monthly_stats[] = [
            'month' => $month_name,
            'month_num' => (int) $row['month'],
            'year' => (int) $row['year'],
            'count' => (int) $row['count'],
        ];
    }
}

if (isset($_SESSION['form_submitted']) && $_SESSION['form_submitted'] === true) {
    $success_message = 'Qeydiyyat uğurla tamamlandı!';
    
    // Add email status check
    if (isset($_SESSION['email_sent']) && $_SESSION['email_sent'] === true) {
        $email_status = 'Giriş məlumatları e-poçt ünvanına göndərildi.';
        unset($_SESSION['email_sent']);
    } else if (isset($_SESSION['email_failed']) && $_SESSION['email_failed'] === true) {
        $email_status = 'Qeydiyyat tamamlandı, lakin e-poçt göndərilmədi.';
        unset($_SESSION['email_failed']);
    }
    
    unset($_SESSION['form_submitted']);
    if (isset($_SESSION['last_form_data'])) {
        $form_data = $_SESSION['last_form_data'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect student data from TƏLƏBƏ QEYDİYYATI section
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $telebe_ad_soyad = "$firstName.$lastName"; // Dot between first and last name
    $student_email = trim($_POST['email'] ?? ''); // Get email for sending credentials
    
    $form_data = [
        'telebe_ad_soyad' => $telebe_ad_soyad,
        'baslama_tarixi' => trim($_POST['baslama_tarixi'] ?? date('Y-m-d')),
        'tehsil_haqqi' => trim($_POST['tehsil_haqqi'] ?? ''),
        'odenis_novu' => $_POST['odenis_novu'] ?? 'paket',
        'ilkin_odenis' => trim($_POST['ilkin_odenis'] ?? '30.00'),
        'ders_sayi' => trim($_POST['ders_sayi'] ?? ''),
        'tedris_ili' => trim($_POST['tedris_ili'] ?? '2025-2026'),
        'vetandasliq' => '',
        'ixtisas_adi' => trim($_POST['selected_ixtisas'] ?? ''),
        'muellim_adi' => 'Naməlum'
    ];

    // Fetch vetandasliq (citizenship) name
    $vetandasliq_id = trim($_POST['class'] ?? '');
    if (!empty($vetandasliq_id)) {
        $vetandasliq_query = "SELECT country_name FROM vetandasliq WHERE id = ?";
        $vetandasliq_stmt = mysqli_prepare($conn, $vetandasliq_query);
        if ($vetandasliq_stmt) {
            mysqli_stmt_bind_param($vetandasliq_stmt, 'i', $vetandasliq_id);
            mysqli_stmt_execute($vetandasliq_stmt);
            $vetandasliq_result = mysqli_stmt_get_result($vetandasliq_stmt);
            if ($row = mysqli_fetch_assoc($vetandasliq_result)) {
                $form_data['vetandasliq'] = $row['country_name'];
            } else {
                $error_message = 'Seçilmiş vətəndaşlıq tapılmadı.';
            }
            mysqli_stmt_close($vetandasliq_stmt);
        } else {
            $error_message = 'Vətəndaşlıq sorğusu hazırlanarkən xəta baş verdi.';
        }
    }

    // Fetch sinif (class) name instead of ID
    $sinif_id = trim($_POST['class'] ?? '');
    $sinif_name = '';
    if (!empty($sinif_id)) {
        $sinif_query = "SELECT sinif_number FROM sinifler WHERE id = ?";
        $sinif_stmt = mysqli_prepare($conn, $sinif_query);
        if ($sinif_stmt) {
            mysqli_stmt_bind_param($sinif_stmt, 'i', $sinif_id);
            mysqli_stmt_execute($sinif_stmt);
            $sinif_result = mysqli_stmt_get_result($sinif_stmt);
            if ($row = mysqli_fetch_assoc($sinif_result)) {
                $sinif_name = $row['sinif_number'];
            } else {
                $error_message = 'Seçilmiş sinif tapılmadı.';
            }
            mysqli_stmt_close($sinif_stmt);
        } else {
            $error_message = 'Sinif sorğusu hazırlanarkən xəta baş verdi.';
        }
    }

    // Generate unique u_id
    $u_id = generateUID($conn, 12);

    // Handle photo upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($filetype), $allowed)) {
            $newname = 'student_' . $u_id . '.' . $filetype;
            $target = 'telebeler/' . $newname;
            if (!file_exists('telebeler/')) {
                mkdir('telebeler/', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $photo = $newname;
            } else {
                $error_message = 'Şəkil yükləmə zamanı xəta baş verdi.';
            }
        } else {
            $error_message = 'Yalnız JPG, JPEG, PNG və GIF formatları dəstəklənir.';
        }
    } elseif (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
        $img = $_POST['photo_data'];
        $img = str_replace('data:image/jpeg;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $newname = 'student_' . $u_id . '.jpg';
        $target = 'telebeler/' . $newname;
        if (!file_exists('telebeler/')) {
            mkdir('telebeler/', 0777, true);
        }
        if (file_put_contents($target, $data)) {
            $photo = $newname;
        } else {
            $error_message = 'Şəkil yükləmə zamanı xəta baş verdi.';
        }
    }

    // Student Data
    $student_data = [
        'username' => $telebe_ad_soyad,
        'number' => trim($_POST['phone'] ?? ''),
        'poct' => $student_email,
        'photo' => $photo,
        'u_id' => $u_id,
        'active_status' => trim($_POST['status'] ?? 'active'),
        'dogum_tarixi' => trim($_POST['dogum_tarixi'] ?? ''),
        'years' => trim($_POST['yas'] ?? ''),
        'cins' => trim($_POST['gender'] ?? ''),
        'unvan' => trim($_POST['address'] ?? ''),
        'sinif' => $sinif_name,
        'qebul_tarixi' => trim($_POST['qebul_tarixi'] ?? ''),
        'ata' => trim($_POST['ata'] ?? ''),
        'elaqe_nomre_ata' => trim($_POST['elaqe_nomre_ata'] ?? ''),
        'ana' => trim($_POST['ana'] ?? ''),
        'elaqe_nomre_ana' => trim($_POST['elaqe_nomre_ana'] ?? ''),
        'vetandasliq' => $form_data['vetandasliq'],
        'ixtisas_adi' => $form_data['ixtisas_adi'],
        'muellim_adi' => 'Naməlum'
    ];

    // Validate required fields
    $required_fields = ['telebe_ad_soyad', 'baslama_tarixi', 'vetandasliq', 'ixtisas_adi'];
    $errors = [];
    foreach ($required_fields as $field) {
        if (empty($form_data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' sahəsi tələb olunur.';
        }
    }

    // Validate email
    if (empty($student_email)) {
        $errors[] = 'E-poçt sahəsi tələb olunur.';
    } elseif (!filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-poçt formatı düzgün deyil.';
    }

    // Validate education fee
    if (empty($form_data['tehsil_haqqi'])) {
        $errors[] = 'Təhsil haqqı sahəsi tələb olunur.';
    } else {
        $form_data['tehsil_haqqi'] = str_replace(',', '.', $form_data['tehsil_haqqi']);
        if (!is_numeric($form_data['tehsil_haqqi']) || floatval($form_data['tehsil_haqqi']) <= 0) {
            $errors[] = 'Təhsil haqqı düzgün daxil edilməyib!';
        }
    }

    // Validate initial payment
    if (empty($form_data['ilkin_odenis'])) {
        $errors[] = 'İlkin ödəniş sahəsi tələb olunur.';
    } else {
        $form_data['ilkin_odenis'] = str_replace(',', '.', $form_data['ilkin_odenis']);
        if (!is_numeric($form_data['ilkin_odenis']) || floatval($form_data['ilkin_odenis']) < 30) {
            $errors[] = 'İlkin ödəniş minimum 30 AZN olmalıdır!';
        }
    }

    // Validate sinif
    if (empty($sinif_name)) {
        $errors[] = 'Sinif sahəsi tələb olunur.';
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            // Convert gender to 0 or 1
            $student_data['cins'] = ($student_data['cins'] === 'male') ? 0 : 1;

            // Ensure username column is not UNIQUE
            $check_user_username_index_query = "SHOW INDEX FROM users WHERE Key_name = 'username'";
            $check_user_username_index_result = mysqli_query($conn, $check_user_username_index_query);
            if (mysqli_num_rows($check_user_username_index_result) > 0) {
                $drop_user_username_index_query = "ALTER TABLE users DROP INDEX username";
                if (!mysqli_query($conn, $drop_user_username_index_query)) {
                    throw new Exception('Ошибка при удалении индекса username в таблице users: ' . mysqli_error($conn));
                }
            }

            // Add u_id column to users if not exists
            $check_user_uid_column_query = "SHOW COLUMNS FROM users LIKE 'u_id'";
            $check_user_uid_column_result = mysqli_query($conn, $check_user_uid_column_query);
            if (mysqli_num_rows($check_user_uid_column_result) == 0) {
                $add_user_uid_column_query = "ALTER TABLE users ADD COLUMN u_id VARCHAR(50) UNIQUE AFTER username";
                if (!mysqli_query($conn, $add_user_uid_column_query)) {
                    throw new Exception('Ошибка при добавлении столбца u_id в таблицу users: ' . mysqli_error($conn));
                }
            }

            // Insert into users
            $password = generateRandomPassword(8);
            $password_hash = app_hash_password($password);
            $role = 'student';
            $created_at = date('Y-m-d H:i:s');
            $user_query = "INSERT INTO users (username, u_id, password, plain_password, role, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            $user_stmt = mysqli_prepare($conn, $user_query);
            if ($user_stmt) {
                mysqli_stmt_bind_param(
                    $user_stmt,
                    'ssssss',
                    $telebe_ad_soyad,
                    $u_id,
                    $password_hash,
                    $password,
                    $role,
                    $created_at
                );
                if (!mysqli_stmt_execute($user_stmt)) {
                    throw new Exception('Ошибка при регистрации пользователя: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($user_stmt);
            } else {
                throw new Exception('Ошибка при подготовке запроса пользователя: ' . mysqli_error($conn));
            }

            // Ensure telebe_ad_soyad column is not UNIQUE in qeydiyyatar
            $check_qeydiyyatar_index_query = "SHOW INDEX FROM qeydiyyatar WHERE Key_name = 'telebe_ad_soyad'";
            $check_qeydiyyatar_index_result = mysqli_query($conn, $check_qeydiyyatar_index_query);
            if (mysqli_num_rows($check_qeydiyyatar_index_result) > 0) {
                $drop_qeydiyyatar_index_query = "ALTER TABLE qeydiyyatar DROP INDEX telebe_ad_soyad";
                if (!mysqli_query($conn, $drop_qeydiyyatar_index_query)) {
                    throw new Exception('Ошибка при удалении индекса telebe_ad_soyad в таблице qeydiyyatar: ' . mysqli_error($conn));
                }
            }

            // Add u_id and muellim_adi columns to qeydiyyatar if not exists
            $check_uid_column_query = "SHOW COLUMNS FROM qeydiyyatar LIKE 'u_id'";
            $check_uid_column_result = mysqli_query($conn, $check_uid_column_query);
            if (mysqli_num_rows($check_uid_column_result) == 0) {
                $add_uid_column_query = "ALTER TABLE qeydiyyatar ADD COLUMN u_id VARCHAR(50) UNIQUE AFTER id";
                if (!mysqli_query($conn, $add_uid_column_query)) {
                    throw new Exception('Ошибка при добавлении столбца u_id в таблицу qeydiyyatar: ' . mysqli_error($conn));
                }
            }

            $check_muellim_column_query = "SHOW COLUMNS FROM qeydiyyatar LIKE 'muellim_adi'";
            $check_muellim_column_result = mysqli_query($conn, $check_muellim_column_query);
            if (mysqli_num_rows($check_muellim_column_result) == 0) {
                $add_muellim_column_query = "ALTER TABLE qeydiyyatar ADD COLUMN muellim_adi VARCHAR(100) AFTER ixtisas_adi";
                if (!mysqli_query($conn, $add_muellim_column_query)) {
                    throw new Exception('Ошибка при добавлении столбца muellim_adi в таблицу qeydiyyatar: ' . mysqli_error($conn));
                }
            }

            // Insert into qeydiyyatar
            $tehsil_haqqi = floatval($form_data['tehsil_haqqi']);
            $ilkin_odenis = floatval($form_data['ilkin_odenis']);
            $ders_sayi = !empty($form_data['ders_sayi']) ? intval($form_data['ders_sayi']) : null;
            $current_date = date('Y-m-d H:i:s');
            $odenis_novu = odenis_normalize_odenis_novu($form_data['odenis_novu'] ?? 'paket');
            $novbeti_odenis_tarixi = odenis_next_due_date($form_data['baslama_tarixi'], $odenis_novu);

            $query = "INSERT INTO qeydiyyatar (
                u_id, telebe_ad_soyad, baslama_tarixi, tehsil_haqqi, odenis_novu, ilkin_odenis,
                novbeti_odenis_tarixi, qeydiyyat_tarixi, tedris_ili, ders_sayi, vetandasliq, ixtisas_adi, muellim_adi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssdssdssissss',
                    $u_id,
                    $form_data['telebe_ad_soyad'],
                    $form_data['baslama_tarixi'],
                    $tehsil_haqqi,
                    $odenis_novu,
                    $ilkin_odenis,
                    $novbeti_odenis_tarixi,
                    $current_date,
                    $form_data['tedris_ili'],
                    $ders_sayi,
                    $form_data['vetandasliq'],
                    $form_data['ixtisas_adi'],
                    $form_data['muellim_adi']
                );
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Ошибка при регистрации в qeydiyyatar: ' . mysqli_error($conn));
                }
                $qeydiyyat_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Ошибка при подготовке запроса для qeydiyyatar: ' . mysqli_error($conn));
            }

            // Ensure username column is not UNIQUE in telebeler
            $check_telebeler_index_query = "SHOW INDEX FROM telebeler WHERE Key_name = 'username'";
            $check_telebeler_index_result = mysqli_query($conn, $check_telebeler_index_query);
            if (mysqli_num_rows($check_telebeler_index_result) > 0) {
                $drop_telebeler_index_query = "ALTER TABLE telebeler DROP INDEX username";
                if (!mysqli_query($conn, $drop_telebeler_index_query)) {
                    throw new Exception('Ошибка при удалении индекса username в таблице telebeler: ' . mysqli_error($conn));
                }
            }

            // Add columns to telebeler if not exists
            $check_column_query = "SHOW COLUMNS FROM telebeler LIKE 'u_id'";
            $check_column_result = mysqli_query($conn, $check_column_query);
            if (mysqli_num_rows($check_column_result) == 0) {
                $add_column_query = "ALTER TABLE telebeler ADD COLUMN u_id VARCHAR(50) UNIQUE AFTER photo";
                if (!mysqli_query($conn, $add_column_query)) {
                    throw new Exception('Ошибка при добавлении столбца u_id в таблицу telebeler: ' . mysqli_error($conn));
                }
            }

            $check_vetandasliq_query = "SHOW COLUMNS FROM telebeler LIKE 'vetandasliq'";
            $check_vetandasliq_result = mysqli_query($conn, $check_vetandasliq_query);
            if (mysqli_num_rows($check_vetandasliq_result) == 0) {
                $add_vetandasliq_query = "ALTER TABLE telebeler ADD COLUMN vetandasliq VARCHAR(100) AFTER sinif";
                if (!mysqli_query($conn, $add_vetandasliq_query)) {
                    throw new Exception('Ошибка при добавлении столбца vetandasliq в таблицу telebeler: ' . mysqli_error($conn));
                }
            }

            $check_ixtisas_query = "SHOW COLUMNS FROM telebeler LIKE 'ixtisas_adi'";
            $check_ixtisas_result = mysqli_query($conn, $check_ixtisas_query);
            if (mysqli_num_rows($check_ixtisas_result) == 0) {
                $add_ixtisas_query = "ALTER TABLE telebeler ADD COLUMN ixtisas_adi VARCHAR(100) AFTER vetandasliq";
                if (!mysqli_query($conn, $add_ixtisas_query)) {
                    throw new Exception('Ошибка при добавлении столбца ixtisas_adi в таблицу telebeler: ' . mysqli_error($conn));
                }
            }

            $check_muellim_telebeler_query = "SHOW COLUMNS FROM telebeler LIKE 'muellim_adi'";
            $check_muellim_telebeler_result = mysqli_query($conn, $check_muellim_telebeler_query);
            if (mysqli_num_rows($check_muellim_telebeler_result) == 0) {
                $add_muellim_telebeler_query = "ALTER TABLE telebeler ADD COLUMN muellim_adi VARCHAR(100) AFTER ixtisas_adi";
                if (!mysqli_query($conn, $add_muellim_telebeler_query)) {
                    throw new Exception('Ошибка при добавлении столбца muellim_adi в таблицу telebeler: ' . mysqli_error($conn));
                }
            }

            // Insert into telebeler
            $student_query = "INSERT INTO telebeler (
                username, number, poct, photo, u_id, active_status, dogum_tarixi, years, cins, unvan, sinif, vetandasliq, ixtisas_adi, muellim_adi, qebul_tarixi,
                 ata, elaqe_nomre_ata, ana, elaqe_nomre_ana, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $student_stmt = mysqli_prepare($conn, $student_query);
            if ($student_stmt) {
                $years = !empty($student_data['years']) ? intval($student_data['years']) : null;
                mysqli_stmt_bind_param(
                    $student_stmt,
                    'ssssssssisssssssssss',
                    $telebe_ad_soyad,
                    $student_data['number'],
                    $student_data['poct'],
                    $student_data['photo'],
                    $u_id,
                    $student_data['active_status'],
                    $student_data['dogum_tarixi'],
                    $years,
                    $student_data['cins'],
                    $student_data['unvan'],
                    $student_data['sinif'],
                    $student_data['vetandasliq'],
                    $student_data['ixtisas_adi'],
                    $student_data['muellim_adi'],
                    $student_data['qebul_tarixi'],
                    $student_data['ata'],
                    $student_data['elaqe_nomre_ata'],
                    $student_data['ana'],
                    $student_data['elaqe_nomre_ana'],
                    $created_at
                );
                if (!mysqli_stmt_execute($student_stmt)) {
                    throw new Exception('Ошибка при регистрации в telebeler: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($student_stmt);
            } else {
                throw new Exception('Ошибка при подготовке запроса для telebeler: ' . mysqli_error($conn));
            }

            mysqli_commit($conn);

            // Send email with credentials including student info
            $email_sent = false;
            if (!empty($student_email)) {
                $full_name = str_replace('.', ' ', $telebe_ad_soyad);
                $email_sent = sendCredentialsEmail($student_email, $telebe_ad_soyad, $password, $full_name, $u_id, $photo);
            }

            $_SESSION['last_form_data'] = $form_data;
            $_SESSION['form_submitted'] = true;
            $_SESSION['email_sent'] = $email_sent;
            $_SESSION['email_failed'] = !$email_sent;

            header("Location: " . basename($_SERVER['PHP_SELF']));
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

function formatDate($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d.m.Y', $timestamp);
}

function getAzMonthName($month_number) {
    $months = [
        1 => 'Yanvar',
        2 => 'Fevral',
        3 => 'Mart',
        4 => 'Aprel',
        5 => 'May',
        6 => 'İyun',
        7 => 'İyul',
        8 => 'Avqust',
        9 => 'Sentyabr',
        10 => 'Oktyabr',
        11 => 'Noyabr',
        12 => 'Dekabr'
    ];
    return $months[$month_number] ?? '';
}

// Fetch tehsil_ve_ixtisas from ixtisas table
$result = mysqli_query($conn, "SELECT ixtisas_adi FROM ixtisas WHERE active='1'");
$ixtisaslar = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ixtisaslar[] = ['ixtisas_adi' => $row['ixtisas_adi']];
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Qeydiyyat</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>

        .book{
            border:none;
            transition:0.3s ease-in-out;
            color:gray;
        }

        .book:hover{
            transition:0.3s ease-in-out;
            color:rgba(91, 82, 222, 0.76);
        }

        #preview {
            margin-top: 10px;
            max-width: 90px;
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
        
        #form:focus{
            transition:0.23s easy-in-out;
            border-radius:6px;
            border:1px solid lightblue;
            background-color: lightblue;
        }
        
        .main-content {
            margin-left: 0;
            padding: 0px;
            flex: 1;
            margin-top: 80px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: margin-left 0.3s ease;
            background-color: #f5f5f5;
        }

        /* Material Design Variables */
        :root {
            --primary-color: #1d6a9d;
            --primary-color-hover:rgb(25, 128, 197);
            --primary-light: #2479b1;
            --primary-dark: #0d5a8d;
            --accent-color: #ff4081;
            --text-primary: #212121;
            --text-secondary: #757575;
            --divider-color: #BDBDBD;
            --background: #f5f5f5;
            --surface: #ffffff;
            --error: #B00020;
            --success: #4CAF50;
            --card-bg: #24425a;
        }

        /* Card Styles */
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 1.5rem;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Monthly Stats */
        .monthly-stats {
            margin-top: 20px;
        }

        .monthly-stats-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .monthly-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .monthly-stats-item {
            background-color: #fff;
            border-radius: 6px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }

        .monthly-stats-item-clickable {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .monthly-stats-item-clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .monthly-stats-month {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .monthly-stats-count {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 5px;
        }

        /* Form Styles */
        .form-control {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 8px 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(29, 106, 157, 0.25);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        /* Button Styles */
        .btn {
            border-radius: 4px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* Alert Styles */
        .alert {
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border-color: var(--success);
            color: var(--success);
        }

        .alert-danger {
            background-color: rgba(176, 0, 32, 0.1);
            border-color: var(--error);
            color: var(--error);
        }

        /* Contract Styles */
        .contract-section {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .contract-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-align: center;
        }

        .contract-subtitle {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .payment-section {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .payment-title {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 0px;
            }

            
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
                padding: 10px;
            }
        }


        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                font-size: 12pt;
                line-height: 1.3;
                background: #fff !important;
                color: #000;
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }
            
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
                border: none !important;
            }
            
            .card-header {
                display: none;
            }
            
            .card-body {
                padding: 0;
            }
            
            .contract-section {
                break-inside: avoid;
                background-color: #fff;
                border: none;
                padding: 0;
                margin: 0 0 15px 0;
            }
            
            .payment-section {
                border: none;
                border-bottom: 1px dotted #000;
                border-radius: 0;
                padding: 5px 0;
                margin-bottom: 10px;
            }
            
            .form-control {
                display: none;
            }
            
            .form-group {
                margin-bottom: 5px;
            }
            
            .btn, .alert {
                display: none;
            }
            
            /* Print-specific styles */
            .print-document {
                width: 21cm;
                min-height: 29.7cm;
                margin: 0 auto;
                padding: 1cm;
                font-size: 12pt;
                line-height: 1.5;
                display: block !important;
            }
            
            /* Header styles - fixed to be in one row */
            .print-header {
                text-align: center;
                margin-bottom: 30px;
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                position: relative;
                height: 80px;
            }
            
            .print-logo {
                width: 80px;
                height: 80px;
                display: flex !important;
                justify-content: center;
                align-items: center;
            }
            
            .print-logo img {
                max-width: 100%;
                max-height: 100%;
            }
            
            .print-title {
                font-size: 18pt;
                font-weight: bold;
                text-align: center;
                flex-grow: 1;
            }
            
            .print-year {
                font-size: 14pt;
                text-align: right;
                width: 150px;
            }
            
            .print-section {
                margin-bottom: 15px;
                display: block !important;
                position: relative;
            }
            
            .print-section-title {
                font-weight: bold;
                text-align: center;
                margin: 15px 0;
                display: block !important;
                font-size: 14pt;
            }
            
            .print-paragraph {
                margin-bottom: 10px;
                text-align: justify;
                display: block !important;
            }
            
            /* Signature section styles */
            .signature-container {
                display: flex !important;
                justify-content: space-between;
                margin-top: 30px;
                width: 100%;
            }
            
            .signature-box {
                width: 45%;
            }
            
            .signature-title {
                font-weight: bold;
                margin-bottom: 15px;
            }
            
            .signature-content {
                margin-bottom: 5px;
            }
            
            .signature-line {
                border-bottom: 1px solid #000;
                margin: 30px 0 5px;
                width: 100%;
            }
            
            .signature-label {
                font-size: 10pt;
                text-align: center;
            }
            
            /* Blank line styles for handwritten information */
            .blank-line {
                display: inline-block;
                position: relative;
                border-bottom: 1px solid #000;
                min-width: 150px;
                text-align: center;
                margin: 0 5px;
            }
            
            /* Hide navbar and sidebar when printing */
            #main-wrapper, .topbar, .left-sidebar, .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                position: static !important;
                width: 100% !important;
            }
            
            .navbar, .sidebar, #sidebar, .left-sidebar, .navbar-header, .topbar {
                display: none !important;
            }
            
            /* Reset margins for print */
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            
            .page-wrapper {
                margin-left: 0 !important;
                padding-left: 0 !important;
            }
            
            /* Hide stats section when printing */
            .monthly-stats {
                display: none !important;
            }
        }

        /* Hide print-only elements when not printing */
        .print-only {
            display: none;
        }

        

        

        .custom-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }

        .custom-modal.active {
            display: flex;
            animation: fadeIn 0.4s ease-out;
        }

        .custom-modal-content {
            background:rgb(255, 255, 255);
            width: min(95%, 900px);
            max-height: 90vh;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            animation: slideIn 0.4s ease-out;
        }

        .custom-close {
            position: relative;
            outline:none;
            border:none;
            top: 0px;
            background: none;
            left: 96%;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            color:rgba(81, 81, 81, 0.3);
            transition: color 0.2s ease;
        }

         .custom-close:hover {
         color: red;
         }

        .ixtisas_h2 {
            font-size: clamp(20px, 5vw, 26px);
            color: var(--text-color);
            margin-bottom: 0px;
            font-weight: 600;
        }

        .checkbox-container {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: #f9fafb;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .checkbox-container label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #eff6ff;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            font-size: clamp(14px, 3.5vw, 16px);
            color: var(--text-color);
        }

        .checkbox-container label:hover {
            background: #eff6ff;
            border-color: var(--primary-color);
        }

        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .teacher-container {
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            max-height: 350px;
            overflow-y: auto;
        }

        .teacher-container h3 {
            font-size: clamp(18px, 4vw, 22px);
            color: var(--text-color);
            margin-bottom: 16px;
            font-weight: 600;
        }

        .ixtisas-folder {
            margin-bottom: 12px;
            border-radius: 8px;
            overflow: hidden;
        }

        .ixtisas-header {
            background: var(--primary-color);
            color: white;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.2s ease;
            font-size: clamp(16px, 3.5vw, 18px);
            font-weight: 500;
        }

        .ixtisas-header .toggle-icon {
            font-size: 14px;
            width: 20px;
            text-align: center;
        }

        .ixtisas-teachers {
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-left: 8px solid var(--accent-color);
            transition: max-height 0.3s ease, opacity 0.3s ease;
        }

        .ixtisas-teachers p {
            color: var(--error-color);
            font-size: clamp(14px, 3.5vw, 16px);
            padding: 8px;
        }

        .teacher-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s ease;
            animation: fadeInUp 0.3s ease-out;
        }

        .teacher-item input[type="checkbox"] {
            width: 17px;
            height: 17px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .teacher-item span {
            flex-grow: 1;
            font-size: clamp(14px, 3.5vw, 16px);
            color: var(--text-color);
        }

        .custom-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: clamp(14px, 4vw, 16px);
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 0px;
        }

        .custom-btn:hover {
            color: white;
            background: var(--primary-color-hover);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeInUp {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 600px) {
            .custom-modal-content {
                width: 95%;
                padding: 16px;
                border-radius: 12px;
            }

            .custom-close {
                top: 1px;
                left: 90%;
            }

            .checkbox-container {
                grid-template-columns: 1fr;
                padding: 12px;
            }

            .custom-btn {
                padding: 10px;
            }

            .ixtisas_h2 {
                font-size: clamp(18px, 4vw, 22px);
            }
        }

        @media (max-width: 400px) {
            .custom-modal-content {
                padding: 12px;
            }

            .custom-btn {
                width: 100%;
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

    <div class="no-print">
        <?php include('navbar_sidebar.php'); ?>
    </div>

    <!-- Country Modal -->
    <div class="modal fade" id="modal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Yeni Ölkə Əlavə Et</h5>
                </div>
                <form id="countryForm" action="qeydiyyatar/insert_country.php" method="POST">
                    <div class="modal-body">
                        <input type="text" id="searchInput" name="country_name" oninput="filterCountries()" placeholder="Ölkə Axtar" class="form-control mb-3" required>
                        <div id="countryList" style="max-height: 360px; overflow-y: auto; border: 1px solid #ccc; border-radius: 6px; padding: 5px;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv Et</button>
                        <button type="submit" class="btn btn-success">Yadda Saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Student Detail Modal -->
    <div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentDetailModalLabel">Qeydiyyatlı Tələbələr</h5>
                </div>
                <div class="modal-body">
                    <div id="studentDetailContent" style="max-height: 400px; overflow-y: auto;">
                        <div class="mb-3">
                            <div class="list-group">
                                <?php
                                    include('db.php');
                                    $today = date('Y-m-d');
                                    $query = "SELECT id, telebe_ad_soyad, created_at FROM qeydiyyatar WHERE DATE(created_at) = '$today' ORDER BY telebe_ad_soyad";
                                    $result = mysqli_query($conn, $query);
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $id = htmlspecialchars($row['id']);
                                            $telebe_ad_soyad = htmlspecialchars($row['telebe_ad_soyad']);
                                            $created_at = date('Y-m-d H:i', strtotime($row['created_at']));
                                            echo "<div class='list-group-item' style='width: 100%; display: block; padding: 12px; margin-bottom: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative;' data-id='$id'>";
                                            echo "<span>$telebe_ad_soyad</span>";
                                            echo "<sup style='margin-left:10px;'>$created_at</sup>";
                                            echo "<i class='fas fa-trash delete-icon' style='position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #dc3545; cursor: pointer; font-size: 16px;' title='Silmək'></i>";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "<p class='text-muted'>Bu gün qeydiyyatlı tələbə tapılmadı</p>";
                                    }
                                    mysqli_close($conn);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content main">
        <!-- İxtisas Modal (No Teachers) -->
        <div id="customModal" class="custom-modal">
            <div class="custom-modal-content">
                <button type="button" class="custom-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h2 class="ixtisas_h2">İxtisas seçin</h2>
                <form id="ixtisasForm" method="post">
                    <div class="checkbox-container">
                        <?php foreach ($ixtisaslar as $ixtisas): ?>
                            <label>
                                <input type="radio" name="selected_ixtisas_radio" value="<?php echo htmlspecialchars($ixtisas['ixtisas_adi']); ?>">
                                <?php echo htmlspecialchars($ixtisas['ixtisas_adi']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="custom-btn" onclick="saveIxtisasSelection()">Seçimi Təsdiqlə</button>
                </form>
            </div>
        </div>

        <div class="container-fluid">
            <!-- Monthly Stats -->
            <div class="row no-print">
                <div class="col-12">
                    <div class="monthly-stats mb-4">
                        <div class="monthly-stats-grid">
                            <?php foreach ($monthly_stats as $stat): ?>
                            <div class="monthly-stats-item monthly-stats-item-clickable"
                                 data-stat-type="month"
                                 data-year="<?= (int) $stat['year'] ?>"
                                 data-month="<?= (int) $stat['month_num'] ?>"
                                 role="button"
                                 tabindex="0"
                                 aria-label="<?= htmlspecialchars(getAzMonthName((int) $stat['month_num']) . ' qeydiyyatları', ENT_QUOTES, 'UTF-8') ?>">
                                <p style="margin-bottom:-26px; text-align:left;">
                                    <?php echo getAzMonthName((int) $stat['month_num']); ?> Qeydiyyatar
                                </p>
                                <div style="text-align:right;" class="monthly-stats-count"><?php echo $stat['count']; ?> <sup style="font-weight:900;"></sup></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-1">
                    <div class="form-group">
                        <button onclick="openModalQeydiyyat()" class="book"> 
                            <i style="font-size:30px;" class="fas fa-book"></i> 
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Main Form -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header no-print">
                            <h4 class="mb-0">Yeni Qeydiyyat Müqaviləsi</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success no-print">
                                    <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                                    <button style="margin-left:10px;" type="button" class="btn btn-sm btn-success" id="printBtn">
                                        <i class="fas fa-print mr-2"></i> Çap Et
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger no-print">
                                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form id="qeydiyyatForm" method="POST" action="" class="no-print" enctype="multipart/form-data">
                                <!-- Contract Section -->
                                <div class="contract-section">
                                    <div class="mb-4 contract-title">QEYDİYYAT MÜQAVİLƏSİ</div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tedris_ili" class="form-label">Tədris ili:</label>
                                                <input type="text" class="form-control" id="tedris_ili" name="tedris_ili" value="<?php echo htmlspecialchars($form_data['tedris_ili']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="odenis_novu" class="form-label">Ödəniş növü:</label>
                                                <select class="form-control" id="odenis_novu" name="odenis_novu" required>
                                                    <option value="paket" <?php echo $form_data['odenis_novu'] === 'paket' ? 'selected' : ''; ?>>Paket</option>
                                                    <option value="ayliq" <?php echo $form_data['odenis_novu'] === 'ayliq' ? 'selected' : ''; ?>>Aylıq</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                            
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="form-label">İxtisas:</label><br>
                                                <button type="button" style="border-radius:12px;" class="custom-btn" id="openModalBtn">Seçin</button>
                                                <div id="selectedInfo" style="margin-top: 10px; font-size: 14px; color: #666;"></div>
                                                <input type="hidden" name="selected_ixtisas" id="selected_ixtisas">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="baslama_tarixi" class="form-label">Başlama tarixi:</label>
                                                <input type="date" class="form-control" id="baslama_tarixi" name="baslama_tarixi" value="<?php echo htmlspecialchars($form_data['baslama_tarixi']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tehsil_haqqi" class="form-label">Təhsil haqqı (AZN):</label>
                                                <input type="number" step="0.01" class="form-control" id="tehsil_haqqi" name="tehsil_haqqi" value="<?php echo htmlspecialchars($form_data['tehsil_haqqi']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ders_sayi" class="form-label">Ay ərzində dərs sayı:</label>
                                                <input type="number" class="form-control" id="ders_sayi" name="ders_sayi" value="<?php echo htmlspecialchars($form_data['ders_sayi']); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="payment-title">İlkin ödəniş məbləği (AZN):</div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <input type="number" step="0.01" class="form-control" id="ilkin_odenis" name="ilkin_odenis" value="<?php echo htmlspecialchars($form_data['ilkin_odenis']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Student Registration Section -->
                                <div class="contract-section">
                                    <div class="mb-4 contract-title">TƏLƏBƏ QEYDİYYATI</div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="firstName">Ad</label>
                                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="lastName">Soyad</label>
                                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="qebul_tarixi">Qəbul tarixi</label>
                                                <input type="date" class="form-control" id="qebul_tarixi" name="qebul_tarixi" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="gender">Cins</label>
                                                <select class="form-control" id="gender" name="gender" required>
                                                    <option value="">Seçin</option>
                                                    <option value="male">Kişi</option>
                                                    <option value="female">Qadın</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="dogum_tarixi">Doğum tarixi</label>
                                                <input type="date" class="form-control" id="dogum_tarixi" name="dogum_tarixi" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="yas">Yaş</label>
                                                <input type="number" min="0" class="form-control" id="yas" name="yas">
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ata">Ata</label>
                                                <input type="text" class="form-control" id="ata" name="ata" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ata_nomre">Əlaqə nömrəsi</label>
                                                <input type="number" min="0" class="form-control" id="ata_nomre" name="elaqe_nomre_ata">
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ana">Ana</label>
                                                <input type="text" class="form-control" id="ana" name="ana" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ana_nomre">Əlaqə nömrəsi</label>
                                                <input type="number" min="0" class="form-control" id="ana_nomre" name="elaqe_nomre_ana">
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="class">Sinif</label>
                                                <select class="form-control" id="class" name="class" required>
                                                    <option value="">Seçin</option>
                                                    <?php
                                                    include('db.php');
                                                    $sql = "SELECT id, sinif_number FROM sinifler";
                                                    $result = $conn->query($sql);
                                                    if ($result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["sinif_number"]) . "</option>";
                                                        }
                                                    }
                                                    $conn->close();
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="active">Aktiv</option>
                                                    <option value="inactive">Qeyri-aktiv</option>
                                                    <option value="graduate">Məzun</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone">Telefon</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                            </div>
                                        </div>
                                    </div>
                                    <br>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="citizenship">Vətəndaşlıq</label>
                                                <button style="transform: scale(0.9); background-color: #f9f9f9; border: 0px solid #ddd; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); cursor: pointer;" class="btn" onclick="event.preventDefault(); openModal()"> 
                                                    <i style="font-size: 17px; color: #333;" class="fas fa-plus"></i>
                                                </button>                                                        
                                                <select class="mt-2 form-control" id="citizenship" name="class" required>
                                                    <option value="">Seçin</option>
                                                    <?php
                                                    include('db.php');
                                                    $sql = "SELECT id, country_name FROM vetandasliq";
                                                    $result = $conn->query($sql);
                                                    if ($result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["country_name"]) . "</option>";
                                                        }
                                                    }
                                                    $conn->close();
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="address">Ünvan</label>
                                                <textarea class="form-control" id="address" name="address"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-file">
                                            <input hidden type="file" class="custom-file-input" id="fileInput" name="photo" accept="image/*">
                                            <label class="custom-file-label" for="fileInput">Şəkil seçin</label>
                                        </div>
                                        <input type="hidden" name="photo_data" id="photoData">
                                        <div class="mt-2">
                                            <img id="preview" src="/placeholder.svg" style="display: none; max-width: 200px; max-height: 200px;">
                                        </div>
                                    </div>
                                    <br>
                                </div>

                                <div class="row mt-4">
                                    <div>
                                        <button type="button" class="mb-1 btn btn-secondary mr-2" id="printBtn">
                                            <i class="fas fa-print mr-1"></i> Çap Et
                                        </button>
                                        <button type="reset" class="mb-1 btn btn-danger">
                                            <i class="fas fa-redo-alt mr-1"></i> Formu Təmizlə
                                        </button>
                                        <button type="submit" class="mb-1 btn btn-primary mr-2">
                                            <i class="fas fa-save mr-1"></i> Qeydiyyatı Tamamla
                                        </button>
                                    </div>
                                </div>
                            </form>
                
                            <!-- Print Document -->
                            <div class="print-only print-document">
                                <div class="print-header">
                                    <div class="print-logo">
                                        <img src="images/logo.jpg" alt="Logo" style="margin-left:70px; transform:scale(0.5); min-width: 350px; height: 60px;">
                                    </div>
                                    <div style="margin-left:95px;" class="print-title">QEYDİYYAT MÜQAVİLƏSİ</div>
                                    <div class="print-year"><?php echo htmlspecialchars($form_data['tedris_ili']); ?></div>
                                </div>
                                
                                <div class="print-section">
                                    <p class="print-paragraph">
                                        Bu müqavilə, bir tərəfdən, bundan sonra "Tədris Mərkəzi" adlanacaq "Magistratura.az" Təhsil Mərkəzinin rəhbəri  
                                        fiziki şəxs Nurəliyev Anar Ziyəddin oğlu, digər tərəfdən bundan sonra "Tələbə" adlanacaq 
                                        <span class="blank-line"><?php echo htmlspecialchars($form_data['telebe_ad_soyad']); ?></span>
                                        arasında Azərbaycan Respublikasının müvafiq qanunvericiliyinə uyğun 
                                        olaraq bağlanılır. Bu müqavilə bağlanan gündən "Tədris Mərkəzi" və Tələbənin arasında yaranmış münasibətlər, tərəflərin 
                                        hüquqları, vəzifələri və məsuliyyəti müəyyən edilmiş qaydalarla tənzimlənir.
                                    </p>
                                </div>
                                
                                <div class="print-section">
                                    <p style="text-align: center; font-weight: bold; margin: 20px 0;">MÜQAVİLƏNİN PREDMETİ</p>

                                    <p class="print-paragraph">
                                        Bu müqavilənin predmetini "Tədris Mərkəz"i tərəfdən Tələbənin 
                                        <span class="blank-line"><?php echo formatDate($form_data['baslama_tarixi']); ?></span> tarixdən etibarən 
                                        <strong><?php echo htmlspecialchars($form_data['ixtisas_adi']); ?></strong> ixtisası üzrə tədris xidmətinin göstərilməsi, "Tələbə" tərəfdən isə xidmət haqqının ödənilməsi 
                                        və digər öhdəliklərlə bağlı yaranacaq münasibətlərin tənzimlənməsi təşkil edir.
                                    </p>
                                    <p class="print-paragraph">
                                        Təhsil haqqı <strong><?php echo htmlspecialchars($form_data['tehsil_haqqi']); ?> AZN</strong> təşkil edir.
                                    </p>
                                    <p class="print-paragraph">
                                        Ödəniş növü: <strong><?php echo $form_data['odenis_novu'] === 'paket' ? 'paket' : 'aylıq'; ?></strong>
                                    </p>
                                </div>
                                
                                <div class="print-section">
                                    <p style="text-align: center; font-weight: bold; margin: 20px 0;">TƏRƏFLƏRİN HÜQUQ VƏ ÖHDƏLİKLƏRİ</p>
                                    <div class="print-subsection">
                                        <p><strong>TƏLƏBƏ</strong></p>
                                        <ul style="list-style-type: none; padding-left: 10px;">
                                            <li style="margin-bottom: 8px;">Qeydiyyatdan keçdiyi zaman ilkin ödəniş 30 AZN ödəyir (kursa gəlmədiyi halda ilkin ödəniş geri qaytarılmır).</li>
                                            <li style="margin-bottom: 8px;">Davamlı olaraq dərslərdə iştirak edir və iştirak etmədiyi dərslər (üzürlü və üzürsüz səbəblər) dərs prossesinə aiddir və ödəniş zamanı hesablanır.</li>
                                            <li style="margin-bottom: 8px;">Tələbə keçirilən kurs üzrə keyfiyyətin əldə olunması üçün dərslərə hazırlıqlı gəlməli, verilən tapşırıqları məsuliyyətlə yerinə yetirməlidir.</li>
                                            <li style="margin-bottom: 8px;">Ödəniş tarixi barədə məlumat müqavilədə qeyd olunur, təyin edilmiş vaxtlarda ödəniş ödənilməzsə, tələbənin dərsləri dayandırılır.</li>
                                            <li style="margin-bottom: 8px;">Tələbənin paralel olaraq bir neçə qrupla dərslərdə iştirakına icazə verilmir və iştirak etmədiyi dərslər əvəz olunmur.</li>
                                            <li style="margin-bottom: 8px;">Tələbə dərsləri dayandırdığını bildirdiyi tarixədək keçirilən dərslərin və tədris proqramı başa çatmadan hazırlığı dayandırdığı üçün təqdim edilmiş dərs vəsaitlərinin ödənişi hesablanır.</li>
                                            <li style="margin-bottom: 8px;">Onlayn tədris üzrə qeydiyyatdan keçən tələbələr üçün video dərs materialları imtahan müddətinə qədər aktiv olur.</li>
                                            <li style="margin-bottom: 8px;">Hər hansı bir narazılıq meydana gələrsə, bu barədə yazılı və ya şifahi şəkildə məlumat bölməsinə, fənn koordinatorlarına və birbaşa rəhbərliyə müraciət edə bilər.</li>
                                        </ul>
                                    </div>
                                    <div class="print-subsection">
                                        <p><strong>TƏDRİS MƏRKƏZİ</strong></p>
                                        <ul style="list-style-type: none; padding-left: 10px;">
                                            <li style="margin-bottom: 8px;">Paket və ya aylıq ödənişlə qeydiyyatdan keçib tədris proqramının sonunadək davam edən tələbələri dərs vəsaitləri ilə ödənişsiz təmin edir (tələbə kursu sonuna qədər davam etmədiyi halda kitabların ödənişi hesablanır);</li>
                                            <li style="margin-bottom: 8px;">Təhsilin keyfiyyətinə təminat verir;</li>
                                            <li style="margin-bottom: 8px;">Dərsin vaxtında keçirilməsini və lazımı bütün şəraitin yaradılmasını təmin edir;</li>
                                            <li style="margin-bottom: 8px;">Xarici dil dərslərini beynəlxalq sertifikatlı müəllimlərin tədris etməsini təmin edir;</li>
                                            <li style="margin-bottom: 8px;">Ay ərzində Məntiq / İnformatika / Xarici dil fən(lər)i üzrə <strong><?php echo htmlspecialchars($form_data['ders_sayi']); ?></strong> dərs keçirilməsini təmin edir;</li>
                                        </ul>
                                        <p>Tədris Mərkəzi tərəfindən təmin edilmiş dərs vəsaitləri itirildikdə və ya yararsız hala salındıqda tələbə vəsaitin yenisini yalnız ödənişli şəkildə əldə edə bilər;</p>
                                        <p>Paket ödənişi ilə qeydiyyatdan keçən tələbə hazırlıqdan imtina edərsə, keçirilmiş dərslərin aylıq ödənişi 140 AZN üzərindən hesablanır.</p>
                                    </div>
                                </div>
                                
                                <div class="print-section">
                                    <p style="text-align: center; font-weight: bold; margin: 20px 0;">TƏRƏFLƏRİN İMZALARI</p>
                                    
                                    <div class="signature-container">
                                        <div class="signature-box" style="float: left; width: 45%;">
                                            <div class="signature-title">TƏDRİS MƏRKƏZİ:</div>
                                            <div class="signature-content">"Magistratura.az"</div>
                                            <div class="signature-content">Nurəliyev A.Z</div>
                                            <div class="signature-line"></div>
                                            <div class="signature-label">(imza və möhür)</div>
                                        </div>
                                        
                                        <div class="signature-box" style="float: right; width: 45%;">
                                            <div class="signature-title">TƏLƏBƏ:</div>
                                            <div class="signature-content"><?php echo htmlspecialchars($form_data['telebe_ad_soyad']); ?></div>
                                            <div class="signature-line"></div>
                                            <div class="signature-label">(imza)</div>
                                        </div>
                                        <div style="clear: both;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>

    <script>
        function saveIxtisasSelection() {
            const selectedIxtisasRadio = document.querySelector('input[name="selected_ixtisas_radio"]:checked');
            if (selectedIxtisasRadio) {
                const selectedIxtisas = selectedIxtisasRadio.value;
                document.getElementById('selected_ixtisas').value = selectedIxtisas;
                document.getElementById('selectedInfo').innerHTML = `
                    <strong>Seçilmiş İxtisas:</strong> ${selectedIxtisas}
                `;
                
                document.getElementById('customModal').classList.remove('active');
            } else {
                alert('Zəhmət olmasa ixtisas seçin!');
            }
        }

        const openModalBtn = document.getElementById('openModalBtn');
        const modalElement = document.getElementById('customModal');
        const closeBtn = document.querySelector('.custom-close');
        if (openModalBtn) {
            openModalBtn.addEventListener('click', () => {
                modalElement.classList.add('active');
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modalElement.classList.remove('active');
            });
        }

        if (modalElement) {
            modalElement.addEventListener('click', (e) => {
                if (e.target === modalElement) {
                    modalElement.classList.remove('active');
                }
            });
        }

        function openModalQeydiyyat() {
            const studentDetailModal = new bootstrap.Modal(document.getElementById('studentDetailModal'));
            studentDetailModal.show();
        }

        const countries = [
            ["Afghanistan", "🇦🇫"], ["Albania", "🇦🇱"], ["Algeria", "🇩🇿"], ["Andorra", "🇦🇩"],
            ["Angola", "🇦🇴"], ["Antigua and Barbuda", "🇦🇬"], ["Argentina", "🇦🇷"], ["Armenia", "🇦🇲"],
            ["Australia", "🇦🇺"], ["Austria", "🇦🇹"], ["Azerbaijan", "🇦🇿"], ["Bahamas", "🇧🇸"],
            ["Bahrain", "🇧🇭"], ["Bangladesh", "🇧🇩"], ["Barbados", "🇧🇧"], ["Belarus", "🇧🇾"],
            ["Belgium", "🇧🇪"], ["Belize", "🇧🇿"], ["Benin", "🇧🇯"], ["Bhutan", "🇧🇹"],
            ["Bolivia", "🇧🇴"], ["Bosnia and Herzegovina", "🇧🇦"], ["Botswana", "🇧🇼"], ["Brazil", "🇧🇷"],
            ["Brunei", "🇧🇳"], ["Bulgaria", "🇧🇬"], ["Burkina Faso", "🇧🇫"], ["Burundi", "🇧🇮"],
            ["Cambodia", "🇰🇭"], ["Cameroon", "🇨🇲"], ["Canada", "🇨🇦"], ["Cape Verde", "🇨🇻"],
            ["Central African Republic", "🇨🇫"], ["Chad", "🇹🇩"], ["Chile", "🇨🇱"], ["China", "🇨🇳"],
            ["Colombia", "🇨🇴"], ["Comoros", "🇰🇲"], ["Congo - Brazzaville", "🇨🇬"], ["Congo - Kinshasa", "🇨🇩"],
            ["Costa Rica", "🇨🇷"], ["Croatia", "🇭🇷"], ["Cuba", "🇨🇺"], ["Cyprus", "🇨🇾"],
            ["Czech Republic", "🇨🇿"], ["Denmark", "🇩🇰"], ["Djibouti", "🇩🇯"], ["Dominica", "🇩🇲"],
            ["Dominican Republic", "🇩🇴"], ["Ecuador", "🇪🇨"], ["Egypt", "🇪🇬"], ["El Salvador", "🇸🇻"],
            ["Equatorial Guinea", "🇬🇶"], ["Eritrea", "🇪🇷"], ["Estonia", "🇪🇪"], ["Eswatini", "🇸🇿"],
            ["Ethiopia", "🇪🇹"], ["Fiji", "🇫🇯"], ["Finland", "🇫🇮"], ["France", "🇫🇷"],
            ["Gabon", "🇬🇦"], ["Gambia", "🇬🇲"], ["Georgia", "🇬🇪"], ["Germany", "🇩🇪"],
            ["Ghana", "🇬🇭"], ["Greece", "🇬🇷"], ["Grenada", "🇬🇩"], ["Guatemala", "🇬🇹"],
            ["Guinea", "🇬🇳"], ["Guinea-Bissau", "🇬🇼"], ["Guyana", "🇬🇾"], ["Haiti", "🇭🇹"],
            ["Honduras", "🇭🇳"], ["Hungary", "🇭🇺"], ["Iceland", "🇮🇸"], ["India", "🇮🇳"],
            ["Indonesia", "🇮🇩"], ["Iran", "🇮🇷"], ["Iraq", "🇮🇶"], ["Ireland", "🇮🇪"],
            ["Israel", "🇮🇱"], ["Italy", "🇮🇹"], ["Jamaica", "🇯🇲"], ["Japan", "🇯🇵"],
            ["Jordan", "🇯🇴"], ["Kazakhstan", "🇰🇿"], ["Kenya", "🇰🇪"], ["Kiribati", "🇰🇮"],
            ["Kuwait", "🇰🇼"], ["Kyrgyzstan", "🇰🇬"], ["Laos", "🇱🇦"], ["Latvia", "🇱🇻"],
            ["Lebanon", "🇱🇧"], ["Lesotho", "🇱🇸"], ["Liberia", "🇱🇷"], ["Libya", "🇱🇾"],
            ["Liechtenstein", "🇱🇮"], ["Lithuania", "🇱🇹"], ["Luxembourg", "🇱🇺"], ["Madagascar", "🇲🇬"],
            ["Malawi", "🇲🇼"], ["Malaysia", "🇲🇾"], ["Maldives", "🇲🇻"], ["Mali", "🇲🇱"],
            ["Malta", "🇲🇹"], ["Marshall Islands", "🇲🇭"], ["Mauritania", "🇲🇷"], ["Mauritius", "🇲🇺"],
            ["Mexico", "🇲🇽"], ["Micronesia", "🇫🇲"], ["Moldova", "🇲🇩"], ["Monaco", "🇲🇨"],
            ["Mongolia", "🇲🇳"], ["Montenegro", "🇲🇪"], ["Morocco", "🇲🇦"], ["Mozambique", "🇲🇿"],
            ["Myanmar", "🇲🇲"], ["Namibia", "🇳🇦"], ["Nauru", "🇳🇷"], ["Nepal", "🇳🇵"],
            ["Netherlands", "🇳🇱"], ["New Zealand", "🇳🇿"], ["Nicaragua", "🇳🇮"], ["Niger", "🇳🇪"],
            ["Nigeria", "🇳🇬"], ["North Korea", "🇰🇵"], ["North Macedonia", "🇲🇰"], ["Norway", "🇳🇴"],
            ["Oman", "🇴🇲"], ["Pakistan", "🇵🇰"], ["Palau", "🇵🇼"], ["Palestine", "🇵🇸"],
            ["Panama", "🇵🇦"], ["Papua New Guinea", "🇵🇬"], ["Paraguay", "🇵🇾"], ["Peru", "🇵🇪"],
            ["Philippines", "🇵🇭"], ["Poland", "🇵🇱"], ["Portugal", "🇵🇹"], ["Qatar", "🇶🇦"],
            ["Romania", "🇷🇴"], ["Russia", "🇷🇺"], ["Rwanda", "🇷🇼"], ["Saint Kitts and Nevis", "🇰🇳"],
            ["Saint Lucia", "🇱🇨"], ["Saint Vincent and the Grenadines", "🇻🇨"], ["Samoa", "🇼🇸"], ["San Marino", "🇸🇲"],
            ["Sao Tome and Principe", "🇸🇹"], ["Saudi Arabia", "🇸🇦"], ["Senegal", "🇸🇳"], ["Serbia", "🇷🇸"],
            ["Seychelles", "🇸🇨"], ["Sierra Leone", "🇸🇱"], ["Singapore", "🇸🇬"], ["Slovakia", "🇸🇰"],
            ["Slovenia", "🇸🇮"], ["Solomon Islands", "🇸🇧"], ["Somalia", "🇸🇴"], ["South Africa", "🇿🇦"],
            ["South Korea", "🇰🇷"], ["South Sudan", "🇸🇸"], ["Spain", "🇪🇸"], ["Sri Lanka", "🇱🇰"],
            ["Sudan", "🇸🇩"], ["Suriname", "🇸🇷"], ["Sweden", "🇸🇪"], ["Switzerland", "🇨🇭"],
            ["Syria", "🇸🇾"], ["Taiwan", "🇹🇼"], ["Tajikistan", "🇹🇯"], ["Tanzania", "🇹🇿"],
            ["Thailand", "🇹🇭"], ["Togo", "🇹🇬"], ["Tonga", "🇹🇴"], ["Trinidad and Tobago", "🇹🇹"],
            ["Tunisia", "🇹🇳"], ["Turkey", "🇹🇷"], ["Turkmenistan", "🇹🇲"], ["Tuvalu", "🇹🇻"],
            ["Uganda", "🇺🇬"], ["Ukraine", "🇺🇦"], ["United Arab Emirates", "🇦🇪"], ["United Kingdom", "🇬🇧"],
            ["United States", "🇺🇸"], ["Uruguay", "🇺🇾"], ["Uzbekistan", "🇺🇿"], ["Vanuatu", "🇻🇺"],
            ["Vatican City", "🇻🇦"], ["Venezuela", "🇻🇪"], ["Vietnam", "🇻🇳"], ["Yemen", "🇾🇪"],
            ["Zambia", "🇿🇲"], ["Zimbabwe", "🇿🇼"]
        ].map(([name, flag]) => ({ name, flag }));

        function openModal() {
            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            const modal = new bootstrap.Modal(document.getElementById('modal'));
            modal.show();
            loadCountries();
            window.scrollTo(0, scrollPosition);
        }

        function closeModal() {
            const modal = new bootstrap.Modal(document.getElementById('modal'));
            modal.hide(); 
            document.body.style.overflow = ''; 
        }

        function loadCountries() {
            const countryList = document.getElementById('countryList');
            countryList.innerHTML = '';
            countries.forEach(country => {
                const div = document.createElement('div');
                div.innerHTML = `${country.flag} ${country.name}`;
                div.style.padding = '6px';
                div.style.cursor = 'pointer';
                div.style.borderRadius = '4px';
                div.onmouseover = () => div.style.background = '#f0f0f0';
                div.onmouseout = () => div.style.background = 'transparent';
                div.onclick = () => selectCountry(country.name);
                countryList.appendChild(div);
            });
        }

        function filterCountries() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const countryList = document.getElementById('countryList');
            countryList.innerHTML = '';
            countries
              .filter(country => country.name.toLowerCase().includes(search))
              .forEach(country => {
              const div = document.createElement('div');
              div.innerHTML = `${country.flag} ${country.name}`;
              div.style.padding = '6px';
              div.style.cursor = 'pointer';
              div.style.borderRadius = '4px';
              div.onmouseover = () => div.style.background = '#f0f0f0';
              div.onmouseout = () => div.style.background = 'transparent';
              div.onclick = () => selectCountry(country.name);
              countryList.appendChild(div);
            });
        }

        function selectCountry(name) {
            document.getElementById('searchInput').value = name;
            document.getElementById('countryList').innerHTML = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('fileInput');
            const preview = document.getElementById('preview');
            const photoData = document.getElementById('photoData');
            
            if (fileInput) {
                fileInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const fileLabel = document.querySelector('.custom-file-label');
                        if (fileLabel) {
                            fileLabel.textContent = file.name;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            photoData.value = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            const printBtns = document.querySelectorAll('#printBtn');
            printBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    window.print();
                });
            });
            
            const qeydiyyatForm = document.getElementById('qeydiyyatForm');
            if (qeydiyyatForm) {
                qeydiyyatForm.addEventListener('submit', function(e) {
                    const selectedIxtisas = document.getElementById('selected_ixtisas').value;
                    if (!selectedIxtisas) {
                        alert('Zəhmət olmasa İxtisas seçin!');
                        e.preventDefault();
                        return false;
                    }
                    
                    const tehsilHaqqi = parseFloat(document.getElementById('tehsil_haqqi').value) || 0;
                    const ilkinOdenis = parseFloat(document.getElementById('ilkin_odenis').value) || 0;
                    
                    if (tehsilHaqqi <= 0) {
                        alert('Təhsil haqqı düzgün daxil edilməyib!');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (ilkinOdenis < 30) {
                        alert('İlkin ödəniş minimum 30 AZN olmalıdır!');
                        e.preventDefault();
                        return false;
                    }
                    
                    return true;
                });
            }

            const modalBody = document.getElementById('studentDetailContent');
            if (modalBody) {
                modalBody.addEventListener('click', (e) => {
                    const deleteIcon = e.target.closest('.delete-icon');
                    if (deleteIcon) {
                        e.stopPropagation();
                        const listItem = deleteIcon.parentElement;
                        const id = listItem.getAttribute('data-id');
                        const existingModals = document.querySelectorAll('.delete-modal');
                        const existingOverlays = document.querySelectorAll('.modal-overlay');
                        existingModals.forEach(modal => modal.remove());
                        existingOverlays.forEach(overlay => overlay.remove());
                        const modal = document.createElement('div');
                        modal.className = 'delete-modal';
                        modal.style.cssText = `
                            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                            background: white; padding: 20px; border-radius: 8px;
                            box-shadow: 0 4px 8px rgba(0,0,0,0.2); z-index: 10000;
                            width: 300px; text-align: center;
                        `;
                        modal.innerHTML = `
                            <p style="margin-bottom: 20px;">Bu tələbəni silmək istəyirsiniz?</p>
                            <button id="confirmDelete" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Təsdiqlə</button>
                            <button id="cancelDelete" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Ləğv et</button>
                        `;

                        const overlay = document.createElement('div');
                        overlay.className = 'modal-overlay';
                        overlay.style.cssText = `
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                            background: rgba(0,0,0,0.5); z-index: 9999;
                        `;
                        document.body.appendChild(overlay);
                        document.body.appendChild(modal);
                        modal.querySelector('#confirmDelete').addEventListener('click', () => {
                            fetch('qeydiyyatar/delete_student.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'id=' + encodeURIComponent(id),
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    listItem.remove();
                                    location.reload();
                                    const listGroup = modalBody.querySelector('.list-group');
                                    if (!listGroup.querySelector('.list-group-item')) {
                                        listGroup.innerHTML = "<p class='text-muted'>Qeydiyyatlı tələbə tapılmadı</p>";
                                    }
                                } else {
                                    alert(data.error || 'Silmə əməliyyatı uğursuz oldu');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Silmə əməliyyatı uğursuz oldu');
                            })
                            .finally(() => {
                                modal.remove();
                                overlay.remove();
                                document.body.style.overflow = '';
                            });
                        });

                        modal.querySelector('#cancelDelete').addEventListener('click', () => {
                            modal.remove();
                            overlay.remove();
                            document.body.style.overflow = '';
                        });
                    }
                });
            }

            if (window.location.search.includes('success=1')) {
                setTimeout(function() {
                    window.print();
                }, 1000);
            }

            document.querySelectorAll('input[name="selected_ixtisas_radio"]').forEach(radio => {
                radio.addEventListener('click', function() {
                    document.querySelectorAll('.checkbox-container label').forEach(label => {
                        label.style.backgroundColor = '';
                        label.style.borderColor = '#ddd';
                    });
                    
                    this.parentElement.style.backgroundColor = '#e3f2fd';
                    this.parentElement.style.borderColor = '#2196f3';
                });
            });
        });
    </script>

    <div class="modal fade" id="statDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
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

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var azMonths = {
            1: 'Yanvar', 2: 'Fevral', 3: 'Mart', 4: 'Aprel',
            5: 'May', 6: 'İyun', 7: 'İyul', 8: 'Avqust',
            9: 'Sentyabr', 10: 'Oktyabr', 11: 'Noyabr', 12: 'Dekabr'
        };

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function openMonthlyStatModal(item) {
            var year = item.dataset.year;
            var month = item.dataset.month;
            var modalEl = document.getElementById('statDetailsModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            var monthName = azMonths[parseInt(month, 10)] || month;
            document.getElementById('statDetailsTitle').textContent = monthName + ' ' + year + ' Qeydiyyatları';
            document.getElementById('statDetailsLoading').classList.remove('d-none');
            document.getElementById('statDetailsContent').classList.add('d-none');
            document.getElementById('statDetailsEmpty').classList.add('d-none');
            document.getElementById('statDetailsHead').innerHTML = '';
            document.getElementById('statDetailsBody').innerHTML = '';

            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            var url = 'qeydiyyatar/qeydiyyat_stat_operations.php?type=month'
                + '&year=' + encodeURIComponent(year)
                + '&month=' + encodeURIComponent(month);

            fetch(url)
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    document.getElementById('statDetailsLoading').classList.add('d-none');
                    if (data.status !== 'success' || !data.data || !data.data.length) {
                        document.getElementById('statDetailsEmpty').classList.remove('d-none');
                        return;
                    }
                    document.getElementById('statDetailsContent').classList.remove('d-none');
                    var headHtml = '<tr>';
                    data.columns.forEach(function (column) {
                        headHtml += '<th>' + escapeHtml(column.label) + '</th>';
                    });
                    headHtml += '</tr>';
                    document.getElementById('statDetailsHead').innerHTML = headHtml;

                    var bodyHtml = '';
                    data.data.forEach(function (row) {
                        bodyHtml += '<tr>';
                        data.columns.forEach(function (column) {
                            bodyHtml += '<td>' + escapeHtml(row[column.key] ?? '-') + '</td>';
                        });
                        bodyHtml += '</tr>';
                    });
                    document.getElementById('statDetailsBody').innerHTML = bodyHtml;
                })
                .catch(function () {
                    document.getElementById('statDetailsLoading').classList.add('d-none');
                    document.getElementById('statDetailsEmpty').classList.remove('d-none');
                });
        }

        document.querySelectorAll('.monthly-stats-item-clickable').forEach(function (item) {
            item.addEventListener('click', function () {
                openMonthlyStatModal(item);
            });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openMonthlyStatModal(item);
                }
            });
        });
    });
    </script>

</body>
</html>