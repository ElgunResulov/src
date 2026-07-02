<?php
session_start();
include('../db.php');

ini_set('display_errors', 0); // Disable in production
ini_set('log_errors', 1);
error_reporting(E_ALL);

ob_start();
header('Content-Type: application/json');

function sendError($message) {
    error_log($message);
    echo json_encode(['status' => 'error', 'message' => $message]);
    global $stmt, $conn;
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    ob_end_flush();
    exit;
}

// Check if username exists in session
if (!isset($_SESSION['username'])) {
    sendError('No user logged in: Session username not set');
}

if (!$conn || $conn->connect_error) {
    sendError('Database connection failed: ' . ($conn ? $conn->connect_error : 'Connection not initialized'));
}

// Fetch u_id from users table based on session username
$username = trim($_SESSION['username']); // Trim to avoid whitespace issues
error_log("Fetching u_id for username: $username");
$query = "SELECT u_id FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    sendError('Failed to prepare user query: ' . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError('User not found in users table for username: ' . $username);
}
$user = $result->fetch_assoc();
$u_id = $user['u_id'];
error_log("Found u_id: $u_id");
$stmt->close();

// Validate required POST fields
$required_fields = [
    'imtahan_id', 'dogru_cavablar', 'sehv_cavablar',
    'umumui_sual_sayi', 'faiz', 'kecid_statusu', 'cavablar',
    'baslama_vaxti', 'bitme_vaxti'
];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        sendError("Missing required field: $field");
    }
}

// Validate and process POST data
$imtahan_id = filter_var($_POST['imtahan_id'], FILTER_VALIDATE_INT);
$telebe_adi = trim($_SESSION['username']); // Use exact session username (e.g., Kasha.Malasha)
$dogru_cavablar = filter_var($_POST['dogru_cavablar'], FILTER_VALIDATE_INT);
$sehv_cavablar = filter_var($_POST['sehv_cavablar'], FILTER_VALIDATE_INT);
$umumui_sual_sayi = filter_var($_POST['umumui_sual_sayi'], FILTER_VALIDATE_INT);
$faiz = filter_var($_POST['faiz'], FILTER_VALIDATE_FLOAT);
$kecid_statusu = in_array($_POST['kecid_statusu'], ['Keçdi', 'Kəsildi']) ? $_POST['kecid_statusu'] : null;
$cavablar = json_decode($_POST['cavablar'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('Invalid JSON in cavablar: ' . json_last_error_msg());
}
$baslama_vaxti = DateTime::createFromFormat('Y-m-d\TH:i:s.uO', $_POST['baslama_vaxti']);
if (!$baslama_vaxti) {
    sendError('Invalid baslama_vaxti format: ' . $_POST['baslama_vaxti']);
}
$bitme_vaxti = DateTime::createFromFormat('Y-m-d\TH:i:s.uO', $_POST['bitme_vaxti']);
if (!$bitme_vaxti) {
    sendError('Invalid bitme_vaxti format: ' . $_POST['bitme_vaxti']);
}

if ($imtahan_id === false || $dogru_cavablar === false ||
    $sehv_cavablar === false || $umumui_sual_sayi === false || $faiz === false ||
    !$kecid_statusu) {
    sendError('Invalid input data: Check integer/float values or kecid_statusu');
}

$telebe_adi = $telebe_adi ?: 'unknown_user'; // Fallback if username is empty
$baslama_vaxti = $baslama_vaxti->format('Y-m-d H:i:s');
$bitme_vaxti = $bitme_vaxti->format('Y-m-d H:i:s');
$company_id = isset($_POST['company_id']) ? filter_var($_POST['company_id'], FILTER_VALIDATE_INT) : null;

// Log all data for debugging
error_log("Inserting data: u_id=$u_id, company_id=" . ($company_id ?? 'NULL') . ", imtahan_id=$imtahan_id, " .
          "telebe_adi=$telebe_adi, dogru_cavablar=$dogru_cavablar, sehv_cavablar=$sehv_cavablar, " .
          "umumui_sual_sayi=$umumui_sual_sayi, faiz=$faiz, kecid_statusu=$kecid_statusu, " .
          "cavablar=" . $_POST['cavablar'] . ", baslama_vaxti=$baslama_vaxti, bitme_vaxti=$bitme_vaxti");

// Insert into imtahan_neticeler
$query = "INSERT INTO imtahan_neticeler (
    u_id, company_id, imtahan_id, telebe_adi, dogru_cavablar, sehv_cavablar,
    umumui_sual_sayi, faiz, kecid_statusu, cavablar, baslama_vaxti, bitme_vaxti, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($query);
if (!$stmt) {
    sendError('Database prepare failed: ' . $conn->error);
}

$stmt->bind_param(
    "sisssidsssss",
    $u_id,
    $company_id,
    $imtahan_id,
    $telebe_adi,
    $dogru_cavablar,
    $sehv_cavablar,
    $umumui_sual_sayi,
    $faiz,
    $kecid_statusu,
    $_POST['cavablar'],
    $baslama_vaxti,
    $bitme_vaxti
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Results saved successfully']);
} else {
    sendError('Database insert failed: ' . $stmt->error);
}

$stmt->close();
$conn->close();
ob_end_flush();
?>