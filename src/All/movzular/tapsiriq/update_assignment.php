<?php
include('../../db.php');

// Helper function to handle errors
function dieWithError($message) {
    // Log the error for debugging
    error_log("Assignment Update Error: " . $message);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Helper function to handle success
function dieWithSuccess($message, $data = null) {
    $response = ['status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dieWithError("Invalid request method");
}

// Validate required fields
$requiredFields = ['assignmentId', 'assignmentName', 'assignmentTopic', 'assignmentGroup', 'assignmentDeadline'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        dieWithError("Missing required field: $field");
    }
}

// Collect and sanitize form data
$assignmentId = intval($_POST['assignmentId']);
$assignmentName = $_POST['assignmentName'];
$topicId = intval($_POST['assignmentTopic']);
$groupId = intval($_POST['assignmentGroup']);

// Get the rich text content from the form - DO NOT sanitize HTML content
$description = isset($_POST['assignmentDescription']) ? $_POST['assignmentDescription'] : '';
$deadline = $_POST['assignmentDeadline'];

// Validate specific fields
if (!is_numeric($assignmentId) || !is_numeric($topicId) || !is_numeric($groupId)) {
    dieWithError('Invalid ID values');
}

if (!strtotime($deadline)) {
    dieWithError('Invalid deadline format');
}

// Get existing files
$stmt = $conn->prepare("SELECT fayllar FROM tapsiriqlar WHERE id = ?");
$stmt->bind_param("i", $assignmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    dieWithError("Assignment not found");
}

$row = $result->fetch_assoc();
$existingFiles = !empty($row['fayllar']) ? json_decode($row['fayllar'], true) : [];

// Handle file removals if specified
if (isset($_POST['removedFiles']) && !empty($_POST['removedFiles'])) {
    $removedFiles = json_decode($_POST['removedFiles'], true);
    if (is_array($removedFiles)) {
        foreach ($removedFiles as $fileToRemove) {
            // Remove from existingFiles array
            $existingFiles = array_filter($existingFiles, function($file) use ($fileToRemove) {
                return $file !== $fileToRemove;
            });
            
            // Optionally delete the physical file
            $fullPath = '../../' . $fileToRemove;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}

// Handle new file uploads
$uploadedFiles = [];
$uploadDir = '../../uploads/'; // Ensure this path is correct
$allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if (!empty($_FILES['assignmentFiles']['name'][0])) {
    // Create upload directory if it doesn't exist
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
            $relativePath = 'uploads/' . $newFileName;
            $uploadedFiles[] = $relativePath;
        } else {
            dieWithError('Failed to move uploaded file');
        }
    }
}

// Combine existing and new files
$allFiles = array_merge($existingFiles, $uploadedFiles);
$filesJson = !empty($allFiles) ? json_encode($allFiles) : null;

// Update database
$sql = "UPDATE tapsiriqlar SET ad = ?, movzu = ?, qrup = ?, tesvir = ?, son_tarix = ?, fayllar = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    dieWithError('Database prepare error: ' . $conn->error);
}

$stmt->bind_param('siisssi', 
    $assignmentName, 
    $topicId, 
    $groupId, 
    $description, 
    $deadline, 
    $filesJson, 
    $assignmentId
);

if ($stmt->execute()) {
    dieWithSuccess('Tapşırıq uğurla yeniləndi');
} else {
    dieWithError('Database error: ' . $stmt->error);
}

// Clean up
$stmt->close();
$conn->close();
?>
