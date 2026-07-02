<?php
// This file handles all backend operations for the dersler (lessons) system
// Including CRUD operations, API endpoints, and data processing

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include('../db.php');
app_require_auth_api($conn);

// Set character set
$conn->set_charset("utf8mb4");

// Get the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Process the action
switch ($action) {
    case 'list':
        // List all lessons
        listLessons();
        break;
    case 'view':
        // View a specific lesson
        viewLesson();
        break;
    case 'add':
        // Add a new lesson
        addLesson();
        break;
    case 'edit':
        // Edit an existing lesson
        editLesson();
        break;
    case 'delete':
        // Delete a lesson
        deleteLesson();
        break;
    case 'add_class':
        // Add a new class
        addClass();
        break;
    case 'add_room':
        // Add a new room
        addRoom();
        break;
    case 'today':
        // Get today's lessons
        getTodayLessons();
        break;
    case 'calendar':
        // Get calendar data
        getCalendarData();
        break;
    default:
        // Handle invalid action
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // If it's a direct access to this file without action
            header("Location: dersler.php");
            exit();
        } else {
            // If it's an AJAX request with invalid action
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
        break;
}

// Function to list all lessons
function listLessons() {
    global $conn;
    
    header('Content-Type: application/json');
    
    $query = "SELECT id, fenn, sinif, muellim, tarix, otaq, sagird_sayi, status, movzu, tesvir, materiallar, 
                     DATE_FORMAT(start_time, '%H:%i') AS start_time,
                     DATE_FORMAT(end_time, '%H:%i') AS end_time 
              FROM dersler 
              WHERE active_status = 1
              ORDER BY id DESC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Database query error: ' . mysqli_error($conn)]);
        return;
    }
    
    $lessons = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lessons[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'data' => $lessons]);
}

// Function to view a specific lesson
function viewLesson() {
    global $conn;
    
    header('Content-Type: application/json');
    
    if (!isset($_GET['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Lesson ID is required']);
        return;
    }
    
    $id = (int) $_GET['id'];

    $query = "SELECT id, fenn, sinif, muellim, tarix, otaq, sagird_sayi, status, movzu, tesvir, materiallar, 
                     DATE_FORMAT(start_time, '%H:%i') AS start_time,
                     DATE_FORMAT(end_time, '%H:%i') AS end_time 
              FROM dersler 
              WHERE id = ? AND active_status = 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Database query error: ' . mysqli_error($conn)]);
        return;
    }
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Lesson not found']);
        return;
    }
    
    $lesson = mysqli_fetch_assoc($result);
    
    echo json_encode(['status' => 'success', 'data' => $lesson]);
}

