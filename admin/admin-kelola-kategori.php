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
    <link rel="stylesheet" href="../css/main.css?v=<?= filemtime(__DIR__ . '/../css/main.css') ?? time(); ?>">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="admin-kelola-kategori.php" class="active">
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
                <h1>Kelola Kategori Barang</h1>
                <p>Atur kategori barang untuk mempermudah pencarian dan filter oleh pengguna kampus.</p>
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

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                <div><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- FORM TAMBAH KATEGORI -->
            <div class="col-lg-4">
                <div class="admin-card-table p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="stat-circle-icon stat-circle-blue" style="width: 38px; height: 38px; border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold" style="color: #1e293b;">Tambah Kategori</h5>
                            <small class="text-muted">Buat kelompok barang baru</small>
                        </div>
                    </div>
                    <form method="post" action="admin-kelola-kategori.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 13px; color: #475569;" for="nama_kategori">Nama Kategori</label>
                            <input type="text" id="nama_kategori" name="nama_kategori" class="admin-filter-input w-100" placeholder="Contoh: Alat Tulis &amp; Buku" required>
                        </div>
                        <button type="submit" name="tambah_kategori" value="1" class="admin-btn-filter w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            <span>Simpan Kategori</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABEL DAFTAR KATEGORI -->
            <div class="col-lg-8">
                <div class="admin-card-table">
                    <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
                        <h6 class="mb-0 fw-bold" style="color: #1e293b;">Daftar Kategori Terdaftar</h6>
                        <span class="badge-pill-soft terbit"><?= count($categories); ?> Total Kategori</span>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">ID Kategori</th>
                                    <th>Nama Kategori</th>
                                    <th class="text-end" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td>
                                                <code style="background-color: #f1f5f9; color: #0284c7; padding: 3px 8px; border-radius: 6px; font-weight: 600;">
                                                    <?= htmlspecialchars((string) $cat['ID'], ENT_QUOTES, 'UTF-8'); ?>
                                                </code>
                                            </td>
                                            <td>
                                                <strong style="color: #1e293b;"><?= htmlspecialchars((string) $cat['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            </td>
                                            <td class="text-end">
                                                <a href="admin-kelola-kategori.php?hapus=<?= urlencode((string) $cat['ID']); ?>"
                                                   class="btn-action-sm btn-action-outline-danger"
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Data barang dengan kategori ini mungkin akan terpengaruh.');">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                                    <span>Hapus</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">
                                            <div class="admin-empty-state">
                                                <div class="admin-empty-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                                </div>
                                                <div class="admin-empty-title">Belum Ada Kategori</div>
                                                <div class="admin-empty-text">Gunakan form di sebelah kiri untuk menambahkan kategori barang pertama.</div>
                                            </div>
                                        </td>
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