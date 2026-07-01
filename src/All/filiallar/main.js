let filials = [];
let teachers = [];
let currentTeacher = null;
let selectedSchedule = [];
let allTeachersData = [];
let cedvelCurrentTeacher = null;
let cedvelSelectedSchedule = [];
let customTimeSlots = [];
let cedvelCustomTimeSlots = [];
let selectedFilialName = '';
let currentTeacherFilials = [];
let activeTab = 'filials';

const days = ['Bazar ertəsi', 'Çərşənbə axşamı', 'Çərşənbə', 'Cümə axşamı', 'Cümə', 'Şənbə', 'Bazar'];
const timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];

async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

function showTab(tabName) {
    try {
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });

        const selectedTab = document.querySelector(`[data-tab="${tabName}"]`);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }

        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        const selectedContent = document.getElementById(tabName);
        if (selectedContent) {
            selectedContent.classList.add('active');
        }

        activeTab = tabName;
        localStorage.setItem('lastActiveTab', tabName);
        loadTabData(tabName);
        updateTabIndicators(tabName);

    } catch (error) {
        console.error('Error switching tab:', error);
        handleTabError(tabName, error);
    }
}

function loadTabData(tabName) {
    try {
        switch (tabName) {
            case 'filials':
                loadFilialsFromDB();
                break;
            case 'teachers':
                displayTeacherUsernames();
                break;
            case 'cedvel':
                loadFilialSelectOptions();
                break;
            case 'students':
                break;
            default:
        }
    } catch (error) {
        console.error(`Error loading data for tab ${tabName}:`, error);
        handleTabError(tabName, error);
    }
}

function loadLastActiveTab() {
    const lastActiveTab = localStorage.getItem('lastActiveTab');
    if (lastActiveTab && isValidTab(lastActiveTab)) {
        const tabButton = document.querySelector(`[data-tab="${lastActiveTab}"]`);
        if (tabButton) {
            showTab(lastActiveTab);
            return true;
        }
    }

    showTab('filials');
    return false;
}

function isValidTab(tabName) {
    const validTabs = ['filials', 'teachers', 'cedvel', 'students'];
    return validTabs.includes(tabName);
}

function updateTabIndicators(tabName) {
    const tabDisplayNames = {
        'filials': 'Filiallar',
        'teachers': 'Müəllimlər',
        'cedvel': 'Cədvəl',
        'students': 'Tələbələr'
    };

    const displayName = tabDisplayNames[tabName] || tabName;
    document.title = `${displayName} - İdarəetmə Sistemi`;
}

document.addEventListener('DOMContentLoaded', function () {
    try {
        const navTabs = document.querySelectorAll('.nav-tab');
        navTabs.forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                const targetTab = this.dataset.tab;
                if (targetTab && isValidTab(targetTab)) {
                    showTab(targetTab);
                }
            });
        });

        loadLastActiveTab();

        const filialForm = document.getElementById('filial-form');
        if (filialForm) {
            filialForm.addEventListener('submit', handleFilialSubmitDB);
        }

        const teacherForm = document.getElementById('teacher-update-form');
        if (teacherForm) {
            teacherForm.addEventListener('submit', handleTeacherUpdate);
        }

        setupCedvelEventListeners();
        initializeNotificationSystem();
        setupKeyboardShortcuts();

    } catch (error) {
        console.error('Error initializing system:', error);
        showNotification('Sistem başladılarkən xəta baş verdi!', 'error');
    }
});

function setupCedvelEventListeners() {
    const filialSelect = document.getElementById('filial_select');
    if (filialSelect) {
        filialSelect.addEventListener('change', function () {
            const selectedFilial = this.value;
            if (selectedFilial) {
                loadFennByFilial(selectedFilial);
            } else {
                hideCedvelSections();
            }
        });
    }

    const fennSelect = document.getElementById('fenn_select');
    if (fennSelect) {
        fennSelect.addEventListener('change', function () {
            const selectedFenn = this.value;
            const filialSelect = document.getElementById('filial_select');
            const selectedFilial = filialSelect ? filialSelect.value : '';

            if (selectedFenn && selectedFilial) {
                loadTeachersByFilialAndFenn(selectedFilial, selectedFenn);
            } else {
                hideTeacherSection();
            }
        });
    }

    const teacherSelect = document.getElementById('teacher_select');
    if (teacherSelect) {
        teacherSelect.addEventListener('change', function () {
            const selectedTeacher = this.value;
            if (selectedTeacher) {
                showActionButtons();
            } else {
                hideActionButtons();
            }
        });
    }
}

function hideCedvelSections() {
    const fennContainer = document.getElementById('fenn_selection_container');
    const teacherContainer = document.getElementById('teacher_selection_container');
    const actionContainer = document.getElementById('action_buttons_container');
    if (fennContainer) fennContainer.style.display = 'none';
    if (teacherContainer) teacherContainer.style.display = 'none';
    if (actionContainer) actionContainer.style.display = 'none';
    const fennSelect = document.getElementById('fenn_select');
    const teacherSelect = document.getElementById('teacher_select');
    if (fennSelect) fennSelect.innerHTML = '<option value="">Seçin...</option>';
    if (teacherSelect) teacherSelect.innerHTML = '<option value="">Seçin...</option>';
}

function hideTeacherSection() {
    const teacherContainer = document.getElementById('teacher_selection_container');
    const actionContainer = document.getElementById('action_buttons_container');
    if (teacherContainer) teacherContainer.style.display = 'none';
    if (actionContainer) actionContainer.style.display = 'none';
    const teacherSelect = document.getElementById('teacher_select');
    if (teacherSelect) teacherSelect.innerHTML = '<option value="">Seçin...</option>';
}

function showActionButtons() {
    const actionContainer = document.getElementById('action_buttons_container');
    const loadScheduleBtn = document.getElementById('load-schedule-btn');
    if (actionContainer) actionContainer.style.display = 'block';
    if (loadScheduleBtn) loadScheduleBtn.style.display = 'inline-block';
}

function hideActionButtons() {
    const actionContainer = document.getElementById('action_buttons_container');
    if (actionContainer) actionContainer.style.display = 'none';
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
        if (e.altKey && !e.ctrlKey && !e.shiftKey) {
            const tabMap = {
                '1': 'filials',
                '2': 'teachers',
                '3': 'cedvel',
                '4': 'students'
            };

            if (tabMap[e.key]) {
                e.preventDefault();
                showTab(tabMap[e.key]);
            }
        }

        if (e.ctrlKey && e.key === 'r' && e.shiftKey) {
            e.preventDefault();
            refreshCurrentTab();
        }
    });
}

