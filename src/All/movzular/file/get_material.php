<?php
session_start();
include('../../db.php'); // Adjust path to your db.php

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize input
    $sql = "SELECT material_adi, file FROM materiallar WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $filePath = $row['file'];
        if (!empty($filePath)) {
            // Calculate absolute path
            $absoluteFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($filePath, '/');
            // Try decoding for non-ASCII characters
            $decodedFilePath = urldecode($absoluteFilePath);
            error_log("Checking file: $absoluteFilePath (Decoded: $decodedFilePath)");

            if (file_exists($absoluteFilePath) || file_exists($decodedFilePath)) {
                echo json_encode([
                    'success' => true,
                    'file' => $filePath,
                    'material_adi' => $row['material_adi']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Fayl serverdə tapılmadı: ' . $filePath
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fayl yolu boşdur.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Material tapılmadı.'
        ]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID təqdim edilməyib.'
    ]);
}

mysqli_close($conn);
?>