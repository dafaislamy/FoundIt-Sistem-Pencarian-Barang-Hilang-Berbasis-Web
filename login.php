<?php // Developed by Fasbir Sabran
session_start();
require_once __DIR__ . '/config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputRaw = file_get_contents('php://input');
    $jsonData = json_decode($inputRaw, true);

    $email    = trim($jsonData['email'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? $jsonData['password'] ?? '';
    $role     = trim($jsonData['role'] ?? $_POST['role'] ?? 'user');

    $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) 
              || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $userFound = null;
        $userRole  = null;

        if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch();

            if ($admin && (password_verify($password, $admin['password']) || $password === $admin['password'] || md5($password) === $admin['password'])) {
                $userFound = $admin;
                $userRole  = 'admin';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin' LIMIT 1");
                $stmt->execute([':email' => $email]);
                $userAdmin = $stmt->fetch();

                if ($userAdmin && (password_verify($password, $userAdmin['password']) || $password === $userAdmin['password'] || md5($password) === $userAdmin['password'])) {
                    $userFound = $userAdmin;
                    $userRole  = 'admin';
                }
            }
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['password']) || $password === $user['password'] || md5($password) === $user['password'])) {
                $userFound = $user;
                $userRole  = $user['role'] ?? 'user';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $admin = $stmt->fetch();

                if ($admin && (password_verify($password, $admin['password']) || $password === $admin['password'] || md5($password) === $admin['password'])) {
                    $userFound = $admin;
                    $userRole  = 'admin';
                }
            }
        }

        if ($userFound) {
            $_SESSION['user_id']      = $userFound['id'];
            $_SESSION['nama']         = $userFound['nama'];
            $_SESSION['email']        = $userFound['email'];
            $_SESSION['role']         = $userRole;
            $_SESSION['is_logged_in'] = true;

            $redirectUrl = ($userRole === 'admin') ? 'admin/admin-dashboard.html' : 'user/dashboard.php';

            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status'   => 'success',
                    'message'  => 'Login berhasil',
                    'redirect' => $redirectUrl,
                    'user'     => ['id' => $userFound['id'], 'nama' => $userFound['nama'], 'email' => $userFound['email'], 'role' => $userRole]
                ]);
                exit;
            } else {
                header("Location: {$redirectUrl}");
                exit;
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }

    if ($isJson && !empty($error)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FoundIt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="login-container container-fluid">
        <div class="row g-0 min-vh-100">
            <div class="col-md-6 login-info">
                <div class="logo"><span>Found</span>It</div>
                <h1>Temukan Kembali Barangmu.</h1>
                <p>Platform barang hilang dan ditemukan untuk seluruh civitas kampus.</p>
                <a href="index.html" class="back-home">← Kembali ke Beranda</a>
            </div>
            <div class="col-md-6 login-box">
                <div class="login-header">
                    <h2>Selamat Datang</h2>
                    <p>Silakan pilih akun untuk masuk ke FoundIt.</p>
                </div>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <div class="role-selection">
                    <button type="button" class="role-card active" onclick="pilihRole('user')">
                        <div class="role-icon">👤</div>
                        <div><h3>User</h3><p>Cari dan laporkan barang</p></div>
                    </button>
                    <button type="button" class="role-card" onclick="pilihRole('admin')">
                        <div class="role-icon">🛠️</div>
                        <div><h3>Admin</h3><p>Kelola FoundIt</p></div>
                    </button>
                </div>
                <form action="login.php" method="POST">
                    <input type="hidden" id="role" name="role" value="user">
                    <div class="form-group mb-3">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Masukkan email" required class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Masukkan password" minlength="8" required class="form-control">
                        <div class="password-requirements">
                            <p id="lengthCheck">✕ Minimal 8 karakter</p>
                            <p id="uppercaseCheck">✕ Mengandung huruf kapital (A-Z)</p>
                            <p id="symbolCheck">✕ Mengandung simbol (!@#$%^&*)</p>
                        </div>
                    </div>
                    <button type="submit" class="btn-login w-100 mt-2">Login sebagai User</button>
                </form>
                <p class="login-note mt-3" id="registerNote">
                    Belum memiliki akun? <a href="register.php" id="daftarLink">Daftar sebagai User</a>
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function pilihRole(role) {
        document.getElementById("role").value = role;
        const roleCards = document.querySelectorAll(".role-card");
        const btn = document.querySelector(".btn-login");
        const note = document.getElementById("registerNote");
        roleCards.forEach(c => c.classList.remove("active"));
        if (role === 'user') {
            roleCards[0].classList.add("active");
            btn.innerText = "Login sebagai User";
            note.style.display = "block";
        } else {
            roleCards[1].classList.add("active");
            btn.innerText = "Login sebagai Admin";
            note.style.display = "none";
        }
    }
    const pwd = document.getElementById("password");
    if (pwd) {
        pwd.addEventListener("input", function() {
            const val = pwd.value;
            const l = val.length >= 8;
            const u = /[A-Z]/.test(val);
            const s = /[^A-Za-z0-9]/.test(val);
            const lc = document.getElementById("lengthCheck");
            const uc = document.getElementById("uppercaseCheck");
            const sc = document.getElementById("symbolCheck");
            if (lc) { lc.className = l ? "valid" : ""; lc.textContent = (l ? "✓ " : "✕ ") + "Minimal 8 karakter"; }
            if (uc) { uc.className = u ? "valid" : ""; uc.textContent = (u ? "✓ " : "✕ ") + "Mengandung huruf kapital (A-Z)"; }
            if (sc) { sc.className = s ? "valid" : ""; sc.textContent = (s ? "✓ " : "✕ ") + "Mengandung simbol (!@#$%^&*)"; }
        });
    }
    </script>
</body>
</html>