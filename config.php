<?php
// Parameter koneksi membaca dari Environment Variables Coolify
$host = getenv('DB_HOST');
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');
$db   = getenv('DB_DATABASE');

// Panggilan koneksi pertama
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
<?php 
// 2. Konfigurasi Database (Panggilan kedua sesuai format Anda)
$conn = new mysqli($host, $user, $pass, $db, $port); 
?>
