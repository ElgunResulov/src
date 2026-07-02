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
    
    // Toggle repeat until date field
    $('#repeatWeekly').on('change', function() {
        if ($(this).is(':checked')) {
            $('#repeatUntilGroup').show();
        } else {
            $('#repeatUntilGroup').hide();
        }
    });
    
    // Save lesson button click
    $('#saveLesson').on('click', function() {
        // Validate form
        if ($('#addLessonForm')[0].checkValidity()) {
            // Here you would normally submit the form via AJAX
            // For demo purposes, we'll just show an alert
            alert('Dərs məlumatları uğurla yadda saxlanıldı!');
            $('#addLessonModal').modal('hide');
            
            // Refresh calendar
            calendar.refetchEvents();
        } else {
            $('#addLessonForm')[0].reportValidity();
        }
    });
    
    // View lesson details
    $(document).on('click', '.fc-event', function() {
        $('#viewLessonModal').modal('show');
    });
    
    // Edit lesson
    $('.edit-lesson').on('click', function() {
        $('#viewLessonModal').modal('hide');
        setTimeout(function() {
            $('#addLessonModal').modal('show');
        }, 500);
    });
    
    // Delete lesson confirmation
    $('.delete-lesson').on('click', function() {
        $('#viewLessonModal').modal('hide');
        setTimeout(function() {
            $('#deleteLessonModal').modal('show');
        }, 500);
    });
    
    // Enable delete button when checkbox is checked
    $('#deleteConfirm').on('change', function() {
        $('#confirmDelete').prop('disabled', !this.checked);
    });
    
    // Confirm delete action
    $('#confirmDelete').on('click', function() {
        // Here you would normally send an AJAX request to delete the lesson
        alert('Dərs uğurla silindi!');
        $('#deleteLessonModal').modal('hide');
        
        // Refresh calendar
        calendar.refetchEvents();
    });
    
    // Export schedule
    $('#exportSchedule').on('click', function() {
        alert('Dərs cədvəli ixrac edilir...');
        // Here you would normally trigger the export function
    });
    
    // Print schedule
    $('#printSchedule, #printScheduleBtn').on('click', function() {
        alert('Dərs cədvəli çap edilir...');
        // Here you would normally trigger the print function
    });
    
    // View teacher schedule
    $('#viewTeacherScheduleBtn').on('click', function() {
        $('#filterTeacher').val('1').trigger('change');
        $('#filterClass, #filterSubject, #filterRoom').val('').trigger('change');
        calendar.refetchEvents();
    });
    
    // View class schedule
    $('#viewClassScheduleBtn').on('click', function() {
        $('#filterClass').val('9A').trigger('change');
        $('#filterTeacher, #filterSubject, #filterRoom').val('').trigger('change');
        calendar.refetchEvents();
    });
    
    // View switcher buttons
    $('#weekViewBtn').on('click', function() {
        $('.view-switcher .btn').removeClass('active');
        $(this).addClass('active');
        calendar.changeView('timeGridWeek');
    });
    
    $('#dayViewBtn').on('click', function() {
        $('.view-switcher .btn').removeClass('active');
        $(this).addClass('active');
        calendar.changeView('timeGridDay');
    });
    
    $('#listViewBtn').on('click', function() {
        $('.view-switcher .btn').removeClass('active');
        $(this).addClass('active');
        calendar.changeView('listWeek');
    });
    
    $('#resourceViewBtn').on('click', function() {
        $('.view-switcher .btn').removeClass('active');
        $(this).addClass('active');
        calendar.changeView('resourceTimeGridDay');
    });
    
    // Filter change events
    $('#filterTeacher, #filterClass, #filterSubject, #filterRoom').on('change', function() {
        calendar.refetchEvents();
    });
    
    // Initialize FullCalendar
    var calendarEl = document.getElementById('scheduleCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        slotDuration: '00:45:00',
        allDaySlot: false,
        height: 'auto',
        locale: 'az',
        buttonText: {
            today: 'Bu gün',
            month: 'Ay',
            week: 'Həftə',
            day: 'Gün',
            list: 'Siyahı'
        },
        dayHeaderFormat: { weekday: 'long', day: 'numeric', month: 'numeric' },
        events: [
            {
                id: '1',
                title: 'Riyaziyyat - 9A',
                start: '2023-09-18T08:00:00',
                end: '2023-09-18T08:45:00',
                className: 'math-event',
                extendedProps: {
                    teacher: 'Əliyev Rəşad',
                    class: '9A',
                    room: '101',
                    topic: 'Kvadrat tənliklər',
                    notes: 'Ev tapşırığı: Səh. 25, tapşırıq 1-5'
                }
            },
            {
                id: '2',
                title: 'Fizika - 10B',
                start: '2023-09-18T09:00:00',
                end: '2023-09-18T09:45:00',
                className: 'physics-event',
                extendedProps: {
                    teacher: 'Hüseynov Elçin',
                    class: '10B',
                    room: '201',
                    topic: 'Nyutonun qanunları',
                    notes: 'Laboratoriya işi'
                }
            },
            {
                id: '3',
                title: 'Kimya - 11A',
                start: '2023-09-18T10:00:00',
                end: '2023-09-18T10:45:00',
                className: 'chemistry-event',
                extendedProps: {
                    teacher: 'Məmmədova Aygün',
                    class: '11A',
                    room: 'lab1',
                    topic: 'Üzvi birləşmələr',
                    notes: 'Laboratoriya işi'
                }
            },
            {
                id: '4',
                title: 'Biologiya - 9B',
                start: '2023-09-18T11:00:00',
                end: '2023-09-18T11:45:00',
                className: 'biology-event',
                extendedProps: {
                    teacher: 'Qasımova Sevda',
                    class: '9B',
                    room: '102',
                    topic: 'Hüceyrə quruluşu',
                    notes: ''
                }
            },
            {
                id: '5',
                title: 'Tarix - 10A',
                start: '2023-09-18T12:00:00',
                end: '2023-09-18T12:45:00',
                className: 'history-event',
                extendedProps: {
                    teacher: 'Nəsirov Tural',
                    class: '10A',
                    room: '103',
                    topic: 'Orta əsrlər',
                    notes: 'Prezentasiya'
                }
            },
            {
                id: '6',
                title: 'Ədəbiyyat - 11B',
                start: '2023-09-18T13:00:00',
                end: '2023-09-18T13:45:00',
                className: 'literature-event',
                extendedProps: {
                    teacher: 'Qasımova Sevda',
                    class: '11B',
                    room: '202',
                    topic: 'Nizami Gəncəvi',
                    notes: 'Esse yazılacaq'
                }
            },
            {
                id: '7',
                title: 'İngilis dili - 9A',
                start: '2023-09-18T14:00:00',
                end: '2023-09-18T14:45:00',
                className: 'english-event',
                extendedProps: {
                    teacher: 'Məmmədova Aygün',
                    class: '9A',
                    room: '203',
                    topic: 'Present Perfect',
                    notes: 'Workbook p.15'
                }
            },
            {
                id: '8',
                title: 'Coğrafiya - 10B',
                start: '2023-09-18T15:00:00',
                end: '2023-09-18T15:45:00',
                className: 'geography-event',
                extendedProps: {
                    teacher: 'Əliyev Rəşad',
                    class: '10B',
                    room: '101',
                    topic: 'İqlim zonaları',
                    notes: 'Xəritə işi'
                }
            },
            
            // Tuesday
            {
                id: '9',
                title: 'Riyaziyyat - 10A',
                start: '2023-09-19T08:00:00',
                end: '2023-09-19T08:45:00',
                className: 'math-event',
                extendedProps: {
                    teacher: 'Əliyev Rəşad',
                    class: '10A',
                    room: '101',
                    topic: 'Loqarifmik funksiyalar',
                    notes: ''
                }
            },
            {
                id: '10',
                title: 'Fizika - 11B',
                start: '2023-09-19T09:00:00',
                end: '2023-09-19T09:45:00',
                className: 'physics-event',
                extendedProps: {
                    teacher: 'Hüseynov Elçin',
                    class: '11B',
                    room: '201',
                    topic: 'Elektromaqnit dalğaları',
                    notes: ''
                }
            },
            
            // Wednesday
            {
                id: '11',
                title: 'Kimya - 9B',
                start: '2023-09-20T08:00:00',
                end: '2023-09-20T08:45:00',
                className: 'chemistry-event',
                extendedProps: {
                    teacher: 'Məmmədova Aygün',
                    class: '9B',
                    room: 'lab1',
                    topic: 'Kimyəvi reaksiyalar',
                    notes: 'Laboratoriya işi'
                }
            },
            {
                id: '12',
                title: 'Biologiya - 10A',
                start: '2023-09-20T09:00:00',
                end: '2023-09-20T09:45:00',
                className: 'biology-event',
                extendedProps: {
                    teacher: 'Qasımova Sevda',
                    class: '10A',
                    room: '102',
                    topic: 'Genetika',
                    notes: ''
                }
            },
            
            // Thursday
            {
                id: '13',
                title: 'Tarix - 11A',
                start: '2023-09-21T08:00:00',
                end: '2023-09-21T08:45:00',
                className: 'history-event',
                extendedProps: {
                    teacher: 'Nəsirov Tural',
                    class: '11A',
                    room: '103',
                    topic: 'Müasir dövr',
                    notes: ''
                }
            },
            {
                id: '14',
                title: 'Ədəbiyyat - 9A',
                start: '2023-09-21T09:00:00',
                end: '2023-09-21T09:45:00',
                className: 'literature-event',
                extendedProps: {
                    teacher: 'Qasımova Sevda',
                    class: '9A',
                    room: '202',
                    topic: 'Mirzə Fətəli Axundov',
                    notes: ''
                }
            },
            
            // Friday
            {
                id: '15',
                title: 'İngilis dili - 10B',
                start: '2023-09-22T08:00:00',
                end: '2023-09-22T08:45:00',
                className: 'english-event',
                extendedProps: {
                    teacher: 'Məmmədova Aygün',
                    class: '10B',
                    room: '203',
                    topic: 'Conditionals',
                    notes: ''
                }
            },
            {
                id: '16',
                title: 'Coğrafiya - 11B',
                start: '2023-09-22T09:00:00',
                end: '2023-09-22T09:45:00',
                className: 'geography-event',
                extendedProps: {
                    teacher: 'Əliyev Rəşad',
                    class: '11B',
                    room: '101',
                    topic: 'Geosiyasət',
                    notes: ''
                }
            }
        ],
        eventClick: function(info) {
            // Update modal with event details
            $('#viewSubject').text(info.event.title.split(' - ')[0]);
            $('#viewTeacher').text(info.event.extendedProps.teacher);
            $('#viewClass').text(info.event.extendedProps.class);
            $('#viewRoom').text(info.event.extendedProps.room);
            
            // Format date and time
            var startDate = new Date(info.event.start);
            var endDate = new Date(info.event.end);
            var formattedDate = startDate.toLocaleDateString('az-AZ');
            var formattedStartTime = startDate.toLocaleTimeString('az-AZ', { hour: '2-digit', minute: '2-digit' });
            var formattedEndTime = endDate.toLocaleTimeString('az-AZ', { hour: '2-digit', minute: '2-digit' });
            
            $('#viewDateTime').text(formattedDate + ', ' + formattedStartTime + ' - ' + formattedEndTime);
            $('#viewTopic').text(info.event.extendedProps.topic || 'Təyin edilməyib');
            $('#viewNotes').text(info.event.extendedProps.notes || 'Qeyd yoxdur');
            
            // Show modal
            $('#viewLessonModal').modal('show');
        },
        eventContent: function(arg) {
            var event = arg.event;
            var timeText = arg.timeText;
            var title = event.title;
            
            // Split title into subject and class
            var parts = title.split(' - ');
            var subject = parts[0];
            var classGroup = parts[1];
            
            // Create custom content
            var content = document.createElement('div');
            content.innerHTML = '<div class="fc-event-time">' + timeText + '</div>' +
                                '<div class="fc-event-title">' + subject + '</div>' +
                                '<div class="fc-event-class">' + classGroup + '</div>' +
                                '<div class="fc-event-room">Otaq: ' + event.extendedProps.room + '</div>';
            return { domNodes: [content] };
        }
    });
    
    calendar.render();
});
