<?php
session_start();

// ==========================================================================
// 1. MENGAMBIL DATA PENGGUNA (Menggantikan localStorage "dataUser")
// ==========================================================================
// Mengambil dari session jika ada, atau gunakan nilai default "User"
$namaUser = isset($_SESSION['dataUser']['nama']) ? $_SESSION['dataUser']['nama'] : 'User';
$inisialUser = strtoupper(substr($namaUser, 0, 1));


// ==========================================================================
// 2. MENGAMBIL DATA LAPORAN (Menggantikan localStorage "daftarLaporan")
// ==========================================================================

/* --- OPSI A: Jika menggunakan Session / Data Dummy (Aktif) --- */
$daftarLaporan = isset($_SESSION['daftarLaporan']) ? $_SESSION['daftarLaporan'] : [
    // Contoh data (hapus atau kosongkan array ini untuk mengetes tampilan "Belum Ada Laporan")
    [
        'namaBarang'   => 'Dompet Kulit Hitam',
        'jenisLaporan' => 'Laporan Barang Hilang',
        'status'       => 'Menunggu Verifikasi',
        'kategori'     => 'Dompet / Tas',
        'lokasi'       => 'Perpustakaan Lantai 2',
        'tanggal'      => '2025-05-10'
    ],
    [
        'namaBarang'   => 'Kunci Motor Honda',
        'jenisLaporan' => 'Laporan Barang Ditemukan',
        'status'       => 'Terverifikasi',
        'kategori'     => 'Kunci',
        'lokasi'       => 'Parkiran Gedung B',
        'tanggal'      => '2025-05-12'
    ]
];

// Membalik urutan array agar laporan terbaru tampil di paling atas (seperti .slice().reverse() di JS)
$daftarLaporanTerbaru = array_reverse($daftarLaporan);
$totalLaporan = count($daftarLaporanTerbaru);


/* --- OPSI B: Jika menggunakan Database MySQL (Uncomment jika pakai DB) ---
include '../config/koneksi.php'; // Sesuaikan path koneksi database kamu
$id_user = $_SESSION['user_id'];
$query = mysqli_query($conn, "SELECT * FROM laporan WHERE user_id = '$id_user' ORDER BY id DESC");
$daftarLaporanTerbaru = [];
while ($row = mysqli_fetch_assoc($query)) {
    $daftarLaporanTerbaru[] = [
        'namaBarang'   => $row['nama_barang'],
        'jenisLaporan' => $row['jenis_laporan'],
        'status'       => $row['status'],
        'kategori'     => $row['kategori'],
        'lokasi'       => $row['lokasi'],
        'tanggal'      => $row['tanggal']
    ];
}
$totalLaporan = count($daftarLaporanTerbaru);
------------------------------------------------------------------------- */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - FoundIt</title>

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
            <a href="status-klaim.php">Status Klaim</a>
            <a href="riwayat-laporan.php" class="active">Riwayat Laporan</a>
            <a href="profil.php">Profil</a>
        </nav>
        <!-- Tombol keluar -->
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
            <!-- Profil pengguna -->
            <div class="user-profile">
                <div class="profile-icon" id="profileInitial"><?= htmlspecialchars($inisialUser); ?></div>
                <div>
                    <strong id="profileName"><?= htmlspecialchars($namaUser); ?></strong>
                    <small>User</small>
                </div>
            </div>
        </header>

        <!-- DAFTAR RIWAYAT LAPORAN -->
        <section class="history-container">
            <div class="history-header">
                <div>
                    <h2>Daftar Laporan</h2>
                    <p id="jumlahLaporan"><?= $totalLaporan; ?> laporan ditemukan</p>
                </div>
                <!-- Tombol buat laporan -->
                <a href="lapor-hilang.php" class="btn-report">+ Buat Laporan</a>
            </div>

            <!-- Daftar laporan dirender oleh PHP -->
            <div id="riwayatList">
                <?php if ($totalLaporan > 0): ?>
                    <?php foreach ($daftarLaporanTerbaru as $laporan): ?>
                        <div class="history-card">
                            <div class="history-top">
                                <div class="history-title">
                                    <h3><?= htmlspecialchars($laporan['namaBarang']); ?></h3>
                                    <p><?= htmlspecialchars($laporan['jenisLaporan']); ?></p>
                                </div>
                                <span class="history-status">
                                    <?= htmlspecialchars($laporan['status']); ?>
                                </span>
                            </div>

                            <div class="history-detail">
                                <div class="history-detail-item">
                                    <span>Kategori</span>
                                    <strong><?= htmlspecialchars($laporan['kategori']); ?></strong>
                                </div>

                                <div class="history-detail-item">
                                    <span>Lokasi</span>
                                    <strong><?= htmlspecialchars($laporan['lokasi']); ?></strong>
                                </div>

                                <div class="history-detail-item">
                                    <span>Tanggal</span>
                                    <strong><?= htmlspecialchars($laporan['tanggal']); ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tampilan jika belum ada laporan -->
            <?php if ($totalLaporan === 0): ?>
                <div id="emptyHistory" class="empty-history" style="display: block;">
                    <h3>Belum Ada Laporan</h3>
                    <p>Kamu belum membuat laporan barang.</p>
                    <a href="lapor-hilang.php" class="btn-report">Buat Laporan</a>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- JAVASCRIPT BOOTSTRAP -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>