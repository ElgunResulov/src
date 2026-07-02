<?php
header('Content-Type: application/json; charset=utf-8');
include('../../db.php');

// Set charset to UTF-8
mysqli_set_charset($conn, 'utf8');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logError($message) {
    error_log($message);
}

// Handle API requests based on method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Handle GET requests
            if (!isset($_GET['action'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing action parameter']);
                exit;
            }

            $action = $_GET['action'];

            switch ($action) {
                case 'get_question':
                    // Fetch a specific question by ID
                    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid or missing ID']);
                        exit;
                    }

                    $id = intval($_GET['id']);
                    logError("Fetching question with ID: $id");

                    // Check if question exists
                    $checkQuery = "SELECT COUNT(*) as count FROM sual_banki WHERE id = ?";
                    $checkStmt = mysqli_prepare($conn, $checkQuery);
                    if (!$checkStmt) {
                        throw new Exception('Failed to prepare check statement: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($checkStmt, 'i', $id);
                    mysqli_stmt_execute($checkStmt);
                    $checkResult = mysqli_stmt_get_result($checkStmt);
                    $count = mysqli_fetch_assoc($checkResult)['count'];
                    mysqli_stmt_close($checkStmt);

                    if ($count == 0) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Question not found']);
                        exit;
                    }

                    // Fetch question details with proper joins
                    $query = "SELECT q.*, f.fenn_adi as subject_name, f.id as subject_id, 
                              m.movzu_adi as topic_name, m.id as topic_id
                              FROM sual_banki q 
                              LEFT JOIN fennler_new f ON q.subject = f.id 
                              LEFT JOIN movzular_new m ON q.topic = m.id
                              WHERE q.id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($stmt, 'i', $id);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
                    }
                    $result = mysqli_stmt_get_result($stmt);
                    $question = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt);

                    if (!$question) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Question data not found']);
                        exit;
                    }

                    // Get the ixtisas_adi for the subject
                    if ($question['subject_id']) {
                        $ixtisasQuery = "SELECT i.ixtisas_adi FROM ixtisas i 
                                        INNER JOIN fennler_new f ON f.fenn_adi = i.ixtisas_adi 
                                        WHERE f.id = ?";
                        $ixtisasStmt = mysqli_prepare($conn, $ixtisasQuery);
                        if ($ixtisasStmt) {
                            mysqli_stmt_bind_param($ixtisasStmt, 'i', $question['subject_id']);
                            mysqli_stmt_execute($ixtisasStmt);
                            $ixtisasResult = mysqli_stmt_get_result($ixtisasStmt);
                            if ($ixtisasResult && $ixtisasRow = mysqli_fetch_assoc($ixtisasResult)) {
                                $question['ixtisas_adi'] = $ixtisasRow['ixtisas_adi'];
                            } else {
                                $question['ixtisas_adi'] = $question['subject_name'];
                            }
                            mysqli_stmt_close($ixtisasStmt);
                        }
                    }

                    // Check if question has an image
                    $question['has_image'] = !empty($question['question_image']);

                    // Parse options if they exist
                    if (isset($question['options']) && !empty($question['options'])) {
                        try {
                            $decodedOptions = json_decode($question['options'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOptions)) {
                                $question['options'] = $decodedOptions;
                            } else {
                                logError("JSON decode error for options: " . json_last_error_msg());
                                $question['options'] = [];
                            }
                        } catch (Exception $e) {
                            logError("Exception parsing options: " . $e->getMessage());
                            $question['options'] = [];
                        }
                    } else {
                        $question['options'] = [];
                    }

                    // Parse correct_answer if it exists
                    if (isset($question['correct_answer']) && !empty($question['correct_answer'])) {
                        try {
                            $decodedAnswer = json_decode($question['correct_answer'], true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $question['correct_answer'] = $decodedAnswer;
                            } else {
                                logError("JSON decode error for correct_answer: " . json_last_error_msg());
                                // Keep as string if JSON decode fails
                            }
                        } catch (Exception $e) {
                            logError("Exception parsing correct_answer: " . $e->getMessage());
                        }
                    }

                    logError("Retrieved question data: " . json_encode($question));
                    echo json_encode(['success' => true, 'question' => $question]);
                    break;

                case 'get_questions':
                    // Fetch all questions with optional filters
                    $subject = isset($_GET['subject']) ? intval($_GET['subject']) : null;
                    $topic = isset($_GET['topic']) ? intval($_GET['topic']) : null;
                    $difficulty = isset($_GET['difficulty']) ? intval($_GET['difficulty']) : null;
                    $type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : null;
                    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

                    // Build query with filters
                    $query = "SELECT q.id, q.subject, q.topic, q.question_type, 
                             q.question_text, CASE WHEN q.question_image != '' THEN 1 ELSE 0 END as has_image,
                             q.difficulty, q.created_at, f.fenn_adi as subject_name, m.movzu_adi as topic_name
                             FROM sual_banki q
                             LEFT JOIN fennler_new f ON q.subject = f.id
                             LEFT JOIN movzular_new m ON q.topic = m.id
                             WHERE 1=1";
                    
                    $params = [];
                    $types = "";
                    
                    if ($subject) {
                        $query .= " AND q.subject = ?";
                        $params[] = $subject;
                        $types .= "i";
                    }
                    
                    if ($topic) {
                        $query .= " AND q.topic = ?";
                        $params[] = $topic;
                        $types .= "i";
                    }
                    
                    if ($difficulty) {
                        $query .= " AND q.difficulty = ?";
                        $params[] = $difficulty;
                        $types .= "i";
                    }
                    
                    if ($type) {
                        $query .= " AND q.question_type = ?";
                        $params[] = $type;
                        $types .= "s";
                    }
                    
                    $query .= " ORDER BY q.id DESC LIMIT ? OFFSET ?";
                    $params[] = $limit;
                    $params[] = $offset;
                    $types .= "ii";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
                    }
                    
                    if (!empty($params)) {
                        mysqli_stmt_bind_param($stmt, $types, ...$params);
                    }
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
                    }
                    
                    $result = mysqli_stmt_get_result($stmt);
                    $questions = [];
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Truncate question content for list view
                        if (!empty($row['question_text'])) {
                            $row['question_content'] = mb_substr(strip_tags($row['question_text']), 0, 100) . '...';
                        } else {
                            $row['question_content'] = '[Image Question]';
                        }
                        $questions[] = $row;
                    }
                    
                    mysqli_stmt_close($stmt);
                    
                    // Get total count for pagination
                    $countQuery = "SELECT COUNT(*) as total FROM sual_banki q WHERE 1=1";
                    $countParams = [];
                    $countTypes = "";
                    
                    if ($subject) {
                        $countQuery .= " AND q.subject = ?";
                        $countParams[] = $subject;
                        $countTypes .= "i";
                    }
                    
                    if ($topic) {
                        $countQuery .= " AND q.topic = ?";
                        $countParams[] = $topic;
                        $countTypes .= "i";
                    }
                    
                    if ($difficulty) {
                        $countQuery .= " AND q.difficulty = ?";
                        $countParams[] = $difficulty;
                        $countTypes .= "i";
                    }
                    
                    if ($type) {
                        $countQuery .= " AND q.question_type = ?";
                        $countParams[] = $type;
                        $countTypes .= "s";
                    }
                    
                    $countStmt = mysqli_prepare($conn, $countQuery);
                    if (!$countStmt) {
                        throw new Exception('Failed to prepare count statement: ' . mysqli_error($conn));
                    }
                    
                    if (!empty($countParams)) {
                        mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
                    }
                    
                    if (!mysqli_stmt_execute($countStmt)) {
                        throw new Exception('Failed to execute count statement: ' . mysqli_stmt_error($countStmt));
                    }
                    
                    $countResult = mysqli_stmt_get_result($countStmt);
                    $totalCount = mysqli_fetch_assoc($countResult)['total'];
                    mysqli_stmt_close($countStmt);
                    
                    echo json_encode([
                        'success' => true, 
                        'questions' => $questions,
                        'total' => $totalCount,
                        'limit' => $limit,
                        'offset' => $offset
                    ]);
                    break;

                case 'get_subjects_topics':
                    try {
                        $ixtisas_adi = isset($_GET['ixtisas_adi']) ? mysqli_real_escape_string($conn, $_GET['ixtisas_adi']) : null;

                        if ($ixtisas_adi) {
                            // Debug: Log the received ixtisas_adi
                            error_log("Received ixtisas_adi: " . $ixtisas_adi);

                            // Fetch topics for the given ixtisas_adi
                            $query = "SELECT id, movzu_adi FROM movzular_new WHERE fenn = ? ORDER BY movzu_adi";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, 's', $ixtisas_adi);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            $topics = [];
                            while ($row = mysqli_fetch_assoc($result)) {
                                $topics[] = $row;
                            }
                            mysqli_stmt_close($stmt);

                            // Debug: Log the number of topics found
                            error_log("Topics found for ixtisas_adi '$ixtisas_adi': " . count($topics));

                            echo json_encode(['success' => true, 'topics' => $topics]);
                        } else {
                            // Fetch subjects from ixtisas table
                            $query = "SELECT id, ixtisas_adi FROM ixtisas WHERE active = 1 ORDER BY ixtisas_adi";
                            $result = mysqli_query($conn, $query);
                            if (!$result) {
                                throw new Exception("Ixtisas query failed: " . mysqli_error($conn));
                            }

                            $subjects = [];
                            while ($row = mysqli_fetch_assoc($result)) {
                                $subjects[] = $row;
                            }

                            // Debug: Log the number of subjects found
                            error_log("Subjects found: " . count($subjects));

                            echo json_encode(['success' => true, 'subjects' => $subjects]);
                        }
                    } catch (Exception $e) {
                        error_log("Error in get_subjects_topics: " . $e->getMessage());
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
                    break;
        
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
                    break;
            }
            break;

