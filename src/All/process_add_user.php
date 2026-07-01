<?php
include('db.php');
app_require_auth($conn);
app_require_role(['super_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Hesablar.php");
    exit();
}

// Function to generate random password in UyVuHZm3 format (7-8 characters)
function generateRandomPassword($length = 8) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    
    $password = '';
    
    // Ensure at least one uppercase, one lowercase, and one number
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $numbers[rand(0, strlen($numbers) - 1)];
    
    // Add one more character if length is 8
    if ($length == 8) {
        $allChars = $uppercase . $lowercase . $numbers;
        $password .= $allChars[rand(0, strlen($allChars) - 1)];
    }
    
    // Shuffle the password to randomize position
    return str_shuffle($password);
}

// Function to show success modal
function showSuccessModal($message, $password) {
    echo "<!DOCTYPE html>
    <html lang='az'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>Uğurlu Əməliyyat</title>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
        <style>
            body { 
             
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                min-height: auto;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .modal-content { 
                transform:scale(0.85);
                border-radius: 25px;
                box-shadow: 0 25px 50px rgba(0,0,0,0.3);
                border: none;
                overflow: hidden;
            }
            .modal-header {
                background: linear-gradient(45deg, #11998e, #38ef7d);
                color: white;
                border: none;
                padding: 25px 30px;
            }
            .modal-body { padding: 40px 30px; }
            .success-icon { animation: bounce 0.6s ease-in-out; }
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            .btn-custom {
                border-radius: 25px;
                padding: 12px 30px;
                font-weight: 600;
                transition: all 0.3s ease;
                border: none;
            }
            .btn-custom:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
        </style>
    </head>
    <body>
        <div class='modal fade show d-block' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered modal-lg'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title'><i class='fas fa-check-circle me-2'></i>Əməliyyat Uğurla Tamamlandı</h5>
                    </div>
                    <div class='modal-body text-center'>
                        <i class='fas fa-check-circle text-success mb-4 success-icon' style='font-size: 5rem;'></i>
                        <h4 class='text-success mb-4'>Mükəmməl!</h4>
                        <p class='mb-4 fs-5 text-muted'>" . htmlspecialchars($message) . "</p>
                        <div class='alert alert-info mt-4 p-4' style='border-radius: 15px; border: 2px solid #17a2b8;'>
                            <h6 class='text-info mb-3'>
                                <i class='fas fa-key me-2'></i>Avtomatik Yaradılan Şifrə
                            </h6>
                            <div class='input-group mb-3'>
                                <input type='text' class='form-control form-control-lg' value='" . htmlspecialchars($password) . "' id='generatedPassword' readonly style='font-family: monospace; font-size: 24px; font-weight: bold; text-align: center; background: #f8f9fa; letter-spacing: 2px;'>
                                <button class='btn btn-outline-info btn-lg' type='button' onclick='copyPassword()' title='Kopyala'>
                                    <i class='fas fa-copy'></i>
                                </button>
                            </div>
                            <div class='text-center'>
                                <small class='text-muted'>
                                    <i class='fas fa-info-circle me-1'></i>
                                    Bu şifrəni kopyalayın və istifadəçiyə təqdim edin.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer justify-content-center border-0 pb-4'>
                        <button type='button' class='btn btn-success btn-custom px-5' onclick='redirectPage()'>
                            <i class='fas fa-arrow-right me-2'></i>Davam Et
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function copyPassword() {
                const passwordField = document.getElementById('generatedPassword');
                passwordField.select();
                passwordField.setSelectionRange(0, 99999);
                
                try {
                    document.execCommand('copy');
                    passwordField.style.background = '#d4edda';
                    
                    const button = event.target.closest('button');
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class=\"fas fa-check\"></i>';
                    button.classList.remove('btn-outline-info');
                    button.classList.add('btn-success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-info');
                        passwordField.style.background = '#f8f9fa';
                    }, 1000);
                    
                } catch (err) {
                    alert('Şifrə kopyalanmadı. Zəhmət olmasa manual kopyalayın.');
                }
            }
            
            function redirectPage() {
                window.location.href = 'Hesablar.php';
            }
            
            setTimeout(redirectPage, 8000);
        </script>
    </body>
    </html>";
    exit();
}

