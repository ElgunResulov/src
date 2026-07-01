<?php
// Include database connection
include('../../db.php');

// Function to handle errors
function handleError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Function to handle success
function handleSuccess($data = null, $message = 'Operation successful') {
    $response = ['status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

// Handle API requests based on action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_assignments':
        // Get all assignments with topic and group names
        $sql = "SELECT t.*, m.movzu_adi, q.qrup_adi 
                FROM tapsiriqlar t
                LEFT JOIN movzular_new m ON t.movzu = m.id
                LEFT JOIN qruplar q ON t.qrup = q.id
                ORDER BY t.yaradilma_tarixi DESC";
        $result = $conn->query($sql);
        
        if (!$result) {
            handleError("Database error: " . $conn->error);
        }
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            // Parse JSON files if needed
            if (!empty($row['fayllar'])) {
                $row['fayllar'] = json_decode($row['fayllar'], true);
            }
            $assignments[] = $row;
        }
        
        handleSuccess(['assignments' => $assignments]);
        break;
        
    case 'get_topics_and_groups':
        // Get topics
        $movzularSql = "SELECT * FROM movzular_new ORDER BY movzu_adi";
        $movzularResult = $conn->query($movzularSql);
        
        if (!$movzularResult) {
            handleError("Database error: " . $conn->error);
        }
        
        $movzular = [];
        while ($row = $movzularResult->fetch_assoc()) {
            $movzular[] = $row;
        }
        
        // Get groups
        $qruplarSql = "SELECT * FROM qruplar ORDER BY qrup_adi";
        $qruplarResult = $conn->query($qruplarSql);
        
        if (!$qruplarResult) {
            handleError("Database error: " . $conn->error);
        }
        
        $qruplar = [];
        while ($row = $qruplarResult->fetch_assoc()) {
            $qruplar[] = $row;
        }
        
        handleSuccess([
            'movzular' => $movzular,
            'qruplar' => $qruplar
        ]);
        break;
        
    case 'get_assignment':
        // Get single assignment
        if (!isset($_GET['id'])) {
            handleError("Assignment ID is required");
        }
        
        $id = intval($_GET['id']);
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
        break;
        
    default:
        handleError("Unknown action");
}
?>
