<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: Login.php");
        exit();
    }

    include('navbar_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Tələbələr</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="telebeler/css.css">
    <style>
        .lds-ripple {  display: inline-block;  position: relative; width: 80px; height: 80px; }
        .lds-ripple div { position: absolute; border: 4px solid #3182ce; opacity: 1; border-radius: 50%; animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;}
        .lds-ripple div:nth-child(2) {animation-delay: -0.5s;}

        @keyframes lds-ripple {
            0% { top: 36px; left: 36px; width: 0; height: 0; opacity: 1; }
            100% { top: 0; left: 0; width: 72px; height: 72px; opacity: 0; }
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
                    <button hidden type="button" class="btn btn-primary add-student" data-toggle="modal" data-target="#addStudentModal">
                        <i class="fas fa-user-plus mr-1"></i> Yeni Tələbə
                    </button>
               </div>
            </div>
        </div>

        <?php include('telebeler/table-search.php'); ?>
    </div>

<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tələbə Məlumatlarını Redaktə Et</h5>
            </div>
            <div class="modal-body">
                <form id="editStudentForm" method="POST" action="telebeler/telebeler-edit.php" enctype="multipart/form-data">
                    <input type="hidden" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstName">Ad</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastName">Soyad</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="qebul_tarixi">Qəbul tarixi</label>
                                <input type="date" class="form-control" id="qebul_tarixi" name="qebul_tarixi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Cins</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="male">Kişi</option>
                                    <option value="female">Qadın</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dogum_tarixi">Doğum tarixi</label>
                                <input type="date" class="form-control" id="dogum_tarixi" name="dogum_tarixi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="yas">Yaş</label>
                                <input type="number" min="0" class="form-control" id="yas" name="yas">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ata">Ata</label>
                                <input type="text" class="form-control" id="ata" name="ata" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ata_nomre">Əlaqə nömrəsi</label>
                                <input type="tel" class="form-control" id="ata_nomre" name="elaqe_nomre_ata">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ana">Ana</label>
                                <input type="text" class="form-control" id="ana" name="ana" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ana_nomre">Əlaqə nömrəsi</label>
                                <input type="tel" class="form-control" id="ana_nomre" name="elaqe_nomre_ana">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class">Sinif</label>
                                <select class="form-control" id="class" name="class" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    include('db.php');
                                    $sql = "SELECT sinif_number FROM sinifler";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row["sinif_number"]) . "'>" . htmlspecialchars($row["sinif_number"]) . "</option>";
                                        }
                                    }
                                    $conn->close();
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">Aktiv</option>
                                    <option value="inactive">Qeyri-aktiv</option>
                                    <option value="graduate">Məzun</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Telefon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="muellim">Müəllim</label>
                                <select class="form-control" id="muellim" name="muellim">
                                    <option value="" disabled selected>Müəllim Seç</option>
                                    <?php
                                    include('db.php');
                                    $query = "SELECT username FROM muellimler_new WHERE active_status = 'active'";
                                    $result = mysqli_query($conn, $query);
                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . htmlspecialchars($row['username']) . "'>" . htmlspecialchars($row['username']) . "</option>";
                                        }
                                        mysqli_free_result($result);
                                    } else {
                                        echo "<option value=''>No active teachers found</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="address">Ünvan</label>
                                <textarea readonly class="form-control" id="address" name="address"></textarea>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div hidden class="form-group">
                        <label for="photo">Şəkil</label>
                        <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
                    </div>
                </form>
                <script>
                document.getElementById('editStudentForm').addEventListener('submit', function(event) {
                    const gender = document.getElementById('gender').value;
                    if (!['male', 'female'].includes(gender)) {
                        event.preventDefault();
                        alert('Please select a valid gender (Male or Female).');
                    }
                });
                </script>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                <button type="submit" form="editStudentForm" class="btn btn-primary">Yadda saxla</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Tələbə Əlavə Et</h5>
            </div>
            <div class="modal-body">
                <form id="addStudentForm" method="POST" action="telebeler/telebeler-insert.php" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstName">Ad</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastName">Soyad</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="qebul_tarixi">Qəbul tarixi</label>
                                <input type="date" class="form-control" id="qebul_tarixi" name="qebul_tarixi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Cins</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Seçin</option>
                                    <option value="male">Kişi</option>
                                    <option value="female">Qadın</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dogum_tarixi">Doğum tarixi</label>
                                <input type="date" class="form-control" id="dogum_tarixi" name="dogum_tarixi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="yas">Yaş</label>
                                <input type="number" min="0" class="form-control" id="yas" name="yas">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ata">Ata</label>
                                <input type="text" class="form-control" id="ata" name="ata" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ata_nomre">Əlaqə nömrəsi</label>
                                <input type="number" min="0" class="form-control" id="ata_nomre" name="elaqe_nomre_ata">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ana">Ana</label>
                                <input type="text" class="form-control" id="ana" name="ana" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ana_nomre">Əlaqə nömrəsi</label>
                                <input type="number" min="0" class="form-control" id="ana_nomre" name="elaqe_nomre_ana">
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class">Sinif</label>
                                <select class="form-control" id="class" name="class" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    include('db.php');
                                    $sql = "SELECT sinif_number FROM sinifler";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row["sinif_number"]) . "'>" . htmlspecialchars($row["sinif_number"]) . "</option>";
                                        }
                                    }
                                    $conn->close();
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">Aktiv</option>
                                    <option value="inactive">Qeyri-aktiv</option>
                                    <option value="graduate">Məzun</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Telefon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="address">Ünvan</label>
                        <textarea class="form-control" id="address" name="address"></textarea>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="photo">Şəkil</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="photo" name="photo">
                            <label class="custom-file-label" for="photo">Şəkil seçin</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="submit" form="addStudentForm" class="btn btn-primary">Yadda saxla</button>
            </div>
        </div>
    </div>
