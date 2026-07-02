<?php
require_once '../db.php';

header('Content-Type: text/html; charset=utf-8');

// Initialize response
$response = ['status' => 'error', 'message' => 'An error occurred.'];

// Check database connection
if (!$conn) {
    $response['message'] = 'Database connection failed.';
    error_log('Database connection failed in telebeler-edit.php');
    echo json_encode($response);
    exit;
}

// Sanitize and validate input
function sanitize_input($data) {
    global $conn;
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Collect and validate form data
$studentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($studentId <= 0) {
    $response['message'] = 'Invalid student ID.';
    echo json_encode($response);
    exit;
}

$firstName = ucwords(sanitize_input($_POST['firstName'] ?? ''));
$lastName = ucwords(sanitize_input($_POST['lastName'] ?? ''));

// Combine firstName and lastName with a dot ('.') to create username
$username = $firstName . '.' . $lastName;
// Create full name for qeydiyyatar table
$fullName = $firstName . ' ' . $lastName;

if (empty($firstName) || empty($lastName)) {
    $response['message'] = 'First name and last name are required.';
    echo json_encode($response);
    exit;
}

$email = sanitize_input($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email address.';
    echo json_encode($response);
    exit;
}

$phone = sanitize_input($_POST['phone'] ?? '');
$qebul_tarixi = sanitize_input($_POST['qebul_tarixi'] ?? '');
$dogum_tarixi = sanitize_input($_POST['dogum_tarixi'] ?? '');
$years = sanitize_input($_POST['yas'] ?? '');
$address = sanitize_input($_POST['address'] ?? '');
$ata = sanitize_input($_POST['ata'] ?? '');
$elaqe_nomre_ata = sanitize_input($_POST['elaqe_nomre_ata'] ?? '');
$ana = sanitize_input($_POST['ana'] ?? '');
$elaqe_nomre_ana = sanitize_input($_POST['elaqe_nomre_ana'] ?? '');
$class = sanitize_input($_POST['class'] ?? '');
$muellim_adi = sanitize_input($_POST['muellim'] ?? '');

// Map gender to database value
$gender = sanitize_input($_POST['gender'] ?? '');
if ($gender === 'male') {
    $cins = '0';
} elseif ($gender === 'female') {
    $cins = '1';
} else {
    $response['message'] = 'Please select a valid gender (Male or Female).';
    error_log("Invalid gender value received: '$gender' in telebeler-edit.php");
    echo json_encode($response);
    exit;
}

// Validate status
$status = sanitize_input($_POST['status'] ?? '');
$validStatuses = ['active', 'inactive', 'graduate'];
if (!in_array($status, $validStatuses)) {
    $response['message'] = 'Invalid status value.';
    echo json_encode($response);
    exit;
}

// Handle file upload
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Generate a unique file name
    $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $new_file_name = uniqid() . '.' . $file_extension;

    // Move the uploaded file to the uploads directory
    $upload_dir = 'Uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_file_name)) {
        $photoPath = $upload_dir . $new_file_name;
    } else {
        $response['message'] = 'Failed to upload photo.';
        error_log('Photo upload failed in telebeler-edit.php');
        echo json_encode($response);
        exit;
    }
}

// Prepare SQL query for updating the telebeler table
$sql_telebeler = "UPDATE telebeler SET 
        username = ?, 
        number = ?, 
        poct = ?, 
        active_status = ?, 
        dogum_tarixi = ?, 
        years = ?, 
        cins = ?, 
        unvan = ?, 
        sinif = ?, 
        qebul_tarixi = ?, 
        ata = ?, 
        elaqe_nomre_ata = ?, 
        ana = ?, 
        elaqe_nomre_ana = ?, 
        muellim_adi = ?";
$params_telebeler = [
    $username, 
    $phone, 
    $email, 
    $status, 
    $dogum_tarixi, 
    $years, 
    $cins, 
    $address, 
    $class, 
    $qebul_tarixi, 
    $ata, 
    $elaqe_nomre_ata, 
    $ana, 
    $elaqe_nomre_ana, 
    $muellim_adi
];
$types_telebeler = "sssssisssssssss";

if ($photoPath !== null) {
    $sql_telebeler .= ", photo = ?";
    $params_telebeler[] = $photoPath;
    $types_telebeler .= "s";
}

$sql_telebeler .= " WHERE id = ?";
$params_telebeler[] = $studentId;
$types_telebeler .= "i";

try {
    // Start transaction
    $conn->begin_transaction();

    // Update telebeler table
    $stmt_telebeler = $conn->prepare($sql_telebeler);
    if ($stmt_telebeler) {
        $stmt_telebeler->bind_param($types_telebeler, ...$params_telebeler);
        if (!$stmt_telebeler->execute()) {
            throw new Exception('Failed to update telebeler table: ' . $stmt_telebeler->error);
        }
        $stmt_telebeler->close();
    } else {
        throw new Exception('Query preparation failed for telebeler table: ' . $conn->error);
    }

    // Get u_id from telebeler table first
    $sql_get_uid = "SELECT u_id FROM telebeler WHERE id = ?";
    $stmt_get_uid = $conn->prepare($sql_get_uid);
    if ($stmt_get_uid) {
        $stmt_get_uid->bind_param("i", $studentId);
        if (!$stmt_get_uid->execute()) {
            throw new Exception('Failed to get u_id from telebeler table: ' . $stmt_get_uid->error);
        }
        $result = $stmt_get_uid->get_result();
        $row = $result->fetch_assoc();
        $u_id = $row['u_id'];
        $stmt_get_uid->close();
    } else {
        throw new Exception('Query preparation failed for getting u_id: ' . $conn->error);
    }

    // Update qeydiyyatar table - update telebe_ad_soyad where u_id matches
    $sql_qeydiyyatar = "UPDATE qeydiyyatar SET telebe_ad_soyad = ? WHERE u_id = ?";
    $stmt_qeydiyyatar = $conn->prepare($sql_qeydiyyatar);
    if ($stmt_qeydiyyatar) {
        $stmt_qeydiyyatar->bind_param("ss", $fullName, $u_id);
        if (!$stmt_qeydiyyatar->execute()) {
            throw new Exception('Failed to update qeydiyyatar table: ' . $stmt_qeydiyyatar->error);
        }
        $stmt_qeydiyyatar->close();
    } else {
        throw new Exception('Query preparation failed for qeydiyyatar table: ' . $conn->error);
    }

    // Update users table - update username where u_id matches
    $sql_users = "UPDATE users SET username = ? WHERE u_id = ?";
    $stmt_users = $conn->prepare($sql_users);
    if ($stmt_users) {
        $stmt_users->bind_param("ss", $username, $u_id);
        if (!$stmt_users->execute()) {
            throw new Exception('Failed to update users table: ' . $stmt_users->error);
        }
        $stmt_users->close();
    } else {
        throw new Exception('Query preparation failed for users table: ' . $conn->error);
    }

    // Commit transaction
    $conn->commit();

    $response['status'] = 'success';
    $response['message'] = 'Student updated successfully in all tables.';
    header('Location: ../Tələbələr.php');
    exit;
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['message'] = $e->getMessage();
    error_log('Error in telebeler-edit.php: ' . $e->getMessage());
} finally {
    $conn->close();
    echo json_encode($response);
}
?>