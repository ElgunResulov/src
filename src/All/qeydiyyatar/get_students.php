<?php
include('../db.php');

$query = "SELECT id, telebe_ad_soyad FROM qeydiyyatar ORDER BY telebe_ad_soyad";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<div class='list-group'>";
    while ($row = mysqli_fetch_assoc($result)) {
        $id = htmlspecialchars($row['id']);
        $telebe_ad_soyad = htmlspecialchars($row['telebe_ad_soyad']);
        echo "<div class='list-group-item' style='width: 100%; display: block; padding: 12px; margin-bottom: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative;' data-id='$id'>";
        echo "<span>$telebe_ad_soyad</span>";
        echo "<i class='fas fa-trash delete-icon' style='position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #dc3545; cursor: pointer; font-size: 16px;' title='Silmək'></i>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p class='text-muted'>Qeydiyyatlı tələbə tapılmadı</p>";
}

mysqli_close($conn);
?>