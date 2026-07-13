<?php
// Add PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Baku');
include('db.php');

// Session check function
function checkSession($conn) {
    if (!isset($_SESSION['u_id']) || empty($_SESSION['u_id'])) {
        throw new Exception('İstifadəçi daxil olmayıb.');
    }

    $student_u_id = mysqli_real_escape_string($conn, $_SESSION['u_id']);

    $sql = "SELECT u_id, username FROM telebeler WHERE u_id = ? AND active_status = 'active'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Verilənlər bazası xətası: ' . $conn->error);
    }

    $stmt->bind_param("s", $student_u_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception('Tələbə məlumatları tapılmadı və ya hesab aktiv deyil.');
    }

    $student_info = $result->fetch_assoc();
    $stmt->close();

    $timeout_duration = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
        session_unset();
        session_destroy();
        throw new Exception('Sessiya vaxtı bitdi. Zəhmət olmasa yenidən daxil olun.');
    }

    $_SESSION['last_activity'] = time();
    $_SESSION['student_display_username'] = $student_info['username'];

    return $student_info['u_id'];
}

function getStudentDisplayUsername($conn, $student_u_id) {
    if (!empty($_SESSION['student_display_username'])) {
        return $_SESSION['student_display_username'];
    }

    $sql = "SELECT username FROM telebeler WHERE u_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $_SESSION['username'] ?? '';
    }

    $stmt->bind_param("s", $student_u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['username'] ?? ($_SESSION['username'] ?? '');
}

// Function to get total required lessons from qeydiyyatar table
function getTotalRequiredLessons($conn, $u_id) {
    $sql = "SELECT ders_sayi FROM qeydiyyatar WHERE u_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['ders_sayi'];
    }
    
    $stmt->close();
    return 10; // Default fallback
}

// Function to get current lesson count from qr_scans table
function getCurrentLessonCount($conn, $teacher_username, $student_username) {
    $sql = "SELECT COUNT(*) as current_count, MAX(lesson_count) as max_lesson_count FROM qr_scans WHERE teacher_username = ? AND student_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $teacher_username, $student_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return [
            'current_count' => (int)$row['current_count'],
            'max_lesson_count' => (int)$row['max_lesson_count']
        ];
    }
    
    $stmt->close();
    return [
        'current_count' => 0,
        'max_lesson_count' => 0
    ];
}

