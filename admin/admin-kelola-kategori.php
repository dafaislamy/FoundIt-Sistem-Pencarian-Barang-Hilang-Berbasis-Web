<?php
include 'koneksi.php';

// TAMBAH KATEGORI BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    if (!empty($nama_kategori)) {
        // Generate Auto ID CATxxx
        $max_id = $conn->query("SELECT MAX(id) FROM categories")->fetchColumn();
        $num = (int) substr($max_id, 3) + 1;
        $id = 'CAT' . str_pad($num, 3, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO categories (id, nama_kategori) VALUES (?, ?)");
        $stmt->execute([$id, $nama_kategori]);
    }
    header("Location: admin-kelola-kategori.php");
    exit();
}

// HAPUS KATEGORI
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id_hapus]);
    header("Location: admin-kelola-kategori.php");
    exit();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - FoundIt Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>

    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php">Dashboard</a>
            <a href="admin-kelola-kategori.php" class="active">Kelola Kategori</a>
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
                <h1>Kelola Kategori Barang</h1>
                <p>Atur kategori barang untuk mempermudah pencarian dan filter pengguna.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <div class="row g-4">
            <!-- FORM TAMBAH KATEGORI -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 rounded-3">
                    <h5 class="mb-3">Tambah Kategori Baru</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Alat Tulis & Buku" required>
                        </div>
                        <button type="submit" name="tambah_kategori" class="btn btn-primary w-100">Simpan Kategori</button>
                    </form>
                </div>
            </div>

            <!-- TABEL DAFTAR KATEGORI -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Kategori</th>
                                    <th>Nama Kategori</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><code><?= $cat['id'] ?></code></td>
                                    <td><strong><?= htmlspecialchars($cat['nama_kategori']) ?></strong></td>
                                    <td class="text-end">
                                        <a href="admin-kelola-kategori.php?hapus=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin menghapus kategori ini?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>