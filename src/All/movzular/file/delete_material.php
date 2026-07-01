<?php
header('Content-Type: application/json');
include('../../db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = (int) $_POST['id'];

    $stmt = $conn->prepare("SELECT file FROM materiallar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $file_path = $row['file'];

        $delete_stmt = $conn->prepare("DELETE FROM materiallar WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        if ($delete_stmt->execute()) {
            // Delete file if it exists
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }
            echo json_encode(['success' => true, 'message' => 'Material silindi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Məlumat silinərkən xəta: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Material tapılmadı']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Düzgün sorğu göndərilməyib']);
}

mysqli_close($conn);
?>