<?php
session_start();
include('../../db.php');
header('Content-Type: application/json');

function handleError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function handleSuccess($data) {
    $response = ['status' => 'success'];
    echo json_encode(array_merge($response, $data));
    exit;
}

$u_id = $_SESSION['u_id'] ?? null;
if (!$u_id) {
    handleError("User not authenticated");
}

$sql = "SELECT t.*, m.movzu_adi, q.qrup_adi 
        FROM tapsiriqlar t
        LEFT JOIN movzular_new m ON t.movzu = m.id
        LEFT JOIN qruplar q ON t.qrup = q.id
        WHERE t.u_id = ?
        ORDER BY t.yaradilma_tarixi DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    handleError("Database prepare error: " . $conn->error);
}
$stmt->bind_param("s", $u_id);
$stmt->execute();
$result = $stmt->get_result();

$assignments = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['fayllar'])) {
        $row['fayllar'] = json_decode($row['fayllar'], true);
    }
    $assignments[] = $row;
}
$stmt->close();

$movzularSql = "SELECT * FROM movzular_new WHERE u_id = ? ORDER BY movzu_adi";
$stmt = $conn->prepare($movzularSql);
if (!$stmt) {
    handleError("Database prepare error: " . $conn->error);
}
$stmt->bind_param("s", $u_id);
$stmt->execute();
$movzularResult = $stmt->get_result();

$movzular = [];
while ($row = $movzularResult->fetch_assoc()) {
    $movzular[] = $row;
}
$stmt->close();

$qruplarSql = "SELECT * FROM qruplar WHERE u_id = ? ORDER BY qrup_adi";
$stmt = $conn->prepare($qruplarSql);
if (!$stmt) {
    handleError("Database prepare error: " . $conn->error);
}
$stmt->bind_param("s", $u_id);
$stmt->execute();
$qruplarResult = $stmt->get_result();

$qruplar = [];
while ($row = $qruplarResult->fetch_assoc()) {
    $qruplar[] = $row;
}
$stmt->close();

$conn->close();

handleSuccess([
    'assignments' => $assignments,
    'movzular' => $movzular,
    'qruplar' => $qruplar
]);
?>









<!-- OLD WITH EDIT CORRECT -->


<?php
// include('../../db.php');

// function handleError($message) {
//     echo json_encode(['status' => 'error', 'message' => $message]);
//     exit;
// }

// function handleSuccess($data) {
//     $response = ['status' => 'success'];
//     echo json_encode(array_merge($response, $data));
//     exit;
// }

// $sql = "SELECT t.*, m.movzu_adi, q.qrup_adi 
//         FROM tapsiriqlar t
//         LEFT JOIN movzular_new m ON t.movzu = m.id
//         LEFT JOIN qruplar q ON t.qrup = q.id
//         ORDER BY t.yaradilma_tarixi DESC";
// $result = $conn->query($sql);

// if (!$result) {
//     handleError("Database error: " . $conn->error);
// }

// $assignments = [];
// while ($row = $result->fetch_assoc()) {
//     if (!empty($row['fayllar'])) {
//         $row['fayllar'] = json_decode($row['fayllar'], true);
//     }
//     $assignments[] = $row;
// }

// $movzularSql = "SELECT * FROM movzular_new ORDER BY movzu_adi";
// $movzularResult = $conn->query($movzularSql);

// if (!$movzularResult) {
//     handleError("Database error: " . $conn->error);
// }

// $movzular = [];
// while ($row = $movzularResult->fetch_assoc()) {
//     $movzular[] = $row;
// }

// $qruplarSql = "SELECT * FROM qruplar ORDER BY qrup_adi";
// $qruplarResult = $conn->query($qruplarSql);

// if (!$qruplarResult) {
//     handleError("Database error: " . $conn->error);
// }

// $qruplar = [];
// while ($row = $qruplarResult->fetch_assoc()) {
//     $qruplar[] = $row;
// }

// handleSuccess([
//     'assignments' => $assignments,
//     'movzular' => $movzular,
//     'qruplar' => $qruplar
// ]);
?>
