<?php
require_once __DIR__ . '/auth.php';
app_start_secure_session();
app_csrf_token();

// Authentication check
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include('navbar_sidebar.php');
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Əməkdaşlar</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet">
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
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

        .main-content {
            margin-left: 0;
            padding: 20px;
            flex: 1;
            margin-top: 86px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: margin-left 0.3s ease;
            background-color: var(--background);
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 1.25rem;
        }

        .card-header {
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card {
            color: white;
            height: 100%;
            border-radius: 10px;
            position: relative;
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

        .btn {
            border-radius: 6px;
            font-weight: 500;
            display: inline-block;
            padding: 8px 15px;
            margin-right: 4px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .btn:hover {
            transform: scale(1.01);
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

        .filter-panel {
            background-color: var(--surface);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .employee-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
        }

        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .employee-card .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .employee-card .badge {
            position: absolute;
            top: 1%;
            right: 4.5px;
            border-radius:6px;
            padding: 8px 8px;
            font-size: 0.89rem;
            font-weight: 500;
        }

        .employee-contact {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-top: 10px;
        }

        .employee-contact-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .employee-contact-item i {
            width: 20px;
            margin-right: 8px;
            color: var(--primary-color);
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-secondary);
            padding: 12px 20px;
            font-weight: 500;
            position: relative;
            transition: all 0.3s;
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
        }

        .badge-teaching { background-color: var(--primary-color); }
        .badge-admin { background-color: var(--success); }
        .badge-it { background-color: var(--info); }
        .badge-finance { background-color: var(--warning); }


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
                top: 0;
                left: 0;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

        .alert-dismissible {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
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
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary new-emekdas" data-toggle="modal" data-target="#addEmployeeModal" aria-label="Yeni Əməkdaş Əlavə Et">
                            <i class="fas fa-user-plus mr-1"></i> Yeni Əməkdaş
                        </button>
                        <button type="button" class="btn btn-outline-primary ml-2 dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Daha çox seçim">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#importEmployeeModal" aria-label="Əməkdaşları İdxal Et">
                                <i class="fas fa-file-import mr-2"></i> İdxal
                            </a>
                            <a class="dropdown-item" href="#" id="exportEmployee" aria-label="Əməkdaşları İxrac Et">
                                <i class="fas fa-file-export mr-2"></i> İxrac
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="printEmployee" aria-label="Siyahını Çap Et">
                                <i class="fas fa-print mr-2"></i> Çap et
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-primary text-white h-100" id="totalEmployees">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Ümumi Əməkdaşlar</h6>
                        <h3 class="stat-number">0</h3>
                        <p class="mb-0 small">Aktiv: 0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-success text-white h-100" id="teachingEmployees">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-chalkboard-teacher fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Müəllimlər</h6>
                        <h3 class="stat-number">0</h3>
                        <p class="mb-0 small">Tam ştat: 0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-info text-white h-100" id="adminEmployees">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-user-tie fa-lg"></i>
                        </div>
                        <h6 class="stat-title">İnzibati İşçilər</h6>
                        <h3 class="stat-number">0</h3>
                        <p class="mb-0 small">Rəhbərlik: 0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card bg-warning text-white h-100" id="technicalEmployees">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-user-cog fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Texniki İşçilər</h6>
                        <h3 class="stat-number">0</h3>
                        <p class="mb-0 small">IT: 0</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-panel">
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterDepartment">Şöbə</label>
                        <select class="form-control" id="filterDepartment" aria-label="Şöbə seçimi">
                            <option value="">Bütün Şöbələr</option>
                            <option value="teaching">Tədris</option>
                            <option value="admin">İnzibati</option>
                            <option value="it">IT</option>
                            <option value="finance">Maliyyə</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterPosition">Vəzifə</label>
                        <select class="form-control" id="filterPosition" aria-label="Vəzifə seçimi">
                            <option value="">Bütün Vəzifələr</option>
                            <option value="teacher">Müəllim</option>
                            <option value="manager">Menecer</option>
                            <option value="director">Direktor</option>
                            <option value="specialist">Mütəxəssis</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="form-group mb-0">
                        <label for="filterStatus">Status</label>
                        <select class="form-control" id="filterStatus" aria-label="Status seçimi">
                            <option value="">Bütün Statuslar</option>
                            <option value="active">Aktiv</option>
                            <option value="inactive">Qeyri-aktiv</option>
                            <option value="vacation">Məzuniyyətdə</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label for="searchEmployee">Axtarış</label>
                        <input type="text" class="form-control" id="searchEmployee" placeholder="Ad, soyad və ya vəzifə..." aria-label="Əməkdaş axtarışı">
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-right">
                    <button type="button" class="btn btn-outline-secondary ml-2" id="resetFilters" aria-label="Filtrləri sıfırla">
                        <i class="fas fa-redo-alt mr-1"></i> Sıfırla
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="grid-tab" data-toggle="tab" href="#grid" role="tab" aria-controls="grid" aria-selected="true">
                            <i class="fas fa-th-large mr-2"></i> Şəbəkə Görünüşü 
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" id="list-tab" data-toggle="tab" href="#list" role="tab" aria-controls="list" aria-selected="false">
                            <i class="fas fa-list mr-2"></i> Siyahı Görünüşü
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="stats-tab" data-toggle="tab" href="#stats" role="tab" aria-controls="stats" aria-selected="false">
                            <i class="fas fa-chart-bar mr-2"></i> Statistika
                        </a>
                    </li> -->
                </ul>

                <div class="tab-content mt-4" id="employeeTabsContent">
                    <div class="tab-pane fade show active" id="grid" role="tabpanel" aria-labelledby="grid-tab">
                        <div class="row"></div>
                    </div>
                    <div class="tab-pane fade" id="list" role="tabpanel" aria-labelledby="list-tab">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ad Soyad</th>
                                        <th>Vəzifə</th>
                                        <th>Şöbə</th>
                                        <th>Email</th>
                                        <th>Telefon</th>
                                        <th>İşə başlama</th>
                                        <th>Status</th>
                                        <th>Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Şöbələr üzrə Əməkdaşlar</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="departmentEmployeeChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">İş Təcrübəsi</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="experienceChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Əməkdaş Dinamikası</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="employeeDynamicsChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">Yeni Əməkdaş Əlavə Et</h5>
                </div>
                <div class="modal-body">
                    <form action="emekdaslar/add_employee.php" method="POST" enctype="multipart/form-data" id="addEmployeeForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="firstName">Ad</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required aria-required="true">
                                    <div class="invalid-feedback">Ad daxil edin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lastName">Soyad</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required aria-required="true">
                                    <div class="invalid-feedback">Soyad daxil edin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department">Şöbə</label>
                                    <select class="form-control" id="department" name="department" required aria-required="true">
                                        <option value="">Seçin</option>
                                        <option value="teaching">Tədris</option>
                                        <option value="admin">İnzibati</option>
                                        <option value="it">IT</option>
                                        <option value="finance">Maliyyə</option>
                                    </select>
                                    <div class="invalid-feedback">Şöbə seçin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="position">Vəzifə</label>
                                    <input type="text" class="form-control" id="position" name="position" required aria-required="true">
                                    <div class="invalid-feedback">Vəzifə daxil edin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required aria-required="true">
                                    <div class="invalid-feedback">Düzgün email daxil edin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Telefon</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" pattern="\+994\s[0-9]{2}\s[0-9]{3}\s[0-9]{2}\s[0-9]{2}" placeholder="+994 50 123 45 67" required aria-required="true">
                                    <div class="invalid-feedback">Düzgün telefon nömrəsi daxil edin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="startDate">İşə başlama tarixi</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" required aria-required="true">
                                    <div class="invalid-feedback">Tarix seçin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status" required aria-required="true">
                                        <option value="active">Aktiv</option>
                                        <option value="inactive">Qeyri-aktiv</option>
                                        <option value="vacation">Məzuniyyətdə</option>
                                    </select>
                                    <div class="invalid-feedback">Status seçin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="address">Ünvan</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="education">Təhsil</label>
                                    <textarea class="form-control" id="education" name="education" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="experience">İş Təcrübəsi</label>
                                    <textarea class="form-control" id="experience" name="experience" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="employeeImage">Şəkil</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="employeeImage" name="employeeImage" accept="image/*" aria-describedby="employeeImageHelp">
                                <label class="custom-file-label" for="employeeImage">Şəkil seçin</label>
                                <small id="employeeImageHelp" class="form-text text-muted">Maksimum 2MB, JPG/PNG formatında.</small>
                            </div>
                        </div>
                        <br>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal" aria-label="Bağla">Bağla</button>
                            <button type="submit" class="btn btn-primary" aria-label="Yadda saxla">Yadda saxla</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="viewEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewEmployeeModalLabel">Əməkdaş Məlumatları</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img src="https://via.placeholder.com/200" class="img-fluid mb-3" alt="Əməkdaş Şəkli" id="viewEmployeeImage">
                            <h4 id="viewEmployeeName"></h4>
                            <p class="text-muted" id="viewEmployeePosition"></p>
                            <span class="badge px-3 py-2" id="viewEmployeeDepartment"></span>
                            <br><br>
                        </div>
                        <div class="col-md-8">
                            <h5 class="border-bottom pb-2">Əlaqə Məlumatları</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-envelope mr-2"></i> Email:</strong></p>
                                    <p id="viewEmployeeEmail"></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-phone mr-2"></i> Telefon:</strong></p>
                                    <p id="viewEmployeePhone"></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-map-marker-alt mr-2"></i> Ünvan:</strong></p>
                                    <p id="viewEmployeeAddress"></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-calendar-alt mr-2"></i> İşə başlama:</strong></p>
                                    <p id="viewEmployeeStartDate"></p>
                                </div>
                            </div>
                            <h5 class="border-bottom pb-2 mt-4">Əlavə Məlumatlar</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Təhsil:</strong></p>
                                    <p id="viewEmployeeEducation"></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>İş Təcrübəsi:</strong></p>
                                    <p id="viewEmployeeExperience"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal" aria-label="Bağla">Bağla</button>
                    <button type="button" class="btn btn-primary edit-from-view" aria-label="Redaktə et">Redaktə et</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">Əməkdaş Redaktə Et</h5>
                </div>
                <div class="modal-body">
                    <form id="editEmployeeForm">
                        <input type="hidden" id="editEmployeeId">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editFirstName">Ad</label>
                                    <input type="text" class="form-control" id="editFirstName" required aria-required="true">
                                    <div class="invalid-feedback">Ad daxil edin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editLastName">Soyad</label>
                                    <input type="text" class="form-control" id="editLastName" required aria-required="true">
                                    <div class="invalid-feedback">Soyad daxil edin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editDepartment">Şöbə</label>
                                    <select class="form-control" id="editDepartment" required aria-required="true">
                                        <option value="">Seçin</option>
                                        <option value="teaching">Tədris</option>
                                        <option value="admin">İnzibati</option>
                                        <option value="it">IT</option>
                                        <option value="finance">Maliyyə</option>
                                    </select>
                                    <div class="invalid-feedback">Şöbə seçin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editPosition">Vəzifə</label>
                                    <input type="text" class="form-control" id="editPosition" required aria-required="true">
                                    <div class="invalid-feedback">Vəzifə daxil edin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editEmail">Email</label>
                                    <input type="email" class="form-control" id="editEmail" required aria-required="true">
                                    <div class="invalid-feedback">Düzgün email daxil edin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editPhone">Telefon</label>
                                    <input type="tel" class="form-control" id="editPhone" pattern="\+994\s[0-9]{2}\s[0-9]{3}\s[0-9]{2}\s[0-9]{2}" required aria-required="true">
                                    <div class="invalid-feedback">Düzgün telefon nömrəsi daxil edin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editStartDate">İşə başlama tarixi</label>
                                    <input type="date" class="form-control" id="editStartDate" required aria-required="true">
                                    <div class="invalid-feedback">Tarix seçin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editStatus">Status</label>
                                    <select class="form-control" id="editStatus" required aria-required="true">
                                        <option value="active">Aktiv</option>
                                        <option value="inactive">Qeyri-aktiv</option>
                                        <option value="vacation">Məzuniyyətdə</option>
                                    </select>
                                    <div class="invalid-feedback">Status seçin.</div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editEducation">Təhsil</label>
                                    <textarea class="form-control" id="editEducation" name="education" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editExperience">İş Təcrübəsi</label>
                                    <textarea class="form-control" id="editExperience" name="experience" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="form-group">
                            <label for="editAddress">Ünvan</label>
                            <textarea class="form-control" id="editAddress" rows="2"></textarea>
                        </div>
                        <br>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal" aria-label="Bağla">Bağla</button>
                    <button type="button" class="btn btn-primary" id="updateEmployee" aria-label="Yadda saxla">Yadda saxla</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="deleteEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEmployeeModalLabel">Əməkdaşı Sil</h5>
                </div>
                <div class="modal-body">
                    <p>Bu əməkdaşı silmək istədiyinizə əminsiniz?</p>
                    <input type="hidden" id="deleteEmployeeId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal" aria-label="Xeyr">Xeyr</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteEmployee" aria-label="Bəli, Sil">Bəli, Sil</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="importEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importEmployeeModalLabel">Əməkdaşları İdxal Et</h5>
                </div>
                <div class="modal-body">
                    <form id="importEmployeeForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="form-group">
                            <label for="importFile">CSV Faylı Seçin</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="importFile" accept=".csv" required aria-required="true">
                                <label class="custom-file-label" for="importFile">Fayl seçin</label>
                                <div class="invalid-feedback">Fayl seçin.</div>
                            </div>
                            <small class="form-text text-muted">Yalnız CSV formatı dəstəklənir.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal" aria-label="Bağla">Bağla</button>
                    <button type="button" class="btn btn-primary" id="confirmImport" aria-label="İdxal et">İdxal et</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" integrity="sha256-+8RzD0Ur3R1UHL9H6A/2vnl86k3sE6UoV47pJ+BsfsQ=" crossorigin="anonymous"></script>
    <script>

        
    $(document).on('click', '.close-modal', function() {
        $(".modal").modal("hide");
    });

        // Hide preloader after page load
        $(window).on('load', function() {
            $('.preloader').fadeOut('fast');
        });

        // Check for error or success query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        const success = urlParams.get('success');
        if (error) {
            const alertDiv = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Xəta:</strong> ${decodeURIComponent(error)}
       
                </div>
            `;
            $('body').append(alertDiv);
            $('#addEmployeeModal').modal('show');
            // Clear the error from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (success) {
            const alertDiv = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Uğur:</strong> ${decodeURIComponent(success)}
          
                </div>
            `;
            $('body').append(alertDiv);
            // Clear the success message from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Custom file input handling
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
        });

        // Fetch employee data
        function fetchEmployees(filters = {}) {
            $.ajax({
                url: 'emekdaslar/get_employees.php',
                method: 'GET',
                data: filters,
                dataType: 'json',
                beforeSend: function() {
                    $('.preloader').show();
                },
                success: function(data) {
                    $('.preloader').hide();
                    if (data.error) {
                        console.error('Error from API:', data.error);
                        alert('Məlumatlar yüklənərkən xəta baş verdi: ' + data.error);
                        return;
                    }
                    renderCards(data);
                    renderList(data);
                    renderStats(data);
                    updateStatCards(data);
                },
                error: function(xhr, status, error) {
                    $('.preloader').hide();
                    console.error('Error fetching employees:', error);
                    alert('Server xətası: Məlumatlar yüklənə bilmədi. Xəta: ' + error);
                }
            });
        }

        // Render employee cards (grid view)
        function renderCards(employees) {
            const grid = $('#grid .row');
            grid.empty();
            employees.forEach(employee => {
                const badgeClass = {
                    teaching: 'badge-teaching',
                    admin: 'badge-admin',
                    it: 'badge-it',
                    finance: 'badge-finance'
                }[employee.sobe] || 'badge-primary';
                const card = `
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card employee-card">
                            <img src="${employee.sekil ? 'emekdaslar/uploads/' + employee.sekil : 'https://via.placeholder.com/200'}" class="card-img-top" alt="${employee.ad_soyad}">
                            <span class="badge ${badgeClass}">${employee.sobe}</span>
                            <div class="card-body">
                                <h5 class="card-title">${employee.ad_soyad}</h5>
                                <p class="card-text text-muted">${employee.vezife}</p>
                                <div class="employee-contact">
                                    <div class="employee-contact-item">
                                        <i class="fas fa-envelope"></i> ${employee.email}
                                    </div>
                                    <div class="employee-contact-item">
                                        <i class="fas fa-phone"></i> ${employee.telefon}
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-primary view-employee" data-id="${employee.id}" aria-label="Bax">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning edit-employee" data-id="${employee.id}" aria-label="Redaktə et">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-employee" data-id="${employee.id}" aria-label="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                grid.append(card);
            });
        }

        // Render employee list (table view)
        function renderList(employees) {
            const tbody = $('#list tbody');
            tbody.empty();
            employees.forEach(employee => {
                const row = `
                    <tr>
                        <td>${employee.ad_soyad}</td>
                        <td>${employee.vezife}</td>
                        <td>${employee.sobe}</td>
                        <td>${employee.email}</td>
                        <td>${employee.telefon}</td>
                        <td>${employee.ise_baslama_tarixi}</td>
                        <td>
                            <span class="badge ${employee.status === 'active' ? 'badge-success' : employee.status === 'inactive' ? 'badge-danger' : 'badge-warning'}">
                                ${employee.status}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary view-employee" data-id="${employee.id}" aria-label="Bax">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning edit-employee" data-id="${employee.id}" aria-label="Redaktə et">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-employee" data-id="${employee.id}" aria-label="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        // Update stat cards
        function updateStatCards(employees) {
            const total = employees.length;
            const active = employees.filter(e => e.status === 'active').length;
            const teaching = employees.filter(e => e.sobe === 'teaching').length;
            const admin = employees.filter(e => e.sobe === 'admin').length;
            const it = employees.filter(e => e.sobe === 'it').length;

            $('#totalEmployees .stat-number').text(total);
            $('#totalEmployees .small').text(`Aktiv: ${active}`);
            $('#teachingEmployees .stat-number').text(teaching);
            $('#teachingEmployees .small').text(`Tam ştat: ${teaching}`);
            $('#adminEmployees .stat-number').text(admin);
            $('#adminEmployees .small').text(`Rəhbərlik: ${admin}`);
            $('#technicalEmployees .stat-number').text(it);
            $('#technicalEmployees .small').text(`IT: ${it}`);
        }

        // Render stats charts
        let departmentChart, experienceChart, dynamicsChart;
        function renderStats(employees) {
            // Department chart
            const departments = {};
            employees.forEach(e => {
                departments[e.sobe] = (departments[e.sobe] || 0) + 1;
            });
            if (departmentChart) departmentChart.destroy();
            departmentChart = new Chart(document.getElementById('departmentEmployeeChart'), {
                type: 'pie',
                data: {
                    labels: Object.keys(departments),
                    datasets: [{
                        data: Object.values(departments),
                        backgroundColor: ['#1d6a9d', '#4CAF50', '#03A9F4', '#FFC107']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Experience chart (placeholder)
            if (experienceChart) experienceChart.destroy();
            experienceChart = new Chart(document.getElementById('experienceChart'), {
                type: 'bar',
                data: {
                    labels: ['0-2 il', '2-5 il', '5+ il'],
                    datasets: [{
                        label: 'Əməkdaşlar',
                        data: [employees.length / 3, employees.length / 3, employees.length / 3],
                        backgroundColor: '#1d6a9d'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Dynamics chart (placeholder)
            if (dynamicsChart) dynamicsChart.destroy();
            dynamicsChart = new Chart(document.getElementById('employeeDynamicsChart'), {
                type: 'line',
                data: {
                    labels: ['Yan', 'Fev', 'Mar', 'Apr', 'May'],
                    datasets: [{
                        label: 'Əməkdaş Sayı',
                        data: [employees.length, employees.length + 1, employees.length, employees.length + 2, employees.length],
                        borderColor: '#1d6a9d',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // View employee
        $(document).on('click', '.view-employee', function() {
            const id = $(this).data('id');
            $.ajax({
                url: 'emekdaslar/get_employee.php',
                method: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        alert('Xəta: ' + data.error);
                        return;
                    }
                    const [firstName, lastName] = data.ad_soyad.split(' ');
                    $('#viewEmployeeName').text(data.ad_soyad);
                    $('#viewEmployeePosition').text(data.vezife);
                    $('#viewEmployeeDepartment').text(data.sobe).removeClass().addClass('badge px-3 py-2 badge-' + data.sobe);
                    $('#viewEmployeeEmail').text(data.email);
                    $('#viewEmployeePhone').text(data.telefon);
                    $('#viewEmployeeAddress').text(data.unvan || 'Məlumat yoxdur');
                    $('#viewEmployeeStartDate').text(data.ise_baslama_tarixi);
                    $('#viewEmployeeEducation').text(data.tehsil || 'Məlumat yoxdur');
                    $('#viewEmployeeExperience').text(data.is_tecrubesi || 'Məlumat yoxdur');
                    $('#viewEmployeeImage').attr('src', data.sekil ? 'emekdaslar/uploads/' + data.sekil : 'https://via.placeholder.com/200');
                    $('.edit-from-view').data('id', id);
                    $('#viewEmployeeModal').modal('show');
                },
                error: function(xhr, status, error) {
                    alert('Məlumatlar yüklənərkən xəta: ' + error);
                }
            });
        });

        // Edit employee
        $(document).on('click', '.edit-employee, .edit-from-view', function() {
            const id = $(this).data('id');
            $.ajax({
                url: 'emekdaslar/get_employee.php',
                method: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        alert('Xəta: ' + data.error);
                        return;
                    }
                    const [firstName, lastName] = data.ad_soyad.split(' ');
                    $('#editEmployeeId').val(data.id);
                    $('#editFirstName').val(firstName || '');
                    $('#editLastName').val(lastName || '');
                    $('#editDepartment').val(data.sobe);
                    $('#editPosition').val(data.vezife);
                    $('#editEmail').val(data.email);
                    $('#editPhone').val(data.telefon);
                    $('#editStartDate').val(data.ise_baslama_tarixi);
                    $('#editStatus').val(data.status);
                    $('#editAddress').val(data.unvan || '');
                    $('#editEducation').val(data.tehsil || '');
                    $('#editExperience').val(data.is_tecrubesi || '');
                    $('#viewEmployeeModal').modal('hide');
                    $('#editEmployeeModal').modal('show');
                },
                error: function(xhr, status, error) {
                    alert('Məlumatlar yüklənərkən xəta: ' + error);
                }
            });
        });

        // Update employee
        $('#updateEmployee').on('click', function() {
            const form = $('#editEmployeeForm');
            if (!form[0].checkValidity()) {
                form.addClass('was-validated');
                return;
            }
            const formData = {
                editEmployeeId: $('#editEmployeeId').val(),
                editFirstName: $('#editFirstName').val(),
                editLastName: $('#editLastName').val(),
                editDepartment: $('#editDepartment').val(),
                editPosition: $('#editPosition').val(),
                editEmail: $('#editEmail').val(),
                editPhone: $('#editPhone').val(),
                editStartDate: $('#editStartDate').val(),
                editStatus: $('#editStatus').val(),
                editAddress: $('#editAddress').val(),
                education: $('#editEducation').val(),
                experience: $('#editExperience').val(),
                csrf_token: form.find('[name="csrf_token"]').val()
            };
            $.ajax({
                url: 'emekdaslar/update_employee.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        alert('Xəta: ' + data.error);
                        return;
                    }
                    alert('Əməkdaş məlumatları yeniləndi!');
                    $('#editEmployeeModal').modal('hide');
                    fetchEmployees();
                },
                error: function(xhr, status, error) {
                    alert('Yeniləmə zamanı xəta: ' + error);
                }
            });
        });


        // Delete employee
        $(document).on('click', '.delete-employee', function() {
            $('#deleteEmployeeId').val($(this).data('id'));
            $('#deleteEmployeeModal').modal('show');
        });

        $('#confirmDeleteEmployee').on('click', function() {
            const id = $('#deleteEmployeeId').val();
            $.ajax({
                url: 'emekdaslar/delete_employee.php',
                method: 'POST',
                data: { id: id, csrf_token: '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>' },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        alert('Xəta: ' + data.error);
                        return;
                    }
                    alert('Əməkdaş silindi!');
                    $('#deleteEmployeeModal').modal('hide');
                    fetchEmployees();
                },
                error: function(xhr, status, error) {
                    alert('Silinmə zamanı xəta: ' + error);
                }
            });
        });

        // Export employees
        $('#exportEmployee').on('click', function(e) {
            e.preventDefault();
            window.location.href = 'emekdaslar/export_employees.php';
        });

        // Import employees
        $('#confirmImport').on('click', function() {
            const form = $('#importEmployeeForm');
            if (!form[0].checkValidity()) {
                form.addClass('was-validated');
                return;
            }
            const formData = new FormData(form[0]);
            $.ajax({
                url: 'emekdaslar/import_employees.php',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        alert('Xəta: ' + data.error);
                        return;
                    }
                    alert(`İdxal uğurlu oldu! ${data.imported} əməkdaş əlavə edildi.`);
                    $('#importEmployeeModal').modal('hide');
                    fetchEmployees();
                },
                error: function(xhr, status, error) {
                    alert('İdxal zamanı xəta: ' + error);
                }
            });
        });

        // Filter employees
        $('#filterDepartment, #filterPosition, #filterStatus, #searchEmployee').on('change keyup', function() {
            const filters = {
                department: $('#filterDepartment').val(),
                position: $('#filterPosition').val(),
                status: $('#filterStatus').val(),
                search: $('#searchEmployee').val()
            };
            fetchEmployees(filters);
        });

        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#filterDepartment').val('');
            $('#filterPosition').val('');
            $('#filterStatus').val('');
            $('#searchEmployee').val('');
            fetchEmployees();
        });

        // Print employees
        $('#printEmployee').on('click', function(e) {
            e.preventDefault();
            const printContent = $('#list').html();
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Əməkdaş Siyahısı</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        });

        // Initial fetch
        fetchEmployees();
        
        
            // Add employee
            $('.new-emekdas').on('click', function() {
                $('#addEmployeeModal').modal('show');
            });

    </script>
</body>
</html>