<?php
require_once __DIR__ . '/auth.php';
app_start_secure_session();

// Include database connection
include('db.php');

// Log the logout action and clean database session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = app_session_token(session_id());

    // Remove session from database
    try {
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $session_id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affected_rows > 0) {
                error_log("Session deleted from database for user ID: " . $user_id);
            } else {
                error_log("No session found in database for user ID: " . $user_id);
            }
        }
        
        // Also clean all expired sessions for this user
        $clean_stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()");
        if ($clean_stmt) {
            $clean_stmt->bind_param("i", $user_id);
            $clean_stmt->execute();
            $expired_count = $clean_stmt->affected_rows;
            $clean_stmt->close();
            
            if ($expired_count > 0) {
                error_log("Cleaned " . $expired_count . " expired sessions for user ID: " . $user_id);
            }
        }
        
        // Log the logout
        error_log("LOGOUT SUCCESS: User ID " . $user_id . " (u_id: " . ($_SESSION['u_id'] ?? 'unknown') . ") logged out successfully");
        
    } catch (Exception $e) {
        error_log("Logout database error: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = array();

// Explicitly unset u_id from session (if it exists)
if (isset($_SESSION['u_id'])) {
    unset($_SESSION['u_id']);
}

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Delete cookies related to "Remember Me" and u_id
$cookies_to_clear = ['user_login', 'user_id', 'remembered_username', 'u_id'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, "", time() - 3600, "/");
        error_log("Cleared cookie: " . $cookie);
    }
}

// Preserve the latest_username cookie (do not delete it)
if (isset($_COOKIE['latest_username'])) {
    error_log("Latest username cookie preserved: " . $_COOKIE['latest_username']);
}

// Destroy the session
session_destroy();

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page
header("Location: Login.php");
exit;
?>