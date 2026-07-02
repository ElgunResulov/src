<?php
$upload_dir = 'uploads/profiles/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "Directory created successfully: " . $upload_dir;
    } else {
        echo "Failed to create directory: " . $upload_dir;
        echo "<br>Error: " . error_get_last()['message'];
    }
} else {
    echo "Directory already exists: " . $upload_dir;
    
    // Check if directory is writable
    if (is_writable($upload_dir)) {
        echo "<br>Directory is writable";
    } else {
        echo "<br>Directory is not writable";
        
        // Try to make it writable
        if (chmod($upload_dir, 0777)) {
            echo "<br>Successfully changed permissions to 0777";
        } else {
            echo "<br>Failed to change permissions";
            echo "<br>Error: " . error_get_last()['message'];
        }
    }
}

// Try to create a test file
$test_file = $upload_dir . 'test.txt';
if (file_put_contents($test_file, 'This is a test file')) {
    echo "<br>Successfully created test file: " . $test_file;
    
    // Delete the test file
    if (unlink($test_file)) {
        echo "<br>Successfully deleted test file";
    } else {
        echo "<br>Failed to delete test file";
        echo "<br>Error: " . error_get_last()['message'];
    }
} else {
    echo "<br>Failed to create test file";
    echo "<br>Error: " . error_get_last()['message'];
}
?>

