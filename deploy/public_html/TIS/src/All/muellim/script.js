
function formatTeacherName(username) {
   if (!username) {
       return '';
   }
   return String(username).replace(/\./g, ' ');
}

// Close modals with the close-modal class
$('.close-modal').on('click', function() {
   $(this).closest('.modal').modal('hide');
});



function previewImage() {
var input = document.getElementById('profileImage');
var preview = document.getElementById('profileImagePreview');

if (input.files && input.files[0]) {
var reader = new FileReader();
reader.onload = function(e) {
preview.src = e.target.result;
}
reader.readAsDataURL(input.files[0]);
}
}

function getCsrfToken() {
   if (window.APP_CSRF_TOKEN) {
       return window.APP_CSRF_TOKEN;
   }
   const metaToken = document.querySelector('meta[name="csrf-token"]');
   return metaToken ? metaToken.getAttribute('content') : '';
}

$(document).ready(function() {
// Hide preloader when page is loaded
$(".preloader").fadeOut();

// Initialize tooltips
$('[data-toggle="tooltip"]').tooltip();

if (getCsrfToken() && typeof $.ajaxSetup === 'function') {
   $.ajaxSetup({
       beforeSend: function(xhr, settings) {
           if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type || 'GET')) {
               xhr.setRequestHeader('X-CSRF-Token', getCsrfToken());
           }
       }
   });
}

// Global variables
let currentPage = 1;
let currentTeacherId = null;
let teachersData = [];

// Load teachers on page load
loadTeachers();

// Function to show loading spinner
function showLoading() {
   $("#loadingSpinner").show();
}

// Function to hide loading spinner
function hideLoading() {
   $("#loadingSpinner").hide();
}

// Function to show alert message
function showAlert(message, type) {
   const alertContainer = $("#alertContainer");
   const alert = alertContainer.find(".alert");
   
   alert.removeClass("alert-success alert-danger")
        .addClass("alert-" + type)
        .html(message);
        
   alertContainer.show();
   
   // Auto hide after 5 seconds
   setTimeout(function() {
       alertContainer.fadeOut();
   }, 5000);
}

// Function to load teachers
function loadTeachers(page = 1, search = "", fenn = "", status = "") {
   showLoading();
   
   $.ajax({
       url: "muellim/muellimler_fetch.php",
       type: "GET",
       data: {
           page: page,
           search: search,
           fenn: fenn,
           status: status,
           limit: 15
       },
       dataType: "json",
       success: function(response) {
           hideLoading();
           
           if (response.success) {
               teachersData = response.data;
               renderTeachers(response);
               renderPagination(response);
           } else {
               showAlert("Məlumatları yükləyərkən xəta baş verdi: " + response.message, "danger");
           }
       },
       error: function(xhr, status, error) {
           hideLoading();
           showAlert("Server xətası: " + error, "danger");
       }
   });
}


// Function to render teachers table
function renderTeachers(response) {
    const tableBody = $("#teachersTableBody");
    tableBody.empty();
    
    if (response.data.length === 0) {
        tableBody.html('<tr><td colspan="8" class="text-center">Heç bir məlumat tapılmadı</td></tr>');
        return;
    }
    
    // Initialize the counter based on pagination, starting from 1
    let count = (response.page - 1) * response.limit + 1;
 
    response.data.forEach(function(teacher) {
        let statusBadge = '';
        
        switch(teacher.active_status) {
            case 'active':
                statusBadge = '<span class="badge badge-success">Aktiv</span>';
                break;
            case 'inactive':
                statusBadge = '<span class="badge badge-danger">Qeyri-aktiv</span>';
                break;
            case 'onleave':
                statusBadge = '<span class="badge badge-warning">Məzuniyyətdə</span>';
                break;
            default:
                statusBadge = '<span class="badge badge-secondary">Naməlum</span>';
        }
 
        const row = `
            <tr data-id="${teacher.id}">
                <td>${count}</td> <!-- Display the counter -->
                <td>${formatTeacherName(teacher.username)}</td> <!-- Teacher's name -->
                <td>${teacher.tehsil_ve_ixtisas}</td>
                <td>${teacher.email}</td>
                <td>${statusBadge}</td>
                <td>${teacher.tecrube ? teacher.tecrube + ' il' : '-'}</td>
                <td class="text-center">
                    <div class="actions text-center">
                        <a href="#" class="btn btn-sm btn-info view-teacher mr-1" data-id="${teacher.id}" data-toggle="tooltip" title="Bax">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-primary edit-teacher mr-1" data-id="${teacher.id}" data-toggle="tooltip" title="Redaktə et">
                            <i class="fas fa-edit"></i>
                        </a>
                        <div hidden>
                            <a href="#" class="btn btn-sm btn-danger delete-teacher" data-id="${teacher.id}" data-toggle="tooltip" title="Sil">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        `;
        
        tableBody.append(row);
 
        // Increment the counter for the next row
        count++;
    });
    
    // Update pagination info
    const start = (response.page - 1) * response.limit + 1;
    const end = Math.min(start + response.data.length - 1, response.total);
    $("#paginationInfo").text(`${response.total} müəllim`);
    
    // Reinitialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
 }

