<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn)) {
    include('../db.php');
}
app_require_auth_api($conn);

header('Content-Type: application/json; charset=utf-8');

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

function resolve_subject_name(string $fenn_id): string {
    $map = [
        '1' => 'Riyaziyyat',
        '2' => 'Fizika',
        '3' => 'Kimya',
        '4' => 'Biologiya',
        '5' => 'Tarix',
        '6' => 'Ədəbiyyat',
    ];

    if (isset($map[$fenn_id])) {
        return $map[$fenn_id];
    }

    return $fenn_id;
}

function resolve_lesson_payload(mysqli $conn, array $input): array {
    $fenn_id = trim((string) ($input['fenn'] ?? ''));
    $sinif_id = (int) ($input['sinif'] ?? 0);
    $muellim_id = (int) ($input['muellim_id'] ?? 0);
    $otaq_id = (int) ($input['otaq'] ?? 0);
    $tarix = trim((string) ($input['tarix'] ?? ''));
    $start_time = trim((string) ($input['start_time'] ?? ''));
    $end_time = trim((string) ($input['end_time'] ?? ''));
    $movzu = trim((string) ($input['movzu'] ?? ''));
    $tesvir = trim((string) ($input['tesvir'] ?? ''));
    $status = trim((string) ($input['status'] ?? 'Planlaşdırılıb'));
    $materiallar = trim((string) ($input['materiallar'] ?? ''));
    $muellim = trim((string) ($input['muellim'] ?? ''));

    $errors = [];
    if ($fenn_id === '') {
        $errors[] = 'Fənn seçilməlidir';
    }
    if ($sinif_id <= 0) {
        $errors[] = 'Sinif seçilməlidir';
    }
    if ($muellim_id <= 0) {
        $errors[] = 'Müəllim seçilməlidir';
    }
    if ($otaq_id <= 0) {
        $errors[] = 'Otaq seçilməlidir';
    }
    if ($tarix === '') {
        $errors[] = 'Tarix daxil edilməlidir';
    }
    if ($start_time === '') {
        $errors[] = 'Başlama vaxtı daxil edilməlidir';
    }
    if ($end_time === '') {
        $errors[] = 'Bitmə vaxtı daxil edilməlidir';
    }
    if ($movzu === '') {
        $errors[] = 'Mövzu daxil edilməlidir';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }

    $fenn_text = resolve_subject_name($fenn_id);

    $sinif_text = '';
    $sinif_stmt = $conn->prepare('SELECT sinif_number FROM sinifler WHERE id = ?');
    $sinif_stmt->bind_param('i', $sinif_id);
    $sinif_stmt->execute();
    $sinif_result = $sinif_stmt->get_result();
    if ($sinif_result && $sinif_result->num_rows > 0) {
        $sinif_text = (string) $sinif_result->fetch_assoc()['sinif_number'];
    }
    $sinif_stmt->close();

    $otaq_text = '';
    $sagird_sayi = '0';
    $otaq_stmt = $conn->prepare('SELECT otaq_number, tutum FROM otaqlar WHERE id = ?');
    $otaq_stmt->bind_param('i', $otaq_id);
    $otaq_stmt->execute();
    $otaq_result = $otaq_stmt->get_result();
    if ($otaq_result && $otaq_result->num_rows > 0) {
        $otaq_data = $otaq_result->fetch_assoc();
        $otaq_text = (string) $otaq_data['otaq_number'];
        if (isset($otaq_data['tutum']) && $otaq_data['tutum'] !== null) {
            $sagird_sayi = (string) $otaq_data['tutum'];
        }
    }
    $otaq_stmt->close();

    if ($muellim === '') {
        $muellim_stmt = $conn->prepare('SELECT username FROM muellimler_new WHERE id = ?');
        $muellim_stmt->bind_param('i', $muellim_id);
        $muellim_stmt->execute();
        $muellim_result = $muellim_stmt->get_result();
        if ($muellim_result && $muellim_result->num_rows > 0) {
            $muellim = (string) $muellim_result->fetch_assoc()['username'];
        }
        $muellim_stmt->close();
    }

    return [
        'success' => true,
        'data' => [
            'fenn' => $fenn_text,
            'sinif' => $sinif_text,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'otaq' => $otaq_text,
            'muellim' => $muellim,
            'sagird_sayi' => $sagird_sayi,
            'status' => $status,
            'movzu' => $movzu,
            'tesvir' => $tesvir,
            'materiallar' => $materiallar,
            'tarix' => $tarix,
            'muellim_id' => $muellim_id,
            'company_id' => isset($_SESSION['company_id']) ? (int) $_SESSION['company_id'] : 0,
        ],
    ];
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Add new lesson
    if ($action == 'add_lesson') {
        $payload = resolve_lesson_payload($conn, $_POST);
        if (empty($payload['success'])) {
            echo json_encode($payload);
            exit;
        }

        $lesson = $payload['data'];
        $stmt = $conn->prepare("INSERT INTO dersler (company_id, fenn, sinif, start_time, end_time, otaq, muellim, sagird_sayi, status, movzu, active_status, tesvir, materiallar, tarix, muellim_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, NOW())");
        $stmt->bind_param(
            "isssssssissssi",
            $lesson['company_id'],
            $lesson['fenn'],
            $lesson['sinif'],
            $lesson['start_time'],
            $lesson['end_time'],
            $lesson['otaq'],
            $lesson['muellim'],
            $lesson['sagird_sayi'],
            $lesson['status'],
            $lesson['movzu'],
            $lesson['tesvir'],
            $lesson['materiallar'],
            $lesson['tarix'],
            $lesson['muellim_id']
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dərs uğurla əlavə edildi', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
        $stmt->close();
    }


        // Edit lesson
    else if ($action == 'edit_lesson') {
        $id = (int) $_POST['lessonId'];
        $fenn = sanitize_input($_POST['fenn']);        // Now expecting text (e.g., "Riyaziyyat")
        $sinif = sanitize_input($_POST['sinif']);      // Now expecting text (e.g., "10A")
        $start_time = sanitize_input($_POST['start_time']);
        $end_time = sanitize_input($_POST['end_time']);
        $otaq = sanitize_input($_POST['otaq']);        // Now expecting text (e.g., "Room 101")
        $muellim = sanitize_input($_POST['muellim']);
        $sagird_sayi = isset($_POST['sagird_sayi']) ? (int) $_POST['sagird_sayi'] : 0;
        $status = sanitize_input($_POST['status']);
        $movzu = isset($_POST['movzu']) ? sanitize_input($_POST['movzu']) : '';
        $tesvir = isset($_POST['tesvir']) ? sanitize_input($_POST['tesvir']) : '';
        $materiallar = isset($_POST['materiallar']) ? sanitize_input($_POST['materiallar']) : '';
        $tarix = sanitize_input($_POST['tarix']);
        $muellim_id = (int) $_POST['muellim_id'];
        
        // Validate required fields
        if (empty($id) || empty($fenn) || empty($sinif) || empty($start_time) || 
            empty($end_time) || empty($otaq) || empty($muellim) || empty($status) || 
            empty($tarix) || empty($muellim_id)) {
            echo json_encode(['success' => false, 'message' => 'Bütün tələb olunan sahələri doldurun']);
            exit;
        }

        // Validate status value
        $valid_statuses = ['Planlaşdırılıb', 'Aktiv', 'Dəyişiklik var', 'Ləğv edilib'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Yanlış status dəyəri']);
            exit;
        }

        // Validate fenn (subjects) - optional, adjust as needed
        $valid_subjects = ['Riyaziyyat', 'Fizika', 'Kimya', 'Biologiya', 'Tarix', 'Ədəbiyyat'];
        if (!in_array($fenn, $valid_subjects)) {
            echo json_encode(['success' => false, 'message' => 'Yanlış fənn dəyəri']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE dersler SET fenn = ?, sinif = ?, start_time = ?, end_time = ?, otaq = ?, muellim = ?, sagird_sayi = ?, status = ?, movzu = ?, tesvir = ?, materiallar = ?, tarix = ?, muellim_id = ? WHERE id = ?");
        $stmt->bind_param("ssssssisssssii", $fenn, $sinif, $start_time, $end_time, $otaq, $muellim, $sagird_sayi, $status, $movzu, $tesvir, $materiallar, $tarix, $muellim_id, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dərs uğurla yeniləndi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
    }


    if ($action == 'delete_lesson') {
        $id = (int) $_POST['lessonId'];
        
        // Ensure $id is valid
        if (!is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
            exit;
        }
    
        $stmt = $conn->prepare("DELETE FROM dersler WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dərs uğurla silindi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
    }

    
    // Get lesson details
    else if ($action == 'get_lesson') {
        $id = (int) $_POST['lessonId'];

        $stmt = $conn->prepare("SELECT * FROM dersler WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $lesson = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $lesson]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dərs tapılmadı']);
        }
    }

    // Get all lessons
    else if ($action == 'get_lessons') {
        $sql = "SELECT d.*, m.username as muellim_adi 
                FROM dersler d 
                LEFT JOIN muellimler_new m ON d.muellim_id = m.id 
                WHERE d.active_status = 1 
                ORDER BY d.tarix ASC, d.start_time ASC";
        $result = $conn->query($sql);
        
        $lessons = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lessons[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $lessons]);
        } else {
            echo json_encode(['success' => true, 'data' => []]);
        }
    }

    // Get lessons by teacher
    else if ($action == 'get_lessons_by_teacher') {
        $muellim_id = (int) $_POST['teacherId'];

        $stmt = $conn->prepare("SELECT d.*, m.username as muellim_adi 
                FROM dersler d 
                LEFT JOIN muellimler_new m ON d.muellim_id = m.id 
                WHERE d.muellim_id = ? AND d.active_status = 1 
                ORDER BY d.tarix ASC, d.start_time ASC");
        $stmt->bind_param("i", $muellim_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $lessons = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lessons[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $lessons]);
        } else {
            echo json_encode(['success' => true, 'data' => []]);
        }
    }

    // Get lessons by class
    else if ($action == 'get_lessons_by_class') {
        $sinif = sanitize_input($_POST['class']);

        $stmt = $conn->prepare("SELECT d.*, m.username as muellim_adi 
                FROM dersler d 
                LEFT JOIN muellimler_new m ON d.muellim_id = m.id 
                WHERE d.sinif = ? AND d.active_status = 1 
                ORDER BY d.tarix ASC, d.start_time ASC");
        $stmt->bind_param("s", $sinif);
        $stmt->execute();
        $result = $stmt->get_result();

        $lessons = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $lessons[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $lessons]);
        } else {
            echo json_encode(['success' => true, 'data' => []]);
        }
    }

    else if ($action == 'add_room') {
        $otaq_number = trim((string) ($_POST['otaq_number'] ?? ''));
        $tutum = (int) ($_POST['tutum'] ?? 0);

        if ($otaq_number === '' || $tutum <= 0) {
            echo json_encode(['success' => false, 'message' => 'Otaq nömrəsi və tutum doldurulmalıdır']);
            exit;
        }

        $check = $conn->prepare('SELECT id FROM otaqlar WHERE otaq_number = ? LIMIT 1');
        $check->bind_param('s', $otaq_number);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            echo json_encode([
                'success' => true,
                'message' => 'Bu otaq artıq mövcuddur',
                'data' => ['id' => (int) $existing['id'], 'otaq_number' => $otaq_number, 'tutum' => $tutum],
            ]);
            exit;
        }

        $stmt = $conn->prepare('INSERT INTO otaqlar (otaq_number, tutum) VALUES (?, ?)');
        $stmt->bind_param('si', $otaq_number, $tutum);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Otaq uğurla əlavə edildi',
                'data' => ['id' => (int) $stmt->insert_id, 'otaq_number' => $otaq_number, 'tutum' => $tutum],
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
        $stmt->close();
    }

    else if ($action == 'add_class') {
        $sinif_number = trim((string) ($_POST['sinif_number'] ?? ''));
        $tutum = (int) ($_POST['tutum'] ?? 0);

        if ($sinif_number === '' || $tutum <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sinif nömrəsi və tutum doldurulmalıdır']);
            exit;
        }

        $check = $conn->prepare('SELECT id FROM sinifler WHERE sinif_number = ? LIMIT 1');
        $check->bind_param('s', $sinif_number);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            echo json_encode([
                'success' => true,
                'message' => 'Bu sinif artıq mövcuddur',
                'data' => ['id' => (int) $existing['id'], 'sinif_number' => $sinif_number, 'tutum' => $tutum],
            ]);
            exit;
        }

        $stmt = $conn->prepare('INSERT INTO sinifler (sinif_number, tutum) VALUES (?, ?)');
        $stmt->bind_param('si', $sinif_number, $tutum);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Sinif uğurla əlavə edildi',
                'data' => ['id' => (int) $stmt->insert_id, 'sinif_number' => $sinif_number, 'tutum' => $tutum],
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error]);
        }
        $stmt->close();
    }
    
    // Only close the connection if this file is called directly
    if (!isset($dontCloseConnection)) {
        $conn->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stat_details') {
    $type = trim((string) ($_GET['type'] ?? ''));
    $columns = [];
    $query = '';

    switch ($type) {
        case 'lessons':
            $query = "SELECT d.id, d.fenn, d.sinif, d.tarix,
                             CONCAT(d.start_time, ' - ', d.end_time) as vaxt,
                             d.otaq, d.status,
                             COALESCE(m.username, d.muellim) as muellim_adi
                      FROM dersler d
                      LEFT JOIN muellimler_new m ON d.muellim_id = m.id
                      WHERE d.active_status = 1
                      ORDER BY d.tarix DESC, d.start_time ASC";
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'fenn', 'label' => 'Fənn'],
                ['key' => 'sinif', 'label' => 'Sinif'],
                ['key' => 'muellim_adi', 'label' => 'Müəllim'],
                ['key' => 'tarix', 'label' => 'Tarix'],
                ['key' => 'vaxt', 'label' => 'Vaxt'],
                ['key' => 'otaq', 'label' => 'Otaq'],
                ['key' => 'status', 'label' => 'Status'],
            ];
            break;

        case 'teachers':
            $query = "SELECT id, username, tehsil_ve_ixtisas, fenn, active_status
                      FROM muellimler_new
                      WHERE active_status = 'active'
                      ORDER BY username ASC";
            $columns = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'username', 'label' => 'Ad'],
                ['key' => 'tehsil_ve_ixtisas', 'label' => 'İxtisas'],
                ['key' => 'fenn', 'label' => 'Fənn'],
                ['key' => 'status_label', 'label' => 'Status'],
            ];
            break;

        case 'subjects':
            $query = "SELECT fenn as fenn_adi, COUNT(*) as ders_sayi
                      FROM dersler
                      WHERE active_status = 1 AND fenn IS NOT NULL AND fenn != ''
                      GROUP BY fenn
                      ORDER BY fenn ASC";
            $columns = [
                ['key' => 'fenn_adi', 'label' => 'Fənn'],
                ['key' => 'ders_sayi', 'label' => 'Dərs sayı'],
            ];
            break;

        case 'rooms':
            $query = "SELECT otaq as otaq_adi, COUNT(*) as ders_sayi
                      FROM dersler
                      WHERE active_status = 1 AND otaq IS NOT NULL AND otaq != ''
                      GROUP BY otaq
                      ORDER BY otaq ASC";
            $columns = [
                ['key' => 'otaq_adi', 'label' => 'Otaq'],
                ['key' => 'ders_sayi', 'label' => 'Dərs sayı'],
            ];
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Yanlış statistik tipi.']);
            exit;
    }

    $rows = [];
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Məlumat bazası xətası: ' . mysqli_error($conn)]);
        exit;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        if ($type === 'teachers') {
            $row['status_label'] = (($row['active_status'] ?? '') === 'active') ? 'Aktiv' : 'Passiv';
        }

        foreach ($row as $key => $value) {
            if ($value === null || $value === '') {
                $row[$key] = '-';
            }
        }

        $rows[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'type' => $type,
        'columns' => $columns,
        'data' => $rows,
    ]);

    if (!isset($dontCloseConnection)) {
        $conn->close();
    }
    exit;
}
?>