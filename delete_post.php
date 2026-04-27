<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$postId = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT image_path, file_path FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $postId, $_SESSION['user_id']);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if ($post) {
    deleteFile('assets/uploads/post_images/' . $post['image_path']);
    deleteFile('assets/uploads/post_files/' . $post['file_path']);
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
}
header("Location: index.php");