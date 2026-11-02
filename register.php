<?php // Developed by Fasbir Sabran
session_start();
require_once __DIR__ . '/config/koneksi.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputRaw = file_get_contents('php://input');
    $jsonData = json_decode($inputRaw, true);

    $nama            = trim($jsonData['nama'] ?? $_POST['nama'] ?? '');
    $email           = trim($jsonData['email'] ?? $_POST['email'] ?? '');
    $password        = $_POST['password'] ?? $jsonData['password'] ?? '';
    $nomor_identitas = trim($jsonData['nim'] ?? $jsonData['nomor_identitas'] ?? $_POST['nim'] ?? $_POST['nomor_identitas'] ?? '');
    $no_telp         = trim($jsonData['nohp'] ?? $jsonData['no_telp'] ?? $_POST['nohp'] ?? $_POST['no_telp'] ?? '');

    $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) 
              || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Validasi keamanan password sisi server
        $hasLength = strlen($password) >= 8;
        $hasUpper  = preg_match('/[A-Z]/', $password);
        $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password);

        if (!$hasLength || !$hasUpper || !$hasSymbol) {
            $error = 'Password harus minimal 8 karakter, mengandung minimal 1 huruf besar (A-Z), dan minimal 1 karakter simbol (!@#$%^&*).';
        } else {
            // Cek duplikasi email
            $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmtEmail->execute([':email' => $email]);

            // Cek duplikasi NIM/nomor identitas jika diisi
            $stmtNim = null;
            if (!empty($nomor_identitas)) {
                $stmtNim = $pdo->prepare("SELECT id FROM users WHERE nomor_identitas = :nim LIMIT 1");
                $stmtNim->execute([':nim' => $nomor_identitas]);
            }

            if ($stmtEmail->fetch()) {
                $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
            } elseif ($stmtNim && $stmtNim->fetch()) {
                $error = 'Nomor Identitas (NIM) sudah terdaftar.';
            } else {
                // Auto-generate ID USRxxx
                $stmtId = $pdo->query("SELECT id FROM users WHERE id LIKE 'USR%' ORDER BY CAST(SUBSTRING(id, 4) AS UNSIGNED) DESC LIMIT 1");
                $lastId = $stmtId->fetchColumn();
                $newId  = $lastId ? 'USR' . str_pad((int)substr($lastId, 3) + 1, 3, '0', STR_PAD_LEFT) : 'USR001';

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmtInsert = $pdo->prepare("INSERT INTO users (id, nama, nomor_identitas, email, password, no_telp, role) VALUES (:id, :nama, :nomor_identitas, :email, :password, :no_telp, 'user')");
                $inserted = $stmtInsert->execute([
                    ':id'              => $newId,
                    ':nama'            => $nama,
                    ':nomor_identitas' => !empty($nomor_identitas) ? $nomor_identitas : null,
                    ':email'           => $email,
                    ':password'        => $hashedPassword,
                    ':no_telp'         => !empty($no_telp) ? $no_telp : null
                ]);

                if ($inserted) {
                    // Auto-login: langsung deklarasikan sesi pengguna
                    $_SESSION['user_id']      = $newId;
                    $_SESSION['nama']         = $nama;
                    $_SESSION['email']        = $email;
                    $_SESSION['role']         = 'user';
                    $_SESSION['is_logged_in'] = true;

                    if ($isJson) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'status'   => 'success',
                            'message'  => 'Registrasi berhasil! Mengalihkan ke dashboard...',
                            'redirect' => 'user/dashboard.php'
                        ]);
                        exit;
                    } else {
                        header("Location: user/dashboard.php");
                        exit;
                    }
                } else {
                    $error = 'Gagal mendaftar. Silakan coba lagi.';
                }
            }
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
    <title>Registrasi User - FoundIt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="register-container container-fluid">
        <div class="row g-0 min-vh-100">
            <div class="col-lg-5 col-xl-4 register-info">
                <div class="logo"><span>Found</span>It</div>
                <h1>Lengkapi Profilmu.</h1>
                <p>Lengkapi data diri kamu agar dapat menggunakan seluruh fitur FoundIt.</p>
                <a href="login.php" class="back-home text-white text-decoration-none">← Kembali ke Login</a>
            </div>

            <div class="col-lg-7 col-xl-8 register-box">
                <div class="register-header">
                    <h2>Registrasi Pengguna Baru</h2>
                    <p>Masukkan data diri kamu dengan benar.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form id="registerForm" action="register.php" method="POST">
                    <div class="form-group mb-3">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="nim">NIM / Nomor Identitas</label>
                        <input type="text" id="nim" name="nim" class="form-control" placeholder="Masukkan NIM atau Nomor Identitas">
                    </div>

                    <div class="form-group mb-3">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Masukkan email" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" minlength="8" required>
                        <small class="text-muted d-block mt-1">Minimal 8 karakter, wajib mengandung minimal 1 huruf besar (A-Z) dan 1 simbol (!@#$%^&*).</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="nohp">Nomor WhatsApp / Telp</label>
                        <input type="tel" id="nohp" name="nohp" class="form-control" placeholder="Contoh: 081234567890">
                    </div>

                    <button type="submit" class="btn-register w-100 mt-2">Daftar Akun Baru</button>
                </form>
                
                <p class="mt-3 text-center">
                    Sudah memiliki akun? <a href="login.php">Login di sini</a>
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>