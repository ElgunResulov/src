<?php
// Include database connection
include('db.php');
app_require_auth_api($conn);

// Function to get exam statistics
function getExamStatistics() {
    global $conn;
    
    $statistics = [];
    
    // Total exams
    $sql = "SELECT COUNT(*) as total FROM i̇mtahanlar_maybe WHERE active_status = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['total_exams'] = $row['total'];
    
    // Exams this month
    $sql = "SELECT COUNT(*) as total FROM i̇mtahanlar_maybe WHERE active_status = 1 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['exams_this_month'] = $row['total'];
    
    // Completed exams
    $sql = "SELECT COUNT(*) as total FROM i̇mtahanlar_maybe WHERE active_status = 1 AND status = 'Tamamlanıb'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['completed_exams'] = $row['total'];
    
    // Completed exams last month
    $sql = "SELECT COUNT(*) as total FROM i̇mtahanlar_maybe WHERE active_status = 1 AND status = 'Tamamlanıb' AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['completed_exams_last_month'] = $row['total'];
    
    // Average score
    $sql = "SELECT AVG(orta_bal) as avg_score FROM i̇mtahanlar_maybe WHERE active_status = 1 AND orta_bal IS NOT NULL";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['average_score'] = $row['avg_score'] ? round($row['avg_score'], 1) : 0;
    
    // Average score last year
    $sql = "SELECT AVG(orta_bal) as avg_score FROM i̇mtahanlar_maybe WHERE active_status = 1 AND orta_bal IS NOT NULL AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 YEAR))";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['average_score_last_year'] = $row['avg_score'] ? round($row['avg_score'], 1) : 0;
    
    // Upcoming exams
    $sql = "SELECT COUNT(*) as total FROM i̇mtahanlar_maybe WHERE active_status = 1 AND status = 'Gələcək'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['upcoming_exams'] = $row['total'];
    
    // Upcoming exams this week
    $sql = "SELECT COUNT(*) as total FROM i̇mtahanlar_maybe WHERE active_status = 1 AND status = 'Gələcək' AND YEARWEEK(tarix, 1) = YEARWEEK(CURDATE(), 1)";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $statistics['upcoming_exams_this_week'] = $row['total'];
    
    return $statistics;
}

// If this file is accessed directly via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_statistics') {
    $statistics = getExamStatistics();
    echo json_encode(['success' => true, 'data' => $statistics]);
}

?>