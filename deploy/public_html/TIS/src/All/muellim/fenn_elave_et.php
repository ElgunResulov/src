<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Include database connection
include('../db.php');

// Set header to return JSON response
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fenn_adi = isset($_POST['fenn_adi']) ? trim($_POST['fenn_adi']) : '';
    
    // Validate required fields
    if (empty($fenn_adi)) {
        echo json_encode([
            'success' => false,
            'message' => 'Fənn adını daxil edin.'
        ]);
        exit;
    }
    
    // Generate random fenn_id
    $fenn_id = bin2hex(random_bytes(8)); // Generates a 16-character random string
    
    // Check if fennler table exists
    $sql = "SHOW TABLES LIKE 'fennler_new'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $stmt = $conn->prepare("SELECT id FROM fennler_new WHERE fenn_adi = ? OR fenn_id = ?");
        $stmt->bind_param("ss", $fenn_adi, $fenn_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && mysqli_num_rows($result) > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Bu fənn və ya fenn_id artıq mövcuddur.'
            ]);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO fennler_new (fenn_id, fenn_adi, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $fenn_id, $fenn_adi);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Fənn uğurla əlavə edildi.',
                'fenn_id' => $fenn_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Xəta baş verdi: ' . mysqli_error($conn)
            ]);
        }
    } else {
        // Create fennler table if it doesn't exist
        $sql = "CREATE TABLE fennler_new (
            id INT(11) NOT NULL AUTO_INCREMENT,
            fenn_id VARCHAR(16) NOT NULL,
            fenn_adi VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (fenn_id)
        )";
        
        if (mysqli_query($conn, $sql)) {
            $stmt = $conn->prepare("INSERT INTO fennler_new (fenn_id, fenn_adi, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $fenn_id, $fenn_adi);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fənn uğurla əlavə edildi.',
                    'fenn_id' => $fenn_id
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Xəta baş verdi: ' . mysqli_error($conn)
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fennler cədvəlini yaradarkən xəta baş verdi: ' . mysqli_error($conn)
            ]);
        }
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Yanlış sorğu metodu.'
    ]);
}

// Close the database connection
mysqli_close($conn);
?>