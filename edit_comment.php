<?php
/**
 * Halaman untuk mengedit komentar yang sudah dibuat
 * Hanya pemilik komentar yang bisa mengedit
 */
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$commentId = $_GET['id'] ?? 0;

// Ambil data komentar beserta post_id untuk validasi kepemilikan
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->bind_param("i", $commentId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();

if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    
    // Validasi panjang maksimal 250 karakter
    if (strlen($content) > 250) {
        $error = "Komentar maksimal 250 karakter!";
    } else {
        $imagePath = $comment['image_path'];
        $filePath = $comment['file_path'];
        
        // Upload gambar baru jika ada
        if (!empty($_FILES['image']['name'])) {
            deleteFile('assets/uploads/comment_images/' . $comment['image_path']);
            $uploaded = uploadFile($_FILES['image'], 'assets/uploads/comment_images/', ['jpg','jpeg','png','gif'], 2097152);
            if ($uploaded) $imagePath = $uploaded;
        }
        
        // Upload file baru jika ada
        if (!empty($_FILES['file']['name'])) {
            deleteFile('assets/uploads/comment_files/' . $comment['file_path']);
            $uploaded = uploadFile($_FILES['file'], 'assets/uploads/comment_files/', ['pdf','doc','docx','txt','zip'], 5242880);
            if ($uploaded) $filePath = $uploaded;
        }
        
        // Update ke database
        $stmt = $conn->prepare("UPDATE comments SET content = ?, image_path = ?, file_path = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $content, $imagePath, $filePath, $commentId);
        $stmt->execute();
        
        header("Location: index.php");
        exit();
    }
}

include 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">Edit Komentar</div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= safeOutput($error) ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <textarea name="content" class="form-control mb-2" rows="3" maxlength="250" required><?= safeOutput($comment['content']) ?></textarea>
                    
                    <div class="mb-2">
                        <label>Gambar saat ini:</label><br>
                        <?php if ($comment['image_path']): ?>
                            <img src="assets/uploads/comment_images/<?= safeOutput($comment['image_path']) ?>" width="150" class="mb-2"><br>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="mb-2">
                        <label>File saat ini:</label><br>
                        <?php if ($comment['file_path']): ?>
                            <a href="assets/uploads/comment_files/<?= safeOutput($comment['file_path']) ?>" target="_blank">Lihat file</a><br>
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