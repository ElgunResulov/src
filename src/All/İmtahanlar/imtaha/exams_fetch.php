<?php
include('../../db.php');

// Ensure database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error, 3, '/path/to/error.log');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10; // Number of records per page
$offset = ($page - 1) * $per_page;

// Fetch total number of exams
$total_query = "SELECT COUNT(*) as total FROM imtahanlar_exam";
$total_result = $conn->query($total_query);
if (!$total_result) {
    error_log("Total query failed: " . $conn->error, 3, '/path/to/error.log');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch exam count']);
    exit;
}
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Fetch exams for the current page, formatting exam_date to exclude seconds
$sql = "SELECT id, exam_name, fenn_adi, description, 
               DATE_FORMAT(exam_date, '%Y-%m-%d %H:%i') as exam_date, 
               duration, passing_score, groups, questions, status, created_at 
        FROM imtahanlar_exam 
        ORDER BY exam_date DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error, 3, '/path/to/error.log');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare query']);
    exit;
}
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$exams = [];
while ($row = $result->fetch_assoc()) {
    // Translate status to Azerbaijani
    switch ($row['status']) {
        case 'upcoming':
            $row['status'] = 'Gələcək';
            $badge_class = 'bg-primary';
            break;
        case 'completed':
            $row['status'] = 'Tamamlanmış';
            $badge_class = 'bg-success';
            break;
        case 'active':
            $row['status'] = 'Aktiv';
            $badge_class = 'bg-warning';
            break;
        default:
            $row['status'] = 'Bilinmir';
            $badge_class = 'bg-secondary';
    }
    $row['badge_class'] = $badge_class;
    $exams[] = $row;
}

$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'exams' => $exams,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_rows' => $total_rows
]);
?>