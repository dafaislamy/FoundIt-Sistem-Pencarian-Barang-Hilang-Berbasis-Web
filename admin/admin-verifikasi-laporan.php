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

if (isset($_GET['status']) && $_GET['status'] === 'verified') {
    $pesan = 'Status verifikasi laporan berhasil diperbarui.';
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = trim($_GET['action']);
    $item_id = trim($_GET['id']);

    if (in_array($action, ['setuju', 'tolak'], true) && $item_id !== '') {
        $status = ($action === 'setuju') ? 'Terbit' : 'Ditolak';

        try {
            $stmt = $conn->prepare("
                UPDATE ITEMS
                SET STATUS = :status
                WHERE ID = :id
            ");
            $stmt->execute([
                ':status' => $status,
                ':id' => $item_id
            ]);

            header("Location: admin-verifikasi-laporan.php?status=verified");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Gagal memverifikasi laporan barang.';
        }
    }
}

$laporan = [];
try {
    $query = "
        SELECT I.ID, I.JUDUL, I.DESKRIPSI, I.TIPE, I.LOKASI, I.CREATED_AT, I.NAMA_PENEMU,
               C.NAMA_KATEGORI,
               U.NAMA AS PELAPOR_NAMA, U.NOMOR_IDENTITAS AS NIM
        FROM ITEMS I
        JOIN CATEGORIES C ON I.CATEGORIES_ID = C.ID
        LEFT JOIN USERS U ON I.USERS_ID = U.ID
        WHERE I.STATUS = 'Pending'
        ORDER BY I.CREATED_AT DESC
    ";
    $laporan = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal memuat daftar laporan pending.';
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Laporan - FoundIt Admin</title>
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
            <a href="admin-ubah-status.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                <span>Ubah Status</span>
            </a>
            <a href="admin-verifikasi-klaim.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                <span>Verifikasi Klaim</span>
            </a>
            <a href="admin-verifikasi-laporan.php" class="active">
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
                <h1>Verifikasi Laporan Masuk</h1>
                <p>Validasi laporan barang yang diajukan oleh pengguna sebelum ditayangkan ke publik.</p>
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
        <section class="row g-4">
            <?php if (!empty($laporan)): ?>
                <?php foreach ($laporan as $lap): ?>
                    <?php
                        $isLost = strtolower((string) $lap['TIPE']) === 'lost';
                        $pelapor = $lap['PELAPOR_NAMA'] ?: ($lap['NAMA_PENEMU'] ?: 'Anonim');
                    ?>
                    <div class="col-lg-6">
                        <div class="admin-card-table p-4 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge-pill-soft <?= $isLost ? 'hilang' : 'ditemukan'; ?>">
                                    <?= $isLost ? 'Laporan Kehilangan' : 'Laporan Ditemukan'; ?>
                                </span>
                                <small class="text-muted d-flex align-items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <?= !empty($lap['CREATED_AT']) ? htmlspecialchars(date('d M Y H:i', strtotime((string) $lap['CREATED_AT'])), ENT_QUOTES, 'UTF-8') : '-'; ?>
                                </small>
                            </div>

                            <h4 class="mb-1 fw-bold" style="color: #1e293b;"><?= htmlspecialchars((string) $lap['JUDUL'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="text-muted small mb-3">
                                Pelapor: <strong style="color: #334155;"><?= htmlspecialchars($pelapor, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($lap['NIM'])): ?>
                                    <span class="badge-pill-soft ms-1" style="background: #f1f5f9; color: #64748b; font-size: 11px; padding: 2px 7px;">NIM: <?= htmlspecialchars((string) $lap['NIM'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </p>

                            <div class="p-3 rounded-3 mb-3" style="background-color: #f8fafc; border: 1px solid #eef2f6;">
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 11px;">Kategori</span>
                                        <strong style="color: #1e293b;"><?= htmlspecialchars((string) $lap['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 11px;">Lokasi</span>
                                        <strong style="color: #1e293b;"><?= htmlspecialchars((string) $lap['LOKASI'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                    <div class="col-12 mt-2 pt-2 border-top">
                                        <span class="text-muted d-block" style="font-size: 11px;">Deskripsi / Ciri Khusus</span>
                                        <p class="mb-0 text-secondary" style="line-height: 1.5;"><?= nl2br(htmlspecialchars((string) $lap['DESKRIPSI'], ENT_QUOTES, 'UTF-8')); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-auto pt-3 border-top">
                                <a href="admin-verifikasi-laporan.php?action=tolak&id=<?= urlencode((string) $lap['ID']); ?>"
                                   class="btn-action-sm btn-action-outline-danger w-50"
                                   style="height: 38px;"
                                   onclick="return confirm('Apakah Anda yakin ingin menolak laporan ini?');">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                    <span>Tolak Laporan</span>
                                </a>
                                <a href="admin-verifikasi-laporan.php?action=setuju&id=<?= urlencode((string) $lap['ID']); ?>"
                                   class="btn-action-sm btn-action-primary w-50"
                                   style="height: 38px;"
                                   onclick="return confirm('Setujui dan publikasikan laporan ini ke katalog publik?');">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Setujui &amp; Terbitkan</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="admin-card-table p-5">
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon" style="background-color: #dcfce7; color: #16a34a;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            </div>
                            <div class="admin-empty-title">Semua Laporan Telah Diverifikasi</div>
                            <div class="admin-empty-text">Tidak ada laporan barang baru yang menunggu verifikasi saat ini. Sistem dalam keadaan terkini.</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>