function initializeNotificationSystem() {
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 350px;
                pointer-events: none;
            `;
        document.body.appendChild(container);
    }
}

function showNotification(message, type = 'info', duration = 3000) {
    const container = document.getElementById('notification-container') || document.body;
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            word-wrap: break-word;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-left: 4px solid rgba(255,255,255,0.3);
        `;

    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };

    notification.style.backgroundColor = colors[type] || colors.info;
    const messageSpan = document.createElement('span');
    messageSpan.textContent = message;
    messageSpan.style.display = 'block';
    messageSpan.style.marginRight = '25px';
    const closeBtn = document.createElement('span');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = `
            position: absolute;
            top: 10px;
            right: 15px;
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
            opacity: 0.7;
        `;
    closeBtn.onmouseover = () => closeBtn.style.opacity = '1';
    closeBtn.onmouseout = () => closeBtn.style.opacity = '0.7';
    closeBtn.onclick = (e) => {
        e.stopPropagation();
        removeNotification(notification);
    };

    notification.appendChild(messageSpan);
    notification.appendChild(closeBtn);
    container.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);

    const timeoutId = setTimeout(() => {
        removeNotification(notification);
    }, duration);

    notification.onclick = () => {
        clearTimeout(timeoutId);
        removeNotification(notification);
    };

    return notification;
}