case 'POST':
            // Handle POST requests for creating or updating questions
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON: ' . json_last_error_msg());
            }

            if (isset($data['id']) && is_numeric($data['id'])) {
                // Update existing question
                $required = ['id', 'subject', 'topic', 'question_type', 'difficulty'];
                foreach ($required as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        throw new Exception("Missing or empty $field");
                    }
                }

                $id = intval($data['id']);
                $ixtisas_adi = mysqli_real_escape_string($conn, trim($data['subject']));
                $topic = intval($data['topic']);
                $question_type = mysqli_real_escape_string($conn, $data['question_type']);
                $difficulty = intval($data['difficulty']);

                // Map ixtisas_adi to fennler_new.id
                $query = "SELECT id FROM fennler_new WHERE TRIM(LOWER(fenn_adi)) = TRIM(LOWER(?))";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 's', $ixtisas_adi);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $subject = $row['id'];
                } else {
                    // Insert new fennler_new record if no match
                    $insert_query = "INSERT INTO fennler_new (fenn_adi, created_at, updated_at) VALUES (?, NOW(), NOW())";
                    $insert_stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, 's', $ixtisas_adi);
                    if (!mysqli_stmt_execute($insert_stmt)) {
                        throw new Exception("Failed to insert into fennler_new: " . mysqli_stmt_error($insert_stmt));
                    }
                    $subject = mysqli_insert_id($conn);
                    mysqli_stmt_close($insert_stmt);
                    error_log("Inserted new fennler_new record for ixtisas_adi '$ixtisas_adi' with id $subject");
                }
                mysqli_stmt_close($stmt);

                // Get question text and image
                $question_text = '';
                $question_image = '';
                
                if (isset($data['question_text'])) {
                    $question_text = mysqli_real_escape_string($conn, $data['question_text']);
                }
                
                if (isset($data['question_image'])) {
                    $question_image = mysqli_real_escape_string($conn, $data['question_image']);
                }

                // Handle options and correct answer
                $options = null;
                $correct_answer = null;
                
                if ($question_type === 'multiple_choice' && isset($data['options'])) {
                    $options = $data['options'];
                    if (is_string($options)) {
                        $options = json_decode($options, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new Exception('Invalid options JSON');
                        }
                    }
                    $correct_answers = array_filter($options, function($opt) {
                        return isset($opt['isCorrect']) && $opt['isCorrect'];
                    });
                    $correct_answer = json_encode($correct_answers);
                    $options = json_encode($options);
                } elseif (isset($data['correct_answer'])) {
                    $correct_answer = is_array($data['correct_answer']) ? 
                                     json_encode($data['correct_answer']) : 
                                     json_encode(['answer' => $data['correct_answer']]);
                }

                // Start transaction
                mysqli_begin_transaction($conn);

                // Build update query
                $updateQuery = "UPDATE sual_banki SET 
                                subject = ?, 
                                topic = ?, 
                                question_type = ?, 
                                difficulty = ?,
                                question_text = ?,
                                question_image = ?,
                                updated_at = NOW()";
                $params = [$subject, $topic, $question_type, $difficulty, $question_text, $question_image];
                $types = "iisiss";

                if ($options !== null) {
                    $updateQuery .= ", options = ?";
                    $params[] = $options;
                    $types .= "s";
                }

                if ($correct_answer !== null) {
                    $updateQuery .= ", correct_answer = ?";
                    $params[] = $correct_answer;
                    $types .= "s";
                }

                // Add image_path if provided
                if (isset($data['image_path'])) {
                    $updateQuery .= ", image_path = ?";
                    $params[] = mysqli_real_escape_string($conn, $data['image_path']);
                    $types .= "s";
                }

                $updateQuery .= " WHERE id = ?";
                $params[] = $id;
                $types .= "i";

                $updateStmt = mysqli_prepare($conn, $updateQuery);
                if (!$updateStmt) {
                    throw new Exception('Failed to prepare update statement: ' . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($updateStmt, $types, ...$params);
                if (!mysqli_stmt_execute($updateStmt)) {
                    throw new Exception('Failed to execute update statement: ' . mysqli_stmt_error($updateStmt));
                }
                mysqli_stmt_close($updateStmt);
                mysqli_commit($conn);

                echo json_encode(['success' => true, 'message' => 'Question updated successfully']);
            } else {
                // Create new question
                $required = ['subject', 'topic', 'question_type', 'difficulty'];
                foreach ($required as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        throw new Exception("Missing or empty $field");
                    }
                }

                $ixtisas_adi = mysqli_real_escape_string($conn, trim($data['subject']));
                $topic = intval($data['topic']);
                $question_type = mysqli_real_escape_string($conn, $data['question_type']);
                $difficulty = intval($data['difficulty']);
                
                // Get current session u_id
                session_start();
                $u_id = isset($_SESSION['u_id']) ? mysqli_real_escape_string($conn, $_SESSION['u_id']) : '1';

                // Map ixtisas_adi to fennler_new.id
                $query = "SELECT id FROM fennler_new WHERE TRIM(LOWER(fenn_adi)) = TRIM(LOWER(?))";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 's', $ixtisas_adi);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $subject = $row['id'];
                } else {
                    // Insert new fennler_new record if no match
                    $insert_query = "INSERT INTO fennler_new (fenn_adi, created_at, updated_at) VALUES (?, NOW(), NOW())";
                    $insert_stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, 's', $ixtisas_adi);
                    if (!mysqli_stmt_execute($insert_stmt)) {
                        throw new Exception("Failed to insert into fennler_new: " . mysqli_stmt_error($insert_stmt));
                    }
                    $subject = mysqli_insert_id($conn);
                    mysqli_stmt_close($insert_stmt);
                    error_log("Inserted new fennler_new record for ixtisas_adi '$ixtisas_adi' with id $subject");
                }
                mysqli_stmt_close($stmt);

                // Get question text and image
                $question_text = '';
                $question_image = '';
                
                if (isset($data['question_text'])) {
                    $question_text = mysqli_real_escape_string($conn, $data['question_text']);
                }
                
                if (isset($data['question_image'])) {
                    $question_image = mysqli_real_escape_string($conn, $data['question_image']);
                }

                // Handle options and correct answer based on question type
                $options = null;
                $correct_answer = null;
                
                if ($question_type === 'multiple_choice' && isset($data['options'])) {
                    $options = json_encode($data['options']);
                    $correct_answers = array_filter($data['options'], function($opt) {
                        return isset($opt['isCorrect']) && $opt['isCorrect'];
                    });
                    $correct_answer = json_encode($correct_answers);
                } elseif ($question_type === 'open_ended' && isset($data['correct_answer'])) {
                    $correct_answer = json_encode(['answer' => $data['correct_answer']]);
                } elseif ($question_type === 'true_false' && isset($data['correct_answer'])) {
                    $correct_answer = json_encode(['answer' => $data['correct_answer']]);
                } elseif ($question_type === 'matching' && isset($data['pairs'])) {
                    $correct_answer = json_encode($data['pairs']);
                }
                
                // Set image path if provided
                $image_path = isset($data['image_path']) ? mysqli_real_escape_string($conn, $data['image_path']) : '';
                
                // Start transaction
                mysqli_begin_transaction($conn);
                
                // Insert into sual_banki
                $query = "INSERT INTO sual_banki (
                            subject, 
                            topic, 
                            question_type, 
                            question_text, 
                            question_image, 
                            options, 
                            correct_answer, 
                            difficulty, 
                            image_path, 
                            u_id, 
                            created_at, 
                            updated_at
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = mysqli_prepare($conn, $query);
                if (!$stmt) {
                    throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
                }
                
                // Changed parameter binding to handle u_id as string
                mysqli_stmt_bind_param(
                    $stmt, 
                    'iissssisis', 
                    $subject, 
                    $topic, 
                    $question_type, 
                    $question_text, 
                    $question_image, 
                    $options, 
                    $correct_answer, 
                    $difficulty, 
                    $image_path, 
                    $u_id
                );
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
                }
                
                $question_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Return success response
                echo json_encode([
                    'success' => true, 
                    'message' => 'Sual uğurla əlavə edildi', 
                    'id' => $question_id
                ]);
            }
            break;

        case 'DELETE':
            // Handle DELETE requests for deleting a question
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid or missing ID']);
                exit;
            }

            $id = intval($_GET['id']);
            // Add permission check here (e.g., ensure user is authorized)

            // Delete question
            $query = "DELETE FROM sual_banki WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Deletion failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    logError("Exception in API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

// Close database connection
mysqli_close($conn);
?>