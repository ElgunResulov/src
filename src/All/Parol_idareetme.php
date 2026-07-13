<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'admin'], true)) {
    header('Location: index.php');
    exit();
}

include('navbar_sidebar.php');
include('db.php');
require_once __DIR__ . '/user_credentials_helper.php';

app_ensure_plain_password_column($conn);
$roleLabels = app_user_role_labels();
$users = app_fetch_manageable_users($conn, $_SESSION['role'], (int) ($_SESSION['company_id'] ?? 0));
$missingCount = count(array_filter($users, static fn($u) => ($u['visible_password'] ?? '') === 'Mövcud deyil'));
$conn->close();
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Parol İdarəetməsi - TIS</title>
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-page.main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 20px;
            max-width: 100%;
            transition: margin-left 0.3s ease;
        }

        .password-page-header {
            gap: 16px;
            margin-bottom: 20px;
        }

        .password-page-header h3 {
            font-size: 1.5rem;
        }

        .password-panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            padding: 24px;
            margin-bottom: 24px;
            width: 100%;
        }

        .password-panel h5 {
            margin-bottom: 16px;
            font-size: 1.05rem;
        }

        .stat-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .stat-chip.warning {
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
        }

        .password-preview {
            font-family: Consolas, monospace;
            font-size: clamp(1rem, 4vw, 1.5rem);
            letter-spacing: 1px;
            word-break: break-all;
            text-align: center;
            padding: 16px 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            min-height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-table td,
        .password-table th {
            vertical-align: middle;
        }

        .pw-hidden {
            filter: blur(6px);
            user-select: none;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .action-row .btn {
            flex: 1 1 auto;
            min-width: 140px;
        }

        .panel-section + .panel-section {
            margin-top: 8px;
        }

        .table-toolbar {
            gap: 12px;
        }

        .password-table .text-right {
            text-align: right;
            white-space: nowrap;
        }

        #resultModal .modal-dialog {
            margin: 1rem;
        }

        @media (min-width: 769px) {
            .password-page.main-content {
                margin-left: 250px;
                padding: 24px;
            }
        }

        @media (max-width: 992px) {
            .password-panel {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .password-page.main-content {
                margin-left: 0;
                margin-top: 64px;
                padding: 12px;
            }

            .password-page-header {
                flex-direction: column;
                align-items: stretch !important;
            }

            .password-page-header .btn {
                width: 100%;
            }

            .password-panel {
                padding: 16px;
                border-radius: 14px;
                margin-bottom: 16px;
            }

            .panel-section {
                margin-bottom: 24px;
            }

            .panel-section:last-child {
                margin-bottom: 0;
            }

            .action-row .btn {
                width: 100%;
                min-width: 0;
            }

            .table-toolbar {
                flex-direction: column;
                align-items: stretch !important;
            }

            .table-toolbar .btn {
                width: 100%;
            }

            .password-table thead {
                display: none;
            }

            .password-table,
            .password-table tbody,
            .password-table tr,
            .password-table td {
                display: block;
                width: 100%;
            }

            .password-table tr {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 12px 14px;
                margin-bottom: 12px;
            }

            .password-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 8px 0;
                border: none;
                text-align: right;
            }

            .password-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #475569;
                text-align: left;
                flex: 0 0 auto;
            }

            .password-table td.text-right {
                justify-content: space-between;
                text-align: right;
            }

            .password-table td.text-right .btn {
                width: auto;
                min-width: 0;
            }

            .pw-value {
                max-width: 55%;
                overflow-wrap: anywhere;
            }
        }

        @media (max-width: 480px) {
            .password-page-header h3 {
                font-size: 1.25rem;
            }

            .stat-chip {
                width: 100%;
                justify-content: center;
            }

            .password-preview {
                min-height: 56px;
                padding: 12px 8px;
            }

            #resultModal .modal-footer {
                flex-direction: column;
                gap: 8px;
            }

            #resultModal .modal-footer .btn {
                width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>
