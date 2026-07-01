<?php
// Start session to check user authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo '<p class="no-announcements">Zəhmət olmasa daxil olun.</p>';
    exit();
}

// Include database connection
include('../db.php');

// Pagination settings
$items_per_page = 8;
$current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$current_page = max(1, $current_page);

// Fetch total number of announcements for pagination
$category_filter = isset($_POST['category']) ? $_POST['category'] : '';
$total_query = "SELECT COUNT(*) as total FROM elanlar";
if ($category_filter) {
    $total_query .= " WHERE category = ?";
}
$total_stmt = $conn->prepare($total_query);
if ($category_filter) {
    $total_stmt->bind_param("s", $category_filter);
}
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_items = $total_result->fetch_assoc()['total'];
$total_stmt->close();

// Calculate total pages
$total_pages = ceil($total_items / $items_per_page);

// Generate pagination HTML
if ($total_pages > 1) {
    echo '<a href="#" class="page-link ' . ($current_page == 1 ? 'disabled' : '') . '" data-page="' . ($current_page - 1) . '"><i class="fas fa-chevron-left"></i></a>';

    for ($i = 1; $i <= $total_pages; $i++) {
        echo '<a href="#" class="page-link ' . ($i == $current_page ? 'active' : '') . '" data-page="' . $i . '">' . $i . '</a>';
    }

    echo '<a href="#" class="page-link ' . ($current_page == $total_pages ? 'disabled' : '') . '" data-page="' . ($current_page + 1) . '"><i class="fas fa-chevron-right"></i></a>';
}

// Close the database connection
$conn->close();
?>