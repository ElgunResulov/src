<?php
include('../db.php');

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: imtahan-list.php");
    exit();
}

$exam_id = intval($_GET['id']);

// Fetch exam details
$query = "SELECT * FROM imtahanlar_exam WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: imtahan-list.php");
    exit();
}

$exam = $result->fetch_assoc();

// Clean up fenn_adi if it's JSON
$fenn_adi = $exam['fenn_adi'];
if (json_decode($fenn_adi, true)) {
    $fenn_adi = json_decode($fenn_adi, true)[0] ?? 'Unknown Subject';
}

// Determine the actual question count based on sual_secimi
$actual_question_count = 0;
$sual_secimi = strtolower($exam['sual_secimi']);
if ($sual_secimi === 'manual' && !empty($exam['questions'])) {
    $question_ids = json_decode($exam['questions'], true);
    $actual_question_count = is_array($question_ids) ? count($question_ids) : 0;
} else {
    $actual_question_count = (int)($exam['sual_sayi'] ?? 0);
}

// Fetch topic names from movzular table
$topic_map = [];
$topic_query = "SELECT id, movzu_adi FROM movzular_new";
$topic_result = $conn->query($topic_query);
while ($row = $topic_result->fetch_assoc()) {
    $topic_map[$row['id']] = $row['movzu_adi'];
}
$topic_map['N/A'] = 'Bilinməyən'; // Fallback for missing topics

// Fetch questions from sual_banki table based on exam settings
$questions = [];

// Determine how to select questions based on exam settings
if ($exam['sual_secimi'] === 'Təsadüfi') {
    $question_query = "SELECT id, question_text, question_image, question_type, options, correct_answer, image_path, topic, difficulty FROM sual_banki WHERE subject = ? ";
    if ($exam['cetinlik_seviyyesi'] !== 'Qarışıq') {
        $question_query .= "AND difficulty = ? ";
    }
    $question_query .= "ORDER BY RAND() LIMIT ?";

    $question_stmt = $conn->prepare($question_query);
    $sual_sayi = (int)$exam['sual_sayi'];

    if ($exam['cetinlik_seviyyesi'] !== 'Qarışıq') {
        $question_stmt->bind_param("ssi", $fenn_adi, $exam['cetinlik_seviyyesi'], $sual_sayi);
    } else {
        $question_stmt->bind_param("si", $fenn_adi, $sual_sayi);
    }
} elseif ($exam['sual_secimi'] === 'Mövzuya görə') {
    $topics = explode(',', $exam['movzular']);
    $topic_placeholders = str_repeat('?,', count($topics) - 1) . '?';
    $question_query = "SELECT id, question_text, question_image, question_type, options, correct_answer, image_path, topic, difficulty FROM sual_banki WHERE subject = ? AND topic IN ($topic_placeholders) ";
    if ($exam['cetinlik_seviyyesi'] !== 'Qarışıq') {
        $question_query .= "AND difficulty = ? ";
    }
    $question_query .= "ORDER BY RAND() LIMIT ?";

    $question_stmt = $conn->prepare($question_query);
    $sual_sayi = (int)$exam['sual_sayi'];

    $params = array_merge([$fenn_adi], $topics);
    if ($exam['cetinlik_seviyyesi'] !== 'Qarışıq') {
        $params[] = $exam['cetinlik_seviyyesi'];
        $params[] = $sual_sayi;
        $types = 's' . str_repeat('s', count($topics)) . 'si';
    } else {
        $params[] = $sual_sayi;
        $types = 's' . str_repeat('s', count($topics)) . 'i';
    }

    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $types);
    call_user_func_array([$question_stmt, 'bind_param'], $refs);
} elseif ($exam['sual_secimi'] === 'manual' && !empty($exam['questions'])) {
    $questions = json_decode($exam['questions'], true);
    if (is_array($questions) && isset($questions[0]) && is_numeric($questions[0])) {
        $question_ids = $questions;
        $questions = [];
        $id_placeholders = str_repeat('?,', count($question_ids) - 1) . '?';
        $question_query = "SELECT id, question_text, question_image, question_type, options, correct_answer, image_path, topic, difficulty FROM sual_banki WHERE id IN ($id_placeholders)";
        $question_stmt = $conn->prepare($question_query);

        $types = str_repeat('i', count($question_ids));
        $refs = [];
        foreach ($question_ids as $key => $value) {
            $refs[$key] = &$question_ids[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$question_stmt, 'bind_param'], $refs);
    }
}

// Execute query and fetch questions
if (isset($question_stmt)) {
    $question_stmt->execute();
    $question_result = $question_stmt->get_result();

    while ($question = $question_result->fetch_assoc()) {
        $options = json_decode($question['options'], true) ?? [];
        $correct_answer = $question['question_type'] === 'multiple_select' 
            ? json_decode($question['correct_answer'], true) ?? []
            : json_decode($question['correct_answer'], true) ?? [];

        // Format correct answer for multiple-choice
        $formatted_correct_answer = [];
        if ($question['question_type'] === 'multiple_choice') {
            foreach ($options as $index => $option) {
                if (isset($option['isCorrect']) && $option['isCorrect']) {
                    $formatted_correct_answer = [(string)$index];
                    break;
                }
            }
        } elseif ($question['question_type'] === 'multiple_select') {
            foreach ($options as $index => $option) {
                if (isset($option['isCorrect']) && $option['isCorrect']) {
                    $formatted_correct_answer[] = (string)$index;
                }
            }
        } else {
            $formatted_correct_answer = is_array($correct_answer) ? $correct_answer : [(string)$correct_answer];
        }

        // Parse options to extract text and image
        $formatted_options = [];
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($options as $index => $opt) {
            $option_text = $opt['text'];
            $display_text = '';
            $display_image = '';

            $doc = new DOMDocument();
            @$doc->loadHTML('<?xml encoding="UTF-8">' . $option_text);
            $xpath = new DOMXpath($doc);

            $text_nodes = $xpath->query('//text()[normalize-space()]');
            foreach ($text_nodes as $node) {
                $display_text .= trim($node->nodeValue) . ' ';
            }
            $display_text = trim($display_text);

            $images = $xpath->query('//img');
            if ($images->length > 0) {
                $display_image = $images->item(0)->getAttribute('src');
            }

            $formatted_options[] = [
                'value' => (string)$index,
                'text' => $display_text,
                'image' => $display_image,
                'letter' => isset($letters[$index]) ? $letters[$index] : ($index + 1)
            ];
        }

        $formatted_question = [
            'id' => $question['id'],
            'text' => strip_tags($question['question_text']),
            'type' => $question['question_type'] === 'multiple_choice' ? 'multiple-choice' : $question['question_type'],
            'options' => $formatted_options,
            'image_path' => $question['image_path'] ?? '',
            'topic' => $question['topic'] ?? 'N/A',
            'difficulty' => $question['difficulty'] ?? 'N/A',
            'correct_answer' => $question['question_type'] === 'multiple_select' ? $formatted_correct_answer : $formatted_correct_answer[0] ?? '',
            'question_image' => $question['question_image'] ?? ''
        ];

        if ($question['question_type'] === 'multiple_select') {
            $formatted_question['correct_answers'] = $formatted_correct_answer;
        }

        $questions[] = $formatted_question;
    }

    $question_stmt->close();
}

