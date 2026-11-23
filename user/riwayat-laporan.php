
<?php
require_once '../config/koneksi.php';

// ==========================================
// 1. CEK LOGIN USER
// ==========================================

if (
    !isset($_SESSION['is_logged_in']) ||
    $_SESSION['is_logged_in'] !== true ||
    ($_SESSION['role'] ?? '') !== 'user'
) {
    header('Location: ../login.php');
    exit;
}

$namaUser = $_SESSION['nama'] ?? 'User';
$inisialUser = strtoupper(substr($namaUser, 0, 1));
$idUser = $_SESSION['id_user'] ?? '';

$daftarLaporan = [];
$error = '';

// ==========================================
// 2. AMBIL RIWAYAT LAPORAN DARI ORACLE
// ==========================================

try {

    $sql = "
        SELECT
            i.ID,
            i.JUDUL,
            i.TIPE,
            i.LOKASI,
            TO_CHAR(i.TANGGAL_KEJADIAN, 'DD-MM-YYYY') AS TANGGAL,
            i.STATUS,
            c.NAMA_KATEGORI
        FROM ITEMS i
        LEFT JOIN CATEGORIES c
            ON i.CATEGORIES_ID = c.ID
        WHERE i.USERS_ID = :id_user
        ORDER BY i.CREATED_AT DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_user' => $idUser
    ]);

    $daftarLaporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalLaporan = count($daftarLaporan);

} catch (PDOException $e) {

    error_log($e->getMessage());
    $totalLaporan = 0;
    $error = 'Gagal mengambil riwayat laporan dari database Oracle.';

}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - FoundIt</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="cari-barang.php">Cari Barang</a>
            <a href="lapor-hilang.php">Lapor Barang Hilang</a>
            <a href="lapor-ditemukan.php">Lapor Barang Ditemukan</a>
            <a href="klaim.php">Klaim Barang</a>
            <a href="status-klaim.php">Status Klaim</a>
            <a href="riwayat-laporan.php" class="active">Riwayat Laporan</a>
            <a href="profil.php">Profil</a>
        </nav>

        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <!-- KONTEN UTAMA -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="topbar">
            <div>
                <h1>Riwayat Laporan</h1>
                <p>Lihat laporan barang yang pernah kamu buat.</p>
            </div>

            <div class="user-profile">
                <div class="profile-icon">
                    <?= htmlspecialchars($inisialUser, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div>
                    <strong><?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>User</small>
                </div>
            </div>
        </header>

        <!-- DAFTAR RIWAYAT LAPORAN -->
        <section class="history-container">

            <div class="history-header">
                <div>
                    <h2>Daftar Laporan</h2>
                    <p><?= $totalLaporan; ?> laporan ditemukan</p>
                </div>

                <a href="lapor-hilang.php" class="btn-report">
                    + Buat Laporan
                </a>
            </div>

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php elseif ($totalLaporan > 0): ?>

                <div id="riwayatList">

                    <?php foreach ($daftarLaporan as $laporan): ?>

                        <div class="history-card">

                            <div class="history-top">

                                <div class="history-title">
                                    <h3>
                                        <?= htmlspecialchars($laporan['JUDUL'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h3>

                                    <p>
                                        <?= $laporan['TIPE'] === 'lost'
                                            ? 'Laporan Barang Hilang'
                                            : 'Laporan Barang Ditemukan'; ?>
                                    </p>
                                </div>

                                <span class="history-status">
                                    <?= htmlspecialchars($laporan['STATUS'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>

                            </div>

                            <div class="history-detail">

                                <div class="history-detail-item">
                                    <span>Kategori</span>
                                    <strong>
                                        <?= htmlspecialchars($laporan['NAMA_KATEGORI'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>
                                </div>

                                <div class="history-detail-item">
                                    <span>Lokasi</span>
                                    <strong>
                                        <?= htmlspecialchars($laporan['LOKASI'], ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>
                                </div>

                                <div class="history-detail-item">
                                    <span>Tanggal</span>
                                    <strong>
                                        <?= htmlspecialchars($laporan['TANGGAL'], ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div id="emptyHistory" class="empty-history" style="display: block;">
                    <h3>Belum Ada Laporan</h3>
                    <p>Kamu belum membuat laporan barang.</p>
                    <a href="lapor-hilang.php" class="btn-report">
                        Buat Laporan
                    </a>
                </div>

            <?php endif; ?>

        </section>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
