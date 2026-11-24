
<?php
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
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
$laporan_pending = [];
$db_error = '';

try {
    $total_user = (int) $conn->query(
        "SELECT COUNT(*) FROM USERS WHERE UPPER(TRIM(ROLE)) = 'USER'"
    )->fetchColumn();

    $total_laporan = (int) $conn->query(
        "SELECT COUNT(*) FROM ITEMS"
    )->fetchColumn();

    $perlu_verifikasi = (int) $conn->query(
        "SELECT COUNT(*) FROM ITEMS WHERE STATUS = 'Pending'"
    )->fetchColumn();

    $klaim_selesai = (int) $conn->query(
        "SELECT COUNT(*) FROM CLAIMS WHERE STATUS_KLAIM = 'Disetujui'"
    )->fetchColumn();

    $stmt_laporan = $conn->query("
        SELECT I.ID, I.JUDUL, I.DESKRIPSI, I.TIPE, I.STATUS,
               I.CREATED_AT, I.NAMA_PENEMU,
               U.NAMA AS NAMA_PELAPOR, U.FAKULTAS
        FROM ITEMS I
        LEFT JOIN USERS U ON I.USERS_ID = U.ID
        WHERE I.STATUS = 'Pending'
        ORDER BY I.CREATED_AT DESC
        FETCH FIRST 5 ROWS ONLY
    ");

    $laporan_pending = $stmt_laporan->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $db_error = 'Gagal memuat data dashboard dari database Oracle.';
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - FoundIt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css?v=<?= filemtime(__DIR__ . '/../css/main.css') ?? time(); ?>">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php" class="active">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="admin-kelola-kategori.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <span>Kelola Kategori</span>
            </a>
            <a href="admin-kelola-laporan.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                <span>Kelola Laporan</span>
            </a>
            <a href="admin-kelola-user.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Kelola User</span>
            </a>
            <a href="admin-ubah-status.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                <span>Ubah Status</span>
            </a>
            <a href="admin-verifikasi-klaim.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                <span>Verifikasi Klaim</span>
            </a>
            <a href="admin-verifikasi-laporan.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span>Verifikasi Laporan</span>
            </a>
        </nav>
        <a href="../logout.php" class="logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            <span>Keluar</span>
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <header class="admin-topbar">
            <div class="admin-topbar-title">
                <h1>Dashboard Admin</h1>
                <p>Selamat datang di panel kendali sistem pencarian barang kampus FoundIt.</p>
            </div>
            <div class="admin-user-badge">
                <div class="admin-avatar">
                    <?= htmlspecialchars($inisialAdmin, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="admin-user-info">
                    <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>Super Admin</span>
                </div>
            </div>
        </header>

        <!-- WELCOME BANNER -->
        <section class="welcome-card mb-4">
            <div>
                <span class="welcome-label">Halo, <?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></span>
                <h2>Pusat Kontrol &amp; Verifikasi Barang Kampus</h2>
                <p>Pantau laporan barang, verifikasi bukti klaim mahasiswa, dan kelola ekosistem FoundIt secara terpusat.</p>
            </div>
            <a href="admin-verifikasi-laporan.php" class="btn btn-primary text-white shadow-sm">
                Verifikasi Sekarang →
            </a>
        </section>

        <!-- STATS CARDS -->
        <section class="stats mb-4">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-circle-icon stat-circle-blue">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div class="stat-meta">
                            <span>Total Pengguna</span>
                            <strong><?= number_format($total_user, 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-circle-icon stat-circle-purple">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        </div>
                        <div class="stat-meta">
                            <span>Laporan Barang</span>
                            <strong><?= number_format($total_laporan, 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-circle-icon stat-circle-amber">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div class="stat-meta">
                            <span>Perlu Verifikasi</span>
                            <strong style="color: #d97706;"><?= number_format($perlu_verifikasi, 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-circle-icon stat-circle-emerald">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                        </div>
                        <div class="stat-meta">
                            <span>Klaim Selesai</span>
                            <strong><?= number_format($klaim_selesai, 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- AKSI PINTAS ADMIN -->
        <section class="section mb-4">
            <div class="section-header">
                <div>
                    <h2>Aksi Pintas Admin</h2>
                    <p>Navigasi cepat untuk tugas verifikasi dan pengelolaan data.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <a href="admin-verifikasi-laporan.php" class="action-card text-decoration-none">
                        <div class="stat-circle-icon stat-circle-blue" style="width: 44px; height: 44px; border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                        <div>
                            <h3>Verifikasi Laporan</h3>
                            <p><?= $perlu_verifikasi ?> laporan menunggu peninjauan Anda.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="admin-verifikasi-klaim.php" class="action-card text-decoration-none">
                        <div class="stat-circle-icon stat-circle-emerald" style="width: 44px; height: 44px; border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                        </div>
                        <div>
                            <h3>Verifikasi Klaim</h3>
                            <p>Cocokkan bukti kepemilikan barang dari pemohon.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="admin-kelola-user.php" class="action-card text-decoration-none">
                        <div class="stat-circle-icon stat-circle-purple" style="width: 44px; height: 44px; border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <h3>Kelola Pengguna</h3>
                            <p>Lihat dan kelola akun mahasiswa terdaftar.</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- LAPORAN BUTUH TINDAKAN -->
        <section class="section">
            <div class="section-header">
                <div>
                    <h2>Laporan Butuh Tindakan</h2>
                    <p>Laporan barang hilang &amp; ditemukan terbaru yang membutuhkan verifikasi.</p>
                </div>
                <a href="admin-verifikasi-laporan.php" class="fw-semibold">Lihat Semua →</a>
            </div>

            <div class="admin-card-table">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Tipe</th>
                                <th>Pelapor / Penemu</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($laporan_pending)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="admin-empty-state">
                                            <div class="admin-empty-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                                            </div>
                                            <div class="admin-empty-title">Semua Laporan Telah Diproses</div>
                                            <div class="admin-empty-text">Tidak ada laporan barang yang menunggu verifikasi saat ini.</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($laporan_pending as $row): ?>
                                    <?php
                                        $tipeStr = strtolower((string) ($row['TIPE'] ?? ''));
                                        $isLost = ($tipeStr === 'lost' || $tipeStr === 'hilang');
                                        $statusStr = strtolower((string) ($row['STATUS'] ?? ''));
                                    ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #1e293b;"><?= htmlspecialchars($row['JUDUL'] ?? '-') ?></strong>
                                            <?php if (!empty($row['DESKRIPSI'])): ?>
                                                <div class="text-muted small" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?= htmlspecialchars($row['DESKRIPSI']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-pill-soft <?= $isLost ? 'hilang' : 'ditemukan' ?>">
                                                <?= $isLost ? 'Hilang' : 'Ditemukan' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500;"><?= htmlspecialchars($row['NAMA_PELAPOR'] ?? ($row['NAMA_PENEMU'] ?? 'Anonim')) ?></span>
                                            <?php if (!empty($row['FAKULTAS'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($row['FAKULTAS']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <?= !empty($row['CREATED_AT']) ? date('d M Y', strtotime($row['CREATED_AT'])) : '-' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-pill-soft pending">
                                                <?= htmlspecialchars($row['STATUS'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="admin-verifikasi-laporan.php" class="btn-action-sm btn-action-outline-primary">
                                                Tinjau Laporan
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>
