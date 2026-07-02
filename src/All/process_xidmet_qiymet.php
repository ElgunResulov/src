<?php
include('db.php');
require_once __DIR__ . '/qeydiyyatar/services_helpers.php';

app_require_auth($conn);
app_require_role(['super_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Xidmet_qiymetleri.php');
    exit();
}

$serviceKey = trim((string) ($_POST['service_key'] ?? ''));
$ayliq = (float) str_replace(',', '.', (string) ($_POST['qiymet_ayliq'] ?? '0'));
$paket = (float) str_replace(',', '.', (string) ($_POST['qiymet_paket'] ?? '0'));
$qeyd = trim((string) ($_POST['qeyd'] ?? ''));
$aktiv = isset($_POST['aktiv']) && $_POST['aktiv'] === '1';

$result = xidmet_save_price($conn, $serviceKey, $ayliq, $paket, $qeyd, $aktiv);

if ($result['ok']) {
    $_SESSION['xidmet_flash_success'] = $result['message'];
} else {
    $_SESSION['xidmet_flash_error'] = $result['message'];
}

$redirect = 'Xidmet_qiymetleri.php';
if ($serviceKey !== '') {
    $redirect .= '?open=' . rawurlencode($serviceKey);
}

header('Location: ' . $redirect);
exit();
