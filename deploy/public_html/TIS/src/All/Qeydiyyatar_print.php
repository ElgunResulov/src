<?php
include('db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die('Yanlış qeydiyyat ID.');
}

$stmt = $conn->prepare("
    SELECT *
    FROM qeydiyyatar
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    http_response_code(404);
    die('Qeydiyyat tapılmadı.');
}

function print_h(?string $value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function print_date_az(?string $date): string {
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d.m.Y', $ts) : print_h($date);
}

function print_json_list(?string $json, array $labels = []): string {
    if ($json === null || $json === '' || $json === '[]') {
        return '—';
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return print_h($json);
    }
    $items = [];
    foreach ($decoded as $item) {
        if ($item === null || $item === '' || $item === '""') {
            continue;
        }
        $items[] = $labels[$item] ?? (string) $item;
    }
    return $items ? print_h(implode(', ', $items)) : '—';
}

$bolmeLabels = ['azerbaycan' => 'Azərbaycan', 'rus' => 'Rus'];
$tedrisLabels = ['enenevi' => 'Ənənəvi', 'onlayn' => 'Onlayn'];
$vaxtLabels = ['seher' => 'Səhər 08:20-13:10', 'gunorta' => 'Günorta 14:00-18:50', 'axsam' => 'Axşam 19:10'];
$menbeLabels = ['sosial' => 'Sosial şəbəkə', 'dostlar' => 'Dostlar', 'telebeleden' => 'Burada hazırlanan tələbələrdən'];

$studentName = $row['telebe_ad_soyad'] ?: ($row['form_ad_soyad'] ?? '—');
$studentDisplay = str_replace('.', ' ', (string) $studentName);
$ixtisas = $row['ixtisas_adi'] ?: ($row['form_ixtisas'] ?? '—');
$odenisNovu = ($row['odenis_novu'] ?? '') === 'paket' ? 'Paket' : 'Aylıq';
$autoPrint = !isset($_GET['preview']);
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qeydiyyat çapı — <?= print_h($studentDisplay) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef2f7;
            color: #111827;
            line-height: 1.5;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 14px;
            background: #1e3a8a;
        }
        .toolbar button {
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
            color: #fff;
            background: #3b82f6;
        }
        .toolbar button.secondary { background: #64748b; }
        .print-document {
            width: 21cm;
            min-height: 29.7cm;
            margin: 24px auto;
            background: #fff;
            padding: 1.4cm 1.6cm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        }
        .print-header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }
        .brand {
            font-size: 9.5pt;
            font-weight: 700;
            color: #1d4ed8;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .print-title {
            font-size: 14.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .print-subtitle {
            margin-top: 6px;
            font-size: 7.5pt;
            color: #475569;
        }
        .section-title {
            margin: 22px 0 12px;
            padding: 8px 12px;
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e40af;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 24px;
        }
        .info-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 0;
            font-size: 7pt;
        }
        .info-item.full { grid-column: 1 / -1; }
        .info-label {
            display: block;
            font-size: 5.5pt;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-value { font-weight: 600; color: #0f172a; }
        .contract-text {
            font-size: 7pt;
            text-align: justify;
            margin-bottom: 12px;
        }
        .blank-line {
            display: inline-block;
            min-width: 140px;
            border-bottom: 1px solid #000;
            padding: 0 6px 1px;
            font-weight: 600;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 36px;
        }
        .sign-box { text-align: center; font-size: 7pt; }
        .sign-title { font-weight: 700; margin-bottom: 10px; }
        .sign-line {
            border-bottom: 1px solid #000;
            height: 42px;
            margin: 18px 0 6px;
        }
        .sign-note { font-size: 5.5pt; color: #64748b; }
        .footer-note {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5e1;
            font-size: 5.5pt;
            color: #64748b;
            text-align: center;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .print-document {
                width: auto;
                min-height: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Çap et</button>
        <button type="button" class="secondary" onclick="window.close()">Bağla</button>
    </div>

    <div class="print-document">
        <div class="print-header">
            <div class="brand">Magistratura.az — Təhsil Mərkəzi</div>
            <div class="print-title">Qeydiyyat Formu</div>
            <div class="print-subtitle">Tədris ili: <?= print_h($row['tedris_ili'] ?: '—') ?> · Tarix: <?= print_date_az($row['created_at'] ?? date('Y-m-d')) ?></div>
        </div>

        <div class="section-title">Tələbə məlumatları</div>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Ad Soyad</span><span class="info-value"><?= print_h($studentDisplay) ?></span></div>
            <div class="info-item"><span class="info-label">Ata adı</span><span class="info-value"><?= print_h($row['form_ata_adi'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">Universitet</span><span class="info-value"><?= print_h($row['form_universitet'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">İxtisas</span><span class="info-value"><?= print_h($ixtisas) ?></span></div>
            <div class="info-item"><span class="info-label">Qəbul ili</span><span class="info-value"><?= print_h($row['form_qebul_ili'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">Doğum tarixi</span><span class="info-value"><?= print_date_az($row['form_dogum_tarixi'] ?? null) ?></span></div>
            <div class="info-item"><span class="info-label">Telefon</span><span class="info-value"><?= print_h($row['form_telefon'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">E-mail</span><span class="info-value"><?= print_h($row['form_email'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">FIN kod</span><span class="info-value"><?= print_h($row['form_fin_kod'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">İş nömrəsi</span><span class="info-value"><?= print_h($row['form_is_nomresi'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">Bakalavr balı</span><span class="info-value"><?= print_h($row['form_bakalavr_bali'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">Magistr balı</span><span class="info-value"><?= print_h($row['form_magistr_bali'] ?? '—') ?></span></div>
        </div>

        <div class="section-title">Tədris prosesi</div>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Bölmə</span><span class="info-value"><?= print_h($bolmeLabels[$row['form_bolme'] ?? ''] ?? ($row['form_bolme'] ?: '—')) ?></span></div>
            <div class="info-item"><span class="info-label">Tədris</span><span class="info-value"><?= print_h($tedrisLabels[$row['form_tedris'] ?? ''] ?? ($row['form_tedris'] ?: '—')) ?></span></div>
            <div class="info-item full"><span class="info-label">Arzu olunan vaxt</span><span class="info-value"><?= print_json_list($row['form_vaxt'] ?? null, $vaxtLabels) ?></span></div>
            <div class="info-item full"><span class="info-label">Xidmətlər</span><span class="info-value"><?= print_json_list($row['form_services'] ?? null) ?></span></div>
            <div class="info-item"><span class="info-label">Sinif qeydi</span><span class="info-value"><?= print_h($row['form_sinif_qeyd'] ?? '—') ?></span></div>
            <div class="info-item"><span class="info-label">Mənbə</span><span class="info-value"><?= print_json_list($row['form_menbe'] ?? null, $menbeLabels) ?></span></div>
        </div>

        

        <?php if (!empty($row['form_elave_qeyd_1']) || !empty($row['form_elave_qeyd_2']) || !empty($row['form_elave_qeyd_3'])): ?>
        <div class="section-title">Əlavə qeydlər</div>
        <div class="info-grid">
            <?php foreach (['form_elave_qeyd_1', 'form_elave_qeyd_2', 'form_elave_qeyd_3'] as $i => $field): ?>
                <?php if (!empty($row[$field])): ?>
                <div class="info-item full"><span class="info-label">Qeyd <?= $i + 1 ?></span><span class="info-value"><?= print_h($row[$field]) ?></span></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="section-title">Müqavilə xülasəsi</div>
        <p class="contract-text">
            Bu sənəd <span class="blank-line"><?= print_h($studentDisplay) ?></span> adlı tələbənin
            <span class="blank-line"><?= print_date_az($row['baslama_tarixi'] ?? null) ?></span> tarixindən etibarən
            <strong><?= print_h($ixtisas) ?></strong> ixtisası üzrə tədris xidmətinə qeydiyyatını təsdiqləyir.
            Təhsil haqqı <strong><?= print_h(number_format((float) ($row['tehsil_haqqi'] ?? 0), 2, '.', '')) ?> AZN</strong>,
            ödəniş növü <strong><?= print_h($odenisNovu) ?></strong> olaraq qeydə alınmışdır.
        </p>

        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">TƏDRİS MƏRKƏZİ</div>
                <div>Magistratura.az</div>
                <div class="sign-line"></div>
                <div class="sign-note">İmza və möhür</div>
            </div>
            <div class="sign-box">
                <div class="sign-title">TƏLƏBƏ</div>
                <div><?= print_h($studentDisplay) ?></div>
                <div class="sign-line"></div>
                <div class="sign-note">İmza</div>
            </div>
        </div>

        <div class="footer-note">
            Sənəd № <?= (int) $row['id'] ?> · Çap tarixi: <?= date('d.m.Y H:i') ?>
        </div>
    </div>

    <?php if ($autoPrint): ?>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 500);
        });
    </script>
    <?php endif; ?>
</body>
</html>
