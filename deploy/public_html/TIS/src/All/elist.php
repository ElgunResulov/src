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
        /* General Layout */
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

        h2 {
            font-size: 1.8rem;
            color: #1a202c;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Table Container */
        .table-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 10px;
            z-index: 0;
        }

        /* Table Styling */
        .exam-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .exam-table th, .exam-table td {
            padding: 16px;
            text-align: left;
            font-size: 0.95rem;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
        }

        .exam-table th {
            background-color: #edf2f7;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #4a5568;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .exam-table tbody tr {
            transition: all 0.2s ease;
        }

        .exam-table tbody tr:nth-child(even) {
            background-color: #f7fafc;
        }

        .exam-table tbody tr:hover {
            background-color: #edf2f7;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        /* Status and Blocked Styling */
        .exam-table td.status-active {
            color: #2f855a;
            font-weight: 500;
        }

        .exam-table td.status-inactive {
            color: #e53e3e;
            font-weight: 500;
        }

        .exam-table td.blocked-yes {
            color: #c53030;
            font-weight: 500;
        }

        .exam-table td.blocked-no {
            color: #2f855a;
            font-weight: 500;
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

        /* Responsive Design */
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 260px;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .main-content {
                margin-left: 260px;
                padding: 20px;
            }
        }

        @media (max-width: 767px) {
            .main-content {
                margin-left: 0;
                margin-top: 60px;
                padding: 15px;
            }
            .main-content.open {
                margin-left: 0;
            }
            .table-container {
                max-width: 100%;
                overflow-x: auto;
            }
            .exam-table th, .exam-table td {
                font-size: 0.9rem;
                padding: 12px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }
            .exam-table th, .exam-table td {
                font-size: 0.85rem;
                padding: 10px;
            }
            h2 {
                font-size: 1.5rem;
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
        <h2></h2>
        <div class="table-container">
            <?php if (!empty($exam_schedule)): ?>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Qrupun adı</th>
                            <th>İmtahan tarixi</th>
                            <th>Başlama vaxtı</th>
                            <th>Bitmə vaxtı</th>
                            <th>Giriş balı</th>
                            <th>İstifadəçi adı</th>
                            <th>Şifrə</th>
                            <th>İmtahan növü</th>
                            <th>Status</th>
                            <th>Bloklanıb</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($exam_schedule as $row):
                        ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($row['group_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['exam_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['entry_score']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['password']); ?></td>
                                <td><?php echo htmlspecialchars($row['exam_type']); ?></td>
                                <td class="status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></td>
                                <td class="blocked-<?php echo strtolower($row['blocked']); ?>"><?php echo htmlspecialchars($row['blocked']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7">Məlumat 3</td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p style="padding: 20px; color: #e53e3e;">Heç bir imtahan məlumatı tapılmadı.</p>
            <?php endif; ?>
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
        // Fade out preloader after page load
        window.addEventListener('load', () => {
            const preloader = document.querySelector('.preloader');
            preloader.style.opacity = '0';
            setTimeout(() => preloader.style.display = 'none', 300);
        });
    </script>
</body>
</html>