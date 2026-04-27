<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$userId = $_GET['id'] ?? 0;
if ($userId == 0) {
    header("Location: index.php");
    exit();
}

$user = getUserById($conn, $userId);
if (!$user) {
    header("Location: index.php");
    exit();
}

$user['bio'] = $user['bio'] ?? '';
$user['profile_pic'] = $user['profile_pic'] ?? 'default.png';

$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userPosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Profil <?= safeOutput($user['username']) ?></li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <?php
                    $avatar = 'assets/uploads/profile/' . $user['profile_pic'];
                    if (!file_exists($avatar)) $avatar = 'assets/uploads/profile/default.png';
                    ?>
                    <img src="<?= $avatar ?>" class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                    <h3><?= safeOutput($user['username']) ?></h3>
                    <p class="text-muted">Bergabung sejak <?= date('d F Y', strtotime($user['created_at'] ?? 'now')) ?></p>
                    <hr>
                    <div class="text-start">
                        <strong>Bio:</strong>
                        <p><?= $user['bio'] === '' ? '<em>Tidak ada bio</em>' : nl2br(safeOutput($user['bio'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5><i class="bi bi-file-post"></i> Postingan <?= safeOutput($user['username']) ?></h5></div>
                <div class="card-body">
                    <?php if (count($userPosts) == 0): ?>
                        <p class="text-muted">Belum ada postingan.</p>
                    <?php else: ?>
                        <?php foreach ($userPosts as $post): ?>
                            <div class="border-bottom mb-3 pb-3">
                                <small class="text-muted"><i class="bi bi-clock"></i> <?= timeAgo($post['created_at']) ?></small>
                                <p class="mt-2"><?= highlightHashtags(safeOutput($post['content'])) ?></p>
                                <?php if ($post['image_path']): ?>
                                    <img src="assets/uploads/post_images/<?= safeOutput($post['image_path']) ?>" class="img-fluid rounded mb-2" style="max-height: 200px;">
                                <?php endif; ?>
                                <?php if ($post['file_path']): ?>
                                    <div><a href="assets/uploads/post_files/<?= safeOutput($post['file_path']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bi bi-paperclip"></i> Lampiran</a></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>