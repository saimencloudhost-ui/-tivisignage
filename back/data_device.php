<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// --- LOGIKA SEARCH ---
$search = isset($_GET['q']) ? $_GET['q'] : '';
$query_where = "";
$params = [];

if ($search != '') {
    $query_where = " WHERE namatv LIKE :search OR cabang LIKE :search ";
    $params[':search'] = "%$search%";
}

// --- LOGIKA PAGING ---
$limit = 10; 
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif <= 0) $halaman_aktif = 1;
$offset = ($halaman_aktif - 1) * $limit;

try {
    // 1. Hitung total data (dengan filter search jika ada)
    $stmt_total = $db->prepare("SELECT COUNT(*) FROM tbl_devicetv $query_where");
    $stmt_total->execute($params);
    $total_data = $stmt_total->fetchColumn();
    $total_halaman = ceil($total_data / $limit);

    // 2. Ambil data (dengan filter search + paging)
    $query_sql = "SELECT * FROM tbl_devicetv $query_where ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query_sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syuharto TV | Device Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        :root { --primary-color: #4361ee; --danger-color: #ef233c; --bg-color: #f8f9fc; }
        body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: #2b2d42; }
        .main-container { padding-top: 40px; padding-bottom: 40px; }
        .custom-card { background: white; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04); overflow: hidden; }
        
        /* Search Bar Styling */
        .search-box { border-radius: 12px; border: 1px solid #e0e0e0; padding: 10px 15px; transition: 0.3s; }
        .search-box:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.1); }
        
        .table thead th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; padding: 20px; color: #8d99ae; border: none; background: #f1f3f9; }
        .table td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid #f1f3f9; }
        
        .badge-branch { background-color: rgba(67, 97, 238, 0.1); color: var(--primary-color); font-weight: 600; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; }
        .pagination .page-link { border: none; margin: 0 3px; border-radius: 8px; font-weight: 600; }
        .pagination .page-item.active .page-link { background-color: var(--primary-color); box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3); }
    </style>
</head>
<body>

<div class="container main-container">
    <div class="row align-items-center mb-4 animate__animated animate__fadeInDown">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-1">🖥️ Device Manager</h2>
            <p class="text-muted mb-0">Kelola unit TV Signage di seluruh cabang Toko</p>
        </div>
        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
            <a href="index.php" class="btn btn-light border px-4 py-2 me-2" style="border-radius: 12px;">Kembali</a>
            <a href="tambah_device.php" class="btn btn-primary px-4 py-2 shadow-sm" style="border-radius: 12px;">+ Tambah</a>
        </div>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Perangkat telah dihapus dari sistem.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'cache_cleared'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Perintah Hapus Cache telah dikirim ke TV. Layar TV akan otomatis memuat ulang sesaat lagi.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm mb-4 p-3 animate__animated animate__fadeInUp" style="border-radius: 15px;">
        <form method="GET" action="">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="q" class="form-control border-start-0 search-box" 
                       placeholder="Cari nama TV atau lokasi cabang..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary ms-2 px-4" style="border-radius: 12px;">Cari</button>
                <?php if($search != ''): ?>
                    <a href="data_device.php" class="btn btn-outline-secondary ms-1" style="border-radius: 12px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card custom-card animate__animated animate__fadeInUp">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Nama Perangkat</th>
                        <th>Cabang</th>
                        <th>Fitur Slide</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($devices) > 0): ?>
                        <?php foreach ($devices as $row): ?>
                        <tr>
                            <td class="ps-4 text-muted fw-bold">#<?php echo $row['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light text-primary me-3 d-none d-md-flex" style="width: 35px; height: 35px; border-radius: 10px; align-items: center; justify-content: center;">
                                        <i class="fas fa-desktop"></i>
                                    </div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($row['namatv']); ?></span>
                                </div>
                            </td>
                            <td><span class="badge-branch"><?php echo strtoupper($row['cabang']); ?></span></td>
                            <td>
                                <?php if (isset($row['slide']) && (int)$row['slide'] === 0): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i> Tidak Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fas fa-play-circle me-1"></i> Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="edit_device.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary border-0 me-1" 
                                   title="Edit Perangkat">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="aksi_cache.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-warning border-0 me-1" 
                                   title="Hapus Cache Browser TV" onclick="return confirm('Kirim perintah hapus cache ke TV ini? Layar TV akan dimuat ulang dari awal.')">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                                <a href="hapus_device.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-danger border-0" 
                                   onclick="return confirm('Hapus perangkat?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-2x mb-2 opacity-25"></i><br>
                                Data tidak ditemukan untuk "<strong><?php echo htmlspecialchars($search); ?></strong>"
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_halaman > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($halaman_aktif <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link shadow-sm" href="?halaman=<?php echo $halaman_aktif - 1; ?>&q=<?php echo $search; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                <li class="page-item <?php echo ($i == $halaman_aktif) ? 'active' : ''; ?>">
                    <a class="page-link shadow-sm" href="?halaman=<?php echo $i; ?>&q=<?php echo $search; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($halaman_aktif >= $total_halaman) ? 'disabled' : ''; ?>">
                <a class="page-link shadow-sm" href="?halaman=<?php echo $halaman_aktif + 1; ?>&q=<?php echo $search; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <div class="text-center mt-4 text-muted small">
        Syuharto IT Infrastructure &bull; 2026
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>