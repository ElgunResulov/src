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
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
        animation: slideIn 0.3s ease;

    }

    .modal-content {
        background-color: #fff;
        border-radius: 8px;
        width: 90%;
        max-width: 1200px;
        margin: 4% auto;
        position: relative;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .modal-content h3 {
        padding: 15px 20px;
        margin: 0;
        font-family: Arial;
        border-radius: 8px 8px 0 0;
        font-size: 18px;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .survey-form {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        padding: 20px;
        background-color: #fafafa;
        border-radius: 0 0 8px 8px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }

    .form-group select,
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 20px;
        box-sizing: border-box;
        font-size: 14px;
        color: #374151;
        background-color: #fff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-group select:focus,
    .form-group input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .button-group {
        grid-column: span 4;
        text-align: right;
        margin-top: 10px;
    }

    .confirm-btn, .cancel-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .confirm-btn {
        background-color: #4CAF50;
        color: white;
    }

    .confirm-btn:hover {
        background-color: #45a049;
    }

    .cancel-btn {
        background-color: #f44336;
        color: white;
        margin-left: 10px;
    }

    .cancel-btn:hover {
        background-color: #e53935;
    }


        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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

        @media (max-width: 768px) {
        .survey-form {
            grid-template-columns: repeat(2, 1fr);
        }

        .button-group {
            grid-column: span 2;
        }
    }

        /* Desktop Styles */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 260px;
            }
            .filter-section {
                flex-direction: row;
            }
            .filter-section select {
                width: auto;
                max-width: none;
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
            .filter-section {
                flex-direction: column;
                gap: 8px;
            }
            .filter-section select {
                max-width: none;
            }
            .material-table th, .material-table td {
                padding: 10px;
                font-size: 13px;
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
            .filter-section {
                gap: 6px;
            }
            .filter-section select {
                padding: 5px 8px;
                font-size: 12px;
            }
            .no-data {
                font-size: 12px;
                padding: 10px;
            }
            .survey-form {
            grid-template-columns: 1fr;
        }

        .button-group {
            grid-column: span 1;
        }

        .modal-content {
            width: 95%;
        }
        }

        .fa-file:hover{
            transition:0.32s ease-in-out;
            color:rgb(14, 170, 72);
        }

         /* Table and Filter Styles */
         .filter-section {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .filter-section select {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #fff;
            font-size: 14px;
            transition: border-color 0.3s ease;
            width: 100%;
            max-width: 200px;
        }

        .filter-section select:focus {
            outline: none;
            border-color: #3182ce;
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

        <div class="filter-section">
            <select>
                <option>Tədris ili seçin</option>
                <option>2024-2025</option>
                <option>2023-2024</option>
            </select>
            <select>
                <option>Semestri seçin</option>
                <option>I Semestr</option>
                <option>II Semestr</option>
            </select>
            <select>
                <option>Fənni seçin</option>
                <option>Riyaziyyat</option>
                <option>Fizika</option>
            </select>
        </div>


            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Fənn</th>
                        <th>Şikayətin tarixi</th>
                        <th>Şikayətin tipi</th>
                        <th>Şikayətin alt tipi</th>
                        <th>Şikayətin statusu</th>
                        <th>Şikayətin kateqoriyası</th>
                        <th>Qeyd</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="no-results">Nəticə yoxdur.</td>
                    </tr>
                </tbody>
            </table>
        </div>

      
        <div id="surveyModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">×</span>
                <h3>Ərizə</h3>
                <form class="survey-form">
                    <!-- KIME -->
                    <div class="form-group">
                        <label for="kime">KIME</label>
                        <input type="text" id="kime" name="kime" value="Geray Musayev Cumşud" placeholder="Adı və soyadı daxil edin" required>
                    </div>

                    <!-- KIMDEN -->
                    <div class="form-group">
                        <label for="kimden">KIMDEN</label>
                        <input type="text" id="kimden" name="kimden" value="Aişə Tahirova Faiq" placeholder="Adı və soyadı daxil edin" required>
                    </div>

                    <!-- FAKULTE -->
                    <div class="form-group">
                        <label for="fakulte">FAKULTE</label>
                        <input type="text" id="fakulte" name="fakulte" value="Rus iqtisad məktəbi" placeholder="Fakültə adını daxil edin" required>
                    </div>

                    <!-- QRUP -->
                    <div class="form-group">
                        <label for="qrup">QRUP</label>
                        <input type="text" id="qrup" name="qrup" value="24.02.125, İqtisadiyyat" placeholder="Qrup adını daxil edin" required>
                    </div>

                    <!-- SIKAYETIN TARIHI -->
                    <div class="form-group">
                        <label for="sikayetTarixi">ŞIKAYƏTİN TARİXI</label>
                        <input type="date" id="sikayetTarixi" name="sikayetTarixi" value="2025-04-28" required>
                    </div>

                    <!-- TEDRIS ILI -->
                    <div class="form-group">
                        <label for="tedrisIli">TƏDRİS İLİ</label>
                        <select id="tedrisIli" name="tedrisIli" required>
                            <option value="">--Tədris ilini seçin--</option>
                            <option value="2024-2025">2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                        </select>
                    </div>

                    <!-- SEMESTR -->
                    <div class="form-group">
                        <label for="semestr">SEMESTR</label>
                        <select id="semestr" name="semestr" required>
                            <option value="">--Semestri seçin--</option>
                            <option value="1">1-ci semestr</option>
                            <option value="2">2-ci semestr</option>
                        </select>
                    </div>

                    <!-- FENN -->
                    <div class="form-group">
                        <label for="fenn">FƏNN</label>
                        <select id="fenn" name="fenn" required>
                            <option value="">--Fənni seçin--</option>
                            <option value="math">Riyaziyyat</option>
                            <option value="economics">İqtisadiyyat</option>
                        </select>
                    </div>

                    <!-- SIKAYETIN TIPI -->
                    <div class="form-group">
                        <label for="sikayetTipi">ŞIKAYƏTİN TİPİ</label>
                        <select id="sikayetTipi" name="sikayetTipi" required>
                            <option value="">--Şikayətin tipini seçin--</option>
                            <option value="grade">Qiymətləndirmə</option>
                            <option value="exam">İmtahan prosesi</option>
                        </select>
                    </div>

                    <!-- IMTAHAN NOVU -->
                    <div class="form-group">
                        <label for="imtahanNovu">İMTAHAN NÖVÜ</label>
                        <select id="imtahanNovu" name="imtahanNovu" required>
                            <option value="">--İmtahanın növünü seçin--</option>
                            <option value="midterm">Aralıq imtahan</option>
                            <option value="final">Yekun imtahan</option>
                        </select>
                    </div>

                    <!-- KECIRILME FORMASI -->
                    <div class="form-group">
                        <label for="kecirilmeFormasi">KEÇİRİLMƏ FORMASI</label>
                        <select id="kecirilmeFormasi" name="kecirilmeFormasi" required>
                            <option value="">--İmtahanın keçirilmə formasını seçin--</option>
                            <option value="written">Yazılı</option>
                            <option value="oral">Şifahi</option>
                        </select>
                    </div>

                    <!-- FENN UZRE QRUP -->
                    <div class="form-group">
                        <label for="fennUzreQrup">FƏNN ÜZRƏ QRUP</label>
                        <select id="fennUzreQrup" name="fennUzreQrup" required>
                            <option value="">--Fənn üzrə qrupu seçin--</option>
                            <option value="group1">Qrup 1</option>
                            <option value="group2">Qrup 2</option>
                        </select>
                    </div>

                    <!-- SIKAYET KATEQORIYASI -->
                    <div class="form-group">
                        <label for="sikayetKateqoriyasi">ŞIKAYƏTİN KATEQORİYASI</label>
                        <select id="sikayetKateqoriyasi" name="sikayetKateqoriyasi" required>
                            <option value="">--Şikayətin kateqoriyasını seçin--</option>
                            <option value="academic">Akademik</option>
                            <option value="administrative">İnzibati</option>
                        </select>
                    </div>

                    <!-- IMTAHAN TARIHI -->
                    <div class="form-group">
                        <label for="imtahanTarixi">İMTAHAN TARİXI</label>
                        <input type="date" id="imtahanTarixi" name="imtahanTarixi" required>
                    </div>

                    <!-- IMTAHAN BALI -->
                    <div class="form-group">
                        <label for="imtahanBali">İMTAHAN BALI</label>
                        <input type="number" id="imtahanBali" name="imtahanBali" placeholder="İmtahan balını daxil edin" min="0" max="100">
                    </div>

                    <!-- YEKUN BAL -->
                    <div class="form-group">
                        <label for="yekunBal">YEKUN BAL</label>
                        <input type="number" id="yekunBal" name="yekunBal" placeholder="Yekun balı daxil edin" min="0" max="100">
                    </div>

                    <!-- Buttons -->
                    <div class="button-group">
                        <button type="button" class="confirm-btn">Təsdiq et</button>
                        <button type="button" class="cancel-btn" onclick="closeModal()">İmtina et</button>
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