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
?>