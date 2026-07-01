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

// Include necessary files
include('db.php');
include('navbar_sidebar.php');

// Pagination settings
$items_per_page = 15; // Number of announcements per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1
$offset = ($current_page - 1) * $items_per_page;

// Fetch total number of announcements for pagination
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
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

// Fetch announcements for the current page
$query = "SELECT id, movzu, text, status, created_at, file, category FROM elanlar";
if ($category_filter) {
    $query .= " WHERE category = ?";
}
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
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
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Elanlar - TIS Dərslər</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>

        
.lds-ripple {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-ripple div {
            position: absolute;
            border: 4px solid #3182ce;
            opacity: 1;
            border-radius: 50%;
            animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }

        .lds-ripple div:nth-child(2) {
            animation-delay: -0.5s;
        }

        @keyframes lds-ripple {
            0% {
                top: 36px;
                left: 36px;
                width: 0;
                height: 0;
                opacity: 1;
            }
            100% {
                top: 0;
                left: 0;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

        /* General Layout */
        .main-content {
            margin-left: 0;
            margin-top: 86px;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
            align-content: flex-start;
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            border-radius: 8px;
        }

        .main-content.open {
            margin-left: 250px;
        }

        /* Filter Dropdown */
        .filter-container {
            margin-bottom: 15px;
            display: flex;
            justify-content: flex-end;
            width: 100%;
        }

        .filter-container select {
            padding: 8px 12px;
            font-size: 0.95rem;
            border-radius: 20px;
            border: none;
            background-color: #fff;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.08);
            width: 180px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-container select:focus {
            outline: none;
            box-shadow: 0 1px 12px rgba(52, 152, 219, 0.25);
        }

        /* Announcement Grid */
        .announcement-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            width: 100%;
        }

        .announcement-card {
            background-color: #fff;
            border: none;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
            flex: 1 1 calc(24% - 15px);
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .announcement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: rgba(52, 55, 219, 0.51);
            transition: width 0.3s ease;
        }

        .announcement-card:hover::before {
            width: 8px;
        }

        .category-label {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .announcement-card h5 {
            font-size: 1rem;
            margin-bottom: 8px;
            color: #2c3e50;
            line-height: 1.4;
            font-weight: 600;
        }

        .announcement-card p {
            font-size: 0.85rem;
            color: #636e72;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .status-dot {
            position: relative;
            top: 5px;
            left: 0px;
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            border: 1px solid #fff;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
        }

        .status-active {
            height: 20px;
            width: 20px;
            background: lightgreen;
        }

        .status-inactive {
            height: 20px;
            width: 20px;
            background-color: #95a5a6;
        }

        .status-viewed {
            height: 20px;
            width: 20px;
            background-color: #b0b0b0;
        }

        .details-link {
            color: #3498db;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.3s ease;
            cursor: pointer;
        }

        .details-link:hover {
            color: rgb(71, 170, 237);
            text-decoration: none;
            font-weight: bolder;
            transition: 0.3s ease;
        }

        .details-link i {
            margin-left: 4px;
            font-size: 0.8rem;
        }

        .no-announcements {
            text-align: center;
            color: #7f8c8d;
            font-size: 1rem;
            padding: 20px;
            width: 100%;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.05);
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background-color: rgba(52, 55, 219, 0.68);
            color: #fff;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 15px 20px;
            border-bottom: none;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 20px;
            background-color: #fff;
            color: #2c3e50;
        }

        .modal-body p {
            margin-bottom: 15px;
            font-size: 1rem;
            line-height: 1.6;
        }

        .modal-body strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .modal-footer {
            border-top: none;
            padding: 10px 20px;
            background-color: #f5f7fa;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .modal-footer .btn {
            padding: 8px 20px;
            font-size: 0.95rem;
        }

        .modal-backdrop {
            z-index: 1040 !important;
        }

        .modal {
            z-index: 1050 !important;
        }

        .download-btn {
            display: inline-block;
            padding: 6px 12px;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .download-btn:hover {
            background-color: #2980b9;
            color: #fff;
        }

        /* Pagination Styling */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            width: 100%;
            margin-bottom: 55px;
            flex-wrap: wrap; /* Allow pagination items to wrap on smaller screens */
            gap: 5px; /* Add spacing between pagination items */
        }

        .pagination a {
            color: #3498db;
            text-decoration: none;
            padding: 8px 12px;
            margin: 0 3px;
            border-radius: 5px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            text-align: center;
            min-width: 36px; /* Ensure consistent width for buttons */
            box-sizing: border-box;
        }

        .pagination a:hover {
            background-color: rgba(99, 89, 240, 0.42);
            color: #fff;
            border-color: rgba(52, 152, 219, 0);
        }

        .pagination a.active {
            background-color: rgba(99, 89, 240, 0.76);
            color: #fff;
            border-color: #3498db;
            font-weight: 600;
        }

        .pagination a.disabled {
            color: #7f8c8d;
            pointer-events: none;
            border-color: #e9ecef;
        }

        /* Responsive Design */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 250px;
            }
            .filter-container{
                position: relative;
                left: -40px;
            }
            .announcement-card {
                flex: 1 1 calc(24% - 15px);
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .main-content {
                margin-left: 250px;
            }
            .announcement-card {
                flex: 1 1 calc(32% - 15px);
            }
        }

        @media (max-width: 767px) {
            .pagination {
        gap: 4px; /* Slightly reduce gap for mobile */
        padding: 0 10px; /* Add padding to prevent edge clipping */
        justify-content: center; /* Center items */
    }

    .pagination a {
        padding: 6px 10px; /* Smaller padding for mobile */
        font-size: 0.9rem; /* Slightly smaller font size */
        min-width: 32px; /* Adjust minimum width for mobile */
        margin: 0 2px; /* Reduce margin for tighter spacing */
    }
            .main-content {
                margin-left: 0;
                margin-top: 86px;
                padding: 15px;
            }
            .main-content.open {
                margin-left: 0;
            }
            .page-title {
                font-size: 1.4rem;
                margin-bottom: 10px;
            }
            .filter-container {
                justify-content: center;
            }
            .filter-container select {
                width: 100%;
                max-width: 250px;
                margin-bottom: -10px;
            }
            .announcement-card {
                flex: 1 1 100%;
                padding: 22px;
            }
            .announcement-card h5 {
                font-size: 0.95rem;
            }
            .announcement-card p {
                font-size: 0.8rem;
            }
            .category-label {
                font-size: 0.7rem;
            }
            .details-link {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .pagination {
        gap: 3px; /* Further reduce gap for very small screens */
    }

    .pagination a {
        padding: 5px 8px; /* Even smaller padding */
        font-size: 0.85rem; /* Smaller font size */
        margin: 3px;
        min-width: 28px; /* Smaller minimum width */
    }
            .main-content {
                padding: 10px;
            }
            .page-title {
                font-size: 1.2rem;
            }
            .filter-container select {
                padding: 6px 10px;
                font-size: 0.9rem;
            }
            .announcement-card {
                padding: 20px;
            }
            .announcement-card h5 {
                font-size: 0.9rem;
            }
            .announcement-card p {
                font-size: 0.75rem;
            }
        }

    </style>
</head>
<body>


<div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>
    
    <div class="main-content main">
        <div class="filter-container">
            <select name="category" id="category">
                <option value="">Seçin</option>
                <option value="telimat" <?php echo $category_filter == 'telimat' ? 'selected' : ''; ?>>Təlimat</option>
                <option value="tecrube" <?php echo $category_filter == 'tecrube' ? 'selected' : ''; ?>>Təcrübə</option>
                <option value="sorgu" <?php echo $category_filter == 'sorgu' ? 'selected' : ''; ?>>Sorgu</option>
                <option value="diger" <?php echo $category_filter == 'diger' ? 'selected' : ''; ?>>Digər</option>
            </select>
        </div>

        <!-- Announcement Grid -->
        <div class="announcement-grid">
            <?php if (empty($announcements)): ?>
                <p class="no-announcements">Heç bir elan tapılmadı.</p>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="category-label"><?php echo htmlspecialchars(ucfirst($announcement['category'])); ?></div>
                        <h5><?php echo htmlspecialchars($announcement['movzu']); ?></h5>
                        <p><?php echo htmlspecialchars($announcement['text'] ?: 'Təsvir yoxdur.'); ?></p>
                        <p><?php echo htmlspecialchars($announcement['file'] ?: 'File yoxdur.'); ?></p>
                        <div>
                            <span class="status-dot <?php 
                                if ($announcement['status'] == 'active') {
                                    echo 'status-active';
                                } elseif ($announcement['status'] == 'viewed') {
                                    echo 'status-viewed';
                                } else {
                                    echo 'status-inactive';
                                }
                            ?>"></span>
                            <a class="details-link" 
                               data-toggle="modal" 
                               data-target="#announcementModal"
                               data-id="<?php echo $announcement['id']; ?>"
                               data-category="<?php echo htmlspecialchars(ucfirst($announcement['category'])); ?>"
                               data-movzu="<?php echo htmlspecialchars($announcement['movzu']); ?>"
                               data-text="<?php echo htmlspecialchars($announcement['text'] ?: 'Təsvir yoxdur.'); ?>"
                               data-file="<?php echo htmlspecialchars($announcement['file'] ?: 'File yoxdur.'); ?>"
                               data-status="<?php echo $announcement['status'] == 'active' ? 'Aktiv' : ($announcement['status'] == 'viewed' ? 'Baxılıb' : 'Qeyri-aktiv'); ?>">
                                <span style="font-family:Arial;">Ətraflı</span> <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($total_pages > 1): ?>
                <!-- Previous Button -->
                <a href="#" class="page-link <?php echo $current_page == 1 ? 'disabled' : ''; ?>" data-page="<?php echo $current_page - 1; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>

                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="#" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>" data-page="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next Button -->
                <a href="#" class="page-link <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>" data-page="<?php echo $current_page + 1; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="announcementModalLabel">Elan Detalları</h5>
                </div>
                <div class="modal-body">
                    <p><strong>Kateqoriya:</strong> <span id="modal-category">N/A</span></p>
                    <p><strong>Başlıq:</strong> <span id="modal-movzu">N/A</span></p>
                    <p><strong>Təsvir:</strong> <span id="modal-text">N/A</span></p>
                    <p><strong>Fayl:</strong> <span id="modal-file">N/A</span>
                        <a id="download-link" class="download-btn" style="display: none;" href="#">Yüklə</a>
                    </p>
                    <p><strong>Status:</strong> <span id="modal-status">N/A</span></p>
                </div>
                <div hidden class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script>
        $(document).ready(function() {
            // Function to fetch announcements via AJAX
            function fetchAnnouncements(category, page) {
                $.ajax({
                    url: 'elanlar/fetch_elanlar.php',
                    method: 'POST',
                    data: { 
                        category: category,
                        page: page
                    },
                    success: function(response) {
                        $('.announcement-grid').html(response);
                        // Update pagination links
                        updatePagination(category, page);
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        $('.announcement-grid').html('<p class="no-announcements">Xəta baş verdi. Zəhmət olmasa yenidən cəhd edin.</p>');
                    }
                });
            }

            // Function to update pagination links via AJAX
            function updatePagination(category, currentPage) {
                $.ajax({
                    url: 'elanlar/fetch_pagination.php',
                    method: 'POST',
                    data: { 
                        category: category,
                        page: currentPage
                    },
                    success: function(response) {
                        $('.pagination').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Pagination AJAX Error:', status, error);
                    }
                });
            }

            // AJAX for category filter
            $('#category').on('change', function() {
                var category = $(this).val();
                fetchAnnouncements(category, 1); // Reset to page 1 on category change
            });

            // AJAX for pagination links
            $('.pagination').on('click', '.page-link', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;

                var page = $(this).data('page');
                var category = $('#category').val();
                fetchAnnouncements(category, page);
            });

            // Populate modal and update status to viewed
            $('.announcement-grid').on('click', '.details-link', function(e) {
                e.preventDefault();
                console.log('Details link clicked');

                var $link = $(this);
                var announcementId = $link.data('id');
                var category = $link.data('category') || 'N/A';
                var movzu = $link.data('movzu') || 'N/A';
                var text = $link.data('text') || 'N/A';
                var file = $link.data('file') || 'File yoxdur.';
                var status = $link.data('status') || 'N/A';

                // Populate modal fields
                $('#modal-category').text(category);
                $('#modal-movzu').text(movzu);
                $('#modal-text').text(text);
                $('#modal-file').text(file);
                $('#modal-status').text('Baxılıb');

                // Handle file download link
                if (file !== 'File yoxdur.') {
                    $('#download-link').attr('href', file).show();
                } else {
                    $('#download-link').hide();
                }

                // Update status in the database via AJAX
                $.ajax({
                    url: 'elanlar/update_status.php',
                    method: 'POST',
                    data: { id: announcementId, status: 'viewed' },
                    success: function(response) {
                        console.log('Status updated to viewed');
                        $link.closest('.announcement-card').find('.status-dot')
                            .removeClass('status-active status-inactive')
                            .addClass('status-viewed');
                        $link.data('status', 'Baxılıb');
                    },
                    error: function(xhr, status, error) {
                        console.error('Status Update Error:', status, error);
                    }
                });

                $('#announcementModal').modal('show');
            });

            // Fix backdrop issue
            $('#announcementModal').on('hidden.bs.modal', function () {
                console.log('Modal hidden event fired');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('#modal-category').text('N/A');
                $('#modal-movzu').text('N/A');
                $('#modal-text').text('N/A');
                $('#modal-file').text('N/A');
                $('#download-link').hide();
                $('#modal-status').text('N/A');
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>