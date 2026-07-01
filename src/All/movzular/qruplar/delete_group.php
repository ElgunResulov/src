<?php
include('../../db.php');

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    // Parse the query string parameters
    $queryParams = $_SERVER['QUERY_STRING'];
    parse_str($queryParams, $data); // Parse the query string into an array

    $groupId = intval($data['id']); // Sanitize input

    if ($groupId > 0) {
        // Delete group from the database
        $stmt = $conn->prepare("DELETE FROM qruplar WHERE id = ?");
        $stmt->bind_param("i", $groupId);

        if ($stmt->execute()) {
            echo "success"; // Indicate success
        } else {
            echo "error: " . $stmt->error; // Return error message
        }

        $stmt->close();
    } else {
        echo "error: Geçersiz ID.";
    }
} else {
    echo "error: DELETE isteği göndərilməlidir.";
}

$conn->close();
?>