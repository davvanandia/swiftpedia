<?php
/**
 * File helper berisi fungsi-fungsi umum
 */

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($url = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $url");
        exit();
    }
}

/**
 * Safe output - menangani NULL dengan aman
 */
function safeOutput($data) {
    // Kalau NULL atau empty string, kembalikan string kosong
    if ($data === null || $data === '') {
        return '';
    }
    // Pastikan di-cast ke string
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

function uploadFile($file, $targetDir, $allowedExtensions = ['jpg','jpeg','png','gif','pdf','doc','docx','txt'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedExtensions)) return false;
    if ($file['size'] > $maxSize) return false;
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $newFileName = time() . '_' . uniqid() . '.' . $fileExt;
    $targetPath = $targetDir . $newFileName;
    return move_uploaded_file($file['tmp_name'], $targetPath) ? $newFileName : false;
}

function deleteFile($filePath) {
    if (!empty($filePath) && file_exists($filePath)) return unlink($filePath);
    return true;
}

function getUserById($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, username, email, bio, profile_pic, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function timeAgo($datetime) {
    return date('d/m/Y H:i:s', strtotime($datetime));
}

function highlightHashtags($text) {
    if (empty($text)) return '';
    return preg_replace('/#(\w+)/', '<a href="hashtag_search.php?tag=$1">#$1</a>', (string)$text);
}

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
    foreach ($comments as &$comment) {
        $comment['replies'] = getCommentsByPost($conn, $postId, $comment['id']);
    }
    return $comments;
}
?>