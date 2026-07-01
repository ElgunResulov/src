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
            margin-top: 86px;
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

        /* Buttons */
        .btn {
            border-radius: 6px;
            font-weight: bold;
            padding: 8px 18px;
            font-family: Arial;
            font-size:16px;
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

        /* Filter Panel */
        .filter-panel {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        /* Specialty Card */
        .specialty-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
        }

        .specialty-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .specialty-card .card-img-top {
            height: 160px;
            object-fit: cover;
        }

        .specialty-card .badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .specialty-card .card-body {
            padding: 1.25rem;
        }

        .specialty-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .specialty-card .card-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .specialty-card .card-footer {
            background-color: transparent;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
        }

        .badge-success {
            padding: 7px;
            font-family: Arial;
            font-weight: bold;
            border-radius:6px;
            background-color: #28a745 !important;
            color: white;
        }

        .badge-danger {
            padding: 7px;
            font-family: Arial;
            font-weight: bold;
            border-radius:6px;  
            background-color:rgb(225, 47, 65) !important;
            color: white;
        }

        .specialty-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
        }

        .specialty-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .specialty-stat-value {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .specialty-stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 0px;
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
            font-weight: bolder;
            font-family: Arial;
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
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 0.75rem;
            }

            /* Tabs */
            .nav-tabs {
                text-align: center;
                width: 100%;
                display: inline;
            }
        }
    </style>


    <!-- Add/Edit Specialty Modal -->
    <div class="modal fade" id="specialtyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">İxtisası Əlavə Et</h5>
            
                </div>
                <div class="modal-body">
                    <form id="specialtyForm" enctype="multipart/form-data">
                        <input type="hidden" id="specialtyId" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specialtyName">İxtisas Adı <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="specialtyName" name="ixtisas_adi" required>
                                    <div class="invalid-feedback">Zəhmət olmasa ixtisas adını daxil edin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specialtyCode">İxtisas Kodu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="specialtyCode" name="ixtisas_kodu" required>
                                    <div class="invalid-feedback">Zəhmət olmasa ixtisas kodunu daxil edin.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6" style="display: none;">
                                <div class="form-group">
                                    <label for="department">Fakültə</label>
                                    <select class="form-control" id="department" name="fakulte">
                                        <option value="">Seçin</option>
                                        <option value="1">Mühəndislik</option>
                                        <option value="2">İqtisadiyyat</option>
                                        <option value="3">Humanitar</option>
                                        <option value="4">Tibb</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="educationLevel">Təhsil Səviyyəsi <span class="text-danger">*</span></label>
                                    <select class="form-control" id="educationLevel" name="tehsil_seviyyesi" required>
                                        <option value="">Seçin</option>
                                        <option value="bachelor">Bakalavr</option>
                                        <option value="master">Magistr</option>
                                        <option value="phd">Doktorantura</option>
                                    </select>
                                    <div class="invalid-feedback">Zəhmət olmasa təhsil səviyyəsini seçin.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="specialtyActive">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" id="specialtyActive" name="active" required>
                                        <option value="1">Aktiv</option>
                                        <option value="0">Passiv</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
                            <label for="specialtyDescription">Təsvir</label>
                            <textarea class="form-control" id="specialtyDescription" name="tesvir" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group mt-3">
                            <label for="specialtyImage">İxtisas Şəkli</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="specialtyImage" name="sekil" accept="image/*">
                                <label class="custom-file-label" for="specialtyImage">Şəkil seçin</label>
                            </div>
                            <div class="mt-2">
                                <img id="specialtyImagePreview" src="" class="img-thumbnail" style="max-height: 150px;">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Bağla</button>
                    <button type="button" class="btn btn-primary" id="saveSpecialty">Yadda saxla</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Specialty Modal -->
    <div class="modal fade" id="viewSpecialtyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">İxtisas Məlumatları</h5>
               
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <img id="viewSpecialtyImage" src="" class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: cover;">
                        </div>
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>İxtisas Adı:</strong></td>
                                    <td id="viewSpecialtyName">-</td>
                                </tr>
                                <tr>
                                    <td><strong>İxtisas Kodu:</strong></td>
                                    <td id="viewSpecialtyCode">-</td>
                                </tr>
                                <tr hidden>
                                    <td><strong>Fakültə:</strong></td>
                                    <td id="viewSpecialtyFaculty">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Təhsil Səviyyəsi:</strong></td>
                                    <td id="viewSpecialtyLevel">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td id="viewSpecialtyStatus">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Yaradılma Tarixi:</strong></td>
                                    <td id="viewSpecialtyCreated">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><strong>Təsvir:</strong></h6>
                            <p class="text-muted" id="viewSpecialtyDescription">Təsvir yoxdur</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-primary" id="viewStudentCount">0</h5>
                                    <p class="card-text">Tələbələr</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-success" id="viewTeacherCount">0</h5>
                                    <p class="card-text">Müəllimlər</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title text-info" id="viewSubjectCount">0</h5>
                                    <p class="card-text">Fənnlər</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Specialty Modal -->
    <div class="modal fade" id="deleteSpecialtyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">İxtisası Sil</h5>
                
                </div>
                <div class="modal-body">
                    <input type="hidden" id="deleteSpecialtyId" name="id">
                    <p>Bu ixtisası silmək istədiyinizə əminsiniz?</p>
                    <p class="text-danger"><strong>Diqqət:</strong> Bu əməliyyat geri qaytarıla bilməz!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Ləğv et</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteSpecialty">Sil</button>
                </div>
            </div>
        </div>
    </div>