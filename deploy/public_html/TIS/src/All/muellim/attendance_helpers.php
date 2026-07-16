<?php

/**
 * Davamiyyət / maaş köməkçiləri.
 * 1 maaş vahidi = 8 dərs (müəllim–tələbə cütü).
 * Gün qrupları: 1-4, 2-5, 3-6.
 */

declare(strict_types=1);

const ATT_CYCLE_SIZE = 8;
const ATT_MAX_SCANS_PER_WEEK = 2; // bir tələbə eyni müəllimlə həftədə max 2 dəfə
const ATT_SALARY_RATE_AZN = 0.0; // >0 olarsa paneldə AZN də göstərilir

const ATT_DAY_NAMES = [
    1 => 'Bazar ertəsi',
    2 => 'Çərşənbə axşamı',
    3 => 'Çərşənbə',
    4 => 'Cümə axşamı',
    5 => 'Cümə',
    6 => 'Şənbə',
    7 => 'Bazar',
];

const ATT_DAY_GROUPS = [
    '1-4' => [1, 4],
    '2-5' => [2, 5],
    '3-6' => [3, 6],
];

function att_day_name_to_iso(?string $dayName): ?int
{
    $dayName = trim((string) $dayName);
    if ($dayName === '') {
        return null;
    }
    $map = array_flip(ATT_DAY_NAMES);

    return $map[$dayName] ?? null;
}

function att_iso_to_day_name(int $isoDay): string
{
    return ATT_DAY_NAMES[$isoDay] ?? '';
}

function att_group_for_iso(int $isoDay): ?string
{
    foreach (ATT_DAY_GROUPS as $code => $days) {
        if (in_array($isoDay, $days, true)) {
            return $code;
        }
    }

    return null;
}

function att_group_for_day_name(?string $dayName): ?string
{
    $iso = att_day_name_to_iso($dayName);

    return $iso === null ? null : att_group_for_iso($iso);
}

function att_group_day_names(string $groupCode): array
{
    $isos = ATT_DAY_GROUPS[$groupCode] ?? [];
    $names = [];
    foreach ($isos as $iso) {
        $names[] = att_iso_to_day_name($iso);
    }

    return $names;
}

function att_today_iso(?int $timestamp = null): int
{
    return (int) date('N', $timestamp ?? time());
}

/**
 * @return array<int, array{filial:string,saat:string,gun:string,telebe:string,gun_iso:?int,gun_qrupu:?string}>
 */
function att_parse_teacher_schedule(?string $telebelerJson): array
{
    $decoded = json_decode((string) $telebelerJson, true);
    if (!is_array($decoded)) {
        return [];
    }

    $rows = [];
    foreach ($decoded as $entry) {
        if (!is_array($entry) || count($entry) < 4) {
            continue;
        }
        $student = trim((string) ($entry[3] ?? ''));
        $day = trim((string) ($entry[2] ?? ''));
        if ($student === '' || $day === '') {
            continue;
        }
        $iso = att_day_name_to_iso($day);
        $rows[] = [
            'filial' => trim((string) ($entry[0] ?? '')),
            'saat' => trim((string) ($entry[1] ?? '')),
            'gun' => $day,
            'telebe' => $student,
            'gun_iso' => $iso,
            'gun_qrupu' => $iso === null ? null : att_group_for_iso($iso),
        ];
    }

    return $rows;
}

/**
 * Müəllimin cədvəlindəki unikal tələbələr + əsas gün qrupu.
 *
 * @return array<string, array{username:string,gun_qrupu:?string,gunler:string[],saatlar:string[],filiallar:string[]}>
 */
