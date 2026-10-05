<?php
header('Content-Type: application/json');
include 'config.php';

$user   = $_POST['user'];
$cabang = $_POST['cabang'];

// Cari apakah ada TV dengan nama dan cabang tersebut
$sql = "SELECT * FROM tbl_devicetv WHERE namatv = '$user' AND cabang = '$cabang' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(["status" => "success", "cabang" => $cabang, "slide" => isset($row['slide']) ? $row['slide'] : 1]);
} else {
    echo json_encode(["status" => "error", "message" => "Perangkat tidak terdaftar!"]);
}
$conn->close();
?>