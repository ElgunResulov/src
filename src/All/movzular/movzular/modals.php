<?php
include('db.php');

if (!isset($_SESSION['u_id'])) {
    echo '<tr><td colspan="5" class="text-center">İstifadəçi daxil olmayıb.</td></tr>';
    exit;
}

$u_id = trim($conn->real_escape_string($_SESSION['u_id']));
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mövzular</title>
</head>
<body>
<div class="section" id="topics">
    <div class="d-flex justify-content-between align-items-center mb-20">
        <h1></h1>
        <button class="btn btn-primary" onclick="openModal('addTopicModal')">
            <i class="fas fa-plus"></i> Yeni Mövzu
        </button>
    </div>
    <div class="card mb-0">
        <div class="card-header">
            <h3 class="card-title">Mövzular Siyahısı</h3>
        </div>
        <div style="box-shadow:0px 0px 0px white;" class="card m-1 mb-20">
            <div class="mb-2 d-flex gap-10">
                <select style="border-radius:10px;" class="form-select" id="topicSubjectFilter" onchange="filterTopics()">
                    <option value="">Bütün Fənlər</option>
                    <?php
                        $u_id = trim($conn->real_escape_string($_SESSION['u_id']));
                        $sql = "SELECT id, fenn FROM movzular_new WHERE u_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $u_id); // Assuming u_id is an integer; use "s" if it's a string
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["fenn"]) . "</option>";
                            }
                        }
                        $stmt->close();
                    ?>
                </select>
            </div>
            <div class="d-flex gap-10">
                <input style="border-radius:10px;" type="text" class="form-control" id="topicSearchInput" placeholder="Axtar..." oninput="filterTopics()">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Mövzu Adı</th>
                            <th>Fənn</th>
                            <th>Təsvir</th>
                            <th hidden>Yaradılış Tarixi</th>
                            <th>Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT m.u_id, m.id, m.movzu_adi, m.fenn, m.fenn_id, m.tesvir, m.created_at
                                FROM movzular_new m
                                LEFT JOIN fennler_new f ON m.fenn_id = f.fenn_id 
                                WHERE m.u_id = ?
                                ORDER BY m.id DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $u_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $created_at = new DateTime($row['created_at']);
                                echo '<tr 
                                    data-id="' . $row['id'] . '"
                                    data-movzu="' . htmlspecialchars($row['movzu_adi'], ENT_QUOTES) . '" 
                                    data-fenn="' . htmlspecialchars($row['fenn'], ENT_QUOTES) . '" 
                                    data-tesvir="' . (!empty($row['tesvir']) ? htmlspecialchars($row['tesvir'], ENT_QUOTES) : 'Təsvir yoxdur') . '"
                                    data-created-at="' . $created_at->format('Y-m-d H:i') . '"
                                >';
                                echo '<td>' . htmlspecialchars($row['movzu_adi']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['fenn']) . '</td>';
                                echo '<td>' . (empty($row['tesvir']) ? 'Təsvir yoxdur' : htmlspecialchars($row['tesvir'])) . '</td>';
                                echo '<td hidden>' . $created_at->format('Y-m-d H:i') . '</td>';
                                echo '<td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="viewTopic(' . $row['id'] . ')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick="editTopic(' . $row['id'] . ')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteTopic(' . $row['id'] . ')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">Mövzu tapılmadı.</td></tr>';
                        }
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Topic Modal -->
<div class="modal fade" id="addTopicModal" tabindex="-1" aria-labelledby="addTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="addTopicModalLabel">Yeni Mövzu Əlavə Et</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addTopicForm">
                    <div class="form-group">
                        <label class="form-label" for="topicName">Mövzu Adı</label>
                        <input type="text" class="form-control" id="topicName" name="movzu_adi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="topicSubject">Fənn</label>
                        <select class="form-select" id="topicSubject" name="fenn" required>
                            <option value="">Fənni seçin</option>
                            <?php
                            include('db.php');
                            $sql = "SELECT id, ixtisas_adi FROM ixtisas";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["ixtisas_adi"]) . "</option>";
                                }
                            }
                            $conn->close();
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="topicDescription">Təsvir</label>
                        <textarea class="form-control" id="topicDescription" name="tesvir" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="addTopic()">Yadda Saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Topic Modal -->
<div class="modal fade" id="editTopicModal" tabindex="-1" aria-labelledby="editTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="editTopicModalLabel">Mövzunu Redaktə Et</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTopicForm">
                    <input type="hidden" id="editTopicId" name="id">
                    <div class="form-group">
                        <label class="form-label" for="editTopicName">Mövzu Adı</label>
                        <input type="text" class="form-control" id="editTopicName" name="movzu_adi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editTopicSubject">Fənn</label>
                        <select class="form-select" id="editTopicSubject" name="fenn" required>
                            <option value="">Fənni seçin</option>
                            <?php
                            include('db.php');
                            $sql = "SELECT id, ixtisas_adi FROM ixtisas";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["ixtisas_adi"]) . "</option>";
                                }
                            }
                            $conn->close();
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editTopicDescription">Təsvir</label>
                        <textarea class="form-control" id="editTopicDescription" name="tesvir" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="updateTopic()">Yadda Saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Topic Modal -->