function att_teacher_students(array $scheduleRows): array
{
    $students = [];
    foreach ($scheduleRows as $row) {
        $name = $row['telebe'];
        if (!isset($students[$name])) {
            $students[$name] = [
                'username' => $name,
                'gun_qrupu' => $row['gun_qrupu'],
                'gunler' => [],
                'saatlar' => [],
                'filiallar' => [],
            ];
        }
        if ($row['gun'] !== '' && !in_array($row['gun'], $students[$name]['gunler'], true)) {
            $students[$name]['gunler'][] = $row['gun'];
        }
        if ($row['saat'] !== '' && !in_array($row['saat'], $students[$name]['saatlar'], true)) {
            $students[$name]['saatlar'][] = $row['saat'];
        }
        if ($row['filial'] !== '' && !in_array($row['filial'], $students[$name]['filiallar'], true)) {
            $students[$name]['filiallar'][] = $row['filial'];
        }
        if ($students[$name]['gun_qrupu'] === null && $row['gun_qrupu'] !== null) {
            $students[$name]['gun_qrupu'] = $row['gun_qrupu'];
        }
    }

    ksort($students, SORT_STRING);

    return $students;
}

/**
 * @return array{current:int,cycle_size:int,completed_units:int,percent:int,label:string,is_cycle_complete:bool}
 */
function att_cycle_from_count(int $totalScans): array
{
    $totalScans = max(0, $totalScans);
    $cycle = ATT_CYCLE_SIZE;
    $units = intdiv($totalScans, $cycle);
    if ($totalScans === 0) {
        $current = 0;
    } else {
        $mod = $totalScans % $cycle;
        $current = $mod === 0 ? $cycle : $mod;
    }

    return [
        'current' => $current,
        'cycle_size' => $cycle,
        'completed_units' => $units,
        'percent' => (int) round(($current / $cycle) * 100),
        'label' => $current . '/' . $cycle,
        'is_cycle_complete' => $totalScans > 0 && ($totalScans % $cycle) === 0,
    ];
}

function att_scan_counts_by_student(mysqli $conn, string $teacherUsername): array
{
    $sql = 'SELECT student_username, COUNT(*) AS total_scans, MAX(scan_date) AS last_scan_date, MAX(scan_time) AS last_scan_time
            FROM qr_scans
            WHERE teacher_username = ?
            GROUP BY student_username';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 's', $teacherUsername);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $map = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $map[$row['student_username']] = [
            'total_scans' => (int) $row['total_scans'],
            'last_scan_date' => $row['last_scan_date'] ?? null,
            'last_scan_time' => $row['last_scan_time'] ?? null,
        ];
    }
    mysqli_stmt_close($stmt);

    return $map;
}

function att_scanned_usernames_on_date(mysqli $conn, string $teacherUsername, string $date): array
{
    $sql = 'SELECT DISTINCT student_username FROM qr_scans WHERE teacher_username = ? AND DATE(scan_date) = ?';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'ss', $teacherUsername, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $names = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $names[$row['student_username']] = true;
    }
    mysqli_stmt_close($stmt);

    return $names;
}

/**
 * Bu gün gözlənilən tələbələr + status + 8/8 progress.
 *
 * @return array{day_name:string,gun_qrupu:?string,expected:int,scanned:int,pending:int,students:array}
 */
