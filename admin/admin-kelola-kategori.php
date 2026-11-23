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

$pesan = '';
$error = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sukses') {
        $pesan = 'Kategori baru berhasil ditambahkan.';
    }
    if ($_GET['status'] === 'dihapus') {
        $pesan = 'Kategori berhasil dihapus.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if ($nama_kategori === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        try {
            $max_id = $conn->query("
                SELECT MAX(ID)
                FROM CATEGORIES
                WHERE REGEXP_LIKE(ID, '^CAT[0-9]+$')
            ")->fetchColumn();
            $num = $max_id ? ((int) substr((string) $max_id, 3) + 1) : 1;
            $id = 'CAT' . str_pad($num, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("
                INSERT INTO CATEGORIES (ID, NAMA_KATEGORI)
                VALUES (:id, :nama_kategori)
            ");
            $stmt->execute([
                ':id' => $id,
                ':nama_kategori' => $nama_kategori
            ]);

            header("Location: admin-kelola-kategori.php?status=sukses");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Gagal menambahkan kategori ke database Oracle.';
        }
    }
}

if (isset($_GET['hapus']) && trim($_GET['hapus']) !== '') {
    $id_hapus = trim($_GET['hapus']);
    try {
        $stmt = $conn->prepare("DELETE FROM CATEGORIES WHERE ID = :id");
        $stmt->execute([':id' => $id_hapus]);

        header("Location: admin-kelola-kategori.php?status=dihapus");
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'Kategori tidak dapat dihapus karena masih digunakan pada data barang.';
    }
}

$categories = [];
try {
    $categories = $conn->query("
        SELECT ID, NAMA_KATEGORI
        FROM CATEGORIES
        ORDER BY NAMA_KATEGORI ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal mengambil daftar kategori.';
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
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
        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Kelola Kategori Barang</h1>
                <p>Atur kategori barang untuk mempermudah pencarian dan filter pengguna.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white"><?= htmlspecialchars($inisialAdmin, ENT_QUOTES, 'UTF-8'); ?></div>
                <div>
                    <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 rounded-3">
                    <h5 class="mb-3">Tambah Kategori Baru</h5>
                    <form method="post" action="admin-kelola-kategori.php">
                        <div class="mb-3">
                            <label class="form-label" for="nama_kategori">Nama Kategori</label>
                            <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" placeholder="Contoh: Alat Tulis &amp; Buku" required>
                        </div>
                        <button type="submit" name="tambah_kategori" value="1" class="btn btn-primary w-100">Simpan Kategori</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Kategori</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars((string) $cat['ID'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td><strong><?= htmlspecialchars((string) $cat['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                            <td class="text-end">
                                                <a href="admin-kelola-kategori.php?hapus=<?= urlencode((string) $cat['ID']); ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Hapus kategori ini?');">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Belum ada kategori terdaftar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>