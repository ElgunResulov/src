<?php
session_start();
include('../../db.php');
error_log(print_r($_POST, true));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['u_id'])) {
        echo "error: İstifadəçi daxil olmayıb. Zəhmət olmasa daxil olun.";
        exit;
    }

    $u_id = $_SESSION['u_id'];

    $stmt = $conn->prepare("SELECT u_id FROM users WHERE u_id = ?");
    $stmt->bind_param("i", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "error: İstifadəçi tapılmadı.";
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    error_log("Verified u_id: " . $u_id);

    $groupName = trim($_POST["groupName"]);
    $studentCount = intval($_POST["telebe_sayi"]);
    $tarix = trim($_POST["tarix"]);
    $gunler = trim($_POST["gunler"]);
    $createdAt = date("Y-m-d H:i:s");

    error_log("Received tarix: " . $tarix);

    if (empty($groupName) || empty($tarix) || empty($gunler)) {
        echo "error: Bütün sahələr doldurulmalıdır.";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO qruplar (qrup_adi, telebe_sayi, gunler, tarix, created_at, u_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissss", $groupName, $studentCount, $gunler, $tarix, $createdAt, $u_id);

    if (!$stmt->execute()) {
        error_log("SQL Error: " . $stmt->error);
        echo "error: " . $stmt->error;
    } else {
        echo "success";
    }

    $stmt->close();
}

$conn->close();
?>