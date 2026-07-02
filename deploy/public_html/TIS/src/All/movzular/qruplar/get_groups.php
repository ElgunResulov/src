<?php
include('../../db.php');
header('Content-Type: application/json');

try {
    session_start();
    $u_id = $_SESSION['u_id'] ?? null;
    
    if (!$u_id) {
        echo json_encode(['error' => 'İstifadəçi ID-si tapılmadı']);
        exit;
    }
    
    $query = "
        SELECT 
            q.id, 
            q.qrup_adi, 
            q.telebe_sayi AS student_count, 
            q.created_at 
        FROM qruplar q
        WHERE q.u_id = ?
        ORDER BY q.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }

    echo json_encode($groups);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>