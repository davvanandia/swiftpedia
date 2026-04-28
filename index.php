<?php
// Halaman utama: menampilkan semua postingan dan komentar

require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

// Ambil semua postingan terbaru
$result = $conn->query("SELECT p.*, u.username, u.profile_pic FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$posts = $result->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb navigasi -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Beranda</li>
        </ol>
    </nav>

    <!-- Alert jika sukses posting -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 'posted'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Postingan berhasil dibuat!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sidebar kiri: pencarian hashtag -->
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

        <!-- Feed utama: daftar postingan -->
        <div class="col-md-6">
            <?php foreach ($posts as $post): ?>
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
                                        echo displayComments($comments, $post['id']); // fungsi displayComments didefinisikan di bawah
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
        </div>

        <!-- Sidebar kanan: info user yang login -->
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

<?php
// Fungsi rekursif untuk menampilkan komentar bertingkat (nested)
function displayComments($comments, $postId, $depth = 0) {
    if (empty($comments)) return '';
    $html = '';
    $ml = $depth * 30; // indentasi kiri berdasarkan level
    foreach ($comments as $c) {
        $avatar = 'assets/uploads/profile/' . safeOutput($c['profile_pic']);
        if (!file_exists($avatar)) $avatar = 'assets/uploads/profile/default.png';
        $html .= '<div class="card card-body bg-light mb-2" style="margin-left: ' . $ml . 'px;">';
        $html .= '<div class="d-flex gap-2">';
        $html .= '<a href="user_profile.php?id=' . $c['user_id'] . '"><img src="' . $avatar . '" width="30" height="30" class="rounded-circle"></a>';
        $html .= '<div class="flex-grow-1">';
        $html .= '<div class="d-flex justify-content-between"><strong><a href="user_profile.php?id=' . $c['user_id'] . '" class="text-decoration-none text-dark">' . safeOutput($c['username']) . '</a></strong><small>' . timeAgo($c['created_at']) . '</small></div>';
        $html .= '<p class="mb-1">' . highlightHashtags(safeOutput($c['content'])) . '</p>';
        if ($c['image_path']) $html .= '<img src="assets/uploads/comment_images/' . safeOutput($c['image_path']) . '" class="img-fluid rounded mb-1" style="max-height:150px;">';
        if ($c['file_path']) $html .= '<div><a href="assets/uploads/comment_files/' . safeOutput($c['file_path']) . '" target="_blank" class="small"><i class="bi bi-file-earmark"></i> Lampiran</a></div>';
        // Tombol edit/hapus untuk pemilik komentar
        if ($c['user_id'] == $_SESSION['user_id']) {
            $html .= '<div class="mt-1"><a href="edit_comment.php?id=' . $c['id'] . '" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i> Edit</a> ';
            $html .= '<a href="delete_comment.php?id=' . $c['id'] . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Yakin hapus komentar?\')"><i class="bi bi-trash"></i> Hapus</a></div>';
        }
        // Tombol balas
        $html .= '<div class="mt-1"><button class="btn btn-sm btn-link text-primary p-0 reply-btn" data-post-id="' . $postId . '" data-parent-id="' . $c['id'] . '"><i class="bi bi-reply"></i> Balas</button></div>';
        // Form balas (tersembunyi awal)
        $html .= '<div id="reply-form-' . $c['id'] . '" style="display:none;" class="mt-2">';
        $html .= '<form action="create_comment.php" method="POST" enctype="multipart/form-data">';
        $html .= '<input type="hidden" name="post_id" value="' . $postId . '"><input type="hidden" name="parent_id" value="' . $c['id'] . '">';
        $html .= '<textarea name="content" class="form-control form-control-sm mb-1" rows="2" placeholder="Balas komentar..." maxlength="250" required></textarea>';
        $html .= '<div class="row mb-1"><div class="col"><input type="file" name="image" class="form-control form-control-sm" accept="image/*"></div><div class="col"><input type="file" name="file" class="form-control form-control-sm"></div></div>';
        $html .= '<button type="submit" class="btn btn-sm btn-primary">Kirim Balasan</button>';
        $html .= '<button type="button" class="btn btn-sm btn-secondary cancel-reply" data-parent-id="' . $c['id'] . '">Batal</button></form></div>';
        $html .= '</div></div>';
        // Panggil rekursif untuk balasan
        if (!empty($c['replies'])) $html .= displayComments($c['replies'], $postId, $depth + 1);
        $html .= '</div>';
    }
    return $html;
}
?>

<script>
// JavaScript untuk menampilkan/menyembunyikan form reply
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