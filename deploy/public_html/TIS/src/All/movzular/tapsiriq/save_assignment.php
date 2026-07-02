<?php
session_start();
include('../../db.php');

function dieWithError($message) {
    error_log("Assignment Error: " . $message);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function dieWithSuccess($message, $data = null) {
    $response = ['status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

$u_id = $_SESSION['u_id'] ?? null;
if (!$u_id) {
    dieWithError("User not authenticated");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dieWithError("Invalid request method");
}

$requiredFields = ['assignmentName', 'assignmentTopic', 'assignmentGroup', 'assignmentDeadline'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        dieWithError("Missing required field: $field");
    }
}

$assignmentName = $_POST['assignmentName'];
$topicId = intval($_POST['assignmentTopic']);
$groupId = intval($_POST['assignmentGroup']);

$description = isset($_POST['assignmentDescription']) ? $_POST['assignmentDescription'] : '';

error_log("Description content: " . $description);

$deadline = $_POST['assignmentDeadline'];

if (!is_numeric($topicId) || !is_numeric($groupId)) {
    dieWithError('Invalid topic or group ID');
}

if (!strtotime($deadline)) {
    dieWithError('Invalid deadline format');
}

$uploadedFiles = [];
$uploadDir = '../../Uploads/';
$allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
$maxFileSize = 5 * 1024 * 1024; 

if (!empty($_FILES['assignmentFiles']['name'][0])) {
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            dieWithError('Failed to create upload directory');
        }
    }

    foreach ($_FILES['assignmentFiles']['name'] as $key => $fileName) {
        if ($_FILES['assignmentFiles']['error'][$key] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $fileTmpPath = $_FILES['assignmentFiles']['tmp_name'][$key];
        $fileError = $_FILES['assignmentFiles']['error'][$key];
        $fileSize = $_FILES['assignmentFiles']['size'][$key];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $safeFileName = preg_replace("/[^a-zA-Z0-9_.-]/", "_", pathinfo($fileName, PATHINFO_FILENAME));

        if ($fileError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            dieWithError($errorMessages[$fileError] ?? 'Unknown upload error');
        }

        if ($fileSize > $maxFileSize) {
            dieWithError('File size exceeds 5MB');
        }

        if (!in_array($fileExtension, $allowedExtensions)) {
            dieWithError('Invalid file type');
        }

        $newFileName = $safeFileName . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $filePath)) {
            $relativePath = 'Uploads/' . $newFileName;
            $uploadedFiles[] = $relativePath;
        } else {
            dieWithError('Failed to move uploaded file');
        }
    }
}

$filesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles) : null;
$currentTimestamp = date('Y-m-d H:i:s');

$sql = "INSERT INTO tapsiriqlar (ad, movzu, qrup, tesvir, son_tarix, yaradilma_tarixi, fayllar, created_at, u_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    dieWithError('Database prepare error: ' . $conn->error);
}

$stmt->bind_param('siissssss', 
    $assignmentName, 
    $topicId, 
    $groupId, 
    $description, 
    $deadline, 
    $currentTimestamp, 
    $filesJson, 
    $currentTimestamp,
    $u_id
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    dieWithSuccess('Tapşırıq uğurla əlavə edildi', ['id' => $newId]);
} else {
    dieWithError('Database error: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>