// Function to send lesson notification email when 1 lesson remains
function sendLessonReminderEmail($email, $username, $fullName, $teacherName, $subject, $completedLessons, $totalLessons, $u_id) {
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
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Dərs Xatırlatması - 1 Dərs Qalıb';
        
        $remainingLessons = $totalLessons - $completedLessons;
        
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
                    background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
                    color: #ffffff;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 700;
                    color: #ffffff;
                }
                .header p {
                    margin: 8px 0 0;
                    font-size: 16px;
                    color: #ffffff;
                    opacity: 0.9;
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
                .lesson-info {
                    background-color: #fff3cd;
                    padding: 25px;
                    border-radius: 8px;
                    border-left: 5px solid #ffc107;
                    margin: 25px 0;
                }
                .lesson-info p {
                    margin: 10px 0;
                    font-size: 16px;
                    color: #333;
                }
                .student-details {
                    background-color: #f8f9fa;
                    padding: 25px;
                    border-radius: 8px;
                    border: 1px solid #e8e8e8;
                    margin: 25px 0;
                }
                .student-details p {
                    margin: 10px 0;
                    font-size: 16px;
                }
                .reminder {
                    color: #856404;
                    font-weight: 600;
                    font-size: 18px;
                    margin: 20px 0;
                    padding: 15px;
                    background-color: #fff3cd;
                    border-radius: 8px;
                    border-left: 4px solid #ffc107;
                    text-align: center;
                }
                .progress-bar {
                    background-color: #e9ecef;
                    border-radius: 10px;
                    height: 20px;
                    margin: 20px 0;
                    overflow: hidden;
                }
                .progress-fill {
                    background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
                    height: 100%;
                    border-radius: 10px;
                    transition: width 0.3s ease;
                }
                .button {
                    display: block;
                    width: 90%;
                    padding: 12px;
                    background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    font-size: 16px;
                    text-align: center;
                    transition: background 0.3s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    margin: 20px auto;
                }
                .button:hover {
                    background: linear-gradient(135deg, #ff8c00 0%, #ffc107 100%);
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
                    <h1>⚠️ Dərs Xatırlatması</h1>
                    <p>Magistratura AZ</p>
                </div>
                
                <div class='content'>
                    <h2>Hörmətli $fullName,</h2>
                    
                    <p>Bu gün keçirdiyiniz dərslə bağlı vacib bir xatırlatmamız var:</p>
                    
                    <div class='reminder'>
                        ⚠️ Sizə yalnız 1 dərs qalıb!
                    </div>
                    
                    <div class='lesson-info'>
                        <h3>📚 Dərs Məlumatları:</h3>
                        <p><strong>👨‍🏫 Müəllim:</strong> $teacherName</p>
                        <p><strong>📖 Fənn:</strong> $subject</p>
                        <p><strong>✅ Keçirilən Dərs:</strong> $completedLessons / $totalLessons</p>
                        <p><strong>📊 Qalan Dərs:</strong> $remainingLessons</p>
                        <p><strong>📅 Tarix:</strong> " . date('d.m.Y H:i') . "</p>
                    </div>
                    
                    <div class='progress-bar'>
                        <div class='progress-fill' style='width: " . (($completedLessons / $totalLessons) * 100) . "%;'></div>
                    </div>
                    <p style='text-align: center; font-size: 14px; color: #666;'>Tamamlanma: " . round(($completedLessons / $totalLessons) * 100, 1) . "%</p>
                    
                    <div class='student-details'>
                        <h3>👤 Tələbə Məlumatları:</h3>
                        <p><strong>Ad və Soyad:</strong> $fullName</p>
                    </div>
                    
                    <p>Dərs proqramınızı tamamlamaq üçün yalnız 1 dərs qalıb. Növbəti dərsinizi planlaşdırmaq üçün müəlliminizlə əlaqə saxlayın.</p>                    
                    <p>Təhsilinizdə uğurlar diləyirik!</p>
                </div>
                
                <div class='footer'>
                    <p>📧 Magistratura AZ - Bütün hüquqlar qorunur.</p>
                    <p>Bu avtomatik göndərilən e-poçt mesajıdır.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "
        Hörmətli $fullName,
        
        Bu gün keçirdiyiniz dərslə bağlı vacib bir xatırlatmamız var:
        
        ⚠️ SİZƏ YALNIZ 1 DƏRS QALIB! ⚠️
        
        Dərs Məlumatları:
        Müəllim: $teacherName
        Fənn: $subject
        Keçirilən Dərs: $completedLessons / $totalLessons
        Qalan Dərs: $remainingLessons
        Tarix: " . date('d.m.Y H:i') . "
        
        Tələbə Məlumatları:
        Ad və Soyad: $fullName
        İstifadəçi adı: $username
        Tələbə ID: $u_id
        E-poçt: $email
        
        Dərs proqramınızı tamamlamaq üçün yalnız 1 dərs qalıb. Növbəti dərsinizi planlaşdırmaq üçün müəlliminizlə əlaqə saxlayın.
        
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

// Function to send final lesson notification email
function sendFinalLessonNotificationEmail($email, $username, $fullName, $teacherName, $subject, $totalLessons, $u_id) {
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
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Dərs Proqramı Tamamlandı - Son Dərs';
        
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
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: #ffffff;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 700;
                    color: #ffffff;
                }
                .header p {
                    margin: 8px 0 0;
                    font-size: 16px;
                    color: #ffffff;
                    opacity: 0.9;
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
                .lesson-info {
                    background-color: #d4edda;
                    padding: 25px;
                    border-radius: 8px;
                    border-left: 5px solid #28a745;
                    margin: 25px 0;
                }
                .lesson-info p {
                    margin: 10px 0;
                    font-size: 16px;
                    color: #333;
                }
                .student-details {
                    background-color: #f8f9fa;
                    padding: 25px;
                    border-radius: 8px;
                    border: 1px solid #e8e8e8;
                    margin: 25px 0;
                }
                .student-details p {
                    margin: 10px 0;
                    font-size: 16px;
                }
                .success {
                    color: #155724;
                    font-weight: 600;
                    font-size: 18px;
                    margin: 20px 0;
                    padding: 15px;
                    background-color: #d4edda;
                    border-radius: 8px;
                    border-left: 4px solid #28a745;
                    text-align: center;
                }
                .button {
                    display: block;
                    width: 90%;
                    padding: 12px;
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    font-size: 16px;
                    text-align: center;
                    transition: background 0.3s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    margin: 20px auto;
                }
                .button:hover {
                    background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
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
                    <h1>🎉 Təbriklər!</h1>
                    <p>Magistratura AZ</p>
                </div>
                
                <div class='content'>
                    <h2>Hörmətli $fullName,</h2>
                    
                    <p>Bu gün keçirdiyiniz dərslə bağlı xoş xəbərimiz var:</p>
                    
                    <div class='success'>
                        🎓 Dərs proqramınızı uğurla tamamladınız!
                    </div>
                    
                    <div class='lesson-info'>
                        <h3>📚 Tamamlanan Dərs Proqramı:</h3>
                        <p><strong>👨‍🏫 Müəllim:</strong> $teacherName</p>
                        <p><strong>📖 Fənn:</strong> $subject</p>
                        <p><strong>✅ Tamamlanan Dərs:</strong> $totalLessons / $totalLessons</p>
                        <p><strong>📅 Tamamlanma Tarixi:</strong> " . date('d.m.Y H:i') . "</p>
                    </div>
                    
                    <div class='student-details'>
                        <h3>👤 Tələbə Məlumatları:</h3>
                        <p><strong>Ad və Soyad:</strong> $fullName</p>
                    </div>
                    
                    <p>Bu müəllimlə dərs proqramınız uğurla tamamlandı. Əgər əlavə dərslərə ehtiyacınız varsa, müəllim və ya idarə ilə əlaqə saxlayın.</p>                    
                    <p>Təhsilinizdə uğurlar diləyirik!</p>
                </div>
                
                <div class='footer'>
                    <p>📧 Magistratura AZ - Bütün hüquqlar qorunur.</p>
                    <p>Bu avtomatik göndərilən e-poçt mesajıdır.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "
        Hörmətli $fullName,
        
        Bu gün keçirdiyiniz dərslə bağlı xoş xəbərimiz var:
        
        🎉 TƏBRIKLƏR! 🎉
        
        Dərs proqramınızı uğurla tamamladınız!
        
        Tamamlanan Dərs Proqramı:
        Müəllim: $teacherName
        Fənn: $subject
        Tamamlanan Dərs: $totalLessons / $totalLessons
        Tamamlanma Tarixi: " . date('d.m.Y H:i') . "
        
        Tələbə Məlumatları:
        Ad və Soyad: $fullName
        İstifadəçi adı: $username
        Tələbə ID: $u_id
        E-poçt: $email
        
        Bu müəllimlə dərs proqramınız uğurla tamamlandı. Əgər əlavə dərslərə ehtiyacınız varsa, müəllim və ya idarə ilə əlaqə saxlayın.
        
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

$scan_result = null;
$session_valid = true;

try {
    $student_u_id = checkSession($conn);
} catch (Exception $e) {
    $session_valid = false; 
    echo '
    <p style="position:relative; display: flex; justify-content: center; align-items: center; margin: 65% 15%; font-family: Arial; font-weight:bold;font-size:18px; text-align: center;">Sessiya vaxtı bitdi. Zəhmət olmasa yenidən daxil olun.</p>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    if (!$session_valid) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Sessiya vaxtı bitdi. Zəhmət olmasa yenidən daxil olun.'
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data']) && $session_valid) {
    $qr_data = trim($_POST['qr_data']);
    
    try {
        $student_u_id = checkSession($conn);
        $student_username = mysqli_real_escape_string($conn, getStudentDisplayUsername($conn, $student_u_id));
        
        $u_id = null;
        $teacher_username = null;
        if (preg_match('/^([^:]+):(.+)$/', $qr_data, $matches)) {
            $u_id = trim($matches[1]);
            $teacher_username = trim($matches[2]);
        } else {
            throw new Exception('QR kod formatı düzgün deyil. Müəllim QR kodu skan edin.');
        }

        $sql = "SELECT id, u_id, username, tehsil_ve_ixtisas, fenn, qr_code FROM muellimler_new WHERE u_id = ? AND username = ? AND active_status = 'active'";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Verilənlər bazası xətası: ' . $conn->error);
        }
        $stmt->bind_param("ss", $u_id, $teacher_username);
        
        if (!$stmt->execute()) {
            throw new Exception('Sorğu xətası: ' . $stmt->error);
        }
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Müəllim tapılmadı. U_ID: ' . htmlspecialchars($u_id) . ', İstifadəçi: ' . htmlspecialchars($teacher_username));
        }
        
        $teacher_info = $result->fetch_assoc();
        $stmt->close();
        
        if (isset($_POST['validate_only'])) {
            // Get current lesson count from qr_scans table
            $lesson_data = getCurrentLessonCount($conn, $teacher_info['username'], $student_username);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'teacher_info' => [
                    'u_id' => $teacher_info['u_id'],
                    'username' => $teacher_info['username'],
                    'fenn' => $teacher_info['tehsil_ve_ixtisas'] ?? 'Fənn yoxdur',
                    'qr_code' => $teacher_info['qr_code'] ? '../Uploads/qrcodes/' . $teacher_info['qr_code'] : '',
                    'current_lessons' => $lesson_data['current_count']
                ]
            ]);
            exit;
        }
        
        $today = date('Y-m-d');
        $sql = "SELECT id FROM qr_scans WHERE teacher_username = ? AND student_username = ? AND scan_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $teacher_info['username'], $student_username, $today);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception('Bu müəllimlə bu gün artıq dərs qeydiyyatı var.');
        }
        $stmt->close();
        
        // Get current lesson count from qr_scans table
        $lesson_data = getCurrentLessonCount($conn, $teacher_info['username'], $student_username);
        $current_lessons = $lesson_data['current_count'];
        
        // Get total required lessons from qeydiyyatar table using student u_id
        $total_required_lessons = getTotalRequiredLessons($conn, $student_u_id);
        
        // Insert new scan with u_id
        $scan_time = date('Y-m-d H:i:s');
        $new_lesson_count = $current_lessons + 1; // This will be the new total after insertion
        $company_id = (int) ($_SESSION['company_id'] ?? 0);
        $sql = "INSERT INTO qr_scans (u_id, company_id, teacher_id, teacher_username, teacher_fenn, student_username, student_u_id, scan_date, scan_time, lesson_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $teacher_fenn = $teacher_info['tehsil_ve_ixtisas'] ?? 'Fənn yoxdur';
        $stmt->bind_param("siissssssi", $student_u_id, $company_id, $teacher_info['id'], $teacher_info['username'], $teacher_fenn, $student_username, $student_u_id, $today, $scan_time, $new_lesson_count);
        
        if ($stmt->execute()) {
            $remaining_lessons = $total_required_lessons - $new_lesson_count;
            
            // Get student email and full name for notifications
            $student_sql = "SELECT poct as email, username as full_name FROM telebeler WHERE username = ?";
            $student_stmt = $conn->prepare($student_sql);
            $student_stmt->bind_param("s", $student_username);
            $student_stmt->execute();
            $student_result = $student_stmt->get_result();

            if ($student_result->num_rows > 0) {
                $student_data = $student_result->fetch_assoc();
                $teacher_display_name = $teacher_info['username'];
                
                // Send email notification when only 1 lesson remains (before the last lesson)
                if ($remaining_lessons == 1) {
                    $email_sent = sendLessonReminderEmail(
                        $student_data['email'],
                        $student_username,
                        $student_data['full_name'],
                        $teacher_display_name,
                        $teacher_fenn,
                        $new_lesson_count,
                        $total_required_lessons,
                        $student_u_id
                    );
                    
                    // Log email sending attempt
                    if ($email_sent) {
                        error_log("Reminder email sent successfully to: " . $student_data['email'] . " for lesson " . $new_lesson_count . "/" . $total_required_lessons);
                    } else {
                        error_log("Failed to send reminder email to: " . $student_data['email']);
                    }
                }
                // Send final completion email when all lessons are completed
                elseif ($remaining_lessons <= 0) {
                    $email_sent = sendFinalLessonNotificationEmail(
                        $student_data['email'],
                        $student_username,
                        $student_data['full_name'],
                        $teacher_display_name,
                        $teacher_fenn,
                        $total_required_lessons,
                        $student_u_id
                    );
                    
                    // Log email sending attempt
                    if ($email_sent) {
                        error_log("Completion email sent successfully to: " . $student_data['email'] . " for completing " . $total_required_lessons . " lessons");
                    } else {
                        error_log("Failed to send completion email to: " . $student_data['email']);
                    }
                }
            }
            $student_stmt->close();
            
            $scan_result = [
                'success' => true,
                'message' => 'Dərs uğurla qeydiyyat edildi!',
                'u_id' => $teacher_info['u_id'],
                'username' => $teacher_info['username'],
                'tehsil_ve_ixtisas' => $teacher_info['tehsil_ve_ixtisas'] ?? 'Fənn yoxdur',
                'qr_code' => $teacher_info['qr_code'] ? '../Uploads/qrcodes/' . $teacher_info['qr_code'] : '',
                'total_scans' => $new_lesson_count,
                'scan_time' => date('d.m.Y H:i', strtotime($scan_time)),
                'remaining_lessons' => max(0, $remaining_lessons),
                'total_required_lessons' => $total_required_lessons
            ];
        } else {
            throw new Exception('Qeydiyyat xətası: ' . $stmt->error);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        if (isset($_POST['validate_only'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
        $scan_result = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

$scan_history = [];
if ($session_valid) {
    try {
        $student_u_id = checkSession($conn);
        $student_username = mysqli_real_escape_string($conn, getStudentDisplayUsername($conn, $student_u_id));
        
        $history_sql = "SELECT qs.*,
                        (SELECT COUNT(*) FROM qr_scans qs2 WHERE qs2.teacher_username = qs.teacher_username AND qs2.student_username = qs.student_username) as completed_lessons
                        FROM qr_scans qs
                        JOIN muellimler_new m ON qs.teacher_id = m.id
                        WHERE qs.student_username = ?
                        ORDER BY qs.scan_time DESC";
        $history_stmt = $conn->prepare($history_sql);
        if (!$history_stmt) {
            throw new Exception('Verilənlər bazası xətası: ' . $conn->error);
        }
        $history_stmt->bind_param("s", $student_username);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        
        while ($row = $history_result->fetch_assoc()) {
            $scan_history[] = $row;
        }
        $history_stmt->close();
    } catch (Exception $e) {
        $scan_result = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Skan - TIS</title>
    <script>
        window.APP_CSRF_TOKEN = <?php echo json_encode(app_csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="../assets/libs/zxing/index.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/libs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet"> 
      <style>
        .main-content { 
            margin-left: 250px; 
            margin-top: 6px; 
            padding: 0px;
            min-height: calc(100vh - 86px);
        }
        @media (max-width: 1024px) { 
            .main-content { 
                margin-left: 0; 
                margin-top: 0px; 
            } 
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            font-family: Arial;
            padding: 30px; 
            border-radius: 15px; 
        }
        h1 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        .scanner { 
            text-align: center; 
            margin: 20px 0; 
            padding: 25px; 
            border: 3px dashed #007bff; 
            border-radius: 15px; 
            background: #f8f9ff;
        }
        .btn { 
            position: relative;
            top: 6px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white; 
            border: none; 
            padding: 15px 30px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            margin: 8px; 
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,123,255,0.3);
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
            color: white;
        }
        .btn-danger { 
            background: linear-gradient(135deg, #dc3545, #c82333);
            box-shadow: 0 3px 10px rgba(220,53,69,0.3);
        }
        .btn-danger:hover { 
            box-shadow: 0 5px 15px rgba(220,53,69,0.4);
        }
        #video { 
            width: 100%; 
            max-width: 350px; 
            border-radius: 10px; 
            margin: 15px 0;
            box-shadow: 0 3px 15px rgba(0,0,0,0.2);
        }
        .result { 
            margin: 25px 0; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center;
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success { 
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724; 
            border: 2px solid #28a745;
        }
        .error { 
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24; 
            border: 2px solid #dc3545;
        }
        .hidden { 
            display: none; 
        }
        .info { 
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            text-align: left;
            border-left: 5px solid #2196f3;
        }
        .info h4 { 
            margin: 0 0 15px 0; 
            color: #1976d2;
            font-size: 1.2rem;
        }
        .info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info li {
            margin: 8px 0;
            color: #333;
        }
        .details { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            margin-top: 15px; 
            text-align: left;
            border: 1px solid #dee2e6;
        }
        .details p { 
            margin: 10px 0; 
            font-size: 15px;
            display: flex;
            align-items: center;
        }
        .details p i {
            margin-right: 10px;
            width: 20px;
            color: #007bff;
        }
        .badge { 
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white; 
            padding: 12px 20px; 
            border-radius: 25px; 
            font-size: 18px; 
            font-weight: bold;
            display: inline-block; 
            margin: 15px 0;
            box-shadow: 0 3px 10px rgba(40,167,69,0.3);
        }
        #status {
            margin: 15px 0;
            font-weight: bold;
            color: #007bff;
            font-size: 16px;
        }
        .scan-history {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .scan-history h3 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        .history-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }a
        .history-item .teacher-name {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }
        .history-item .scan-time {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .scanner-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        
       .details .qr-image{
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 40%;
            transform:scale(0.95);
        }

        .button-group {
            display: flex;
            gap: 0px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: -20px;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="button-group">
            <a href="Home.php" class="btn btn-back">
                ← Geri
            </a>
        </div>
        
        <?php if ($session_valid): ?>
        <div class="container">
            <h1><i class="fas fa-qrcode"></i> QR Kod Skan</h1>
            
            <div class="info">
                <h4><i class="fas fa-info-circle"></i> Necə istifadə etmək:</h4>
                <ul>
                    <li><strong>Kameranı Başlat</strong> düyməsini basın və brauzer kamera icazəsi istəyəndə <strong>İcazə ver</strong> seçin</li>
                    <li>Müəllimin QR kodunu kameraya tutun</li>
                    <li>QR kod tanınacaq və müəllim məlumatları ilə təsdiq pəncərəsi açılacaq</li>
                    <li>Təsdiq etdikdən sonra dərs qeydiyyatı tamamlanacaq</li>
                    <li><strong>Qeyd:</strong> Hər müəllimlə gündə 1 dəfə</li>
                </ul>
            </div>
            
            <div class="scanner">
                <div class="scanner-icon">
                    <i class="fas fa-camera"></i>
                </div>
                <button onclick="startCamera()" id="startBtn" class="btn btn-primary">
                    <i style="margin-right:6px;" class="fas fa-play"></i> Başlat
                </button>
                <button onclick="stopCamera()" id="stopBtn" class="btn btn-danger hidden">
                    <i class="fas fa-stop"></i> Dayandır
                </button>
                <br>
                <video id="video" class="hidden" autoplay playsinline muted></video>
                <div id="status" class="hidden">
                    <i class="fas fa-search"></i> QR kod axtarılır...
                </div>
            </div>
            
            <?php if ($scan_result): ?>
            <div class="result <?= $scan_result['success'] ? 'success' : 'error' ?>">
                <h3>
                    <?php if ($scan_result['success']): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($scan_result['message']) ?>
                </h3>
                <?php if ($scan_result['success']): ?>
                    <div class="badge">
                        <i class="fas fa-graduation-cap"></i>
                        Gəldiyi Dərs: <?= $scan_result['total_scans'] ?>
                    </div>
                    <?php if (isset($scan_result['remaining_lessons']) && $scan_result['remaining_lessons'] <= 1): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Diqqət:</strong> 
                            <?php if ($scan_result['remaining_lessons'] == 1): ?>
                                Bu müəllimlə 1 dərsiniz qalıb! E-poçt bildirişi göndərildi.
                            <?php else: ?>
                                Bu müəllimlə son dərsinizi keçirdiniz!
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="details">
                        <p hidden><i class="fas fa-id-card"></i><strong style="margin-right:4px;">U_ID: </strong> <?= htmlspecialchars($scan_result['u_id']) ?></p>
                        <p><i class="fas fa-user-tie"></i><strong style="margin-right:4px;">Müəllim: </strong> <?= htmlspecialchars($scan_result['username']) ?></p>
                        <p><i class="fas fa-book"></i><strong style="margin-right:4px;">Fənn: </strong> <?= htmlspecialchars($scan_result['tehsil_ve_ixtisas']) ?></p>
                        <p><i class="fas fa-chart-line"></i><strong style="margin-right:4px;">Ümumi Gəldiyi Dərs: </strong> <?= $scan_result['total_scans'] ?></p>
                        <p><i class="fas fa-clock"></i><strong style="margin-right:4px;">Tarix: </strong> <?= $scan_result['scan_time'] ?></p>
                        <?php if ($scan_result['qr_code']): ?>
                            <img hidden src="<?= htmlspecialchars($scan_result['qr_code']) ?>" alt="Müəllim QR Kodu" class="qr-image">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($scan_history)): ?>
            <div class="scan-history">
                <h3><i class="fas fa-history"></i> Son Skanlar</h3>
                <?php foreach ($scan_history as $history): ?>
                    <div class="history-item">
                        <div class="teacher-name">
                            <i class="fas fa-user-tie"></i>
                            <?= htmlspecialchars(($history['teacher_username'])) ?>
                            <?= htmlspecialchars($history['teacher_fenn']) ?>
                            <span hidden class="lesson-progress">(Gəldiyi: <?= $history['completed_lessons'] ?>)</span>
                        </div>
                        <div class="scan-time">
                            <i class="fas fa-calendar"></i>
                            <?= date('d.m.Y H:i', strtotime($history['scan_time'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="scanForm" class="hidden">
                <input type="hidden" name="qr_data" id="qrDataInput">
                <?= app_csrf_field() ?>
            </form>

            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-check-circle"></i> Müəllim Tapıldı!</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p hidden><i style="margin-right:3px;" class="fas fa-id-card"></i><strong style="margin-right:2px;">U_ID:</strong> <span id="modalUId"></span></p>
                            <p><i style="margin-right:3px;" class="fas fa-user-tie"></i><strong style="margin-right:2px;">İstifadəçi:</strong> <span id="modalUsername"></span></p>
                            <p><i style="margin-right:3px;" class="fas fa-book"></i><strong style="margin-right:2px;">Fənn:</strong> <span id="modalFenn"></span></p>
                            <p><i style="margin-right:3px;" class="fas fa-chart-line"></i><strong style="margin-right:2px;">Hazırda Gəldiyi Dərs:</strong> <span id="modalCurrentLessons"></span></p>
                            <p>Dərsi qeydiyyat etmək istəyirsiniz?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Ləğv et</button>
                            <button type="button" class="btn btn-primary" id="confirmScanBtn"><i class="fas fa-check"></i> Təsdiq et</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
<br><br>

    <script>
        let codeReader = null;
        let isScanning = false;
        let currentQrData = '';
        let cameraErrorShown = false;

        function getCameraErrorMessage(error) {
            const name = (error && error.name) ? error.name : '';
            const message = (error && error.message) ? error.message : '';
            const combined = (name + ' ' + message).toLowerCase();

            if (combined.includes('permission') || combined.includes('notallowed') || combined.includes('denied')) {
                return 'Kamera icazəsi verilməyib. Ünvan çubuğundakı kamera ikonuna basıb "İcazə ver" seçin və səhifəni yeniləyin.';
            }
            if (combined.includes('notfound') || combined.includes('devicesnotfound')) {
                return 'Kamera tapılmadı. Cihazınızda aktiv kamera olduğundan əmin olun.';
            }
            if (combined.includes('notreadable') || combined.includes('in use') || combined.includes('inuse')) {
                return 'Kamera başqa proqram tərəfindən istifadə olunur. Digər tətbiqləri bağlayıb yenidən cəhd edin.';
            }
            if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
                return 'Kamera yalnız HTTPS və ya localhost üzərində işləyir.';
            }

            return message || 'Kamera açıla bilmədi.';
        }

        function showCameraError(error) {
            if (cameraErrorShown) {
                return;
            }
            cameraErrorShown = true;
            alert('❌ Kamera xətası: ' + getCameraErrorMessage(error));
            setTimeout(function () {
                cameraErrorShown = false;
            }, 2500);
        }

        async function ensureCameraAccess() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Brauzeriniz kamera dəstəkləmir.');
            }

            const constraints = {
                video: {
                    facingMode: { ideal: 'environment' }
                },
                audio: false
            };

            let stream;
            try {
                stream = await navigator.mediaDevices.getUserMedia(constraints);
            } catch (firstError) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                } catch (secondError) {
                    throw firstError;
                }
            }

            if (stream) {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });
            }
        }

        function buildQrRequestBody(qrData, validateOnly) {
            const params = new URLSearchParams();
            params.append('qr_data', qrData);
            if (validateOnly) {
                params.append('validate_only', '1');
            }
            if (window.APP_CSRF_TOKEN) {
                params.append('csrf_token', window.APP_CSRF_TOKEN);
            }
            return params.toString();
        }

        function parseJsonResponse(response) {
            return response.text().then(function (text) {
                if (!text) {
                    throw new Error('Server boş cavab qaytardı.');
                }

                try {
                    return JSON.parse(text);
                } catch (error) {
                    const plainText = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                    throw new Error(plainText || 'Server cavabı oxunmadı.');
                }
            });
        }

        function submitQrValidation(qrData) {
            return fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: buildQrRequestBody(qrData, true)
            }).then(parseJsonResponse);
        }

        async function startCamera() {
            if (isScanning) return;

            if (typeof ZXing === 'undefined' || !ZXing.BrowserMultiFormatReader) {
                alert('❌ QR skan kitabxanası yüklənmədi. Səhifəni yeniləyin.');
                return;
            }

            const videoElement = document.getElementById('video');
            const statusElement = document.getElementById('status');
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');

            try {
                statusElement.classList.remove('hidden');
                statusElement.innerHTML = '<i class="fas fa-camera"></i> Kamera icazəsi yoxlanılır...';

                await ensureCameraAccess();

                isScanning = true;
                codeReader = new ZXing.BrowserMultiFormatReader();

                startBtn.classList.add('hidden');
                stopBtn.classList.remove('hidden');
                videoElement.classList.remove('hidden');
                statusElement.innerHTML = '<i class="fas fa-search"></i> QR kod axtarılır...';

                await codeReader.decodeFromVideoDevice(null, 'video', function (result, err) {
                    if (result) {
                        console.log('QR detected:', result.text);
                        currentQrData = result.text.trim();
                        stopCamera();

                        statusElement.classList.remove('hidden');
                        statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> QR kod yoxlanılır...';

                        submitQrValidation(currentQrData)
                        .then(function (data) {
                            console.log('Response:', data);
                            statusElement.classList.add('hidden');

                            if (data.success) {
                                const info = data.teacher_info;
                                document.getElementById('modalUId').textContent = info.u_id;
                                document.getElementById('modalUsername').textContent = info.username;
                                document.getElementById('modalFenn').textContent = info.fenn;
                                document.getElementById('modalCurrentLessons').textContent = info.current_lessons;
                                new bootstrap.Modal(document.getElementById('confirmModal')).show();
                            } else {
                                alert('❌ Xəta: ' + (data.message || 'Naməlum xəta') + '\n\n📱 QR Kod: ' + currentQrData.substring(0, 50) + '...');
                            }
                        })
                        .catch(function (error) {
                            console.error('Error:', error);
                            statusElement.classList.add('hidden');
                            alert('❌ Şəbəkə xətası: ' + error.message + '\n\n📱 QR Kod: ' + currentQrData.substring(0, 50) + '...');
                        });
                        return;
                    }

                    if (!err) {
                        return;
                    }

                    if (err instanceof ZXing.NotFoundException) {
                        return;
                    }

                    const errText = ((err.name || '') + ' ' + (err.message || '')).toLowerCase();
                    if (errText.includes('permission') || errText.includes('notallowed') || errText.includes('denied')) {
                        showCameraError(err);
                        stopCamera();
                    }
                });
            } catch (error) {
                console.error('Camera error:', error);
                showCameraError(error);
                stopCamera();
            }
        }

        function stopCamera() {
            if (codeReader) {
                codeReader.reset();
                codeReader = null;
            }
            
            isScanning = false;
            
            const videoElement = document.getElementById('video');
            const statusElement = document.getElementById('status');
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            
            if (videoElement) videoElement.classList.add('hidden');
            if (statusElement) statusElement.classList.add('hidden');
            if (startBtn) startBtn.classList.remove('hidden');
            if (stopBtn) stopBtn.classList.add('hidden');
        }

        const confirmScanBtn = document.getElementById('confirmScanBtn');
        if (confirmScanBtn) {
            confirmScanBtn.addEventListener('click', () => {
                const statusElement = document.getElementById('status');
                statusElement.classList.remove('hidden');
                statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Qeydiyyat edilir...';
                
                // Submit the form
                document.getElementById('qrDataInput').value = currentQrData;
                document.getElementById('scanForm').submit();
            });
        }

        window.addEventListener('beforeunload', stopCamera);
    </script>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
</body>
</html>
