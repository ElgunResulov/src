<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check for user authentication
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    include('db.php');

    // Initialize counts
    $specialty_count = 0;
    $active_specialty_count = 0;
    $student_count = 0;
    $teacher_count = 0;
    $subject_count = 0;

    // Query for specialty counts
    $sql_specialties = "SELECT COUNT(*) as count, SUM(IF(active=1, 1, 0)) as active_count FROM ixtisas";
    $result_specialties = $conn->query($sql_specialties);
    if ($result_specialties && $result_specialties->num_rows > 0) {
        $row = $result_specialties->fetch_assoc();
        $specialty_count = $row['count'];
        $active_specialty_count = $row['active_count'];
    }

    // Query for student count
    $sql_students = "SELECT COUNT(*) as count FROM telebeler";
    $result_students = $conn->query($sql_students);
    if ($result_students && $result_students->num_rows > 0) {
        $student_count = $result_students->fetch_assoc()['count'];
    }

    // Query for teacher count
    $sql_teachers = "SELECT COUNT(*) as count FROM muellimler_new";
    $result_teachers = $conn->query($sql_teachers);
    if ($result_teachers && $result_teachers->num_rows > 0) {
        $teacher_count = $result_teachers->fetch_assoc()['count'];
    }

    // Query for subject count
    $sql_subjects = "SELECT COUNT(*) as count FROM fennler_new";
    $result_subjects = $conn->query($sql_subjects);
    if ($result_subjects && $result_subjects->num_rows > 0) {
        $subject_count = $result_subjects->fetch_assoc()['count'];
    }

    include('navbar_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS İxtisas üzrə idarəetmə</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>

    <?php include('ixtisas/ixtisas-modals.php'); ?>

    <div class="main-content main">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="addSpecialtyBtn">
                            <i class="fas fa-plus-circle mr-1"></i> Yeni İxtisas
                        </button>

                        <button hidden
                            type="button" 
                            class="btn btn-outline-primary ml-2" 
                            id="dropdownButton"
                            aria-haspopup="true" 
                            aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-right" id="dropdownMenu">
                            <a class="dropdown-item" href="#" id="importSpecialty">
                                <i class="fas fa-file-import mr-2"></i> İdxal
                            </a>
                            <a class="dropdown-item" href="#" id="exportSpecialty">
                                <i class="fas fa-file-export mr-2"></i> İxrac
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="printSpecialty">
                                <i class="fas fa-print mr-2"></i> Çap et
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-primary text-white h-100" data-stat-type="specialties" role="button" tabindex="0" aria-label="Ümumi ixtisasları göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-graduation-cap fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Ümumi İxtisaslar</h6>
                        <h3 class="stat-number"><?php echo $specialty_count; ?></h3>
                        <p class="mb-0 small">Aktiv: <?php echo $active_specialty_count; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-success text-white h-100" data-stat-type="students" role="button" tabindex="0" aria-label="Tələbələri göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-user-graduate fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Tələbələr</h6>
                        <h3 class="stat-number"><?php echo $student_count; ?></h3>
                        <p class="mb-0 small">İxtisaslar üzrə</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-info text-white h-100" data-stat-type="teachers" role="button" tabindex="0" aria-label="Müəllimləri göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-chalkboard-teacher fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Müəllimlər</h6>
                        <h3 class="stat-number"><?php echo $teacher_count; ?></h3>
                        <p class="mb-0 small">İxtisaslar üzrə</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card stat-card stat-card-clickable bg-warning text-white h-100" data-stat-type="subjects" role="button" tabindex="0" aria-label="Fənnləri göstər">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-book fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Fənnlər</h6>
                        <h3 class="stat-number"><?php echo $subject_count; ?></h3>
                        <p class="mb-0 small">İxtisaslar üzrə</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="filterDepartment">Fakültə</label>
                            <select class="form-control" id="filterDepartment">
                                <option value="">Bütün Fakültələr</option>
                                <option value="1">Mühəndislik</option>
                                <option value="2">İqtisadiyyat</option>
                                <option value="3">Humanitar</option>
                                <option value="4">Tibb</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="form-group mb-0">
                            <label for="filterLevel">Təhsil Səviyyəsi</label>
                            <select class="form-control" id="filterLevel">
                                <option value="">Bütün Səviyyələr</option>
                                <option value="bachelor">Bakalavr</option>
                                <option value="master">Magistr</option>
                                <option value="phd">Doktorantura</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="filterStatus">Status</label>
                            <select class="form-control" id="filterStatus">
                                <option value="">Bütün Statuslar</option>
                                <option value="1">Aktiv</option>
                                <option value="0">Qeyri-aktiv</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="searchSpecialty">Axtarış</label>
                            <input type="text" class="form-control" id="searchSpecialty" placeholder="İxtisas adı, kodu və ya açar sözlər...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="specialtyTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="grid-tab" data-toggle="tab" href="#grid" role="tab">
                            <i class="fas fa-th-large mr-2"></i> Şəbəkə Görünüşü
                        </a>
                    </li>
                    <li hidden class="nav-item">
                        <a class="nav-link" id="list-tab" data-toggle="tab" href="#list" role="tab">
                            <i class="fas fa-list mr-2"></i> Siyahı Görünüşü
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-4" id="specialtyTabsContent">
                    <div class="tab-pane fade show active" id="grid" role="tabpanel">
                        <div class="row" id="specialtiesGrid">
                            <div class="col-12 text-center py-5">
                                <div class="spinner-border" role="status">
                                    <span class="sr-only">Yüklənir...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="list" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>İxtisas Adı</th>
                                        <th>İxtisas Kodu</th>
                                        <th>Təhsil Səviyyəsi</th>
                                        <th>Status</th>
                                        <th>Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody id="specialtiesList">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <script>
    $(document).ready(function() {
        let isEditMode = false;
        
        // Load specialties on page load
        loadSpecialties();

        const statTitles = {
            specialties: 'Ümumi İxtisaslar',
            students: 'Tələbələr',
            teachers: 'Müəllimlər',
            subjects: 'Fənnlər'
        };

        $('.stat-card-clickable').on('click', function () {
            openStatDetailsModal($(this).data('stat-type'));
        });

        $('.stat-card-clickable').on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openStatDetailsModal($(this).data('stat-type'));
            }
        });

        // Add Specialty Button
        $('#addSpecialtyBtn').on('click', function() {
            openModal('add');
        });

        // Save Specialty (Add/Edit)
        $('#saveSpecialty').on('click', function(e) {
            e.preventDefault();
            const $form = $('#specialtyForm');
            const form = $form[0];

            if (form.checkValidity()) {
                const formData = new FormData(form);
                const action = isEditMode ? 'edit' : 'insert';
                
                $.ajax({
                    url: `ixtisas/ixtisas_operations.php?action=${action}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#saveSpecialty').prop('disabled', true).text(isEditMode ? 'Yenilənir...' : 'Yadda saxlanılır...');
                    },
                    success: function(response) {
                        try {
                            const res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (res.status === 'success') {
                                $('#specialtyModal').modal('hide');
                                showAlert('success', res.message);
                                loadSpecialties();
                            } else {
                                showAlert('danger', res.message || 'Bilinməyən xəta');
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e, 'Response:', response);
                            showAlert('danger', 'Server xətası: Cavab formatı yanlışdır.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', xhr.status, xhr.responseText, status, error);
                        showAlert('danger', 'Serverə qoşulmaq mümkün olmadı: ' + xhr.status);
                    },
                    complete: function() {
                        $('#saveSpecialty').prop('disabled', false).text('Yadda saxla');
                    }
                });
            } else {
                validateForm($form);
            }
        });

        // View Specialty
        $(document).on('click', '.view-specialty', function() {
            const specialtyId = $(this).data('id');
            viewSpecialty(specialtyId);
        });

        // Edit Specialty
        $(document).on('click', '.edit-specialty', function() {
            const specialtyId = $(this).data('id');
            openModal('edit', specialtyId);
        });

        // Delete Specialty
        $(document).on('click', '.delete-specialty', function() {
            const specialtyId = $(this).data('id');
            $('#deleteSpecialtyId').val(specialtyId);
            $('#deleteSpecialtyModal').modal('show');
        });

        // Confirm Delete
        $('#confirmDeleteSpecialty').on('click', function() {
            const specialtyId = $('#deleteSpecialtyId').val();
            
            const $deleteButton = $(this);
            const originalText = $deleteButton.text();
            $deleteButton.prop('disabled', true).text('Silinir...');
            
            $.ajax({
                url: 'ixtisas/ixtisas_operations.php?action=delete',
                type: 'POST',
                data: { id: specialtyId },
                dataType: 'json',
                success: function(response) {
                    try {
                        const res = typeof response === 'string' ? JSON.parse(response) : response;
                        if (res.status === "success") {
                            $('#deleteSpecialtyModal').modal('hide');
                            showAlert('success', res.message);
                            loadSpecialties();
                        } else {
                            showAlert('danger', res.message || 'Bilinməyən xəta');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e, 'Response:', response);
                        showAlert('danger', 'Server xətası: Cavab formatı yanlışdır.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Delete error:", xhr.status, xhr.responseText, status, error);
                    showAlert('danger', 'Xəta baş verdi: ' + xhr.status);
                },
                complete: function() {
                    $deleteButton.prop('disabled', false).text(originalText);
                }
            });
        });

        // Image preview handler
        $('#specialtyImage').on('change', function() {
            const fileName = this.files[0]?.name || 'Şəkil seçin';
            $(this).next('.custom-file-label').text(fileName);
            
            const $preview = $('#specialtyImagePreview');
            if (this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $preview.attr('src', e.target.result);
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Filter handlers
        $('#filterLevel, #filterStatus').on('change', function() {
            filterSpecialties();
        });

        $('#searchSpecialty').on('keyup', debounce(function() {
            filterSpecialties();
        }, 300));

        // Functions
        function openModal(mode, specialtyId = null) {
            isEditMode = mode === 'edit';
            
            // Reset form
            const $form = $('#specialtyForm');
            $form[0].reset();
            $form.find('.form-control').removeClass('is-invalid is-valid');
            $('#specialtyImagePreview').attr('src', '');
            $('#specialtyImage').next('.custom-file-label').text('Şəkil seçin');
            
            // Set modal title
            $('#modalTitle').text(isEditMode ? 'İxtisası Redaktə Et' : 'İxtisası Əlavə Et');
            
            if (isEditMode && specialtyId) {
                // Load specialty data for editing
                $.ajax({
                    url: `ixtisas/ixtisas_operations.php?action=view&id=${specialtyId}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const specialty = response.data;
                            
                            $('#specialtyId').val(specialty.id);
                            $('#specialtyName').val(specialty.ixtisas_adi || '');
                            $('#specialtyCode').val(specialty.ixtisas_kodu || '');
                            $('#department').val(specialty.fakulte || '');
                            $('#educationLevel').val(specialty.tehsil_seviyyesi || '');
                            $('#specialtyDescription').val(specialty.tesvir || '');
                            $('#specialtyActive').val(specialty.active || '1');
                            $('#specialtyImagePreview').attr('src', specialty.sekil || '');
                            
                            $('#specialtyModal').modal('show');
                        } else {
                            showAlert('danger', response.message || 'Məlumat tapılmadı');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Edit load error:', xhr.status, xhr.responseText, status, error);
                        showAlert('danger', 'Məlumatları yükləmək mümkün olmadı: ' + xhr.status);
                    }
                });
            } else {
                $('#specialtyModal').modal('show');
            }
        }

        function viewSpecialty(specialtyId) {
            // Reset modal content
            $('#viewSpecialtyName').text('-');
            $('#viewSpecialtyCode').text('-');
            $('#viewSpecialtyFaculty').text('-');
            $('#viewSpecialtyLevel').text('-');
            $('#viewSpecialtyStatus').text('-');
            $('#viewSpecialtyCreated').text('-');
            $('#viewSpecialtyDescription').text('-');
            $('#viewSpecialtyImage').attr('src', '');
            $('#viewStudentCount').text('0');
            $('#viewTeacherCount').text('0');
            $('#viewSubjectCount').text('0');
            
            // Show modal
            $('#viewSpecialtyModal').modal('show');
            
            // Load data
            $.ajax({
                url: `ixtisas/ixtisas_operations.php?action=view&id=${specialtyId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const specialty = response.data;
                        const counts = response.counts;
                        
                        $('#viewSpecialtyName').text(specialty.ixtisas_adi || '-');
                        $('#viewSpecialtyCode').text(specialty.ixtisas_kodu || '-');
                        $('#viewSpecialtyFaculty').text(specialty.fakulte_adi || 'Naməlum');
                        $('#viewSpecialtyLevel').text(specialty.tehsil_seviyyesi_adi || '-');
                        $('#viewSpecialtyStatus').html(specialty.active == 1 ? '<span class="badge badge-success">Aktiv</span>' : '<span class="badge badge-danger">Passiv</span>');
                        $('#viewSpecialtyCreated').text(specialty.created_at ? new Date(specialty.created_at).toLocaleDateString('az-AZ') : '-');
                        $('#viewSpecialtyDescription').text(specialty.tesvir || 'Təsvir yoxdur');
                        $('#viewSpecialtyImage').attr('src', specialty.sekil || '');
                        
                        $('#viewStudentCount').text(counts.students || 0);
                        $('#viewTeacherCount').text(counts.teachers || 0);
                        $('#viewSubjectCount').text(counts.subjects || 0);
                    } else {
                        showAlert('danger', response.message || 'Məlumat tapılmadı');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('View error:', xhr.status, xhr.responseText, status, error);
                    showAlert('danger', 'Məlumatları yükləmək mümkün olmadı: ' + xhr.status);
                }
            });
        }

        function loadSpecialties() {
            $.ajax({
                url: 'ixtisas/ixtisas_operations.php?action=list',
                type: 'GET',
                dataType: 'json',
                timeout: 100,
                success: function(response) {
                    if (response.status === 'success') {
                        renderSpecialties(response.data);
                    } else {
                        showAlert('danger', response.message || 'Məlumatları yükləmək mümkün olmadı');
                        $('#specialtiesGrid').html('<div class="col-12 text-center py-5">Məlumat tapılmadı</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Load error:", xhr.status, xhr.responseText, status, error);
                    showAlert('danger', 'Məlumatları yükləmək mümkün olmadı: ' + xhr.status + ' - ' + error);
                    $('#specialtiesGrid').html('<div class="col-12 text-center py-5">Xəta baş verdi</div>');
                }
            });
        }

        function renderSpecialties(specialties) {
            let gridHtml = '';
            
            specialties.forEach(function(specialty) {
                const statusClass = specialty.active == 1 ? "badge-success" : "badge-danger";
                const statusText = specialty.active == 1 ? "Aktiv" : "Passiv";
                
                let imageSrc = specialty.sekil_full_path || '';
                
                gridHtml += `
                    <div class="col-md-4 col-sm-6 mb-4 specialty-item" 
                         data-level="${specialty.tehsil_seviyyesi}" 
                         data-status="${specialty.active}">
                        <div class="card h-100 specialty-card">
                            <img src="${imageSrc}" class="card-img-top" style="height: 160px; object-fit: cover;" onerror="this.src=''">
                            <span class="badge ${statusClass}" style="position: absolute; top: 10px; right: 10px;">${statusText}</span>
                            <div class="card-body">
                                <h5 class="card-title">${specialty.ixtisas_adi}</h5>
                                <p class="card-text">Kod: ${specialty.ixtisas_kodu}</p>
                                <p class="card-text">Səviyyə: ${specialty.tehsil_seviyyesi_adi}</p>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100">
                                    <button type="button" class="btn btn-sm btn-outline-primary view-specialty" data-id="${specialty.id}" title="Bax">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success edit-specialty" data-id="${specialty.id}" title="Redaktə et">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-specialty" data-id="${specialty.id}" title="Sil">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#specialtiesGrid').html(gridHtml || '<div class="col-12 text-center py-5">Məlumat tapılmadı</div>');
        }

        function filterSpecialties() {
            const level = $('#filterLevel').val();
            const status = $('#filterStatus').val();
            const search = $('#searchSpecialty').val().toLowerCase();
            
            $('.specialty-item').each(function() {
                const $item = $(this);
                const itemLevel = $item.data('level') || '';
                const itemStatus = $item.data('status').toString();
                const itemText = $item.text().toLowerCase();
                let show = true;
                
                if (level && itemLevel !== level) {
                    show = false;
                }
                if (status !== '' && itemStatus !== status) {
                    show = false;
                }
                if (search && itemText.indexOf(search) === -1) {
                    show = false;
                }
                
                $item.toggle(show);
            });
        }

        function validateForm($form) {
            $form.find('.form-control').each(function() {
                if (!this.checkValidity()) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                }
            });
        }

        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                </div>
            `;
            
            $('.alert-container').remove();
            
            if ($('.alert-container').length === 0) {
                $('<div class="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;"></div>').appendTo('body');
            }
            
            $('.alert-container').append(alertHtml);
            
            setTimeout(function() {
                $('.alert-container .alert').alert('close');
            }, 5000);
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function openStatDetailsModal(type) {
            $('#statDetailsTitle').text(statTitles[type] || 'Məlumatlar');
            $('#statDetailsLoading').removeClass('d-none');
            $('#statDetailsContent').addClass('d-none');
            $('#statDetailsEmpty').addClass('d-none');
            $('#statDetailsHead').empty();
            $('#statDetailsBody').empty();

            const statModalEl = document.getElementById('statDetailsModal');
            if (statModalEl) {
                bootstrap.Modal.getOrCreateInstance(statModalEl).show();
            }

            $.ajax({
                url: `ixtisas/ixtisas_operations.php?action=stat_details&type=${type}`,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    $('#statDetailsLoading').addClass('d-none');

                    if (response.status === 'success' && response.data && response.data.length > 0) {
                        renderStatDetailsTable(response.columns, response.data);
                        $('#statDetailsContent').removeClass('d-none');
                    } else if (response.status === 'success') {
                        $('#statDetailsEmpty').removeClass('d-none');
                    } else {
                        showAlert('danger', response.message || 'Məlumat tapılmadı');
                        $('#statDetailsEmpty').removeClass('d-none');
                    }
                },
                error: function (xhr) {
                    $('#statDetailsLoading').addClass('d-none');
                    $('#statDetailsEmpty').removeClass('d-none');
                    showAlert('danger', 'Məlumatları yükləmək mümkün olmadı: ' + xhr.status);
                }
            });
        }

        function renderStatDetailsTable(columns, rows) {
            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            let headHtml = '<tr>';
            columns.forEach(function (column) {
                headHtml += `<th>${escapeHtml(column.label)}</th>`;
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
                        value = `<span class="badge ${badgeClass}">${escapeHtml(value)}</span>`;
                    } else {
                        value = escapeHtml(value);
                    }
                    bodyHtml += `<td>${value}</td>`;
                });
                bodyHtml += '</tr>';
            });
            $('#statDetailsBody').html(bodyHtml);
        }
    });
    </script>
</body>
</html>