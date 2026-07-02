<?php
if (!function_exists('app_apply_security_headers')) {
    function app_current_origin(): ?string {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            return null;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $host;
    }

    function app_allowed_origins(): array {
        $origins = [];
        $currentOrigin = app_current_origin();
        if ($currentOrigin !== null) {
            $origins[] = $currentOrigin;
        }

        $configuredOrigins = getenv('APP_ALLOWED_ORIGINS') ?: '';
        foreach (explode(',', $configuredOrigins) as $origin) {
            $origin = rtrim(trim($origin), '/');
            if ($origin !== '') {
                $origins[] = $origin;
            }
        }

        return array_values(array_unique($origins));
    }

    function app_origin_is_allowed(string $origin): bool {
        $origin = rtrim($origin, '/');
        return in_array($origin, app_allowed_origins(), true);
    }

    function app_request_header_allowed(string $requestedHeaders): bool {
        if ($requestedHeaders === '') {
            return true;
        }

        $allowedHeaders = [
            'accept',
            'authorization',
            'content-type',
            'x-csrf-token',
            'x-requested-with',
        ];

        foreach (explode(',', $requestedHeaders) as $header) {
            if (!in_array(strtolower(trim($header)), $allowedHeaders, true)) {
                return false;
            }
        }

        return true;
    }

    function app_forbid_cross_origin_request(): void {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Cross-origin request blocked.']);
        exit;
    }

    function app_reject_request(int $statusCode, string $message): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }

    function app_normalized_host(string $host): string {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host);
        return trim($host, '[]');
    }

    function app_host_is_allowed(): bool {
        $host = app_normalized_host($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') {
            return false;
        }

        $allowedHosts = ['localhost', '127.0.0.1', '::1'];
        $serverName = app_normalized_host($_SERVER['SERVER_NAME'] ?? '');
        if ($serverName !== '') {
            $allowedHosts[] = $serverName;
        }

        $configuredHosts = getenv('APP_ALLOWED_HOSTS') ?: '';
        foreach (explode(',', $configuredHosts) as $configuredHost) {
            $configuredHost = app_normalized_host($configuredHost);
            if ($configuredHost !== '') {
                $allowedHosts[] = $configuredHost;
            }
        }

        return in_array($host, array_values(array_unique($allowedHosts)), true);
    }

    function app_repeated_urldecode(string $value): string {
        $decoded = $value;
        for ($i = 0; $i < 3; $i++) {
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        return $decoded;
    }

    function app_content_security_policy(): string {
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://stackpath.bootstrapcdn.com",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://stackpath.bootstrapcdn.com https://code.jquery.com",
            "connect-src 'self'",
            "media-src 'self'",
            "worker-src 'self' blob:",
            "upgrade-insecure-requests",
        ];

        return implode('; ', $directives);
    }

    function app_apply_request_bypass_guard(): void {
        if (!app_host_is_allowed()) {
            app_reject_request(400, 'Invalid Host header.');
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $allowedMethods = ['GET', 'POST', 'HEAD', 'DELETE', 'OPTIONS'];
        if (!in_array($method, $allowedMethods, true)) {
            app_reject_request(405, 'HTTP method not allowed.');
        }

        if (in_array($method, ['TRACE', 'TRACK'], true)) {
            app_reject_request(405, 'HTTP method not allowed.');
        }

        $overrideHeaders = [
            'HTTP_X_HTTP_METHOD_OVERRIDE',
            'HTTP_X_METHOD_OVERRIDE',
            'HTTP_X_HTTP_METHOD',
        ];
        foreach ($overrideHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                app_reject_request(400, 'HTTP method override is not allowed.');
            }
        }

        if (isset($_REQUEST['_method']) || isset($_REQUEST['method_override'])) {
            app_reject_request(400, 'HTTP method override is not allowed.');
        }

        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? '';
        $transferEncoding = $_SERVER['HTTP_TRANSFER_ENCODING'] ?? '';
        if ($contentLength !== '' && $transferEncoding !== '') {
            app_reject_request(400, 'Ambiguous request body headers are not allowed.');
        }
        if (strpos($contentLength, ',') !== false) {
            app_reject_request(400, 'Multiple Content-Length values are not allowed.');
        }

        $requestTarget = ($_SERVER['REQUEST_URI'] ?? '') . "\n" . ($_SERVER['QUERY_STRING'] ?? '');
        $decodedTarget = app_repeated_urldecode($requestTarget);
        if (
            strpos($decodedTarget, "\0") !== false ||
            strpos($decodedTarget, '../') !== false ||
            strpos($decodedTarget, '..\\') !== false
        ) {
            app_reject_request(400, 'Suspicious request path blocked.');
        }
    }

    function app_apply_security_headers(): void {
        if (headers_sent()) {
            return;
        }

        app_apply_request_bypass_guard();

        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 0');
        header('Content-Security-Policy: ' . app_content_security_policy());
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Vary: Origin, Access-Control-Request-Method, Access-Control-Request-Headers');

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '') {
            if (!app_origin_is_allowed($origin)) {
                app_forbid_cross_origin_request();
            }

            header('Access-Control-Allow-Origin: ' . rtrim($origin, '/'));
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
            header('Access-Control-Max-Age: 600');

            $requestedHeaders = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '';
            if (!app_request_header_allowed($requestedHeaders)) {
                app_forbid_cross_origin_request();
            }

            if ($requestedHeaders !== '') {
                header('Access-Control-Allow-Headers: ' . $requestedHeaders);
            } else {
                header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
            }
        }

        $fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($fetchSite === 'cross-site' && !in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            app_forbid_cross_origin_request();
        }

        if ($method === 'OPTIONS') {
            http_response_code($origin === '' || app_origin_is_allowed($origin) ? 204 : 403);
            exit;
        }
    }

    app_apply_security_headers();
}
?>
