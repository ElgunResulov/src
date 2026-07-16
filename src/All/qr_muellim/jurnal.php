<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../db.php';
require_once __DIR__ . '/../muellim/attendance_helpers.php';
app_require_auth($conn);

$role = $_SESSION['role'] ?? '';
$sessionUser = trim((string) ($_SESSION['username'] ?? ''));
$sessionUId = trim((string) ($_SESSION['u_id'] ?? ''));
$sessionFin = trim((string) ($_SESSION['fin_kod'] ?? ''));
$teacher = trim((string) ($_GET['teacher'] ?? ''));
$uId = trim((string) ($_GET['u_id'] ?? ''));

if (in_array($role, ['super_admin', 'admin'], true)) {
    // ok
} elseif ($role === 'teacher') {
    $teacher = $sessionUser;
    $uId = $sessionUId;
} else {
    http_response_code(403);
    echo 'İcazə yoxdur.';
    exit;
}

$row = att_resolve_teacher_row($conn, $teacher, $uId, $sessionFin);
if (!$row) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo 'Müəllim tapılmadı.';
    exit;
}

$group = trim((string) ($_GET['group'] ?? ''));
if (!isset(ATT_DAY_GROUPS[$group])) {
    $group = att_group_for_iso(att_today_iso()) ?: '1-4';
}

$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));
if ($year < 2020 || $year > 2100) {
    $year = (int) date('Y');
}
if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}

$sheet = att_build_journal_sheet(
    $conn,
    (string) $row['username'],
    $row['telebeler'] ?? null,
    $group,
    $year,
    $month
);

$teacherDisplay = str_replace('.', ' ', (string) $row['username']);
$studentCount = count($sheet['students']);
// Minimum 30 tələbə sətiri (boşlar da çapda qalır)
$minRows = max(30, $studentCount);
$rowPad = max(0, $minRows - $studentCount);

$prev = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month');
$next = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('+1 month');
$baseQuery = http_build_query([
    'u_id' => $row['u_id'] ?? '',
    'teacher' => $row['username'] ?? '',
    'group' => $group,
]);
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --ink: #111;
            --line: #222;
            --muted: #555;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            font-family: "Times New Roman", Times, serif;
            color: var(--ink);
            background: #f3f4f6;
        }
        .sheet {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            padding: 18px 20px 24px;
            border: 1px solid #ddd;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 14px;
        }
        .toolbar a, .toolbar button, .toolbar select {
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 7px 12px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            color: #111;
        }
        .toolbar button.primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin: 4px 0 12px;
            text-transform: uppercase;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .meta strong { font-weight: 700; }
        .qrup-box {
            border: 1px solid var(--line);
            padding: 8px 12px;
            min-width: 220px;
        }
        .qrup-box .line { margin: 2px 0; }
        table.jurnal {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 13px;
        }
        table.jurnal th, table.jurnal td {
            border: 1px solid var(--line);
            padding: 4px 2px;
            text-align: center;
            height: 26px;
            vertical-align: middle;
        }
        table.jurnal th.asa, table.jurnal td.asa {
            width: 200px;
            text-align: left;
            padding-left: 8px;
            font-weight: 600;
            font-size: 13px;
        }
        table.jurnal thead th.tarix-head {
            font-size: 12px;
            letter-spacing: 1px;
        }
        table.jurnal .mark {
            font-weight: 700;
            font-size: 15px;
        }
        .footer {
            margin-top: 18px;
            font-size: 13px;
        }
        .footer .notes {
            margin-bottom: 18px;
        }
        .footer .notes .blank {
            border-bottom: 1px solid var(--line);
            height: 22px;
            margin: 6px 0;
        }
        .footer .sign {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            align-items: baseline;
        }
        .footer .sign .line {
            min-width: 220px;
            border-bottom: 1px solid var(--line);
            display: inline-block;
            padding: 0 8px 2px;
            font-weight: 600;
        }
        .hint {
            margin-top: 8px;
            color: var(--muted);
            font-size: 11px;
            font-family: Arial, sans-serif;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 5mm;
            }
            html, body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100%;
                height: 100%;
            }
            .toolbar, .hint, .no-print { display: none !important; }
            .sheet {
                border: none !important;
                max-width: none !important;
                width: 100% !important;
                padding: 2mm !important;
                margin: 0 !important;
                transform-origin: top center;
            }
            .title {
                font-size: 16px !important;
                margin: 0 0 6px !important;
            }
            .meta {
                font-size: 12px !important;
                margin-bottom: 6px !important;
                gap: 10px !important;
            }
            .qrup-box {
                padding: 4px 8px !important;
                min-width: 180px !important;
            }
            .qrup-box .line { margin: 1px 0 !important; }
            table.jurnal {
                font-size: 11px !important;
            }
            table.jurnal th, table.jurnal td {
                padding: 2px 1px !important;
                height: 18px !important;
                line-height: 1.15 !important;
            }
            table.jurnal th.asa, table.jurnal td.asa {
                width: 150px !important;
                padding-left: 5px !important;
                font-size: 11px !important;
            }
            table.jurnal thead th.tarix-head {
                font-size: 11px !important;
                letter-spacing: 0.5px !important;
            }
            table.jurnal .mark {
                font-size: 13px !important;
            }
            .footer {
                margin-top: 8px !important;
                font-size: 12px !important;
            }
            .footer .notes {
                margin-bottom: 8px !important;
            }
            .footer .notes .blank {
                height: 16px !important;
                margin: 3px 0 !important;
            }
            .footer .sign .line {
                min-width: 180px !important;
            }
        }
    </style>
