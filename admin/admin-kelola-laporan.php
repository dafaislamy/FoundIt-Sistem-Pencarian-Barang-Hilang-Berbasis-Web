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
            <a href="admin-kelola-laporan.php" class="active">
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
                <h1>Kelola Laporan Barang</h1>
                <p>Daftar seluruh laporan barang hilang &amp; ditemukan yang terdaftar di dalam sistem.</p>
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

        <!-- FILTER BAR SATU BARIS HORIZONTAL -->
        <section class="admin-filter-card">
            <form method="get" action="admin-kelola-laporan.php" class="admin-filter-bar">
                <div style="flex: 2; min-width: 220px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" class="admin-filter-input w-100" placeholder="Cari nama barang..." autocomplete="off">
                </div>
                <div style="flex: 1.2; min-width: 150px;">
                    <select name="tipe" class="admin-filter-select w-100">
                        <option value="">Semua Tipe</option>
                        <option value="lost" <?= $tipe === 'lost' ? 'selected' : ''; ?>>Hilang</option>
                        <option value="found" <?= $tipe === 'found' ? 'selected' : ''; ?>>Ditemukan</option>
                    </select>
                </div>
                <div style="flex: 1.5; min-width: 170px;">
                    <select name="kategori_id" class="admin-filter-select w-100">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars((string) $cat['ID'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?= $kategori_id === (string) $cat['ID'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $cat['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="admin-btn-filter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <span>Filter</span>
                    </button>
                    <a href="admin-kelola-laporan.php" class="admin-btn-reset">Reset</a>
                </div>
            </form>
        </section>

        <!-- TABEL DATA LAPORAN -->
        <section class="admin-card-table">
            <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
                <h6 class="mb-0 fw-bold" style="color: #1e293b;">Data Laporan Barang</h6>
                <span class="badge-pill-soft terbit"><?= count($items); ?> Data Ditemukan</span>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Jenis Laporan</th>
                            <th>Lokasi Kejadian</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $tipeStr = strtolower((string) ($item['TIPE'] ?? ''));
                                    $isLost = ($tipeStr === 'lost' || $tipeStr === 'hilang');
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
                                        <?php if (!empty($item['DESKRIPSI'])): ?>
                                            <div class="text-muted small" style="max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= htmlspecialchars((string) $item['DESKRIPSI'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500; color: #475569;"><?= htmlspecialchars((string) $item['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-pill-soft <?= $isLost ? 'hilang' : 'ditemukan'; ?>">
                                            <?= $isLost ? 'Hilang' : 'Ditemukan'; ?>
                                        </span>
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
                                        <a href="admin-ubah-status.php" class="btn-action-sm btn-action-outline-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                                            <span>Ubah Status</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="admin-empty-state">
                                        <div class="admin-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                        </div>
                                        <div class="admin-empty-title">Tidak Ada Laporan Ditemukan</div>
                                        <div class="admin-empty-text">Tidak ada data laporan barang yang cocok dengan kata kunci atau filter yang Anda pilih.</div>
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