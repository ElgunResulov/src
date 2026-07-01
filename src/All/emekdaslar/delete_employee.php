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

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['error' => 'Invalid or missing employee ID']);
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
    $id = (int)$_POST['id'];

    // Fetch existing image to delete
    $query = "SELECT sekil FROM emekdaslar WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['sekil'])) {
            $image_path = __DIR__ . '/uploads/' . $row['sekil'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    $stmt->close();

    // Delete employee
    $query = "DELETE FROM emekdaslar WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    echo json_encode(['success' => true]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: Unable to delete employee', 'details' => $e->getMessage()]);
    if (isset($conn) && $conn) {
        $conn->close();
    }
    exit();
}
?>