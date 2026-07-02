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
