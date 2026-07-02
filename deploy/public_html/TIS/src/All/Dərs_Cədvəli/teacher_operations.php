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

    // Get all teachers
    if ($action == 'get_teachers') {
        $sql = "SELECT * FROM muellimler_new WHERE active_status = 1 ORDER BY username ASC";
        $result = $conn->query($sql);
        
        $teachers = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $teachers[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $teachers]);
        } else {
            echo json_encode(['success' => true, 'data' => []]);
        }
    }
    
    // Get teacher by ID
    else if ($action == 'get_teacher') {
        $id = (int) $_POST['teacherId'];

        $stmt = $conn->prepare("SELECT * FROM muellimler_new WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $teacher = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $teacher]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Müəllim tapılmadı']);
        }
    }
    
    // Get teachers by subject
    else if ($action == 'get_teachers_by_subject') {
        $fenn = sanitize_input($_POST['subject']);

        $stmt = $conn->prepare("SELECT * FROM muellimler_new WHERE fenn = ? AND active_status = 1 ORDER BY username ASC");
        $stmt->bind_param("s", $fenn);
        $stmt->execute();
        $result = $stmt->get_result();

        $teachers = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $teachers[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $teachers]);
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