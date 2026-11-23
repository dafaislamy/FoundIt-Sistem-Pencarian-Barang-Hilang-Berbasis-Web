
<?php
// Developed by Fasbir Sabran

session_start();
require_once '../config/koneksi.php';

// ==========================================
// PENGECEKAN LOGIN USER
// ==========================================

if (
    !isset($_SESSION['is_logged_in']) ||
    $_SESSION['is_logged_in'] !== true ||
    ($_SESSION['role'] ?? '') !== 'user'
) {
    header("Location: ../login.php");
    exit;
}

// ==========================================
// LOGOUT
// ==========================================

if (isset($_GET['action']) && $_GET['action'] === 'logout') {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: ../login.php");
    exit;
}

// ==========================================
// DATA USER
// ==========================================

$idUser = $_SESSION['id_user'] ?? '';
$namaUser = $_SESSION['nama'] ?? 'User';
$emailUser = $_SESSION['email'] ?? '';

$inisial = strtoupper(substr($namaUser, 0, 1));

// ==========================================
// NILAI AWAL
// ==========================================

$totalHilang = 0;
$totalDitemukan = 0;
$totalKlaim = 0;
$totalDikembalikan = 0;

$laporanTerbaru = [];

$pesanDatabase = '';

// ==========================================
// MENGAMBIL DATA DARI ORACLE
// ==========================================

