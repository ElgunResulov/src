<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;

define('MUELLIM_UPLOADS_DIR', __DIR__ . '/../../Uploads');
define('MUELLIM_PROFILES_DIR', MUELLIM_UPLOADS_DIR . '/profiles');
define('MUELLIM_QRCODES_DIR', MUELLIM_UPLOADS_DIR . '/qrcodes');
define('MUELLIM_QRCODES_URL', '../Uploads/qrcodes/');

function qr_ensure_upload_dir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new Exception('Qovluq yaradıla bilmədi: ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new Exception('Qovluq yazmağa icazəli deyil: ' . $dir);
    }
}

function qr_filename_for_teacher(int $teacherId): string
{
    $extension = extension_loaded('gd') ? 'png' : 'svg';

    return 'qr_muellim_' . $teacherId . '.' . $extension;
}

function qr_generate_teacher_code(string $content, string $dir, ?string $filename = null): string
{
    qr_ensure_upload_dir($dir);

    if ($filename === null) {
        $filename = uniqid('qr_', true) . (extension_loaded('gd') ? '.png' : '.svg');
    }

    $path = $dir . DIRECTORY_SEPARATOR . $filename;

    if (extension_loaded('gd')) {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => 10,
        ]);
        $imageData = (new QRCode($options))->render($content);
        if (file_put_contents($path, $imageData) === false) {
            throw new Exception('QR kod faylı yaradıla bilmədi.');
        }
    } else {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgUseFillAttributes' => true,
        ]);
        $svg = (new QRCode($options))->render($content);
        if (file_put_contents($path, $svg) === false) {
            throw new Exception('QR kod faylı yaradıla bilmədi.');
        }
    }

    if (!is_file($path)) {
        throw new Exception('QR kod faylı yaradıla bilmədi.');
    }

    return $filename;
}

function qr_teacher_content(array $teacher): string
{
    $uId = trim((string) ($teacher['u_id'] ?? ''));
    $username = trim((string) ($teacher['username'] ?? ''));

    if ($uId === '' || $username === '') {
        throw new Exception('Müəllim məlumatları tam deyil.');
    }

    return $uId . ':' . $username;
}

function qr_teacher_file_exists(?string $filename): bool
{
    if ($filename === null || trim($filename) === '') {
        return false;
    }

    return is_file(MUELLIM_QRCODES_DIR . DIRECTORY_SEPARATOR . $filename);
}

function qr_remove_orphan_file(?string $filename, int $teacherId): void
{
    if ($filename === null || trim($filename) === '') {
        return;
    }

    $expectedFilename = qr_filename_for_teacher($teacherId);
    if ($filename === $expectedFilename) {
        return;
    }

    $path = MUELLIM_QRCODES_DIR . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

function qr_is_code_used_by_other(mysqli $conn, string $filename, int $teacherId): bool
{
    $sql = 'SELECT COUNT(*) AS total FROM muellimler_new WHERE qr_code = ? AND id <> ?';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'si', $filename, $teacherId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return ((int) ($row['total'] ?? 0)) > 0;
}

function qr_activate_teacher(mysqli $conn, array $teacher, bool $forceRegenerate = false): array
{
    $teacherId = (int) ($teacher['id'] ?? 0);
    if ($teacherId <= 0) {
        throw new Exception('Müəllim ID tapılmadı.');
    }

    $content = qr_teacher_content($teacher);
    $expectedFilename = qr_filename_for_teacher($teacherId);
    $existingFile = trim((string) ($teacher['qr_code'] ?? ''));

    if (
        !$forceRegenerate
        && $existingFile === $expectedFilename
        && qr_teacher_file_exists($expectedFilename)
    ) {
        return $teacher;
    }

    if ($existingFile !== '' && $existingFile !== $expectedFilename) {
        qr_remove_orphan_file($existingFile, $teacherId);
    }

    $filename = qr_generate_teacher_code($content, MUELLIM_QRCODES_DIR, $expectedFilename);

    $updateSql = 'UPDATE muellimler_new SET qr_code = ?, active_status = ? WHERE id = ?';
    $stmt = mysqli_prepare($conn, $updateSql);
    if (!$stmt) {
        throw new Exception('Verilənlər bazası xətası: ' . mysqli_error($conn));
    }

    $activeStatus = 'active';
    mysqli_stmt_bind_param($stmt, 'ssi', $filename, $activeStatus, $teacherId);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('QR kod aktivləşdirilə bilmədi: ' . $error);
    }
    mysqli_stmt_close($stmt);

    $teacher['qr_code'] = $filename;
    $teacher['active_status'] = $activeStatus;

    return $teacher;
}

function qr_activate_all_teachers(mysqli $conn): array
{
    $summary = [
        'total' => 0,
        'activated' => 0,
        'already_active' => 0,
        'errors' => [],
    ];

    $result = mysqli_query(
        $conn,
        'SELECT id, u_id, username, qr_code, active_status FROM muellimler_new ORDER BY id ASC'
    );

    if (!$result) {
        throw new Exception('Müəllim siyahısı alına bilmədi: ' . mysqli_error($conn));
    }

    while ($teacher = mysqli_fetch_assoc($result)) {
        $summary['total']++;
        $before = trim((string) ($teacher['qr_code'] ?? ''));
        $expected = qr_filename_for_teacher((int) $teacher['id']);

        try {
            $activated = qr_activate_teacher($conn, $teacher);
            $after = trim((string) ($activated['qr_code'] ?? ''));

            if ($before === $after && $before === $expected && qr_teacher_file_exists($after)) {
                $summary['already_active']++;
            } else {
                $summary['activated']++;
            }
        } catch (Exception $e) {
            $summary['errors'][] = [
                'username' => $teacher['username'] ?? '',
                'message' => $e->getMessage(),
            ];
        }
    }

    return $summary;
}

function qr_teacher_public_meta(array $teacher): array
{
    $filename = trim((string) ($teacher['qr_code'] ?? ''));
    $hasFile = qr_teacher_file_exists($filename);

    return [
        'qr_code' => $filename,
        'qr_url' => $hasFile ? MUELLIM_QRCODES_URL . $filename : '',
        'qr_content' => qr_teacher_content($teacher),
        'qr_ready' => $hasFile,
    ];
}
