<!-- Materials Section -->
<div class="section" id="materials">
    <div class="d-flex justify-content-between align-items-center mb-20">
        <h1></h1>
        <button class="btn btn-primary" id="addMaterialBtn">
            <i class="fas fa-plus"></i> Yeni Material
        </button>
    </div>
    
    <div class="card mb-20">
        <div class="card-header">
            <h3 class="card-title">Materiallar Siyahısı</h3>
        </div>

        <div class="m-2 d-flexbox gap-10">
            <input type="text" class="mb-2 form-control" id="materialTopicFilter" placeholder="Mövzu axtar...">
            <select class="mb-2 form-select" id="materialTypeFilter">
                <option value="">Bütün Tiplər</option>
                <option value="document">Sənəd</option>
                <option value="presentation">Təqdimat</option>
                <option value="video">Video</option>
                <option value="image">Şəkil</option>
            </select>
            <input hidden type="text" class="form-control" id="materialSearchInput" placeholder="Axtar...">
        </div>

        <div class="card-body">
            <div class="table-container">
                <table class="table" id="materialsTable">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Mövzu</th>
                            <th>Tip</th>
                            <th>Ölçü</th>
                            <th>Yaradılma Tarixi</th>
                            <th>Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated dynamically by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Material Modal -->
<div id="addMaterialModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Material Əlavə Et</h5>
            </div>
            <div class="modal-body">
                <form id="addMaterialForm">
                    <div class="form-group mb-3">
                        <label for="materialName">Material Adı</label>
                        <input type="text" class="form-control" placeholder="Material Adı" id="materialName" name="materialName" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="materialType">Tipi</label>
                        <select class="form-control" id="materialType" name="materialType" required>
                            <option value="">Seçin</option>
                            <option value="document">Sənəd</option>
                            <option value="presentation">Təqdimat</option>
                            <option value="video">Video</option>
                            <option value="image">Şəkil</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="materialTopic">Mövzu</label>
                        <input type="text" class="form-control" id="materialTopic" name="materialTopic" placeholder="Mövzu daxil edin">
                    </div>
                    <div class="form-group mb-3">
                        <div id="materialFileUpload" class="file-upload-container">
                            <input type="file" id="materialFile" name="materialFile" class="d-none" required>
                            <div class="file-upload-text">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Faylı seçmək üçün klikləyin və ya buraya sürükləyin</span>
                            </div>
                            <div id="materialFileInfo" class="file-info"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveMaterialBtn">Yadda Saxla</button>
            </div>
        </div>
    </div>
</div>

<!-- Material Details Modal -->
<div id="materialModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div style="width:358px;"  class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Material Details</h5>
            </div>
            <div class="modal-body" id="materialModalBody">
                <!-- Material details will be dynamically inserted here -->
            </div>
        
        </div>
    </div>
</div>

<!-- CSS for modal animations -->
<style>
/* Modal animation styles */
.modal {
    transition: opacity 0.3s ease;
}

.modal-dialog {
    transition: transform 0.3s ease;
    transform: translate(0, -25%);
}

.modal.show .modal-dialog {
    transform: translate(0, 0);
}

.modal-backdrop {
    transition: opacity 0.3s ease;
}

.modal-backdrop.show {
    opacity: 0.5;
}

.fade {
    transition: opacity 0.3s ease;
}

.fade:not(.show) {
    opacity: 0;
}