// Function to render pagination
function renderPagination(response) {
   const pagination = $("#pagination");
   pagination.empty();
   
   // Previous button
   const prevDisabled = response.page <= 1 ? 'disabled' : '';
   pagination.append(`
       <li class="page-item ${prevDisabled}">
           <a class="page-link" href="#" data-page="${response.page - 1}" tabindex="-1">Əvvəlki</a>
       </li>
   `);
   
   // Page numbers
   const totalPages = response.total_pages;
   let startPage = Math.max(1, response.page - 2);
   let endPage = Math.min(totalPages, response.page + 2);
   
   // Ensure we always show 5 pages if possible
   if (endPage - startPage < 4) {
       if (startPage === 1) {
           endPage = Math.min(5, totalPages);
       } else if (endPage === totalPages) {
           startPage = Math.max(1, totalPages - 4);
       }
   }
   
   for (let i = startPage; i <= endPage; i++) {
       const active = i === response.page ? 'active' : '';
       pagination.append(`
           <li class="page-item ${active}">
               <a class="page-link" href="#" data-page="${i}">${i}</a>
           </li>
       `);
   }
   
   // Next button
   const nextDisabled = response.page >= totalPages ? 'disabled' : '';
   pagination.append(`
       <li class="page-item ${nextDisabled}">
           <a class="page-link" href="#" data-page="${response.page + 1}">Sonrakı</a>
       </li>
   `);
   
   // Update current page
   currentPage = response.page;
}

// Handle pagination clicks
$(document).on('click', '.page-link', function(e) {
   e.preventDefault();
   
   if ($(this).parent().hasClass('disabled')) {
       return;
   }
   
   const page = $(this).data('page');
   const search = $("#searchTeacher").val();
   const fenn = $("#filterDepartment").val();
   const status = $("#filterStatus").val();
   
   loadTeachers(page, search, fenn, status);
});

// Handle search and filter
$("#searchTeacher, #filterDepartment, #filterStatus").on('change', function() {
   const search = $("#searchTeacher").val();
   const fenn = $("#filterDepartment").val();
   const status = $("#filterStatus").val();
   
   loadTeachers(1, search, fenn, status);
});

// Reset filters
$('#resetFilters').on('click', function() {
   $('#searchTeacher').val('');
   $('#filterDepartment').val('');
   $('#filterStatus').val('');
   loadTeachers(1, '', '', '');
});

// Open Add Teacher Modal
$('#addTeacherBtn').on('click', function() {
   resetTeacherForm();
   $('#teacherModalLabel').text('Yeni Müəllim Əlavə Et');
   $('#teacherModal').modal('show');
});

// Handle profile image preview
$('#profileImage').on('change', function() {
   const file = this.files[0];
   if (file) {
       const reader = new FileReader();
       reader.onload = function(e) {
           $('#profileImagePreview').attr('src', e.target.result);
       }
       reader.readAsDataURL(file);
       
       // Update file label
       $(this).next('.custom-file-label').html(file.name);
   }
});

// Handle fenn dropdown change
$('#fenn').on('change', function() {
   if ($(this).val() === 'new') {
       $('#teacherModal').modal('hide');
       $('#newSubjectModal').modal('show');
   }
});

// Save new subject
$('#saveNewSubject').on('click', function() {
   const newSubject = $('#newSubjectName').val().trim();
   
   if (!newSubject) {
       alert('Fənn adını daxil edin');
       return;
   }
   
   showLoading();
   
   // Save new subject to database
   $.ajax({
       url: "muellim/fenn_elave_et.php",
       type: "POST",
       data: { fenn_adi: newSubject, csrf_token: getCsrfToken() },
       dataType: "json",
       success: function(response) {
           hideLoading();
           
           if (response.success) {
               // Add new option to fenn dropdown
               $('#fenn').append(`<option value="${newSubject}">${newSubject}</option>`);
               
               // Select the new option
               $('#fenn').val(newSubject);
               
               // Close the modal
               $('#newSubjectModal').modal('hide');
               $('#teacherModal').modal('show');
           } else {
               alert('Xəta baş verdi: ' + response.message);
           }
       },
       error: function(xhr, status, error) {
           hideLoading();
           alert('Server xətası: ' + error);
       }
   });
});

