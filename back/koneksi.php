<?php
$host = "localhost";
$db_name = "db_tvsaimen";
$username = "root"; // Sesuaikan dengan user database Anda
$password = "";     // Sesuaikan dengan password database Anda

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    // Set agar PDO menampilkan error jika ada masalah
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>