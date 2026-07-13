<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$type = trim((string) ($_GET['type'] ?? ''));
$columns = [];
$query = '';

switch ($type) {
    case 'students':
        $query = "SELECT id,
                         COALESCE(NULLIF(TRIM(reg_ad_soyad), ''), username) AS ad_soyad,
                         sinif, ixtisas_adi, orta_bal, davamiyyet, active_status
                  FROM telebeler
                  ORDER BY id DESC";
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'ixtisas_adi', 'label' => 'İxtisas'],
            ['key' => 'orta_bal', 'label' => 'Orta bal'],
            ['key' => 'davamiyyet', 'label' => 'Davamiyyət'],
            ['key' => 'status_label', 'label' => 'Status'],
        ];
        break;

    case 'grades':
        $query = "SELECT id,
                         COALESCE(NULLIF(TRIM(reg_ad_soyad), ''), username) AS ad_soyad,
                         sinif, orta_bal
                  FROM telebeler
                  WHERE orta_bal REGEXP '^[0-9]+\\.?[0-9]*$'
                  ORDER BY CAST(orta_bal AS DECIMAL(5,2)) DESC
                  LIMIT 100";
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'orta_bal', 'label' => 'Orta bal'],
        ];
        break;

    case 'attendance':
        $query = "SELECT id,
                         COALESCE(NULLIF(TRIM(reg_ad_soyad), ''), username) AS ad_soyad,
                         sinif, davamiyyet, muellim_adi
                  FROM telebeler
                  ORDER BY id DESC";
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'ad_soyad', 'label' => 'Ad Soyad'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'davamiyyet', 'label' => 'Davamiyyət'],
            ['key' => 'muellim_adi', 'label' => 'Müəllim'],
        ];
        break;

    case 'olympiad':
        $query = "SELECT id, exam_name, fenn_adi, sinif, exam_date, status
                  FROM imtahanlar_exam
                  WHERE LOWER(exam_name) LIKE '%olimpiad%' OR LOWER(fenn_adi) LIKE '%olimpiad%'
                  ORDER BY exam_date DESC, id DESC";
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'exam_name', 'label' => 'İmtahan'],
            ['key' => 'fenn_adi', 'label' => 'Fənn'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'exam_date', 'label' => 'Tarix'],
            ['key' => 'status_label', 'label' => 'Status'],
        ];
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
    if ($type === 'students') {
        $status = strtolower((string) ($row['active_status'] ?? ''));
        $row['status_label'] = in_array($status, ['active', 'aktiv', '1'], true) ? 'Aktiv' : 'Passiv';
    }

    if ($type === 'olympiad') {
        $status = strtolower((string) ($row['status'] ?? ''));
        $statusLabels = [
            'upcoming' => 'Gələcək',
            'active' => 'Aktiv',
            'completed' => 'Tamamlanmış',
        ];
        $row['status_label'] = $statusLabels[$status] ?? ($row['status'] ?? '-');
    }

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
