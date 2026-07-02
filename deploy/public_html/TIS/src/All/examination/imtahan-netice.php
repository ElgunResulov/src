<?php
session_start(); // Start session for potential user authentication

// Validate ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: imtahan-list.php");
    exit();
}

require_once '../db.php'; // Use require_once to ensure DB connection is included once

$result_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($result_id === false || $result_id <= 0) {
    header("Location: imtahan-list.php");
    exit();
}

// Fetch result details
$query = "SELECT n.id, n.imtahan_id, n.telebe_id, n.telebe_adi, n.dogru_cavablar, n.sehv_cavablar, 
                 n.umumui_sual_sayi, n.faiz, n.kecid_statusu, n.cavablar, n.baslama_vaxti, n.bitme_vaxti, 
                 n.created_at, i.exam_name, i.fenn_adi, i.sinif, i.passing_score, i.questions 
          FROM imtahan_neticeler n 
          JOIN imtahanlar_exam i ON n.imtahan_id = i.id 
          WHERE n.id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database query preparation failed: " . $conn->error);
}
$stmt->bind_param("i", $result_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: imtahan-list.php");
    exit();
}

$exam_result = $result->fetch_assoc();
$stmt->close();

// Decode user answers
$user_answers = json_decode($exam_result['cavablar'] ?? '[]', true);
$user_answers = is_array($user_answers) ? $user_answers : [];

// Decode exam questions
$exam_questions = json_decode($exam_result['questions'] ?? '[]', true);
$exam_questions = is_array($exam_questions) ? $exam_questions : [];

// Preprocess fenn_adi
$fenn_subject = $exam_result['fenn_adi'];
$fenn_decoded = json_decode($fenn_subject, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($fenn_decoded)) {
    $fenn_subject = $fenn_decoded[0] ?? $fenn_subject; // Fallback to original if empty
}

// Fetch questions from sual_banki
$display_questions = [];
if (!empty($exam_questions)) {
    if (isset($exam_questions[0]['question_text'])) {
        // Full question details already available
        $display_questions = $exam_questions;
    } else {
        // Extract question IDs
        $question_ids = [];
        foreach ($exam_questions as $q_data) {
            if (is_numeric($q_data)) {
                $question_ids[] = (int)$q_data;
            } elseif (is_array($q_data) && (isset($q_data['id']) || isset($q_data['question_id']))) {
                $question_ids[] = (int)($q_data['id'] ?? $q_data['question_id']);
            }
        }

        if (!empty($question_ids)) {
            $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
            $query = "SELECT * FROM sual_banki WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                die("Question query preparation failed: " . $conn->error);
            }
            $types = str_repeat('i', count($question_ids) * 2); // For IN and ORDER BY
            $stmt->bind_param($types, ...array_merge($question_ids, $question_ids));
            $stmt->execute();
            $result = $stmt->get_result();
            while ($question = $result->fetch_assoc()) {
                $display_questions[] = $question;
            }
            $stmt->close();
        }
    }
}

// Fallback: Fetch questions by subject if none found
if (empty($display_questions) && !empty($user_answers)) {
    $limit = count($user_answers);
    $query = "SELECT * FROM sual_banki WHERE subject = ? ORDER BY id LIMIT ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $fenn_subject, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($question = $result->fetch_assoc()) {
            $display_questions[] = $question;
        }
        $stmt->close();
    }
}

// Final fallback: Fetch all questions for subject
if (empty($display_questions)) {
    $query = "SELECT * FROM sual_banki WHERE subject = ? ORDER BY id";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $fenn_subject);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($question = $result->fetch_assoc()) {
            $display_questions[] = $question;
        }
        $stmt->close();
    }
}

// Calculate time spent
$time_spent = '00:00:00';
try {
    $start_time = new DateTime($exam_result['baslama_vaxti'] ?? 'now');
    $end_time = new DateTime($exam_result['bitme_vaxti'] ?? 'now');
    $time_diff = $start_time->diff($end_time);
    $time_spent = sprintf("%02d:%02d:%02d", $time_diff->h, $time_diff->i, $time_diff->s);
} catch (Exception $e) {
    // Log error if needed, keep default time_spent
}

