<?php
require_once __DIR__ . '/security_headers.php';
require_once __DIR__ . '/auth.php';

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'intbakuc_tis';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!$conn->set_charset('utf8mb4')) {
    die("Database charset error.");
}

app_start_secure_session();

if (!empty($_SESSION['user_id'])) {
    app_validate_current_session($conn);
    app_enforce_operator_page_access();
    app_csrf_token();
    app_require_csrf();
    app_start_csrf_form_injection();
}
?>