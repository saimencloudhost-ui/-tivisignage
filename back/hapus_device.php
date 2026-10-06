<?php
ob_start();
session_start();

// Pastikan user sudah login sebelum bisa menghapus
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include '../config.php';

// 1. Tangkap ID dari URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 2. Gunakan Prepared Statement untuk keamanan (mencegah SQL Injection)
    $stmt = $conn->prepare("DELETE FROM tbl_devicetv WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // 3. Jika berhasil, kembali ke halaman data_device dengan pesan sukses
        header("Location: data_device.php?msg=deleted");
    } else {
        // Jika gagal
        echo "Gagal menghapus data: " . $conn->error;
    }

    $stmt->close();
} else {
    // Jika tidak ada ID yang dikirim, lempar balik ke halaman utama
    header("Location: data_device.php");
}

$conn->close();
?>
