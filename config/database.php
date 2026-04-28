<?php
// konfigurasi koneksi database MySQL
$host = 'localhost';      
$user = 'root';           
$pass = '';               
$db   = 'swiftpedia_db';  

// koneksi menggunakan mysqli
$conn = mysqli_connect($host, $user, $pass, $db);

// apakah koneksi berhasil
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// charset ke UTF-8 agar mendukung karakter khusus
mysqli_set_charset($conn, "utf8");
?>