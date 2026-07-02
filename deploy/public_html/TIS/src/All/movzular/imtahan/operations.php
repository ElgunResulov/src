<?php
session_start();
header('Content-Type: application/json');
include('../../db.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_log('Script accessed: ' . $_SERVER['REQUEST_URI']);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING) ?? 
          filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) ?? '';

try {
    switch ($action) {
        case 'delete_exam':
            $exam_id = isset($_POST['exam_id']) ? filter_var($_POST['exam_id'], FILTER_VALIDATE_INT) : null;

            if (!$exam_id || $exam_id <= 0) {
                throw new Exception('Invalid or missing exam_id');
            }

            $sql = "DELETE FROM imtahanlar_exam WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $exam_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'İmtahan silindi']);
                } else {
                    throw new Exception('No exam found with the provided ID');
                }
            } else {
                throw new Exception('Silmə xətası');
            }
            $stmt->close();
            break;

        case 'fetch_topics':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['fenn_ids'])) {
                throw new Exception('Invalid request');
            }

            error_log('POST data received: ' . print_r($_POST, true));
            $fenn_ids = json_decode($_POST['fenn_ids'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON decode error: ' . json_last_error_msg());
                throw new Exception('Invalid JSON data');
            }

            $response = ['success' => false, 'topics' => [], 'message' => ''];

            if (!empty($fenn_ids)) {
                $fenn_ids = array_map('intval', $fenn_ids);
                $placeholders = implode(',', array_fill(0, count($fenn_ids), '?'));

                $sql = "SELECT m.movzu_adi, f.ixtisas_adi 
                        FROM movzular_new m
                        JOIN ixtisas f ON m.fenn_id = f.id
                        WHERE m.fenn_id IN ($placeholders)";
                error_log('SQL query: ' . $sql);
                error_log('Fenn IDs: ' . implode(',', $fenn_ids));

                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param(str_repeat('i', count($fenn_ids)), ...$fenn_ids);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $topics = [];
                    while ($row = $result->fetch_assoc()) {
                        $topics[] = [
                            'movzu_adi' => $row['movzu_adi'],
                            'ixtisas_adi' => $row['ixtisas_adi']
                        ];
                    }

                    $response['success'] = true;
                    $response['topics'] = $topics;
                    $response['message'] = count($topics) > 0 ? 'Topics fetched successfully' : 'No topics found';
                    $stmt->close();
                } else {
                    error_log('Prepare failed: ' . $conn->error);
                    throw new Exception('Query preparation failed');
                }
            } else {
                $response['message'] = 'No subjects selected';
            }
            echo json_encode($response);
            break;

    case 'insert_exam':
    error_log('POST data received: ' . print_r($_POST, true));
    
    // Get user ID from session
    if (!isset($_SESSION['u_id']) || empty($_SESSION['u_id'])) {
        throw new Exception('User not authenticated. Please log in.');
    }
    $u_id = $_SESSION['u_id'];
    
    $exam_name = filter_input(INPUT_POST, 'exam_name', FILTER_SANITIZE_STRING) ?? '';
    $fenn_adi_json = isset($_POST['fenn_adi']) ? trim($_POST['fenn_adi']) : '[]';
    $sinif = filter_input(INPUT_POST, 'sinif', FILTER_SANITIZE_STRING) ?? '';
    $description = isset($_POST['description']) ? filter_var($_POST['description'], FILTER_SANITIZE_STRING) : null;
    $exam_date = filter_input(INPUT_POST, 'exam_date', FILTER_SANITIZE_STRING) ?? '';
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT) ?? 0;
    $passing_score = filter_input(INPUT_POST, 'passing_score', FILTER_VALIDATE_INT) ?? 0;
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?? '';
    $groups = filter_input(INPUT_POST, 'groups', FILTER_SANITIZE_STRING) ?? '';
    $questions = isset($_POST['questions']) ? json_decode($_POST['questions'], true) : [];
    $movzular = isset($_POST['movzular']) ? json_decode($_POST['movzular'], true) : [];
    $sual_secimi = filter_input(INPUT_POST, 'sual_secimi', FILTER_SANITIZE_STRING) ?? null;
    $sual_sayi = filter_input(INPUT_POST, 'sual_sayi', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $cetinlik_seviyyesi = isset($_POST['cetinlik_seviyyesi']) ? json_decode($_POST['cetinlik_seviyyesi'], true) : [];

    $fenn_adi = null;
    if (!empty($fenn_adi_json)) {
        $fenn_adi_decoded = json_decode($fenn_adi_json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($fenn_adi_decoded)) {
            $fenn_adi = empty($fenn_adi_decoded) ? null : $fenn_adi_decoded;
        } else {
            error_log('Invalid fenn_adi JSON: ' . json_last_error_msg());
            throw new Exception('Invalid fenn_adi JSON format');
        }
    }

    $missing_fields = [];
    if (empty($exam_name)) $missing_fields[] = 'exam_name';
    if (empty($exam_date)) $missing_fields[] = 'exam_date';
    if ($duration <= 0) $missing_fields[] = 'duration';
    if ($passing_score < 0 || $passing_score > 100) $missing_fields[] = 'passing_score';
    if (empty($status) || !in_array($status, ['upcoming', 'completed', 'active'])) $missing_fields[] = 'status';
    if (empty($groups)) $missing_fields[] = 'groups';

    if (!empty($missing_fields)) {
        throw new Exception('Missing or invalid required fields: ' . implode(', ', $missing_fields));
    }

    if (!is_array($questions)) {
        error_log('Invalid questions JSON');
        $questions = [];
    } else {
        foreach ($questions as $question_id) {
            if (!is_numeric($question_id) || (int)$question_id <= 0) {
                throw new Exception('Invalid question id in questions');
            }
        }
    }
    if (!is_array($movzular)) {
        error_log('Invalid movzular JSON');
        $movzular = [];
    }
    if (!is_array($cetinlik_seviyyesi)) {
        error_log('Invalid cetinlik_seviyyesi JSON');
        $cetinlik_seviyyesi = [];
    }

    // Validate optional fields
    if ($sual_secimi && !in_array($sual_secimi, ['manual', 'random'])) {
        error_log('Invalid sual_secimi: ' . $sual_secimi);
        $sual_secimi = null;
    }

    $stmt = $conn->prepare("
        INSERT INTO imtahanlar_exam (
            u_id, exam_name, fenn_adi, sinif, description, exam_date, duration, passing_score, 
            groups, questions, status, movzular, sual_secimi, sual_sayi, 
            cetinlik_seviyyesi, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $fenn_adi_json = is_null($fenn_adi) ? null : json_encode($fenn_adi);
    $questions_json = json_encode($questions);
    $movzular_json = !empty($movzular) ? json_encode($movzular) : null;
    $cetinlik_seviyyesi_json = !empty($cetinlik_seviyyesi) ? json_encode($cetinlik_seviyyesi) : null;

    $stmt->bind_param(
        'ssssssiisssssis',
        $u_id,
        $exam_name,
        $fenn_adi_json,
        $sinif,
        $description,
        $exam_date,
        $duration,
        $passing_score,
        $groups,
        $questions_json,
        $status,
        $movzular_json,
        $sual_secimi,
        $sual_sayi,
        $cetinlik_seviyyesi_json
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Exam inserted successfully']);
    } else {
        error_log('SQL execute failed: ' . $conn->error);
        throw new Exception('Failed to insert exam: ' . $conn->error);
    }
    $stmt->close();
    break;

        case 'update_exam':
            error_log('Update exam - POST data received: ' . print_r($_POST, true));
            $exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
            $exam_name = filter_input(INPUT_POST, 'exam_name', FILTER_SANITIZE_STRING) ?? '';
            $fenn_adi_json = isset($_POST['fenn_adi']) ? trim($_POST['fenn_adi']) : '[]';
            $sinif = filter_input(INPUT_POST, 'sinif', FILTER_SANITIZE_STRING) ?? '';
            $description = isset($_POST['description']) ? filter_var($_POST['description'], FILTER_SANITIZE_STRING) : null;
            $exam_date = filter_input(INPUT_POST, 'exam_date', FILTER_SANITIZE_STRING) ?? '';
            $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT) ?? 0;
            $passing_score = filter_input(INPUT_POST, 'passing_score', FILTER_VALIDATE_INT) ?? 0;
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?? '';
            $groups = filter_input(INPUT_POST, 'groups', FILTER_SANITIZE_STRING) ?? '';
            $questions = isset($_POST['questions']) ? json_decode($_POST['questions'], true) : [];
            $movzular = isset($_POST['movzular']) ? json_decode($_POST['movzular'], true) : [];
            $sual_secimi = filter_input(INPUT_POST, 'sual_secimi', FILTER_SANITIZE_STRING) ?? null;
            $sual_sayi = filter_input(INPUT_POST, 'sual_sayi', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
            $cetinlik_seviyyesi = isset($_POST['cetinlik_seviyyesi']) ? json_decode($_POST['cetinlik_seviyyesi'], true) : [];

            $fenn_adi = null;
            if (!empty($fenn_adi_json)) {
                $fenn_adi_decoded = json_decode($fenn_adi_json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($fenn_adi_decoded)) {
                    $fenn_adi = empty($fenn_adi_decoded) ? null : $fenn_adi_decoded;
                } else {
                    error_log('Invalid fenn_adi JSON: ' . json_last_error_msg());
                    throw new Exception('Invalid fenn_adi JSON format');
                }
            }

            $missing_fields = [];
            if (empty($exam_id) || $exam_id <= 0) $missing_fields[] = 'exam_id';
            if (empty($exam_name)) $missing_fields[] = 'exam_name';
            if (empty($exam_date)) $missing_fields[] = 'exam_date';
            if ($duration <= 0) $missing_fields[] = 'duration';
            if ($passing_score < 0 || $passing_score > 100) $missing_fields[] = 'passing_score';
            if (empty($status) || !in_array($status, ['upcoming', 'completed', 'active'])) $missing_fields[] = 'status';
            if (empty($groups)) $missing_fields[] = 'groups';

            if (!empty($missing_fields)) {
                throw new Exception('Missing or invalid required fields: ' . implode(', ', $missing_fields));
            }

            if (!is_array($questions)) {
                error_log('Invalid questions JSON');
                $questions = [];
            } else {
                foreach ($questions as $question_id) {
                    if (!is_numeric($question_id) || (int)$question_id <= 0) {
                        throw new Exception('Invalid question id in questions');
                    }
                }
            }
            if (!is_array($movzular)) {
                error_log('Invalid movzular JSON');
                $movzular = [];
            }
            if (!is_array($cetinlik_seviyyesi)) {
                error_log('Invalid cetinlik_seviyyesi JSON');
                $cetinlik_seviyyesi = [];
            }

            if ($sual_secimi && !in_array($sual_secimi, ['manual', 'random'])) {
                error_log('Invalid sual_secimi: ' . $sual_secimi);
                $sual_secimi = null;
            }

            $stmt = $conn->prepare("
                UPDATE imtahanlar_exam SET
                    exam_name = ?,
                    fenn_adi = ?,
                    sinif = ?,
                    description = ?,
                    exam_date = ?,
                    duration = ?,
                    passing_score = ?,
                    groups = ?,
                    questions = ?,
                    status = ?,
                    movzular = ?,
                    sual_secimi = ?,
                    sual_sayi = ?,
                    cetinlik_seviyyesi = ?
                WHERE id = ?
            ");
            
            $fenn_adi_json = is_null($fenn_adi) ? null : json_encode($fenn_adi);
            $questions_json = json_encode($questions);
            $movzular_json = !empty($movzular) ? json_encode($movzular) : null;
            $cetinlik_seviyyesi_json = !empty($cetinlik_seviyyesi) ? json_encode($cetinlik_seviyyesi) : null;

            $stmt->bind_param(
                'sssssiisssssssi',
                $exam_name,
                $fenn_adi_json,
                $sinif,
                $description,
                $exam_date,
                $duration,
                $passing_score,
                $groups,
                $questions_json,
                $status,
                $movzular_json,
                $sual_secimi,
                $sual_sayi,
                $cetinlik_seviyyesi_json,
                $exam_id
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'İmtahan uğurla yeniləndi']);
                } else {
                    $sql = "SELECT id FROM imtahanlar_exam WHERE id = ?";
                    $check_stmt = $conn->prepare($sql);
                    $check_stmt->bind_param('i', $exam_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        echo json_encode(['success' => true, 'message' => 'Heç bir dəyişiklik edilmədi']);
                    } else {
                        throw new Exception('Verilən ID ilə imtahan tapılmadı');
                    }
                    $check_stmt->close();
                }
            } else {
                error_log('SQL execute failed: ' . $conn->error);
                throw new Exception('İmtahanı yeniləmək alınmadı: ' . $conn->error);
            }
            $stmt->close();
            break;

        case 'get_exam':
            $exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
            
            if (!$exam_id || $exam_id <= 0) {
                throw new Exception('Invalid or missing exam_id');
            }
            
            $sql = "SELECT * FROM imtahanlar_exam WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $exam_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $exam = $result->fetch_assoc();
                
                if (!empty($exam['fenn_adi'])) {
                    $exam['fenn_adi'] = json_decode($exam['fenn_adi'], true);
                }
                if (!empty($exam['questions'])) {
                    $exam['questions'] = json_decode($exam['questions'], true);
                }
                if (!empty($exam['movzular'])) {
                    $exam['movzular'] = json_decode($exam['movzular'], true);
                }
                if (!empty($exam['cetinlik_seviyyesi'])) {
                    $exam['cetinlik_seviyyesi'] = json_decode($exam['cetinlik_seviyyesi'], true);
                }
                
                echo json_encode(['success' => true, 'exam' => $exam]);
            } else {
                throw new Exception('İmtahan tapılmadı');
            }
            $stmt->close();
            break;

        default:
            throw new Exception('Invalid or missing action');
    }
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>