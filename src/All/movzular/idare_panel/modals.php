<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../../db.php');

$uId = trim((string) ($_SESSION['u_id'] ?? ''));
$username = trim((string) ($_SESSION['username'] ?? ''));
$role = $_SESSION['role'] ?? '';
$isAdmin = in_array($role, ['super_admin', 'admin'], true);

$stats = [
    'students' => 0,
    'groups' => 0,
    'topics' => 0,
    'exams' => 0,
];

$upcomingExams = [];
$recentAssignments = [];

$examStatusLabels = [
    'upcoming' => 'Gələcək',
    'active' => 'Aktiv',
    'completed' => 'Tamamlanmış',
];

if ($uId !== '') {
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM qruplar WHERE u_id = ?');
    if ($stmt) {
        $stmt->bind_param('s', $uId);
        $stmt->execute();
        $stats['groups'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM movzular_new WHERE u_id = ?');
    if ($stmt) {
        $stmt->bind_param('s', $uId);
        $stmt->execute();
        $stats['topics'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM imtahanlar_exam WHERE u_id = ?');
    if ($stmt) {
        $stmt->bind_param('s', $uId);
        $stmt->execute();
        $stats['exams'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }

    if ($isAdmin) {
        $studentResult = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM telebeler');
        if ($studentResult) {
            $stats['students'] = (int) mysqli_fetch_assoc($studentResult)['total'];
        }
    } elseif ($username !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM telebeler WHERE JSON_CONTAINS(muellim_adi, JSON_QUOTE(?), '$')");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stats['students'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $stmt->close();
        }
    }

    $stmt = $conn->prepare(
        "SELECT exam_name, fenn_adi, exam_date, duration, `groups`, status
         FROM imtahanlar_exam
         WHERE u_id = ?
         ORDER BY exam_date ASC, id DESC
         LIMIT 5"
    );
    if ($stmt) {
        $stmt->bind_param('s', $uId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $status = strtolower((string) ($row['status'] ?? ''));
            $row['status_label'] = $examStatusLabels[$status] ?? ($row['status'] ?? '-');
            $upcomingExams[] = $row;
        }
        $stmt->close();
    }

    $stmt = $conn->prepare(
        "SELECT t.ad, m.movzu_adi, q.qrup_adi, t.son_tarix
         FROM tapsiriqlar t
         LEFT JOIN movzular_new m ON t.movzu = m.id
         LEFT JOIN qruplar q ON t.qrup = q.id
         WHERE t.u_id = ?
         ORDER BY t.yaradilma_tarixi DESC
         LIMIT 5"
    );
    if ($stmt) {
        $stmt->bind_param('s', $uId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentAssignments[] = $row;
        }
        $stmt->close();
    }
}
?>

<div class="stats-grid mb-0">
    <div class="card">
        <div class="stat-card stat-card-clickable" data-stat-type="students" role="button" tabindex="0" aria-label="Tələbələri göstər">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h2 class="stat-value"><?= (int) $stats['students'] ?></h2>
                <div class="stat-label">Tələbə</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat-card stat-card-clickable" data-stat-type="groups" role="button" tabindex="0" aria-label="Qrupları göstər">
            <div class="stat-icon success">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-info">
                <h2 class="stat-value"><?= (int) $stats['groups'] ?></h2>
                <div class="stat-label">Qrup</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat-card stat-card-clickable" data-stat-type="topics" role="button" tabindex="0" aria-label="Mövzuları göstər">
            <div class="stat-icon info">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-info">
                <h2 class="stat-value"><?= (int) $stats['topics'] ?></h2>
                <div class="stat-label">Mövzu</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat-card stat-card-clickable" data-stat-type="exams" role="button" tabindex="0" aria-label="İmtahanları göstər">
            <div class="stat-icon warning">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-info">
                <h2 class="stat-value"><?= (int) $stats['exams'] ?></h2>
                <div class="stat-label">İmtahan</div>
            </div>
        </div>
    </div>
</div>

<div class="m-3 d-flexbox mb-0">
    <div class="row">
        <div class="card mb-20">
            <div class="card-header">
                <h3 class="card-title">Yaxın İmtahanlar</h3>
                <a href="#" class="btn btn-sm btn-primary" onclick="showSection('exams')">Bütün İmtahanlar</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>İmtahan</th>
                                <th>Fənn</th>
                                <th>Tarix</th>
                                <th>Müddət</th>
                                <th>Qruplar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($upcomingExams)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">İmtahan tapılmadı</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcomingExams as $exam): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($exam['exam_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($exam['fenn_adi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($exam['exam_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($exam['duration'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($exam['groups'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($exam['status_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-20">
            <div class="card-header">
                <h3 class="card-title">Son Tapşırıqlar</h3>
                <a href="#" class="btn btn-sm btn-primary" onclick="showSection('assignments')">Bütün Tapşırıqlar</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tapşırıq</th>
                                <th>Mövzu</th>
                                <th>Qrup</th>
                                <th>Son Tarix</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAssignments)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tapşırıq tapılmadı</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAssignments as $assignment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($assignment['ad'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($assignment['movzu_adi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($assignment['qrup_adi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($assignment['son_tarix'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
