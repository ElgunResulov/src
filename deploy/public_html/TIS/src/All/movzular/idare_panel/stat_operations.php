<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../../db.php');
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$uId = trim((string) ($_SESSION['u_id'] ?? ''));
$username = trim((string) ($_SESSION['username'] ?? ''));
$role = $_SESSION['role'] ?? '';

if ($uId === '') {
    echo json_encode(['status' => 'error', 'message' => 'İstifadəçi ID-si tapılmadı.']);
    $conn->close();
    exit;
}

$type = trim((string) ($_GET['type'] ?? ''));
$columns = [];
$query = '';
$types = '';
$params = [];

$isAdmin = in_array($role, ['super_admin', 'admin'], true);

switch ($type) {
    case 'students':
        if ($isAdmin) {
            $query = "SELECT id,
                             COALESCE(NULLIF(TRIM(reg_ad_soyad), ''), username) AS ad_soyad,
                             sinif, poct, active_status
                      FROM telebeler
                      ORDER BY id DESC";
        } else {
            $query = "SELECT id,
                             COALESCE(NULLIF(TRIM(reg_ad_soyad), ''), username) AS ad_soyad,
                             sinif, poct, active_status
                      FROM telebeler
                      WHERE JSON_CONTAINS(muellim_adi, JSON_QUOTE(?), '$')
                      ORDER BY id DESC";
            $types = 's';
            $params[] = $username;
        }
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'poct', 'label' => 'E-poçt'],
            ['key' => 'status_label', 'label' => 'Status'],
        ];
        break;

    case 'groups':
        $query = "SELECT id, qrup_adi, telebe_sayi, gunler, tarix, created_at
                  FROM qruplar
                  WHERE u_id = ?
                  ORDER BY created_at DESC";
        $types = 's';
        $params[] = $uId;
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'qrup_adi', 'label' => 'Qrup'],
            ['key' => 'telebe_sayi', 'label' => 'Tələbə sayı'],
            ['key' => 'gunler', 'label' => 'Günlər'],
            ['key' => 'tarix', 'label' => 'Tarix'],
        ];
        break;

    case 'topics':
        $query = "SELECT id, movzu_adi, fenn, created_at
                  FROM movzular_new
                  WHERE u_id = ?
                  ORDER BY created_at DESC";
        $types = 's';
        $params[] = $uId;
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'movzu_adi', 'label' => 'Mövzu'],
            ['key' => 'fenn', 'label' => 'Fənn'],
            ['key' => 'created_at', 'label' => 'Yaradılma'],
        ];
        break;

    case 'exams':
        $query = "SELECT id, exam_name, fenn_adi, sinif, exam_date, duration, status
                  FROM imtahanlar_exam
                  WHERE u_id = ?
                  ORDER BY exam_date DESC, id DESC";
        $types = 's';
        $params[] = $uId;
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'exam_name', 'label' => 'İmtahan'],
            ['key' => 'fenn_adi', 'label' => 'Fənn'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'exam_date', 'label' => 'Tarix'],
            ['key' => 'duration', 'label' => 'Müddət'],
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

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$statusLabels = [
    'upcoming' => 'Gələcək',
    'active' => 'Aktiv',
    'completed' => 'Tamamlanmış',
];

$rows = [];
while ($row = $result->fetch_assoc()) {
    if ($type === 'students') {
        $status = strtolower((string) ($row['active_status'] ?? ''));
        $row['status_label'] = in_array($status, ['active', 'aktiv', '1'], true) ? 'Aktiv' : 'Passiv';
    }

    if ($type === 'exams') {
        $status = strtolower((string) ($row['status'] ?? ''));
        $row['status_label'] = $statusLabels[$status] ?? ($row['status'] ?? '-');
    }

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
