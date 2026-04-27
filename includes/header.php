<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swiftpedia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-twitter-x"></i> Swiftpedia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="new_post.php"><i class="bi bi-plus-square"></i> Buat Postingan</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-person-plus"></i> Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container mt-4">