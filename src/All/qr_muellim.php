<?php
ob_start(); // Start output buffering to prevent stray output
date_default_timezone_set('Asia/Baku');
include('db.php');
include('navbar_sidebar.php');

// QR code directory configuration
$qr_code_dir = '../Uploads/qrcodes/';
$qr_code_url = '../Uploads/qrcodes/';

// Get current date and time
$current_time = time();
$today = date('Y-m-d', $current_time);
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$current_week_end = date('Y-m-d', strtotime('sunday this week'));

// Get current user role and username
$current_username = $_SESSION['username'] ?? '';
$user_role = $_SESSION['role'] ?? '';

// Handle week navigation
$selected_week = isset($_POST['selected_week']) ? $_POST['selected_week'] : $current_week_start;
$week_start = date('Y-m-d', strtotime($selected_week));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Improved function to format Azerbaijan date and time
function formatAzerbaijanDateTime($timestamp = null, $format = 'full') {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $months_az = [
        1 => 'yanvar', 2 => 'fevral', 3 => 'mart', 4 => 'aprel',
        5 => 'may', 6 => 'iyun', 7 => 'iyul', 8 => 'avqust',
        9 => 'sentyabr', 10 => 'oktyabr', 11 => 'noyabr', 12 => 'dekabr'
    ];
    
    $days_az = [
        'Monday' => 'Bazar ertəsi',
        'Tuesday' => 'Çərşənbə axşamı', 
        'Wednesday' => 'Çərşənbə',
        'Thursday' => 'Cümə axşamı',
        'Friday' => 'Cümə',
        'Saturday' => 'Şənbə',
        'Sunday' => 'Bazar'
    ];
    
    $day = date('j', $timestamp);
    $month = $months_az[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    $time = date('H:i:s', $timestamp);
    $weekday = $days_az[date('l', $timestamp)];
    
    switch ($format) {
        case 'date_only':
            return "$day $month $year";
        case 'time_only':
            return $time;
        case 'short':
            return "$day $month";
        default:
            return "$day $month $year, $weekday - $time";
    }
}

// Improved function to get calendar days with better structure
function getCalendarDays($year, $month, $attendance_data = []) {
    $days = [];
    $first_day = new DateTime("$year-$month-01");
    $days_in_month = $first_day->format('t');
    $first_weekday = $first_day->format('N') - 1; // 0 (Monday) to 6 (Sunday)
    
    // Add padding days from previous month
    $prev_month = $month - 1;
    $prev_year = $year;
    if ($prev_month == 0) {
        $prev_month = 12;
        $prev_year--;
    }
    $prev_month_days = (new DateTime("$prev_year-$prev_month-01"))->format('t');
    
    for ($i = $first_weekday - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("$prev_year-$prev_month-" . ($prev_month_days - $i)));
        $days[] = [
            'date' => $date,
            'day' => $prev_month_days - $i,
            'is_current_month' => false,
            'is_today' => $date === date('Y-m-d'),
            'attendance' => isset($attendance_data[$date]) ? $attendance_data[$date] : null
        ];
    }
    
    // Add current month days
    for ($i = 1; $i <= $days_in_month; $i++) {
        $date = date('Y-m-d', strtotime("$year-$month-$i"));
        $days[] = [
            'date' => $date,
            'day' => $i,
            'is_current_month' => true,
            'is_today' => $date === date('Y-m-d'),
            'attendance' => isset($attendance_data[$date]) ? $attendance_data[$date] : null
        ];
    }
    
    // Add padding days from next month
    $total_days = count($days);
    $remaining_days = (42 - $total_days); // 6 weeks x 7 days
    $next_month = $month + 1;
    $next_year = $year;
    if ($next_month == 13) {
        $next_month = 1;
        $next_year++;
    }
    
    for ($i = 1; $i <= $remaining_days; $i++) {
        $date = date('Y-m-d', strtotime("$next_year-$next_month-$i"));
        $days[] = [
            'date' => $date,
            'day' => $i,
            'is_current_month' => false,
            'is_today' => $date === date('Y-m-d'),
            'attendance' => isset($attendance_data[$date]) ? $attendance_data[$date] : null
        ];
    }
    
    return $days;
}

