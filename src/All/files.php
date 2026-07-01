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

        /* Table Wrapper for Horizontal Scroll on Mobile */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .material-table {
            width: 100%;
            min-width: 700px; /* Ensures table is wide enough to prevent squashing */
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .material-table th, .material-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }

        .material-table th {
            background-color: #f7fafc;
            color: #2d3748;
            font-weight: 500;
            font-size: 14px;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .material-table td {
            color: #4a5568;
            font-size: 14px;
        }

        .material-table tbody tr:hover {
            background-color: #f7fafc;
        }

        .no-data {
            text-align: center;
            padding: 15px;
            color: #a0aec0;
            font-size: 14px;
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
                padding: 10px;
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
                padding: 8px;
                margin-top: 85px;
            }
            .material-table th, .material-table td {
                padding: 8px;
                font-size: 12px;
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
            <select>
                <option>Müəllimi seçin</option>
                <option>Müəllim 1</option>
                <option>Müəllim 2</option>
            </select>
        </div>

        <div class="table-wrapper">
            <table class="material-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Mövzu</th>
                        <th>Mühazirə</th>
                        <th>Tapşırıq</th>
                        <th>Test</th>
                        <th>Seminar</th>
                        <th>Digər</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="no-data">Nəticə yoxdur.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
</body>
</html>