<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['u_id']) || empty($_SESSION['u_id'])) {
    echo "<script>alert('Session expired or invalid user. Redirecting to login...'); window.location.href = 'Login.php';</script>";
    exit;
}

include('db.php');
include('navbar_sidebar.php');
$username = "N/A";
$firstname = "N/A";
$lastname = "N/A";
$ata = "N/A";
$poct = "N/A";
$cins = "N/A";
$dogum_tarixi = "N/A";
$vetandasliq = "N/A";
$muellim_adi = "N/A";
$ixtisas_adi = "N/A";
$filial_adi = "N/A"; // Initialize filial_adi
$is_muellim = false; // Flag to determine if user is a teacher
$muellim_count = 0; // Initialize teacher count
$students = []; // Array to store students for teachers

try {
    $session_u_id = $_SESSION['u_id'];
    error_log("Session u_id: " . $session_u_id);

    $muellim_query = "SELECT username, tehsil_ve_ixtisas, email, filial_adi FROM muellimler_new WHERE u_id = ?";
    $muellim_stmt = $conn->prepare($muellim_query);
    if (!$muellim_stmt) {
        throw new Exception("Prepare failed for muellimler_new: " . $conn->error);
    }
    $muellim_stmt->bind_param("s", $session_u_id);
    $muellim_stmt->execute();
    $muellim_result = $muellim_stmt->get_result();

    if ($muellim_result->num_rows > 0) {
        $is_muellim = true;
        $muellim_row = $muellim_result->fetch_assoc();
        $filial_adi_raw = $muellim_row['filial_adi'] ?? 'N/A';
        if (!empty($filial_adi_raw)) {
            $filial_adi_clean = trim($filial_adi_raw, '[]');
            $filial_adi_array = array_filter(array_map('trim', explode(',', str_replace('"', '', $filial_adi_clean))));
            $filial_adi = htmlspecialchars(implode(', ', $filial_adi_array), ENT_QUOTES, 'UTF-8');
        } else {
            $filial_adi = "N/A";
        }
        $username = htmlspecialchars($muellim_row['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $ixtisas_adi = htmlspecialchars($muellim_row['tehsil_ve_ixtisas'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $muellim_adi = $username; 
        $poct = htmlspecialchars($muellim_row['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');

        $student_query = "SELECT username, ata, poct, cins, dogum_tarixi, vetandasliq, ixtisas_adi 
                         FROM telebeler 
                         WHERE muellim_adi LIKE ?";
        $like_username = '%"' . $username . '"%';
        $student_stmt = $conn->prepare($student_query);
        if (!$student_stmt) {
            throw new Exception("Prepare failed for telebeler: " . $conn->error);
        }
        $student_stmt->bind_param("s", $like_username);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();

        while ($student_row = $student_result->fetch_assoc()) {
            $students[] = [
                'username' => htmlspecialchars($student_row['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                'ata' => htmlspecialchars($student_row['ata'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                'poct' => htmlspecialchars($student_row['poct'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                'cins' => ($student_row['cins'] == 0) ? "Kişi" : ($student_row['cins'] == 1 ? "Qadın" : "N/A"),
                'dogum_tarixi' => ($student_row['dogum_tarixi'] && $student_row['dogum_tarixi'] !== '0000-00-00')
                    ? htmlspecialchars(date('d.m.Y', strtotime($student_row['dogum_tarixi'])), ENT_QUOTES, 'UTF-8')
                    : "N/A",
                'vetandasliq' => htmlspecialchars($student_row['vetandasliq'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                'ixtisas_adi' => htmlspecialchars($student_row['ixtisas_adi'] ?? 'N/A', ENT_QUOTES, 'UTF-8')
            ];
        }
        $muellim_count = count($students); 
        $student_stmt->close();
    } else {
        $telebe_query = "SELECT username, ata, reg_ata_adi, poct, cins, reg_email, reg_dogum_tarixi, dogum_tarixi, vetandasliq, muellim_adi, ixtisas_adi FROM telebeler WHERE u_id = ?";
        $telebe_stmt = $conn->prepare($telebe_query);
        if (!$telebe_stmt) {
            throw new Exception("Prepare failed for telebeler: " . $conn->error);
        }
        $telebe_stmt->bind_param("s", $session_u_id);
        $telebe_stmt->execute();
        $telebe_result = $telebe_stmt->get_result();
        if ($telebe_result->num_rows > 0) {
            $telebe_row = $telebe_result->fetch_assoc();
            $username = htmlspecialchars($telebe_row['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $ata = htmlspecialchars($telebe_row['reg_ata_adi'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $poct = htmlspecialchars($telebe_row['reg_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $vetandasliq = htmlspecialchars($telebe_row['vetandasliq'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $ixtisas_adi = htmlspecialchars($telebe_row['ixtisas_adi'] ?? 'UnN/AkN/Anown', ENT_QUOTES, 'UTF-8');
            $muellim_adi_raw = $telebe_row['muellim_adi'] ?? null;
            if (!empty($muellim_adi_raw)) {
                $muellim_adi_clean = trim($muellim_adi_raw, '[]');
                $muellim_adi_array = array_filter(array_map('trim', explode(',', str_replace('"', '', $muellim_adi_clean))));
                $muellim_count = count($muellim_adi_array);
                $muellim_adi = htmlspecialchars(implode(', ', $muellim_adi_array), ENT_QUOTES, 'UTF-8');
            } else {
                $muellim_adi = "N/A";
                $muellim_count = 0;
            }

            $cins_value = $telebe_row['cins'] ?? null;
            $cins = ($cins_value == 0) ? "Kişi" : ($cins_value == 1 ? "Qadın" : "N/A");
            $cins = htmlspecialchars($cins, ENT_QUOTES, 'UTF-8');
            $dogum_tarixi_raw = $telebe_row['reg_dogum_tarixi'] ?? null;
            $dogum_tarixi = ($dogum_tarixi_raw && $dogum_tarixi_raw !== '0000-00-00')
                ? htmlspecialchars(date('d.m.Y', strtotime($dogum_tarixi_raw)), ENT_QUOTES, 'UTF-8')
                : "N/A";
        } else {
            error_log("No matching user data found for u_id: " . $session_u_id);
            echo "<script>alert('No matching user data found in telebeler or muellimler_new tables. u_id: " . htmlspecialchars($session_u_id, ENT_QUOTES, 'UTF-8') . "');</script>";
            $telebe_stmt->close();
            $muellim_stmt->close();
            exit;
        }
        $telebe_stmt->close();
    }
    $muellim_stmt->close();
    $name_parts = explode('.', $username);
    if (count($name_parts) === 2) {
        $firstname = htmlspecialchars($name_parts[0], ENT_QUOTES, 'UTF-8');
        $lastname = htmlspecialchars($name_parts[1], ENT_QUOTES, 'UTF-8');
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    echo "<script>alert('Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "');</script>";
} finally {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet">
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .main-content { margin-left: 0; margin-top: 90px; padding: 14px; flex: 1; display: flex; flex-direction: column; max-width: 100%; margin-left: auto; margin-right: auto; background-color: #f8fafc; border-radius: 12px; min-height: calc(100vh - 90px);}
        .main-content.open { margin-left: 260px;}
        .lds-ripple { display: inline-block; position: relative; width: 80px; height: 80px;}
        .lds-ripple div { position: absolute; border: 4px solid #3182ce; opacity: 1; border-radius: 50%; animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;}
        .lds-ripple div:nth-child(2) { animation-delay: -0.5s;}
        @keyframes lds-ripple { 0% { top: 36px; left: 36px; width: 0; height: 0; opacity: 1;} 100% { top: 0; left: 0; width: 72px; height: 72px; opacity: 0;}}
        .user-info-section { background-color: #fff; margin: 0px auto; padding: 0; margin-left: 14px; width: 96%; max-width: 95%; border-radius: 12px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.09); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; overflow: hidden;}
        .section-header { color: white; padding: 15px 20px; border-radius: 12px 12px 0 0;}
        .section-header h3 { margin: 0; color: #000; font-size: 1.5rem; font-weight: 500;}
        .section-body { padding: 20px;}
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px;}
        .info-group { margin-bottom: 15px; display: flex; flex-direction: column; transition: transform 0.2s ease;}
        .info-group label { font-weight: 600; margin-bottom: 5px; color: #2d3748; font-size: 0.9rem;}
        .info-group span, .info-group input { padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; background-color: #f7fafc; font-size: 0.95rem; color: #4a5568; margin: 5px 0px 0px 0px;}
        .info-group input { background-color: #fff; border-color: #cbd5e0;}
        .password-container { position: relative;}
        .password-container span { padding-right: 40px;}
        .toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #718096; font-size: 1rem;}
        .toggle-password:hover { color: #2d3748;}
        @media (max-width: 768px) { 
        .user-info-section { width: 95%; margin: 15px auto;}
        .info-grid { grid-template-columns: 1fr;}
        .section-header h3 { font-size: 1.3rem;}
        .info-group label, .info-group span, .info-group input { font-size: 0.85rem;}
        .section-body { padding: 15px;}
        }
        @media (max-width: 480px) { 
        .user-info-section { width: 98%; margin: 10px auto; margin-bottom: 60px;}
        .section-body { padding: 10px;}
        .info-group { margin-bottom: 10px;}
        .info-group label, .info-group span, .info-group input { font-size: 0.8rem;}
        .section-header { padding: 12px 15px;}
        .section-header h3 { font-size: 1.1rem;}
        }
        @media (min-width: 1024px) { 
        .main-content { margin-left: 260px;}
        }
        @media (max-width: 767px) { 
        .main-content { margin-left: 0; margin-top: 80px; padding: 15px;}
        .main-content.open { margin-left: 0;}
        }
    </style>
</head>
<body>
    <div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>
    <div class="main-content">
        <div class="user-info-section">
            <div class="section-body">
                <div class="info-grid">
                    <?php if (!$is_muellim): ?>
                        <div class="info-group">
                            <label for="muellim">Müəllim</label>
                            <span><?php echo htmlspecialchars($muellim_adi); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($is_muellim): ?>
                        <div class="info-group">
                            <label for="telebeler">Tələbələr</label>
                            <span>
                                <button style="font-family:Arial;font-weight:bold;" class="btn btn-submit" data-toggle="modal" data-target="#studentModal">Ətraflı</button>
                                <button style="font-family:Arial;font-weight:bold;" class="btn btn-submit"><?php echo $muellim_count; ?></button>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ($is_muellim): ?>
                        <div class="info-group">
                            <label for="filial">Filial</label>
                            <span><?php echo htmlspecialchars($filial_adi); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="info-group">
                        <label for="ixtisas">İxtisas</label>
                        <span><?php echo htmlspecialchars($ixtisas_adi); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <br>
        <div class="user-info-section">
            <div class="section-header">
                <h3>İstifadəçi Məlumatları</h3>
            </div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-group">
                        <label for="first_name">Adı</label>
                        <span><?php echo htmlspecialchars($firstname); ?></span>
                    </div>
                    <div class="info-group">
                        <label for="last_name">Soyadı</label>
                        <span><?php echo htmlspecialchars($lastname); ?></span>
                    </div>
                    <?php if (!$is_muellim): ?>
                        <div class="info-group">
                            <label for="father_name">Ata adı</label>
                            <span><?php echo htmlspecialchars($ata); ?></span>
                        </div>
                        <div class="info-group">
                            <label for="birth_date">Doğum tarixi</label>
                            <span><?php echo htmlspecialchars($dogum_tarixi); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="info-group">
                        <label for="personal_email">E-poçt</label>
                        <span><?php echo htmlspecialchars($poct); ?></span>
                    </div>
                    <div hidden class="info-group">
                        <label for="corporate_password">Korporativ E-poçt şifrəsi</label>
                        <div class="password-container">
                            <span id="corporate_password" data-password="H$393193788326ar">••••••••••••••</span>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                        </div>
                    </div>
                    <div hidden class="info-group">
                        <label for="new_password">Yeni şifrə</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Yeni şifrə daxil edin">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($is_muellim): ?>
    <div class="modal fade" id="studentModal" tabindex="-1" role="dialog" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">Tələbələr</h5>
                </div>
                <div class="modal-body">
                    <?php if (empty($students)): ?>
                        <p>Heç bir tələbə tapılmadı.</p>
                    <?php else: ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Adı Soyadı</th>
                                    <th>E-poçt</th>
                                    <th>Cins</th>
                                    <th>İxtisas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['username']; ?></td>
                                        <td><?php echo $student['poct']; ?></td>
                                        <td><?php echo $student['cins']; ?></td>
                                        <td><?php echo $student['ixtisas_adi']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script>
        feather.replace();
        function togglePassword() {
            const passwordSpan = document.getElementById('corporate_password');
            const toggleIcon = document.querySelector('.toggle-password');
            const isHidden = passwordSpan.textContent === '••••••••••••••';
            passwordSpan.textContent = isHidden ? passwordSpan.dataset.password : '••••••••••••••';
            toggleIcon.classList.toggle('fa-eye');
            toggleIcon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html>