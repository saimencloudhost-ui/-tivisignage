<?php
session_start();
include 'koneksi.php';
if (isset($_SESSION['username'])) { header("Location: index.php"); exit; }

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM userpublik WHERE username = :u");
    $stmt->execute([':u' => $user]);
    $data = $stmt->fetch();

    if ($data && $pass === $data['password']) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['nama'] = $data['nama_lengkap'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Digital Signate TV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #000; color: #fff; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: #111; border: 1px solid #333; border-radius: 20px; padding: 40px; width: 100%; max-width: 380px; box-shadow: 0 10px 40px rgba(0,0,0,0.8); }
        .text-primary { color: #0d6efd !important; }
        label { color: #aaa; font-size: 12px; font-weight: bold; margin-bottom: 8px; display: block; }
        .form-control { background: #222 !important; border: 1px solid #444 !important; color: #fff !important; padding: 12px; border-radius: 10px; }
        .form-control:focus { border-color: #0d6efd !important; box-shadow: none; }
        .btn-primary { background: #0d6efd; border: none; padding: 12px; font-weight: bold; width: 100%; border-radius: 10px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Digital Signate TV</h2>
        <p class="text-white small">TV Konten Manajemen Sistem</p>
    </div>
    <?php if($error): ?><div class="alert alert-danger py-2 small text-center" style="background:rgba(255,0,0,0.1); border:none; color:#ff6b6b;"><?= $error ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>USERNAME</label>
            <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
        </div>
        <div class="mb-4">
            <label>PASSWORD</label>
            <input type="password" name="password" class="form-control" placeholder="••••••" required>
        </div>
        <button type="submit" class="btn btn-primary">MASUK SISTEM</button>
    </form>
</div>
</body>
</html>