<?php
error_reporting(0); // Mencegah Warning/Error PHP merusak format JSON
header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');
include 'config.php';

$user = isset($_GET['user']) ? $_GET['user'] : '';
$cabang = isset($_GET['cabang']) ? $_GET['cabang'] : '';

if($user && $cabang) {
    // Gunakan LOWER() agar kebal terhadap perbedaan huruf besar/kecil antara local dan hosting
    $stmt = $conn->prepare("SELECT perintah_khusus, slide FROM tbl_devicetv WHERE LOWER(namatv) = LOWER(?) AND LOWER(cabang) = LOWER(?) LIMIT 1");
    $stmt->bind_param("ss", $user, $cabang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        $cmd = trim($row['perintah_khusus'] ?? ''); // Cegah error trim() jika nilainya NULL
        $slide = isset($row['slide']) ? $row['slide'] : 1;
        
        // Kosongkan perintah setelah dibaca agar tidak terus menerus clear cache
        if($cmd === 'CLEAR_CACHE') {
            // Gunakan string kosong '' alih-alih NULL untuk menghindari error Strict Mode MySQL di Hosting
            $stmt_clear = $conn->prepare("UPDATE tbl_devicetv SET perintah_khusus = '' WHERE namatv = ? AND cabang = ?");
            $stmt_clear->bind_param("ss", $user, $cabang);
            $stmt_clear->execute();
        }

        // Output JSON diberikan setelah update selesai dieksekusi
        echo json_encode(["status" => "ok", "perintah" => $cmd, "slide" => $slide, "db_updated" => isset($stmt_clear) ? true : false]);
    } else {
        echo json_encode(["status" => "error", "message" => "Device tidak ditemukan"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Parameter tidak lengkap"]);
}
$conn->close();
?>
