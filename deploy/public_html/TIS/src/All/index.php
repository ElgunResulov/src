<?php
include('navbar_sidebar.php');
require_once __DIR__ . '/statistika/dashboard_stats_helper.php';
$dashboardStats = dashboard_load_stats($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Statistika</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        
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


        /* Base Styles */
        .main-content {
            margin-left: 0;
            padding: 20px;
            flex: 1;
            margin-top: 80px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: margin-left 0.3s ease;
            background-color: #f5f5f5;
        }

        /* Material Design Variables */
        :root {
            --primary-color: #1d6a9d;
            --primary-light: #2479b1;
            --primary-dark: #0d5a8d;
            --accent-color: #ff4081;
            --text-primary: #212121;
            --text-secondary: #757575;
            --divider-color: #BDBDBD;
            --background: #f5f5f5;
            --surface: #ffffff;
            --error: #B00020;
            --success: #4CAF50;
            --warning: #FFC107;
            --info: #03A9F4;
        }

        /* Card Styles */
        .card {
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            margin-left: 5px;
            margin-bottom: 0px;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 0.98rem;
        }

        .card-header {
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            margin-bottom: 0;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Stat Cards */
        .stat-card {
            position: relative;
            overflow: hidden;
            color: white;
            height: 100%;
            border-radius: 10px;
        }

        .stat-card .icon-box {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.2);
            transition: transform 0.3s;
        }

        .stat-card:hover .icon-box {
            transform: scale(1.1);
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card .stat-title {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card-clickable {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card-clickable:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card .stat-trend {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            margin-top: 10px;
        }

        .stat-card .stat-trend i {
            margin-right: 5px;
        }

        .stat-trend-up {
            color: #a7f3d0;
        }

        .stat-trend-down {
            color: #fecaca;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }

        /* Filter Panel */
        .filter-panel {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 18px;
            transition: all 0.3s;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* Form Controls */
        .form-control {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 10px 14px;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(29, 106, 157, 0.15);
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        /* Progress Bar */
        .progress {
            height: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
            background-color: rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 4px;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-secondary);
            padding: 12px 20px;
            font-weight: 500;
            position: relative;
            transition: all 0.3s;
            border-radius: 0;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(29, 106, 157, 0.05);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            font-weight: 600;
        }

        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
            border-top-left-radius: 3px;
            border-top-right-radius: 3px;
        }

        .lds-ripple {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-ripple div {
            position: absolute;
            border: 4px solid var(--primary-color);
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
                top: 0px;
                left: 0px;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1.25rem;
            }
            
            .chart-container {
                height: 200px;
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
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="generateReportBtn">
                            <i class="fas fa-file-pdf mr-1"></i> Hesabat Yarat
                        </button>
                        <button type="button" class="btn btn-outline-primary ml-2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#" id="exportData">
                                <i class="fas fa-file-export mr-2"></i> İxrac
                            </a>
                            <a class="dropdown-item" href="#" id="printStats">
                                <i class="fas fa-print mr-2"></i> Çap et
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterPeriod">Dövr</label>
                        <select class="form-control" id="filterPeriod">
                            <option value="current">Cari Tədris İli (2023-2024)</option>
                            <option value="previous">Əvvəlki Tədris İli (2022-2023)</option>
                            <option value="semester1">1-ci Yarımil</option>
                            <option value="semester2">2-ci Yarımil</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterClass">Sinif</label>
                        <select class="form-control" id="filterClass">
                            <option value="">Bütün Siniflər</option>
                            <option value="9">9-cu Siniflər</option>
                            <option value="10">10-cu Siniflər</option>
                            <option value="11">11-ci Siniflər</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterSubject">Fənn</label>
                        <select class="form-control" id="filterSubject">
                            <option value="">Bütün Fənnlər</option>
                            <option value="math">Riyaziyyat</option>
                            <option value="physics">Fizika</option>
                            <option value="chemistry">Kimya</option>
                            <option value="biology">Biologiya</option>
                            <option value="history">Tarix</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label for="filterTeacher">Müəllim</label>
                        <select class="form-control" id="filterTeacher">
                            <option value="">Bütün Müəllimlər</option>
                            <option value="1">Əliyev Rəşad</option>
                            <option value="2">Məmmədova Aygün</option>
                            <option value="3">Hüseynov Elçin</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-right">
                    <button type="button" class="btn btn-primary" id="applyFilters">
                        <i class="fas fa-filter mr-1"></i> Tətbiq et
                    </button>
                    <button type="button" class="btn btn-outline-secondary ml-2" id="resetFilters">
                        <i class="fas fa-redo-alt mr-1"></i> Sıfırla
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-primary text-white h-100" data-stat-type="students" role="button" tabindex="0" aria-label="Tələbələri göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-user-graduate fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Ümumi Tələbələr</h6>
                        <h3 class="stat-number"><?= (int) $dashboardStats['total_students'] ?></h3>
                        <div class="stat-trend <?= $dashboardStats['student_trend_pct'] >= 0 ? 'stat-trend-up' : 'stat-trend-down' ?>">
                            <i class="fas fa-arrow-<?= $dashboardStats['student_trend_pct'] >= 0 ? 'up' : 'down' ?>"></i>
                            Keçən ildən: <?= ($dashboardStats['student_trend_pct'] >= 0 ? '+' : '') . $dashboardStats['student_trend_pct'] ?>%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-success text-white h-100" data-stat-type="grades" role="button" tabindex="0" aria-label="Orta balları göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-chart-bar fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Orta Bal</h6>
                        <h3 class="stat-number"><?= htmlspecialchars((string) $dashboardStats['avg_grade'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="stat-trend <?= $dashboardStats['grade_trend'] >= 0 ? 'stat-trend-up' : 'stat-trend-down' ?>">
                            <i class="fas fa-arrow-<?= $dashboardStats['grade_trend'] >= 0 ? 'up' : 'down' ?>"></i>
                            Keçən ildən: <?= ($dashboardStats['grade_trend'] >= 0 ? '+' : '') . $dashboardStats['grade_trend'] ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-info text-white h-100" data-stat-type="attendance" role="button" tabindex="0" aria-label="Davamiyyəti göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-user-check fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Davamiyyət</h6>
                        <h3 class="stat-number"><?= htmlspecialchars((string) $dashboardStats['attendance_pct'], ENT_QUOTES, 'UTF-8') ?>%</h3>
                        <div class="stat-trend <?= $dashboardStats['attendance_trend'] >= 0 ? 'stat-trend-up' : 'stat-trend-down' ?>">
                            <i class="fas fa-arrow-<?= $dashboardStats['attendance_trend'] >= 0 ? 'up' : 'down' ?>"></i>
                            Keçən ildən: <?= ($dashboardStats['attendance_trend'] >= 0 ? '+' : '') . $dashboardStats['attendance_trend'] ?>%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-warning text-white h-100" data-stat-type="olympiad" role="button" tabindex="0" aria-label="Olimpiada məlumatlarını göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-trophy fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Olimpiada Uğurları</h6>
                        <h3 class="stat-number"><?= (int) $dashboardStats['olympiad_count'] ?></h3>
                        <div class="stat-trend stat-trend-up">
                            <i class="fas fa-arrow-up"></i> Keçən ildən: +8
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Tabs -->
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="statsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab">
                            <i class="fas fa-chart-pie mr-2"></i> Ümumi Baxış
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="academic-tab" data-toggle="tab" href="#academic" role="tab">
                            <i class="fas fa-graduation-cap mr-2"></i> Akademik Performans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                            <i class="fas fa-calendar-check mr-2"></i> Davamiyyət
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="statsTabsContent">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Akademik Performans Trendi</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="academicTrendChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Qiymət Paylanması</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="gradeDistributionChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Sinif Müqayisəsi</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Sinif</th>
                                                        <th>Tələbə Sayı</th>
                                                        <th>Orta Bal</th>
                                                        <th>Davamiyyət</th>
                                                        <th>Ən Yüksək Bal</th>
                                                        <th>Trend</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>9A</td>
                                                        <td>28</td>
                                                        <td>76.4</td>
                                                        <td>92.8%</td>
                                                        <td>98</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>9B</td>
                                                        <td>26</td>
                                                        <td>74.2</td>
                                                        <td>91.5%</td>
                                                        <td>96</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>10A</td>
                                                        <td>30</td>
                                                        <td>79.8</td>
                                                        <td>95.2%</td>
                                                        <td>99</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>10B</td>
                                                        <td>29</td>
                                                        <td>77.5</td>
                                                        <td>93.7%</td>
                                                        <td>97</td>
                                                        <td><i class="fas fa-equals text-warning"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>11A</td>
                                                        <td>25</td>
                                                        <td>82.3</td>
                                                        <td>96.4%</td>
                                                        <td>100</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>11B</td>
                                                        <td>24</td>
                                                        <td>80.1</td>
                                                        <td>94.8%</td>
                                                        <td>98</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Performance Tab -->
                    <div class="tab-pane fade" id="academic" role="tabpanel">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Fənnlər üzrə Performans</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="subjectPerformanceChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Ən Yaxşı Nəticə Göstərən Tələbələr</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Sıra</th>
                                                        <th>Tələbə</th>
                                                        <th>Sinif</th>
                                                        <th>Orta Bal</th>
                                                        <th>Ən Yüksək Bal</th>
                                                        <th>İmtahan Sayı</th>
                                                        <th>Trend</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Aysel Hüseynova</div>
                                                            </div>
                                                        </td>
                                                        <td>11A</td>
                                                        <td>92.5</td>
                                                        <td>98</td>
                                                        <td>12</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>2</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Səbinə Qasımova</div>
                                                            </div>
                                                        </td>
                                                        <td>11A</td>
                                                        <td>90.2</td>
                                                        <td>96</td>
                                                        <td>12</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>3</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Əli Məmmədli</div>
                                                            </div>
                                                        </td>
                                                        <td>10A</td>
                                                        <td>88.7</td>
                                                        <td>94</td>
                                                        <td>12</td>
                                                        <td><i class="fas fa-equals text-warning"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>4</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Orxan Əliyev</div>
                                                            </div>
                                                        </td>
                                                        <td>10B</td>
                                                        <td>85.3</td>
                                                        <td>92</td>
                                                        <td>12</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>5</td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Tural Nəsirov</div>
                                                            </div>
                                                        </td>
                                                        <td>11B</td>
                                                        <td>84.9</td>
                                                        <td>91</td>
                                                        <td>12</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Tab -->
                    <div class="tab-pane fade" id="attendance" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Aylıq Davamiyyət Trendi</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="monthlyAttendanceChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Davamiyyət Səbəbləri</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="absenceReasonsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Davamiyyət Problemi olan Tələbələr</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Tələbə</th>
                                                        <th>Sinif</th>
                                                        <th>Davamiyyət %</th>
                                                        <th>Buraxılmış Dərslər</th>
                                                        <th>Üzrlü</th>
                                                        <th>Üzrsüz</th>
                                                        <th>Trend</th>
                                                        <th>Əməliyyatlar</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Rəşad Məmmədov</div>
                                                            </div>
                                                        </td>
                                                        <td>9B</td>
                                                        <td>78.5%</td>
                                                        <td>42</td>
                                                        <td>15</td>
                                                        <td>27</td>
                                                        <td><i class="fas fa-arrow-down text-danger"></i></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary">Ətraflı</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Leyla Əlizadə</div>
                                                            </div>
                                                        </td>
                                                        <td>10A</td>
                                                        <td>82.3%</td>
                                                        <td>35</td>
                                                        <td>22</td>
                                                        <td>13</td>
                                                        <td><i class="fas fa-arrow-up text-success"></i></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary">Ətraflı</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://via.placeholder.com/36" class="rounded-circle mr-2" width="36" height="36" alt="Student">
                                                                <div>Elşən Quliyev</div>
                                                            </div>
                                                        </td>
                                                        <td>11B</td>
                                                        <td>80.7%</td>
                                                        <td>38</td>
                                                        <td>18</td>
                                                        <td>20</td>
                                                        <td><i class="fas fa-equals text-warning"></i></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary">Ətraflı</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for functionality -->
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Stat Details Modal -->
    <div class="modal fade" id="statDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statDetailsTitle">Məlumatlar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
                </div>
                <div class="modal-body">
                    <div id="statDetailsLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div class="table-responsive d-none" id="statDetailsContent">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light" id="statDetailsHead"></thead>
                            <tbody id="statDetailsBody"></tbody>
                        </table>
                    </div>
                    <div id="statDetailsEmpty" class="text-center py-4 text-muted d-none">Məlumat tapılmadı</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const statTitles = {
            students: 'Ümumi Tələbələr',
            grades: 'Orta Ballar',
            attendance: 'Davamiyyət',
            olympiad: 'Olimpiada Uğurları'
        };

        $(document).ready(function() {
            $(".preloader").fadeOut();

            $('.stat-card-clickable').on('click', function () {
                openStatDetailsModal($(this).data('stat-type'));
            });

            $('.stat-card-clickable').on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openStatDetailsModal($(this).data('stat-type'));
                }
            });
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Apply filters
            $('#applyFilters').on('click', function() {
                alert('Filtrlər tətbiq edildi!');
                // Here you would normally update the charts and tables based on the selected filters
                updateCharts();
            });
            
            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#filterPeriod').val('current');
                $('#filterClass').val('');
                $('#filterSubject').val('');
                $('#filterTeacher').val('');
                
                alert('Filtrlər sıfırlandı!');
                // Here you would normally reset the charts and tables to their default state
                updateCharts();
            });
            
            // Generate report
            $('#generateReportBtn').on('click', function() {
                alert('Hesabat yaradılır...');
                // Here you would normally trigger the report generation function
            });
            
            // Export data
            $('#exportData').on('click', function() {
                alert('Məlumatlar ixrac edilir...');
                // Here you would normally trigger the export function
            });
            
            // Print statistics
            $('#printStats').on('click', function() {
                alert('Statistika çap edilir...');
                // Here you would normally trigger the print function
            });
            
            // Initialize Charts
            function updateCharts() {
                // Academic Trend Chart
                if (document.getElementById('academicTrendChart')) {
                    var academicTrendCtx = document.getElementById('academicTrendChart').getContext('2d');
                    var academicTrendChart = new Chart(academicTrendCtx, {
                        type: 'line',
                        data: {
                            labels: ['Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May'],
                            datasets: [
                                {
                                    label: '2023-2024',
                                    data: [72, 74, 76, 78, 80, 79, 81, 83, 85],
                                    backgroundColor: 'rgba(29, 106, 157, 0.1)',
                                    borderColor: 'rgba(29, 106, 157, 1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: '2022-2023',
                                    data: [70, 71, 73, 75, 76, 77, 78, 80, 82],
                                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                    borderColor: 'rgba(76, 175, 80, 1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    min: 60,
                                    max: 100
                                }
                            }
                        }
                    });
                }
                
                // Grade Distribution Chart
                if (document.getElementById('gradeDistributionChart')) {
                    var gradeDistCtx = document.getElementById('gradeDistributionChart').getContext('2d');
                    var gradeDistChart = new Chart(gradeDistCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Əla (5)', 'Yaxşı (4)', 'Kafi (3)', 'Qeyri-kafi (2)'],
                            datasets: [{
                                data: [35, 45, 15, 5],
                                backgroundColor: [
                                    'rgba(76, 175, 80, 0.7)',
                                    'rgba(3, 169, 244, 0.7)',
                                    'rgba(255, 193, 7, 0.7)',
                                    'rgba(176, 0, 32, 0.7)'
                                ],
                                borderColor: [
                                    'rgba(76, 175, 80, 1)',
                                    'rgba(3, 169, 244, 1)',
                                    'rgba(255, 193, 7, 1)',
                                    'rgba(176, 0, 32, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
                
                // Subject Performance Chart
                if (document.getElementById('subjectPerformanceChart')) {
                    var subjectPerfCtx = document.getElementById('subjectPerformanceChart').getContext('2d');
                    var subjectPerfChart = new Chart(subjectPerfCtx, {
                        type: 'radar',
                        data: {
                            labels: ['Riyaziyyat', 'Fizika', 'Kimya', 'Biologiya', 'Tarix', 'Ədəbiyyat'],
                            datasets: [
                                {
                                    label: '9-cu Siniflər',
                                    data: [75, 72, 78, 80, 74, 82],
                                    backgroundColor: 'rgba(29, 106, 157, 0.2)',
                                    borderColor: 'rgba(29, 106, 157, 1)',
                                    borderWidth: 2,
                                    pointBackgroundColor: 'rgba(29, 106, 157, 1)'
                                },
                                {
                                    label: '10-cu Siniflər',
                                    data: [78, 76, 75, 82, 77, 84],
                                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                                    borderColor: 'rgba(76, 175, 80, 1)',
                                    borderWidth: 2,
                                    pointBackgroundColor: 'rgba(76, 175, 80, 1)'
                                },
                                {
                                    label: '11-ci Siniflər',
                                    data: [82, 80, 83, 85, 79, 88],
                                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                                    borderColor: 'rgba(255, 193, 7, 1)',
                                    borderWidth: 2,
                                    pointBackgroundColor: 'rgba(255, 193, 7, 1)'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                r: {
                                    beginAtZero: false,
                                    min: 60,
                                    max: 100
                                }
                            }
                        }
                    });
                }
                
                // Monthly Attendance Chart
                if (document.getElementById('monthlyAttendanceChart')) {
                    var monthlyAttendanceCtx = document.getElementById('monthlyAttendanceChart').getContext('2d');
                    var monthlyAttendanceChart = new Chart(monthlyAttendanceCtx, {
                        type: 'line',
                        data: {
                            labels: ['Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May'],
                            datasets: [
                                {
                                    label: '9-cu Siniflər',
                                    data: [95, 94, 92, 91, 93, 94, 95, 96, 97],
                                    borderColor: 'rgba(29, 106, 157, 1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false
                                },
                                {
                                    label: '10-cu Siniflər',
                                    data: [96, 95, 93, 92, 94, 95, 96, 97, 98],
                                    borderColor: 'rgba(76, 175, 80, 1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false
                                },
                                {
                                    label: '11-ci Siniflər',
                                    data: [97, 96, 94, 93, 95, 96, 97, 98, 99],
                                    borderColor: 'rgba(255, 193, 7, 1)',
                                    borderWidth: 2,
                                    tension: 0.3,
                                    fill: false
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: false,
                                    min: 80,
                                    max: 100
                                }
                            }
                        }
                    });
                }
                
                // Absence Reasons Chart
                if (document.getElementById('absenceReasonsChart')) {
                    var absenceReasonsCtx = document.getElementById('absenceReasonsChart').getContext('2d');
                    var absenceReasonsChart = new Chart(absenceReasonsCtx, {
                        type: 'pie',
                        data: {
                            labels: ['Xəstəlik', 'Ailə səbəbləri', 'Nəqliyyat', 'Digər üzrlü', 'Üzrsüz'],
                            datasets: [{
                                data: [60, 15, 8, 7, 10],
                                backgroundColor: [
                                    'rgba(76, 175, 80, 0.7)',
                                    'rgba(3, 169, 244, 0.7)',
                                    'rgba(255, 193, 7, 0.7)',
                                    'rgba(156, 39, 176, 0.7)',
                                    'rgba(233, 30, 99, 0.7)'
                                ],
                                borderColor: [
                                    'rgba(76, 175, 80, 1)',
                                    'rgba(3, 169, 244, 1)',
                                    'rgba(255, 193, 7, 1)',
                                    'rgba(156, 39, 176, 1)',
                                    'rgba(233, 30, 99, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            }
            
            // Initialize all charts on page load
            updateCharts();
        });

        function openStatDetailsModal(type) {
            $('#statDetailsTitle').text(statTitles[type] || 'Məlumatlar');
            $('#statDetailsLoading').removeClass('d-none');
            $('#statDetailsContent').addClass('d-none');
            $('#statDetailsEmpty').addClass('d-none');
            $('#statDetailsHead').empty();
            $('#statDetailsBody').empty();

            const modalEl = document.getElementById('statDetailsModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            $.ajax({
                url: 'statistika/stat_operations.php?type=' + encodeURIComponent(type),
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    $('#statDetailsLoading').addClass('d-none');
                    if (response.status === 'success' && response.data && response.data.length > 0) {
                        renderStatDetailsTable(response.columns, response.data);
                        $('#statDetailsContent').removeClass('d-none');
                    } else {
                        $('#statDetailsEmpty').removeClass('d-none');
                    }
                },
                error: function () {
                    $('#statDetailsLoading').addClass('d-none');
                    $('#statDetailsEmpty').removeClass('d-none');
                }
            });
        }

        function renderStatDetailsTable(columns, rows) {
            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');

            let headHtml = '<tr>';
            columns.forEach(function (column) {
                headHtml += '<th>' + escapeHtml(column.label) + '</th>';
            });
            headHtml += '</tr>';
            $('#statDetailsHead').html(headHtml);

            let bodyHtml = '';
            rows.forEach(function (row) {
                bodyHtml += '<tr>';
                columns.forEach(function (column) {
                    let value = row[column.key] ?? '-';
                    if (column.key === 'status_label') {
                        const badgeClass = value === 'Aktiv' ? 'badge-success' : 'badge-danger';
                        value = '<span class="badge ' + badgeClass + '">' + escapeHtml(value) + '</span>';
                    } else {
                        value = escapeHtml(value);
                    }
                    bodyHtml += '<td>' + value + '</td>';
                });
                bodyHtml += '</tr>';
            });
            $('#statDetailsBody').html(bodyHtml);
        }
    </script>
</body>
</html>