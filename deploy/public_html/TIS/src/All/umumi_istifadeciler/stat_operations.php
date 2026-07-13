<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['super_admin', 'admin'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Giriş icazəsi yoxdur.']);
    $conn->close();
    exit;
}

$type = trim((string) ($_GET['type'] ?? ''));
$companyId = (int) ($_SESSION['company_id'] ?? 0);

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'teacher' => 'Müəllim',
    'student' => 'Tələbə',
    'parent' => 'Valideyn',
    'staff' => 'Əməkdaş',
    'examiner' => 'İmtahan nəzarətçisi',
    'operator' => 'Operator',
];

$columns = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'username', 'label' => 'İstifadəçi adı'],
    ['key' => 'role_label', 'label' => 'Rol'],
    ['key' => 'u_id', 'label' => 'U-ID'],
    ['key' => 'company_id', 'label' => 'Şirkət ID'],
    ['key' => 'created_at', 'label' => 'Yaradılma tarixi'],
];

$baseSelect = "SELECT id, username, role, u_id, company_id, created_at FROM users";
$orderBy = " ORDER BY created_at DESC";
$where = '';
$params = [];
$types = '';

if ($role === 'admin') {
    $where = " WHERE company_id = ?";
    $params[] = $companyId;
    $types = 'i';
}

switch ($type) {
    case 'all':
        $query = $baseSelect . $where . $orderBy;
        break;

    case 'teachers':
        $query = $baseSelect . ($where ? $where . " AND role = 'teacher'" : " WHERE role = 'teacher'") . $orderBy;
        break;

    case 'students':
        $query = $baseSelect . ($where ? $where . " AND role = 'student'" : " WHERE role = 'student'") . $orderBy;
        break;

    case 'others':
        $query = $baseSelect . ($where ? $where . " AND role NOT IN ('teacher', 'student')" : " WHERE role NOT IN ('teacher', 'student')") . $orderBy;
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Yanlış statistik tipi.']);
        $conn->close();
        exit;
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . $conn->error]);
    $conn->close();
    exit;
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $userRole = (string) ($row['role'] ?? '');
    $row['role_label'] = $roleLabels[$userRole] ?? $userRole;

    foreach ($row as $key => $value) {
        if ($value === null || $value === '') {
            $row[$key] = '-';
        }
    }
    $rows[] = $row;
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'type' => $type,
    'columns' => $columns,
    'data' => $rows,
]);

$conn->close();
