<?php
include('db.php');
?>

    <style>
        .magnifier-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 16px; color: #fff; opacity: 0; transition: opacity 0.3s ease; pointer-events: none;}
        .question-image-container:hover .magnifier-icon { opacity: 1;}
        #fullScreenImageOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); z-index: 1060; display: flex; align-items: center; justify-content: center;}
        .full-screen-image-container { position: relative; max-width: 90%; max-height: 90%;}
        .full-screen-image { max-width: 100%; max-height: 100%; object-fit: contain;}
        .close-full-screen { position: absolute; top: -25px; right: -25px; font-size: 24px; color: #fff; cursor: pointer;}
        @media (max-width: 576px) { .question-image { width: 40px; height: 40px;} .close-full-screen { top: -20px; right: -20px; font-size: 20px;}}
        .container { max-width: 100%; margin: 0 auto;}
        .section { margin-bottom: 30px;}
        .d-flex { display: flex;}
        .justify-content-between { justify-content: space-between;}
        .align-items-center { align-items: center;}
        .mb-20 { margin-bottom: 20px;}
        .m-2 { margin: 0.5rem;}
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background-color 0.3s;}
        .btn-primary:hover { background-color: #0056b3;}
        .btn-secondary { background-color: #6c757d; color: white;}
        .btn-secondary:hover { background-color: #5a6268;}
        .btn-info { background-color: #17a2b8; color: white;}
        .btn-info:hover { background-color: #138496;}
        .btn-danger { background-color: #dc3545; color: white;}
        .btn-danger:hover { background-color: #c82333;}
        .btn-sm { padding: 5px 10px; font-size: 12px;}
        .card { background-color: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); margin-bottom: 20px;}
        .card-header { padding: 15px; border-bottom: 1px solid #eee; background-color: #f8f9fa;}
        .card-title { margin: 0; font-size: 18px;}
        .card-body { padding: 15px;}
        .form-group { margin-bottom: 15px;}
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold;}
        .form-select, .form-control, .form-group textarea, .form-group input[type="text"], .form-group input[type="file"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;}
        .form-group textarea { height: 80px; resize: vertical;}
        .simple-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; opacity: 0; transition: opacity 0.3s ease-in-out;}
        .simple-modal.fade-in { opacity: 1;}
        .simple-modal.fade-out { opacity: 0;}
        .simple-modal-content { background-color: white; margin: 4% auto; padding: 20px; width: 90%; max-width: 700px; max-height: 80vh; overflow: auto; border-radius: 5px; position: relative; transform: translateY(-20px); transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; opacity: 0;}
        .simple-modal.fade-in .simple-modal-content { transform: translateY(0); opacity: 1;}
        .simple-modal.fade-out .simple-modal-content { transform: translateY(-20px); opacity: 0;}
        .simple-modal-close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #333;}
        .simple-modal-close:hover { color: #e63946;}
        .option-item { margin-bottom: 25px; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px; background-color: #f9f9f9;}
        .option-row { display: grid; grid-template-columns: auto 1fr auto; grid-template-areas: "checkbox editor remove" "checkbox toolbar remove"; gap: 10px; align-items: start;}
        .option-checkbox { grid-area: checkbox; align-self: center; margin-top: 15px; transform: scale(1.2);}
        .option-editor { grid-area: editor; border: 1px solid #ddd; border-radius: 4px; background-color: white;}
        .option-editor .ql-container { min-height: 60px; border-bottom: none !important; border-bottom-left-radius: 0; border-bottom-right-radius: 0;}
        .option-editor .ql-toolbar { border-top: none !important; border-top-left-radius: 0; border-top-right-radius: 0; background-color: #f0f0f0; padding: 5px;}
        .option-remove { grid-area: remove; align-self: center; margin-top: 15px;}
        .quill-container { display: flex; flex-direction: column;}
        .quill-container .ql-container { order: 1; border-bottom: none !important; border-bottom-left-radius: 0; border-bottom-right-radius: 0;}
        .quill-container .ql-toolbar { order: 2; border-top: 1px solid #ccc !important; border-bottom: none !important; border-top-left-radius: 0; border-top-right-radius: 0; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; background-color: #f0f0f0;}
        .pair-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px;}
        .pair-left, .pair-right { flex: 1;}
        .answer-option { margin-bottom: 10px;}
        .form-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;}
        #questionImageInfo { margin-top: 5px; font-size: 12px; color: #666;}
        .preview-container { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;}
        .preview-container h3 { margin-top: 0; font-size: 18px;}
        .close-preview { margin-top: 10px;}
        .error-message { color: #dc3545; font-size: 12px; margin-top: 5px;}
        .question-item { border: 0px solid #ddd; border-radius: 6px; padding: 8px; margin-bottom: 20px; background-color: #fff;}
        .question-header { display: flex; justify-content: space-between; margin-bottom: 10px;}
        .question-meta { display: flex; flex-wrap: wrap; gap: 15px; font-size: 14px; color: #666;}
        .question-meta span { display: flex; align-items: center; gap: 5px;}
        .action-buttons { display: flex; gap: 5px;}
        .question-text { font-size: 16px; margin-bottom: 10px;}
        .question-options { display: flex; flex-direction: column; margin-left: 10px;}
        .question-option { margin-bottom: 0px; align-items: center; gap: 5px;}
        .question-option-correct { font-weight: bolder; color: rgb(255, 255, 255); background: rgba(11, 171, 48, 0.59); padding: 6px; border-radius: 6px;}
        .row { display: flex; flex-wrap: wrap; margin-right: -10px; margin-left: -10px;}
        .col-md-6, .col-md-12 { position: relative; width: 100%; padding-right: 10px; padding-left: 10px;}
        @media (min-width: 768px) { .col-md-6 { flex: 0 0 50%; max-width: 50%;} .col-md-12 { flex: 0 0 100%; max-width: 100%;}}
        .question-view { display: flex; flex-direction: column; gap: 20px;}
        .question-meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 10px;}
        .meta-item { display: flex; flex-direction: column; padding: 10px; background-color: #f8f9fa; border-radius: 5px; border-left: 3px solid #007bff;}
        .meta-label { font-weight: bold; color: #555; margin-bottom: 5px;}
        .meta-value { font-size: 16px;}
        .question-content { background-color: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 3px solid #28a745;}
        .question-text-content { background-color: white; padding: 15px; border-radius: 4px; border: 1px solid #e0e0e0; overflow: auto;}
        .question-image { text-align: center;}
        .question-image img { max-width: 100%; max-height: 300px; border-radius: 5px; border: 1px solid #ddd;}
        .question-options { display: flex; flex-direction: column; gap: 10px;}
        .option-view { display: grid; grid-template-columns: 40px 1fr auto; align-items: center; gap: 10px; padding: 10px; background-color: white; border-radius: 5px; border: 1px solid #e0e0e0;}
        .option-correct { background-color: #f0fff4; border-color: #28a745;}
        .option-letter { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; background-color: #e9ecef; border-radius: 50%; font-weight: bolder;}
        .option-correct .option-letter { background-color: #28a745; color: white;}
        .option-status { color: #28a745; font-weight: bold;}
        .question-answer { background-color: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 3px solid #17a2b8;}
        .answer-text { background-color: white; padding: 15px; border-radius: 4px; border: 1px solid #e0e0e0;}
        .question-pairs { display: flex; flex-direction: column; gap: 10px;}
        .pair-view { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 15px; padding: 10px; background-color: white; border-radius: 5px; border: 1px solid #e0e0e0;}
        .pair-arrow { color: #6c757d;}
        .pair-left, .pair-right { padding: 8px; background-color: #f8f9fa; border-radius: 4px; border: 1px solid #e0e0e0;}
        .option-image { width: 50px; height: 50px; object-fit: cover; vertical-align: middle; margin-right: 10px; border-radius: 3px;}
    </style>

    <div class="container">
        <div class="section" id="question-bank">
            <div class="d-flex justify-content-between align-items-center mb-20">
                <h1></h1>
                <button class="btn btn-primary" id="openModalBtn">
                    <i class="fas fa-plus"></i> Yeni Sual
                </button>
            </div>
            <div class="card mb-20">
                <div class="card-header">
                    <h3 class="card-title">Suallar Siyahısı</h3>
                </div>
                <div class="m-2">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <select class="form-select" id="questionSubjectFilter" onchange="filterQuestions()">
                                <option value="">Bütün Fənlər</option>
                                <?php
                                $query = "SELECT id, fenn_adi FROM fennler_new ORDER BY fenn_adi";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<option value='{$row['id']}'>{$row['fenn_adi']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <select class="form-select" id="questionTopicFilter" onchange="filterQuestions()">
                              <option value="">Bütün Mövzular</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <select class="form-select" id="questionTypeFilter" onchange="filterQuestions()">
                                <option value="">Bütün Tiplər</option>
                                <option value="multiple_choice">Çoxseçimli</option>
                                <option value="open_ended">Açıq</option>
                                <option value="true_false">Doğru/Yanlış</option>
                                <option value="matching">Uyğunlaşdırma</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <select class="form-select" id="questionDifficultyFilter" onchange="filterQuestions()">
                                <option value="">Bütün Çətinliklər</option>
                                <option value="1">Asan</option>
                                <option value="2">Orta</option>
                                <option value="3">Çətin</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-2">
                            <input type="text" class="form-control" id="questionSearchInput" placeholder="Axtar..." oninput="filterQuestions()">
                        </div>
                    </div>
                </div>
                    <div class="card-body">
                        <div id="questionsList">
                            <?php
                            $current_u_id = isset($_SESSION['u_id']) ? $_SESSION['u_id'] : null;
                            
                            if ($current_u_id) {
                                $query = "SELECT q.*, f.fenn_adi, m.movzu_adi 
                                        FROM sual_banki q 
                                        LEFT JOIN fennler_new f ON q.subject = f.id 
                                        LEFT JOIN movzular_new m ON q.topic = m.id
                                        WHERE q.u_id = ?
                                        ORDER BY q.id DESC";
                                
                                $stmt = mysqli_prepare($conn, $query);
                                mysqli_stmt_bind_param($stmt, 's', $current_u_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);

                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $question_type_az = "";
                                        switch ($row['question_type']) {
                                            case 'multiple_choice': $question_type_az = "Çoxseçimli"; break;
                                            case 'open_ended': $question_type_az = "Açıq"; break;
                                            case 'true_false': $question_type_az = "Doğru/Yanlış"; break;
                                            case 'matching': $question_type_az = "Uyğunlaşdırma"; break;
                                            default: $question_type_az = "Digər"; break;
                                        }
                                        
                                        $difficulty_az = "";
                                        switch ($row['difficulty']) {
                                            case 1: $difficulty_az = "Asan"; break;
                                            case 2: $difficulty_az = "Orta"; break;
                                            case 3: $difficulty_az = "Çətin"; break;
                                            default: $difficulty_az = "Naməlum"; break;
                                        }
                                        
                                        echo "<div class='question-item' data-id='{$row['id']}' data-subject='{$row['subject']}' data-topic='{$row['topic']}' data-type='{$row['question_type']}' data-difficulty='{$row['difficulty']}'>";
                                        echo "<div class='question-header'>";
                                        echo "<div class='question-meta'>";
                                        echo "<span><i class='fas fa-book'></i> {$row['fenn_adi']}</span>";
                                        echo "<span><i class='fas fa-bookmark'></i> {$row['movzu_adi']}</span>";
                                        echo "<span><i class='fas fa-question-circle'></i> {$question_type_az}</span>";
                                        echo "<span><i class='fas fa-signal'></i> {$difficulty_az}</span>";
                                        echo "</div>";
                                        echo "<div class='action-buttons'>";
                                        echo "<button class='btn btn-sm btn-info' onclick='viewQuestion({$row['id']})'><i class='fas fa-eye'></i></button>";
                                        echo "<button class='btn btn-sm btn-primary' onclick='editQuestion({$row['id']})'><i class='fas fa-edit'></i></button>";                                    
                                        echo "<button class='btn btn-sm btn-danger' onclick='deleteRecord({$row['id']})'><i class='fas fa-trash'></i></button>";
                                        echo "</div>";
                                        echo "</div>";
                                        
                                        echo "<div class='question-text'>";
                                        echo "<div class='question-text-content'>";
                                        echo substr(strip_tags($row['question_text']), 0, 200) . (strlen(strip_tags($row['question_text'])) > 200 ? '...' : '');
                                        if (!empty($row['question_image'])) {
                                            echo "<div class='question-image-container'>";
                                            echo "<img src='" . htmlspecialchars($row['question_image']) . "' alt='Question Image' class='question-image' data-image='" . htmlspecialchars($row['question_image']) . "'>";
                                            echo "<span class='magnifier-icon'><i class='fas fa-search-plus'></i></span>";
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                        echo "</div>";
                                        
                                        if ($row['question_type'] == 'multiple_choice' && !empty($row['options'])) {
                                            $options = json_decode($row['options'], true);
                                            
                                            if (is_array($options)) {
                                                echo "<div class='question-options'>";
                                                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                                                foreach ($options as $index => $option) {
                                                    $letter = isset($letters[$index]) ? $letters[$index] : ($index + 1);
                                                    $is_correct = isset($option['isCorrect']) && $option['isCorrect'] ? true : false;
                                                    $option_class = $is_correct ? 'question-option question-option-correct' : 'question-option';
                                                    
                                                    echo "<div class='{$option_class}'>";
                                                    
                                                    $option_text = $option['text'];
                                                    $display_text = '';
                                                    $display_image = '';

                                                    $doc = new DOMDocument();
                                                    @$doc->loadHTML('<?xml encoding="UTF-8">' . $option_text); // Suppress warnings for malformed HTML
                                                    $xpath = new DOMXPath($doc);
                                                    
                                                    $text_nodes = $xpath->query('//text()[normalize-space()]');
                                                    foreach ($text_nodes as $node) {
                                                        $display_text .= trim($node->nodeValue) . ' ';
                                                    }
                                                    $display_text = htmlspecialchars(trim($display_text)); // Sanitize text
                                                    
                                                    $images = $xpath->query('//img');
                                                    if ($images->length > 0) {
                                                        $image_src = $images->item(0)->getAttribute('src');
                                                        $display_image = "<img src='" . htmlspecialchars($image_src) . "' alt='Option Image' class='option-image' data-fullscreen='" . htmlspecialchars($image_src) . "'>";
                                                    }
                                                    
                                                    echo "<span>{$letter}. ";
                                                    if ($display_image) {
                                                        echo $display_image . ' ';
                                                    }
                                                    echo $display_text . "</span>";
                                                    
                                                    if ($is_correct) {
                                                        echo "<i hidden style='background-color:red;' class='fas fa-check text-success'></i>";
                                                    }
                                                    echo "</div>";
                                                }
                                                echo "</div>";
                                            }
                                        }
                                        
                                        echo "</div>"; // End question item
                                    }
                                    mysqli_stmt_close($stmt);
                                } else {
                                    echo "<p>Heç bir sual tapılmadı.</p>";
                                }
                            } else {
                                echo "<p>Zəhmət olmasa daxil olun.</p>";
                            }
                            ?>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <script>
        function openFullScreenImage(imageUrl) {
            const overlay = document.createElement('div');
            overlay.id = 'fullScreenImageOverlay';
            overlay.innerHTML = `
                <div class="full-screen-image-container">
                    <span class="close-full-screen">×</span>
                    <img src="${imageUrl}" alt="Full Screen Question Image" class="full-screen-image">
                </div>
            `;
            document.body.appendChild(overlay);

            overlay.querySelector('.close-full-screen').addEventListener('click', closeFullScreenImage);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    closeFullScreenImage();
                }
            });
        }

        function closeFullScreenImage() {
            const overlay = document.getElementById('fullScreenImageOverlay');
            if (overlay) {
                overlay.remove();
            }
        }

        function attachImageClickListeners() {
            document.querySelectorAll('.question-image').forEach(img => {
                img.addEventListener('click', () => {
                    const imageUrl = img.getAttribute('data-image');
                    openFullScreenImage(imageUrl);
                });
                img.onerror = () => {
                    img.style.display = 'none';
                    img.nextElementSibling.style.display = 'none';
                };
            });
        }

        document.addEventListener('DOMContentLoaded', attachImageClickListeners);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <script src="movzular/suallar/script.js"></script>