<?php
include('db.php');
require_once __DIR__ . '/qeydiyyatar/contract_helpers.php';
require_once __DIR__ . '/qeydiyyatar/odenis_helpers.php';

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

odenis_ensure_columns($conn);
$row = contract_load_row($conn, $id);
$conn->close();

if (!$row) {
    http_response_code(404);
    die('Qeydiyyat tapılmadı.');
}

$studentName = contract_student_display($row);
$courseName = contract_course_display($row);
$startDate = contract_date_az($row['baslama_tarixi'] ?? null);
$fee = contract_fee($row);
$dersSayi = contract_lesson_count($row);
$paymentSchedule = contract_payment_schedule($row);
$ilkinOdenis = contract_ilkin_odenis_amount($row);
$autoPrint = !isset($_GET['preview']);
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müqavilə — <?= contract_h($studentName) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Times New Roman", Times, serif;
            background: #e8e8e8;
            color: #000;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 12px;
            background: #1e3a8a;
        }
        .toolbar button {
            border: none;
            border-radius: 6px;
            padding: 9px 16px;
            font-size: 13px;
            cursor: pointer;
            color: #fff;
            background: #3b82f6;
        }
        .toolbar button.secondary { background: #64748b; }
        .page-wrap {
            width: 210mm;
            margin: 16px auto;
            padding: 0 0 12mm;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
        }
        .contract-box {
            border: 1.5px dashed #333;
            margin: 5mm 7mm 3mm;
            padding: 9mm 10mm 7mm;
            font-size: 11pt;
            line-height: 1.45;
            text-align: justify;
        }
        p { margin-bottom: 7px; }
        .intro-student {
            display: block;
            text-align: right;
            min-height: 1.4em;
            margin: 3px 0 5px;
            font-weight: 600;
        }
        .section-title {
            text-align: center;
            font-weight: 700;
            font-size: 11pt;
            margin: 11px 0 7px;
            text-transform: uppercase;
        }
        .clause-block { margin-bottom: 5px; }
        .clause-title {
            font-weight: 700;
            margin: 7px 0 3px;
        }
        .clause-item { margin-bottom: 5px; }
        .dots { letter-spacing: 0.5px; }
        .payment-title {
            text-align: center;
            font-weight: 700;
            font-size: 11pt;
            margin: 13px 0 9px;
            text-transform: uppercase;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 11pt;
        }
        .payment-table td {
            vertical-align: top;
            padding: 5px 7px 9px 0;
            width: 50%;
        }
        .payment-table .sign-cell {
            text-align: left;
            padding-left: 11px;
        }
        .payment-side-label {
            text-align: right;
            font-weight: 700;
            margin-bottom: 5px;
            min-height: 1.2em;
        }
        .line {
            display: inline-block;
            min-width: 109px;
            border-bottom: 1px solid #000;
            padding: 0 3px 0;
        }
        .line.filled {
            font-weight: 700;
            text-align: center;
        }
        .line.wide { min-width: 169px; }
        .copy-note {
            margin: 13px 0 9px;
            font-size: 11pt;
        }
        .signatures-title {
            text-align: center;
            font-weight: 700;
            font-size: 11pt;
            margin: 11px 0 15px;
            text-transform: uppercase;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 23px;
            font-size: 11pt;
        }
        .sign-col { min-height: 119px; }
        .sign-col .head {
            font-weight: 700;
            margin-bottom: 17px;
        }
        .sign-col .center-name {
            text-align: center;
            margin: 9px 0;
        }
        .sign-col .student-name {
            text-align: right;
            margin: 9px 0 17px;
            min-height: 1.2em;
        }
        .sign-col .director {
            margin: 7px 0 15px;
        }
        .sign-line {
            display: block;
            width: 179px;
            border-bottom: 1px solid #000;
            margin: 11px 0 3px;
        }
        .sign-col.right .sign-line { margin-left: auto; }
        .sign-hint {
            font-size: 10pt;
            margin-top: 1px;
        }
        .sign-col.right .sign-hint { text-align: right; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap {
                width: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
            .contract-box {
                margin: 0;
                border: 1.5px dashed #000;
                font-size: 10.5pt;
                line-height: 1.4;
                padding: 7mm 8mm 5mm;
            }
            @page { size: A4 portrait; margin: 11mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Çap et</button>
        <button type="button" class="secondary" onclick="window.close()">Bağla</button>
    </div>

    <div class="page-wrap">
        <div class="contract-box">
            <p>
                Bu müqavilə, bir tərəfdən, bundan sonra “Tədris Mərkəzi” adlanacaq “Magistratura.az” Təhsil Mərkəzinin rəhbəri
                fiziki şəxs Nurəliyev Anar Ziyəddin oğlu, digər tərəfdən bundan sonra “Tələbə” adlanacaq
            </p>
            <span class="intro-student"><?= contract_h($studentName) ?></span>
            <p>
                arasında Azərbaycan Respublikasının müvafiq qanunvericiliyinə uyğun olaraq bağlanılır. Bu müqavilə bağlanan gündən
                “Tədris Mərkəzi” və Tələbənin arasında yaranmış münasibətlər, tərəflərin hüquqları, vəzifələri və məsuliyyəti
                müəyyən edilmiş qaydalarla tənzimlənir.
            </p>

            <div class="section-title">Müqavilənin predmeti</div>
            <p>
                Bu müqavilənin predmetini “Tədris Mərkəzi” tərəfdən Tələbənin <?= $startDate ?> tarixdən etibarən
                <?= contract_h($courseName) ?> kursu üzrə tədris xidmətinin göstərilməsi, “Tələbə” tərəfdən isə xidmət haqqının
                ödənilməsi və digər öhdəliklərlə bağlı yaranacaq münasibətlərin tənzimlənməsi təşkil edir<span class="dots">......................................................................</span>
                Təhsil haqqı ( <?= contract_h($fee) ?> ) AZN təşkil edir.
            </p>

            <div class="section-title">Tərəflərin hüquq və öhdəlikləri</div>

            <div class="clause-title">TƏLƏBƏ:</div>
            <div class="clause-block">
                <p class="clause-item">Qeydiyyatdan keçdiyi zaman (tam paketdən əlavə olaraq) ilkin ödəniş olaraq dərs vəsaitlərinin endirimli ödənişini 45 AZN edir. (kursa gəlmədiyi halda ilkin ödəniş və kitablar geri qaytarılmır).</p>
                <p class="clause-item">Davamlı olaraq dərslərdə iştirak edir və iştirak etmədiyi dərslər (üzürlü və üzürsüz səbəblər) dərs prossesinə aiddir və ödəniş zamanı hesablanır.</p>
                <p class="clause-item">Tələbə keçirilən kurs üzrə keyfiyyətin əldə olunması üçün dərslərə hazırlıqlı gəlməli, verilən tapşırıqları məsuliyyətlə yerinə yetirməlidir.</p>
                <p class="clause-item">Ödəniş tarixi barədə məlumat müqavilədə qeyd olunur, təyin edilmiş vaxtlarda ödəniş ödənilməzsə, tələbənin dərsləri dayandırılır.</p>
                <p class="clause-item">Tələbənin paralel olaraq bir neçə qrupla dərslərdə iştirakına icazə verilmir və iştirak etmədiyi dərslər əvəz olunmur.</p>
                <p class="clause-item">Tələbə dərsləri dayandırdığını bildirdiyi tarixədək keçirilən dərslərin ödənişi hesablanır.</p>
                <p class="clause-item">Onlayn tədris üzrə qeydiyyatdan keçən tələbələr üçün video dərs materialları imtahan müddətinə qədər və ya tələbə kursda qaldığı müddətə qədər aktiv olur.</p>
                <p class="clause-item">Paket ödənişi tamamlanmış tələbə ardıcıl 3 dərs iştirak etmədiyi halda kursdan və whatsapp qruplarından çıxardılır.</p>
                <p class="clause-item">Əyani qruplarda təhsil alan tələbələr üçün teleqram kanalına / teleqram qrupuna, whatsapp qruplarına çıxış təmin olunmaya bilər. Əsas iştirak forması tələbənin dərslərdə əyani iştirakıdır.</p>
                <p class="clause-item">Tələbə dərslərdən ardıcıl üç dəfə qaldığı halda (üzürsüz) aid olduğu teleqram kanalından / teleqram qrupundan və Whatsapp qruplarından çıxarılır.</p>
                <p class="clause-item">Hər hansı bir narazılıq meydana gələrsə, bu barədə yazılı və ya şifahi şəkildə məlumat bölməsinə bildirə, birbaşa rəhbərliyə müraciət edə bilər.</p>
            </div>

            <div class="clause-title">TƏDRİS MƏRKƏZİ</div>
            <div class="clause-block">
                <p class="clause-item">Təhsilin keyfiyyətinə təminat verir;</p>
                <p class="clause-item">Dərsin vaxtında keçirilməsini və lazımı bütün şəraitin yaradılmasını təmin edir;</p>
                <p class="clause-item">Xarici dil dərslərini beynəlxalq sertifikatlı müəllimlərin tədris etməsini təmin edir;</p>
                <p class="clause-item">Ay ərzində Məntiq / İnformatika / Xarici dil fən(lər)i üzrə <?= contract_h($dersSayi) ?> dərs keçirilməsini təmin edir;</p>
            </div>

            <p class="clause-item">
                Tədris Mərkəzi tərəfindən təmin edilmiş dərs vəsaitləri itirildikdə və ya yararsız hala salındıqda tələbə vəsaitin yenisini yalnız ödənişli şəkildə əldə edə bilər;
            </p>
            <p class="clause-item">
                Paket ödənişi ilə qeydiyyatdan keçən tələbə hazırlıqdan imtina edərsə, keçirilmiş dərslərin aylıq ödənişi 130 AZN üzərindən hesablanır.
            </p>
            <br>    
            <br>    
            <div class="payment-title">Ödəniş müddəti, şərtləri və qaydaları</div>

            <table class="payment-table">
                <tr>
                    <td>
                        İlkin ödəniş məbləği: <span class="line filled"><?= contract_h($ilkinOdenis) ?></span><br>
                        Tarix: <span class="line wide">_____________________</span>
                    </td>
                    <td class="sign-cell">
                        İmza: <span class="line">__________</span><br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;M.Y
                    </td>
                </tr>
                <?php for ($i = 0; $i < 6; $i++):
                    $slot = contract_payment_slot($paymentSchedule, $i);
                ?>
                    <tr>
                        <td>
                            Ödənişin məbləği: <span class="line<?= $slot['filled'] ? ' filled' : '' ?>"><?= contract_h($slot['text']) ?></span><br>
                            Tarix: <span class="line wide">_____________________</span>
                        </td>
                        <td class="sign-cell">
                            <?php $sideLabel = contract_payment_side_label($i); ?>
                            <?php if ($sideLabel !== ''): ?>
                                <div class="payment-side-label"><?= contract_h($sideLabel) ?></div>
                            <?php else: ?>
                                <div class="payment-side-label">&nbsp;</div>
                            <?php endif; ?>
                            İmza: <span class="line">__________</span><br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;M.Y
                        </td>
                    </tr>
                <?php endfor; ?>
            </table>

            <p class="copy-note">
                Müqavilə 2 (iki) nüsxədən ibarət tərtib olundu. Birinci nüsxə tələbəyə verilir, ikinci nüsxə Təhsil Mərkəzində qalır.
            </p>

            <div class="signatures-title">Tərəflərin imzaları</div>
            <div class="signatures">
                <div class="sign-col">
                    <div class="head">TƏDRİS MƏRKƏZİ:</div>
                    <div class="center-name">“Magistratura.az”</div>
                    <div class="director">Nurəliyev A.Z</div>
                    <span class="sign-line"></span>
                    <div class="sign-hint">(imza və möhür)</div>
                </div>
                <div class="sign-col right">
                    <div class="head">TƏLƏBƏ:</div>
                    <div class="student-name"><?= contract_h($studentName) ?></div>
                    <span class="sign-line"></span>
                    <div class="sign-hint">(imza)</div>
                </div>
            </div>
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
