<?php
/**
 * Halaman utama feed postingan
 * Menampilkan semua postingan terbaru beserta komentar bertingkat
 * Form posting sudah dipindah ke new_post.php
 */
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

// Ambil semua postingan terbaru
$query = "SELECT p.*, u.username, u.profile_pic 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC";
$result = $conn->query($query);
$posts = $result->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Beranda</li>
        </ol>
    </nav>

    <!-- Alert sukses posting -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 'posted'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> Postingan berhasil dibuat!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sidebar kiri: filter hashtag -->
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

        <!-- Feed utama -->
        <div class="col-md-6">
            <?php foreach ($posts as $post): ?>
                <div class="card mb-3 shadow-sm" id="post-<?= $post['id'] ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <!-- Avatar dengan fallback -->
                            <?php
                            $profilePic = 'assets/uploads/profile/' . safeOutput($post['profile_pic']);
                            if (!file_exists($profilePic)) $profilePic = 'assets/uploads/profile/default.png';
                            ?>
                            <a href="user_profile.php?id=<?= $post['user_id'] ?>">
                                <img src="<?= $profilePic ?>" width="50" height="50" class="rounded-circle" style="object-fit: cover;">
                            </a>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong><a href="user_profile.php?id=<?= $post['user_id'] ?>" class="text-decoration-none text-dark"><?= safeOutput($post['username']) ?></a></strong>
                                    <small class="text-muted"><?= timeAgo($post['created_at']) ?></small>
                                </div>
                                <p class="mt-2"><?= highlightHashtags(safeOutput($post['content'])) ?></p>
                                
                                <!-- Gambar postingan -->
                                <?php if ($post['image_path']): ?>
                                    <img src="assets/uploads/post_images/<?= safeOutput($post['image_path']) ?>" class="img-fluid rounded mb-2" style="max-height: 300px;">
                                <?php endif; ?>
                                
                                <!-- File lampiran postingan -->
                                <?php if ($post['file_path']): ?>
                                    <div class="mb-2">
                                        <a href="assets/uploads/post_files/<?= safeOutput($post['file_path']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                            <i class="bi bi-paperclip"></i> Lampiran
                                        </a>
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
                                <!-- Bagian Komentar dengan reply nested -->
                                <div class="comments-section">
                                    <h6><i class="bi bi-chat-dots"></i> Komentar</h6>
                                    <div id="comments-container-<?= $post['id'] ?>">
                                        <?php
                                        // Ambil komentar root (parent_id = NULL)
                                        $comments = getCommentsByPost($conn, $post['id'], null);
                                        echo displayComments($comments, $post['id']);
                                        ?>
                                    </div>
                                    
                                    <!-- Form komentar baru (root) -->
                                    <form action="create_comment.php" method="POST" enctype="multipart/form-data" class="mt-3">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <input type="hidden" name="parent_id" value="0">
                                        <textarea name="content" class="form-control form-control-sm mb-1" rows="2" placeholder="Tulis komentar... (max 250 karakter)" maxlength="250" required></textarea>
                                        <div class="row mb-1">
                                            <div class="col">
                                                <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                                            </div>
                                            <div class="col">
                                                <input type="file" name="file" class="form-control form-control-sm">
                                            </div>
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
        
        <!-- Sidebar kanan: info user -->
        <div class="col-md-3">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body text-center">
                    <?php
                    $myProfilePic = 'assets/uploads/profile/' . safeOutput($_SESSION['profile_pic'] ?? 'default.png');
                    if (!file_exists($myProfilePic)) $myProfilePic = 'assets/uploads/profile/default.png';
                    ?>
                    <img src="<?= $myProfilePic ?>" width="80" height="80" class="rounded-circle mb-2" style="object-fit: cover;">
                    <h5><?= safeOutput($_SESSION['username']) ?></h5>
                    <a href="profile.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-person-gear"></i> Kelola Profil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Fungsi rekursif untuk menampilkan komentar bertingkat (nested)
 */