<div class="main-content password-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap password-page-header">
        <div>
            <h3 class="mb-1"><i class="fas fa-key text-primary"></i> Parol İdarəetməsi</h3>
            <p class="text-muted mb-0">Bütün istifadəçilər üçün mərkəzləşdirilmiş şifrə yaratma və yeniləmə bölməsi</p>
        </div>
        <a href="Hesablar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Hesablar
        </a>
    </div>

    <div class="password-panel">
        <div class="stat-chips">
            <span class="stat-chip"><i class="fas fa-users"></i> <?= count($users) ?> istifadəçi</span>
            <?php if ($missingCount > 0): ?>
                <span class="stat-chip warning"><i class="fas fa-exclamation-triangle"></i> <?= $missingCount ?> köhnə hesab</span>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-lg-5 panel-section">
                <h5><i class="fas fa-magic"></i> Yeni şifrə yarat</h5>
                <div class="form-group">
                    <label for="generatorLength">Şifrə uzunluğu</label>
                    <select id="generatorLength" class="form-control">
                        <option value="6">6 simvol</option>
                        <option value="8" selected>8 simvol</option>
                        <option value="10">10 simvol</option>
                        <option value="12">12 simvol</option>
                    </select>
                </div>
                <div class="password-preview mb-3" id="generatedPasswordPreview">—</div>
                <div class="action-row mb-4">
                    <button type="button" class="btn btn-primary" id="generatePasswordBtn">
                        <i class="fas fa-sync-alt"></i> Şifrə yarat
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="copyGeneratedBtn">
                        <i class="fas fa-copy"></i> Kopyala
                    </button>
                </div>
            </div>

            <div class="col-lg-7 panel-section">
                <h5><i class="fas fa-user-cog"></i> İstifadəçiyə şifrə təyin et</h5>
                <div class="form-group">
                    <label for="targetUserId">İstifadəçi</label>
                    <select id="targetUserId" class="form-control">
                        <option value="">Seçin...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int) $user['id'] ?>">
                                <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($roleLabels[$user['role']] ?? $user['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="customPassword">Öz şifrənizi yazın (boş buraxsanız avtomatik yaradılır)</label>
                    <input type="text" id="customPassword" class="form-control" placeholder="Məs: Abc12345">
                </div>
                <div class="action-row">
                    <button type="button" class="btn btn-success" id="applyPasswordBtn">
                        <i class="fas fa-check"></i> Şifrəni təyin et
                    </button>
                    <button type="button" class="btn btn-warning" id="useGeneratedBtn">
                        <i class="fas fa-arrow-down"></i> Yuxarıdakı şifrəni istifadə et
                    </button>
                    <?php if ($missingCount > 0): ?>
                    <button type="button" class="btn btn-outline-danger" id="resetMissingBtn">
                        <i class="fas fa-tools"></i> Köhnə hesablara şifrə ver (<?= $missingCount ?>)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="password-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap table-toolbar">
            <h5 class="mb-0"><i class="fas fa-list"></i> Bütün istifadəçi şifrələri</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleAllPasswordsBtn">
                <i class="fas fa-eye"></i> <span class="toggle-label">Hamısını göstər</span>
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover password-table mb-0">
                <thead>
                    <tr>
                        <th>İstifadəçi</th>
                        <th>Rol</th>
                        <th>Şifrə</th>
                        <th>Status</th>
                        <th class="text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody id="passwordTableBody">
                    <?php foreach ($users as $user): ?>
                        <?php
                        $visible = $user['visible_password'];
                        $hasPlain = $visible !== 'Mövcud deyil' && $visible !== '—';
                        ?>
                        <tr data-user-id="<?= (int) $user['id'] ?>">
                            <td data-label="İstifadəçi"><?= htmlspecialchars($user['username']) ?></td>
                            <td data-label="Rol"><?= htmlspecialchars($roleLabels[$user['role']] ?? $user['role']) ?></td>
                            <td data-label="Şifrə">
                                <span class="pw-value <?= $hasPlain ? 'pw-hidden' : '' ?>" data-password="<?= htmlspecialchars($visible, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($visible) ?>
                                </span>
                            </td>
                            <td data-label="Status">
                                <?php if ($hasPlain): ?>
                                    <span class="badge badge-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Yenilənməli</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right" data-label="Əməliyyat">
                                <button type="button" class="btn btn-sm btn-outline-primary row-reset-btn" data-user-id="<?= (int) $user['id'] ?>">
                                    <i class="fas fa-redo"></i> <span class="d-none d-md-inline">Yenilə</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Şifrə yeniləndi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
            </div>
            <div class="modal-body">
                <p id="resultMessage" class="mb-3"></p>
                <div class="password-preview" id="resultPassword"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="copyResultBtn"><i class="fas fa-copy"></i> Kopyala</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrfToken = window.APP_CSRF_TOKEN || '';
    let currentGenerated = '';
    let showAllPasswords = false;

    function postAction(action, data) {
        const body = new URLSearchParams({ action, csrf_token: csrfToken, ...data });
        return fetch('parol_idareetme/operations.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body
        }).then(r => r.json());
    }

    function setGenerated(password) {
        currentGenerated = password || '';
        document.getElementById('generatedPasswordPreview').textContent = currentGenerated || '—';
    }

    function showResult(message, password) {
        document.getElementById('resultMessage').textContent = message;
        document.getElementById('resultPassword').textContent = password;
        if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('resultModal')).show();
        }
    }

    document.getElementById('generatePasswordBtn').addEventListener('click', function () {
        const length = document.getElementById('generatorLength').value;
        postAction('generate', { length }).then(function (res) {
            if (!res.success) throw new Error(res.message || 'Xəta');
            setGenerated(res.password);
        }).catch(function (e) { alert(e.message); });
    });

    document.getElementById('copyGeneratedBtn').addEventListener('click', function () {
        if (!currentGenerated) return;
        navigator.clipboard.writeText(currentGenerated).catch(function () {
            alert('Kopyalama alınmadı');
        });
    });

    document.getElementById('useGeneratedBtn').addEventListener('click', function () {
        if (!currentGenerated) {
            alert('Əvvəlcə şifrə yaradın.');
            return;
        }
        document.getElementById('customPassword').value = currentGenerated;
    });

    document.getElementById('applyPasswordBtn').addEventListener('click', function () {
        const userId = document.getElementById('targetUserId').value;
        if (!userId) {
            alert('İstifadəçi seçin.');
            return;
        }
        const password = document.getElementById('customPassword').value.trim();
        const length = document.getElementById('generatorLength').value;
        postAction('reset', { user_id: userId, password, length }).then(function (res) {
            if (!res.success) throw new Error(res.message || 'Xəta');
            showResult(res.message + ' (' + res.username + ')', res.password);
            setTimeout(function () { window.location.reload(); }, 1500);
        }).catch(function (e) { alert(e.message); });
    });

    const resetMissingBtn = document.getElementById('resetMissingBtn');
    if (resetMissingBtn) {
        resetMissingBtn.addEventListener('click', function () {
            if (!confirm('Köhnə hesablar üçün yeni şifrələr yaradılsın?')) return;
            postAction('reset_missing', {}).then(function (res) {
                if (!res.success) throw new Error(res.message || 'Xəta');
                alert(res.message);
                window.location.reload();
            }).catch(function (e) { alert(e.message); });
        });
    }

    document.querySelectorAll('.row-reset-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const userId = this.dataset.userId;
            const length = document.getElementById('generatorLength').value;
            if (!confirm('Bu istifadəçi üçün yeni şifrə yaradılsın?')) return;
            postAction('reset', { user_id: userId, password: '', length }).then(function (res) {
                if (!res.success) throw new Error(res.message || 'Xəta');
                showResult(res.message + ' (' + res.username + ')', res.password);
                setTimeout(function () { window.location.reload(); }, 1500);
            }).catch(function (e) { alert(e.message); });
        });
    });

    document.getElementById('toggleAllPasswordsBtn').addEventListener('click', function () {
        showAllPasswords = !showAllPasswords;
        document.querySelectorAll('.pw-value').forEach(function (el) {
            el.classList.toggle('pw-hidden', !showAllPasswords && el.dataset.password !== 'Mövcud deyil' && el.dataset.password !== '—');
        });
        const label = this.querySelector('.toggle-label');
        const icon = showAllPasswords ? 'fa-eye-slash' : 'fa-eye';
        const text = showAllPasswords ? 'Gizlət' : 'Hamısını göstər';
        this.innerHTML = '<i class="fas ' + icon + '"></i> <span class="toggle-label">' + text + '</span>';
    });

    document.getElementById('copyResultBtn').addEventListener('click', function () {
        const text = document.getElementById('resultPassword').textContent;
        navigator.clipboard.writeText(text).catch(function () { alert('Kopyalama alınmadı'); });
    });

    setGenerated('');
    document.getElementById('generatePasswordBtn').click();
})();
</script>
</body>
</html>