// Reset teacher form
function resetTeacherForm() {
   $('#teacherForm')[0].reset();
   $('#teacherId').val('');
   $('#teacherForm .is-invalid').removeClass('is-invalid');
   $('#profileImagePreview').attr('src', '');
   $('.custom-file-label').html('Profil şəkli seçin');
}

// Validate teacher form
function validateTeacherForm() {
   let isValid = true;
   
   // Reset validation
   $('#teacherForm .is-invalid').removeClass('is-invalid');
   
   // Validate required fields
   $('#teacherForm [required]').each(function() {
       if (!$(this).val()) {
           $(this).addClass('is-invalid');
           isValid = false;
       }
   });
   
   // Validate email format
   const email = $('#email').val();
   if (email && !isValidEmail(email)) {
       $('#email').addClass('is-invalid');
       isValid = false;
   }
   
   return isValid;
}

// Email validation helper
function isValidEmail(email) {
   const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
   return re.test(email);
}


 $('#saveTeacher').on('click', function() {
        if (!validateTeacherForm()) {
            return;
        }

        showLoading();

        const teacherId = $('#teacherId').val();
        const isEdit = teacherId !== '';
        
        const formData = new FormData($('#teacherForm')[0]);
        formData.set('csrf_token', getCsrfToken() || $('#teacherForm [name="csrf_token"]').val());
        
        if (isEdit) {
            formData.append('id', teacherId);
        }

        $.ajax({
            url: isEdit ? "muellim/muellim_redakte_et.php" : "muellim/muellim_elave_et.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#teacherModal').modal('hide');
                    showAlert(response.message, "success");
                    loadTeachers(currentPage);
                } else {
                    showAlert(response.message, "danger");
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('AJAX Error:', xhr.responseText); // Log raw response for debugging
                showAlert("Server xətası: " + (xhr.responseText || error), "danger");
            }
        });
    });

// Load teacher classes
function loadTeacherClasses(teacherId) {
$.ajax({
url: "muellim/muellim_dersler_fetch.php",
type: "GET",
data: { teacher_id: teacherId },
dataType: "json",
success: function(response) {
const classesTable = $("#viewTeacherClasses");
classesTable.empty();

if (response.success && response.data.length > 0) {
   response.data.forEach(function(lesson) {
       classesTable.append(`
           <tr>
               <td>${lesson.sinif}</td>
               <td>${lesson.tarix}</td>
               <td>${lesson.vaxt}</td>
               <td>${lesson.otaq}</td>
           </tr>
       `);
   });
} else {
   classesTable.html('<tr><td colspan="4" class="text-center text-danger">Cari dərs tapılmadı</td></tr>');
}
},
error: function(xhr, status, error) {
console.error("AJAX Error:", status, error, xhr.responseText); // Log actual error
$("#viewTeacherClasses").html(`
   <tr>
       <td colspan="4" class="text-center text-danger">
           Məlumat yüklənərkən xəta baş verdi: ${xhr.responseText || error}
       </td>
   </tr>
`);
}
});
}


// View teacher details
$(document).on('click', '.view-teacher', function(e) {
   e.preventDefault();
   
   const teacherId = $(this).data('id');
   const teacher = teachersData.find(t => t.id == teacherId);
   
   if (teacher) {
       // Fill modal with teacher data
       $('#viewTeacherName').text(formatTeacherName(teacher.username));
       $('#viewTeacherSubject').text(teacher.fenn + ' müəllimi');
       
       // Set profile image
       const profileImage = teacher.profile && teacher.profile !== '' ? 
           '../Uploads/profiles/' + teacher.profile : 
           '';
       $('#viewTeacherImage').attr('src', profileImage);
       
       // Set status badge
       let statusClass = 'badge-secondary';
       let statusText = 'Naməlum';
       
       switch(teacher.active_status) {
           case 'active':
               statusClass = 'badge-success';
               statusText = 'Aktiv';
               break;
           case 'inactive':
               statusClass = 'badge-danger';
               statusText = 'Qeyri-aktiv';
               break;
           case 'onleave':
               statusClass = 'badge-warning';
               statusText = 'Məzuniyyətdə';
               break;
       }
       
       $('#viewTeacherStatus').removeClass('badge-success badge-danger badge-warning badge-secondary')
                 .addClass(statusClass)
                 .text(statusText);
       
       $('#viewTeacherEmail').text(teacher.email || '-');
       $('#viewTeacherPhone').text(teacher.telefon || '-');
       $('#viewTeacherExperience').text(teacher.tecrube ? teacher.tecrube + ' il' : '-');
       $('#viewTeacherStartDate').text(teacher.ise_baslama_tarixi || '-');
       $('#viewTeacherAddress').text(teacher.unvan || '-');
       $('#viewTeacherQualifications').text(teacher.tehsil_ve_ixtisas || '-');
       
       // Store current teacher ID for edit button
       currentTeacherId = teacher.id;
       
       // Load teacher classes
       loadTeacherClasses(teacher.id);
       
       $('#viewTeacherModal').modal('show');
   }
});

