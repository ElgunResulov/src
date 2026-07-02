<?php
include('db.php');
app_require_auth($conn);
app_require_role(['super_admin', 'admin']);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $user_id = intval($_POST['id']);

    // Everyone (admin & super_admin) can now delete ANY user just by ID
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // If current user deleted himself/herself → logout
            if ($user_id === $_SESSION['user_id']) {
                session_destroy();
                echo "<script>window.location.href = 'Login.php';</script>";
                exit();
            }
            echo "İstifadəçi uğurla silindi!";
        } else {
            header("HTTP/1.1 404 Not Found");
            echo "İstifadəçi tapılmadı!";
        }
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Xəta baş verdi: " . $stmt->error;
    }

    $stmt->close();
} else {
    header("HTTP/1.1 400 Bad Request");
    echo "Yanlış sorğu!";
}

$conn->close();
?>