</div>


<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tələbə Məlumatları</h5>
            </div>
            <div class="modal-body">
                <div class="student-profile">
                    <img src="telebeler/uploads/68061c450972f.png" class="profile-image">
                    <div class="profile-info">
                        <h4 class="profile-name"></h4>
                        <p hidden class="profile-id"></p>
                        <div class="profile-contact">
                            <span class="contact-item"><i class="fas fa-envelope mr-1"></i></span>
                        </div>
                        <span class="badge"></span>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header"><h6 class="mb-0">Qeydiyyat məlumatları</h6></div>
                            <div class="card-body">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr><th width="30%">Ad Soyad:</th><td data-field="reg_ad_soyad"></td></tr>
                                        <tr><th>E-mail:</th><td data-field="reg_email"></td></tr>
                                        <tr><th>Bölmə:</th><td data-field="reg_bolme"></td></tr>
                                        <tr><th>Tədris:</th><td data-field="reg_tedris"></td></tr>
                                        <tr><th>Arzu olunan vaxt:</th><td data-field="reg_vaxt"></td></tr>
                                        <tr><th>Xidmətlər:</th><td data-field="reg_services"></td></tr>
                                        <tr><th>Mənbə:</th><td data-field="reg_menbe"></td></tr>
                                        <tr><th>Sinif qeydi:</th><td data-field="reg_sinif_qeyd"></td></tr>
                                        <tr><th>Qeydiyyat tarixi:</th><td data-field="created_at"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th width="42%">Doğum tarixi:</th>
                                            <td data-field="dogum_tarixi"></td>
                                        </tr>
                                        <tr>
                                            <th>Telefon:</th>
                                            <td data-field="reg_telefon"></td>
                                        </tr>
                                        <tr>
                                            <th>İş nömrəsi:</th>
                                            <td data-field="reg_is_nomresi"></td>
                                        </tr>
                                        <tr>
                                            <th>Yaş:</th>
                                            <td data-field="years"></td>
                                        </tr>
                                        <tr>
                                            <th width="40%">Ata:</th>
                                            <td data-field="ata"></td>
                                        </tr>
                                        <tr>
                                            <th width="40%">Sinif:</th>
                                            <td data-field="sinif"></td>
                                        </tr>
                                        <tr>
                                            <th>Qəbul ili:</th>
                                            <td data-field="reg_qebul_ili"></td>
                                        </tr>
                                        <tr>
                                            <th>Müəllim:</th>
                                            <td data-field="muellim_adi"></td>
                                        </tr>
                                        <tr>
                                            <th>Universitet:</th>
                                            <td data-field="reg_universitet"></td>
                                        </tr>
                                        <tr>
                                            <th>İxtisas:</th>
                                            <td data-field="reg_ixtisas"></td>
                                        </tr>
                                        <tr>
                                            <th>Davamiyyət:</th>
                                            <td data-field="davamiyyet"></td>
                                        </tr>
                                        <tr>
                                            <th>FIN kod:</th>
                                            <td data-field="reg_fin_kod"></td>
                                        </tr>
                                        <tr>
                                            <th>Bakalavr balı:</th>
                                            <td data-field="reg_bakalavr_bali"></td>
                                        </tr>
                                        <tr>
                                            <th>Magistr balı:</th>
                                            <td data-field="reg_magistr_bali"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th width="40%">Riyaziyyat:</th>
                                            <td data-field="riyaziyyat"></td>
                                        </tr>
                                        <tr>
                                            <th>Fizika:</th>
                                            <td data-field="fizika"></td>
                                        </tr>
                                        <tr>
                                            <th>Kimya:</th>
                                            <td data-field="kimya"></td>
                                        </tr>
                                        <tr>
                                            <th>Biologiya:</th>
                                            <td data-field="biologiya"></td>
                                        </tr>
                                        <tr>
                                            <th>Tarix:</th>
                                            <td data-field="tarix"></td>
                                        </tr>
                                        <tr>
                                            <th>Ədəbiyyat:</th>
                                            <td data-field="edebiyyat"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Əlavə qeydlər</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless mb-2">
                                    <tbody>
                                        <tr><th width="20%">Qeyd 1:</th><td data-field="reg_elave_qeyd_1"></td></tr>
                                        <tr><th>Qeyd 2:</th><td data-field="reg_elave_qeyd_2"></td></tr>
                                        <tr><th>Qeyd 3:</th><td data-field="reg_elave_qeyd_3"></td></tr>
                                    </tbody>
                                </table>
                                <p class="mb-0"><strong>Ümumi qeyd:</strong> <span data-field="qeyd"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>
    
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tələbəni Sil</h5>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                        <h5>Bu tələbəni silmək istədiyinizə əminsiniz?</h5>
                        <p class="text-muted">Bu əməliyyat geri qaytarıla bilməz.</p>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="deleteConfirm">
                            <label class="custom-control-label" for="deleteConfirm">Bəli, bu tələbəni silmək istəyirəm</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>Sil</button>
                </div>
            </div>
        </div>
    </div>


    
<script>
    $(document).ready(function () {
        $('#editStudentForm').on('submit', function (e) {
            const email = $('#email').val();
            const phone = $('#phone').val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^\+?\d{7,15}$/;

            let isValid = true;

            if (!emailRegex.test(email)) {
                $('#email').addClass('is-invalid');
                isValid = false;
            } else {
                $('#email').removeClass('is-invalid');
            }

            if (phone && !phoneRegex.test(phone)) {
                $('#phone').addClass('is-invalid');
                isValid = false;
            } else {
                $('#phone').removeClass('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
                alert('Please correct the invalid fields.');
            }
        });
    });
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="telebeler/script.js"></script>
</body>
</html>