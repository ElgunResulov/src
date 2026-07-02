<?php
// File: ixtisas/ixtisas_operations.php
header('Content-Type: application/json');

// Include database connection
require_once('../db.php');
app_require_auth_api($conn);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Function to check if column exists in table
function columnExists($conn, $tableName, $columnName) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return mysqli_num_rows($result) > 0;
}

// Function to get count safely
function getCountSafely($conn, $tableName, $columnName, $id) {
    $id = (int) $id;
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName) || !preg_match('/^[A-Za-z0-9_]+$/', $columnName)) {
        return 0;
    }

    if (!tableExists($conn, $tableName)) {
        return 0;
    }
    
    if (!columnExists($conn, $tableName, $columnName)) {
        return 0;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `$tableName` WHERE `$columnName` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result)['count'];
    }
    
    return 0;
}

// File upload handler
function handleFileUpload($file) {
    $target_dir = "uploads/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Validate image
    if (!empty($file["tmp_name"])) {
        $check = getimagesize($file["tmp_name"]);
        if($check === false) {
            return ["status" => "error", "message" => "Fayl şəkil deyil."];
        }
    }
    
    // Check file size (5MB limit)
    if ($file["size"] > 5000000) {
        return ["status" => "error", "message" => "Fayl həcmi çox böyükdür (maksimum 5MB)."];
    }
    
    // Allow certain file formats
    $allowed_types = ["jpg", "jpeg", "png", "gif", "webp"];
    if(!in_array($imageFileType, $allowed_types)) {
        return ["status" => "error", "message" => "Yalnız JPG, JPEG, PNG, GIF və WEBP faylları qəbul edilir."];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["status" => "success", "file_name" => $file_name];
    } else {
        return ["status" => "error", "message" => "Fayl yükləmə zamanı xəta baş verdi."];
    }
}

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'insert':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ixtisas_adi = trim($_POST['ixtisas_adi']);
                $ixtisas_kodu = trim($_POST['ixtisas_kodu']);
                $fakulte = isset($_POST['fakulte']) ? trim($_POST['fakulte']) : '';
                $tehsil_seviyyesi = trim($_POST['tehsil_seviyyesi']);
                $tesvir = isset($_POST['tesvir']) ? trim($_POST['tesvir']) : '';
                $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
                $u_id = (int)$_SESSION['user_id'];
                
                // Validate required fields
                if (empty($ixtisas_adi) || empty($ixtisas_kodu) || empty($tehsil_seviyyesi)) {
                    echo json_encode(['status' => 'error', 'message' => 'Bütün məcburi sahələri doldurun.']);
                    exit;
                }
                
                $check_stmt = $conn->prepare("SELECT id FROM ixtisas WHERE ixtisas_kodu = ?");
                $check_stmt->bind_param("s", $ixtisas_kodu);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if (mysqli_num_rows($check_result) > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Bu ixtisas kodu artıq mövcuddur.']);
                    exit;
                }
                
                $sekil_path = '';
                $sekil_type = 'placeholder';
                
                // Handle file upload
                if (isset($_FILES['sekil']) && $_FILES['sekil']['size'] > 0) {
                    $upload_result = handleFileUpload($_FILES['sekil']);
                    if ($upload_result['status'] === 'success') {
                        $sekil_path = $upload_result['file_name'];
                        $sekil_type = 'file';
                    } else {
                        echo json_encode($upload_result);
                        exit;
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO ixtisas (u_id, ixtisas_adi, ixtisas_kodu, fakulte, tehsil_seviyyesi, tesvir, sekil, sekil_type, active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isssssssi", $u_id, $ixtisas_adi, $ixtisas_kodu, $fakulte, $tehsil_seviyyesi, $tesvir, $sekil_path, $sekil_type, $active);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'İxtisas uğurla əlavə edildi.']);
                } else {
                    error_log("Insert error: " . mysqli_error($conn));
                    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
                }
            }
            break;
            
        case 'view':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                
                $query = "SELECT i.*, 
                          CASE 
                              WHEN i.fakulte = '1' THEN 'Mühəndislik'
                              WHEN i.fakulte = '2' THEN 'İqtisadiyyat'
                              WHEN i.fakulte = '3' THEN 'Humanitar'
                              WHEN i.fakulte = '4' THEN 'Tibb'
                              ELSE 'Naməlum'
                          END as fakulte_adi,
                          CASE 
                              WHEN i.tehsil_seviyyesi = 'bachelor' THEN 'Bakalavr'
                              WHEN i.tehsil_seviyyesi = 'master' THEN 'Magistr'
                              WHEN i.tehsil_seviyyesi = 'phd' THEN 'Doktorantura'
                              ELSE 'Naməlum'
                          END as tehsil_seviyyesi_adi
                          FROM ixtisas i WHERE i.id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $specialty = mysqli_fetch_assoc($result);
                    
                    // Handle image path
                    if ($specialty['sekil_type'] === 'file' && !empty($specialty['sekil'])) {
                        $specialty['sekil'] = 'ixtisas/uploads/' . $specialty['sekil'];
                    } else {
                        $specialty['sekil'] = '';
                    }
                    
                    // Get counts safely - try different possible column names
                    $counts = [
                        'students' => 0,
                        'teachers' => 0,
                        'subjects' => 0
                    ];
                    
                    // Try different possible column names for students
                    $student_columns = ['ixtisas_id', 'specialty_id', 'ixtisas', 'id'];
                    foreach ($student_columns as $col) {
                        $counts['students'] = getCountSafely($conn, 'telebeler', $col, $id);
                        if ($counts['students'] > 0) break;
                    }
                    
                    // Try different possible column names for teachers
                    $teacher_tables = ['muellimler_new', 'muellimler', 'teachers'];
                    $teacher_columns = ['ixtisas_id', 'specialty_id', 'ixtisas', 'id'];
                    foreach ($teacher_tables as $table) {
                        foreach ($teacher_columns as $col) {
                            $counts['teachers'] = getCountSafely($conn, $table, $col, $id);
                            if ($counts['teachers'] > 0) break 2;
                        }
                    }
                    
                    // Try different possible column names for subjects
                    $subject_tables = ['fennler_new', 'fennler', 'subjects'];
                    $subject_columns = ['ixtisas_id', 'specialty_id', 'ixtisas', 'id'];
                    foreach ($subject_tables as $table) {
                        foreach ($subject_columns as $col) {
                            $counts['subjects'] = getCountSafely($conn, $table, $col, $id);
                            if ($counts['subjects'] > 0) break 2;
                        }
                    }
                    
                    echo json_encode([
                        'status' => 'success', 
                        'data' => $specialty,
                        'counts' => $counts
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'İxtisas tapılmadı.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID parametri təqdim edilməyib.']);
            }
            break;
            
        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = (int)$_POST['id'];
                $ixtisas_adi = trim($_POST['ixtisas_adi']);
                $ixtisas_kodu = trim($_POST['ixtisas_kodu']);
                $fakulte = isset($_POST['fakulte']) ? trim($_POST['fakulte']) : '';
                $tehsil_seviyyesi = trim($_POST['tehsil_seviyyesi']);
                $tesvir = isset($_POST['tesvir']) ? trim($_POST['tesvir']) : '';
                $active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
                
                // Validate required fields
                if (empty($ixtisas_adi) || empty($ixtisas_kodu) || empty($tehsil_seviyyesi)) {
                    echo json_encode(['status' => 'error', 'message' => 'Bütün məcburi sahələri doldurun.']);
                    exit;
                }
                
                $check_stmt = $conn->prepare("SELECT id FROM ixtisas WHERE ixtisas_kodu = ? AND id != ?");
                $check_stmt->bind_param("si", $ixtisas_kodu, $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if (mysqli_num_rows($check_result) > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Bu ixtisas kodu artıq mövcuddur.']);
                    exit;
                }
                
                $sekil_path = null;

                // Handle file upload
                if (isset($_FILES['sekil']) && $_FILES['sekil']['size'] > 0) {
                    $upload_result = handleFileUpload($_FILES['sekil']);
                    if ($upload_result['status'] === 'success') {
                        $sekil_path = $upload_result['file_name'];

                        // Delete old image
                        $old_image_stmt = $conn->prepare("SELECT sekil, sekil_type FROM ixtisas WHERE id = ?");
                        $old_image_stmt->bind_param("i", $id);
                        $old_image_stmt->execute();
                        $old_image_result = $old_image_stmt->get_result();
                        if ($old_image_result && mysqli_num_rows($old_image_result) > 0) {
                            $old_image = mysqli_fetch_assoc($old_image_result);
                            if ($old_image['sekil_type'] === 'file' && !empty($old_image['sekil']) && file_exists('uploads/' . $old_image['sekil'])) {
                                unlink('uploads/' . $old_image['sekil']);
                            }
                        }
                    } else {
                        echo json_encode($upload_result);
                        exit;
                    }
                }

                if ($sekil_path !== null) {
                    $stmt = $conn->prepare("UPDATE ixtisas SET ixtisas_adi = ?, ixtisas_kodu = ?, fakulte = ?, tehsil_seviyyesi = ?, tesvir = ?, active = ?, sekil = ?, sekil_type = 'file', updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("sssssisi", $ixtisas_adi, $ixtisas_kodu, $fakulte, $tehsil_seviyyesi, $tesvir, $active, $sekil_path, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE ixtisas SET ixtisas_adi = ?, ixtisas_kodu = ?, fakulte = ?, tehsil_seviyyesi = ?, tesvir = ?, active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("sssssii", $ixtisas_adi, $ixtisas_kodu, $fakulte, $tehsil_seviyyesi, $tesvir, $active, $id);
                }

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'İxtisas uğurla yeniləndi.']);
                } else {
                    error_log("Update error: " . mysqli_error($conn));
                    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
                }
            }
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                
                // Get image info before deletion
                $stmt = $conn->prepare("SELECT sekil, sekil_type FROM ixtisas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $specialty = mysqli_fetch_assoc($result);
                    
                    // Delete the record
                    $delete_stmt = $conn->prepare("DELETE FROM ixtisas WHERE id = ?");
                    $delete_stmt->bind_param("i", $id);
                    if ($delete_stmt->execute()) {
                        // Delete image file if exists
                        if ($specialty['sekil_type'] === 'file' && !empty($specialty['sekil']) && file_exists('uploads/' . $specialty['sekil'])) {
                            unlink('uploads/' . $specialty['sekil']);
                        }
                        echo json_encode(['status' => 'success', 'message' => 'İxtisas uğurla silindi.']);
                    } else {
                        error_log("Delete error: " . mysqli_error($conn));
                        echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'İxtisas tapılmadı.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID parametri təqdim edilməyib.']);
            }
            break;
            
        case 'list':
            $query = "SELECT i.*, 
                      CASE 
                          WHEN i.fakulte = '1' THEN 'Mühəndislik'
                          WHEN i.fakulte = '2' THEN 'İqtisadiyyat'
                          WHEN i.fakulte = '3' THEN 'Humanitar'
                          WHEN i.fakulte = '4' THEN 'Tibb'
                          ELSE 'Naməlum'
                      END as fakulte_adi,
                      CASE 
                          WHEN i.tehsil_seviyyesi = 'bachelor' THEN 'Bakalavr'
                          WHEN i.tehsil_seviyyesi = 'master' THEN 'Magistr'
                          WHEN i.tehsil_seviyyesi = 'phd' THEN 'Doktorantura'
                          ELSE 'Naməlum'
                      END as tehsil_seviyyesi_adi
                      FROM ixtisas i 
                      ORDER BY i.created_at DESC";
            
            $result = mysqli_query($conn, $query);
            
            if (!$result) {
                error_log("List query error: " . mysqli_error($conn));
                echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
                exit;
            }
            
            $specialties = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Handle image path
                if ($row['sekil_type'] === 'file' && !empty($row['sekil'])) {
                    $row['sekil_full_path'] = 'ixtisas/uploads/' . $row['sekil'];
                } else {
                    $row['sekil_full_path'] = '';
                }
                $specialties[] = $row;
            }
            
            echo json_encode(['status' => 'success', 'data' => $specialties]);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Yanlış əməliyyat.']);
            break;
    }
} catch (Exception $e) {
    error_log("Exception in ixtisas_operations.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server xətası: ' . $e->getMessage()]);
}

// Close connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>