<?php
// get_elanlar_count.php

// Include your database connection file
require_once '../db.php'; // Adjust the path as needed

// Query to count active announcements
$query = "SELECT COUNT(*) as total FROM elanlar WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$elanlar_count = $row['total'];
$stmt->close();

// Return the count as JSON
header('Content-Type: application/json');
echo json_encode(['count' => $elanlar_count]);
?>