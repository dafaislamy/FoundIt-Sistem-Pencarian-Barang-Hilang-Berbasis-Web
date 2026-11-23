<?php
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['is_logged_in'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION['is_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$total_user = 0;
$total_laporan = 0;
$perlu_verifikasi = 0;
$klaim_selesai = 0;
$laporan_pending = [];$db_error = '';

try {
    $total_user = (int)$conn->query("SELECT COUNT(*) FROM USERS WHERE UPPER(TRIM(ROLE)) = 'USER'")->fetchColumn();
    $total_laporan = (int)$conn->query("SELECT COUNT(*) FROM ITEMS")->fetchColumn();
    $perlu_verifikasi = (int)$conn->query("SELECT COUNT(*) FROM ITEMS WHERE STATUS = 'Pending'")->fetchColumn();
    $klaim_selesai = (int)$conn->query("SELECT COUNT(*) FROM CLAIMS WHERE STATUS_KLAIM = 'Disetujui'")->fetchColumn();

    $stmt_laporan =$conn->query("
        SELECT I.ID, I.JUDUL, I.DESKRIPSI, I.TIPE, I.STATUS, I.CREATED_AT, I.NAMA_PENEMU,
               U.NAMA AS NAMA_PELAPOR, U.FAKULTAS
        FROM ITEMS I
        LEFT JOIN USERS U ON I.USERS_ID = U.ID
        WHERE I.STATUS = 'Pending'
        ORDER BY I.CREATED_AT DESC
        FETCH FIRST 5 ROWS ONLY
    ");
    $laporan_pending =$stmt_laporan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());$db_error = 'Gagal memuat data dashboard dari database Oracle.';
}

$namaAdmin =$_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - FoundIt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php" class="active">Dashboard</a>
            <a href="admin-kelola-kategori.php">Kelola Kategori</a>
            <a href="admin-kelola-laporan.php">Kelola Laporan Barang</a>
            <a href="admin-kelola-user.php">Kelola User</a>
            <a href="admin-ubah-status.php">Ubah Status Barang</a>
            <a href="admin-verifikasi-klaim.php">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php">Verifikasi Laporan</a>
        </nav>
        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Dashboard Admin</h1>
                <p>Selamat datang di panel kendali sistem FoundIt.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white"><?= htmlspecialchars($inisialAdmin, ENT_QUOTES, 'UTF-8'); ?></div>
                <div>
                    <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <?php if ($db_error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($db_error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="welcome-card mb-4">
            <div>
                <span class="welcome-label">Halo Admin</span>
                <h2>Pusat Kontrol &amp; Verifikasi Barang Kampus</h2>
                <p>Kelola pengguna, pantau laporan yang masuk, dan selesaikan verifikasi kepemilikan barang dengan cepat.</p>
            </div>
            <a href="admin-verifikasi-laporan.php" class="btn btn-primary text-white">Verifikasi Sekarang</a>
        </section>

        <section class="stats mb-4">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">USR</div>
                        <div><span>Total Pengguna</span><strong><?= $total_user; ?></strong></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">BRG</div>
                        <div><span>Laporan Barang</span><strong><?= $total_laporan; ?></strong></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">VER</div>
                        <div><span>Perlu Verifikasi</span><strong class="text-warning"><?= $perlu_verifikasi; ?></strong></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">KLM</div>
                        <div><span>Klaim Selesai</span><strong><?= $klaim_selesai; ?></strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section mb-4">
            <div class="section-header">
                <div>
                    <h2>Aksi Pintas Admin</h2>
                    <p>Pilih navigasi pintas untuk mengelola sistem.</p>
                </div>
            </div>
            <div class="row g-3