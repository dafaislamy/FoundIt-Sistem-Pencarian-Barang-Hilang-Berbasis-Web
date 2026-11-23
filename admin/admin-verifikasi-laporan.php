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
            <a href="admin-verifikasi-klaim.php">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php" class="active">Verifikasi Laporan</a>
        </nav>
        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Verifikasi Laporan Masuk</h1>
                <p>Validasi laporan barang yang diajukan oleh pengguna sebelum ditayangkan ke publik.</p>
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

        <section class="row g-4">
            <?php if (!empty($laporan)): ?>
                <?php foreach ($laporan as $lap): ?>
                    <?php
                        $isLost = strtolower((string) $lap['TIPE']) === 'lost';
                        $pelapor = $lap['PELAPOR_NAMA'] ?: ($lap['NAMA_PENEMU'] ?: 'Anonim');
                    ?>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm p-4 rounded-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge <?= $isLost ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                    <?= $isLost ? 'Laporan Kehilangan' : 'Laporan Ditemukan'; ?>
                                </span>
                                <small class="text-muted">
                                    <?= !empty($lap['CREATED_AT']) ? htmlspecialchars(date('d M Y H:i', strtotime((string) $lap['CREATED_AT'])), ENT_QUOTES, 'UTF-8') : '-'; ?>
                                </small>
                            </div>

                            <h4 class="mb-2"><?= htmlspecialchars((string) $lap['JUDUL'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="text-muted small mb-3">
                                Oleh: <strong><?= htmlspecialchars($pelapor, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($lap['NIM'])): ?>
                                    (NIM: <?= htmlspecialchars((string) $lap['NIM'], ENT_QUOTES, 'UTF-8'); ?>)
                                <?php endif; ?>
                            </p>

                            <div class="bg-light p-3 rounded-2 mb-3">
                                <div class="row g-2 small">
                                    <div class="col-6"><strong>Kategori:</strong> <?= htmlspecialchars((string) $lap['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="col-6"><strong>Lokasi:</strong> <?= htmlspecialchars((string) $lap['LOKASI'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="col-12 mt-2">
                                        <strong>Deskripsi / Ciri:</strong>
                                        <p class="mb-0 text-secondary"><?= htmlspecialchars((string) $lap['DESKRIPSI'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-auto">
                                <a href="admin-verifikasi-laporan.php?action=tolak&id=<?= urlencode((string) $lap['ID']); ?>"
                                   class="btn btn-outline-danger w-50"
                                   onclick="return confirm('Tolak laporan ini?');">Tolak Laporan</a>
                                <a href="admin-verifikasi-laporan.php?action=setuju&id=<?= urlencode((string) $lap['ID']); ?>"
                                   class="btn btn-primary w-50"
                                   onclick="return confirm('Setujui dan publikasikan laporan ini?');">Setujui &amp; Publikasikan</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-5 text-center text-muted rounded-3">
                        Tidak ada laporan yang menunggu verifikasi saat ini.
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>