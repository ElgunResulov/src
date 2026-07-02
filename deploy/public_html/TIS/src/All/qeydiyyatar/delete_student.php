<?php
include('../db.php'); // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the ID from POST data
    $id = isset($_POST['id']) ? $_POST['id'] : null;

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID göndərilməyib']);
        exit;
    }

    // Sanitize input to prevent SQL injection
    $id = intval($id); // Ensure the ID is an integer

    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // Fetch u_id and telebe_ad_soyad from qeydiyyatar
        $select_query = "SELECT u_id, telebe_ad_soyad FROM qeydiyyatar WHERE id = ?";
        $select_stmt = mysqli_prepare($conn, $select_query);
        if ($select_stmt) {
            mysqli_stmt_bind_param($select_stmt, 'i', $id);
            mysqli_stmt_execute($select_stmt);
            $result = mysqli_stmt_get_result($select_stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($select_stmt);

            if (!$row) {
                throw new Exception('Tələbə tapılmadı');
            }

            $u_id = $row['u_id'];
            $telebe_ad_soyad = $row['telebe_ad_soyad'];
            
            // Convert "FirstName LastName" to "FirstName.LastName" for users table
            $username = str_replace(' ', '.', $telebe_ad_soyad);
        } else {
            throw new Exception('Sorgu hazırlanarkən xəta baş verdi: ' . mysqli_error($conn));
        }

        // Delete from users where u_id matches - cascade will handle other tables
        $user_query = "DELETE FROM users WHERE u_id = ?";
        $user_stmt = mysqli_prepare($conn, $user_query);
        if ($user_stmt) {
            mysqli_stmt_bind_param($user_stmt, 's', $u_id);
            if (!mysqli_stmt_execute($user_stmt)) {
                throw new Exception('İstifadəçi silinə bilmədi: ' . mysqli_error($conn));
            }
            // Check if any rows were affected
            if (mysqli_affected_rows($conn) == 0) {
                throw new Exception('Heç bir istifadəçi silinmədi: u_id tapılmadı');
            }
            mysqli_stmt_close($user_stmt);
        } else {
            throw new Exception('İstifadəçi sorgusu hazırlanarkən xəta baş verdi: ' . mysqli_error($conn));
        }

        // Commit the transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    mysqli_close($conn);
} else {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek']);
}
?>