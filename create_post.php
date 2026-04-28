<?php
// Memproses pembuatan postingan baru

require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $userId  = $_SESSION['user_id'];

    // Validasi panjang konten
    if (strlen($content) > 250) {
        header("Location: index.php?error=Panjang melebihi 250 karakter");
        exit();
    }

    // Upload gambar dan file jika ada
    $imagePath = null;
    $filePath  = null;
    if (!empty($_FILES['image']['name'])) {
        // Batasi ekstensi gambar dan ukuran 2MB
        $imagePath = uploadFile($_FILES['image'], 'assets/uploads/post_images/', ['jpg','jpeg','png','gif'], 2097152);
    }
    if (!empty($_FILES['file']['name'])) {
        // File lain maksimal 5MB
        $filePath  = uploadFile($_FILES['file'], 'assets/uploads/post_files/', ['pdf','doc','docx','txt','zip'], 5242880);
    }

    // Simpan ke tabel posts
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $content, $imagePath, $filePath);
    $stmt->execute();

    // Redirect ke index
    header("Location: index.php");
    exit();
}
?>