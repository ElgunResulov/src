<?php
include('db.php');
app_require_auth($conn);
app_require_role(['super_admin', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Yanlış sorğu!']);
    exit;
}

$user_id = (int) $_POST['id'];
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Etibarsız istifadəçi ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Xəta baş verdi: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'İstifadəçi tapılmadı!']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

if ($user_id === (int) ($_SESSION['user_id'] ?? 0)) {
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'İstifadəçi silindi.', 'redirect' => 'Login.php']);
    $conn->close();
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'İstifadəçi uğurla silindi!']);
$conn->close();