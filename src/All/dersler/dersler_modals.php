
<!-- Add Lesson Modal -->
<div class="modal fade" id="addLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Dərs Əlavə Et</h5>
            </div>
            <div class="modal-body">
                <form id="addLessonForm" action="dersler/dersler_operations.php?action=add" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subject">Fənn</label>
                                <select class="form-control" id="subject" name="subject" required>
                                    <option value="">Seçin</option>
                                    <option value="1">Riyaziyyat</option>
                                    <option value="2">Fizika</option>
                                    <option value="3">Kimya</option>
                                    <option value="4">Biologiya</option>
                                    <option value="5">Tarix</option>
                                    <option value="6">Ədəbiyyat</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class">Sinif</label>
                                <select class="form-control" id="class" name="class" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    include('db.php');
                                    $sql = "SELECT id, sinif_number FROM sinifler";
                                    $result = $conn->query($sql);

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["sinif_number"]) . "</option>";
                                        }
                                    }
                                    ?>
                                    <option value="new">+ Yeni Sinif Əlavə Et</option>
                                </select>
                                <div class="invalid-feedback">Sinif seçin</div>
                            </div>
                        </div>
                    </div>
                    <br>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="teacher">Müəllim</label>
                                <select class="form-control" id="teacher" name="teacher" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    $sql = "SELECT id, username FROM muellimler_new";
                                    $result = $conn->query($sql);
                            
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["username"]) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="room">Otaq</label>
                                <select class="form-control" id="room" name="room" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    $sql = "SELECT id, otaq_number FROM otaqlar";
                                    $result = $conn->query($sql);
                            
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["otaq_number"]) . "</option>";
                                        }
                                    }
                                    $conn->close();
                                    ?>
                                    <option value="new">+ Yeni Otaq Əlavə Et</option>
                                </select>
                                <div class="invalid-feedback">Otaq seçin</div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date">Tarix</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="startTime">Başlama vaxtı</label>
                                <input type="time" class="form-control" id="startTime" name="startTime" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="endTime">Bitmə vaxtı</label>
                                <input type="time" class="form-control" id="endTime" name="endTime" required>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="topic">Mövzu</label>
                        <input type="text" class="form-control" id="topic" name="topic" required>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="description">Təsvir</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <br>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="">Seçin</option>
                                <option value="Aktiv">Aktiv</option>
                                <option value="Dəyişiklik var">Dəyişiklik var</option>
                                <option value="Ləğv edilib">Ləğv edilib</option>
                                <option value="Planlaşdırılıb" selected>Planlaşdırılıb</option>
                            </select>
                        </div>
                    </div>
                    <br>
                    <div hidden class="form-group">
                        <label for="materials">Materiallar</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="materials" name="materials[]" multiple>
                            <label class="custom-file-label" for="materials">Faylları seçin</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                        <button type="submit" class="btn btn-primary">Yadda saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Lesson Modal -->
<div class="modal fade" id="editLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dərsi Redaktə Et</h5>
            </div>
            <div class="modal-body">
                <form id="editLessonForm" enctype="multipart/form-data">
                    <input type="hidden" id="lessonId" name="lessonId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editSubject">Fənn</label>
                                <select class="form-control" id="editSubject" name="subject" required>
                                    <option value="">Seçin</option>
                                    <option value="1">Riyaziyyat</option>
                                    <option value="2">Fizika</option>
                                    <option value="3">Kimya</option>
                                    <option value="4">Biologiya</option>
                                    <option value="5">Tarix</option>
                                    <option value="6">Ədəbiyyat</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editClass">Sinif</label>
                                <select class="form-control" id="editClass" name="class" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    include('db.php');
                                    $sql = "SELECT id, sinif_number FROM sinifler";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["sinif_number"]) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editTeacher">Müəllim</label>
                                <select class="form-control" id="editTeacher" name="teacher" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    $sql = "SELECT id, username FROM muellimler_new";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["username"]) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editRoom">Otaq</label>
                                <select class="form-control" id="editRoom" name="room" required>
                                    <option value="">Seçin</option>
                                    <?php
                                    $sql = "SELECT id, otaq_number FROM otaqlar";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["otaq_number"]) . "</option>";
                                        }
                                    }
                                    $conn->close();
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editDate">Tarix</label>
                                <input type="date" class="form-control" id="editDate" name="date" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="editStartTime">Başlama vaxtı</label>
                                <input type="time" class="form-control" id="editStartTime" name="startTime" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="editEndTime">Bitmə vaxtı</label>
                                <input type="time" class="form-control" id="editEndTime" name="endTime" required>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="editTopic">Mövzu</label>
                        <input type="text" class="form-control" id="editTopic" name="topic" required>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="editDescription">Təsvir</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    <br>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="editStatus">Status</label>
                            <select class="form-control" id="editStatus" name="status" required>
                                <option value="">Seçin</option>
                                <option value="Aktiv">Aktiv</option>
                                <option value="Dəyişiklik var">Dəyişiklik var</option>
                                <option value="Ləğv edilib">Ləğv edilib</option>
                                <option value="Planlaşdırılıb">Planlaşdırılıb</option>
                            </select>
                        </div>
                    </div>
                    <br>
                    <div hidden class="form-group">
                        <label for="editMaterials">Materiallar</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="editMaterials" name="materials[]" multiple>
                            <label class="custom-file-label" for="editMaterials">Faylları seçin</label>
                        </div>
                        <small class="form-text text-muted">Yeni fayllar əlavə etmək üçün seçin. Mövcud fayllar saxlanılacaq.</small>
                    </div>
                    <!-- Existing attachments will be displayed here -->
                    <div hidden id="existingAttachments" class="mt-3" style="display: none;">
                        <h6>Mövcud materiallar:</h6>
                        <div class="existing-files-list"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary" id="saveEditLesson">Yadda Saxla</button>
            </div>
        </div>
    </div>
