<?php
ob_start();
session_start();
include '../config.php';

// Cek apakah ada ID yang dikirim melalui URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Ambil informasi file sebelum datanya dihapus dari DB
    $query = $conn->query("SELECT src FROM tbl_kontentv WHERE id = $id");
    
    if ($query->num_rows > 0) {
        $data = $query->fetch_assoc();
        $file_path = "../KONTEN/" . $data['src'];

        // 2. Hapus file fisik dari folder uploads jika filenya ada (dengan fallback ke root)
        if (file_exists($file_path)) {
            unlink($file_path);
        } elseif (file_exists("../" . $data['src'])) {
            unlink("../" . $data['src']);
        }

        // 3. Hapus data dari database
        $delete = $conn->query("DELETE FROM tbl_kontentv WHERE id = $id");

        if ($delete) {
            // Berhasil, balikkan ke halaman utama admin
            header("Location: index.php?pesan=hapus_berhasil");
        } else {
            echo "Gagal menghapus data dari database: " . $conn->error;
        }
    } else {
        echo "Data tidak ditemukan!";
    }
} else {
    // Jika mencoba akses langsung tanpa ID
    header("Location: index.php");
}
?>
