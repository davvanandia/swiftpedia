<?php
// Menghapus postingan beserta file lampirannya (hanya pemilik)

require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$postId = $_GET['id'] ?? 0;

// Ambil path file dari postingan milik user yang login
$stmt = $conn->prepare("SELECT image_path, file_path FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $postId, $_SESSION['user_id']);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if ($post) {
    // Hapus file gambar dan lampiran
    deleteFile('assets/uploads/post_images/' . $post['image_path']);
    deleteFile('assets/uploads/post_files/' . $post['file_path']);
    
    // Hapus postingan dari database
    $del = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $del->bind_param("i", $postId);
    $del->execute();
}

header("Location: index.php");
exit();
?>