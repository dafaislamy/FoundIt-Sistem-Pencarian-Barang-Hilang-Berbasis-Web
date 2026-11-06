<?php
include 'koneksi.php';

// AMBIL KATEGORI UNTUK FILTER DROPDOWN
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// FILTERING LAPORAN
$where_clauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $where_clauses[] = "items.judul LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['tipe'])) {
    $where_clauses[] = "items.tipe = ?";
    $params[] = $_GET['tipe'];
}
if (!empty($_GET['kategori_id'])) {
    $where_clauses[] = "items.categories_id = ?";
    $params[] = $_GET['kategori_id'];
}

$sql = "SELECT items.*, categories.nama_kategori FROM items 
        JOIN categories ON items.categories_id = categories.id";
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY items.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan Barang - FoundIt Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>

    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php">Dashboard</a>
            <a href="admin-kelola-kategori.php">Kelola Kategori</a>
            <a href="admin-kelola-laporan.php" class="active">Kelola Laporan Barang</a>
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
                <h1>Kelola Laporan Barang</h1>
                <p>Daftar seluruh laporan barang yang terdaftar di dalam sistem.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <!-- FILTER BARANG FORM -->
        <section class="card border-0 shadow-sm p-4 mb-4 rounded-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama barang..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select name="tipe" class="form-select">
                        <option value="">Semua Tipe Laporan</option>
                        <option value="lost" <?= (($_GET['tipe'] ?? '') == 'lost') ? 'selected' : '' ?>>Hilang (Lost)</option>
                        <option value="found" <?= (($_GET['tipe'] ?? '') == 'found') ? 'selected' : '' ?>>Ditemukan (Found)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="kategori_id" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($_GET['kategori_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= $cat['nama_kategori'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="admin-kelola-laporan.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </section>

        <!-- TABEL LAPORAN BARANG -->
        <section class="card border-0 shadow-sm rounded-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Jenis Laporan</th>
                            <th>Lokasi Kejadian</th>
                            <th>Status saat Ini</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['judul']) ?></strong>
                                <div class="text-muted small"><?= htmlspecialchars($item['deskripsi']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($item['nama_kategori']) ?></td>
                            <td><span class="badge bg-<?= $item['tipe'] == 'lost' ? 'danger' : 'warning text-dark' ?>"><?= ucfirst($item['tipe']) ?></span></td>
                            <td><?= htmlspecialchars($item['lokasi']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= $item['status'] ?></span></td>
                            <td class="text-end">
                                <a href="admin-ubah-status.php" class="btn btn-sm btn-outline-primary">Ubah Status</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>