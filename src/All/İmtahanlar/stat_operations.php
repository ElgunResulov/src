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
    case 'all':
        $query = "SELECT id, exam_name, fenn_adi, sinif, exam_date, duration, passing_score, status
                  FROM imtahanlar_exam
                  ORDER BY exam_date DESC, id DESC";
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'exam_name', 'label' => 'İmtahan'],
            ['key' => 'fenn_adi', 'label' => 'Fənn'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'exam_date', 'label' => 'Tarix'],
            ['key' => 'duration', 'label' => 'Müddət'],
            ['key' => 'passing_score', 'label' => 'Keçid balı'],
            ['key' => 'status_label', 'label' => 'Status'],
        ];
        break;

    case 'completed':
        $query = "SELECT id, exam_name, fenn_adi, sinif, exam_date, duration, passing_score, status
                  FROM imtahanlar_exam
                  WHERE status = 'completed'
                  ORDER BY exam_date DESC, id DESC";
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'exam_name', 'label' => 'İmtahan'],
            ['key' => 'fenn_adi', 'label' => 'Fənn'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'exam_date', 'label' => 'Tarix'],
            ['key' => 'passing_score', 'label' => 'Keçid balı'],
            ['key' => 'status_label', 'label' => 'Status'],
        ];
        break;

    case 'average':
        $query = "SELECT id, exam_name, fenn_adi, sinif, passing_score, status, exam_date
                  FROM imtahanlar_exam
                  WHERE passing_score IS NOT NULL
                  ORDER BY passing_score DESC, exam_date DESC";
        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'exam_name', 'label' => 'İmtahan'],
            ['key' => 'fenn_adi', 'label' => 'Fənn'],
            ['key' => 'sinif', 'label' => 'Sinif'],
            ['key' => 'passing_score', 'label' => 'Keçid balı'],
            ['key' => 'exam_date', 'label' => 'Tarix'],
        ];
        break;

    case 'upcoming':
        $query = "SELECT id, exam_name, fenn_adi, sinif, exam_date, duration, passing_score, status
                  FROM imtahanlar_exam
                  WHERE status = 'upcoming'
                  ORDER BY exam_date ASC, id DESC";
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

$result = mysqli_query($conn, $query);
if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
    $conn->close();
    exit;
}

$statusLabels = [
    'upcoming' => 'Gələcək',
    'active' => 'Aktiv',
    'completed' => 'Tamamlanmış',
];

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $status = strtolower((string) ($row['status'] ?? ''));
    $row['status_label'] = $statusLabels[$status] ?? ($row['status'] ?? '-');

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
