
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

$idUser = $_SESSION['id_user'] ?? '';
$namaUser = $_SESSION['nama'] ?? 'User';
$inisialUser = strtoupper(substr($namaUser, 0, 1));

$error = '';
$bukti = '';
$bolehAjukan = true;

// ==========================================
// 2. AMBIL ID BARANG
// ==========================================

$idItem = trim($_GET['id'] ?? $_POST['id'] ?? '');

if ($idItem === '') {
    header('Location: cari-barang.php?tipe=found');
    exit;
}

// ==========================================
// 3. AMBIL DATA BARANG
// ==========================================

try {

    $stmt = $conn->prepare("
        SELECT
            I.ID,
            I.JUDUL,
            I.DESKRIPSI,
            I.TIPE,
            I.LOKASI,
            I.TANGGAL_KEJADIAN,
            I.FOTO,
            I.STATUS,
            I.USERS_ID,
            C.NAMA_KATEGORI
        FROM ITEMS I
        JOIN CATEGORIES C
            ON C.ID = I.CATEGORIES_ID
        WHERE I.ID = :p_item_id
    ");

    $stmt->bindValue(':p_item_id', $idItem, PDO::PARAM_STR);
    $stmt->execute();

    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header('Location: cari-barang.php');
        exit;
    }

    if (
        strtolower(trim($item['TIPE'])) !== 'found' ||
        strtolower(trim($item['STATUS'])) !== 'terbit'
    ) {
        $error = 'Barang ini belum tersedia untuk diklaim.';
        $bolehAjukan = false;
    }

    if ((string)$item['USERS_ID'] === (string)$idUser) {
        $error = 'Kamu tidak dapat mengklaim laporan barang milikmu sendiri.';
        $bolehAjukan = false;
    }

} catch (PDOException $e) {

    error_log($e->getMessage());

    die(
        'Error mengambil data barang: ' .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}

// ==========================================
// 4. CEK KLAIM GANDA
// ==========================================

if ($bolehAjukan) {

    try {

        $stmtDup = $conn->prepare("
            SELECT COUNT(*)
            FROM CLAIMS
            WHERE ITEMS_ID = :p_items_id
              AND USERS_ID = :p_users_id
              AND STATUS_KLAIM IN ('Menunggu', 'Disetujui')
        ");

        $stmtDup->bindValue(':p_items_id', $idItem, PDO::PARAM_STR);
        $stmtDup->bindValue(':p_users_id', $idUser, PDO::PARAM_STR);

        $stmtDup->execute();

        $jumlahKlaim = (int)$stmtDup->fetchColumn();

        if ($jumlahKlaim > 0) {
            $error = 'Kamu sudah memiliki klaim aktif untuk barang ini.';
            $bolehAjukan = false;
        }

    } catch (PDOException $e) {

        error_log($e->getMessage());

        $error = 'Error Oracle saat memeriksa klaim: ' .
            $e->getMessage();

        $bolehAjukan = false;
    }
}

// ==========================================
// 5. PROSES PENGAJUAN KLAIM
// ==========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $bolehAjukan
) {

    $bukti = trim($_POST['bukti_kepemilikan'] ?? '');

    $fileTersimpan = false;
    $tujuanFoto = null;

    try {

        // Validasi bukti kepemilikan
        $panjangBukti = mb_strlen($bukti);

        if ($panjangBukti < 20) {
            throw new Exception(
                'Bukti kepemilikan minimal 20 karakter.'
            );
        }

        if ($panjangBukti > 1000) {
            throw new Exception(
                'Bukti kepemilikan maksimal 1000 karakter.'
            );
        }

        // ======================================
        // 6. VALIDASI FOTO
        // ======================================

        $file = $_FILES['lampiran_foto'] ?? null;

        $formatDiizinkan = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $ekstensi = null;

        if (
            $file &&
            $file['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Upload foto gagal.');
            }

            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('Ukuran foto maksimal 2 MB.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            if (!isset($formatDiizinkan[$mime])) {
                throw new Exception(
                    'Format foto harus JPG, PNG, atau WEBP.'
                );
            }

            $ekstensi = $formatDiizinkan[$mime];
        }

        // ======================================
        // 7. BUAT ID KLAIM
        // ======================================

        $stmtID = $conn->query("
            SELECT NVL(MAX(TO_NUMBER(SUBSTR(ID, 4))), 0) + 1
            FROM CLAIMS
            WHERE REGEXP_LIKE(ID, '^CLM[0-9]+$')
        ");

        $nomorBaru = (int)$stmtID->fetchColumn();

        $idKlaim = 'CLM' . str_pad(
            $nomorBaru,
            3,
            '0',
            STR_PAD_LEFT
        );

        // ======================================
        // 8. SIMPAN FOTO LAMPIRAN
        // ======================================

        $fotoDatabase = null;

        if ($ekstensi !== null) {

            $folderUpload = dirname(__DIR__) . '/uploads/bukti';

            if (!is_dir($folderUpload)) {
                if (!mkdir($folderUpload, 0775, true)) {
                    throw new Exception(
                        'Folder uploads/bukti gagal dibuat.'
                    );
                }
            }

            if (!is_writable($folderUpload)) {
                throw new Exception(
                    'Folder uploads/bukti tidak dapat ditulisi.'
                );
            }

            $namaFoto = 'bukti_' .
                strtolower($idKlaim) . '_' .
                bin2hex(random_bytes(4)) . '.' .
                $ekstensi;

            $tujuanFoto = $folderUpload . '/' . $namaFoto;

            if (!move_uploaded_file(
                $file['tmp_name'],
                $tujuanFoto
            )) {
                throw new Exception(
                    'Foto lampiran gagal disimpan.'
                );
            }

            $fileTersimpan = true;

            $fotoDatabase = 'uploads/bukti/' . $namaFoto;
        }

        // ======================================
        // 9. SIMPAN KE ORACLE
        // ======================================

        $conn->beginTransaction();

        $stmtInsert = $conn->prepare("
            INSERT INTO CLAIMS (
                ID,
                BUKTI_KEPEMILIKAN,
                LAMPIRAN_FOTO,
                STATUS_KLAIM,
                CREATED_AT,
                ITEMS_ID,
                USERS_ID
            ) VALUES (
                :p_claim_id,
                :p_bukti,
                :p_foto,
                'Menunggu',
                SYSTIMESTAMP,
                :p_items_id,
                :p_users_id
            )
        ");

        $stmtInsert->bindValue(':p_claim_id', $idKlaim, PDO::PARAM_STR);
        $stmtInsert->bindValue(':p_bukti', $bukti, PDO::PARAM_STR);

        if ($fotoDatabase === null) {
            $stmtInsert->bindValue(':p_foto', null, PDO::PARAM_NULL);
        } else {
            $stmtInsert->bindValue(':p_foto', $fotoDatabase, PDO::PARAM_STR);
        }

        $stmtInsert->bindValue(':p_items_id', $idItem, PDO::PARAM_STR);
        $stmtInsert->bindValue(':p_users_id', $idUser, PDO::PARAM_STR);

        $stmtInsert->execute();

        $conn->commit();

        // ======================================
        // 10. BERHASIL
        // ======================================

        echo "<script>
            alert('Klaim berhasil diajukan! Menunggu verifikasi admin.');
            window.location.href = 'status-klaim.php';
        </script>";

        exit;

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        if (
            $fileTersimpan &&
            $tujuanFoto &&
            file_exists($tujuanFoto)
        ) {
            unlink($tujuanFoto);
        }

        error_log($e->getMessage());

        $error = 'Error Oracle saat menyimpan klaim: ' .
            $e->getMessage();

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

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

// ==========================================
// 11. FOTO BARANG
// ==========================================

$fotoBarang = trim($item['FOTO'] ?? '');

if ($fotoBarang === '') {
    $fotoBarang = '../uploads/no-image.svg';
} elseif (strpos($fotoBarang, 'uploads/') === 0) {
    $fotoBarang = '../' . $fotoBarang;
} else {
    $fotoBarang = '../uploads/' . basename($fotoBarang);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Klaim Barang - FoundIt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../css/main.css">
</head>

<body>

    <aside class="sidebar">

        <div class="logo">
            <span>Found</span>It
        </div>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="cari-barang.php">Cari Barang</a>
            <a href="lapor-hilang.php">Lapor Barang Hilang</a>
            <a href="lapor-ditemukan.php">Lapor Barang Ditemukan</a>
            <a href="klaim.php" class="active">Klaim Barang</a>
            <a href="status-klaim.php">Status Klaim</a>
            <a href="riwayat-laporan.php">Riwayat Laporan</a>
            <a href="profil.php">Profil</a>
        </nav>

        <a href="../logout.php" class="logout">
            Keluar
        </a>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Klaim Barang</h1>
                <p>Ajukan klaim untuk barang yang kamu kenali.</p>
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

        <section class="claim-container">

            <div class="claim-header">
                <h2>Form Pengajuan Klaim</h2>
                <p>
                    Jelaskan bukti kepemilikan agar admin dapat
                    memverifikasi bahwa barang ini milik Anda.
                </p>
            </div>

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php endif; ?>

            <div class="d-flex gap-3 align-items-center mb-4 p-3 border rounded">

                <img
                    src="<?= htmlspecialchars($fotoBarang, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Foto barang"
                    style="width:90px;height:90px;object-fit:cover;border-radius:8px;"
                >

                <div>

                    <span class="status-badge ditemukan">
                        Ditemukan
                    </span>

                    <h5 class="mb-1 mt-1">
                        <?= htmlspecialchars($item['JUDUL'], ENT_QUOTES, 'UTF-8'); ?>
                    </h5>

                    <small class="text-muted">
                        <?= htmlspecialchars($item['NAMA_KATEGORI'], ENT_QUOTES, 'UTF-8'); ?>
                        &middot;
                        <?= htmlspecialchars($item['LOKASI'], ENT_QUOTES, 'UTF-8'); ?>
                        &middot;
                        <?= htmlspecialchars(
                            $item['TANGGAL_KEJADIAN'] instanceof DateTimeInterface
                                ? $item['TANGGAL_KEJADIAN']->format('d-m-Y')
                                : (string)$item['TANGGAL_KEJADIAN'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </small>

                </div>

            </div>

            <?php if ($bolehAjukan): ?>

                <form
                    method="POST"
                    action="klaim.php?id=<?= urlencode($item['ID']); ?>"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= htmlspecialchars($item['ID'], ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <div class="form-group">

                        <label for="bukti">
                            Bukti Kepemilikan
                        </label>

                        <textarea
                            id="bukti"
                            name="bukti_kepemilikan"
                            class="form-control"
                            rows="6"
                            maxlength="1000"
                            required
                            placeholder="Sebutkan ciri khusus yang membuktikan barang tersebut milik Anda..."
                        ><?= htmlspecialchars($bukti, ENT_QUOTES, 'UTF-8'); ?></textarea>

                        <small class="text-muted">
                            Minimal 20 karakter, maksimal 1000 karakter.
                        </small>

                    </div>

                    <div class="form-group">

                        <label for="lampiran">
                            Lampiran Foto (opsional)
                        </label>

                        <input
                            type="file"
                            id="lampiran"
                            name="lampiran_foto"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <small class="text-muted">
                            JPG, PNG, atau WEBP. Maksimal 2 MB.
                        </small>

                    </div>

                    <div class="form-actions">

                        <a
                            href="detail-barang.php?id=<?= urlencode($item['ID']); ?>"
                            class="btn-cancel"
                        >
                            Batal
                        </a>

                        <button type="submit" class="btn-submit">
                            Ajukan Klaim
                        </button>

                    </div>

                </form>

            <?php else: ?>

                <a
                    href="detail-barang.php?id=<?= urlencode($item['ID']); ?>"
                    class="btn-cancel"
                >
                    Kembali ke Detail Barang
                </a>

            <?php endif; ?>

        </section>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
