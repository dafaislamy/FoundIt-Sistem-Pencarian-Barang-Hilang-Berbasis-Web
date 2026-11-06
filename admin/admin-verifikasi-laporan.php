<?php
include 'koneksi.php';

// PROSES VERIFIKASI LAPORAN
if (isset($_GET['action']) && isset($_GET['id'])) {
    $item_id = $_GET['id'];
    $status = ($_GET['action'] == 'setuju') ? 'Terbit' : 'Ditolak';

    $stmt = $conn->prepare("UPDATE items SET status = ? WHERE id = ?");
    $stmt->execute([$status, $item_id]);

    header("Location: admin-verifikasi-laporan.php");
    exit();
}

// QUERY LAPORAN BER-STATUS PENDING
$query = "SELECT items.*, categories.nama_kategori, users.nama AS pelapor_nama, users.nomor_identitas AS nim 
          FROM items 
          JOIN categories ON items.categories_id = categories.id 
          LEFT JOIN users ON items.users_id = users.id 
          WHERE items.status = 'Pending' 
          ORDER BY items.created_at DESC";
$laporan = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Laporan - FoundIt Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>

    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php">Dashboard</a>
            <a href="admin-kelola-kategori.php">Kelola Kategori</a>
            <a href="admin-kelola-laporan.php">Kelola Laporan Barang</a>
            <a href="admin-kelola-user.php">Kelola User</a>
            <a href="admin-ubah-status.php">Ubah Status Barang</a>
            <a href="admin-verifikasi-klaim.php">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php" class="active">Verifikasi Laporan</a>
        </nav>
        <a href="../login.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Verifikasi Laporan Masuk</h1>
                <p>Validasi laporan barang yang diajukan sebelum ditayangkan ke publik.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <section class="row g-4">
            <?php if (empty($laporan)): ?>
                <div class="col-12"><div class="alert alert-info">Tidak ada laporan barang baru yang membutuhkan verifikasi.</div></div>
            <?php else: ?>
                <?php foreach ($laporan as $lap): ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm p-4 rounded-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-<?= $lap['tipe'] == 'lost' ? 'danger' : 'warning text-dark' ?>">Laporan <?= ucfirst($lap['tipe']) ?></span>
                            <small class="text-muted"><?= date('d M Y H:i', strtotime($lap['created_at'])) ?></small>
                        </div>

                        <h4 class="mb-2"><?= htmlspecialchars($lap['judul']) ?></h4>
                        <p class="text-muted small mb-3">Oleh: <strong><?= htmlspecialchars($lap['pelapor_nama'] ?? $lap['nama_penemu'] ?? 'Anonim') ?></strong> (NIM: <?= htmlspecialchars($lap['nim'] ?? '-') ?>)</p>

                        <div class="bg-light p-3 rounded-2 mb-3">
                            <div class="row g-2 small">
                                <div class="col-6"><strong>Kategori:</strong> <?= htmlspecialchars($lap['nama_kategori']) ?></div>
                                <div class="col-6"><strong>Lokasi:</strong> <?= htmlspecialchars($lap['lokasi']) ?></div>
                                <div class="col-12 mt-2">
                                    <strong>Deskripsi / Ciri:</strong>
                                    <p class="mb-0 text-secondary"><?= htmlspecialchars($lap['deskripsi']) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <a href="admin-verifikasi-laporan.php?action=tolak&id=<?= $lap['id'] ?>" class="btn btn-outline-danger w-50" onclick="return confirm('Tolak laporan ini?')">Tolak Laporan</a>
                            <a href="admin-verifikasi-laporan.php?action=setuju&id=<?= $lap['id'] ?>" class="btn btn-primary w-50" onclick="return confirm('Setujui dan publikasikan laporan ini?')">Setujui & Publikasikan</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>