<div class="section" id="groups">
    <div class="d-flex justify-content-between align-items-center mb-20">
        <h1></h1>
        <button class="btn btn-primary" onclick="openModal('addGroupModal')">
            <i class="fas fa-plus"></i> Yeni Qrup
        </button>
    </div>
    
    <div class="card mb-20">
        <div class="card-header">
            <h3 class="card-title">Qruplar Siyahısı</h3>
            <input style="width:100px;" type="text" class="form-control" id="groupSearchInput" placeholder="Axtar..." oninput="filterGroups()">
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="groupsTable">
                    <thead>
                        <tr>
                            <th>Qrup</th>
                            <th>Tələbə Sayı</th>
                            <th>Yaradılma Tarixi</th>
                            <th>Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table content will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Group Modal -->
<div class="modal" id="addGroupModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Yeni Qrup Əlavə Et</h3>
            </div>
            <div class="modal-body">
                <form id="addGroupForm">
                    <div class="form-group">
                        <label class="form-label" for="groupName">Qrup Adı</label>
                        <input type="text" class="mb-3 form-control" id="groupName" name="groupName" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="studentCount">Tələbə Sayı</label>
                        <input type="number" class="form-control" id="studentCount" name="telebe_sayi" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="datetime">Tarix və Vaxt</label>
                        <input type="datetime-local" class="form-control" id="datetime" name="tarix" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Günler</label>
                        <div class="week-days">
                            <input type="checkbox" id="monday" value="Bazar ertəsi">
                            <label for="monday">Bazar ertəsi</label>

                            <input type="checkbox" id="tuesday" value="Çərşənbə axşamı">
                            <label for="tuesday">Çərşənbə axşamı</label>

                            <input type="checkbox" id="wednesday" value="Çərşənbə">
                            <label for="wednesday">Çərşənbə</label>

                            <input type="checkbox" id="thursday" value="Cümə axşamı">
                            <label for="thursday">Cümə axşamı</label>

                            <input type="checkbox" id="friday" value="Cümə">
                            <label for="friday">Cümə</label>

                            <input type="checkbox" id="saturday" value="Şənbə">
                            <label for="saturday">Şənbə</label>

                            <input type="checkbox" id="sunday" value="Bazar">
                            <label for="sunday">Bazar</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="saveGroup()">Qrupu Yadda Saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Group Modal -->
<div class="modal" id="editGroupModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Qrupu Redaktə Et</h3>
            </div>
            <div class="modal-body">
                <form id="editGroupForm">
                    <input type="hidden" id="editGroupId" name="id">
                    <div class="form-group">
                        <label class="form-label" for="editGroupName">Qrup Adı</label>
                        <input type="text" class="mb-3 form-control" id="editGroupName" name="groupName" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editStudentCount">Tələbə Sayı</label>
                        <input type="number" class="form-control" id="editStudentCount" name="telebe_sayi" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editDatetime">Tarix və Vaxt</label>
                        <input type="datetime-local" class="form-control" id="editDatetime" name="tarix" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Günler</label>
                        <div class="week-days">
                            <input type="checkbox" id="editMonday" value="Bazar ertəsi">
                            <label for="editMonday">Bazar ertəsi</label>

                            <input type="checkbox" id="editTuesday" value="Çərşənbə axşamı">
                            <label for="editTuesday">Çərşənbə axşamı</label>

                            <input type="checkbox" id="editWednesday" value="Çərşənbə">
                            <label for="editWednesday">Çərşənbə</label>

                            <input type="checkbox" id="editThursday" value="Cümə axşamı">
                            <label for="editThursday">Cümə axşamı</label>

                            <input type="checkbox" id="editFriday" value="Cümə">
                            <label for="editFriday">Cümə</label>

                            <input type="checkbox" id="editSaturday" value="Şənbə">
                            <label for="editSaturday">Şənbə</label>

                            <input type="checkbox" id="editSunday" value="Bazar">
                            <label for="editSunday">Bazar</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="saveEditedGroup()">Yadda Saxla</button>
            </div>
        </div>
    </div>
