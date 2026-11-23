
<?php
require_once '../config/koneksi.php';

// ==========================================
// CEK LOGIN USER
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

if ($idUser === '') {
    die('ID pengguna tidak ditemukan. Silakan login kembali.');
}

// ==========================================
// FUNGSI BANTU
// ==========================================

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ==========================================
// AMBIL DATA USER
// ==========================================

$stmt = $conn->prepare("
    SELECT
        ID,
        NAMA,
        NOMOR_IDENTITAS,
        EMAIL,
        NO_TELP,
        CREATED_AT,
        FAKULTAS,
        JURUSAN,
        ANGKATAN
    FROM USERS
    WHERE ID = :p_users_id
");

$stmt->bindValue(':p_users_id', $idUser);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Data pengguna tidak ditemukan.');
}

$_SESSION['nama'] = $user['NAMA'];

// ==========================================
// AMBIL TOTAL LAPORAN
// ==========================================

$stmtLap = $conn->prepare("
    SELECT COUNT(*)
    FROM ITEMS
    WHERE USERS_ID = :p_users_id
");

$stmtLap->bindValue(':p_users_id', $idUser);
$stmtLap->execute();

$totalLaporan = (int) $stmtLap->fetchColumn();

// ==========================================
// AMBIL TOTAL KLAIM
// ==========================================

$stmtKlaim = $conn->prepare("
    SELECT
        COUNT(*) AS TOTAL,
        COUNT(
            CASE
                WHEN UPPER(STATUS_KLAIM) = 'DISETUJUI'
                THEN 1
            END
        ) AS DISETUJUI
    FROM CLAIMS
    WHERE USERS_ID = :p_users_id
");

$stmtKlaim->bindValue(':p_users_id', $idUser);
$stmtKlaim->execute();

$klaim = $stmtKlaim->fetch(PDO::FETCH_ASSOC);

$totalKlaim = (int) ($klaim['TOTAL'] ?? 0);
$klaimDisetujui = (int) ($klaim['DISETUJUI'] ?? 0);

// ==========================================
// DATA PROFIL
// ==========================================

$nama = $user['NAMA'] ?? '-';
$nim = $user['NOMOR_IDENTITAS'] ?? '-';
$email = $user['EMAIL'] ?? '-';
$telepon = $user['NO_TELP'] ?? '-';
$fakultas = $user['FAKULTAS'] ?? '-';
$jurusan = $user['JURUSAN'] ?? '-';
$angkatan = $user['ANGKATAN'] ?? '-';

$inisial = strtoupper(substr(trim($nama), 0, 1));

// ==========================================
// FORMAT TANGGAL BERGABUNG
// ==========================================

$tanggalDaftar = '-';
$tanggalDatabase = $user['CREATED_AT'] ?? null;

if (!empty($tanggalDatabase)) {
    try {
        if ($tanggalDatabase instanceof DateTimeInterface) {
            $tanggalDaftar = $tanggalDatabase->format('d F Y');
        } else {
            $timestamp = strtotime((string) $tanggalDatabase);

            if ($timestamp !== false) {
                $tanggalDaftar = date('d F Y', $timestamp);
            }
        }
    } catch (Throwable $e) {
        $tanggalDaftar = '-';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - FoundIt</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            color: #263247;
        }

        .profile-page {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .page-heading p {
            color: #718096;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            padding: 25px;
            border-radius: 14px;
            margin-bottom: 22px;
            box-shadow: 0 3px 12px rgba(0,0,0,.04);
        }

        .profile-photo {
            width: 75px;
            height: 75px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #dce9ff;
            color: #2864c5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
        }

        .profile-header h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-header p {
            color: #718096;
            margin: 0;
        }

        .profile-card {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0,0,0,.04);
        }

        .profile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .profile-card-header h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-card-header p {
            color: #718096;
            margin: 0;
        }

        .btn-edit {
            background: #2864c5;
            color: white;
            text-decoration: none;
            padding: 10px 17px;
            border-radius: 8px;
            font-size: 14px;
            white-space: nowrap;
        }

        .btn-edit:hover {
            background: #194e9e;
            color: white;
        }

        .info-item {
            background: #f8faff;
            border: 1px solid #edf1f7;
            border-radius: 10px;
            padding: 17px;
            height: 100%;
        }

        .info-item span {
            display: block;
            color: #718096;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .info-item strong {
            display: block;
            font-size: 15px;
            color: #263247;
            overflow-wrap: anywhere;
        }

        @media (max-width: 576px) {
            .profile-card-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="profile-page">

    <div class="page-heading">
        <h1>Profil Saya</h1>
        <p>Kelola informasi akun kamu.</p>
    </div>

    <section class="profile-header">
        <div class="profile-photo">
            <?= e($inisial) ?>
        </div>

        <div>
            <h2><?= e($nama) ?></h2>
            <p><?= $nim !== '' ? 'NIM ' . e($nim) : 'NIM belum diisi' ?></p>
        </div>
    </section>

    <section class="profile-card">

        <div class="profile-card-header">
            <div>
                <h2>Informasi Pribadi</h2>
                <p>Informasi akun yang kamu daftarkan.</p>
            </div>

            <a href="edit-profil.php" class="btn-edit">
                Edit Profil
            </a>
        </div>

        <div class="row g-3">

            <div class="col-md-6">
                <div class="info-item">
                    <span>Nama Lengkap</span>
                    <strong><?= e($nama) ?></strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-item">
                    <span>NIM / Nomor Identitas</span>
                    <strong><?= e($nim ?: '-') ?></strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-item">
                    <span>Email</span>
                    <strong><?= e($email) ?></strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-item">
                    <span>Nomor WhatsApp</span>
                    <strong><?= e($telepon ?: '-') ?></strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-item">
                    <span>Fakultas</span>
                    <strong><?= e($fakultas ?: '-') ?></strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-item">
                    <span>Jurusan</span>
                    <strong><?= e($jurusan ?: '-') ?></strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-item">
                    <span>Angkatan</span>
                    <strong><?= e($angkatan ?: '-') ?></strong>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-item">
                    <span>Bergabung Sejak</span>
                    <strong><?= e($tanggalDaftar) ?></strong>
                </div>
            </div>

            <div class="col-md-12">
                <div class="info-item">
                    <span>Aktivitas</span>
                    <strong>
                        <?= $totalLaporan ?> laporan ·
                        <?= $totalKlaim ?> klaim
                        (<?= $klaimDisetujui ?> disetujui)
                    </strong>
                </div>
            </div>

        </div>
    </section>

</div>

</body>
</html>
