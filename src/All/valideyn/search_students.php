<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    $query = isset($_POST['query']) ? trim($_POST['query']) : '';
    
    $sql = "SELECT username, ata, ana FROM telebeler WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($query)) {
        $sql .= " AND (username LIKE ? OR ata LIKE ? OR ana LIKE ?)";
        $like_query = "%$query%";
        $params = [$like_query, $like_query, $like_query];
        $types = "sss";
    }
    
    $sql .= " ORDER BY username ASC LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $student = [
            'username' => trim($row['username']) ?: null,
            'ata' => !empty(trim($row['ata'])) ? trim($row['ata']) : null,
            'ana' => !empty(trim($row['ana'])) ? trim($row['ana']) : null
        ];
        
        if ($student['username'] || $student['ata'] || $student['ana']) {
            $students[] = $student;
        }
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students),
        'query' => $query
    ], JSON_UNESCAPED_UNICODE);
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Search students error: " . $e->getMessage());
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>