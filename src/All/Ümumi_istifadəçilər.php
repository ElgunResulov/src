<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include('navbar_sidebar.php');

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

$users = [];
$roleCounts = array_fill_keys(array_keys($roleLabels), 0);
$canManage = in_array($_SESSION['role'] ?? '', ['super_admin', 'admin'], true);

if ($canManage) {
    $role = $_SESSION['role'];
    $company_id = (int) ($_SESSION['company_id'] ?? 0);

    if ($role === 'super_admin') {
        $sql = "SELECT id, username, role, u_id, company_id, created_at FROM users ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT id, username, role, u_id, company_id, created_at FROM users WHERE company_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $company_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
        $userRole = $row['role'];
        if (isset($roleCounts[$userRole])) {
            $roleCounts[$userRole]++;
        }
    }
    $stmt->close();
}

$totalUsers = count($users);
$isSuperAdmin = (($_SESSION['role'] ?? '') === 'super_admin');
$assignableRoles = $roleLabels;
if (!$isSuperAdmin) {
    unset($assignableRoles['super_admin']);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Ümumi istifadəçilər</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #1d6a9d;
            --text-primary: #212121;
            --text-secondary: #757575;
            --background: #f5f5f5;
            --surface: #ffffff;
        }

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
            0% { top: 36px; left: 36px; width: 0; height: 0; opacity: 1; }
            100% { top: 0; left: 0; width: 72px; height: 72px; opacity: 0; }
        }

        .main-content {
            margin-left: 0;
            padding: 20px;
            flex: 1;
            margin-top: 86px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: margin-left 0.3s ease;
            background-color: var(--background);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .stat-card {
            border-radius: 10px;
            color: #fff;
            height: 100%;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card-clickable {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card-clickable:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-card .stat-title {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        .filter-panel {
            background: var(--surface);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .users-table-card {
            background: var(--surface);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .users-table-card .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .users-table {
            width: 100%;
            margin: 0;
        }

        .users-table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.85rem;
            padding: 12px 16px;
            white-space: nowrap;
        }

        .users-table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 0.9rem;
        }

        .users-table tbody tr:hover {
            background: rgba(29, 106, 157, 0.04);
        }

        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 500;
            background: rgba(29, 106, 157, 0.12);
            color: var(--primary-color);
        }

        .btn-edit {
            padding: 5px 12px;
            font-size: 0.82rem;
            border-radius: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            opacity: 0.4;
            margin-bottom: 16px;
        }

        .role-panel {
            background: var(--surface);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .role-panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .role-panel-header h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .role-panel-body {
            padding: 20px;
            display: none;
        }

        .role-panel.open .role-panel-body {
            display: block;
        }

        .role-panel.open .role-panel-toggle i {
            transform: rotate(180deg);
        }

        .role-panel-toggle i {
            transition: transform 0.3s ease;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 8px;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .role-option label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0;
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-primary);
            background: #fafafa;
        }

        .role-option label i {
            color: var(--primary-color);
            width: 18px;
            text-align: center;
        }

        .role-option input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: rgba(29, 106, 157, 0.08);
            color: var(--primary-color);
        }

        .role-option label:hover {
            border-color: var(--primary-color);
        }

        .role-select {
            min-width: 140px;
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .role-update-msg {
            font-size: 0.78rem;
            margin-top: 4px;
        }

        .role-update-msg.success { color: #198754; }
        .role-update-msg.error { color: #dc3545; }

        .password-hint {
            background: rgba(29, 106, 157, 0.08);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        .password-mode-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }

        .password-mode-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid #dbe3ec;
            border-radius: 8px;
            margin: 0;
            cursor: pointer;
            flex: 1 1 180px;
        }

        .password-mode-row label:has(input:checked) {
            border-color: var(--primary-color);
            background: rgba(29, 106, 157, 0.06);
        }

        #manualPasswordWrap {
            display: none;
        }

        #manualPasswordWrap.is-visible {
            display: block;
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            .users-table-card {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<div class="preloader">
    <div class="lds-ripple"><div></div><div></div></div>
</div>

<div class="main-content main">
    <h1 class="page-title"><i class="fas fa-users mr-2"></i>Ümumi istifadəçilər</h1>

    <?php if ($canManage): ?>

    <div class="role-panel open" id="rolePanel">
        <div class="role-panel-header" id="rolePanelToggle">
            <h5><i class="fas fa-user-tag mr-2"></i>Yeni istifadəçi və rol əlavə et</h5>
            <button type="button" class="btn btn-sm btn-outline-primary role-panel-toggle" aria-label="Paneli aç/bağla">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="role-panel-body">
            <div class="password-hint">
                <i class="fas fa-key mr-1"></i>
                Şifrəni avtomatik yarada və ya özünüz təyin edə bilərsiniz.
                <a href="Parol_idareetme.php" class="ml-2">Parol İdarəetməsi →</a>
            </div>
            <form method="POST" action="process_add_user.php" id="addUserRoleForm">
                <input type="hidden" name="redirect_to" value="Ümumi_istifadəçilər.php">
                <div class="form-group">
                    <label for="newUsername">İstifadəçi adı</label>
                    <input type="text" class="form-control" id="newUsername" name="username" required placeholder="Yeni istifadəçi adı daxil edin">
                </div>
                <div class="form-group">
                    <label>Şifrə seçimi</label>
                    <div class="password-mode-row">
                        <label>
                            <input type="radio" name="password_mode" value="auto" checked>
                            Avtomatik şifrə
                        </label>
                        <label>
                            <input type="radio" name="password_mode" value="manual">
                            Öz şifrəmi yazım
                        </label>
                    </div>
                </div>
                <div class="form-group" id="manualPasswordWrap">
                    <label for="newPassword">Şifrə (ən azı 6 simvol)</label>
                    <input type="text" class="form-control" id="newPassword" name="password" minlength="6" autocomplete="new-password" placeholder="Şifrənizi daxil edin">
                </div>
                <div class="form-group mb-0">
                    <label>Rol seçin</label>
                    <div class="role-grid">
                        <?php
                        $roleIcons = [
                            'super_admin' => 'fa-crown',
                            'admin' => 'fa-user-shield',
                            'teacher' => 'fa-chalkboard-teacher',
                            'student' => 'fa-user-graduate',
                            'parent' => 'fa-heart',
                            'staff' => 'fa-users',
                            'examiner' => 'fa-clipboard-check',
                            'operator' => 'fa-headset',
                        ];
                        $firstRole = array_key_first($assignableRoles);
                        foreach ($assignableRoles as $key => $label):
                            $icon = $roleIcons[$key] ?? 'fa-user';
                        ?>
                        <div class="role-option">
                            <input type="radio" id="role_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                   name="role" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                   <?= $key === $firstRole ? 'checked' : '' ?>>
                            <label for="role_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas <?= $icon ?>"></i>
                                <?= htmlspecialchars($label) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> İstifadəçi əlavə et
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">Təmizlə</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card stat-card-clickable bg-primary text-white h-100" data-stat-type="all" role="button" tabindex="0" aria-label="Bütün istifadəçiləri göstər">
                <div class="card-body">
                    <p class="stat-title">Ümumi say</p>
                    <h3 class="stat-number"><?= $totalUsers ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card stat-card-clickable bg-success text-white h-100" data-stat-type="teachers" role="button" tabindex="0" aria-label="Müəllimləri göstər">
                <div class="card-body">
                    <p class="stat-title">Müəllimlər</p>
                    <h3 class="stat-number"><?= $roleCounts['teacher'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card stat-card-clickable bg-info text-white h-100" data-stat-type="students" role="button" tabindex="0" aria-label="Tələbələri göstər">
                <div class="card-body">
                    <p class="stat-title">Tələbələr</p>
                    <h3 class="stat-number"><?= $roleCounts['student'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stat-card stat-card-clickable bg-warning text-white h-100" data-stat-type="others" role="button" tabindex="0" aria-label="Digər rolları göstər">
                <div class="card-body">
                    <p class="stat-title">Digər rollar</p>
                    <h3 class="stat-number"><?= $totalUsers - $roleCounts['teacher'] - $roleCounts['student'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-panel">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <label for="searchUser">Axtarış</label>
                <input type="text" class="form-control" id="searchUser" placeholder="İstifadəçi adı ilə axtarın...">
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <label for="filterRole">Rol</label>
                <select class="form-control" id="filterRole">
                    <option value="">Bütün rollar</option>
                    <?php foreach ($roleLabels as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                    <i class="fas fa-times mr-1"></i> Təmizlə
                </button>
            </div>
        </div>
    </div>

    <div class="users-table-card">
        <div class="card-header">
            <span><strong><?= $totalUsers ?></strong> istifadəçi</span>
            <a href="Hesablar.php" class="btn btn-primary btn-sm">
                <i class="fas fa-cog mr-1"></i> Hesabları idarə et
            </a>
        </div>
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users d-block"></i>
                <h5>Heç bir istifadəçi tapılmadı</h5>
                <p class="mb-0">Sistemdə hələ qeydiyyatdan keçmiş istifadəçi yoxdur.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table users-table mb-0" id="usersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>İstifadəçi adı</th>
                            <th>Rol</th>
                            <th>UID</th>
                            <?php if (($_SESSION['role'] ?? '') === 'super_admin'): ?>
                                <th>Şirkət ID</th>
                            <?php endif; ?>
                            <th>Qeydiyyat tarixi</th>
                            <th>Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $user): ?>
                            <tr data-username="<?= htmlspecialchars(strtolower($user['username']), ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td>
                                    <?php
                                    $canEditRole = isset($assignableRoles[$user['role']]) || $isSuperAdmin;
                                    $selectRoles = $assignableRoles;
                                    if (!isset($selectRoles[$user['role']])) {
                                        $selectRoles[$user['role']] = $roleLabels[$user['role']] ?? $user['role'];
                                    }
                                    ?>
                                    <?php if ($canEditRole): ?>
                                    <select class="form-control form-control-sm role-select"
                                            data-user-id="<?= (int) $user['id'] ?>"
                                            data-original-role="<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?php foreach ($selectRoles as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= $user['role'] === $key ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="role-update-msg" id="role-msg-<?= (int) $user['id'] ?>"></div>
                                    <?php else: ?>
                                    <span class="role-badge">
                                        <?= htmlspecialchars($roleLabels[$user['role']] ?? $user['role']) ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['u_id']) ?></td>
                                <?php if (($_SESSION['role'] ?? '') === 'super_admin'): ?>
                                    <td><?= htmlspecialchars((string) ($user['company_id'] ?? '-')) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($user['created_at']))) ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= (int) $user['id'] ?>" class="btn btn-outline-primary btn-edit">
                                        <i class="fas fa-edit"></i> Redaktə
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="empty-state" id="noResults" style="display: none;">
                <i class="fas fa-search d-block"></i>
                <h5>Heç bir nəticə tapılmadı</h5>
                <p class="mb-0">Axtarış kriteriyalarınızı dəyişdirin.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="users-table-card">
        <div class="empty-state">
            <i class="fas fa-lock d-block"></i>
            <h5>Giriş icazəsi yoxdur</h5>
            <p class="mb-0">Bu səhifəyə yalnız admin və super admin baxa bilər.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="statDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statDetailsTitle">Məlumatlar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
            </div>
            <div class="modal-body">
                <div id="statDetailsLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div class="table-responsive d-none" id="statDetailsContent">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light" id="statDetailsHead"></thead>
                        <tbody id="statDetailsBody"></tbody>
                    </table>
                </div>
                <div id="statDetailsEmpty" class="text-center py-4 text-muted d-none">Məlumat tapılmadı</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/app-style-switcher.js"></script>
<script src="../dist/js/feather.min.js"></script>
<script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="../dist/js/sidebarmenu.js"></script>
<script src="../dist/js/custom.min.js"></script>
<script>
$(function () {
    $('#rolePanelToggle').on('click', function () {
        $('#rolePanel').toggleClass('open');
    });

    function syncPasswordMode() {
        var isManual = $('input[name="password_mode"]:checked').val() === 'manual';
        $('#manualPasswordWrap').toggleClass('is-visible', isManual);
        $('#newPassword').prop('required', isManual);
        if (!isManual) {
            $('#newPassword').val('');
        }
    }

    $('input[name="password_mode"]').on('change', syncPasswordMode);
    syncPasswordMode();

    $('#addUserRoleForm').on('submit', function (event) {
        if ($('input[name="password_mode"]:checked').val() === 'manual') {
            var password = ($('#newPassword').val() || '').trim();
            if (password.length < 6) {
                event.preventDefault();
                alert('Şifrə ən azı 6 simvol olmalıdır.');
                $('#newPassword').focus();
            }
        }
    });

    function filterUsers() {
        var search = ($('#searchUser').val() || '').toLowerCase().trim();
        var role = $('#filterRole').val();
        var visible = 0;

        $('#usersTable tbody tr').each(function () {
            var $row = $(this);
            var matchSearch = !search || $row.data('username').indexOf(search) !== -1;
            var matchRole = !role || $row.data('role') === role;
            var show = matchSearch && matchRole;
            $row.toggle(show);
            if (show) visible++;
        });

        if ($('#usersTable').length) {
            $('#usersTable').toggle(visible > 0);
            $('#noResults').toggle(visible === 0);
        }
    }

    $('#searchUser').on('input', filterUsers);
    $('#filterRole').on('change', filterUsers);
    $('#clearFilters').on('click', function () {
        $('#searchUser').val('');
        $('#filterRole').val('');
        filterUsers();
    });

    $('.role-select').on('change', function () {
        var $select = $(this);
        var userId = $select.data('user-id');
        var newRole = $select.val();
        var originalRole = $select.data('original-role');
        var $msg = $('#role-msg-' + userId);

        if (newRole === originalRole) {
            $msg.removeClass('success error').text('');
            return;
        }

        $msg.removeClass('success error').text('Yenilənir...');

        $.ajax({
            url: 'update_user_role.php',
            method: 'POST',
            dataType: 'json',
            data: {
                user_id: userId,
                role: newRole,
                csrf_token: window.APP_CSRF_TOKEN || ''
            },
            headers: window.APP_CSRF_TOKEN ? { 'X-CSRF-Token': window.APP_CSRF_TOKEN } : {},
            success: function (res) {
                if (res.status === 'success') {
                    $select.data('original-role', newRole);
                    $select.closest('tr').attr('data-role', newRole);
                    $msg.removeClass('error').addClass('success').text(res.message || 'Yeniləndi');
                    setTimeout(function () { $msg.text(''); }, 2500);
                } else {
                    $select.val(originalRole);
                    $msg.removeClass('success').addClass('error').text(res.message || 'Xəta baş verdi');
                }
            },
            error: function (xhr) {
                $select.val(originalRole);
                var message = 'Xəta baş verdi';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.message) message = res.message;
                } catch (e) {}
                $msg.removeClass('success').addClass('error').text(message);
            }
        });
    });
});

(function () {
    var userStatTitles = {
        all: 'Bütün İstifadəçilər',
        teachers: 'Müəllimlər',
        students: 'Tələbələr',
        others: 'Digər Rollar'
    };

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function openUserStatModal(type) {
        var modalEl = document.getElementById('statDetailsModal');
        if (!modalEl) return;

        document.getElementById('statDetailsTitle').textContent = userStatTitles[type] || 'Məlumatlar';
        document.getElementById('statDetailsLoading').classList.remove('d-none');
        document.getElementById('statDetailsContent').classList.add('d-none');
        document.getElementById('statDetailsEmpty').classList.add('d-none');
        document.getElementById('statDetailsHead').innerHTML = '';
        document.getElementById('statDetailsBody').innerHTML = '';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        fetch('umumi_istifadeciler/stat_operations.php?type=' + encodeURIComponent(type))
            .then(function (response) { return response.json(); })
            .then(function (data) {
                document.getElementById('statDetailsLoading').classList.add('d-none');
                if (data.status !== 'success' || !data.data || !data.data.length) {
                    document.getElementById('statDetailsEmpty').classList.remove('d-none');
                    return;
                }
                renderStatTable(data.columns, data.data);
            })
            .catch(function () {
                document.getElementById('statDetailsLoading').classList.add('d-none');
                document.getElementById('statDetailsEmpty').classList.remove('d-none');
            });
    }

    function renderStatTable(columns, rows) {
        document.getElementById('statDetailsContent').classList.remove('d-none');
        var headHtml = '<tr>';
        columns.forEach(function (column) {
            headHtml += '<th>' + escapeHtml(column.label) + '</th>';
        });
        headHtml += '</tr>';
        document.getElementById('statDetailsHead').innerHTML = headHtml;

        var bodyHtml = '';
        rows.forEach(function (row) {
            bodyHtml += '<tr>';
            columns.forEach(function (column) {
                bodyHtml += '<td>' + escapeHtml(row[column.key] ?? '-') + '</td>';
            });
            bodyHtml += '</tr>';
        });
        document.getElementById('statDetailsBody').innerHTML = bodyHtml;
    }

    document.querySelectorAll('.stat-card-clickable').forEach(function (card) {
        card.addEventListener('click', function () {
            openUserStatModal(card.dataset.statType);
        });
        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openUserStatModal(card.dataset.statType);
            }
        });
    });
})();
</script>
</body>
</html>