// Function to show error modal
function showErrorModal($message) {
    echo "<!DOCTYPE html>
    <html lang='az'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>Xəta</title>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                min-height: 100vh;
                display: flex;
                transform:scale(0.87);
                align-items: center;
                justify-content: center;
            }
            .modal-content { 
                border-radius: 25px;
                box-shadow: 0 25px 50px rgba(0,0,0,0.3);
                border: none;
                overflow: hidden;
            }
            .modal-header {
                background: linear-gradient(45deg, #fd79a8, #e84393);
                color: white;
                border: none;
                padding: 25px 30px;
            }
            .modal-body { padding: 40px 30px; }
            .error-icon { animation: shake 0.5s ease-in-out; }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        </style>
    </head>
    <body>
        <div class='modal fade show d-block' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title'><i class='fas fa-exclamation-triangle me-2'></i>Xəta Baş Verdi</h5>
                    </div>
                    <div class='modal-body text-center'>
                        <i class='fas fa-times-circle text-danger mb-4 error-icon' style='font-size: 5rem;'></i>
                        <h4 class='text-danger mb-3'>Əməliyyat Uğursuz Oldu</h4>
                        <p class='mb-0 fs-5 text-muted'>" . htmlspecialchars($message) . "</p>
                    </div>
                    <div class='modal-footer justify-content-center border-0 pb-4'>
                        <button type='button' class='btn btn-secondary btn-custom px-5' onclick='redirectPage()'>
                            <i class='fas fa-arrow-left me-2'></i>Geri Qayıt
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function redirectPage() {
                window.location.href = 'Hesablar.php';
            }
            setTimeout(redirectPage, 5000);
        </script>
    </body>
    </html>";
    exit();
}