</div>


<!-- View Group Modal -->
<div class="modal" id="viewGroupModal">
    <div class="modal-dialog" style="width: 358px;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Qrup Məlumatları</h3>
            </div>
            <div class="modal-body">
                <div style="margin-top:-5px;" class="form-group">
                    <label class="form-label">Qrup Adı:</label>
                    <span id="viewGroupName">N/A</span>

                    <label style="margin-top:10px;" class="form-label">Tələbə Sayı:</label>
                    <span id="viewStudentCount">0</span> <button style="padding:3px 8px; font-size:14px;" class="btn btn-primary">Ətraflı</button>

                    <label style="margin-top:10px;" class="form-label">Günler:</label>
                    <span id="viewGunler">N/A</span>

                    <label style="margin-top:10px;" class="form-label">Tarix:</label>
                    <span id="viewTarix">N/A</span>

                    <div hidden>
                        <label style="margin-top:10px;" class="form-label">Yaradılma Tarixi:</label>
                        <span id="viewCreatedAt">N/A</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    /* General styling for the container */
    .form-group {
        max-width: auto;
        margin: 20px auto;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    }

    /* Styling for the week-days container */
    .week-days {
        display: flex;
        flex-wrap: wrap; /* Allow wrapping if needed */
        gap: 15px; /* Space between items */
    }

    /* Styling for each checkbox container */
    .week-days label {
        display: inline-flex;
        align-items: center;
        padding: 10px 15px;
        background: rgb(245, 245, 245);
        border-radius: 8px;
        font-family: Arial, sans-serif;
        font-size: 14px;
        color: #333;
        cursor: pointer;
        transition: all 0.3s ease; /* Smooth hover effect */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); /* Soft shadow */
    }

    /* Hover effect for labels */
    .week-days label:hover {
        background: #e0f3ff; /* Light blue background on hover */
        color: #0056b3; /* Darker blue text */
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1); /* Enhanced shadow */
    }

    /* Active (checked) state for labels */
    .week-days input[type="checkbox"]:checked + label {
        background: #007bff; /* Blue background when checked */
        color: #ffffff; /* White text */
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1); /* Enhanced shadow */
    }

    /* Styling for the checkbox itself */
    .week-days input[type="checkbox"] {
        display: none; /* Hide the default checkbox */
    }

    /* Styling for the date input */
    #date {
        width: 100%; /* Full width of the container */
        padding: 12px 15px; /* Consistent padding */
        font-family: Arial, sans-serif;
        font-size: 14px;
        color: #333;
        background-color: rgb(245, 245, 245); /* Match other elements */
        border: none; /* Remove default border */
        border-radius: 8px; /* Rounded corners */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); /* Soft shadow */
        transition: all 0.3s ease; /* Smooth hover effect */
        outline: none; /* Remove focus outline */
    }

    /* Hover effect for the date input */
    #date:hover {
        background-color: #e0f3ff; /* Light blue background on hover */
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1); /* Enhanced shadow */
    }

    /* Focus effect for the date input */
    #date:focus {
        background-color: #ffffff; /* White background on focus */
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1); /* Enhanced shadow */
    }
</style>

