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
include('db.php');
include('navbar_sidebar.php');
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS - Calendar</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* General Layout */
        .main-content {
            margin-left: 0;
            margin-top: 86px;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
            align-content: flex-start;
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            border-radius: 8px;
        }

        .main-content.open {
            margin-left: 250px;
        }

        /* Box Design */
        .box {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 100%;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .box h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Table Design */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            color: #333;
        }

        .data-table thead {
            background-color: #f8f9fa;
            color: #333;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table th {
            font-weight: 600;
            text-transform: uppercase;
        }

        .data-table tbody tr:hover {
            background-color: #f1f3f5;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Table Footer Design */
        .data-table tfoot {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 500;
        }

        .data-table tfoot td {
            padding: 10px 15px;
            text-align: center;
            border-top: 2px solid #e9ecef;
        }

        /* Icon Styling */
        .data-table .fas.fa-exclamation-circle {
            color:rgb(255, 255, 255);
            padding: 10px;
            border-radius:50%;
            background:rgba(83, 83, 229, 0.73);
            cursor: pointer;
            transition: color 0.2s;
        }

        /* Modal Custom Styling */
        .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-title {
            font-size: 1.25rem;
            color: #333;
        }

        .modal-body {
            font-size: 0.9rem;
            color: #333;
        }

        .modal-body p {
            margin-bottom: 10px;
        }

        /* Responsive Design */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 767px) {
            .main-content {
                margin-left: 0;
                margin-top: 40px;
                padding: 15px;
            }
            .main-content.open {
                margin-left: 0;
            }
            .box {
                padding: 15px;
            }
            .data-table th,
            .data-table td,
            .data-table tfoot td {
                padding: 10px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }
            .box {
                padding: 10px;
            }
            .data-table th,
            .data-table td,
            .data-table tfoot td {
                padding: 8px;
                font-size: 0.8rem;
            }
            .modal-body {
                font-size: 0.85rem;
            }
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
        <div class="box">
            <h2></h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Tarixdən</th>
                        <th>Tarixə</th>
                        <th>Fakültə</th>
                        <th>Period</th>
                        <th>Semestr</th>
                        <th>Təlimat</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Sample Data (Replace with dynamic data from database) -->
                    <tr>
                        <td>1</td>
                        <td>2025-01-01</td>
                        <td>2025-06-30</td>
                        <td>İnformatika</td>
                        <td>1-ci il</td>
                        <td>1</td>
                        <td> <a href="#" class="fas fa-exclamation-circle" data-toggle="modal" data-target="#detailsModal" data-id="2" data-faculty="Mühəndislik" data-semester="2"></a> </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>2025-07-01</td>
                        <td>2025-12-31</td>
                        <td>Mühəndislik</td>
                        <td>2-ci il</td>
                        <td>2</td>
                        <td> <a href="#" class="fas fa-exclamation-circle" data-toggle="modal" data-target="#detailsModal" data-id="2" data-faculty="Mühəndislik" data-semester="2"></a> </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7">Məlumat 2</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Təlimat Detalları</h5>
                </div>
                <div class="modal-body">
                    <p><strong>ID:</strong> <span id="modal-id"></span></p>
                    <p><strong>Fakültə:</strong> <span id="modal-faculty"></span></p>
                    <p><strong>Semestr:</strong> <span id="modal-semester"></span></p>
                    <p><strong>Əlavə Məlumat:</strong> Bu modal nümunə məlumatları göstərir. Verilənlər bazasından real məlumat əlavə edin.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Bağla</button>
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
            $('.fas.fa-exclamation-circle').on('click', function() {
                // Get data attributes from the clicked icon
                var id = $(this).data('id');
                var faculty = $(this).data('faculty');
                var semester = $(this).data('semester');

                // Populate modal with data
                $('#modal-id').text(id);
                $('#modal-faculty').text(faculty);
                $('#modal-semester').text(semester);
            });
        });
    </script>
</body>
</html>