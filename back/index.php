<?php
ob_start();
session_start();
include '../config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$user_aktif = $_SESSION['username'];
$nama_admin = $_SESSION['nama'];
// 1. Pengaturan Pagination
$limit = 20; // Jumlah data per halaman
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif <= 0) $halaman_aktif = 1;
$offset = ($halaman_aktif - 1) * $limit;

// 2. Logika Filter Cabang
$filter_cabang = isset($_GET['f_cabang']) ? $_GET['f_cabang'] : '';
$query_where = "";
if ($filter_cabang != '' && $filter_cabang != 'all_data') {
    $query_where = " WHERE cabang LIKE '%$filter_cabang%' ";
}

// 3. Hitung Total Data (untuk tahu ada berapa halaman)
$total_data_query = $conn->query("SELECT COUNT(*) AS total FROM tbl_kontentv $query_where");
$total_data = $total_data_query->fetch_assoc()['total'];
$total_halaman = ceil($total_data / $limit);

// 4. Ambil Data dengan LIMIT & OFFSET
$sql = "SELECT * FROM tbl_kontentv $query_where ORDER BY id ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

 // Query untuk mengambil nama cabang yang unik
$query_cb = "SELECT DISTINCT cabang FROM tbl_devicetv ORDER BY cabang ASC";
$result_cb = mysqli_query($conn, $query_cb);

// Inisialisasi array dengan pilihan 'all' di urutan pertama
$daftar_cabang = ['all'];

if ($result_cb) {
    // Ambil data hasil query dan masukkan ke dalam array
    while ($row = mysqli_fetch_assoc($result_cb)) {
        // Hindari memasukkan 'all' dua kali jika di database sudah ada kata 'all'
        if (strtolower($row['cabang']) !== 'all') {
            $daftar_cabang[] = $row['cabang'];
        }
    }
} else {
    // Jika query gagal, gunakan data cadangan agar sistem tidak error
    $daftar_cabang = ['all', 'sipin', 'rimbo', 'pasar']; 
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Saimen TV</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .btn-logout {
        border-radius: 12px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 1px solid #ef233c; /* Warna merah soft */
        color: #ef233c;
        background: transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        
    }

    .btn-logout:hover {
        background-color: #ef233c;
        color: white;
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(239, 35, 60, 0.3);
    }
    .btn-custom {
        border-radius: 12px; /* Membuat sudut lebih membulat */
        padding: 10px 24px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px; /* Jarak antara icon dan teks */
    }

    .btn-custom:hover {
        transform: translateY(-3px); /* Tombol naik sedikit */
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); /* Bayangan saat hover */
    }

    .btn-icon {
        font-size: 1.1rem;
    }
