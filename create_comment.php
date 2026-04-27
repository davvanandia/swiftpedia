<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['post_id'] ?? 0;
    $parentId = $_POST['parent_id'] ?? 0;
    $content = trim($_POST['content']);
    $userId = $_SESSION['user_id'];
    
    if (strlen($content) > 250) {
        header("Location: index.php?error=Komentar terlalu panjang (max 250 karakter)");
        exit();
    }
    
    $checkPost = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    $checkPost->bind_param("i", $postId);
    $checkPost->execute();
    if (!$checkPost->get_result()->num_rows) {
        header("Location: index.php");
        exit();
    }
    
    // Parent ID yang valid (NULL jika tidak ada)
    $validParentId = null;
    if ($parentId != 0) {
        $checkParent = $conn->prepare("SELECT id FROM comments WHERE id = ? AND post_id = ?");
        $checkParent->bind_param("ii", $parentId, $postId);
        $checkParent->execute();
        if ($checkParent->get_result()->num_rows) {
            $validParentId = $parentId;
        }
    }
    
    $imagePath = null;
    $filePath = null;
    
    if (!empty($_FILES['image']['name'])) {
        $imagePath = uploadFile($_FILES['image'], 'assets/uploads/comment_images/');
    }
    if (!empty($_FILES['file']['name'])) {
        $filePath = uploadFile($_FILES['file'], 'assets/uploads/comment_files/');
    }
    
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, parent_id, content, image_path, file_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $postId, $userId, $validParentId, $content, $imagePath, $filePath);
    $stmt->execute();
    
    header("Location: index.php");
    exit();
}
?>