function att_build_today_list(
    mysqli $conn,
    string $teacherUsername,
    ?string $telebelerJson,
    ?string $date = null
): array {
    $date = $date ?: date('Y-m-d');
    $iso = (int) date('N', strtotime($date));
    $dayName = att_iso_to_day_name($iso);
    $group = att_group_for_iso($iso);
    $schedule = att_parse_teacher_schedule($telebelerJson);
    $allStudents = att_teacher_students($schedule);
    $scanCounts = att_scan_counts_by_student($conn, $teacherUsername);
    $scannedToday = att_scanned_usernames_on_date($conn, $teacherUsername, $date);

    $todayNames = [];
    foreach ($schedule as $row) {
        if ($row['gun_iso'] === $iso) {
            $todayNames[$row['telebe']] = true;
        }
    }

    $students = [];
    foreach ($allStudents as $username => $info) {
        $isExpected = isset($todayNames[$username]);
        $didScan = isset($scannedToday[$username]);
        $count = (int) ($scanCounts[$username]['total_scans'] ?? 0);
        $cycle = att_cycle_from_count($count);

        if ($isExpected) {
            $status = $didScan ? 'scanned' : 'pending';
        } elseif ($didScan) {
            $status = 'extra';
        } else {
            continue;
        }

        $students[] = [
            'username' => $username,
            'gun_qrupu' => $info['gun_qrupu'],
            'gunler' => $info['gunler'],
            'saat' => implode(', ', $info['saatlar']),
            'status' => $status,
            'total_scans' => $count,
            'cycle' => $cycle,
            'last_scan_date' => $scanCounts[$username]['last_scan_date'] ?? null,
            'alert_near_complete' => $cycle['current'] === ($cycle['cycle_size'] - 1),
        ];
    }

    usort($students, static function ($a, $b) {
        $order = ['pending' => 0, 'scanned' => 1, 'extra' => 2];
        $oa = $order[$a['status']] ?? 9;
        $ob = $order[$b['status']] ?? 9;
        if ($oa !== $ob) {
            return $oa <=> $ob;
        }

        return strcmp($a['username'], $b['username']);
    });

    $expected = 0;
    $scanned = 0;
    foreach ($students as $s) {
        if ($s['status'] === 'pending' || $s['status'] === 'scanned') {
            $expected++;
        }
        if ($s['status'] === 'scanned' || $s['status'] === 'extra') {
            $scanned++;
        }
    }
    $pending = 0;
    foreach ($students as $s) {
        if ($s['status'] === 'pending') {
            $pending++;
        }
    }

    return [
        'day_name' => $dayName,
        'gun_qrupu' => $group,
        'expected' => $expected,
        'scanned' => $scanned,
        'pending' => $pending,
        'students' => $students,
    ];
}

/**
 * @return array{group:string,days:array,summary:string}
 */
function att_build_week_view(
    mysqli $conn,
    string $teacherUsername,
    ?string $telebelerJson,
    string $groupCode,
    ?string $weekStart = null
): array {
    if (!isset(ATT_DAY_GROUPS[$groupCode])) {
        $groupCode = '1-4';
    }
    $weekStart = $weekStart ?: date('Y-m-d', strtotime('monday this week'));
    $mondayTs = strtotime($weekStart);
    $schedule = att_parse_teacher_schedule($telebelerJson);
    $dayIsos = ATT_DAY_GROUPS[$groupCode];
    $days = [];

    foreach ($dayIsos as $iso) {
        $date = date('Y-m-d', strtotime('+' . ($iso - 1) . ' days', $mondayTs));
        $dayName = att_iso_to_day_name($iso);
        $scannedToday = att_scanned_usernames_on_date($conn, $teacherUsername, $date);
        $names = [];
        foreach ($schedule as $row) {
            if ($row['gun_iso'] === $iso) {
                $names[$row['telebe']] = true;
            }
        }
        $list = [];
        foreach (array_keys($names) as $username) {
            $list[] = [
                'username' => $username,
                'scanned' => isset($scannedToday[$username]),
            ];
        }
        usort($list, static fn ($a, $b) => strcmp($a['username'], $b['username']));
        $days[] = [
            'iso' => $iso,
            'date' => $date,
            'day_name' => $dayName,
            'students' => $list,
            'scanned_count' => count(array_filter($list, static fn ($s) => $s['scanned'])),
            'total' => count($list),
        ];
    }

    $came = 0;
    $slots = 0;
    foreach ($days as $d) {
        $came += $d['scanned_count'];
        $slots += $d['total'];
    }
    $left = max(0, $slots - $came);
    $summary = sprintf('Bu həftə %d dərsə gəlib, %d qalıb', $came, $left);

    return [
        'group' => $groupCode,
        'week_start' => $weekStart,
        'days' => $days,
        'summary' => $summary,
    ];
}

