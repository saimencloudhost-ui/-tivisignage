<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $db->prepare("UPDATE tbl_devicetv SET perintah_khusus = 'CLEAR_CACHE' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        // Redirect back with success message
        header("Location: data_device.php?msg=cache_cleared");
        exit;
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: data_device.php");
    exit;
}
?>
