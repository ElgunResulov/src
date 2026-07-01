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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fenn_id = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $sinif_id = isset($_POST['class']) ? (int) $_POST['class'] : 0;
    $muellim_id = isset($_POST['teacher']) ? (int) $_POST['teacher'] : 0;
    $otaq_id = isset($_POST['room']) ? (int) $_POST['room'] : 0;
    $tarix = isset($_POST['date']) ? trim($_POST['date']) : '';
    $start_time = isset($_POST['startTime']) ? trim($_POST['startTime']) : '';
    $end_time = isset($_POST['endTime']) ? trim($_POST['endTime']) : '';
    $movzu = isset($_POST['topic']) ? trim($_POST['topic']) : '';
    $tesvir = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Get status from the form or use default
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Planlaşdırılıb';
    $active_status = 1; // Default active status
    
    // Handle file uploads for materials
    $materiallar = '';
    if (isset($_FILES['materials']) && $_FILES['materials']['error'][0] != 4) {
        $fileCount = count($_FILES['materials']['name']);
        $uploadedFiles = [];
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $_FILES['materials']['name'][$i];
            $fileTmpName = $_FILES['materials']['tmp_name'][$i];
            $fileSize = $_FILES['materials']['size'][$i];
            $fileError = $_FILES['materials']['error'][$i];
            
            if ($fileError === 0) {
                // Create uploads directory if it doesn't exist
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                $fileDestination = 'uploads/' . time() . '_' . $fileName;
                move_uploaded_file($fileTmpName, $fileDestination);
                $uploadedFiles[] = $fileDestination;
            }
        }
        
        if (!empty($uploadedFiles)) {
            $materiallar = json_encode($uploadedFiles);
        }
    }
    
    // Get fenn (subject) name based on the selected option
    $fenn_text = '';
    switch ($fenn_id) {
        case '1':
            $fenn_text = 'Riyaziyyat';
            break;
        case '2':
            $fenn_text = 'Fizika';
            break;
        case '3':
            $fenn_text = 'Kimya';
            break;
        case '4':
            $fenn_text = 'Biologiya';
            break;
        case '5':
            $fenn_text = 'Tarix';
            break;
        case '6':
            $fenn_text = 'Ədəbiyyat';
            break;
        default:
            $fenn_text = 'Bilinməyən';
    }
    
    // Get sinif (class) name/number
    $sinif_text = '';
    $sinif_stmt = $conn->prepare("SELECT sinif_number FROM sinifler WHERE id = ?");
    $sinif_stmt->bind_param("i", $sinif_id);
    $sinif_stmt->execute();
    $sinif_result = $sinif_stmt->get_result();
    if ($sinif_result && mysqli_num_rows($sinif_result) > 0) {
        $sinif_text = mysqli_fetch_assoc($sinif_result)['sinif_number'];
    }
    
    // Get otaq (room) number and capacity
    $otaq_text = '';
    $sagird_sayi = '0';
    
    // Debug the otaq_id
    error_log("Selected Otaq ID: " . print_r($otaq_id, true));
    
    $otaq_stmt = $conn->prepare("SELECT otaq_number, tutum FROM otaqlar WHERE id = ?");
    $otaq_stmt->bind_param("i", $otaq_id);
    $otaq_stmt->execute();
    $otaq_result = $otaq_stmt->get_result();
    
    if ($otaq_result && mysqli_num_rows($otaq_result) > 0) {
        $otaq_data = mysqli_fetch_assoc($otaq_result);
        error_log("Room Data: " . print_r($otaq_data, true));
        
        $otaq_text = $otaq_data['otaq_number'];
        
        // Make sure tutum is not null and convert to string
        if (isset($otaq_data['tutum']) && $otaq_data['tutum'] !== null) {
            $sagird_sayi = (string)$otaq_data['tutum']; // Convert to string explicitly
        }
        
        error_log("Otaq Text: $otaq_text, Sagird Sayi: $sagird_sayi");
    } else {
        error_log("No room data found for ID: $otaq_id");
    }
    
    // Get muellim (teacher) username and extract only the first name
    $muellim_text = '';
    $muellim_stmt = $conn->prepare("SELECT username FROM muellimler_new WHERE id = ?");
    $muellim_stmt->bind_param("i", $muellim_id);
    $muellim_stmt->execute();
    $muellim_result = $muellim_stmt->get_result();
    if ($muellim_result && mysqli_num_rows($muellim_result) > 0) {
        $muellim_full_name = mysqli_fetch_assoc($muellim_result)['username'];
        // Extract only the first name (everything before the first space)
        $name_parts = explode(' ', $muellim_full_name, 2);
        $muellim_text = $name_parts[0]; // Get only the first name
    }
    
    // Validate required fields
    $errors = [];
    if (empty($fenn_id)) $errors[] = "Fənn seçilməlidir";
    if (empty($sinif_id)) $errors[] = "Sinif seçilməlidir";
    if (empty($muellim_id)) $errors[] = "Müəllim seçilməlidir";
    if (empty($otaq_id)) $errors[] = "Otaq seçilməlidir";
    if (empty($tarix)) $errors[] = "Tarix daxil edilməlidir";
    if (empty($start_time)) $errors[] = "Başlama vaxtı daxil edilməlidir";
    if (empty($end_time)) $errors[] = "Bitmə vaxtı daxil edilməlidir";
    if (empty($movzu)) $errors[] = "Mövzu daxil edilməlidir";
    
    // If there are validation errors
    if (!empty($errors)) {
        $_SESSION['error_message'] = "Xəta: " . implode(", ", $errors);
        header("Location: ../Dərslər.php");
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO dersler (fenn, sinif, start_time, end_time, otaq, muellim, sagird_sayi, status, movzu, active_status, tesvir, materiallar, tarix, muellim_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssississsi", $fenn_text, $sinif_text, $start_time, $end_time, $otaq_text, $muellim_text, $sagird_sayi, $status, $movzu, $active_status, $tesvir, $materiallar, $tarix, $muellim_id);

    if ($stmt->execute()) {
        // Success message
        $_SESSION['success_message'] = "Dərs uğurla əlavə edildi!";
        header("Location: ../Dərs_Cədvəli.php");
        exit();
    } else {
        // Error message
        $error = mysqli_error($conn);
        error_log("Insert Error: " . $error);
        $_SESSION['error_message'] = "Xəta baş verdi: " . $error;
        header("Location: ../Dərs_Cədvəli.php");
        exit();
    }
} else {
    // If accessed directly without POST
    header("Location: ../Dərs_Cədvəli.php");
    exit();
}
?>