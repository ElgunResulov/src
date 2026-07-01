<?php

// Get statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'subjects' => 0,
    'new_this_month' => 0
];

// Total teachers
$sql = "SELECT COUNT(*) as total FROM muellimler_new";
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['total'] = $row['total'];
}

// Active teachers
$sql = "SELECT COUNT(*) as active FROM muellimler_new WHERE active_status = 'active'";
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['active'] = $row['active'];
}

// Count unique subjects
$sql = "SELECT COUNT(DISTINCT fenn) as subjects FROM muellimler_new";
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['subjects'] = $row['subjects'];
}

// New teachers this month
$sql = "SELECT COUNT(*) as new_this_month FROM muellimler_new WHERE created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')";
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['new_this_month'] = $row['new_this_month'];
}

// Get all subjects for dropdown
$subjects = [];
$sql = "SELECT DISTINCT fenn FROM muellimler_new ORDER BY fenn";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['fenn'])) {
            $subjects[] = $row['fenn'];
        }
    }
}

// Get all subjects from fennler table if it exists
$sql = "SHOW TABLES LIKE 'fennler_new'";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $sql = "SELECT * FROM fennler_new ORDER BY fenn_adi";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['fenn_adi']) && !in_array($row['fenn_adi'], $subjects)) {
                $subjects[] = $row['fenn_adi'];
            }
        }
    }
}

sort($subjects);
?>