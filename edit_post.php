<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$postId = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $imagePath = $post['image_path'];
    $filePath  = $post['file_path'];

    if (!empty($_FILES['image']['name'])) {
        deleteFile('assets/uploads/post_images/' . $post['image_path']);
        $imagePath = uploadFile($_FILES['image'], 'assets/uploads/post_images/');
    }
    if (!empty($_FILES['file']['name'])) {
        deleteFile('assets/uploads/post_files/' . $post['file_path']);
        $filePath = uploadFile($_FILES['file'], 'assets/uploads/post_files/');
    }

    $upd = $conn->prepare("UPDATE posts SET content = ?, image_path = ?, file_path = ?, updated_at = NOW() WHERE id = ?");
    $upd->bind_param("sssi", $content, $imagePath, $filePath, $postId);
    $upd->execute();
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Edit Postingan</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <textarea name="content" class="form-control mb-2" rows="3" maxlength="250" required><?= safeOutput($post['content']) ?></textarea>
                    <div class="mb-2">
                        <label>Gambar saat ini:</label><br>
                        <?php if ($post['image_path']): ?>
                            <img src="assets/uploads/post_images/<?= $post['image_path'] ?>" width="150" class="mb-2"><br>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-2">
                        <label>File saat ini:</label><br>
                        <?php if ($post['file_path']): ?>
                            <a href="assets/uploads/post_files/<?= $post['file_path'] ?>" target="_blank">Lihat file</a><br>
                        <?php endif; ?>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>