// Fallback if not enough questions
if (count($questions) < $actual_question_count) {
    $fallback_query = "SELECT id, question_text, question_image, question_type, options, correct_answer, image_path, topic, difficulty FROM sual_banki ORDER BY RAND() LIMIT ?";
    $fallback_stmt = $conn->prepare($fallback_query);
    $needed = (int)$actual_question_count - count($questions);
    $fallback_stmt->bind_param("i", $needed);
    $fallback_stmt->execute();
    $fallback_result = $fallback_stmt->get_result();

    while ($question = $fallback_result->fetch_assoc()) {
        $options = json_decode($question['options'], true) ?? [];
        $correct_answer = $question['question_type'] === 'multiple_select' 
            ? json_decode($question['correct_answer'], true) ?? []
            : json_decode($question['correct_answer'], true) ?? [];

        $formatted_correct_answer = [];
        if ($question['question_type'] === 'multiple_choice') {
            foreach ($options as $index => $option) {
                if (isset($option['isCorrect']) && $option['isCorrect']) {
                    $formatted_correct_answer = [(string)$index];
                    break;
                }
            }
        } elseif ($question['question_type'] === 'multiple_select') {
            foreach ($options as $index => $option) {
                if (isset($option['isCorrect']) && $option['isCorrect']) {
                    $formatted_correct_answer[] = (string)$index;
                }
            }
        } else {
            $formatted_correct_answer = is_array($correct_answer) ? $correct_answer : [(string)$correct_answer];
        }

        $formatted_options = [];
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($options as $index => $opt) {
            $option_text = $opt['text'];
            $display_text = '';
            $display_image = '';

            $doc = new DOMDocument();
            @$doc->loadHTML('<?xml encoding="UTF-8">' . $option_text);
            $xpath = new DOMXpath($doc);

            $text_nodes = $xpath->query('//text()[normalize-space()]');
            foreach ($text_nodes as $node) {
                $display_text .= trim($node->nodeValue) . ' ';
            }
            $display_text = trim($display_text);

            $images = $xpath->query('//img');
            if ($images->length > 0) {
                $display_image = $images->item(0)->getAttribute('src');
            }

            $formatted_options[] = [
                'value' => (string)$index,
                'text' => $display_text,
                'image' => $display_image,
                'letter' => isset($letters[$index]) ? $letters[$index] : ($index + 1)
            ];
        }

        $formatted_question = [
            'id' => $question['id'],
            'text' => strip_tags($question['question_text']),
            'type' => $question['question_type'] === 'multiple_choice' ? 'multiple-choice' : $question['question_type'],
            'options' => $formatted_options,
            'image_path' => $question['image_path'] ?? '',
            'topic' => $question['topic'] ?? 'N/A',
            'difficulty' => $question['difficulty'] ?? 'N/A',
            'correct_answer' => $question['question_type'] === 'multiple_select' ? $formatted_correct_answer : $formatted_correct_answer[0] ?? '',
            'question_image' => $question['question_image'] ?? ''
        ];

        if ($question['question_type'] === 'multiple_select') {
            $formatted_question['correct_answers'] = $formatted_correct_answer;
        }

        $questions[] = $formatted_question;
    }

    $fallback_stmt->close();
}

// Limit questions to the specified number
$questions = array_slice($questions, 0, $actual_question_count);

// Map difficulty and topic to readable values
$difficulty_map = [
    '1' => 'Asan',
    '2' => 'Orta',
    '3' => 'Çətin',
    'N/A' => 'Bilinməyən'
];

// Apply mappings to questions
foreach ($questions as &$question) {
    $question['topic'] = $topic_map[$question['topic']] ?? $question['topic'];
    $question['difficulty'] = $difficulty_map[$question['difficulty']] ?? $question['difficulty'];
}

// Encode questions for JavaScript
$questions_json = json_encode($questions);
?>


