<?php
header("HTTP/1.0 404 Not Found");
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Səhifə Tapılmadı</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(-45deg, #ffffffff);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .student {
            fill: none;
            stroke: #1f2937;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .book {
            fill: #f87171;
            stroke: #1f2937;
            stroke-width: 1;
        }
        .chair {
            fill: #6b7280;
            stroke: #1f2937;
            stroke-width: 1.5;
        }
        .head {
            transform-origin: 90px 60px;
            animation: nod 1s ease-in-out infinite;
        }
        .arm-left {
            transform-origin: 90px 90px;
            animation: wave-left 2s ease-in-out infinite;
        }
        .arm-right {
            transform-origin: 110px 90px;
            animation: wave-right 2.2s ease-in-out infinite;
        }
        .book-page {
            transform-origin: 80px 110px;
            animation: flip-page 4s ease-in-out infinite;
        }
        .eyes {
            animation: blink 5s ease-in-out infinite;
        }
        @keyframes nod {
            0%{ transform: rotate(0deg); }
            100% { transform: rotate(1deg); }
        }
        @keyframes wave-left {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(-15deg); }
        }
        @keyframes wave-right {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(15deg); }
        }
        @keyframes flip-page {
            0%, 100% { transform: rotateY(0deg); }
            50% { transform: rotateY(-30deg); }
        }
        @keyframes blink {
            0%, 90%, 100% { transform: scaleY(1); }
            95% { transform: scaleY(0.1); }
        }
        .shadow-pulse {
            animation: shadow-pulse 3s ease-in-out infinite;
        }
        @keyframes shadow-pulse {
            0%, 100% { box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }
            50% { box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center text-gray-800">
    <div class="container mx-auto px-4 text-center max-w-xl">
        <div class="svg-container w-64 h-64 md:w-80 md:h-80 mx-auto mb-8">
            <svg viewBox="0 0 200 200" class="student-svg">
                <!-- Chair -->
                <g class="chair">
                    <rect x="70" y="120" width="60" height="5" rx="2" />
                    <rect x="70" y="125" width="10" height="30" />
                    <rect x="120" y="125" width="10" height="30" />
                    <rect x="75" y="100" width="50" height="20" rx="3" fill="#9ca3af" />
                </g>
                <!-- Student figure -->
                <g class="student">
                    <!-- Head -->
                    <circle class="head" cx="100" cy="60" r="20" />
                    <!-- Eyes -->
                    <ellipse class="eyes" cx="92" cy="50" rx="2" ry="2" fill="#1f2937" />
                    <ellipse class="eyes" cx="108" cy="50" rx="2" ry="2" fill="#1f2937" />
                    <ellipse class="eyes" cx="100" cy="68" rx="9" ry="7" fill="#1f2937" />
                    <!-- Body -->
                    <path d="M100 80 L100 120" />
                    <!-- Arms -->
                    <path class="arm-left" d="M100 90 L80 110" />
                    <path class="arm-right" d="M100 90 L120 110" />
                    <!-- Legs -->
                    <path d="M100 120 L90 150" />
                    <path d="M100 120 L110 150" />
                </g>
                <!-- Book -->
                <g class="book">
                    <rect x="80" y="100" width="40" height="20" rx="5" />
                    <rect class="book-page" x="80" y="100" width="20" height="20" rx="2" fill="#fecaca" />
                    <path d="M80 110 H120" />
                </g>
            </svg>
        </div>
        <h1 class="text-5xl md:text-5xl font-extrabold text-gray-900 mb-4 drop-shadow-lg">404 - Səhifə Tapılmadı</h1>
        <p class="text-lg md:text-1xl text-gray-700 mb-8 font-medium">Axtardığınız səhifə yoxdur, amma narahat olmayın!</p>
        <a href="/All/TIS/src/All/Home.php" class="inline-block bg-red-500 text-white font-semibold py-3 px-8 rounded-full shadow-md hover:bg-red-600 transition-colors duration-300 shadow-pulse">
            Ana Səhifəyə Qayıt
        </a>
    </div>
</body>
</html>
