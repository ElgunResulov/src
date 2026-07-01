<?php
include('db.php');
require_once __DIR__ . '/qeydiyyatar/odenis_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

odenis_ensure_columns($conn);

$flashSuccess = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $result = odenis_mark_received($conn, $paymentId);
    if ($result['ok']) {
        $flashSuccess = $result['message'];
    } else {
        $flashError = $result['message'];
    }
}

$search = trim($_GET['search'] ?? '');
$typeFilter = in_array($_GET['type'] ?? '', ['ayliq', 'paket'], true) ? $_GET['type'] : '';
$statusFilter = in_array($_GET['status'] ?? '', ['gecikmis', 'bu_ay', 'gozleyir', 'paket'], true) ? $_GET['status'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$today = date('Y-m-d');
$monthEnd = date('Y-m-t');

$where = ['q.tehsil_haqqi > 0'];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = '(q.telebe_ad_soyad LIKE ? OR COALESCE(NULLIF(q.form_email, \'\'), t.reg_email, t.poct) LIKE ? OR q.ixtisas_adi LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($typeFilter !== '') {
    $where[] = 'q.odenis_novu = ?';
    $types .= 's';
    $params[] = $typeFilter;
}

if ($statusFilter === 'paket') {
    $where[] = "q.odenis_novu = 'paket'";
} elseif ($statusFilter === 'gecikmis') {
    $where[] = "q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi < ?";
    $types .= 's';
    $params[] = $today;
} elseif ($statusFilter === 'bu_ay') {
    $where[] = "q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi >= ? AND q.novbeti_odenis_tarixi <= ?";
    $types .= 'ss';
    $params[] = $today;
    $params[] = $monthEnd;
} elseif ($statusFilter === 'gozleyir') {
    $where[] = "q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi > ?";
    $types .= 's';
    $params[] = $monthEnd;
}

$whereSql = implode(' AND ', $where);

$baseFrom = "
    FROM qeydiyyatar q
    LEFT JOIN telebeler t ON t.u_id = q.u_id
    WHERE {$whereSql}
";

function odenis_run_query(mysqli $conn, string $sql, string $types, array $params): mysqli_result|false {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '') {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$refs);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

$countResult = odenis_run_query($conn, "SELECT COUNT(*) AS total {$baseFrom}", $types, $params);
$totalRecords = $countResult ? (int) mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages = max(1, (int) ceil($totalRecords / $perPage));

$listSql = "
    SELECT
        q.id,
        q.u_id,
        q.telebe_ad_soyad,
        q.tehsil_haqqi,
        q.odenis_novu,
        q.ilkin_odenis,
        q.novbeti_odenis_tarixi,
        q.son_odenis_xatirlatma,
        q.baslama_tarixi,
        q.tedris_ili,
        q.ixtisas_adi,
        COALESCE(NULLIF(q.form_email, ''), t.reg_email, t.poct) AS email,
        COALESCE(t.active_status, 'active') AS active_status
    {$baseFrom}
    ORDER BY
        CASE
            WHEN q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi < ? THEN 0
            WHEN q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi <= ? THEN 1
            ELSE 2
        END,
        q.novbeti_odenis_tarixi ASC,
        q.id DESC
    LIMIT ?, ?
";

$listTypes = $types . 'ssii';
$listParams = array_merge($params, [$today, $monthEnd, $offset, $perPage]);
$listResult = odenis_run_query($conn, $listSql, $listTypes, $listParams);
$payments = [];
if ($listResult) {
    while ($row = mysqli_fetch_assoc($listResult)) {
        $payments[] = $row;
    }
}

$statsSql = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi < ? THEN 1 ELSE 0 END) AS gecikmis,
        SUM(CASE WHEN q.odenis_novu = 'ayliq' AND q.novbeti_odenis_tarixi IS NOT NULL AND q.novbeti_odenis_tarixi >= ? AND q.novbeti_odenis_tarixi <= ? THEN 1 ELSE 0 END) AS bu_ay,
        SUM(CASE WHEN q.odenis_novu = 'paket' THEN 1 ELSE 0 END) AS paket
    FROM qeydiyyatar q
    WHERE q.tehsil_haqqi > 0
