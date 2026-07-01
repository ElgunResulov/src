<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
require_once '../../db.php';
require_once '../../TCPDF-main/TCPDF-main/tcpdf.php';
if (!isset($_GET['id']) || empty($_GET['id'])) {
    ob_end_clean();
    die("Error: No exam ID provided");
}
$exam_id = intval($_GET['id']);
error_log("Fetching exam ID: $exam_id");
$stmt = $conn->prepare("SELECT exam_name, fenn_adi, description, sual_sayi, questions FROM imtahanlar_exam WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam_result = $stmt->get_result();
if ($exam_result->num_rows === 0) {
    error_log("Exam not found for ID: $exam_id");
    ob_end_clean();
    die("Error: Exam not found");
}
$exam = $exam_result->fetch_assoc();
$exam_name = $exam['exam_name'];
$subject = json_decode($exam['fenn_adi'], true);
$subject_name = is_array($subject) ? implode(", ", $subject) : $subject;
$description = !empty($exam['description']) ? $exam['description'] : "";
$num_questions = isset($exam['sual_sayi']) ? intval($exam['sual_sayi']) : 10;
$question_data = json_decode($exam['questions'], true);
error_log("Question data from exam: " . json_encode($question_data));
if (!is_array($question_data) || empty($question_data)) {
    error_log("No valid question data found in exam");
    ob_end_clean();
    die("Xəta: Bu imtahana sual verilmir. Zəhmət olmasa əvvəlcə imtahana suallar əlavə edin.");
}
$questions_array = [];
$question_count = 0;
$has_numeric_ids = false;
$has_text_strings = false;
foreach ($question_data as $item) {
    if (is_numeric($item)) {
        $has_numeric_ids = true;
    } else {
        $has_text_strings = true;
    }
}

if ($has_numeric_ids) {
    error_log("Found numeric IDs in question data, querying by ID");
    foreach ($question_data as $item) {
        if (is_numeric($item)) {
            $query = "SELECT id, question_text, question_image, options FROM sual_banki WHERE id = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("i", $item);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $questions_array[] = $row;
                    $question_count++;
                    error_log("Found question ID: " . $row['id']);
                } else {
                    error_log("Could not find question with ID: " . $item);
                }
            }
        }
    }
}