</head>
<body>
    <div class="sheet" id="printSheet">
        <div class="toolbar no-print">
            <button type="button" class="primary" id="btnPrint">Çap et (1 səhifə)</button>
            <a href="?<?php echo htmlspecialchars($baseQuery . '&year=' . $prev->format('Y') . '&month=' . $prev->format('n'), ENT_QUOTES, 'UTF-8'); ?>">← Əvvəlki ay</a>
            <a href="?<?php echo htmlspecialchars($baseQuery . '&year=' . $next->format('Y') . '&month=' . $next->format('n'), ENT_QUOTES, 'UTF-8'); ?>">Növbəti ay →</a>
            <form method="get" style="display:flex;gap:6px;align-items:center;margin:0;">
                <input type="hidden" name="u_id" value="<?php echo htmlspecialchars((string) ($row['u_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="teacher" value="<?php echo htmlspecialchars((string) ($row['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="year" value="<?php echo (int) $year; ?>">
                <input type="hidden" name="month" value="<?php echo (int) $month; ?>">
                <label for="groupSelect"><strong>QRUP:</strong></label>
                <select name="group" id="groupSelect" onchange="this.form.submit()">
                    <?php foreach (array_keys(ATT_DAY_GROUPS) as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $g === $group ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="title">Tələbələrin dərsə davamiyyət cədvəli</div>

        <div class="meta">
            <div>
                <div><strong>Müəllim:</strong> <?php echo htmlspecialchars($teacherDisplay); ?></div>
                <div><strong>Ay:</strong> <?php echo htmlspecialchars($sheet['month_label']); ?></div>
                <div><strong>İxtisas:</strong> <?php echo htmlspecialchars((string) ($row['tehsil_ve_ixtisas'] ?? '—')); ?></div>
            </div>
            <div class="qrup-box">
                <div class="line"><strong>QRUP:</strong> (<?php echo htmlspecialchars($sheet['group']); ?>)</div>
                <div class="line"><?php echo htmlspecialchars(implode(' / ', $sheet['group_days'])); ?></div>
                <?php if (!empty($sheet['times'])): ?>
                    <div class="line"><strong>Saat:</strong> <?php echo htmlspecialchars(implode(', ', $sheet['times'])); ?></div>
                <?php endif; ?>
                <div class="line" style="font-size:11px;color:#555;">Həftədə max 2 dərs</div>
            </div>
        </div>

        <table class="jurnal">
            <thead>
                <tr>
                    <th class="asa" rowspan="2">A. S. A</th>
                    <th class="tarix-head" colspan="<?php echo max(1, count($sheet['dates'])); ?>">TARİX</th>
                </tr>
                <tr>
                    <?php if (empty($sheet['dates'])): ?>
                        <th>—</th>
                    <?php else: ?>
                        <?php foreach ($sheet['dates'] as $dt): ?>
                            <th title="<?php echo htmlspecialchars($dt['date']); ?>"><?php echo (int) $dt['day']; ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sheet['students'])): ?>
                    <?php for ($i = 0; $i < $minRows; $i++): ?>
                        <tr>
                            <td class="asa">&nbsp;</td>
                            <?php if (empty($sheet['dates'])): ?>
                                <td></td>
                            <?php else: ?>
                                <?php foreach ($sheet['dates'] as $_dt): ?>
                                    <td></td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                <?php else: ?>
                    <?php foreach ($sheet['students'] as $st): ?>
                        <tr>
                            <td class="asa"><?php echo htmlspecialchars(str_replace('.', ' ', $st['username'])); ?></td>
                            <?php if (empty($sheet['dates'])): ?>
                                <td></td>
                            <?php else: ?>
                                <?php foreach ($sheet['dates'] as $dt): ?>
                                    <td class="mark"><?php echo !empty($st['marks'][$dt['date']]) ? '+' : ''; ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php for ($i = 0; $i < $rowPad; $i++): ?>
                        <tr>
                            <td class="asa">&nbsp;</td>
                            <?php foreach ($sheet['dates'] as $_dt): ?>
                                <td></td>
                            <?php endforeach; ?>
                            <?php if (empty($sheet['dates'])): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <div class="notes">
                <strong>ƏLAVƏ QEYD:</strong>
                <div class="blank"></div>
            </div>
            <div class="sign">
                <strong>MÜƏLLİM:</strong>
                <span class="line"><?php echo htmlspecialchars($teacherDisplay); ?></span>
            </div>
        </div>
        <p class="hint">Çap avtomatik 1 səhifəyə sığdırılır (A4 landşaft). + = dərsə gəlib (QR skan).</p>
    </div>
    <script>
    (function () {
        var sheet = document.getElementById('printSheet');
        var btn = document.getElementById('btnPrint');
        if (!sheet || !btn) return;

        function fitToOnePage() {
            sheet.style.transform = 'none';
            sheet.style.width = '100%';
            // A4 landscape usable area — bir az ehtiyatla
            var maxW = 1120;
            var maxH = 740;
            var rect = sheet.getBoundingClientRect();
            var scaleW = maxW / Math.max(rect.width, 1);
            var scaleH = maxH / Math.max(rect.height, 1);
            var scale = Math.min(1, scaleW, scaleH);
            // Çox kiçiltmə: minimum 0.88 — oxunaqlı qalsın
            if (scale < 0.88) {
                scale = 0.88;
            }
            if (scale < 0.995) {
                sheet.style.transform = 'scale(' + scale.toFixed(4) + ')';
            }
        }

        function resetScale() {
            sheet.style.transform = 'none';
        }

        btn.addEventListener('click', function () {
            fitToOnePage();
            setTimeout(function () {
                window.print();
            }, 80);
        });

        window.addEventListener('afterprint', resetScale);
        window.addEventListener('beforeprint', fitToOnePage);
    })();
    </script>
</body>
</html>
<?php
$conn->close();