</style>
</head>
<body class="bg-light">
    
    <div class="container mt-5">
        
        <div class="d-flex justify-content-between mb-3">
            <h3>Manajemen Konten TV</h3>
        </div>

        <?php if (isset($_SESSION['upload_feedback'])): 
            $fb = $_SESSION['upload_feedback'];
            unset($_SESSION['upload_feedback']);
        ?>
            <div class="alert alert-info alert-dismissible fade show shadow-sm border-0" role="alert" style="border-radius: 12px;">
                <h5 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i>Hasil Upload Konten</h5>
                <p class="mb-0">Berhasil mengunggah: <strong><?= $fb['success_count'] ?></strong> file.</p>
                <?php if (!empty($fb['rejected_files'])): ?>
                    <hr>
                    <p class="mb-1 text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Beberapa file berikut GAGAL diunggah karena melebihi batas ukuran 22MB:</p>
                    <ul class="mb-0 text-danger small">
                        <?php foreach ($fb['rejected_files'] as $file): ?>
                            <li><?= htmlspecialchars($file) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

     <div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="fw-bold">Filter Cabang:</label>
            </div>
            <div class="col-auto">
                <select name="f_cabang" class="form-select" onchange="this.form.submit()">
                    <option value="all_data">-- Tampilkan Semua --</option>
                    <?php foreach($daftar_cabang as $cb): ?>
                        <option value="<?php echo $cb; ?>" <?php echo ($filter_cabang == $cb) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($cb); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-secondary btn-custom shadow-sm">Reset</a>
                
                <a href="tambah.php" class="btn btn-primary btn-custom shadow-sm">
                    <i class="fas fa-cloud-upload-alt btn-icon"></i>
                    <span>Tambah Konten</span>
                </a>

                <a href="tambah_device.php" class="btn btn-success btn-custom shadow-sm">
                    <i class="fas fa-tv btn-icon"></i>
                    <span>Tambah Device</span>
                </a>
                
                <a href="data_device.php" class="btn btn-info text-white btn-custom shadow-sm">
                    <i class="fas fa-list btn-icon"></i>
                    <span>Data Device</span>
                </a>

                <div class="ms-auto">
                    <a href="logout.php" class="btn-logout text-decoration-none" onclick="return confirm('Yakin ingin keluar?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
       <table class="table table-bordered bg-white shadow-sm align-middle">
    <thead class="table-dark">
        <tr>
            <th>Preview</th>
            <th>Path/Nama File</th> <th>Status</th>
            <th>Cabang</th>
            <th>Tipe</th>
            <th>Jadwal Tayang</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td class="text-center">
                <?php if($row['ket'] == 'video'): ?>
                    <div class="d-inline-flex align-items-center justify-content-center bg-light border rounded" style="width: 100px; height: 60px;">
                        <i class="fas fa-file-video fa-2x text-secondary"></i>
                    </div>
                <?php else: ?>
                    <?php
                    $preview_src = "../KONTEN/" . $row['src'];
                    if (!file_exists($preview_src) && file_exists("../" . $row['src'])) {
                        $preview_src = "../" . $row['src'];
                    }
                    ?>
                    <img src="<?php echo $preview_src; ?>" width="100" loading="lazy">
                <?php endif; ?>
            </td>
            <td>
                <small class="text-muted"><?php echo $row['src']; ?></small>
            </td>
            <td>
                <span class="badge <?php echo ($row['nama'] == 'aktif') ? 'bg-success' : 'bg-secondary'; ?>">
                    <?php echo $row['nama']; ?>
                </span>
            </td>
            <td><?php echo $row['cabang']; ?></td>
            <td><?php echo $row['ket']; ?></td>
            <td>
                <?php 
                $start = $row['tanggal_mulai'] ?? null;
                $end = $row['tanggal_selesai'] ?? null;
                if ((empty($start) || $start == '0000-00-00 00:00:00') && (empty($end) || $end == '0000-00-00 00:00:00')) {
                    echo '<span class="badge bg-info text-dark">Selalu Tayang</span>';
                } else {
                    echo '<div class="small">';
                    if (!empty($start) && $start != '0000-00-00 00:00:00') {
                        echo '<strong>Mulai:</strong><br><span class="text-muted">' . date('d-m-Y H:i', strtotime($start)) . '</span><br>';
                    } else {
                        echo '<strong>Mulai:</strong> <span class="text-muted">Segera</span><br>';
                    }
                    if (!empty($end) && $end != '0000-00-00 00:00:00') {
                        echo '<strong>Selesai:</strong><br><span class="text-muted">' . date('d-m-Y H:i', strtotime($end)) . '</span>';
                    } else {
                        echo '<strong>Selesai:</strong> <span class="text-muted">Selamanya</span>';
                    }
                    echo '</div>';
                }
                ?>
            </td>
            <td>
    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
    <a href="hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus konten ini?')">Hapus</a>
</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($halaman_aktif <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?halaman=<?php echo $halaman_aktif - 1; ?>&f_cabang=<?php echo $filter_cabang; ?>">Previous</a>
        </li>

        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
            <li class="page-item <?php echo ($i == $halaman_aktif) ? 'active' : ''; ?>">
                <a class="page-link" href="?halaman=<?php echo $i; ?>&f_cabang=<?php echo $filter_cabang; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?php echo ($halaman_aktif >= $total_halaman) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?halaman=<?php echo $halaman_aktif + 1; ?>&f_cabang=<?php echo $filter_cabang; ?>">Next</a>
        </li>
    </ul>
    <p class="text-center text-muted small">Menampilkan <?php echo $result->num_rows; ?> dari total <?php echo $total_data; ?> konten</p>
</nav>
    </div>
</body>
</html>
