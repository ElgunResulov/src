<?php
include('../../db.php');

// Helper function to handle errors
function dieWithError($message) {
    // Log the error for debugging
    error_log("Assignment Delete Error: " . $message);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Helper function to handle success
function dieWithSuccess($message, $data = null) {
    $response = ['status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Check if ID is provided
if (!isset($data['id'])) {
    dieWithError("Assignment ID is required");
}

$id = intval($data['id']);

// Get files to delete
$stmt = $conn->prepare("SELECT fayllar FROM tapsiriqlar WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $files = !empty($row['fayllar']) ? json_decode($row['fayllar'], true) : [];
    
    // Delete physical files
    foreach ($files as $file) {
        $fullPath = '../../' . $file;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}

// Delete from database
$stmt = $conn->prepare("DELETE FROM tapsiriqlar WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    dieWithSuccess('Tapşırıq uğurla silindi');
} else {
    dieWithError('Database error: ' . $stmt->error);
}

// Clean up
$stmt->close();
$conn->close();
?>
