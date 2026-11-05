
<?php
require_once 'config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = strtolower(trim($_POST['role'] ?? ''));

    if ($email === '' || $password === '' || $role === '') {

        $error = 'Semua kolom harus diisi!';

    } elseif (!in_array($role, ['user', 'admin'], true)) {

        $error = 'Pilihan akun tidak valid!';

    } else {

        try {

            // Mencari akun berdasarkan email
            $stmt = $conn->prepare("
                SELECT ID, NAMA, EMAIL, PASSWORD, ROLE
                FROM USERS
                WHERE LOWER(TRIM(EMAIL)) = LOWER(TRIM(:email))
            ");

            $stmt->execute([
                ':email' => $email
            ]);

            $akun = $stmt->fetch(PDO::FETCH_ASSOC);

            // Memeriksa apakah email terdaftar
            if (!$akun) {

                $error = 'Email belum terdaftar. Silakan daftar terlebih dahulu.';

            // Memeriksa password
            } elseif (!password_verify($password, $akun['PASSWORD'])) {

                $error = 'Password yang kamu masukkan salah.';

            // Memeriksa jenis akun
            } elseif (strtolower(trim($akun['ROLE'])) !== $role) {

                $error = 'Jenis akun yang kamu pilih tidak sesuai.';

            } else {

                // Membuat session login
                session_regenerate_id(true);

                $_SESSION['is_logged_in'] = true;
                $_SESSION['id_user'] = $akun['ID'];
                $_SESSION['nama'] = $akun['NAMA'];
                $_SESSION['email'] = $akun['EMAIL'];
                $_SESSION['role'] = strtolower(trim($akun['ROLE']));

                // Mengarahkan berdasarkan role
                if ($role === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: user/dashboard.php');
                }

                exit;
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $error = 'Terjadi kesalahan saat menghubungkan ke database.';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - FoundIt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="css/main.css">
</head>

<body>

<div class="login-container container-fluid">

    <div class="row g-0 min-vh-100">

        <!-- BAGIAN KIRI -->
        <div class="col-md-6 login-info">

            <div class="logo">
                <span>Found</span>It
            </div>

            <h1>Temukan Kembali Barangmu.</h1>

            <p>
                Platform barang hilang dan ditemukan
                untuk seluruh civitas kampus.
            </p>

            <a href="index.php" class="back-home">
                ← Kembali ke Beranda
            </a>

        </div>

        <!-- BAGIAN KANAN -->
        <div class="col-md-6 login-box">

            <div class="login-header">

                <h2>Selamat Datang</h2>

                <p>
                    Silakan pilih akun untuk masuk ke FoundIt.
                </p>

            </div>

            <!-- PESAN ERROR -->
            <?php if (!empty($error)): ?>

                <div class="alert alert-danger mb-3">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php endif; ?>

            <!-- PILIH ROLE -->
            <div class="role-selection">

                <button
                    type="button"
                    class="role-card <?= (($_POST['role'] ?? 'user') === 'user') ? 'active' : ''; ?>"
                    onclick="pilihRole('user')"
                >

                    <div class="role-icon">👤</div>

                    <div>
                        <h3>User</h3>
                        <p>Cari dan laporkan barang</p>
                    </div>

                </button>

                <button
                    type="button"
                    class="role-card <?= (($_POST['role'] ?? '') === 'admin') ? 'active' : ''; ?>"
                    onclick="pilihRole('admin')"
                >

                    <div class="role-icon">🛠️</div>

                    <div>
                        <h3>Admin</h3>
                        <p>Kelola FoundIt</p>
                    </div>

                </button>

            </div>

            <!-- FORM LOGIN -->
            <form id="loginForm" action="login.php" method="POST">

                <input
                    type="hidden"
                    id="role"
                    name="role"
                    value="<?= htmlspecialchars($_POST['role'] ?? 'user', ENT_QUOTES, 'UTF-8'); ?>"
                >

                <!-- EMAIL -->
                <div class="form-group mb-3">

                    <label for="email">Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Masukkan email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>

                <!-- PASSWORD -->
                <div class="form-group mb-3">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        minlength="8"
                        required
                    >

                    <div class="password-requirements">

                        <p id="lengthCheck">
                            ✕ Minimal 8 karakter
                        </p>

                        <p id="uppercaseCheck">
                            ✕ Mengandung huruf kapital (A-Z)
                        </p>

                        <p id="symbolCheck">
                            ✕ Mengandung simbol (!@#$%^&*)
                        </p>

                    </div>

                </div>

                <!-- TOMBOL LOGIN -->
                <button type="submit" class="btn-login w-100">
                    Login sebagai User
                </button>

            </form>

            <!-- LINK REGISTER -->
            <p class="login-note mt-3" id="registerNote">

                Belum memiliki akun?

                <a href="user/register.php" id="daftarLink">
                    Daftar sebagai User
                </a>

            </p>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/login.js"></script>

</body>
</html>
