<?php
$conn = new mysqli("localhost", "root", "Saimen123456", "db_tvsaimen");
if ($conn->connect_error) die("Koneksi gagal");
?>
<?php // 2. Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "Saimen123456";
$db   = "db_tvsaimen"; 

$conn = new mysqli($host, $user, $pass, $db); ?>
