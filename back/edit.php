<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(["status" => "error", "message" => "Sesi habis, silakan login ulang."]);
    exit;
}
include 'koneksi.php';

$id = $_GET['id'];

// 1. Ambil data konten saat ini
// Menggunakan PDO prepare untuk keamanan
$stmt = $db->prepare("SELECT * FROM tbl_kontentv WHERE id = :id");
$stmt->execute([':id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data tidak ditemukan!");
}

// 2. Ambil daftar cabang unik dari tbl_devicetv untuk pilihan
try {
    $stmt_cb = $db->query("SELECT DISTINCT cabang FROM tbl_devicetv ORDER BY cabang ASC");
    $daftar_cabang_db = $stmt_cb->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $daftar_cabang_db = [];
}

// 3. Proses Update
if(isset($_POST['update'])) {
    $nama = $_POST['status'];
    // Mengubah array cabang menjadi string dipisahkan koma
    $cabang = isset($_POST['cabang_array']) ? implode(',', $_POST['cabang_array']) : '';
    $tipe = $_POST['tipe'];
    $db_path = $data['src']; 
    
    // Ambil data penjadwalan
    $tanggal_mulai = !empty($_POST['tanggal_mulai']) ? str_replace('T', ' ', $_POST['tanggal_mulai']) : null;
    $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? str_replace('T', ' ', $_POST['tanggal_selesai']) : null;

    if($_FILES["file_konten"]["name"] != "") {
        // Validasi Ukuran File (Maksimal 22 MB)
        if ($_FILES["file_konten"]["size"] > 22 * 1024 * 1024) {
            echo "<script>alert('File baru yang Anda upload Melebihi Batas Maksimal Ukuran 22 MB'); window.location.href='edit.php?id=" . $id . "';</script>";
            exit;
        }

        $target_dir = "../KONTEN/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . "_" . str_replace(' ', '_', basename($_FILES["file_konten"]["name"]));
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["file_konten"]["tmp_name"], $target_file)) {
            if(!empty($data['src'])) {
                if (file_exists("../KONTEN/".$data['src'])) {
                    unlink("../KONTEN/".$data['src']);
                } elseif (file_exists("../".$data['src'])) {
                    unlink("../".$data['src']);
                }
            }
            $db_path = $file_name; // Path relatif
        }
    }

    try {
        $sql = "UPDATE tbl_kontentv SET 
                nama = :nama, 
                src = :src, 
                ket = :ket, 
                cabang = :cabang,
                tanggal_mulai = :tanggal_mulai,
                tanggal_selesai = :tanggal_selesai
                WHERE id = :id";
        $stmt_upd = $db->prepare($sql);
        $stmt_upd->execute([
            ':nama'            => $nama,
            ':src'             => $db_path,
            ':ket'             => $tipe,
            ':cabang'          => $cabang,
            ':tanggal_mulai'   => $tanggal_mulai,
            ':tanggal_selesai' => $tanggal_selesai,
            ':id'              => $id
        ]);
        header("Location: index.php");
        exit;
    } catch(PDOException $e) {
        echo "Gagal update: " . $e->getMessage();
    }
}

// Pecah string cabang dari database menjadi array untuk ditandai 'selected'
$cabang_terpilih = explode(',', $data['cabang']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Konten #<?php echo $id; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="container mt-5" style="max-width: 700px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4 fw-bold text-primary">Edit Konten #<?php echo $id; ?></h4>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">File Saat Ini:</label><br>
                        <div class="p-2 border rounded bg-light">
                            <?php
                            $preview_src = "../KONTEN/" . $data['src'];
                            if (!file_exists($preview_src) && file_exists("../" . $data['src'])) {
                                $preview_src = "../" . $data['src'];
                            }
                            ?>
                            <?php if($data['ket'] == 'video'): ?>
                                <video src="<?php echo $preview_src; ?>" width="150" class="rounded me-2" muted></video>
                            <?php else: ?>
                                <img src="<?php echo $preview_src; ?>" width="150" class="rounded me-2">
                            <?php endif; ?>
                            <small class="text-muted"><?php echo $data['src']; ?></small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Ganti File (Kosongkan jika tidak ingin ganti)</label>
                        <input type="file" name="file_konten" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Konten</label>
                        <select name="tipe" class="form-select">
                            <option value="video" <?php if($data['ket'] == 'video') echo 'selected'; ?>>Video</option>
                            <option value="image" <?php if($data['ket'] == 'image') echo 'selected'; ?>>Gambar (Image)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Target Cabang (Pilih dari Daftar Resmi)</label>
                        <select name="cabang_array[]" id="select-cabang" class="form-select" multiple="multiple" data-placeholder="Pilih cabang..." required>
                            <option value="all" <?php if(in_array('all', $cabang_terpilih)) echo 'selected'; ?>>SEMUA CABANG (ALL)</option>
                            <?php foreach($daftar_cabang_db as $cb): ?>
                                <?php if($cb['cabang'] != 'all'): ?>
                                    <option value="<?php echo htmlspecialchars($cb['cabang']); ?>" 
                                        <?php if(in_array($cb['cabang'], $cabang_terpilih)) echo 'selected'; ?>>
                                        <?php echo strtoupper(htmlspecialchars($cb['cabang'])); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text mt-2 text-muted small">*Data cabang diambil otomatis dari tabel Perangkat TV.</div>
                    </div>

                    <?php
                    $val_mulai = "";
                    if (!empty($data['tanggal_mulai']) && $data['tanggal_mulai'] != '0000-00-00 00:00:00') {
                        $val_mulai = date('Y-m-d\TH:i', strtotime($data['tanggal_mulai']));
                    }
                    $val_selesai = "";
                    if (!empty($data['tanggal_selesai']) && $data['tanggal_selesai'] != '0000-00-00 00:00:00') {
                        $val_selesai = date('Y-m-d\TH:i', strtotime($data['tanggal_selesai']));
                    }
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jadwal Mulai Tayang (WIB/WITA/WIT)</label>
                            <input type="datetime-local" name="tanggal_mulai" class="form-control" value="<?php echo $val_mulai; ?>">
                            <div class="form-text text-muted small">Kosongkan jika ingin segera ditayangkan.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jadwal Selesai Tayang (WIB/WITA/WIT)</label>
                            <input type="datetime-local" name="tanggal_selesai" class="form-control" value="<?php echo $val_selesai; ?>">
                            <div class="form-text text-muted small">Kosongkan jika ingin ditayangkan selamanya.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold d-block">Status</label>
                        <div class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="radio" name="status" id="status_aktif" value="aktif" <?php if($data['nama'] == 'aktif') echo 'checked'; ?>>
                            <label class="form-check-label fw-semibold" for="status_aktif">Aktif</label>
                        </div>
                        <div class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="radio" name="status" id="status_tidak_aktif" value="tidak aktif" <?php if($data['nama'] == 'tidak aktif') echo 'checked'; ?>>
                            <label class="form-check-label fw-semibold" for="status_tidak_aktif">Tidak Aktif</label>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" name="update" class="btn btn-primary w-100 fw-bold py-2">Update Konten</button>
                        <a href="index.php" class="btn btn-link w-100 mt-2 text-decoration-none text-muted">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#select-cabang').select2({
                theme: 'bootstrap-5',
                width: '100%',
                closeOnSelect: false,
                allowClear: true
            });
        });
    </script>
</body>
</html>