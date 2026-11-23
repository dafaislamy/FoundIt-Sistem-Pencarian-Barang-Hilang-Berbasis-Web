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

$categories = [];
$items = [];
$error = '';

$search = trim($_GET['search'] ?? '');
$tipe = trim($_GET['tipe'] ?? '');
$kategori_id = trim($_GET['kategori_id'] ?? '');

try {
    $categories = $conn->query("
        SELECT ID, NAMA_KATEGORI
        FROM CATEGORIES
        ORDER BY NAMA_KATEGORI ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $where_clauses = [];
    $params = [];

    if ($search !== '') {
        $where_clauses[] = "UPPER(I.JUDUL) LIKE UPPER(:search)";
        $params[':search'] = '%' . $search . '%';
    }
    if ($tipe !== '' && in_array($tipe, ['lost', 'found'], true)) {
        $where_clauses[] = "I.TIPE = :tipe";
        $params[':tipe'] = $tipe;
    }
    if ($kategori_id !== '') {
        $where_clauses[] = "I.CATEGORIES_ID = :kategori_id";
        $params[':kategori_id'] = $kategori_id;
    }

    $sql = "
        SELECT I.ID, I.JUDUL, I.DESKRIPSI, I.TIPE, I.LOKASI, I.STATUS, I.CREATED_AT,
               C.NAMA_KATEGORI
        FROM ITEMS I
        JOIN CATEGORIES C ON I.CATEGORIES_ID = C.ID
    ";

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    $sql .= " ORDER BY I.CREATED_AT DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal memuat data laporan barang.';
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
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
        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Kelola Laporan Barang</h1>
                <p>Daftar seluruh laporan barang yang terdaftar di dalam sistem beserta statusnya.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white"><?= htmlspecialchars($inisialAdmin, ENT_QUOTES, 'UTF-8'); ?></div>
                <div>
                    <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="card border-0 shadow-sm p-4 mb-4 rounded-3">
            <form method="get" action="admin-kelola-laporan.php" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Cari nama barang..." autocomplete="off">
                </div>
                <div class="col-md-3">
                    <select name="tipe" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="lost" <?= $tipe === 'lost' ? 'selected' : ''; ?>>Hilang</option>
                        <option value="found" <?= $tipe === 'found' ? 'selected' : ''; ?>>Ditemukan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="kategori_id" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars((string) $cat['ID'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?= $kategori_id === (string) $cat['ID'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $cat['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="admin-kelola-laporan.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </section>

        <section class="card border-0 shadow-sm rounded-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Jenis Laporan</th>
                            <th>Lokasi Kejadian</th>
                            <th>Status Saat Ini</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?php $isLost = strtolower((string) $item['TIPE']) === 'lost'; ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) $item['JUDUL'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars((string) $item['DESKRIPSI'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?= htmlspecialchars((string) $item['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge <?= $isLost ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                            <?= $isLost ? 'Hilang' : 'Ditemukan'; ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars((string) $item['LOKASI'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars((string) $item['STATUS'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td class="text-end">
                                        <a href="admin-ubah-status.php" class="btn btn-sm btn-outline-primary">Ubah Status</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada data laporan barang yang cocok.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>