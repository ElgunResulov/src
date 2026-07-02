<?php
include('db.php');
app_require_auth($conn);
app_require_role(['super_admin', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Yalnız POST sorğusu qəbul olunur.']);
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
$newRole = trim($_POST['role'] ?? '');
$allowedRoles = ['super_admin', 'admin', 'teacher', 'student', 'staff', 'parent', 'examiner', 'operator'];

if ($userId <= 0 || !in_array($newRole, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Etibarsız istifadəçi və ya rol.']);
    exit;
}

if (($_SESSION['role'] ?? '') === 'admin' && $newRole === 'super_admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Super admin rolunu təyin etmək icazəniz yoxdur.']);
    exit;
}

$stmt = $conn->prepare('SELECT id, role, company_id FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$targetUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'İstifadəçi tapılmadı.']);
    exit;
}

if (($_SESSION['role'] ?? '') === 'admin') {
    $companyId = (int) ($_SESSION['company_id'] ?? 0);
    if ((int) ($targetUser['company_id'] ?? 0) !== $companyId) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bu istifadəçini redaktə etmək icazəniz yoxdur.']);
        exit;
    }
}

$update = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
$update->bind_param('si', $newRole, $userId);

if (!$update->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Rol yenilənərkən xəta baş verdi.']);
    $update->close();
    $conn->close();
    exit;
}

$update->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Rol uğurla yeniləndi.',
    'role' => $newRole,
]);
