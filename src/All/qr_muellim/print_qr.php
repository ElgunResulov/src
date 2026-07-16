<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../db.php';
require_once __DIR__ . '/../muellim/qr_helpers.php';
require_once __DIR__ . '/../muellim/attendance_helpers.php';
app_require_auth($conn);

$role = $_SESSION['role'] ?? '';
$sessionUser = trim((string) ($_SESSION['username'] ?? ''));
$sessionUId = trim((string) ($_SESSION['u_id'] ?? ''));
$sessionFin = trim((string) ($_SESSION['fin_kod'] ?? ''));
$teacher = trim((string) ($_GET['teacher'] ?? ''));
$uIdParam = trim((string) ($_GET['u_id'] ?? ''));

if (!in_array($role, ['super_admin', 'admin', 'teacher'], true)) {
    http_response_code(403);
    echo 'İcazə yoxdur.';
    exit;
}

if ($role === 'teacher') {
    $uIdParam = $sessionUId;
    $teacher = $sessionUser;
}

$current = att_resolve_teacher_row($conn, $teacher, $uIdParam, $sessionFin);

if (!$current) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo 'Müəllim tapılmadı.';
    exit;
}

try {
    $current = qr_activate_teacher($conn, $current);
} catch (Exception $e) {
    // keep existing qr if any
}

$qrUrl = '';
if (!empty($current['qr_code']) && qr_teacher_file_exists($current['qr_code'])) {
    $qrUrl = muellim_qr_web_url($current['qr_code'], 'sub');
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR çap — <?php echo htmlspecialchars($current['username']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 40px; color: #111; }
        h1 { font-size: 1.6rem; margin-bottom: 8px; }
        p { color: #555; margin: 4px 0 20px; }
        img { width: 280px; height: 280px; border: 1px solid #ddd; padding: 12px; }
        .hint { margin-top: 24px; font-size: 0.9rem; color: #666; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
        button {
            margin-top: 20px;
            padding: 10px 18px;
            font-size: 1rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($current['username']); ?></h1>
    <p><?php echo htmlspecialchars($current['tehsil_ve_ixtisas'] ?: 'Müəllim QR'); ?></p>
    <?php if ($qrUrl !== ''): ?>
        <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="QR kod">
    <?php else: ?>
        <p>QR kod tapılmadı.</p>
    <?php endif; ?>
    <p class="hint">Tələbə bu QR-ı skan edərək dərsə qeydiyyat olur.</p>
    <button class="no-print" type="button" onclick="window.print()">Çap et</button>
</body>
</html>
