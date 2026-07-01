<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include('../db.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Yanlış müəllim ID-si.'
        ]);
        exit;
    }

    // Step 1: Get username and u_id from muellimler_new
    $stmt = $conn->prepare("SELECT username, u_id FROM muellimler_new WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($username, $u_id);

    if ($stmt->fetch()) {
        $stmt->close();

        // Step 2: Delete from users WHERE username and u_id match
        $stmtDel = $conn->prepare("DELETE FROM users WHERE username = ? AND u_id = ?");
        $stmtDel->bind_param("si", $username, $u_id);
        $stmtDel->execute();

        if ($stmtDel->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'İstifadəçi uğurla silindi.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'İstifadəçi tapılmadı və ya artıq silinib.'
            ]);
        }

        $stmtDel->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Müəllim tapılmadı.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Yanlış sorğu metodu.'
    ]);
}

$conn->close();
?>
