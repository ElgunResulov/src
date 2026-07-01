<?php
    include('db.php');
    app_start_secure_session();
    app_require_auth($conn);
    app_require_role(['super_admin', 'admin'], 'index.php');

    function getDefaultPermissionsForRole($role) {
        $permissions = [
            'student' => ['Elanlar','Academic Calendar Telebe','Dərs Cədvəli Telebe','Zoom cədvəli','İmtahan cədvəli','İmtahan nəticələri','Elektron jurnal','Tədris materialları','Məmnunluq anketi', 'Apellyasiya','İmtahan Sualları','Sərbəst işlər'],
            'teacher' => ['Hesablar', 'Müəllimlər', 'Dərslər', 'Tələbələr'],
            'staff' => ['Əsas', 'Əməkdaşlar', 'Qeydiyyatar'],
            'parent' => ['Tələbələr', 'Dərs Cədvəli'],
            'examiner' => ['İmtahanlar', 'İmtahan Sualları']
        ];
        return $permissions[$role] ?? [];
    }

    function encodePermissions($permissions) {
        return json_encode($permissions, JSON_UNESCAPED_UNICODE);
    }

    function decodePermissions($permissions_json) {
        return $permissions_json ? json_decode($permissions_json, true) : [];
    }

    // Main logic
    $logged_in_role = $_SESSION['role'] ?? ''; 
    $user_id = (int)$_GET['id']; // Cast to integer for safety

    // Fetch user data
    $sql = "SELECT u.username, u.password, u.role, up.permissions, u.company_id 
            FROM users u 
            LEFT JOIN user_permissions up ON u.id = up.user_id 
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "User not found!";
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $user_name = trim($_POST['username']);
        $user_password = $_POST['password'];
        $user_role = $_POST['role'];
        $company_id = $user['company_id'];
        
        if ($user_role == 'super_admin' && isset($_POST['company_id'])) {
            $company_id = $_POST['company_id'];
        }

        if ($user_name !== $user['username']) {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt_check->bind_param("si", $user_name, $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                echo "
                <!DOCTYPE html>
                <html lang='az'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1'>
                    <title>Xəta</title>
                    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
                    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
                </head>
                <body class='bg-light'>
                    <div class='container d-flex justify-content-center align-items-center' style='height: 100vh;'>
                        <div class='modal fade show d-block' id='errorModal' tabindex='-1' aria-labelledby='errorModalLabel' aria-hidden='true'>
                            <div class='modal-dialog modal-dialog-centered'>
                                <div class='modal-content'>
                                    <div class='modal-header bg-danger text-white'>
                                        <h5 class='modal-title' id='errorModalLabel'>Xəta</h5>
                                    </div>
                                    <div class='modal-body text-center'>
                                        <p>Bu istifadəçi adı artıq mövcuddur. Başqa ad seçin.</p>
                                    </div>
                                    <div class='modal-footer justify-content-center'>
                                        <button type='button' class='btn btn-secondary' onclick='redirectPage()'>Bağla</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        function redirectPage() {
                            window.history.back();
                        }
                        setTimeout(redirectPage, 3500);
                    </script>
                </body>
                </html>";
                exit;
            }
            $stmt_check->close();
        }
        
        if (!empty($user_password)) {
            $user_password_hash = app_hash_password($user_password);
            $update_sql = "UPDATE users SET username = ?, password = ?, role = ?, company_id = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssii", $user_name, $user_password_hash, $user_role, $company_id, $user_id);
        } else {
            $update_sql = "UPDATE users SET username = ?, role = ?, company_id = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssii", $user_name, $user_role, $company_id, $user_id);
        }

        if ($stmt->execute()) {
            if ($user_role != 'super_admin' && $user_role != 'admin') {
                $permissions = isset($_POST['modules']) && !empty($_POST['modules']) ? $_POST['modules'] : getDefaultPermissionsForRole($user_role);
                $permissions_json = encodePermissions($permissions);
                $stmt_check_perm = $conn->prepare("SELECT id FROM user_permissions WHERE user_id = ?");
                $stmt_check_perm->bind_param("i", $user_id);
                $stmt_check_perm->execute();
                $result_perm = $stmt_check_perm->get_result();
                
                if ($result_perm->num_rows > 0) {
                    $update_permissions_sql = "UPDATE user_permissions SET permissions = ?, company_id = ? WHERE user_id = ?";
                    $stmt_update_perm = $conn->prepare($update_permissions_sql);
                    $stmt_update_perm->bind_param("sii", $permissions_json, $company_id, $user_id);
                    $stmt_update_perm->execute();
                    $stmt_update_perm->close();
                } else {
                    $insert_permissions_sql = "INSERT INTO user_permissions (user_id, permissions, company_id) VALUES (?, ?, ?)";
                    $stmt_insert_perm = $conn->prepare($insert_permissions_sql);
                    $stmt_insert_perm->bind_param("isi", $user_id, $permissions_json, $company_id);
                    $stmt_insert_perm->execute();
                    $stmt_insert_perm->close();
                }
                $stmt_check_perm->close();
            } else {
                $stmt_delete_perm = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                $stmt_delete_perm->bind_param("i", $user_id);
                $stmt_delete_perm->execute();
                $stmt_delete_perm->close();
            }
            
            echo "
            <!DOCTYPE html>
            <html lang='az'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <title>Uğurlu</title>
                <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
            </head>
            <body class='bg-light'>
                <div class='container d-flex justify-content-center align-items-center' style='height: 100vh;'>
                    <div class='modal fade show d-block' id='successModal' tabindex='-1'>
                        <div class='modal-dialog modal-dialog-centered'>
                            <div class='modal-content'>
                                <div class='modal-header bg-success text-white'>
                                    <h5 class='modal-title'>Uğurlu</h5>
                                </div>
                                <div class='modal-body text-center'>
                                    <p>İstifadəçi məlumatları uğurla yeniləndi!</p>
                                </div>
                                <div class='modal-footer justify-content-center'>
                                    <button type='button' class='btn btn-primary' onclick='redirectPage()'>Tamam</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    function redirectPage() {
                        window.location.href = 'Hesablar.php';
                    }
                    setTimeout(redirectPage, 2000);
                </script>
            </body>
            </html>";
        } else {
            echo "
            <!DOCTYPE html>
            <html lang='az'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <title>Xəta</title>
                <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
            </head>
            <body class='bg-light'>
                <div class='container d-flex justify-content-center align-items-center' style='height: 100vh;'>
                    <div class='modal fade show d-block' id='errorModal' tabindex='-1'>
                        <div class='modal-dialog modal-dialog-centered'>
                            <div class='modal-content'>
                                <div class='modal-header bg-danger text-white'>
                                    <h5 class='modal-title'>Xəta</h5>
                                </div>
                                <div class='modal-body text-center'>
                                    <p>Xəta baş verdi: " . $conn->error . "</p>
                                </div>
                                <div class='modal-footer justify-content-center'>
                                    <button type='button' class='btn btn-secondary' onclick='redirectPage()'>Bağla</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    function redirectPage() {
                        window.history.back();
                    }
                    setTimeout(redirectPage, 3000);
                </script>
            </body>
            </html>";
        }
        $stmt->close();
        exit;
    }

    // Parse permissions from JSON if they exist
    $permissions = decodePermissions($user['permissions'] ?? '');
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Edit User</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: rgb(25, 115, 225); --secondary-color: #f0f5ff; --text-color: #333; --border-color: #e0e0e0; --success-color: #00c853; --error-color: #ff3d00;}
        body { font-family: 'Poppins', sans-serif; background-color: rgba(238, 233, 228, 0.38); color: var(--text-color); line-height: 1.6; margin: 0; padding: 20px;}
        .form-container { max-width: 93%; overflow: auto; margin: 0 auto; background-color: #fff; height: auto; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);}
        h2 { color: var(--primary-color); text-align: center; margin-bottom: 20px; font-weight: 600; font-size: 32px;}
        .form-group { margin-bottom: 20px;}
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #555;}
        input[type="text"], input[type="password"], select { width: 95%; padding: 12px; border: 2px solid var(--border-color); border-radius: 12px; font-size: 16px; transition: all 0.3s ease;}
        input[type="text"]:focus, input[type="password"]:focus, select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(74, 108, 247, 0.1); outline: none;}
        .permissions-group { display: grid; height: 195px; overflow: auto; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8.9px; margin-top: 10px;}
        .permissions-group label { display: flex; align-items: center; padding: 12px; background-color: var(--secondary-color); border-radius: 8px; cursor: pointer; font-size: 14px; transition: all 0.3s ease; font-weight: 600;}
        .permissions-group input[type="checkbox"] { margin-right: 8px; appearance: none; width: 18px; height: 18px; border: 2px solid var(--border-color); border-radius: 4px; outline: none; transition: all 0.3s ease;}
        .permissions-group input[type="checkbox"]:checked { border: 0.1px solid var(--primary-color); background-color: var(--primary-color);}
        .permissions-group input[type="checkbox"]:checked::after { content: '\2714'; display: block; text-align: center; color: white; font-size: 14px; line-height: 18px;}
        .permissions-group label:hover { background-color: rgba(54, 154, 236, 0.25);}
        .btn { display: inline-block; padding: 12px 20px; background-color: var(--primary-color); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 500; transition: all 0.3s ease; text-decoration: none;}
        .btn:hover { background-color: #3a5bd9; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(74, 108, 247, 0.2);}
        .btn-secondary { background-color: #f0f0f0; color: #333;}
        .btn-secondary:hover { background-color: #e0e0e0;}
        .mt-3 { margin-top: 20px;}
        .button-group { display: flex; justify-content: space-between; align-items: center; margin-top: 30px;}
        .company_id { margin-top: -30px; text-align: right; font-size: 12px; font-family: Arial; width: 100%;}
        .role-group { display: flex; flex-wrap: wrap; justify-content: space-between; margin-top: 10px; gap: 10px;}
        .role-group label { flex: 1; min-width: 120px; text-align: center; padding: 12px 8px; cursor: pointer; transition: all 0.3s ease; margin: 0; font-weight: 500; background-color: var(--secondary-color); border-radius: 8px;}
        .role-group input[type="radio"] { display: none;}
        .role-group input[type="radio"]:checked + label { background-color: rgba(6, 173, 64, 0.65); color: #fff; font-weight: 500;}
        @media (max-width: 768px) {
        body { padding: 20px 10px;}
        .form-container { padding: 40px; border-radius: 15px;}
        h2 { font-size: 24px; margin-bottom: 20px;}
        .form-group { margin-bottom: 15px;}
        .permissions-group input[type="checkbox"]:checked::after { content: ''; display: block; text-align: center; font-size: 14px; line-height: 18px;}
        .permissions-group input[type="checkbox"]:checked { border: 0px solid var(--primary-color); background-color: var(--primary-color);}
        input[type="text"], input[type="password"], select { padding: 10px; width: 90%; font-size: 14px;}
        .permissions-group { height: auto; max-height: 200px; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 6px;}
        .permissions-group label { padding: 9px; font-size: 11px;}
        .permissions-group input[type="checkbox"] { width: 16px; height: 16px;}
        .btn { padding: 10px 20px; font-size: 14px;}
        .button-group { flex-direction: column; gap: 15px;}
        .button-group .btn { width: 70%; text-align: center;}
        .role-group { flex-direction: column;}
        .role-group label { width: 100%; padding: 8px; font-size: 14px;}
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>İstifadəçi Redaktə Et</h2>
        <p style="display:none;" class="company_id">Company ID: <?php echo htmlspecialchars($user['company_id']); ?></p>
        <form method="POST">
          <div hidden>
            <div class="form-group">
                <label for="username">İstifadəçi Adı</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
          </div>

            
            <div hidden class="form-group">
                <label for="password">Şifrə (boş buraxsanız dəyişməz)</label>
                <input type="password" id="password" name="password" placeholder="Yeni şifrə daxil edin">
            </div>
            
            <div class="form-group">
                <label for="role">Rol</label>
                <div class="role-group">
                    <input type="radio" id="super_admin" name="role" value="super_admin" <?php echo $user['role'] == 'super_admin' ? 'checked' : ''; ?>>
                    <label for="super_admin">Super Admin</label>
                    <input type="radio" id="admin" name="role" value="admin" <?php echo $user['role'] == 'admin' ? 'checked' : ''; ?>>
                    <label for="admin">Admin</label>
                    <input type="radio" id="teacher" name="role" value="teacher" <?php echo $user['role'] == 'teacher' ? 'checked' : ''; ?>>
                    <label for="teacher">Müəllim</label>
                    <input type="radio" id="student" name="role" value="student" <?php echo $user['role'] == 'student' ? 'checked' : ''; ?>>
                    <label for="student">Tələbə</label>
                    <input type="radio" id="staff" name="role" value="staff" <?php echo $user['role'] == 'staff' ? 'checked' : ''; ?>>
                    <label for="staff">Staff</label>
                    <input type="radio" id="parent" name="role" value="parent" <?php echo $user['role'] == 'parent' ? 'checked' : ''; ?>>
                    <label for="parent">Valideyn</label>
                    <input type="radio" id="examiner" name="role" value="examiner" <?php echo $user['role'] == 'examiner' ? 'checked' : ''; ?>>
                    <label for="examiner">İmtahan yoxlayıcısı</label>
                </div>
            </div>
            
            <div id="permissionsBox1" class="form-group" style="display: <?php echo ($user['role'] != 'super_admin' && $user['role'] != 'admin') ? 'block' : 'none'; ?>;">
                <label>İcazələr</label>
                <div class="permissions-group">
                    <?php 
                    $modules = [
                        'Hesablar',
                        'Ümumi istifadəçilər',
                        'Əsas',
                        'Mövzular',
                        'Müəllimlər',
                        'Dərslər',
                        'Tələbələr',
                        'İmtahanlar',
                        'Dərs Cədvəli',
                        'Statistika',
                        'İxtisas üzrə idarəetmə',
                        'Əməkdaşlar',
                        'Qeydiyyatar',
                    ];
                    
                    foreach ($modules as $module) {
                        $checked = in_array($module, $permissions) ? 'checked' : '';
                        echo "<label><input type='checkbox' name='modules[]' value='$module' $checked> <span>$module</span></label>";
                    }
                    ?>
                </div>
            </div>
            
            <div id="permissionsBox2" class="form-group" style="display: <?php echo ($user['role'] != 'super_admin' && $user['role'] != 'admin') ? 'block' : 'none'; ?>;">
                <label>Tələbə icazələri</label>
                <div class="permissions-group">
                    <?php 
                    $student_modules = [
                        'Elanlar',
                        'Academic Calendar Telebe',
                        'Dərs Cədvəli Telebe',
                        'Zoom cədvəli',
                        'İmtahan cədvəli',
                        'İmtahan nəticələri',
                        'Elektron jurnal',
                        'Tədris materialları',
                        'Məmnunluq anketi',
                        'Apellyasiya',
                        'İmtahan Sualları',
                        'Sərbəst işlər'
                    ];
                    
                    foreach ($student_modules as $module) {
                        $checked = in_array($module, $permissions) ? 'checked' : '';
                        echo "<label><input type='checkbox' name='modules[]' value='$module' $checked> <span>$module</span></label>";
                    }
                    ?>
                </div>
            </div>
            
            <div class="button-group">
                <a style="font-family:Arial;font-weight:bold;" href="Hesablar.php" class="btn btn-secondary">Geri</a>
                <button style="font-family:Arial;font-weight:bold;" type="submit" class="btn">Təsdiq</button>
            </div>
        </form>
    </div>
    
    <script>
        const roleRadios = document.querySelectorAll('input[name="role"]');
        const permissionsBox1 = document.getElementById('permissionsBox1');
        const permissionsBox2 = document.getElementById('permissionsBox2');
        
        roleRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const showPermissions = (this.value !== 'super_admin' && this.value !== 'admin');
                permissionsBox1.style.display = showPermissions ? 'block' : 'none';
                permissionsBox2.style.display = showPermissions ? 'block' : 'none';
                const checkboxes = document.querySelectorAll('input[name="modules[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                if (showPermissions) {
                    const rolePermissions = {
                        'student': ['Elanlar','Academic Calendar Telebe','Dərs Cədvəli Telebe','Zoom cədvəli','İmtahan cədvəli','İmtahan nəticələri','Elektron jurnal','Tədris materialları','Məmnunluq anketi', 'Apellyasiya','İmtahan Sualları','Sərbəst işlər'],
                        'teacher': ['Hesablar', 'Müəllimlər', 'Dərslər', 'Tələbələr'],
                        'staff': ['Əsas', 'Əməkdaşlar', 'Qeydiyyatar'],
                        'parent': ['Tələbələr', 'Dərs Cədvəli'],
                        'examiner': ['İmtahanlar', 'İmtahan Sualları']
                    };
                    
                    if (rolePermissions[this.value]) {
                        checkboxes.forEach(checkbox => {
                            if (rolePermissions[this.value].includes(checkbox.value)) {
                                checkbox.checked = true;
                            }
                        });
                    }
                }
            });
        });
        
        window.addEventListener('load', function() {
            const selectedRole = document.querySelector('input[name="role"]:checked');
            if (selectedRole) {
                const showPermissions = (selectedRole.value !== 'super_admin' && selectedRole.value !== 'admin');
                permissionsBox1.style.display = showPermissions ? 'block' : 'none';
                permissionsBox2.style.display = showPermissions ? 'block' : 'none';
            }
        });
    </script>
</body>
</html>