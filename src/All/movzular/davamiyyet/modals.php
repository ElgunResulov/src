    <div class="main-container">
        <div class="section" id="journal">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Davamiyyət Jurnalı
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="journalGroupFilter" class="form-label">Qrup Seçin</label>
                            <select class="form-select" id="journalGroupFilter" onchange="loadStudents()">
                                <option value="">Bütün qruplar</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editDatetime" class="form-label">Tarix</label>
                            <input type="date" class="form-control" id="editDatetime" name="tarix" required>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">
                                        Tələbə
                                    </th>
                                    <th style="width: 20%;">
                                        Status
                                    </th>
                                    <th style="width: 35%;">
                                        Qeyd
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTableBody">
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Yüklənir...</span>
                                        </div>
                                        <div class="mt-2">Məlumatlar yüklənir...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="studentInfoModal" tabindex="-1" aria-labelledby="studentInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentInfoModalLabel">
                        <i class="fas fa-user-graduate me-2"></i>
                        Tələbə Məlumatları
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="studentInfoContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yüklənir...</span>
                        </div>
                        <div class="mt-2">Məlumatlar yüklənir...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Bağla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let updateTimeout = null;
        const API_BASE_URL = 'movzular/davamiyyet/jurnal.php';
        
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('editDatetime').value = today;
            loadGroups();
            loadStudents();
        });
        
        function showNotification(message, type = 'info') {
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(n => n.remove());
            
            const alertClass = type === 'error' ? 'alert-danger' : 
                              type === 'success' ? 'alert-success' : 'alert-info';
            
            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} notification alert-dismissible fade show`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 500px;
            `;
            notification.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
        
        function loadGroups() {
            const groupSelect = document.getElementById('journalGroupFilter');
            groupSelect.disabled = true;
            
            fetch(API_BASE_URL)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    groupSelect.innerHTML = '<option value="">Bütün qruplar</option>';
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(group => {
                            const option = document.createElement('option');
                            option.value = group;
                            option.textContent = group;
                            groupSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading groups:', error);
                    showNotification('Qruplar yüklənərkən xəta baş verdi: ' + error.message, 'error');
                })
                .finally(() => {
                    groupSelect.disabled = false;
                });
        }
        
        function loadStudents() {
            const groupFilter = document.getElementById('journalGroupFilter').value;
            const tableBody = document.getElementById('attendanceTableBody');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yüklənir...</span>
                        </div>
                        <div class="mt-2">Tələbələr yüklənir...</div>
                    </td>
                </tr>
            `;
            
            const url = groupFilter ? 
                `${API_BASE_URL}?group=${encodeURIComponent(groupFilter)}` : 
                `${API_BASE_URL}?all=1`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    tableBody.innerHTML = '';
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    if (data.message) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <div>${data.message}</div>
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    if (!Array.isArray(data) || data.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-user-slash fa-2x mb-2"></i>
                                    <div>Tələbə tapılmadı</div>
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    data.forEach(student => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                    </div>
                                    <div>
                                        <div class="m-1 fw-bold">${escapeHtml(student.username)}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select class="form-select status-select" data-student-id="${student.id}">
                                    <option value="Istirak_edir" ${student.status === "Istirak_edir" ? "selected" : ""}>İştirak edir</option>
                                    <option value="Istirak_etmir" ${student.status === "Istirak_etmir" ? "selected" : ""}>İştirak etmir</option>
                                    <option value="Uzrli" ${student.status === "Uzrli" ? "selected" : ""}>Üzrli</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control qeyd-input" 
                                       placeholder="Qeyd əlavə edin..."
                                       data-student-id="${student.id}"
                                       value="${escapeHtml(student.qeyd || '')}">
                            </td>
                            <td>
                                <button type="button" class="m-1 btn btn-primary btn-sm" onclick="showStudentInfo(${student.id})">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                    
                    addEventListeners();
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Tələbələr yüklənərkən xəta baş verdi: ${error.message}
                                </div>
                            </td>
                        </tr>
                    `;
                    showNotification('Tələbələr yüklənərkən xəta baş verdi: ' + error.message, 'error');
                });
        }
        
        function addEventListeners() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function() {
                    const studentId = this.dataset.studentId;
                    const status = this.value;
                    const qeydInput = document.querySelector(`.qeyd-input[data-student-id="${studentId}"]`);
                    const qeyd = qeydInput ? qeydInput.value : '';
                    
                    updateStudentData(studentId, status, qeyd);
                });
            });
            
            document.querySelectorAll('.qeyd-input').forEach(input => {
                input.addEventListener('input', function() {
                    const studentId = this.dataset.studentId;
                    const qeyd = this.value;
                    const statusSelect = document.querySelector(`.status-select[data-student-id="${studentId}"]`);
                    const status = statusSelect ? statusSelect.value : 'Istirak_edir';
                    
                    if (updateTimeout) {
                        clearTimeout(updateTimeout);
                    }
                    
                    updateTimeout = setTimeout(() => {
                        updateStudentData(studentId, status, qeyd);
                    }, 1000);
                });
            });
        }
        
        function updateStudentData(studentId, status, qeyd) {
            const data = {
                studentId: parseInt(studentId),
                status: status,
                qeyd: qeyd
            };
            
            const statusSelect = document.querySelector(`.status-select[data-student-id="${studentId}"]`);
            const qeydInput = document.querySelector(`.qeyd-input[data-student-id="${studentId}"]`);
            
            if (statusSelect) statusSelect.style.opacity = '0.6';
            if (qeydInput) qeydInput.style.opacity = '0.6';
            
            fetch(API_BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.error) {
                    throw new Error(result.error);
                }
            })
            .catch(error => {
                console.error('Error updating student data:', error);
                showNotification('Məlumat yenilənərkən xəta baş verdi: ' + error.message, 'error');
            })
            .finally(() => {
                if (statusSelect) statusSelect.style.opacity = '1';
                if (qeydInput) qeydInput.style.opacity = '1';
            });
        }
        
        function showStudentInfo(studentId) {
            const modal = new bootstrap.Modal(document.getElementById('studentInfoModal'));
            const modalContent = document.getElementById('studentInfoContent');
            
            modalContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yüklənir...</span>
                    </div>
                    <div class="mt-2">Tələbə məlumatları yüklənir...</div>
                </div>
            `;
            
            modal.show();
            
            fetch(`${API_BASE_URL}?id=${studentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(student => {
                    if (student.error) {
                        throw new Error(student.error);
                    }
                    
                    const defaultAvatar = `
                        <div class="d-flex align-items-center justify-content-center bg-primary text-white" 
                             style="width: 100px; height: 100px; border-radius: 50%; font-size: 36px;">
                            ${student.username ? student.username.charAt(0).toUpperCase() : 'T'}
                        </div>
                    `;
                    
                    const cinsLabel = student.cins === '1' ? 'Qadın' : student.cins === '0' ? 'Kişi' : 'Məlum deyil';
                    
                    let html = `
                        <div class="student-info">
                            <div class="row mb-4">
                                <div class="col-md-3 text-center">
                                    <div class="student-photo">
                    `;
                    
                    if (student.photo && student.photo.trim() !== '') {
                        html += `
                            <img src="${student.photo}" 
                                 alt="${escapeHtml(student.username)}"
                                 class="img-thumbnail"
                                 style="width: 100px; height: 100px; object-fit: cover;"
                                 onerror="this.parentNode.innerHTML='${defaultAvatar.replace(/'/g, "\\'")}'"
                                 loading="lazy">
                        `;
                    } else {
                        html += defaultAvatar;
                    }
                    
                    html += `
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <h4 class="mb-3">${escapeHtml(student.username || 'Adsız tələbə')}</h4>
                                    <p><strong>Sinif:</strong> ${escapeHtml(student.sinif || 'Məlum deyil')}</p>
                                    ${student.muellim_adi && student.muellim_adi.length > 0 ? 
                                        `<p><strong>Müəllimlər:</strong> ${student.muellim_adi.join(', ')}</p>` : 
                                        ''}
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6"><strong>Doğum tarixi:</strong> ${escapeHtml(student.dogum_tarixi || 'Məlum deyil')}</div>
                                <div class="col-md-6"><strong>Cins:</strong> ${cinsLabel}</div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6"><strong>Ünvan:</strong> ${escapeHtml(student.unvan || 'Məlum deyil')}</div>
                                <div class="col-md-6"><strong>Orta bal:</strong> ${escapeHtml(student.orta_bal || 'Məlum deyil')}</div>
                            </div>
                    `;
                    
                    if (student.ata || student.ana) {
                        html += `
                            <h5 class="mt-4 mb-3">
                                <i class="fas fa-users me-2"></i>
                                Valideyn məlumatları
                            </h5>
                            <div class="row mb-2">
                                <div class="col-md-6"><strong>Ata:</strong> ${escapeHtml(student.ata || 'Məlum deyil')}</div>
                                <div class="col-md-6"><strong>Əlaqə nömrəsi:</strong> ${escapeHtml(student.elaqe_nomre_ata || 'Məlum deyil')}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6"><strong>Ana:</strong> ${escapeHtml(student.ana || 'Məlum deyil')}</div>
                                <div class="col-md-6"><strong>Əlaqə nömrəsi:</strong> ${escapeHtml(student.elaqe_nomre_ana || 'Məlum deyil')}</div>
                            </div>
                        `;
                    }
                    
                    if (student.riyaziyyat || student.fizika || student.kimya || student.biologiya || student.tarix || student.edebiyyat) {
                        html += `
                            <h5 class="mt-4 mb-3">
                                <i class="fas fa-graduation-cap me-2"></i>
                                Fənn balları
                            </h5>
                            <div class="row">
                                <div class="col-md-4"><strong>Riyaziyyat:</strong> ${escapeHtml(student.riyaziyyat || 'Məlum deyil')}</div>
                                <div class="col-md-4"><strong>Fizika:</strong> ${escapeHtml(student.fizika || 'Məlum deyil')}</div>
                                <div class="col-md-4"><strong>Kimya:</strong> ${escapeHtml(student.kimya || 'Məlum deyil')}</div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4"><strong>Biologiya:</strong> ${escapeHtml(student.biologiya || 'Məlum deyil')}</div>
                                <div class="col-md-4"><strong>Tarix:</strong> ${escapeHtml(student.tarix || 'Məlum deyil')}</div>
                                <div class="col-md-4"><strong>Ədəbiyyat:</strong> ${escapeHtml(student.edebiyyat || 'Məlum deyil')}</div>
                            </div>
                        `;
                    }
                    
                    if (student.qeyd && student.qeyd.trim() !== '') {
                        html += `
                            <h5 class="mt-4 mb-3">
                                <i class="fas fa-sticky-note me-2"></i>
                                Əlavə məlumat
                            </h5>
                            <div class="alert alert-info">
                                ${escapeHtml(student.qeyd)}
                            </div>
                        `;
                    }
                    
                    html += `</div>`;
                    
                    modalContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching student info:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Məlumat yüklənərkən xəta baş verdi: ${error.message}
                        </div>
                    `;
                });
        }
        
        function escapeHtml(text) {
            if (typeof text !== 'string') return text || '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
