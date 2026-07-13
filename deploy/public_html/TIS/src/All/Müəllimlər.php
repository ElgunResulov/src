<?php
require_once __DIR__ . '/auth.php';
app_start_secure_session();
app_csrf_token();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include('navbar_sidebar.php');
include('muellim/statistika.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Müəllimlər</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="muellim/css.css">
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

    </style>
 </head>
<body>

    <div class="preloader">
        <div class="lds-ripple">
            <div></div> <div></div>
        </div>
    </div>
    
    <div class="main-content main">

        <div style="margin-bottom:-12px;" class="form-group">
            <button class="btn btn-primary" id="addTeacherBtn">
                <i class="fas fa-plus mr-1"></i> Müəllim Əlavə Et
            </button>
        </div>

        <div class="mt-3 row">
            <div class="col-md-3 col-sm-6">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-normal">Ümumi Müəllimlər</h6>
                                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                            </div>
                            <div class="icon-box">
                                <i class="fas fa-chalkboard-teacher fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-normal">Aktiv Müəllimlər</h6>
                                <h3 class="mb-0"><?php echo $stats['active']; ?></h3>
                            </div>
                            <div class="icon-box">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-normal">Fənn Sayı</h6>
                                <h3 class="mb-0"><?php echo $stats['subjects']; ?></h3>
                            </div>
                            <div class="icon-box">
                                <i class="fas fa-book fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-normal">Bu Ay Yeni</h6>
                                <h3 class="mb-0"><?php echo $stats['new_this_month']; ?></h3>
                            </div>
                            <div class="icon-box">
                                <i class="fas fa-user-plus fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom:-5px;" class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-1 mb-md-0">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Müəllim axtar..." id="searchTeacher">
                        </div>
                    </div>
                    <div class="col-md-3 mb-1 mb-md-0">
                        <select class="form-control" id="filterDepartment">
                            <option value="">Bütün Fənnlər</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-1 mb-md-0">
                        <select class="form-control" id="filterStatus">
                            <option value="">Bütün Statuslar</option>
                            <option value="active">Aktiv</option>
                            <option value="inactive">Qeyri-aktiv</option>
                            <option value="onleave">Məzuniyyətdə</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-block" id="resetFilters">
                            <i class="fas fa-redo-alt mr-1"></i> Sıfırla
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="alertContainer" style="display: none;">
            <div class="alert" role="alert"></div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">Müəllimlər Siyahısı</h5>
                </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th> 
                                    <th>Ad Soyad</th>
                                    <th>Fənn</th>
                                    <th>Əlaqə</th>
                                    <th>Status</th>
                                    <th>Təcrübə</th>
                                    <th class="text-center">QR Kod</th>
                                    <th class="text-center">Əməliyyatlar</th>
                                </tr>
                            </thead>
                            <tbody id="teachersTableBody">
                                <tr>
                                    <td colspan="9" class="text-center">Yüklənir...</td> 
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <!-- Pagination -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <p class="mb-0" id="paginationInfo">Yüklənir...</p>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-end mb-0" id="pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Add/Edit Teacher Modal -->
<div class="modal fade" id="teacherModal" tabindex="-1" role="dialog" aria-labelledby="teacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="teacherModalLabel">Yeni Müəllim Əlavə Et</h5>
            </div>
            <div class="modal-body">
                <form id="teacherForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(app_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" id="teacherId" name="teacherId" value="">
                    <div class="image-upload-container">
                        <img src="" id="profileImagePreview" class="profile-image-preview">
                        <label for="profileImage" class="custom-file-upload">Profil şəkli seçin</label>
                        <input type="file" id="profileImage" name="profileImage" accept="image/*" class="hidden-input" onchange="previewImage()">
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ad">Ad</label>
                                <input type="text" class="form-control" id="ad" name="ad" required>
                                <div class="invalid-feedback">Ad daxil edin</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="soyad">Soyad</label>
                                <input type="text" class="form-control" id="soyad" name="soyad" required>
                                <div class="invalid-feedback">Soyad daxil edin</div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Düzgün email daxil edin</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefon">Telefon</label>
                                <input type="tel" class="form-control" id="telefon" name="telefon">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div hidden class="col-md-6">
                            <div class="form-group">
                                <label for="fenn">Fənn</label>
                                <select class="form-control" id="fenn" name="fenn">
                                    <option value="">Seçin</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                                    <?php endforeach; ?>
                                    <option value="new">+ Yeni Fənn Əlavə Et</option>
                                </select>
                                <div class="invalid-feedback">Fənn seçin</div>
                            </div>
                        </div>
                        <div class="">
                            <div class="form-group">
                                <label for="active_status">Status</label>
                                <select class="form-control" id="active_status" name="active_status" required>
                                    <option value="active">Aktiv</option>
                                    <option value="inactive">Qeyri-aktiv</option>
                                    <option value="onleave">Məzuniyyətdə</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tecrube">Təcrübə (il)</label>
                                <input type="number" class="form-control" id="tecrube" name="tecrube" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ise_baslama_tarixi">İşə başlama tarixi</label>
                                <input type="date" class="form-control" id="ise_baslama_tarixi" name="ise_baslama_tarixi" required>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="unvan">Ünvan</label>
                        <textarea class="form-control" id="unvan" name="unvan" rows="2"></textarea>
                    </div>
                    <br>
                    <div class="form-group">
                        <label class="mb-1" for="class">Təhsil və İxtisas</label>
                        <select class="form-control" id="class" name="class" required>
                            <option value="">Seçin</option>
                            <?php
                                include('db.php');
                                $sql = "SELECT ixtisas_adi FROM ixtisas WHERE active='1'";
                                $result = $conn->query($sql);
                                
                                if ($result) {
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row["ixtisas_adi"]) . "'>" . htmlspecialchars($row["ixtisas_adi"]) . "</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>Heç bir ixtisas tapılmadı</option>";
                                    }
                                } else {
                                    echo "<option value='' disabled>Sorgu xətası: " . htmlspecialchars($conn->error) . "</option>";
                                }
                                $conn->close();
                            ?>
                        </select>
                        <div class="invalid-feedback">İxtisas seçin</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary" id="saveTeacher">Yadda saxla</button>
            </div>
        </div>
    </div>
