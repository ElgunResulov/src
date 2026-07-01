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

// Fetch exam details
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

// Get question data from the exam
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

// Determine if we have IDs or text strings
foreach ($question_data as $item) {
    if (is_numeric($item)) {
        $has_numeric_ids = true;
    } else {
        $has_text_strings = true;
    }
}

// Query by ID if numeric IDs are present
if ($has_numeric_ids) {
    error_log("Found numeric IDs in question data, querying by ID");
    foreach ($question_data as $item) {
        if (is_numeric($item)) {
            // Updated query to include question_image
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

// Query by text if text strings are present
if ($has_text_strings) {
    error_log("Found text strings in question data, querying by text");
    foreach ($question_data as $item) {
        if (!is_numeric($item)) {
            // Updated query to include question_image
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
                    // Updated query to include question_image
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
        // Header removed for cleaner look
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        // Footer removed for cleaner look
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Exam System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle($exam_name);
$pdf->SetSubject($subject_name);
$pdf->setSubjectName($subject_name);
$pdf->setExamName($exam_name);

// Set margins - improved spacing
$pdf->SetMargins(20, 20, 20);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(5);
$pdf->SetAutoPageBreak(TRUE, 10);

$option_letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];

// Add first page
$pdf->AddPage();

// Get page dimensions
$page_height = $pdf->getPageHeight();
$page_width = $pdf->getPageWidth();
$mid_x = $page_width / 2;

// Draw vertical line with improved styling
$pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(200, 200, 200)));
$pdf->Line($mid_x, 0, $mid_x, $page_height);

// Set column widths and margins with better spacing
$left_margin = 20;
$right_margin = $mid_x + 5;
$column_width = $mid_x - 25;

// Dynamic positioning
$current_page = 0;
$left_y = 5; // Start at top
$right_y = 5; // Start at top
$question_index = 0;
$total_questions = count($questions_array);

// Function to extract image from HTML content with improved handling
function extractImage($html) {
    $images = array();
    if (preg_match_all('/<img src="data:image\/[a-zA-Z]+;base64,([^"]+)"/', $html, $matches)) {
        return $matches[1][0]; // Return only the first image found
    }
    return null;
}

// Clean HTML content to remove nested images
function cleanHtmlContent($html) {
    // Extract the first image if it exists
    $image_base64 = null;
    if (preg_match('/<img src="data:image\/[a-zA-Z]+;base64,([^"]+)"/', $html, $matches)) {
        $image_base64 = $matches[1];
        // Remove all img tags from the HTML
        $html = preg_replace('/<img[^>]+>/', '', $html);
    }
    
    // Clean the text
    $text = strip_tags($html);
    
    return array('text' => $text, 'image' => $image_base64);
}

// Process all questions
while ($question_index < $total_questions) {
    // Check if we need a new page
    if ($question_index > 0 && ($left_y > $page_height - 30 || $right_y > $page_height - 30)) {
        $pdf->AddPage();
        $current_page++;
        $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(200, 200, 200)));
        $pdf->Line($mid_x, 0, $mid_x, $page_height);
        $left_y = 5;
        $right_y = 5;
    }
    
    $is_left_column = ($left_y <= $right_y);
    $margin = $is_left_column ? $left_margin : $right_margin;
    $current_y = $is_left_column ? $left_y : $right_y;
    
    $question = $questions_array[$question_index];
    $question_number = $question_index + 1;
    
    // Clean question content
    $question_content = cleanHtmlContent($question['question_text']);
    $question_text = $question_content['text'];
    $question_image_from_text = $question_content['image'];
    
    // Get direct question_image if available
    $question_image_direct = !empty($question['question_image']) ? $question['question_image'] : null;
    
    // Use direct image if available, otherwise use extracted image
    $question_image = $question_image_direct ?: $question_image_from_text;
    
    $options = json_decode($question['options'], true);
    
    if (!is_array($options) || empty($options)) {
        error_log("Invalid options for question ID: " . $question['id']);
        $question_index++;
        continue;
    }
    
    // Add question box with light background
    $box_start_y = $current_y;
    
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetXY($margin, $box_start_y);
    $pdf->MultiCell($column_width, 5, $question_number . '. ' . $question_text, 0, 'L');
    $current_y = $pdf->GetY() + 2; // Reduced spacing
    
    // Handle question image if exists
    if ($question_image) {
        // Check if the image is already in base64 format or needs to be extracted
        if (strpos($question_image, 'data:image') === 0) {
            // Extract base64 data from data URI
            $image_parts = explode(',', $question_image, 2);
            if (count($image_parts) == 2) {
                $question_image = $image_parts[1];
            }
        }
        
        // Create temporary image file
        $image_data = base64_decode($question_image);
        $temp_image = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($temp_image, $image_data);
        
        // Get image dimensions
        list($width, $height) = getimagesizefromstring($image_data);
        
        // Calculate aspect ratio and set max width
        $max_width = $column_width * 0.8; // 80% of column width
        $max_height = 40; // Maximum height in mm - reduced significantly
        
        // Calculate dimensions to maintain aspect ratio while fitting within constraints
        if ($width / $height > $max_width / $max_height) {
            // Image is wider than tall
            $image_width = $max_width;
            $image_height = $height * ($max_width / $width);
            
            // If still too tall, constrain by height
            if ($image_height > $max_height) {
                $image_height = $max_height;
                $image_width = $width * ($max_height / $height);
            }
        } else {
            // Image is taller than wide
            $image_height = $max_height;
            $image_width = $width * ($max_height / $height);
            
            // If still too wide, constrain by width
            if ($image_width > $max_width) {
                $image_width = $max_width;
                $image_height = $height * ($max_width / $width);
            }
        }
        
        // Center the image
        $image_x = $margin + (($column_width - $image_width) / 2);
        
        // Add image with no padding
        $pdf->Image($temp_image, $image_x, $current_y, $image_width, $image_height, '', '', 'T', true, 300, '', false, false, 0, false);
        $current_y = $current_y + $image_height + 1; // Minimal spacing after image
        unlink($temp_image);
    }
    
    // Draw options with better formatting    
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->SetTextColor(80, 80, 80);
    
    foreach ($options as $opt_index => $option) {
        if ($opt_index < count($option_letters)) {
            $letter = $option_letters[$opt_index];
            
            // Clean option content
            $option_content = cleanHtmlContent($option['text']);
            $option_text = $option_content['text'];
            $option_image = $option_content['image'];
            
            // Draw option letter with circle
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Circle($margin + 2, $current_y + 2, 2, 0, 360, 'F');
            
            // Draw option text
            $pdf->SetXY($margin + 6, $current_y);
            $pdf->MultiCell($column_width - 6, 4, $letter . ') ' . $option_text, 0, 'L');
            $current_y = $pdf->GetY() + 1; // Minimal spacing
            
            // Handle option image if exists
            if ($option_image) {
                // Create temporary image file
                $image_data = base64_decode($option_image);
                $temp_image = tempnam(sys_get_temp_dir(), 'img');
                file_put_contents($temp_image, $image_data);
                
                // Get image dimensions
                list($width, $height) = getimagesizefromstring($image_data);
                
                // Calculate aspect ratio and set max width
                $max_width = ($column_width - 15) * 0.7; // 70% of column width, with indent
                $max_height = 30; // Maximum height for option images - reduced significantly
                
                // Calculate dimensions to maintain aspect ratio while fitting within constraints
                if ($width / $height > $max_width / $max_height) {
                    // Image is wider than tall
                    $image_width = $max_width;
                    $image_height = $height * ($max_width / $width);
                    
                    // If still too tall, constrain by height
                    if ($image_height > $max_height) {
                        $image_height = $max_height;
                        $image_width = $width * ($max_height / $height);
                    }
                } else {
                    // Image is taller than wide
                    $image_height = $max_height;
                    $image_width = $width * ($max_height / $height);
                    
                    // If still too wide, constrain by width
                    if ($image_width > $max_width) {
                        $image_width = $max_width;
                        $image_height = $height * ($max_width / $width);
                    }
                }
                
                // Indent the image
                $image_x = $margin + 10;
                
                // Add image with no container or padding
                $pdf->Image($temp_image, $image_x, $current_y, $image_width, $image_height, '', '', 'T', true, 300, '', false, false, 0, false);
                $current_y = $current_y + $image_height + 1; // Minimal spacing after image
                unlink($temp_image);
            }
        }
    }
    
    $current_y += 3; // Minimal space after each question
    
    if ($is_left_column) {
        $left_y = $current_y;
    } else {
        $right_y = $current_y;
    }
    
    $question_index++;
    
    // Better column balancing
    if ($question_index < $total_questions) {
        if ($left_y > $right_y + 60 && $is_left_column) {
            $is_left_column = false;
        } else if ($right_y > $left_y + 60 && !$is_left_column) {
            $pdf->AddPage();
            $current_page++;
            $pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0, 'color' => array(200, 200, 200)));
            $pdf->Line($mid_x, 0, $mid_x, $page_height);
            $left_y = 5;
            $right_y = 5;
        }
    }
}

// Output the PDF
ob_end_clean();
$pdf->Output('Imtahan_' . $exam_id . '.pdf', 'I');
exit;
?>
