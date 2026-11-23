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

$error = '';
$pesan = '';
$allowed_statuses = ['Pending', 'Terbit', 'Selesai', 'Ditolak', 'Arsip'];

if (isset($_GET['status']) && $_GET['status'] === 'updated') {
    $pesan = 'Status barang berhasil diperbarui.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $item_id = trim($_POST['item_id'] ?? '');
    $status_baru = trim($_POST['status_baru'] ?? '');

    if ($item_id === '' || !in_array($status_baru, $allowed_statuses, true)) {
        $error = 'Data ID barang atau status baru tidak valid.';
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE ITEMS
                SET STATUS = :status_baru
                WHERE ID = :item_id
            ");
            $stmt->execute([
                ':status_baru' => $status_baru,
                ':item_id' => $item_id
            ]);

            header("Location: admin-ubah-status.php?status=updated");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Gagal memperbarui status barang.';
        }
    }
}

$items = [];
try {
    $items = $conn->query("
        SELECT I.ID, I.JUDUL, I.LOKASI, I.STATUS, I.CREATED_AT,
               C.NAMA_KATEGORI
        FROM ITEMS I
        JOIN CATEGORIES C ON I.CATEGORIES_ID = C.ID
        ORDER BY I.CREATED_AT DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal memuat daftar barang.';
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Status Barang - FoundIt Admin</title>
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
            <a href="admin-ubah-status.php" class="active">Ubah Status Barang</a>
            <a href="admin-verifikasi-klaim.php">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php">Verifikasi Laporan</a>
        </nav>
        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Ubah Status Barang</h1>
                <p>Perbarui status siklus barang di database sistem FoundIt.</p>
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

        <section class="card border-0 shadow-sm rounded-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID &amp; Barang</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Status Saat Ini</th>
                            <th class="text-end">Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) $item['JUDUL'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars((string) $item['ID'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?= htmlspecialchars((string) $item['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) $item['LOKASI'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars((string) $item['STATUS'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td class="text-end">
                                        <form method="post" action="admin-ubah-status.php" class="d-inline-flex gap-2 justify-content-end">
                                            <input type="hidden" name="item_id" value="<?= htmlspecialchars((string) $item['ID'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <select name="status_baru" class="form-select form-select-sm" style="width: auto;">
                                                <?php foreach ($allowed_statuses as $st): ?>
                                                    <option value="<?= $st; ?>" <?= $item['STATUS'] === $st ? 'selected' : ''; ?>><?= $st; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="update_status" value="1" class="btn btn-sm btn-primary">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data barang.</td>
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