</div>

    <!-- View Teacher Modal -->
    <div class="modal fade" id="viewTeacherModal" tabindex="-1" role="dialog" aria-labelledby="viewTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTeacherModalLabel">Müəllim Məlumatları</h5>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="avatar mx-auto mb-3" style="width: 120px; height: 120px;">
                                <img id="viewTeacherImage" src="../assets/images/users/default-profile.jpg" class="rounded-circle img-fluid">
                            </div>
                            <div class="mb-3">
                                <img id="viewTeacherQr" src="" alt="Müəllim QR kodu" class="img-fluid border rounded" style="max-width: 140px; display: none;">
                                <p id="viewTeacherQrEmpty" class="text-muted small mb-0" style="display: none;">QR kod hazır deyil</p>
                            </div>
                            <h5 class="mb-1" id="viewTeacherName"></h5>
                            <p hidden class="text-muted" id="viewTeacherSubject"></p>
                            <span class="badge" id="viewTeacherStatus"></span>
                        </div>
                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th width="35%">Email:</th>
                                            <td id="viewTeacherEmail"></td>
                                        </tr>
                                        <tr>
                                            <th>Telefon:</th>
                                            <td id="viewTeacherPhone"></td>
                                        </tr>
                                        <tr>
                                            <th>Təcrübə:</th>
                                            <td id="viewTeacherExperience"></td>
                                        </tr>
                                        <tr>
                                            <th>İşə başlama tarixi:</th>
                                            <td id="viewTeacherStartDate"></td>
                                        </tr>
                                        <tr>
                                            <th>Ünvan:</th>
                                            <td id="viewTeacherAddress"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="font-weight-bold">Təhsil və İxtisas</h6>
                            <p id="viewTeacherQualifications"></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="font-weight-bold">Cari Dərslər</h6>
                            <div class="table-responsive current-classes-table">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sinif</th>
                                            <th>Gün</th>
                                            <th>Vaxt</th>
                                            <th>Otaq</th>
                                        </tr>
                                    </thead>
                                    <tbody id="viewTeacherClasses"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-primary edit-teacher-from-view">Redaktə et</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteTeacherModal" tabindex="-1" role="dialog" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTeacherModalLabel">Müəllimi Sil</h5>
                </div>
                <div class="modal-body">
                    <p>Bu müəllimi silmək istədiyinizə əminsiniz?</p>
                    <p class="text-danger"><strong>Diqqət:</strong> Bu əməliyyat geri qaytarıla bilməz.</p>
                    <input type="hidden" id="deleteTeacherId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Sil</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newSubjectModal" tabindex="-1" role="dialog" aria-labelledby="newSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newSubjectModalLabel">Yeni Fənn Əlavə Et</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="newSubjectName">Fənn adı</label>
                        <input type="text" class="form-control" id="newSubjectName" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-primary" id="saveNewSubject">Əlavə et</button>
                </div>
            </div>
        </div>
    </div>

    <div class="spinner-overlay" id="loadingSpinner" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Yüklənir...</span>
        </div>
    </div>

    <script src="muellim/script.js"></script>
</body>
</html>