/**
 * Maaş hesabatı: ömür boyu vahidlər + seçilmiş dövrdə bağlanan 8-liklər.
 *
 * @return array{students:array,total_units_lifetime:int,total_units_period:int,period_start:string,period_end:string,rate_azn:float,amount_azn:float}
 */
function att_build_salary_report(
    mysqli $conn,
    string $teacherUsername,
    ?string $telebelerJson,
    string $periodStart,
    string $periodEnd
): array {
    $schedule = att_parse_teacher_schedule($telebelerJson);
    $scheduled = att_teacher_students($schedule);
    $scanCounts = att_scan_counts_by_student($conn, $teacherUsername);

    $sql = 'SELECT student_username, scan_date, scan_time
            FROM qr_scans
            WHERE teacher_username = ?
            ORDER BY student_username ASC, scan_time ASC';
    $stmt = mysqli_prepare($conn, $sql);
    $byStudent = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $teacherUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $byStudent[$row['student_username']][] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    $allNames = array_unique(array_merge(array_keys($scheduled), array_keys($byStudent)));
    sort($allNames);

    $students = [];
    $totalLife = 0;
    $totalPeriod = 0;

    foreach ($allNames as $username) {
        $scans = $byStudent[$username] ?? [];
        $total = count($scans);
        $cycle = att_cycle_from_count($total);
        $periodUnits = 0;
        $n = 0;
        foreach ($scans as $scan) {
            $n++;
            if ($n % ATT_CYCLE_SIZE !== 0) {
                continue;
            }
            $scanDay = substr((string) ($scan['scan_date'] ?? ''), 0, 10);
            if ($scanDay >= $periodStart && $scanDay <= $periodEnd) {
                $periodUnits++;
            }
        }
        $totalLife += $cycle['completed_units'];
        $totalPeriod += $periodUnits;
        $students[] = [
            'username' => $username,
            'gun_qrupu' => $scheduled[$username]['gun_qrupu'] ?? att_group_for_day_name($scheduled[$username]['gunler'][0] ?? null),
            'total_scans' => $total,
            'cycle' => $cycle,
            'units_lifetime' => $cycle['completed_units'],
            'units_period' => $periodUnits,
            'last_scan_date' => $scanCounts[$username]['last_scan_date'] ?? null,
        ];
    }

    $rate = ATT_SALARY_RATE_AZN;

    return [
        'students' => $students,
        'total_units_lifetime' => $totalLife,
        'total_units_period' => $totalPeriod,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'rate_azn' => $rate,
        'amount_azn' => $rate > 0 ? $totalPeriod * $rate : 0.0,
    ];
}

/**
 * @return array{pending_today:array,near_complete:array,inactive:array}
 */
function att_build_alerts(array $todayList, array $salaryStudents, int $inactiveDays = 14): array
{
    $pending = [];
    $near = [];
    foreach ($todayList['students'] as $s) {
        if ($s['status'] === 'pending') {
            $pending[] = $s;
        }
        if (!empty($s['alert_near_complete'])) {
            $near[] = $s;
        }
    }

    $cutoff = date('Y-m-d', strtotime('-' . $inactiveDays . ' days'));
    $inactive = [];
    foreach ($salaryStudents as $s) {
        $last = $s['last_scan_date'] ?? null;
        if ($last === null) {
            if (($s['total_scans'] ?? 0) === 0 && isset($s['gun_qrupu'])) {
                // scheduled but never scanned — mild risk, skip unless we know they're scheduled
            }
            continue;
        }
        $lastDay = substr((string) $last, 0, 10);
        if ($lastDay < $cutoff) {
            $inactive[] = $s;
        }
    }

    return [
        'pending_today' => $pending,
        'near_complete' => $near,
        'inactive' => $inactive,
    ];
}

function att_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Gözlənilir',
        'scanned' => 'Skan olundu',
        'extra' => 'Əlavə skan',
        default => $status,
    };
}

