<?php
/**
 * Halaman untuk membuat postingan baru
 * Terpisah dari index.php sesuai permintaan
 */
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $userId = $_SESSION['user_id'];
    
    if (strlen($content) > 250) {
        $error = "Postingan maksimal 250 karakter!";
    } else {
        $imagePath = null;
        $filePath = null;
        
        if (!empty($_FILES['image']['name'])) {
            $imagePath = uploadFile($_FILES['image'], 'assets/uploads/post_images/', ['jpg','jpeg','png','gif'], 2097152);
        }
        if (!empty($_FILES['file']['name'])) {
            $filePath = uploadFile($_FILES['file'], 'assets/uploads/post_files/', ['pdf','doc','docx','txt','zip'], 5242880);
        }
        
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $content, $imagePath, $filePath);
        $stmt->execute();
        
        header("Location: index.php?success=posted");
        exit();
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Buat Postingan Baru</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5><i class="bi bi-pencil-square"></i> Buat Postingan Baru</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= safeOutput($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Apa yang sedang terjadi?</label>
                            <textarea name="content" class="form-control" rows="4" maxlength="250" required placeholder="Tulis sesuatu... (max 250 karakter)"></textarea>
                            <small class="text-muted">Gunakan #hashtag untuk memudahkan pencarian</small>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Gambar (opsional)</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <div class="col">
                                <label class="form-label">File (opsional)</label>
                                <input type="file" name="file" class="form-control">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Batal</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Posting</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>