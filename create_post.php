<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $userId  = $_SESSION['user_id'];

    if (strlen($content) > 250) {
        header("Location: index.php?error=Panjang melebihi 250 karakter");
        exit();
    }

    $imagePath = null;
    $filePath  = null;
    if (!empty($_FILES['image']['name'])) $imagePath = uploadFile($_FILES['image'], 'assets/uploads/post_images/', ['jpg','jpeg','png','gif'], 2097152);
    if (!empty($_FILES['file']['name']))  $filePath  = uploadFile($_FILES['file'], 'assets/uploads/post_files/', ['pdf','doc','docx','txt','zip'], 5242880);

    $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $content, $imagePath, $filePath);
    $stmt->execute();

    header("Location: index.php");
    exit();
}
?>