function displayComments($comments, $postId, $depth = 0) {
    if (empty($comments)) return '';
    $html = '';
    $marginLeft = $depth * 30;
    foreach ($comments as $comment) {
        // Avatar fallback
        $commentAvatar = 'assets/uploads/profile/' . safeOutput($comment['profile_pic']);
        if (!file_exists($commentAvatar)) $commentAvatar = 'assets/uploads/profile/default.png';
        
        $html .= '<div class="card card-body bg-light mb-2" style="margin-left: ' . $marginLeft . 'px;">';
        $html .= '<div class="d-flex gap-2">';
        $html .= '<a href="user_profile.php?id=' . $comment['user_id'] . '">';
        $html .= '<img src="' . $commentAvatar . '" width="30" height="30" class="rounded-circle" style="object-fit: cover;">';
        $html .= '</a>';
        $html .= '<div class="flex-grow-1">';
        $html .= '<div class="d-flex justify-content-between">';
        $html .= '<strong><a href="user_profile.php?id=' . $comment['user_id'] . '" class="text-decoration-none text-dark">' . safeOutput($comment['username']) . '</a></strong>';
        $html .= '<small>' . timeAgo($comment['created_at']) . '</small>';
        $html .= '</div>';
        $html .= '<p class="mb-1">' . highlightHashtags(safeOutput($comment['content'])) . '</p>';
        
        if ($comment['image_path']) {
            $html .= '<img src="assets/uploads/comment_images/' . safeOutput($comment['image_path']) . '" class="img-fluid rounded mb-1" style="max-height: 150px;">';
        }
        if ($comment['file_path']) {
            $html .= '<div><a href="assets/uploads/comment_files/' . safeOutput($comment['file_path']) . '" target="_blank" class="small"><i class="bi bi-file-earmark"></i> Lampiran</a></div>';
        }
        
        // Tombol edit/hapus jika pemilik komentar
        if ($comment['user_id'] == $_SESSION['user_id']) {
            $html .= '<div class="mt-1">';
            $html .= '<a href="edit_comment.php?id=' . $comment['id'] . '" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i> Edit</a> ';
            $html .= '<a href="delete_comment.php?id=' . $comment['id'] . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Yakin hapus komentar?\')"><i class="bi bi-trash"></i> Hapus</a>';
            $html .= '</div>';
        }
        
        // Tombol Balas (reply)
        $html .= '<div class="mt-1">';
        $html .= '<button class="btn btn-sm btn-link text-primary p-0 reply-btn" data-post-id="' . $postId . '" data-parent-id="' . $comment['id'] . '"><i class="bi bi-reply"></i> Balas</button>';
        $html .= '</div>';
        
        // Form reply (hidden by default)
        $html .= '<div id="reply-form-' . $comment['id'] . '" style="display:none;" class="mt-2">';
        $html .= '<form action="create_comment.php" method="POST" enctype="multipart/form-data" class="mt-2">';
        $html .= '<input type="hidden" name="post_id" value="' . $postId . '">';
        $html .= '<input type="hidden" name="parent_id" value="' . $comment['id'] . '">';
        $html .= '<textarea name="content" class="form-control form-control-sm mb-1" rows="2" placeholder="Balas komentar... (max 250 karakter)" maxlength="250" required></textarea>';
        $html .= '<div class="row mb-1">';
        $html .= '<div class="col"><input type="file" name="image" class="form-control form-control-sm" accept="image/*"></div>';
        $html .= '<div class="col"><input type="file" name="file" class="form-control form-control-sm"></div>';
        $html .= '</div>';
        $html .= '<button type="submit" class="btn btn-sm btn-primary">Kirim Balasan</button>';
        $html .= '<button type="button" class="btn btn-sm btn-secondary cancel-reply" data-parent-id="' . $comment['id'] . '">Batal</button>';
        $html .= '</form>';
        $html .= '</div>';
        
        $html .= '</div></div>';
        // Tampilkan replies rekursif
        if (!empty($comment['replies'])) {
            $html .= displayComments($comment['replies'], $postId, $depth + 1);
        }
        $html .= '</div>';
    }
    return $html;
}
?>

<script>
// JavaScript untuk menampilkan form reply
document.addEventListener('DOMContentLoaded', function() {
    // Event listener untuk tombol reply
    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let parentId = this.dataset.parentId;
            let formDiv = document.getElementById('reply-form-' + parentId);
            if (formDiv.style.display === 'none') {
                formDiv.style.display = 'block';
            } else {
                formDiv.style.display = 'none';
            }
        });
    });
    
    // Event listener untuk tombol batal reply
    document.querySelectorAll('.cancel-reply').forEach(btn => {
        btn.addEventListener('click', function() {
            let parentId = this.dataset.parentId;
            document.getElementById('reply-form-' + parentId).style.display = 'none';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>