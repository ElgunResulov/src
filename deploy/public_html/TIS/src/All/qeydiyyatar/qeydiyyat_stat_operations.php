<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$type = trim((string) ($_GET['type'] ?? ''));

$columns = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'telebe_ad_soyad', 'label' => 'Tələbə'],
    ['key' => 'ixtisas_adi', 'label' => 'İxtisas'],
    ['key' => 'baslama_tarixi', 'label' => 'Başlama tarixi'],
    ['key' => 'tedris_ili', 'label' => 'Tədris ili'],
    ['key' => 'odenis_novu_label', 'label' => 'Ödəniş növü'],
    ['key' => 'tehsil_haqqi', 'label' => 'Təhsil haqqı'],
];

$odenisLabels = [
    'ayliq' => 'Aylıq',
    'paket' => 'Paket',
];

switch ($type) {
    case 'month':
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (int) ($_GET['month'] ?? 0);

        if ($month < 1 || $month > 12) {
            echo json_encode(['status' => 'error', 'message' => 'Yanlış ay seçimi.']);
            $conn->close();
            exit;
        }

        $query = "SELECT id, telebe_ad_soyad, ixtisas_adi, baslama_tarixi, tedris_ili, odenis_novu, tehsil_haqqi
                  FROM qeydiyyatar
                  WHERE YEAR(baslama_tarixi) = ? AND MONTH(baslama_tarixi) = ?
                  ORDER BY baslama_tarixi DESC, id DESC";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . $conn->error]);
            $conn->close();
            exit;
        }
        $stmt->bind_param('ii', $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $novu = (string) ($row['odenis_novu'] ?? '');
            $row['odenis_novu_label'] = $odenisLabels[$novu] ?? $novu;
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
        exit;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Yanlış statistik tipi.']);
        $conn->close();
        exit;
}
