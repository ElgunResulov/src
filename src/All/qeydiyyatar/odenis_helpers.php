<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function odenis_default_tedris_ili(): string {
    $year = (int) date('Y');
    $month = (int) date('n');
    if ($month >= 9) {
        return $year . '-' . ($year + 1);
    }
    return ($year - 1) . '-' . $year;
}

function odenis_ensure_columns(mysqli $conn): void {
    $columns = [
        'novbeti_odenis_tarixi' => "ALTER TABLE qeydiyyatar ADD COLUMN novbeti_odenis_tarixi DATE NULL DEFAULT NULL AFTER ilkin_odenis",
        'son_odenis_xatirlatma' => "ALTER TABLE qeydiyyatar ADD COLUMN son_odenis_xatirlatma DATE NULL DEFAULT NULL AFTER novbeti_odenis_tarixi",
    ];

    foreach ($columns as $name => $sql) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM qeydiyyatar LIKE '$name'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, $sql);
        }
    }

    odenis_backfill_missing($conn);
}

function odenis_normalize_odenis_novu(?string $odenisNovu): string {
    $odenisNovu = trim((string) $odenisNovu);
    return in_array($odenisNovu, ['paket', 'ayliq'], true) ? $odenisNovu : 'ayliq';
}

function odenis_is_valid_date(?string $date): bool {
    $date = trim((string) $date);
    if ($date === '' || $date === '0000-00-00') {
        return false;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    return $date >= '1900-01-01';
}

/**
 * Köhnə və ya natamam qeydiyyatların ödəniş məlumatlarını düzəldir.
 */
function odenis_backfill_missing(mysqli $conn): int {
    $result = mysqli_query(
        $conn,
        "SELECT id, baslama_tarixi, odenis_novu, novbeti_odenis_tarixi
         FROM qeydiyyatar
         WHERE tehsil_haqqi > 0"
    );
    if (!$result) {
        return 0;
    }

    $updated = 0;
    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE qeydiyyatar SET odenis_novu = ?, novbeti_odenis_tarixi = ? WHERE id = ?"
    );
    if (!$updateStmt) {
        mysqli_free_result($result);
        return 0;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $currentNovu = trim((string) ($row['odenis_novu'] ?? ''));
        $novu = odenis_normalize_odenis_novu($currentNovu);
        $baslama = trim((string) ($row['baslama_tarixi'] ?? ''));
        if (!odenis_is_valid_date($baslama)) {
            $baslama = date('Y-m-d');
        }

        $currentDue = (string) ($row['novbeti_odenis_tarixi'] ?? '');
        $due = odenis_is_valid_date($currentDue)
            ? $currentDue
            : odenis_next_due_date($baslama, $novu);

        $needsNovu = !in_array($currentNovu, ['paket', 'ayliq'], true);
        $needsDue = !odenis_is_valid_date($currentDue) && $novu === 'ayliq';

        if (!$needsNovu && !$needsDue) {
            continue;
        }

        $dueParam = $due;
        $id = (int) $row['id'];
        mysqli_stmt_bind_param($updateStmt, 'ssi', $novu, $dueParam, $id);
        if (mysqli_stmt_execute($updateStmt)) {
            $updated++;
        }
    }

    mysqli_stmt_close($updateStmt);
    mysqli_free_result($result);

    return $updated;
}

function odenis_format_due_display(?string $date): string {
    if (!odenis_is_valid_date($date)) {
        return '—';
    }
    return odenis_format_az_date((string) $date);
}

function odenis_next_due_date(string $baslamaTarixi, string $odenisNovu = 'ayliq'): ?string {
    if ($odenisNovu !== 'ayliq') {
        return null;
    }

    try {
        $start = new DateTime($baslamaTarixi ?: 'today');
        $due = new DateTime($start->format('Y-m-01'));
        $due->modify('last day of next month');
        return $due->format('Y-m-d');
    } catch (Exception $e) {
        $due = new DateTime('last day of next month');
        return $due->format('Y-m-d');
    }
}

function odenis_advance_due_date(string $currentDue): string {
    $due = new DateTime($currentDue);
    $due->modify('first day of next month');
    $due->modify('last day of this month');
    return $due->format('Y-m-d');
}

