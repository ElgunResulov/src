<?php
/** @var array $todayPanel */
/** @var array $weekPanel */
/** @var array $salaryReport */
/** @var array $alerts */
/** @var string $selected_teacher_username */
/** @var string $selected_week_group */
/** @var array $current_teacher */

if (empty($current_teacher)) {
    return;
}
?>

<style>
.att-panel {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    padding: 24px;
    margin-bottom: 24px;
}
.att-panel h2 {
    font-size: 1.25rem;
    margin-bottom: 8px;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 8px;
}
.att-panel .att-sub {
    color: var(--gray-500);
    margin-bottom: 16px;
    font-size: 0.95rem;
}
.att-counters {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}
.att-chip {
    background: var(--gray-100);
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 0.9rem;
    color: var(--gray-700);
}
.att-chip strong { color: var(--gray-900); }
.att-table-wrap { overflow-x: auto; }
.att-table {
    width: 100%;
    border-collapse: collapse;
}
.att-table th, .att-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--gray-200);
    text-align: left;
    font-size: 0.92rem;
}
.att-table th {
    background: var(--gray-100);
    color: var(--gray-600);
    font-weight: 600;
}
.att-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
}
.att-badge-pending { background: #fef3c7; color: #92400e; }
.att-badge-scanned { background: #d1fae5; color: #065f46; }
.att-badge-extra { background: #e0e7ff; color: #3730a3; }
.att-badge-alert { background: #fee2e2; color: #991b1b; }
.att-progress {
    height: 8px;
    background: var(--gray-200);
    border-radius: 999px;
    overflow: hidden;
    min-width: 80px;
}
.att-progress > span {
    display: block;
    height: 100%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}
.att-row-pending { background: #fffbeb; }
.att-row-near { box-shadow: inset 3px 0 0 #f59e0b; }
.att-week-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 768px) {
    .att-week-grid { grid-template-columns: 1fr; }
}
.att-week-day {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius-sm);
    padding: 14px;
}
.att-week-day h3 {
    font-size: 1rem;
    margin-bottom: 10px;
}
.att-week-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed var(--gray-200);
    font-size: 0.9rem;
}
.att-alerts {
    display: grid;
    gap: 12px;
}
.att-alert-box {
    border-left: 4px solid var(--warning-color);
    background: #fffbeb;
    padding: 12px 14px;
    border-radius: 0 var(--border-radius-sm) var(--border-radius-sm) 0;
}
.att-alert-box.danger {
    border-left-color: var(--danger-color);
    background: #fef2f2;
}
.att-alert-box.info {
    border-left-color: var(--info-color);
    background: #eff6ff;
}
.att-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
    align-items: center;
}
.att-toolbar select, .att-toolbar .btn-att {
    padding: 8px 12px;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius-sm);
    background: var(--white);
    font-size: 0.9rem;
}
.att-toolbar .btn-att {
    cursor: pointer;
    text-decoration: none;
    color: var(--gray-800);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.att-toolbar .btn-att:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}
.att-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--success-color);
    display: inline-block;
    animation: attPulse 1.4s infinite;
}
@keyframes attPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.35; }
}
</style>