function att_progress_message(string $studentName, int $totalScans): string
{
    $cycle = att_cycle_from_count($totalScans);
    if ($cycle['is_cycle_complete']) {
        return sprintf(
            '%s — %s tamamlandı · +1 maaş vahidi · yeni dövr 0/%d',
            $studentName,
            $cycle['label'],
            $cycle['cycle_size']
        );
    }
    $left = $cycle['cycle_size'] - $cycle['current'];

    return sprintf(
        '%s — %s · qalan %d dərs',
        $studentName,
        $cycle['label'],
        $left
    );
}

/**
 * Həftənin bazar ertəsi–bazar aralığında eyni müəllim–tələbə skan sayı.
 */
function att_week_scan_count(
    mysqli $conn,
    string $teacherUsername,
    string $studentUsername,
    ?string $forDate = null
): int {
    $ts = $forDate ? strtotime($forDate) : time();
    if ($ts === false) {
        $ts = time();
    }
    $weekStart = date('Y-m-d', strtotime('monday this week', $ts));
    $weekEnd = date('Y-m-d', strtotime('sunday this week', $ts));

    $sql = 'SELECT COUNT(*) AS total
            FROM qr_scans
            WHERE teacher_username = ?
              AND student_username = ?
              AND DATE(scan_date) BETWEEN ? AND ?';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0;
    }
    mysqli_stmt_bind_param($stmt, 'ssss', $teacherUsername, $studentUsername, $weekStart, $weekEnd);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (int) ($row['total'] ?? 0);
}

/**
 * @return array{allowed:bool,count:int,max:int,week_start:string,week_end:string,message:?string}
 */
function att_week_scan_gate(
    mysqli $conn,
    string $teacherUsername,
    string $studentUsername,
    ?string $forDate = null
): array {
    $ts = $forDate ? strtotime($forDate) : time();
    if ($ts === false) {
        $ts = time();
    }
    $weekStart = date('Y-m-d', strtotime('monday this week', $ts));
    $weekEnd = date('Y-m-d', strtotime('sunday this week', $ts));
    $count = att_week_scan_count($conn, $teacherUsername, $studentUsername, $forDate);
    $max = ATT_MAX_SCANS_PER_WEEK;
    $allowed = $count < $max;

    return [
        'allowed' => $allowed,
        'count' => $count,
        'max' => $max,
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'message' => $allowed
            ? null
            : sprintf(
                'Bu həftə bu müəllimlə artıq %d dərs qeydiyyatı var (maksimum %d). Növbəti həftə yenidən gələ bilərsiniz.',
                $count,
                $max
            ),
    ];
}

/**
 * Kağız davamiyyət jurnalı üçün matris.
 *
 * @return array{
 *   group:string,
 *   month:string,
 *   year:int,
 *   month_label:string,
 *   dates:array<int,array{date:string,day:int,iso:int}>,
 *   students:array<int,array{username:string,saat:string,marks:array<string,bool>}>,
 *   times:string[]
 * }
 */
