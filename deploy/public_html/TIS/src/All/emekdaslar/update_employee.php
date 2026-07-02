<?php
require_once __DIR__ . '/../auth.php';
app_start_secure_session();
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check authentication and CSRF
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized: Please log in']);
    exit();
}

if (!app_validate_csrf_token(app_request_csrf_token())) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

// Validate input
$required_fields = ['editEmployeeId', 'editFirstName', 'editLastName', 'editDepartment', 'editPosition', 'editEmail', 'editPhone', 'editStartDate', 'editStatus'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(['error' => 'Missing required field: ' . $field]);
        exit();
    }
}

if (!is_numeric($_POST['editEmployeeId'])) {
    echo json_encode(['error' => 'Invalid employee ID']);
    exit();
}

// Include database connection
$db_path = __DIR__ . '/../db.php';
if (!file_exists($db_path)) {
    echo json_encode(['error' => 'Server error: Database configuration file missing']);
    exit();
}
require $db_path;

try {
    // Sanitize input
    $id = (int)$_POST['editEmployeeId'];
    $ad_soyad = htmlspecialchars(trim($_POST['editFirstName'] . ' ' . $_POST['editLastName']), ENT_QUOTES, 'UTF-8');
    $sobe = htmlspecialchars(trim($_POST['editDepartment']), ENT_QUOTES, 'UTF-8');
    $vezife = htmlspecialchars(trim($_POST['editPosition']), ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['editEmail'], FILTER_SANITIZE_EMAIL);
    $telefon = htmlspecialchars(trim($_POST['editPhone']), ENT_QUOTES, 'UTF-8');
    $ise_baslama_tarixi = htmlspecialchars(trim($_POST['editStartDate']), ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars(trim($_POST['editStatus']), ENT_QUOTES, 'UTF-8');
    $unvan = isset($_POST['editAddress']) ? htmlspecialchars(trim($_POST['editAddress']), ENT_QUOTES, 'UTF-8') : '';
    $tehsil = isset($_POST['education']) ? htmlspecialchars(trim($_POST['education']), ENT_QUOTES, 'UTF-8') : '';
    $is_tecrubesi = isset($_POST['experience']) ? htmlspecialchars(trim($_POST['experience']), ENT_QUOTES, 'UTF-8') : '';

    // Update database
    $query = "UPDATE emekdaslar SET ad_soyad = ?, sobe = ?, vezife = ?, email = ?, telefon = ?, ise_baslama_tarixi = ?, status = ?, unvan = ?, tehsil = ?, is_tecrubesi = ? 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }

    $stmt->bind_param('ssssssssssi', $ad_soyad, $sobe, $vezife, $email, $telefon, $ise_baslama_tarixi, $status, $unvan, $tehsil, $is_tecrubesi, $id);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    echo json_encode(['success' => true]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: Unable to update employee', 'details' => $e->getMessage()]);
    if (isset($conn) && $conn) {
        $conn->close();
    }
    exit();
}
?>