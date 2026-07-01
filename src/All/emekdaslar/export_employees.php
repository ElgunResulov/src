<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ../Login.php');
    exit();
}

if (!file_exists($db_path)) {
    die('Server error: Database configuration file missing');
}
require $db_path;

try {
    // Fetch all employees
    $query = "SELECT ad_soyad, sobe, vezife, email, telefon, ise_baslama_tarixi, status, unvan, tehsil, is_tecrubesi 
              FROM emekdaslar";
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=employees_' . date('Y-m-d_His') . '.csv');

    // Output CSV
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($output, ['Ad Soyad', 'Şöbə', 'Vəzifə', 'Email', 'Telefon', 'İşə Başlama Tarixi', 'Status', 'Ünvan', 'Təhsil', 'İş Təcrübəsi']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['ad_soyad'],
            $row['sobe'],
            $row['vezife'],
            $row['email'],
            $row['telefon'],
            $row['ise_baslama_tarixi'],
            $row['status'],
            $row['unvan'],
            $row['tehsil'],
            $row['is_tecrubesi']
        ]);
    }

    fclose($output);
    $conn->close();
    exit();

} catch (Exception $e) {
    die('Server error: Unable to export employees');
}
?>