</div>

<!-- View Lesson Modal -->
<div class="modal fade" id="viewLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dərs Məlumatları</h5>
            </div>
            <div class="modal-body">
                <div class="card mb-4">
                    <div class="card-body">
                        <!-- Header Section -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0" id="lesson-header"></h4>
                            <span id="lesson-status-badge" class="badge"></span>
                        </div>
                        <p class="text-muted" id="lesson-date-time-room"></p>

                        <!-- Details Section -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6>Müəllim</h6>
                                <p id="lesson-teacher"></p>

                                <h6 class="mt-3">Mövzu</h6>
                                <p id="lesson-topic"></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Şagird sayı</h6>
                                <p id="lesson-student-count"></p>

                                <div hidden>
                                <h6 class="mt-3">Status</h6>
                                <p id="lesson-status-text"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Description Section -->
                        <h6 class="mt-3">Təsvir</h6>
                        <p id="lesson-description"></p>

                        <div hidden>
                        <h6 class="mt-3">Materiallar</h6>
                        <div id="lesson-materials" class="file-list">
                            <!-- Files will be dynamically added here -->
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Lesson Modal -->
<div class="modal fade" id="deleteLessonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dərsi Sil</h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5>Bu dərsi silmək istədiyinizə əminsiniz?</h5>
                    <p class="text-muted">Bu əməliyyat geri qaytarıla bilməz.</p>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="deleteConfirm">
                        <label class="custom-control-label" for="deleteConfirm">Bəli, bu dərsi silmək istəyirəm</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-danger" id="confirmDelete" disabled>Sil</button>
            </div>
        </div>
    </div>
</div>

<!-- New Class Modal -->
<div class="modal fade" id="newClassModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Sinif Əlavə Et</h5>
            </div>
            <div class="modal-body">
                <form id="addClassForm" action="dersler_operations.php?action=add_class" method="post">
                    <div class="form-group">
                        <label for="classNumber">Sinif Nömrəsi</label>
                        <input type="text" class="form-control" id="classNumber" name="classNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="classCapacity">Tutum (Nəfər)</label>
                        <input type="number" class="form-control" id="classCapacity" name="classCapacity" min="1" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                        <button type="submit" class="btn btn-primary">Yadda Saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- New Room Modal -->
<div class="modal fade" id="newRoomModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Otaq Əlavə Et</h5>
            </div>
            <div class="modal-body">
                <form id="addRoomForm" action="dersler_operations.php?action=add_room" method="post">
                    <div class="form-group">
                        <label for="roomNumber">Otaq Nömrəsi</label>
                        <input type="text" class="form-control" id="roomNumber" name="otaq_number" required>
                    </div>
                    <div class="form-group">
                        <label for="roomCapacity">Tutum (Nəfər)</label>
                        <input type="number" class="form-control" id="roomCapacity" name="tutum" min="1" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
                        <button type="submit" class="btn btn-primary">Yadda Saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Modal -->
<div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Təqvim</h5>
            </div>
            <div class="modal-body">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button class="btn btn-sm btn-outline-secondary" id="prevMonth">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h4 id="currentMonth">Aprel 2025</h4>
                        <button class="btn btn-sm btn-outline-secondary" id="nextMonth">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid">
                        <div class="calendar-day-header">B.</div>
                        <div class="calendar-day-header">B.e</div>
                        <div class="calendar-day-header">Ç.a</div>
                        <div class="calendar-day-header">Ç.</div>
                        <div class="calendar-day-header">C.a</div>
                        <div class="calendar-day-header">C.</div>
                        <div class="calendar-day-header">Ş.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<!-- Today's Lessons Modal -->
<div class="modal fade" id="todayLessonsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bu Günün Dərsləri</h5>
            </div>
            <div class="modal-body">
                <div class="today-lessons-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 id="todayDate"><?php echo date('d F Y'); ?></h4>
                        <div class="badge badge-primary p-2">
                            <i class="fas fa-calendar-day mr-1"></i>
                            <span id="todayLessonCount">0</span> Dərs
                        </div>
                    </div>
                    <div id="todayLessonsList">
                        <!-- Today's lessons will be dynamically loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<!-- Stat Details Modal -->
<div class="modal fade" id="statDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statDetailsTitle">Məlumatlar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
            </div>
            <div class="modal-body">
                <div id="statDetailsLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Yüklənir...</span>
                    </div>
                </div>
                <div class="table-responsive d-none" id="statDetailsContent">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light" id="statDetailsHead"></thead>
                        <tbody id="statDetailsBody"></tbody>
                    </table>
                </div>
                <div id="statDetailsEmpty" class="text-center py-4 text-muted d-none">
                    Məlumat tapılmadı
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>