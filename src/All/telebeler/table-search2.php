<?php
include('db.php');

// Check user role
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'teacher'])) {
    die("Giriş icazəsi yoxdur.");
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$classFilter = isset($_GET['class']) ? trim($_GET['class']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;
$teacherUsername = $_SESSION['username'];

if (!function_exists('executePreparedQuery')) {
    function executePreparedQuery($conn, $sql, $types = '', $params = []) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Query prepare failed.");
        }

        if ($types !== '') {
            $refs = [];
            foreach ($params as $key => $value) {
                $refs[$key] = &$params[$key];
            }
            $stmt->bind_param($types, ...$refs);
        }

        $stmt->execute();
        return $stmt->get_result();
    }
}

// Build where clause
$whereClause = "WHERE 1=1";
$whereTypes = '';
$whereParams = [];
if ($_SESSION['role'] === 'teacher') {
    $whereClause .= " AND JSON_CONTAINS(muellim_adi, JSON_QUOTE(?), '$')";
    $whereTypes .= 's';
    $whereParams[] = $teacherUsername;
}
if (!empty($search)) {
    $searchLike = '%' . $search . '%';
    $whereClause .= " AND (username LIKE ? OR sinif LIKE ? OR poct LIKE ?)";
    $whereTypes .= 'sss';
    $whereParams[] = $searchLike;
    $whereParams[] = $searchLike;
    $whereParams[] = $searchLike;
}
if (!empty($classFilter)) {
    $whereClause .= " AND sinif = ?";
    $whereTypes .= 's';
    $whereParams[] = $classFilter;
}
if (!empty($statusFilter)) {
    $whereClause .= " AND active_status = ?";
    $whereTypes .= 's';
    $whereParams[] = $statusFilter;
}

// Statistics queries
$totalStudentsQuery = "SELECT COUNT(*) AS total FROM telebeler $whereClause";
$activeStudentsQuery = "SELECT COUNT(*) AS active FROM telebeler $whereClause AND active_status = ?";
$genderRatioQuery = "SELECT cins, COUNT(*) AS count FROM telebeler $whereClause GROUP BY cins";
$currentYear = date('Y');
$previousYear = date('Y') - 1;
$averageGradeQuery = "SELECT AVG(CAST(orta_bal AS DECIMAL(5,2))) AS average FROM telebeler $whereClause AND orta_bal REGEXP '^[0-9]+\\.?[0-9]*$' AND YEAR(created_at) = ?";
$previousAverageGradeQuery = "SELECT AVG(CAST(orta_bal AS DECIMAL(5,2))) AS average FROM telebeler $whereClause AND orta_bal REGEXP '^[0-9]+\\.?[0-9]*$' AND YEAR(created_at) = ?";
$currentMonth = date('Y-m');
$newStudentsThisMonthQuery = "SELECT COUNT(*) AS new_students FROM telebeler $whereClause AND DATE_FORMAT(created_at, '%Y-%m') = ?";

$totalStudentsResult = executePreparedQuery($conn, $totalStudentsQuery, $whereTypes, $whereParams);
$activeStudentsResult = executePreparedQuery($conn, $activeStudentsQuery, $whereTypes . 's', array_merge($whereParams, ['active']));
$genderRatioResult = executePreparedQuery($conn, $genderRatioQuery, $whereTypes, $whereParams);
$averageGradeResult = executePreparedQuery($conn, $averageGradeQuery, $whereTypes . 'i', array_merge($whereParams, [$currentYear]));
$previousAverageGradeResult = executePreparedQuery($conn, $previousAverageGradeQuery, $whereTypes . 'i', array_merge($whereParams, [$previousYear]));
$newStudentsThisMonthResult = executePreparedQuery($conn, $newStudentsThisMonthQuery, $whereTypes . 's', array_merge($whereParams, [$currentMonth]));

