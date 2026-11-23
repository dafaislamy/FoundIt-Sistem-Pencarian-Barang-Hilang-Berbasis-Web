
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

$daftarKlaim = [];
$error = '';

// ==========================================
// 2. AMBIL DATA KLAIM DARI ORACLE
// ==========================================

try {

    $sql = "
        SELECT
            CL.ID AS ID_KLAIM,
            CL.BUKTI_KEPEMILIKAN,
            CL.LAMPIRAN_FOTO,
            CL.STATUS_KLAIM,
            CL.CREATED_AT,

            I.ID AS ID_BARANG,
            I.JUDUL AS NAMA_BARANG,
            I.DESKRIPSI AS CIRI_BARANG,
            I.LOKASI,
            I.TANGGAL_KEJADIAN,
            I.FOTO AS FOTO_BARANG,

            C.NAMA_KATEGORI

        FROM CLAIMS CL

        INNER JOIN ITEMS I
            ON CL.ITEMS_ID = I.ID

        LEFT JOIN CATEGORIES C
            ON I.CATEGORIES_ID = C.ID

        WHERE CL.USERS_ID = :p_users_id

        ORDER BY CL.CREATED_AT DESC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(
        ':p_users_id',
        $idUser,
        PDO::PARAM_STR
    );

    $stmt->execute();

    $daftarKlaim = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());

    $error = 'Gagal mengambil data klaim: ' . $e->getMessage();
}

$totalKlaim = count($daftarKlaim);

// ==========================================
// 3. FUNGSI STATUS
// ==========================================

function statusKlaimLabel($status)
{
    $status = strtolower(trim($status));

    switch ($status) {
        case 'menunggu':
            return 'Menunggu Verifikasi';

        case 'disetujui':
            return 'Disetujui';

        case 'ditolak':
            return 'Ditolak';

        case 'dibatalkan':
            return 'Dibatalkan';

        default:
            return ucfirst($status);
    }
}

function statusKlaimClass($status)
{
    $status = strtolower(trim($status));

    switch ($status) {
        case 'menunggu':
            return 'menunggu';

        case 'disetujui':
            return 'disetujui';

        case 'ditolak':
            return 'ditolak';

        case 'dibatalkan':
            return 'dibatalkan';

        default:
            return 'menunggu';
    }
}

