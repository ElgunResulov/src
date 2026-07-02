<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // First, get the u_id from telebeler table
        $select_query = "SELECT u_id FROM telebeler WHERE id = ?";
        $select_stmt = mysqli_prepare($conn, $select_query);
        mysqli_stmt_bind_param($select_stmt, 'i', $id);
        mysqli_stmt_execute($select_stmt);
        $result = mysqli_stmt_get_result($select_stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $u_id = $row['u_id'];

            // Delete from telebeler
            $delete_telebeler_query = "DELETE FROM telebeler WHERE id = ?";
            $delete_telebeler_stmt = mysqli_prepare($conn, $delete_telebeler_query);
            mysqli_stmt_bind_param($delete_telebeler_stmt, 'i', $id);
            
            if (!mysqli_stmt_execute($delete_telebeler_stmt)) {
                throw new Exception("Error deleting from telebeler: " . mysqli_error($conn));
            }
            mysqli_stmt_close($delete_telebeler_stmt);

            // Delete from users
            $delete_users_query = "DELETE FROM users WHERE u_id = ?";
            $delete_users_stmt = mysqli_prepare($conn, $delete_users_query);
            mysqli_stmt_bind_param($delete_users_stmt, 's', $u_id);
            
            if (!mysqli_stmt_execute($delete_users_stmt)) {
                throw new Exception("Error deleting from users: " . mysqli_error($conn));
            }
            mysqli_stmt_close($delete_users_stmt);

            // Commit the transaction
            mysqli_commit($conn);
            echo "success";
        } else {
            // No record found in telebeler
            mysqli_rollback($conn);
            echo "error: No student found with ID $id";
        }
        
        mysqli_stmt_close($select_stmt);
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($conn);
        echo "error: " . $e->getMessage();
    }
} else {
    echo "error: Invalid request";
}

mysqli_close($conn);
?>