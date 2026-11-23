
<?php
// ==========================================
// EDIT PROFIL USER - FOUNDIT
// ==========================================

require_once '../config/koneksi.php';

// ==========================================
// PENGECEKAN LOGIN
// ==========================================

if (
    !isset($_SESSION['is_logged_in']) ||
    $_SESSION['is_logged_in'] !== true ||
    ($_SESSION['role'] ?? '') !== 'user'
) {
    header("Location: ../login.php");
    exit;
}

$idUser = $_SESSION['id_user'] ?? '';

if ($idUser === '') {
    header("Location: ../login.php");
    exit;
}

// ==========================================
// MENGAMBIL DATA USER
// ==========================================

try {
    $stmt = $conn->prepare("
        SELECT ID, NAMA, NOMOR_IDENTITAS, EMAIL, PASSWORD, NO_TELP
        FROM USERS
        WHERE ID = :id
    ");

    $stmt->execute([':id' => $idUser]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        session_destroy();
        header("Location: ../login.php");
        exit;
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Gagal mengambil data profil.");
}

// ==========================================
// DATA FORM
// ==========================================

$errors = [];
$success = '';

$form = [
    'nama' => $currentUser['NAMA'] ?? '',
    'nomor_identitas' => $currentUser['NOMOR_IDENTITAS'] ?? '',
    'email' => $currentUser['EMAIL'] ?? '',
    'no_telp' => $currentUser['NO_TELP'] ?? ''
];

// ==========================================
// PROSES FORM
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form['nama'] = trim($_POST['nama'] ?? '');
    $form['nomor_identitas'] = trim($_POST['nomor_identitas'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['no_telp'] = trim($_POST['no_telp'] ?? '');

    $passLama = $_POST['password_lama'] ?? '';
    $passBaru = $_POST['password_baru'] ?? '';
    $passKonfirmasi = $_POST['password_konfirmasi'] ?? '';

    // ======================================
    // VALIDASI NAMA
    // ======================================

    if ($form['nama'] === '' || mb_strlen($form['nama']) > 100) {
        $errors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
    }

    // ======================================
    // VALIDASI EMAIL
    // ======================================

    if (
        !filter_var($form['email'], FILTER_VALIDATE_EMAIL) ||
        mb_strlen($form['email']) > 100
    ) {
        $errors[] = 'Format email tidak valid.';
    }

    // ======================================
    // VALIDASI NIM
    // ======================================

    if (
        $form['nomor_identitas'] !== '' &&
        !preg_match('/^[A-Za-z0-9]{3,20}$/', $form['nomor_identitas'])
    ) {
        $errors[] = 'NIM hanya boleh berisi huruf dan angka (3-20 karakter).';
    }

    // ======================================
    // VALIDASI NOMOR TELEPON
    // ======================================

    if (
        $form['no_telp'] !== '' &&
        !preg_match('/^[0-9+\-\s]{8,20}$/', $form['no_telp'])
    ) {
        $errors[] = 'Nomor WhatsApp tidak valid (8-20 karakter).';
    }

    // ======================================
    // CEK EMAIL DUPLIKAT
    // ======================================

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM USERS
            WHERE LOWER(TRIM(EMAIL)) = LOWER(TRIM(:email))
            AND ID <> :id
        ");

        $stmt->execute([
            ':email' => $form['email'],
            ':id' => $idUser
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'Email sudah digunakan akun lain.';
        }
    }

    // ======================================
    // CEK NIM DUPLIKAT
    // ======================================

    if (empty($errors) && $form['nomor_identitas'] !== '') {

        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM USERS
            WHERE NOMOR_IDENTITAS = :nim
            AND ID <> :id
        ");

        $stmt->execute([
            ':nim' => $form['nomor_identitas'],
            ':id' => $idUser
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'NIM sudah digunakan akun lain.';
        }
    }

    // ======================================
    // GANTI PASSWORD (OPSIONAL)
    // ======================================

    $hashBaru = null;

    if (
        $passLama !== '' ||
        $passBaru !== '' ||
        $passKonfirmasi !== ''
    ) {

        if (!password_verify($passLama, trim($currentUser['PASSWORD']))) {

            $errors[] = 'Password saat ini salah.';

        } elseif ($passBaru !== $passKonfirmasi) {

            $errors[] = 'Konfirmasi password baru tidak sama.';

        } elseif (
            strlen($passBaru) < 8 ||
            !preg_match('/[A-Z]/', $passBaru) ||
            !preg_match('/[^A-Za-z0-9]/', $passBaru)
        ) {

            $errors[] = 'Password baru minimal 8 karakter, mengandung huruf kapital dan simbol.';

        } else {

            $hashBaru = password_hash($passBaru, PASSWORD_BCRYPT);

        }
    }

    // ======================================
    // UPDATE DATA PROFIL
    // ======================================

    if (empty($errors)) {

        try {

            $sql = "
                UPDATE USERS
                SET
                    NAMA = :nama,
                    NOMOR_IDENTITAS = :nim,
                    EMAIL = :email,
                    NO_TELP = :telp
            ";

            if ($hashBaru !== null) {
                $sql .= ", PASSWORD = :password";
            }

            $sql .= " WHERE ID = :id";

            $params = [
                ':nama' => $form['nama'],
                ':nim' => $form['nomor_identitas'] !== ''
                    ? $form['nomor_identitas']
                    : null,
                ':email' => $form['email'],
                ':telp' => $form['no_telp'] !== ''
                    ? $form['no_telp']
                    : null,
                ':id' => $idUser
            ];

            if ($hashBaru !== null) {
                $params[':password'] = $hashBaru;
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Perbarui session
            $_SESSION['nama'] = $form['nama'];
            $_SESSION['email'] = $form['email'];

            // Perbarui data lokal
            $currentUser['NAMA'] = $form['nama'];
            $currentUser['NOMOR_IDENTITAS'] = $form['nomor_identitas'];
            $currentUser['EMAIL'] = $form['email'];
            $currentUser['NO_TELP'] = $form['no_telp'];

            if ($hashBaru !== null) {
                $currentUser['PASSWORD'] = $hashBaru;
            }

            $success = 'Profil berhasil diperbarui.';

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $errors[] = 'Profil gagal diperbarui. Silakan coba lagi.';

        }
    }
}

$namaUser = $_SESSION['nama'] ?? 'User';
$inisial = strtoupper(substr($namaUser, 0, 1));

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profil - FoundIt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

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
            <a href="lapor-ditemukan.php">Lapor Barang Ditemukan</a>
            <a href="klaim.php">Klaim Barang</a>
            <a href="status-klaim.php">Status Klaim</a>
            <a href="riwayat-laporan.php">Riwayat Laporan</a>
            <a href="edit-profil.php" class="active">Profil</a>
        </nav>

        <a
            href="dashboard.php?action=logout"
            class="logout"
            onclick="return confirm('Apakah Anda yakin ingin keluar?');"
        >
            Keluar
        </a>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="main-content">

        <header class="topbar mb-4">

            <div>
                <h1 class="fw-bold mb-2">Edit Profil</h1>

                <p class="text-muted mb-0">
                    Perbarui informasi akun kamu.
                </p>
            </div>

            <div class="user-profile">

                <div class="profile-icon">
                    <?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div>
                    <strong>
                        <?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8'); ?>
                    </strong>

                    <small class="text-muted">User</small>
                </div>

            </div>

        </header>

        <section class="card border-0 shadow-sm p-4">

            <div class="mb-4">
                <h4 class="fw-bold">Informasi Pribadi</h4>

                <p class="text-muted">
                    Ubah data yang ingin kamu perbarui.
                </p>
            </div>

            <!-- PESAN BERHASIL -->

            <?php if ($success !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- PESAN ERROR -->

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li>
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- FORM EDIT PROFIL -->

            <form method="POST" action="edit-profil.php" autocomplete="off">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label for="nama" class="form-label">
                            Nama Lengkap
                        </label>

                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            class="form-control"
                            maxlength="100"
                            value="<?= htmlspecialchars($form['nama'], ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="nim" class="form-label">
                            NIM / Nomor Identitas
                        </label>

                        <input
                            type="text"
                            id="nim"
                            name="nomor_identitas"
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($form['nomor_identitas'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            maxlength="100"
                            value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="nohp" class="form-label">
                            Nomor WhatsApp
                        </label>

                        <input
                            type="tel"
                            id="nohp"
                            name="no_telp"
                            class="form-control"
                            maxlength="20"
                            value="<?= htmlspecialchars($form['no_telp'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                </div>

                <hr class="my-4">

                <h5 class="fw-bold mb-1">
                    Ganti Password
                </h5>

                <p class="text-muted small">
                    Kosongkan semua kolom password jika tidak ingin mengganti password.
                </p>

                <div class="mb-3">
                    <label for="pl" class="form-label">
                        Password Saat Ini
                    </label>

                    <input
                        type="password"
                        id="pl"
                        name="password_lama"
                        class="form-control"
                        autocomplete="current-password"
                    >
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label for="pb" class="form-label">
                            Password Baru
                        </label>

                        <input
                            type="password"
                            id="pb"
                            name="password_baru"
                            class="form-control"
                            minlength="8"
                            autocomplete="new-password"
                        >

                        <small class="text-muted">
                            Minimal 8 karakter, ada huruf kapital dan simbol.
                        </small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="pk" class="form-label">
                            Konfirmasi Password Baru
                        </label>

                        <input
                            type="password"
                            id="pk"
                            name="password_konfirmasi"
                            class="form-control"
                            autocomplete="new-password"
                        >
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">

                    <a
                        href="dashboard.php"
                        class="btn btn-outline-secondary"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Simpan Perubahan
                    </button>

                </div>

            </form>

        </section>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
