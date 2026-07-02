<?php
include('../db.php');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the country name from POST data
    $country_name = trim($_POST['country_name'] ?? '');

    if ($country_name === '') {
        echo "Country name is required.";
        exit;
    }

    // Get current timestamp
    $created_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO vetandasliq (country_name, created_at) VALUES (?, ?)");
    $stmt->bind_param("ss", $country_name, $created_at);

    if ($stmt->execute()) {
        // Success message (optional)
        echo "Country added successfully.";
        
        // Redirect to another page (replace 'yourpage.php' with your target page)
        echo "<script>window.location.href = '../Qeydiyyatar.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    // Close the database connection
    mysqli_close($conn);
}
?>
