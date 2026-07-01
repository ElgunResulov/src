<?php
include('db.php');
require_once __DIR__ . '/qeydiyyatar/contract_helpers.php';

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

$row = contract_load_row($conn, $id);
$conn->close();

if (!$row) {
    http_response_code(404);
    die('Qeydiyyat tapılmadı.');
}

$studentName = contract_student_name($row);
$courseName = contract_course_name($row);
$startDate = contract_date_az($row['baslama_tarixi'] ?? null);
$tedrisIli = contract_h($row['tedris_ili'] ?: date('Y') . '-' . (date('Y') + 1));
$fee = contract_fee($row);
$odenisNovu = contract_payment_type($row);
$dersSayi = contract_lesson_count($row);
$autoPrint = !isset($_GET['preview']);
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qeydiyyat müqaviləsi — <?= contract_h($studentName) ?></title>
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
            min-height: 297mm;
            margin: 16px auto;
            padding: 0 0 12mm;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
        }
        .contract-box {
            border: 1.5px dashed #333;
            margin: 6mm 8mm 4mm;
            padding: 8mm 9mm 6mm;
            font-size: 11pt;
            line-height: 1.4;
            text-align: justify;
        }
        .contract-header {
            display: grid;
            grid-template-columns: 1fr 1.4fr 0.8fr;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 8px;
        }
        .brand {
            font-size: 11pt;
            font-weight: 700;
            line-height: 1.2;
        }
        .brand small {
            display: block;
            font-size: 10pt;
            font-weight: 600;
        }
        .title {
            text-align: center;
            font-size: 15pt;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .year {
            text-align: right;
            font-size: 13pt;
            font-weight: 700;
        }
        p { margin-bottom: 7px; }
        .section-title {
            text-align: center;
            font-weight: 700;
            font-size: 12pt;
            margin: 10px 0 7px;
            text-transform: uppercase;
        }
        .blank {
            display: inline-block;
            min-width: 140px;
            border-bottom: 1px solid #000;
            padding: 0 6px 2px;
            text-align: center;
            font-weight: 600;
        }
        .blank.wide { min-width: 240px; }
        ul.contract-list {
            list-style: none;
            padding: 0;
            margin: 4px 0 8px;
        }
        ul.contract-list li {
            position: relative;
            padding-left: 18px;
            margin-bottom: 5px;
        }
        ul.contract-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            font-size: 11pt;
            font-weight: 700;
        }
        .subsection-title {
            font-weight: 700;
            margin: 6px 0 4px;
            font-size: 11pt;
        }
        .note {
            margin-top: 6px;
            font-size: 10pt;
        }
        .note.emphasis {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 8px;
        }
        .staff-sign {
            margin: 0 8mm;
            font-size: 11pt;
            padding-top: 4mm;
        }
        .staff-sign .line {
            display: inline-block;
            min-width: 180px;
            border-bottom: 1px solid #000;
            margin-left: 6px;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap {
                width: auto;
                min-height: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
            .contract-box {
                margin: 0;
                border: 1.5px dashed #000;
                page-break-inside: avoid;
                font-size: 11pt;
                line-height: 1.35;
                padding: 7mm 8mm 5mm;
            }
            @page { size: A4 portrait; margin: 10mm; }
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
            <div class="contract-header">
                <div class="brand">
                    MAGISTRATURA.AZ
                    <small>TƏHSİL MƏRKƏZİ</small>
                </div>
                <div class="title">QEYDİYYAT MÜQAVİLƏSİ</div>
                <div class="year"><?= $tedrisIli ?></div>
            </div>

            <p>
                Bu müqavilə, bir tərəfdən, bundan sonra “Tədris Mərkəzi” adlanacaq “Magistratura.az” Təhsil Mərkəzinin rəhbəri
                fiziki şəxs Nurəliyev Anar Ziyəddin oğlu, digər tərəfdən bundan sonra “Tələbə” adlanacaq
                <span class="blank wide"><?= contract_h($studentName) ?></span>
                arasında Azərbaycan Respublikasının müvafiq qanunvericiliyinə uyğun olaraq bağlanılır. Bu müqavilə bağlanan gündən
                “Tədris Mərkəzi” və Tələbənin arasında yaranmış münasibətlər, tərəflərin hüquqları, vəzifələri və məsuliyyəti
                müəyyən edilmiş qaydalarla tənzimlənir.
            </p>

            <div class="section-title">Müqavilənin predmeti</div>
            <p>
                Bu müqavilənin predmetini “Tədris Mərkəzi” tərəfdən Tələbənin <?= $startDate ?> tarixdən etibarən
                <span class="blank"><?= contract_h($courseName) ?></span> kursu üzrə tədris xidmətinin göstərilməsi,
                “Tələbə” tərəfdən isə xidmət haqqının ödənilməsi və digər öhdəliklərlə bağlı yaranacaq münasibətlərin
                tənzimlənməsi təşkil edir.
            </p>
            <p>
                Təhsil haqqı ( <strong><?= contract_h($fee) ?></strong> ) AZN təşkil edir.
                Ödəniş növü: ( <strong><?= contract_h($odenisNovu) ?></strong> )
            </p>

            <div class="section-title">Tərəflərin hüquq və öhdəlikləri</div>

            <div class="subsection-title">TƏLƏBƏ</div>
            <ul class="contract-list">
                <li>Qeydiyyatdan keçdiyi zaman dərs vəsaitləri üçün 45 AZN ödəyir (kursa gəlmədiyi halda ödəniş geri qaytarılmır).</li>
                <li>Davamlı olaraq dərslərdə iştirak edir və iştirak etmədiyi dərslər (üzürlü və üzürsüz səbəblər) dərs prosesinə aiddir və ödəniş zamanı hesablanır.</li>
                <li>Keçirilən kurs üzrə keyfiyyətin əldə olunması üçün dərslərə hazırlıqlı gəlməli, verilən tapşırıqları məsuliyyətlə yerinə yetirməlidir.</li>
                <li>Ödəniş tarixi barədə məlumat müqavilədə qeyd olunur; təyin edilmiş vaxtlarda ödəniş ödənilməzsə, tələbənin dərsləri dayandırılır.</li>
                <li>Paralel olaraq bir neçə qrupla dərslərdə iştirakına icazə verilmir və iştirak etmədiyi dərslər əvəz olunmur.</li>
                <li>Dərsləri dayandırdığı tarixədək keçirilən dərslərin və tədris proqramı başa çatmadan hazırlığı dayandırdığı üçün təqdim edilmiş dərs vəsaitlərinin ödənişi hesablanır.</li>
                <li>Onlayn tədris üzrə qeydiyyatdan keçən tələbələr üçün video dərs materialları imtahan müddətinə qədər aktiv olur.</li>
                <li>WhatsApp və Telegram qruplarına daxil olmalı, elan və tapşırıqları vaxtında izləməlidir.</li>
                <li>Ardıcıl 3 dəfə dərsə gəlmədikdə qruplardan çıxarıla bilər.</li>
                <li>Hər hansı narazılıq olduqda yazılı və ya şifahi şəkildə məlumat bölməsinə, fənn koordinatorlarına və birbaşa rəhbərliyə müraciət edə bilər.</li>
            </ul>

            <div class="subsection-title">TƏDRİS MƏRKƏZİ</div>
            <ul class="contract-list">
                <li>Paket və ya aylıq ödənişlə qeydiyyatdan keçib tədris proqramının sonunadək davam edən tələbələri dərs vəsaitləri ilə ödənişsiz təmin edir (kursu sonuna qədər davam etmədiyi halda kitabların ödənişi hesablanır).</li>
                <li>Təhsilin keyfiyyətinə təminat verir.</li>
                <li>Dərsin vaxtında keçirilməsini və lazımı bütün şəraitin yaradılmasını təmin edir.</li>
                <li>Xarici dil dərslərini beynəlxalq sertifikatlı müəllimlərin tədris etməsini təmin edir.</li>
                <li>Ay ərzində Məntiq / İnformatika / Xarici dil fən(lər)i üzrə <?= contract_h($dersSayi) ?> dərs keçirilməsini təmin edir.</li>
            </ul>

            <p class="note">
                Tədris Mərkəzi tərəfindən təmin edilmiş dərs vəsaitləri itirildikdə və ya yararsız hala salındıqda tələbə vəsaitin yenisini yalnız ödənişli şəkildə əldə edə bilər.
            </p>
            <p class="note emphasis">
                Paket ödənişi ilə qeydiyyatdan keçən tələbə hazırlıqdan imtina edərsə, keçirilmiş dərslərin aylıq ödənişi 130 AZN üzərindən hesablanır.
            </p>
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
