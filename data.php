<?php
// 1. Header Keamanan & Tipe Data
header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

include 'config.php';

// 3. Cek Koneksi
if ($conn->connect_error) {
    // Jika gagal, kirim pesan error dalam format JSON
    die(json_encode(["error" => "Koneksi ke database gagal"]));
}

// 4. Query Data (Mengambil semua kolom yang dibutuhkan)
// Pastikan nama kolom 'cabang' ada di tabel tbl_kontentv
$sql = "SELECT id, nama, src, ket, cabang, tanggal_mulai, tanggal_selesai FROM tbl_kontentv WHERE nama = 'aktif'";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// 5. Tampilkan sebagai JSON
echo json_encode($data);

// 6. Tutup Koneksi (Sangat penting agar slot koneksi tidak penuh)
$conn->close();
?>