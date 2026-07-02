<?php
// Set UTF-8 encoding for proper character handling
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
include('navbar_sidebar.php');
include('db.php');

// Ensure the session variable is set; if not, redirect to login (optional)
if (!isset($_SESSION['username'])) {
    header("Location: Login.php");
    exit();
}

// Conditionally include right_bar.php if the user is a Student
if (strtolower($user_role) === 'student' || strtolower($user_role) === 'teacher' || strtolower($user_role) === 'parent') {
    include('right_bar.php');
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>TİS Əsas</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">

    <style>
        /* Your existing CSS remains unchanged */
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

        .main-content {
            margin-left: 0;
            margin-top: 50px;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            justify-content: flex-start;
            align-content: flex-start;
        }

        .main-content.open {
            margin-left: 250px;
        }

        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px;
            }

            .main-content .card {
                width: 31%;
            }
        }

        @media (min-width: 1024px) {
            .main-content .card {
                width: 23.5%;
            }
        }

        .content {
            position: absolute;
            top: 46%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            width: fit-content;
            padding: 20px;
            background: transparent;
        }

        .content .salam_text {
            font-size: 24px;
            opacity: 0.2;
            font-weight: bolder;
            text-align: center;
            white-space: nowrap;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .content .logo {
            font-size: 140px;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bolder;
            opacity: 0.04;
            text-align: center;
        }

        @media (max-width: 768px) {
            .content {
                top: 50%;
                padding: 15px;
            }

            .content .salam_text {
                font-size: 24px;
            }

            .content .logo {
                font-size: 100px;
            }
        }

        @media (max-width: 480px) {
            .content {
                top: 45%;
                padding: 10px;
            }

            .content .salam_text {
                font-size: 14px;
            }

            .content .logo {
                font-size: 75px;
            }
        }

        .username {
            position: relative;
            font-weight: 900;
            opacity: 1;
            top: 10px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(82, 82, 82, 0.62);
            padding: 6px 12px;
            color: black;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .fade-in {
            opacity: 0;
            transition: opacity 1s ease-in;
        }

        .animated-text {
            font-family: monospace;
            font-size: 24px;
            color: #00ff00;
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

    <div class="main-content">
        <div class="content">
            <p style="display:none;" class="logo">LOGO</p>
            <p class="salam_text">Sistemə Xoş Gəldin
                <br>
                <span class="username" id="username-span">
                    <?php 
                    if (isset($_SESSION['username'])) {
                        // Ensure proper UTF-8 encoding for username display
                        $username = $_SESSION['username'];
                        // Convert to UTF-8 if not already
                        if (!mb_check_encoding($username, 'UTF-8')) {
                            $username = mb_convert_encoding($username, 'UTF-8', 'auto');
                        }
                        echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
                    } else {
                        echo 'Qonaq';
                    }
                    ?>
                </span>
            </p>
        </div>
    </div>

    <script>
        // Fixed animateUsername function with proper UTF-8 handling
        function animateUsername() {
            var usernameSpan = document.getElementById("username-span");
            var username = usernameSpan.innerText;
            var randomChars = "!@#$%^&*()_+=-0987654321ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyzƏəÇçĞğIıİiÖöŞşÜü";
            var animationDuration = 2500;
            var steps = 45;
            var stepTime = animationDuration / steps;
            var intervalCount = 0;
            usernameSpan.innerText = '';

            var interval = setInterval(function () {
                let displayedText = '';
                // Use proper string length handling for UTF-8
                var usernameLength = [...username].length; // Handle multi-byte characters correctly
                
                for (let i = 0; i < usernameLength; i++) {
                    if (intervalCount > steps / 2 && i < intervalCount - (steps / 2)) {
                        displayedText += [...username][i]; // Get character by index properly
                    } else {
                        displayedText += randomChars[Math.floor(Math.random() * randomChars.length)];
                    }
                }
                usernameSpan.innerText = displayedText;

                if (++intervalCount >= steps) {
                    clearInterval(interval);
                    usernameSpan.innerText = username;
                }
            }, stepTime);
        }

        // Trigger the animation once the page is loaded
        window.onload = function () {
            // animateUsername(); // Uncomment if you want to use the animation
        };

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const openButton = document.querySelector('.open-button');
            const closeButton = document.querySelector('.close-button');
            const mainContent = document.querySelector('.main-content');

            sidebar.classList.toggle('open');
            mainContent.classList.toggle('open');

            // Toggle button visibility
            if (sidebar.classList.contains('open')) {
                openButton.style.display = 'none';
                closeButton.style.display = 'block';
            } else {
                openButton.style.display = 'block';
                closeButton.style.display = 'none';
            }
        }
    </script>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script src="../assets/extra-libs/c3/d3.min.js"></script>
    <script src="../assets/extra-libs/c3/c3.min.js"></script>
    <script src="../assets/libs/chartist/dist/chartist.min.js"></script>
    <script src="../assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
    <script src="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.min.js"></script>
    <script src="../assets/extra-libs/jvector/jquery-jvectormap-world-mill-en.js"></script>
    <script src="../dist/js/pages/dashboards/dashboard1.min.js"></script>
</body>
</html>