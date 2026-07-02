<?php
include('../db.php');

// Sanitize input to prevent SQL injection and XSS
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data); // Escape for SQL
    return $data;
}

// Sanitize and process form inputs
$firstName = ucwords(sanitize_input($_POST['firstName']));
$lastName = ucwords(sanitize_input($_POST['lastName']));
$qebul_tarixi = sanitize_input($_POST['qebul_tarixi']);
$dogum_tarixi = sanitize_input($_POST['dogum_tarixi']);
$years = sanitize_input($_POST['yas']);
$gender = sanitize_input($_POST['gender']);
$class = sanitize_input($_POST['class']);
$status = sanitize_input($_POST['status']);
$email = sanitize_input($_POST['email']);
$phone = sanitize_input($_POST['phone']);
$address = sanitize_input($_POST['address']);
$ata = sanitize_input($_POST['ata']);
$elaqe_nomre_ata = sanitize_input($_POST['elaqe_nomre_ata']);
$ana = sanitize_input($_POST['ana']);
$elaqe_nomre_ana = sanitize_input($_POST['elaqe_nomre_ana']);
$username = $firstName . " " . $lastName;

// Map gender to 0 (Kişi) or 1 (Qadın)
$cins = null;
if ($gender === 'male') {
    $cins = 0;
} elseif ($gender === 'female') {
    $cins = 1;
} else {
    echo "Xəta: Keçərsiz cins dəyəri.";
    exit;
}

// Calculate age if birth date is provided and age is empty
if (empty($years) && !empty($dogum_tarixi)) {
    $birthDateObj = new DateTime($dogum_tarixi);
    $currentDateObj = new DateTime();
    $ageDiff = $birthDateObj->diff($currentDateObj);
    $years = $ageDiff->y; // Age in years
}

// Handle file upload
$photoPath = '';
if ($_FILES['photo']['error'] == 0) {
    $target_dir = "Uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($file_extension, $allowed_types)) {
        echo "Xəta: Yalnız JPG, JPEG, PNG və ya GIF faylları icazə verilir.";
        exit;
    }

    // Validate file size (e.g., max 5MB)
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        echo "Xəta: Fayl ölçüsü 5MB-dan böyük ola bilməz.";
        exit;
    }

    $new_file_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        $photoPath = $target_file;
    } else {
        echo "Xəta: Fayl yüklənmədi.";
        exit;
    }
}

// Prepare SQL query
$sql = "INSERT INTO telebeler (
    username, number, poct, active_status, dogum_tarixi, years, cins, unvan, sinif, qebul_tarixi, 
    ata, elaqe_nomre_ata, ana, elaqe_nomre_ana, photo, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param(
        "sssssisssssssss", // Changed 's' to 'i' for cins (7th parameter)
        $username,
        $phone,
        $email,
        $status,
        $dogum_tarixi,
        $years,
        $cins, // Use $cins (0 or 1) instead of $gender
        $address,
        $class,
        $qebul_tarixi,
        $ata,
        $elaqe_nomre_ata,
        $ana,
        $elaqe_nomre_ana,
        $photoPath
    );

    if ($stmt->execute()) {
        echo "<script>window.location.href = '../Tələbələr.php';</script>";
    } else {
        echo "Xəta: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Xəta: SQL sorğusu hazırlana bilmədi: " . $conn->error;
}

$conn->close();
?>