<?php
require_once __DIR__ . '/auth.php';
app_start_secure_session();

if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

$_SESSION = array();

include('db.php');

$ip = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

function getDeviceModel($userAgent) {
    if (preg_match('/iPhone/', $userAgent)) return 'iPhone';
    if (preg_match('/iPad/', $userAgent)) return 'iPad';
    if (preg_match('/Android.*Mobile/', $userAgent)) return 'Android Phone';
    if (preg_match('/Android/', $userAgent)) return 'Android Tablet';
    if (preg_match('/Windows NT/', $userAgent)) return 'Windows PC';
    if (preg_match('/Macintosh/', $userAgent)) return 'Mac';
    if (preg_match('/Linux/', $userAgent)) return 'Linux';
    return 'Unknown Device';
}

function isMobile() {
    return preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

$device_model = getDeviceModel($agent);
$device_hash = sha1($agent . $ip);
$login_error = '';
$latest_username = $_COOKIE['latest_username'] ?? '';
$appBasePath = app_base_path();
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$loginFormAction = ($requestPath === '/' || $requestPath === '/index.php') ? '/' : $_SERVER['PHP_SELF'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $login_error = "İstifadəçi adı və şifrə daxil edin.";
    } else {
        try {
            // Check user by username instead of u_id
            $stmt = $conn->prepare("SELECT id, username, password, role, company_id, u_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (app_password_matches($password, $user['password'])) {
                    $user_id = $user['id'];
                    
                    // Check devices
                    $check = $conn->prepare("SELECT device_hash, ip_address FROM user_devices WHERE user_id = ? AND user_agent = ?");
                    $check->bind_param("is", $user_id, $agent);
                    $check->execute();
                    $check_result = $check->get_result();
                    $check_data = $check_result->fetch_assoc();
                    $exists = $check_data !== null;
                    $check->close();
                    
                    if ($exists) {
                        if ($check_data['ip_address'] !== $ip) {
                            $update = $conn->prepare("UPDATE user_devices SET ip_address = ?, device_hash = ? WHERE user_id = ? AND user_agent = ?");
                            $new_device_hash = sha1($agent . $ip);
                            $update->bind_param("ssis", $ip, $new_device_hash, $user_id, $agent);
                            $update->execute();
                            $update->close();
                        }
                    } else {
                        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_devices WHERE user_id = ?");
                        $count_stmt->bind_param("i", $user_id);
                        $count_stmt->execute();
                        $count_result = $count_stmt->get_result();
                        $device_count = $count_result->fetch_assoc()['count'];
                        $count_stmt->close();
                        
                        if ($device_count >= 1) {
                            $login_error = "Yalnız 1 cihazdan girişə icazə verilir. Yeni cihazdan daxil ola bilməzsiniz.";
                        } else {
                            $insert = $conn->prepare("INSERT INTO user_devices (user_id, device_hash, ip_address, user_agent, device_model) VALUES (?, ?, ?, ?, ?)");
                            $insert->bind_param("issss", $user_id, $device_hash, $ip, $agent, $device_model);
                            $insert->execute();
                            $insert->close();
                        }
                    }
                    
                    if (empty($login_error)) {
                        session_regenerate_id(true);

                        if (app_password_should_rehash($user['password'])) {
                            $password_hash = app_hash_password($password);
                            $hash_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $hash_stmt->bind_param("si", $password_hash, $user_id);
                            $hash_stmt->execute();
                            $hash_stmt->close();
                        }

                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['company_id'] = $user['company_id'];
                        $_SESSION['u_id'] = $user['u_id'];
                        $_SESSION['login_time'] = time();
                        
                        // Clean old sessions
                        $clean_stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                        $clean_stmt->bind_param("i", $user_id);
                        $clean_stmt->execute();
                        $clean_stmt->close();
                        
                        $session_id = app_session_token(session_id());
                        $expires_at = date('Y-m-d H:i:s', time() + 86400);
                        $session_stmt = $conn->prepare("INSERT INTO user_sessions (user_id, username, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $session_stmt->bind_param("isssss", $user_id, $user['username'], $session_id, $ip, $agent, $expires_at);
                        $session_stmt->execute();
                        $session_stmt->close();
                        
                        // Set cookie for remembering username (not u_id)
                        if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
                            setcookie("latest_username", $username, [
                                'expires' => time() + (30 * 24 * 60 * 60),
                                'path' => '/',
                                'secure' => isset($_SERVER['HTTPS']),
                                'httponly' => true,
                                'samesite' => 'Strict'
                            ]);
                        } else {
                            setcookie("latest_username", "", time() - 3600, "/");
                        }
                        
                        error_log("LOGIN SUCCESS: User " . $user['username'] . " (u_id: " . $user['u_id'] . ") logged in successfully");
                        
                        header('Location: ' . $appBasePath . '/Home.php');
                        exit;
                    }
                } else {
                    $login_error = "İstifadəçi adı və ya şifrə yanlışdır.";
                }
            } else {
                $login_error = "İstifadəçi adı və ya şifrə yanlışdır.";
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $login_error = "Giriş zamanı xəta baş verdi. Yenidən cəhd edin.";
        }
    }
}

$conn->close();
?>

<!doctype html>
<html lang="az">
<head>
    <title>Login</title>
    <meta charset="utf-8">
    <base href="<?php echo htmlspecialchars($appBasePath . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="shortcut icon" href="images/bg-2.jpeg" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css's/style.css">
</head>
<body>
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-10">
                    <div class="wrap d-md-flex">
                        <div class="img" style="background-image: url(images/bg-2.jpeg);"></div>
                        <div class="login-wrap p-4 p-md-5">
                            <h3 class="mb-4">Daxil olun</h3>
                            <?php if (!empty($login_error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo htmlspecialchars($loginFormAction); ?>" class="signin-form">
                                <div class="form-group mb-3">
                                    <label class="label">İstifadəçi adı</label>
                                    <input type="text" name="username" class="form-control" required 
                                           value="<?php echo htmlspecialchars($latest_username); ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="label">Şifrə</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="login" class="form-control btn btn-dark rounded px-3" style="background-color:#204c65;color: white;">Daxil Ol</button>
                                </div>
                                <div class="form-group d-md-flex">
                                    <div class="w-50 text-left">
                                        <label class="checkbox-wrap checkbox-dark mb-0">Yadda saxla
                                            <input type="checkbox" name="remember" <?php echo $latest_username ? 'checked' : ''; ?>>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <div hidden class="w-50 text-md-right">
                                        <a href="restore.php">Şifrəmi unutdum</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>