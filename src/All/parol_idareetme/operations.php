<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../db.php');
require_once __DIR__ . '/../user_credentials_helper.php';

app_require_auth($conn);
app_require_role(['super_admin', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));
$role = $_SESSION['role'] ?? '';
$companyId = (int) ($_SESSION['company_id'] ?? 0);
$labels = app_user_role_labels();

try {
    switch ($action) {
        case 'list':
            $users = app_fetch_manageable_users($conn, $role, $companyId);
            $rows = array_map(static function (array $user) use ($labels) {
                return [
                    'id' => (int) $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'role_label' => $labels[$user['role']] ?? $user['role'],
                    'u_id' => $user['u_id'],
                    'password' => $user['visible_password'],
                    'has_plain' => ($user['visible_password'] !== 'Mövcud deyil' && $user['visible_password'] !== '—'),
                    'created_at' => $user['created_at'],
                ];
            }, $users);

            echo json_encode(['success' => true, 'users' => $rows], JSON_UNESCAPED_UNICODE);
            break;

        case 'generate':
            $length = (int) ($_POST['length'] ?? 8);
            $password = app_generate_random_password($length);
            echo json_encode(['success' => true, 'password' => $password], JSON_UNESCAPED_UNICODE);
            break;

        case 'reset':
            $userId = (int) ($_POST['user_id'] ?? 0);
            $customPassword = trim((string) ($_POST['password'] ?? ''));
            $length = (int) ($_POST['length'] ?? 8);

            if ($userId <= 0) {
                throw new Exception('İstifadəçi seçilməyib.');
            }

            $checkSql = $role === 'super_admin'
                ? 'SELECT id, username FROM users WHERE id = ? LIMIT 1'
                : 'SELECT id, username FROM users WHERE id = ? AND company_id = ? LIMIT 1';
            $stmt = mysqli_prepare($conn, $checkSql);
            if ($role === 'super_admin') {
                mysqli_stmt_bind_param($stmt, 'i', $userId);
            } else {
                mysqli_stmt_bind_param($stmt, 'ii', $userId, $companyId);
            }
            mysqli_stmt_execute($stmt);
            $userResult = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($userResult);
            mysqli_stmt_close($stmt);

            if (!$user) {
                throw new Exception('İstifadəçi tapılmadı və ya icazəniz yoxdur.');
            }

            $newPassword = $customPassword !== '' ? $customPassword : app_generate_random_password($length);
            if (strlen($newPassword) < 6) {
                throw new Exception('Şifrə ən azı 6 simvol olmalıdır.');
            }

            if (!app_update_user_password($conn, $userId, $newPassword)) {
                throw new Exception('Şifrə yenilənə bilmədi.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Şifrə uğurla yeniləndi.',
                'user_id' => $userId,
                'username' => $user['username'],
                'password' => $newPassword,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'reset_missing':
            $users = app_fetch_manageable_users($conn, $role, $companyId);
            $updated = [];

            foreach ($users as $user) {
                if ($user['visible_password'] !== 'Mövcud deyil') {
                    continue;
                }

                $newPassword = app_generate_random_password(8);
                if (app_update_user_password($conn, (int) $user['id'], $newPassword)) {
                    $updated[] = [
                        'id' => (int) $user['id'],
                        'username' => $user['username'],
                        'password' => $newPassword,
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'message' => count($updated) . ' istifadəçi üçün yeni şifrə yaradıldı.',
                'updated' => $updated,
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception('Naməlum əməliyyat.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
