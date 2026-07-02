<?php
require_once __DIR__ . '/../auth.php';
app_start_secure_session();
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check authentication and CSRF
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized: Please log in']);
    exit();
}

if (!app_validate_csrf_token(app_request_csrf_token())) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit();
}

// Include database connection
$db_path = __DIR__ . '/../db.php';
if (!file_exists($db_path)) {
    echo json_encode(['error' => 'Server error: Database configuration file missing']);
    exit();
}
require $db_path;

try {
    $file = $_FILES['importFile']['tmp_name'];
    $handle = fopen($file, 'r');
    if (!$handle) {
        throw new Exception('Unable to open CSV file');
    }

    // Skip header row
    fgetcsv($handle);

    $query = "INSERT INTO emekdaslar (ad_soyad, sobe, vezife, email, telefon, ise_baslama_tarixi, status, unvan, tehsil, is_tecrubesi) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }

    $count = 0;
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 10) {
            continue; // Skip invalid rows
        }

        $ad_soyad = htmlspecialchars(trim($data[0]), ENT_QUOTES, 'UTF-8');
        $sobe = htmlspecialchars(trim($data[1]), ENT_QUOTES, 'UTF-8');
        $vezife = htmlspecialchars(trim($data[2]), ENT_QUOTES, 'UTF-8');
        $email = filter_var($data[3], FILTER_SANITIZE_EMAIL);
        $telefon = htmlspecialchars(trim($data[4]), ENT_QUOTES, 'UTF-8');
        $ise_baslama_tarixi = htmlspecialchars(trim($data[5]), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars(trim($data[6]), ENT_QUOTES, 'UTF-8');
        $unvan = htmlspecialchars(trim($data[7]), ENT_QUOTES, 'UTF-8');
        $tehsil = htmlspecialchars(trim($data[8]), ENT_QUOTES, 'UTF-8');
        $is_tecrubesi = htmlspecialchars(trim($data[9]), ENT_QUOTES, 'UTF-8');

        $stmt->bind_param('ssssssssss', $ad_soyad, $sobe, $vezife, $email, $telefon, $ise_baslama_tarixi, $status, $unvan, $tehsil, $is_tecrubesi);

        if ($stmt->execute()) {
            $count++;
        }
    }

    fclose($handle);
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'imported' => $count]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: Unable to import employees', 'details' => $e->getMessage()]);
    if (isset($conn) && $conn) {
        $conn->close();
    }
    exit();
}
?>