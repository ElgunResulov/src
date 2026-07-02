<?php
include('../../db.php');

// Handle form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $groupId = intval($_POST["id"]);
    $groupName = trim($_POST["groupName"]);
    $studentCount = intval($_POST["telebe_sayi"]);
    $tarix = trim($_POST["tarix"]);
    $gunler = trim($_POST["gunler"]);

    // Validate inputs
    if (empty($groupName) || empty($tarix) || empty($gunler)) {
        echo "error: Bütün sahələr doldurulmalıdır.";
        exit;
    }

    // Update data in the database
    $query = "UPDATE qruplar SET qrup_adi = ?, telebe_sayi = ?, gunler = ?, tarix = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sisss", $groupName, $studentCount, $gunler, $tarix, $groupId);

    if ($stmt->execute()) {
        echo "success"; // Indicate success
    } else {
        echo "error: " . $stmt->error; // Return error message
    }

    $stmt->close();
}

$conn->close();
?>