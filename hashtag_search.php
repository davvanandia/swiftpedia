<?php
// Mencari postingan berdasarkan hashtag, menampilkan hasil dengan tampilan yang sama persis seperti index.php

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

// Kumpulkan ID postingan yang mengandung hashtag (langsung dari posts)
$postIds = [];
$postIdQuery = "SELECT id FROM posts WHERE content LIKE ?";
$stmt = $conn->prepare($postIdQuery);
$stmt->bind_param("s", $searchPattern);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $postIds[] = $row['id'];
}

// Kumpulkan ID postingan yang memiliki komentar mengandung hashtag
$commentPostQuery = "SELECT DISTINCT post_id FROM comments WHERE content LIKE ?";
$stmt2 = $conn->prepare($commentPostQuery);
$stmt2->bind_param("s", $searchPattern);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row2 = $res2->fetch_assoc()) {
    $postIds[] = $row2['post_id'];
}

// Hapus duplikat
$postIds = array_unique($postIds);

// Ambil data postingan yang terpilih
$searchPosts = [];
if (!empty($postIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($postIds), '?'));
    $types = str_repeat('i', count($postIds));
    $query = "SELECT p.*, u.username, u.profile_pic 
              FROM posts p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.id IN ($inPlaceholders) 
              ORDER BY p.created_at DESC";
    $stmt3 = $conn->prepare($query);
    $stmt3->bind_param($types, ...$postIds);
    $stmt3->execute();
    $searchPosts = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb navigasi -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item active">Hasil pencarian: #<?= safeOutput($tag) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Sidebar kiri: pencarian hashtag (sama seperti index) -->
        <div class="col-md-3">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5><i class="bi bi-hash"></i> Cari Hashtag</h5>
                    <form action="hashtag_search.php" method="GET">
                        <div class="input-group">
                            <input type="text" name="tag" class="form-control" placeholder="contoh: swiftpedia">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                        <small class="text-muted">Masukkan kata tanpa #</small>
                    </form>
                </div>
            </div>
        </div>

        <!-- Feed utama: hasil pencarian dengan tampilan persis seperti index.php -->
        <div class="col-md-6">
            <?php if (empty($searchPosts)): ?>
                <div class="alert alert-info">
                    Tidak ada postingan atau komentar dengan hashtag #<?= safeOutput($tag) ?>
                </div>
            <?php else: ?>
                <?php foreach ($searchPosts as $post): ?>
                    <div class="card mb-3 shadow-sm" id="post-<?= $post['id'] ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start gap-3">
                                <!-- Avatar user -->
                                <?php
                                $avatar = 'assets/uploads/profile/' . safeOutput($post['profile_pic']);
                                if (!file_exists($avatar)) $avatar = 'assets/uploads/profile/default.png';
                                ?>
                                <a href="user_profile.php?id=<?= $post['user_id'] ?>">
                                    <img src="<?= $avatar ?>" width="50" height="50" class="rounded-circle" style="object-fit: cover;">
                                </a>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong><a href="user_profile.php?id=<?= $post['user_id'] ?>" class="text-decoration-none text-dark"><?= safeOutput($post['username']) ?></a></strong>
                                        <small class="text-muted"><?= timeAgo($post['created_at']) ?></small>
                                    </div>
                                    <p class="mt-2"><?= highlightHashtags(safeOutput($post['content'])) ?></p>

                                    <!-- Tampilkan gambar postingan jika ada -->
                                    <?php if ($post['image_path']): ?>
                                        <img src="assets/uploads/post_images/<?= safeOutput($post['image_path']) ?>" class="img-fluid rounded mb-2" style="max-height: 300px;">
                                    <?php endif; ?>
                                    <!-- Tampilkan file lampiran jika ada -->
                                    <?php if ($post['file_path']): ?>
                                        <div class="mb-2">
                                            <a href="assets/uploads/post_files/<?= safeOutput($post['file_path']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bi bi-paperclip"></i> Lampiran</a>
                                        </div>
                                    <?php endif; ?>
                                    <!-- Tombol edit/hapus jika pemilik postingan -->
                                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                        <div class="mb-2">
                                            <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i> Edit</a>
                                            <a href="delete_post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus postingan ini?')"><i class="bi bi-trash"></i> Hapus</a>
                                        </div>
                                    <?php endif; ?>
                                    <hr>
                                    <!-- Bagian komentar -->
                                    <div class="comments-section">
                                        <h6><i class="bi bi-chat-dots"></i> Komentar</h6>
                                        <div id="comments-container-<?= $post['id'] ?>">
                                            <?php
                                            $comments = getCommentsByPost($conn, $post['id'], null);
                                            echo displayComments($comments, $post['id']); // fungsi dari helpers.php
                                            ?>
                                        </div>
                                        <!-- Form komentar baru (root) -->
                                        <form action="create_comment.php" method="POST" enctype="multipart/form-data" class="mt-3">
                                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="parent_id" value="0">
                                            <textarea name="content" class="form-control form-control-sm mb-1" rows="2" placeholder="Tulis komentar... (max 250 karakter)" maxlength="250" required></textarea>
                                            <div class="row mb-1">
                                                <div class="col"><input type="file" name="image" class="form-control form-control-sm" accept="image/*">Image</div>
                                                <div class="col"><input type="file" name="file" class="form-control form-control-sm">File</div>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send"></i> Kirim Komentar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar kanan: info user yang login (sama seperti index) -->
        <div class="col-md-3">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body text-center">
                    <?php
                    $myAvatar = 'assets/uploads/profile/' . safeOutput($_SESSION['profile_pic'] ?? 'default.png');
                    if (!file_exists($myAvatar)) $myAvatar = 'assets/uploads/profile/default.png';
                    ?>
                    <img src="<?= $myAvatar ?>" width="80" height="80" class="rounded-circle mb-2" style="object-fit: cover;">
                    <h5><?= safeOutput($_SESSION['username']) ?></h5>
                    <a href="profile.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-person-gear"></i> Kelola Profil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript untuk menampilkan/menyembunyikan form reply (sama persis dengan index.php)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let pid = this.dataset.parentId;
            let f = document.getElementById('reply-form-' + pid);
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        });
    });
    document.querySelectorAll('.cancel-reply').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reply-form-' + this.dataset.parentId).style.display = 'none';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>