try {
    // Get and validate form data
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? '';
    $company_id = $_SESSION['company_id'] ?? 0;
    $selected_student = trim($_POST['selected_student'] ?? '');
    $selected_parent = trim($_POST['selected_parent'] ?? '');
    $parent_type = trim($_POST['parent_type'] ?? '');

    // Generate a temporary password; only the hash is stored.
    $password = generateRandomPassword(8);
    $password_hash = app_hash_password($password);

    // Validate required fields
    if (empty($username)) {
        showErrorModal('İstifadəçi adı boş ola bilməz.');
    }

    if (empty($role)) {
        showErrorModal('Səlahiyyət seçilməlidir.');
    }

    // Validate role permissions
    $allowed_roles = ['super_admin', 'admin', 'teacher', 'student', 'staff', 'parent', 'examiner'];
    if (!in_array($role, $allowed_roles)) {
        showErrorModal('Seçilmiş səlahiyyət etibarsızdır.');
    }

    // Check if current user can create this role
    if ($_SESSION['role'] === 'admin' && in_array($role, ['super_admin'])) {
        showErrorModal('Bu səlahiyyəti yaratmaq üçün icazəniz yoxdur.');
    }

    // Check if username already exists
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        showErrorModal('Bu istifadəçi adı artıq mövcuddur. Zəhmət olmasa başqa ad seçin.');
    }
    $stmt_check->close();

    // Generate u_id for specific roles
    $u_id = null;
    if (in_array($role, ['super_admin', 'admin', 'parent', 'examiner'])) {
        do {
            $u_id = rand(100000, 999999);
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE u_id = ?");
            $check_stmt->bind_param("i", $u_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $exists = $check_result->num_rows > 0;
            $check_stmt->close();
        } while ($exists);
    }

    // Handle company_id for admin role
    if ($_SESSION['role'] === 'super_admin' && $role === 'admin') {
        do {
            $company_id = rand(1000, 9999);
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE company_id = ?");
            $check_stmt->bind_param("i", $company_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $exists = $check_result->num_rows > 0;
            $check_stmt->close();
        } while ($exists);
    }

    // Validate parent role specific requirements
    if ($role === 'parent') {
        if (empty($selected_student) || empty($selected_parent) || empty($parent_type)) {
            showErrorModal('Valideyn rolunu seçdiyiniz zaman tələbə və valideyn məlumatları mütləqdir.');
        }

        if (!in_array($parent_type, ['ata', 'ana'])) {
            showErrorModal('Valideyn tipi yalnız "ata" və ya "ana" ola bilər.');
        }

        // Validate that the selected student exists
        $check_student_stmt = $conn->prepare("SELECT username FROM telebeler WHERE username = ?");
        $check_student_stmt->bind_param("s", $selected_student);
        $check_student_stmt->execute();
        $student_result = $check_student_stmt->get_result();
        
        if ($student_result->num_rows === 0) {
            $check_student_stmt->close();
            showErrorModal('Seçilmiş tələbə sistemdə tapılmadı.');
        }
        $check_student_stmt->close();

        // Validate that the parent name matches the student's parent
        $check_parent_stmt = $conn->prepare("SELECT " . $parent_type . " FROM telebeler WHERE username = ?");
        $check_parent_stmt->bind_param("s", $selected_student);
        $check_parent_stmt->execute();
        $parent_result = $check_parent_stmt->get_result();
        
        if ($parent_result->num_rows > 0) {
            $parent_row = $parent_result->fetch_assoc();
            $actual_parent = trim($parent_row[$parent_type]);
            
            if (empty($actual_parent) || $actual_parent !== $selected_parent) {
                $check_parent_stmt->close();
                showErrorModal('Seçilmiş valideyn məlumatları tələbə ilə uyğun gəlmir.');
            }
        } else {
            $check_parent_stmt->close();
            showErrorModal('Tələbə məlumatları tapılmadı.');
        }
        $check_parent_stmt->close();

        // Check if parent already exists
        $check_parent_stmt = $conn->prepare("SELECT id FROM valideyn WHERE telebe_name = ? AND parent_name = ?");
        $check_parent_stmt->bind_param("ss", $selected_student, $selected_parent);
        $check_parent_stmt->execute();
        $check_parent_result = $check_parent_stmt->get_result();
        
        if ($check_parent_result->num_rows > 0) {
            $check_parent_stmt->close();
            showErrorModal('Bu valideyn artıq qeydiyyatdan keçmişdir.');
        }
        $check_parent_stmt->close();
    }

    // Begin transaction
    $conn->autocommit(FALSE);

    try {
        if ($u_id !== null) {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, company_id, u_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssii", $username, $password_hash, $role, $company_id, $u_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, company_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssi", $username, $password_hash, $role, $company_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("User insert failed: " . $stmt->error);
        }

        $user_id = $stmt->insert_id;
        $stmt->close();

        // Insert into valideyn table for parent role
        if ($role === 'parent') {
            $stmt_valideyn = $conn->prepare("INSERT INTO valideyn (u_id, telebe_name, parent_name, parent_type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_valideyn->bind_param("isss", $u_id, $selected_student, $selected_parent, $parent_type);
            
            if (!$stmt_valideyn->execute()) {
                throw new Exception("Parent insert failed: " . $stmt_valideyn->error);
            }
            $stmt_valideyn->close();
        }

        // Commit transaction
        $conn->commit();

        // Log successful user creation
        error_log("User created successfully: Username: $username, Role: $role, Created by: " . $_SESSION['username']);

        // Success message
        $successMessage = '';
        switch ($role) {
            case 'parent':
                $parentTypeText = $parent_type === 'ata' ? 'Ata' : 'Ana';
                $successMessage = "Valideyn istifadəçisi uğurla yaradıldı!\n\nTələbə: {$selected_student}\n{$parentTypeText}: {$selected_parent}\nİstifadəçi adı: {$username}";
                break;
            case 'admin':
                $successMessage = "Admin istifadəçisi '{$username}' uğurla yaradıldı!\nŞirkət ID: {$company_id}";
                break;
            case 'super_admin':
                $successMessage = "Super Admin istifadəçisi '{$username}' uğurla yaradıldı!";
                break;
            default:
                $successMessage = "İstifadəçi '{$username}' ({$role}) uğurla yaradıldı!";
        }

        showSuccessModal($successMessage, $password);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Add user error: " . $e->getMessage() . " | User: " . ($_SESSION['username'] ?? 'Unknown'));
    
    $errorMessage = 'İstifadəçi əlavə edilərkən xəta baş verdi.';
    
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $errorMessage = 'Bu məlumatlar artıq sistemdə mövcuddur.';
    } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        $errorMessage = 'Əlaqəli məlumatlar tapılmadı.';
    } elseif (strpos($e->getMessage(), 'Data too long') !== false) {
        $errorMessage = 'Daxil edilən məlumatlar çox uzundur.';
    }
    
    showErrorModal($errorMessage . ' Texniki dəstək ilə əlaqə saxlayın.');
} finally {
    if (isset($conn)) {
        $conn->autocommit(TRUE);
        $conn->close();
    }
}
?>