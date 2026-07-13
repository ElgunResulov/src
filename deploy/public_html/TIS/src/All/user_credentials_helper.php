<?php

function app_ensure_plain_password_column(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'plain_password'");
    if ($result && mysqli_num_rows($result) === 0) {
        mysqli_query(
            $conn,
            "ALTER TABLE users ADD COLUMN plain_password VARCHAR(255) NULL DEFAULT NULL AFTER password"
        );
    }

    $checked = true;
}

function app_generate_random_password(int $length = 8): string
{
    $length = max(6, min(16, $length));
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';

    $password = '';
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];

    $allChars = $uppercase . $lowercase . $numbers;
    while (strlen($password) < $length) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }

    return str_shuffle($password);
}

function app_update_user_password(mysqli $conn, int $userId, string $plainPassword): bool
{
    app_ensure_plain_password_column($conn);

    $hash = app_hash_password($plainPassword);
    $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ?, plain_password = ? WHERE id = ?');
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ssi', $hash, $plainPassword, $userId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function app_user_visible_password(?string $plainPassword, ?string $storedPassword): string
{
    $plain = trim((string) $plainPassword);
    if ($plain !== '') {
        return $plain;
    }

    $stored = (string) $storedPassword;
    if ($stored === '') {
        return '—';
    }

    $info = password_get_info($stored);
    if (($info['algo'] ?? 0) === 0) {
        return $stored;
    }

    return 'Mövcud deyil';
}

function app_user_role_labels(): array
{
    return [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'teacher' => 'Müəllim',
        'student' => 'Tələbə',
        'parent' => 'Valideyn',
        'staff' => 'Əməkdaş',
        'examiner' => 'İmtahan nəzarətçisi',
        'operator' => 'Operator',
    ];
}

function app_fetch_manageable_users(mysqli $conn, string $role, int $companyId = 0): array
{
    app_ensure_plain_password_column($conn);

    if ($role === 'super_admin') {
        $sql = 'SELECT id, username, role, u_id, plain_password, password, company_id, created_at
                FROM users
                ORDER BY created_at DESC';
        $result = mysqli_query($conn, $sql);
    } else {
        $sql = 'SELECT id, username, role, u_id, plain_password, password, company_id, created_at
                FROM users
                WHERE company_id = ?
                ORDER BY created_at DESC';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }

    $users = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['visible_password'] = app_user_visible_password($row['plain_password'] ?? null, $row['password'] ?? null);
            $users[] = $row;
        }
    }

    return $users;
}
