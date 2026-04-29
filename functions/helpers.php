<?php
// File helper: fungsi-fungsi umum yang dipakai di seluruh aplikasi
// Mulai session untuk manajemen login
session_start();
// Fungsi: cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
// Fungsi: memaksa user login, jika belum maka redirect ke halaman login
function requireLogin($url = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $url");
        exit();
    }
}

function safeOutput($data) {
    if ($data === null || $data === '') return '';
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

// Fungsi: upload file dengan validasi ekstensi dan ukuran
function uploadFile($file, $targetDir, $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','txt'], $maxSize = 5242880) {
    // Cek apakah tidak ada error saat upload
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    
    // Ambil ekstensi file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    
    // Cek ukuran file
    if ($file['size'] > $maxSize) return false;
    
    // Buat direktori jika belum ada
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    // Buat nama file unik (timestamp + random + ekstensi)
    $newName = time() . '_' . uniqid() . '.' . $ext;
    $path = $targetDir . $newName;
    
    // Pindahkan file dari temporary ke tujuan
    return move_uploaded_file($file['tmp_name'], $path) ? $newName : false;
}
// Fungsi: hapus file jika ada
function deleteFile($path) {
    if (!empty($path) && file_exists($path)) {
        return unlink($path);
    }
    return true; // tidak perlu dihapus jika file tidak ada
}

// Fungsi: ambil data user berdasarkan ID
function getUserById($conn, $id) {
    $stmt = $conn->prepare("SELECT id, username, email, bio, profile_pic, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
// Fungsi: format waktu menjadi tanggal/bulan/tahun jam:menit:detik
function timeAgo($datetime) {
    return date('d/m/Y H:i:s', strtotime($datetime));
}
// Fungsi: mengubah teks #hashtag menjadi link pencarian
function highlightHashtags($text) {
    if (empty($text)) return '';
    // Cari pola #kata dan ganti dengan <a href="...">#kata</a>
    return preg_replace('/#(\w+)/', '<a href="hashtag_search.php?tag=$1">#$1</a>', (string)$text);
}
// Fungsi: ambil komentar dari sebuah postingan (rekursif untuk nested reply)
function getCommentsByPost($conn, $postId, $parentId = null) {
    if ($parentId === null) {
        // Ambil komentar utama (parent_id = NULL)
        $stmt = $conn->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.parent_id IS NULL ORDER BY c.created_at ASC");
        $stmt->bind_param("i", $postId);
    } else {
        // Ambil balasan dari komentar tertentu (parent_id tertentu)
        $stmt = $conn->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.parent_id = ? ORDER BY c.created_at ASC");
        $stmt->bind_param("ii", $postId, $parentId);
    }
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Untuk setiap komentar, ambil lagi balasannya secara rekursif
    foreach ($comments as &$c) {
        $c['replies'] = getCommentsByPost($conn, $postId, $c['id']);
    }
    return $comments;
}

// Fungsi: menampilkan komentar bertingkat (nested) secara rekursif
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