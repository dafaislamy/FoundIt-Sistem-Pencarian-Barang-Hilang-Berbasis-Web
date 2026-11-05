<?php
// Mulai session dan panggil koneksi database Oracle
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Menangkap data dari form HTML aslimu
    $nama     = trim($_POST['nama'] ?? '');
    $nim      = trim($_POST['nim'] ?? '');
    $fakultas = trim($_POST['fakultas'] ?? '');
    $jurusan  = trim($_POST['jurusan'] ?? '');
    $angkatan = trim($_POST['angkatan'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $nohp     = trim($_POST['nohp'] ?? '');
    
    // Password default (bisa diubah nanti)
    $password = password_hash('Password123!', PASSWORD_DEFAULT); 
    $role     = 'user';

    if (empty($nama) || empty($nim) || empty($email)) {
        $error = "Nama, NIM, dan Email wajib diisi!";
    } else {
        try {
            // Cek apakah email atau NIM sudah terdaftar
            $stmt_cek = $conn->prepare("SELECT COUNT(*) FROM USERS WHERE EMAIL = :email OR NIM = :nim");
            $stmt_cek->execute(['email' => $email, 'nim' => $nim]);
            
            if ($stmt_cek->fetchColumn() > 0) {
                $error = "Email atau NIM sudah terdaftar!";
            } else {
                // Simpan data ke tabel USERS di Oracle APEX
                $sql = "INSERT INTO USERS (NAMA, NIM, FAKULTAS, JURUSAN, ANGKATAN, EMAIL, NOHP, PASSWORD, ROLE) 
                        VALUES (:nama, :nim, :fakultas, :jurusan, :angkatan, :email, :nohp, :password, :role)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'nama'     => $nama,
                    'nim'      => $nim,
                    'fakultas' => $fakultas,
                    'jurusan'  => $jurusan,
                    'angkatan' => $angkatan,
                    'email'    => $email,
                    'nohp'     => $nohp,
                    'password' => $password,
                    'role'     => $role
                ]);

                // Jika berhasil, arahkan ke halaman login
                echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location.href = '../login.php';</script>";
                exit;
            }
        } catch (PDOException $e) {
            $error = "Registrasi Gagal: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Profil - FoundIt</title>

    <!-- Bootstrap dan CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>

    <!-- CONTAINER REGISTRASI -->
    <div class="register-container container-fluid">
        <div class="row g-0 min-vh-100">

            <!-- BAGIAN KIRI -->
            <div class="col-lg-5 col-xl-4 register-info">
                <div class="logo"><span>Found</span>It</div>
                <h1>Lengkapi Profilmu.</h1>
                <p>Lengkapi data diri kamu agar dapat menggunakan seluruh fitur FoundIt.</p>

                <!-- Langkah registrasi -->
                <div class="steps">
                    <div class="step active">
                        <span>1</span>
                        <div><strong>Data Diri</strong><small>Informasi mahasiswa</small></div>
                    </div>
                    <div class="step">
                        <span>2</span>
                        <div><strong>Profil Selesai</strong><small>Mulai menggunakan FoundIt</small></div>
                    </div>
                </div>
            </div>

            <!-- BAGIAN KANAN -->
            <div class="col-lg-7 col-xl-8 register-box">
                <div class="register-header">
                    <h2>Data Mahasiswa</h2>
                    <p>Masukkan data diri kamu dengan benar.</p>
                </div>

                <!-- Tampilkan pesan error jika ada -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-3"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- FORM REGISTRASI DENGAN KODE HTML ASLIMU -->
                <form id="registerForm" action="register.php" method="POST" enctype="multipart/form-data">

                    <!-- Upload foto profil -->
                    <div class="photo-section">
                        <div class="photo-preview">👤</div>
                        <div>
                            <label for="photo">Foto Profil</label>
                            <input type="file" id="photo" name="photo" accept="image/*">
                            <small>Opsional. Format JPG atau PNG.</small>
                        </div>
                    </div>

                    <!-- Input nama lengkap -->
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                    </div>

                    <!-- Input NIM -->
                    <div class="form-group">
                        <label for="nim">NIM</label>
                        <input type="text" id="nim" name="nim" class="form-control" placeholder="Masukkan NIM" required>
                    </div>

                    <!-- Pilihan fakultas dan program studi -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fakultas">Fakultas</label>
                                <select id="fakultas" name="fakultas" class="form-select" required>
                                    <option value="">Pilih Fakultas</option>
                                    <option value="Fakultas Teknik">Fakultas Teknik</option>
                                    <option value="Fakultas Ekonomi dan Bisnis">Fakultas Ekonomi dan Bisnis</option>
                                    <option value="Fakultas Hukum">Fakultas Hukum</option>
                                    <option value="Fakultas Pertanian">Fakultas Pertanian</option>
                                    <option value="Fakultas Ilmu Sosial dan Ilmu Politik">Fakultas Ilmu Sosial dan Ilmu Politik</option>
                                    <option value="Fakultas Matematika dan Ilmu Pengetahuan Alam">Fakultas Matematika dan Ilmu Pengetahuan Alam</option>
                                    <option value="Fakultas Keguruan dan Ilmu Pendidikan">Fakultas Keguruan dan Ilmu Pendidikan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jurusan">Program Studi</label>
                                <input type="text" id="jurusan" name="jurusan" class="form-control" placeholder="Contoh: Teknik Informatika" required>
                            </div>
                        </div>
                    </div>

                    <!-- Pilihan angkatan -->
                    <div class="form-group">
                        <label for="angkatan">Angkatan</label>
                        <select id="angkatan" name="angkatan" class="form-select" required>
                            <option value="">Pilih Angkatan</option>
                            <option value="2022">2022</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                        </select>
                    </div>

                    <!-- Input email -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Masukkan email" required>
                    </div>

                    <!-- Input nomor WhatsApp -->
                    <div class="form-group">
                        <label for="nohp">Nomor WhatsApp</label>
                        <input type="tel" id="nohp" name="nohp" class="form-control" placeholder="Contoh: 081234567890" required>
                    </div>

                    <!-- Tombol simpan -->
                    <button type="submit" class="btn-register">Simpan & Lanjutkan</button>
                </form>
            </div>

        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/register.js"></script>
</body>
</html>