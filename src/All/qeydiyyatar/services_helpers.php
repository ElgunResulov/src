<?php

function xidmet_get_catalog(): array
{
    return [
        [
            'title' => 'Magistratura',
            'services' => [
                ['key' => 'Məntiq', 'label' => 'Məntiq'],
                ['key' => 'İnformatika', 'label' => 'İnformatika'],
            ],
            'subsections' => [
                [
                    'title' => 'Xarici dil',
                    'services' => [
                        ['key' => 'İngilis', 'label' => 'İngilis'],
                        ['key' => 'Rus', 'label' => 'Rus'],
                        ['key' => 'Alman', 'label' => 'Alman'],
                        ['key' => 'Fransız', 'label' => 'Fransız'],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Dövlət qulluğu',
            'services' => [
                ['key' => 'DQ-Məntiq', 'label' => 'Məntiq'],
                ['key' => 'Qanunvericilik', 'label' => 'Qanunvericilik'],
                ['key' => 'DQ-İnformatika', 'label' => 'İnformatika'],
                ['key' => 'Azərbaycan dili', 'label' => 'Azərbaycan dili'],
                ['key' => 'Müsahibə', 'label' => 'Müsahibə mərhələsi'],
                ['key' => 'Prokurorluq', 'label' => 'Prokurorluq'],
                ['key' => 'Vergi', 'label' => 'Vergi orqanları'],
            ],
        ],
        [
            'title' => 'MİQ və SERTİFİKASIYA',
            'services' => [
                ['key' => 'Kurikulum', 'label' => 'Kurikulum'],
                ['key' => 'İxtisas', 'label' => 'İxtisas'],
            ],
        ],
        [
            'title' => 'Xarici dil dərsləri',
            'services' => [
                ['key' => 'Beginner', 'label' => 'Beginner'],
                ['key' => 'Elementary', 'label' => 'Elementary'],
                ['key' => 'Pre-Intermediate', 'label' => 'Pre-Intermediate'],
                ['key' => 'Intermediate', 'label' => 'Intermediate'],
                ['key' => 'IELTS', 'label' => 'IELTS'],
                ['key' => 'Rus dili', 'label' => 'Rus dili'],
            ],
        ],
        [
            'title' => 'Doktorantura',
            'services' => [
                ['key' => 'Dok-İngilis', 'label' => 'İngilis dili'],
                ['key' => 'Fəlsəfə', 'label' => 'Fəlsəfə'],
            ],
        ],
        [
            'title' => 'Digər xidmətlər',
            'services' => [
                ['key' => 'Robototexnika', 'label' => 'Robototexnika'],
                ['key' => 'Ofis proqramları', 'label' => 'Ofis proqramları'],
                ['key' => 'Sabah qrupları', 'label' => 'Sabah qrupları'],
                ['key' => 'Məktəbəqədər', 'label' => 'Məktəbəqədər'],
                ['key' => 'İbtidai sinif', 'label' => 'İbtidai sinif'],
                ['key' => 'Təkmilləşdirmə', 'label' => 'Təkmilləşdirmə'],
                ['key' => 'Abituriyent', 'label' => 'Abituriyent'],
                ['key' => 'Blok', 'label' => 'Blok'],
                ['key' => 'Buraxılış', 'label' => 'Buraxılış'],
            ],
        ],
    ];
}

function xidmet_flat_list(): array
{
    $list = [];
    foreach (xidmet_get_catalog() as $group) {
        foreach ($group['services'] ?? [] as $service) {
            $list[] = $service;
        }
        foreach ($group['subsections'] ?? [] as $subsection) {
            foreach ($subsection['services'] ?? [] as $service) {
                $list[] = $service;
            }
        }
    }
    return $list;
}

function xidmet_ensure_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS xidmet_qiymetleri (
        service_key VARCHAR(100) NOT NULL,
        service_label VARCHAR(150) NOT NULL DEFAULT '',
        category VARCHAR(150) NOT NULL DEFAULT '',
        qiymet_ayliq DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        qiymet_paket DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        qeyd TEXT NULL,
        aktiv TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (service_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    mysqli_query($conn, $sql);
    xidmet_seed_defaults($conn);
}

function xidmet_seed_defaults(mysqli $conn): void
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT IGNORE INTO xidmet_qiymetleri (service_key, service_label, category) VALUES (?, ?, ?)"
    );
    if (!$stmt) {
        return;
    }

    foreach (xidmet_get_catalog() as $group) {
        $category = (string) ($group['title'] ?? '');
        $allServices = $group['services'] ?? [];
        foreach ($group['subsections'] ?? [] as $subsection) {
            $allServices = array_merge($allServices, $subsection['services'] ?? []);
        }

        foreach ($allServices as $service) {
            $key = (string) ($service['key'] ?? '');
            $label = (string) ($service['label'] ?? $key);
            if ($key === '') {
                continue;
            }
            mysqli_stmt_bind_param($stmt, 'sss', $key, $label, $category);
            mysqli_stmt_execute($stmt);
        }
    }

    mysqli_stmt_close($stmt);
}

function xidmet_get_all_prices(mysqli $conn): array
{
    xidmet_ensure_table($conn);

    $prices = [];
    $result = mysqli_query($conn, "SELECT * FROM xidmet_qiymetleri");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $prices[$row['service_key']] = $row;
        }
        mysqli_free_result($result);
    }

    return $prices;
}