function att_build_journal_sheet(
    mysqli $conn,
    string $teacherUsername,
    ?string $telebelerJson,
    string $groupCode,
    int $year,
    int $month
): array {
    if (!isset(ATT_DAY_GROUPS[$groupCode])) {
        $groupCode = '1-4';
    }
    $isos = ATT_DAY_GROUPS[$groupCode];
    $schedule = att_parse_teacher_schedule($telebelerJson);
    $allStudents = att_teacher_students($schedule);

    $times = [];
    foreach ($schedule as $row) {
        if (($row['gun_qrupu'] ?? null) === $groupCode && $row['saat'] !== '') {
            $times[$row['saat']] = true;
        }
    }
    $times = array_keys($times);
    sort($times);

    $monthStart = sprintf('%04d-%02d-01', $year, $month);
    $daysInMonth = (int) date('t', strtotime($monthStart));
    $dates = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $iso = (int) date('N', strtotime($date));
        if (!in_array($iso, $isos, true)) {
            continue;
        }
        $dates[] = [
            'date' => $date,
            'day' => $d,
            'iso' => $iso,
        ];
    }

    $monthEnd = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
    $sql = 'SELECT student_username, DATE(scan_date) AS scan_day
            FROM qr_scans
            WHERE teacher_username = ?
              AND DATE(scan_date) BETWEEN ? AND ?';
    $stmt = mysqli_prepare($conn, $sql);
    $marksMap = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sss', $teacherUsername, $monthStart, $monthEnd);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $marksMap[$row['student_username']][$row['scan_day']] = true;
        }
        mysqli_stmt_close($stmt);
    }

    $students = [];
    foreach ($allStudents as $username => $info) {
        $onGroup = ($info['gun_qrupu'] ?? null) === $groupCode;
        if (!$onGroup) {
            foreach ($info['gunler'] as $gun) {
                if (att_group_for_day_name($gun) === $groupCode) {
                    $onGroup = true;
                    break;
                }
            }
        }
        if (!$onGroup && empty($marksMap[$username])) {
            continue;
        }
        if (!$onGroup) {
            // yalnız bu ay skanı olanlar (qrupdan kənar əlavə)
            $hasMark = false;
            foreach ($dates as $dt) {
                if (!empty($marksMap[$username][$dt['date']])) {
                    $hasMark = true;
                    break;
                }
            }
            if (!$hasMark) {
                continue;
            }
        }

        $marks = [];
        foreach ($dates as $dt) {
            $marks[$dt['date']] = !empty($marksMap[$username][$dt['date']]);
        }
        $students[] = [
            'username' => $username,
            'saat' => implode(', ', $info['saatlar'] ?? []),
            'marks' => $marks,
        ];
    }

    usort($students, static fn ($a, $b) => strcmp($a['username'], $b['username']));

    $monthNames = [
        1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel',
        5 => 'May', 6 => 'İyun', 7 => 'İyul', 8 => 'Avqust',
        9 => 'Sentyabr', 10 => 'Oktyabr', 11 => 'Noyabr', 12 => 'Dekabr',
    ];

    return [
        'group' => $groupCode,
        'month' => sprintf('%02d', $month),
        'year' => $year,
        'month_label' => ($monthNames[$month] ?? '') . ' ' . $year,
        'dates' => $dates,
        'students' => $students,
        'times' => $times,
        'group_days' => att_group_day_names($groupCode),
    ];
}

/**
 * Müəllimi u_id / username / FIN ilə tapır (ə/a encoding probleminə qarşı).
 *
 * @return array{id?:int,u_id:string,username:string,telebeler?:?string,tehsil_ve_ixtisas?:string,qr_code?:string,fenn?:string,unvan?:string}|null
 */
function att_resolve_teacher_row(
    mysqli $conn,
    string $teacherUsername = '',
    string $uId = '',
    string $fin = ''
): ?array {
    $teacherUsername = trim($teacherUsername);
    $uId = trim($uId);
    $fin = trim($fin);

    if ($uId !== '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, u_id, username, telebeler, tehsil_ve_ixtisas, qr_code, fenn, unvan
             FROM muellimler_new
             WHERE u_id = ? AND active_status = 'active'
             LIMIT 1"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $uId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                return $row;
            }
        }
    }

    if ($teacherUsername !== '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, u_id, username, telebeler, tehsil_ve_ixtisas, qr_code, fenn, unvan
             FROM muellimler_new
             WHERE username = ? AND active_status = 'active'
             LIMIT 1"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $teacherUsername);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                return $row;
            }
        }
    }

    $finLookup = $fin !== '' ? $fin : $teacherUsername;
    if ($finLookup !== '') {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT m.id, m.u_id, m.username, m.telebeler, m.tehsil_ve_ixtisas, m.qr_code, m.fenn, m.unvan
             FROM users u
             INNER JOIN muellimler_new m ON m.u_id = u.u_id
             WHERE u.username = ? AND m.active_status = 'active'
             LIMIT 1"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $finLookup);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                return $row;
            }
        }
    }

    return null;
}