<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['exam_name']); ?> - İmtahan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
                    /* General Reset and Base Styles */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            /* Container */
            .page-wrapper {
                max-width: 100%;
                margin: 0 auto;
            }

            /* Card Styling */
            .card {
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
                background-color: #fff;
                transition: transform 0.2s ease;
            }

            .card:hover {
                transform: translateY(-2px);
            }

            /* Option Images */
            .option-image-wrapper {
                display: inline-block;
                margin-right: 12px;
            }

            .option-image {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 6px;
                cursor: pointer;
                transition: transform 0.2s ease;
            }

            .option-image:hover {
                transform: scale(1.05);
            }

            /* Option Letters */
            .option-letter {
                font-weight: 600;
                min-width: 24px;
                color: #1a73e8;
            }



        .question-image {
            max-width: 100%;
            max-height: 300px;
            margin: 15px auto;
            cursor: pointer;
            display: block;
            border-radius: 5px;
        }


     .question-image-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
            cursor: pointer;
            overflow: hidden;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .question-image {
            max-width: 300px;
            max-height: 200px;
            transition: transform 0.3s ease;
        }
        
        .question-image-wrapper:hover .question-image {
            opacity: 0.8;
        }
        
        .question-image-wrapper:hover .zoom-icon {
            opacity: 1;
        }

        .zoom-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        /* Image Modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            transition: opacity 0.3s ease;
        }

        .image-modal.active {
            display: flex;
            opacity: 1;
        }

        .image-modal-content {
            max-width: 90%;
            max-height: 90%;
        }

        .modal-image {
            width: 100%;
            height: auto;
            border: 3px solid #fff;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            top: 16px;
            right: 16px;
            color: #fff;
            font-size: 32px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close-modal:hover {
            color: #ddd;
        }

        /* Question Options */
        .question-option {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .question-option:hover {
            background-color: #f0f4ff;
            border-color: #1a73e8;
        }

        .question-option.selected {
            background-color: #e6f0ff;
            border-color: #1a73e8;
        }

        .question-option .form-check-input {
            margin-right: 12px;
        }

        /* Progress Bar */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }

        .progress-bar {
            background-color: #1a73e8;
            transition: width 0.3s ease-in-out;
        }

        /* Navigation Grid */
        .question-nav-button {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 14px;
            margin: 4px;
            transition: all 0.2s ease;
        }

        .question-nav-button.current {
            background-color: #1a73e8;
            color: #fff;
        }

        .question-nav-button.answered {
            background-color: #28a745;
            color: #fff;
        }

        .question-nav-button.marked {
            border: 2px solid #ff9500;
        }

        .question-nav-button:hover {
            background-color: #e6f0ff;
            color: black;
        }

        /* Difficulty Badges */
        .difficulty-easy {
            color: #28a745;
            font-weight: 500;
        }

        .difficulty-medium {
            color: #ff9500;
            font-weight: 500;
        }

        .difficulty-hard {
            color: #dc3545;
            font-weight: 500;
        }

        /* Timer */
        #timer {
            font-size: 1.25rem;
            font-weight: 500;
        }

        #timer.warning {
            color: #dc3545;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: #1a73e8;
            border-color: #1a73e8;
        }

        .btn-primary:hover {
            background-color: #1557b0;
            border-color: #1557b0;
        }

        .btn-outline-primary {
            border-color: #1a73e8;
            color: #1a73e8;
        }

        .btn-outline-primary:hover {
            background-color: #e6f0ff;
            border-color: #1a73e8;
            color: black;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #b02a37;
            border-color: #b02a37;
        }

        /* Typography */
        h3.card-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        h5 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        /* Exam Intro */
        #exam-intro .table {
            font-size: 0.95rem;
        }

        #exam-intro .table th {
            background-color: #f8f9fa;
            font-weight: 500;
        }

        /* Question Card */
        #question-card .card-header {
            background-color: #f8f9fa;
            padding: 12px 20px;
        }

        #question-text {
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Results Container */
        #results-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 20px;
        }

        #results-container .card-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1a3c6d;
            margin-bottom: 24px;
            text-align: center;
        }

        /* Result Circle */
        .result-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.25rem;
            font-weight: 600;
            border: 4px solid;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .result-circle.pass {
            border-color: #28a745;
            background-color: #e6f7e6;
            color: #28a745;
        }

        .result-circle.fail {
            border-color: #dc3545;
            background-color: #ffe6e6;
            color: #dc3545;
        }

        .result-circle:hover {
            transform: scale(1.05);
        }

        /* Results Message */
        #results-message {
            font-size: 1.1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        #results-message.text-success {
            color: #28a745;
        }

        #results-message.text-danger {
            color: #dc3545;
        }

        #results-message i {
            margin-right: 8px;
        }

        /* Result Stats */
        .result-stat {
            text-align: center;
            margin-bottom: 16px;
        }

        .result-stat h3 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .result-stat p {
            font-size: 0.95rem;
            color: #6c757d;
            margin: 0;
        }

        .text-success h3 {
            color: #28a745;
        }

        .text-danger h3 {
            color: #dc3545;
        }

        .text-primary h3 {
            color: #1a73e8;
        }

        /* Action Buttons in Results */
        #results-container .btn {
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 500;
            margin: 0 8px;
        }

        /* Detailed Results Accordion */
        #detailed-results {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        #detailed-results .card-header {
            background-color: #f8f9fa;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
        }

        #detailed-results h5 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a3c6d;
            margin: 0;
        }

        .accordion-item {
            border: none;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 8px;
        }

        .accordion-button {
            background-color: #fafafa;
            color: #333;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 12px 16px;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .accordion-button:not(.collapsed) {
            background-color: #e6f0ff;
            color: #1a73e8;
        }

        .accordion-button:focus {
            box-shadow: none;
            outline: 2px solid #1a73e8;
        }

        .accordion-button .badge {
            font-size: 0.8rem;
            margin-right: 6px;
            padding: 4px 8px;
        }

        .accordion-body {
            padding: 16px;
            background-color: #fff;
            border-radius: 0 0 8px 8px;
        }

        .list-group-item {
            padding: 10px 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .list-group-item-success {
            background-color: #e6f7e6;
            border-color:rgb(15, 167, 50);
        }

        .list-group-item-danger {
            background-color: #ffe6e6;
            border-color: #dc3545;
        }

        .list-group-item .form-check-input {
            margin-left: 6px;
            display: none;
        }

        .list-group-item .option-letter {
            font-weight: 600;
            color: #1a73e8;
            min-width: 24px;
            margin-right: 8px;
        }

        .list-group-item .option-image-wrapper {
            margin-right: 12px;
        }

        .list-group-item .option-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .list-group-item .option-image:hover {
            transform: scale(1.05);
        }

        /* Question Image in Accordion */
        .accordion-body .question-image-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 16px;
        }

        .accordion-body .question-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .accordion-body .question-image:hover {
            opacity: 0.9;
        }

        .accordion-body .zoom-icon {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 6px;
            border-radius: 4px;
            font-size: 14px;
        }

        /* Toast Notification */
        .toast {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Modal */
        .modal-content {
            border-radius: 12px;
            padding: 16px;
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
            .page-wrapper {
                padding: 1px;
            }

            .card {
                margin-bottom: 15px;
            }

            .question-nav-button {
                width: 36px;
                height: 36px;
                font-size: 13px;
            }

            #question-text {
                font-size: 1rem;
            }

            .question-option {
                padding: 10px;
                margin-bottom: 10px;
            }

            #results-container {
                padding: 20px;
                margin-bottom: 16px;
            }

            #results-container .card-title {
                font-size: 1.5rem;
            }

            .result-circle {
                width: 90px;
                height: 90px;
                font-size: 1.1rem;
            }

            #results-message {
                font-size: 1rem;
            }

            .result-stat h3 {
                font-size: 1.5rem;
            }

            .result-stat p {
                font-size: 0.9rem;
            }

            #detailed-results {
                padding: 16px;
            }

            #detailed-results h5 {
                font-size: 1.1rem;
            }

            .accordion-button {
                font-size: 0.9rem;
                padding: 10px 14px;
            }

            .list-group-item {
                font-size: 0.85rem;
                padding: 8px 10px;
            }

            .list-group-item .option-image {
                width: 45px;
                height: 45px;
            }
        }

                .difficulty-easy {
                    padding: 6px;
                    font-weight: bolder;
                    border-radius:6px;
                    background-color: #d1e7dd;
                    color: #0f5132;
                }
                
                .difficulty-medium {
                    padding: 6px;
                    font-weight: bolder;
                    border-radius:6px;
                    background-color: #fff3cd;
                    color: #664d03;
                }
                
                .difficulty-hard {
                    padding: 6px;
                    font-weight: bolder;
                    border-radius:6px;
                    background-color: #f8d7da;
                    color: #842029;
                }

        @media (max-width: 767.98px) {
            body {
                padding: 0px;
            }

            .page-wrapper {
                padding: 10px;
            }

            .card {
                margin-bottom: 12px;
            }

            #timer {
                font-size: 1rem;
            }

            .progress {
                height: 6px;
            }

            .question-nav-button {
                width: 32px;
                height: 32px;
                font-size: 12px;
                margin: 3px;
            }

            .question-option {
                padding: 8px;
                margin-bottom: 8px;
            }

            .option-image {
                width: 40px;
                height: 40px;
            }

            .option-letter {
                min-width: 20px;
            }

            #question-text {
                font-size: 0.95rem;
            }

            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            h3.card-title {
                font-size: 1.25rem;
            }

            h5 {
                font-size: 1.1rem;
            }

            #results-container {
                padding: 16px;
                margin-bottom: 12px;
            }

            #results-container .card-title {
                font-size: 1.25rem;
            }

            .result-circle {
                width: 80px;
                height: 80px;
                font-size: 1rem;
            }

            #results-message {
                font-size: 0.95rem;
            }

            .result-stat h3 {
                font-size: 1.25rem;
            }

            .result-stat p {
                font-size: 0.85rem;
            }

            #results-container .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
                margin: 4px;
            }

            #detailed-results {
                padding: 12px;
            }

            #detailed-results h5 {
                font-size: 1rem;
            }

            .accordion-button {
                font-size: 0.85rem;
                padding: 8px 12px;
            }

            .accordion-body {
                padding: 12px;
            }

            .list-group-item {
                font-size: 0.8rem;
                padding: 6px 8px;
            }

            .list-group-item .option-image {
                width: 40px;
                height: 40px;
            }

            .list-group-item .option-letter {
                min-width: 20px;
            }
        }
    </style>
