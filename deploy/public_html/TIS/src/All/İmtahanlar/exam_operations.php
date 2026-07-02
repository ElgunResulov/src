<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include('../db.php');
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
    
    // Add new exam
    if ($action == 'add_exam') {
        $imtahan_adi = sanitize_input($_POST['examName']);
        $fenn = sanitize_input($_POST['subject']);
        $tarix = sanitize_input($_POST['examDate']);
        $vaxt = sanitize_input($_POST['examTime']);
        $sinif = sanitize_input($_POST['classes']);
        $muddet_deqiqe = sanitize_input($_POST['duration']);
        $mekan = sanitize_input($_POST['location']);
        $maksimum_bal = sanitize_input($_POST['totalMarks']);
        $kecid_bali = sanitize_input($_POST['passingMarks']);
        $tesvir = sanitize_input($_POST['description']);
        $telimatlar = sanitize_input($_POST['instructions']);
        $status = sanitize_input($_POST['status']);
        
        // Convert status code to text
        switch ($status) {
            case '1':
                $status_text = 'Tamamlanıb';
                break;
            case '2':
                $status_text = 'Gələcək';
                break;
            case '3':
                $status_text = 'Davam edir';
                break;
            case '4':
                $status_text = 'Ləğv edilib';
                break;
            default:
                $status_text = 'Gələcək';
        }
        
        $stmt = $conn->prepare("INSERT INTO i̇mtahanlar (imtahan_adi, fenn, tarix, vaxt, sinif, muddet_deqiqe, mekan, maksimum_bal, kecid_bali, tesvir, telimatlar, status, active_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->bind_param("ssssssssssss", $imtahan_adi, $fenn, $tarix, $vaxt, $sinif, $muddet_deqiqe, $mekan, $maksimum_bal, $kecid_bali, $tesvir, $telimatlar, $status_text);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'İmtahan uğurla əlavə edildi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
    }
    
    // Edit exam
    else if ($action == 'edit_exam') {
        $id = (int) $_POST['examId'];
        $imtahan_adi = sanitize_input($_POST['examName']);
        $fenn = sanitize_input($_POST['subject']);
        $tarix = sanitize_input($_POST['examDate']);
        $vaxt = sanitize_input($_POST['examTime']);
        $sinif = sanitize_input($_POST['classes']);
        $muddet_deqiqe = sanitize_input($_POST['duration']);
        $mekan = sanitize_input($_POST['location']);
        $maksimum_bal = sanitize_input($_POST['totalMarks']);
        $kecid_bali = sanitize_input($_POST['passingMarks']);
        $tesvir = sanitize_input($_POST['description']);
        $telimatlar = sanitize_input($_POST['instructions']);
        $status = sanitize_input($_POST['status']);
        
        // Convert status code to text
        switch ($status) {
            case '1':
                $status_text = 'Tamamlanıb';
                break;
            case '2':
                $status_text = 'Gələcək';
                break;
            case '3':
                $status_text = 'Davam edir';
                break;
            case '4':
                $status_text = 'Ləğv edilib';
                break;
            default:
                $status_text = 'Gələcək';
        }
        
        $stmt = $conn->prepare("UPDATE i̇mtahanlar SET imtahan_adi = ?, fenn = ?, tarix = ?, vaxt = ?, sinif = ?, muddet_deqiqe = ?, mekan = ?, maksimum_bal = ?, kecid_bali = ?, tesvir = ?, telimatlar = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssssi", $imtahan_adi, $fenn, $tarix, $vaxt, $sinif, $muddet_deqiqe, $mekan, $maksimum_bal, $kecid_bali, $tesvir, $telimatlar, $status_text, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'İmtahan uğurla yeniləndi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
    }
    
    // Delete exam (soft delete by setting active_status to 0)
    else if ($action == 'delete_exam') {
        $id = (int) $_POST['examId'];

        $stmt = $conn->prepare("UPDATE i̇mtahanlar SET active_status = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'İmtahan uğurla silindi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
    }
    
    // Get exam details
    else if ($action == 'get_exam') {
        $id = (int) $_POST['examId'];

        $stmt = $conn->prepare("SELECT * FROM i̇mtahanlar WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $exam = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $exam]);
        } else {
            echo json_encode(['success' => false, 'message' => 'İmtahan tapılmadı']);
        }
    }
}

// Close connection
$conn->close();
?>