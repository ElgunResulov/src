<?php
include('navbar_sidebar.php');
include('db.php');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_fenn = isset($_GET['fenn']) ? trim($_GET['fenn']) : '';
$filter_sinif = isset($_GET['sinif']) ? trim($_GET['sinif']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

function executeExamQuery($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Query failed.");
    }

    if ($types !== '') {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        $stmt->bind_param($types, ...$refs);
    }

    $stmt->execute();
    return $stmt->get_result();
}

$where_clause = "WHERE 1=1";
$where_types = '';
$where_params = [];
if (!empty($search)) {
    $search_like = '%' . $search . '%';
    $where_clause .= " AND (exam_name LIKE ? OR description LIKE ? OR fenn_adi LIKE ?)";
    $where_types .= 'sss';
    $where_params[] = $search_like;
    $where_params[] = $search_like;
    $where_params[] = $search_like;
}
if (!empty($filter_fenn)) {
    $where_clause .= " AND fenn_adi = ?";
    $where_types .= 's';
    $where_params[] = $filter_fenn;
}
if (!empty($filter_sinif)) {
    $where_clause .= " AND sinif = ?";
    $where_types .= 's';
    $where_params[] = $filter_sinif;
}
if (!empty($filter_status)) {
    $where_clause .= " AND status = ?";
    $where_types .= 's';
    $where_params[] = $filter_status;
}

$count_query = "SELECT COUNT(*) as total FROM imtahanlar_exam $where_clause";
$count_result = executeExamQuery($conn, $count_query, $where_types, $where_params);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$fenn_query = "SELECT DISTINCT fenn_adi FROM imtahanlar_exam ORDER BY fenn_adi";
$fenn_result = $conn->query($fenn_query);

$sinif_query = "SELECT DISTINCT sinif FROM imtahanlar_exam ORDER BY sinif";
$sinif_result = $conn->query($sinif_query);

$query = "SELECT * FROM imtahanlar_exam $where_clause ORDER BY created_at DESC LIMIT ?, ?";
$result = executeExamQuery($conn, $query, $where_types . 'ii', array_merge($where_params, [$offset, $records_per_page]));