function odenis_monthly_amount(float $tehsilHaqqi, string $odenisNovu): float {
    if ($odenisNovu === 'ayliq') {
        return $tehsilHaqqi;
    }
    return 0.0;
}

function odenis_format_az_date(string $date): string {
    try {
        return (new DateTime($date))->format('d.m.Y');
    } catch (Exception $e) {
        return $date;
    }
}

function odenis_novu_label(string $odenisNovu): string {
    return $odenisNovu === 'paket' ? 'Paket' : 'Aylıq';
}

/**
 * @return array{key: string, label: string, class: string}
 */
function odenis_status_meta(array $row): array {
    $odenisNovu = (string) ($row['odenis_novu'] ?? 'ayliq');
    if ($odenisNovu === 'paket') {
        return ['key' => 'paket', 'label' => 'Paket', 'class' => 'text-info'];
    }

    $due = trim((string) ($row['novbeti_odenis_tarixi'] ?? ''));
    if (!odenis_is_valid_date($due)) {
        return ['key' => 'yoxdur', 'label' => 'Tarix yoxdur', 'class' => 'text-secondary'];
    }

    $today = date('Y-m-d');
    if ($due < $today) {
        return ['key' => 'gecikmis', 'label' => 'Gecikmiş', 'class' => 'text-danger'];
    }

    $monthEnd = date('Y-m-t');
    if ($due <= $monthEnd) {
        return ['key' => 'bu_ay', 'label' => 'Bu ay', 'class' => 'text-warning'];
    }

    return ['key' => 'gozleyir', 'label' => 'Gözləyir', 'class' => 'text-primary'];
}

function odenis_mark_received(mysqli $conn, int $qeydiyyatarId): array {
    odenis_ensure_columns($conn);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, odenis_novu, novbeti_odenis_tarixi FROM qeydiyyatar WHERE id = ? LIMIT 1"
    );
    if (!$stmt) {
        return ['ok' => false, 'message' => 'Sorğu xətası.'];
    }

    mysqli_stmt_bind_param($stmt, 'i', $qeydiyyatarId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return ['ok' => false, 'message' => 'Qeydiyyat tapılmadı.'];
    }

    if (($row['odenis_novu'] ?? '') !== 'ayliq') {
        return ['ok' => false, 'message' => 'Yalnız aylıq ödənişlər üçün tətbiq olunur.'];
    }

    $currentDue = (string) ($row['novbeti_odenis_tarixi'] ?? '');
    if ($currentDue === '') {
        return ['ok' => false, 'message' => 'Növbəti ödəniş tarixi təyin edilməyib.'];
    }

    $nextDue = odenis_advance_due_date($currentDue);
    $today = date('Y-m-d');

    $update = mysqli_prepare(
        $conn,
        "UPDATE qeydiyyatar SET novbeti_odenis_tarixi = ?, son_odenis_xatirlatma = NULL WHERE id = ?"
    );
    if (!$update) {
        return ['ok' => false, 'message' => 'Yeniləmə xətası.'];
    }

    mysqli_stmt_bind_param($update, 'si', $nextDue, $qeydiyyatarId);
    $ok = mysqli_stmt_execute($update);
    mysqli_stmt_close($update);

    if (!$ok) {
        return ['ok' => false, 'message' => 'Ödəniş qeydə alınmadı.'];
    }

    return [
        'ok' => true,
        'message' => 'Ödəniş alındı. Növbəti tarix: ' . odenis_format_az_date($nextDue),
        'next_due' => $nextDue,
    ];
}