// Improved function to get attendance statistics
function getAttendanceStats($conn, $teacher_username, $start_date, $end_date) {
    $stats_sql = "SELECT 
                    COUNT(DISTINCT student_username) as unique_students,
                    COUNT(*) as total_scans,
                    DATE(scan_date) as scan_day,
                    AVG(lesson_count) as avg_lessons
                  FROM qr_scans 
                  WHERE teacher_username = ? 
                  AND scan_date BETWEEN ? AND ?
                  GROUP BY DATE(scan_date)
                  ORDER BY scan_date";
    
    $stmt = mysqli_prepare($conn, $stats_sql);
    mysqli_stmt_bind_param($stmt, 'sss', $teacher_username, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $stats = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[$row['scan_day']] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    
    return $stats;
}

// AJAX handler for getting day details (improved)
if (isset($_POST['action']) && $_POST['action'] == 'get_day_details') {
    header('Content-Type: application/json; charset=utf-8');
    ob_end_clean();
    
    try {
        $selected_date = $_POST['date'] ?? '';
        $teacher_username = $_POST['teacher_username'] ?? '';
        
        if (empty($selected_date) || empty($teacher_username)) {
            throw new Exception('Tarix və ya müəllim adı boşdur');
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
            throw new Exception('Yanlış tarix formatı');
        }
        
        // Verify teacher exists
        $teacher_check_sql = "SELECT username FROM muellimler_new WHERE username = ? AND active_status = 'active'";
        $stmt = mysqli_prepare($conn, $teacher_check_sql);
        mysqli_stmt_bind_param($stmt, 's', $teacher_username);
        mysqli_stmt_execute($stmt);
        $teacher_check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($teacher_check_result) == 0) {
            throw new Exception('Müəllim tapılmadı');
        }
        mysqli_stmt_close($stmt);
        
        // Fetch day details with improved query
        $details_sql = "SELECT 
                            student_username,
                            student_u_id,
                            scan_time,
                            lesson_count,
                            created_at,
                            scan_date,
                            TIME(scan_time) as time_only
                        FROM qr_scans 
                        WHERE teacher_username = ? 
                        AND DATE(scan_date) = ?
                        ORDER BY scan_time ASC";
        
        $stmt = mysqli_prepare($conn, $details_sql);
        mysqli_stmt_bind_param($stmt, 'ss', $teacher_username, $selected_date);
        mysqli_stmt_execute($stmt);
        $details_result = mysqli_stmt_get_result($stmt);
        
        if (!$details_result) {
            throw new Exception('Verilənlər bazası xətası: ' . mysqli_error($conn));
        }
        
        $students = [];
        $total_lessons = 0;
        while ($row = mysqli_fetch_assoc($details_result)) {
            $students[] = $row;
            $total_lessons += $row['lesson_count'];
        }
        mysqli_stmt_close($stmt);
        
        $unique_students = array_unique(array_column($students, 'student_username'));
        
        echo json_encode([
            'success' => true,
            'date' => $selected_date,
            'students' => $students,
            'total_count' => count($students),
            'unique_count' => count($unique_students),
            'total_lessons' => $total_lessons,
            'teacher' => $teacher_username,
            'formatted_date' => formatAzerbaijanDateTime(strtotime($selected_date), 'date_only')
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug_info' => [
                'date' => $selected_date ?? 'not set',
                'teacher' => $teacher_username ?? 'not set',
                'sql_error' => mysqli_error($conn)
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Get teachers list for admin/super_admin
$selected_teacher_username = '';
$all_teachers = [];

if ($user_role == 'super_admin' || $user_role == 'admin') {
    $all_teachers_sql = "SELECT username, u_id, tehsil_ve_ixtisas, fenn FROM muellimler_new WHERE active_status = 'active' ORDER BY username";
    $all_teachers_result = mysqli_query($conn, $all_teachers_sql);
    
    if ($all_teachers_result) {
        while ($row = mysqli_fetch_assoc($all_teachers_result)) {
            $all_teachers[] = $row;
        }
    }
    
    if (isset($_POST['selected_teacher']) && !empty($_POST['selected_teacher'])) {
        $selected_teacher_username = $_POST['selected_teacher'];
    } elseif (!empty($all_teachers)) {
        $selected_teacher_username = $all_teachers[0]['username'];
    }
} else {
    $selected_teacher_username = $current_username;
}

// Fetch selected teacher's data
$teacher_sql = "SELECT id, u_id, tehsil_ve_ixtisas, username, fenn, qr_code, unvan FROM muellimler_new WHERE username = ? AND active_status = 'active'";
$stmt = mysqli_prepare($conn, $teacher_sql);
mysqli_stmt_bind_param($stmt, 's', $selected_teacher_username);
mysqli_stmt_execute($stmt);
$teacher_result = mysqli_stmt_get_result($stmt);
$current_teacher = null;

if ($teacher_result && mysqli_num_rows($teacher_result) > 0) {
    $current_teacher = mysqli_fetch_assoc($teacher_result);
} else {
    $error_message = "Müəllim məlumatları tapılmadı.";
}
mysqli_stmt_close($stmt);

// Handle month navigation - FIXED
$selected_month = isset($_POST['selected_month']) ? $_POST['selected_month'] : date('m');
$selected_year = isset($_POST['selected_year']) ? $_POST['selected_year'] : date('Y');

// Get monthly attendance data
$monthly_start = "$selected_year-$selected_month-01";
$monthly_end = date('Y-m-t', strtotime($monthly_start));
$monthly_stats = [];

if ($current_teacher) {
    $monthly_stats = getAttendanceStats($conn, $current_teacher['username'], $monthly_start, $monthly_end);
}

$calendar_days = getCalendarDays($selected_year, $selected_month, $monthly_stats);

// Get weekly stats for overview
$weekly_stats = [];
if ($current_teacher) {
    $weekly_stats = getAttendanceStats($conn, $current_teacher['username'], $week_start, $week_end);
}

// Calculate previous and next month/year
$prev_date = new DateTime("$selected_year-$selected_month-01");
$prev_date->modify('-1 month');
$next_date = new DateTime("$selected_year-$selected_month-01");
$next_date->modify('+1 month');
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Davamiyyət Təqvimi - TIS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --border-radius: 12px;
            --border-radius-sm: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            margin-top: 86px;
            padding: 24px;
            min-height: calc(100vh - 86px);
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                margin-top: 90px;
                padding: 16px;
            }
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        /* Header Styles */
        .header {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 32px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }

        .header-content {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 32px;
            align-items: start;
        }

        .teacher-info p {
            color: var(--gray-600);
            font-size: 1.1rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

            
        .teacher-info h1 {
         font-family: Arial;
         font-weight: bolder;
        }

        .qr-code-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .qr-code-container img {
            width: 120px;
            height: 120px;
            border: 3px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            padding: 8px;
            background: var(--white);
            box-shadow: var(--shadow);
            transition: transform 0.2s ease;
        }

        .qr-code-container img:hover {
            transform: scale(1.05);
        }

        /* Stats Overview */
        .stats-overview {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            padding: 28px;
            width: 100%;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .stat-card p {
            color: var(--gray-600);
            font-weight: 600;
            font-size: 1rem;
        }

        /* Teacher Selector */
        .teacher-selector {
            background: var(--white);
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
        }

        .teacher-selector h3 {
            color: var(--gray-700);
            margin-bottom: 16px;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .selector-form {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .selector-form select {
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            background: var(--white);
            color: var(--gray-700);
            font-size: 1rem;
            min-width: 280px;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .selector-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .selector-form button {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .selector-form button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Calendar Navigation */
        .calendar-nav {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
        }

        .nav-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .nav-button {
            padding: 12px 16px;
            background: var(--gray-100);
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-sm);
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
        }

        .nav-button:hover {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .month-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            text-align: center;
            flex: 1;
        }

        /* Improved Calendar Styles */
        .calendar-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .calendar-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 24px;
            text-align: center;
        }

        .calendar-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .calendar-grid {
            padding: 24px;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            margin-bottom: 16px;
        }

        .weekday-header {
            padding: 16px 8px;
            text-align: center;
            font-weight: 700;
            color: var(--gray-700);
            background: var(--gray-100);
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 12px 8px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            background: var(--gray-50);
            position: relative;
            min-height: 80px;
        }

        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .calendar-day.current-month {
            background: var(--white);
            border-color: var(--gray-200);
        }

        .calendar-day.today {
            background: linear-gradient(135deg, var(--warning-color), #fbbf24);
            color: var(--white);
            font-weight: 700;
            box-shadow: var(--shadow-md);
        }

        .calendar-day.has-activity {
            background: linear-gradient(135deg, var(--success-color), #34d399);
            color: var(--white);
            font-weight: 600;
        }

        .calendar-day.has-activity:hover {
            background: linear-gradient(135deg, #059669, var(--success-color));
        }

        .calendar-day.not-current-month {
            opacity: 0.4;
            background: var(--gray-100);
        }

        .day-number {
            font-size: 1.45rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .day-stats {
            font-size: 0.9rem;
            text-align: center;
            line-height: 1.55;
        }

        .day-stats div {
            margin: 1px 0;
        }

        /* Modal Improvements */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            width: 95%;
            border:none;
            max-width: 86%;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .close {
            color: var(--white);
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s ease-in-out;
            padding: 4px;
        }

        .close:hover {
            transition: 0.2s ease-in-out;
            color: rgba(246, 9, 9, 1);
        }

        .modal-body {
            padding: 32px;
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Summary Info Improvements */
        .summary-info {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px solid #0ea5e9;
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 24px;
        }

        .summary-info h3 {
            color: #0369a1;
            margin-bottom: 16px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
        }

        .summary-stat {
            text-align: center;
            padding: 16px;
            background: var(--white);
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow-sm);
        }

        .summary-stat .number {
            font-size:1.1rem;
            font-weight: 700;
            color: #0369a1;
            margin-bottom: 4px;
        }

        .summary-stat .label {
            font-size: 0.9rem;
            color: var(--gray-600);
            font-weight: 600;
        }

        /* Table Improvements */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .students-table th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            color: var(--gray-800);
            border-bottom: 2px solid var(--gray-300);
        }

        .students-table td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-200);
            transition: background-color 0.2s ease;
        }

        
        /* Error and Loading States */
        .error-message {
            color: var(--danger-color);
            background: #fef2f2;
            border: 2px solid #fecaca;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .loading {
            text-align: center;
            padding: 60px;
            color: var(--gray-500);
            font-size: 1.1rem;
        }

        .no-data {
            text-align: center;
            color: var(--gray-500);
            font-style: italic;
            padding: 60px;
            font-size: 1.1rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .stats-overview {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
            }

            .selector-form {
                flex-direction: column;
                align-items: stretch;
            }

            .selector-form select {
                min-width: 100%;
            }

            .nav-form {
                flex-direction: column;
                gap: 12px;
            }

            .calendar-days {
                gap: 4px;
            }

            .calendar-day {
                min-height: 60px;
                padding: 8px 4px;
            }

            .day-stats {
                font-size: 0.65rem;
            }

            .modal-content {
                width: 98%;
                margin: 2% auto;
            }

            .modal-header {
                padding: 16px;
            }

            .modal-body {
                padding: 20px;
            }

            .students-table {
                font-size: 0.85rem;
            }

            .students-table th,
            .students-table td {
                padding: 12px 8px;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 2rem;
            }

            .stat-card h3 {
                font-size: 2rem;
            }

            .calendar-day {
                min-height: 50px;
                padding: 6px 2px;
            }

            .day-number {
                font-size: 1rem;
            }

            .day-stats {
                font-size: 0.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <!-- Teacher Selector for Admin/Super Admin -->
            <?php if ($user_role == 'super_admin' || $user_role == 'admin'): ?>
            <div class="teacher-selector">
                <h3><i class="fas fa-user-cog"></i> Müəllim Seçin</h3>
                <form method="POST" action="" class="selector-form">
                    <select name="selected_teacher" onchange="this.form.submit()">
                        <?php foreach ($all_teachers as $teacher): ?>
                            <option value="<?php echo htmlspecialchars($teacher['username']); ?>"
                                     <?php echo ($teacher['username'] == $selected_teacher_username) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['username']); ?> -
                                <?php echo htmlspecialchars($teacher['tehsil_ve_ixtisas'] ?: 'Fənn təyin edilməyib'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="selected_week" value="<?php echo htmlspecialchars($selected_week, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="selected_month" value="<?php echo htmlspecialchars($selected_month, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="selected_year" value="<?php echo htmlspecialchars($selected_year, ENT_QUOTES, 'UTF-8'); ?>">
                    <noscript>
                        <button type="submit"><i class="fas fa-search"></i> Göstər</button>
                    </noscript>
                </form>
            </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="header">
                <div class="header-content">
                    <div class="teacher-info">
                        <h1><i class="fas fa-calendar-alt"></i> Davamiyyət Təqvimi</h1>
                        <?php if ($current_teacher): ?>
                        <p><i class="fas fa-user"></i> <strong>Müəllim:</strong> <?php echo htmlspecialchars($current_teacher['username']); ?></p>
                        <p><i class="fas fa-graduation-cap"></i> <strong>İxtisas:</strong> <?php echo htmlspecialchars($current_teacher['tehsil_ve_ixtisas'] ?: 'Təyin edilməyib'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="qr-code-container">
                        <?php if ($current_teacher && !empty($current_teacher['qr_code']) && file_exists($qr_code_dir . $current_teacher['qr_code'])): ?>
                            <img src="<?php echo htmlspecialchars($qr_code_url . $current_teacher['qr_code']); ?>"
                                  alt="QR Code for <?php echo htmlspecialchars($current_teacher['username']); ?>"
                                 title="<?php echo htmlspecialchars($current_teacher['username']); ?> üçün QR Kod">
                        <?php else: ?>
                            <div class="no-qr">
                                <i class="fas fa-qrcode" style="font-size: 3rem; color: var(--gray-400);"></i>
                                <p style="color: var(--gray-500); margin-top: 8px;">QR kod tapılmadı</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Statistics Overview -->
            <div class="stats-overview">
                <div class="stat-card">
                    <h3><?php 
                        $total_scans_today = 0;
                        $unique_students_today = 0;
                        if (isset($weekly_stats[$today])) {
                            $total_scans_today = $weekly_stats[$today]['total_scans'];
                            $unique_students_today = $weekly_stats[$today]['unique_students'];
                        }
                        echo $total_scans_today;
                    ?></h3>
                    <p><i class="fas fa-qrcode"></i> Bugünkü Skanlar</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $unique_students_today; ?></h3>
                    <p><i class="fas fa-users"></i> Bugünkü Tələbələr</p>
                </div>
                <div class="stat-card">
                    <h3><?php 
                        $total_week_scans = 0;
                        foreach ($weekly_stats as $day_stats) {
                            $total_week_scans += $day_stats['total_scans'];
                        }
                        echo $total_week_scans;
                    ?></h3>
                    <p><i class="fas fa-chart-line"></i> Həftəlik Skanlar</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo date('H:i'); ?></h3>
                    <p><i class="fas fa-clock"></i> Cari Vaxt</p>
                </div>
            </div>

            <!-- Calendar Navigation - FIXED -->
            <div class="calendar-nav">
                <div class="nav-form">
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" class="nav-button" title="Əvvəlki ay">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <input type="hidden" name="selected_month" value="<?php echo $prev_date->format('m'); ?>">
                        <input type="hidden" name="selected_year" value="<?php echo $prev_date->format('Y'); ?>">
                        <input type="hidden" name="selected_teacher" value="<?php echo htmlspecialchars($selected_teacher_username, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="selected_week" value="<?php echo htmlspecialchars($selected_week, ENT_QUOTES, 'UTF-8'); ?>">
                    </form>
                    
                    <h3 class="month-title">
                        <?php 
                            $date = new DateTime("$selected_year-$selected_month-01");
                            $monthNamesAz = [
                                1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel', 
                                5 => 'May', 6 => 'İyun', 7 => 'İyul', 8 => 'Avqust', 
                                9 => 'Sentyabr', 10 => 'Oktyabr', 11 => 'Noyabr', 12 => 'Dekabr'
                            ];
                            echo $monthNamesAz[$date->format('n')] . ' ' . $date->format('Y');
                        ?>
                    </h3>
                    
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" class="nav-button" title="Növbəti ay">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <input type="hidden" name="selected_month" value="<?php echo $next_date->format('m'); ?>">
                        <input type="hidden" name="selected_year" value="<?php echo $next_date->format('Y'); ?>">
                        <input type="hidden" name="selected_teacher" value="<?php echo htmlspecialchars($selected_teacher_username, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="selected_week" value="<?php echo htmlspecialchars($selected_week, ENT_QUOTES, 'UTF-8'); ?>">
                    </form>
                </div>
            </div>

            <!-- Improved Calendar -->
            <?php if ($current_teacher): ?>
            <div class="calendar-section">
                <div hidden class="calendar-header">
                    <h3><i class="fas fa-calendar-alt"></i> Aylıq Davamiyyət Təqvimi</h3>
                </div>
                <div class="calendar-grid">
                    <div class="calendar-weekdays">
                        <div class="weekday-header">B.E</div>
                        <div class="weekday-header">Ç.A</div>
                        <div class="weekday-header">Ç</div>
                        <div class="weekday-header">C.A</div>
                        <div class="weekday-header">C</div>
                        <div class="weekday-header">Ş</div>
                        <div class="weekday-header">B</div>
                    </div>
                    
                    <div class="calendar-days">
                        <?php foreach ($calendar_days as $day): 
                            $day_stats = $day['attendance'];
                            $has_activity = $day_stats && $day_stats['total_scans'] > 0;
                            
                            $classes = ['calendar-day'];
                            if ($day['is_current_month']) $classes[] = 'current-month';
                            if ($day['is_today']) $classes[] = 'today';
                            if ($has_activity) $classes[] = 'has-activity';
                            if (!$day['is_current_month']) $classes[] = 'not-current-month';
                        ?>
                            <div class="<?php echo implode(' ', $classes); ?>"
                                  data-date="<?php echo $day['date']; ?>"
                                 data-teacher="<?php echo htmlspecialchars($current_teacher['username']); ?>"
                                 title="<?php echo formatAzerbaijanDateTime(strtotime($day['date']), 'date_only'); ?> - Klik edərək ətraflı məlumat">
                                <div class="day-number"><?php echo $day['day']; ?></div>
                                <div class="day-stats">
                                    <div><i class="fas fa-qrcode"></i> <?php echo $day_stats ? $day_stats['total_scans'] : '0'; ?></div>
                                    <div><i class="fas fa-users"></i> <?php echo $day_stats ? $day_stats['unique_students'] : '0'; ?></div>
                                    <?php if ($day_stats && isset($day_stats['avg_lessons'])): ?>
                                    <div hidden><i class="fas fa-book"></i> <?php echo round($day_stats['avg_lessons'], 1); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="error-message">
                    <i class="fas fa-info-circle"></i> 
                    <span>Müəllim məlumatları tapılmadı.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enhanced Modal for Day Details -->
    <div id="dayModal" class="modal">
        <div class="modal-content">
            <div hidden class="modal-header">
                <h2><i class="fas fa-calendar-day"></i> Gün Ətraflı Məlumatları</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Məlumatlar yüklənir...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    
    <script>
        // Enhanced Modal functionality
        const modal = document.getElementById('dayModal');
        const closeBtn = document.querySelector('.close');
        const modalContent = document.getElementById('modalContent');
        
        // Add click event to all calendar day elements
        document.querySelectorAll('.calendar-day').forEach(function(element) {
            element.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                const teacher = this.getAttribute('data-teacher');
                
                if (date && teacher && this.classList.contains('current-month')) {
                    openDayModal(date, teacher);
                }
            });
        });
        
        // Close modal events
        closeBtn.addEventListener('click', function() {
            modal.classList.remove('show');
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        });
        
        // ESC key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                modal.classList.remove('show');
            }
        });
        
        function openDayModal(date, teacher) {
            modal.classList.add('show');
            modalContent.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Məlumatlar yüklənir...</div>';
            
            const formData = new FormData();
            formData.append('action', 'get_day_details');
            formData.append('date', date);
            formData.append('teacher_username', teacher);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayDayDetails(data);
                } else {
                    displayError(data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalContent.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <span>Məlumatları yükləyərkən xəta baş verdi: ${error.message}</span>
                    </div>
                `;
            });
        }
        
        function displayError(data) {
            let html = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <span>Xəta: ${data.error || 'Naməlum xəta'}</span>
                </div>
            `;
            
            if (data.debug_info) {
                html += `
                    <div style="background: var(--gray-100); border: 1px solid var(--gray-300); border-radius: var(--border-radius-sm); padding: 16px; margin-top: 16px; font-size: 0.9rem; color: var(--gray-600);">
                        <strong>Debug məlumatları:</strong><br>
                        Tarix: ${data.debug_info.date}<br>
                        Müəllim: ${data.debug_info.teacher}<br>
                        SQL Xətası: ${data.debug_info.sql_error || 'Yoxdur'}
                    </div>
                `;
            }
            
            modalContent.innerHTML = html;
        }
        
        function displayDayDetails(data) {
            let html = `
                <div class="summary-info">
                    <h3><i class="fas fa-calendar-day"></i> ${data.formatted_date}</h3>
                    <div class="summary-stats">
                        <div class="summary-stat">
                            <div class="number">${data.total_count}</div>
                            <div class="label">Ümumi Skan</div>
                        </div>
                        <div class="summary-stat">
                            <div class="number">${data.total_lessons || 0}</div>
                            <div class="label">Ümumi Dərs</div>
                        </div>
                        <div class="summary-stat">
                            <div class="number">${data.teacher}</div>
                            <div class="label">Müəllim</div>
                        </div>
                    </div>
                </div>
            `;
            
            if (data.students.length > 0) {
                html += `
                    <table class="students-table">
                        <thead>
                            <tr>
                                <th>Tələbə Adı</th>
                                <th>Skan Vaxtı</th>
                                <th>Dərs Sayı</th>
                                <th>Əlavə Tarixi</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.students.forEach(function(student) {
                    html += `
                        <tr>
                            <td><strong>${student.student_username}</strong></td>
                            <td>${student.time_only || student.scan_time}</td>
                            <td><span style="background: var(--success-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">${student.lesson_count}</span></td>
                            <td>${new Date(student.created_at).toLocaleString('az-AZ')}</td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;
            } else {
                html += `
                    <div class="no-data">
                        <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 16px;"></i>
                        <p>Bu gün heç bir skan edilməyib</p>
                    </div>
                `;
            }
            
            modalContent.innerHTML = html;
        }
        
        // Auto refresh functionality with better user interaction tracking
        let lastInteraction = Date.now();
        let refreshInterval = 60000; // 60 seconds
        
        // Track user interactions
        ['click', 'keydown', 'mousemove', 'scroll'].forEach(event => {
            document.addEventListener(event, function() {
                lastInteraction = Date.now();
            });
        });
        
        // Auto refresh with conditions
        setInterval(function() {
            const timeSinceInteraction = Date.now() - lastInteraction;
            const modalOpen = modal.classList.contains('show');
            
            // Only refresh if:
            // 1. User hasn't interacted in last 30 seconds
            // 2. Modal is not open
            // 3. Page is visible (not in background tab)
            if (timeSinceInteraction > 30000 && !modalOpen && !document.hidden) {
                location.reload();
            }
        }, refreshInterval);
        
        // Pause auto-refresh when page is not visible
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                lastInteraction = Date.now(); // Reset timer when page becomes hidden
            }
        });
        
        // Add smooth scrolling for better UX
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Add keyboard navigation for calendar
        document.addEventListener('keydown', function(event) {
            if (modal.classList.contains('show')) return;
            
            const currentDay = document.querySelector('.calendar-day.today');
            if (!currentDay) return;
            
            let targetDay = null;
            
            switch(event.key) {
                case 'ArrowLeft':
                    targetDay = currentDay.previousElementSibling;
                    break;
                case 'ArrowRight':
                    targetDay = currentDay.nextElementSibling;
                    break;
                case 'ArrowUp':
                    const currentIndex = Array.from(currentDay.parentNode.children).indexOf(currentDay);
                    targetDay = currentDay.parentNode.children[currentIndex - 7];
                    break;
                case 'ArrowDown':
                    const currentIdx = Array.from(currentDay.parentNode.children).indexOf(currentDay);
                    targetDay = currentDay.parentNode.children[currentIdx + 7];
                    break;
                case 'Enter':
                case ' ':
                    if (currentDay.classList.contains('current-month')) {
                        currentDay.click();
                    }
                    event.preventDefault();
                    break;
            }
            
            if (targetDay && targetDay.classList.contains('calendar-day')) {
                // Remove focus from current day
                document.querySelectorAll('.calendar-day').forEach(day => {
                    day.style.outline = 'none';
                });
                
                // Add focus to target day
                targetDay.style.outline = '3px solid var(--primary-color)';
                targetDay.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                event.preventDefault();
            }
        });
    </script>
</body>
</html>

<?php
ob_end_flush();
?>