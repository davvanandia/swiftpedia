<?php
// Menghapus komentar beserta file lampirannya (hanya pemilik)

require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$commentId = $_GET['id'] ?? 0;

// Ambil data komentar untuk cek kepemilikan dan path file
$stmt = $conn->prepare("SELECT user_id, image_path, file_path FROM comments WHERE id = ?");
$stmt->bind_param("i", $commentId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();

// Jika komentar ada dan user adalah pemiliknya
if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
    // Hapus file fisik gambar dan lampiran
    deleteFile('assets/uploads/comment_images/' . $comment['image_path']);
    deleteFile('assets/uploads/comment_files/' . $comment['file_path']);
    
    // Hapus record dari database
    $del = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $del->bind_param("i", $commentId);
    $del->execute();
}

// Kembali ke halaman utama
header("Location: index.php");
exit();
?>