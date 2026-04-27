<?php
session_start();

// Cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect jika belum login
function requireLogin($url = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $url");
        exit();
    }
}

// Output aman dari XSS
function safeOutput($data) {
    if ($data === null || $data === '') return '';
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

// Upload file dengan validasi
function uploadFile($file, $targetDir, $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','txt'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > $maxSize) return false;
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $newName = time() . '_' . uniqid() . '.' . $ext;
    $path = $targetDir . $newName;
    return move_uploaded_file($file['tmp_name'], $path) ? $newName : false;
}

// Hapus file
function deleteFile($path) {
    if (!empty($path) && file_exists($path)) return unlink($path);
    return true;
}

// Ambil data user
function getUserById($conn, $id) {
    $stmt = $conn->prepare("SELECT id, username, email, bio, profile_pic, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Format waktu
function timeAgo($datetime) {
    return date('d/m/Y H:i:s', strtotime($datetime));
}

// Ubah #hashtag jadi link
function highlightHashtags($text) {
    if (empty($text)) return '';
    return preg_replace('/#(\w+)/', '<a href="hashtag_search.php?tag=$1">#$1</a>', (string)$text);
}

// Ambil komentar (rekursif)
function getCommentsByPost($conn, $postId, $parentId = null) {
    if ($parentId === null) {
        $stmt = $conn->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.parent_id IS NULL ORDER BY c.created_at ASC");
        $stmt->bind_param("i", $postId);
    } else {
        $stmt = $conn->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.parent_id = ? ORDER BY c.created_at ASC");
        $stmt->bind_param("ii", $postId, $parentId);
    }
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($comments as &$c) {
        $c['replies'] = getCommentsByPost($conn, $postId, $c['id']);
    }
    return $comments;
}
?>