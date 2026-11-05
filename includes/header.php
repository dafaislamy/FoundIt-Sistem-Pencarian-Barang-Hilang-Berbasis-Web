<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoundIt - Sistem Pencarian Barang Hilang</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header style="background: #007bff; color: #fff; padding: 15px 0;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="margin: 0;"><a href="index.php" style="color: #fff; text-decoration: none;">FoundIt</a></h1>
            <nav>
                <a href="index.php" style="color: #fff; margin-right: 15px; text-decoration: none;">Beranda</a>
                <?php if (isset($_SESSION['id_user'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" style="color: #fff; margin-right: 15px; text-decoration: none;">Admin Dashboard</a>
                    <?php else: ?>
                        <a href="user/dashboard.php" style="color: #fff; margin-right: 15px; text-decoration: none;">Dashboard User</a>
                    <?php endif; ?>
                    <a href="logout.php" style="color: #ffcccc; text-decoration: none;">Logout (<?= htmlspecialchars($_SESSION['nama']); ?>)</a>
                <?php else: ?>
                    <a href="login.php" style="color: #fff; margin-right: 15px; text-decoration: none;">Login</a>
                    <a href="register.php" style="color: #fff; text-decoration: none;">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main style="padding: 20px 0;">