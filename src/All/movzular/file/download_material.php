<?php
session_start();
include('../../db.php');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Material ID is required']);
    exit;
}

$material_id = intval($_GET['id']);

// Verify table exists
$sql_check_table = "SHOW TABLES LIKE 'materiallar'";
$result_check_table = $conn->query($sql_check_table);

if ($result_check_table->num_rows === 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Table "materiallar" does not exist']);
    $conn->close();
    exit;
}

// Query to fetch material details from the database
$sql = "SELECT material_adi, file, tipi, size FROM materiallar WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("i", $material_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Material not found']);
    $stmt->close();
    $conn->close();
    exit;
}

// Fetch material details
$material = $result->fetch_assoc();
$file_path = $material['file'];
$file_name = $material['material_adi'];
$file_type = strtolower($material['tipi']);

// Check if the file exists on the server
if (!file_exists($file_path)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found on server']);
    $stmt->close();
    $conn->close();
    exit;
}

// Determine MIME type based on file extension and tipi
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mime_types = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'pdf' => 'application/pdf',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt' => 'text/plain',
    'mp4' => 'video/mp4',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'svg' => 'image/svg+xml',
    'csv' => 'text/csv'
];

// Get the original file extension
$original_extension = $extension;

// Set MIME type based on extension, fallback to tipi or default
$mime_type = isset($mime_types[$extension]) ? $mime_types[$extension] : 'application/octet-stream';

// Special handling for different file types
if ($file_type === 'image') {
    $image_extensions = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg'];
    if (in_array($extension, $image_extensions)) {
        $mime_type = $mime_types[$extension];
    } else {
        // Fallback for image tipi with unknown extension
        $mime_type = 'image/jpeg'; // Default to JPEG if extension is unclear
    }
} elseif ($file_type === 'audio' || $extension === 'mp3' || $extension === 'wav') {
    // Special handling for audio files
    if ($extension === 'mp3') {
        $mime_type = 'audio/mpeg';
    } elseif ($extension === 'wav') {
        $mime_type = 'audio/wav';
    } else {
        $mime_type = 'audio/mpeg'; // Default audio type
    }
} elseif ($file_type === 'document' && !isset($mime_types[$extension])) {
    $mime_type = 'application/pdf'; // Default for documents
} elseif ($file_type === 'video' && !isset($mime_types[$extension])) {
    $mime_type = 'video/mp4'; // Default for videos
} elseif ($file_type === 'presentation' && !isset($mime_types[$extension])) {
    $mime_type = 'application/vnd.ms-powerpoint'; // Default for presentations
}

// Ensure filename has the correct extension
$file_name_parts = pathinfo($file_name);
$file_name_without_extension = $file_name_parts['filename'];

// If the filename doesn't have an extension, add the original file extension
if (!isset($file_name_parts['extension']) || empty($file_name_parts['extension'])) {
    $file_name = $file_name_without_extension . '.' . $original_extension;
} else {
    // Always use the original file extension from the server
    $file_name = $file_name_without_extension . '.' . $original_extension;
}

// Clean the filename to remove any potentially problematic characters
$file_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_name);

// For MP3 files, ensure we're using the correct extension and MIME type
if ($extension === 'mp3') {
    $mime_type = 'audio/mpeg';
    // Make sure the filename ends with .mp3
    if (!str_ends_with(strtolower($file_name), '.mp3')) {
        $file_name = $file_name_without_extension . '.mp3';
    }
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Clear any previous output that might corrupt the file
if (ob_get_level()) {
    ob_end_clean();
}

// Read the file and output it to the browser
readfile($file_path);

// Close the database connection
$stmt->close();
$conn->close();
exit;

// Helper function for PHP < 8.0
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}
?>