function formatTanggalKlaim($tanggal)
{
    if ($tanggal instanceof DateTimeInterface) {
        return $tanggal->format('d-m-Y H:i');
    }

    if (empty($tanggal)) {
        return '-';
    }

    $timestamp = strtotime((string)$tanggal);

    if ($timestamp === false) {
        return (string)$tanggal;
    }

    return date('d-m-Y H:i', $timestamp);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Status Klaim - FoundIt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../css/main.css">

    <style>

        .claim-status-container {
            margin-top: 25px;
        }

        .claim-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 20px;
        }

        .claim-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .claim-title h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .claim-title p {
            color: #777;
            margin-bottom: 0;
        }

        .claim-badge {
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #fff3cd;
            color: #856404;
        }

        .claim-badge.disetujui {
            background: #d1e7dd;
            color: #0f5132;
        }

        .claim-badge.ditolak,
        .claim-badge.dibatalkan {
            background: #f8d7da;
            color: #842029;
        }

        .claim-detail {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .claim-detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .claim-detail-item span {
            color: #777;
            font-size: 13px;
        }

        .claim-detail-item strong {
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .claim-photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 8px;
        }

        .empty-claim {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-claim h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .empty-claim p {
            color: #777;
        }

        @media (max-width: 600px) {
            .claim-detail {
                grid-template-columns: 1fr;
            }
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

            <a href="dashboard.php">Dashboard</a>

            <a href="cari-barang.php">Cari Barang</a>

            <a href="lapor-hilang.php">Lapor Barang Hilang</a>

            <a href="lapor-ditemukan.php">Lapor Barang Ditemukan</a>

            <a href="klaim.php">Klaim Barang</a>

            <a href="status-klaim.php" class="active">
                Status Klaim
            </a>

            <a href="riwayat-laporan.php">Riwayat Laporan</a>

            <a href="profil.php">Profil</a>

        </nav>

        <a href="../logout.php" class="logout">
            Keluar
        </a>

    </aside>

    <!-- KONTEN UTAMA -->

    <main class="main-content">

        <!-- HEADER -->

        <header class="topbar">

            <div>

                <h1>Status Klaim</h1>

                <p>
                    Pantau status pengajuan klaim barang kamu.
                </p>

            </div>

            <div class="user-profile">

                <div class="profile-icon">
                    <?= htmlspecialchars(
                        $inisialUser,
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

        <!-- DAFTAR STATUS KLAIM -->

        <section class="claim-status-container">

            <div class="status-header">

                <div>

                    <h2>Pengajuan Klaim</h2>

                    <p>
                        <?= $totalKlaim; ?> pengajuan klaim
                    </p>

                </div>

                <a href="cari-barang.php" class="btn-claim">
                    + Cari Barang
                </a>

            </div>

            <!-- ERROR -->

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </div>

            <?php endif; ?>

            <!-- DAFTAR KLAIM -->

            <?php if ($totalKlaim > 0): ?>

                <?php foreach ($daftarKlaim as $klaim): ?>

                    <div class="claim-card">

                        <div class="claim-top">

                            <div class="claim-title">

                                <h3>
                                    <?= htmlspecialchars(
                                        $klaim['NAMA_BARANG'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </h3>

                                <p>
                                    Pengajuan klaim barang
                                    ·
                                    <?= htmlspecialchars(
                                        $klaim['ID_KLAIM'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </p>

                            </div>

                            <span class="claim-badge <?= statusKlaimClass($klaim['STATUS_KLAIM']); ?>">

                                <?= htmlspecialchars(
                                    statusKlaimLabel($klaim['STATUS_KLAIM']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>

                        <div class="claim-detail">

                            <div class="claim-detail-item">

                                <span>Kategori</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $klaim['NAMA_KATEGORI'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>

                            </div>

                            <div class="claim-detail-item">

                                <span>Lokasi Barang</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $klaim['LOKASI'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>

                            </div>

                            <div class="claim-detail-item">

                                <span>Ciri Barang</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $klaim['CIRI_BARANG'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>

                            </div>

                            <div class="claim-detail-item">

                                <span>Bukti Kepemilikan</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $klaim['BUKTI_KEPEMILIKAN'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>

                            </div>

                            <div class="claim-detail-item">

                                <span>Tanggal Pengajuan</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        formatTanggalKlaim($klaim['CREATED_AT']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>

                            </div>

                            <div class="claim-detail-item">

                                <span>Foto Bukti</span>

                                <?php if (!empty($klaim['LAMPIRAN_FOTO'])): ?>

                                    <?php
                                    $fotoKlaim = trim($klaim['LAMPIRAN_FOTO']);

                                    if (strpos($fotoKlaim, 'uploads/') === 0) {
                                        $fotoKlaim = '../' . $fotoKlaim;
                                    } else {
                                        $fotoKlaim = '../uploads/bukti/' . basename($fotoKlaim);
                                    }
                                    ?>

                                    <a
                                        href="<?= htmlspecialchars($fotoKlaim, ENT_QUOTES, 'UTF-8'); ?>"
                                        target="_blank"
                                    >

                                        <img
                                            src="<?= htmlspecialchars($fotoKlaim, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="Foto bukti klaim"
                                            class="claim-photo"
                                        >

                                    </a>

                                <?php else: ?>

                                    <strong>-</strong>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php elseif ($error === ''): ?>

                <div class="empty-claim">

                    <h3>
                        Belum Ada Pengajuan Klaim
                    </h3>

                    <p>
                        Kamu belum mengajukan klaim barang.
                    </p>

                    <a href="cari-barang.php" class="btn-claim">
                        Cari Barang
                    </a>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>

</body>

</html>