try {

    // --------------------------------------
    // 1. TOTAL BARANG HILANG MILIK USER
    // --------------------------------------

// TOTAL BARANG HILANG YANG SUDAH TERBIT
$stmt = $conn->query("
    SELECT COUNT(*)
    FROM ITEMS
    WHERE LOWER(TRIM(TIPE)) = 'lost'
    AND LOWER(TRIM(STATUS)) = 'terbit'
");

$totalHilang = (int) $stmt->fetchColumn();

    // --------------------------------------
    // 2. TOTAL BARANG DITEMUKAN YANG TERBIT
    // --------------------------------------

    $stmt = $conn->query("
        SELECT COUNT(*)
        FROM ITEMS
        WHERE LOWER(TRIM(TIPE)) = 'found'
        AND LOWER(TRIM(STATUS)) = 'terbit'
    ");

    $totalDitemukan = (int) $stmt->fetchColumn();


    // --------------------------------------
    // 3. TOTAL KLAIM MILIK USER
    // --------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM CLAIMS
        WHERE USERS_ID = :user_id
    ");

    $stmt->execute([
        ':user_id' => $idUser
    ]);

    $totalKlaim = (int) $stmt->fetchColumn();


    // --------------------------------------
    // 4. TOTAL KLAIM YANG DISETUJUI
    // --------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM CLAIMS
        WHERE USERS_ID = :user_id
        AND LOWER(TRIM(STATUS_KLAIM)) = 'disetujui'
    ");

    $stmt->execute([
        ':user_id' => $idUser
    ]);

    $totalDikembalikan = (int) $stmt->fetchColumn();


    // --------------------------------------
    // 5. LAPORAN TERBARU SELURUH PENGGUNA
    // Hanya menampilkan barang berstatus Terbit
    // --------------------------------------

    $stmt = $conn->query("
        SELECT *
        FROM (
            SELECT
                i.ID,
                i.JUDUL,
                i.DESKRIPSI,
                i.TIPE,
                i.LOKASI,
                i.TANGGAL_KEJADIAN,
                i.FOTO,
                i.STATUS,
                i.CREATED_AT,
                c.NAMA_KATEGORI
            FROM ITEMS i
            LEFT JOIN CATEGORIES c
                ON c.ID = i.CATEGORIES_ID
            WHERE LOWER(TRIM(i.STATUS)) = 'terbit'
            ORDER BY i.CREATED_AT DESC
        )
        WHERE ROWNUM <= 5
    ");

    $laporanTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());

    $pesanDatabase = 'Data dashboard gagal dimuat. Silakan coba lagi.';
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - FoundIt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../css/main.css">

</head>

<body>

    <!-- ================================== -->
    <!-- SIDEBAR -->
    <!-- ================================== -->

    <aside class="sidebar">

        <div class="logo">
            <span>Found</span>It
        </div>

        <nav>

            <a href="dashboard.php" class="active">
                Dashboard
            </a>

            <a href="cari-barang.php">
                Cari Barang
            </a>

            <a href="lapor-hilang.php">
                Lapor Barang Hilang
            </a>

            <a href="lapor-ditemukan.php">
                Lapor Barang Ditemukan
            </a>

            <a href="klaim.php">
                Klaim Barang
            </a>

            <a href="status-klaim.php">
                Status Klaim
            </a>

            <a href="riwayat-laporan.php">
                Riwayat Laporan
            </a>

            <a href="edit-profil.php">
                Profil
            </a>

        </nav>

        <a
            href="dashboard.php?action=logout"
            class="logout"
            onclick="return confirm('Apakah Anda yakin ingin keluar?');"
        >
            Keluar
        </a>

    </aside>


    <!-- ================================== -->
    <!-- MAIN CONTENT -->
    <!-- ================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar align-items-start mb-4 py-4">

            <div style="max-width: 650px; padding-right: 20px;">

                <h1 class="fw-bold mb-2">

                    Selamat datang,
                    <?= htmlspecialchars(
                        $namaUser,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>!

                </h1>

                <p class="text-muted mb-0">

                    Pantau status laporan barang hilang, temukan barang
                    yang tertinggal di area kampus, dan kelola klaim Anda
                    dengan mudah melalui pusat kendali ini.

                </p>

            </div>


            <!-- PROFIL USER -->

            <div class="user-profile">

                <div class="profile-icon">

                    <?= htmlspecialchars(
                        $inisial,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>

                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $namaUser,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </strong>

                    <small class="text-muted">
                        User
                    </small>

                </div>

            </div>

        </header>


        <!-- PESAN DATABASE -->

        <?php if (!empty($pesanDatabase)): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars(
                    $pesanDatabase,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </div>

        <?php endif; ?>


        <!-- ================================== -->
        <!-- STATISTIK -->
        <!-- ================================== -->

        <section class="mb-4">

            <div class="row row-cols-1 row-cols-md-4 g-4">


                <!-- BARANG HILANG -->

                <div class="col">

                    <div class="card border-0 shadow-sm p-3">

                        <p class="text-muted mb-1">
                            Barang Hilang
                        </p>

                        <h3 class="fw-bold mb-0">

                            <?= $totalHilang; ?>

                        </h3>

                    </div>

                </div>


                <!-- BARANG DITEMUKAN -->

                <div class="col">

                    <div class="card border-0 shadow-sm p-3">

                        <p class="text-muted mb-1">
                            Barang Ditemukan
                        </p>

                        <h3 class="fw-bold mb-0">

                            <?= $totalDitemukan; ?>

                        </h3>

                    </div>

                </div>


                <!-- KLAIM SAYA -->

                <div class="col">

                    <div class="card border-0 shadow-sm p-3">

                        <p class="text-muted mb-1">
                            Klaim Saya
                        </p>

                        <h3 class="fw-bold mb-0">

                            <?= $totalKlaim; ?>

                        </h3>

                    </div>

                </div>


                <!-- DIKEMBALIKAN -->

                <div class="col">

                    <div class="card border-0 shadow-sm p-3">

                        <p class="text-muted mb-1">
                            Dikembalikan
                        </p>

                        <h3 class="fw-bold mb-0">

                            <?= $totalDikembalikan; ?>

                        </h3>

                    </div>

                </div>

            </div>

        </section>


        <!-- ================================== -->
        <!-- AKSI CEPAT -->
        <!-- ================================== -->

        <section class="mb-5">

            <div class="card border-0 shadow-sm p-4">

                <h5 class="mb-3">
                    Aksi Cepat
                </h5>

                <div class="d-flex gap-3 flex-wrap">

                    <a
                        href="lapor-hilang.php"
                        class="btn btn-danger"
                    >
                        Lapor Barang Hilang
                    </a>

                    <a
                        href="lapor-ditemukan.php"
                        class="btn btn-success"
                    >
                        Lapor Barang Ditemukan
                    </a>

                    <a
                        href="cari-barang.php"
                        class="btn btn-outline-primary"
                    >
                        Cari Barang
                    </a>

                </div>

            </div>

        </section>


        <!-- ================================== -->
        <!-- LAPORAN TERBARU -->
        <!-- ================================== -->

        <section class="result-section">

            <div class="result-header d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h2>
                        Laporan Terbaru
                    </h2>

                    <p class="text-muted">

                        Aktivitas laporan barang hilang dan ditemukan
                        yang telah diterbitkan.

                    </p>

                </div>

                <a
                    href="cari-barang.php"
                    class="text-decoration-none fw-semibold"
                >
                    Lihat Semua →
                </a>

            </div>


            <div class="card border-0 shadow-sm p-4">

                <div class="report-list">


                    <!-- JIKA BELUM ADA LAPORAN -->

                    <?php if (empty($laporanTerbaru)): ?>

                        <div class="text-center py-4">

                            <p class="text-muted mb-0">

                                Belum ada laporan barang yang diterbitkan.

                            </p>

                        </div>


                    <!-- JIKA ADA LAPORAN -->

                    <?php else: ?>

                        <?php foreach ($laporanTerbaru as $laporan): ?>

                            <div class="d-flex align-items-center gap-3 border-bottom py-3">


                                <!-- FOTO BARANG -->

                                <?php if (!empty($laporan['FOTO'])): ?>

                                    <img
                                        src="../<?= htmlspecialchars(
                                            ltrim($laporan['FOTO'], '/'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        alt="Foto barang"
                                        style="
                                            width: 75px;
                                            height: 75px;
                                            object-fit: cover;
                                            border-radius: 8px;
                                        "
                                    >

                                <?php else: ?>

                                    <div
                                        class="bg-light d-flex align-items-center justify-content-center"
                                        style="
                                            width: 75px;
                                            height: 75px;
                                            border-radius: 8px;
                                        "
                                    >

                                        <span class="text-muted">
                                            No Foto
                                        </span>

                                    </div>

                                <?php endif; ?>


                                <!-- INFORMASI BARANG -->

                                <div class="flex-grow-1">

                                    <h6 class="fw-bold mb-1">

                                        <?= htmlspecialchars(
                                            $laporan['JUDUL'] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </h6>


                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $laporan['NAMA_KATEGORI'] ?? 'Tanpa kategori',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        &nbsp;•&nbsp;

                                        <?= htmlspecialchars(
                                            $laporan['LOKASI'] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </small>


                                    <!-- JENIS BARANG -->

                                    <div class="mt-1">

                                        <?php if (
                                            strtolower(trim($laporan['TIPE'] ?? '')) === 'lost'
                                        ): ?>

                                            <span class="badge bg-danger">
                                                Barang Hilang
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-success">
                                                Barang Ditemukan
                                            </span>

                                        <?php endif; ?>


                                        <!-- STATUS -->

                                        <span class="badge bg-secondary">

                                            <?= htmlspecialchars(
                                                $laporan['STATUS'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </div>

                                </div>


                                <!-- TANGGAL -->

                                <div class="text-end">

                                    <small class="text-muted">

                                        <?php

                                        if (!empty($laporan['TANGGAL_KEJADIAN'])) {

                                            echo htmlspecialchars(
                                                date(
                                                    'd-m-Y',
                                                    strtotime($laporan['TANGGAL_KEJADIAN'])
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );

                                        }

                                        ?>

                                    </small>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    </main>


    <!-- JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
