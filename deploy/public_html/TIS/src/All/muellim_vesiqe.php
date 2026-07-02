<?php
include('db.php');
$conn->set_charset("utf8");

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Check role
$sql = "SELECT role, u_id FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$should_display = false;
$u_id = null;
$user_role = null;

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_role = $user['role'];
    if (in_array($user_role, ['super_admin', 'admin'])) {
        $should_display = true;
    }
    $u_id = $user['u_id'];
}
$stmt->close();

// JSON endpoint for teacher role (get own data)
if ($user_role === 'teacher' && isset($_GET['action']) && $_GET['action'] == 'get_own_teacher') {
    $sql = "SELECT id, u_id, username, tehsil_ve_ixtisas fenn, email, tecrube, profile, qr_code FROM muellimler_new WHERE u_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($teacher);
    } else {
        echo json_encode(null);
    }
    $stmt->close();
    exit;
}

// JSON endpoint for super_admin/admin (get teacher by username)
if ($should_display && isset($_GET['action']) && $_GET['action'] == 'get_teacher_by_username' && isset($_GET['username'])) {
    $username = $_GET['username'];
    $sql = "SELECT id, u_id, username, tehsil_ve_ixtisas, fenn, email, tecrube, profile, qr_code FROM muellimler_new WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($teacher);
    } else {
        echo json_encode(null);
    }
    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müəllim Vəsiqəsi - TIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            transform:scale(0.92);
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .controls {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
            align-items: center;
        }

        .teacher-select-wrapper {
            <?php echo $should_display ? '' : 'display: none;'; ?>
        }

        .select-container {
            position: relative;
            width: 100%;
            min-width: 300px;
        }

        .teacher-select {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 50px;
            outline: none;
            appearance: none;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .teacher-select:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .teacher-select:focus {
            background: white;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }

        .select-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #666;
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .btn-print {
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
        }

        .btn-print:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .card-wrapper {
            perspective: 1000px;
            display: flex;
            justify-content: center;
        }

        .id-card {
            width: 100%;
            max-width: 800px;
            height: 400px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .id-card:hover {
            transform: rotateY(5deg) rotateX(5deg);
        }

        .card-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .card-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%23667eea" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        }

        .card-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            color: white;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(10px);
        }

        .org-info h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .org-info p {
            font-size: 12px;
            opacity: 0.8;
            font-weight: 300;
        }

        .card-id {
            font-size: 12px;
            font-weight: 600;
            opacity: 0.8;
            font-family: 'Courier New', monospace;
        }

        .card-content {
            position: absolute;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 40px;
            display: grid;
            grid-template-columns: 200px 1fr 120px;
            gap: 40px;
            align-items: start;
        }

        .photo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-container {
            width: 200px;
            height: 240px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 3px solid white;
        }

        .photo-container.has-image {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .photo-placeholder {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            font-weight: bold;
        }

        .photo-placeholder i {
            font-size: 45px;
            margin-bottom: 10px;
            display: block;
        }

        .info-section {
            margin-top: -25px;
        }

        .teacher-name {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 3px;
            line-height: 1.2;
        }

        .teacher-email {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: 400;
        }

        .details-grid {
            display: grid;
            gap: 20px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .detail-row:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-left: 40%;
            padding-top: 155px;
        }

        .qr-container {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border: 2px solid white;
        }

        .qr-container.has-image {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .qr-placeholder {
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 500;
        }

        .qr-placeholder i {
            font-size: 24px;
            margin-bottom: 5px;
            display: block;
        }

        .qr-label {
            font-size: 10px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .editable {
            border: none;
            background: transparent;
            font-family: inherit;
            color: inherit;
            font-size: inherit;
            font-weight: inherit;
            width: 100%;
            outline: none;
        }

        .status-message {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .loading {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .loading::before {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }

        /* Print Styles */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            body::before {
                display: none !important;
            }

            .header,
            .controls {
                display: none !important;
            }

            .container {
                max-width: none !important;
            }

            .id-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                transform: none !important;
                page-break-inside: avoid;
                margin: 0 auto;
                max-width: 800px;
                height: 400px;
            }

            .card-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .detail-icon {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .logo {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .id-card {
                height: auto;
                min-height: 600px;
            }

            .card-header {
                height: 100px;
                padding: 0 20px;
            }

            .logo {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .org-info h3 {
                font-size: 16px;
            }

            .card-content {
                top: 100px;
                padding: 20px;
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }

            .photo-container {
                width: 140px;
                height: 180px;
                margin: 0 auto;
            }

            .teacher-name {
                font-size: 24px;
            }

            .qr-section {
                padding-top: 0;
            }

            .button-group {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 200px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .card-content {
                padding: 15px;
                gap: 20px;
            }

            .teacher-name {
                font-size: 20px;
            }

            .detail-row {
                padding: 12px 15px;
            }

            .detail-icon {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }

            .photo-container {
                width: 120px;
                height: 150px;
            }

            .qr-container {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Müəllim Vəsiqəsi</h1>
        </div>

        <div class="controls">
            <div class="teacher-select-wrapper">
                <div class="select-container">
                    <select name="username" id="username-select" class="teacher-select" onchange="loadTeacherData()">
                      <option value="">Müəllimi seçin</option>
                        <?php
                        try {
                            $conn->set_charset("utf8");
                            $sql = "SELECT username FROM muellimler_new ORDER BY username ASC";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['username']) . "'>" . htmlspecialchars($row['username']) . "</option>";
                                }
                            } else {
                                echo "<option value=''>İstifadəçi tapılmadı</option>";
                            }
                        } catch (Exception $e) {
                            echo "<option value=''>Xəta: " . htmlspecialchars($e->getMessage()) . "</option>";
                        } finally {
                            $conn->close();
                        }
                        ?>
                    </select>
                    <span class="select-icon">▼</span>
                </div>
            </div>

            <div class="button-group">
                <a href="qr_muellim.php" class="btn btn-back">
                    ← Geri
                </a>
                <button class="btn btn-print" onclick="printCard()">
                    🖨️ Çap et
                </button>
            </div>
        </div>

        <div class="card-wrapper">
            <div class="id-card" id="idCard">
                <div class="card-bg"></div>
                <div class="card-pattern"></div>
                
                <div class="card-header">
                    <div class="logo-section">
                        <div class="logo">🎓</div>
                        <div class="org-info">
                            <h3>Müəllim Vəsiqəsi</h3>
                        </div>
                    </div>
                    <div class="card-id" id="cardId"></div>
                </div>

                <div class="card-content">
                    <div class="photo-section">
                        <div class="photo-container" id="photoContainer">
                            <div class="photo-placeholder" id="photoPlaceholder">
                                <i>👤</i>
                                Şəkil<br>Yüklənməyib
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="teacher-name">
                            <input type="text" class="editable" id="teacherName" value="Müəllimin Adı" readonly>
                        </div>
                        <div class="teacher-email">
                            <input type="text" class="editable" id="teacherEmail" value="muallim@mektep.edu.az" readonly>
                        </div>

                        <div class="details-grid">
                            <div class="detail-row">
                                <div class="detail-icon">📚</div>
                                <div class="detail-content">
                                    <div class="detail-label">Fənn</div>
                                    <div class="detail-value">
                                        <input type="text" class="editable" id="subject" value="Riyaziyyat" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-icon">⏱️</div>
                                <div class="detail-content">
                                    <div class="detail-label">İş Təcrübəsi</div>
                                    <div class="detail-value">
                                        <input type="text" class="editable" id="experience" value="8 il" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="qr-section">
                        <div class="qr-container" id="qrContainer">
                            <div class="qr-placeholder" id="qrPlaceholder">
                                <i>📱</i>
                                QR Kod
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="loadingMessage" class="status-message loading" style="display: none;">
            Məlumatlar yüklənir...
        </div>
        <div id="errorMessage" class="status-message error" style="display: none;"></div>
    </div>

    <script>
        const userRole = <?php echo json_encode($user_role, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            if (userRole === 'teacher') {
                loadTeacherData();
            }
            
            // Add entrance animation
            setTimeout(() => {
                document.querySelector('.id-card').style.opacity = '1';
                document.querySelector('.id-card').style.transform = 'translateY(0)';
            }, 300);
        });

        function loadTeacherData() {
            showLoading(true);
            hideMessages();

            const select = document.getElementById('username-select');
            const username = select ? select.value : '';

            let url = '';
            if (userRole === 'teacher') {
                url = '?action=get_own_teacher';
            } else if (['super_admin', 'admin'].includes(userRole) && username) {
                url = `?action=get_teacher_by_username&username=${encodeURIComponent(username)}`;
            } else {
                showLoading(false);
                if (['super_admin', 'admin'].includes(userRole)) {
                    showError("Müəllim seçin.");
                } else {
                    showError("Giriş icazəniz yoxdur.");
                }
                return;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data) {
                        populateTeacherData(data);
                    } else {
                        showError("Müəllim məlumatları tapılmadı.");
                    }
                    showLoading(false);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError("Xəta: " + error.message);
                    showLoading(false);
                });
        }

        function populateTeacherData(data) {
            document.getElementById('teacherName').value = data.username || "Müəllimin Adı və Soyadı";
            document.getElementById('subject').value = data.tehsil_ve_ixtisas || "Riyaziyyat";
            document.getElementById('experience').value = data.tecrube ? `${data.tecrube} il` : "8 il";
            document.getElementById('teacherEmail').value = data.email || "muallim@mektep.edu.az";
            document.getElementById('cardId').textContent = "";

            loadProfileImage(data.profile);
            loadQrCode(data.qr_code);
        }

        function loadProfileImage(profileImage) {
            const container = document.getElementById('photoContainer');
            const placeholder = document.getElementById('photoPlaceholder');
            
            if (profileImage && profileImage.trim() !== '') {
                const possiblePaths = [
                    `../Uploads/profiles/${profileImage}`,
                    `./Uploads/profiles/${profileImage}`,
                    `Uploads/profiles/${profileImage}`
                ];
                
                tryLoadImage(possiblePaths, 0, container, placeholder, 'photo');
            } else {
                container.classList.remove('has-image');
                container.style.backgroundImage = '';
                placeholder.style.display = 'block';
                placeholder.innerHTML = '<i>👤</i>Şəkil<br>Yüklənməyib';
            }
        }

        function loadQrCode(qrImage) {
            const container = document.getElementById('qrContainer');
            const placeholder = document.getElementById('qrPlaceholder');
            
            if (qrImage && qrImage.trim() !== '') {
                const possiblePaths = [
                    `../Uploads/qrcodes/${qrImage}`,
                    `./Uploads/qrcodes/${qrImage}`,
                    `Uploads/qrcodes/${qrImage}`
                ];
                
                tryLoadImage(possiblePaths, 0, container, placeholder, 'qr');
            } else {
                container.classList.remove('has-image');
                container.style.backgroundImage = '';
                placeholder.style.display = 'block';
                placeholder.innerHTML = '<i>📱</i>QR Kod';
            }
        }

        function tryLoadImage(paths, index, container, placeholder, type) {
            if (index >= paths.length) {
                container.classList.remove('has-image');
                container.style.backgroundImage = '';
                placeholder.style.display = 'block';
                if (type === 'photo') {
                    placeholder.innerHTML = '<i>👤</i>Şəkil<br>Yüklənməyib';
                } else {
                    placeholder.innerHTML = '<i>📱</i>QR Kod';
                }
                return;
            }
            
            const currentPath = paths[index];
            const img = new Image();
            
            img.onload = function() {
                container.style.backgroundImage = `url('${currentPath}')`;
                container.style.backgroundSize = 'cover';
                container.style.backgroundPosition = 'center';
                container.style.backgroundRepeat = 'no-repeat';
                container.classList.add('has-image');
                placeholder.style.display = 'none';
            };
            
            img.onerror = function() {
                tryLoadImage(paths, index + 1, container, placeholder, type);
            };
            
            img.crossOrigin = 'anonymous';
            img.src = currentPath;
        }

        function showLoading(show) {
            document.getElementById('loadingMessage').style.display = show ? 'block' : 'none';
        }

        function showError(message) {
            const errorElement = document.getElementById('errorMessage');
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        function hideMessages() {
            document.getElementById('errorMessage').style.display = 'none';
        }

        function printCard() {
            const printStyles = `
                <style>
                    @media print {
                        body { 
                            background: white !important; 
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        .card-header { 
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                        .detail-icon { 
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                    }
                </style>
            `;
            
            const originalHead = document.head.innerHTML;
            document.head.innerHTML = originalHead + printStyles;
            
            setTimeout(() => {
                window.print();
                document.head.innerHTML = originalHead;
            }, 100);
        }

        // Add initial animation
        document.querySelector('.id-card').style.opacity = '0';
        document.querySelector('.id-card').style.transform = 'translateY(30px)';
        document.querySelector('.id-card').style.transition = 'all 0.6s ease';

        window.addEventListener('beforeprint', function() {
            document.body.style.webkitPrintColorAdjust = 'exact';
            document.body.style.printColorAdjust = 'exact';
            document.body.style.colorAdjust = 'exact';
        });
    </script>
</body>
</html>