if ($has_text_strings) {
    error_log("Found text strings in question data, querying by text");
    foreach ($question_data as $item) {
        if (!is_numeric($item)) {
            $query = "SELECT id, question_text, question_image, options FROM sual_banki WHERE question_text = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("s", $item);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $duplicate = false;
                        foreach ($questions_array as $existing) {
                            if ($existing['id'] == $row['id']) {
                                $duplicate = true;
                                break;
                            }
                        }
                        if (!$duplicate) {
                            $questions_array[] = $row;
                            $question_count++;
                            error_log("Found question by exact text match, ID: " . $row['id']);
                        }
                    }
                } else {
                    $query = "SELECT id, question_text, question_image, options FROM sual_banki WHERE question_text LIKE ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $search_text = "%" . $item . "%";
                        $stmt->bind_param("s", $search_text);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $duplicate = false;
                            foreach ($questions_array as $existing) {
                                if ($existing['id'] == $row['id']) {
                                    $duplicate = true;
                                    break;
                                }
                            }
                            if (!$duplicate) {
                                $questions_array[] = $row;
                                $question_count++;
                                error_log("Found question by LIKE match, ID: " . $row['id']);
                            }
                        }
                    }
                }
            }
        }
    }
}
if (empty($questions_array)) {
    error_log("No questions available in sual_banki");
    ob_end_clean();
    die("Error: No questions found for this exam. Please check the question bank.");
}
error_log("Final questions to display: " . count($questions_array));
class MYPDF extends TCPDF {
    protected $subject_name = '';
    protected $exam_name = '';
    public function setSubjectName($name) {
        $this->subject_name = $name;
    }
    public function setExamName($name) {
        $this->exam_name = $name;
    }
    public function Header() {
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Exam System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle($exam_name);
$pdf->SetSubject($subject_name);
$pdf->setSubjectName($subject_name);
$pdf->setExamName($exam_name);
$pdf->SetMargins(15, 15, 15); // Reduced margins to use more space
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(5);
$pdf->SetAutoPageBreak(TRUE, 10);
$option_letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];

function extractImage($html) {
    if (preg_match_all('/<img src="data:image\/[a-zA-Z]+;base64,([^"]+)"/', $html, $matches)) {
        return $matches[1][0]; 
    }
    return null;
}

function cleanHtmlContent($html) {
    $image_base64 = null;
    if (preg_match('/<img src="data:image\/[a-zA-Z]+;base64,([^"]+)"/', $html, $matches)) {
        $image_base64 = $matches[1];
        $html = preg_replace('/<img[^>]+>/', '', $html);
    }
    $text = strip_tags($html);
    return array('text' => $text, 'image' => $image_base64);
}

function renderQuestion($pdf, $question, $question_number, $margin, $column_width, $current_y, $option_letters) {
    $start_y = $current_y;
    
    $question_content = cleanHtmlContent($question['question_text']);
    $question_text = $question_content['text'];
    $question_image_from_text = $question_content['image'];
    $question_image_direct = !empty($question['question_image']) ? $question['question_image'] : null;
    $question_image = $question_image_direct ?: $question_image_from_text;
    $options = json_decode($question['options'], true);
    if (!is_array($options) || empty($options)) {
        error_log("Invalid options for question ID: " . $question['id']);
        return $current_y;
    }
    
    // Render question text - more compact
    $pdf->SetFont('dejavusans', 'B', 9); // Smaller font
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetXY($margin, $current_y);
    $pdf->MultiCell($column_width, 5, $question_number . '. ' . $question_text, 0, 'L');
    $current_y = $pdf->GetY();

    // Render question image if exists - more compact
    if ($question_image) {
        if (strpos($question_image, 'data:image') === 0) {
            $image_parts = explode(',', $question_image, 2);
            if (count($image_parts) == 2) {
                $question_image = $image_parts[1];
            }
        }
        
        $image_data = base64_decode($question_image);
        $temp_image = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($temp_image, $image_data);
        
        list($width, $height) = getimagesizefromstring($image_data);
        
        $max_width = $column_width * 0.8;
        $max_height = 30; // Even more compact

        if ($width / $height > $max_width / $max_height) {
            $image_width = $max_width;
            $image_height = $height * ($max_width / $width);
            if ($image_height > $max_height) {
                $image_height = $max_height;
                $image_width = $width * ($max_height / $height);
            }
        } else {
            $image_height = $max_height;
            $image_width = $width * ($max_height / $height);
            if ($image_width > $max_width) {
                $image_width = $max_width;
                $image_height = $height * ($max_width / $width);
            }
        }
        
        $image_x = $margin + (($column_width - $image_width) / 2);
        $pdf->Image($temp_image, $image_x, $current_y, $image_width, $image_height, '', '', 'T', true, 300, '', false, false, 0, false);
        $current_y = $current_y + $image_height; // No extra spacing
        unlink($temp_image);
    }
    
    // Render options - more compact
    $pdf->SetFont('dejavusans', '', 8); // Smaller font
    $pdf->SetTextColor(80, 80, 80);
    
    for ($opt_index = 0; $opt_index < count($options); $opt_index++) {
        $option = $options[$opt_index];
        if ($opt_index < count($option_letters)) {
            $letter = $option_letters[$opt_index];
            
            $option_content = cleanHtmlContent($option['text']);
            $option_text = $option_content['text'];
            $option_image = $option_content['image'];
            
            // $pdf->SetFillColor(230, 230, 230);
            // $pdf->Circle($margin + 2, $current_y + 2, 1.5, 0, 360, 'F'); 
            
            $pdf->SetXY($margin + 0, $current_y); // Less indent
            $pdf->MultiCell($column_width - 5, 4, $letter . ') ' . $option_text, 0, 'L'); // Smaller line height
            $current_y = $pdf->GetY();

            if ($option_image) {
                $image_data = base64_decode($option_image);
                $temp_image = tempnam(sys_get_temp_dir(), 'img');
                file_put_contents($temp_image, $image_data);
                
                list($width, $height) = getimagesizefromstring($image_data);
                
                $max_width = ($column_width - 10) * 0.7; // Less indent
                $max_height = 20; // Even more compact
                if ($width / $height > $max_width / $max_height) {
                    $image_width = $max_width;
                    $image_height = $height * ($max_width / $width);
                    if ($image_height > $max_height) {
                        $image_height = $max_height;
                        $image_width = $width * ($max_height / $height);
                    }
                } else {
                    $image_height = $max_height;
                    $image_width = $width * ($max_height / $height);
                    if ($image_width > $max_width) {
                        $image_width = $max_width;
                        $image_height = $height * ($max_width / $width);
                    }
                }
                
                $image_x = $margin + 8; // Less indent
                
                $pdf->Image($temp_image, $image_x, $current_y, $image_width, $image_height, '', '', 'T', true, 300, '', false, false, 0, false);
                $current_y = $current_y + $image_height; // No extra spacing
                unlink($temp_image);
            }
        }
    }
    
    $current_y += 8; // Minimal spacing between questions
    return $current_y;
}

// Initialize PDF
$pdf->AddPage();
$page_height = $pdf->getPageHeight();
$page_width = $pdf->getPageWidth();
$mid_x = $page_width / 2;
$pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(200, 200, 200)));
$pdf->Line($mid_x, 0, $mid_x, $page_height);
$left_margin = 15; // Reduced margin
$right_margin = $mid_x + 3; // Reduced margin
$column_width = $mid_x - 18; // Adjusted for reduced margins

