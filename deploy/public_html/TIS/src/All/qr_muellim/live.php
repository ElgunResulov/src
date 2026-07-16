<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../db.php';
require_once __DIR__ . '/../muellim/attendance_helpers.php';
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['role'] ?? '';
$sessionUser = trim((string) ($_SESSION['username'] ?? ''));
$sessionUId = trim((string) ($_SESSION['u_id'] ?? ''));
$sessionFin = trim((string) ($_SESSION['fin_kod'] ?? ''));
$teacher = trim((string) ($_GET['teacher'] ?? ''));
$uId = trim((string) ($_GET['u_id'] ?? ''));

if (in_array($role, ['super_admin', 'admin'], true)) {
    // ok
} elseif ($role === 'teacher') {
    $teacher = $sessionUser;
    $uId = $sessionUId;
} else {
    echo json_encode(['status' => 'error', 'message' => 'İcazə yoxdur.']);
    exit;
}

$row = att_resolve_teacher_row($conn, $teacher, $uId, $sessionFin);

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Müəllim tapılmadı.']);
    exit;
}

$teacherUsername = (string) $row['username'];
$today = att_build_today_list($conn, $teacherUsername, $row['telebeler'] ?? null);
$students = [];
foreach ($today['students'] as $s) {
    $students[] = [
        'username' => $s['username'],
        'status' => $s['status'],
        'status_label' => att_status_label($s['status']),
        'cycle' => $s['cycle']['label'],
    ];
}

echo json_encode([
    'status' => 'success',
    'expected' => $today['expected'],
    'scanned' => $today['scanned'],
    'pending' => $today['pending'],
    'students' => $students,
], JSON_UNESCAPED_UNICODE);

$conn->close();
