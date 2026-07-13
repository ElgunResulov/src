<?php

function contract_load_row(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT * FROM qeydiyyatar WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function contract_h(?string $value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function contract_date_az(?string $date): string {
    if (empty($date) || $date === '0000-00-00') {
        return '“___” “_______” “____”';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return contract_h($date);
    }
    $months = [
        1 => 'yanvar', 2 => 'fevral', 3 => 'mart', 4 => 'aprel', 5 => 'may', 6 => 'iyun',
        7 => 'iyul', 8 => 'avqust', 9 => 'sentyabr', 10 => 'oktyabr', 11 => 'noyabr', 12 => 'dekabr',
    ];
    $day = date('j', $ts);
    $month = $months[(int) date('n', $ts)] ?? '';
    $year = date('Y', $ts);
    return '“' . $day . '” “' . $month . '” “' . $year . '”';
}

function contract_course_name(array $row): string {
    $name = trim((string) ($row['ixtisas_adi'] ?? ''));
    if ($name === '' || $name === 'Naməlum') {
        $name = trim((string) ($row['form_ixtisas'] ?? ''));
    }
    if ($name === '') {
        $services = json_decode((string) ($row['form_services'] ?? ''), true);
        if (is_array($services) && !empty($services[0])) {
            $name = (string) $services[0];
        }
    }
    return $name !== '' ? $name : '_________________';
}

function contract_student_name(array $row): string {
    $raw = $row['telebe_ad_soyad'] ?: ($row['form_ad_soyad'] ?? '');
    return str_replace('.', ' ', trim((string) $raw));
}

function contract_payment_type(array $row): string {
    return (($row['odenis_novu'] ?? '') === 'paket') ? 'paket' : 'aylıq';
}

function contract_fee(array $row): string {
    $fee = (float) ($row['tehsil_haqqi'] ?? 0);
    return $fee > 0 ? number_format($fee, 0, '.', '') : '_________';
}

function contract_lesson_count(array $row): string {
    $count = trim((string) ($row['ders_sayi'] ?? ''));
    return $count !== '' ? $count : '8 / 16 / 24';
}

function contract_course_display(array $row): string {
    $name = contract_course_name($row);
    return $name !== '_________________' ? $name : '_________________________';
}

function contract_student_display(array $row): string {
    $name = contract_student_name($row);
    return $name !== '' && $name !== '.' ? $name : '_________________________';
}

function contract_payment_schedule(array $row): array {
    require_once __DIR__ . '/odenis_helpers.php';
    require_once __DIR__ . '/services_helpers.php';

    $net = odenis_effective_fee(
        (float) ($row['tehsil_haqqi'] ?? 0),
        (float) ($row['endirim_meqdar'] ?? 0)
    );

    if ($net <= 0) {
        return [];
    }

    return odenis_split_monthly_schedule($net);
}

/**
 * @return array{text: string, filled: bool}
 */
function contract_payment_slot(array $schedule, int $index): array {
    if (isset($schedule[$index]) && (float) $schedule[$index] > 0) {
        return [
            'text' => number_format((float) $schedule[$index], 2, '.', ''),
            'filled' => true,
        ];
    }

    return ['text' => '___________', 'filled' => false];
}

function contract_payment_amount(array $schedule, int $index): string {
    return contract_payment_slot($schedule, $index)['text'];
}

function contract_ilkin_odenis_amount(array $row): string {
    require_once __DIR__ . '/odenis_helpers.php';
    return number_format(odenis_ilkin_mebleg(), 2, '.', '');
}

function contract_payment_side_label(int $index): string {
    $labels = [
        2 => 'III ödəniş',
        3 => 'IV ödəniş',
        4 => 'V ödəniş',
        5 => 'VI ödəniş',
    ];

    return $labels[$index] ?? '';
}
