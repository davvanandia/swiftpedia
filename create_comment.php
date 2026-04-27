<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId   = $_POST['post_id'] ?? 0;
    $parentId = $_POST['parent_id'] ?? 0;
    $content  = trim($_POST['content']);
    $userId   = $_SESSION['user_id'];

    if (strlen($content) > 250) {
        header("Location: index.php?error=Komentar maksimal 250 karakter");
        exit();
    }

    // Cek apakah post ada
    $check = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    $check->bind_param("i", $postId);
    $check->execute();
    if (!$check->get_result()->num_rows) {
        header("Location: index.php");
        exit();
    }

    // Validasi parent comment jika ada
    $validParent = null;
    if ($parentId != 0) {
        $cekParent = $conn->prepare("SELECT id FROM comments WHERE id = ? AND post_id = ?");
        $cekParent->bind_param("ii", $parentId, $postId);
        $cekParent->execute();
        if ($cekParent->get_result()->num_rows) $validParent = $parentId;
    }

    // Upload lampiran
    $imagePath = null;
    $filePath  = null;
    if (!empty($_FILES['image']['name'])) $imagePath = uploadFile($_FILES['image'], 'assets/uploads/comment_images/');
    if (!empty($_FILES['file']['name']))  $filePath  = uploadFile($_FILES['file'], 'assets/uploads/comment_files/');

    // Simpan komentar
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, parent_id, content, image_path, file_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $postId, $userId, $validParent, $content, $imagePath, $filePath);
    $stmt->execute();

    header("Location: index.php");
    exit();
}
?>