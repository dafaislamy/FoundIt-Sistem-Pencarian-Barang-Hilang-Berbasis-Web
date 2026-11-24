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
            <a href="admin-ubah-status.php" class="active">
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
                <h1>Ubah Status Barang</h1>
                <p>Perbarui status siklus publikasi dan status penyelesaian barang di database sistem FoundIt.</p>
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
        <!-- TABEL UBAH STATUS -->
        <section class="admin-card-table">
            <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
                <h6 class="mb-0 fw-bold" style="color: #1e293b;">Daftar Status Barang</h6>
                <span class="badge-pill-soft terbit"><?= count($items); ?> Total Barang</span>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID &amp; Nama Barang</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Status Saat Ini</th>
                            <th class="text-end">Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $stLower = strtolower(trim((string) ($item['STATUS'] ?? '')));
                                    $stClass = 'pending';
                                    if ($stLower === 'terbit' || $stLower === 'disetujui' || $stLower === 'terverifikasi') {
                                        $stClass = 'terbit';
                                    } elseif ($stLower === 'selesai') {
                                        $stClass = 'selesai';
                                    } elseif ($stLower === 'ditolak' || $stLower === 'arsip' || $stLower === 'dibatalkan') {
                                        $stClass = 'ditolak';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: #1e293b;"><?= htmlspecialchars((string) $item['JUDUL'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div>
                                            <code style="background-color: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                                                <?= htmlspecialchars((string) $item['ID'], ENT_QUOTES, 'UTF-8'); ?>
                                            </code>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500; color: #475569;"><?= htmlspecialchars((string) $item['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= htmlspecialchars((string) $item['LOKASI'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-pill-soft <?= $stClass; ?>">
                                            <?= htmlspecialchars((string) $item['STATUS'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="admin-ubah-status.php" class="d-inline-flex gap-2 justify-content-end align-items-center">
                                            <input type="hidden" name="item_id" value="<?= htmlspecialchars((string) $item['ID'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <select name="status_baru" class="admin-filter-select" style="height: 34px; padding: 0 28px 0 10px; font-size: 13px; width: auto;">
                                                <?php foreach ($allowed_statuses as $st): ?>
                                                    <option value="<?= $st; ?>" <?= $item['STATUS'] === $st ? 'selected' : ''; ?>><?= $st; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="update_status" value="1" class="btn-action-sm btn-action-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                <span>Simpan</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="admin-empty-state">
                                        <div class="admin-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        </div>
                                        <div class="admin-empty-title">Belum Ada Data Barang</div>
                                        <div class="admin-empty-text">Saat ini belum ada barang yang terdaftar untuk diubah statusnya.</div>
                                    </div>
                                </td>
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