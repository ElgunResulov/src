<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$type = trim((string) ($_GET['type'] ?? ''));

$sobeLabels = [
    'teaching' => 'Tədris',
    'admin' => 'İnzibati',
    'it' => 'Texniki',
];

$statusLabels = [
    'active' => 'Aktiv',
    'inactive' => 'Passiv',
    'on_leave' => 'Məzuniyyətdə',
];

$columns = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
    ['key' => 'sobe_label', 'label' => 'Şöbə'],
    ['key' => 'vezife', 'label' => 'Vəzifə'],
    ['key' => 'email', 'label' => 'E-poçt'],
    ['key' => 'telefon', 'label' => 'Telefon'],
    ['key' => 'status_label', 'label' => 'Status'],
    ['key' => 'ise_baslama_tarixi', 'label' => 'İşə başlama'],
];

$baseSelect = "SELECT id, ad_soyad, sobe, vezife, email, telefon, status, ise_baslama_tarixi FROM emekdaslar";
$orderBy = ' ORDER BY ad_soyad ASC';

switch ($type) {
    case 'all':
        $query = $baseSelect . $orderBy;
        break;

    case 'teaching':
        $query = $baseSelect . " WHERE sobe = 'teaching'" . $orderBy;
        break;

    case 'admin':
        $query = $baseSelect . " WHERE sobe = 'admin'" . $orderBy;
        break;

    case 'technical':
        $query = $baseSelect . " WHERE sobe = 'it'" . $orderBy;
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Yanlış statistik tipi.']);
        $conn->close();
        exit;
}

$result = mysqli_query($conn, $query);
if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
    $conn->close();
    exit;
}

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sobe = (string) ($row['sobe'] ?? '');
    $status = (string) ($row['status'] ?? '');
    $row['sobe_label'] = $sobeLabels[$sobe] ?? $sobe;
    $row['status_label'] = $statusLabels[$status] ?? $status;

    foreach ($row as $key => $value) {
        if ($value === null || $value === '') {
            $row[$key] = '-';
        }
    }
    $rows[] = $row;
}

echo json_encode([
    'status' => 'success',
    'type' => $type,
    'columns' => $columns,
    'data' => $rows,
]);

$conn->close();
