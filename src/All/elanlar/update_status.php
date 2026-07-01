<?php
// Start session to check user authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Zəhmət olmasa daxil olun.']);
    exit();
}

// Include database connection
include('../db.php');

// Get the announcement ID and new status from the AJAX request
$announcement_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate inputs
if ($announcement_id <= 0 || !in_array($new_status, ['active', 'inactive', 'viewed'])) {
    echo json_encode(['success' => false, 'message' => 'Yanlış məlumatlar.']);
    exit();
}

// Update the status in the database
$query = "UPDATE elanlar SET status = ? WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı xətası: Sorgu hazırlanarkən xəta baş verdi.']);
    exit();
}

$stmt->bind_param("si", $new_status, $announcement_id);
$success = $stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Status uğurla yeniləndi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Status yenilənərkən xəta baş verdi.']);
}

$stmt->close();
$conn->close();
?>