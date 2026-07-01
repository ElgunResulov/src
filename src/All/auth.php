<?php
if (!function_exists('app_start_secure_session')) {
    function app_is_https_request(): bool {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    function app_start_secure_session(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => app_is_https_request(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }

    function app_hash_password(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    function app_password_matches(string $password, string $storedPassword): bool {
        $info = password_get_info($storedPassword);
        if (($info['algo'] ?? 0) !== 0) {
            return password_verify($password, $storedPassword);
        }

        return hash_equals($storedPassword, $password);
    }

    function app_password_should_rehash(string $storedPassword): bool {
        $info = password_get_info($storedPassword);
        return (($info['algo'] ?? 0) === 0) || password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
    }

    function app_session_token(string $sessionId): string {
        return sha1($sessionId);
    }

    function app_csrf_token(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            app_start_secure_session();
        }

        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    function app_csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(app_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    function app_request_csrf_token(): string {
        if (isset($_POST['csrf_token'])) {
            return (string) $_POST['csrf_token'];
        }

        if (isset($_POST['_token'])) {
            return (string) $_POST['_token'];
        }

        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($headerToken !== '') {
            return (string) $headerToken;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $rawBody = file_get_contents('php://input');
            $json = json_decode($rawBody, true);
            if (is_array($json)) {
                return (string) ($json['csrf_token'] ?? $json['_token'] ?? '');
            }
        }

        return '';
    }

    function app_validate_csrf_token(string $token): bool {
        return $token !== '' && hash_equals(app_csrf_token(), $token);
    }

    function app_csrf_failure(): void {
        http_response_code(419);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        if (stripos($accept, 'application/json') !== false || strtolower($requestedWith) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        } else {
            echo 'Invalid CSRF token.';
        }
        exit;
    }

    function app_require_csrf(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (empty($_SESSION['user_id'])) {
            return;
        }

        if (!app_validate_csrf_token(app_request_csrf_token())) {
            app_csrf_failure();
        }
    }

    function app_start_csrf_form_injection(): void {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return;
        }

        if (defined('APP_CSRF_FORM_INJECTION_STARTED')) {
            return;
        }
        define('APP_CSRF_FORM_INJECTION_STARTED', true);

        ob_start(function ($buffer) {
            if (stripos($buffer, '<form') === false || stripos($buffer, 'method=') === false) {
                return $buffer;
            }

            $tokenField = app_csrf_field();
            return preg_replace_callback('/<form\b([^>]*)>/i', function ($matches) use ($tokenField) {
                $attributes = $matches[1];
                if (stripos($attributes, 'data-no-csrf') !== false) {
                    return $matches[0];
                }

                if (!preg_match('/method\s*=\s*([\'"]?)post\1/i', $attributes)) {
                    return $matches[0];
                }

                return $matches[0] . $tokenField;
            }, $buffer);
        });
    }

    function app_clear_auth_session(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Strict',
            ]);
        }

        session_destroy();
    }

    function app_lookup_user_session(mysqli $conn, int $userId, string $sessionId): ?array {
        $stmt = $conn->prepare(
            "SELECT us.user_id, us.session_id, u.username, u.role, u.company_id, u.u_id
             FROM user_sessions us
             INNER JOIN users u ON u.id = us.user_id
             WHERE us.user_id = ?
               AND us.session_id = ?
               AND us.expires_at > NOW()
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('is', $userId, $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc() ?: null;
        $stmt->close();

        return $session;
    }

    function app_validate_current_session(mysqli $conn): bool {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
            return false;
        }

        $userId = (int) $_SESSION['user_id'];
        $hashedSessionId = app_session_token(session_id());
        $session = app_lookup_user_session($conn, $userId, $hashedSessionId);
        $storedSessionId = $hashedSessionId;

        if (!$session) {
            $rawSessionId = session_id();
            if ($rawSessionId !== $hashedSessionId) {
                $session = app_lookup_user_session($conn, $userId, $rawSessionId);
                if ($session) {
                    $migrate = $conn->prepare(
                        "UPDATE user_sessions SET session_id = ? WHERE user_id = ? AND session_id = ?"
                    );
                    if ($migrate) {
                        $migrate->bind_param('sis', $hashedSessionId, $userId, $rawSessionId);
                        $migrate->execute();
                        $migrate->close();
                        $storedSessionId = $hashedSessionId;
                    }
                }
            }
        }

        if (!$session) {
            app_clear_auth_session();
            return false;
        }

        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $session['username'];
        $_SESSION['role'] = $session['role'];
        $_SESSION['company_id'] = $session['company_id'];
        $_SESSION['u_id'] = $session['u_id'];
        $_SESSION['last_activity'] = time();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);

        $refresh = $conn->prepare(
            "UPDATE user_sessions
             SET expires_at = ?, ip_address = ?, user_agent = ?
             WHERE user_id = ? AND session_id = ?"
        );
        if ($refresh) {
            $refresh->bind_param('sssis', $expiresAt, $ip, $agent, $userId, $storedSessionId);
            $refresh->execute();
            $refresh->close();
        }

        return true;
    }

    function app_require_auth_api(mysqli $conn): void {
        if (app_validate_current_session($conn)) {
            return;
        }

        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'İstifadəçi daxil olmayıb']);
        exit;
    }

    function app_require_auth(mysqli $conn, string $redirect = 'Login.php'): void {
        if (app_validate_current_session($conn)) {
            return;
        }

        if (!headers_sent()) {
            header('Location: ' . $redirect);
        } else {
            echo '<script>window.location.href = ' . json_encode($redirect) . ';</script>';
        }
        exit;
    }

    function app_base_path(): string {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $appDir = str_replace('\\', '/', __DIR__);

        if ($docRoot !== '' && strpos($appDir, $docRoot) === 0) {
            $base = substr($appDir, strlen($docRoot));
        } else {
            $base = '/TIS/src/All';
        }

        return $base;
    }

    function app_require_role(array $roles, string $redirect = 'index.php'): void {
        $role = $_SESSION['role'] ?? '';
        if (in_array($role, $roles, true)) {
            return;
        }

        if (!headers_sent()) {
            header('Location: ' . $redirect);
        } else {
            echo '<script>window.location.href = ' . json_encode($redirect) . ';</script>';
        }
        exit;
    }
}
?>
