<?php
include('db.php');

function safeJsonDecode($jsonString, $default = []) {
    if (empty($jsonString) || $jsonString === 'null' || $jsonString === '""' || $jsonString === "''") {
        return $default;
    }
    
    $jsonString = trim($jsonString);
    
    if ((substr($jsonString, 0, 1) === '"' && substr($jsonString, -1) === '"') ||
        (substr($jsonString, 0, 1) === "'" && substr($jsonString, -1) === "'")) {
        $jsonString = substr($jsonString, 1, -1);
    }
    
    $decoded = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error: " . json_last_error_msg() . " for string: " . $jsonString);
        return $default;
    }
    return $decoded !== null ? $decoded : $default;
}

function formatTime($time) {
    return !empty($time) && $time !== 'N/A' ? $time : 'Vaxt təyin edilməyib';
}

function formatDay($day) {
    $dayMap = [
        'Bazar' => 'Bazar',
        'Bazar ertəsi' => 'Bazar ertəsi',
        'Çərşənbə axşamı' => 'Çərşənbə axşamı',
        'Çərşənbə' => 'Çərşənbə',
        'Cümə axşamı' => 'Cümə axşamı',
        'Cümə' => 'Cümə',
        'Şənbə' => 'Şənbə'
    ];
    return isset($dayMap[$day]) ? $dayMap[$day] : (!empty($day) ? $day : 'Gün təyin edilməyib');
}