// Function to add a new lesson
function addLesson() {
    global $conn;
    
    // Check for user authentication
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: Login.php");
        exit();
    }
    
    // Get form data
    $fenn_id = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $sinif_id = isset($_POST['class']) ? (int) $_POST['class'] : 0;
    $muellim_id = isset($_POST['teacher']) ? (int) $_POST['teacher'] : 0;
    $otaq_id = isset($_POST['room']) ? (int) $_POST['room'] : 0;
    $tarix = isset($_POST['date']) ? trim($_POST['date']) : '';
    $start_time = isset($_POST['startTime']) ? trim($_POST['startTime']) : '';
    $end_time = isset($_POST['endTime']) ? trim($_POST['endTime']) : '';
    $movzu = isset($_POST['topic']) ? trim($_POST['topic']) : '';
    $tesvir = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Get status from the form or use default
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Planlaşdırılıb';
    $active_status = 1; // Default active status
    
    // Handle file uploads for materials
    $materiallar = '';
    if (isset($_FILES['materials']) && $_FILES['materials']['error'][0] != 4) {
        $fileCount = count($_FILES['materials']['name']);
        $uploadedFiles = [];
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $_FILES['materials']['name'][$i];
            $fileTmpName = $_FILES['materials']['tmp_name'][$i];
            $fileSize = $_FILES['materials']['size'][$i];
            $fileError = $_FILES['materials']['error'][$i];
            
            if ($fileError === 0) {
                // Create uploads directory if it doesn't exist
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                $fileDestination = 'uploads/' . time() . '_' . $fileName;
                move_uploaded_file($fileTmpName, $fileDestination);
                $uploadedFiles[] = $fileDestination;
            }
        }
        
        if (!empty($uploadedFiles)) {
            $materiallar = json_encode($uploadedFiles);
        }
    }
    
    // Get fenn (subject) name based on the selected option
    $fenn_text = '';
    switch ($fenn_id) {
        case '1':
            $fenn_text = 'Riyaziyyat';
            break;
        case '2':
            $fenn_text = 'Fizika';
            break;
        case '3':
            $fenn_text = 'Kimya';
            break;
        case '4':
            $fenn_text = 'Biologiya';
            break;
        case '5':
            $fenn_text = 'Tarix';
            break;
        case '6':
            $fenn_text = 'Ədəbiyyat';
            break;
        default:
            $fenn_text = 'Bilinməyən';
    }
    
    // Get sinif (class) name/number
    $sinif_text = '';
    $sinif_stmt = $conn->prepare("SELECT sinif_number FROM sinifler WHERE id = ?");
    $sinif_stmt->bind_param("i", $sinif_id);
    $sinif_stmt->execute();
    $sinif_result = $sinif_stmt->get_result();
    if ($sinif_result && mysqli_num_rows($sinif_result) > 0) {
        $sinif_text = mysqli_fetch_assoc($sinif_result)['sinif_number'];
    }
    
    // Get otaq (room) number and capacity
    $otaq_text = '';
    $sagird_sayi = '0';

    $otaq_stmt = $conn->prepare("SELECT otaq_number, tutum FROM otaqlar WHERE id = ?");
    $otaq_stmt->bind_param("i", $otaq_id);
    $otaq_stmt->execute();
    $otaq_result = $otaq_stmt->get_result();
    
    if ($otaq_result && mysqli_num_rows($otaq_result) > 0) {
        $otaq_data = mysqli_fetch_assoc($otaq_result);
        $otaq_text = $otaq_data['otaq_number'];
        
        // Make sure tutum is not null and convert to string
        if (isset($otaq_data['tutum']) && $otaq_data['tutum'] !== null) {
            $sagird_sayi = (string)$otaq_data['tutum']; // Convert to string explicitly
        }
    }
    
    // Get muellim (teacher) username and extract only the first name
    $muellim_text = '';
    $muellim_stmt = $conn->prepare("SELECT username FROM muellimler_new WHERE id = ?");
    $muellim_stmt->bind_param("i", $muellim_id);
    $muellim_stmt->execute();
    $muellim_result = $muellim_stmt->get_result();
    if ($muellim_result && mysqli_num_rows($muellim_result) > 0) {
        $muellim_full_name = mysqli_fetch_assoc($muellim_result)['username'];
        // Extract only the first name (everything before the first space)
        $name_parts = explode(' ', $muellim_full_name, 2);
        $muellim_text = $name_parts[0]; // Get only the first name
    }
    
    // Validate required fields
    $errors = [];
    if (empty($fenn_id)) $errors[] = "Fənn seçilməlidir";
    if (empty($sinif_id)) $errors[] = "Sinif seçilməlidir";
    if (empty($muellim_id)) $errors[] = "Müəllim seçilməlidir";
    if (empty($otaq_id)) $errors[] = "Otaq seçilməlidir";
    if (empty($tarix)) $errors[] = "Tarix daxil edilməlidir";
    if (empty($start_time)) $errors[] = "Başlama vaxtı daxil edilməlidir";
    if (empty($end_time)) $errors[] = "Bitmə vaxtı daxil edilməlidir";
    if (empty($movzu)) $errors[] = "Mövzu daxil edilməlidir";
    
    // If there are validation errors
    if (!empty($errors)) {
        $_SESSION['error_message'] = "Xəta: " . implode(", ", $errors);
        header("Location: Dərslər.php");
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO dersler (fenn, sinif, start_time, end_time, otaq, muellim, sagird_sayi, status, movzu, active_status, tesvir, materiallar, tarix, muellim_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssississsi", $fenn_text, $sinif_text, $start_time, $end_time, $otaq_text, $muellim_text, $sagird_sayi, $status, $movzu, $active_status, $tesvir, $materiallar, $tarix, $muellim_id);

    if ($stmt->execute()) {
        // Success message
        $_SESSION['success_message'] = "Dərs uğurla əlavə edildi!";
        header("Location: ../Dərslər.php");
        exit();
    } else {
        // Error message
        $error = mysqli_error($conn);
        $_SESSION['error_message'] = "Xəta baş verdi: " . $error;
        header("Location: ../Dərslər.php");
        exit();
    }
}

// Function to edit an existing lesson
function editLesson() {
    global $conn;
    
    header('Content-Type: application/json');
    
    $lessonId = isset($_POST['lessonId']) ? (int) $_POST['lessonId'] : null;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : null;
    $class = isset($_POST['class']) ? (int) $_POST['class'] : null;
    $teacher = isset($_POST['teacher']) ? (int) $_POST['teacher'] : null;
    $room = isset($_POST['room']) ? (int) $_POST['room'] : null;
    $date = isset($_POST['date']) ? trim($_POST['date']) : null;
    $startTime = isset($_POST['startTime']) ? trim($_POST['startTime']) : null;
    $endTime = isset($_POST['endTime']) ? trim($_POST['endTime']) : null;
    $topic = isset($_POST['topic']) ? trim($_POST['topic']) : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $status = isset($_POST['status']) ? trim($_POST['status']) : null;

    // Validate required fields
    if (!$lessonId || !$subject || !$class || !$teacher || !$room || !$date || !$startTime || !$endTime || !$topic || !$status) {
        echo json_encode(['success' => false, 'error' => 'Bütün tələb olunan sahələr doldurulmalıdır']);
        exit;
    }

    // Map subject ID to subject name
    $subjectMap = [
        "1" => "Riyaziyyat",
        "2" => "Fizika",
        "3" => "Kimya",
        "4" => "Biologiya",
        "5" => "Tarix",
        "6" => "Ədəbiyyat"
    ];
    $fenn = $subjectMap[$subject] ?? null;

    // Fetch class name from sinifler table
    $classStmt = $conn->prepare("SELECT sinif_number FROM sinifler WHERE id = ?");
    $classStmt->bind_param("i", $class);
    $classStmt->execute();
    $classResult = $classStmt->get_result();
    $sinif = $classResult && mysqli_num_rows($classResult) > 0 ? mysqli_fetch_assoc($classResult)['sinif_number'] : null;

    // Fetch teacher name from muellimler table
    $teacherStmt = $conn->prepare("SELECT username FROM muellimler_new WHERE id = ?");
    $teacherStmt->bind_param("i", $teacher);
    $teacherStmt->execute();
    $teacherResult = $teacherStmt->get_result();
    $muellim = $teacherResult && mysqli_num_rows($teacherResult) > 0 ? mysqli_fetch_assoc($teacherResult)['username'] : null;

    // Fetch room name from otaqlar table
    $roomStmt = $conn->prepare("SELECT otaq_number FROM otaqlar WHERE id = ?");
    $roomStmt->bind_param("i", $room);
    $roomStmt->execute();
    $roomResult = $roomStmt->get_result();
    $otaq = $roomResult && mysqli_num_rows($roomResult) > 0 ? mysqli_fetch_assoc($roomResult)['otaq_number'] : null;

    if (!$fenn || !$sinif || !$muellim || !$otaq) {
        echo json_encode(['success' => false, 'error' => 'Fənn, sinif, müəllim və ya otaq tapılmadı']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE dersler SET fenn = ?, sinif = ?, muellim = ?, otaq = ?, tarix = ?, start_time = ?, end_time = ?, movzu = ?, tesvir = ?, status = ? WHERE id = ? AND active_status = 1");
    $stmt->bind_param("ssssssssssi", $fenn, $sinif, $muellim, $otaq, $date, $startTime, $endTime, $topic, $description, $status, $lessonId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Dərs yenilənərkən xəta baş verdi: ' . mysqli_error($conn)]);
    }
}

// Function to delete a lesson
function deleteLesson() {
    global $conn;
    
    header('Content-Type: application/json');
    
    // Read the raw POST data
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);

    if (isset($data['id'])) {
        $lessonId = (int) $data['id'];

        $stmt = $conn->prepare("DELETE FROM dersler WHERE id = ?");
        $stmt->bind_param("i", $lessonId);

        if ($stmt->execute()) {
            // Check if any rows were affected to confirm the deletion
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Dərs tapılmadı və ya artıq silinib']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Dərs silinərkən xəta baş verdi: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Yanlış sorğu metodu və ya ID təqdim edilməyib']);
    }
}

// Function to add a new class
function addClass() {
    global $conn;
    
    $classNumber = isset($_POST['classNumber']) ? $_POST['classNumber'] : '';
    $classCapacity = isset($_POST['classCapacity']) ? $_POST['classCapacity'] : '';

    // Validate inputs
    if (!empty($classNumber) && !empty($classCapacity)) {
        // Insert into the database
        $sql = "INSERT INTO sinifler (sinif_number, tutum) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $classNumber, $classCapacity);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Sinif uğurla əlavə edildi!";
        } else {
            $_SESSION['error_message'] = "Xəta baş verdi: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Bütün sahələri doldurun!";
    }
    
    header("Location: dersler.php");
    exit();
}

// Function to add a new room
function addRoom() {
    global $conn;
    
    $roomNumber = isset($_POST['otaq_number']) ? $_POST['otaq_number'] : '';
    $roomCapacity = isset($_POST['tutum']) ? $_POST['tutum'] : '';

    // Validate inputs
    if (!empty($roomNumber) && !empty($roomCapacity)) {
        // Insert into the database
        $sql = "INSERT INTO otaqlar (otaq_number, tutum) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $roomNumber, $roomCapacity);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Otaq uğurla əlavə edildi!";
        } else {
            $_SESSION['error_message'] = "Xəta baş verdi: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Bütün sahələri doldurun!";
    }
    
    header("Location: dersler.php");
    exit();
}

// Function to get today's lessons
function getTodayLessons() {
    global $conn;
    
    header('Content-Type: application/json');
    
    // Get the current date
    $currentDate = date('Y-m-d');

    // Fetch lessons for today
    $query = "SELECT id, fenn AS subject, sinif AS class, muellim AS teacher, tarix, otaq AS room, sagird_sayi, status, movzu, tesvir, materiallar AS materials, 
                     DATE_FORMAT(start_time, '%H:%i') AS startTime,
                     DATE_FORMAT(end_time, '%H:%i') AS endTime 
              FROM dersler 
              WHERE active_status = 1 
              AND tarix = '$currentDate'
              ORDER BY start_time";

    $result = mysqli_query($conn, $query);

    $lessons = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Split materials into an array if they exist
            $row['materials'] = !empty($row['materials']) ? explode(',', $row['materials']) : [];
            $lessons[] = $row;
        }
    }

    echo json_encode($lessons);
}