// Edit teacher from table
$(document).on('click', '.edit-teacher', function(e) {
   e.preventDefault();
   
   const teacherId = $(this).data('id');
   const teacher = teachersData.find(t => t.id == teacherId);
   
   if (teacher) {
       fillTeacherForm(teacher);
       $('#teacherModalLabel').text('Müəllim Məlumatlarını Redaktə Et');
       $('#teacherModal').modal('show');
   }
});

// Edit teacher from view modal
$('.edit-teacher-from-view').on('click', function() {
   if (currentTeacherId) {
       const teacher = teachersData.find(t => t.id == currentTeacherId);
       
       if (teacher) {
           $('#viewTeacherModal').modal('hide');
           fillTeacherForm(teacher);
           $('#teacherModalLabel').text('Müəllim Məlumatlarını Redaktə Et');
           $('#teacherModal').modal('show');
       }
   }
});

function fillTeacherForm(teacher) {
    resetTeacherForm();

    $('#teacherId').val(teacher.id);
    
    // Split username into ad and soyad
    const usernameParts = teacher.username.split('.');
    const ad = usernameParts[0] || '';
    const soyad = usernameParts[1] || '';
    $('#ad').val(ad);
    $('#soyad').val(soyad);
    
    $('#email').val(teacher.email);
    $('#telefon').val(teacher.telefon);

    // Check if fenn exists in dropdown, if not add it
    if ($('#fenn option[value="' + teacher.fenn + '"]').length === 0 && teacher.fenn) {
        $('#fenn').append(`<option value="${teacher.fenn}">${teacher.fenn}</option>`);
    }
    $('#fenn').val(teacher.fenn);

    $('#active_status').val(teacher.active_status);
    $('#tecrube').val(teacher.tecrube);
    $('#ise_baslama_tarixi').val(teacher.ise_baslama_tarixi);
    $('#unvan').val(teacher.unvan);

    // Check if tehsil_ve_ixtisas exists in dropdown, if not add it
    if ($('#class option[value="' + teacher.tehsil_ve_ixtisas + '"]').length === 0 && teacher.tehsil_ve_ixtisas) {
        $('#class').append(`<option value="${teacher.tehsil_ve_ixtisas}">${teacher.tehsil_ve_ixtisas}</option>`);
    }
    $('#class').val(teacher.tehsil_ve_ixtisas);

    // Set profile image if exists
    if (teacher.profile && teacher.profile !== '') {
        $('#profileImagePreview').attr('src', '../Uploads/profiles/' + teacher.profile);
    }

    $('#teacherModalLabel').text('Müəllim Məlumatlarını Redaktə Et');
    $('#teacherModal').modal('show');
}

// Delete teacher confirmation
$(document).on('click', '.delete-teacher', function(e) {
   e.preventDefault();
   
   const teacherId = $(this).data('id');
   $('#deleteTeacherId').val(teacherId);
   $('#deleteTeacherModal').modal('show');
});

// Confirm delete action
$('#confirmDelete').on('click', function() {
   const teacherId = $('#deleteTeacherId').val();
   
   if (!teacherId) {
       return;
   }
   
   showLoading();
   
   $.ajax({
       url: "muellim/muellim_sil.php",
       type: "POST",
       data: { id: teacherId, csrf_token: getCsrfToken() },
       dataType: "json",
       success: function(response) {
           hideLoading();
           
           if (response.success) {
               $('#deleteTeacherModal').modal('hide');
               showAlert(response.message, "success");
               loadTeachers(currentPage);
           } else {
               showAlert(response.message, "danger");
           }
       },
       error: function(xhr, status, error) {
           hideLoading();
           showAlert("Server xətası: " + error, "danger");
       }
   });
});
});