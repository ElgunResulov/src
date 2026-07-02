<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../../db.php';

// Set character set for proper UTF-8 handling
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
}

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Dynamically generate the base URL for photos
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/telebeler/';

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

function handleGetRequest() {
    global $conn, $base_url;
    
    // Case 1: Get individual student info by ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        getStudentInfo($_GET['id']);
        return;
    }
    
    // Case 2: Get all students
    if (isset($_GET['all'])) {
        getAllStudents();
        return;
    }
    
    // Case 3: Get students by group
    if (isset($_GET['group']) && !empty($_GET['group'])) {
        getStudentsByGroup($_GET['group']);
        return;
    }
    
    // Case 4: Get students by teacher (muellim)
    if (isset($_GET['muellim']) && !empty($_GET['muellim'])) {
        getStudentsByMuellim($_GET['muellim']);
        return;
    }
    
    // Case 5: Get all groups (default)
    getGroups();
}

function handlePostRequest() {
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (!$data || !isset($data['studentId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Student ID is required'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    updateStudentAttendance($data);
}

function getGroups() {
    global $conn;
    
    try {
        // Get distinct groups from telebeler table
        $query = "SELECT DISTINCT sinif FROM telebeler WHERE sinif IS NOT NULL AND sinif != '' AND sinif != '0' ORDER BY sinif";
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception('Database query failed: ' . $conn->error);
        }
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty(trim($row['sinif']))) {
                $groups[] = trim($row['sinif']);
            }
        }
        
        echo json_encode($groups, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching groups: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function getAllStudents() {
    global $conn;
    
    try {
        // Try different possible column names for student name
        $possible_name_columns = ['username', 'name', 'ad', 'telebe_adi', 'student_name'];
        $name_column = 'username'; // default
        
        // Check which column exists
        foreach ($possible_name_columns as $col) {
            $check_query = "SHOW COLUMNS FROM telebeler LIKE '$col'";
            $check_result = $conn->query($check_query);
            if ($check_result && $check_result->num_rows > 0) {
                $name_column = $col;
                break;
            }
        }
        
        $query = "SELECT id, $name_column as username, sinif, 
                         COALESCE(status, 'Istirak_edir') as status, 
                         COALESCE(qeyd, '') as qeyd, 
                         COALESCE(muellim_adi, '[]') as muellim_adi 
                  FROM telebeler 
                  WHERE $name_column IS NOT NULL AND $name_column != ''
                  ORDER BY sinif, $name_column";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception('Database query failed: ' . $conn->error);
        }
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            // Parse muellim_adi JSON field
            $muellim_adi_array = [];
            if (!empty($row['muellim_adi']) && $row['muellim_adi'] !== '[]') {
                $decoded = json_decode($row['muellim_adi'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $muellim_adi_array = $decoded;
                }
            }
            
            $students[] = [
                'id' => (int)$row['id'],
                'username' => !empty($row['username']) ? $row['username'] : 'Adsız tələbə',
                'sinif' => $row['sinif'] ?? '',
                'status' => $row['status'] ?? 'Istirak_edir',
                'qeyd' => $row['qeyd'] ?? '',
                'muellim_adi' => $muellim_adi_array
            ];
        }
        
        if (empty($students)) {
            echo json_encode(['message' => 'Tələbə tapılmadı'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($students, JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching all students: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function getStudentsByGroup($group) {
    global $conn;
    
    try {
        if (empty(trim($group))) {
            echo json_encode(['message' => 'Qrup seçin'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Try different possible column names for student name
        $possible_name_columns = ['username', 'name', 'ad', 'telebe_adi', 'student_name'];
        $name_column = 'username'; // default
        foreach ($possible_name_columns as $col) {
            $check_query = "SHOW COLUMNS FROM telebeler LIKE '$col'";
            $check_result = $conn->query($check_query);
            if ($check_result && $check_result->num_rows > 0) {
                $name_column = $col;
                break;
            }
        }
        
        $query = "SELECT id, $name_column as username, sinif, 
                         COALESCE(status, 'Istirak_edir') as status, 
                         COALESCE(qeyd, '') as qeyd, 
                         COALESCE(muellim_adi, '[]') as muellim_adi 
                  FROM telebeler 
                  WHERE sinif = ? AND $name_column IS NOT NULL AND $name_column != ''
                  ORDER BY $name_column";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $group);
        
        if (!$stmt->execute()) {
            throw new Exception('Query execution failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $students = [];
        
        while ($row = $result->fetch_assoc()) {
            // Parse muellim_adi JSON field
            $muellim_adi_array = [];
            if (!empty($row['muellim_adi']) && $row['muellim_adi'] !== '[]') {
                $decoded = json_decode($row['muellim_adi'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $muellim_adi_array = $decoded;
                }
            }
            
            $students[] = [
                'id' => (int)$row['id'],
                'username' => !empty($row['username']) ? $row['username'] : 'Adsız tələbə',
                'sinif' => $row['sinif'] ?? '',
                'status' => $row['status'] ?? 'Istirak_edir',
                'qeyd' => $row['qeyd'] ?? '',
                'muellim_adi' => $muellim_adi_array
            ];
        }
        
        if (empty($students)) {
            echo json_encode(['message' => 'Bu qrupda tələbə tapılmadı'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($students, JSON_UNESCAPED_UNICODE);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching students: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function getStudentsByMuellim($muellim_name) {
    global $conn;
    
    try {
        if (empty(trim($muellim_name))) {
            echo json_encode(['message' => 'Müəllim adı seçin'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Try different possible column names for student name
        $possible_name_columns = ['username', 'name', 'ad', 'telebe_adi', 'student_name'];
        $name_column = 'username'; // default
        foreach ($possible_name_columns as $col) {
            $check_query = "SHOW COLUMNS FROM telebeler LIKE '$col'";
            $check_result = $conn->query($check_query);
            if ($check_result && $check_result->num_rows > 0) {
                $name_column = $col;
                break;
            }
        }
        
        $query = "SELECT id, $name_column as username, sinif, 
                         COALESCE(status, 'Istirak_edir') as status, 
                         COALESCE(qeyd, '') as qeyd, 
                         COALESCE(muellim_adi, '[]') as muellim_adi 
                  FROM telebeler 
                  WHERE $name_column IS NOT NULL 
                  AND $name_column != ''
                  AND JSON_CONTAINS(muellim_adi, ?, '$')
                  ORDER BY sinif, $name_column";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        // Encode muellim_name as JSON string for JSON_CONTAINS
        $json_muellim = json_encode($muellim_name);
        $stmt->bind_param('s', $json_muellim);
        
        if (!$stmt->execute()) {
            throw new Exception('Query execution failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $students = [];
        
        while ($row = $result->fetch_assoc()) {
            // Parse muellim_adi JSON field
            $muellim_adi_array = [];
            if (!empty($row['muellim_adi']) && $row['muellim_adi'] !== '[]') {
                $decoded = json_decode($row['muellim_adi'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $muellim_adi_array = $decoded;
                }
            }
            
            $students[] = [
                'id' => (int)$row['id'],
                'username' => !empty($row['username']) ? $row['username'] : 'Adsız tələbə',
                'sinif' => $row['sinif'] ?? '',
                'status' => $row['status'] ?? 'Istirak_edir',
                'qeyd' => $row['qeyd'] ?? '',
                'muellim_adi' => $muellim_adi_array
            ];
        }
        
        if (empty($students)) {
            echo json_encode(['message' => 'Bu müəllimə aid tələbə tapılmadı'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($students, JSON_UNESCAPED_UNICODE);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching students by muellim: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function getStudentInfo($studentId) {
    global $conn, $base_url;
    
    try {
        $studentId = intval($studentId);
        if ($studentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid student ID'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Try different possible column names for student name
        $possible_name_columns = ['username', 'name', 'ad', 'telebe_adi', 'student_name'];
        $name_column = 'username'; // default
        foreach ($possible_name_columns as $col) {
            $check_query = "SHOW COLUMNS FROM telebeler LIKE '$col'";
            $check_result = $conn->query($check_query);
            if ($check_result && $check_result->num_rows > 0) {
                $name_column = $col;
                break;
            }
        }
        
        $query = "SELECT id, $name_column as username, sinif, dogum_tarixi, cins, unvan, orta_bal, photo,
                         ata, elaqe_nomre_ata, ana, elaqe_nomre_ana,
                         riyaziyyat, fizika, kimya, biologiya, tarix, edebiyyat, qeyd, muellim_adi, status
                  FROM telebeler WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param('i', $studentId);
        
        if (!$stmt->execute()) {
            throw new Exception('Query execution failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $student = $result->fetch_assoc();
            
            // Handle photo path
            $full_photo_url = '';
            if (!empty($student['photo'])) {
                $photo_path = trim($student['photo']);
                $full_file_path = $_SERVER['DOCUMENT_ROOT'] . '/telebeler/' . $photo_path;
                if (file_exists($full_file_path)) {
                    $full_photo_url = $base_url . $photo_path;
                }
            }
            
            // Parse muellim_adi JSON field
            $muellim_adi_array = [];
            if (!empty($student['muellim_adi'])) {
                $decoded = json_decode($student['muellim_adi'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $muellim_adi_array = $decoded;
                }
            }
            
            $response = [
                'id' => (int)$student['id'],
                'username' => !empty($student['username']) ? $student['username'] : 'Adsız tələbə',
                'sinif' => $student['sinif'] ?? '',
                'dogum_tarixi' => $student['dogum_tarixi'] ?? '',
                'cins' => isset($student['cins']) ? strval($student['cins']) : null,
                'unvan' => $student['unvan'] ?? '',
                'orta_bal' => $student['orta_bal'] ?? '',
                'photo' => $full_photo_url,
                'ata' => $student['ata'] ?? '',
                'elaqe_nomre_ata' => $student['elaqe_nomre_ata'] ?? '',
                'ana' => $student['ana'] ?? '',
                'elaqe_nomre_ana' => $student['elaqe_nomre_ana'] ?? '',
                'riyaziyyat' => $student['riyaziyyat'] ?? '',
                'fizika' => $student['fizika'] ?? '',
                'kimya' => $student['kimya'] ?? '',
                'biologiya' => $student['biologiya'] ?? '',
                'tarix' => $student['tarix'] ?? '',
                'edebiyyat' => $student['edebiyyat'] ?? '',
                'qeyd' => $student['qeyd'] ?? '',
                'muellim_adi' => $muellim_adi_array,
                'status' => $student['status'] ?? 'Istirak_edir'
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tələbə tapılmadı'], JSON_UNESCAPED_UNICODE);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error fetching student info: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function updateStudentAttendance($data) {
    global $conn;
    
    try {
        $studentId = intval($data['studentId']);
        $status = isset($data['status']) ? trim($data['status']) : 'Istirak_edir';
        $qeyd = isset($data['qeyd']) ? trim($data['qeyd']) : '';
        
        if ($studentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid student ID'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Validate status
        $valid_statuses = ['Istirak_edir', 'Istirak_etmir', 'Uzrli'];
        if (!in_array($status, $valid_statuses)) {
            $status = 'Istirak_edir';
        }
        
        // Check if student exists first
        $check_query = "SELECT id FROM telebeler WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $check_stmt->bind_param('i', $studentId);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Tələbə tapılmadı'], JSON_UNESCAPED_UNICODE);
            $check_stmt->close();
            return;
        }
        $check_stmt->close();
        
        // Update student
        $query = "UPDATE telebeler SET status = ?, qeyd = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $stmt->bind_param("ssi", $status, $qeyd, $studentId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Məlumat uğurla yeniləndi'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Update failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error updating attendance: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
?>