function sendPaymentReminderEmail(
    string $email,
    string $fullName,
    float $amount,
    string $dueDate,
    string $odenisNovu
): bool {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.texnosoft.com.tr';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'account@texnosoft.com.tr';
        $mail->Password   = 'Kamran1962+++';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->setFrom('account@texnosoft.com.tr', 'Magistratura AZ');
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Ödəniş xatırlatması - ' . odenis_format_az_date($dueDate);

        $amountText = number_format($amount, 2, '.', '');
        $dueText = odenis_format_az_date($dueDate);
        $novuText = $odenisNovu === 'paket' ? 'Paket' : 'Aylıq';

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='az'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family:Segoe UI,Arial,sans-serif;background:#f5f7fb;padding:24px;color:#1e293b;'>
            <div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 8px 24px rgba(30,58,138,.12);'>
                <h2 style='color:#1d4ed8;margin:0 0 16px;'>Ödəniş xatırlatması</h2>
                <p>Hörmətli <strong>" . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                <p>Ödəniş müddətiniz yaxınlaşır. Zəhmət olmasa aşağıdakı məlumatları nəzərə alın:</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
                    <tr><td style='padding:10px;border-bottom:1px solid #e2e8f0;'><strong>Ödəniş növü</strong></td><td style='padding:10px;border-bottom:1px solid #e2e8f0;'>{$novuText}</td></tr>
                    <tr><td style='padding:10px;border-bottom:1px solid #e2e8f0;'><strong>Məbləğ</strong></td><td style='padding:10px;border-bottom:1px solid #e2e8f0;'>{$amountText} AZN</td></tr>
                    <tr><td style='padding:10px;'><strong>Son ödəniş tarixi</strong></td><td style='padding:10px;'>{$dueText}</td></tr>
                </table>
                <p style='color:#64748b;font-size:14px;'>Ödəniş vaxtında edilmədikdə dərsləriniz dayandırıla bilər. Suallarınız üçün bizimlə əlaqə saxlayın.</p>
                <p style='margin-top:24px;'>Hörmətlə,<br><strong>Magistratura AZ</strong></p>
            </div>
        </body>
        </html>";

        $mail->AltBody = "Hörmətli {$fullName}, ödəniş müddətiniz {$dueText} tarixinədəkdir. Məbləğ: {$amountText} AZN.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Payment reminder email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ay sonu ödəniş xatırlatmalarını göndərir.
 * Cron: hər gün işlədin; son ödəniş günü və ya gecikmiş tarixlər üçün e-poçt göndərilir.
 */
function process_payment_reminders(mysqli $conn): array {
    odenis_ensure_columns($conn);

    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');

    $sql = "
        SELECT q.id, q.u_id, q.telebe_ad_soyad, q.tehsil_haqqi, q.odenis_novu,
               q.novbeti_odenis_tarixi, q.son_odenis_xatirlatma,
               COALESCE(NULLIF(q.form_email, ''), t.reg_email, t.poct) AS email,
               t.active_status
        FROM qeydiyyatar q
        INNER JOIN telebeler t ON t.u_id = q.u_id
        WHERE t.active_status = 'active'
          AND q.odenis_novu = 'ayliq'
          AND q.tehsil_haqqi > 0
          AND q.novbeti_odenis_tarixi IS NOT NULL
          AND q.novbeti_odenis_tarixi <= ?
          AND (q.son_odenis_xatirlatma IS NULL OR q.son_odenis_xatirlatma < ?)
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'error' => mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'ss', $today, $monthStart);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $sent = 0;
    $failed = 0;
    $skipped = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $skipped++;
            continue;
        }

        $fullName = str_replace('.', ' ', (string) $row['telebe_ad_soyad']);
        $amount = odenis_monthly_amount((float) $row['tehsil_haqqi'], (string) $row['odenis_novu']);
        $dueDate = (string) $row['novbeti_odenis_tarixi'];

        if (sendPaymentReminderEmail($email, $fullName, $amount, $dueDate, (string) $row['odenis_novu'])) {
            $nextDue = odenis_advance_due_date($dueDate);
            $update = mysqli_prepare(
                $conn,
                "UPDATE qeydiyyatar SET son_odenis_xatirlatma = ?, novbeti_odenis_tarixi = ? WHERE id = ?"
            );
            if ($update) {
                mysqli_stmt_bind_param($update, 'ssi', $today, $nextDue, $row['id']);
                mysqli_stmt_execute($update);
                mysqli_stmt_close($update);
            }
            $sent++;
        } else {
            $failed++;
        }
    }

    mysqli_stmt_close($stmt);

    return [
        'sent' => $sent,
        'failed' => $failed,
        'skipped' => $skipped,
        'date' => $today,
    ];
}