$totalStudents = mysqli_fetch_assoc($totalStudentsResult)['total'];
$activeStudents = mysqli_fetch_assoc($activeStudentsResult)['active'];
$currentAverageGradeRow = mysqli_fetch_assoc($averageGradeResult);
$previousAverageGradeRow = mysqli_fetch_assoc($previousAverageGradeResult);
$currentAverageGrade = $currentAverageGradeRow['average'] ? round($currentAverageGradeRow['average'], 1) : 0;
$previousAverageGrade = $previousAverageGradeRow['average'] ? round($previousAverageGradeRow['average'], 1) : 0;
$newStudentsThisMonth = mysqli_fetch_assoc($newStudentsThisMonthResult)['new_students'];

$genderCounts = [];
while ($row = mysqli_fetch_assoc($genderRatioResult)) {
    $genderCounts[$row['cins']] = $row['count'];
}
$maleCount = $genderCounts['0'] ?? 0; // Male (Kişi)
$femaleCount = $genderCounts['1'] ?? 0; // Female (Qadın)
$totalGender = $maleCount + $femaleCount;
$malePercentage = $totalGender > 0 ? round(($maleCount / $totalGender) * 100, 1) : 0;
$femalePercentage = $totalGender > 0 ? round(($femaleCount / $totalGender) * 100, 1) : 0;
$gradeIncrease = $currentAverageGrade - $previousAverageGrade;

// Build queries for student list
$countQuery = "SELECT COUNT(*) as total FROM telebeler $whereClause";
$dataQuery = "SELECT id, username, sinif, years, cins, poct, orta_bal, active_status, muellim_adi FROM telebeler $whereClause";

// Execute count query
$countResult = executePreparedQuery($conn, $countQuery, $whereTypes, $whereParams);
$totalRow = mysqli_fetch_assoc($countResult);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Execute data query with pagination
$dataQuery .= " ORDER BY id DESC LIMIT ?, ?";
$query = executePreparedQuery($conn, $dataQuery, $whereTypes . 'ii', array_merge($whereParams, [$offset, $recordsPerPage]));

