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
$offset = ($current_page - 1) * $items_per_page;

// Get the category from the AJAX request
$category_filter = isset($_POST['category']) ? $_POST['category'] : '';

// Build the query with pagination
$query = "SELECT id, movzu, text, status, created_at, file, category FROM elanlar";
if ($category_filter) {
    $query .= " WHERE category = ?";
}
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo '<p class="no-announcements">Veritabanı xətası: Sorgu hazırlanarkən xəta baş verdi.</p>';
    exit();
}

if ($category_filter) {
    $stmt->bind_param("sii", $category_filter, $items_per_page, $offset);
} else {
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}
$stmt->close();

// Generate the HTML for the announcements
if (empty($announcements)) {
    echo '<p class="no-announcements">Heç bir elan tapılmadı.</p>';
} else {
    foreach ($announcements as $announcement) {
        echo '<div class="announcement-card">';
        echo '<div class="category-label">' . htmlspecialchars(ucfirst($announcement['category'])) . '</div>';
        echo '<h5>' . htmlspecialchars($announcement['movzu']) . '</h5>';
        echo '<p>' . htmlspecialchars($announcement['text'] ?: 'Təsvir yoxdur.') . '</p>';
        echo '<p>' . htmlspecialchars($announcement['file'] ?: 'File yoxdur.') . '</p>';
        echo '<div>';
        echo '<span class="status-dot ';
        if ($announcement['status'] == 'active') {
            echo 'status-active';
        } elseif ($announcement['status'] == 'viewed') {
            echo 'status-viewed';
        } else {
            echo 'status-inactive';
        }
        echo '"></span>';
        echo '<a class="details-link" ';
        echo 'data-toggle="modal" ';
        echo 'data-target="#announcementModal" ';
        echo 'data-id="' . $announcement['id'] . '" ';
        echo 'data-category="' . htmlspecialchars(ucfirst($announcement['category'])) . '" ';
        echo 'data-movzu="' . htmlspecialchars($announcement['movzu']) . '" ';
        echo 'data-text="' . htmlspecialchars($announcement['text'] ?: 'Təsvir yoxdur.') . '" ';
        echo 'data-file="' . htmlspecialchars($announcement['file'] ?: 'File yoxdur.') . '" ';
        echo 'data-status="' . ($announcement['status'] == 'active' ? 'Aktiv' : ($announcement['status'] == 'viewed' ? 'Baxılıb' : 'Qeyri-aktiv')) . '">';
        echo '<span style="font-family:Arial;">Ətraflı</span> <i class="fas fa-arrow-right"></i></a>';
        echo '</div>';
        echo '</div>';
    }
}

// Close the database connection
$conn->close();
?>