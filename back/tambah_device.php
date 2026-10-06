<?php
ob_start();
session_start();
include 'koneksi.php';

// --- PROSES SIMPAN DATA ---
$message = "";

// Cek apakah tombol simpan ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_simpan'])) {
    
    $namatv  = $_POST['namatv'];
    $cabang  = $_POST['cabang'];
    $slide   = isset($_POST['slide']) ? (int)$_POST['slide'] : 1;

    if (!empty($namatv) && !empty($cabang)) {
        try {
            // Menggunakan variabel $db sesuai yang ada di config.php Anda
            $sql = "INSERT INTO tbl_devicetv (namatv, cabang, slide) VALUES (:namatv, :cabang, :slide)";
            $stmt = $db->prepare($sql);
            
            // Bind parameter untuk keamanan (Anti SQL Injection)
            $stmt->bindParam(':namatv', $namatv);
            $stmt->bindParam(':cabang', $cabang);
            $stmt->bindParam(':slide', $slide, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success border-0 shadow animate__animated animate__bounceIn'>🚀 Device <b>$namatv</b> berhasil didaftarkan!</div>";
            }
        } catch(PDOException $e) {
            $message = "<div class='alert alert-danger border-0 shadow'>❌ Gagal Simpan: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning border-0 shadow'>⚠️ Harap isi semua kolom!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Device TV | Saimen Digital Signage</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body { 
            background-color: #f3f4f7;
            background-image: 
                radial-gradient(at 0% 0%, rgba(0,123,255,0.15) 0px, transparent 50%), 
                radial-gradient(at 100% 0%, rgba(102,16,242,0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(0,123,255,0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(102,16,242,0.15) 0px, transparent 50%);
            font-family: 'Segoe UI', sans-serif; 
            height: 100vh; 
            display: flex; 
            align-items: center;
            margin: 0;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }
        .card-header-custom {
            background: #fff;
            padding: 35px 20px 20px 20px;
            text-align: center;
            border-radius: 30px 30px 0 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .icon-box {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #007bff, #6610f2);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px; color: white; font-size: 30px;
            box-shadow: 0 10px 20px rgba(0,123,255,0.2);
        }
        .form-control {
            background: #f8f9fa; border: 1px solid #e9ecef;
            border-radius: 0 15px 15px 0; height: 55px;
        }
        .input-group-text {
            background: #f8f9fa; border: 1px solid #e9ecef;
            color: #007bff; border-radius: 15px 0 0 15px;
            width: 55px; justify-content: center; border-right: none;
        }
        .btn-register {
            background: linear-gradient(45deg, #007bff, #6610f2);
            border: none; border-radius: 18px; padding: 15px;
            font-weight: 700; transition: 0.4s; letter-spacing: 1.5px;
        }
        .btn-register:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102, 16, 242, 0.3);
        }
        .label-custom { font-size: 11px; font-weight: 800; color: #6c757d; letter-spacing: 1px; margin-bottom: 5px; display: block; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <div class="card glass-card animate__animated animate__zoomIn">
                <div class="card-header-custom">
                    <div class="icon-box animate__animated animate__pulse animate__infinite">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h3 class="font-weight-bold mb-1" style="color: #1a1a1a;">ADD DEVICE</h3>
                    <p class="text-muted small">Input Perangkat Digital Signage Saimen</p>
                    <p><a href="index">Kembali</a></p>
                </div>
                <div class="card-body p-4">
                    <?php echo $message; ?>
                    <form method="POST" action="">
                        
                        <div class="form-group mb-4">
                            <span class="label-custom">DISPLAY NAME / POSITION</span>
                            <div class="input-group shadow-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                </div>
                                <input type="text" name="namatv" class="form-control" placeholder="Contoh: TV Area Kasir" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <span class="label-custom">BRANCH / CABANG</span>
                            <div class="input-group shadow-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-store"></i></span>
                                </div>
                                <input type="text" name="cabang" class="form-control" placeholder="Contoh: JAMBI-01" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <span class="label-custom">FITUR SLIDE / TRANSERA</span>
                            <div class="shadow-sm p-3 bg-light" style="border: 1px solid #e9ecef; border-radius: 15px;">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="slide_aktif" name="slide" class="custom-control-input" value="1" checked>
                                    <label class="custom-control-label font-weight-bold text-dark" for="slide_aktif">Aktif (Slide Berjalan)</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline mt-2 mt-sm-0">
                                    <input type="radio" id="slide_nonaktif" name="slide" class="custom-control-input" value="0">
                                    <label class="custom-control-label font-weight-bold text-muted" for="slide_nonaktif">Nonaktif (1 Konten Saja)</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="btn_simpan" class="btn btn-primary btn-block btn-register text-white shadow">
                            <i class="fas fa-plus-circle mr-2"></i> Register Device
                        </button>
                    </form>
                </div>
            </div>
            
            <p class="text-center mt-4 text-muted small font-weight-bold">
                SYUHARTO IT DEPT &bull; 2026
            </p>
        </div>
    </div>
</div>

</body>
</html>
