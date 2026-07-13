<?php

function dashboard_load_stats(mysqli $conn): array
{
    $currentYear = (int) date('Y');
    $previousYear = $currentYear - 1;

    $stats = [
        'total_students' => 0,
        'avg_grade' => 0.0,
        'attendance_pct' => 0.0,
        'olympiad_count' => 0,
        'student_trend_pct' => 0.0,
        'grade_trend' => 0.0,
        'attendance_trend' => 0.0,
    ];

    $totalResult = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM telebeler');
    if ($totalResult) {
        $stats['total_students'] = (int) mysqli_fetch_assoc($totalResult)['total'];
    }

    $gradeResult = mysqli_query(
        $conn,
        "SELECT AVG(CAST(orta_bal AS DECIMAL(5,2))) AS avg_grade
         FROM telebeler
         WHERE orta_bal REGEXP '^[0-9]+\\.?[0-9]*$'"
    );
    if ($gradeResult) {
        $row = mysqli_fetch_assoc($gradeResult);
        $stats['avg_grade'] = $row['avg_grade'] !== null ? round((float) $row['avg_grade'], 1) : 0.0;
    }

    $activeResult = mysqli_query(
        $conn,
        "SELECT
            SUM(CASE WHEN active_status = 'active' THEN 1 ELSE 0 END) AS active_count,
            COUNT(*) AS total_count
         FROM telebeler"
    );
    if ($activeResult) {
        $row = mysqli_fetch_assoc($activeResult);
        $total = (int) ($row['total_count'] ?? 0);
        $active = (int) ($row['active_count'] ?? 0);
        $stats['attendance_pct'] = $total > 0 ? round(($active / $total) * 100, 1) : 0.0;
    }

    $yearStudentsResult = mysqli_query(
        $conn,
        "SELECT
            SUM(CASE WHEN YEAR(created_at) = {$currentYear} THEN 1 ELSE 0 END) AS current_year,
            SUM(CASE WHEN YEAR(created_at) = {$previousYear} THEN 1 ELSE 0 END) AS previous_year
         FROM telebeler"
    );
    if ($yearStudentsResult) {
        $row = mysqli_fetch_assoc($yearStudentsResult);
        $current = (int) ($row['current_year'] ?? 0);
        $previous = (int) ($row['previous_year'] ?? 0);
        if ($previous > 0) {
            $stats['student_trend_pct'] = round((($current - $previous) / $previous) * 100, 1);
        }
    }

    $yearGradeResult = mysqli_query(
        $conn,
        "SELECT
            AVG(CASE WHEN YEAR(created_at) = {$currentYear} AND orta_bal REGEXP '^[0-9]+\\.?[0-9]*$' THEN CAST(orta_bal AS DECIMAL(5,2)) END) AS current_avg,
            AVG(CASE WHEN YEAR(created_at) = {$previousYear} AND orta_bal REGEXP '^[0-9]+\\.?[0-9]*$' THEN CAST(orta_bal AS DECIMAL(5,2)) END) AS previous_avg
         FROM telebeler"
    );
    if ($yearGradeResult) {
        $row = mysqli_fetch_assoc($yearGradeResult);
        $currentAvg = $row['current_avg'] !== null ? (float) $row['current_avg'] : 0.0;
        $previousAvg = $row['previous_avg'] !== null ? (float) $row['previous_avg'] : 0.0;
        $stats['grade_trend'] = round($currentAvg - $previousAvg, 1);
    }

    $yearAttendanceResult = mysqli_query(
        $conn,
        "SELECT
            AVG(CASE WHEN YEAR(created_at) = {$currentYear} AND active_status = 'active' THEN 1 ELSE 0 END) AS current_avg,
            AVG(CASE WHEN YEAR(created_at) = {$previousYear} AND active_status = 'active' THEN 1 ELSE 0 END) AS previous_avg
         FROM telebeler"
    );
    if ($yearAttendanceResult) {
        $row = mysqli_fetch_assoc($yearAttendanceResult);
        $currentAvg = $row['current_avg'] !== null ? (float) $row['current_avg'] * 100 : 0.0;
        $previousAvg = $row['previous_avg'] !== null ? (float) $row['previous_avg'] * 100 : 0.0;
        $stats['attendance_trend'] = round($currentAvg - $previousAvg, 1);
    }

    $olympiadResult = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM imtahanlar_exam
         WHERE LOWER(exam_name) LIKE '%olimpiad%' OR LOWER(fenn_adi) LIKE '%olimpiad%'"
    );
    if ($olympiadResult) {
        $stats['olympiad_count'] = (int) mysqli_fetch_assoc($olympiadResult)['total'];
    }

    return $stats;
}