// Function to get calendar data
function getCalendarData() {
    global $conn;
    
    header('Content-Type: application/json');
    
    if (isset($_GET['il']) && isset($_GET['ay'])) {
        $year = mysqli_real_escape_string($conn, $_GET['il']);
        $month = mysqli_real_escape_string($conn, $_GET['ay']);

        // Validate year and month
        if (!is_numeric($year) || !is_numeric($month) || $month < 1 || $month > 12) {
            echo json_encode(['error' => 'Yanlış il və ya ay']);
            exit;
        }

        // Calculate the start and end dates for the month
        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate)); // Last day of the month

        // Fetch lessons for the given month
        $query = "SELECT id, fenn, sinif, muellim, tarix, otaq, sagird_sayi, status, movzu, tesvir, materiallar, 
                         DATE_FORMAT(start_time, '%H:%i') AS start_time,
                         DATE_FORMAT(end_time, '%H:%i') AS end_time 
                  FROM dersler 
                  WHERE active_status = 1 
                  AND tarix BETWEEN '$startDate' AND '$endDate'
                  ORDER BY tarix, start_time";

        $result = mysqli_query($conn, $query);

        $events = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Split materials into an array if they exist
                $row['materiallar'] = !empty($row['materiallar']) ? explode(',', $row['materiallar']) : [];
                $events[] = $row;
            }
        }

        echo json_encode($events);
    } else {
        echo json_encode(['error' => 'İl və ya ay parametri təqdim edilməyib']);
    }
}

// Close the database connection
$conn->close();
?>