// Determine pass status
$isPassed = ($exam_result['kecid_statusu'] ?? '') === 'Keçdi';
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmtahan Nəticəsi - <?php echo htmlspecialchars($exam_result['exam_name'] ?? 'İmtahan'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
        :root {
            --primary-color: #4f46e5; /* Indigo for primary actions */
            --secondary-color: #4338ca; /* Darker indigo for hover */
            --success-color: #10b981; /* Emerald for pass/success */
            --danger-color: #ef4444; /* Red for fail/incorrect */
            --warning-color: #f59e0b; /* Amber for warnings */
            --info-color: #3b82f6; /* Blue for info */
            --light-color: #f9fafb; /* Light gray for backgrounds */
            --dark-color: #1f2937; /* Dark gray for text */
            --border-radius: 8px;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.2s ease-in-out;
        }

        body {
            color: var(--dark-color);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container-fluid {
            max-width: 100%;
            padding: 20px;
            flex: 1;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            background-color: white;
            transition: var(--transition);
            margin-bottom: 24px;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 24px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .card-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: space-between;
            transition: var(--transition);
        }

        .card-footer {
            background-color: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px 24px;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .btn {
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 6px;
            transition: var(--transition);
            font-size: 0.9rem;
            width: 100%; /* Ensure buttons are equal width */
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            border-color: #d1d5db;
            color: var(--dark-color);
        }

        .btn-outline-secondary:hover {
            background-color: #f3f4f6;
            border-color: #9ca3af;
            color: black;
            transform: translateY(-1px);
        }

        .form-group-buttons {
            display: flex;
            gap: 12px;
            flex-direction: row; /* Default for PC */
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .result-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 25px;
            font-weight: bolder;
            color: var(--primary-color);
            border: 8px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .result-circle.pass {
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .result-circle.fail {
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .result-stat {
            text-align: center;
            padding: 16px;
            border-radius: 6px;
            background-color: var(--light-color);
            margin-bottom: 12px;
            transition: var(--transition);
        }

        .result-stat:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .result-stat h3 {
            font-size: 1.75rem;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .result-stat p {
            color: #6b7280;
            margin: 0;
            font-size: 0.875rem;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table th {
            background-color: #f3f4f6;
            color: var(--dark-color);
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 2px solid #e5e7eb;
            text-align: left;
            font-size: 0.9rem;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            font-size: 0.875rem;
            color: #374151;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: #f9fafb;
        }

        .question-option {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 12px;
            background-color: white;
            transition: var(--transition);
        }

        .question-option.correct {
            background-color: #ecfdf5;
            border-color: #6ee7b7;
        }

        .question-option.incorrect {
            background-color: #fef2f2;
            border-color: #f87171;
        }

        .question-option:hover {
            box-shadow: var(--shadow-sm);
        }

        .question-image {
            max-width: 100%;
            max-height: 280px;
            margin: 16px auto;
            display: block;
            border-radius: 6px;
            object-fit: contain;
        }

        .accordion-button {
            font-weight: 500;
            color: var(--dark-color);
            padding: 16px 20px;
            transition: var(--transition);
        }

        .accordion-button:not(.collapsed) {
            background-color: #eff6ff;
            color: var(--primary-color);
        }

        .accordion-button:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        .badge {
            font-weight: bolder;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
            color: white;
        }

        .badge.bg-danger {
            background-color: var(--danger-color) !important;
            color: white;
        }

        .alert-info {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
            border-radius: 6px;
        }

        footer {
            padding: 16px;
            background-color: white;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 13px;
            }

            .card-body {
                justify-content: center;
                padding: 14px;
            }

            .form-group-buttons {
                flex-direction: column; /* Stack buttons vertically */
                align-items: center; /* Center buttons */
                max-width: 300px; /* Limit width for better appearance */
                margin-left: auto;
                margin-right: auto;
            }

            .btn {
                width: 100%; /* Full width for mobile */
            }

            .result-circle {
                width: 120px;
                height: 120px;
                font-size: 25px;
                border-width: 6px;
            }

            .result-stat h3 {
                font-size: 1.2rem;
            }

            .result-stat p {
                font-size: 0.55rem;
            }


            .result-stat p {
                font-weight: bolder;
            }

            .table {
                min-width: 100%;
            }

            .table th,
            .table td {
                padding: 6px 12px;
                font-size: 0.65rem;
            }

            .table th {
                font-size: 0.68rem;
            }
        }

        @media (min-width: 769px) {
            .card-body {
                justify-content: space-between;
            }

            .form-group-buttons {
                flex-direction: row; /* Buttons in one line */
                justify-content: center;
            }

            .btn {
                width: auto; /* Auto width for PC */
                min-width: 150px; /* Ensure buttons are not too small */
            }
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background-color: white;
                color: #000;
            }

            .card {
                box-shadow: none;
                border: 1px solid #000;
            }

            .container-fluid {
                padding: 0;
                margin: 0;
                width: 100%;
            }

            .accordion-button::after {
                display: none !important;
            }

            .accordion-collapse {
                display: block !important;
            }

            .result-circle {
                border: 4px solid #000 !important;
                color: #000 !important;
            }

            .question-option.correct {
                background-color: #e0e0e0 !important;
            }

            .question-option.incorrect {
                background-color: #f0f0f0 !important;
            }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h4 class="card-title">İmtahan Nəticəsi</h4>
                        </div>
                        <div class="form-group-buttons no-print mb-3">
                            <a href="../Examination.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>İmtahanlara Qayıt
                            </a>
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print me-2"></i>Çap et
                            </button>
                        </div>
                        <div class="row mb-4">
                            <div class="">
                                <div class="card border">
                                    <div class="card-header">
                                        <h5 class="mb-0">İmtahan Məlumatları</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>İmtahan adı:</th>
                                                    <td><?php echo htmlspecialchars($exam_result['exam_name'] ?? ''); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Fənn:</th>
                                                    <td>
                                                        <?php
                                                        $fenn_decoded = json_decode($exam_result['fenn_adi'] ?? '', true);
                                                        if (json_last_error() === JSON_ERROR_NONE && is_array($fenn_decoded)) {
                                                            echo htmlspecialchars(implode(', ', array_map('trim', $fenn_decoded)));
                                                        } else {
                                                            echo htmlspecialchars($exam_result['fenn_adi'] ?? '');
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Sinif:</th>
                                                    <td><?php echo htmlspecialchars($exam_result['sinif'] ?? ''); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Tələbə:</th>
                                                    <td><?php echo htmlspecialchars($exam_result['telebe_adi'] ?? ''); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Tarix:</th>
                                                    <td><?php echo isset($exam_result['created_at']) ? date('d.m.Y H:i', strtotime($exam_result['created_at'])) : ''; ?></td>
                                                </tr>
                                                <tr hidden>
                                                    <th>Sərf olunan vaxt:</th>
                                                    <td><?php echo $time_spent; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <div class="card border">
                                    <div class="card-header">
                                        <h5 class="mb-0">Nəticə</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-4">
                                            <div class="result-circle <?php echo $isPassed ? 'pass' : 'fail'; ?>">
                                                <?php echo htmlspecialchars($exam_result['faiz'] ?? '0'); ?>%
                                            </div>
                                            <div class="mt-2">
                                                <span class="badge <?php echo $isPassed ? 'bg-success' : 'bg-danger'; ?> fs-8">
                                                    <?php echo htmlspecialchars($exam_result['kecid_statusu'] ?? 'Keçmədi'); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="result-stat">
                                                    <h3 class="text-success"><?php echo htmlspecialchars($exam_result['dogru_cavablar'] ?? '0'); ?></h3>
                                                    <p>Doğru</p>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="result-stat">
                                                    <h3 class="text-danger"><?php echo htmlspecialchars($exam_result['sehv_cavablar'] ?? '0'); ?></h3>
                                                    <p>Səhv</p>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="result-stat">
                                                    <h3 class="text-primary"><?php echo htmlspecialchars($exam_result['umumui_sual_sayi'] ?? '0'); ?></h3>
                                                    <p>Ümumi</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div hidden class="card border">
                            <div class="card-header">
                                <h5 class="mb-0">Suallar və Cavablar</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($display_questions)): ?>
                                    <div class="accordion" id="questionsAccordion">
                                        <?php foreach ($display_questions as $index => $question): 
                                            $user_answer = $user_answers[$index] ?? null;
                                            $is_correct = false;
                                            $correct_answer = $question['correct_answer'] ?? '';

                                            if ($correct_answer !== '') {
                                                if (isset($question['question_type']) && $question['question_type'] === 'multiple-select') {
                                                    $correct_answers = is_array($correct_answer) ? $correct_answer : json_decode($correct_answer, true) ?? [];
                                                    if (is_array($correct_answers) && is_array($user_answer)) {
                                                        sort($correct_answers);
                                                        sort($user_answer);
                                                        $is_correct = ($correct_answers === $user_answer);
                                                    }
                                                } else {
                                                    $is_correct = ($user_answer === $correct_answer);
                                                }
                                            }

                                            $question_text = $question['question_text'] ?? $question['text'] ?? $question['question'] ?? 'Sual ' . ($index + 1);
                                            if (is_array($question_text)) {
                                                $question_text = $question_text['text'] ?? json_encode($question_text);
                                            }
                                            $question_text = strip_tags($question_text);

                                            $options = [];
                                            if (isset($question['options'])) {
                                                $options = is_array($question['options']) ? $question['options'] : json_decode($question['options'], true) ?? [];
                                            }
                                            $cleaned_options = [];
                                            foreach ($options as $key => $value) {
                                                $cleaned_options[$key] = is_array($value) ? strip_tags($value['text'] ?? $value['label'] ?? json_encode($value)) : strip_tags($value);
                                            }
                                            $options = $cleaned_options;
                                        ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                                    <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                        <div class="d-flex align-items-center w-100">
                                                            <span class="me-2">Sual</span>
                                                            <span class="flex-grow-1"><?php echo htmlspecialchars($question_text); ?></span>
                                                            <span class="badge <?php echo $is_correct ? 'bg-success' : 'bg-danger'; ?> ms-3">
                                                                <?php echo $is_correct ? 'Doğru' : 'Səhv'; ?>
                                                            </span>
                                                        </div>
                                                    </button>
                                                </h2>
                                                <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#questionsAccordion">
                                                    <div class="accordion-body">
                                                        <?php if (!empty($question['image_path'])): ?>
                                                            <div class="mb-3 text-center">
                                                                <img src="<?php echo htmlspecialchars($question['image_path']); ?>" alt="Sual şəkli" class="question-image">
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="mb-3">
                                                            <?php if (!empty($options)): ?>
                                                                <?php foreach ($options as $key => $value): 
                                                                    $option_class = '';
                                                                    if (isset($question['question_type']) && $question['question_type'] === 'multiple-select') {
                                                                        $correct_answers = is_array($correct_answer) ? $correct_answer : json_decode($correct_answer, true) ?? [];
                                                                        if (in_array($key, $correct_answers)) {
                                                                            $option_class = 'correct';
                                                                        } elseif (is_array($user_answer) && in_array($key, $user_answer) && !in_array($key, $correct_answers)) {
                                                                            $option_class = 'incorrect';
                                                                        }
                                                                    } else {
                                                                        if ($key === $correct_answer) {
                                                                            $option_class = 'correct';
                                                                        } elseif ($key === $user_answer && $key !== $correct_answer) {
                                                                            $option_class = 'incorrect';
                                                                        }
                                                                    }
                                                                ?>
                                                                    <div class="question-option <?php echo $option_class; ?>">
                                                                        <div class="form-check">
                                                                            <?php if (isset($question['question_type']) && $question['question_type'] === 'multiple-select'): ?>
                                                                                <input class="form-check-input" type="checkbox" disabled <?php echo is_array($user_answer) && in_array($key, $user_answer) ? 'checked' : ''; ?>>
                                                                            <?php else: ?>
                                                                                <input class="form-check-input" type="radio" disabled <?php echo $user_answer === $key ? 'checked' : ''; ?>>
                                                                            <?php endif; ?>
                                                                            <label class="form-check-label"><?php echo htmlspecialchars($value); ?></label>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <div class="alert alert-warning">
                                                                    <p class="mb-0">Cavab variantları mövcud deyil.</p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($question['explanation'])): ?>
                                                            <div class="alert alert-info">
                                                                <h6>Açıqlama:</h6>
                                                                <p class="mb-0"><?php echo htmlspecialchars($question['explanation']); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <div>
                                                <h6 class="mb-1">Suallar tapılmadı</h6>
                                                <p class="mb-0">Bu imtahan üçün suallar tapılmadı. Zəhmət olmasa, verilənlər bazasını yoxlayın.</p>
                                                <small class="text-muted">
                                                    Fənn: <?php echo htmlspecialchars($fenn_subject ?? ''); ?><br>
                                                    İmtahan sualları: <?php echo count($exam_questions); ?><br>
                                                    İstifadəçi cavabları: <?php echo count($user_answers); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>