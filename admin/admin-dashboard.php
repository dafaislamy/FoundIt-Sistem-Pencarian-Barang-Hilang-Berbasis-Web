<?php
include 'koneksi.php';

// Menghitung statistik riil
$total_user = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_laporan = $conn->query("SELECT COUNT(*) FROM items")->fetchColumn();
$perlu_verifikasi = $conn->query("SELECT COUNT(*) FROM items WHERE status = 'Pending'")->fetchColumn();
$klaim_selesai = $conn->query("SELECT COUNT(*) FROM claims WHERE status_klaim = 'Disetujui'")->fetchColumn();

// Mengambil laporan yang butuh verifikasi (Pending)
$stmt_laporan = $conn->query("
    SELECT items.*, users.nama AS nama_pelapor 
    FROM items 
    LEFT JOIN users ON items.users_id = users.id 
    WHERE items.status = 'Pending' 
    ORDER BY items.created_at DESC LIMIT 5
");
$laporan_pending = $stmt_laporan->fetchAll(PDO::FETCH_ASSOC);
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
        <a href="../login.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Dashboard Admin</h1>
                <p>Selamat datang di panel kendali sistem FoundIt.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <section class="welcome-card mb-4">
            <div>
                <span class="welcome-label">Halo Admin 👋</span>
                <h2>Pusat Kontrol & Verifikasi Barang Kampus</h2>
                <p>Kelola pengguna, pantau laporan yang masuk, dan selesaikan verifikasi kepemilikan barang dengan cepat.</p>
            </div>
            <a href="admin-verifikasi-laporan.php" class="btn btn-primary text-white">Verifikasi Sekarang</a>
        </section>

        <!-- STATISTIK KARTU DINAMIS -->
        <section class="stats mb-4">
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div><span>Total Pengguna</span><strong><?= $total_user ?></strong></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div><span>Laporan Barang</span><strong><?= $total_laporan ?></strong></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div><span>Perlu Verifikasi</span><strong class="text-warning"><?= $perlu_verifikasi ?></strong></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">✓</div>
                        <div><span>Klaim Selesai</span><strong><?= $klaim_selesai ?></strong></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- AKSI CEPAT ADMIN -->
        <section class="section mb-4">
            <div class="section-header">
                <div>
                    <h2>Aksi Pintas Admin</h2>
                    <p>Pilih navigasi pintas untuk mengelola sistem.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="admin-verifikasi-laporan.php" class="action-card text-decoration-none">
                        <div class="action-icon">📋</div>
                        <div>
                            <h3>Verifikasi Laporan</h3>
                            <p><?= $perlu_verifikasi ?> laporan baru menunggu peninjauan.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="admin-verifikasi-klaim.php" class="action-card text-decoration-none">
                        <div class="action-icon">🤝</div>
                        <div>
                            <h3>Verifikasi Klaim</h3>
                            <p>Cocokkan data pengaju klaim barang.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="admin-kelola-user.php" class="action-card text-decoration-none">
                        <div class="action-icon">👥</div>
                        <div>
                            <h3>Kelola Pengguna</h3>
                            <p>Lihat dan atur status akun mahasiswa.</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- TABEL LAPORAN TERAKHIR -->
        <section class="section">
            <div class="section-header">
                <div>
                    <h2>Laporan Butuh Tindakan</h2>
                    <p>Laporan barang hilang & ditemukan terbaru yang masuk.</p>
                </div>
                <a href="admin-verifikasi-laporan.php">Lihat Semua</a>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
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
                                <tr><td colspan="6" class="text-center py-3 text-muted">Tidak ada laporan yang perlu diverifikasi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($laporan_pending as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['judul']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['deskripsi']) ?></small></td>
                                    <td><span class="badge bg-<?= $row['tipe'] == 'lost' ? 'danger' : 'warning text-dark' ?>"><?= $row['tipe'] == 'lost' ? 'Hilang' : 'Ditemukan' ?></span></td>
                                    <td><?= htmlspecialchars($row['nama_pelapor'] ?? $row['nama_penemu'] ?? 'Anonim') ?></td>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= $row['status'] ?></span></td>
                                    <td class="text-end">
                                        <a href="admin-verifikasi-laporan.php" class="btn btn-sm btn-outline-primary">Tinjau</a>
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