<div class="modal fade" id="topicViewModal" tabindex="-1" aria-labelledby="topicViewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="topicViewModalLabel">Mövzu Detalları</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div style="line-height: 1.8;">
                    <p style="margin: 8px 0;"><strong>Mövzu Adı:</strong> <span id="modalMovzuAdi" style="color: #333;"></span></p>
                    <p style="margin: 8px 0;"><strong>Fənn:</strong> <span id="modalFenn" style="color: #333;"></span></p>
                    <p style="margin: 8px 0;"><strong>Təsvir:</strong> <span id="modalTesvir" style="color: #555;"></span></p>
                    <p style="margin: 8px 0;"><strong>Yaradılış Tarixi:</strong> <span id="modalCreatedAt" style="color: #555;"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Topic Modal -->
<div class="modal fade" id="deleteTopicModal" tabindex="-1" aria-labelledby="deleteTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="deleteTopicModalLabel">Mövzunu Sil</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bu mövzunu silmək istədiyinizə əminsiniz?</p>
                <input type="hidden" id="deleteTopicId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina Et</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteTopic()">Sil</button>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(modalId, data = {}) {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    // Store data in modal for delete confirmation
    if (modalId === 'deleteTopicModal' && data.id) {
        document.getElementById('deleteTopicId').value = data.id;
    }
    modal.show();
}

function addTopic() {
    const movzu_adi = document.getElementById('topicName').value;
    const fenn_id = document.getElementById('topicSubject').value;
    const tesvir = document.getElementById('topicDescription').value;

    if (!movzu_adi || !fenn_id) {
        alert('Mövzu adı və fənn tələb olunur');
        return;
    }

    const formData = new FormData();
    formData.append('case', 'insert');
    formData.append('movzu_adi', movzu_adi);
    formData.append('fenn', fenn_id);
    formData.append('tesvir', tesvir);

    fetch('movzular/movzular/insert_topic.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Xəta: Mövzu əlavə olunmadı');
    });
}

function viewTopic(id) {
    const formData = new FormData();
    formData.append('case', 'view');
    formData.append('id', id);

    fetch('movzular/movzular/insert_topic.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('modalMovzuAdi').innerText = data.data.movzu_adi;
            document.getElementById('modalFenn').innerText = data.data.fenn;
            document.getElementById('modalTesvir').innerText = data.data.tesvir;
            document.getElementById('modalCreatedAt').innerText = data.data.created_at;
            const topicViewModal = new bootstrap.Modal(document.getElementById('topicViewModal'));
            topicViewModal.show();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Xəta: Mövzu göstərilə bilmədi');
    });
}

function editTopic(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    document.getElementById('editTopicId').value = id;
    document.getElementById('editTopicName').value = row.getAttribute('data-movzu');
    const tesvir = row.getAttribute('data-tesvir');
    document.getElementById('editTopicDescription').value = tesvir === 'Təsvir yoxdur' ? '' : decodeHTMLEntities(tesvir);
    const fenn = row.getAttribute('data-fenn');
    const fennSelect = document.getElementById('editTopicSubject');
    for (let option of fennSelect.options) {
        option.selected = option.text === fenn;
    }
    openModal('editTopicModal');
}

function decodeHTMLEntities(text) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
}

function updateTopic() {
    const id = document.getElementById('editTopicId').value;
    const movzu_adi = document.getElementById('editTopicName').value;
    const fenn_id = document.getElementById('editTopicSubject').value;
    const tesvir = document.getElementById('editTopicDescription').value;

    if (!id || !movzu_adi || !fenn_id) {
        alert('Mövzu ID, adı və fənn tələb olunur');
        return;
    }

    const formData = new FormData();
    formData.append('case', 'edit');
    formData.append('id', id);
    formData.append('movzu_adi', movzu_adi);
    formData.append('fenn', fenn_id);
    formData.append('tesvir', tesvir);

    fetch('movzular/movzular/insert_topic.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Xəta: Mövzu yenilənmədi');
    });
}

function deleteTopic(id) {
    document.getElementById('deleteTopicId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteTopicModal'));
    modal.show();
}

function confirmDeleteTopic() {
    const id = document.getElementById('deleteTopicId').value;
    if (!id) {
        alert("ID tapılmadı");
        return;
    }
    if (!confirm("Bu mövzunu həqiqətən silmək istəyirsiniz?")) return;
    const formData = new FormData();
    formData.append('case', 'delete');
    formData.append('id', id);
    fetch('movzular/movzular/insert_topic.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message); 
        if (data.status === 'success') {
            const row = document.querySelector(`[data-topic-id="${id}"]`);
            if (row) {
                row.remove();
            }
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteTopicModal'));
            if (modal) modal.hide();
            setTimeout(() => {
                window.location.href = 'Mövzular.php';
            }, 800); 
        } else {
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        alert("Server ilə əlaqə qurula bilmədi");
    });
}

function filterTopics() {
    const filter = document.getElementById('topicSubjectFilter').value.toLowerCase();
    const search = document.getElementById('topicSearchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#topics table tbody tr');

    rows.forEach(row => {
        const movzu = row.getAttribute('data-movzu').toLowerCase();
        const fenn = row.getAttribute('data-fenn').toLowerCase();
        const matchesFilter = filter === '' || fenn.includes(filter);
        const matchesSearch = movzu.includes(search) || fenn.includes(search);
        row.style.display = matchesFilter && matchesSearch ? '' : 'none';
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>