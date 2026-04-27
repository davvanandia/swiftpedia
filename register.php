<?php
require_once 'config/database.php';
require_once 'functions/helpers.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    if ($stmt->execute()) {
        header("Location: login.php?registered=1");
        exit();
    } else {
        $error = "Username atau email sudah terdaftar!";
    }
}
include 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Daftar Akun Swiftpedia</div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary w-100">Daftar</button>
                </form>
                <p class="mt-3">Sudah punya akun? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>