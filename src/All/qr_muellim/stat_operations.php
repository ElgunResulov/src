<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['role'] ?? '';
$teacherUsername = trim((string) ($_SESSION['username'] ?? ''));

if ($role !== 'teacher' || $teacherUsername === '') {
    echo json_encode(['status' => 'error', 'message' => 'Yalnız müəllimlər üçün əlçatandır.']);
    $conn->close();
    exit;
}

$type = trim((string) ($_GET['type'] ?? ''));
$today = date('Y-m-d');
$weekStart = trim((string) ($_GET['week_start'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart)) {
    $weekStart = date('Y-m-d', strtotime('monday this week'));
}
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

$columns = [];
$query = '';
$types = '';
$params = [];

switch ($type) {
    case 'today_scans':
        $query = "SELECT student_username, student_u_id, scan_time, lesson_count, scan_date
                  FROM qr_scans
                  WHERE teacher_username = ? AND DATE(scan_date) = ?
                  ORDER BY scan_time ASC";
        $types = 'ss';
        $params = [$teacherUsername, $today];
        $columns = [
            ['key' => 'student_username', 'label' => 'Tələbə'],
            ['key' => 'student_u_id', 'label' => 'U-ID'],
            ['key' => 'scan_time', 'label' => 'Skan vaxtı'],
            ['key' => 'lesson_count', 'label' => 'Dərs sayı'],
        ];
        break;

    case 'today_students':
        $query = "SELECT student_username,
                         MAX(student_u_id) AS student_u_id,
                         MIN(scan_time) AS first_scan,
                         MAX(scan_time) AS last_scan,
                         COUNT(*) AS scan_count,
                         SUM(lesson_count) AS total_lessons
                  FROM qr_scans
                  WHERE teacher_username = ? AND DATE(scan_date) = ?
                  GROUP BY student_username
                  ORDER BY student_username ASC";
        $types = 'ss';
        $params = [$teacherUsername, $today];
        $columns = [
            ['key' => 'student_username', 'label' => 'Tələbə'],
            ['key' => 'student_u_id', 'label' => 'U-ID'],
            ['key' => 'first_scan', 'label' => 'İlk skan'],
            ['key' => 'last_scan', 'label' => 'Son skan'],
            ['key' => 'scan_count', 'label' => 'Skan sayı'],
            ['key' => 'total_lessons', 'label' => 'Dərs sayı'],
        ];
        break;

    case 'week_scans':
        $query = "SELECT DATE(scan_date) AS scan_day,
                         COUNT(*) AS total_scans,
                         COUNT(DISTINCT student_username) AS unique_students,
                         SUM(lesson_count) AS total_lessons
                  FROM qr_scans
                  WHERE teacher_username = ? AND scan_date BETWEEN ? AND ?
                  GROUP BY DATE(scan_date)
                  ORDER BY scan_day ASC";
        $types = 'sss';
        $params = [$teacherUsername, $weekStart, $weekEnd];
        $columns = [
            ['key' => 'scan_day', 'label' => 'Tarix'],
            ['key' => 'total_scans', 'label' => 'Skan sayı'],
            ['key' => 'unique_students', 'label' => 'Tələbə sayı'],
            ['key' => 'total_lessons', 'label' => 'Dərs sayı'],
        ];
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Yanlış statistik tipi.']);
        $conn->close();
        exit;
}

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
    $conn->close();
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    foreach ($row as $key => $value) {
        if ($value === null || $value === '') {
            $row[$key] = '-';
        }
    }
    $rows[] = $row;
}

mysqli_stmt_close($stmt);

echo json_encode([
    'status' => 'success',
    'type' => $type,
    'columns' => $columns,
    'data' => $rows,
]);

$conn->close();
