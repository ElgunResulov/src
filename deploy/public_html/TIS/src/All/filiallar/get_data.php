<?php
include('../db.php');

$method = $_GET['method'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $method = $data['method'] ?? '';
}

if (!empty($method)) {
    header('Content-Type: application/json');
    switch ($method) {
        case 'get_filials':
            $stmt = $conn->prepare("SELECT DISTINCT filial_adi FROM filiallar WHERE filial_adi IS NOT NULL AND filial_adi != ''");
            $stmt->execute();
            $result = $stmt->get_result();
            $allFilials = [];
            while ($row = $result->fetch_assoc()) {
                $filialData = $row['filial_adi'];
                if (is_string($filialData) && (substr($filialData, 0, 1) === '[' || substr($filialData, 0, 1) === '{')) {
                    $decodedFilials = json_decode($filialData, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilials)) {
                        foreach ($decodedFilials as $filial) {
                            if (!empty(trim($filial)) && !in_array(trim($filial), $allFilials)) {
                                $allFilials[] = trim($filial);
                            }
                        }
                    } else {
                        if (!empty(trim($filialData)) && !in_array(trim($filialData), $allFilials)) {
                            $allFilials[] = trim($filialData);
                        }
                    }
                } else {
                    if (!empty(trim($filialData)) && !in_array(trim($filialData), $allFilials)) {
                        $allFilials[] = trim($filialData);
                    }
                }
            }
            sort($allFilials);
            echo json_encode($allFilials);
            break;

        case 'get_fenns':
            $filial = $_GET['filial'] ?? '';
            if (empty($filial)) {
                echo json_encode([]);
                break;
            }
            $stmt = $conn->prepare("SELECT DISTINCT tehsil_ve_ixtisas, filial_adi, filial_adi_second FROM muellimler_new WHERE tehsil_ve_ixtisas IS NOT NULL AND tehsil_ve_ixtisas != ''");
            $stmt->execute();
            $result = $stmt->get_result();
            $fenns = [];
            while ($row = $result->fetch_assoc()) {
                $matchFound = false;
                if (!empty($row['filial_adi'])) {
                    $filialData = $row['filial_adi'];
                    if (is_string($filialData) && (substr($filialData, 0, 1) === '[' || substr($filialData, 0, 1) === '{')) {
                        $decodedFilials = json_decode($filialData, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilials)) {
                            if (in_array($filial, $decodedFilials)) {
                                $matchFound = true;
                            }
                        }
                    } else {
                        if ($filialData === $filial) {
                            $matchFound = true;
                        }
                    }
                }
                if (!$matchFound && !empty($row['filial_adi_second']) && $row['filial_adi_second'] === $filial) {
                    $matchFound = true;
                }
                if ($matchFound && !empty(trim($row['tehsil_ve_ixtisas'])) && !in_array(trim($row['tehsil_ve_ixtisas']), $fenns)) {
                    $fenns[] = trim($row['tehsil_ve_ixtisas']);
                }
            }
            sort($fenns);
            echo json_encode($fenns);
            break;

        case 'get_teachers':
            $filial = $_GET['filial'] ?? '';
            $fenn = $_GET['fenn'] ?? '';
            if (!$filial || !$fenn) {
                echo json_encode([]);
                exit;
            }
            $stmt = $conn->prepare("SELECT id, username, filial_adi, filial_adi_second, tehsil_ve_ixtisas FROM muellimler_new WHERE tehsil_ve_ixtisas = ?");
            $stmt->bind_param("s", $fenn);
            $stmt->execute();
            $result = $stmt->get_result();
            $teachers = [];
            while ($row = $result->fetch_assoc()) {
                $matchFound = false;
                if (!empty($row['filial_adi'])) {
                    $filialData = $row['filial_adi'];
                    if (is_string($filialData) && (substr($filialData, 0, 1) === '[' || substr($filialData, 0, 1) === '{')) {
                        $decodedFilials = json_decode($filialData, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilials)) {
                            if (in_array($filial, $decodedFilials)) {
                                $matchFound = true;
                            }
                        }
                    } else {
                        if ($filialData === $filial) {
                            $matchFound = true;
                        }
                    }
                }
                if (!$matchFound && !empty($row['filial_adi_second']) && $row['filial_adi_second'] === $filial) {
                    $matchFound = true;
                }
                if ($matchFound) {
                    $teachers[] = [
                        'id' => $row['id'],
                        'username' => $row['username']
                    ];
                }
            }
            echo json_encode($teachers);
            break;

        case 'get_schedule':
            $teacherId = $_GET['teacher_id'] ?? '';
            $filial = $_GET['filial'] ?? '';
            if (!$teacherId || !$filial) {
                echo json_encode(['error' => 'Teacher ID and filial are required']);
                exit;
            }
            $stmt = $conn->prepare("SELECT cedvel, username, telebeler, filial_adi, filial_adi_second FROM muellimler_new WHERE id = ?");
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $result = $stmt->get_result();
            $responseData = [
                'cedvel' => [],
                'teacher_info' => [],
                'telebeler' => []
            ];
            if ($row = $result->fetch_assoc()) {
                $matchFound = false;
                if (!empty($row['filial_adi'])) {
                    $filialData = $row['filial_adi'];
                    if (is_string($filialData) && (substr($filialData, 0, 1) === '[' || substr($filialData, 0, 1) === '{')) {
                        $decodedFilials = json_decode($filialData, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilials)) {
                            if (in_array($filial, $decodedFilials)) {
                                $matchFound = true;
                            }
                        }
                    } else {
                        if ($filialData === $filial) {
                            $matchFound = true;
                        }
                    }
                }
                if (!$matchFound && !empty($row['filial_adi_second']) && $row['filial_adi_second'] === $filial) {
                    $matchFound = true;
                }
                if (!$matchFound) {
                    echo json_validate(['error' => 'Teacher not associated with this filial']);
                    exit;
                }
                $cedvel = json_decode($row['cedvel'], true) ?: [];
                $telebeler = json_decode($row['telebeler'], true) ?: [];
                $cleanedCedvel = [];
                foreach ($cedvel as $entry) {
                    if (is_array($entry) && count($entry) >= 3 && !empty($entry[1]) && !empty($entry[2]) && $entry[0] === $filial && in_array($entry[2], ['Bazar', 'Bazar ertəsi', 'Çərşənbə', 'Çərşənbə axşamı', 'Cümə', 'Cümə axşamı', 'Şənbə'])) {
                        $cleanedCedvel[] = [$entry[0], $entry[1], $entry[2], $entry[3] ?? ''];
                    }
                }
                $cleanedTelebeler = [];
                foreach ($telebeler as $entry) {
                    if (is_array($entry) && count($entry) >= 4 && !empty($entry[1]) && !empty($entry[2]) && $entry[0] === $filial && in_array($entry[2], ['Bazar', 'Bazar ertəsi', 'Çərşənbə', 'Çərşənbə axşamı', 'Cümə', 'Cümə axşamı', 'Şənbə'])) {
                        $studentName = isset($entry[3]) ? trim($entry[3]) : '';
                        if (!empty($studentName)) {
                            $cleanedTelebeler[] = [$entry[0], $entry[1], $entry[2], $studentName];
                        }
                    }
                }
                $responseData['cedvel'] = $cleanedCedvel;
                $responseData['teacher_info'] = [
                    'username' => $row['username']
                ];
                $responseData['telebeler'] = $cleanedTelebeler;
            }
            echo json_encode($responseData);
            break;

        case 'get_telebeler':
            $stmt = $conn->prepare("SELECT telebeler FROM muellimler_new WHERE telebeler IS NOT NULL AND telebeler != ''");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $telebeler = json_decode($row['telebeler'], true);
                if (is_array($telebeler)) {
                    foreach ($telebeler as $entry) {
                        if (is_array($entry) && count($entry) >= 4 && !empty(trim($entry[3]))) {
                            $studentName = trim($entry[3]);
                            if (!in_array($studentName, $data)) {
                                $data[] = $studentName;
                            }
                        }
                    }
                }
            }
            sort($data);
            echo json_encode($data);
            break;

        case 'update_student_teachers':
            $postData = json_decode(file_get_contents('php://input'), true);
            $username = isset($postData['username']) ? trim($postData['username']) : '';
            $teachers = $postData['teachers'] ?? [];
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username is required']);
                exit;
            }
            if (!is_array($teachers)) {
                $teachers = [];
            }
            $teachersJson = json_encode($teachers, JSON_UNESCAPED_UNICODE);
            try {
                $conn->begin_transaction();
                $checkStmt = $conn->prepare("SELECT id FROM telebeler WHERE username = ?");
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult->num_rows > 0) {
                    $updateStmt = $conn->prepare("UPDATE telebeler SET muellim_adi = ? WHERE username = ?");
                    $updateStmt->bind_param("ss", $teachersJson, $username);
                    $updateStmt->execute();
                    if ($updateStmt->affected_rows >= 0) {
                        $conn->commit();
                        echo json_encode([
                            'success' => true,
                            'message' => "Student '$username' teachers updated successfully",
                            'data' => [
                                'username' => $username,
                                'teachers' => $teachers
                            ]
                        ]);
                    } else {
                        $conn->rollback();
                        echo json_encode(['success' => false, 'message' => 'Failed to update student teachers']);
                    }
                } else {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => "Student '$username' not found"]);
                }
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Student teachers update error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred: ' + $e->getMessage()]);
            }
            break;

        case 'save_schedule':
            $postData = json_decode(file_get_contents('php://input'), true);
            $teacherId = $postData['teacher_id'] ?? '';
            $time = $postData['time'] ?? '';
            $day = $postData['day'] ?? '';
            $studentName = isset($postData['student_name']) ? trim($postData['student_name']) : '';
            $filial = $postData['filial'] ?? '';
            if (!$teacherId || !$time || !$day || !in_array($day, ['Bazar', 'Bazar ertəsi', 'Çərşənbə', 'Çərşənbə axşamı', 'Cümə', 'Cümə axşamı', 'Şənbə'])) {
                echo json_encode(['success' => false, 'message' => 'Missing or invalid required fields: teacher_id, time, day']);
                exit;
            }
            try {
                $conn->begin_transaction();
                $stmt = $conn->prepare("SELECT cedvel, telebeler FROM muellimler_new WHERE id = ?");
                $stmt->bind_param("i", $teacherId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $cedvel = json_decode($row['cedvel'], true) ?: [];
                    $telebeler = json_decode($row['telebeler'], true) ?: [];
                    $timeSlotExists = false;
                    foreach ($cedvel as $slot) {
                        if (is_array($slot) && count($slot) >= 3 && $slot[1] === $time && $slot[2] === $day && $slot[0] === $filial) {
                            $timeSlotExists = true;
                            break;
                        }
                    }
                    if (!$timeSlotExists) {
                        echo json_encode(['success' => false, 'message' => 'Time slot does not exist in cedvel for this filial']);
                        exit;
                    }
                    $telebeler = array_filter($telebeler, function($entry) use ($time, $day, $filial) {
                        return !(is_array($entry) && count($entry) >= 3 && $entry[1] === $time && $entry[2] === $day && $entry[0] === $filial);
                    });
                    if (!empty($studentName)) {
                        $telebeler[] = [$filial, $time, $day, $studentName];
                    }
                    $telebeler = array_values($telebeler);
                    $telebelerJson = json_encode($telebeler, JSON_UNESCAPED_UNICODE);
                    $updateStmt = $conn->prepare("UPDATE muellimler_new SET telebeler = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $telebelerJson, $teacherId);
                    $updateStmt->execute();
                    if ($updateStmt->affected_rows >= 0) {
                        $conn->commit();
                        $message = !empty($studentName) ?
                            "Student '$studentName' added to $time - $day" :
                            "Student removed from $time - $day";
                        echo json_encode([
                            'success' => true,
                            'message' => $message,
                            'data' => [
                                'telebeler' => $telebeler,
                                'student_count' => count(array_filter($telebeler, function($entry) {
                                    return is_array($entry) && count($entry) >= 4 && !empty(trim($entry[3]));
                                }))
                            ]
                        ]);
                    } else {
                        $conn->rollback();
                        echo json_encode(['success' => false, 'message' => 'Failed to update schedule']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
                }
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Schedule save error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid method']);
            break;
    }
    exit;
}
?>