// Use more of the page height
$max_height_per_column = $page_height - 20; // Use more of the page
$total_questions = count($questions_array);

// Process questions page by page
$current_question = 0;
$current_page = 1;

// Pre-calculate question heights for better estimation
$question_heights = [];
for ($i = 0; $i < $total_questions; $i++) {
    $question = $questions_array[$i];
    $options = json_decode($question['options'], true);
    $estimated_height = 2; // Base height for question (reduced)
    
    // Estimate text height based on length
    $question_text = strip_tags($question['question_text']);
    $text_length = strlen($question_text);
    $estimated_height += ceil($text_length / 100) * 4; // Rough estimate
    
    if (!empty($question['question_image'])) {
        $estimated_height += 30; // Reduced height for images
    }
    
    if (is_array($options)) {
        $estimated_height += count($options) * 5; // Reduced height per option
    }
    
    $question_heights[$i] = $estimated_height;
}

while ($current_question < $total_questions) {
    if ($current_page > 1) {
        $pdf->AddPage();
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(200, 200, 200)));
        $pdf->Line($mid_x, 0, $mid_x, $page_height);
    }
    
    // Left column
    $left_y = 5;
    $left_column_start = $current_question;
    $left_column_questions = [];
    $left_column_height = 0;
    
    // Fill left column first - use more aggressive estimation
    while ($current_question < $total_questions) {
        // If adding this question would exceed the column height, stop
        if ($left_column_height + $question_heights[$current_question] > $max_height_per_column) {
            // But if we haven't added any questions yet to this column, add at least one
            if (count($left_column_questions) == 0) {
                $left_column_questions[] = $current_question;
                $current_question++;
            }
            break;
        }
        
        // Add to left column
        $left_column_questions[] = $current_question;
        $left_column_height += $question_heights[$current_question];
        $current_question++;
    }
    
    // Render left column questions
    $left_y = 5;
    foreach ($left_column_questions as $q_index) {
        $question = $questions_array[$q_index];
        $question_number = $q_index + 1;
        $left_y = renderQuestion($pdf, $question, $question_number, $left_margin, $column_width, $left_y, $option_letters);
    }
    
    // Right column
    $right_y = 5;
    $right_column_start = $current_question;
    $right_column_questions = [];
    $right_column_height = 0;
    
    // Fill right column - use more aggressive estimation
    while ($current_question < $total_questions) {
        // If adding this question would exceed the column height, stop
        if ($right_column_height + $question_heights[$current_question] > $max_height_per_column) {
            // But if we haven't added any questions yet to this column, add at least one
            if (count($right_column_questions) == 0) {
                $right_column_questions[] = $current_question;
                $current_question++;
            }
            break;
        }
        
        // Add to right column
        $right_column_questions[] = $current_question;
        $right_column_height += $question_heights[$current_question];
        $current_question++;
    }
    
    // Render right column questions
    $right_y = 5;
    foreach ($right_column_questions as $q_index) {
        $question = $questions_array[$q_index];
        $question_number = $q_index + 1;
        $right_y = renderQuestion($pdf, $question, $question_number, $right_margin, $column_width, $right_y, $option_letters);
    }
    
    $current_page++;
}

ob_end_clean();
$pdf->Output('Imtahan_' . $exam_id . '.pdf', 'I');
exit;
?>