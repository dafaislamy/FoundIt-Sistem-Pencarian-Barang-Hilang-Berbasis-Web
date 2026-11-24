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

if (isset($_GET['status']) && $_GET['status'] === 'processed') {
    $pesan = 'Status verifikasi klaim berhasil diperbarui.';
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = trim($_GET['action']);
    $claim_id = trim($_GET['id']);
    $admin_id = $_SESSION['id_user'] ?? 'ADM001';

    if (in_array($action, ['setuju', 'tolak'], true) && $claim_id !== '') {
        $status = ($action === 'setuju') ? 'Disetujui' : 'Ditolak';

        try {
            $stmt = $conn->prepare("
                UPDATE CLAIMS
                SET STATUS_KLAIM = :status_klaim,
                    ADMINS_ID = :admins_id
                WHERE ID = :id
            ");
            $stmt->execute([
                ':status_klaim' => $status,
                ':admins_id' => $admin_id,
                ':id' => $claim_id
            ]);

            header("Location: admin-verifikasi-klaim.php?status=processed");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Gagal memproses verifikasi klaim.';
        }
    }
}

$claims = [];
try {
    $query = "
        SELECT CL.ID, CL.BUKTI_KEPEMILIKAN, CL.STATUS_KLAIM,
               I.JUDUL AS BARANG, I.LOKASI, I.DESKRIPSI AS DESKRIPSI_BARANG,
               U.NAMA AS PENGAJU, U.NOMOR_IDENTITAS AS NIM
        FROM CLAIMS CL
        JOIN ITEMS I ON CL.ITEMS_ID = I.ID
        JOIN USERS U ON CL.USERS_ID = U.ID
        WHERE CL.STATUS_KLAIM = 'Menunggu'
        ORDER BY CL.ID DESC
    ";
    $claims = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal memuat data klaim yang menunggu verifikasi.';
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Klaim - FoundIt Admin</title>
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
            <a href="admin-verifikasi-klaim.php" class="active">
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
                <h1>Verifikasi Klaim Barang</h1>
                <p>Cocokkan data pengajuan klaim pemohon dengan bukti dan detail barang yang ditemukan.</p>
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
        <?php if (!empty($claims)): ?>
            <?php foreach ($claims as $cl): ?>
                <section class="admin-card-table p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 flex-wrap gap-2">
                        <div>
                            <span class="badge-pill-soft terbit mb-2" style="font-size: 11px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                                Tiket Klaim: <?= htmlspecialchars((string) $cl['ID'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <h4 class="mb-0 fw-bold" style="color: #1e293b;">
                                Pengajuan Klaim: <?= htmlspecialchars((string) $cl['BARANG'], ENT_QUOTES, 'UTF-8'); ?>
                            </h4>
                        </div>
                        <span class="badge-pill-soft pending">
                            <?= htmlspecialchars((string) $cl['STATUS_KLAIM'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                    <div class="row g-4">
                        <!-- DATA BARANG ASLI -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 h-100" style="background-color: #f8fafc; border: 1px solid #eef2f6;">
                                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #475569;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                    Data Barang Ditemukan
                                </h6>
                                <div class="mb-2">
                                    <span class="text-muted small d-block">Nama Barang</span>
                                    <strong style="color: #1e293b;"><?= htmlspecialchars((string) $cl['BARANG'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted small d-block">Lokasi Ditemukan</span>
                                    <span style="color: #334155; font-weight: 500;"><?= htmlspecialchars((string) $cl['LOKASI'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Deskripsi / Ciri Khusus</span>
                                    <p class="mb-0 text-secondary small" style="line-height: 1.6;">
                                        <?= nl2br(htmlspecialchars((string) ($cl['DESKRIPSI_BARANG'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- DATA BUKTI PEMOHON -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 h-100" style="background-color: #f0fdf4; border: 1px solid #dcfce7;">
                                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #15803d;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    Bukti Pemohon
                                </h6>
                                <div class="mb-2">
                                    <span class="text-muted small d-block">Nama Pengaju (Pemilik)</span>
                                    <strong style="color: #1e293b;"><?= htmlspecialchars((string) $cl['PENGAJU'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="badge-pill-soft" style="background: #e2e8f0; color: #475569; font-size: 11px; padding: 2px 8px;">NIM: <?= htmlspecialchars((string) ($cl['NIM'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="mt-3">
                                    <span class="text-muted small d-block mb-1">Bukti Kepemilikan &amp; Keterangan</span>
                                    <div class="p-3 rounded-2 bg-white small" style="border: 1px solid #bbf7d0; color: #334155; line-height: 1.6;">
                                        <?= nl2br(htmlspecialchars((string) $cl['BUKTI_KEPEMILIKAN'], ENT_QUOTES, 'UTF-8')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-4 d-flex justify-content-end gap-2">
                        <a href="admin-verifikasi-klaim.php?action=tolak&id=<?= urlencode((string) $cl['ID']); ?>"
                           class="btn-action-sm btn-action-outline-danger"
                           style="height: 38px; padding: 0 18px;"
                           onclick="return confirm('Tolak pengajuan klaim ini?');">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <span>Tolak Klaim</span>
                        </a>
                        <a href="admin-verifikasi-klaim.php?action=setuju&id=<?= urlencode((string) $cl['ID']); ?>"
                           class="btn-action-sm btn-action-success"
                           style="height: 38px; padding: 0 20px;"
                           onclick="return confirm('Setujui pengajuan klaim ini? Status klaim akan diubah menjadi Disetujui.');">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Setujui Klaim</span>
                        </a>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="admin-card-table p-5">
                <div class="admin-empty-state">
                    <div class="admin-empty-icon" style="background-color: #dcfce7; color: #16a34a;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <div class="admin-empty-title">Semua Klaim Telah Diverifikasi</div>
                    <div class="admin-empty-text">Tidak ada pengajuan klaim barang yang menunggu verifikasi dari admin saat ini.</div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>