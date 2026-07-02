<?php
require_once '../db.php';

header('Content-Type: application/json');

$response = [
    'status'  => 'error',
    'message' => 'Tələbə məlumatları alınarkən xəta baş verdi',
    'data'    => null
];

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Yanlış və ya çatışmayan tələbə ID');
    }

    $id = (int)$_GET['id'];

    $query = "
        SELECT 
            id, u_id, username, number, poct, active_status,
            dogum_tarixi, years, cins, unvan, vetandasliq,
            sinif, qebul_tarixi, orta_bal, davamiyyet, status,
            ata, elaqe_nomre_ata, ana, elaqe_nomre_ana,
            photo, muellim_adi, ixtisas_adi,
            riyaziyyat, fizika, kimya, biologiya, tarix, edebiyyat, qeyd,
            
            -- Registration block (most important for display)
            reg_ad_soyad, reg_ata_adi, reg_universitet, reg_ixtisas, 
            reg_qebul_ili, reg_dogum_tarixi, reg_years,
            reg_is_nomresi, reg_telefon, reg_fin_kod, reg_email,
            reg_bakalavr_bali, reg_magistr_bali,
            reg_bolme, reg_tedris, reg_vaxt, reg_services,
            reg_sinif_qeyd, reg_menbe,
            reg_elave_qeyd_1, reg_elave_qeyd_2, reg_elave_qeyd_3,
            created_at, updated_at
        FROM telebeler 
        WHERE id = ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("SQL hazırlıq xətası: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Bu ID ilə tələbə tapılmadı");
    }

    $row = $result->fetch_assoc();

    $decodeJsonList = static function (?string $value): string {
        if ($value === null || $value === '' || $value === '[]') {
            return '—';
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $items = array_filter($decoded, static fn($item) => $item !== null && $item !== '' && $item !== '""');
            return !empty($items) ? implode(', ', array_map('strval', $items)) : '—';
        }
        return $value !== '' ? $value : '—';
    };

    $bolmeLabels = ['azerbaycan' => 'Azərbaycan', 'rus' => 'Rus'];
    $tedrisLabels = ['enenevi' => 'Ənənəvi', 'onlayn' => 'Onlayn'];
    $menbeLabels = [
        'sosial' => 'Sosial şəbəkə',
        'dostlar' => 'Dostlar',
        'telebeleden' => 'Burada hazırlanan tələbələrdən',
    ];
    $vaxtLabels = [
        'seher' => 'Səhər 08:20-13:10',
        'gunorta' => 'Günorta 14:00-18:50',
        'axsam' => 'Axşam 19:10',
    ];

    $formatLabelList = static function (?string $value, array $labels) use ($decodeJsonList): string {
        if ($value === null || $value === '' || $value === '[]') {
            return '—';
        }
        $decoded = json_decode($value, true);
        $items = is_array($decoded) ? $decoded : [$value];
        $mapped = [];
        foreach ($items as $item) {
            if ($item === null || $item === '' || $item === '""') {
                continue;
            }
            $mapped[] = $labels[$item] ?? (string) $item;
        }
        return !empty($mapped) ? implode(', ', $mapped) : '—';
    };

    // Gender label
    $cinsLabel = match ((int)$row['cins']) {
        0 => 'Kişi',
        1 => 'Qadın',
        default => 'Təyin edilməyib'
    };

    // Status badge
    $statusInfo = match (strtolower($row['active_status'] ?? '')) {
        'active'   => ['class' => 'success',  'text' => 'Aktiv'],
        'inactive' => ['class' => 'danger',   'text' => 'Qeyri-aktiv'],
        'graduate' => ['class' => 'info',     'text' => 'Məzun'],
        default    => ['class' => 'secondary', 'text' => 'Naməlum']
    };

    // Teachers list
    $muellimDisplay = 'Təyin edilməyib';
    $muellimValue = '';
    if (!empty($row['muellim_adi'])) {
        $teachers = json_decode($row['muellim_adi'], true);
        if (is_array($teachers)) {
            $validTeachers = array_filter($teachers, fn($v) => !empty($v) && $v !== '""');
            if (!empty($validTeachers)) {
                $muellimDisplay = implode(', ', array_map('htmlspecialchars', $validTeachers));
                $muellimValue = (string) reset($validTeachers);
            }
        } else if ($row['muellim_adi'] !== '""' && $row['muellim_adi'] !== '') {
            $muellimDisplay = htmlspecialchars($row['muellim_adi']);
            $muellimValue = $row['muellim_adi'];
        }
    }

    // Main response structure — flat + some grouped
    $response = [
        'status'  => 'success',
        'message' => 'Məlumatlar uğurla alındı',
        'data'    => [
            // Main profile block
            'id'              => $row['id'],
            'reg_ad_soyad'    => $row['reg_ad_soyad'] ?: '—',
            'username'        => $row['username'] ?: '—',
            'photo'           => $row['photo'] ?: '',
            'poct'            => $row['poct'] ?: $row['reg_email'] ?: '—',
            'phone'           => $row['number'] ?: $row['reg_telefon'] ?: '—',
            'cins'            => $cinsLabel,
            'active_status'   => $row['active_status'] ?? '—',
            'status_label'    => $statusInfo['text'],
            'status_class'    => $statusInfo['class'],

            // Registration / Admission information (very important)
            'reg_ad_soyad'    => $row['reg_ad_soyad'] ?: '—',
            'reg_ata_adi'     => $row['reg_ata_adi'] ?: '—',
            'reg_dogum_tarixi'=> $row['reg_dogum_tarixi'] ?: $row['dogum_tarixi'] ?: '—',
            'reg_years'       => $row['reg_years'] ?: $row['years'] ?: '—',
            'reg_sinif_qeyd'  => $row['reg_sinif_qeyd'] ?: $row['sinif'] ?: '—',
            'reg_qebul_ili'   => $row['reg_qebul_ili'] ?: '—',
            'reg_universitet' => $row['reg_universitet'] ?: '—',
            'reg_ixtisas'     => $row['reg_ixtisas'] ?: '—',

            'reg_telefon' => $row['reg_telefon'] ?: '—',
            'reg_is_nomresi'     => $row['reg_is_nomresi'] ?: '—',

            // Current academic
            'sinif'           => $row['sinif'] ?: '—',
            'orta_bal'        => $row['orta_bal'] ?: '—',
            'muellim_adi'     => $muellimDisplay,
            'muellim_value'   => $muellimValue,
            'davamiyyet'      => $row['davamiyyet'] ?: '—',

            // Personal
            'dogum_tarixi'    => $row['dogum_tarixi'] ?: '—',
            'years'           => $row['years'] ?: '—',
            'unvan'           => $row['unvan'] ?: '—',
            'vetendasliq'     => $row['vetandasliq'] ?: '—',

            'reg_magistr_bali'           => $row['reg_magistr_bali'] ?: '—',
            'reg_bakalavr_bali'     => $row['reg_bakalavr_bali'] ?: '—',

            // Parents
            'ata'             => $row['reg_ata_adi'] ?: '—',
            'elaqe_nomre_ata' => $row['elaqe_nomre_ata'] ?: '—',
            'ana'             => $row['ana'] ?: '—',
            'elaqe_nomre_ana' => $row['elaqe_nomre_ana'] ?: '—',

            // Grades
            'riyaziyyat'      => $row['riyaziyyat'] ?: '—',
            'fizika'          => $row['fizika'] ?: '—',
            'kimya'           => $row['kimya'] ?: '—',
            'biologiya'       => $row['biologiya'] ?: '—',
            'tarix'           => $row['tarix'] ?: '—',
            'edebiyyat'       => $row['edebiyyat'] ?: '—',

            // Note
            'qeyd'            => $row['qeyd'] ?: 'Qeyd yoxdur',

            // Qeydiyyat formu (Qeydiyyatar.php)
            'reg_fin_kod'       => $row['reg_fin_kod'] ?: '—',
            'reg_email'         => $row['reg_email'] ?: '—',
            'reg_bolme'         => $bolmeLabels[$row['reg_bolme'] ?? ''] ?? ($row['reg_bolme'] ?: '—'),
            'reg_tedris'        => $tedrisLabels[$row['reg_tedris'] ?? ''] ?? ($row['reg_tedris'] ?: '—'),
            'reg_vaxt'          => $formatLabelList($row['reg_vaxt'] ?? '', $vaxtLabels),
            'reg_services'      => $decodeJsonList($row['reg_services'] ?? null),
            'reg_menbe'         => $formatLabelList($row['reg_menbe'] ?? '', $menbeLabels),
            'reg_elave_qeyd_1'  => $row['reg_elave_qeyd_1'] ?: '—',
            'reg_elave_qeyd_2'  => $row['reg_elave_qeyd_2'] ?: '—',
            'reg_elave_qeyd_3'  => $row['reg_elave_qeyd_3'] ?: '—',
            'qebul_tarixi'      => $row['qebul_tarixi'] ?: '—',
            'created_at'        => $row['created_at'] ?: '—',
            'updated_at'        => $row['updated_at'] ?: '—'
        ]
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['debug']   = [
        'error' => $e->getMessage(),
        'file'  => $e->getFile(),
        'line'  => $e->getLine()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$conn->close();
?>