if (isset($_GET['u_id']) && isset($_GET['action']) && $_GET['action'] === 'get_schedule') {
    $response = ['cedvel' => [], 'success' => false, 'message' => '', 'user_type' => ''];
    
    try {
        $u_id = trim($_GET['u_id']);
        $selected_filial = isset($_GET['filial']) ? trim($_GET['filial']) : null;
        $selected_telebe = isset($_GET['telebe']) ? trim($_GET['telebe']) : null;
        $date = isset($_GET['date']) ? trim($_GET['date']) : null;
        
        error_log("Fetching schedule for u_id: '" . $u_id . "'" . ($date ? " for date: " . $date : ""));
        
        $found = false;
        $scheduleData = [];
        
        // First check if u_id belongs to a teacher (muellimler_new)
        $query = "SELECT m.id, m.username, m.fenn, m.filial_adi, m.telebeler, m.tehsil_ve_ixtisas 
                  FROM muellimler_new m WHERE m.u_id = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $u_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $found = true;
                $response['user_type'] = 'teacher';
                
                // Get teacher's students from telebeler column
                $telebeler_json = $row['telebeler'];
                $telebeler_array = safeJsonDecode($telebeler_json, []);
                
                // Get filial info
                $filial_adi_json = $row['filial_adi'];
                $filial_array = safeJsonDecode($filial_adi_json, []);
                
                // Filter schedule data based on selected filial and telebe
                if (!empty($telebeler_array) && is_array($telebeler_array)) {
                    foreach ($telebeler_array as $schedule_item) {
                        if (is_array($schedule_item) && count($schedule_item) >= 4) {
                            $filial = $schedule_item[0];
                            $vaxt = $schedule_item[1];
                            $gun = $schedule_item[2];
                            $telebe_name = $schedule_item[3];
                            
                            // Apply filters
                            $show_item = true;
                            
                            // Filter by filial if selected
                            if ($selected_filial && $filial !== $selected_filial) {
                                $show_item = false;
                            }
                            
                            // Filter by telebe if selected
                            if ($selected_telebe && $telebe_name !== $selected_telebe) {
                                $show_item = false;
                            }
                            
                            if ($show_item) {
                                $scheduleData[] = [
                                    'filial' => $filial,
                                    'vaxt' => formatTime($vaxt),
                                    'gun' => formatDay($gun),
                                    'telebe' => $telebe_name,
                                    'fenn' => $row['fenn'] ?: 'Fənn təyin edilməyib',
                                    'muellim' => $row['username'],
                                    'type' => 'teacher_schedule'
                                ];
                            }
                        }
                    }
                }
                
                // Add filial and telebe options for filtering
                $response['filial_options'] = $filial_array;
                
                // Get unique telebe names for dropdown
                $telebe_options = [];
                if (!empty($telebeler_array) && is_array($telebeler_array)) {
                    foreach ($telebeler_array as $schedule_item) {
                        if (is_array($schedule_item) && count($schedule_item) >= 4) {
                            $telebe_name = $schedule_item[3];
                            if (!in_array($telebe_name, $telebe_options)) {
                                $telebe_options[] = $telebe_name;
                            }
                        }
                    }
                }
                $response['telebe_options'] = $telebe_options;
            }
            $stmt->close();
        }
        
        // If not found as teacher, check if u_id belongs to a student
        if (!$found) {
            $query = "SELECT t.username, t.muellim_adi, t.sinif, t.ixtisas_adi, t.qebul_tarixi 
                      FROM telebeler t WHERE t.u_id = ?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("s", $u_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $student_name = $row['username'];
                    $muellim_adi_json = $row['muellim_adi'];
                    $found = true;
                    $response['user_type'] = 'student';
                    
                    // Get teachers assigned to this student
                    $muellim_names = safeJsonDecode($muellim_adi_json, []);
                    
                    if (!empty($muellim_names) && is_array($muellim_names)) {
                        // Look for this student in teachers' telebeler column
                        $teacher_query = "SELECT m.username, m.filial_adi, m.telebeler, m.fenn, m.tehsil_ve_ixtisas
                                         FROM muellimler_new m WHERE m.username IN (" . str_repeat('?,', count($muellim_names) - 1) . "?)";
                        
                        if ($teacher_stmt = $conn->prepare($teacher_query)) {
                            $teacher_stmt->bind_param(str_repeat('s', count($muellim_names)), ...$muellim_names);
                            $teacher_stmt->execute();
                            $teacher_result = $teacher_stmt->get_result();
                            
                            while ($teacher_row = $teacher_result->fetch_assoc()) {
                                $teacher_telebeler_json = $teacher_row['telebeler'];
                                $teacher_telebeler_array = safeJsonDecode($teacher_telebeler_json, []);
                                
                                // Find schedule entries for this specific student
                                if (!empty($teacher_telebeler_array) && is_array($teacher_telebeler_array)) {
                                    foreach ($teacher_telebeler_array as $schedule_item) {
                                        if (is_array($schedule_item) && count($schedule_item) >= 4) {
                                            $telebe_in_schedule = $schedule_item[3];
                                            
                                            // Only show if this schedule entry is for the current student
                                            if ($telebe_in_schedule === $student_name) {
                                                $scheduleData[] = [
                                                    'filial' => $schedule_item[0],
                                                    'vaxt' => formatTime($schedule_item[1]),
                                                    'gun' => formatDay($schedule_item[2]),
                                                    'muellim' => $teacher_row['username'],
                                                    'fenn' => $teacher_row['fenn'] ?: 'Fənn təyin edilməyib',
                                                    'sinif' => $row['sinif'],
                                                    'ixtisas' => $row['ixtisas_adi'],
                                                    'type' => 'student_schedule'
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                            $teacher_stmt->close();
                        }
                    }
                    
                    if (empty($scheduleData)) {
                        $response['message'] = 'Tələbə tapıldı lakin cədvəl məlumatı yoxdur';
                    }
                }
                $stmt->close();
            }
        }

        if ($found) {
            if (!empty($scheduleData)) {
                // Remove duplicates and sort
                $unique_schedules = [];
                $seen = [];
                
                foreach ($scheduleData as $schedule) {
                    $key = $schedule['filial'] . '|' . $schedule['vaxt'] . '|' . $schedule['gun'];
                    if (!isset($seen[$key])) {
                        $unique_schedules[] = $schedule;
                        $seen[$key] = true;
                    }
                }
                
                // Sort by day and time
                $dayOrder = [
                    'Bazar' => 0,
                    'Bazar ertəsi' => 1,
                    'Çərşənbə' => 2,
                    'Çərşənbə axşamı' => 3,
                    'Cümə' => 4,
                    'Cümə axşamı' => 5,
                    'Şənbə' => 6
                ];
                
                usort($unique_schedules, function($a, $b) use ($dayOrder) {
                    $aDayOrder = $dayOrder[$a['gun']] ?? 999;
                    $bDayOrder = $dayOrder[$b['gun']] ?? 999;
                    
                    if ($aDayOrder === $bDayOrder) {
                        return strcmp($a['vaxt'], $b['vaxt']);
                    }
                    
                    return $aDayOrder - $bDayOrder;
                });
                
                $response['success'] = true;
                $response['cedvel'] = $unique_schedules;
                $response['message'] = 'Cədvəl uğurla yükləndi';
                error_log("Successfully constructed schedule data with " . count($unique_schedules) . " entries");
            } else {
                $response['success'] = false;
                $response['message'] = 'Cədvəl məlumatı tapılmadı';
                $response['cedvel'] = [];
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'İstifadəçi tapılmadı';
            $response['cedvel'] = [];
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'Xəta: ' . $e->getMessage();
        $response['cedvel'] = [];
        error_log("Exception in get_schedule: " . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// If not a valid request, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Yanlış sorgu', 'cedvel' => []], JSON_UNESCAPED_UNICODE);
exit;
?>