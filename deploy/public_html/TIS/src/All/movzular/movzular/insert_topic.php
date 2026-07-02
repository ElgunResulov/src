<?php
session_start();
header('Content-Type: application/json');
include('../../db.php');

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Bağlantı xətası: ' . $conn->connect_error]);
    exit;
}

$case = isset($_POST['case']) ? $_POST['case'] : '';

if (!isset($_SESSION['u_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'İstifadəçi daxil olmayıb']);
    exit;
}

$u_id = trim($conn->real_escape_string($_SESSION['u_id']));

switch ($case) {
    case 'insert':
        $movzu_adi = isset($_POST['movzu_adi']) ? trim($conn->real_escape_string($_POST['movzu_adi'])) : '';
        $fenn_id_from_form = isset($_POST['fenn']) ? (int)$_POST['fenn'] : 0;
        $tesvir = isset($_POST['tesvir']) ? trim($conn->real_escape_string($_POST['tesvir'])) : '';

        if (empty($movzu_adi) || $fenn_id_from_form <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Mövzu adı və fənn tələb olunur']);
            exit;
        }

        // Fetch fenn_adi and fenn_id from ixtisas
        $sql = "SELECT ixtisas_adi, id FROM ixtisas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fenn_id_from_form);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $fenn_adi = $row['ixtisas_adi'];
            $fenn_id = $row['id'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Seçilmiş fənn tapılmadı']);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();

        // Insert into movzular_new with u_id
        $sql = "INSERT INTO movzular_new (movzu_adi, fenn, fenn_id, tesvir, u_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiss", $movzu_adi, $fenn_adi, $fenn_id, $tesvir, $u_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Mövzu uğurla əlavə olundu']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Xəta: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'edit':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $movzu_adi = isset($_POST['movzu_adi']) ? trim($conn->real_escape_string($_POST['movzu_adi'])) : '';
        $fenn_id_from_form = isset($_POST['fenn']) ? (int)$_POST['fenn'] : 0;
        $tesvir = isset($_POST['tesvir']) ? trim($conn->real_escape_string($_POST['tesvir'])) : '';

        if ($id <= 0 || empty($movzu_adi) || $fenn_id_from_form <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Mövzu ID, adı və fənn tələb olunur']);
            exit;
        }

        // Fetch fenn_adi and fenn_id from fennler_new
        $sql = "SELECT ixtisas_adi, id FROM ixtisas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fenn_id_from_form);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $fenn_adi = $row['ixtisas_adi'];
            $fenn_id = $row['id'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Seçilmiş fənn tapılmadı']);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();

        // Update movzular_new
        $sql = "UPDATE movzular_new SET movzu_adi = ?, fenn = ?, fenn_id = ?, tesvir = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisi", $movzu_adi, $fenn_adi, $fenn_id, $tesvir, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Mövzu uğurla yeniləndi']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Xəta: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'delete':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Mövzu ID tələb olunur']);
            exit;
        }

        $sql = "DELETE FROM movzular_new WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Mövzu uğurla silindi']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Xəta: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'view':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Mövzu ID tələb olunur']);
            exit;
        }

        $sql = "SELECT movzu_adi, fenn, tesvir, created_at FROM movzular_new WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $created_at = new DateTime($row['created_at']);
            $data = [
                'movzu_adi' => $row['movzu_adi'],
                'fenn' => $row['fenn'],
                'tesvir' => $row['tesvir'] ? $row['tesvir'] : 'Təsvir yoxdur',
                'created_at' => $created_at->format('Y-m-d H:i')
            ];
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mövzu tapılmadı']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Yanlış əməliyyat']);
        break;
}

$conn->close();
?>