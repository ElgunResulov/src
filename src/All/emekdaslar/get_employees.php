<?php
// Start session for user authentication
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized: Please log in']);
    exit();
}

// Define path to db.php
$db_path = __DIR__ . '/../db.php';

// Check if db.php exists
if (!file_exists($db_path)) {
    echo json_encode(['error' => 'Server error: Database configuration file missing', 'details' => 'File not found at: ' . $db_path]);
    exit();
}

// Include database connection
require $db_path;

try {
    // Verify connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection not established');
    }

    // Build SQL query with filters
    $query = "SELECT id, ad_soyad, sobe, vezife, email, telefon, ise_baslama_tarixi, status, unvan, sekil, tehsil, is_tecrubesi 
              FROM emekdaslar WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($_GET['department'])) {
        $query .= " AND sobe = ?";
        $params[] = $_GET['department'];
        $types .= 's';
    }
    
    if (!empty($_GET['position'])) {
        $query .= " AND vezife = ?";
        $params[] = $_GET['position'];
        $types .= 's';
    }
    
    if (!empty($_GET['status'])) {
        $query .= " AND status = ?";
        $params[] = $_GET['status'];
        $types .= 's';
    }
    
    if (!empty($_GET['search'])) {
        $query .= " AND (ad_soyad LIKE ? OR vezife LIKE ?)";
        $search = '%' . $_GET['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $types .= 'ss';
    }
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $employees = [];
    
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        $employees[] = $row;
    }
    
    // Return employee data as JSON
    echo json_encode($employees);
    
    // Close statement and connection
    $stmt->close();
    $conn->close();

} catch (Exception $e) {

    // Return generic error message
    echo json_encode(['error' => 'Server error: Unable to fetch employees', 'details' => $e->getMessage()]);
    
    // Close connection if still open
    if (isset($conn) && $conn) {
        $conn->close();
    }
    exit();
}
?>