<?php
session_start();
include('../../db.php');

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure UTF-8 encoding
mb_internal_encoding('UTF-8');
header('Content-Type: application/json; charset=utf-8');

// Function to return JSON response
function sendResponse($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get u_id from session
        $u_id = $_SESSION['u_id'] ?? null;

        // Check if u_id is set
        if (empty($u_id)) {
            sendResponse(false, 'İstifadəçi ID-si tapılmadı. Zəhmət olmasa daxil olun.');
        }

        // Get form data
        $material_adi = trim($_POST['materialName'] ?? '');
        $movzu = trim($_POST['materialTopic'] ?? '');
        $tipi = trim($_POST['materialType'] ?? '');

        // Debug: Log received data
        error_log("Received data: u_id=$u_id, materialName=$material_adi, materialTopic=$movzu, materialType=$tipi");
        error_log("FILES: " . print_r($_FILES, true));

        // Validate required fields
        if (empty($material_adi)) {
            sendResponse(false, 'Material adı daxil edilməyib.');
        }
        
        if (empty($tipi)) {
            sendResponse(false, 'Kateqoriya seçilməyib.');
        }

        // Validate tipi
        $valid_tipi = ['document', 'presentation', 'video', 'image'];
        if (!in_array($tipi, $valid_tipi)) {
            sendResponse(false, 'Yanlış kateqoriya seçilib.');
        }

        // File handling
        $file_name = '';
        $file_size = 0;
        $target_file = '';
        $max_file_size = 20 * 1024 * 1024; // 20MB in bytes

        // Check if file was uploaded
        if (isset($_FILES['materialFile']) && $_FILES['materialFile']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['materialFile'];
            $file_name = basename($file['name']);
            $file_size = $file['size'];

            // Validate file size
            if ($file_size > $max_file_size) {
                sendResponse(false, 'Fayl ölçüsü 20MB-dan böyük ola bilməz.');
            }

            // Create safe filename
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_base = pathinfo($file_name, PATHINFO_FILENAME);
            
            // Transliterate and sanitize filename
            $file_base = preg_replace('/[^A-Za-z0-9_-]/', '_', $file_base);
            $sanitized_file_name = $file_base . '.' . strtolower($file_ext);
            
            // Ensure unique filename with timestamp
            $timestamp = time();
            $upload_dir = 'uploads/';
            
            // Create upload directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    error_log("Failed to create directory: $upload_dir");
                    sendResponse(false, 'Upload qovluğu yaradıla bilmədi.');
                }
            }
            
            // Set target file path
            $target_file = $upload_dir . $timestamp . '_' . $sanitized_file_name;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                error_log("Failed to move uploaded file to: $target_file");
                sendResponse(false, 'Fayl yüklənmədi. Serverdə xəta baş verdi.');
            }
            
            // Verify file exists
            if (!file_exists($target_file)) {
                error_log("File not found after upload: $target_file");
                sendResponse(false, 'Fayl yükləndi, lakin serverdə tapılmadı.');
            }
            
            error_log("File uploaded successfully: $target_file (Size: $file_size bytes)");
        } elseif (isset($_FILES['materialFile']) && $_FILES['materialFile']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle upload errors
            $error_code = $_FILES['materialFile']['error'];
            $error_message = 'Fayl yüklənməsində xəta: ';
            
            switch ($error_code) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_message .= 'Fayl ölçüsü PHP tərəfindən icazə verilən maksimum ölçüdən böyükdür.';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message .= 'Fayl ölçüsü forma tərəfindən icazə verilən maksimum ölçüdən böyükdür.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message .= 'Fayl yalnız qismən yükləndi.';
                    break;
                default:
                    $error_message .= 'Kod: ' . $error_code;
            }
            
            error_log("File upload error: $error _

message");
            sendResponse(false, $error_message);
        }

        // Prepare SQL query with prepared statement
        $sql = "INSERT INTO materiallar (u_id, material_adi, movzu, tipi, file, size, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("SQL prepare error: " . mysqli_error($conn));
            sendResponse(false, 'SQL hazırlanarkən xəta: ' . mysqli_error($conn));
        }
        
        // Bind parameters (u_id is integer, others are strings, size is integer)
        mysqli_stmt_bind_param($stmt, 'sssssi', $u_id, $material_adi, $movzu, $tipi, $target_file, $file_size);
        
        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            error_log("SQL execute error: " . mysqli_stmt_error($stmt));
            sendResponse(false, 'Məlumat əlavə edilərkən xəta: ' . mysqli_stmt_error($stmt));
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
        
        // Return success response
        sendResponse(true, 'Material uğurla əlavə olundu!');
        
    } catch (Exception $e) {
        // Log and return any unexpected errors
        error_log("Exception: " . $e->getMessage());
        sendResponse(false, 'Sistem xətası: ' . $e->getMessage());
    }
} else {
    // Not a POST request
    sendResponse(false, 'Form düzgün göndərilməyib. POST metodu istifadə edin.');
}

// Close connection
mysqli_close($conn);
?>