<?php
include 'koneksi.php';

// PROSES SETUJUI ATAU TOLAK KLAIM
if (isset($_GET['action']) && isset($_GET['id'])) {
    $claim_id = $_GET['id'];
    $admin_id = 'ADM001'; // ID admin default
    $status = ($_GET['action'] == 'setuju') ? 'Disetujui' : 'Ditolak';

    $stmt = $pdo->prepare("UPDATE claims SET status_klaim = ?, admins_id = ? WHERE id = ?");
    $stmt->execute([$status, $admin_id, $claim_id]);

    header("Location: admin-verifikasi-klaim.php");
    exit();
}

// QUERY MENAMPILKAN KLAIM HANYA YANG STATUSNYA 'Menunggu'
$query = "SELECT claims.*, items.judul AS barang, items.lokasi, users.nama AS pengaju, users.nomor_identitas AS nim 
          FROM claims 
          JOIN items ON claims.items_id = items.id 
          JOIN users ON claims.users_id = users.id 
          WHERE claims.status_klaim = 'Menunggu'";
$claims = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Klaim - FoundIt Admin</title>
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
            <a href="admin-verifikasi-klaim.php" class="active">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php">Verifikasi Laporan</a>
        </nav>
        <a href="../login.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Verifikasi Klaim Barang</h1>
                <p>Cocokkan data pengajuan klaim pemilik dengan detail barang yang ditemukan.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <?php if (empty($claims)): ?>
            <div class="alert alert-info rounded-3">Tidak ada pengajuan klaim barang yang perlu diverifikasi saat ini.</div>
        <?php else: ?>
            <?php foreach ($claims as $c): ?>
            <section class="card border-0 shadow-sm p-4 mb-4 rounded-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <span class="badge bg-primary mb-1">Tiket Klaim #<?= $c['id'] ?></span>
                        <h3 class="mb-0">Klaim atas: <?= htmlspecialchars($c['barang']) ?></h3>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-warning text-dark">Status: <?= $c['status_klaim'] ?></span>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 border-end">
                        <h5 class="text-muted mb-3">Data Barang Temuan</h5>
                        <div class="mb-2"><strong>Nama Barang:</strong> <?= htmlspecialchars($c['barang']) ?></div>
                        <div class="mb-2"><strong>Lokasi Ditemukan:</strong> <?= htmlspecialchars($c['lokasi']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">Bukti Pengajuan Pemohon</h5>
                        <div class="mb-2"><strong>Nama Pengaju:</strong> <?= htmlspecialchars($c['pengaju']) ?> (NIM: <?= htmlspecialchars($c['nim'] ?? '-') ?>)</div>
                        <div class="mb-2"><strong>Bukti Kepemilikan:</strong></div>
                        <p class="bg-light p-3 rounded-2 text-secondary small">"<?= htmlspecialchars($c['bukti_kepemilikan']) ?>"</p>
                    </div>
                </div>

                <div class="border-top pt-3 mt-4 d-flex justify-content-end gap-2">
                    <a href="admin-verifikasi-klaim.php?action=tolak&id=<?= $c['id'] ?>" class="btn btn-outline-danger px-4" onclick="return confirm('Tolak klaim ini?')">Tolak Klaim</a>
                    <a href="admin-verifikasi-klaim.php?action=setuju&id=<?= $c['id'] ?>" class="btn btn-success px-4 text-white" onclick="return confirm('Setujui klaim barang ini?')">Setujui & Hubungi Pemilik</a>
                </div>
            </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>