function removeNotification(notification) {
    if (notification && notification.parentNode) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

function refreshCurrentTab() {
    try {
        const activeTabElement = document.querySelector('.nav-tab.active');
        if (activeTabElement) {
            const tabName = activeTabElement.dataset.tab;
            loadTabData(tabName);
            showNotification(`${getTabDisplayName(tabName)} bölməsi yeniləndi`, 'info', 2000);
        }
    } catch (error) {
        console.error('Error refreshing tab:', error);
        showNotification('Bölmə yenilənərkən xəta baş verdi!', 'error');
    }
}

function getTabDisplayName(tabName) {
    const displayNames = {
        'filials': 'Filiallar',
        'teachers': 'Müəllimlər',
        'cedvel': 'Cədvəl',
        'students': 'Tələbələr'
    };
    return displayNames[tabName] || tabName;
}

function handleTabError(tabName, error) {
    console.error(`Error in tab ${tabName}:`, error);
    showNotification(`${getTabDisplayName(tabName)} bölməsində xəta baş verdi`, 'error');
}

function getCurrentTab() {
    return activeTab;
}

function isTabActive(tabName) {
    return activeTab === tabName;
}

async function loadFilialsFromDB() {
    if (!isTabActive('filials')) return;

    try {
        const result = await apiRequest('filiallar/filiallar_operations.php?action=list');
        if (result.status === 'success') {
            filials = result.data;
            displayFilials();
            updateFilialSelects();
        } else {
            console.error('Database error:', result.message);
            showNotification(result.message || 'Filiallar yüklənə bilmədi!', 'error');
            displayFilials();
        }
    } catch (error) {
        console.error('Error loading filials:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
        displayFilials();
    }
}

async function handleFilialSubmitDB(e) {
    e.preventDefault();
    const name = document.getElementById('filial-name').value.trim();
    const address = document.getElementById('filial-address').value.trim();
    const phone = document.getElementById('filial-phone').value.trim();

    if (!name || !address || !phone) {
        showNotification('Bütün sahələri doldurun!', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'insert');
    formData.append('filial_adi', name);
    formData.append('unvan', address);
    formData.append('telefon', phone);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Əlavə edilir...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('filiallar/filiallar_operations.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status === 'success') {
            e.target.reset();
            showNotification('Filial uğurla əlavə edildi!', 'success');
            setTimeout(() => {
                location.reload();
            }, 800);
            await loadFilialsFromDB();
        } else {
            showNotification(result.message || 'Xəta baş verdi!', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function deleteFilial(filialId, event) {
    event.stopPropagation();
    if (!confirm('Bu filialı silmək istədiyinizə əminsiniz?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', filialId);

    try {
        const response = await fetch('filiallar/filiallar_operations.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status === 'success') {
            showNotification('Filial uğurla silindi!', 'success');
            setTimeout(() => {
                location.reload();
            }, 800);
            await loadFilialsFromDB();
        } else {
            showNotification(result.message || 'Xəta baş verdi!', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

function displayFilials() {
    const grid = document.getElementById('filials-grid');

    if (filials.length === 0) {
        grid.innerHTML = `
                <div class="m-2 empty-state">
                    <div class="form-group">
                        <p>Hələ filial əlavə edilməyib</p>
                    </div>
                </div>
            `;
        return;
    }

    let gridHtml = '';
    filials.forEach(filial => {
        gridHtml += `
                <div class="grid-item" onclick="showFilialDetails(${filial.id})">
                    <div class="header-container">
                        <h3><i class="fas fa-building"></i> ${filial.name}</h3>
                        <button onclick="deleteFilial(${filial.id}, event)" style="height: 40px; width: 40px;" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="info">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${filial.address}</span>
                    </div>
                    <div class="info">
                        <i class="fas fa-phone"></i>
                        <span>${filial.phone}</span>
                    </div>
                    <div class="info">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>${filial.teacher_count} müəllim</span>
                    </div>
                </div>
            `;
    });

    grid.innerHTML = gridHtml;
}

async function showFilialDetails(filialId) {
    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_filial_details&id=${filialId}`);
        if (result.status === 'success') {
            const { filial, teachers } = result.data;
            document.getElementById('filial-modal-title').textContent = `${filial.name} - Təfərrüatlar`;

            let content = `
                    <div class="filial-info-grid">
                        <div class="filial-info-item">
                            <div class="filial-info-label">Ünvan</div>
                            <div class="filial-info-value">${filial.address}</div>
                        </div>
                        <div class="filial-info-item">
                            <div class="filial-info-label">Telefon</div>
                            <div class="filial-info-value">${filial.phone}</div>
                        </div>
                        <div class="filial-info-item">
                            <div class="filial-info-label">Müəllim sayı</div>
                            <div class="filial-info-value">${teachers.length}</div>
                        </div>
                    </div>
                    <div class="teachers-section">
                        <h3>Müəllimlər:</h3>
                `;

            if (teachers.length === 0) {
                content += `
                        <div class="empty-teachers">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <p>Bu filialda hələ müəllim yoxdur.</p>
                        </div>
                    `;
            } else {
                teachers.forEach(teacher => {
                    content += `
                            <div class="teacher-item">
                                <div class="teacher-info">
                                    <h4>${teacher.username}</h4>
                                    <div class="teacher-details">
                                        <span><i class="fas fa-book"></i> ${teacher.subject}</span>
                                    </div>
                                </div>
                                <div class="teacher-actions">
                                    <button class="btn btn-danger" onclick="removeTeacherFromFilial(${teacher.id}, '${teacher.username}', '${filial.name}', ${filial.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                });
            }

            content += '</div>';

            document.getElementById('filial-modal-body').innerHTML = content;
            document.getElementById('filial-modal').classList.add('show');
        } else {
            showNotification(result.message || 'Filial məlumatları yüklənə bilmədi!', 'error');
        }
    } catch (error) {
        console.error('Error loading filial details:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

async function displayTeacherUsernames() {
    if (!isTabActive('teachers')) return;
    const grid = document.getElementById('teacher-usernames-grid');
    const card = document.getElementById('teacher-usernames-card');

    try {
        const result = await apiRequest('filiallar/filiallar_operations.php?action=get_teacher_usernames');
        if (result.status === 'error') {
            grid.innerHTML = '<p class="no-data">Müəllim məlumatları yüklənə bilmədi</p>';
            card.innerHTML = '<p class="no-data">Müəllim məlumatları yüklənə bilmədi</p>';
            return;
        }

        const usernames = result.data || [];
        if (usernames.length === 0) {
            grid.innerHTML = '<p class="no-data">Müəllim tapılmadı</p>';
            card.innerHTML = '<p class="no-data">Müəllim tapılmadı</p>';
            return;
        }

        let gridHtml = '';
        usernames.forEach(username => {
            gridHtml += `<div class='teacher-item-clickable' data-username='${username}' onclick='openTeacherModal("${username}")'>${username}</div>`;
        });

        let cardHtml = '';
        for (const username of usernames) {
            try {
                const teacherResult = await apiRequest(`filiallar/filiallar_operations.php?action=get_teacher_by_username&username=${encodeURIComponent(username)}`);

                if (teacherResult.status === 'success') {
                    const teacher = teacherResult.data || {};
                    const subject = teacher.tehsil_ve_ixtisas || 'Bilinmir';
                    let filialsList = 'Naməlum';
                    let filialsArray = [];
                    if (teacher.filial_adi) {
                        try {
                            filialsArray = JSON.parse(teacher.filial_adi);
                            if (Array.isArray(filialsArray) && filialsArray.length > 0) {
                                if (filialsArray.length <= 3) {
                                    filialsList = filialsArray.join(', ');
                                } else {
                                    filialsList = filialsArray.slice(0, 3).join(', ') + '...';
                                }
                            }
                        } catch (e) {
                            filialsList = teacher.filial_adi;
                            filialsArray = [teacher.filial_adi];
                        }
                    }

                    cardHtml += `
                            <div class="grid-item">
                                <h4><i class="fas fa-user"></i> ${teacher.username}</h4>
                                <div class='teacher-card-content'>
                                    <div class='teacher-details-right'>
                                        <div class='teacher-detail-item'>
                                            <span class='detail-label'>Fənn:</span>
                                            <span class='detail-value'>${subject}</span>
                                        </div>
                                        <div class='teacher-detail-item'>
                                            <span class='detail-label'>Filiallar:</span>
                                            <span class='d-none detail-value'>${filialsList}</span>
                                            ${filialsArray.length > 0 ? `<button style="font-family:Arial; font-weight:bold; max-height:30px; width:auto" class="btn btn-sm btn-outline-info ms-2" onclick="event.stopPropagation(); showTeacherFilialsModal('${username}', ${JSON.stringify(filialsArray).replace(/"/g, '&quot;')})">Ətraflı</button>` : 'Naməlum'}
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                                        <button class="btn btn-primary" onclick="event.stopPropagation(); openScheduleModal('${username}', ${teacher.id})" style="flex: 1; font-size: 0.85rem; padding: 0.8rem;">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                        <button class="btn btn-info" onclick="event.stopPropagation(); showTeacherScheduleModal(${teacher.id}, '${username}')" style="flex: 1; font-size: 0.85rem; padding: 0.8rem;">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                }
            } catch (error) {
                console.error('Error loading teacher details for:', username, error);
            }
        }

        grid.innerHTML = gridHtml;
        card.innerHTML = cardHtml;
    } catch (error) {
        console.error('Error loading teacher data:', error);
        grid.innerHTML = '<p class="no-data">Serverə qoşulmaq mümkün olmadı!</p>';
        card.innerHTML = '<p class="no-data">Serverə qoşulmaq mümkün olmadı!</p>';
    }
}

function showTeacherFilialsModal(username, filials) {
    let modal = document.getElementById('teacherFilialsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'teacherFilialsModal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.innerHTML = `
                <div class="modal-dialog new_modal" role="document" style="max-width: 600px;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="teacherFilialsModalTitle">Müəllimin Filialları</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="teacherFilialsModalBody">
                            <!-- Филиалы будут загружены здесь -->
                        </div>
                        <div class="modal-footer">
                            <button id="teacherFilialsModalCloseBtn" type="button" class="btn btn-secondary">Bağla</button>
                        </div>
                    </div>
                </div>
            `;
        document.body.appendChild(modal);
    }

    document.getElementById('teacherFilialsModalTitle').textContent = `${username} - Filialları`;

    let filialsHtml = '<div class="list-group">';
    filials.forEach((filial, index) => {
        filialsHtml += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-building text-primary me-2"></i>
                        <strong>${filial}</strong>
                    </div>
                </div>
            `;
    });
    filialsHtml += '</div>';

    filialsHtml = `
            <div class="mb-3">
                <h6><i class="fas fa-info-circle text-info"></i> Ümumi məlumat:</h6>
                <p class="text-muted mb-2">Müəllim: <strong>${username}</strong></p>
                <p class="text-muted mb-3">Cəmi filial sayı: <strong>${filials.length}</strong></p>
            </div>
            <div class="mb-3">
                <h6><i class="fas fa-list text-success"></i> Filialların siyahısı:</h6>
            </div>
        ` + filialsHtml;

    document.getElementById('teacherFilialsModalBody').innerHTML = filialsHtml;

    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();

    const closeBtn = document.getElementById('teacherFilialsModalCloseBtn');
    if (closeBtn) {
        closeBtn.onclick = function () {
            bootstrapModal.hide();
        };
    }
}

async function openTeacherModal(username) {
    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_teacher_by_username&username=${encodeURIComponent(username)}`);

        if (result.status === 'success') {
            const teacher = result.data;
            document.getElementById('modal-username').textContent = username;
            document.getElementById('modal-username-hidden').value = username;
            document.getElementById('modal-teacher-id').value = teacher.id;
            await loadFilialsForTeacherModal(teacher.filial_adi);
            const modal = new bootstrap.Modal(document.getElementById('teacherModal'));
            modal.show();
        } else {
            showNotification('Müəllim məlumatları yüklənə bilmədi!', 'error');
        }
    } catch (error) {
        console.error('Error loading teacher details:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

async function loadFilialsForTeacherModal(currentFilials) {
    try {
        const result = await apiRequest('filiallar/filiallar_operations.php?action=get_filial_details');
        if (result.status === 'success') {
            const container = document.getElementById('modal-filial-selects-container');

            let selectedFilials = [];
            if (currentFilials) {
                try {
                    selectedFilials = JSON.parse(currentFilials);
                    if (!Array.isArray(selectedFilials)) {
                        selectedFilials = [currentFilials];
                    }
                } catch (e) {
                    selectedFilials = [currentFilials];
                }
            }

            let html = '';
            result.data.forEach((filial, index) => {
                const isChecked = selectedFilials.includes(filial.filial_adi) ? 'checked' : '';
                html += `
                    <div class="d-inline-block" style="margin-bottom: 8px;">
                        <label class="form-check-label" for="filial-${index}">
                            ${filial.filial_adi}
                            <input class="form-check-input" type="checkbox" value="${filial.filial_adi}"
                                id="filial-${index}" name="filials[]" ${isChecked}>
                        </label>
                    </div>
                    `;
            });

            container.innerHTML = html;
        } else {
            console.error('Error loading filials:', result.message);
        }
    } catch (error) {
        console.error('Error loading filials for modal:', error);
    }
}

async function handleTeacherUpdate(e) {
    e.preventDefault();
    const checkboxes = document.querySelectorAll('input[name="filials[]"]:checked');
    const selectedFilials = Array.from(checkboxes).map(cb => cb.value);
    const formData = new FormData();
    formData.append('action', 'update_teacher');
    formData.append('teacher_id', document.getElementById('modal-teacher-id').value);
    formData.append('username', document.getElementById('modal-username-hidden').value);
    formData.append('filial_adi', JSON.stringify(selectedFilials));
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yenilənir...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('filiallar/filiallar_operations.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            showNotification('Müəllim məlumatları yeniləndi!', 'success');
            setTimeout(() => {
                location.reload();
            }, 800);

            const modal = bootstrap.Modal.getInstance(document.getElementById('teacherModal'));
            if (modal) {
                modal.hide();
            }

            await displayTeacherUsernames();
        } else {
            showNotification(result.message || 'Xəta baş verdi!', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function removeTeacherFromFilial(teacherId, username, filialName, filialId) {
    if (!confirm(`${username} müəllimini ${filialName} filialından silmək istədiyinizə əminsiniz?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'remove_teacher_from_filial');
        formData.append('teacher_id', teacherId);
        formData.append('username', username);
        formData.append('filial_name', filialName);

        const response = await fetch('filiallar/filiallar_operations.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        showNotification(result.message, result.status);

        if (result.status === 'success') {
            setTimeout(() => {
                location.reload();
            }, 800);
            showFilialDetails(filialId);
        }
    } catch (error) {
        console.error('Error removing teacher from filial:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

function handleFilialSelection() {
    const select = document.getElementById('filial-select');
    selectedFilialName = select.value;
    if (selectedFilialName && currentTeacher) {
        loadScheduleForFilial(selectedFilialName);
        showNotification(`${selectedFilialName} filialı seçildi`, 'info');
    } else {
        selectedSchedule = [];
        generateScheduleGrid();
    }
}

async function loadScheduleForFilial(filialName) {
    if (!currentTeacher || !filialName) return;

    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_cedvel_by_filial&teacher_id=${currentTeacher.id}&username=${encodeURIComponent(currentTeacher.username)}&filial_name=${encodeURIComponent(filialName)}`);
        if (result && result.status === 'success') {
            selectedSchedule = result.data || [];
            if (result.has_schedule) {
                showNotification(`${filialName} filialı üçün mövcud cədvəl yükləndi`, 'success');
            } else {
                showNotification(`${filialName} filialı üçün yeni cədvəl yaradılır`, 'info');
            }
        } else {
            selectedSchedule = [];
            showNotification(`${filialName} filialı üçün yeni cədvəl yaradılır`, 'info');
        }

        generateScheduleGrid();
    } catch (error) {
        console.error('Error loading schedule for filial:', error);
        selectedSchedule = [];
        generateScheduleGrid();
        showNotification('Cədvəl yüklənərkən xəta baş verdi!', 'error');
    }
}

function updateFilialSelectOptions() {
    const filialSelect = document.getElementById('filial-select');
    if (!filialSelect) return;

    filialSelect.innerHTML = '<option value="">Filial seçin...</option>';
    currentTeacherFilials.forEach(filial => {
        const option = document.createElement('option');
        option.value = filial;
        option.textContent = filial;
        filialSelect.appendChild(option);
    });

    if (currentTeacherFilials.length === 0) {
        filialSelect.innerHTML = '<option value="">Bu müəllimin filialı yoxdur</option>';
        filialSelect.disabled = true;
    } else {
        filialSelect.disabled = false;
    }
}

async function openScheduleModal(username, teacherId) {
    currentTeacher = { username, id: teacherId };
    selectedSchedule = [];
    selectedFilialName = '';
    document.getElementById('schedule-modal-title').textContent = `${username} - Cədvəl Təyini`;
    document.getElementById('schedule-modal').classList.add('show');

    try {
        const teacherResult = await apiRequest(`filiallar/filiallar_operations.php?action=get_teacher_by_username&username=${encodeURIComponent(username)}`);

        if (teacherResult.status === 'success' && teacherResult.data.filial_adi) {
            try {
                currentTeacherFilials = JSON.parse(teacherResult.data.filial_adi);
                if (!Array.isArray(currentTeacherFilials)) {
                    currentTeacherFilials = [teacherResult.data.filial_adi];
                }
            } catch (e) {
                currentTeacherFilials = [teacherResult.data.filial_adi];
            }
        } else {
            currentTeacherFilials = [];
        }
    } catch (error) {
        console.error('Error loading teacher filials:', error);
        currentTeacherFilials = [];
    }

    updateFilialSelectOptions();
    document.getElementById('schedule-modal-body').innerHTML = `
            <div class="custom-time-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 15px; color: #495057;">
                    <i class="fas fa-clock"></i> Xüsusi Vaxt Əlavə Et
                </h4>
                <div style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 120px;">
                        <label for="custom-time-input" style="display: block; margin-bottom: 5px; font-weight: 500;">Vaxt:</label>
                        <input type="time" id="custom-time-input" class="form-control" style="padding: 8px;">
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <label for="custom-day-select" style="display: block; margin-bottom: 5px; font-weight: 500;">Gün:</label>
                        <select id="custom-day-select" class="form-control" style="padding: 8px;">
                            <option value="">Gün seçin</option>
                            <option value="Bazar ertəsi">Bazar ertəsi</option>
                            <option value="Çərşənbə axşamı">Çərşənbə axşamı</option>
                            <option value="Çərşənbə">Çərşənbə</option>
                            <option value="Cümə axşamı">Cümə axşamı</option>
                            <option value="Cümə">Cümə</option>
                            <option value="Şənbə">Şənbə</option>
                            <option value="Bazar">Bazar</option>
                        </select>
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <label for="custom-note-input" style="display: block; margin-bottom: 5px; font-weight: 500;">Qeyd (İstəyə bağlı):</label>
                        <input type="text" id="custom-note-input" class="form-control" placeholder="Qeyd əlavə edin" style="padding: 8px;">
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" onclick="addCustomTimeSlot()" style="padding: 8px 16px;">
                            <i class="fas fa-plus"></i> Əlavə Et
                        </button>
                    </div>
                </div>
            </div>
            <div id="schedule-loading" style="text-align: center; padding: 20px;">
                <i class="fas fa-info-circle"></i> Filial seçin və cədvəl yaradın
            </div>
        `;
}

function addCustomTimeSlot() {
    const timeInput = document.getElementById('custom-time-input');
    const daySelect = document.getElementById('custom-day-select');
    const noteInput = document.getElementById('custom-note-input');
    const time = timeInput.value;
    const day = daySelect.value;
    const note = noteInput.value.trim();

    if (!selectedFilialName) {
        showNotification('Zəhmət olmasa əvvəlcə filial seçin!', 'warning');
        return;
    }

    if (!time || !day) {
        showNotification('Zəhmət olmasa vaxt və gün seçin!', 'warning');
        return;
    }

    const exists = selectedSchedule.some(slot =>
        slot[0] === time && slot[1] === day
    );

    if (exists) {
        showNotification('Bu vaxt və gün artıq seçilmişdir!', 'warning');
        return;
    }

    selectedSchedule.push([time, day, note]);
    timeInput.value = '';
    daySelect.value = '';
    noteInput.value = '';
    generateScheduleGrid();
    showNotification('Xüsusi vaxt əlavə edildi!', 'success');
}

function generateScheduleGrid() {
    const allTimeSlots = [...new Set([...timeSlots, ...selectedSchedule.map(slot => slot[0])])].sort();
    let content = `
            <div class="custom-time-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h5 style="margin-bottom: 10px; color: #495057;">
                    <i class="fas fa-clock"></i> Xüsusi Vaxt Əlavə Et
                </h5>
                <div style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 120px;">
                        <input type="time" id="custom-time-input" class="form-control" style="padding: 8px;">
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <select id="custom-day-select" class="form-control" style="padding: 8px;">
                            <option value="">Gün seçin</option>
                            <option value="Bazar ertəsi">Bazar ertəsi</option>
                            <option value="Çərşənbə axşamı">Çərşənbə axşamı</option>
                            <option value="Çərşənbə">Çərşənbə</option>
                            <option value="Cümə axşamı">Cümə axşamı</option>
                            <option value="Cümə">Cümə</option>
                            <option value="Şənbə">Şənbə</option>
                            <option value="Bazar">Bazar</option>
                        </select>
                    </div>
                    <div style="flex: 2; min-width: 150px;">
                        <input type="text" id="custom-note-input" class="form-control" placeholder="Qeyd əlavə edin" style="padding: 8px;">
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" onclick="addCustomTimeSlot()" style="padding: 8px 16px;">
                        Əlavə Et
                        </button>
                    </div>
                </div>
            </div>
            <div class="schedule-grid">
        `;

    content += '<div class="schedule-header"></div>';
    days.forEach(day => {
        content += `<div class="schedule-header">${day}</div>`;
    });

    allTimeSlots.forEach(time => {
        content += `<div class="schedule-time">${time}</div>`;
        days.forEach((day, dayIndex) => {
            const isSelected = selectedSchedule.some(slot =>
                slot[0] === time && slot[1] === day
            );
            const cellClass = isSelected ? 'schedule-cell selected' : 'schedule-cell';
            content += `<div class="${cellClass}"
                            data-day="${dayIndex}"
                            data-time="${time}"
                            data-day-name="${day}"
                            onclick="toggleScheduleCell(this)">
                            ${isSelected ? 'Seçildi' : ''}
                        </div>`;
        });
    });

    content += '</div>';

    document.getElementById('schedule-modal-body').innerHTML = content;
}

function toggleScheduleCell(cell) {
    if (!selectedFilialName) {
        showNotification('Zəhmət olmasa əvvəlcə filial seçin!', 'warning');
        return;
    }

    const time = cell.dataset.time;
    const dayName = cell.dataset.dayName;
    if (cell.classList.contains('selected')) {
        cell.classList.remove('selected');
        cell.textContent = '';
        selectedSchedule = selectedSchedule.filter(slot =>
            !(slot[0] === time && slot[1] === dayName)
        );
    } else {
        cell.classList.add('selected');
        cell.textContent = 'Seçildi';
        selectedSchedule.push([time, dayName, '']);
    }
}

function clearSchedule() {
    selectedSchedule = [];
    customTimeSlots = [];
    generateScheduleGrid();
    showNotification('Cədvəl təmizləndi!', 'info');
}

async function saveSchedule() {
    if (!currentTeacher) {
        showNotification('Müəllim məlumatları tapılmadı!', 'error');
        return;
    }

    if (!selectedFilialName) {
        showNotification('Zəhmət olmasa filial seçin!', 'warning');
        return;
    }

    if (selectedSchedule.length === 0) {
        showNotification('Zəhmət olmasa ən azı bir vaxt seçin!', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_schedule_by_filial');
    formData.append('teacher_id', currentTeacher.id);
    formData.append('username', currentTeacher.username);
    formData.append('filial_name', selectedFilialName);
    formData.append('schedule', JSON.stringify(selectedSchedule));

    try {
        const response = await fetch('filiallar/filiallar_operations.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.status === 'success') {
            showNotification(`${selectedFilialName} filialı üçün cədvəl saxlanıldı!`, 'success');
            closeScheduleModal();
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            showNotification(result.message || 'Xəta baş verdi!', 'error');
        }
    } catch (error) {
        console.error('Error saving schedule:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

async function showTeacherScheduleModal(teacherId, username) {
    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_all_schedules_by_teacher&teacher_id=${teacherId}&username=${encodeURIComponent(username)}`);
        if (result && result.status === 'success') {
            document.getElementById('schedule-display-modal-title').textContent = `${username} - Bütün Cədvəllər`;
            displayAllSchedulesByFilial(result.data, 'schedule-display-modal-body');
            document.getElementById('schedule-display-modal').classList.add('show');
        } else {
            showNotification('Cədvəl yüklənə bilmədi!', 'error');
        }
    } catch (error) {
        console.error('Error loading teacher schedules:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

function displayAllSchedulesByFilial(data, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Schedule display container not found:', containerId);
        return;
    }

    const { schedules_by_filial, teacher_filials } = data;
    if (!schedules_by_filial || Object.keys(schedules_by_filial).length === 0) {
        container.innerHTML = `
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <p>Bu müəllimin heç bir filialda cədvəli yoxdur</p>
                    ${teacher_filials.length > 0 ? `<p class="text-muted">Mövcud filiallar: ${teacher_filials.join(', ')}</p>` : ''}
                </div>
            `;
        return;
    }

    let html = '';

    Object.keys(schedules_by_filial).forEach(filial => {
        const scheduleData = schedules_by_filial[filial];

        html += `
                <div class="filial-schedule-section" style="margin-bottom: 30px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px;">
                    <h4 style="margin-bottom: 15px; color: #2c3e50;">
                        <i class="fas fa-building"></i> ${filial}
                        <span class="badge badge-primary ms-2">${scheduleData.length} dərs</span>
                    </h4>
                    <div class="schedule-display-grid">
            `;

        html += '<div class="schedule-display-header"></div>';
        days.forEach(day => {
            html += `<div class="schedule-display-header">${day}</div>`;
        });

        const filialTimeSlots = [...new Set([...timeSlots, ...scheduleData.map(slot => slot[0])])].sort();
        filialTimeSlots.forEach(time => {
            html += `<div class="schedule-display-time">${time}</div>`;

            days.forEach(day => {
                const scheduleEntry = scheduleData.find(slot =>
                    slot[0] === time && slot[1] === day
                );

                if (scheduleEntry) {
                    const note = scheduleEntry[2];
                    const cellClass = note ? 'schedule-display-cell occupied with-note' : 'schedule-display-cell occupied';
                    const displayText = note ? note : 'Dərs var';
                    html += `<div class="${cellClass}" title="${note || 'Dərs var'}">${displayText}</div>`;
                } else {
                    html += '<div class="schedule-display-cell"></div>';
                }
            });
        });

        html += '</div></div>';
    });

    container.innerHTML = html;
}

async function loadFilialSelectOptions() {
    if (!isTabActive('cedvel')) return;

    try {
        const result = await apiRequest('filiallar/filiallar_operations.php?action=get_filial_details');
        if (result.status === 'success') {
            const select = document.getElementById('filial_select');
            select.innerHTML = '<option value="">Seçin...</option>';
            result.data.forEach(filial => {
                const option = document.createElement('option');
                option.value = filial.filial_adi;
                option.textContent = filial.filial_adi;
                select.appendChild(option);
            });
        } else {
            console.error('Error loading filials for select:', result.message);
            showNotification('Filiallar yüklənə bilmədi!', 'error');
        }
    } catch (error) {
        console.error('Error loading filials for select:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

async function loadFennByFilial(filialAdi) {
    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_fenn_by_filial&filial_adi=${encodeURIComponent(filialAdi)}`);
        if (result.status === 'success') {
            const select = document.getElementById('fenn_select');
            select.innerHTML = '<option value="">Seçin...</option>';
            result.data.forEach(fenn => {
                const option = document.createElement('option');
                option.value = fenn.fenn_adi;
                option.textContent = fenn.fenn_adi;
                select.appendChild(option);
            });
            document.getElementById('fenn_selection_container').style.display = 'block';
            document.getElementById('teacher_selection_container').style.display = 'none';
            document.getElementById('action_buttons_container').style.display = 'none';
            document.getElementById('teacher_select').innerHTML = '<option value="">Seçin...</option>';
        } else {
            console.error('Error loading subjects:', result.message);
            showNotification('Fənlər yüklənə bilmədi!', 'error');
        }
    } catch (error) {
        console.error('Error loading subjects:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

async function loadTeachersByFilialAndFenn(filialAdi, fennAdi) {
    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_teachers_by_filial_and_fenn&filial_adi=${encodeURIComponent(filialAdi)}&fenn_adi=${encodeURIComponent(fennAdi)}`);
        if (result.status === 'success') {
            const select = document.getElementById('teacher_select');
            select.innerHTML = '<option value="">Seçin...</option>';
            result.data.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.username;
                option.dataset.teacherId = teacher.id;
                option.textContent = `${teacher.username}`;
                select.appendChild(option);
            });
            document.getElementById('teacher_selection_container').style.display = 'block';
            document.getElementById('action_buttons_container').style.display = 'none';
        } else {
            console.error('Error loading teachers:', result.message);
            showNotification('Müəllimlər yüklənə bilmədi!', 'error');
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
        showNotification('Serverə qoşulmaq mümkün olmadı!', 'error');
    }
}

async function loadSelectedTeacherScheduleModal() {
    const select = document.getElementById('teacher_select');
    const selectedUsername = select.value;
    if (!selectedUsername) {
        showNotification('Zəhmət olmasa müəllim seçin!', 'warning');
        return;
    }
    const selectedOption = select.options[select.selectedIndex];
    const teacherId = selectedOption.dataset.teacherId;
    if (!teacherId) {
        showNotification('Müəllim ID tapılmadı!', 'error');
        return;
    }

    await openScheduleModal(selectedUsername, parseInt(teacherId));
}

async function viewSelectedTeacherSchedule() {
    const select = document.getElementById('teacher_select');
    const selectedUsername = select.value;
    if (!selectedUsername) {
        showNotification('Zəhmət olmasa müəllim seçin!', 'warning');
        return;
    }
    const selectedOption = select.options[select.selectedIndex];
    const teacherId = selectedOption.dataset.teacherId;
    if (!teacherId) {
        showNotification('Müəllim ID tapılmadı!', 'error');
        return;
    }

    await showTeacherScheduleModal(parseInt(teacherId), selectedUsername);
}

function closeScheduleModal() {
    document.getElementById('schedule-modal').classList.remove('show');
    selectedSchedule = [];
    selectedFilialName = '';
    currentTeacher = null;
}

function closeScheduleDisplayModal() {
    document.getElementById('schedule-display-modal').classList.remove('show');
}

function closeCedvelScheduleModal() {
    document.getElementById('cedvel-schedule-modal').classList.remove('show');
    cedvelSelectedSchedule = [];
    cedvelCurrentTeacher = null;
}

function closeFilialModal() {
    document.getElementById('filial-modal').classList.remove('show');
}

async function loadFilialsForStudentModal() {
    try {
        const result = await apiRequest('filiallar/filiallar_operations.php?action=get_filial_details');
        if (result.status === 'success') {
            const select = document.getElementById('filialSelect');
            select.innerHTML = '<option value="">Filial seçin...</option>';
            result.data.forEach(filial => {
                const option = document.createElement('option');
                option.value = filial.filial_adi;
                option.textContent = filial.filial_adi;
                select.appendChild(option);
            });

            select.addEventListener('change', function () {
                const selectedFilial = this.value;
                if (selectedFilial) {
                    loadSubjectsForStudentModal(selectedFilial);
                } else {
                    document.getElementById('subjectSelect').innerHTML = '<option value="">Fənn seçin...</option>';
                    document.getElementById('subjectSelect').disabled = true;
                    document.getElementById('teacherSelect1').innerHTML = '<option value="">Müəllim seçin...</option>';
                    document.getElementById('teacherSelect1').disabled = true;
                }
            });
        }
    } catch (error) {
        console.error('Error loading filials for student modal:', error);
    }
}

async function loadSubjectsForStudentModal(filialName) {
    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_fenn_by_filial&filial_adi=${encodeURIComponent(filialName)}`);
        if (result.status === 'success') {
            const select = document.getElementById('subjectSelect');
            select.innerHTML = '<option value="">Fənn seçin...</option>';
            result.data.forEach(fenn => {
                const option = document.createElement('option');
                option.value = fenn.fenn_adi;
                option.textContent = fenn.fenn_adi;
                select.appendChild(option);
            });
            select.disabled = false;
            select.addEventListener('change', function () {
                const selectedSubject = this.value;
                const filialSelect = document.getElementById('filialSelect');
                const selectedFilial = filialSelect.value;

                if (selectedSubject && selectedFilial) {
                    loadTeachersForStudentModal(selectedFilial, selectedSubject);
                } else {
                    document.getElementById('teacherSelect1').innerHTML = '<option value="">Müəllim seçin...</option>';
                    document.getElementById('teacherSelect1').disabled = true;
                }
            });
        }
    } catch (error) {
        console.error('Error loading subjects for student modal:', error);
    }
}

async function loadTeachersForStudentModal(filialName, subjectName) {
    try {
        const result = await apiRequest(`filiallar/filiallar_operations.php?action=get_teachers_by_filial_and_fenn&filial_adi=${encodeURIComponent(filialName)}&fenn_adi=${encodeURIComponent(subjectName)}`);
        if (result.status === 'success') {
            const select = document.getElementById('teacherSelect1');
            select.innerHTML = '<option value="">Müəllim seçin...</option>';
            result.data.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.username;
                option.dataset.teacherId = teacher.id;
                option.textContent = teacher.username;
                select.appendChild(option);
            });
            select.disabled = false;
        }
    } catch (error) {
        console.error('Error loading teachers for student modal:', error);
    }
}

function schedule_saveForm() {
    const username = document.getElementById('modalUsernameInput').value;
    const filial = document.getElementById('filialSelect').value;
    const subject = document.getElementById('subjectSelect').value;
    const teacher = document.getElementById('teacherSelect1').value;
    if (!filial || !subject || !teacher) {
        showNotification('Zəhmət olmasa bütün sahələri doldurun!', 'warning');
        return;
    }

    showNotification(`${username} tələbəsi ${teacher} müəlliminə təyin edildi`, 'success');
    const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
    if (modal) {
        modal.hide();
    }
}

function updateFilialSelects() { }

window.addEventListener('unhandledrejection', function (event) {
    console.error('Unhandled promise rejection:', event.reason);
    showNotification('Gözlənilməz xəta baş verdi!', 'error');
});

window.showTab = showTab;
window.loadLastActiveTab = loadLastActiveTab;
window.refreshCurrentTab = refreshCurrentTab;
window.getCurrentTab = getCurrentTab;
window.isTabActive = isTabActive;
window.showNotification = showNotification;
document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
    } else {
    }
});

window.addEventListener('beforeunload', function (e) {
});

function initializeSystemStatus() {
    const statusIndicator = document.createElement('div');
    statusIndicator.id = 'system-status';
    statusIndicator.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            padding: 8px 12px;
            background: #2ccd52ff;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            z-index: 9999;
        `;
    statusIndicator.textContent = 'Sistem hazır';
    document.body.appendChild(statusIndicator);
    setTimeout(() => {
        statusIndicator.style.display = 'none';
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(initializeSystemStatus, 1000);
});













let currentScheduleData = null;

function schedule_openModal(button) {
    document.getElementById('modalUsernameInput').value = button.getAttribute('data-username');
    schedule_resetForm();
    schedule_loadFilials();
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function schedule_resetForm() {
    document.getElementById('filialSelect').innerHTML = '<option value="">Filial seçin...</option>';
    document.getElementById('subjectSelect').innerHTML = '<option value="">Fənn seçin...</option>';
    document.getElementById('subjectSelect').disabled = true;
    teachers = [];
    schedule_resetTeacherSelects(1);
}

function schedule_resetTeacherSelects(count) {
    const container = document.getElementById('teacherContainer');
    container.innerHTML = '<label class="form-label">Müəllim</label>';
    for (let i = 1; i <= count; i++) {
        const div = document.createElement('div');
        div.className = 'input-group mb-2';
        const select = document.createElement('select');
        select.className = 'form-control';
        select.id = `teacherSelect${i}`;
        select.disabled = true;
        select.innerHTML = '<option value="">Müəllim seçin...</option>';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-info btn-sm';
        button.textContent = 'Aç Cedvel';
        button.onclick = () => schedule_openScheduleModal(i);
        div.appendChild(select);
        div.appendChild(button);
        container.appendChild(div);
    }
}

function schedule_populateTeacherSelects() {
    const teacherCount = document.getElementById('subjectSelect').value === 'Magistratura' ? 3 : 1;
    for (let i = 1; i <= teacherCount; i++) {
        const select = document.getElementById(`teacherSelect${i}`);
        if (select) {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Müəllim seçin...</option>';
            if (teachers && teachers.length > 0) {
                teachers.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.username;
                    select.appendChild(option);
                });
                select.value = currentValue;
                select.disabled = false;
            } else {
                select.innerHTML = '<option value="">No teachers available</option>';
                select.disabled = true;
            }
        }
    }
}

async function schedule_loadFilials() {
    try {
        const response = await fetch('filiallar/get_data.php?method=get_filials');
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const filials = await response.json();
        const select = document.getElementById('filialSelect');
        select.innerHTML = '<option value="">Filial seçin...</option>';
        if (filials && filials.length > 0) {
            filials.forEach(filial => {
                const option = document.createElement('option');
                option.value = filial;
                option.textContent = filial;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading filials:', error);
        alert('Error loading filials: ' + error.message);
    }
}

async function schedule_loadFenns(filial) {
    try {
        const response = await fetch(`filiallar/get_data.php?method=get_fenns&filial=${encodeURIComponent(filial)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const fenns = await response.json();
        const select = document.getElementById('subjectSelect');
        select.innerHTML = '<option value="">Fənn seçin...</option>';
        if (fenns && fenns.length > 0) {
            fenns.forEach(fenn => {
                const option = document.createElement('option');
                option.value = fenn;
                option.textContent = fenn;
                select.appendChild(option);
            });
            select.disabled = false;
        } else {
            select.innerHTML = '<option value="">No subjects available</option>';
            select.disabled = true;
        }
        teachers = [];
        schedule_resetTeacherSelects(1);
    } catch (error) {
        console.error('Error loading fenns:', error);
        alert('Error loading subjects: ' + error.message);
    }
}

async function schedule_loadTeachers(filial, fenn) {
    try {
        const response = await fetch(`filiallar/get_data.php?method=get_teachers&filial=${encodeURIComponent(filial)}&fenn=${encodeURIComponent(fenn)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const teachersData = await response.json();
        teachers = teachersData || [];
        const teacherCount = fenn === 'Magistratura' ? 3 : 1;
        schedule_resetTeacherSelects(teacherCount);
        if (teachers.length > 0) {
            schedule_populateTeacherSelects();
        } else {
            alert('Bu filial və fənn üçün müəllim tapılmadı');
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
        alert('Error loading teachers: ' + error.message);
        teachers = [];
        schedule_resetTeacherSelects(1);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const filialSelect = document.getElementById('filialSelect');
    if (filialSelect) {
        filialSelect.addEventListener('change', function () {
            const selectedFilial = this.value;
            if (selectedFilial) {
                schedule_loadFenns(selectedFilial);
            } else {
                document.getElementById('subjectSelect').innerHTML = '<option value="">Fənn seçin...</option>';
                document.getElementById('subjectSelect').disabled = true;
                teachers = [];
                schedule_resetTeacherSelects(1);
            }
        });
    }
    const subjectSelect = document.getElementById('subjectSelect');
    if (subjectSelect) {
        subjectSelect.addEventListener('change', function () {
            const selectedSubject = this.value;
            const selectedFilial = document.getElementById('filialSelect').value;
            if (selectedFilial && selectedSubject) {
                schedule_loadTeachers(selectedFilial, selectedSubject);
            } else {
                teachers = [];
                schedule_resetTeacherSelects(1);
            }
        });
    }
});

async function schedule_saveForm() {
    const formData = {
        username: document.getElementById('modalUsernameInput').value,
        filial: document.getElementById('filialSelect').value,
        fenn: document.getElementById('subjectSelect').value,
        teachers: []
    };
    if (!formData.username) {
        alert('Please enter username');
        return;
    }
    const teacherCount = formData.fenn === 'Magistratura' ? 3 : 1;
    for (let i = 1; i <= teacherCount; i++) {
        const select = document.getElementById(`teacherSelect${i}`);
        if (select && select.value) {
            const teacher = teachers.find(t => t.id == select.value);
            if (teacher) {
                formData.teachers.push(teacher.username);
            } else {
                formData.teachers.push('');
            }
        } else {
            formData.teachers.push('');
        }
    }
    try {
        const response = await fetch('filiallar/get_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                method: 'update_student_teachers',
                username: formData.username,
                teachers: formData.teachers
            })
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const responseText = await response.text();
        if (!responseText) {
            throw new Error('Empty response from server');
        }
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('Invalid JSON response:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        if (result.success) {
            alert('Tələbə müəllimlər uğurla yeniləndi!');
            try {
                const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                if (modal) {
                    modal.hide();
                } else {
                    const modalElement = document.getElementById('userModal');
                    modalElement.style.display = 'none';
                    modalElement.classList.remove('show');
                    modalElement.setAttribute('aria-hidden', 'true');
                    modalElement.removeAttribute('aria-modal');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            } catch (e) {
                console.error('Error closing modal:', e);
            }
        } else {
            throw new Error(result.message || 'Failed to update student teachers');
        }
    } catch (error) {
        console.error('Error updating student teachers:', error);
        alert('Yeniləmə zamanı xəta baş verdi: ' + error.message);
    }
}

function schedule_openScheduleModal(teacherIndex) {
    const teacherSelect = document.getElementById(`teacherSelect${teacherIndex}`);
    if (!teacherSelect || !teacherSelect.value) {
        alert('Zəhmət olmasa əvvəlcə müəllim seçin');
        return;
    }
    const teacherId = teacherSelect.value;
    const teacherName = teacherSelect.options[teacherSelect.selectedIndex].textContent;
    document.getElementById('scheduleTeacherName').textContent = teacherName;
    document.getElementById('scheduleTeacherId').value = teacherId;
    schedule_loadSchedule(teacherId);
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}

async function schedule_loadSchedule(teacherId) {
    try {
        const selectedFilial = document.getElementById('filialSelect').value;
        if (!selectedFilial) {
            alert('Zəhmət olmasa filial seçin!');
            return;
        }
        const response = await fetch(`filiallar/get_data.php?method=get_schedule&teacher_id=${teacherId}&filial=${encodeURIComponent(selectedFilial)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const scheduleData = await response.json();
        currentScheduleData = scheduleData;
        const cedvel = scheduleData.cedvel || [];
        const teacherInfo = scheduleData.teacher_info || {};
        const telebeler = scheduleData.telebeler || [];
        const grid = document.getElementById('scheduleGrid');
        grid.innerHTML = '';
        if (cedvel.length === 0) {
            grid.innerHTML = '<div class="alert alert-warning">Bu müəllim üçün cedvel məlumatı tapılmadı</div>';
            return;
        }
        const times = [...new Set(cedvel.map(entry => entry[1]).filter(t => t))].sort();
        const days = [...new Set(cedvel.map(entry => entry[2]).filter(d => d))].sort();
        const currentStudents = telebeler.filter(entry => entry.length >= 4 && entry[3] && entry[3].trim()).length;
        const infoRow = document.createElement('div');
        infoRow.className = 'row mb-3';
        const infoCol = document.createElement('div');
        infoCol.className = 'col-12';
        infoCol.innerHTML = `
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <div>
                    <strong>Cedvel | Müəllim: ${teacherInfo.username || 'N/A'}</strong> <br>
                    <strong>Filial: ${selectedFilial}</strong>
                </div>
                <div>
                    <span style="font-weight:bold;border-radius:8px;" class="badge p-2 bg-success me-2">Mövcud tələbə: ${currentStudents}</span>
                </div>
            </div>
        `;
        infoRow.appendChild(infoCol);
        grid.appendChild(infoRow);
        const headerRow = document.createElement('div');
        headerRow.className = 'row border-bottom mb-2';
        const timeHeader = document.createElement('div');
        timeHeader.className = 'col-1 p-2 bg-light border text-center fw-bold';
        timeHeader.style.fontSize = '14px';
        timeHeader.innerHTML = 'Saat';
        headerRow.appendChild(timeHeader);
        days.forEach(day => {
            const dayCell = document.createElement('div');
            dayCell.className = 'col p-2 bg-light border text-center fw-bold';
            dayCell.style.fontSize = '14px';
            dayCell.textContent = day;
            headerRow.appendChild(dayCell);
        });
        grid.appendChild(headerRow);
        times.forEach(time => {
            const row = document.createElement('div');
            row.className = 'row mb-1';
            const timeCell = document.createElement('div');
            timeCell.className = 'col-1 p-2 bg-light border text-center fw-bold';
            timeCell.style.fontSize = '12px';
            timeCell.textContent = time;
            row.appendChild(timeCell);
            days.forEach(day => {
                const cell = document.createElement('div');
                cell.className = 'col p-2 border text-center schedule-cell';
                cell.style.minHeight = '20px';
                cell.dataset.time = time;
                cell.dataset.day = day;
                const slot = cedvel.find(entry => entry[1] === time && entry[2] === day && entry[0] === selectedFilial);
                if (slot) {
                    const student = telebeler.find(entry => entry[1] === time && entry[2] === day && entry[3] && entry[3].trim());
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm ' + (student ? 'btn-danger' : 'btn-success');
                    btn.style.width = '100%';
                    btn.style.fontSize = '14px';
                    btn.style.borderRadius = '6px';
                    if (!student) {
                        btn.style.padding = '4px'; // Different padding for "Boş yer"
                        btn.style.transform = 'scale(1.1)';
                        btn.style.transition = 'transform 0.2s';
                        btn.style.margin = '2px';
                        btn.style.background = 'linear-gradient(135deg, #28a745, #218838)';
                    } else {
                        btn.style.padding = '6px'; // Default padding for named buttons
                        btn.style.transform = 'scale(1.0)';
                        btn.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.2)';
                        btn.style.transition = 'box-shadow 0.2s';
                    }
                    btn.textContent = student ? student[3] : 'Boş yer';
                    btn.onclick = student ? () => schedule_deleteStudent(teacherId, time, day, student[3]) : () => schedule_addStudent(cell, teacherId, time, day);
                    cell.appendChild(btn);
                } else {
                    cell.innerHTML = '<span class="text-muted">Mövcud deyil</span>';
                }
                row.appendChild(cell);
            });
            grid.appendChild(row);
        });
    } catch (error) {
        console.error('Schedule loading error:', error);
        alert('Error loading schedule: ' + error.message);
    }
}

function schedule_addStudent(cell, teacherId, time, day) {
    const studentName = document.getElementById('modalUsernameInput').value;
    const selectedFilial = document.getElementById('filialSelect').value;
    if (!studentName || !studentName.trim()) {
        alert('Zəhmət olmasa əvvəlcə tələbə adını daxil edin!');
        return;
    }
    if (!selectedFilial) {
        alert('Zəhmət olmasa filial seçin!');
        return;
    }
    const btn = cell.querySelector('button');
    btn.textContent = 'Əlavə olunur...';
    btn.className = 'btn btn-sm btn-info';
    btn.style.width = '100%';
    btn.onclick = null;
    schedule_saveSchedule(teacherId, time, day, studentName.trim(), selectedFilial);
}

function schedule_deleteStudent(teacherId, time, day, studentName) {
    const selectedFilial = document.getElementById('filialSelect').value;
    schedule_saveSchedule(teacherId, time, day, '', selectedFilial);
}

async function schedule_saveSchedule(teacherId, time, day, studentName, filial) {
    try {
        const requestData = {
            method: 'save_schedule',
            teacher_id: parseInt(teacherId),
            time: time,
            day: day,
            student_name: studentName || '',
            filial: filial || ''
        };
        const response = await fetch('filiallar/get_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const responseText = await response.text();
        if (!responseText) {
            throw new Error('Empty response from server');
        }
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('Invalid JSON response:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        if (result.success) {
            await schedule_loadSchedule(teacherId);
        } else {
            throw new Error(result.message || 'Failed to save schedule');
        }
    } catch (error) {
        console.error('Error saving schedule:', error);
        alert('Schedule saxlanılarkən xəta baş verdi: ' + error.message);
        await schedule_loadSchedule(teacherId);
    }
}