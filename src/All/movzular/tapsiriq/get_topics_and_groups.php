<?php
session_start();
include('../../db.php');

// Function to handle errors
function handleError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Function to handle success
function handleSuccess($data) {
    $response = ['status' => 'success'];
    echo json_encode(array_merge($response, $data));
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['u_id'])) {
    handleError("User not authenticated");
}

$u_id = $_SESSION['u_id'];

// Get topics for this user
$movzularSql = "SELECT * FROM movzular_new WHERE u_id = ? ORDER BY movzu_adi";
$stmt = $conn->prepare($movzularSql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$movzularResult = $stmt->get_result();

if (!$movzularResult) {
    handleError("Database error: " . $conn->error);
}

$movzular = [];
while ($row = $movzularResult->fetch_assoc()) {
    $movzular[] = $row;
}
$stmt->close();

// Get groups for this user
$qruplarSql = "SELECT * FROM qruplar WHERE u_id = ? ORDER BY qrup_adi";
$stmt = $conn->prepare($qruplarSql);
$stmt->bind_param("i", $u_id);
$stmt->execute();
$qruplarResult = $stmt->get_result();

if (!$qruplarResult) {
    handleError("Database error: " . $conn->error);
}

$qruplar = [];
while ($row = $qruplarResult->fetch_assoc()) {
    $qruplar[] = $row;
}
$stmt->close();

handleSuccess([
    'movzular' => $movzular,
    'qruplar' => $qruplar
]);
?>