<!-- Bu gün + xəbərdarlıqlar -->
<section class="att-panel" id="attTodayPanel">
    <h2><i class="fas fa-users"></i> Bu günün tələbələri</h2>
    <p class="att-sub">
        <?php echo htmlspecialchars($todayPanel['day_name'] ?: '—'); ?>
        <?php if (!empty($todayPanel['gun_qrupu'])): ?>
            · Qrup <?php echo htmlspecialchars($todayPanel['gun_qrupu']); ?>
        <?php endif; ?>
        <span style="margin-left:8px;"><span class="att-live-dot"></span> canlı</span>
    </p>
    <div class="att-counters">
        <div class="att-chip">Gözlənilən: <strong><?php echo (int) $todayPanel['expected']; ?></strong></div>
        <div class="att-chip">Skan: <strong id="attLiveScanned"><?php echo (int) $todayPanel['scanned']; ?></strong></div>
        <div class="att-chip">Qalan: <strong id="attLivePending"><?php echo (int) $todayPanel['pending']; ?></strong></div>
    </div>
    <div class="att-toolbar">
        <a class="btn-att" href="qr_muellim/print_qr.php?u_id=<?php echo urlencode((string) ($current_teacher['u_id'] ?? '')); ?>&amp;teacher=<?php echo rawurlencode($selected_teacher_username); ?>" target="_blank" rel="noopener">
            <i class="fas fa-print"></i> QR çap
        </a>
        <a class="btn-att" href="qr_muellim/jurnal.php?u_id=<?php echo urlencode((string) ($current_teacher['u_id'] ?? '')); ?>&amp;teacher=<?php echo rawurlencode($selected_teacher_username); ?>&amp;group=<?php echo urlencode((string) ($todayPanel['gun_qrupu'] ?: $selected_week_group)); ?>&amp;year=<?php echo (int) $selected_year; ?>&amp;month=<?php echo (int) $selected_month; ?>" target="_blank" rel="noopener">
            <i class="fas fa-table"></i> Davamiyyət cədvəli / Çap
        </a>
    </div>
    <div class="att-table-wrap">
        <table class="att-table" id="attTodayTable">
            <thead>
                <tr>
                    <th>Tələbə</th>
                    <th>Qrup</th>
                    <th>Saat</th>
                    <th>Status</th>
                    <th>8-lik</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($todayPanel['students'])): ?>
                <tr><td colspan="6">Bu gün cədvəldə tələbə yoxdur (və ya skan yoxdur).</td></tr>
            <?php else: ?>
                <?php foreach ($todayPanel['students'] as $s): ?>
                    <tr class="<?php echo $s['status'] === 'pending' ? 'att-row-pending' : ''; ?> <?php echo !empty($s['alert_near_complete']) ? 'att-row-near' : ''; ?>"
                        data-student="<?php echo htmlspecialchars($s['username'], ENT_QUOTES, 'UTF-8'); ?>">
                        <td><?php echo htmlspecialchars($s['username']); ?></td>
                        <td><?php echo htmlspecialchars($s['gun_qrupu'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($s['saat'] ?: '—'); ?></td>
                        <td>
                            <span class="att-badge att-badge-<?php echo htmlspecialchars($s['status']); ?>">
                                <?php echo htmlspecialchars(att_status_label($s['status'])); ?>
                            </span>
                            <?php if (!empty($s['alert_near_complete'])): ?>
                                <span class="att-badge att-badge-alert">7/8 — növbəti dərsdə bağlanır</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($s['cycle']['label']); ?></td>
                        <td>
                            <div class="att-progress" title="<?php echo (int) $s['cycle']['percent']; ?>%">
                                <span style="width: <?php echo (int) $s['cycle']['percent']; ?>%;"></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (!empty($alerts['pending_today']) || !empty($alerts['near_complete']) || !empty($alerts['inactive'])): ?>
<section class="att-panel">
    <h2><i class="fas fa-bell"></i> Xəbərdarlıqlar</h2>
    <div class="att-alerts">
        <?php if (!empty($alerts['pending_today'])): ?>
            <div class="att-alert-box danger">
                <strong>Bu gün skan etməyənlər (<?php echo count($alerts['pending_today']); ?>):</strong>
                <?php echo htmlspecialchars(implode(', ', array_column($alerts['pending_today'], 'username'))); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($alerts['near_complete'])): ?>
            <div class="att-alert-box">
                <strong>7/8 — növbəti dərsdə dövr bağlanır:</strong>
                <?php echo htmlspecialchars(implode(', ', array_column($alerts['near_complete'], 'username'))); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($alerts['inactive'])): ?>
            <div class="att-alert-box info">
                <strong>14+ gündür gəlməyənlər:</strong>
                <?php
                    $names = [];
                    foreach ($alerts['inactive'] as $row) {
                        $names[] = $row['username'] . ' (son: ' . substr((string) $row['last_scan_date'], 0, 10) . ')';
                    }
                    echo htmlspecialchars(implode(', ', $names));
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- Həftə görünüşü -->
<section class="att-panel">
    <h2><i class="fas fa-calendar-week"></i> Həftə görünüşü</h2>
    <p class="att-sub"><?php echo htmlspecialchars($weekPanel['summary']); ?></p>
    <form method="POST" class="att-toolbar">
        <label for="weekGroupSelect">Qrup:</label>
        <select name="week_group" id="weekGroupSelect" onchange="this.form.submit()">
            <?php foreach (array_keys(ATT_DAY_GROUPS) as $g): ?>
                <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $selected_week_group === $g ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($g); ?>
                    (<?php echo htmlspecialchars(implode(' + ', att_group_day_names($g))); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="selected_teacher" value="<?php echo htmlspecialchars($selected_teacher_username, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="selected_month" value="<?php echo htmlspecialchars((string) $selected_month, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="selected_year" value="<?php echo htmlspecialchars((string) $selected_year, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="selected_week" value="<?php echo htmlspecialchars($selected_week, ENT_QUOTES, 'UTF-8'); ?>">
        <noscript><button type="submit" class="btn-att">Göstər</button></noscript>
    </form>
    <div class="att-week-grid">
        <?php foreach ($weekPanel['days'] as $day): ?>
            <div class="att-week-day">
                <h3>
                    <?php echo htmlspecialchars($day['day_name']); ?>
                    <small style="color:var(--gray-500);font-weight:400;">
                        · <?php echo htmlspecialchars($day['date']); ?>
                        · <?php echo (int) $day['scanned_count']; ?>/<?php echo (int) $day['total']; ?>
                    </small>
                </h3>
                <?php if (empty($day['students'])): ?>
                    <p style="color:var(--gray-500);font-size:0.9rem;">Tələbə yoxdur</p>
                <?php else: ?>
                    <?php foreach ($day['students'] as $ws): ?>
                        <div class="att-week-item">
                            <span><?php echo htmlspecialchars($ws['username']); ?></span>
                            <span class="att-badge <?php echo $ws['scanned'] ? 'att-badge-scanned' : 'att-badge-pending'; ?>">
                                <?php echo $ws['scanned'] ? 'Skan oldu' : 'Gözlənilir'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Maaş paneli -->
<section class="att-panel">
    <h2><i class="fas fa-coins"></i> Maaş vahidi hesabatı</h2>
    <p class="att-sub">
        Dövr: <?php echo htmlspecialchars($salaryReport['period_start']); ?> — <?php echo htmlspecialchars($salaryReport['period_end']); ?>
        · 1 vahid = <?php echo ATT_CYCLE_SIZE; ?> dərs
    </p>
    <div class="att-counters">
        <div class="att-chip">Bu dövrdə bağlanan: <strong><?php echo (int) $salaryReport['total_units_period']; ?></strong></div>
        <div class="att-chip">Ömür boyu vahid: <strong><?php echo (int) $salaryReport['total_units_lifetime']; ?></strong></div>
        <?php if ($salaryReport['rate_azn'] > 0): ?>
            <div class="att-chip">AZN: <strong><?php echo number_format($salaryReport['amount_azn'], 2); ?></strong></div>
        <?php endif; ?>
    </div>
    <div class="att-table-wrap">
        <table class="att-table">
            <thead>
                <tr>
                    <th>Tələbə</th>
                    <th>Qrup</th>
                    <th>Natamam</th>
                    <th>Dövrdə vahid</th>
                    <th>Ömür boyu vahid</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($salaryReport['students'])): ?>
                <tr><td colspan="6">Məlumat yoxdur.</td></tr>
            <?php else: ?>
                <?php foreach ($salaryReport['students'] as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['username']); ?></td>
                        <td><?php echo htmlspecialchars($s['gun_qrupu'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($s['cycle']['label']); ?></td>
                        <td><?php echo (int) $s['units_period']; ?></td>
                        <td><?php echo (int) $s['units_lifetime']; ?></td>
                        <td>
                            <div class="att-progress">
                                <span style="width: <?php echo (int) $s['cycle']['percent']; ?>%;"></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    var teacher = <?php echo json_encode($selected_teacher_username, JSON_UNESCAPED_UNICODE); ?>;
    var teacherUid = <?php echo json_encode((string) ($current_teacher['u_id'] ?? ''), JSON_UNESCAPED_UNICODE); ?>;
    if (!teacher && !teacherUid) return;

    function refreshLive() {
        var url = 'qr_muellim/live.php?u_id=' + encodeURIComponent(teacherUid || '')
            + '&teacher=' + encodeURIComponent(teacher || '');
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.status !== 'success') return;
                var scannedEl = document.getElementById('attLiveScanned');
                var pendingEl = document.getElementById('attLivePending');
                if (scannedEl) scannedEl.textContent = data.scanned;
                if (pendingEl) pendingEl.textContent = data.pending;
                (data.students || []).forEach(function (s) {
                    var safe = (window.CSS && CSS.escape) ? CSS.escape(s.username) : String(s.username).replace(/"/g, '\\"');
                    var row = document.querySelector('#attTodayTable tr[data-student="' + safe + '"]');
                    if (!row) return;
                    var badge = row.querySelector('.att-badge');
                    if (!badge) return;
                    badge.className = 'att-badge att-badge-' + s.status;
                    badge.textContent = s.status_label || s.status;
                    if (s.status === 'scanned') {
                        row.classList.remove('att-row-pending');
                    }
                });
            })
            .catch(function () {});
    }

    setInterval(refreshLive, 8000);
})();
</script>
