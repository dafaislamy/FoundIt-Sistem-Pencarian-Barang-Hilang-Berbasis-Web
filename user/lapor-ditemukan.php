
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

$error = '';

// ==========================================
// 2. AMBIL DATA KATEGORI
// ==========================================

try {
    $stmtKategori = $conn->query("
        SELECT ID, NAMA_KATEGORI
        FROM CATEGORIES
        ORDER BY ID
    ");

    $daftarKategori = $stmtKategori->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $daftarKategori = [];
    $error = 'Gagal mengambil data kategori.';
}

// ==========================================
// 3. PROSES FORM
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {

    $judul = trim($_POST['namaBarang'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    $idUser = $_SESSION['id_user'] ?? '';

    $fileTersimpan = false;
    $tujuanFoto = null;

    try {

        // ======================================
        // 4. VALIDASI INPUT
        // ======================================

        if (
            $judul === '' ||
            $kategori === '' ||
            $lokasi === '' ||
            $tanggal === '' ||
            $deskripsi === '' ||
            $idUser === ''
        ) {
            throw new Exception('Semua kolom wajib diisi.');
        }

        if (
            strlen($judul) > 150 ||
            strlen($lokasi) > 100 ||
            strlen($deskripsi) > 1000
        ) {
            throw new Exception('Ada data yang melebihi batas karakter.');
        }

        // ======================================
        // 5. VALIDASI KATEGORI
        // ======================================

        $cekKategori = $conn->prepare("
            SELECT COUNT(*)
            FROM CATEGORIES
            WHERE ID = :id
        ");

        $cekKategori->execute([
            ':id' => $kategori
        ]);

        if ((int)$cekKategori->fetchColumn() === 0) {
            throw new Exception('Kategori tidak valid.');
        }

        // ======================================
        // 6. VALIDASI TANGGAL
        // ======================================

        $tanggalObj = DateTime::createFromFormat('!Y-m-d', $tanggal);

        if (
            !$tanggalObj ||
            $tanggalObj->format('Y-m-d') !== $tanggal
        ) {
            throw new Exception('Tanggal tidak valid.');
        }

        // ======================================
        // 7. FOLDER UPLOADS
        // ======================================

        $folderUpload = dirname(__DIR__) . '/uploads';

        if (!is_dir($folderUpload)) {
            if (!mkdir($folderUpload, 0775, true)) {
                throw new Exception('Folder uploads gagal dibuat.');
            }
        }

        if (!is_writable($folderUpload)) {
            throw new Exception('Folder uploads tidak dapat ditulisi.');
        }

        // ======================================
        // 8. FOTO DEFAULT
        // ======================================

        $gambarDefault = $folderUpload . '/no-image.svg';

        if (!file_exists($gambarDefault)) {

            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400">
                <rect width="100%" height="100%" fill="#eeeeee"/>
                <text x="50%" y="50%" text-anchor="middle"
                dominant-baseline="middle" font-size="28" fill="#777777">
                Foto tidak tersedia
                </text>
            </svg>';

            if (file_put_contents($gambarDefault, $svg) === false) {
                throw new Exception('Gagal membuat foto default.');
            }
        }

        $namaFoto = 'no-image.svg';

        // ======================================
        // 9. UPLOAD FOTO
        // ======================================

        if (
            isset($_FILES['foto']) &&
            $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Upload foto gagal.');
            }

            if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Ukuran foto maksimal 5 MB.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['foto']['tmp_name']);

            $ekstensiDiizinkan = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($ekstensiDiizinkan[$mime])) {
                throw new Exception('Foto harus JPG, PNG, atau WEBP.');
            }

            $namaFoto = 'ditemukan_' .
                bin2hex(random_bytes(12)) . '.' .
                $ekstensiDiizinkan[$mime];

            $tujuanFoto = $folderUpload . '/' . $namaFoto;

            if (!move_uploaded_file(
                $_FILES['foto']['tmp_name'],
                $tujuanFoto
            )) {
                throw new Exception('Foto gagal disimpan.');
            }

            $fileTersimpan = true;
        }

        // ======================================
        // 10. BUAT ID LAPORAN
        // ======================================

        $stmtID = $conn->query("
            SELECT NVL(MAX(TO_NUMBER(SUBSTR(ID, 4))), 0) + 1
            FROM ITEMS
            WHERE REGEXP_LIKE(ID, '^ITM[0-9]+$')
        ");

        $nomorBaru = (int)$stmtID->fetchColumn();

        $idItem = 'ITM' . str_pad(
            $nomorBaru,
            3,
            '0',
            STR_PAD_LEFT
        );

        // ======================================
        // 11. SIMPAN KE ORACLE
        // ======================================

        $fotoDatabase = 'uploads/' . $namaFoto;

        $stmtInsert = $conn->prepare("
            INSERT INTO ITEMS (
                ID,
                JUDUL,
                DESKRIPSI,
                TIPE,
                LOKASI,
                TANGGAL_KEJADIAN,
                FOTO,
                STATUS,
                CREATED_AT,
                CATEGORIES_ID,
                USERS_ID
            ) VALUES (
                :id,
                :judul,
                :deskripsi,
                'found',
                :lokasi,
                TO_DATE(:tanggal, 'YYYY-MM-DD'),
                :foto,
                'Pending',
                SYSTIMESTAMP,
                :kategori,
                :id_user
            )
        ");

        $stmtInsert->execute([
            ':id' => $idItem,
            ':judul' => $judul,
            ':deskripsi' => $deskripsi,
            ':lokasi' => $lokasi,
            ':tanggal' => $tanggal,
            ':foto' => $fotoDatabase,
            ':kategori' => $kategori,
            ':id_user' => $idUser
        ]);

        // ======================================
        // 12. BERHASIL
        // ======================================

        echo "<script>
            alert('Laporan barang ditemukan berhasil dikirim!');
            window.location.href = 'riwayat-laporan.php';
        </script>";

        exit;

    } catch (PDOException $e) {

        if (
            $fileTersimpan &&
            $tujuanFoto &&
            file_exists($tujuanFoto)
        ) {
            unlink($tujuanFoto);
        }

        error_log($e->getMessage());

        $error = 'Gagal menyimpan laporan ke database Oracle.';

    } catch (Exception $e) {

        if (
            $fileTersimpan &&
            $tujuanFoto &&
            file_exists($tujuanFoto)
        ) {
            unlink($tujuanFoto);
        }

        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lapor Barang Ditemukan - FoundIt</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- CSS -->
    <link rel="stylesheet" href="../css/main.css">
</head>

<body>

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            <span>Found</span>It
        </div>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="cari-barang.php">Cari Barang</a>
            <a href="lapor-hilang.php">Lapor Barang Hilang</a>
            <a href="lapor-ditemukan.php" class="active">Lapor Barang Ditemukan</a>
            <a href="klaim.php">Klaim Barang</a>
            <a href="status-klaim.php">Status Klaim</a>
            <a href="riwayat-laporan.php">Riwayat Laporan</a>
            <a href="profil.php">Profil</a>
        </nav>

        <a href="../logout.php" class="logout">
            Keluar
        </a>

    </aside>


    <!-- KONTEN UTAMA -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div>
                <h1>Lapor Barang Ditemukan</h1>
                <p>Laporkan barang yang kamu temukan di lingkungan kampus.</p>
            </div>

            <div class="user-profile">

                <div class="profile-icon">
                    <?= htmlspecialchars($inisialUser, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div>
                    <strong>
                        <?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8'); ?>
                    </strong>

                    <small>User</small>
                </div>

            </div>

        </header>


        <!-- FORM LAPORAN -->

        <section class="report-container">

            <div class="report-header">
                <h2>Form Laporan Barang Ditemukan</h2>
                <p>Isi informasi barang yang kamu temukan dengan lengkap.</p>
            </div>

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php endif; ?>


            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="namaBarang">Nama Barang</label>

                    <input
                        type="text"
                        id="namaBarang"
                        name="namaBarang"
                        class="form-control"
                        maxlength="150"
                        placeholder="Contoh: Dompet hitam"
                        required
                    >
                </div>


                <div class="form-group">
                    <label for="kategori">Kategori</label>

                    <select
                        id="kategori"
                        name="kategori"
                        class="form-select"
                        required
                    >
                        <option value="">Pilih kategori</option>

                        <?php foreach ($daftarKategori as $kategoriItem): ?>

                            <option value="<?= htmlspecialchars($kategoriItem['ID'], ENT_QUOTES, 'UTF-8'); ?>">

                                <?= htmlspecialchars($kategoriItem['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>


                <div class="form-group">
                    <label for="lokasi">Lokasi Ditemukan</label>

                    <input
                        type="text"
                        id="lokasi"
                        name="lokasi"
                        class="form-control"
                        maxlength="100"
                        placeholder="Contoh: Gedung C Fakultas Teknik"
                        required
                    >
                </div>


                <div class="form-group">
                    <label for="tanggal">Tanggal Ditemukan</label>

                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        class="form-control"
                        required
                    >
                </div>


                <div class="form-group">
                    <label for="deskripsi">Deskripsi Barang</label>

                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        class="form-control"
                        rows="5"
                        maxlength="1000"
                        placeholder="Jelaskan ciri-ciri atau informasi lain mengenai barang..."
                        required
                    ></textarea>
                </div>


                <div class="form-group">
                    <label for="foto">Foto Barang</label>

                    <input
                        type="file"
                        id="foto"
                        name="foto"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small class="form-hint">
                        Foto bersifat opsional. Maksimal 5 MB.
                    </small>
                </div>


                <div class="form-actions">

                    <a href="dashboard.php" class="btn-cancel">
                        Batal
                    </a>

                    <button type="submit" class="btn-submit">
                        Kirim Laporan
                    </button>

                </div>

            </form>

        </section>

    </main>


    <!-- JAVASCRIPT -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
