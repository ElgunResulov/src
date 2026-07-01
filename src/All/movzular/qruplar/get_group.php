<?php
include('../../db.php');
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $groupId = intval($_GET['id']); // Sanitize input

    // Query to fetch group details
    $query = "SELECT id, qrup_adi, telebe_sayi, gunler, tarix FROM qruplar WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(null); // No group found
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Group ID is required']);
}

$conn->close();
?>