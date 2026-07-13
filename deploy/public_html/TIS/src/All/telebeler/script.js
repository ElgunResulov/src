    
    $(document).ready(function() {
        // Hide preloader when page is loaded
        $(".preloader").fadeOut();
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Handle file input display
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
        
        // Save student button click
        $('#saveStudent').on('click', function() {
            // Validate form
            if ($('#addStudentForm')[0].checkValidity()) {
                // Here you would normally submit the form via AJAX
                // For demo purposes, we'll just show an alert
                alert('Tələbə məlumatları uğurla yadda saxlanıldı!');
                $('#addStudentModal').modal('hide');
            } else {
                $('#addStudentForm')[0].reportValidity();
            }
        });
        
        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#searchStudent').val('');
            $('#filterClass').val('');
            $('#filterStatus').val('');
            // Here you would normally trigger the search/filter function
            alert('Filtrlər sıfırlandı!');
        });
        
        // View and edit modals are handled in telebeler/table-search.php

        // Add student 
        $('.add-student').on('click', function(e) {
            e.preventDefault();
            $('#addStudentModal').modal('show');
        });
        
        // Export students
        $('#exportStudents').on('click', function() {
            alert('Tələbələr ixrac edilir...');
            // Here you would normally trigger the export function
        });
        
        // Initialize charts
        if ($('#classAveragesChart').length) {
            var classCtx = document.getElementById('classAveragesChart').getContext('2d');
            var classChart = new Chart(classCtx, {
                type: 'bar',
                data: {
                    labels: ['9A', '9B', '10A', '10B', '11A', '11B'],
                    datasets: [{
                        label: 'Orta Bal',
                        data: [82, 78, 80, 76, 85, 79],
                        backgroundColor: 'rgba(29, 106, 157, 0.7)',
                        borderColor: 'rgba(29, 106, 157, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
        
        if ($('#subjectAveragesChart').length) {
            var subjectCtx = document.getElementById('subjectAveragesChart').getContext('2d');
            var subjectChart = new Chart(subjectCtx, {
                type: 'radar',
                data: {
                    labels: ['Riyaziyyat', 'Fizika', 'Kimya', 'Biologiya', 'Tarix', 'Ədəbiyyat'],
                    datasets: [{
                        label: 'Orta Bal',
                        data: [75, 78, 80, 82, 76, 85],
                        backgroundColor: 'rgba(29, 106, 157, 0.2)',
                        borderColor: 'rgba(29, 106, 157, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(29, 106, 157, 1)'
                    }]
                },
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    });



        // Close modals with the class 'close-modal'
        const closeModalBtns = document.querySelectorAll('.close-modal');
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const modal = this.closest('.modal');
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            });
        });


(function () {
    var studentStatTitles = {
        all: 'Bütün Tələbələr',
        active: 'Aktiv Tələbələr',
        gender: 'Cins Üzrə Tələbələr',
        grades: 'Orta Ballar'
    };

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function openStudentStatModal(type) {
        var modalEl = document.getElementById('statDetailsModal');
        if (!modalEl) return;

        document.getElementById('statDetailsTitle').textContent = studentStatTitles[type] || 'Məlumatlar';
        document.getElementById('statDetailsLoading').classList.remove('d-none');
        document.getElementById('statDetailsContent').classList.add('d-none');
        document.getElementById('statDetailsEmpty').classList.add('d-none');
        document.getElementById('statDetailsHead').innerHTML = '';
        document.getElementById('statDetailsBody').innerHTML = '';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        fetch('telebeler/stat_operations.php?type=' + encodeURIComponent(type))
            .then(function (response) { return response.json(); })
            .then(function (data) {
                document.getElementById('statDetailsLoading').classList.add('d-none');
                if (data.status !== 'success' || !data.data || !data.data.length) {
                    document.getElementById('statDetailsEmpty').classList.remove('d-none');
                    return;
                }
                var columns = data.columns;
                var rows = data.data;
                document.getElementById('statDetailsContent').classList.remove('d-none');

                var headHtml = '<tr>';
                columns.forEach(function (column) {
                    headHtml += '<th>' + escapeHtml(column.label) + '</th>';
                });
                headHtml += '</tr>';
                document.getElementById('statDetailsHead').innerHTML = headHtml;

                var bodyHtml = '';
                rows.forEach(function (row) {
                    bodyHtml += '<tr>';
                    columns.forEach(function (column) {
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

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.stat-card-clickable').forEach(function (card) {
            card.addEventListener('click', function () {
                openStudentStatModal(card.dataset.statType);
            });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openStudentStatModal(card.dataset.statType);
                }
            });
        });
    });
})();




        let studentToDelete = null;

        function openDeleteModal(id) {
            studentToDelete = id;
            document.getElementById('deleteConfirm').checked = false;
            document.getElementById('confirmDeleteBtn').disabled = true;
            $('#deleteStudentModal').modal('show');
        }
    
        document.getElementById('deleteConfirm').addEventListener('change', function () {
            document.getElementById('confirmDeleteBtn').disabled = !this.checked;
        });
    
        document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
            if (studentToDelete !== null) {
                const formData = new FormData();
                formData.append("id", studentToDelete);
    
                fetch('telebeler/delete_student.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result.trim() === "success") {
                        window.location.href = "Tələbələr.php?deleted=1";
                    } else {
                        window.location.href = "Tələbələr.php?deleted=0";
                    }
                });
            }
        });