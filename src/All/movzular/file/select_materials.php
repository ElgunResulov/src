<?php
session_start();
header('Content-Type: application/json');
include('../../db.php');

mb_internal_encoding('UTF-8');
$u_id = $_SESSION['u_id'] ?? null;

if (!$u_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, username, material_adi, movzu, tipi, file, size, created_at FROM materiallar WHERE u_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $u_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$materials = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $materials[] = $row;
    }
}

echo json_encode($materials);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>