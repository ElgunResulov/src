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

// Handle month/year navigation
$selectedMonth = isset($_POST['month']) ? (int)$_POST['month'] : 10; // Default to October
$selectedYear = isset($_POST['year']) ? (int)$_POST['year'] : 2025; // Default to 2025

// Adjust month/year for navigation
if (isset($_POST['prev'])) {
    $selectedMonth--;
    if ($selectedMonth < 1) {
        $selectedMonth = 12;
        $selectedYear--;
    }
} elseif (isset($_POST['next'])) {
    $selectedMonth++;
    if ($selectedMonth > 12) {
        $selectedMonth = 1;
        $selectedYear++;
    }
} elseif (isset($_POST['today'])) {
    // Set to current date (April 2025, based on the current date: April 26, 2025)
    $today = new DateTime('2025-04-26');
    $selectedMonth = (int)$today->format('n'); // Month (1-12)
    $selectedYear = (int)$today->format('Y');  // Year
}

// Get the number of days in the selected month
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$firstDayOfMonth = new DateTime("$selectedYear-$selectedMonth-01");
$firstDayOfWeek = $firstDayOfMonth->format('N'); // 1 (Monday) to 7 (Sunday)

// Get the last day of the previous month for filling "other-month" days
$prevMonth = $selectedMonth == 1 ? 12 : $selectedMonth - 1;
$prevYear = $selectedMonth == 1 ? $selectedYear - 1 : $selectedYear;
$lastDayOfPrevMonth = cal_days_in_month(CAL_GREGORIAN, $prevMonth, $prevYear);

// Zoom sessions (hardcoded for now, can be fetched from DB later)
$zoomSessions = [
    '2025-10-10' => ['session' => 'Normal Hefte', 'teacher' => 'Müəllim: A. Rafiq', 'date_range' => '17.02.2025 - 30.06.2025'],
    '2025-10-11' => ['session' => 'Normal Hefte', 'teacher' => 'Müəllim: A. Rafiq', 'date_range' => '17.02.2025 - 30.06.2025'],
    '2025-10-25' => ['session' => 'Normal Hefte', 'teacher' => 'Müəllim: A. Rafiq', 'date_range' => '17.02.2025 - 30.06.2025'],
    '2025-10-26' => ['session' => 'Normal Hefte', 'teacher' => 'Müəllim: A. Rafiq', 'date_range' => '17.02.2025 - 30.06.2025'],
];