";
$statsResult = odenis_run_query($conn, $statsSql, 'sss', [$today, $today, $monthEnd]);
$stats = $statsResult ? mysqli_fetch_assoc($statsResult) : ['total' => 0, 'gecikmis' => 0, 'bu_ay' => 0, 'paket' => 0];

function odenis_build_query(array $overrides = []): string {
    $query = array_merge($_GET, $overrides);
    if (!array_key_exists('page', $overrides)) {
        unset($query['page']);
    }
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }
    $qs = http_build_query($query);
    return $qs !== '' ? '?' . $qs : '';
}

include('navbar_sidebar.php');
?>
<style>
    .odenis-page {
        width: 100%;
        max-width: 100%;
        padding: 76px 12px 24px;
        box-sizing: border-box;
        overflow-x: hidden;
    }
    .odenis-stat-card { border: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(30,58,138,.08); }
    .odenis-stat-card .stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.2; }
    .odenis-stat-card .stat-label { color: #64748b; font-size: .9rem; }
    .filter-panel { background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 4px 14px rgba(30,58,138,.06); margin-bottom: 20px; }
    .table-card { border: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(30,58,138,.08); overflow: hidden; }
    .odenis-table thead th { background: #f8fafc; border-top: none; font-weight: 600; color: #334155; white-space: nowrap; }
    .student-name { font-weight: 600; color: #1e293b; word-break: break-word; }
    .student-meta { font-size: .82rem; color: #64748b; word-break: break-word; }
    .action-btn { padding: .35rem .55rem; font-size: .8rem; min-width: 34px; }
    .odenis-actions { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; }
    .odenis-status {
        font-weight: 700;
        font-size: .95rem;
        display: inline-block;
    }
    .odenis-page-title { font-size: 1.25rem; line-height: 1.3; }
    .odenis-page-subtitle { font-size: .9rem; }
    .odenis-filter-actions .btn { min-width: 110px; }
    .odenis-pagination .page-link { padding: .4rem .65rem; }

    @media (min-width: 768px) {
        .odenis-page { padding: 88px 20px 32px; }
        .odenis-page-title { font-size: 1.5rem; }
        .odenis-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
    }

    @media (min-width: 1170px) {
        .odenis-page {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 96px 24px 32px;
        }
        #main-wrapper[data-sidebartype="mini-sidebar"] ~ .odenis-page,
        body:has(#main-wrapper[data-sidebartype="mini-sidebar"]) .odenis-page {
            margin-left: 65px;
            width: calc(100% - 65px);
        }
    }

    @media (max-width: 767.98px) {
        .odenis-stat-card .stat-value { font-size: 1.45rem; }
        .odenis-stat-card .stat-label { font-size: .82rem; }
        .filter-panel { padding: 14px; }
        .odenis-page-header .btn { width: 100%; }
        .odenis-filter-actions { display: flex; flex-direction: column; gap: 8px; }
        .odenis-filter-actions .btn { width: 100%; margin: 0 !important; }
        .odenis-table thead { display: none; }
        .odenis-table tbody tr {
            display: block;
            margin: 0 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }
        .odenis-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 14px;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            text-align: right;
        }
        .odenis-table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #64748b;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .02em;
            text-align: left;
            flex: 0 0 42%;
            max-width: 42%;
        }
        .odenis-table tbody td:last-child { border-bottom: none; }
        .odenis-table tbody td.odenis-td-index {
            background: #f8fafc;
            font-weight: 700;
            color: #1d4ed8;
        }
        .odenis-table tbody td.odenis-td-index::before { color: #1d4ed8; }
        .odenis-table tbody td.odenis-td-student {
            flex-direction: column;
            align-items: stretch;
            text-align: left;
        }
        .odenis-table tbody td.odenis-td-student::before { margin-bottom: 4px; max-width: 100%; flex: none; }
        .odenis-table tbody td.odenis-td-actions::before { align-self: center; }
        .odenis-table tbody td.odenis-td-actions .odenis-actions { justify-content: flex-end; width: 100%; }
        .odenis-table tbody tr.odenis-empty td {
            display: block;
            text-align: center;
            padding: 2rem 1rem;
        }
        .odenis-table tbody tr.odenis-empty td::before { display: none; }
        .odenis-table .table-responsive { overflow: visible; }
        .odenis-pagination { flex-wrap: wrap; gap: 4px; }
        .odenis-pagination .page-item { margin-bottom: 4px; }
    }

    @media (max-width: 575.98px) {
        .odenis-page { padding-top: 72px; }
        .odenis-stat-card .card-body { padding: 14px; }
    }
</style>

<div class="main-content main odenis-page">
    <div class="page-header mb-3 odenis-page-header">
        <div class="row align-items-center">
            <div class="col-12 col-md-7 col-lg-6">
                <h4 class="mb-0 odenis-page-title"><i class="fas fa-credit-card mr-2 text-primary"></i>Ödənişlər paneli</h4>
                <p class="text-muted mb-0 mt-1 odenis-page-subtitle">Qeydiyyatdan keçən tələbələrin təhsil haqqı və ödəniş tarixləri</p>
            </div>
            <div class="col-12 col-md-5 col-lg-6 text-md-right mt-3 mt-md-0">
                <a href="Qeydiyyatar.php" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus mr-1"></i> Yeni qeydiyyat
                </a>
            </div>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i> <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <div class="row mb-3 mb-md-4">
        <div class="col-6 col-md-3 mb-3">
            <div class="card odenis-stat-card h-100">
                <div class="card-body">
                    <div class="stat-value text-primary"><?= (int) $stats['total'] ?></div>
                    <div class="stat-label">Ümumi ödəniş qeydi</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card odenis-stat-card h-100">
                <div class="card-body">
                    <div class="stat-value text-danger"><?= (int) $stats['gecikmis'] ?></div>
                    <div class="stat-label">Gecikmiş ödəniş</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card odenis-stat-card h-100">
                <div class="card-body">
                    <div class="stat-value text-warning"><?= (int) $stats['bu_ay'] ?></div>
                    <div class="stat-label">Bu ay ödənilməli</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card odenis-stat-card h-100">
                <div class="card-body">
                    <div class="stat-value text-info"><?= (int) $stats['paket'] ?></div>
                    <div class="stat-label">Paket ödəniş</div>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-panel">
        <form method="GET" class="row align-items-end">
            <div class="col-12 col-md-4 mb-3 mb-md-0">
                <label class="small text-muted mb-1">Axtarış</label>
                <input type="text" name="search" class="form-control" placeholder="Ad, e-poçt, ixtisas..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2 mb-3 mb-md-0">
                <label class="small text-muted mb-1">Ödəniş növü</label>
                <select name="type" class="form-control">
                    <option value="">Hamısı</option>
                    <option value="ayliq" <?= $typeFilter === 'ayliq' ? 'selected' : '' ?>>Aylıq</option>
                    <option value="paket" <?= $typeFilter === 'paket' ? 'selected' : '' ?>>Paket</option>
                </select>
            </div>
            <div class="col-6 col-md-3 mb-3 mb-md-0">
                <label class="small text-muted mb-1">Status</label>
                <select name="status" class="form-control">
                    <option value="">Hamısı</option>
                    <option value="gecikmis" <?= $statusFilter === 'gecikmis' ? 'selected' : '' ?>>Gecikmiş</option>
                    <option value="bu_ay" <?= $statusFilter === 'bu_ay' ? 'selected' : '' ?>>Bu ay</option>
                    <option value="gozleyir" <?= $statusFilter === 'gozleyir' ? 'selected' : '' ?>>Gözləyir</option>
                    <option value="paket" <?= $statusFilter === 'paket' ? 'selected' : '' ?>>Paket</option>
                </select>
            </div>
            <div class="col-12 col-md-3 odenis-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filtrlə</button>
                <a href="Odenisler.php" class="btn btn-light">Sıfırla</a>
            </div>
        </form>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 odenis-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tələbə</th>
                            <th>Təhsil haqqı</th>
                            <th>Ödəniş növü</th>
                            <th>Növbəti ödəniş</th>
                            <th>Status</th>
                            <th>Tədris ili</th>
                            <th class="text-center">Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($payments)): ?>
                        <tr class="odenis-empty">
                            <td colspan="8" class="text-center text-muted py-5">Ödəniş qeydi tapılmadı</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $i => $row): ?>
                            <?php
                                $status = odenis_status_meta($row);
                                $displayName = str_replace('.', ' ', (string) $row['telebe_ad_soyad']);
                                $monthlyAmount = odenis_monthly_amount((float) $row['tehsil_haqqi'], (string) $row['odenis_novu']);
                                $dueDate = (string) ($row['novbeti_odenis_tarixi'] ?? '');
                                $dueDateValid = odenis_is_valid_date($dueDate);
                            ?>
                            <tr>
                                <td class="odenis-td-index" data-label="#"><?= $offset + $i + 1 ?></td>
                                <td class="odenis-td-student" data-label="Tələbə">
                                    <div class="student-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="student-meta">
                                        <?= htmlspecialchars((string) ($row['ixtisas_adi'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($row['email'])): ?>
                                            · <?= htmlspecialchars((string) $row['email'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Təhsil haqqı">
                                    <strong><?= number_format((float) $row['tehsil_haqqi'], 2, '.', '') ?> AZN</strong>
                                    <?php if ($row['odenis_novu'] === 'ayliq'): ?>
                                        <div class="student-meta">Aylıq: <?= number_format($monthlyAmount, 2, '.', '') ?> AZN</div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Ödəniş növü"><?= htmlspecialchars(odenis_novu_label((string) $row['odenis_novu']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Növbəti ödəniş">
                                    <?= htmlspecialchars(odenis_format_due_display($dueDate), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($row['son_odenis_xatirlatma']) && odenis_is_valid_date((string) $row['son_odenis_xatirlatma'])): ?>
                                        <div class="student-meta">Xatırlatma: <?= htmlspecialchars(odenis_format_az_date((string) $row['son_odenis_xatirlatma']), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <span class="odenis-status <?= htmlspecialchars($status['class'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($status['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (($row['active_status'] ?? '') === 'inactive'): ?>
                                        <span class="odenis-status text-secondary ml-1">Qeyri-aktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Tədris ili"><?= htmlspecialchars((string) ($row['tedris_ili'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center odenis-td-actions" data-label="Əməliyyatlar">
                                    <div class="odenis-actions">
                                    <a href="Qeydiyyatar_print.php?id=<?= (int) $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary action-btn" title="Çap">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="Qeydiyyatar_muqavile.php?id=<?= (int) $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info action-btn" title="Müqavilə">
                                        <i class="fas fa-file-contract"></i>
                                    </a>
                                    <?php if ($row['odenis_novu'] === 'ayliq' && $dueDateValid): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Ödəniş alındı kimi qeyd edilsin?');">
                                            <?= app_csrf_field() ?>
                                            <input type="hidden" name="action" value="mark_paid">
                                            <input type="hidden" name="payment_id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success action-btn" title="Ödəniş alındı">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white">
            <nav>
                <ul class="pagination justify-content-center mb-0 odenis-pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="Odenisler.php<?= odenis_build_query(['page' => $p]) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    function syncOdenisLayout() {
        var page = document.querySelector('.odenis-page');
        var wrapper = document.getElementById('main-wrapper');
        if (!page || !wrapper) return;

        if (window.innerWidth < 1170) {
            page.style.marginLeft = '0';
            page.style.width = '100%';
            return;
        }

        var isMini = wrapper.getAttribute('data-sidebartype') === 'mini-sidebar';
        var offset = isMini ? 65 : 260;
        page.style.marginLeft = offset + 'px';
        page.style.width = 'calc(100% - ' + offset + 'px)';
    }

    document.addEventListener('DOMContentLoaded', syncOdenisLayout);
    window.addEventListener('resize', syncOdenisLayout);

    var wrapper = document.getElementById('main-wrapper');
    if (wrapper && typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(syncOdenisLayout);
        observer.observe(wrapper, { attributes: true, attributeFilter: ['data-sidebartype', 'class'] });
    }

    var toggler = document.querySelector('.nav-toggler');
    if (toggler) {
        toggler.addEventListener('click', function () {
            setTimeout(syncOdenisLayout, 50);
        });
    }
})();
</script>
