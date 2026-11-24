<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

// Status login pengguna
$isLoggedIn = is_logged_in();
$userRole   = $_SESSION['role'] ?? 'user';
$userName   = $_SESSION['nama'] ?? 'User';

// Parameter pencarian & filter dari GET
$keyword  = trim($_GET['keyword'] ?? '');
$tipe     = strtolower(trim($_GET['tipe'] ?? ''));
$kategori = trim($_GET['kategori'] ?? '');

$categories = [];
$items = [];
$dbError = '';

// Fungsi label tipe barang (sinkron dengan user/cari-barang.php)
if (!function_exists('labelTipe')) {
    function labelTipe($tipe) {
        $tipe = strtolower(trim((string)$tipe));
        if ($tipe === 'lost' || $tipe === 'hilang') {
            return 'Hilang';
        }
        if ($tipe === 'found' || $tipe === 'ditemukan') {
            return 'Ditemukan';
        }
        return ucfirst($tipe);
    }
}

// Format tanggal ke bahasa Indonesia
if (!function_exists('format_tanggal_indo')) {
    function format_tanggal_indo($tgl) {
        if (empty($tgl)) return '-';
        $timestamp = strtotime($tgl);
        if (!$timestamp) return '-';
        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $d = date('j', $timestamp);
        $m = (int)date('n', $timestamp);
        $y = date('Y', $timestamp);
        return "$d " . ($bulanIndo[$m] ?? date('M', $timestamp)) . " $y";
    }
}

