<?php
// Mulai session dan panggil koneksi database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'user'); // Menangkap role dari input hidden form

    if (!empty($email) && !empty($password)) {
        try {
            // Cek ke tabel USERS di database Oracle APEX
            $stmt = $conn->prepare("SELECT * FROM USERS WHERE EMAIL = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['PASSWORD'])) {
                // Set session user
                $_SESSION['id_user'] = $user['ID'] ?? null;
                $_SESSION['nama'] = $user['NAMA'];
                $_SESSION['email'] = $user['EMAIL'];
                $_SESSION['role'] = $user['ROLE'];

                // Redirect berdasarkan role
                if ($user['ROLE'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit;
            } else {
                $error = "Email atau password salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Semua kolom harus diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FoundIt</title>

    <!-- Menghubungkan Bootstrap dan file CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

    <!-- CONTAINER UTAMA LOGIN -->
    <div class="login-container container-fluid">
        <div class="row g-0 min-vh-100">

            <!-- BAGIAN KIRI: INFORMASI FOUNDIT -->
            <div class="col-md-6 login-info">
                <div class="logo"><span>Found</span>It</div>
                <h1>Temukan Kembali Barangmu.</h1>
                <p>Platform barang hilang dan ditemukan untuk seluruh civitas kampus.</p>
                
                <!-- Tombol kembali ke halaman utama -->
                <a href="index.php" class="back-home">
                    ← Kembali ke Beranda
                </a>
            </div>

            <!-- BAGIAN KANAN: FORM LOGIN -->
            <div class="col-md-6 login-box">

                <div class="login-header">
                    <h2>Selamat Datang</h2>
                    <p>Silakan pilih akun untuk masuk ke FoundIt.</p>
                </div>

                <!-- Pesan Error jika gagal login -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-3"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- PILIH ROLE PENGGUNA -->
                <div class="role-selection">
                    <button type="button" class="role-card active" onclick="pilihRole('user')">
                        <div class="role-icon">👤</div>
                        <div>
                            <h3>User</h3>
                            <p>Cari dan laporkan barang</p>
                        </div>
                    </button>

                    <button type="button" class="role-card" onclick="pilihRole('admin')">
                        <div class="role-icon">🛠️</div>
                        <div>
                            <h3>Admin</h3>
                            <p>Kelola FoundIt</p>
                        </div>
                    </button>
                </div>

                <!-- FORM LOGIN -->
                <form id="loginForm" action="login.php" method="POST">

                    <!-- Menyimpan role yang dipilih -->
                    <input type="hidden" id="role" name="role" value="user">

                    <!-- INPUT EMAIL -->
                    <div class="form-group mb-3">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Masukkan email"
                            required
                        >
                    </div>

                    <!-- INPUT PASSWORD -->
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

                        <!-- MENAMPILKAN PERSYARATAN PASSWORD -->
                        <div class="password-requirements">
                            <p id="lengthCheck">✕ Minimal 8 karakter</p>
                            <p id="uppercaseCheck">✕ Mengandung huruf kapital (A-Z)</p>
                            <p id="symbolCheck">✕ Mengandung simbol (!@#$%^&*)</p>
                        </div>
                    </div>

                    <!-- TOMBOL UNTUK LOGIN -->
                    <button type="submit" class="btn-login w-100">
                        Login sebagai User
                    </button>

                </form>

                <!-- LINK MENUJU HALAMAN REGISTRASI -->
                <p class="login-note mt-3" id="registerNote">
                    Belum memiliki akun?
                    <a href="user/register.php" id="daftarLink">
                        Daftar sebagai User
                    </a>
                </p>

            </div>
        </div>
    </div>

    <!-- Menghubungkan Bootstrap dan JavaScript login -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/login.js"></script>

</body>
</html>