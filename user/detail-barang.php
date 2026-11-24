
<?php
// user/detail-barang.php

require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// ==========================================
// MENGAMBIL ID BARANG
// ==========================================

$id_item = $_GET['id'] ?? '';

if (empty($id_item)) {
    header("Location: cari-barang.php");
    exit;
}

// ==========================================
// MENGAMBIL DATA DETAIL BARANG
// ==========================================

try {
    $sql = "SELECT i.*, c.nama_kategori
            FROM items i
            JOIN categories c ON i.categories_id = c.id
            WHERE i.id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id' => $id_item
    ]);

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Barang tidak ditemukan!");
    }

} catch (PDOException $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}

// ==========================================
// MENYIAPKAN DATA FOTO
// ==========================================

$foto = $item['FOTO'] ?? '';
$fotoPath = '';

if (!empty($foto)) {
    $foto = str_replace('\\', '/', $foto);

    if (strpos($foto, 'uploads/') === 0) {
        $fotoPath = '../' . $foto;
    } else {
        $fotoPath = '../uploads/' . basename($foto);
    }
}

// ==========================================
// DATA TAMPILAN
// ==========================================

$tipeBarang = strtolower($item['TIPE'] ?? '');

$statusBarang = $item['STATUS'] ?? '';

$tanggalBarang = '-';

if (!empty($item['TANGGAL_KEJADIAN'])) {
    $timestamp = strtotime($item['TANGGAL_KEJADIAN']);

    if ($timestamp !== false) {
        $tanggalBarang = date('d-m-Y', $timestamp);
    }
}

$isBarangDitemukan =
    $tipeBarang === 'found' &&
    $statusBarang === 'Terbit';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Detail Barang - <?= htmlspecialchars($item['JUDUL'], ENT_QUOTES, 'UTF-8'); ?>
    </title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- CSS -->
    <link rel="stylesheet" href="../css/main.css">
</head>

<body>

    <!-- ======================================
         SIDEBAR
    ======================================= -->

    <aside class="sidebar">

        <div class="logo">
            <span>Found</span>It
        </div>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="cari-barang.php" class="active">Cari Barang</a>
            <a href="lapor-hilang.php">Lapor Barang Hilang</a>
            <a href="lapor-ditemukan.php">Lapor Barang Ditemukan</a>
            <a href="klaim.php">Klaim Barang</a>
            <a href="status-klaim.php">Status Klaim</a>
            <a href="riwayat-laporan.php">Riwayat Laporan</a>
            <a href="profil.php">Profil</a>
        </nav>

        <a href="../logout.php" class="logout">
            Keluar
        </a>

    </aside>


    <!-- ======================================
         KONTEN UTAMA
    ======================================= -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div>
                <h1>Detail Barang</h1>
                <p>Informasi lengkap mengenai barang.</p>
            </div>

            <div class="user-profile">

                <div class="profile-icon">
                    <?= htmlspecialchars(strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div>
                    <strong>
                        <?= htmlspecialchars($_SESSION['nama'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>
                    </strong>

                    <small>User</small>
                </div>

            </div>

        </header>


        <!-- ======================================
             DETAIL BARANG
        ======================================= -->

        <section class="detail-container">

            <div class="row g-4">

                <!-- GAMBAR BARANG -->

                <div class="col-lg-5">

                    <div class="detail-image">

                        <?php if (!empty($fotoPath)): ?>

                            <img
                                src="<?= htmlspecialchars($fotoPath, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?= htmlspecialchars($item['JUDUL'], ENT_QUOTES, 'UTF-8'); ?>"
                                style="width: 100%; max-height: 400px; object-fit: cover;"
                            >

                        <?php else: ?>

                            <div style="
                                width: 100%;
                                height: 250px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background: #eee;
                                border-radius: 8px;
                            ">
                                Tidak ada foto
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- INFORMASI BARANG -->

                <div class="col-lg-7">

                    <div class="detail-content">

                        <!-- STATUS -->

                        <div class="detail-status">

                            <span class="status-badge <?= $tipeBarang === 'lost' ? 'hilang' : 'ditemukan'; ?>">

                                <?= $tipeBarang === 'lost' ? 'Hilang' : 'Ditemukan'; ?>

                            </span>

                        </div>


                        <!-- NAMA BARANG -->

                        <h2>
                            <?= htmlspecialchars($item['JUDUL'], ENT_QUOTES, 'UTF-8'); ?>
                        </h2>


                        <!-- DESKRIPSI -->

                        <p class="detail-description">
                            <?= nl2br(htmlspecialchars($item['DESKRIPSI'] ?? '-', ENT_QUOTES, 'UTF-8')); ?>
                        </p>


                        <!-- INFORMASI LENGKAP -->

                        <div class="row g-3 detail-info">

                            <!-- KATEGORI -->

                            <div class="col-md-6">

                                <div class="detail-item">

                                    <span>Kategori</span>

                                    <strong>
                                        <?= htmlspecialchars($item['NAMA_KATEGORI'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>

                                </div>

                            </div>


                            <!-- LOKASI -->

                            <div class="col-md-6">

                                <div class="detail-item">

                                    <span>Lokasi</span>

                                    <strong>
                                        <?= htmlspecialchars($item['LOKASI'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>

                                </div>

                            </div>


                            <!-- TANGGAL -->

                            <div class="col-md-6">

                                <div class="detail-item">

                                    <span>Tanggal</span>

                                    <strong>
                                        <?= htmlspecialchars($tanggalBarang, ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>

                                </div>

                            </div>


                            <!-- STATUS -->

                            <div class="col-md-6">

                                <div class="detail-item">

                                    <span>Status</span>

                                    <strong>
                                        <?= htmlspecialchars($statusBarang, ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>

                                </div>

                            </div>

                        </div>


                        <!-- ======================================
                             TOMBOL AKSI
                        ======================================= -->

                        <div class="d-flex gap-3 flex-wrap detail-actions">

                            <a
                                href="<?= is_logged_in() ? 'cari-barang.php' : '../index.php#barang'; ?>"
                                class="btn-secondary"
                            >
                                Kembali
                            </a>


                            <?php if ($isBarangDitemukan): ?>

                                <?php if (is_logged_in()): ?>

                                    <a
                                        href="klaim.php?id=<?= urlencode($item['ID']); ?>"
                                        class="btn-primary"
                                    >
                                        Ajukan Klaim
                                    </a>

                                <?php else: ?>

                                    <a
                                        href="../login.php"
                                        class="btn-primary"
                                    >
                                        Login untuk Klaim
                                    </a>

                                <?php endif; ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>


    <!-- BOOTSTRAP JAVASCRIPT -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
