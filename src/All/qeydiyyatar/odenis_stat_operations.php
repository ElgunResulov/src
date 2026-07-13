<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
require_once __DIR__ . '/odenis_helpers.php';
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

odenis_ensure_columns($conn);

$type = trim((string) ($_GET['type'] ?? ''));
$today = date('Y-m-d');
$monthEnd = date('Y-m-t');

$baseSelect = "
    SELECT
        q.id,
        q.telebe_ad_soyad,
        q.ixtisas_adi,
        q.odenis_novu,
        q.tehsil_haqqi,
        q.endirim_meqdar,
        q.novbeti_odenis_tarixi,
        q.tedris_ili,
        COALESCE(NULLIF(q.form_email, ''), t.reg_email, t.poct) AS email,
        COALESCE(t.active_status, 'active') AS active_status
    FROM qeydiyyatar q
    LEFT JOIN telebeler t ON t.u_id = q.u_id
    WHERE q.tehsil_haqqi > 0
";

$columns = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'telebe_ad_soyad', 'label' => 'Tələbə'],
    ['key' => 'ixtisas_adi', 'label' => 'İxtisas'],
    ['key' => 'odenis_novu_label', 'label' => 'Ödəniş növü'],
    ['key' => 'tehsil_haqqi', 'label' => 'Təhsil haqqı'],
    ['key' => 'novbeti_odenis_tarixi', 'label' => 'Növbəti ödəniş'],
    ['key' => 'status_label', 'label' => 'Status'],
    ['key' => 'tedris_ili', 'label' => 'Tədris ili'],
];

switch ($type) {
    case 'total':
        $query = $baseSelect . ' ORDER BY q.id DESC';
        break;

    case 'gecikmis':
        $query = $baseSelect . " AND q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi < ? ORDER BY q.novbeti_odenis_tarixi ASC";
        break;

    case 'bu_ay':
        $query = $baseSelect . " AND q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi >= ? AND q.novbeti_odenis_tarixi <= ? ORDER BY q.novbeti_odenis_tarixi ASC";
        break;

    case 'paket':
        $query = $baseSelect . " AND q.odenis_novu = 'paket' ORDER BY q.id DESC";
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Yanlış statistik tipi.']);
        $conn->close();
        exit;
}

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
    $conn->close();
    exit;
}

if ($type === 'gecikmis') {
    mysqli_stmt_bind_param($stmt, 's', $today);
} elseif ($type === 'bu_ay') {
    mysqli_stmt_bind_param($stmt, 'ss', $today, $monthEnd);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$odenisLabels = [
    'ayliq' => 'Aylıq',
    'paket' => 'Paket',
];

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $status = odenis_status_meta($row);
    $row['status_label'] = $status['label'];
    $novu = (string) ($row['odenis_novu'] ?? '');
    $row['odenis_novu_label'] = $odenisLabels[$novu] ?? $novu;

    foreach ($row as $key => $value) {
        if ($value === null || $value === '') {
            $row[$key] = '-';
        }
    }
    $rows[] = $row;
}

mysqli_stmt_close($stmt);

echo json_encode([
    'status' => 'success',
    'type' => $type,
    'columns' => $columns,
    'data' => $rows,
]);

$conn->close();
