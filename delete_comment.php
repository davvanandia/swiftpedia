<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$commentId = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT user_id, image_path, file_path FROM comments WHERE id = ?");
$stmt->bind_param("i", $commentId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();

if ($comment && $comment['user_id'] == $_SESSION['user_id']) {
    deleteFile('assets/uploads/comment_images/' . $comment['image_path']);
    deleteFile('assets/uploads/comment_files/' . $comment['file_path']);
    $del = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $del->bind_param("i", $commentId);
    $del->execute();
}

header("Location: index.php");
exit();
?>