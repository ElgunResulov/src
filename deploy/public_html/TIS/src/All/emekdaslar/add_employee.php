<?php
require_once __DIR__ . '/../auth.php';
app_start_secure_session();

ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check authentication and CSRF
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../emekdaslar.php?error=' . urlencode('Unauthorized: Please log in'));
    exit();
}

if (!app_validate_csrf_token(app_request_csrf_token())) {
    header('Location: ../emekdaslar.php?error=' . urlencode('Invalid CSRF token'));
    exit();
}

// Validate input
$required_fields = ['firstName', 'lastName', 'department', 'position', 'email', 'phone', 'startDate', 'status'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        header('Location: ../emekdaslar.php?error=' . urlencode('Missing required field: ' . $field));
        exit();
    }
}

// Include database connection
$db_path = __DIR__ . '/../db.php';
if (!file_exists($db_path)) {
    header('Location: ../emekdaslar.php?error=' . urlencode('Server error: Database configuration file missing'));
    exit();
}
require $db_path;

try {
    // Sanitize input
    $ad_soyad = htmlspecialchars(trim($_POST['firstName'] . ' ' . $_POST['lastName']), ENT_QUOTES, 'UTF-8');
    $sobe = htmlspecialchars(trim($_POST['department']), ENT_QUOTES, 'UTF-8');
    $vezife = htmlspecialchars(trim($_POST['position']), ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefon = htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8');
    $ise_baslama_tarixi = htmlspecialchars(trim($_POST['startDate']), ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars(trim($_POST['status']), ENT_QUOTES, 'UTF-8');
    $unvan = isset($_POST['address']) ? htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8') : '';
    $tehsil = isset($_POST['education']) ? htmlspecialchars(trim($_POST['education']), ENT_QUOTES, 'UTF-8') : '';
    $is_tecrubesi = isset($_POST['experience']) ? htmlspecialchars(trim($_POST['experience']), ENT_QUOTES, 'UTF-8') : '';

    // Handle image upload
    $sekil = '';
    if (isset($_FILES['employeeImage']) && $_FILES['employeeImage']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['employeeImage'];
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            header('Location: ../emekdaslar.php?error=' . urlencode('Invalid file type. Only JPG/PNG allowed.'));
            exit();
        }

        if ($file['size'] > $max_size) {
            header('Location: ../emekdaslar.php?error=' . urlencode('File size exceeds 2MB limit.'));
            exit();
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('emp_') . '.' . $ext;
        $upload_dir = __DIR__ . '/uploads/';
        $upload_path = $upload_dir . $filename;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to upload image');
        }

        $sekil = $filename;
    }

    // Insert into database
    $query = "INSERT INTO emekdaslar (ad_soyad, sobe, vezife, email, telefon, ise_baslama_tarixi, status, unvan, sekil, tehsil, is_tecrubesi) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }

    $stmt->bind_param('sssssssssss', $ad_soyad, $sobe, $vezife, $email, $telefon, $ise_baslama_tarixi, $status, $unvan, $sekil, $tehsil, $is_tecrubesi);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

    // Redirect to the previous page with success message
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../emekdaslar.php';
    header('Location: ' . $referer . '?success=' . urlencode('Əməkdaş uğurla əlavə edildi!'));
    exit();

} catch (Exception $e) {
    if (isset($conn) && $conn) {
        $conn->close();
    }
    header('Location: ../emekdaslar.php?error=' . urlencode('Server error: Unable to add employee. ' . $e->getMessage()));
    exit();
}
?>