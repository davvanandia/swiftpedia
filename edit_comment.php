<?php
// Halaman mengedit komentar (tampilkan form + proses update)

require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$commentId = $_GET['id'] ?? 0;

// Ambil data komentar
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->bind_param("i", $commentId);
$stmt->execute();
$comment = $stmt->get_result()->fetch_assoc();

// Jika tidak ditemukan atau bukan milik user, redirect
if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
    header("Location: index.php");
    exit();
}

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    if (strlen($content) > 250) {
        $error = "Komentar maksimal 250 karakter!";
    } else {
        $imagePath = $comment['image_path'];
        $filePath  = $comment['file_path'];

        // Upload gambar baru jika ada, hapus yang lama
        if (!empty($_FILES['image']['name'])) {
            deleteFile('assets/uploads/comment_images/' . $comment['image_path']);
            $up = uploadFile($_FILES['image'], 'assets/uploads/comment_images/', ['jpg','jpeg','png','gif'], 2097152);
            if ($up) $imagePath = $up;
        }
        // Upload file baru jika ada
        if (!empty($_FILES['file']['name'])) {
            deleteFile('assets/uploads/comment_files/' . $comment['file_path']);
            $up = uploadFile($_FILES['file'], 'assets/uploads/comment_files/', ['pdf','doc','docx','txt','zip'], 5242880);
            if ($up) $filePath = $up;
        }

        // Update database
        $upd = $conn->prepare("UPDATE comments SET content = ?, image_path = ?, file_path = ?, updated_at = NOW() WHERE id = ?");
        $upd->bind_param("sssi", $content, $imagePath, $filePath, $commentId);
        $upd->execute();
        header("Location: index.php");
        exit();
    }
}

// Tampilkan form edit (HTML)
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