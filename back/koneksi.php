<?php
// Membaca pengaturan dari Environment Variables Coolify dengan nilai default
$host     = getenv('DB_HOST') ?: 'xrokuqrydyahcy1lrcc3otdl';
$port     = getenv('DB_PORT') ?: '3306';
$db_name  = getenv('DB_DATABASE') ?: 'db_saimentv';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'Saimen123456';

try {
    // Tambahkan parameter port dan charset ke koneksi DSN PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $db = new PDO($dsn, $username, $password);
    
    // Set agar PDO menampilkan error jika ada masalah
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>

