<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Include database connection
include('../db.php');
require_once __DIR__ . '/qr_helpers.php';

// Set header to return JSON response
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'data' => [],
    'total' => 0,
    'message' => ''
];

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$fenn = isset($_GET['fenn']) ? trim($_GET['fenn']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
$offset = ($page - 1) * $limit;

// Build the query
$where_conditions = [];
$types = '';
$params = [];
if (!empty($search)) {
    $searchLike = '%' . $search . '%';
    $where_conditions[] = "(m.username LIKE ? OR m.email LIKE ? OR u.username LIKE ?)";
    $types .= 'sss';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}
if (!empty($fenn)) {
    $where_conditions[] = "m.fenn = ?";
    $types .= 's';
    $params[] = $fenn;
}
if (!empty($status)) {
    $where_conditions[] = "m.active_status = ?";
    $types .= 's';
    $params[] = $status;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total records
$count_sql = "SELECT COUNT(*) as total
              FROM muellimler_new m
              LEFT JOIN users u ON u.u_id = m.u_id
              $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($types !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = 0;

if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    $total_records = $row['total'];
}

// Get the data
$sql = "SELECT m.id, m.u_id, m.username, m.fenn, m.active_status, m.email, m.telefon, m.tecrube, m.ise_baslama_tarixi, m.unvan, m.tehsil_ve_ixtisas, m.profile, m.qr_code, m.created_at,
               u.username AS fin_kod
        FROM muellimler_new m
        LEFT JOIN users u ON u.u_id = m.u_id
        $where_clause
        ORDER BY m.id DESC LIMIT ?, ?";
$dataTypes = $types . 'ii';
$dataParams = array_merge($params, [$offset, $limit]);
$stmt = $conn->prepare($sql);
$stmt->bind_param($dataTypes, ...$dataParams);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $teachers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        try {
            $row = qr_activate_teacher($conn, $row);
        } catch (Exception $e) {
            // QR yaradıla bilməsə də siyahıda göstər
        }
        $row = array_merge($row, qr_teacher_public_meta($row));
        if (empty($row['fin_kod']) && app_is_valid_fin_kod((string) ($row['username'] ?? ''))) {
            $row['fin_kod'] = strtoupper((string) $row['username']);
        }
        $teachers[] = $row;
    }
    
    $response = [
        'success' => true,
        'data' => $teachers,
        'total' => $total_records,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total_records / $limit)
    ];
} else {
    $response['message'] = 'Məlumatları əldə edərkən xəta baş verdi: ' . mysqli_error($conn);
}

// Return the response
echo json_encode($response);

// Close the database connection
mysqli_close($conn);
?>

