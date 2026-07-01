<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure session is valid
if (!isset($_SESSION['u_id']) || empty($_SESSION['u_id'])) {
    echo "<script>alert('Session expired or invalid user. Redirecting to login...'); window.location.href = 'Login.php';</script>";
    exit;
}

include('db.php');
include('navbar_sidebar.php');
?>


<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Universitet Məlumatları</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>


/* Preloader */
.preloader {
    display: flex;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    z-index: 9999;
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
/* Main Content */
.main-content {
    margin-top: 90px;
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    background-color: #f8fafc;
    border-radius: 12px;
    min-height: calc(100vh - 90px);
    transition: margin-left 0.3s ease;
}

.main-content.open {
    margin-left: 260px;
}

/* User Info Section */
.user-info-section {
    background-color: #fff;
    margin: 20px auto;
    width: 100%;
    max-width: 95%;
    border-radius: 12px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.section-header {
    background-color: rgba(49, 130, 206, 0);
    padding: 15px 20px;
    border-radius: 12px 12px 0 0;
}

.section-header h3 {
    margin: 0;
    color: black;
    font-size: 1.5rem;
    font-weight: 500;
}

.section-body {
    padding: 25px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #2d3748;
    font-size: 0.9rem;
}

.form-group input {
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background-color: #f7fafc;
    font-size: 0.95rem;
    color: #4a5568;
    transition: border-color 0.2s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
}

/* Box style for displaying information */
.box {
    background: rgba(225, 225, 225, 0.33);
    padding: 6px 10px;
    border-radius: 6px;
    color: black;
}

.box p {
    margin: 0;
    padding: 4px 0;
    font-size: 0.95rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        margin-top: 80px;
        padding: 15px;
    }

    .main-content.open {
        margin-left: 0;
    }

    .user-info-section {
        width: 98%;
        margin: 15px auto;
    }

    .section-header h3 {
        font-size: 1.3rem;
    }

    .section-body {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 10px;
    }

    .user-info-section {
        width: 100%;
        margin: 10px auto;
    }

    .section-body {
        padding: 15px;
    }

    .form-group label,
    .box p {
        font-size: 0.85rem;
    }

    .section-header {
        padding: 12px 15px;
    }

    .section-header h3 {
        font-size: 1.1rem;
    }

}

@media (min-width: 1024px) {

    .main-content {
        margin-left: 260px;
    }
}
</style>
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- User Info Section -->
        <div class="user-info-section">
            <div class="section-header">
                <h3>Universitet Məlumatları</h3>
            </div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="form-group">
                        <label for="student_id">Kurs</label>
                        <div class="box">
                            <p></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="student_id">Status</label>
                        <div class="box">
                            <p></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="student_id">Təhsil Növü</label>
                        <div class="box">
                            <p></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Hide preloader after page load
        window.addEventListener('load', function() {
            document.querySelector('.preloader').style.display = 'none';
        });
    </script>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>

</body>
</html>