<?php
// Hapus session PHP jika ada
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logging Out...</title>
</head>
<body>
    <script>
        // Hapus localStorage di sisi browser client
        localStorage.removeItem("auth_cabang");
        
        // Arahkan kembali ke halaman index (form login)
        window.location.href = "index";
    </script>
</body>
</html>