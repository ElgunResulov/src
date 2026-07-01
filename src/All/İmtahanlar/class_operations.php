<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn)) {
    include('../db.php');
}
app_require_auth_api($conn);

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Add new class
    if ($action == 'add_class') {
        $sinif_number = sanitize_input($_POST['className']);
        $tutum = isset($_POST['capacity']) ? (int) $_POST['capacity'] : 30; // Default capacity is 30

        $stmt = $conn->prepare("INSERT INTO sinifler (sinif_number, tutum, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("si", $sinif_number, $tutum);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Sinif uğurla əlavə edildi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
    }
    
    // Get all classes
    else if ($action == 'get_classes') {
        $sql = "SELECT * FROM sinifler ORDER BY sinif_number ASC";
        $result = $conn->query($sql);
        
        $classes = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $classes[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $classes]);
        } else {
            echo json_encode(['success' => true, 'data' => []]);
        }
    }
    
    // Only close the connection if this file is called directly
    if (!isset($dontCloseConnection)) {
        $conn->close();
    }
}
?>