</head>
<body>
    <br>
    <div class="page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Image Modal (Lightbox) -->
                    <div id="imageModal" class="image-modal">
                        <span class="close-modal">×</span>
                        <div class="image-modal-content">
                            <img id="modalImage" class="modal-image" src="/placeholder.svg" alt="Böyüdülmüş şəkil">
                        </div>
                    </div>
                    
                    <!-- Exam Introduction -->
                    <div class="card" id="exam-intro">
                        <div class="card-body text-center">
                            <h3 class="card-title mb-4"><?php echo htmlspecialchars($exam['exam_name']); ?></h3>
                            <div class="row justify-content-center mb-4">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr hidden>
                                                    <th>Fənn:</th>
                                                    <td><?php echo htmlspecialchars($fenn_adi); ?></td>
                                                </tr>
                                                <tr hidden>
                                                    <th>Sinif:</th>
                                                    <td><?php echo htmlspecialchars($exam['sinif'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Sual sayı:</th>
                                                    <td><?php echo htmlspecialchars($actual_question_count); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Keçid balı:</th>
                                                    <td><?php echo htmlspecialchars($exam['passing_score'] ?? '0'); ?>%</td>
                                                </tr>
                                                <tr>
                                                    <th>Müddət:</th>
                                                    <td><?php echo htmlspecialchars($exam['duration'] ?? 'N/A'); ?> dəqiqə</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">İmtahan Qaydaları</h5>
                                </div>
                                <div class="card-body text-start">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> İmtahan başladıqdan sonra vaxt dayandırıla bilməz.</li>
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Sualları istədiyiniz ardıcıllıqla cavablandıra bilərsiniz.</li>
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Sualları sonra baxmaq üçün qeyd edə bilərsiniz.</li>
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> İmtahan bitdikdən sonra nəticələrinizi görə biləcəksiniz.</li>
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> İmtahanı bitirmək üçün "İmtahanı Bitir" düyməsini basın.</li>
                                    </ul>
                                </div>
                            </div>
                            <button id="start-exam" class="btn btn-primary btn-lg">
                                <i class="fas fa-play me-2"></i> İmtahana Başla
                            </button>
                        </div>
                    </div>
                    <!-- Exam Container -->
                    <div id="exam-container" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <div id="timer" class="d-flex align-items-center">
                                            <i class="fas fa-clock me-2 text-primary"></i>
                                            <span id="time-left" class="h5 mb-0">00:00</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Tamamlanma: <span id="progress-percent">0%</span></span>
                                                <span><span id="answered-count">0</span>/<span id="total-questions"><?php echo count($questions); ?></span> sual</span>
                                            </div>
                                            <div class="progress">
                                                <div id="progress-fill" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button style="margin-top:8px;" id="finish-exam" class="mb-2 btn btn-danger">
                                            <i class="fas fa-stop-circle me-2"></i> İmtahanı Bitir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Question Categories Tabs -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="questionCategories" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-questions" type="button" role="tab" aria-controls="all-questions" aria-selected="true">Bütün Suallar</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="topic-tab" data-bs-toggle="tab" data-bs-target="#topic-questions" type="button" role="tab" aria-controls="topic-questions" aria-selected="false">Mövzular</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="difficulty-tab" data-bs-toggle="tab" data-bs-target="#difficulty-questions" type="button" role="tab" aria-controls="difficulty-questions" aria-selected="false">Çətinlik</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="marked-tab" data-bs-toggle="tab" data-bs-target="#marked-questions" type="button" role="tab" aria-controls="marked-questions" aria-selected="false">Qeyd Edilmiş</button>
                                    </li>
                                </ul>
                                <div class="tab-content mt-3">
                                    <div class="tab-pane fade show active" id="all-questions" role="tabpanel" aria-labelledby="all-tab"></div>
                                    <div class="tab-pane fade" id="topic-questions" role="tabpanel" aria-labelledby="topic-tab"></div>
                                    <div class="tab-pane fade" id="difficulty-questions" role="tabpanel" aria-labelledby="difficulty-tab"></div>
                                    <div class="tab-pane fade" id="marked-questions" role="tabpanel" aria-labelledby="marked-tab"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-4" id="question-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <span id="question-number" class="badge bg-primary">Sual 1/<?php echo count($questions); ?></span>
                                            <span id="question-topic" class="question-category"></span>
                                            <span id="question-difficulty" class="question-difficulty"></span>
                                        </div>
                                        <button id="mark-button" class="btn btn-sm btn-outline-warning" title="Qeyd et">
                                            <i class="fas fa-bookmark"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="question-text" class="mb-4 fs-5"></div>
                                        <!-- Updated Container for question_image from database -->
                                        <div id="question-db-image-container" class="mb-4 text-center" style="display: none;">
                                            <div class="question-image-wrapper">
                                                <img id="question-db-image" src="/placeholder.svg" alt="Sual şəkli" class="question-image">
                                                <div class="zoom-icon">
                                                    <i class="fas fa-search-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Updated Container for image_path (existing) -->
                                        <div id="question-image-container" class="mb-4 text-center" style="display: none;">
                                            <div class="question-image-wrapper">
                                                <img id="question-image" src="/placeholder.svg" alt="Sual şəkli" class="question-image">
                                                <div class="zoom-icon">
                                                    <i class="fas fa-search-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="options-container" class="mt-4"></div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <button id="prev-button" class="btn btn-outline-primary" disabled>
                                            <i class="fas fa-chevron-left me-2"></i> Əvvəlki
                                        </button>
                                        <button id="next-button" class="btn btn-primary">
                                            Növbəti <i class="fas fa-chevron-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Sualların Statusu</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="nav-grid" class="d-flex flex-wrap gap-2 mb-3"></div>
                                        <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary me-2" style="width: 16px; height: 16px; border-radius: 4px;"></div>
                                                <span>Hazırkı</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-success me-2" style="width: 16px; height: 16px; border-radius: 4px;"></div>
                                                <span>Cavablanmış</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2" style="width: 16px; height: 16px; border-radius: 4px; border: 2px solid #f8961e;"></div>
                                                <span>Qeyd edilmiş</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2" style="width: 16px; height: 16px; border-radius: 4px; border: 1px solid #dee2e6;"></div>
                                                <span>Cavablanmamış</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Results Container -->
                    <div id="results-container" class="card" style="display: none;">
                        <div class="card-body">
                            <h3 class="card-title text-center mb-4">İmtahan Nəticələri</h3>
                            <div class="text-center mb-4">
                                <div id="results-circle" class="result-circle">
                                    <span id="results-score">0%</span>
                                </div>
                                <div id="results-message" class="d-flex align-items-center justify-content-center mb-4"></div>
                            </div>
                            <div class="row justify-content-center mb-5">
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="result-stat">
                                                <h3 id="correct-count" class="text-success">0</h3>
                                                <p>Doğru Cavablar</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="result-stat">
                                                <h3 id="incorrect-count" class="text-danger">0</h3>
                                                <p>Səhv Cavablar</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="result-stat">
                                                <h3 id="total-count" class="text-primary">0</h3>
                                                <p>Ümumi Suallar</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="../Examination.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i> İmtahanlara Qayıt
                                </a>
                                <a href="imtahan-start.php?id=<?php echo $exam_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i> Yenidən Başla
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Detailed Results -->
                    <div id="detailed-results" class="card mt-4" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0">Ətraflı Nəticələr</h5>
                        </div>
                        <div class="card-body">
                            <div id="results-details" class="accordion"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Confirm Finish Modal -->
    <div class="modal fade" id="finish-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">İmtahanı bitirmək istəyirsiniz?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modal-message">İmtahanı bitirmək istədiyinizə əminsiniz? Bu əməliyyat geri qaytarıla bilməz.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Geri qayıt</button>
                    <button type="button" class="btn btn-danger" id="modal-confirm">İmtahanı bitir</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Toast Notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="toast-notification" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toast-title">Bildiriş</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-message">Sual qeyd edildi</div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
 
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Image modal functionality
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const closeModal = document.querySelector('.close-modal');
    
    // Function to open the image modal
    function openImageModal(imageSrc) {
        modalImage.src = imageSrc;
        imageModal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }
    
    // Function to close the image modal
    function closeImageModal() {
        imageModal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
    
    // Close modal when clicking the close button
    closeModal.addEventListener('click', closeImageModal);
    
    // Close modal when clicking outside the image
    imageModal.addEventListener('click', function(event) {
        if (event.target === imageModal) {
            closeImageModal();
        }
    });
    
    // Close modal when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && imageModal.style.display === 'flex') {
            closeImageModal();
        }
    });
    
    // Existing variables and setup
    const examDuration = <?php echo $exam['duration'] ?? 60; ?> * 60;
    const passingScore = <?php echo $exam['passing_score'] ?? 60; ?>;
    const questions = <?php echo $questions_json; ?>;
    let currentQuestionIndex = 0;
    let userAnswers = Array(questions.length).fill(null);
    let markedQuestions = [];
    let timeLeft = examDuration;
    let timerInterval;
    let examStartTime;
    let currentCategory = 'all';
    const toastNotification = new bootstrap.Toast(document.getElementById('toast-notification'));

    function showToast(title, message) {
        document.getElementById('toast-title').textContent = title;
        document.getElementById('toast-message').textContent = message;
        toastNotification.show();
    }

    document.getElementById("start-exam").addEventListener("click", function() {
        document.getElementById("exam-intro").style.display = "none";
        document.getElementById("exam-container").style.display = "block";
        startExam();
    });

    function startExam() {
        examStartTime = new Date();
        updateTimer();
        timerInterval = setInterval(function() {
            timeLeft--;
            updateTimer();
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                finishExam();
            }
        }, 1000);
        loadQuestion(currentQuestionIndex);
        initNavGrid();
        initCategoryTabs();
    }

    function updateTimer() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        const formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        document.getElementById("time-left").textContent = formattedTime;
        if (timeLeft < 300) {
            document.getElementById("timer").classList.add("warning");
        }
    }

    function initCategoryTabs() {
        const topics = [...new Set(questions.map(q => q.topic))];
        const difficulties = [...new Set(questions.map(q => q.difficulty))];

        document.getElementById('all-tab').addEventListener('click', function() {
            currentCategory = 'all';
            currentQuestionIndex = 0;
            loadQuestion(currentQuestionIndex);
        });

        document.getElementById('topic-tab').addEventListener('click', function() {
            const topicTab = document.getElementById('topic-questions');
            if (!topicTab.hasChildNodes()) {
                const topicButtons = document.createElement('div');
                topicButtons.className = 'mt-3 d-flex flex-wrap gap-2';
                topics.forEach(topic => {
                    const button = document.createElement('button');
                    button.className = 'btn btn-outline-primary';
                    button.textContent = topic;
                    button.addEventListener('click', function() {
                        currentCategory = `topic-${topic}`;
                        const index = questions.findIndex(q => q.topic === topic);
                        if (index !== -1) {
                            currentQuestionIndex = index;
                            loadQuestion(currentQuestionIndex);
                        }
                    });
                    topicButtons.appendChild(button);
                });
                topicTab.appendChild(topicButtons);
            }
        });

        document.getElementById('difficulty-tab').addEventListener('click', function() {
            const difficultyTab = document.getElementById('difficulty-questions');
            if (!difficultyTab.hasChildNodes()) {
                const difficultyButtons = document.createElement('div');
                difficultyButtons.className = 'mt-3 d-flex flex-wrap gap-2';
                difficulties.forEach(difficulty => {
                    const button = document.createElement('button');
                    button.className = 'btn btn-outline-primary';
                    button.textContent = difficulty;
                    button.addEventListener('click', function() {
                        currentCategory = `difficulty-${difficulty}`;
                        const index = questions.findIndex(q => q.difficulty === difficulty);
                        if (index !== -1) {
                            currentQuestionIndex = index;
                            loadQuestion(currentQuestionIndex);
                        }
                    });
                    difficultyButtons.appendChild(button);
                });
                difficultyTab.appendChild(difficultyButtons);
            }
        });

        document.getElementById('marked-tab').addEventListener('click', function() {
            currentCategory = 'marked';
            if (markedQuestions.length > 0) {
                currentQuestionIndex = markedQuestions[0];
                loadQuestion(currentQuestionIndex);
            }
        });
    }

    function loadQuestion(index) {
        const question = questions[index];
        document.getElementById("question-number").textContent = `Sual ${index + 1}/${questions.length}`;
        document.getElementById("question-text").textContent = question.text;
        const topicElement = document.getElementById("question-topic");
        topicElement.textContent = question.topic || "Ümumi";
        const difficultyElement = document.getElementById("question-difficulty");
        difficultyElement.textContent = question.difficulty || "Orta";
        difficultyElement.className = "question-difficulty";
        if (question.difficulty === "Asan") {
            difficultyElement.classList.add("difficulty-easy");
        } else if (question.difficulty === "Orta") {
            difficultyElement.classList.add("difficulty-medium");
        } else if (question.difficulty === "Çətin") {
            difficultyElement.classList.add("difficulty-hard");
        }

        // Handle image_path (existing functionality with zoom)
        const imageContainer = document.getElementById("question-image-container");
        const questionImage = document.getElementById("question-image");
        if (question.image_path && question.image_path !== "0" && question.image_path !== "") {
            questionImage.src = question.image_path;
            imageContainer.style.display = "block";
            
            // Add click event for image zoom
            const imageWrapper = imageContainer.querySelector('.question-image-wrapper');
            imageWrapper.onclick = function() {
                openImageModal(question.image_path);
            };
        } else {
            imageContainer.style.display = "none";
        }

        // Handle question_image from database with zoom
        const dbImageContainer = document.getElementById("question-db-image-container");
        const dbQuestionImage = document.getElementById("question-db-image");
        if (question.question_image && question.question_image !== "0" && question.question_image !== "") {
            let imageSrc;
            if (question.question_image.startsWith('data:image')) {
                imageSrc = question.question_image;
                dbQuestionImage.src = imageSrc;
            } else {
                imageSrc = `data:image/jpeg;base64,${question.question_image}`;
                dbQuestionImage.src = imageSrc;
            }
            dbImageContainer.style.display = "block";
            
            // Add click event for image zoom
            const dbImageWrapper = dbImageContainer.querySelector('.question-image-wrapper');
            dbImageWrapper.onclick = function() {
                openImageModal(imageSrc);
            };
        } else {
            dbImageContainer.style.display = "none";
        }

        if (markedQuestions.includes(index)) {
            document.getElementById("mark-button").classList.add("btn-warning");
            document.getElementById("mark-button").classList.remove("btn-outline-warning");
        } else {
            document.getElementById("mark-button").classList.remove("btn-warning");
            document.getElementById("mark-button").classList.add("btn-outline-warning");
        }

        const optionsContainer = document.getElementById("options-container");
        optionsContainer.innerHTML = '';

        if (question.type === "multiple-choice" || question.type === "true-false") {
            question.options.forEach(option => {
                const isSelected = userAnswers[index] === option.value;
                const optionDiv = document.createElement("div");
                optionDiv.className = `question-option ${isSelected ? 'selected' : ''}`;
                optionDiv.dataset.value = option.value;
                const optionContent = document.createElement("div");
                optionContent.className = "d-flex align-items-center";
                
                // Add letter
                const optionLetter = document.createElement("span");
                optionLetter.className = "option-letter me-2";
                optionLetter.textContent = `${option.letter}.`;
                optionContent.appendChild(optionLetter);

                // Add radio input
                const radioInput = document.createElement("input");
                radioInput.type = "radio";
                radioInput.name = "option";
                radioInput.value = option.value;
                radioInput.checked = isSelected;
                radioInput.className = "form-check-input me-3";
                optionContent.appendChild(radioInput);

                // Add image (if present)
                if (option.image && option.image !== "0" && option.image !== "") {
                    const imageWrapper = document.createElement("div");
                    imageWrapper.className = "option-image-wrapper me-3";
                    const optionImage = document.createElement("img");
                    optionImage.src = option.image;
                    optionImage.alt = "Option Image";
                    optionImage.className = "option-image";
                    imageWrapper.appendChild(optionImage);
                    optionContent.appendChild(imageWrapper);
                    // Add click event for image zoom
                    imageWrapper.addEventListener('click', function(e) {
                        e.stopPropagation(); // Prevent option selection when clicking image
                        openImageModal(option.image);
                    });
                }

                // Add text
                const optionText = document.createElement("span");
                optionText.textContent = option.text;
                optionContent.appendChild(optionText);

                optionDiv.appendChild(optionContent);
                optionsContainer.appendChild(optionDiv);
                optionDiv.addEventListener("click", function(e) {
                    // Ignore clicks on image
                    if (e.target.classList.contains('option-image')) return;
                    document.querySelectorAll(".question-option").forEach(item => {
                        item.classList.remove("selected");
                    });
                    this.classList.add("selected");
                    this.querySelector("input").checked = true;
                    userAnswers[currentQuestionIndex] = option.value;
                    updateProgress();
                    updateNavGrid();
                });
            });
        } else if (question.type === "multiple-select") {
            question.options.forEach(option => {
                const isSelected = Array.isArray(userAnswers[index]) && userAnswers[index]?.includes(option.value);
                const optionDiv = document.createElement("div");
                optionDiv.className = `question-option ${isSelected ? 'selected' : ''}`;
                optionDiv.dataset.value = option.value;
                const optionContent = document.createElement("div");
                optionContent.className = "d-flex align-items-center";
                
                // Add letter
                const optionLetter = document.createElement("span");
                optionLetter.className = "option-letter me-2";
                optionLetter.textContent = `${option.letter}.`;
                optionContent.appendChild(optionLetter);

                // Add checkbox input
                const checkboxInput = document.createElement("input");
                checkboxInput.type = "checkbox";
                checkboxInput.name = "checkbox";
                checkboxInput.value = option.value;
                checkboxInput.checked = isSelected;
                checkboxInput.className = "form-check-input me-3";
                optionContent.appendChild(checkboxInput);

                // Add image (if present)
                if (option.image && option.image !== "0" && option.image !== "") {
                    const imageWrapper = document.createElement("div");
                    imageWrapper.className = "option-image-wrapper me-3";
                    const optionImage = document.createElement("img");
                    optionImage.src = option.image;
                    optionImage.alt = "Option Image";
                    optionImage.className = "option-image";
                    imageWrapper.appendChild(optionImage);
                    optionContent.appendChild(imageWrapper);
                    // Add click event for image zoom
                    imageWrapper.addEventListener('click', function(e) {
                        e.stopPropagation(); // Prevent option selection when clicking image
                        openImageModal(option.image);
                    });
                }

                // Add text
                const optionText = document.createElement("span");
                optionText.textContent = option.text;
                optionContent.appendChild(optionText);

                optionDiv.appendChild(optionContent);
                optionsContainer.appendChild(optionDiv);
                optionDiv.addEventListener("click", function(e) {
                    // Ignore clicks on image
                    if (e.target.classList.contains('option-image')) return;
                    this.classList.toggle("selected");
                    const isChecked = this.classList.contains("selected");
                    this.querySelector("input").checked = isChecked;
                    const selectedOptions = [];
                    document.querySelectorAll('input[name="checkbox"]:checked').forEach(function(checkbox) {
                        selectedOptions.push(checkbox.value);
                    });
                    userAnswers[currentQuestionIndex] = selectedOptions.length > 0 ? selectedOptions : null;
                    updateProgress();
                    updateNavGrid();
                });
            });
        }

        document.getElementById("prev-button").disabled = index === 0;
        const nextButton = document.getElementById("next-button");
        if (index === questions.length - 1) {
            nextButton.textContent = "İmtahanı Bitir";
            nextButton.classList.add("btn-success");
            nextButton.classList.remove("btn-primary");
        } else {
            nextButton.textContent = "Növbəti";
            nextButton.innerHTML = 'Növbəti <i class="fas fa-chevron-right ms-2"></i>';
            nextButton.classList.add("btn-primary");
            nextButton.classList.remove("btn-success");
        }

        updateNavGrid();
    }

    function initNavGrid() {
        const navGrid = document.getElementById("nav-grid");
        navGrid.innerHTML = '';
        for (let i = 0; i < questions.length; i++) {
            const navItem = document.createElement("button");
            navItem.className = "question-nav-button btn btn-outline-secondary";
            navItem.textContent = i + 1;
            navItem.setAttribute("data-index", i);
            navItem.addEventListener("click", function() {
                currentQuestionIndex = parseInt(this.getAttribute("data-index"));
                loadQuestion(currentQuestionIndex);
            });
            navGrid.appendChild(navItem);
        }
        updateNavGrid();
    }

    function updateNavGrid() {
        document.querySelectorAll("#nav-grid button").forEach(function(button) {
            const index = parseInt(button.getAttribute("data-index"));
            button.className = "question-nav-button btn";
            if (index === currentQuestionIndex) {
                button.classList.add("current");
            } else if (userAnswers[index] !== null) {
                button.classList.add("answered");
            } else {
                button.classList.add("btn-outline-secondary");
            }
            if (markedQuestions.includes(index)) {
                button.classList.add("marked");
            }
        });
    }

    function updateProgress() {
        const answeredCount = userAnswers.filter(answer => answer !== null).length;
        const progressPercent = Math.round((answeredCount / questions.length) * 100);
        document.getElementById("progress-percent").textContent = `${progressPercent}%`;
        document.getElementById("answered-count").textContent = answeredCount;
        document.getElementById("progress-fill").style.width = `${progressPercent}%`;
    }

    document.getElementById("prev-button").addEventListener("click", function() {
        if (currentQuestionIndex > 0) {
            currentQuestionIndex--;
            loadQuestion(currentQuestionIndex);
        }
    });

    document.getElementById("next-button").addEventListener("click", function() {
        if (currentQuestionIndex < questions.length - 1) {
            currentQuestionIndex++;
            loadQuestion(currentQuestionIndex);
        } else {
            showFinishModal();
        }
    });

    document.getElementById("mark-button").addEventListener("click", function() {
        if (markedQuestions.includes(currentQuestionIndex)) {
            markedQuestions = markedQuestions.filter(index => index !== currentQuestionIndex);
            this.classList.remove("btn-warning");
            this.classList.add("btn-outline-warning");
            showToast("Qeyd edilmədi", "Bu sual qeyd edilənlərdən çıxarıldı");
        } else {
            markedQuestions.push(currentQuestionIndex);
            this.classList.add("btn-warning");
            this.classList.remove("btn-outline-warning");
            showToast("Qeyd edildi", "Bu sual sonra baxmaq üçün qeyd edildi");
        }
        updateNavGrid();
    });

    document.getElementById("finish-exam").addEventListener("click", function() {
        showFinishModal();
    });

    function showFinishModal() {
        const answeredCount = userAnswers.filter(answer => answer !== null).length;
        const unansweredCount = questions.length - answeredCount;
        const modalMessage = document.getElementById("modal-message");
        if (unansweredCount > 0) {
            modalMessage.innerHTML = `<div class="text-danger mb-2"><i class="fas fa-exclamation-triangle me-2"></i> Diqqət! ${unansweredCount} sual cavabsız qalıb.</div><p>İmtahanı bitirmək istədiyinizə əminsiniz?</p>`;
        } else {
            modalMessage.innerHTML = `<div class="text-success mb-2"><i class="fas fa-check-circle me-2"></i> Bütün sualları cavablandırmısınız.</div><p>İmtahanı bitirmək istədiyinizə əminsiniz?</p>`;
        }
        const finishModal = new bootstrap.Modal(document.getElementById('finish-modal'));
        finishModal.show();
    }

    document.getElementById("modal-confirm").addEventListener("click", function() {
        const finishModal = bootstrap.Modal.getInstance(document.getElementById('finish-modal'));
        finishModal.hide();
        finishExam();
    });

    function finishExam() {
        clearInterval(timerInterval);
        const totalQuestions = questions.length;
        let correctCount = 0;

        const detailedResults = questions.map((question, index) => {
            const userAnswer = userAnswers[index];
            let isCorrect = false;

            if (question.type === "multiple-choice" || question.type === "true-false") {
                isCorrect = userAnswer === question.correct_answer;
            } else if (question.type === "multiple-select" && Array.isArray(userAnswer)) {
                const correctAnswers = question.correct_answers || [];
                isCorrect = userAnswer.length === correctAnswers.length && 
                           userAnswer.every(answer => correctAnswers.includes(answer)) &&
                           correctAnswers.every(answer => userAnswer.includes(answer));
            }

            if (isCorrect) correctCount++;

            return {
                question,
                userAnswer,
                isCorrect
            };
        });

        const incorrectCount = totalQuestions - correctCount;
        const scorePercent = Math.round((correctCount / totalQuestions) * 100);
        const isPassed = scorePercent >= passingScore;

        const resultsScore = document.getElementById("results-score");
        resultsScore.textContent = `${scorePercent}%`;
        const resultsCircle = document.getElementById("results-circle");
        if (isPassed) {
            resultsCircle.classList.add("pass");
        } else {
            resultsCircle.classList.add("fail");
        }

        document.getElementById("correct-count").textContent = correctCount;
        document.getElementById("incorrect-count").textContent = incorrectCount;
        document.getElementById("total-count").textContent = totalQuestions;

        const resultsMessage = document.getElementById("results-message");
        if (isPassed) {
            resultsMessage.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> Təbriklər! İmtahanı uğurla tamamladınız.';
            resultsMessage.className = "d-flex align-items-center justify-content-center mb-4 text-success";
        } else {
            resultsMessage.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i> Təəssüf! İmtahandan keçə bilmədiniz.';
            resultsMessage.className = "d-flex align-items-center justify-content-center mb-4 text-danger";
        }

        const resultsDetails = document.getElementById("results-details");
        resultsDetails.innerHTML = '';

        detailedResults.forEach((result, index) => {
            const accordionItem = document.createElement("div");
            accordionItem.className = "accordion-item";
            const accordionHeader = document.createElement("h2");
            accordionHeader.className = "accordion-header";
            accordionHeader.id = `heading-${index}`;
            const accordionButton = document.createElement("button");
            accordionButton.className = `accordion-button ${index === 0 ? '' : 'collapsed'}`;
            accordionButton.type = "button";
            accordionButton.setAttribute("data-bs-toggle", "collapse");
            accordionButton.setAttribute("data-bs-target", `#collapse-${index}`);
            accordionButton.setAttribute("aria-expanded", index === 0 ? "true" : "false");
            accordionButton.setAttribute("aria-controls", `collapse-${index}`);
            const questionTitle = document.createElement("div");
            questionTitle.className = "d-flex align-items-center w-100";
            const questionNumber = document.createElement("span");
            questionNumber.className = "me-3";
            questionNumber.textContent = `Sual ${index + 1}`;
            const questionText = document.createElement("span");
            questionText.className = "flex-grow-1";
            questionText.textContent = result.question.text;
            const questionStatus = document.createElement("span");
            questionStatus.className = `badge ms-3 ${result.isCorrect ? 'bg-success' : 'bg-danger'}`;
            questionStatus.textContent = result.isCorrect ? 'Doğru' : 'Səhv';
            questionTitle.appendChild(questionNumber);
            questionTitle.appendChild(questionText);
            questionTitle.appendChild(questionStatus);
            accordionButton.appendChild(questionTitle);
            accordionHeader.appendChild(accordionButton);
            const accordionCollapse = document.createElement("div");
            accordionCollapse.id = `collapse-${index}`;
            accordionCollapse.className = `accordion-collapse collapse ${index === 0 ? 'show' : ''}`;
            accordionCollapse.setAttribute("aria-labelledby", `heading-${index}`);
            accordionCollapse.setAttribute("data-bs-parent", "#results-details");
            const accordionBody = document.createElement("div");
            accordionBody.className = "accordion-body";

            // Display question_image if available (with zoom functionality)
            if (result.question.question_image && result.question.question_image !== "0" && result.question.question_image !== "") {
                const imageContainer = document.createElement("div");
                imageContainer.className = "mb-3 text-center";
                
                const imageWrapper = document.createElement("div");
                imageWrapper.className = "question-image-wrapper";
                
                const image = document.createElement("img");
                let imageSrc;
                if (result.question.question_image.startsWith('data:image')) {
                    imageSrc = result.question.question_image;
                } else {
                    imageSrc = `data:image/jpeg;base64,${result.question.question_image}`;
                }
                image.src = imageSrc;
                image.alt = "Sual şəkli";
                image.className = "question-image";
                
                const zoomIcon = document.createElement("div");
                zoomIcon.className = "zoom-icon";
                zoomIcon.innerHTML = '<i class="fas fa-search-plus"></i>';
                
                imageWrapper.appendChild(image);
                imageWrapper.appendChild(zoomIcon);
                imageContainer.appendChild(imageWrapper);
                accordionBody.appendChild(imageContainer);
                
                // Add click event for image zoom
                imageWrapper.addEventListener('click', function() {
                    openImageModal(imageSrc);
                });
            }

            // Display image_path if available (with zoom functionality)
            if (result.question.image_path && result.question.image_path !== "0" && result.question.image_path !== "") {
                const imageContainer = document.createElement("div");
                imageContainer.className = "mb-3 text-center";
                
                const imageWrapper = document.createElement("div");
                imageWrapper.className = "question-image-wrapper";
                
                const image = document.createElement("img");
                image.src = result.question.image_path;
                image.alt = "Sual şəkli";
                image.className = "question-image";
                
                const zoomIcon = document.createElement("div");
                zoomIcon.className = "zoom-icon";
                zoomIcon.innerHTML = '<i class="fas fa-search-plus"></i>';
                
                imageWrapper.appendChild(image);
                imageWrapper.appendChild(zoomIcon);
                imageContainer.appendChild(imageWrapper);
                accordionBody.appendChild(imageContainer);
                
                // Add click event for image zoom
                imageWrapper.addEventListener('click', function() {
                    openImageModal(result.question.image_path);
                });
            }

            const optionsContainer = document.createElement("div");
            optionsContainer.className = "mb-3";
            const optionsTitle = document.createElement("h6");
            optionsTitle.textContent = "Cavab variantları:";
            optionsContainer.appendChild(optionsTitle);
            const optionsList = document.createElement("div");
            optionsList.className = "list-group";

            result.question.options.forEach(option => {
                let optionClass = '';
                let isCorrectAnswer = false;

                if (result.question.type === "multiple-choice" || result.question.type === "true-false") {
                    isCorrectAnswer = option.value === result.question.correct_answer;
                    // Only mark as correct if the user's answer was correct
                    if (isCorrectAnswer && result.isCorrect) {
                        optionClass = 'list-group-item-success';
                    } else if (option.value === result.userAnswer && !result.isCorrect) {
                        optionClass = 'list-group-item-danger';
                    }
                } else if (result.question.type === "multiple-select") {
                    const correctAnswers = result.question.correct_answers || [];
                    isCorrectAnswer = correctAnswers.includes(option.value);
                    // Only mark as correct if the user's answer was correct
                    if (isCorrectAnswer && result.isCorrect) {
                        optionClass = 'list-group-item-success';
                    } else if (Array.isArray(result.userAnswer) && result.userAnswer.includes(option.value) && !result.isCorrect) {
                        optionClass = 'list-group-item-danger';
                    }
                }

                const optionItem = document.createElement("div");
                optionItem.className = `list-group-item ${optionClass} d-flex align-items-center`;
                const optionCheck = document.createElement("div");
                optionCheck.className = "form-check d-flex align-items-center";

                // Add letter
                const optionLetter = document.createElement("span");
                optionLetter.className = "option-letter me-2";
                optionLetter.textContent = `${option.letter}.`;
                optionCheck.appendChild(optionLetter);

                // Add input
                const optionInput = document.createElement("input");
                optionInput.className = "form-check-input me-3";
                if (result.question.type === "multiple-choice" || result.question.type === "true-false") {
                    optionInput.type = "radio";
                    optionInput.disabled = true;
                    optionInput.checked = result.userAnswer === option.value;
                } else if (result.question.type === "multiple-select") {
                    optionInput.type = "checkbox";
                    optionInput.disabled = true;
                    optionInput.checked = Array.isArray(result.userAnswer) && result.userAnswer.includes(option.value);
                }
                optionCheck.appendChild(optionInput);

                // Add image (if present)
                if (option.image && option.image !== "0" && option.image !== "") {
                    const imageWrapper = document.createElement("div");
                    imageWrapper.className = "option-image-wrapper me-3";
                    const optionImage = document.createElement("img");
                    optionImage.src = option.image;
                    optionImage.alt = "Option Image";
                    optionImage.className = "option-image";
                    imageWrapper.appendChild(optionImage);
                    optionCheck.appendChild(imageWrapper);
                    // Add click event for image zoom
                    imageWrapper.addEventListener('click', function() {
                        openImageModal(option.image);
                    });
                }

                // Add text
                const optionLabel = document.createElement("label");
                optionLabel.className = "form-check-label";
                optionLabel.textContent = option.text;
                optionCheck.appendChild(optionLabel);

                optionItem.appendChild(optionCheck);
                optionsList.appendChild(optionItem);
            });

            optionsContainer.appendChild(optionsList);
            accordionBody.appendChild(optionsContainer);

            accordionCollapse.appendChild(accordionBody);
            accordionItem.appendChild(accordionHeader);
            accordionItem.appendChild(accordionCollapse);
            resultsDetails.appendChild(accordionItem);
        });

        document.getElementById("exam-container").style.display = "none";
        document.getElementById("results-container").style.display = "block";
        document.getElementById("detailed-results").style.display = "block";

        const examEndTime = new Date();
        fetch('save-exam-result.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                imtahan_id: <?php echo $exam_id; ?>,
                telebe_id: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>,
                telebe_adi: "<?php echo isset($_SESSION['username']) ? addslashes($_SESSION['username']) : 'Test İstifadəçi'; ?>",
                dogru_cavablar: correctCount,
                sehv_cavablar: incorrectCount,
                umumui_sual_sayi: totalQuestions,
                faiz: scorePercent,
                kecid_statusu: isPassed ? "Keçdi" : "Kəsildi",
                cavablar: JSON.stringify(userAnswers),
                baslama_vaxti: examStartTime.toISOString(),
                bitme_vaxti: examEndTime.toISOString()
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log("Results saved successfully", data);
        })
        .catch(error => {
            console.error("Error saving results:", error);
        });
    }
});
    </script>
 
</body>
</html>