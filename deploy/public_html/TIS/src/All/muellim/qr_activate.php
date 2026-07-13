<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php';
require_once __DIR__ . '/qr_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'İstifadəçi autentifikasiyası tapılmadı.']);
    exit;
}

$role = $_SESSION['role'] ?? '';
$currentUsername = trim((string) ($_SESSION['username'] ?? ''));
$action = trim((string) ($_POST['action'] ?? $_GET['action'] ?? 'single'));
$targetUsername = trim((string) ($_POST['username'] ?? $_GET['username'] ?? $currentUsername));
$forceRegenerate = filter_var($_POST['force'] ?? $_GET['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!in_array($role, ['teacher', 'admin', 'super_admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Bu əməliyyat üçün icazəniz yoxdur.']);
    exit;
}

try {
    if ($action === 'all') {
        if (!in_array($role, ['admin', 'super_admin'], true)) {
            throw new Exception('Bütün müəllimlər üçün QR yalnız admin tərəfindən aktivləşdirilə bilər.');
        }

        $summary = qr_activate_all_teachers($conn);
        echo json_encode([
            'success' => true,
            'message' => 'Bütün müəllimlər üçün QR kodlar yoxlanıldı.',
            'summary' => $summary,
        ], JSON_UNESCAPED_UNICODE);
        $conn->close();
        exit;
    }

    if ($targetUsername === '') {
        throw new Exception('Müəllim istifadəçi adı tapılmadı.');
    }

    if ($role === 'teacher' && $targetUsername !== $currentUsername) {
        throw new Exception('Yalnız öz QR kodunuzu aktivləşdirə bilərsiniz.');
    }

    $sql = 'SELECT id, u_id, username, qr_code, active_status FROM muellimler_new WHERE username = ? LIMIT 1';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $targetUsername);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $teacher = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$teacher) {
        throw new Exception('Müəllim məlumatları tapılmadı.');
    }

    $teacher = qr_activate_teacher($conn, $teacher, $forceRegenerate);
    $meta = qr_teacher_public_meta($teacher);

    echo json_encode([
        'success' => true,
        'message' => 'QR kod uğurla aktivləşdirildi.',
        'qr_code' => $meta['qr_code'],
        'qr_url' => $meta['qr_url'],
        'content' => $meta['qr_content'],
        'username' => $teacher['username'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