try {
    // 1. Mengambil data kategori untuk dropdown (sinkron dengan user/cari-barang.php)
    $stmtCat = $conn->query("
        SELECT ID, NAMA_KATEGORI
        FROM CATEGORIES
        ORDER BY NAMA_KATEGORI ASC
    ");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // 2. Query data barang berstatus Terbit (sinkron persis dengan user/cari-barang.php)
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
            I.CATEGORIES_ID,
            C.NAMA_KATEGORI
        FROM ITEMS I
        JOIN CATEGORIES C
            ON I.CATEGORIES_ID = C.ID
        WHERE UPPER(TRIM(I.STATUS)) = 'TERBIT'
    ";

    $params = [];

    // Filter pencarian kata kunci (nama, deskripsi, lokasi)
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

    // Filter tipe status (Hilang vs Ditemukan)
    if ($tipe === 'hilang' || $tipe === 'lost') {
        $sql .= " AND UPPER(TRIM(I.TIPE)) IN ('LOST', 'HILANG')";
    } elseif ($tipe === 'ditemukan' || $tipe === 'found') {
        $sql .= " AND UPPER(TRIM(I.TIPE)) IN ('FOUND', 'DITEMUKAN')";
    }

    // Filter kategori
    if ($kategori !== '') {
        $sql .= " AND (I.CATEGORIES_ID = :kategori OR LOWER(C.NAMA_KATEGORI) = LOWER(:kategoriNama))";
        $params[':kategori'] = $kategori;
        $params[':kategoriNama'] = $kategori;
    }

    // Urutan data terbaru
    $sql .= " ORDER BY I.CREATED_AT DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rawItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalisasi kunci array (case-insensitivity untuk Oracle dan MySQL)
    foreach ($rawItems as $row) {
        $itemNorm = [];
        foreach ($row as $k => $v) {
            $itemNorm[strtoupper($k)] = $v;
        }
        $items[] = $itemNorm;
    }

} catch (PDOException $e) {
    error_log('FoundIt Index DB Error: ' . $e->getMessage());
    $dbError = 'Terjadi kesalahan saat memuat data barang dari database.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoundIt - Platform Barang Hilang Kampus</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS (dengan cache buster) -->
    <link rel="stylesheet" href="css/main.css?v=<?= filemtime(__DIR__ . '/css/main.css') ?? time(); ?>">

    <!-- STYLING INLINE UNTUK MEMASTIKAN TAMPILAN 100% KONSISTEN TANPA MASALAH CACHE BROWSER -->
    <style>
        /* ========================================
           NAVBAR (SLIM, RAMPING, SEJAJAR KONTEN)
        ======================================== */
        .navbar {
            background-color: #ffffff !important;
            height: 70px !important;
            min-height: 70px !important;
            padding: 0 !important;
            margin: 0 !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            box-shadow: 0 2px 14px rgba(0, 0, 0, 0.05) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
        }

        .navbar-container,
        .navbar .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding-left: 32px !important;
            padding-right: 32px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            box-sizing: border-box !important;
            height: 100% !important;
        }

        .container {
            max-width: 1200px !important;
            padding-left: 24px !important;
            padding-right: 24px !important;
        }

        .navbar-brand,
        .navbar .logo {
            font-size: 24px !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            letter-spacing: -0.5px !important;
            text-decoration: none !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
            display: inline-flex !important;
            align-items: center !important;
            flex-shrink: 0 !important;
        }

        .navbar .logo span {
            color: #3498db !important;
        }

        .navbar-collapse {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            flex-grow: 1 !important;
            margin-left: auto !important;
            padding: 0 !important;
        }

        .navbar-nav {
            display: flex !important;
            align-items: center !important;
            gap: 28px !important;
            margin-left: auto !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            margin-right: 0 !important;
            padding: 0 !important;
            list-style: none !important;
        }

        .navbar-nav .nav-item {
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
        }

        .navbar .nav-link {
            color: #475569 !important;
            font-size: 15px !important;
            font-weight: 500 !important;
            padding: 6px 0 !important;
            border-radius: 6px !important;
            line-height: 1.5 !important;
            margin: 0 !important;
            transition: color 0.2s ease !important;
            white-space: nowrap !important;
        }

        .navbar .nav-link:hover {
            color: #3498db !important;
            background-color: transparent !important;
        }

        .btn-login {
            background-color: #3498db !important;
            color: #ffffff !important;
            padding: 8px 22px !important;
            border-radius: 25px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            height: 40px !important;
            line-height: 1 !important;
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.25) !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            margin: 0 !important;
            border: none !important;
            white-space: nowrap !important;
        }

        .btn-login:hover {
            background-color: #2980b9 !important;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4) !important;
            transform: translateY(-1px) !important;
            color: #ffffff !important;
        }

        /* ========================================
           HERO SECTION (VERTICAL RHYTHM DISESUAIKAN)
        ======================================== */
        .hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 45%, #1e3a8a 100%) !important;
            color: #ffffff !important;
            padding: 65px 0 85px !important;
            min-height: auto !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .hero::after {
            content: '' !important;
            position: absolute !important;
            top: -40% !important;
            right: -15% !important;
            width: 550px !important;
            height: 550px !important;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%) !important;
            pointer-events: none !important;
        }

        .hero-text {
            max-width: 780px !important;
            text-align: center !important;
            margin: 0 auto !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .hero-badge {
            display: inline-block !important;
            background: rgba(255, 255, 255, 0.12) !important;
            backdrop-filter: blur(8px) !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            color: #bae6fd !important;
            padding: 6px 18px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            letter-spacing: 1.5px !important;
            margin-bottom: 22px !important;
        }

        .hero h1 {
            font-size: 52px !important;
            font-weight: 800 !important;
            line-height: 1.15 !important;
            margin-bottom: 22px !important;
            color: #ffffff !important;
            letter-spacing: -0.5px !important;
        }

        .hero h1 span {
            color: #38bdf8 !important;
        }

        .hero p {
            font-size: 17px !important;
            line-height: 1.75 !important;
            color: #cbd5e1 !important;
            max-width: 660px !important;
            margin: 0 auto 34px !important;
        }

        .hero-button {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 14px !important;
            flex-wrap: wrap !important;
            margin-top: 10px !important;
        }

        .btn-utama {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: #38bdf8 !important;
            color: #0f172a !important;
            padding: 13px 32px !important;
            border-radius: 30px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            border: none !important;
            box-shadow: 0 4px 16px rgba(56, 189, 248, 0.35) !important;
            transition: all 0.25s ease !important;
        }

        .btn-utama:hover {
            background-color: #7dd3fc !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.5) !important;
            color: #0f172a !important;
        }

        .btn-outline {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 2px solid rgba(255, 255, 255, 0.85) !important;
            color: #ffffff !important;
            padding: 11px 30px !important;
            border-radius: 30px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            background: transparent !important;
            text-decoration: none !important;
            transition: all 0.25s ease !important;
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-color: #ffffff !important;
            transform: translateY(-2px) !important;
            color: #ffffff !important;
        }

        /* ========================================
           3 KARTU FITUR
        ======================================== */
        .fitur {
            margin-top: -55px !important;
            margin-bottom: 60px !important;
            position: relative !important;
            z-index: 10 !important;
        }

        .fitur-box {
            background-color: #ffffff !important;
            padding: 32px 26px !important;
            text-align: center !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid #f1f5f9 !important;
            transition: all 0.25s ease !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
        }

        .fitur-box:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.12) !important;
            border-color: #e2e8f0 !important;
        }

        .ikon-circle {
            width: 64px !important;
            height: 64px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-bottom: 18px !important;
            transition: transform 0.25s ease !important;
        }

        .fitur-box:hover .ikon-circle {
            transform: scale(1.08) !important;
        }

        .ikon-blue {
            background-color: #e0f2fe !important;
            color: #0284c7 !important;
        }

        .ikon-amber {
            background-color: #fef3c7 !important;
            color: #d97706 !important;
        }

        .ikon-emerald {
            background-color: #dcfce7 !important;
            color: #16a34a !important;
        }

        .fitur-box h3 {
            font-size: 19px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            margin-bottom: 8px !important;
        }

        .fitur-box p {
            color: #64748b !important;
            font-size: 14px !important;
            line-height: 1.6 !important;
            margin: 0 !important;
        }

        /* ========================================
           SEARCH & FILTER BAR (SEGARIS, RATA, SIMETRIS)
        ======================================== */
        #barang {
            background-color: #f8fafc !important;
            padding: 80px 0 !important;
        }

        .filter {
            background-color: #ffffff !important;
            padding: 18px 24px !important;
            margin: 32px 0 36px !important;
            border-radius: 14px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #eef2f6 !important;
        }

        .filter-bar {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            width: 100% !important;
            margin: 0 !important;
        }

        .filter-search {
            position: relative !important;
            flex: 2.5 !important;
            min-width: 240px !important;
            display: flex !important;
            align-items: center !important;
            height: 44px !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        .filter-search .search-icon {
            position: absolute !important;
            left: 14px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #94a3b8 !important;
            pointer-events: none !important;
            z-index: 3 !important;
            width: 18px !important;
            height: 18px !important;
        }

        .filter-search input {
            width: 100% !important;
            height: 44px !important;
            padding: 0 38px 0 42px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            font-family: inherit !important;
            font-size: 14px !important;
            color: #1e293b !important;
            background-color: #f8fafc !important;
            box-shadow: none !important;
            outline: none !important;
            transition: all 0.2s ease !important;
            box-sizing: border-box !important;
        }

        .filter-search input:focus {
            background-color: #ffffff !important;
            border-color: #3498db !important;
            box-shadow: 0 0 0 3.5px rgba(52, 152, 219, 0.15) !important;
        }

        .filter-search input::placeholder {
            color: #94a3b8 !important;
        }

        .filter-search .clear-search {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 24px !important;
            height: 24px !important;
            padding: 0 !important;
            border: none !important;
            border-radius: 50% !important;
            background: transparent !important;
            color: #94a3b8 !important;
            font-size: 20px !important;
            line-height: 1 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 3 !important;
            transition: all 0.2s ease !important;
        }

        .filter-search .clear-search:hover {
            background-color: #e2e8f0 !important;
            color: #334155 !important;
        }

        .filter-search .clear-search[hidden] {
            display: none !important;
        }

        .filter-select-wrapper {
            flex: 1.2 !important;
            min-width: 170px !important;
            height: 44px !important;
            position: relative !important;
        }

        .filter-select-wrapper select {
            width: 100% !important;
            height: 44px !important;
            padding: 0 36px 0 14px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            font-family: inherit !important;
            font-size: 14px !important;
            color: #1e293b !important;
            background-color: #f8fafc !important;
            cursor: pointer !important;
            outline: none !important;
            box-shadow: none !important;
            transition: all 0.2s ease !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 14px center !important;
            box-sizing: border-box !important;
        }

        .filter-select-wrapper select:focus {
            background-color: #ffffff !important;
            border-color: #3498db !important;
            box-shadow: 0 0 0 3.5px rgba(52, 152, 219, 0.15) !important;
        }

        .filter-btn-wrapper {
            flex-shrink: 0 !important;
            height: 44px !important;
        }

        .btn-reset-filter {
            height: 44px !important;
            min-height: 44px !important;
            padding: 0 22px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 10px !important;
            background-color: #ffffff !important;
            color: #475569 !important;
            font-family: inherit !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s ease !important;
            box-sizing: border-box !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        }

        .btn-reset-filter:hover {
            background-color: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #1e293b !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.07) !important;
        }

        .btn-reset-filter:active {
            transform: scale(0.98) !important;
        }

        /* ========================================
           GRID KARTU BARANG & DESAIN KARTU
        ======================================== */
        .grid-barang {
            align-items: stretch !important;
        }

        .grid-barang .col {
            display: flex;
        }

        .grid-barang .col.filter-hidden,
        .grid-barang .col.d-none {
            display: none !important;
        }

        .grid-barang .card {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            height: 100% !important;
            background-color: #ffffff !important;
            border-radius: 14px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06) !important;
            transition: transform 0.25s ease, box-shadow 0.25s ease !important;
            border: 1px solid #eef2f6 !important;
        }

        .grid-barang .card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1) !important;
        }

        .gambar-barang {
            width: 100% !important;
            height: 200px !important;
            min-height: 200px !important;
            max-height: 200px !important;
            overflow: hidden !important;
            background-color: #f1f5f9 !important;
            position: relative !important;
        }

        .gambar-barang img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important;
            transition: transform 0.35s ease !important;
        }

        .grid-barang .card:hover .gambar-barang img {
            transform: scale(1.04) !important;
        }

        .grid-barang .card-body {
            padding: 22px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            flex: 1 1 auto !important;
            height: 100% !important;
            box-sizing: border-box !important;
        }

        .card-content-top {
            display: flex !important;
            flex-direction: column !important;
            margin-bottom: 16px !important;
        }

        .card-meta-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 8px !important;
            margin-bottom: 12px !important;
        }

        .grid-barang .card-body h3 {
            font-size: 18px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            margin: 0 0 10px 0 !important;
            line-height: 1.4 !important;
        }

        .card-info-item {
            display: flex !important;
            align-items: center !important;
            gap: 7px !important;
            color: #64748b !important;
            font-size: 13px !important;
            margin: 4px 0 !important;
            line-height: 1.4 !important;
        }

        .card-info-item svg {
            flex-shrink: 0 !important;
            color: #94a3b8 !important;
        }

        .kategori {
            display: inline-block !important;
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            padding: 4px 10px !important;
            border-radius: 6px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
        }

        .badge {
            display: inline-block !important;
            padding: 5px 12px !important;
            border-radius: 20px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 0.3px !important;
        }

        .hilang {
            background-color: #fee2e2 !important;
            color: #dc2626 !important;
            border: 1px solid #fecaca !important;
        }

        .ditemukan {
            background-color: #dcfce7 !important;
            color: #16a34a !important;
            border: 1px solid #bbf7d0 !important;
        }

        /* TOMBOL AKSI KARTU */
        .card-actions {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin-top: auto !important;
            padding-top: 14px !important;
            border-top: 1px solid #f1f5f9 !important;
            width: 100% !important;
        }

        .card-actions a,
        .card-actions button {
            flex: 1 1 0 !important;
            width: 100% !important;
            min-height: 40px !important;
            height: 40px !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
            box-sizing: border-box !important;
            white-space: nowrap !important;
            transition: all 0.2s ease !important;
        }

        .btn-card-detail {
            background-color: #ffffff !important;
            color: #3498db !important;
            border: 1.5px solid #3498db !important;
        }

        .btn-card-detail:hover {
            background-color: #3498db !important;
            color: #ffffff !important;
        }

        .btn-card-klaim {
            background-color: #3498db !important;
            color: #ffffff !important;
            border: 1.5px solid #3498db !important;
        }

        .btn-card-klaim:hover {
            background-color: #2980b9 !important;
            border-color: #2980b9 !important;
            color: #ffffff !important;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center !important;
            padding: 55px 20px !important;
            background-color: #ffffff !important;
            border-radius: 16px !important;
            border: 1.5px dashed #cbd5e1 !important;
            margin: 30px 0 !important;
        }

        .empty-state-icon {
            margin-bottom: 14px !important;
        }

        .empty-state h3 {
            font-size: 19px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            margin-bottom: 8px !important;
        }

        .empty-state p {
            color: #64748b !important;
            font-size: 14px !important;
            margin: 0 !important;
        }

        /* RESPONSIVE LAYOUT */
        @media (max-width: 991px) {
            .navbar {
                height: auto !important;
                min-height: 70px !important;
                padding: 12px 0 !important;
            }
            .navbar-container,
            .navbar .container-fluid {
                flex-wrap: wrap !important;
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            .navbar-collapse {
                width: 100% !important;
                background-color: #ffffff !important;
                padding: 16px 0 !important;
                border-top: 1px solid #f1f5f9 !important;
                margin-top: 12px !important;
            }
            .navbar-collapse:not(.show) {
                display: none !important;
            }
            .navbar-collapse.show {
                display: flex !important;
            }
            .navbar-nav {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 12px !important;
                width: 100% !important;
                margin-left: 0 !important;
            }
            .navbar-nav .nav-item {
                width: 100% !important;
            }
            .navbar-nav .nav-link {
                width: 100% !important;
                padding: 8px 12px !important;
            }
            .navbar-nav .btn-login {
                width: 100% !important;
                justify-content: center !important;
                margin-top: 8px !important;
            }
            .hero {
                padding: 70px 0 90px !important;
            }
            .hero h1 {
                font-size: 38px !important;
            }
            .hero p {
                font-size: 15px !important;
            }
            .filter-bar {
                flex-wrap: wrap !important;
                gap: 12px !important;
            }
            .filter-search {
                flex: 1 1 100% !important;
                min-width: 100% !important;
            }
            .filter-select-wrapper {
                flex: 1 1 calc(50% - 6px) !important;
                min-width: calc(50% - 6px) !important;
            }
            .filter-btn-wrapper {
                flex: 1 1 100% !important;
                min-width: 100% !important;
            }
            .btn-reset-filter {
                width: 100% !important;
            }
        }

        @media (max-width: 575px) {
            .filter {
                padding: 14px 16px !important;
            }
            .filter-select-wrapper {
                flex: 1 1 100% !important;
                min-width: 100% !important;
            }
            .hero-button {
                flex-direction: column !important;
                width: 100% !important;
            }
            .btn-utama, .btn-outline {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid navbar-container">
            <a class="navbar-brand logo" href="index.php">
                <span>Found</span>It
            </a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMenu"
                    aria-controls="navMenu" aria-expanded="false"
                    aria-label="Buka menu navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#barang">Cari Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#cara-kerja">Cara Kerja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tentang">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <?php if ($isLoggedIn): ?>
                            <?php $dashboardUrl = ($userRole === 'admin') ? 'admin/admin-dashboard.php' : 'user/dashboard.php'; ?>
                            <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-login" title="Masuk ke Dashboard">
                                Dashboard (<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>)
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn-login">Login</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- BAGIAN BERANDA (HERO SECTION) -->
    <section class="hero" id="beranda">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 hero-text">
                    <span class="hero-badge">PLATFORM BARANG HILANG KAMPUS</span>

                    <h1>
                        Barang Hilang?<br>
                        <span>Temukan Kembali.</span>
                    </h1>

                    <p>
                        FoundIt membantu mahasiswa dan seluruh civitas kampus
                        melaporkan, mencari, dan mengklaim barang yang hilang
                        di lingkungan kampus secara terpusat, cepat, dan transparan.
                    </p>

                    <div class="hero-button">
                        <a href="#barang" class="btn-utama">Cari Barang</a>
                        <?php if ($isLoggedIn): ?>
                            <a href="user/lapor-hilang.php" class="btn-outline">Lapor Barang</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-outline">Lapor Barang</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3 KARTU FITUR UTAMA -->
    <section class="fitur container">
        <div class="row g-4">

            <!-- FITUR 1: CARI BARANG -->
            <div class="col-md-4">
                <div class="fitur-box">
                    <div class="ikon-circle ikon-blue">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <h3>Cari Barang</h3>
                    <p>
                        Cari barang hilang atau ditemukan berdasarkan nama, kategori,
                        atau lokasi dengan filter instan yang akurat.
                    </p>
                </div>
            </div>

            <!-- FITUR 2: BUAT LAPORAN -->
            <div class="col-md-4">
                <div class="fitur-box">
                    <div class="ikon-circle ikon-amber">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <h3>Buat Laporan</h3>
                    <p>
                        Laporkan barang yang hilang atau barang yang kamu temukan di
                        seluruh area kampus dengan unggahan foto.
                    </p>
                </div>
            </div>

            <!-- FITUR 3: AJUKAN KLAIM -->
            <div class="col-md-4">
                <div class="fitur-box">
                    <div class="ikon-circle ikon-emerald">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                    </div>
                    <h3>Ajukan Klaim</h3>
                    <p>
                        Ajukan bukti kepemilikan dan pantau status verifikasi barang
                        hingga barang berhasil kembali ke tanganmu.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- DAFTAR BARANG -->
    <section id="barang">
        <div class="container">

            <div class="judul-section text-center mb-4">
                <span class="label-biru">BARANG TERBARU</span>
                <h2>Barang Hilang &amp; Ditemukan</h2>
                <p>
                    Telusuri laporan barang yang hilang maupun ditemukan dari civitas kampus.
                </p>
            </div>

            <?php if (!empty($dbError)): ?>
                <div class="alert alert-danger my-3" role="alert">
                    <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- CONTAINER SEARCH & FILTER (SEBARIS HORIZONTAL, RAPI & SIMETRIS) -->
            <div class="filter">
                <form id="filterForm" class="filter-bar" method="GET" action="index.php#barang">

                    <!-- Input Pencarian Nama Barang -->
                    <div class="filter-search">
                        <span class="search-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="18" height="18"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="searchInput"
                            name="keyword"
                            placeholder="Cari nama barang..."
                            aria-label="Cari nama barang"
                            autocomplete="off"
                            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                        >

                        <button
                            type="button"
                            id="clearSearch"
                            class="clear-search"
                            aria-label="Hapus kata pencarian"
                            title="Hapus pencarian"
                            <?= empty($keyword) ? 'hidden' : ''; ?>
                        >&times;</button>
                    </div>

                    <!-- Filter Dropdown Status -->
                    <div class="filter-select-wrapper">
                        <select id="selectStatus" name="tipe" aria-label="Filter status barang" onchange="if (typeof filterBarang === 'function') { filterBarang(); }">
                            <option value="">Semua Status</option>
                            <option value="hilang" <?= ($tipe === 'hilang' || $tipe === 'lost') ? 'selected' : ''; ?>>Hilang</option>
                            <option value="ditemukan" <?= ($tipe === 'ditemukan' || $tipe === 'found') ? 'selected' : ''; ?>>Ditemukan</option>
                        </select>
                    </div>

                    <!-- Filter Dropdown Kategori (Dinamis dari Database) -->
                    <div class="filter-select-wrapper">
                        <select id="selectKategori" name="kategori" aria-label="Filter kategori barang" onchange="if (typeof filterBarang === 'function') { filterBarang(); }">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                $catId   = $cat['ID'] ?? $cat['id'] ?? '';
                                $catNama = $cat['NAMA_KATEGORI'] ?? $cat['nama_kategori'] ?? '';
                                ?>
                                <option value="<?= htmlspecialchars($catId, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nama="<?= htmlspecialchars(strtolower($catNama), ENT_QUOTES, 'UTF-8'); ?>"
                                    <?= ($kategori === $catId || strtolower($kategori) === strtolower($catNama)) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($catNama, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tombol Reset Filter -->
                    <div class="filter-btn-wrapper">
                        <button type="button" id="resetFilter" class="btn-reset-filter" title="Reset filter">
                            Reset
                        </button>
                    </div>

                </form>
            </div>

            <!-- DAFTAR KARTU BARANG (DINAMIS SINKRON DENGAN USER CARI BARANG) -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 grid-barang" id="gridBarang">

                <?php foreach ($items as $item): ?>
                    <?php
                    $itemId        = $item['ID'] ?? '';
                    $itemJudul     = $item['JUDUL'] ?? 'Tanpa Judul';
                    $labelStatus   = labelTipe($item['TIPE'] ?? '');
                    $isFound       = ($labelStatus === 'Ditemukan');
                    $itemLokasi    = $item['LOKASI'] ?? 'Lokasi tidak disebutkan';
                    $itemTanggal   = format_tanggal_indo($item['TANGGAL_KEJADIAN'] ?? '');
                    $kategoriNama  = $item['NAMA_KATEGORI'] ?? 'Lainnya';
                    $kategoriId    = $item['CATEGORIES_ID'] ?? '';

                    // Menentukan path foto barang persis dengan fungsi katalog user
                    $fotoUrl = foundit_image_path($item['FOTO'] ?? '', 'uploads/no-image.svg', '');
                    ?>

                    <div class="col"
                         data-name="<?= htmlspecialchars(strtolower($itemJudul), ENT_QUOTES, 'UTF-8'); ?>"
                         data-status="<?= $isFound ? 'ditemukan' : 'hilang'; ?>"
                         data-kategori="<?= htmlspecialchars(strtolower($kategoriNama), ENT_QUOTES, 'UTF-8'); ?>"
                         data-kategori-id="<?= htmlspecialchars(strtolower($kategoriId), ENT_QUOTES, 'UTF-8'); ?>"
                         data-kategori-name="<?= htmlspecialchars(strtolower($kategoriNama), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="card">
                            <!-- Thumbnail Foto Barang (Aspek Rasio Seragam 200px Cover) -->
                            <div class="gambar-barang">
                                <img
                                    src="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?= htmlspecialchars($itemJudul, ENT_QUOTES, 'UTF-8'); ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.src='uploads/no-image.svg';"
                                >
                            </div>

                            <!-- Body Kartu Barang -->
                            <div class="card-body">
                                <div class="card-content-top">
                                    <div class="card-meta-header">
                                        <span class="badge <?= $isFound ? 'ditemukan' : 'hilang'; ?>">
                                            <?= htmlspecialchars($labelStatus, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <span class="kategori">
                                            <?= htmlspecialchars($kategoriNama, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>

                                    <h3 title="<?= htmlspecialchars($itemJudul, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($itemJudul, ENT_QUOTES, 'UTF-8'); ?>
                                    </h3>

                                    <p class="card-info-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span><?= htmlspecialchars($itemLokasi, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </p>

                                    <p class="card-info-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span><?= htmlspecialchars($itemTanggal, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </p>
                                </div>

                                <!-- Tombol Aksi (Selalu Rata Sejajar di Bawah) -->
                                <div class="card-actions">
                                    <a href="user/detail-barang.php?id=<?= urlencode($itemId); ?>" class="btn-card-detail">
                                        Lihat Detail
                                    </a>

                                    <?php if ($isFound): ?>
                                        <?php if ($isLoggedIn): ?>
                                            <a href="user/klaim.php?id=<?= urlencode($itemId); ?>" class="btn-card-klaim">
                                                Ajukan Klaim
                                            </a>
                                        <?php else: ?>
                                            <a href="login.php" class="btn-card-klaim" title="Login untuk mengajukan klaim">
                                                Ajukan Klaim
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>

            </div>

            <!-- PESAN JIKA BARANG TIDAK DITEMUKAN / EMPTY STATE -->
            <div id="pesanKosong" class="empty-state" style="<?= count($items) === 0 ? 'display: block;' : 'display: none;'; ?>" role="status" aria-live="polite">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                </div>
                <h3>Barang tidak ditemukan</h3>
                <p>Tidak ada laporan barang yang sesuai dengan kata kunci atau filter yang Anda pilih.</p>
            </div>

        </div>
    </section>

    <!-- CARA KERJA -->
    <section class="cara-kerja py-5 bg-white" id="cara-kerja">
        <div class="container py-4">

            <div class="judul-section tengah text-center mb-5">
                <span class="label-biru">CARA KERJA</span>
                <h2>Menemukan Barang Jadi Lebih Mudah</h2>
                <p>
                    FoundIt menghubungkan seluruh civitas kampus
                    agar proses pelaporan, pencarian, dan klaim barang menjadi lebih cepat dan terpercaya.
                </p>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 langkah">

                <div class="col">
                    <div class="langkah-box">
                        <span>01</span>
                        <h3>Laporkan</h3>
                        <p>
                            Buat laporan jika kamu kehilangan barang
                            atau menemukan barang orang lain di kampus.
                        </p>
                    </div>
                </div>

                <div class="col">
                    <div class="langkah-box">
                        <span>02</span>
                        <h3>Cari</h3>
                        <p>
                            Cari barang berdasarkan nama,
                            kategori, lokasi kejadian, dan status.
                        </p>
                    </div>
                </div>

                <div class="col">
                    <div class="langkah-box">
                        <span>03</span>
                        <h3>Klaim</h3>
                        <p>
                            Ajukan klaim kepemilikan jika menemukan
                            barang yang sesuai milik Anda.
                        </p>
                    </div>
                </div>

                <div class="col">
                    <div class="langkah-box">
                        <span>04</span>
                        <h3>Selesai</h3>
                        <p>
                            Admin melakukan verifikasi bukti kepemilikan
                            sebelum barang diserahkan kembali.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- TENTANG FOUNDIT -->
    <section class="tentang container py-5" id="tentang">
        <div class="row align-items-center g-5 py-4">

            <div class="col-lg-8 tentang-text">
                <span class="label-biru">TENTANG FoundIt</span>
                <h2>Karena Barang Hilang Bisa Terjadi pada Siapa Saja</h2>

                <p>
                    Biasanya ketika ada mahasiswa yang kehilangan barang,
                    informasi tersebut hanya dibagikan melalui grup chat tertentu,
                    misalnya grup mahasiswa Teknik atau jurusan tertentu saja.
                </p>

                <p>
                    Namun bagaimana jika barang yang hilang milik mahasiswa fakultas lain?
                    Informasi tersebut sering kali terputus dan tidak sampai kepada pemilik yang sebenarnya.
                </p>

                <p>
                    Oleh karena itu, FoundIt hadir sebagai platform terintegrasi agar setiap civitas kampus
                    dapat saling membantu mengembalikan barang yang hilang dengan mudah dan aman.
                </p>
            </div>

            <div class="col-lg-4">
                <div class="tentang-box">
                    <div class="ikon-besar mb-3">🔍</div>
                    <h3 class="fw-bold mb-2">Satu Platform</h3>
                    <p class="text-muted">
                        Menghubungkan seluruh fakultas dan jurusan dalam mencari dan mengembalikan barang.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- AJAKAN UNTUK MELAPOR -->
    <section class="lapor" id="lapor">
        <h2>Kehilangan atau Menemukan Barang?</h2>
        <p>Bantu barang tersebut kembali kepada pemiliknya yang sah.</p>
        <?php if ($isLoggedIn): ?>
            <a href="user/lapor-hilang.php" class="btn-utama">Mulai Melapor</a>
        <?php else: ?>
            <a href="login.php" class="btn-utama">Mulai Melapor</a>
        <?php endif; ?>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">

                <div class="col-md-6">
                    <h2><span>Found</span>It</h2>
                    <p>
                        Platform barang hilang dan ditemukan
                        untuk seluruh civitas kampus.
                    </p>
                </div>

                <div class="col-md-3">
                    <h3>Menu</h3>
                    <p><a href="#beranda" class="text-secondary text-decoration-none">Beranda</a></p>
                    <p><a href="#barang" class="text-secondary text-decoration-none">Cari Barang</a></p>
                    <p><a href="#cara-kerja" class="text-secondary text-decoration-none">Cara Kerja</a></p>
                    <p><a href="#tentang" class="text-secondary text-decoration-none">Tentang</a></p>
                </div>

                <div class="col-md-3">
                    <h3>Akses</h3>
                    <?php if ($isLoggedIn): ?>
                        <p><a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'); ?>" class="text-secondary text-decoration-none">Dashboard</a></p>
                        <p><a href="logout.php" class="text-secondary text-decoration-none">Keluar</a></p>
                    <?php else: ?>
                        <p><a href="login.php" class="text-secondary text-decoration-none">Login Akun</a></p>
                        <p><a href="user/register.php" class="text-secondary text-decoration-none">Daftar Akun Baru</a></p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </footer>

    <!-- COPYRIGHT -->
    <div class="copyright">
        &copy; 2026 FoundIt. Platform Barang Hilang Kampus.
    </div>

    <!-- JAVASCRIPT BOOTSTRAP & SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js?v=<?= time(); ?>"></script>

    <!-- INLINE FILTER SCRIPT UNTUK MEMASTIKAN FITUR FILTER BERFUNGSI 100% BEBAS MASALAH CACHE BROWSER -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const filterForm = document.getElementById("filterForm");
        const resetFilter = document.getElementById("resetFilter");
        const searchInput = document.getElementById("searchInput");
        const clearSearch = document.getElementById("clearSearch");
        const selectStatus = document.getElementById("selectStatus");
        const selectKategori = document.getElementById("selectKategori");
        const pesanKosong = document.getElementById("pesanKosong");

        function filterBarang() {
            const currentSearchInput = document.getElementById("searchInput");
            const currentSelectStatus = document.getElementById("selectStatus");
            const currentSelectKategori = document.getElementById("selectKategori");
            const currentPesanKosong = document.getElementById("pesanKosong");
            const cols = document.querySelectorAll(".grid-barang .col");

            if (!currentSearchInput || !currentSelectStatus || !currentSelectKategori) return;

            const keyword = currentSearchInput.value.trim().toLowerCase();
            const statusRaw = currentSelectStatus.value.trim().toLowerCase();
            const kategoriRaw = currentSelectKategori.value.trim().toLowerCase();

            const selectedCatOpt = currentSelectKategori.options[currentSelectKategori.selectedIndex];
            const kategoriText = selectedCatOpt ? selectedCatOpt.text.trim().toLowerCase() : "";

            let jumlahDitemukan = 0;

            cols.forEach(function (col) {
                const card = col.querySelector(".card");
                if (!card) return;

                const namaBarang = (
                    col.getAttribute("data-name") ||
                    col.dataset.name ||
                    card.querySelector("h3")?.innerText ||
                    ""
                ).toLowerCase();

                const statusBarang = (
                    col.getAttribute("data-status") ||
                    col.dataset.status ||
                    card.querySelector(".badge-status, .badge")?.innerText ||
                    ""
                ).toLowerCase().trim();

                const kategoriAttr = (
                    col.getAttribute("data-kategori") ||
                    col.dataset.kategori ||
                    ""
                ).toLowerCase().trim();

                const kategoriId = (
                    col.getAttribute("data-kategori-id") ||
                    col.dataset.kategoriId ||
                    ""
                ).toLowerCase().trim();

                const kategoriNama = (
                    col.getAttribute("data-kategori-name") ||
                    col.dataset.kategoriName ||
                    card.querySelector(".kategori")?.innerText ||
                    ""
                ).toLowerCase().trim();

                /* 1. KECOCOKAN KATA KUNCI (NAMA BARANG / TEKS KARTU) */
                const cardAllText = (card.innerText || "").toLowerCase();
                const matchKeyword = (keyword === "" || namaBarang.includes(keyword) || cardAllText.includes(keyword));

                /* 2. KECOCOKAN STATUS (HILANG / DITEMUKAN) */
                let matchStatus = true;
                if (statusRaw !== "" && statusRaw !== "semua status") {
                    if (statusRaw === "hilang" || statusRaw === "lost") {
                        matchStatus = (statusBarang.includes("hilang") || statusBarang.includes("lost"));
                    } else if (statusRaw === "ditemukan" || statusRaw === "found") {
                        matchStatus = (statusBarang.includes("ditemukan") || statusBarang.includes("found"));
                    } else {
                        matchStatus = (statusBarang === statusRaw);
                    }
                }

                /* 3. KECOCOKAN KATEGORI (ID DAN NAMA) */
                let matchKategori = true;
                if (kategoriRaw !== "" && kategoriRaw !== "semua kategori") {
                    matchKategori = (
                        kategoriId === kategoriRaw ||
                        kategoriNama === kategoriRaw ||
                        kategoriAttr === kategoriRaw ||
                        kategoriNama === kategoriText ||
                        kategoriAttr === kategoriText ||
                        (kategoriText !== "" && (kategoriNama.includes(kategoriText) || kategoriAttr.includes(kategoriText)))
                    );
                }

                /* TAMPILKAN / SEMBUNYIKAN (DENGAN KELAS & DISPLAY !IMPORTANT) */
                if (matchKeyword && matchStatus && matchKategori) {
                    col.classList.remove("filter-hidden", "d-none");
                    col.style.setProperty("display", "flex", "important");
                    jumlahDitemukan++;
                } else {
                    col.classList.add("filter-hidden", "d-none");
                    col.style.setProperty("display", "none", "important");
                }
            });

            if (currentPesanKosong) {
                if (jumlahDitemukan === 0) {
                    currentPesanKosong.classList.remove("d-none");
                    currentPesanKosong.style.setProperty("display", "block", "important");
                } else {
                    currentPesanKosong.classList.add("d-none");
                    currentPesanKosong.style.setProperty("display", "none", "important");
                }
            }
        }

        window.filterBarang = filterBarang;

        let debounceTimer;
        function debounceFilter() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                filterBarang();
            }, 200);
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                if (clearSearch) {
                    clearSearch.hidden = searchInput.value.length === 0;
                }
                debounceFilter();
            });
        }

        if (clearSearch) {
            clearSearch.addEventListener("click", function () {
                clearTimeout(debounceTimer);
                if (searchInput) {
                    searchInput.value = "";
                    searchInput.focus();
                }
                clearSearch.hidden = true;
                filterBarang();
            });
        }

        if (selectStatus) {
            selectStatus.addEventListener("change", function () {
                clearTimeout(debounceTimer);
                filterBarang();
            });
        }

        if (selectKategori) {
            selectKategori.addEventListener("change", function () {
                clearTimeout(debounceTimer);
                filterBarang();
            });
        }

        if (resetFilter) {
            resetFilter.addEventListener("click", function () {
                clearTimeout(debounceTimer);

                if (searchInput) {
                    searchInput.value = "";
                }
                if (clearSearch) {
                    clearSearch.hidden = true;
                }
                if (selectStatus) {
                    selectStatus.selectedIndex = 0;
                    selectStatus.value = "";
                }
                if (selectKategori) {
                    selectKategori.selectedIndex = 0;
                    selectKategori.value = "";
                }

                if (window.history && window.history.replaceState) {
                    const cleanUrl = window.location.pathname + "#barang";
                    window.history.replaceState(null, "", cleanUrl);
                }

                filterBarang();

                if (searchInput) {
                    searchInput.focus();
                }
            });
        }

        if (filterForm) {
            filterForm.addEventListener("submit", function (e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                filterBarang();
            });
        }

        filterBarang();
    });
    </script>

</body>
</html>
