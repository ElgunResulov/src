<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['super_admin', 'admin', 'teacher'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Giriş icazəsi yoxdur.']);
    $conn->close();
    exit;
}

$type = trim((string) ($_GET['type'] ?? ''));
$teacherUsername = (string) ($_SESSION['username'] ?? '');

$whereClause = 'WHERE 1=1';
$whereTypes = '';
$whereParams = [];

if ($role === 'teacher') {
    $whereClause .= " AND JSON_CONTAINS(muellim_adi, JSON_QUOTE(?), '$')";
    $whereTypes .= 's';
    $whereParams[] = $teacherUsername;
}

$baseSelect = "SELECT id,
                      COALESCE(NULLIF(TRIM(reg_ad_soyad), ''), username) AS ad_soyad,
                      username, sinif, cins, poct, orta_bal, active_status, qebul_tarixi
               FROM telebeler ";
$orderBy = ' ORDER BY id DESC';

switch ($type) {
    case 'all':
        $query = $baseSelect . $whereClause . $orderBy;
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'poct', 'label' => 'E-poçt'],
            ['key' => 'status_label', 'label' => 'Status'],
            ['key' => 'qebul_tarixi', 'label' => 'Qəbul tarixi'],
        ];
        break;

    case 'active':
        $query = $baseSelect . $whereClause . " AND active_status = 'active'" . $orderBy;
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'poct', 'label' => 'E-poçt'],
            ['key' => 'qebul_tarixi', 'label' => 'Qəbul tarixi'],
        ];
        break;

    case 'gender':
        $query = $baseSelect . $whereClause . $orderBy;
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'cins_label', 'label' => 'Cins'],
            ['key' => 'status_label', 'label' => 'Status'],
        ];
        break;

    case 'grades':
        $query = $baseSelect . $whereClause . " AND orta_bal REGEXP '^[0-9]+\\.?[0-9]*$'" . ' ORDER BY CAST(orta_bal AS DECIMAL(5,2)) DESC';
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'orta_bal', 'label' => 'Orta bal'],
            ['key' => 'status_label', 'label' => 'Status'],
        ];
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Yanlış statistik tipi.']);
        $conn->close();
        exit;
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . $conn->error]);
    $conn->close();
    exit;
}

if ($whereTypes !== '') {
    $refs = [];
    foreach ($whereParams as $key => $value) {
        $refs[$key] = &$whereParams[$key];
    }
    $stmt->bind_param($whereTypes, ...$refs);
}

$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $cins = (string) ($row['cins'] ?? '');
    $row['cins_label'] = $cins === '1' ? 'Qadın' : ($cins === '0' ? 'Kişi' : '-');

    $status = strtolower((string) ($row['active_status'] ?? ''));
    $row['status_label'] = in_array($status, ['active', 'aktiv', '1'], true) ? 'Aktiv' : 'Passiv';

    foreach ($row as $key => $value) {
        if ($value === null || $value === '') {
            $row[$key] = '-';
        }
    }
    $rows[] = $row;
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'type' => $type,
    'columns' => $columns,
    'data' => $rows,
]);

$conn->close();