// Month names in Azerbaijani
$monthNames = [
    1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel', 5 => 'May', 6 => 'İyun',
    7 => 'İyul', 8 => 'Avqust', 9 => 'Sentyabr', 10 => 'Oktyabr', 11 => 'Noyabr', 12 => 'Dekabr'
];
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
            padding: 25px;
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-content: flex-start;
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
            border-radius: 12px;
            background: #f8fafc;
        }

        .main-content.open {
            margin-left: 250px;
        }

        /* Calendar Container */
        .calendar-box {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
            padding: 25px;
            width: 100%;
            max-width: 100%;
            margin-bottom: 25px;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
        }

        /* Form Group Styling */
        .form-group {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-btn {
            outline: none;
            border: none;
            border-radius: 8px;
            height: 40px;
            width: 40px;
            background: #3b82f6;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease, transform 0.1s ease;
            cursor: pointer;
        }

        .nav-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .nav-btn:focus {
            outline: 2px solid #1d4ed8;
            outline-offset: 2px;
        }

        /* Today Button Styling */
        .today-btn {
            outline: none;
            border: none;
            border-radius: 10px;
            height: 40px;
            width: 80px;
            background: #10b981;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease, transform 0.1s ease;
            cursor: pointer;
        }

        .today-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .today-btn:focus {
            outline: 2px solid #047857;
            outline-offset: 2px;
        }

        /* Select Dropdown Styling */
        .academic-year-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .academic-year-group label {
            font-size: 1rem;
            color: #1e293b;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .academic-year-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 40px 10px 14px;
            font-size: 0.95rem;
            color: #1e293b;
            width: 160px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231e293b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.1s ease;
            cursor: pointer;
        }

        .academic-year-group select:hover {
            border-color: #3b82f6;
            transform: translateY(-1px);
        }

        .academic-year-group select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Export Button */
        .excel_btn {
            outline: none;
            border: none;
            border-radius: 8px;
            height: 40px;
            width: 94px;
            background: #10b981;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease, transform 0.1s ease;
            cursor: pointer;
        }

        .excel_btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .excel_btn:focus {
            outline: 2px solid #047857;
            outline-offset: 2px;
        }

        .excel_btn img {
            height: 22px;
            width: 22px;
            margin-right: 10px;
        }

        /* Calendar Table */
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            color: #1e293b;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .calendar-table thead {
            color: #3b82f6;
            font-weight: 700;
            background: #f1f5f9;
        }

        .calendar-table th,
        .calendar-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
            height: 90px;
            vertical-align: top;
            transition: background 0.2s ease;
        }

        .calendar-table th {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Day Styling */
        .calendar-table td {
            position: relative;
            font-size: 0.9rem;
        }

        .calendar-table td.other-month {
            color: #94a3b8;
            background: #f9fafb;
        }

        .calendar-table td .day-number {
            position: absolute;
            top: 8px;
            left: 8px;
            font-weight: 600;
        }

        /* Zoom Session Styling */
        .calendar-table td .zoom-session {
            margin-top: 25px;
            font-size: 0.8rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .calendar-table td .zoom-session:hover {
            background: #dbeafe;
        }

        .calendar-table td .zoom-session-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #3b82f6;
        }

        /* Highlighted Range */
        .calendar-table td.highlighted {
            background-color: #dbeafe;
        }

        /* Legend */
        .calendar-legend {
            margin-top: 15px;
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: #1e293b;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 8px;
        }

        .calendar-legend span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .calendar-legend .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #3b82f6;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            position: fixed;
            transition: transform 0.3s ease-in-out;
        }

        .modal.show .modal-content {
            transform: translateZ(0vh) scale(0.97);
        }

        .modal.closing .modal-content {
            transform: translateZ(-20vh) scale(0.59);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease-in-out 0.1s, transform 0.3s ease-in-out 0.1s;
        }

        .modal.show .modal-header {
            opacity: 1;
            transform: translateY(0);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #1e293b;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close-btn:hover {
            color: #1e293b;
        }

        .modal-body {
            font-size: 0.95rem;
            color: #1e293b;
            line-height: 1.5;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease-in-out 0.2s, transform 0.3s ease-in-out 0.2s;
        }

        .modal.show .modal-body {
            opacity: 1;
            transform: translateY(0);
        }

        .modal-body p {
            margin: 8px 0;
        }

        .modal-body strong {
            color: #3b82f6;
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
                margin-top: 85px;
                padding: 20px;
            }
            .main-content.open {
                margin-left: 0;
            }
            .calendar-box {
                padding: 20px;
            }
            .academic-year-group label {
                font-size: 0.9rem;
            }
            .academic-year-group select {
                font-size: 0.9rem;
                padding: 8px 35px 8px 12px;
                width: 140px;
                background-size: 12px;
                background-position: right 12px center;
            }
            .excel_btn, .nav-btn, .today-btn {
                height: 35px;
                width: 35px;
                font-size: 0.9rem;
                padding: 0 10px;
            }
            .today-btn {
                width: 70px;
            }
            .excel_btn img {
                height: 20px;
                width: 20px;
                margin-right: 8px;
            }
            .calendar-table th,
            .calendar-table td {
                padding: 10px;
                height: 70px;
                font-size: 0.85rem;
            }
            .calendar-table td .zoom-session {
                font-size: 0.75rem;
                padding: 3px 6px;
            }
            .calendar-legend {
                font-size: 0.85rem;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }
            .calendar-box {
                padding: 15px;
            }
            .form-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .academic-year-group {
                width: 100%;
            }
            .academic-year-group label {
                font-size: 0.85rem;
            }
            .academic-year-group select {
                font-size: 0.85rem;
                padding: 6px 30px 6px 10px;
                width: 100%;
                max-width: 100%;
                background-size: 10px;
                background-position: right 10px center;
            }
            .button-group {
                width: 100%;
                justify-content: space-between;
            }
            .nav-btn, .today-btn {
                height: 32px;
                width: 32px;
                font-size: 0.85rem;
                padding: 0 8px;
            }

            .excel_btn{
          width: 90px;
            } 

            .today-btn {
                width: 80px;
            }
            .excel_btn img {
                height: 18px;
                width: 18px;
                margin-right: 6px;
            }
            .calendar-table th,
            .calendar-table td {
                padding: 8px;
                height: 60px;
                font-size: 0.8rem;
            }
            .calendar-table td .zoom-session {
                font-size: 0.7rem;
                padding: 2px 4px;
            }
            .calendar-table td .zoom-session-dot {
                width: 8px;
                height: 8px;
            }
            .calendar-legend {
                font-size: 0.8rem;
                gap: 10px;
            }
            .calendar-legend .legend-dot {
                width: 10px;
                height: 10px;
            }
            .modal-content {
                width: 95%;
                padding: 15px;
            }
            .modal-header h3 {
                font-size: 1.1rem;
            }
            .modal-body {
                font-size: 0.9rem;
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
        <div class="calendar-box">
            <form method="post" class="mb-3 form-group">
                <div class="academic-year-group">
                    <label for="academic-year"><?php echo $monthNames[$selectedMonth] . ' ' . $selectedYear; ?></label>
                    <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
                    <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
                    <div class="nav-buttons">
                        <button type="submit" name="prev" class="nav-btn" title="Previous Month"><i class="fas fa-arrow-left"></i></button>
                        <button type="submit" name="today" class="today-btn" id="today-btn" title="Today">Bu gün</button>
                        <button type="submit" name="next" class="nav-btn" title="Next Month"><i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                <div class="button-group">
                    <button type="button" class="excel_btn" title="Export to PDF">
                        <img src="images/excel.png" alt="Excel Icon"> PDF
                    </button>
                </div>
            </form>
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dayCount = 1;
                    $currentDay = 1;

                    // Adjust the first day of the week (PHP's 'N' is 1 for Monday, but we want Sunday as the first day)
                    $firstDayOfWeek = ($firstDayOfWeek % 7); // Convert to 0 (Sunday) to 6 (Saturday)

                    // Calculate the starting day for the previous month
                    $prevMonthDay = $lastDayOfPrevMonth - $firstDayOfWeek + 1;

                    // Loop through the weeks (up to 6 rows to cover all days)
                    for ($week = 0; $week < 6; $week++) {
                        echo '<tr>';

                        // Loop through each day of the week (0 = Sunday, 6 = Saturday)
                        for ($i = 0; $i < 7; $i++) {
                            $position = $week * 7 + $i;

                            // Before the first day of the month
                            if ($position < $firstDayOfWeek) {
                                echo '<td class="other-month">' . $prevMonthDay . '</td>';
                                $prevMonthDay++;
                            }
                            // After the last day of the month
                            elseif ($dayCount > $daysInMonth) {
                                echo '<td class="other-month">' . ($dayCount - $daysInMonth) . '</td>';
                                $dayCount++;
                            }
                            // Days of the current month
                            else {
                                $currentDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $dayCount);
                                $rangeClass = ($currentDate >= '2025-10-25' && $currentDate <= '2025-10-26') ? 'highlighted' : '';

                                echo '<td class="' . $rangeClass . '">';
                                echo '<span class="day-number">' . $dayCount . '</span>';

                                // Check if there's a Zoom session on this date
                                if (isset($zoomSessions[$currentDate])) {
                                    $session = $zoomSessions[$currentDate];
                                    echo '<div class="zoom-session" data-session="' . htmlspecialchars($session['session']) . '" data-teacher="' . htmlspecialchars($session['teacher']) . '" data-date-range="' . htmlspecialchars($session['date_range']) . '">';
                                    echo '<span class="zoom-session-dot"></span>';
                                    echo htmlspecialchars($session['session']);
                                    echo '</div>';
                                }

                                echo '</td>';
                                $dayCount++;
                            }
                        }

                        echo '</tr>';

                        // Break if we've displayed all days of the month
                        if ($dayCount > $daysInMonth) {
                            break;
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div class="calendar-legend">
                <span><span class="legend-dot"></span> Normal Hefte</span>
            </div>
        </div>
    </div>

    <!-- Modal Structure -->
    <div id="zoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Zoom Sessiya Məlumatları</h3>
            </div>
            <div class="modal-body">
                <p><strong>Sessiya:</strong> <span id="modal-session"></span></p>
                <p><strong>Müəllim:</strong> <span id="modal-teacher"></span></p>
                <p><strong>Tarix Aralığı:</strong> <span id="modal-date-range"></span></p>
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
            // Get modal element
            const modal = $('#zoomModal');
            const closeBtn = $('.close-btn');

            // Add click event to all Zoom sessions
            $('.zoom-session').on('click', function() {
                // Get Zoom session details from data attributes
                const session = $(this).data('session') || 'Məlumat yoxdur';
                const teacher = $(this).data('teacher') || 'Məlumat yoxdur';
                const dateRange = $(this).data('date-range') || 'Məlumat yoxdur';

                // Debug: Log data to console
                console.log('Zoom Session Data:', { session, teacher, dateRange });

                // Populate modal with Zoom session details
                $('#modal-session').text(session);
                $('#modal-teacher').text(teacher);
                $('#modal-date-range').text(dateRange);

                // Show modal with animation
                modal.removeClass('closing').css('display', 'flex').addClass('show');
            });

            // Function to close modal
            function closeModal() {
                modal.removeClass('show').addClass('closing');
                setTimeout(() => {
                    modal.css('display', 'none').removeClass('closing');
                }, 300); // Match transition duration
            }

            // Close modal when clicking the close button
            closeBtn.on('click', closeModal);

            // Close modal when clicking outside the modal content
            modal.on('click', function(event) {
                if ($(event.target).is(modal)) {
                    closeModal();
                }
            });

            // Close modal with Escape key
            $(document).on('keydown', function(event) {
                if (event.key === 'Escape' && modal.hasClass('show')) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>