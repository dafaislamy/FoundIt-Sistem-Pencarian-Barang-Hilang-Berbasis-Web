<?php
session_start();

// ==========================================================================
// 1. MENGAMBIL DATA PENGGUNA (Menggantikan localStorage "dataUser")
// ==========================================================================
$namaUser = isset($_SESSION['dataUser']['nama']) ? $_SESSION['dataUser']['nama'] : 'User';
$inisialUser = strtoupper(substr($namaUser, 0, 1));

// ==========================================================================
// 2. MENGAMBIL DATA KLAIM (Menggantikan localStorage "daftarKlaim")
// ==========================================================================

/* --- OPSI A: Menggunakan Session / Data Dummy (Aktif) --- */
$daftarKlaim = isset($_SESSION['daftarKlaim']) ? $_SESSION['daftarKlaim'] : [
    // Contoh data (kosongkan array ini jika ingin melihat tampilan "Belum Ada Pengajuan Klaim")
    [
        'namaBarang'   => 'Dompet Kulit Hitam',
        'status'       => 'Menunggu Verifikasi',
        'kategori'     => 'Aksesoris',
        'lokasiHilang' => 'Gedung C Fakultas Teknik',
        'ciriBarang'   => 'Ada stiker logo kampus di bagian dalam',
        'keterangan'   => 'Tertinggal di meja laboratorium komputer'
    ]
];

// Membalik urutan array agar klaim terbaru tampil paling atas (seperti .slice().reverse() di JS)
$daftarKlaimTerbaru = array_reverse($daftarKlaim);
$totalKlaim = count($daftarKlaimTerbaru);

/* --- OPSI B: Jika Menggunakan Database MySQL (Uncomment jika pakai DB) ---
include '../config/koneksi.php';
$id_user = $_SESSION['user_id'];
$query = mysqli_query($conn, "SELECT * FROM klaim WHERE user_id = '$id_user' ORDER BY id DESC");
$daftarKlaimTerbaru = [];
while ($row = mysqli_fetch_assoc($query)) {
    $daftarKlaimTerbaru[] = [
        'namaBarang'   => $row['nama_barang'],
        'status'       => $row['status'],
        'kategori'     => $row['kategori'],
        'lokasiHilang' => $row['lokasi_hilang'],
        'ciriBarang'   => $row['ciri_barang'],
        'keterangan'   => $row['keterangan']
    ];
}
$totalKlaim = count($daftarKlaimTerbaru);
------------------------------------------------------------------------- */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Klaim - FoundIt</title>

    <!-- Bootstrap dan CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <!-- Menu navigasi -->
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="cari-barang.php">Cari Barang</a>
            <a href="lapor-hilang.php">Lapor Barang Hilang</a>
            <a href="lapor-ditemukan.php">Lapor Barang Ditemukan</a>
            <a href="klaim.php">Klaim Barang</a>
            <a href="status-klaim.php" class="active">Status Klaim</a>
            <a href="riwayat-laporan.php">Riwayat Laporan</a>
            <a href="profil.php">Profil</a>
        </nav>
        <!-- Tombol keluar -->
        <a href="../login.php" class="logout">Keluar</a>
    </aside>

    <!-- KONTEN UTAMA -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="topbar">
            <div>
                <h1>Status Klaim</h1>
                <p>Pantau status pengajuan klaim barang kamu.</p>
            </div>
            <!-- Profil pengguna -->
            <div class="user-profile">
                <div class="profile-icon" id="profileInitial"><?= htmlspecialchars($inisialUser); ?></div>
                <div>
                    <strong id="profileName"><?= htmlspecialchars($namaUser); ?></strong>
                    <small>User</small>
                </div>
            </div>
        </header>

        <!-- DAFTAR STATUS KLAIM -->
        <section class="claim-status-container">
            <div class="status-header">
                <div>
                    <h2>Pengajuan Klaim</h2>
                    <p id="jumlahKlaim"><?= $totalKlaim; ?> pengajuan klaim</p>
                </div>
                <!-- Tombol ajukan klaim -->
                <a href="klaim.php" class="btn-claim">+ Ajukan Klaim</a>
            </div>

            <!-- Daftar klaim dirender oleh PHP -->
            <div id="klaimList">
                <?php if ($totalKlaim > 0): ?>
                    <?php foreach ($daftarKlaimTerbaru as $klaim): ?>
                        <div class="claim-card">
                            <div class="claim-top">
                                <div class="claim-title">
                                    <h3><?= htmlspecialchars($klaim['namaBarang']); ?></h3>
                                    <p>Pengajuan klaim barang</p>
                                </div>
                                <span class="claim-badge">
                                    <?= htmlspecialchars($klaim['status']); ?>
                                </span>
                            </div>

                            <div class="claim-detail">
                                <div class="claim-detail-item">
                                    <span>Kategori</span>
                                    <strong><?= htmlspecialchars($klaim['kategori']); ?></strong>
                                </div>

                                <div class="claim-detail-item">
                                    <span>Lokasi Kehilangan</span>
                                    <strong><?= htmlspecialchars($klaim['lokasiHilang']); ?></strong>
                                </div>

                                <div class="claim-detail-item">
                                    <span>Ciri Barang</span>
                                    <strong><?= htmlspecialchars($klaim['ciriBarang']); ?></strong>
                                </div>

                                <div class="claim-detail-item">
                                    <span>Keterangan</span>
                                    <strong><?= htmlspecialchars($klaim['keterangan']); ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tampilan jika belum ada klaim -->
            <?php if ($totalKlaim === 0): ?>
                <div id="emptyClaim" class="empty-claim" style="display: block;">
                    <h3>Belum Ada Pengajuan Klaim</h3>
                    <p>Kamu belum mengajukan klaim barang.</p>
                    <a href="klaim.php" class="btn-claim">Ajukan Klaim</a>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>