function xidmet_get_price(mysqli $conn, string $serviceKey): ?array
{
    xidmet_ensure_table($conn);

    $stmt = mysqli_prepare($conn, "SELECT * FROM xidmet_qiymetleri WHERE service_key = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $serviceKey);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function xidmet_find_service_meta(string $serviceKey): ?array
{
    foreach (xidmet_get_catalog() as $group) {
        foreach ($group['services'] ?? [] as $service) {
            if ($service['key'] === $serviceKey) {
                return [
                    'key' => $service['key'],
                    'label' => $service['label'],
                    'category' => $group['title'],
                ];
            }
        }
        foreach ($group['subsections'] ?? [] as $subsection) {
            foreach ($subsection['services'] ?? [] as $service) {
                if ($service['key'] === $serviceKey) {
                    return [
                        'key' => $service['key'],
                        'label' => $service['label'],
                        'category' => $group['title'],
                    ];
                }
            }
        }
    }

    return null;
}

function xidmet_save_price(mysqli $conn, string $serviceKey, float $ayliq, float $paket, string $qeyd, bool $aktiv): array
{
    xidmet_ensure_table($conn);

    $meta = xidmet_find_service_meta($serviceKey);
    if (!$meta) {
        return ['ok' => false, 'message' => 'Xidmət tapılmadı.'];
    }

    if ($ayliq < 0 || $paket < 0) {
        return ['ok' => false, 'message' => 'Qiymət mənfi ola bilməz.'];
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO xidmet_qiymetleri (service_key, service_label, category, qiymet_ayliq, qiymet_paket, qeyd, aktiv)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            service_label = VALUES(service_label),
            category = VALUES(category),
            qiymet_ayliq = VALUES(qiymet_ayliq),
            qiymet_paket = VALUES(qiymet_paket),
            qeyd = VALUES(qeyd),
            aktiv = VALUES(aktiv)"
    );

    if (!$stmt) {
        return ['ok' => false, 'message' => 'Verilənlər bazası xətası.'];
    }

    $label = $meta['label'];
    $category = $meta['category'];
    $aktivInt = $aktiv ? 1 : 0;
    mysqli_stmt_bind_param($stmt, 'sssddsi', $serviceKey, $label, $category, $ayliq, $paket, $qeyd, $aktivInt);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ['ok' => false, 'message' => 'Qiymət saxlanılmadı.'];
    }

    return ['ok' => true, 'message' => $meta['label'] . ' üçün qiymət yeniləndi.'];
}

function xidmet_format_price($value): string
{
    $number = (float) $value;
    if ($number <= 0) {
        return '—';
    }
    return number_format($number, 2, '.', ' ') . ' AZN';
}

function xidmet_get_active_price_map(mysqli $conn): array
{
    $prices = xidmet_get_all_prices($conn);
    $map = [];

    foreach ($prices as $key => $row) {
        if ((int) ($row['aktiv'] ?? 1) !== 1) {
            continue;
        }

        $map[$key] = [
            'label' => (string) ($row['service_label'] ?? $key),
            'ayliq' => (float) ($row['qiymet_ayliq'] ?? 0),
            'paket' => (float) ($row['qiymet_paket'] ?? 0),
        ];
    }

    return $map;
}
