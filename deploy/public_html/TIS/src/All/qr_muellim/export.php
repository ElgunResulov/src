<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../db.php';
require_once __DIR__ . '/../muellim/attendance_helpers.php';
app_require_auth($conn);

$role = $_SESSION['role'] ?? '';
$sessionUser = trim((string) ($_SESSION['username'] ?? ''));
$sessionUId = trim((string) ($_SESSION['u_id'] ?? ''));
$sessionFin = trim((string) ($_SESSION['fin_kod'] ?? ''));
$teacher = trim((string) ($_GET['teacher'] ?? ''));
$uId = trim((string) ($_GET['u_id'] ?? ''));
$type = trim((string) ($_GET['type'] ?? 'salary'));

if (in_array($role, ['super_admin', 'admin'], true)) {
    // admin: teacher və ya u_id gəlməlidir
} elseif ($role === 'teacher') {
    $teacher = $sessionUser;
    $uId = $sessionUId;
} else {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'İcazə yoxdur.';
    exit;
}

$row = att_resolve_teacher_row($conn, $teacher, $uId, $sessionFin);

if (!$row) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Müəllim tapılmadı.';
    exit;
}

$teacherUsername = (string) $row['username'];
$safeFile = preg_replace('/[^a-zA-Z0-9_-]+/u', '_', $teacherUsername) ?: 'teacher';
$filename = 'export_' . $safeFile . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

if ($type === 'today') {
    $today = att_build_today_list($conn, $teacherUsername, $row['telebeler'] ?? null);
    fputcsv($out, ['Tələbə', 'Qrup', 'Saat', 'Status', '8-lik', 'Ümumi skan']);
    foreach ($today['students'] as $s) {
        fputcsv($out, [
            $s['username'],
            $s['gun_qrupu'] ?? '',
            $s['saat'],
            att_status_label($s['status']),
            $s['cycle']['label'],
            $s['total_scans'],
        ]);
    }
} else {
    $start = trim((string) ($_GET['start'] ?? date('Y-m-01')));
    $end = trim((string) ($_GET['end'] ?? date('Y-m-t')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
        $start = date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        $end = date('Y-m-t');
    }
    $report = att_build_salary_report($conn, $teacherUsername, $row['telebeler'] ?? null, $start, $end);
    fputcsv($out, ['Tələbə', 'Qrup', 'Natamam', 'Dövrdə vahid', 'Ömür boyu vahid', 'Ümumi skan']);
    foreach ($report['students'] as $s) {
        fputcsv($out, [
            $s['username'],
            $s['gun_qrupu'] ?? '',
            $s['cycle']['label'],
            $s['units_period'],
            $s['units_lifetime'],
            $s['total_scans'],
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Cəmi dövrdə', $report['total_units_period']]);
    fputcsv($out, ['Cəmi ömür boyu', $report['total_units_lifetime']]);
}

fclose($out);
$conn->close();
exit;