/* File upload styles */
.file-upload-container {
    border: 2px dashed #ccc;
    padding: 20px;
    text-align: center;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.file-upload-container.dragover {
    background-color: #f8f9fa;
    border-color: #6c757d;
}

.file-upload-text {
    margin-bottom: 10px;
}

.file-upload-text i {
    font-size: 24px;
    margin-bottom: 10px;
    display: block;
}

.file-info {
    margin-top: 10px;
    font-size: 14px;
    color: #6c757d;
}

/* Action buttons styles */
.action-buttons {
    display: flex;
    gap: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const addMaterialBtn = document.getElementById('addMaterialBtn');
    const closeAddMaterialBtn = document.getElementById('closeAddMaterialBtn');
    const saveMaterialBtn = document.getElementById('saveMaterialBtn');
    const closeMaterialModalBtn = document.getElementById('closeMaterialModalBtn');
    const closeButtons = document.querySelectorAll('.close');
    const fileInput = document.getElementById('materialFile');
    const fileTextDiv = document.querySelector('.file-upload-text');
    const fileInfo = document.getElementById('materialFileInfo');
    const fileUploadDiv = document.getElementById('materialFileUpload');
    const materialTypeFilter = document.getElementById('materialTypeFilter');
    const materialTopicFilter = document.getElementById('materialTopicFilter');
    const materialSearchInput = document.getElementById('materialSearchInput');
    const maxFileSize = 20 * 1024 * 1024; // 20MB

    document.querySelectorAll('.download-material-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id'); // Get the material ID
            downloadMaterial(id); // Call the download function
        });
    });
    

    // Modal functions with smooth fade animations
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // Create backdrop with fade effect
        const backdrop = document.createElement('div');
        backdrop.classList.add('modal-backdrop', 'fade');
        document.body.appendChild(backdrop);
        
        // Set initial styles for animation
        modal.style.display = 'block';
        modal.style.opacity = '0';
        modal.style.zIndex = '1050';
        backdrop.style.opacity = '0';
        backdrop.style.zIndex = '1040';
        
        // Add modal-open class to body to prevent scrolling
        document.body.classList.add('modal-open');
        
        // Force reflow to ensure transition works
        void modal.offsetWidth;
        void backdrop.offsetWidth;
        
        // Start fade in animation
        setTimeout(() => {
            modal.classList.add('show');
            modal.style.opacity = '1';
            backdrop.classList.add('show');
            backdrop.style.opacity = '0.5';
        }, 10);
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const backdrop = document.querySelector('.modal-backdrop');
        
        // Start fade out animation
        modal.classList.remove('show');
        modal.style.opacity = '0';
        
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.style.opacity = '0';
        }
        
        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            if (backdrop) {
                backdrop.remove();
            }
        }, 300); // Match the CSS transition duration
    }

    // Function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 KB';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Function to format date as DD.MM.YYYY HH:MM
    function formatDate(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}.${month}.${year} ${hours}:${minutes}`;
    }

    // Function to fetch materials and populate the table
    function fetchMaterials() {
        fetch('movzular/file/select_materials.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(materials => {
                const tbody = document.querySelector('#materialsTable tbody');
                if (!tbody) return;
                
                tbody.innerHTML = '';

                materials.forEach(material => {
                    const tipiMap = {
                        'document': 'Sənəd',
                        'presentation': 'Təqdimat',
                        'video': 'Video',
                        'image': 'Şəkil'
                    };
                    const tipiDisplay = tipiMap[material.tipi] || material.tipi;

                    let iconClass;
                    switch (material.tipi) {
                        case 'document':
                            iconClass = 'fas fa-file-alt';
                            break;
                        case 'presentation':
                            iconClass = 'fas fa-file-powerpoint';
                            break;
                        case 'video':
                            iconClass = 'fas fa-file-video';
                            break;
                        case 'image':
                            iconClass = 'fas fa-file-image';
                            break;
                        default:
                            iconClass = 'fas fa-file';
                    }

                    const sizeDisplay = formatFileSize(parseInt(material.size));
                    const date = new Date(material.created_at);
                    const dateDisplay = formatDate(date);

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="${iconClass} m-2 text-secondary mr-10"></i>
                                ${material.material_adi}
                            </div>
                        </td>
                        <td>${material.movzu || ''}</td>
                        <td>${tipiDisplay}</td>
                        <td>${sizeDisplay}</td>
                        <td>${dateDisplay}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-info view-material-btn" data-id="${material.id}" data-name="${material.material_adi}" data-type="${tipiDisplay}" data-size="${sizeDisplay}" data-date="${dateDisplay}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success download-material-btn" data-id="${material.id}">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-material-btn" data-id="${material.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Add event listeners to the dynamically created buttons
                addActionButtonListeners();
            })
            .catch(error => {
                console.error('Error fetching materials:', error);
            });
    }

    // Add event listeners to action buttons
    function addActionButtonListeners() {
        // View material buttons
        document.querySelectorAll('.view-material-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const type = this.getAttribute('data-type');
                const size = this.getAttribute('data-size');
                const date = this.getAttribute('data-date');
                viewMaterial(id, name, type, size, date);
            });
        });

        // Download material buttons
        document.querySelectorAll('.download-material-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                downloadMaterial(id);
            });
        });

        // Delete material buttons
        document.querySelectorAll('.delete-material-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                deleteMaterial(id);
            });
        });
    }

    // Function to view material details in a modal
    function viewMaterial(id, name, type, size, date) {
        const modalBody = document.getElementById('materialModalBody');
        if (!modalBody) return;
        
        modalBody.innerHTML = `
            <p><strong>ID:</strong> ${id}</p>
            <p><strong>Name:</strong> ${name}</p>
            <p><strong>Type:</strong> ${type}</p>
            <p><strong>Size:</strong> ${size}</p>
            <p><strong>Date:</strong> ${date}</p>
        `;
        openModal('materialModal');
    }

    // Function to download material
    function downloadMaterial(id) {
    window.location.href = `movzular/file/download_material.php?id=${id}`;
    }

    // Function to close the delete confirmation form
    function closeForm(id) {
        const formId = id ? `deleteConfirmForm-${id}` : null;
        
        if (formId) {
            const form = document.getElementById(formId);
            if (form) {
                // Add fade out animation
                form.style.opacity = '0';
                setTimeout(() => {
                    form.remove();
                }, 300);
            }
        } else {
            // Remove all delete confirmation forms if no specific ID is provided
            document.querySelectorAll('[id^="deleteConfirmForm-"]').forEach(form => {
                form.style.opacity = '0';
                setTimeout(() => {
                    form.remove();
                }, 300);
            });
        }
    }

    // Delete material by ID
    function deleteMaterial(id) {
        // Remove any existing form to prevent duplicates
        closeForm();

        // Create the confirmation form dynamically
        const formContainer = document.createElement('div');
        formContainer.id = `deleteConfirmForm-${id}`; // Unique ID to avoid conflicts

        formContainer.innerHTML = `
    <style>
        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to { opacity: 0.9; }
        }

        @keyframes fadeInModal {
            from { opacity: 0; transform: translate(-50%, -60%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }

        .overlay-fade {
            animation: fadeInOverlay 0.3s ease forwards;
        }

        .modal-fade {
            animation: fadeInModal 0.3s ease forwards;
        }
    </style>

    <!-- Background overlay with keyframe opacity animation -->
    <div id="overlay-${id}" class="overlay-fade" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.81);
        opacity: 0; /* Initial opacity for animation */
        z-index: 1050;
    "></div>

    <!-- Modal box with keyframe opacity + slight slide animation -->
    <div id="deleteMaterialModal-${id}" class="modal-fade" style="
        position: fixed;
        top: 14%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #ffffff;
        opacity: 0;
        width: 360px;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        z-index: 1060;
        padding: 20px;
        font-family: Arial, sans-serif;
        text-align: center;
    ">
        <form id="deleteMaterialForm-${id}">
            <p style="text-align:left; border-bottom:1px solid lightgray; line-height:40px; margin: 0 0 10px; font-size: 18px; font-weight: 600; color: #000;">Materialı Silmək</p>
            <p style="margin: 0 0 20px; font-size: 14px; color: #333;">Materialı silmək istədiyinizdən əminsiniz?</p>
            <input type="hidden" id="materialId-${id}" name="materialId" value="${id}">
            <div style="display: flex; justify-content: right; gap: 12px;">
                <button type="button" id="closeFormBtn-${id}" style="
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    background-color: #6b7280;
                    color: #fff;
                    font-size: 14px;
                    cursor: pointer;
                ">Ləğv et</button>
                <button type="submit" style="
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    background-color: #dc2626;
                    color: #fff;
                    font-size: 14px;
                    cursor: pointer;
                ">Sil</button>
            </div>
        </form>
    </div>
`;


        // Append the form to the body with fade in animation
        document.body.appendChild(formContainer);
        const form = document.getElementById(`deleteMaterialForm-${id}`);
        
        // Set initial opacity and trigger animation
        form.style.opacity = '0';
        
        // Force reflow
        void form.offsetWidth;
        
        // Fade in
        setTimeout(() => {
            form.style.opacity = '1';
        }, 10);

        // Add event listener to the close button
        document.getElementById(`closeFormBtn-${id}`).addEventListener('click', function() {
            closeForm(id);
        });

        // Add submit event listener to the form
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            const materialId = document.getElementById(`materialId-${id}`).value;

            fetch('movzular/file/delete_material.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${encodeURIComponent(materialId)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Delete request failed');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Material silindi.');
                    closeForm(id); // Close the form
                    location.reload(); // Refresh the page
                } else {
                    alert('Xəta: ' + data.message);
                    closeForm(id); // Close the form on error
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('Xəta baş verdi.');
                closeForm(id); // Close the form on error
            });
        });
    }

    // Filter materials
    function filterMaterials() {
        if (!materialTypeFilter || !materialTopicFilter || !materialSearchInput) return;
        
        const typeFilter = materialTypeFilter.value.toLowerCase();
        const topicFilter = materialTopicFilter.value.toLowerCase();
        const searchFilter = materialSearchInput.value.toLowerCase();
        
        const rows = document.querySelectorAll('#materialsTable tbody tr');
        
        rows.forEach(row => {
            const type = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const topic = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            
            const matchesType = !typeFilter || type.includes(typeFilter);
            const matchesTopic = !topicFilter || topic.includes(topicFilter);
            const matchesSearch = !searchFilter || name.includes(searchFilter);
            
            if (matchesType && matchesTopic && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Updated saveMaterial function
    function saveMaterial() {
        const form = document.getElementById('addMaterialForm');
        if (!form) return;
        
        // Minimal validation to ensure form submission
        const materialName = document.getElementById('materialName').value;
        if (!materialName.trim()) {
            alert('Xəta: Material adı daxil edilməyib.');
            return;
        }

        const formData = new FormData(form);

        fetch('movzular/file/yeni_material.php', {
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
            if (data.success) {
                alert('Material əlavə olundu!');
                closeModal('addMaterialModal');
                location.reload();
            } else {
                alert('Xəta: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Xəta baş verdi, zəhmət olmasa yenidən cəhd edin.');
        });
    }

    // Event Listeners for Modals
    if (addMaterialBtn) {
        addMaterialBtn.addEventListener('click', function() {
            openModal('addMaterialModal');
        });
    }

    if (closeAddMaterialBtn) {
        closeAddMaterialBtn.addEventListener('click', function() {
            closeModal('addMaterialModal');
        });
    }

    if (closeMaterialModalBtn) {
        closeMaterialModalBtn.addEventListener('click', function() {
            closeModal('materialModal');
        });
    }

    if (saveMaterialBtn) {
        saveMaterialBtn.addEventListener('click', function() {
            saveMaterial();
        });
    }

    // Add event listeners to all close buttons (X icons)
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the closest modal parent
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close modal when clicking outside of it (on the backdrop)
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal') && event.target.classList.contains('show')) {
            closeModal(event.target.id);
        }
    });

    // Close modal with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const visibleModal = document.querySelector('.modal.show');
            if (visibleModal) {
                closeModal(visibleModal.id);
            }
        }
    });

    // Click to trigger file input
    if (fileTextDiv) {
        fileTextDiv.addEventListener('click', function() {
            fileInput.click();
        });
    }

    // Update info when file is selected
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > maxFileSize) {
                    fileInfo.textContent = 'Xəta: Fayl ölçüsü 20MB-dan böyük ola bilməz.';
                    this.value = '';
                } else {
                    fileInfo.textContent = `Seçilən fayl: ${file.name} (${formatFileSize(file.size)})`;
                }
            } else {
                fileInfo.textContent = '';
            }
        });
    }

    // Drag-and-drop support
    if (fileUploadDiv) {
        fileUploadDiv.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileUploadDiv.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        fileUploadDiv.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file) {
                if (file.size > maxFileSize) {
                    fileInfo.textContent = 'Xəta: Fayl ölçüsü 20MB-dan böyük ola bilməz.';
                } else {
                    fileInput.files = e.dataTransfer.files;
                    fileInfo.textContent = `Seçilən fayl: ${file.name} (${formatFileSize(file.size)})`;
                }
            }
        });
    }

    // Add event listeners for filtering
    if (materialTypeFilter) {
        materialTypeFilter.addEventListener('change', filterMaterials);
    }
    
    if (materialTopicFilter) {
        materialTopicFilter.addEventListener('input', filterMaterials);
    }
    
    if (materialSearchInput) {
        materialSearchInput.addEventListener('input', filterMaterials);
    }

    // Make global functions available
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.filterMaterials = filterMaterials;
    window.viewMaterial = viewMaterial;
    window.downloadMaterial = downloadMaterial;
    window.deleteMaterial = deleteMaterial;

    // Initial fetch to populate the table
    fetchMaterials();
});
</script>