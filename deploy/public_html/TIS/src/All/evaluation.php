<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for user authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Include necessary files
include('navbar_sidebar.php');
include('db.php');
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>

        .main-content {
            margin-left: 0;
            margin-top: 90px;
            padding: 10px;
            display: flex;
            flex-direction: column;
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            background-color: transparent;
            min-height: calc(100vh - 90px);
        }

        .main-content.open {
            margin-left: 260px;
        }

        .lds-ripple {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-ripple div {
            position: absolute;
            border: 4px solid #3b82f6;
            opacity: 1;
            border-radius: 50%;
            animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }

        .lds-ripple div:nth-child(2) {
            animation-delay: -0.5s;
        }

        @keyframes lds-ripple {
            0% {
                top: 36px;
                left: 36px;
                width: 0;
                height: 0;
                opacity: 1;
            }
            100% {
                top: 0;
                left: 0;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

        .form-container {
            max-width: 100%;
            margin: 0 auto;
            text-align: right;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.1s ease;
        }

        .btn-open-modal {
            background-color: #3b82f6;
            color: #fff;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }

        .btn-open-modal:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }

        .table-container {
            width: 100%;
            max-width: 100%;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        th, td {
            padding: 14px 20px;
            text-align: left;
            font-family: Arial;
        }

        th {
            background-color: #f9fafb;
            color: #6b7280;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            color: #374151;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f9fafb;
            transition: background-color 0.2s ease;
        }

        .no-results {
            text-align: center;
            color: #ef4444;
            font-style: italic;
            font-size: 14px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 24px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 16px;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s ease;
        }

        .close:hover {
            color: #374151;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
            color: #374151;
            background-color: #fff;
            transition: border-color 0.2s ease;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            height: 80px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-success {
            background-color: #10b981;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .notice {
            text-align: center;
            color: #ef4444;
            font-size: 14px;
            margin-top: 10px;
        }

        h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }

        /* Desktop Styles */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 260px;
            }
        }

        /* Tablet Styles */
        @media (max-width: 767px) {
            .main-content {
                margin-left: 0;
                margin-top: 80px;
                padding: 16px;
            }
            .main-content.open {
                margin-left: 0;
            }
        }

        /* Mobile Styles */
        @media (max-width: 480px) {
            .main-content {
                padding: 12px;
                margin-top: 70px;
            }
            th, td {
                padding: 10px 12px;
                font-size: 12px;
            }
            .modal-content {
                margin: 10% auto;
                padding: 16px;
            }
        }

        .fa-file:hover{
            transition:0.32s ease-in-out;
            color:rgb(14, 170, 72);
        }
    </style>
</head>
<body>

    <div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>

    <div class="main-content main">
        <div class="table-container">
        <div class="form-container">
            <i style="transform:scale(0.94);  cursor:pointer; padding:20px;font-size:40px;" onclick="openModal()" class="fas fa-file">
                <i style="position:relative; margin-left:-24px; top:8px; transform:scale(0.7); font-size:16px; text-align:center;align-items:center; line-height:28px; background-color:rgb(14, 170, 72);color:white; height:28px;width:28px; border-radius:50%;" class="fas fa-plus"></i>
            </i>
        </div>
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Müəllim</th>
                        <th>Qiymət</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3" class="no-results">Nəticə yoxdur.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div id="surveyModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">×</span>
                <h3>Məmnunluq anketi</h3>
                <form id="surveyForm" action="submit_survey.php" method="POST">
                    <div class="form-group">
                        <label for="task">Tapşırıq</label>
                        <select id="task" name="task" required>
                            <option value="">-- Seçin --</option>
                            <option value="task1">Tapşırıq 1</option>
                            <option value="task2">Tapşırıq 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Yaradılma tarixi</label>
                        <select id="year" name="year" required>
                            <option value="">-- Seçin --</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department">Müəllim</label>
                        <select id="department" name="department" required>
                            <option value="">-- Seçin --</option>
                            <option value="dept1">Kafedra 1</option>
                            <option value="dept2">Kafedra 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="material">Fərdi nəticələr üzrə müəllimlərə təşəkkür məktubu təqdim edirsinizmi?</label>
                        <select id="material" name="material" required>
                            <option value="">-- Seçin --</option>
                            <option value="yes">Bəli</option>
                            <option value="no">Xeyr</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="research">Müəllimlərə fərdi nəticələrə görə mükafat təyin edirsinizmi?</label>
                        <select id="research" name="research" required>
                            <option value="">-- Seçin --</option>
                            <option value="yes">Bəli</option>
                            <option value="no">Xeyr</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity">Müəllimlərə fərdi nəticələrə görə əmək haqqına əlavə təyin edirsinizmi?</label>
                        <select id="activity" name="activity" required>
                            <option value="">-- Seçin --</option>
                            <option value="yes">Bəli</option>
                            <option value="no">Xeyr</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="other">Digər təşviqlər hansılardır?</label>
                        <textarea id="other" name="other" placeholder="Ətraflı yazın"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="satisfaction">Müəllim məmnunluğu səviyyəsi</label>
                        <select id="satisfaction" name="satisfaction" required>
                            <option value="">-- Seçin --</option>
                            <option value="very_satisfied">Çox məmnun</option>
                            <option value="satisfied">Məmnun</option>
                            <option value="neutral">Neytral</option>
                            <option value="dissatisfied">Narazı</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="student_satisfaction">Tələbə məmnunluğu səviyyəsi</label>
                        <select id="student_satisfaction" name="student_satisfaction" required>
                            <option value="">-- Seçin --</option>
                            <option value="very_satisfied">Çox məmnun</option>
                            <option value="satisfied">Məmnun</option>
                            <option value="neutral">Neytral</option>
                            <option value="dissatisfied">Narazı</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exam_results">İmtahan nəticələri</label>
                        <select id="exam_results" name="exam_results" required>
                            <option value="">-- Seçin --</option>
                            <option value="excellent">Əla</option>
                            <option value="good">Yaxşı</option>
                            <option value="average">Orta</option>
                            <option value="poor">Zəif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Müəllim tədrisə başlama tarixi</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="comment">Əlavə şərhlər</label>
                        <textarea id="comment" name="comment" placeholder="Əlavə şərhlər"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="overall">Ümumi</label>
                        <select id="overall" name="overall" required>
                            <option value="">-- Seçin --</option>
                            <option value="excellent">Əla</option>
                            <option value="good">Yaxşı</option>
                            <option value="average">Orta</option>
                            <option value="poor">Zəif</option>
                        </select>
                    </div>
                    <div class="notice">Nəticə yoxdur.</div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success">Təsdiq</button>
                        <button type="submit" class="btn btn-primary">İmtina</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#surveyForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        alert('Anket uğurla göndərildi!');
                        form[0].reset();
                        closeModal();
                    },
                    error: function() {
                        alert('Xəta baş verdi, zəhmət olmasa yenidən cəhd edin.');
                    }
                });
            });
        });

        function openModal() {
            document.getElementById('surveyModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('surveyModal').style.display = 'none';
        }

        window.onclick = function(event) {
            let modal = document.getElementById('surveyModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>