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
    <title>Mövzular</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="stylesheet" href="movzular/imtahan-system.css">
    <link rel="stylesheet" href="movzular/css.css">
    <link rel="stylesheet" href="movzular/tables-modals.css">
    <style>
        #examTopics { min-height:50px; max-height:auto; overflow:auto; }
        .lds-ripple { display:inline-block; position:relative; width:80px; height:80px; }
        .lds-ripple div { position:absolute; border:4px solid #3182ce; opacity:1; border-radius:50%; animation:lds-ripple 1s cubic-bezier(0,0.2,0.8,1) infinite; }
        .lds-ripple div:nth-child(2) { animation-delay:-0.5s; }
        @keyframes lds-ripple { 0% { top:36px; left:36px; width:0; height:0; opacity:1; } 100% { top:0; left:0; width:72px; height:72px; opacity:0; } }
        :root { --primary-color:#1d6a9d; --primary-light:#2479b1; --primary-dark:#0d5a8d; --secondary-color:#6c757d; --success-color:#28a745; --danger-color:#dc3545; --warning-color:#ffc107; --info-color:#17a2b8; --light-color:#f8f9fa; --dark-color:#343a40; --white-color:#ffffff; --body-bg:#f5f5f5; --card-bg:#ffffff; --sidebar-width:250px; --header-height:60px; --border-radius:8px; --box-shadow:0 2px 10px rgba(0,0,0,0.1); --transition:all 0.3s ease; }
        .card { background-color:var(--card-bg); border-radius:var(--border-radius); box-shadow:var(--box-shadow); margin-bottom:20px; overflow:hidden; transition:var(--transition); }
        .card:hover { box-shadow:0 5px 15px rgba(0,0,0,0.1); transform:translateY(-2px); }
        .card-header { padding:15px 20px; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between; }
        .card-title { font-size:1.2rem; font-weight:500; margin:0; }
        .card-body { padding:20px; }
        .card-footer { padding:15px 20px; border-top:1px solid #eee; background-color:rgba(0,0,0,0.02); }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:20px; }
        .stat-card { padding:20px; display:flex; align-items:center; }
        .stat-icon { width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-right:15px; }
        .stat-icon.primary { background-color:rgba(29,106,157,0.1); color:var(--primary-color); }
        .stat-icon.success { background-color:rgba(40,167,69,0.1); color:var(--success-color); }
        .stat-icon.warning { background-color:rgba(255,193,7,0.1); color:var(--warning-color); }
        .stat-icon.info { background-color:rgba(23,162,184,0.1); color:var(--info-color); }
        .stat-icon.danger { background-color:rgba(220,53,69,0.1); color:var(--danger-color); }
        .stat-info { flex:1; }
        .stat-value { font-size:1.8rem; font-weight:700; margin:0; line-height:1.2; }
        .stat-label { color:var(--secondary-color); font-size:0.9rem; }
        .stat-card-clickable { cursor:pointer; transition:transform 0.2s ease, box-shadow 0.2s ease; }
        .stat-card-clickable:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }
        .stat-card-clickable:focus { outline:2px solid var(--primary-color); outline-offset:2px; }
        .badge { display:inline-block; padding:3px 8px; font-size:0.75rem; font-weight:500; border-radius:20px; }
        .badge-primary { background-color:rgba(29,106,157,0.1); color:var(--primary-color); }
        .badge-success { background-color:rgba(124, 135, 152, 0.14); color:#7c8798; }
        .badge-warning { background-color:rgba(255,193,7,0.1); color:var(--warning-color); }
        .badge-danger { background-color:rgba(220,53,69,0.1); color:var(--danger-color); }
        .badge-info { background-color:rgba(23,162,184,0.1); color:var(--info-color); }
        .badge-secondary { background-color:rgba(108,117,125,0.1); color:var(--secondary-color); }
        .btn { display:inline-block; padding:8px 15px; border-radius:4px; border:none; font-size:0.9rem; font-weight:500; cursor:pointer; transition:var(--transition); text-decoration:none; }
        .btn-sm { padding:5px 10px; font-size:0.8rem; }
        .btn-lg { padding:10px 20px; font-size:1rem; }
        .btn-primary { background-color:var(--primary-color); color:var(--white-color); }
        .btn-primary:hover { background-color:var(--primary-dark); }
        .btn-secondary { background-color:var(--secondary-color); color:var(--white-color); }
        .btn-secondary:hover { background-color:#5a6268; }
        .btn-success { background-color:var(--success-color); color:var(--white-color); }
        .btn-success:hover { background-color:#218838; }
        .btn-danger { background-color:var(--danger-color); color:var(--white-color); }
        .btn-danger:hover { background-color:#c82333; }
        .btn-warning { background-color:var(--warning-color); color:#212529; }
        .btn-warning:hover { background-color:#e0a800; }
        .btn-info { background-color:var(--info-color); color:var(--white-color); }
        .btn-info:hover { background-color:#138496; }
        .btn-light { background-color:var(--light-color); color:#212529; }
        .btn-light:hover { background-color:#e2e6ea; }
        .btn-dark { background-color:var(--dark-color); color:var(--white-color); }
        .btn-dark:hover { background-color:#23272b; }
        .btn-outline-primary { background-color:transparent; border:1px solid var(--primary-color); color:var(--primary-color); }
        .btn-outline-primary:hover { background-color:var(--primary-color); color:var(--white-color); }
        .btn-icon { width:36px; height:36px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; }
        .action-buttons { display:flex; gap:5px; }
        .form-group { margin-bottom:15px; }
        .form-label { display:block; margin-bottom:5px; font-weight:500; }
        .form-control { width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px; outline:none; transition:var(--transition); }
        .form-control:focus { border-color:var(--primary-color); box-shadow:0 0 0 2px rgba(29,106,157,0.2); }
        .form-select { width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px; outline:none; transition:var(--transition); appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23343a40' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; background-size:16px 12px; }
        .form-select:focus { border-color:var(--primary-color); box-shadow:0 0 0 2px rgba(29,106,157,0.2); }
        .form-check { display:flex; align-items:center; margin-bottom:10px; }
        .form-check-input { margin-right:10px; }
        .form-check-label { cursor:pointer; }
        .tabs { display:flex; border-bottom:1px solid #eee; margin-bottom:20px; }
        .tab { padding:10px 15px; cursor:pointer; border-bottom:2px solid transparent; transition:var(--transition); }
        .tab:hover { color:var(--primary-color); }
        .tab.active { color:var(--primary-color); border-bottom-color:var(--primary-color); }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
        .file-upload { border:2px dashed #ddd; border-radius:var(--border-radius); padding:30px; text-align:center; transition:var(--transition); cursor:pointer; }
        .file-upload:hover { border-color:var(--primary-color); }
        .file-upload-icon { font-size:2rem; color:var(--secondary-color); margin-bottom:10px; }
        .file-upload-text { margin-bottom:10px; }
        .file-upload-input { display:none; }
        .file-list { margin-top:20px; }
        .file-item { display:flex; align-items:center; padding:10px; border:1px solid #eee; border-radius:4px; margin-bottom:10px; }
        .file-icon { font-size:1.5rem; margin-right:15px; color:var(--secondary-color); }
        .file-info { flex:1; }
        .file-name { font-weight:500; margin-bottom:5px; }
        .file-meta { font-size:0.8rem; color:var(--secondary-color); }
        .file-actions { display:flex; gap:5px; }
        .attendance-table { width:100%; border-collapse:collapse; }
        .attendance-table th, .attendance-table td { padding:10px; text-align:center; border:1px solid #eee; }
        .attendance-table th { background-color:rgba(0,0,0,0.02); }
        .attendance-status { width:20px; height:20px; border-radius:50%; display:inline-block; }
        .status-present { background-color:var(--success-color); }
        .status-absent { background-color:var(--danger-color); }
        .status-excused { background-color:var(--warning-color); }
        .question-item { border:1px solid #eee; border-radius:var(--border-radius); padding:15px; margin-bottom:15px; }
        .question-header { display:flex; justify-content:space-between; margin-bottom:10px; }
        .question-meta { display:flex; gap:10px; font-size:0.8rem; color:var(--secondary-color); }
        .question-text { margin-bottom:10px; }
        .question-options { margin-top:10px; }
        .question-option { display:flex; align-items:center; margin-bottom:5px; }
        .question-option-correct { color:var(--success-color); font-weight:500; }
        .section { display:none; }
        .section.active { display:block; }
        .d-flex { display:flex; }
        .align-items-center { align-items:center; }
        .justify-content-between { justify-content:space-between; }
        .justify-content-end { justify-content:flex-end; }
        .flex-column { flex-direction:column; }
        .gap-10 { gap:10px; }
        .gap-20 { gap:20px; }
        .mb-10 { margin-bottom:10px; }
        .mb-20 { margin-bottom:20px; }
        .mt-10 { margin-top:10px; }
        .mt-20 { margin-top:20px; }
        .text-center { text-align:center; }
        .text-right { text-align:right; }
        .text-primary { color:var(--primary-color); }
        .text-success { color:var(--success-color); }
        .text-danger { color:var(--danger-color); }
        .text-warning { color:var(--warning-color); }
        .text-info { color:var(--info-color); }
        .text-secondary { color:var(--secondary-color); }
        .fw-bold { font-weight:700; }
        .fw-medium { font-weight:500; }
        .fs-small { font-size:0.8rem; }
        .fs-large { font-size:1.2rem; }
        .ql-container { min-height:150px; max-height:400px; overflow-y:auto; }
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
        <div class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link active" data-section="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="sidebar-menu-text">İdarə Paneli</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link" data-section="topics">
                        <i class="fas fa-book"></i>
                        <span class="sidebar-menu-text">Mövzular</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link" data-section="groups">
                        <i class="fas fa-users"></i>
                        <span class="sidebar-menu-text">Qruplar</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link" data-section="materials">
                        <i class="fas fa-file-alt"></i>
                        <span class="sidebar-menu-text">Materiallar</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link" data-section="assignments">
                        <i class="fas fa-tasks"></i>
                        <span class="sidebar-menu-text">Tapşırıqlar</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link" data-section="journal">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="sidebar-menu-text">Elektron Jurnal</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link" data-section="exams">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="sidebar-menu-text">İmtahanlar</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" class="sidebar-menu-link" data-section="question-bank">
                        <i class="fas fa-question-circle"></i>
                        <span class="sidebar-menu-text">Sual Bankı</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="section active" id="dashboard">    
            <?php include('movzular/idare_panel/modals.php'); ?>
            <?php include('movzular/movzular/modals.php'); ?>
            <?php include('movzular/qruplar/modals.php'); ?>
            <?php include('movzular/file/materials-section.php'); ?>
            <?php include('movzular/tapsiriq/modals.php'); ?>
            <?php include('movzular/davamiyyet/modals.php'); ?>
            <?php include('movzular/imtahan/modals.php'); ?>
            <?php include('movzular/suallar/modals.php'); ?>
        </div>
    </div>
    <script src="movzular/script.js"></script>
    <script>         
        const styles = `
            .spinner { width:40px; height:40px; margin-bottom:10px; border:4px solid rgba(255,255,255,0.3); border-radius:50%; border-top:4px solid #fff; animation:spin 1s linear infinite; }
            @keyframes spin { 0% { transform:rotate(0deg); } 100% { transform:rotate(360deg); } }
            .modal-custom { max-width:1200px; width:100%; }
            .modal-content { border-radius:8px; }
            .modal-header { background:#f8f9fa; border-bottom:1px solid #dee2e6; }
            .modal-body { padding:1.5rem; }
            .modal-footer { border-top:1px solid #dee2e6; padding:1rem; }
            .modal-tabs { display:flex; border-bottom:2px solid #dee2e6; margin-bottom:1.5rem; }
            .modal-tab { flex:1; padding:0.75rem 1rem; text-align:center; cursor:pointer; background:#fff; transition:all 0.3s ease; border-bottom:2px solid transparent; }
            .modal-tab.active { background:#e9ecef; border-bottom:2px solid #007bff; font-weight:bold; }
            .modal-tab:hover { background:#f1f3f5; }
            .tab-content { display:none; }
            .tab-content.active { display:block; }
            .form-group { margin-bottom:1rem; }
            .form-label { font-weight:500; margin-bottom:0.5rem; }
            .form-control, .form-select { border-radius:4px; }
            .d-flex.gap-10 { gap:10px; }
            .quill-editor { min-height:200px; background:#fff; border:1px solid #ced4da; border-radius:4px; }
            .ql-toolbar { border-top-left-radius:4px; border-top-right-radius:4px; border:1px solid #ced4da; border-bottom:none; }
            .ql-container { border-bottom-left-radius:4px; border-bottom-right-radius:4px; border:1px solid #ced4da; }
            .question-item { align-items:center; gap:10px; margin:10px 0; }
            .question-image-container { position:relative; display:inline-block; }
            .question-image { width:50px; height:50px; object-fit:cover; margin-left:5px; border-radius:4px; cursor:pointer; transition:opacity 0.3s ease; }
            .question-image:hover { opacity:0.7; }
            .magnifier-icon { position:absolute; margin-left:3px; top:50%; left:50%; transform:translate(-50%,-50%); font-size:16px; color:#fff; opacity:0; transition:opacity 0.3s ease; pointer-events:none; }
            .question-image-container:hover .magnifier-icon { opacity:1; }
            #fullScreenImageOverlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:1060; display:flex; align-items:center; justify-content:center; }
            .full-screen-image-container { position:relative; max-width:90%; max-height:90%; }
            .full-screen-image { max-width:100%; max-height:100%; object-fit:contain; }
            .close-full-screen { position:absolute; top:-25px; right:-25px; font-size:24px; color:#fff; cursor:pointer; }
            .fenn-folder { margin-bottom:10px; }
            .fenn-header { display:flex; align-items:center; padding:8px; background:#f8f9fa; border-radius:4px; cursor:pointer; transition:background-color 0.2s ease; }
            .fenn-header:hover { background:#e9ecef; }
            .toggle-icon { margin-right:8px; font-size:14px; color:#007bff; }
            .fenn-title { font-weight:500; }
            .fenn-topics { padding-left:20px; padding-top:8px; transition:all 0.3s ease; }
            .topic-item { margin-bottom:6px; }
            @media (max-width:576px) {
            .modal-custom { max-width:100vw; margin:0; }
            .modal-content { border-radius:0; border:none; }
            .modal-tabs { flex-direction:column; }
            .modal-tab { flex:none; border-bottom:1px solid #dee2e6; }
            .modal-tab.active { border-bottom:2px solid #007bff; }
            .modal-body { padding:1rem; }
            .question-image { width:40px; height:40px; }
            .close-full-screen { top:-20px; right:-20px; font-size:20px; }
            }
        `;
        document.head.insertAdjacentHTML('beforeend', `<style>${styles}</style>`);
        function openModalForInsertWithJs(tabName = 'examInfo') {
            const existingModal = document.getElementById('dynamicModal');
            if (existingModal) existingModal.remove();
            const modalHTML = `
                <div class="modal fade" id="dynamicModal" tabindex="-1" aria-labelledby="dynamicModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-custom">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title" id="dynamicModalLabel">Yeni İmtahan Əlavə Et</h3>
                                <button hidden type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="dynamicExamForm">
                                    <input type="hidden" id="examId" name="examId">
                                    <div class="modal-tabs" id="dynamicTabs">
                                        <div class="modal-tab ${tabName === 'examInfo' ? 'active' : ''}" data-tab="examInfo">İmtahan Məlumatları</div>
                                        <div class="modal-tab ${tabName === 'examQuestions' ? 'active' : ''}" data-tab="examQuestions">Suallar</div>
                                        <div class="modal-tab ${tabName === 'examGroups' ? 'active' : ''}" data-tab="examGroups">Qruplar</div>
                                    </div>
                                    <div class="tab-content ${tabName === 'examInfo' ? 'active' : ''}" id="examInfo">
                                        <div class="form-group">
                                            <label class="form-label" for="examName">İmtahan Adı</label>
                                            <input type="text" class="form-control" id="examName" name="exam_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Fənn</label>
                                            <div id="examSubjects">
                                                <?php
                                                include('db.php');
                                                $sql = "SELECT ixtisas_adi, id FROM ixtisas";
                                                $result = $conn->query($sql);
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo '<div class="form-check">';
                                                        echo '<input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="fenn_' . htmlspecialchars($row['ixtisas_adi']) . '" name="fenn_adi[]" value="' . htmlspecialchars($row['ixtisas_adi']) . '" data-fenn-id="' . $row['id'] . '" onchange="updateExamTopics()">';
                                                        echo '<label class="form-check-label" for="fenn_' . htmlspecialchars($row['ixtisas_adi']) . '">' . htmlspecialchars($row['ixtisas_adi']) . '</label>';
                                                        echo '</div>';
                                                    }
                                                } else {
                                                    echo '<p>No subjects found.</p>';
                                                }
                                                $conn->close();
                                                ?>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="sinif">Sinif</label>
                                            <input type="text" class="form-control" id="sinif" name="sinif" placeholder="Məsələn, 10A">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examDescription">Təsvir</label>
                                            <div id="examDescription" class="quill-editor"></div>
                                            <input type="hidden" id="examDescriptionHidden" name="description">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examDate">Tarix və Saat</label>
                                            <input type="datetime-local" class="form-control" id="examDate" name="exam_date" step="60" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examDuration">Müddət (dəqiqə)</label>
                                            <input type="number" class="form-control" id="examDuration" name="duration" min="1" value="45" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examPassingScore">Keçid Balı (%)</label>
                                            <input type="number" class="form-control" id="examPassingScore" name="passing_score" min="0" max="100" value="60" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examStatus">Status</label>
                                            <select class="form-select" id="examStatus" name="status" required>
                                                <option value="upcoming" selected>Gələcək</option>
                                                <option value="completed">Tamamlanmış</option>
                                                <option value="active">Aktiv</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="tab-content ${tabName === 'examQuestions' ? 'active' : ''}" id="examQuestions">
                                        <div class="form-group">
                                            <label class="form-label">Mövzular</label>
                                            <div id="examTopics">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Sual Seçimi</label>
                                            <select class="form-select" id="examQuestionSelection" name="sual_secimi" onchange="toggleQuestionSelection()">
                                                <option value="manual">Əl ilə Seç</option>
                                                <option value="random">Təsadüfi Seç</option>
                                            </select>
                                        </div>
                                        <div id="manualQuestionSelection" style="display: block;">
                                            <div class="form-group">
                                                <label class="form-label">Suallar</label>
                                                <div class="d-flex form-group">
                                                    <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="selectAllQuestions">
                                                    <label class="form-check-label" for="selectAllQuestions">Hamısını Seç</label>
                                                </div>
                                                <div id="examQuestionList">
                                                    <?php
                                                    include('db.php');
                                                    $sql = "SELECT id, question_text, question_image FROM sual_banki";
                                                    $result = $conn->query($sql);
                                                    if ($result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            $clean_question_text = strip_tags($row['question_text']);
                                                            echo '<div class="form-check question-item">';
                                                            echo '<input style="border:2px solid rgba(89, 116, 235, 0.73); margin:8px;" type="checkbox" class="form-check-input" name="examQuestions[]" value="' . htmlspecialchars($row['id']) . '">';
                                                            echo '<label class="form-check-label">' . htmlspecialchars($clean_question_text) . '</label>';
                                                            if (!empty($row['question_image'])) {
                                                                echo '<div class="question-image-container">';
                                                                echo '<img src="' . htmlspecialchars($row['question_image']) . '" alt="Question Image" class="question-image" data-image="' . htmlspecialchars($row['question_image']) . '">';
                                                                echo '<span class="magnifier-icon"><i class="fas fa-search-plus"></i></span>';
                                                                echo '</div>';
                                                            }
                                                            echo '</div>';
                                                        }
                                                    } else {
                                                        echo '<p>No questions found.</p>';
                                                    }
                                                    $conn->close();
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="randomQuestionSelection" style="display: none;">
                                            <div class="form-group">
                                                <label class="form-label" for="examQuestionCount">Sual Sayı</label>
                                                <input type="number" class="form-control" id="examQuestionCount" name="sual_sayi" min="1" value="10">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Çətinlik Səviyyəsi</label>
                                                <div class="d-flex gap-10">
                                                    <div class="form-check">
                                                        <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="difficultyEasy" name="cetinlik_seviyyesi[]" value="Easy" checked>
                                                        <label class="form-check-label" for="difficultyEasy">Asan</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="difficultyMedium" name="cetinlik_seviyyesi[]" value="Medium" checked>
                                                        <label class="form-check-label" for="difficultyMedium">Orta</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="difficultyHard" name="cetinlik_seviyyesi[]" value="Hard" checked>
                                                        <label class="form-check-label" for="difficultyHard">Çətin</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-content ${tabName === 'examGroups' ? 'active' : ''}" id="examGroups">
                                        <div class="form-group">
                                            <label style="margin-bottom:10px;" class="form-label">Qruplar</label>
                                            <?php
                                            include('db.php');
                                            $sql = "SELECT qrup_adi, telebe_sayi FROM qruplar";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo '<div class="form-check">';
                                                    echo '<input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="examGroup_' . htmlspecialchars($row['qrup_adi']) . '" name="groups[]" value="' . htmlspecialchars($row['qrup_adi']) . '">';
                                                    echo '<label class="form-check-label" for="examGroup_' . htmlspecialchars($row['qrup_adi']) . '">' . htmlspecialchars($row['qrup_adi']) . ' (' . $row['telebe_sayi'] . ' tələbə)</label>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<p>No groups found.</p>';
                                            }
                                            $conn->close();
                                            ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Ləğv Et</button>
                                <button class="btn btn-primary" id="prevTabBtn" style="display: none;" onclick="prevDynamicTab()">Əvvəlki</button>
                                <button class="btn btn-primary" id="nextTabBtn" onclick="nextDynamicTab()">Növbəti</button>
                                <button class="btn btn-success" id="saveExamBtn" style="display: none;" onclick="saveDynamicExam('insert')">Yadda Saxla</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML.trim());
            const dynamicModal = new bootstrap.Modal(document.getElementById('dynamicModal'), {
                backdrop: 'static',
                keyboard: false
            });
            dynamicModal.show();
            if (typeof Quill !== 'undefined') {
                const quill = new Quill('#examDescription', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'size': ['small', false, 'large', 'huge'] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    },
                    placeholder: 'İmtahan təsvirini daxil edin...',
                });
                quill.on('text-change', () => {
                    document.getElementById('examDescriptionHidden').value = quill.root.innerHTML;
                });
                window.quillInstance = quill;
            }
            attachImageClickListeners();
            fixModalZIndex('dynamicModal');
            setupDynamicTabNavigation(tabName);
            updateExamTopics();
            toggleQuestionSelection(); // Ensure handlers are set up
            setupQuestionSelectionHandlers(); // Set up click handlers for question items
            document.getElementById('dynamicModal').addEventListener('hidden.bs.modal', () => {
                delete window.quillInstance;
                document.getElementById('dynamicModal').remove();
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            });
        }
        function createEditModal(examData, tabName = 'examInfo') {
            const existingModal = document.getElementById('dynamicModal');
            if (existingModal) existingModal.remove();
            const modalHTML = `
                <div class="modal fade" id="dynamicModal" tabindex="-1" aria-labelledby="dynamicModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-custom">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title" id="dynamicModalLabel">İmtahanı Redaktə Et</h3>
                                <button hidden type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="dynamicExamForm">
                                    <input type="hidden" id="examId" name="examId" value="${examData.id}">
                                    <div class="modal-tabs" id="dynamicTabs">
                                        <div class="modal-tab ${tabName === 'examInfo' ? 'active' : ''}" data-tab="examInfo">İmtahan Məlumatları</div>
                                        <div class="modal-tab ${tabName === 'examQuestions' ? 'active' : ''}" data-tab="examQuestions">Suallar</div>
                                        <div class="modal-tab ${tabName === 'examGroups' ? 'active' : ''}" data-tab="examGroups">Qruplar</div>
                                    </div>
                                    <div class="tab-content ${tabName === 'examInfo' ? 'active' : ''}" id="examInfo">
                                        <div class="form-group">
                                            <label class="form-label" for="examName">İmtahan Adı</label>
                                            <input type="text" class="form-control" id="examName" name="exam_name" value="${examData.exam_name}" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Fənn</label>
                                            <div id="examSubjects">
                                                <?php
                                                include('db.php');
                                                $sql = "SELECT id, ixtisas_adi FROM ixtisas";
                                                $result = $conn->query($sql);
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo '<div class="form-check">';
                                                        echo '<input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="fenn_' . htmlspecialchars($row['ixtisas_adi']) . '" name="fenn_adi[]" value="' . htmlspecialchars($row['ixtisas_adi']) . '" data-fenn-id="' . $row['id'] . '" onchange="updateExamTopics()">';
                                                        echo '<label class="form-check-label" for="fenn_' . htmlspecialchars($row['ixtisas_adi']) . '">' . htmlspecialchars($row['ixtisas_adi']) . '</label>';
                                                        echo '</div>';
                                                    }
                                                } else {
                                                    echo '<p>No subjects found.</p>';
                                                }
                                                $conn->close();
                                                ?>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="sinif">Sinif</label>
                                            <input type="text" class="form-control" id="sinif" name="sinif" value="${examData.sinif || ''}" placeholder="Məsələn, 10A">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examDescription">Təsvir</label>
                                            <div id="examDescription" class="quill-editor"></div>
                                            <input type="hidden" id="examDescriptionHidden" name="description">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examDate">Tarix və Saat</label>
                                            <input type="datetime-local" class="form-control" id="examDate" name="exam_date" step="60" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examDuration">Müddət (dəqiqə)</label>
                                            <input type="number" class="form-control" id="examDuration" name="duration" min="1" value="${examData.duration}" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examPassingScore">Keçid Balı (%)</label>
                                            <input type="number" class="form-control" id="examPassingScore" name="passing_score" min="0" max="100" value="${examData.passing_score}" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="examStatus">Status</label>
                                            <select class="form-select" id="examStatus" name="status" required>
                                                <option value="upcoming" ${examData.status === 'upcoming' ? 'selected' : ''}>Gələcək</option>
                                                <option value="completed" ${examData.status === 'completed' ? 'selected' : ''}>Tamamlanmış</option>
                                                <option value="active" ${examData.status === 'active' ? 'selected' : ''}>Aktiv</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="tab-content ${tabName === 'examQuestions' ? 'active' : ''}" id="examQuestions">
                                        <div class="form-group">
                                            <label class="form-label">Mövzular</label>
                                            <div id="examTopics">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Sual Seçimi</label>
                                            <select class="form-select" id="examQuestionSelection" name="sual_secimi" onchange="toggleQuestionSelection()">
                                                <option value="manual" ${examData.sual_secimi === 'manual' ? 'selected' : ''}>Əl ilə Seç</option>
                                                <option value="random" ${examData.sual_secimi === 'random' ? 'selected' : ''}>Təsadüfi Seç</option>
                                            </select>
                                        </div>
                                        <div id="manualQuestionSelection" style="display: ${examData.sual_secimi === 'manual' ? 'block' : 'none'};">
                                            <div class="form-group">
                                                <label class="form-label">Suallar</label>
                                                <div class="d-flex form-group">
                                                    <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="selectAllQuestions">
                                                    <label class="form-check-label" for="selectAllQuestions">Hamısını Seç</label>
                                                </div>
                                                <div id="examQuestionList">
                                                    <?php
                                                    include('db.php');
                                                    $sql = "SELECT id, question_text, question_image FROM sual_banki";
                                                    $result = $conn->query($sql);
                                                    if ($result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            $clean_question_text = strip_tags($row['question_text']);
                                                            echo '<div class="form-check question-item">';
                                                            echo '<input style="border:2px solid rgba(89, 116, 235, 0.73); margin:8px;" type="checkbox" class="form-check-input" name="examQuestions[]" value="' . htmlspecialchars($row['id']) . '">';
                                                            echo '<label class="form-check-label">' . htmlspecialchars($clean_question_text) . '</label>';
                                                            if (!empty($row['question_image'])) {
                                                                echo '<div class="question-image-container">';
                                                                echo '<img src="' . htmlspecialchars($row['question_image']) . '" alt="Question Image" class="question-image" data-image="' . htmlspecialchars($row['question_image']) . '">';
                                                                echo '<span class="magnifier-icon"><i class="fas fa-search-plus"></i></span>';
                                                                echo '</div>';
                                                            }
                                                            echo '</div>';
                                                        }
                                                    } else {
                                                        echo '<p>No questions found.</p>';
                                                    }
                                                    $conn->close();
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="randomQuestionSelection" style="display: ${examData.sual_secimi === 'random' ? 'block' : 'none'};">
                                            <div class="form-group">
                                                <label class="form-label" for="examQuestionCount">Sual Sayı</label>
                                                <input type="number" class="form-control" id="examQuestionCount" name="sual_sayi" min="1" value="${examData.sual_sayi || '10'}">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Çətinlik Səviyyəsi</label>
                                                <div class="d-flex gap-10">
                                                    <div class="form-check">
                                                        <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="difficultyEasy" name="cetinlik_seviyyesi[]" value="Easy">
                                                        <label class="form-check-label" for="difficultyEasy">Asan</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="difficultyMedium" name="cetinlik_seviyyesi[]" value="Medium">
                                                        <label class="form-check-label" for="difficultyMedium">Orta</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="difficultyHard" name="cetinlik_seviyyesi[]" value="Hard">
                                                        <label class="form-check-label" for="difficultyHard">Çətin</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-content ${tabName === 'examGroups' ? 'active' : ''}" id="examGroups">
                                        <div class="form-group">
                                            <label style="margin-bottom:10px;" class="form-label">Qruplar</label>
                                            <?php
                                            include('db.php');
                                            $sql = "SELECT qrup_adi, telebe_sayi FROM qruplar";
                                            $result = $conn->query($sql);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo '<div class="form-check">';
                                                    echo '<input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" id="examGroup_' . htmlspecialchars($row['qrup_adi']) . '" name="groups[]" value="' . htmlspecialchars($row['qrup_adi']) . '">';
                                                    echo '<label class="form-check-label" for="examGroup_' . htmlspecialchars($row['qrup_adi']) . '">' . htmlspecialchars($row['qrup_adi']) . ' (' . $row['telebe_sayi'] . ' tələbə)</label>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<p>No groups found.</p>';
                                            }
                                            $conn->close();
                                            ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Ləğv Et</button>
                                <button class="btn btn-primary" id="prevTabBtn" style="display: none;" onclick="prevDynamicTab()">Əvvəlki</button>
                                <button class="btn btn-primary" id="nextTabBtn" onclick="nextDynamicTab()">Növbəti</button>
                                <button class="btn btn-success" id="saveExamBtn" style="display: none;" onclick="saveDynamicExam('edit')">Yadda Saxla</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML.trim());
            if (typeof Quill !== 'undefined') {
                const quill = new Quill('#examDescription', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'size': ['small', false, 'large', 'huge'] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    },
                    placeholder: 'İmtahan təsvirini daxil edin...',
                });
                quill.on('text-change', () => {
                    document.getElementById('examDescriptionHidden').value = quill.root.innerHTML;
                });
                window.quillInstance = quill;
            }
            attachImageClickListeners();
            fixModalZIndex('dynamicModal');
            setupDynamicTabNavigation(tabName);
            toggleQuestionSelection(); // Ensure handlers are set up
            setupQuestionSelectionHandlers(); // Set up click handlers for question items
            document.getElementById('dynamicModal').addEventListener('hidden.bs.modal', () => {
                delete window.quillInstance;
                document.getElementById('dynamicModal').remove();
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            });
        }
        function setupQuestionSelectionHandlers() {
            const selectAllCheckbox = document.getElementById('selectAllQuestions');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    const isChecked = selectAllCheckbox.checked;
                    document.querySelectorAll('#examQuestionList input[name="examQuestions[]"]').forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });
                });
            }
            document.querySelectorAll('#examQuestionList .question-item').forEach(item => {
                item.removeEventListener('click', toggleCheckboxHandler); // Remove any existing listeners to prevent duplicates
                item.addEventListener('click', toggleCheckboxHandler);
            });
        }
        function toggleCheckboxHandler(event) {
            const item = event.currentTarget;
            if (
                event.target.type === 'checkbox' ||
                event.target.tagName === 'LABEL' ||
                event.target.classList.contains('question-image') ||
                event.target.closest('.question-image-container')
            ) {
                return;
            }
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                updateSelectAllCheckbox();
            }
        }
        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('selectAllQuestions');
            const questionCheckboxes = document.querySelectorAll('#examQuestionList input[name="examQuestions[]"]');
            if (selectAllCheckbox && questionCheckboxes.length > 0) {
                const allChecked = Array.from(questionCheckboxes).every(checkbox => checkbox.checked);
                selectAllCheckbox.checked = allChecked;
            }
        }
        function editExam(id) {
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loadingIndicator';
            loadingDiv.innerHTML = '<div class="spinner"></div><p></p>';
            loadingDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;flex-direction:column;justify-content:center;align-items:center;color:white;z-index:9999;';
            document.body.appendChild(loadingDiv);
            fetch(`movzular/imtahan/operations.php?action=get_exam&exam_id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(async data => {
                    if (!data.success) {
                        throw new Error(data.message || 'İmtahan məlumatlarını əldə etmək mümkün olmadı');
                    }
                    const examData = data.exam;
                    createEditModal(examData);
                    setBasicFormValues(examData);
                    await updateExamTopicsForEdit(examData);
                    setAllCheckboxes(examData);
                    return new Promise(resolve => setTimeout(() => resolve(examData), 250));
                })
                .then(examData => {
                    const dynamicModal = new bootstrap.Modal(document.getElementById('dynamicModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    document.getElementById('loadingIndicator')?.remove();
                    dynamicModal.show();
                })
                .catch(error => {
                    document.getElementById('loadingIndicator')?.remove();
                    console.error('Error fetching exam data:', error);
                    alert('İmtahan məlumatlarını əldə etməkdə xəta baş verdi: ' + error.message);
                });
        }
        function setBasicFormValues(examData) {
            if (examData.description && window.quillInstance) {
                window.quillInstance.root.innerHTML = examData.description;
                document.getElementById('examDescriptionHidden').value = examData.description;
            }
            if (examData.exam_date) {
                try {
                    const date = new Date(examData.exam_date);
                    if (!isNaN(date.getTime())) {
                        const isoDate = date.toISOString().slice(0, 16);
                        document.getElementById('examDate').value = isoDate;
                    }
                } catch (e) {
                    console.error('Error parsing exam_date:', e);
                }
            }
            if (examData.fenn_adi && Array.isArray(examData.fenn_adi)) {
                document.querySelectorAll('input[name="fenn_adi[]"]').forEach(checkbox => {
                    checkbox.checked = examData.fenn_adi.includes(checkbox.value);
                });
            }
            toggleQuestionSelection();
        }
        function updateExamTopicsForEdit(examData) {
            return new Promise((resolve, reject) => {
                updateExamTopics()
                    .then(() => {
                        resolve();
                    })
                    .catch(error => {
                        console.error('Error updating topics:', error);
                        reject(error);
                    });
            });
        }
        function setAllCheckboxes(examData) {
            if (examData.movzular && Array.isArray(examData.movzular)) {
                document.querySelectorAll('input[name="movzu_adi[]"]').forEach(checkbox => {
                    const fenn_adi = checkbox.getAttribute('data-fenn-adi');
                    const movzu_adi = checkbox.value;
                    checkbox.checked = examData.movzular.some(m => 
                        m.fenn_adi === fenn_adi && m.movzu_adi === movzu_adi
                    );
                });
            }
            if (examData.questions && Array.isArray(examData.questions)) {
                document.querySelectorAll('input[name="examQuestions[]"]').forEach(checkbox => {
                    checkbox.checked = examData.questions.includes(parseInt(checkbox.value));
                });
                updateSelectAllCheckbox(); // Update "Select All" checkbox state
            }
            if (examData.cetinlik_seviyyesi && Array.isArray(examData.cetinlik_seviyyesi)) {
                document.querySelectorAll('input[name="cetinlik_seviyyesi[]"]').forEach(checkbox => {
                    checkbox.checked = examData.cetinlik_seviyyesi.includes(checkbox.value);
                });
            }
            if (examData.groups) {
                const groups = examData.groups.split(',').map(g => g.trim());
                document.querySelectorAll('input[name="groups[]"]').forEach(checkbox => {
                    checkbox.checked = groups.includes(checkbox.value);
                });
            }
        }
        function viewExam(id) {
            if (!id || (typeof id !== 'string' && typeof id !== 'number')) {
                alert('Invalid exam ID provided');
                console.error('viewExam called with invalid ID:', id);
                return;
            }
            const tableBody = $('#examTableBody');
            if (!tableBody.length) {
                alert('Exam table not found in the page');
                console.error('Table with ID #examTableBody not found');
                return;
            }
            const row = tableBody.find('tr').filter(function() {
                const firstCell = $(this).find('td:first');
                return firstCell.length && firstCell.text().trim() === String(id);
            });
            if (!row.length) {
                alert(`Exam with ID ${id} not found in the table`);
                console.warn(`No row found for exam ID: ${id}. Table rows:`, tableBody.find('tr').length);
                return;
            }
            const examData = {
                id: row.find('td').eq(0).text().trim() || 'N/A',
                exam_name: row.find('td').eq(1).text().trim() || 'N/A',
                fenn_adi: row.find('td').eq(2).text().trim() || 'N/A',
                sinif: row.find('td').eq(3).text().trim() || 'N/A',
                description: row.find('td').eq(4).text().trim() || 'N/A',
                exam_date: row.find('td').eq(5).text().trim() || 'N/A',
                duration: row.find('td').eq(6).text().trim() || 'N/A',
                passing_score: row.find('td').eq(7).text().trim() || 'N/A',
                groups: row.find('td').eq(8).text().trim() || 'N/A',
                questions: row.find('td').eq(9).text().trim() || '',
                movzular: row.find('td').eq(10).text().trim() || '',
                sual_secimi: row.find('td').eq(11).text().trim() || 'N/A',
                status: row.find('td').eq(12).find('.badge').text().trim() || 'N/A',
                status_class: row.find('td').eq(12).find('.badge').attr('class')?.split(' ').find(cls => cls.startsWith('bg-')) || 'bg-secondary',
                sual_sayi: row.find('td').eq(13).text().trim() || 'N/A',
                cetinlik_seviyyesi: row.find('td').eq(14).text().trim() || 'N/A',
                created_at: row.find('td').eq(15).text().trim() || 'N/A'
            };
            const questionsArray = examData.questions ? examData.questions.split(',').map(q => q.trim()).filter(q => q) : [];
            const movzularArray = examData.movzular ? examData.movzular.split(',').map(m => m.trim()).filter(m => m) : [];
            const formattedGroups = examData.groups && examData.groups !== 'N/A'
                ? examData.groups.split(',').map(g => g.trim()).filter(g => g).join(', ')
                : examData.groups;
            let actualQuestionCount = 'N/A';
            const sualSecimiLower = examData.sual_secimi.toLowerCase().trim();
            if (sualSecimiLower === 'əl ilə seç' || sualSecimiLower === 'manual') {
                actualQuestionCount = questionsArray.length > 0 ? questionsArray.length : '0';
            } else {
                actualQuestionCount = examData.sual_sayi !== 'N/A' ? examData.sual_sayi : '0';
            }
            $('#modal-exam-id-' + id).text(examData.id);
            $('#modal-exam-name-' + id).text(examData.exam_name);
            $('#modal-exam-fenn-adi-' + id).text(examData.fenn_adi);
            $('#modal-exam-sinif-' + id).text(examData.sinif);
            $('#modal-exam-description-' + id).text(examData.description);
            $('#modal-exam-date-' + id).text(examData.exam_date);
            $('#modal-exam-duration-' + id).text(examData.duration);
            $('#modal-exam-passing-score-' + id).text(`${examData.passing_score}%`);
            $('#modal-exam-groups-' + id).text(formattedGroups);
            $('#modal-exam-sual-secimi-' + id).text(examData.sual_secimi);
            $('#modal-exam-status-' + id).html(`<span class="badge ${examData.status_class}">${examData.status}</span>`);
            $('#modal-exam-sual-sayi-' + id).text(actualQuestionCount);
            $('#modal-exam-cetinlik-seviyyesi-' + id).text(examData.cetinlik_seviyyesi);
            $('#modal-exam-created-at-' + id).text(examData.created_at);
            $('#modal-exam-questions-count-' + id).text(`(${questionsArray.length})`);
            $('#modal-exam-movzular-count-' + id).text(`(${movzularArray.length})`);
            const examModalElement = document.getElementById('examModal-' + id);
            if (!examModalElement) {
                alert('Main exam modal not found for ID: ' + id);
                console.error('Modal with ID #examModal-' + id + ' not found');
                return;
            }
            const examModal = new bootstrap.Modal(examModalElement);
            examModal.show();
            $('#open-more-modal-' + id).off('click').on('click', function() {
                examModal.hide();
                const questionsDetails = $('#modal-questions-details');
                if (!questionsDetails.length) {
                    alert('Questions modal details element not found');
                    console.error('Element with ID #modal-questions-details not found');
                    return;
                }
                questionsDetails.empty();
                if (questionsArray.length > 0) {
                    questionsArray.forEach(question => {
                        questionsDetails.append(
                            `<li class="list-group-item">${question}</li>`
                        );
                    });
                } else {
                    questionsDetails.append(
                        `<li class="list-group-item text-muted fst-italic">No questions available</li>`
                    );
                }
                const questionsModalElement = document.getElementById('questionsModal');
                if (!questionsModalElement) {
                    alert('Questions modal not found');
                    console.error('Modal with ID #questionsModal not found');
                    return;
                }
                const questionsModal = new bootstrap.Modal(questionsModalElement);
                questionsModal.show();
            });
            $('#open-more-movzular-modal-' + id).off('click').on('click', function() {
                examModal.hide();
                const movzularDetails = $('#modal-movzular-details');
                if (!movzularDetails.length) {
                    alert('Movzular modal details element not found');
                    console.error('Element with ID #movzular-details not found');
                    return;
                }
                movzularDetails.empty();
                if (movzularArray.length > 0) {
                    movzularArray.forEach(movzu => {
                        movzularDetails.append(
                            `<li class="list-group-item">${movzu}</li>`
                        );
                    });
                } else {
                    movzularDetails.append(
                        `<li class="list-group-item text-muted fst-italic">No topics available</li>`
                    );
                }
                const movzularModalElement = document.getElementById('movzularModal');
                if (!movzularModalElement) {
                    alert('Movzular modal not found');
                    console.error('Modal with ID #movzularModal not found');
                    return;
                }
                const movzularModal = new bootstrap.Modal(movzularModalElement);
                movzularModal.show();
            });
        }
        function deleteExam(id) {
            if (!confirm(`İmtahan ID ${id} silinsin?`)) return;
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'deleteLoadingIndicator';
            loadingDiv.innerHTML = '<div class="spinner"></div><p>İmtahan silinir...</p>';
            loadingDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;flex-direction:column;justify-content:center;align-items:center;color:white;z-index:9999;';
            document.body.appendChild(loadingDiv);
            const formData = new FormData();
            formData.append('action', 'delete_exam');
            formData.append('exam_id', id);
            fetch('movzular/imtahan/operations.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('deleteLoadingIndicator')?.remove();

                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Xəta baş verdi');
                    }
                })
                .catch(error => {
                    document.getElementById('deleteLoadingIndicator')?.remove();
                    console.error('Delete error:', error);
                    alert('İmtahanı silməkdə xəta baş verdi: ' + error.message);
                });
        }
        function print(examId) {
            if (!examId) {
                alert("Xəta: İmtahan ID təmin edilməyib");
                return;
            }
            var timestamp = new Date().getTime();
            var printUrl = 'movzular/imtahan/get_exam_and_questions.php?id=' + examId + '&t=' + timestamp;
            var loadingMessage = document.createElement('div');
            loadingMessage.style.position = 'fixed';
            loadingMessage.style.top = '50%';
            loadingMessage.style.left = '50%';
            loadingMessage.style.transform = 'translate(-50%, -50%)';
            loadingMessage.style.padding = '20px';
            loadingMessage.style.background = 'rgba(0,0,0,0.7)';
            loadingMessage.style.color = 'white';
            loadingMessage.style.borderRadius = '5px';
            loadingMessage.style.zIndex = '9999';
            loadingMessage.textContent = 'İmtahan yüklənir...';
            document.body.appendChild(loadingMessage);
            var printWindow = window.open(printUrl, '_blank');
            if (!printWindow) {
                document.body.removeChild(loadingMessage);
                alert("Xəta: PDF açıla bilmədi, brauzerinizin pop-up bloklayıcısını yoxlayın");
                return;
            }
            setTimeout(function() {
                document.body.removeChild(loadingMessage);
            }, 1000);
            printWindow.addEventListener('load', function() {
                if (printWindow.document.body.textContent.includes('Error:')) {
                    alert("Xəta: " + printWindow.document.body.textContent.trim());
                    printWindow.close();
                }
            });
        }
        function filterExams() {
            const search = $('#examSearchInput').val().toLowerCase();
            const subject = $('#examSubjectFilter').val();
            const status = $('#examStatusFilter').val();
            $('#examTableBody tr').each(function() {
                const examName = $(this).find('td').eq(1).text().toLowerCase();
                const fennAdi = $(this).find('td').eq(2).text().toLowerCase();
                const examStatus = $(this).find('td').eq(12).find('.badge').text().toLowerCase();
                const matchesSearch = examName.includes(search) || fennAdi.includes(search);
                const matchesSubject = !subject || fennAdi.includes(subject.toLowerCase());
                const matchesStatus = !status || examStatus === status.toLowerCase();
                $(this).toggle(matchesSearch && matchesSubject && matchesStatus);
            });
        }
        function updateExamTopics() {
            return new Promise((resolve, reject) => {
                const checkboxes = document.querySelectorAll('#examSubjects input[name="fenn_adi[]"]:checked');
                const fennIds = Array.from(checkboxes).map(cb => cb.getAttribute('data-fenn-id'));
                const examTopicsDiv = document.getElementById('examTopics');
                if (fennIds.length === 0) {
                    examTopicsDiv.innerHTML = '<p></p>';
                    resolve();
                    return;
                }
                const formData = new FormData();
                formData.append('action', 'fetch_topics');
                formData.append('fenn_ids', JSON.stringify(fennIds));
                fetch('movzular/imtahan/operations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(response => {
                    if (response.success && response.topics?.length > 0) {
                        const topicsByFenn = {};
                        response.topics.forEach(topic => {
                            if (!topicsByFenn[topic.ixtisas_adi]) {
                                topicsByFenn[topic.ixtisas_adi] = [];
                            }
                            topicsByFenn[topic.ixtisas_adi].push(topic);
                        });
                        let topicsHTML = '';
                        Object.keys(topicsByFenn).forEach(ixtisas_adi => {
                            const safeFennId = ixtisas_adi.replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_]/g, '');
                            topicsHTML += `
                                <div class="fenn-folder">
                                    <div class="fenn-header" data-fenn-id="${safeFennId}">
                                        <span class="toggle-icon fas fa-plus"></span>
                                        <span class="fenn-title">${ixtisas_adi}</span>
                                    </div>
                                    <div class="fenn-topics" id="topics_${safeFennId}" style="display: none;">
                            `;
                            topicsByFenn[ixtisas_adi].forEach(topic => {
                                const safeTopicId = topic.movzu_adi.replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_]/g, '');
                                topicsHTML += `
                                    <div class="form-check topic-item">
                                        <input style="border:2px solid rgba(89, 116, 235, 0.73);" type="checkbox" class="form-check-input" 
                                            id="topic_${safeTopicId}" 
                                            name="movzu_adi[]" 
                                            value="${topic.movzu_adi}" 
                                            data-fenn-adi="${topic.ixtisas_adi}">
                                        <label class="form-check-label" for="topic_${safeTopicId}">
                                            ${topic.movzu_adi}
                                        </label>
                                    </div>
                                `;
                            });
                            topicsHTML += `
                                    </div>
                                </div>
                            `;
                        });
                        examTopicsDiv.innerHTML = topicsHTML;
                        document.querySelectorAll('.fenn-header').forEach(header => {
                            header.addEventListener('click', () => {
                                const fennId = header.getAttribute('data-fenn-id');
                                const topicsDiv = document.getElementById(`topics_${fennId}`);
                                const toggleIcon = header.querySelector('.toggle-icon');
                                if (topicsDiv.style.display === 'none') {
                                    topicsDiv.style.display = 'block';
                                    toggleIcon.classList.remove('fa-plus');
                                    toggleIcon.classList.add('fa-minus');
                                } else {
                                    topicsDiv.style.display = 'none';
                                    toggleIcon.classList.remove('fa-minus');
                                    toggleIcon.classList.add('fa-plus');
                                }
                            });
                        });
                    } else {
                        examTopicsDiv.innerHTML = '<p>No topics found.</p>';
                    }
                    resolve();
                })
                .catch(error => {
                    examTopicsDiv.innerHTML = '<p>Error fetching topics.</p>';
                    reject(error);
                });
            });
        }
        function saveDynamicExam(mode) {
            const form = document.getElementById('dynamicExamForm');
            if (!form) {
                alert('Form tapılmadı');
                return;
            }
            const elements = {
                examId: document.getElementById('examId'),
                examName: document.getElementById('examName'),
                examDescriptionHidden: document.getElementById('examDescriptionHidden'),
                examDate: document.getElementById('examDate'),
                examDuration: document.getElementById('examDuration'),
                examPassingScore: document.getElementById('examPassingScore'),
                examStatus: document.getElementById('examStatus'),
                examQuestionSelection: document.getElementById('examQuestionSelection'),
                examQuestionCount: document.getElementById('examQuestionCount'),
                sinif: document.getElementById('sinif')

            };
            const formData = new FormData();
            formData.append('action', mode === 'insert' ? 'insert_exam' : 'update_exam');
            if (mode === 'edit') formData.append('exam_id', elements.examId.value || '');
            formData.append('exam_name', elements.examName.value || '');
            const fenn_adi = Array.from(document.querySelectorAll('#examSubjects input[name="fenn_adi[]"]:checked')).map(input => input.value);
            formData.append('fenn_adi', JSON.stringify(fenn_adi));
            formData.append('sinif', elements.sinif.value || '');
            formData.append('description', elements.examDescriptionHidden?.value || '');
            formData.append('exam_date', elements.examDate.value || '');
            formData.append('duration', parseInt(elements.examDuration.value) || 45);
            formData.append('passing_score', parseInt(elements.examPassingScore.value) || 60);
            formData.append('status', elements.examStatus.value || 'upcoming');
            const sualSecimi = elements.examQuestionSelection.value || 'manual';
            formData.append('sual_secimi', sualSecimi);
            const groups = Array.from(document.querySelectorAll('#examGroups input[name="groups[]"]:checked')).map(input => input.value).join(',');
            formData.append('groups', groups);
            const questions = sualSecimi === 'manual' 
                ? Array.from(document.querySelectorAll('#examQuestionList input[name="examQuestions[]"]:checked')).map(input => parseInt(input.value)) 
                : [];
            formData.append('questions', JSON.stringify(questions));
            const movzular = Array.from(document.querySelectorAll('#examTopics input[name="movzu_adi[]"]:checked')).map(input => ({
                fenn_adi: input.getAttribute('data-fenn-adi'),
                movzu_adi: input.value
            }));
            formData.append('movzular', JSON.stringify(movzular));
            if (sualSecimi === 'random') {
                formData.append('sual_sayi', parseInt(elements.examQuestionCount.value) || 10);
            }
            const cetinlik_seviyyesi = sualSecimi === 'random' 
                ? Array.from(document.querySelectorAll('#randomQuestionSelection input[name="cetinlik_seviyyesi[]"]:checked')).map(input => input.value) 
                : [];
            formData.append('cetinlik_seviyyesi', JSON.stringify(cetinlik_seviyyesi));
            const missingFields = [];
            if (mode === 'edit' && !elements.examId.value) missingFields.push('İmtahan ID');
            if (!elements.examName.value) missingFields.push('İmtahan Adı');
            if (!fenn_adi.length) missingFields.push('Fənn');
            if (!elements.examDate.value) missingFields.push('Tarix və Saat');
            if (elements.examDuration.value <= 0) missingFields.push('Müddət');
            if (elements.examPassingScore.value < 0 || elements.examPassingScore.value > 100) missingFields.push('Keçid Balı');
            if (!groups) missingFields.push('Qruplar');
            if (missingFields.length > 0) {
                alert('Zəhmət olmasa, aşağıdaki sahələri doldurun: ' + missingFields.join(', '));
                return;
            }
            if (form.checkValidity()) {
                const loadingDiv = document.createElement('div');
                loadingDiv.id = 'saveLoadingIndicator';
                loadingDiv.innerHTML = '<div class="spinner"></div><p>İmtahan yadda saxlanılır...</p>';
                loadingDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;flex-direction:column;justify-content:center;align-items:center;color:white;z-index:9999;';
                document.body.appendChild(loadingDiv);

                fetch('movzular/imtahan/operations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('saveLoadingIndicator')?.remove();

                    if (data.success) {
                        alert(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('dynamicModal'))?.hide();
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Xəta baş verdi');
                    }
                })
                .catch(error => {
                    document.getElementById('saveLoadingIndicator')?.remove();
                    console.error(`İmtahanı ${mode === 'insert' ? 'əlavə etməkdə' : 'yeniləməkdə'} xəta:`, error);
                    alert(`İmtahanı ${mode === 'insert' ? 'əlavə etməkdə' : 'yeniləməkdə'} xəta baş verdi: ${error.message}`);
                });
            } else {
                form.reportValidity();
            }
        }
        function toggleQuestionSelection() {
            const selection = document.getElementById('examQuestionSelection')?.value;
            if (selection) {
                document.getElementById('manualQuestionSelection').style.display = selection === 'manual' ? 'block' : 'none';
                document.getElementById('randomQuestionSelection').style.display = selection === 'random' ? 'block' : 'none';
                if (selection === 'manual') {
                    setupQuestionSelectionHandlers();
                }
            }
        }
        function setupDynamicTabNavigation(currentTab) {
            const tabs = document.querySelectorAll('#dynamicTabs .modal-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => switchToTab(tab.dataset.tab));
            });
            updateButtonsVisibility(currentTab);
        }
        function updateButtonsVisibility(tabName) {
            const prevBtn = document.getElementById('prevTabBtn');
            const nextBtn = document.getElementById('nextTabBtn');
            const saveBtn = document.getElementById('saveExamBtn');
            if (prevBtn && nextBtn && saveBtn) {
                prevBtn.style.display = tabName === 'examInfo' ? 'none' : 'inline-block';
                nextBtn.style.display = tabName === 'examGroups' ? 'none' : 'inline-block';
                saveBtn.style.display = tabName === 'examGroups' ? 'inline-block' : 'none';
            }
        }
        function prevDynamicTab() {
            const tabOrder = ['examInfo', 'examQuestions', 'examGroups'];
            const currentIndex = tabOrder.indexOf(document.querySelector('.modal-tab.active').dataset.tab);
            if (currentIndex > 0) switchToTab(tabOrder[currentIndex - 1]);
        }
        function nextDynamicTab() {
            const tabOrder = ['examInfo', 'examQuestions', 'examGroups'];
            const currentIndex = tabOrder.indexOf(document.querySelector('.modal-tab.active').dataset.tab);
            if (currentIndex < tabOrder.length - 1) switchToTab(tabOrder[currentIndex + 1]);
        }
        function switchToTab(tabName) {
            document.querySelectorAll('#dynamicTabs .modal-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.querySelector(`.modal-tab[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(tabName).classList.add('active');
            updateButtonsVisibility(tabName);
        }
        function fixModalZIndex(modalId = 'dynamicModal') {
            const modalEl = document.getElementById(modalId);
            if (modalEl) modalEl.style.zIndex = '1055';
            const observer = new MutationObserver(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.zIndex = '1050';
                    observer.disconnect();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
            document.body.classList.add('modal-open');
        }
        function attachImageClickListeners() {
            document.querySelectorAll('.question-image').forEach(img => {
                img.addEventListener('click', () => {
                    const imageUrl = img.getAttribute('data-image');
                    openFullScreenImage(imageUrl);
                });
                img.onerror = () => {
                    img.style.display = 'none';
                    img.nextElementSibling.style.display = 'none';
                };
            });
        }
        function openFullScreenImage(imageUrl) {
            const overlay = document.createElement('div');
            overlay.id = 'fullScreenImageOverlay';
            overlay.innerHTML = `
                <div class="full-screen-image-container">
                    <span class="close-full-screen">×</span>
                    <img src="${imageUrl}" alt="Full Screen Question Image" class="full-screen-image">
                </div>
            `;
            document.body.appendChild(overlay);
            overlay.querySelector('.close-full-screen').addEventListener('click', closeFullScreenImage);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    closeFullScreenImage();
                }
            });
        }
        function closeFullScreenImage() {
            const overlay = document.getElementById('fullScreenImageOverlay');
            if (overlay) {
                overlay.remove();
            }
        }

        (function () {
            var movzuStatTitles = {
                students: 'Tələbələr',
                groups: 'Qruplar',
                topics: 'Mövzular',
                exams: 'İmtahanlar'
            };

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text == null ? '' : String(text);
                return div.innerHTML;
            }

            function openMovzuStatModal(type) {
                var modalEl = document.getElementById('statDetailsModal');
                if (!modalEl || typeof bootstrap === 'undefined') return;

                document.getElementById('statDetailsTitle').textContent = movzuStatTitles[type] || 'Məlumatlar';
                document.getElementById('statDetailsLoading').classList.remove('d-none');
                document.getElementById('statDetailsContent').classList.add('d-none');
                document.getElementById('statDetailsEmpty').classList.add('d-none');
                document.getElementById('statDetailsHead').innerHTML = '';
                document.getElementById('statDetailsBody').innerHTML = '';

                bootstrap.Modal.getOrCreateInstance(modalEl).show();

                fetch('movzular/idare_panel/stat_operations.php?type=' + encodeURIComponent(type))
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        document.getElementById('statDetailsLoading').classList.add('d-none');
                        if (data.status !== 'success' || !data.data || !data.data.length) {
                            document.getElementById('statDetailsEmpty').classList.remove('d-none');
                            return;
                        }
                        document.getElementById('statDetailsContent').classList.remove('d-none');
                        var headHtml = '<tr>';
                        data.columns.forEach(function (column) {
                            headHtml += '<th>' + escapeHtml(column.label) + '</th>';
                        });
                        headHtml += '</tr>';
                        document.getElementById('statDetailsHead').innerHTML = headHtml;

                        var bodyHtml = '';
                        data.data.forEach(function (row) {
                            bodyHtml += '<tr>';
                            data.columns.forEach(function (column) {
                                bodyHtml += '<td>' + escapeHtml(row[column.key] ?? '-') + '</td>';
                            });
                            bodyHtml += '</tr>';
                        });
                        document.getElementById('statDetailsBody').innerHTML = bodyHtml;
                    })
                    .catch(function () {
                        document.getElementById('statDetailsLoading').classList.add('d-none');
                        document.getElementById('statDetailsEmpty').classList.remove('d-none');
                    });
            }

            document.querySelectorAll('#dashboard .stat-card-clickable').forEach(function (card) {
                card.addEventListener('click', function () {
                    openMovzuStatModal(card.dataset.statType);
                });
                card.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openMovzuStatModal(card.dataset.statType);
                    }
                });
            });
        })();
    </script>

    <div class="modal fade" id="statDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
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
</body>
</html>