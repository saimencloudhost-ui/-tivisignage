<?php
ob_start();
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(["status" => "error", "message" => "Sesi habis, silakan login ulang."]);
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'koneksi.php';

// Ambil daftar cabang unik dari tbl_devicetv untuk autocomplete
try {
    $stmt_cb = $db->query("SELECT DISTINCT cabang FROM tbl_devicetv ORDER BY cabang ASC");
    $daftar_cabang = $stmt_cb->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $daftar_cabang = [];
}

if(isset($_POST['submit'])) {
    $nama = $_POST['status']; // 'aktif' atau 'tidak aktif'
    // Mengubah array dari Select2 menjadi string dipisahkan koma
    $cabang = isset($_POST['cabang']) ? implode(',', $_POST['cabang']) : ''; 
    
    // Ambil data penjadwalan
    $tanggal_mulai = !empty($_POST['tanggal_mulai']) ? str_replace('T', ' ', $_POST['tanggal_mulai']) : null;
    $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? str_replace('T', ' ', $_POST['tanggal_selesai']) : null;

    $success_count = 0;
    $rejected_files = [];
    $target_dir = "../KONTEN/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Set timezone Indonesia WIB untuk catatan log/proses jika dibutuhkan
    date_default_timezone_set('Asia/Jakarta');

    if (isset($_FILES['file_konten']) && is_array($_FILES['file_konten']['name'])) {
        $total_files = count($_FILES['file_konten']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            $file_raw_name = $_FILES['file_konten']['name'][$i];
            
            // Lewati jika kosong
            if (empty($file_raw_name)) {
                continue;
            }

            $file_size = $_FILES['file_konten']['size'][$i];
            $tmp_name = $_FILES['file_konten']['tmp_name'][$i];
            $error = $_FILES['file_konten']['error'][$i];

            // 1. Validasi Ukuran File (Maksimal 22 MB)
            if ($file_size > 22 * 1024 * 1024 || $error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE) {
                $rejected_files[] = $file_raw_name . " (Ukuran > 22MB)";
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                $rejected_files[] = $file_raw_name . " (Error Upload: " . $error . ")";
                continue;
            }

            // 2. Deteksi Otomatis Tipe Konten (Video vs Image)
            $ext = strtolower(pathinfo($file_raw_name, PATHINFO_EXTENSION));
            $video_exts = ['mp4', 'webm', 'ogg'];
            $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            if (in_array($ext, $video_exts)) {
                $tipe = 'video';
            } else {
                $tipe = 'image'; // Default fallback ke image jika tidak cocok
            }

            // 3. Proses Upload File
            $file_name = time() . "_" . $i . "_" . str_replace(' ', '_', $file_raw_name);
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($tmp_name, $target_file)) {
                try {
                    $sql = "INSERT INTO tbl_kontentv (nama, src, ket, cabang, tanggal_mulai, tanggal_selesai) 
                            VALUES (:nama, :src, :ket, :cabang, :tanggal_mulai, :tanggal_selesai)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':nama'            => $nama,
                        ':src'             => $file_name,
                        ':ket'             => $tipe,
                        ':cabang'          => $cabang,
                        ':tanggal_mulai'   => $tanggal_mulai,
                        ':tanggal_selesai' => $tanggal_selesai
                    ]);
                    $success_count++;
                } catch(PDOException $e) {
                    if (file_exists($target_file)) {
                        unlink($target_file);
                    }
                    $rejected_files[] = $file_raw_name . " (Gagal DB: " . $e->getMessage() . ")";
                }
            } else {
                $rejected_files[] = $file_raw_name . " (Gagal simpan ke server)";
            }
        }
    }

    // Simpan feedback hasil upload ke session
    $_SESSION['upload_feedback'] = [
        'success_count' => $success_count,
        'rejected_files' => $rejected_files
    ];

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Konten | Saimen System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 0.375rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Overlay Loading Spinner -->
    <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.85); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; backdrop-filter: blur(3px);">
        <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="mt-4 fw-bold text-primary">Sedang Mengupload Konten...</h5>
        <p class="text-muted">Mohon tunggu sebentar, jangan tutup halaman ini.</p>
    </div>

    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="card-body p-4">
                <h4 class="mb-4 fw-bold">🚀 Upload Konten Baru</h4>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File (Bisa memilih banyak gambar/video sekaligus)</label>
                        <input type="file" name="file_konten[]" class="form-control" multiple required>
                        <div class="form-text mt-1 text-muted">Maksimal 22MB per file. Ekstensi tipe file akan dideteksi otomatis.</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jadwal Mulai Tayang (WIB/WITA/WIT)</label>
                            <input type="datetime-local" name="tanggal_mulai" class="form-control">
                            <div class="form-text text-muted">Kosongkan jika ingin segera ditayangkan.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jadwal Selesai Tayang (WIB/WITA/WIT)</label>
                            <input type="datetime-local" name="tanggal_selesai" class="form-control">
                            <div class="form-text text-muted">Kosongkan jika ingin ditayangkan selamanya.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Cabang (Multi-select)</label>
                        <select name="cabang[]" id="cabang-select" class="form-select" multiple="multiple" data-placeholder="Pilih satu atau beberapa cabang..." required>
                            <option value="all">SEMUA CABANG (ALL)</option>
                            <?php foreach($daftar_cabang as $cb): ?>
                                <option value="<?= htmlspecialchars($cb['cabang']) ?>">
                                    <?= strtoupper(htmlspecialchars($cb['cabang'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text mt-2">Ketik nama cabang untuk mencari cepat.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Status</label>
                        <div class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="radio" name="status" id="status_aktif" value="aktif" checked>
                            <label class="form-check-label fw-semibold" for="status_aktif">Aktif</label>
                        </div>
                        <div class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="radio" name="status" id="status_tidak_aktif" value="tidak aktif">
                            <label class="form-check-label fw-semibold" for="status_tidak_aktif">Tidak Aktif</label>
                        </div>
                    </div>

                    <hr class="my-4">

                    <button type="submit" name="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                        Simpan Konten
                    </button>
                    <a href="index.php" class="btn btn-link w-100 mt-2 text-decoration-none text-muted">Batal</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#cabang-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $(this).data('placeholder'),
                closeOnSelect: false,
                allowClear: true
            });

            $('#uploadForm').on('submit', function(e) {
                var fileInput = $('input[name="file_konten[]"]')[0];
                var tooBigFiles = [];
                var validCount = 0;
                var maxSize = 22 * 1024 * 1024; // 22 MB

                if (fileInput.files.length > 0) {
                    for (var i = 0; i < fileInput.files.length; i++) {
                        if (fileInput.files[i].size > maxSize) {
                            tooBigFiles.push(fileInput.files[i].name);
                        } else {
                            validCount++;
                        }
                    }
                }

                if (tooBigFiles.length > 0) {
                    var msg = "⚠️ Perhatian:\nAda " + tooBigFiles.length + " file yang melebihi batas ukuran 22MB:\n- " + tooBigFiles.join("\n- ") + "\n\nFile tersebut TIDAK akan diunggah. ";
                    if (validCount > 0) {
                        msg += "Sedangkan " + validCount + " file lainnya yang memenuhi syarat tetap akan diproses.";
                        alert(msg);
                    } else {
                        msg += "Tidak ada file valid yang memenuhi syarat untuk diunggah.";
                        alert(msg);
                        e.preventDefault();
                        return false;
                    }
                }
                
                // Tampilkan loading spinner jika lolos validasi
                $('#loadingOverlay').css('display', 'flex');
            });
        });
    </script>
</body>
</html>
