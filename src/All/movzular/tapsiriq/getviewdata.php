<?php
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

// Check if ID is provided
if (!isset($_GET['id'])) {
    handleError("Assignment ID is required");
}

$id = intval($_GET['id']);

// Get assignment with topic and group names
$sql = "SELECT t.*, m.movzu_adi, q.qrup_adi 
        FROM tapsiriqlar t
        LEFT JOIN movzular_new m ON t.movzu = m.id
        LEFT JOIN qruplar q ON t.qrup = q.id
        WHERE t.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    handleError("Assignment not found");
}

$assignment = $result->fetch_assoc();

// Parse JSON files if needed
if (!empty($assignment['fayllar'])) {
    $assignment['fayllar'] = json_decode($assignment['fayllar'], true);
}

handleSuccess(['assignment' => $assignment]);
?>
