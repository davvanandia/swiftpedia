<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';
requireLogin();

$userId = $_SESSION['user_id'];
$user = getUserById($conn, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio']);
    $profilePic = $user['profile_pic'];
    
    if (!empty($_FILES['profile_pic']['name'])) {
        if ($user['profile_pic'] && $user['profile_pic'] != 'default.png') {
            deleteFile('assets/uploads/profile/' . $user['profile_pic']);
        }
        $newPic = uploadFile($_FILES['profile_pic'], 'assets/uploads/profile/', ['jpg','jpeg','png','gif'], 2097152);
        if ($newPic) $profilePic = $newPic;
    }
    
    $stmt = $conn->prepare("UPDATE users SET bio = ?, profile_pic = ? WHERE id = ?");
    $stmt->bind_param("ssi", $bio, $profilePic, $userId);
    $stmt->execute();
    
    $_SESSION['profile_pic'] = $profilePic;
    header("Location: profile.php?updated=1");
    exit();
}

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
            <li class="breadcrumb-item active" aria-current="page">Profil Saya</li>
        </ol>
    </nav>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Profil berhasil diperbarui!</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <?php
                    $myAvatar = 'assets/uploads/profile/' . safeOutput($user['profile_pic']);
                    if (!file_exists($myAvatar)) $myAvatar = 'assets/uploads/profile/default.png';
                    ?>
                    <img src="<?= $myAvatar ?>" class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                    <h3><?= safeOutput($user['username']) ?></h3>
                    <p class="text-muted"><?= safeOutput($user['email']) ?></p>
                    <hr>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-pencil"></i> Bio</label>
                            <textarea name="bio" class="form-control" rows="3" placeholder="Tulis bio Anda..."><?= safeOutput($user['bio']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-camera"></i> Foto Profil</label>
                            <input type="file" name="profile_pic" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan Perubahan</button>
                    </form>
                    <hr>
                    <a href="logout.php" class="btn btn-danger w-100" onclick="return confirm('Yakin ingin logout?')"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5><i class="bi bi-file-post"></i> Postingan Saya</h5>
                </div>
                <div class="card-body">
                    <?php if (count($userPosts) == 0): ?>
                        <p class="text-muted">Belum ada postingan. <a href="new_post.php"><i class="bi bi-plus-circle"></i> Buat postingan sekarang</a></p>
                    <?php else: ?>
                        <?php foreach ($userPosts as $post): ?>
                            <div class="border-bottom mb-3 pb-3">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted"><i class="bi bi-clock"></i> <?= timeAgo($post['created_at']) ?></small>
                                    <div>
                                        <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                        <a href="delete_post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus postingan?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </div>
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