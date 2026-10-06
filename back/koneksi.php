<?php
// Membaca pengaturan murni dari Environment Variables Coolify
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: '3306';
$db_name  = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $db = new PDO($dsn, $username, $password);
    
    // Set agar PDO melemparkan exception jika ada kesalahan query
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Sembunyikan detail teknis database dari publik demi keamanan
    die("Koneksi Database Gagal: Terjadi kesalahan pada sistem.");
}
?> 
