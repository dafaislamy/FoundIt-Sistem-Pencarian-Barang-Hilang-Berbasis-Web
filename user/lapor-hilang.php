<?php
session_start();

// ==========================================================================
// 1. MENGAMBIL DATA PENGGUNA
// ==========================================================================
$namaUser = isset($_SESSION['dataUser']['nama']) ? $_SESSION['dataUser']['nama'] : 'User';
$inisialUser = strtoupper(substr($namaUser, 0, 1));

// ==========================================================================
// 2. PROSES SUBMIT FORM LAPORAN BARANG HILANG
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaBarang = trim($_POST['namaBarang'] ?? '');
    $kategori   = trim($_POST['kategori'] ?? '');
    $lokasi     = trim($_POST['lokasi'] ?? '');
    $tanggal    = trim($_POST['tanggal'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');

    // Opsional: Penanganan upload foto jika diperlukan
    $namaFoto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $namaFoto = 'hilang_' . time() . '.' . $ext;
        // Pastikan folder ../uploads/ sudah ada jika ingin menyimpan file fisik:
        // move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/' . $namaFoto);
    }

    // Membuat struktur data laporan baru (sama seperti objek laporanBaru di lapor-hilang.js)
    $laporanBaru = [
        'id'           => round(microtime(true) * 1000), // Setara Date.now()
        'namaBarang'   => $namaBarang,
        'kategori'     => $kategori,
        'lokasi'       => $lokasi,
        'tanggal'      => $tanggal,
        'deskripsi'    => $deskripsi,
        'foto'         => $namaFoto,
        'status'       => 'Hilang',
        'jenisLaporan' => 'Barang Hilang'
    ];

    /* --- OPSI A: Simpan ke Session PHP --- */
    if (!isset($_SESSION['daftarLaporan'])) {
        $_SESSION['daftarLaporan'] = [];
    }
    $_SESSION['daftarLaporan'][] = $laporanBaru;

    /* --- OPSI B: Simpan ke Database MySQL (Uncomment jika pakai DB) ---
    include '../config/koneksi.php';
    $id_user = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "INSERT INTO laporan (user_id, nama_barang, kategori, lokasi, tanggal, deskripsi, foto, status, jenis_laporan) VALUES (?, ?, ?, ?, ?, ?, ?, 'Hilang', 'Barang Hilang')");
    mysqli_stmt_bind_param($stmt, "issssss", $id_user, $namaBarang, $kategori, $lokasi, $tanggal, $deskripsi, $namaFoto);
    mysqli_stmt_execute($stmt);
    ------------------------------------------------------------------- */

    // Menampilkan alert dan redirect persis seperti di lapor-hilang.js
    echo "<script>
        alert('Laporan barang hilang berhasil dikirim!');
        window.location.href = 'riwayat-laporan.php';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Barang Hilang - FoundIt</title>

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
            <a href="lapor-hilang.php" class="active">Lapor Barang Hilang</a>
            <a href="lapor-ditemukan.php">Lapor Barang Ditemukan</a>
            <a href="klaim.php">Klaim Barang</a>
            <a href="status-klaim.php">Status Klaim</a>
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
                <h1>Lapor Barang Hilang</h1>
                <p>Laporkan barang yang hilang agar dapat ditemukan.</p>
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

        <!-- FORM LAPORAN -->
        <section class="report-container">
            <div class="report-header">
                <h2>Form Laporan Barang Hilang</h2>
                <p>Isi informasi barang yang hilang dengan lengkap.</p>
            </div>

            <form id="laporHilangForm" action="" method="POST" enctype="multipart/form-data">
                <!-- Input nama barang -->
                <div class="form-group">
                    <label for="namaBarang">Nama Barang</label>
                    <input type="text" id="namaBarang" name="namaBarang" class="form-control" placeholder="Contoh: Dompet hitam" required>
                </div>

                <!-- Pilihan kategori -->
                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" class="form-select" required>
                        <option value="">Pilih kategori</option>
                        <option value="elektronik">Elektronik</option>
                        <option value="aksesoris">Aksesoris</option>
                        <option value="dokumen">Dokumen</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <!-- Input lokasi terakhir -->
                <div class="form-group">
                    <label for="lokasi">Lokasi Terakhir</label>
                    <input type="text" id="lokasi" name="lokasi" class="form-control" placeholder="Contoh: Gedung C Fakultas Teknik" required>
                </div>

                <!-- Input tanggal hilang -->
                <div class="form-group">
                    <label for="tanggal">Tanggal Hilang</label>
                    <input type="date" id="tanggal" name="tanggal" class="form-control" required>
                </div>

                <!-- Input deskripsi barang -->
                <div class="form-group">
                    <label for="deskripsi">Deskripsi Barang</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="5" placeholder="Jelaskan ciri-ciri atau informasi lain mengenai barang..." required></textarea>
                </div>

                <!-- Upload foto barang -->
                <div class="form-group">
                    <label for="foto">Foto Barang</label>
                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                    <small class="form-hint">Foto bersifat opsional.</small>
                </div>

                <!-- Tombol batal dan kirim -->
                <div class="form-actions">
                    <a href="dashboard.php" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-submit">Kirim Laporan</button>
                </div>
            </form>
        </section>

    </main>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>