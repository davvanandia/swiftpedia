<?php
// Mencari postingan dan komentar berdasarkan hashtag

require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

// Ambil tag dari URL, hilangkan tanda # jika ada
$tag = trim(ltrim($_GET['tag'] ?? '', '#'));
if ($tag === '') {
    header("Location: index.php");
    exit();
}

$escapedTag = $conn->real_escape_string($tag);
$searchPattern = '%#' . $escapedTag . '%';

// 1. Cari postingan yang mengandung hashtag
$postStmt = $conn->prepare("
    SELECT p.*, u.username, u.profile_pic, 'post' AS type 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.content LIKE ? 
    ORDER BY p.created_at DESC
");
$postStmt->bind_param("s", $searchPattern);
$postStmt->execute();
$posts = $postStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Cari komentar yang mengandung hashtag
$commentStmt = $conn->prepare("
    SELECT c.*, u.username, u.profile_pic, p.id AS post_id, p.user_id AS post_owner_id,
           'comment' AS type 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    JOIN posts p ON c.post_id = p.id 
    WHERE c.content LIKE ? 
    ORDER BY c.created_at DESC
");
$commentStmt->bind_param("s", $searchPattern);
$commentStmt->execute();
$comments = $commentStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <h3>Hasil pencarian untuk #<?= safeOutput($tag) ?></h3>

    <?php if (empty($posts) && empty($comments)): ?>
        <div class="alert alert-info">Tidak ada postingan atau komentar dengan hashtag #<?= safeOutput($tag) ?></div>
    <?php else: ?>
        <!-- Tab navigasi -->
        <ul class="nav nav-tabs mb-3" id="searchTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">Postingan (<?= count($posts) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">Komentar (<?= count($comments) ?>)</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab Postingan -->
            <div class="tab-pane fade show active" id="posts" role="tabpanel">
                <?php if (empty($posts)): ?>
                    <div class="alert alert-secondary">Tidak ada postingan dengan hashtag #<?= safeOutput($tag) ?></div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-3">
                                    <a href="user_profile.php?id=<?= $post['user_id'] ?>">
                                        <img src="assets/uploads/profile/<?= safeOutput($post['profile_pic'] ?? 'default.png') ?>" width="40" height="40" class="rounded-circle" style="object-fit: cover;">
                                    </a>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong><a href="user_profile.php?id=<?= $post['user_id'] ?>" class="text-decoration-none text-dark"><?= safeOutput($post['username']) ?></a></strong>
                                            <small class="text-muted"><?= timeAgo($post['created_at']) ?></small>
                                        </div>
                                        <p class="mt-2"><?= highlightHashtags(safeOutput($post['content'])) ?></p>
                                        <?php if ($post['image_path']): ?>
                                            <img src="assets/uploads/post_images/<?= safeOutput($post['image_path']) ?>" class="img-fluid rounded mb-2" style="max-height: 200px;">
                                        <?php endif; ?>
                                        <?php if ($post['file_path']): ?>
                                            <div><a href="assets/uploads/post_files/<?= safeOutput($post['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-paperclip"></i> Lampiran</a></div>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <a href="index.php#post-<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary">Lihat Postingan</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tab Komentar -->
            <div class="tab-pane fade" id="comments" role="tabpanel">
                <?php if (empty($comments)): ?>
                    <div class="alert alert-secondary">Tidak ada komentar dengan hashtag #<?= safeOutput($tag) ?></div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-3">
                                    <a href="user_profile.php?id=<?= $comment['user_id'] ?>">
                                        <img src="assets/uploads/profile/<?= safeOutput($comment['profile_pic'] ?? 'default.png') ?>" width="40" height="40" class="rounded-circle" style="object-fit: cover;">
                                    </a>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong><a href="user_profile.php?id=<?= $comment['user_id'] ?>" class="text-decoration-none text-dark"><?= safeOutput($comment['username']) ?></a></strong>
                                            <small class="text-muted"><?= timeAgo($comment['created_at']) ?></small>
                                        </div>
                                        <p class="mt-2"><?= highlightHashtags(safeOutput($comment['content'])) ?></p>
                                        <?php if ($comment['image_path']): ?>
                                            <img src="assets/uploads/comment_images/<?= safeOutput($comment['image_path']) ?>" class="img-fluid rounded mb-2" style="max-height: 150px;">
                                        <?php endif; ?>
                                        <?php if ($comment['file_path']): ?>
                                            <div><a href="assets/uploads/comment_files/<?= safeOutput($comment['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-paperclip"></i> Lampiran</a></div>
                                        <?php endif; ?>
                                        <div class="mt-2 small text-muted">
                                            <i class="bi bi-chat"></i> Komentar pada postingan oleh 
                                            <a href="user_profile.php?id=<?= $comment['post_owner_id'] ?>">@<?= safeOutput($comment['post_owner_id']) ?></a> 
                                            - <a href="index.php#post-<?= $comment['post_id'] ?>">Lihat konteks</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <a href="index.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php include 'includes/footer.php'; ?>