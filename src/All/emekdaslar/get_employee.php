<?php
// Start session for user authentication and CSRF validation
require_once __DIR__ . '/../auth.php';
app_start_secure_session();
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized: Please log in']);
    exit();
}

// Validate CSRF token if sent
$requestToken = app_request_csrf_token();
if ($requestToken !== '' && !app_validate_csrf_token($requestToken)) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

// Check if employee ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid or missing employee ID']);
    exit();
}

$employee_id = (int)$_GET['id'];

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
    
    // Prepare SQL query to prevent SQL injection
    $query = "SELECT id, ad_soyad, sobe, vezife, email, telefon, ise_baslama_tarixi, status, unvan, sekil, tehsil, is_tecrubesi 
              FROM emekdaslar 
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }
    
    // Bind the employee ID parameter
    $stmt->bind_param('i', $employee_id);
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Check if employee exists
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Employee not found']);
        $stmt->close();
        $conn->close();
        exit();
    }
    
    // Fetch employee data
    $employee = $result->fetch_assoc();
    
    // Sanitize output to prevent XSS
    foreach ($employee as $key => $value) {
        if (is_string($value)) {
            $employee[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Return employee data as JSON
    echo json_encode($employee);
    
    // Close statement and connection
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Return generic error message
    echo json_encode(['error' => 'Server error: Unable to fetch employee data', 'details' => $e->getMessage()]);
    
    // Close connection if still open
    if (isset($conn) && $conn) {
        $conn->close();
    }
    exit();
}
?>