<?php
// user/cari-barang.php

require_once '../config/koneksi.php';
require_once '../includes/functions.php';

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
// DATA PENGGUNA
// ==========================================

$namaUser = $_SESSION['nama'] ?? 'User';
$inisial = strtoupper(substr($namaUser, 0, 1));

// ==========================================
// PARAMETER PENCARIAN DAN FILTER
// ==========================================

$keyword  = trim($_GET['keyword'] ?? '');
$tipe     = $_GET['tipe'] ?? '';
$kategori = $_GET['kategori'] ?? '';

$categories = [];
$items = [];
$error = '';

// ==========================================
// FUNGSI LABEL TIPE BARANG
// ==========================================

function labelTipe($tipe)
{
    $tipe = strtolower(trim($tipe));

    if ($tipe === 'lost' || $tipe === 'hilang') {
        return 'Hilang';
    }

    if ($tipe === 'found' || $tipe === 'ditemukan') {
        return 'Ditemukan';
    }

    return ucfirst($tipe);
}

// ==========================================
// MENGAMBIL DATA DARI ORACLE
// ==========================================

try {

    // Mengambil kategori
    $stmtCat = $conn->query("
        SELECT ID, NAMA_KATEGORI
        FROM CATEGORIES
        ORDER BY NAMA_KATEGORI ASC
    ");

    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // Query dasar barang yang sudah diterbitkan
    $sql = "
        SELECT
            I.ID,
            I.JUDUL,
            I.DESKRIPSI,
            I.TIPE,
            I.LOKASI,
            I.TANGGAL_KEJADIAN,
            I.FOTO,
            I.STATUS,
            C.NAMA_KATEGORI
        FROM ITEMS I
        JOIN CATEGORIES C
            ON I.CATEGORIES_ID = C.ID
        WHERE UPPER(TRIM(I.STATUS)) = 'TERBIT'
    ";

    $params = [];

    // Filter pencarian
    if ($keyword !== '') {

        $sql .= "
            AND (
                LOWER(I.JUDUL) LIKE LOWER(:judul)
                OR LOWER(I.DESKRIPSI) LIKE LOWER(:deskripsi)
                OR LOWER(I.LOKASI) LIKE LOWER(:lokasi)
            )
        ";

        $kataKunci = '%' . $keyword . '%';

        $params[':judul'] = $kataKunci;
        $params[':deskripsi'] = $kataKunci;
        $params[':lokasi'] = $kataKunci;
    }

    // Filter tipe barang
    if ($tipe === 'hilang') {

        $sql .= "
            AND UPPER(TRIM(I.TIPE)) IN ('LOST', 'HILANG')
        ";

    } elseif ($tipe === 'ditemukan') {

        $sql .= "
            AND UPPER(TRIM(I.TIPE)) IN ('FOUND', 'DITEMUKAN')
        ";
    }

    // Filter kategori
    if ($kategori !== '') {

        $sql .= "
            AND I.CATEGORIES_ID = :kategori
        ";

        $params[':kategori'] = $kategori;
    }

    // Urutan data terbaru
    $sql .= " ORDER BY I.CREATED_AT DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log('Cari Barang FoundIt: ' . $e->getMessage());

    $error = 'Terjadi kesalahan saat mengambil data barang dari database.';
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cari Barang - FoundIt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../css/main.css">

    <style>
        .barang-card {
            display: flex !important;
            flex-direction: column !important;
            height: 100% !important;
        }

        .barang-content {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
        }

        .barang-content .category {
            align-self: flex-start !important;
        }

        .barang-actions {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            margin-top: auto !important;
            padding-top: 16px !important;
        }

        .barang-actions a {
            flex: 1 1 0 !important;
            width: auto !important;
            height: 40px !important;
            min-height: 40px !important;
            max-height: 40px !important;
            padding: 8px 14px !important;
            margin-top: 0 !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
            text-decoration: none !important;
            box-sizing: border-box !important;
        }

        .barang-actions .btn-detail {
            background-color: #ffffff !important;
            color: #3498db !important;
            border: 1px solid #3498db !important;
        }

        .barang-actions .btn-detail:hover {
            background-color: #3498db !important;
            color: #ffffff !important;
        }

        .barang-actions .btn-klaim {
            background-color: #3498db !important;
            color: #ffffff !important;
            border: 1px solid #3498db !important;
        }

        .barang-actions .btn-klaim:hover {
            background-color: #2980b9 !important;
            border-color: #2980b9 !important;
            color: #ffffff !important;
        }
    </style>

</head>

<body>

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            <span>Found</span>It
        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="cari-barang.php" class="active">
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

            <a href="profil.php">
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


    <!-- KONTEN UTAMA -->

    <main class="main-content">

        <!-- HEADER -->

        <header class="topbar">

            <div>

                <h1>Daftar Barang</h1>

                <p>
                    Jelajahi laporan barang hilang dan ditemukan di kampus.
                </p>

            </div>

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

                    <small>User</small>

                </div>

            </div>

        </header>


        <!-- PENCARIAN DAN FILTER -->

        <section class="search-box">

            <div class="search-title">

                <h2>Cari Barang</h2>

                <p>
                    Temukan barang berdasarkan nama, status, atau kategori.
                </p>

            </div>

            <form
                action="cari-barang.php"
                method="GET"
                class="search-filter-wrapper"
            >

                <!-- INPUT PENCARIAN -->

                <div class="search-input-wrapper">

                    <input
                        type="text"
                        name="keyword"
                        placeholder="Cari nama barang..."
                        value="<?= htmlspecialchars(
                            $keyword,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                        class="form-control"
                    >

                </div>


                <!-- FILTER TIPE -->

                <select
                    name="tipe"
                    class="filter-select form-select"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="hilang"
                        <?= $tipe === 'hilang' ? 'selected' : ''; ?>
                    >
                        Hilang
                    </option>

                    <option
                        value="ditemukan"
                        <?= $tipe === 'ditemukan' ? 'selected' : ''; ?>
                    >
                        Ditemukan
                    </option>

                </select>


                <!-- FILTER KATEGORI -->

                <select
                    name="kategori"
                    class="filter-select form-select"
                >

                    <option value="">
                        Semua Kategori
                    </option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $cat['ID'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            <?= $kategori === $cat['ID'] ? 'selected' : ''; ?>
                        >

                            <?= htmlspecialchars(
                                $cat['NAMA_KATEGORI'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <!-- TOMBOL CARI -->

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Cari
                </button>


                <!-- TOMBOL RESET -->

                <a
                    href="cari-barang.php"
                    class="btn btn-outline-secondary"
                >
                    Reset
                </a>

            </form>

        </section>


        <!-- HASIL PENCARIAN -->

        <section class="result-section">

            <div class="result-header">

                <div>

                    <h2>Daftar Barang</h2>

                    <p>
                        Menampilkan <?= count($items); ?> barang.
                    </p>

                </div>

            </div>


            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>


            <?php elseif (count($items) > 0): ?>

                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

                    <?php foreach ($items as $item): ?>

                        <?php

                        $label = labelTipe($item['TIPE']);

                        $isFound = ($label === 'Ditemukan');

                        // Menentukan alamat gambar dan fallback jika file tidak tersedia
                        $fotoPath = foundit_image_path($item['FOTO'] ?? '');

                        ?>

                        <div class="col">

                            <div class="barang-card">


                                <!-- GAMBAR BARANG -->

                                <div class="barang-image">

                                    <img
                                        src="<?= htmlspecialchars(
                                            $fotoPath,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        alt="<?= htmlspecialchars(
                                            $item['JUDUL'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        loading="lazy"
                                        onerror="this.onerror=null; this.src='../uploads/no-image.svg';"
                                    >

                                </div>


                                <!-- INFORMASI BARANG -->

                                <div class="barang-content">

                                    <span class="status-badge <?= $isFound ? 'ditemukan' : 'hilang'; ?>">

                                        <?= htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </span>


                                    <h3>

                                        <?= htmlspecialchars(
                                            $item['JUDUL'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </h3>


                                    <p>

                                        <?= htmlspecialchars(
                                            $item['DESKRIPSI'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </p>


                                    <p>

                                        <strong>Lokasi:</strong>

                                        <?= htmlspecialchars(
                                            $item['LOKASI'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </p>


                                    <span class="category">

                                        <?= htmlspecialchars(
                                            $item['NAMA_KATEGORI'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </span>


                                    <!-- TOMBOL AKSI -->

                                    <div class="barang-actions">

                                        <a
                                            href="detail-barang.php?id=<?= urlencode($item['ID']); ?>"
                                            class="btn-detail"
                                        >
                                            Lihat Detail
                                        </a>


                                        <?php if ($isFound): ?>

                                            <a
                                                href="klaim.php?id=<?= urlencode($item['ID']); ?>"
                                                class="btn-klaim"
                                            >
                                                Klaim
                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>

                <div class="no-result">

                    <h3>Barang tidak ditemukan</h3>

                    <p>
                        Coba gunakan kata kunci atau filter yang berbeda.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>


    <!-- JAVASCRIPT BOOTSTRAP -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>