if (!$result) {
    die("Query failed.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmtahan Siyahısı</title>
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: rgba(79, 70, 229, 0.1);
            --text-color: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --background-color: #f9fafb;
            --card-background: #ffffff;
            --success-color: #10b981;
            --success-hover: #059669;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --info-hover: #2563eb;
            --transition: all 0.2s ease;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --radius-lg: 0.75rem;
        }

        .main-content {
            max-width: 100%;
            margin: 0 auto;
            padding: 1.5rem;
            margin-top: 80px;
            transition: margin-left var(--transition);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }

        .filter-container {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control,
        .form-select {
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            background: var(--card-background);
            transition: var(--transition);
            width: 100%;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-outline {
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-color);
        }

        .btn-outline:hover {
            background: var(--background-color);
            border-color: var(--text-muted);
        }

        .card {
            background: var(--card-background);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
        }

        .table th {
            background: var(--background-color);
            font-weight: 600;
            color: var(--text-color);
            position: sticky;
            top: 0;
            z-index: 0;
        }

        .table tr:hover {
            background: var(--primary-light);
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            color: white;
        }

        .bg-primary { background-color: #0d6efd; }
        .bg-success { background-color: #198754; }
        .bg-warning { background-color: #ffc107; }
        .bg-secondary { background-color: #6c757d; }

        .action-buttons {
            display: flex;
            gap: 0.32rem;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            border: none;
        }

        .btn-view {
            background-color: var(--info-color);
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
        }

        .btn-view:hover {
            color: white;
            background-color: var(--info-hover);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
        }

        .btn-start {
            background-color: var(--success-color);
            box-shadow: 0 2px 5px rgba(16, 185, 129, 0.3);
        }

        .btn-start:hover {
            color: white;
            background-color: var(--success-hover);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination-item {
            min-width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            color: var(--text-color);
            text-decoration: none;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            padding: 0 0.5rem;
        }

        .pagination-item:hover {
            background: var(--background-color);
            border-color: var(--primary-color);
        }

        .pagination-item.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-item.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border-color);
        }

        .empty-state-text {
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .empty-state-subtext {
            font-size: 0.875rem;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 260px;
            }
        }

        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0;
                margin-top: 5rem;
            }
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }

            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
            }

            .hide-on-mobile {
                display: none;
            }

            .action-btn {
                width: 32px;
                height: 32px;
            }

            .form-control,
            .form-select,
            .btn {
                font-size: 0.8125rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 1rem 0.5rem;
            }

            .filter-container {
                padding: 1rem;
            }

            .form-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .pagination-item {
                min-width: 2rem;
                height: 2rem;
                font-size: 0.8125rem;
            }
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">İmtahan Siyahısı</h1>
    </div>

    <div class="filter-container">
        <form action="" method="GET" class="filter-form">
            <div class="form-group">
                <label for="search" class="form-label">Axtarış</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="İmtahan adı, təsvir..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label for="fenn" class="form-label">Fənn</label>
                <select id="fenn" name="fenn" class="form-select">
                    <option value="">Bütün fənlər</option>
                    <?php while ($fenn = $fenn_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($fenn['fenn_adi']); ?>" <?php echo $filter_fenn === $fenn['fenn_adi'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($fenn['fenn_adi']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sinif" class="form-label">Sinif</label>
                <select id="sinif" name="sinif" class="form-select">
                    <option value="">Bütün siniflər</option>
                    <?php while ($sinif = $sinif_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($sinif['sinif']); ?>" <?php echo $filter_sinif === $sinif['sinif'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sinif['sinif']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Bütün statuslar</option>
                    <option value="upcoming" <?php echo $filter_status === 'upcoming' ? 'selected' : ''; ?>>Gələcək</option>
                    <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Aktiv</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Tamamlanmış</option>
                </select>
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Axtar
                </button>
                <a hidden href="imtahan-list.php" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Sıfırla
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>İmtahan Adı</th>
                            <th>Fənn</th>
                            <th>Sinif</th>
                            <th>Tarix</th>
                            <th>Müddət</th>
                            <th>Status</th>
                            <th>Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars(str_replace(['"', '[', ']'], '', $row['fenn_adi'])); ?></td>
                                <td><?php echo htmlspecialchars($row['sinif']); ?></td>
                                <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($row['exam_date']))); ?></td>
                                <td><?php echo htmlspecialchars($row['duration']); ?> dəq</td>
                                <td>
                                    <?php
                                    // Map old status values to new ones
                                    $status_map = [
                                        'aktiv' => 'active',
                                        'gozlemde' => 'upcoming',
                                        'bitmis' => 'completed',
                                        'legv edilmis' => 'completed', // Adjust as needed
                                    ];
                                    $normalized_status = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $row['status']));
                                    $status_key = isset($status_map[$normalized_status]) ? $status_map[$normalized_status] : $normalized_status;

                                    switch (strtolower($status_key)) {
                                        case 'upcoming':
                                            echo '<span class="badge bg-primary">Gələcək</span>';
                                            break;
                                        case 'active':
                                            echo '<span class="badge bg-warning">Aktiv</span>';
                                            break;
                                        case 'completed':
                                            echo '<span class="badge bg-success">Tamamlanmış</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Bilinməyən</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="examination/imtahan-view.php?id=<?php echo $row['id']; ?>" class="action-btn btn-view" title="Bax">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="examination/imtahan-start.php?id=<?php echo $row['id']; ?>" class="action-btn btn-start" title="İmtahanı başlat">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search empty-state-icon"></i>
                    <p class="empty-state-text">Heç bir imtahan tapılmadı</p>
                    <p class="empty-state-subtext">Axtarış parametrlərini dəyişdirməyi və ya bütün imtahanları göstərməyi sınayın</p>
                    <a href="Examination.php" class="btn btn-outline">
                        <i class="fas fa-redo"></i> Bütün imtahanları göstər
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <a class="pagination-item <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_fenn) ? '&fenn=' . urlencode($filter_fenn) : ''; ?><?php echo !empty($filter_sinif) ? '&sinif=' . urlencode($filter_sinif) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?>">
                <i class="fas fa-angle-double-left"></i>
            </a>
            <a class="pagination-item <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_fenn) ? '&fenn=' . urlencode($filter_fenn) : ''; ?><?php echo !empty($filter_sinif) ? '&sinif=' . urlencode($filter_sinif) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?>">
                <i class="fas fa-angle-left"></i>
            </a>
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_fenn) ? '&fenn=' . urlencode($filter_fenn) : ''; ?><?php echo !empty($filter_sinif) ? '&sinif=' . urlencode($filter_sinif) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <a class="pagination-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_fenn) ? '&fenn=' . urlencode($filter_fenn) : ''; ?><?php echo !empty($filter_sinif) ? '&sinif=' . urlencode($filter_sinif) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?>">
                <i class="fas fa-angle-right"></i>
            </a>
            <a class="pagination-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter_fenn) ? '&fenn=' . urlencode($filter_fenn) : ''; ?><?php echo !empty($filter_sinif) ? '&sinif=' . urlencode($filter_sinif) : ''; ?><?php echo !empty($filter_status) ? '&status=' . urlencode($filter_status) : ''; ?>">
                <i class="fas fa-angle-double-right"></i>
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/app-style-switcher.js"></script>
<script src="../dist/js/feather.min.js"></script>
<script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="../dist/js/sidebarmenu.js"></script>
<script src="../dist/js/custom.min.js"></script>
<script src="muellim/script.js"></script>

<script>
    // Add smooth transitions when filtering
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('.filter-form');
        const tableContainer = document.querySelector('.table-container');
        
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                if (tableContainer) {
                    tableContainer.style.opacity = '0.6';
                    tableContainer.style.transition = 'opacity 0.3s ease';
                }
            });
        }
    });
</script>
</body>
</html>