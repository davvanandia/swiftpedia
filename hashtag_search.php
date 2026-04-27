<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$tag = trim(ltrim($_GET['tag'] ?? '', '#'));
if ($tag === '') {
    header("Location: index.php");
    exit();
}

$search = '%#' . $conn->real_escape_string($tag) . '%';
$stmt = $conn->prepare("SELECT p.*, u.username, u.profile_pic FROM posts p JOIN users u ON p.user_id = u.id WHERE p.content LIKE ? ORDER BY p.created_at DESC");
$stmt->bind_param("s", $search);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<div class="container mt-4">
    <h3>Hasil pencarian untuk #<?= safeOutput($tag) ?></h3>
    <?php if (empty($posts)): ?>
        <div class="alert alert-info">Tidak ada postingan dengan hashtag #<?= safeOutput($tag) ?></div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <p><?= highlightHashtags(safeOutput($post['content'])) ?></p>
                    <small>Oleh: <?= safeOutput($post['username']) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>
<?php include 'includes/footer.php'; ?>