// Fetch distinct classes for filter
$classesQuery = mysqli_query($conn, "SELECT DISTINCT sinif FROM telebeler ORDER BY sinif");
$classes = [];
while ($classRow = mysqli_fetch_assoc($classesQuery)) {
    $classes[] = $classRow['sinif'];
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Tələbələr</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-user-graduate fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Ümumi Tələbələr</h6>
                        <h3 class="stat-number"><?php echo $totalStudents; ?></h3>
                        <p class="mb-0 small">Bu ay: +<?php echo $newStudentsThisMonth; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-user-check fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Aktiv Tələbələr</h6>
                        <h3 class="stat-number"><?php echo $activeStudents; ?></h3>
                        <p class="mb-0 small">Davamiyyət: <?php echo $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100) . '%' : '0%'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-venus-mars fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Gender Nisbəti</h6>
                        <h3 class="stat-number"><?php echo $femalePercentage; ?>% / <?php echo $malePercentage; ?>%</h3>
                        <p class="mb-0 small">Qadın <?php echo $femaleCount; ?> / Kişi <?php echo $maleCount; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card stat-card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="icon-box">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                        <h6 class="stat-title">Orta Bal</h6>
                        <h3 class="stat-number"><?php echo $currentAverageGrade; ?></h3>
                        <p class="mb-0 small">Keçən ildən: <?php echo $gradeIncrease >= 0 ? '+' . $gradeIncrease : $gradeIncrease; ?></p>
                    </div>
                </div>
            </div>
        </div>
     <br>
                <h2>Tələbələr Siyahısı</h2>


        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <input type="text" class="form-control" placeholder="Tələbə axtar..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <select class="form-control" name="class">
                                        <option value="">Bütün Siniflər</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $classFilter === $class ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <select class="form-control" name="status">
                                        <option value="">Bütün Statuslar</option>
                                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktiv</option>
                                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Qeyri-aktiv</option>
                                        <option value="graduate" <?php echo $statusFilter === 'graduate' ? 'selected' : ''; ?>>Məzun</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-secondary btn-block" id="resetFilters">
                                        <i class="fas fa-redo-alt mr-1"></i> Sıfırla
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="list-tab" data-toggle="tab" href="#list" role="tab">
                            <i class="fas fa-list mr-2"></i> Tələbə Siyahısı
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="grid-tab" data-toggle="tab" href="#grid" role="tab">
                            <i class="fas fa-th-large mr-2"></i> Şəbəkə Görünüşü
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="performance-tab" data-toggle="tab" href="#performance" role="tab">
                            <i class="fas fa-chart-bar mr-2"></i> Akademik Göstəricilər
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-4" id="studentTabsContent">
                    <div class="tab-pane fade show active" id="list" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ad Soyad</th>
                                        <th>Sinif</th>
                                        <th>Yaş</th>
                                        <th>Cins</th>
                                        <th>Müəllim</th>
                                        <th>Orta Bal</th>
                                        <th>Status</th>
                                        <th class="text-center">Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody id="studentTableBody">
                                    <?php
                                    if (mysqli_num_rows($query) == 0) {
                                        echo "<tr><td colspan='9' class='text-center'>Heç bir tələbə tapılmadı</td></tr>";
                                    } else {
                                        $count = $totalRecords - $offset;
                                        while ($row = mysqli_fetch_assoc($query)) {
                                            $statusClass = ['active' => 'success', 'inactive' => 'danger', 'graduate' => 'info'][strtolower($row['active_status'])] ?? 'secondary';
                                            $statusLabel = ['active' => 'Aktiv', 'inactive' => 'Qeyri-aktiv', 'graduate' => 'Məzun'][strtolower($row['active_status'])] ?? '';
                                            $cinsLabel = $row['cins'] == 0 ? 'Kişi' : ($row['cins'] == 1 ? 'Qadın' : '');

                                            $id = htmlspecialchars($row['id'], ENT_QUOTES);
                                            $username = htmlspecialchars($row['username'], ENT_QUOTES);
                                            $sinif = htmlspecialchars($row['sinif'], ENT_QUOTES);
                                            $yas = htmlspecialchars($row['years'], ENT_QUOTES);
                                            $ortaBal = htmlspecialchars($row['orta_bal'], ENT_QUOTES);

                                            $muellimArray = json_decode($row['muellim_adi'], true);
                                            $muellimDisplay = 'Müəllim təyin edilməyib';
                                            if (is_array($muellimArray)) {
                                                $validMuellim = array_filter($muellimArray, fn($value) => !empty($value) && $value !== '""');
                                                if (!empty($validMuellim)) {
                                                    $muellimDisplay = implode(', ', array_map(fn($value) => htmlspecialchars($value, ENT_QUOTES), $validMuellim));
                                                }
                                            }

                                            echo "<tr>
                                                <td>$count</td>
                                                <td>$username</td>
                                                <td>$sinif</td>
                                                <td>$yas</td>
                                                <td>$cinsLabel</td>
                                                <td style='font-size:15px;'>$muellimDisplay</td>
                                                <td>$ortaBal</td>
                                                <td><span class='badge badge-$statusClass'>$statusLabel</span></td>
                                                <td class='text-center'>
                                                    <a style='margin-right:5px;' href='#' class='btn btn-sm btn-info view-student' data-id='$id' data-toggle='tooltip' title='Bax'>
                                                        <i class='fas fa-eye'></i>
                                                    </a>
                                                    <a style='margin-right:5px;' href='#' class='btn btn-sm btn-primary edit-student' data-id='$id' data-toggle='tooltip' title='Redaktə et'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                    <div hidden>
                                                        <a href='javascript:void(0);' class='btn btn-sm btn-danger' onclick='openDeleteModal($id)' title='Sil'>
                                                            <i class='fas fa-trash'></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>";
                                            $count--;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <form id="deleteForm" style="display:none;">
                                <input type="hidden" name="id" id="delete-id">
                            </form>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <p class="mb-0"><?php echo $totalRecords; ?> tələbə</p>
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-end mb-0">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo urlencode($classFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">Əvvəlki</a>
                                        </li>
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo urlencode($classFilter); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo urlencode($classFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">Sonrakı</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="grid" role="tabpanel">LATER</div>
                    <div class="tab-pane fade" id="performance" role="tabpanel">LATER</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            const filterForm = $('#filterForm');
            const searchInput = $('#filterForm input[name="search"]');
            const classSelect = $('#filterForm select[name="class"]');
            const statusSelect = $('#filterForm select[name="status"]');
            const resetButton = $('#resetFilters');

            function debounce(func, delay) {
                let timeout;
                return function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, arguments), delay);
                };
            }

            function fetchStudents(page) {
                const search = searchInput.val();
                const classFilter = classSelect.val();
                const statusFilter = statusSelect.val();
                window.location.href = `?page=${page}&search=${encodeURIComponent(search)}&class=${encodeURIComponent(classFilter)}&status=${encodeURIComponent(statusFilter)}`;
            }

            searchInput.on('input', debounce(() => fetchStudents(1), 500));
            classSelect.on('change', () => fetchStudents(1));
            statusSelect.on('change', () => fetchStudents(1));
            resetButton.on('click', () => {
                searchInput.val('');
                classSelect.val('');
                statusSelect.val('');
                fetchStudents(1);
            });

            $('[data-toggle="tooltip"]').tooltip();

            $('.view-student').on('click', function() {
                const studentId = $(this).data('id');
                if (!studentId) {
                    alert('Tələbə ID tapılmadı.');
                    return;
                }
                $.ajax({
                    url: 'telebeler/get_student_by_id.php',
                    type: 'GET',
                    data: { id: studentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const student = response.data;
                            const modal = $('#viewStudentModal');
                            modal.find('.profile-name').text(student.profile_info.name);
                            modal.find('.profile-id').text(`ID: ${student.profile_info.id}`);
                            modal.find('.profile-image').attr('src', student.profile_info.photo && student.profile_info.photo !== '' 
                                ? (student.profile_info.photo.includes('Uploads/') ? student.profile_info.photo : `telebeler/${student.profile_info.photo}`)
                                : 'Uploads/68061c450972f.png');
                            modal.find('.contact-item:eq(0)').html(`<i class="fas fa-envelope mr-1"></i> ${student.profile_info.email || 'N/A'}`);
                            modal.find('.contact-item:eq(1)').html(`<i class="fas fa-phone mr-1"></i> ${student.profile_info.phone || 'N/A'}`);
                            modal.find('.badge').removeClass().addClass(`badge badge-${student.profile_info.status_class}`).text(student.profile_info.status);
                            modal.find('td[data-field="dogum_tarixi"]').text(student.personal_info.dogum_tarixi || 'N/A');
                            modal.find('td[data-field="years"]').text(student.personal_info.years || 'N/A');
                            modal.find('td[data-field="cins"]').text(student.personal_info.cins || 'N/A');
                            modal.find('td[data-field="unvan"]').text(student.personal_info.unvan || 'N/A');
                            modal.find('td[data-field="vetandasliq"]').text(student.personal_info.vetandasliq || 'N/A');
                            modal.find('td[data-field="sinif"]').text(student.academic_info.sinif || 'N/A');
                            modal.find('td[data-field="qebul_tarixi"]').text(student.academic_info.qebul_tarixi || 'N/A');
                            modal.find('td[data-field="orta_bal"]').text(student.academic_info.orta_bal || 'N/A');
                            modal.find('td[data-field="davamiyyet"]').text(student.academic_info.davamiyyet || 'N/A');
                            modal.find('td[data-field="status"]').text(student.academic_info.status || 'N/A');
                            modal.find('td[data-field="muellim_adi"]').text(student.academic_info.muellim_adi || 'N/A');
                            modal.find('td[data-field="ixtisas_adi"]').text(student.academic_info.ixtisas_adi || 'N/A');
                            modal.find('td[data-field="ata"]').text(student.parent_info.ata || 'N/A');
                            modal.find('td[data-field="elaqe_nomre_ata"]').text(student.parent_info.elaqe_nomre_ata || 'N/A');
                            modal.find('td[data-field="ana"]').text(student.parent_info.ana || 'N/A');
                            modal.find('td[data-field="elaqe_nomre_ana"]').text(student.parent_info.elaqe_nomre_ana || 'N/A');
                            modal.find('td[data-field="riyaziyyat"]').text(student.grades.riyaziyyat || 'N/A');
                            modal.find('td[data-field="fizika"]').text(student.grades.fizika || 'N/A');
                            modal.find('td[data-field="kimya"]').text(student.grades.kimya || 'N/A');
                            modal.find('td[data-field="biologiya"]').text(student.grades.biologiya || 'N/A');
                            modal.find('td[data-field="tarix"]').text(student.grades.tarix || 'N/A');
                            modal.find('td[data-field="edebiyyat"]').text(student.grades.edebiyyat || 'N/A');
                            modal.find('p[data-field="qeyd"]').text(student.notes || 'Qeyd yoxdur');
                            modal.modal('show');
                        } else {
                            alert(response.message || 'Xəta baş verdi');
                        }
                    },
                    error: function(xhr) {
                        alert('Məlumatlar yüklənərkən xəta baş verdi: ' + xhr.status + ' ' + xhr.statusText);
                    }
                });
            });

            $('.edit-student').on('click', function() {
                const studentId = $(this).data('id');
                const modal = $('#editStudentModal');
                const form = $('#editStudentForm');
                form[0].reset();
                modal.find('input[name="id"]').val('');
                modal.find('.form-control').removeClass('is-invalid');

                $.ajax({
                    url: 'telebeler/get_student_by_id.php',
                    type: 'GET',
                    data: { id: studentId },
                    dataType: 'json',
                    cache: false,
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            const student = response.data;
                            const nameParts = student.profile_info.name.trim().split('.');
                            modal.find('#firstName').val(nameParts[0] || '');
                            modal.find('#lastName').val(nameParts.length > 1 ? nameParts[1] : '');
                            modal.find('#email').val(student.profile_info.email || '');
                            modal.find('#phone').val(student.profile_info.phone || '');
                            modal.find('#dogum_tarixi').val(student.personal_info.dogum_tarixi || '');
                            modal.find('#yas').val(student.personal_info.years || '');
                            modal.find('#gender').val(student.personal_info.cins === 'Kişi' ? 'male' : student.personal_info.cins === 'Qadın' ? 'female' : '');
                            modal.find('#address').val(student.personal_info.unvan || '');
                            modal.find('#class').val(student.academic_info.sinif || '');
                            modal.find('#qebul_tarixi').val(student.academic_info.qebul_tarixi || '');
                            modal.find('#status').val({'Aktiv': 'active', 'Qeyri-aktiv': 'inactive', 'Məzun': 'graduate'}[student.profile_info.status] || '');
                            modal.find('#ata').val(student.parent_info.ata || '');
                            modal.find('#ata_nomre').val(student.parent_info.elaqe_nomre_ata || '');
                            modal.find('#ana').val(student.parent_info.ana || '');
                            modal.find('#ana_nomre').val(student.parent_info.elaqe_nomre_ana || '');
                            modal.find('input[name="id"]').val(student.profile_info.id);
                            modal.modal('show');
                        } else {
                            alert(response.message || 'Failed to fetch student data.');
                        }
                    },
                    error: function() {
                        alert('An error occurred while fetching student data.');
                    }
                });
            });

            $('.close-modal').on('click', () => $('#viewStudentModal').modal('hide'));
        });
    </script>
</body>
</html>