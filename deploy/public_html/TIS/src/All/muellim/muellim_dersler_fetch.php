<?php
include('../db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'İstifadəçi identifikasiyası yoxdur.']);
    exit();
}

header('Content-Type: application/json');

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

if (!isset($_GET['teacher_id']) || empty($_GET['teacher_id'])) {
    $response['message'] = 'Müəllim ID-si təqdim edilməyib.';
    echo json_encode($response);
    exit();
}

$teacher_id = mysqli_real_escape_string($conn, $_GET['teacher_id']);

$sql = "SHOW TABLES LIKE 'dersler'";
$result = mysqli_query($conn, $sql);

if (!$result) {
        $response['message'] = 'Verilənlər bazası xətası.';
    echo json_encode($response);
    exit();
}

if (mysqli_num_rows($result) > 0) {
    $teacher_id = (int) $_GET['teacher_id'];
    $stmt = $conn->prepare("SELECT sinif, tarix, start_time, end_time, otaq FROM dersler WHERE muellim_id = ? ORDER BY tarix, start_time");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        $response['message'] = 'Dərs məlumatlarını əldə edərkən xəta baş verdi.';
        echo json_encode($response);
        exit();
    }

    $classes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['vaxt'] = $row['start_time'] . " - " . $row['end_time'];
        unset($row['start_time'], $row['end_time']);
        $classes[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $classes;
} else {
    $response['message'] = 'Dersler cədvəli mövcud deyil.';
}

echo json_encode($response);
mysqli_close($conn);
?>