<script>
    // Global variable to store all groups
    let allGroups = []; 

    // Function to open modal
    function openModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }

    // Function to close modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
        // Reset form if closing add group modal
        if (modalId === "addGroupModal") {
            document.getElementById("addGroupForm").reset();
        }
    }


    function viewGroup(groupId) {
        // Fetch group data from the server
        fetch(`movzular/qruplar/get_group.php?id=${groupId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.json();
            })
            .then(group => {
                if (group) {
                    // Populate modal fields
                    document.getElementById("viewGroupName").innerText = group.qrup_adi || "N/A";
                    document.getElementById("viewStudentCount").innerText = group.telebe_sayi || 0;
                    document.getElementById("viewGunler").innerText = group.gunler || "N/A";

                    // Format date for tarix
                    const tarixDate = group.tarix ? new Date(group.tarix) : null;
                    const formattedTarix = tarixDate
                        ? formatDate(tarixDate) // Use custom formatting function
                        : "Tarix mövcud deyil";
                    document.getElementById("viewTarix").innerText = formattedTarix;

                    // Format date for created_at
                    const createdDate = group.created_at ? new Date(group.created_at) : null;
                    const formattedCreatedAt = createdDate
                        ? formatDate(createdDate) // Use custom formatting function
                        : "Tarix mövcud deyil";
                    document.getElementById("viewCreatedAt").innerText = formattedCreatedAt;

                    // Show modal
                    openModal("viewGroupModal");
                } else {
                    alert("Qrup məlumatları tapılmadı.");
                }
            })
            .catch(error => {
                console.error("Xəta baş verdi:", error);
                alert("Qrup məlumatlarını yükləmək mümkün olmadı.");
            });
    }

    function formatDate(date) {
        const day = String(date.getDate()).padStart(2, '0'); // Add leading zero if needed
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${day}.${month}.${year} ${hours}:${minutes}`;
    }

  
    // Function to edit group
function editGroup(groupId) {
    // Fetch group data from the server
    fetch(`movzular/qruplar/get_group.php?id=${groupId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then(group => {
            if (group) {
                // Populate modal fields
                document.getElementById("editGroupId").value = group.id;
                document.getElementById("editGroupName").value = group.qrup_adi;
                document.getElementById("editStudentCount").value = group.telebe_sayi;

                // Populate tarix (convert to datetime-local format)
                const tarixDate = group.tarix ? new Date(group.tarix + 'Z') : null; // Append 'Z' for UTC
                const formattedTarix = tarixDate
                    ? tarixDate.toISOString().slice(0, 16) // Convert to YYYY-MM-DDTHH:MM
                    : getCurrentDateTime(); // Use current date and time if tarix is null
                document.getElementById("editDatetime").value = formattedTarix;

                // Populate gunler (split into an array and check corresponding checkboxes)
                const gunler = group.gunler ? group.gunler.split(", ") : [];
                document.querySelectorAll('#editGroupModal .week-days input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = gunler.includes(checkbox.value);
                });

                // Show modal
                openModal("editGroupModal");
            } else {
                alert("Qrup məlumatları tapılmadı.");
            }
        })
        .catch(error => {
            console.error("Xəta baş verdi:", error);
            alert("Qrup məlumatlarını yükləmək mümkün olmadı.");
        });
}

// Helper function to get the current date and time in YYYY-MM-DDTHH:MM format
function getCurrentDateTime() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0'); // Months are zero-based
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}


    // Function to save edited group
    function saveEditedGroup() {
        const groupId = document.getElementById("editGroupId").value.trim();
        const groupName = document.getElementById("editGroupName").value.trim();
        const studentCount = document.getElementById("editStudentCount").value.trim();
        const datetime = document.getElementById("editDatetime").value.trim();

        // Get selected weekdays
        const checkboxes = document.querySelectorAll('#editGroupModal .week-days input[type="checkbox"]:checked');
        const gunler = Array.from(checkboxes).map(checkbox => checkbox.value).join(", ");

        // Validate inputs
        if (!groupName || !studentCount || !datetime || gunler === "") {
            alert("Zəhmət olmasa bütün sahələri doldurun.");
            return;
        }

        // Send updated data to the server
        fetch("movzular/qruplar/edit_group.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `id=${encodeURIComponent(groupId)}&groupName=${encodeURIComponent(groupName)}&telebe_sayi=${encodeURIComponent(studentCount)}&tarix=${encodeURIComponent(datetime)}&gunler=${encodeURIComponent(gunler)}`,
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.text();
            })
            .then(data => {
                if (data === "success") {
                    alert("Qrup uğurla yeniləndi.");
                    closeModal("editGroupModal");
                    fetchGroups(); // Refresh the table after updating the group
                } else {
                    throw new Error(data); // Handle server-side errors
                }
            })
            .catch(error => {
                console.error("Xəta baş verdi:", error);
                alert("Qrup yenilənməsində xəta baş verdi: " + error.message);
            });
    }

    // Function to save new group
    function saveGroup() {
        const groupName = document.getElementById("groupName").value.trim();
        const studentCount = document.getElementById("studentCount").value.trim();
        const datetime = document.getElementById("datetime").value.trim();

        // Get selected weekdays
        const checkboxes = document.querySelectorAll('#addGroupModal .week-days input[type="checkbox"]:checked');
        const gunler = Array.from(checkboxes).map(checkbox => checkbox.value).join(", ");

        // Validate inputs
        if (!groupName || !studentCount || !datetime || gunler === "") {
            alert("Zəhmət olmasa bütün sahələri doldurun.");
            return;
        }

        // Convert datetime-local value to MySQL DATETIME format
        const formattedDatetime = datetime.replace("T", " ") + ":00";

        // Send data to the server
        fetch("movzular/qruplar/add_group.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `groupName=${encodeURIComponent(groupName)}&telebe_sayi=${encodeURIComponent(studentCount)}&tarix=${encodeURIComponent(formattedDatetime)}&gunler=${encodeURIComponent(gunler)}`,
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.text();
            })
            .then(data => {
                if (data === "success") {
                    alert("Qrup uğurla əlavə edildi.");
                    closeModal("addGroupModal");
                    fetchGroups(); // Refresh the table after adding a new group
                } else {
                    throw new Error(data); // Handle server-side errors
                }
            })
            .catch(error => {
                console.error("Xəta baş verdi:", error);
                alert("Qrup əlavə edilərkən xəta baş verdi: " + error.message);
            });
    }

    // Function to fetch groups from server
    function fetchGroups() {
        fetch("movzular/qruplar/get_groups.php")
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((groups) => {
                allGroups = groups; // Store fetched groups in the global variable
                populateTable(groups); // Populate the table initially
            })
            .catch((error) => {
                console.error("Error:", error);
                const tableBody = document.querySelector("#groupsTable tbody");
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Xəta baş verdi: ${error.message}</td></tr>`;
            });
    }

    // Function to populate the table with groups
    function populateTable(groups) {
        const tableBody = document.querySelector("#groupsTable tbody");
        tableBody.innerHTML = "";

        if (groups.error) {
            // Handle server-side errors
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Xəta: ${groups.error}</td></tr>`;
            return;
        }

        if (groups.length === 0) {
            // Handle case where no groups are returned
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Heç bir qrup tapılmadı</td></tr>';
            return;
        }

        // Populate table rows with group data
        groups.forEach((group) => {
            const row = document.createElement("tr");
            row.setAttribute("data-group-name", group.qrup_adi.toLowerCase());

            // Format date with fallback
            const createdDate = new Date(group.created_at);
            const formattedDate = createdDate.toLocaleDateString("az-AZ") || "Tarix mövcud deyil";

            row.innerHTML = `
                <td>${group.qrup_adi || "N/A"}</td>
                <td>${group.student_count || 0}</td>
                <td>${formattedDate}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-info" onclick="viewGroup(${group.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="editGroup(${group.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteGroup(${group.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            tableBody.appendChild(row);
        });
    }

    // Function to filter groups based on search input
    function filterGroups() {
        const searchQuery = document.getElementById("groupSearchInput").value.trim().toLowerCase();

        // Filter groups based on the search query
        const filteredGroups = allGroups.filter((group) =>
            group.qrup_adi.toLowerCase().includes(searchQuery)
        );

        // Repopulate the table with the filtered groups
        populateTable(filteredGroups);
    }

    

    function deleteGroup(groupId) {
        // Check if a delete modal already exists and remove it
        const existingOverlay = document.getElementById(`delete-overlay`);
        if (existingOverlay) existingOverlay.remove();
        
        // Create modal HTML
        const modalHTML = `
            <div id="delete-overlay" class="delete-modal-overlay">
                <div id="delete-modal" class="delete-modal-container">
                    <div class="delete-modal-content">
                        <p class="delete-modal-title">Qrupu Silmək</p>
                        <p class="delete-modal-message">Qrupu silmək istədiyinizdən əminsiniz?</p>
                        <div class="delete-modal-buttons">
                            <button type="button" id="delete-cancel-btn" class="delete-modal-cancel">Ləğv et</button>
                            <button type="button" id="delete-confirm-btn" class="delete-modal-confirm">Sil</button>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                .delete-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.75);
                    z-index: 9999;
                    display: flex;
                    justify-content: center;
                    align-items: flex-start;
                    padding-top: 50px;
                }
                
                .delete-modal-container {
                    background-color: #ffffff;
                    width: 300px;
                    max-width: 95%;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    z-index: 10000;
                    padding: 15px;
                    margin: 0 10px;
                }
                
                .delete-modal-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #000;
                    margin: 0 0 8px;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #eee;
                }
                
                .delete-modal-message {
                    margin: 0 0 15px;
                    font-size: 14px;
                    color: #333;
                }
                
                .delete-modal-buttons {
                    display: flex;
                    justify-content: flex-end;
                    gap: 8px;
                }
                
                .delete-modal-cancel, .delete-modal-confirm {
                    padding: 8px 18px;
                    border: none;
                    border-radius: 4px;
                    font-size: 13px;
                    cursor: pointer;
                }
                
                .delete-modal-cancel {
                    background-color: #6b7280;
                    color: #fff;
                }
                
                .delete-modal-confirm {
                    background-color: #dc2626;
                    color: #fff;
                }
                
                @media (max-width: 480px) {
                    .delete-modal-overlay {
                        padding-top: 50px;
                    }
                    
                    .delete-modal-container {
                        width: 280px;
                        padding: 12px;
                    }
                    
                    .delete-modal-title {
                        font-size: 15px;
                    }
                    
                    .delete-modal-message {
                        font-size: 13px;
                    }
                    
                    .delete-modal-buttons {
                        gap: 6px;
                    }
                    
                    .delete-modal-cancel, .delete-modal-confirm {
                        padding: 7px 10px;
                        font-size: 12px;
                    }
                }
            </style>
        `;
        
        // Add modal to the DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Prevent scrolling on the body
        document.body.style.overflow = 'hidden';
        
        // Get elements
        const overlay = document.getElementById('delete-overlay');
        const cancelBtn = document.getElementById('delete-cancel-btn');
        const confirmBtn = document.getElementById('delete-confirm-btn');
        
        // Close modal function
        const closeDeleteModal = () => {
            overlay.remove();
            document.body.style.overflow = '';
        };
        
        // Add event listeners
        cancelBtn.addEventListener('click', closeDeleteModal);
        
        confirmBtn.addEventListener('click', () => {
            // Show loading state
            confirmBtn.textContent = 'Silinir...';
            confirmBtn.disabled = true;
            
            // Send delete request to the server
            fetch(`movzular/qruplar/delete_group.php?id=${groupId}`, {
                method: "DELETE",
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.text();
                })
                .then(data => {
                    if (data === "success") {
                        closeDeleteModal();
                        fetchGroups(); // Refresh the table
                    } else {
                        throw new Error(data);
                    }
                })
                .catch(error => {
                    console.error("Xəta baş verdi:", error);
                    alert("Qrup silinərkən xəta baş verdi: " + error.message);
                    closeDeleteModal();
                });
        });
    }

    // Initialize the page
    document.addEventListener("DOMContentLoaded", () => {
        // Load groups when the page loads
        fetchGroups();

        // Close modals when clicking outside of them
        window.onclick = (event) => {
            if (event.target.classList.contains("modal")) {
                event.target.style.display = "none";
            }
        };
    });
</script>