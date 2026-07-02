<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once('../db.php');
app_require_auth_api($conn);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası bağlantısı uğursuz']);
    exit();
}

$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Əməliyyat təyin edilməyib']);
    exit();
}

try {
    $u_id = (int)$_SESSION['user_id'];
    
    switch ($action) {
        case 'list':
            $stmt = $conn->prepare("SELECT * FROM filiallar WHERE u_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $u_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $filials = [];
            
            while ($row = $result->fetch_assoc()) {
                $filial_adi = $row['filial_adi'];
                
                // Подсчитываем учителей, у которых этот филиал есть в JSON массиве
                $teacher_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM muellimler_new WHERE JSON_CONTAINS(filial_adi, JSON_QUOTE(?))");
                $teacher_count_stmt->bind_param("s", $filial_adi);
                $teacher_count_stmt->execute();
                $teacher_count_result = $teacher_count_stmt->get_result();
                $teacher_count = $teacher_count_result->fetch_assoc()['count'];
                $teacher_count_stmt->close();
                
                $filials[] = [
                    'id' => $row['id'],
                    'name' => $row['filial_adi'],
                    'address' => $row['unvan'],
                    'phone' => $row['telefon'],
                    'teacher_count' => $teacher_count,
                    'createdAt' => $row['created_at']
                ];
            }
            $stmt->close();
            
            echo json_encode(['status' => 'success', 'data' => $filials]);
            break;
            
        case 'get_filial_details':
            if (isset($_GET['id'])) {
                $filial_id = (int)$_GET['id'];
                $stmt = $conn->prepare("SELECT * FROM filiallar WHERE id = ? AND u_id = ?");
                $stmt->bind_param("ii", $filial_id, $u_id);
                $stmt->execute();
                $filial_result = $stmt->get_result();
                
                if ($filial_result && $filial_result->num_rows > 0) {
                    $filial = $filial_result->fetch_assoc();
                    
                    // Получаем учителей этого филиала
                    $teachers_stmt = $conn->prepare("SELECT * FROM muellimler_new WHERE JSON_CONTAINS(filial_adi, JSON_QUOTE(?))");
                    $teachers_stmt->bind_param("s", $filial['filial_adi']);
                    $teachers_stmt->execute();
                    $teachers_result = $teachers_stmt->get_result();
                    
                    $teachers = [];
                    while ($teacher_row = $teachers_result->fetch_assoc()) {
                        $teachers[] = [
                            'id' => $teacher_row['id'],
                            'username' => $teacher_row['username'],
                            'subject' => $teacher_row['tehsil_ve_ixtisas'] ?? 'Bilinmir'
                        ];
                    }
                    $teachers_stmt->close();
                    
                    echo json_encode([
                        'status' => 'success',
                        'data' => [
                            'filial' => [
                                'id' => $filial['id'],
                                'name' => $filial['filial_adi'],
                                'address' => $filial['unvan'],
                                'phone' => $filial['telefon']
                            ],
                            'teachers' => $teachers
                        ]
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Filial tapılmadı']);
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("SELECT id, filial_adi FROM filiallar WHERE u_id = ? ORDER BY filial_adi ASC");
                $stmt->bind_param("i", $u_id);
                $stmt->execute();
                $filial_result = $stmt->get_result();
                
                $filials = [];
                while ($row = $filial_result->fetch_assoc()) {
                    $filials[] = [
                        'id' => $row['id'],
                        'filial_adi' => $row['filial_adi']
                    ];
                }
                $stmt->close();
                
                echo json_encode(['status' => 'success', 'data' => $filials]);
            }
            break;
            
        case 'get_fenn_by_filial':
            if (!isset($_GET['filial_adi'])) {
                echo json_encode(['status' => 'error', 'message' => 'Filial adı təqdim edilməyib']);
                break;
            }
            
            $filial_adi = trim($_GET['filial_adi']);
            
            $verify_stmt = $conn->prepare("SELECT id FROM filiallar WHERE filial_adi = ? AND u_id = ?");
            $verify_stmt->bind_param("si", $filial_adi, $u_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            if ($verify_result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Bu filial sizə aid deyil']);
                $verify_stmt->close();
                break;
            }
            $verify_stmt->close();
            
            $fenn_stmt = $conn->prepare("SELECT DISTINCT tehsil_ve_ixtisas FROM muellimler_new WHERE JSON_CONTAINS(filial_adi, JSON_QUOTE(?)) AND tehsil_ve_ixtisas IS NOT NULL AND tehsil_ve_ixtisas != '' ORDER BY tehsil_ve_ixtisas ASC");
            $fenn_stmt->bind_param("s", $filial_adi);
            $fenn_stmt->execute();
            $fenn_result = $fenn_stmt->get_result();
            
            $fennler = [];
            while ($row = $fenn_result->fetch_assoc()) {
                $fennler[] = [
                    'fenn_adi' => $row['tehsil_ve_ixtisas']
                ];
            }
            $fenn_stmt->close();
            echo json_encode(['status' => 'success', 'data' => $fennler]);
            break;
            
        case 'get_teachers_by_filial_and_fenn':
            if (!isset($_GET['filial_adi']) || !isset($_GET['fenn_adi'])) {
                echo json_encode(['status' => 'error', 'message' => 'Filial və ya fənn adı təqdim edilməyib']);
                break;
            }
            
            $filial_adi = trim($_GET['filial_adi']);
            $fenn_adi = trim($_GET['fenn_adi']);
            
            $verify_stmt = $conn->prepare("SELECT id FROM filiallar WHERE filial_adi = ? AND u_id = ?");
            $verify_stmt->bind_param("si", $filial_adi, $u_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            if ($verify_result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Bu filial sizə aid deyil']);
                $verify_stmt->close();
                break;
            }
            $verify_stmt->close();
            
            $teachers_stmt = $conn->prepare("SELECT id, username, tehsil_ve_ixtisas FROM muellimler_new WHERE JSON_CONTAINS(filial_adi, JSON_QUOTE(?)) AND tehsil_ve_ixtisas = ? ORDER BY username ASC");
            $teachers_stmt->bind_param("ss", $filial_adi, $fenn_adi);
            $teachers_stmt->execute();
            $teachers_result = $teachers_stmt->get_result();
            
            $teachers = [];
            while ($row = $teachers_result->fetch_assoc()) {
                $teachers[] = [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'tehsil_ve_ixtisas' => $row['tehsil_ve_ixtisas']
                ];
            }
            $teachers_stmt->close();
            echo json_encode(['status' => 'success', 'data' => $teachers]);
            break;
            
        case 'insert':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Yanlış sorğu metodu']);
                break;
            }
            
            $filial_adi = trim($_POST['filial_adi'] ?? '');
            $unvan = trim($_POST['unvan'] ?? '');
            $telefon = trim($_POST['telefon'] ?? '');
            
            if (empty($filial_adi) || empty($unvan) || empty($telefon)) {
                echo json_encode(['status' => 'error', 'message' => 'Bütün sahələri doldurun.']);
                break;
            }
            
            $check_stmt = $conn->prepare("SELECT id FROM filiallar WHERE filial_adi = ? AND u_id = ?");
            $check_stmt->bind_param("si", $filial_adi, $u_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Bu adda filial artıq mövcuddur.']);
                $check_stmt->close();
                break;
            }
            $check_stmt->close();
            
            $insert_stmt = $conn->prepare("INSERT INTO filiallar (u_id, filial_adi, unvan, telefon, created_at) VALUES (?, ?, ?, ?, NOW())");
            $insert_stmt->bind_param("isss", $u_id, $filial_adi, $unvan, $telefon);
            if ($insert_stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Filial uğurla əlavə edildi.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . $insert_stmt->error]);
            }
            $insert_stmt->close();
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'ID parametri təqdim edilməyib.']);
                break;
            }
            
            $id = (int)$_POST['id'];
            $filial_adi = trim($_POST['filial_adi'] ?? '');
            $unvan = trim($_POST['unvan'] ?? '');
            $telefon = trim($_POST['telefon'] ?? '');
            
            if (empty($filial_adi) || empty($unvan) || empty($telefon)) {
                echo json_encode(['status' => 'error', 'message' => 'Bütün sahələri doldurun.']);
                break;
            }
            
            $update_stmt = $conn->prepare("UPDATE filiallar SET filial_adi = ?, unvan = ?, telefon = ? WHERE id = ? AND u_id = ?");
            $update_stmt->bind_param("sssii", $filial_adi, $unvan, $telefon, $id, $u_id);
            if ($update_stmt->execute()) {
                if ($update_stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Filial uğurla yeniləndi.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Filial tapılmadı və ya dəyişiklik edilmədi.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . $update_stmt->error]);
            }
            $update_stmt->close();
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'ID parametri təqdim edilməyib.']);
                break;
            }
            
            $id = (int)$_POST['id'];
            $delete_stmt = $conn->prepare("DELETE FROM filiallar WHERE id = ? AND u_id = ?");
            $delete_stmt->bind_param("ii", $id, $u_id);
            if ($delete_stmt->execute()) {
                if ($delete_stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Filial uğurla silindi.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Filial tapılmadı.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . $delete_stmt->error]);
            }
            $delete_stmt->close();
            break;
            
        case 'get_teachers':
            $stmt = $conn->prepare("SELECT id, username, tehsil_ve_ixtisas, filial_adi FROM muellimler_new ORDER BY id DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            $teachers = [];
            
            while ($row = $result->fetch_assoc()) {
                $teachers[] = [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'tehsil_ve_ixtisas' => $row['tehsil_ve_ixtisas'],
                    'subject' => $row['tehsil_ve_ixtisas'],
                    'filial_adi' => $row['filial_adi']
                ];
            }
            $stmt->close();
            echo json_encode(['status' => 'success', 'data' => $teachers]);
            break;

        case 'get_teacher_usernames':
            $stmt = $conn->prepare("SELECT username FROM muellimler_new WHERE username IS NOT NULL AND username != '' ORDER BY username");
            $stmt->execute();
            $result = $stmt->get_result();
            $usernames = [];
            
            while ($row = $result->fetch_assoc()) {
                $usernames[] = $row['username'];
            }
            $stmt->close();
            echo json_encode(['status' => 'success', 'data' => $usernames]);
            break;

        case 'get_teacher_by_username':
            if (!isset($_GET['username'])) {
                echo json_encode(['status' => 'error', 'message' => 'Username parametri təqdim edilməyib']);
                break;
            }
            
            $username = trim($_GET['username']);
            $stmt = $conn->prepare("SELECT * FROM muellimler_new WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $teacher = $result->fetch_assoc();
                echo json_encode(['status' => 'success', 'data' => $teacher]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Müəllim tapılmadı']);
            }
            $stmt->close();
            break;
            
        case 'update_teacher':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Yanlış sorğu metodu.']);
                break;
            }
            
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $filial_adi_json = trim($_POST['filial_adi'] ?? '[]');
            
            if ($teacher_id <= 0 || empty($username)) {
                echo json_encode(['status' => 'error', 'message' => 'Zəhmət olmasa bütün sahələri düzgün doldurun.']);
                break;
            }
            
            // Проверяем, что это валидный JSON
            $filials = json_decode($filial_adi_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['status' => 'error', 'message' => 'Filial məlumatları düzgün formatda deyil']);
                break;
            }
            
            $stmt = $conn->prepare("UPDATE muellimler_new SET filial_adi = ? WHERE id = ? AND username = ?");
            $stmt->bind_param("sis", $filial_adi_json, $teacher_id, $username);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Müəllim məlumatları yeniləndi.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Müəllim tapılmadı və ya dəyişiklik edilmədi.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Xəta baş verdi: ' . $stmt->error]);
            }
            $stmt->close();
            break;
            
        case 'remove_teacher_from_filial':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Yanlış sorğu metodu.']);
                break;
            }
            
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $filial_name = trim($_POST['filial_name'] ?? '');
            
            if ($teacher_id <= 0 || empty($username) || empty($filial_name)) {
                echo json_encode(['status' => 'error', 'message' => 'Zəhmət olmasa müəllim ID, istifadəçi adı və filial adını daxil edin.']);
                break;
            }
            
            // Получаем текущие филиалы учителя
            $stmt = $conn->prepare("SELECT filial_adi FROM muellimler_new WHERE id = ? AND username = ?");
            $stmt->bind_param("is", $teacher_id, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!$row) {
                echo json_encode(['status' => 'error', 'message' => 'Müəllim tapılmadı.']);
                break;
            }
            
            // Парсим JSON филиалов
            $current_filials = json_decode($row['filial_adi'], true);
            if (!is_array($current_filials)) {
                $current_filials = [$row['filial_adi']]; // Если не JSON, делаем массивом
            }
            
            // Удаляем филиал из массива
            $new_filials = array_filter($current_filials, function($filial) use ($filial_name) {
                return $filial !== $filial_name;
            });
            
            // Проверяем, был ли филиал в списке
            if (count($new_filials) === count($current_filials)) {
                echo json_encode(['status' => 'error', 'message' => 'Bu müəllim bu filialda deyil.']);
                break;
            }
            
            // Обновляем в базе данных
            $new_filials_json = json_encode(array_values($new_filials), JSON_UNESCAPED_UNICODE);
            $update_stmt = $conn->prepare("UPDATE muellimler_new SET filial_adi = ? WHERE id = ? AND username = ?");
            $update_stmt->bind_param("sis", $new_filials_json, $teacher_id, $username);
            
            if ($update_stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Müəllim filialdan silinmişdir.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Xəta baş verdi: ' . $update_stmt->error]);
            }
            $update_stmt->close();
            break;

            case 'get_cedvel_by_filial':
    if (!isset($_GET['teacher_id']) || !isset($_GET['username']) || !isset($_GET['filial_name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Teacher ID, username və filial adı təqdim edilməyib']);
        break;
    }
    
    $teacher_id = (int)$_GET['teacher_id'];
    $username = trim($_GET['username']);
    $filial_name = trim($_GET['filial_name']);
    
    $stmt = $conn->prepare("SELECT cedvel, username, id FROM muellimler_new WHERE id = ? AND username = ? LIMIT 1");
    $stmt->bind_param("is", $teacher_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cedvel = $row['cedvel'];
        $schedule_data = [];
        
        if (!empty($cedvel) && $cedvel !== 'null') {
            $decoded_schedule = json_decode($cedvel, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_schedule)) {
                foreach ($decoded_schedule as $entry) {
                    if (is_array($entry)) {
                        // New format: [filial, time, day, note]
                        if (count($entry) >= 4 && $entry[0] === $filial_name) {
                            $schedule_data[] = [
                                $entry[1], // time
                                $entry[2], // day
                                $entry[3] ?? '' // note
                            ];
                        }
                        // Old format: [time, day, note] - treat as default filial
                        elseif (count($entry) >= 2 && count($entry) < 4) {
                            $schedule_data[] = [
                                $entry[0], // time
                                $entry[1], // day
                                $entry[2] ?? '' // note
                            ];
                        }
                    }
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $schedule_data,
            'teacher_id' => $row['id'],
            'username' => $row['username'],
            'filial_name' => $filial_name,
            'has_schedule' => count($schedule_data) > 0
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Müəllim tapılmadı',
            'teacher_id' => $teacher_id,
            'username' => $username
        ]);
    }
    $stmt->close();
    break;

    case 'update_schedule_by_filial':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Yanlış sorğu metodu.']);
        break;
    }
    
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $filial_name = trim($_POST['filial_name'] ?? '');
    $schedule_json = $_POST['schedule'] ?? '';
    
    if ($teacher_id <= 0 || empty($username) || empty($filial_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Teacher ID, username və filial adı düzgün deyil.']);
        break;
    }
    
    $new_schedule_data = json_decode($schedule_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => 'error', 'message' => 'Cədvəl məlumatları düzgün formatda deyil']);
        break;
    }
    
    // Get existing schedule
    $stmt = $conn->prepare("SELECT cedvel FROM muellimler_new WHERE id = ? AND username = ?");
    $stmt->bind_param("is", $teacher_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $existing_schedule = [];
    if ($row && !empty($row['cedvel']) && $row['cedvel'] !== 'null') {
        $existing_schedule = json_decode($row['cedvel'], true);
        if (!is_array($existing_schedule)) {
            $existing_schedule = [];
        }
    }
    
    // Remove old entries for this filial
    $filtered_schedule = [];
    foreach ($existing_schedule as $entry) {
        if (is_array($entry)) {
            // New format: [filial, time, day, note]
            if (count($entry) >= 4 && $entry[0] !== $filial_name) {
                $filtered_schedule[] = $entry;
            }
            // Old format: [time, day, note] - keep as is
            elseif (count($entry) < 4) {
                $filtered_schedule[] = $entry;
            }
        }
    }
    
    // Add new entries with filial name
    foreach ($new_schedule_data as $entry) {
        if (is_array($entry) && count($entry) >= 2) {
            $filtered_schedule[] = [
                $filial_name,
                trim($entry[0]), // time
                trim($entry[1]), // day
                trim($entry[2] ?? '') // note
            ];
        }
    }
    
    $final_schedule_json = json_encode($filtered_schedule, JSON_UNESCAPED_UNICODE);
    
    $update_stmt = $conn->prepare("UPDATE muellimler_new SET cedvel = ? WHERE id = ? AND username = ?");
    $update_stmt->bind_param("sis", $final_schedule_json, $teacher_id, $username);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Cədvəl uğurla yeniləndi.',
            'teacher_id' => $teacher_id,
            'username' => $username,
            'filial_name' => $filial_name,
            'schedule_count' => count($new_schedule_data)
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Xəta baş verdi: ' . $update_stmt->error]);
    }
    $update_stmt->close();
    break;

    case 'get_all_schedules_by_teacher':
    if (!isset($_GET['teacher_id']) || !isset($_GET['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Teacher ID və username parametrləri təqdim edilməyib']);
        break;
    }
    
    $teacher_id = (int)$_GET['teacher_id'];
    $username = trim($_GET['username']);
    
    $stmt = $conn->prepare("SELECT cedvel, filial_adi FROM muellimler_new WHERE id = ? AND username = ? LIMIT 1");
    $stmt->bind_param("is", $teacher_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cedvel = $row['cedvel'];
        $filial_adi = $row['filial_adi'];
        
        $schedules_by_filial = [];
        $teacher_filials = [];
        
        // Parse teacher's filials
        if (!empty($filial_adi)) {
            try {
                $teacher_filials = json_decode($filial_adi, true);
                if (!is_array($teacher_filials)) {
                    $teacher_filials = [$filial_adi];
                }
            } catch (Exception $e) {
                $teacher_filials = [$filial_adi];
            }
        }
        
        // Parse schedules
        if (!empty($cedvel) && $cedvel !== 'null') {
            $decoded_schedule = json_decode($cedvel, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_schedule)) {
                foreach ($decoded_schedule as $entry) {
                    if (is_array($entry)) {
                        // New format: [filial, time, day, note]
                        if (count($entry) >= 4) {
                            $filial = $entry[0];
                            if (!isset($schedules_by_filial[$filial])) {
                                $schedules_by_filial[$filial] = [];
                            }
                            $schedules_by_filial[$filial][] = [
                                $entry[1], // time
                                $entry[2], // day
                                $entry[3] ?? '' // note
                            ];
                        }
                    }
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'schedules_by_filial' => $schedules_by_filial,
                'teacher_filials' => $teacher_filials,
                'teacher_id' => $teacher_id,
                'username' => $username
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Müəllim tapılmadı']);
    }
    $stmt->close();
    break;
    
            
        case 'get_cedvel_muellim':
            if (!isset($_GET['teacher_id']) || !isset($_GET['username'])) {
                echo json_encode(['status' => 'error', 'message' => 'Teacher ID və username parametrləri təqdim edilməyib']);
                break;
            }
            
            $teacher_id = (int)$_GET['teacher_id'];
            $username = trim($_GET['username']);
            $stmt = $conn->prepare("SELECT cedvel, username, id FROM muellimler_new WHERE id = ? AND username = ? LIMIT 1");
            $stmt->bind_param("is", $teacher_id, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $cedvel = $row['cedvel'];
                $schedule_data = [];
                
                if (!empty($cedvel) && $cedvel !== 'null') {
                    $decoded_schedule = json_decode($cedvel, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_schedule)) {
                        foreach ($decoded_schedule as $entry) {
                            if (is_array($entry) && count($entry) >= 2 && !empty($entry[0]) && !empty($entry[1])) {
                                $schedule_data[] = [
                                    $entry[0], // time
                                    $entry[1], // day
                                    $entry[2] ?? '' // note
                                ];
                            }
                        }
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $schedule_data,
                    'teacher_id' => $row['id'],
                    'username' => $row['username'],
                    'raw_cedvel' => $cedvel
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Müəllim tapılmadı',
                    'teacher_id' => $teacher_id,
                    'username' => $username
                ]);
            }
            $stmt->close();
            break;
            
        case 'update_schedule':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Yanlış sorğu metodu.']);
                break;
            }
            
            $teacher_id = (int)($_POST['teacher_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $schedule_json = $_POST['schedule'] ?? '';
            
            if ($teacher_id <= 0 || empty($username)) {
                echo json_encode(['status' => 'error', 'message' => 'Teacher ID və username parametrləri düzgün deyil.']);
                break;
            }
            
            $schedule_data = json_decode($schedule_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['status' => 'error', 'message' => 'Cədvəl məlumatları düzgün formatda deyil']);
                break;
            }
            
            $clean_schedule = [];
            if (is_array($schedule_data)) {
                foreach ($schedule_data as $entry) {
                    if (is_array($entry) && count($entry) >= 2) {
                        $clean_schedule[] = [
                            trim($entry[0]), // time
                            trim($entry[1]), // day
                            trim($entry[2] ?? '') // note
                        ];
                    }
                }
            }
            
            $final_schedule_json = json_encode($clean_schedule, JSON_UNESCAPED_UNICODE);
            
            $stmt = $conn->prepare("UPDATE muellimler_new SET cedvel = ? WHERE id = ? AND username = ?");
            $stmt->bind_param("sis", $final_schedule_json, $teacher_id, $username);
            
            if ($stmt->execute()) {
                $affected_rows = $stmt->affected_rows;
                if ($affected_rows > 0) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Cədvəl uğurla yeniləndi.',
                        'teacher_id' => $teacher_id,
                        'username' => $username,
                        'schedule_count' => count($clean_schedule)
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'warning',
                        'message' => 'Cədvəl dəyişdirilmədi (eyni məlumat).',
                        'teacher_id' => $teacher_id,
                        'username' => $username
                    ]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Xəta baş verdi: ' . $stmt->error]);
            }
            $stmt->close();
            break;
            
        case 'get_schedule':
            if (!isset($_GET['teacher_id']) || !isset($_GET['username'])) {
                echo json_encode(['status' => 'error', 'message' => 'Teacher ID və username parametrləri təqdim edilməyib']);
                break;
            }
            
            $teacher_id = (int)$_GET['teacher_id'];
            $username = trim($_GET['username']);
            
            $stmt = $conn->prepare("SELECT cedvel FROM muellimler_new WHERE id = ? AND username = ? LIMIT 1");
            $stmt->bind_param("is", $teacher_id, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode(['status' => 'success', 'data' => $row['cedvel']]);
            } else {
                echo json_encode(['status' => 'success', 'data' => null]);
            }
            $stmt->close();
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Yanlış əməliyyat: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Filial Operations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server xətası baş verdi']);
} catch (mysqli_sql_exception $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası baş verdi']);
}

if (isset($conn)) {
    $conn->close();
}
?>