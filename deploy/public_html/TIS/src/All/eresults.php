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
include('navbar_sidebar.php'); // Navigation and sidebar

// Example data (static array)
$exam_schedule = [
    [
        'group_name' => 'Qrup A',
        'exam_date' => '2025-05-01',
        'start_time' => '09:00',
        'end_time' => '11:00',
        'entry_score' => 80,
        'username' => 'user1',
        'password' => 'pass123',
        'exam_type' => 'Yazılı',
        'status' => 'Active',
        'blocked' => 'No'
    ],
    [
        'group_name' => 'Qrup B',
        'exam_date' => '2025-05-02',
        'start_time' => '13:00',
        'end_time' => '15:00',
        'entry_score' => 85,
        'username' => 'user2',
        'password' => 'pass456',
        'exam_type' => 'Şifahi',
        'status' => 'Inactive',
        'blocked' => 'Yes'
    ],
    [
        'group_name' => 'Qrup C',
        'exam_date' => '2025-05-03',
        'start_time' => '10:00',
        'end_time' => '12:00',
        'entry_score' => 90,
        'username' => 'user3',
        'password' => 'pass789',
        'exam_type' => 'Test',
        'status' => 'Active',
        'blocked' => 'No'
    ]
];
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
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .filters select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background-color: #fff;
            transition: border-color 0.2s;
            width: 100%;
            box-sizing: border-box;
        }

        .filters select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .pdf-btn {
            margin-left: 5px;
            padding: 8px 16px;
            background: rgba(8, 172, 117, 0.78);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
            min-width: 70px;
        }

        .pdf-btn:hover {
            background: rgb(4, 166, 112);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            color: #374151;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
        }

        th {
            background-color: #f1f5f9;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            color: #6b7280;
        }

        td {
            background-color: #ffffff;
        }

        tr:hover td {
            background-color: #f9fafb;
        }

        .status-passed {
            color: #10b981;
            font-weight: 500;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
        }

        .pagination span {
            font-size: 14px;
            color: #6b7280;
        }

        .pagination-buttons {
            display: flex;
            gap: 7px;
        }

        .pagination-buttons button {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            background-color: #fff;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 36px;
            text-align: center;
        }

        .pagination-buttons button.active {
            background-color: rgba(106, 86, 237, 0.87);
            color: white;
            border-color: rgba(16, 185, 129, 0);
        }

        .pagination-buttons button:hover:not(.active) {
            background-color: rgba(106, 86, 237, 0.24);
        }

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 700px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            color: #1f2937;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #1f2937;
        }

        .modal-body {
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .modal-body p {
            margin: 8px 0;
            font-size: 15px;
            color: #374151;
            display: flex;
            flex-direction: column;
        }

        .modal-body strong {
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            text-align: right;
        }

        .modal-footer button {
            padding: 8px 16px;
            background: rgba(8, 172, 117, 0.78);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .modal-footer button:hover {
            background: rgb(4, 166, 112);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .main-content {
                margin-left: 260px;
                padding: 20px;
            }
        }

        .main-content {
            margin-left: 0;
            margin-top: 90px;
            padding: 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            background-color: #f8fafc;
            border-radius: 12px;
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
            border: 4px solid #3182ce;
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

        @media (min-width: 1024px) {
            .main-content {
                margin-left: 260px;
            }

            .filters {
                gap: 10px;
                padding: 12px;
            }

            .filters select {
                font-size: 13px;
                padding: 7px 10px;
            }

            .pdf-btn {
                padding: 7px 14px;
                font-size: 13px;
            }

            th, td {
                padding: 10px 12px;
                font-size: 13px;
            }
        }

        @media (max-width: 767px) {
            .main-content {
                margin-left: 0;
                margin-top: 80px;
                padding: 15px;
            }
            .main-content.open {
                margin-left: 0;
            }
         
            .filters {
                flex-direction: column;
                padding: 10px;
            }

            .filters select {
                font-size: 12px;
                padding: 8px;
            }

            .pdf-btn {
                width: 100%;
                margin-left: 0;
                margin-top: 10px;
                padding: 8px;
                font-size: 12px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px 10px;
                font-size: 11px;
                white-space: nowrap;
            }

            th:nth-child(4), td:nth-child(4),
            th:nth-child(6), td:nth-child(6) {
                display: none;
            }

            .pagination {
                flex-direction: column;
                gap: 10px;
                padding: 10px;
            }

            .pagination span {
                font-size: 12px;
            }

            .pagination-buttons button {
                padding: 5px 10px;
                font-size: 12px;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-body {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .modal-header h2 {
                font-size: 20px;
            }

            .modal-body p {
                font-size: 14px;
            }

            .modal-body strong {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }
            .filters {
                padding: 8px;
            }

            .filters select {
                font-size: 11px;
            }

            .pdf-btn {
                font-size: 11px;
            }

            th, td {
                padding: 6px 8px;
                font-size: 10px;
            }

            td:nth-child(2) {
                white-space: normal;
                max-width: 150px;
            }

            .pagination span {
                font-size: 11px;
            }

            .pagination-buttons button {
                padding: 4px 8px;
                font-size: 11px;
                margin-bottom: 40px;
            }
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
        <!-- Filters -->
        <div class="filters">
            <select>
                <option>-Tədris ili seçin-</option>
            </select>
            <select>
                <option>-Yarımil seçin-</option>
            </select>
            <select>
                <option>-İmtahan növünü seçin-</option>
            </select>
            <button class="pdf-btn" aria-label="Download PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Nə</th>
                        <th scope="col">Fenn</th>
                        <th scope="col">İmtahan növü</th>
                        <th scope="col">Keçirilmə forması</th>
                        <th scope="col">Tarix</th>
                        <th scope="col">Giriş balı</th>
                        <th scope="col">İmtahan balı</th>
                        <th scope="col">Nəticə</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>01222 A1-Xarici dilde işgüzar və akademik kommunikasiya-1</td>
                        <td>Yekun imtahan 2</td>
                        <td>Yazılı imtahan</td>
                        <td>31/01/2025</td>
                        <td>47</td>
                        <td>20</td>
                        <td class="status-passed">97 A (əla)</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>00016 İKT - baza komputer bilikləri</td>
                        <td>Yekun imtahan</td>
                        <td>Elektron imtahan</td>
                        <td>30/01/2025</td>
                        <td>49</td>
                        <td>49</td>
                        <td class="status-passed">98 A (əla)</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>00004 Azərbaycan dilində işgüzar və akademik kommunikasiya</td>
                        <td>Ara imtahan 1</td>
                        <td>Elektron imtahan</td>
                        <td>29/11/2024</td>
                        <td></td>
                        <td>20</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>00016 İKT - baza komputer bilikləri</td>
                        <td>Ara imtahan 1</td>
                        <td>Elektron imtahan</td>
                        <td>29/11/2024</td>
                        <td></td>
                        <td>29</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>01222 A1-Xarici dilde işgüzar və akademik kommunikasiya-1</td>
                        <td>Yekun imtahan 3</td>
                        <td>Yazılı imtahan</td>
                        <td>28/01/2025</td>
                        <td>47</td>
                        <td>20</td>
                        <td class="status-passed">97 A (əla)</td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>00056 Xətti cəbr və riyazi analiz</td>
                        <td>Ara imtahan 1</td>
                        <td>Elektron imtahan</td>
                        <td>27/11/2024</td>
                        <td></td>
                        <td>20</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>01222 A1-Xarici dilde işgüzar və akademik kommunikasiya-1</td>
                        <td>Ara imtahan 2</td>
                        <td>Yazılı imtahan</td>
                        <td>27/11/2024</td>
                        <td></td>
                        <td>18</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>01224 Yumşaq bacarıqlar (Soft skills)</td>
                        <td>Ara imtahan 1</td>
                        <td>Elektron imtahan</td>
                        <td>25/04/2025</td>
                        <td></td>
                        <td>30</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>01222 A1-Xarici dilde işgüzar və akademik kommunikasiya-1</td>
                        <td>Yekun imtahan 1</td>
                        <td>Elektron imtahan</td>
                        <td>24/01/2025</td>
                        <td>47</td>
                        <td>10</td>
                        <td class="status-passed">97 A (əla)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <span>Məlumat 1</span>
            <div class="pagination-buttons">
                <button class="active" aria-label="Page 1">1</button>
                <button aria-label="Page 2">2</button>
                <button aria-label="Page 3">3</button>
                <button aria-label="Next page">Növbəti</button>
            </div>
        </div>

        <!-- Modal -->
        <div id="examModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>İmtahan Detalları</h2>
                </div>
                <div class="modal-body">
                    <p>
                        <strong>Fenn</strong>
                        <span id="modal-subject"></span>
                    </p>
                    <p>
                        <strong>İmtahan Növü</strong>
                        <span id="modal-exam-type"></span>
                    </p>
                    <p>
                        <strong>Keçirilmə Forması</strong>
                        <span id="modal-exam-format"></span>
                    </p>
                    <p>
                        <strong>Tarix</strong>
                        <span id="modal-date"></span>
                    </p>
                    <p>
                        <strong>Giriş Balı</strong>
                        <span id="modal-entry-score"></span>
                    </p>
                    <p>
                        <strong>İmtahan Balı</strong>
                        <span id="modal-exam-score"></span>
                    </p>
                    <p>
                        <strong>Nəticə</strong>
                        <span id="modal-result"></span>
                    </p>
                    <p>
                        <strong>Qrup Adı</strong>
                        <span id="modal-group-name"></span>
                    </p>
                    <p>
                        <strong>Başlama Saatı</strong>
                        <span id="modal-start-time"></span>
                    </p>
                    <p>
                        <strong>Bitmə Saatı</strong>
                        <span id="modal-end-time"></span>
                    </p>
                    <p>
                        <strong>İstifadəçi Adı</strong>
                        <span id="modal-username"></span>
                    </p>
                    <p>
                        <strong>Parol</strong>
                        <span id="modal-password"></span>
                    </p>
                    <p>
                        <strong>Status</strong>
                        <span id="modal-status"></span>
                    </p>
                    <p>
                        <strong>Bloklanıb</strong>
                        <span id="modal-blocked"></span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="close-modal-btn">Bağla</button>
                </div>
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
            // Exam schedule data from PHP
            const examSchedule = <?php echo json_encode($exam_schedule); ?>;

            // Modal elements
            const modal = $('#examModal');
            const modalClose = $('.modal-close, .close-modal-btn');

            // Table row click handler
            $('tbody tr td').on('click', function() {
                const row = $(this).closest('tr');
                const rowIndex = row.find('td:first').text() - 1; // Adjust for 0-based index
                const cells = row.find('td');

                // Get table data
                const subject = cells.eq(1).text();
                const examType = cells.eq(2).text();
                const examFormat = cells.eq(3).text();
                const date = cells.eq(4).text();
                const entryScore = cells.eq(5).text();
                const examScore = cells.eq(6).text();
                const result = cells.eq(7).text();

                // Get additional data from examSchedule array
                let additionalData = {
                    group_name: 'Məlumat yoxdur',
                    start_time: 'Məlumat yoxdur',
                    end_time: 'Məlumat yoxdur',
                    username: 'Məlumat yoxdur',
                    password: 'Məlumat yoxdur',
                    status: 'Məlumat yoxdur',
                    blocked: 'Məlumat yoxdur'
                };

                if (examSchedule[rowIndex]) {
                    additionalData = examSchedule[rowIndex];
                }

                // Populate modal
                $('#modal-subject').text(subject);
                $('#modal-exam-type').text(examType);
                $('#modal-exam-format').text(examFormat);
                $('#modal-date').text(date);
                $('#modal-entry-score').text(entryScore || 'Yoxdur');
                $('#modal-exam-score').text(examScore || 'Yoxdur');
                $('#modal-result').text(result || 'Yoxdur');
                $('#modal-group-name').text(additionalData.group_name);
                $('#modal-start-time').text(additionalData.start_time);
                $('#modal-end-time').text(additionalData.end_time);
                $('#modal-username').text(additionalData.username);
                $('#modal-password').text(additionalData.password);
                $('#modal-status').text(additionalData.status);
                $('#modal-blocked').text(additionalData.blocked);

                // Show modal
                modal.css('display', 'block');
            });

            // Close modal
            modalClose.on('click', function() {
                modal.css('display', 'none');
            });

            // Close modal when clicking outside
            $(window).on('click', function(event) {
                if (event.target === modal[0]) {
                    modal.css('display', 'none');
                }
            });
        });
    </script>
</body>
</html>