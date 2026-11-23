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
            <a href="admin-ubah-status.php">Ubah Status Barang</a>
            <a href="admin-verifikasi-klaim.php" class="active">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php">Verifikasi Laporan</a>
        </nav>
        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Verifikasi Klaim Barang</h1>
                <p>Cocokkan data pengajuan klaim pemilik dengan detail barang yang ditemukan.</p>
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

        <?php if (!empty($claims)): ?>
            <?php foreach ($claims as $cl): ?>
                <section class="card border-0 shadow-sm p-4 mb-4 rounded-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                        <div>
                            <span class="badge bg-primary mb-1">Tiket Klaim <?= htmlspecialchars((string) $cl['ID'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <h3 class="mb-0">Klaim atas: <?= htmlspecialchars((string) $cl['BARANG'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                        <span class="badge bg-warning text-dark"><?= htmlspecialchars((string) $cl['STATUS_KLAIM'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6 border-end">
                            <h5 class="text-muted mb-3">Data Barang</h5>
                            <div class="mb-2"><strong>Barang:</strong> <?= htmlspecialchars((string) $cl['BARANG'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="mb-2"><strong>Lokasi:</strong> <?= htmlspecialchars((string) $cl['LOKASI'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>
                                <strong>Deskripsi Barang:</strong>
                                <p class="text-secondary small"><?= htmlspecialchars((string) ($cl['DESKRIPSI_BARANG'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Bukti Pengajuan Pemohon</h5>
                            <div class="mb-2"><strong>Nama Pengaju:</strong> <?= htmlspecialchars((string) $cl['PENGAJU'], ENT_QUOTES, 'UTF-8'); ?> (NIM: <?= htmlspecialchars((string) ($cl['NIM'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>)</div>
                            <div class="mb-2"><strong>Bukti Kepemilikan:</strong></div>
                            <p class="bg-light p-3 rounded-2 text-secondary small">
                                <?= htmlspecialchars((string) $cl['BUKTI_KEPEMILIKAN'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-4 d-flex justify-content-end gap-2">
                        <a href="admin-verifikasi-klaim.php?action=tolak&id=<?= urlencode((string) $cl['ID']); ?>"
                           class="btn btn-outline-danger px-4"
                           onclick="return confirm('Tolak pengajuan klaim ini?');">Tolak Klaim</a>
                        <a href="admin-verifikasi-klaim.php?action=setuju&id=<?= urlencode((string) $cl['ID']); ?>"
                           class="btn btn-success px-4 text-white"
                           onclick="return confirm('Setujui klaim ini?');">Setujui Klaim</a>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card border-0 shadow-sm p-5 text-center text-muted rounded-3">
                Tidak ada pengajuan klaim yang menunggu verifikasi.
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>