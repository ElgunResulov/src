<?php
include('db.php');
require_once __DIR__ . '/qeydiyyatar/services_helpers.php';

app_require_auth($conn);
app_require_role(['super_admin', 'admin']);

$prices = xidmet_get_all_prices($conn);
$openKey = trim((string) ($_GET['open'] ?? ''));

$flashSuccess = $_SESSION['xidmet_flash_success'] ?? '';
$flashError = $_SESSION['xidmet_flash_error'] ?? '';
unset($_SESSION['xidmet_flash_success'], $_SESSION['xidmet_flash_error']);

include('navbar_sidebar.php');
?>
<style>
    .xidmet-page {
        width: 100%;
        max-width: 100%;
        padding: 76px 12px 24px;
        box-sizing: border-box;
    }
    .xidmet-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    .xidmet-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0;
    }
    .xidmet-header p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: .92rem;
    }
    .section-title {
        background: #dbeafe;
        color: #1d4ed8;
        text-align: center;
        font-size: 17px;
        font-weight: 700;
        padding: 12px;
        margin: 28px 0 18px;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px 28px;
    }
    .service-column {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 4px 14px rgba(30, 58, 138, .06);
    }
    .service-column h4 {
        text-align: center;
        font-size: 15px;
        margin: 0 0 14px;
        color: #1d4ed8;
        text-transform: uppercase;
        font-weight: 700;
    }
    .subsection-title {
        margin: 10px 0 8px;
        font-size: 14px;
        color: #475569;
        font-weight: 600;
    }
    .divider {
        height: 1px;
        background: #bfdbfe;
        margin: 14px 0;
    }
    .service-item {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 10px;
        overflow: hidden;
        background: #f8fafc;
    }
    .service-item.open {
        border-color: #93c5fd;
        box-shadow: 0 4px 12px rgba(59, 130, 246, .12);
    }
    .service-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        cursor: pointer;
        user-select: none;
    }
    .service-summary:hover {
        background: #eff6ff;
    }
    .service-name {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
    }
    .service-prices {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 12px;
        color: #64748b;
    }
    .price-badge {
        background: #ecfdf5;
        color: #166534;
        border: 1px solid #86efac;
        border-radius: 999px;
        padding: 2px 8px;
        white-space: nowrap;
    }
    .price-badge.empty {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
    }
    .service-toggle {
        color: #3b82f6;
        font-size: 13px;
        flex-shrink: 0;
    }
    .service-panel {
        display: none;
        padding: 14px;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .service-item.open .service-panel {
        display: block;
    }
    .panel-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .panel-grid .full {
        grid-column: 1 / -1;
    }
    .panel-grid label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #1e3a8a;
        margin-bottom: 5px;
    }
    .panel-grid input[type="number"],
    .panel-grid textarea {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        font-size: 14px;
        background: #f0f9ff;
    }
    .panel-grid textarea {
        min-height: 70px;
        resize: vertical;
    }
    .panel-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 12px;
        flex-wrap: wrap;
    }
    .aktiv-check {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #334155;
        margin: 0;
    }
    .aktiv-check input {
        width: 16px;
        height: 16px;
        accent-color: #2563eb;
    }
    .save-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .save-btn:hover {
        opacity: .92;
    }
    .inactive-tag {
        font-size: 11px;
        background: #fee2e2;
        color: #b91c1c;
        border-radius: 999px;
        padding: 2px 8px;
    }

    @media (min-width: 1170px) {
        .xidmet-page {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 96px 24px 32px;
        }
    }
    @media (max-width: 1024px) {
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 768px) {
        .xidmet-page {
            padding: 72px 10px 20px;
        }
        .services-grid {
            grid-template-columns: 1fr;
        }
        .panel-grid {
            grid-template-columns: 1fr;
        }
        .service-summary {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="xidmet-page">
    <div class="xidmet-header">
        <div>
            <h1>Xidmət Qiymətləri</h1>
            <p>Qeydiyyat formundakı hər xidmət üçün aylıq və paket qiyməti təyin edin.</p>
        </div>
        <a href="Qeydiyyatar.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Qeydiyyat formu
        </a>
    </div>

    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="section-title">XİDMƏTLƏRİMİZ</div>

    <div class="services-grid">
        <?php
        $columnGroups = [
            ['Magistratura', 'Dövlət qulluğu'],
            ['MİQ və SERTİFİKASIYA', 'Xarici dil dərsləri', 'Doktorantura'],
            ['Digər xidmətlər'],
        ];
        $catalogByTitle = [];
        foreach (xidmet_get_catalog() as $group) {
            $catalogByTitle[$group['title']] = $group;
        }

        foreach ($columnGroups as $columnTitles):
        ?>
            <div class="service-column">
                <?php foreach ($columnTitles as $title):
                    $group = $catalogByTitle[$title] ?? null;
                    if (!$group) {
                        continue;
                    }
                ?>
                    <h4><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?></h4>

                    <?php foreach ($group['services'] ?? [] as $service):
                        $key = $service['key'];
                        $row = $prices[$key] ?? null;
                        $isOpen = $openKey === $key;
                        $ayliq = (float) ($row['qiymet_ayliq'] ?? 0);
                        $paket = (float) ($row['qiymet_paket'] ?? 0);
                        $aktiv = !isset($row['aktiv']) || (int) $row['aktiv'] === 1;
                    ?>
                        <div class="service-item<?= $isOpen ? ' open' : '' ?>" data-service="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="service-summary" onclick="toggleServicePanel(this)">
                                <div>
                                    <div class="service-name"><?= htmlspecialchars($service['label'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="service-prices">
                                        <span class="price-badge<?= $ayliq <= 0 ? ' empty' : '' ?>">Aylıq: <?= htmlspecialchars(xidmet_format_price($ayliq), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="price-badge<?= $paket <= 0 ? ' empty' : '' ?>">Paket: <?= htmlspecialchars(xidmet_format_price($paket), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!$aktiv): ?><span class="inactive-tag">Deaktiv</span><?php endif; ?>
                                    </div>
                                </div>
                                <span class="service-toggle"><i class="fas fa-chevron-down"></i> Qiymət paneli</span>
                            </div>
                            <div class="service-panel">
                                <form method="post" action="process_xidmet_qiymet.php">
                                    <?= app_csrf_field() ?>
                                    <input type="hidden" name="service_key" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="panel-grid">
                                        <div>
                                            <label>Aylıq qiymət (AZN)</label>
                                            <input type="number" step="0.01" min="0" name="qiymet_ayliq" value="<?= $ayliq > 0 ? htmlspecialchars(number_format($ayliq, 2, '.', ''), ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="0.00">
                                        </div>
                                        <div>
                                            <label>Paket qiymət (AZN)</label>
                                            <input type="number" step="0.01" min="0" name="qiymet_paket" value="<?= $paket > 0 ? htmlspecialchars(number_format($paket, 2, '.', ''), ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="0.00">
                                        </div>
                                        <div class="full">
                                            <label>Qeyd (istəyə bağlı)</label>
                                            <textarea name="qeyd" placeholder="Məs: materiallar daxildir..."><?= htmlspecialchars((string) ($row['qeyd'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        </div>
                                    </div>
                                    <div class="panel-actions">
                                        <label class="aktiv-check">
                                            <input type="checkbox" name="aktiv" value="1"<?= $aktiv ? ' checked' : '' ?>>
                                            Aktiv xidmət
                                        </label>
                                        <button type="submit" class="save-btn">
                                            <i class="fas fa-save me-1"></i> Saxla
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($group['subsections'] ?? [] as $subsection): ?>
                        <div class="subsection-title"><?= htmlspecialchars($subsection['title'], ENT_QUOTES, 'UTF-8') ?>:</div>
                        <?php foreach ($subsection['services'] ?? [] as $service):
                            $key = $service['key'];
                            $row = $prices[$key] ?? null;
                            $isOpen = $openKey === $key;
                            $ayliq = (float) ($row['qiymet_ayliq'] ?? 0);
                            $paket = (float) ($row['qiymet_paket'] ?? 0);
                            $aktiv = !isset($row['aktiv']) || (int) $row['aktiv'] === 1;
                        ?>
                            <div class="service-item<?= $isOpen ? ' open' : '' ?>" data-service="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="service-summary" onclick="toggleServicePanel(this)">
                                    <div>
                                        <div class="service-name"><?= htmlspecialchars($service['label'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="service-prices">
                                            <span class="price-badge<?= $ayliq <= 0 ? ' empty' : '' ?>">Aylıq: <?= htmlspecialchars(xidmet_format_price($ayliq), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="price-badge<?= $paket <= 0 ? ' empty' : '' ?>">Paket: <?= htmlspecialchars(xidmet_format_price($paket), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!$aktiv): ?><span class="inactive-tag">Deaktiv</span><?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="service-toggle"><i class="fas fa-chevron-down"></i> Qiymət paneli</span>
                                </div>
                                <div class="service-panel">
                                    <form method="post" action="process_xidmet_qiymet.php">
                                        <?= app_csrf_field() ?>
                                        <input type="hidden" name="service_key" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="panel-grid">
                                            <div>
                                                <label>Aylıq qiymət (AZN)</label>
                                                <input type="number" step="0.01" min="0" name="qiymet_ayliq" value="<?= $ayliq > 0 ? htmlspecialchars(number_format($ayliq, 2, '.', ''), ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="0.00">
                                            </div>
                                            <div>
                                                <label>Paket qiymət (AZN)</label>
                                                <input type="number" step="0.01" min="0" name="qiymet_paket" value="<?= $paket > 0 ? htmlspecialchars(number_format($paket, 2, '.', ''), ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="0.00">
                                            </div>
                                            <div class="full">
                                                <label>Qeyd (istəyə bağlı)</label>
                                                <textarea name="qeyd" placeholder="Məs: materiallar daxildir..."><?= htmlspecialchars((string) ($row['qeyd'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="panel-actions">
                                            <label class="aktiv-check">
                                                <input type="checkbox" name="aktiv" value="1"<?= $aktiv ? ' checked' : '' ?>>
                                                Aktiv xidmət
                                            </label>
                                            <button type="submit" class="save-btn">
                                                <i class="fas fa-save me-1"></i> Saxla
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                    <?php if ($title !== end($columnTitles)): ?>
                        <div class="divider"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleServicePanel(summaryEl) {
    const item = summaryEl.closest('.service-item');
    const wasOpen = item.classList.contains('open');

    document.querySelectorAll('.service-item.open').forEach(function(el) {
        el.classList.remove('open');
    });

    if (!wasOpen) {
        item.classList.add('open');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const openKey = <?= json_encode($openKey, JSON_UNESCAPED_UNICODE) ?>;
    if (!openKey) {
        return;
    }

    const target = document.querySelector('.service-item[data-service="' + CSS.escape(openKey) + '"]');
    if (target) {
        target.classList.add('open');
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>
