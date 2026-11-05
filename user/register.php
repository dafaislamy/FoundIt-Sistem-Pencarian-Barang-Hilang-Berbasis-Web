
<?php
require_once '../config/koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Mengambil data dari form
    $nama     = trim($_POST['nama'] ?? '');
    $nim      = trim($_POST['nim'] ?? '');
    $fakultas = trim($_POST['fakultas'] ?? '');
    $jurusan  = trim($_POST['jurusan'] ?? '');
    $angkatan = trim($_POST['angkatan'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $nohp     = trim($_POST['nohp'] ?? '');
    $password = $_POST['password'] ?? '';

    try {

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validasi semua input
        if (
            $nama === '' || $nim === '' ||
            $fakultas === '' || $jurusan === '' ||
            $angkatan === '' || $email === '' ||
            $nohp === '' || $password === ''
        ) {
            throw new Exception('Semua data wajib diisi!');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid!');
        }

        if (
            strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            throw new Exception(
                'Password minimal 8 karakter, memiliki huruf kapital dan simbol!'
            );
        }

        // Memeriksa email atau NIM yang sudah terdaftar
        $cek = $conn->prepare("
            SELECT COUNT(*)
            FROM USERS
            WHERE UPPER(EMAIL) = UPPER(:email)
               OR NOMOR_IDENTITAS = :nim
        ");

        $cek->execute([
            ':email' => $email,
            ':nim'   => $nim
        ]);

        if ((int)$cek->fetchColumn() > 0) {
            throw new Exception(
                'Email atau NIM sudah terdaftar!'
            );
        }

        // Membuat ID user otomatis
        $idQuery = $conn->query("
            SELECT NVL(MAX(TO_NUMBER(SUBSTR(ID, 4))), 0) + 1
            FROM USERS
            WHERE REGEXP_LIKE(ID, '^USR[0-9]+$')
        ");

        $nomorId = (int)$idQuery->fetchColumn();

        $idUser = 'USR' . str_pad(
            $nomorId,
            3,
            '0',
            STR_PAD_LEFT
        );

        // Mengenkripsi password menggunakan bcrypt
        $passwordHash = password_hash(
            $password,
            PASSWORD_BCRYPT
        );

        // Menyimpan data ke tabel USERS
        $sql = "
            INSERT INTO USERS (
                ID,
                NAMA,
                NOMOR_IDENTITAS,
                EMAIL,
                PASSWORD,
                NO_TELP,
                CREATED_AT,
                ROLE,
                FAKULTAS,
                JURUSAN,
                ANGKATAN
            ) VALUES (
                :id,
                :nama,
                :nim,
                :email,
                :password,
                :nohp,
                SYSDATE,
                :role,
                :fakultas,
                :jurusan,
                :angkatan
            )
        ";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':id'       => $idUser,
            ':nama'     => $nama,
            ':nim'      => $nim,
            ':email'    => $email,
            ':password' => $passwordHash,
            ':nohp'     => $nohp,
            ':role'     => 'user',
            ':fakultas' => $fakultas,
            ':jurusan'  => $jurusan,
            ':angkatan' => $angkatan
        ]);

        // Jika berhasil
        echo "
            <script>
                alert('Registrasi berhasil! Silakan login.');
                window.location.href = '../login.php';
            </script>
        ";
        exit;

    } catch (Exception $e) {

        $error = $e->getMessage();

    } catch (PDOException $e) {

        $error = 'Registrasi gagal: ' . $e->getMessage();

    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lengkapi Profil - FoundIt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../css/main.css">
</head>

<body>

<div class="register-container container-fluid">

    <div class="row g-0 min-vh-100">

        <!-- BAGIAN KIRI -->
        <div class="col-lg-5 col-xl-4 register-info">

            <div class="logo">
                <span>Found</span>It
            </div>

            <h1>Lengkapi Profilmu.</h1>

            <p>
                Lengkapi data diri kamu agar dapat menggunakan
                seluruh fitur FoundIt.
            </p>

            <div class="steps">

                <div class="step active">
                    <span>1</span>

                    <div>
                        <strong>Data Diri</strong>
                        <small>Informasi mahasiswa</small>
                    </div>
                </div>

                <div class="step">
                    <span>2</span>

                    <div>
                        <strong>Profil Selesai</strong>
                        <small>Mulai menggunakan FoundIt</small>
                    </div>
                </div>

            </div>

        </div>


        <!-- BAGIAN KANAN -->
        <div class="col-lg-7 col-xl-8 register-box">

            <div class="register-header">

                <h2>Data Mahasiswa</h2>

                <p>
                    Masukkan data diri kamu dengan benar.
                </p>

            </div>


            <!-- PESAN ERROR -->
            <?php if (!empty($error)): ?>

                <div class="alert alert-danger mb-3">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php endif; ?>


            <!-- FORM REGISTRASI -->
            <form
                id="registerForm"
                action="register.php"
                method="POST"
            >

                <!-- NAMA -->
                <div class="form-group">

                    <label for="nama">Nama Lengkap</label>

                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        class="form-control"
                        placeholder="Masukkan nama lengkap"
                        value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>


                <!-- NIM -->
                <div class="form-group">

                    <label for="nim">NIM</label>

                    <input
                        type="text"
                        id="nim"
                        name="nim"
                        class="form-control"
                        placeholder="Masukkan NIM"
                        value="<?= htmlspecialchars($_POST['nim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>


                <!-- FAKULTAS DAN JURUSAN -->
                <div class="row">

                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="fakultas">Fakultas</label>

                            <select
                                id="fakultas"
                                name="fakultas"
                                class="form-select"
                                required
                            >

                                <option value="">Pilih Fakultas</option>

                                <?php
                                $daftarFakultas = [
                                    'Fakultas Teknik',
                                    'Fakultas Ekonomi dan Bisnis',
                                    'Fakultas Hukum',
                                    'Fakultas Pertanian',
                                    'Fakultas Ilmu Sosial dan Ilmu Politik',
                                    'Fakultas Matematika dan Ilmu Pengetahuan Alam',
                                    'Fakultas Keguruan dan Ilmu Pendidikan'
                                ];

                                foreach ($daftarFakultas as $f) {
                                    $selected = (
                                        ($_POST['fakultas'] ?? '') === $f
                                    ) ? 'selected' : '';

                                    echo '<option value="' .
                                        htmlspecialchars($f, ENT_QUOTES, 'UTF-8') .
                                        '" ' . $selected . '>' .
                                        htmlspecialchars($f, ENT_QUOTES, 'UTF-8') .
                                        '</option>';
                                }
                                ?>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="jurusan">Program Studi</label>

                            <input
                                type="text"
                                id="jurusan"
                                name="jurusan"
                                class="form-control"
                                placeholder="Contoh: Teknik Informatika"
                                value="<?= htmlspecialchars($_POST['jurusan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >

                        </div>

                    </div>

                </div>


                <!-- ANGKATAN -->
                <div class="form-group">

                    <label for="angkatan">Angkatan</label>

                    <select
                        id="angkatan"
                        name="angkatan"
                        class="form-select"
                        required
                    >

                        <option value="">Pilih Angkatan</option>

                        <?php
                        for ($tahun = 2022; $tahun <= 2026; $tahun++) {

                            $selected = (
                                ($_POST['angkatan'] ?? '') == $tahun
                            ) ? 'selected' : '';

                            echo "<option value='$tahun' $selected>$tahun</option>";
                        }
                        ?>

                    </select>

                </div>


                <!-- EMAIL -->
                <div class="form-group">

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


                <!-- NOMOR TELEPON -->
                <div class="form-group">

                    <label for="nohp">Nomor WhatsApp</label>

                    <input
                        type="tel"
                        id="nohp"
                        name="nohp"
                        class="form-control"
                        placeholder="Contoh: 081234567890"
                        value="<?= htmlspecialchars($_POST['nohp'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >

                </div>


                <!-- PASSWORD -->
                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Buat password"
                        minlength="8"
                        required
                    >

                    <small>
                        Minimal 8 karakter, huruf kapital dan simbol.
                    </small>

                </div>


                <!-- TOMBOL -->
                <button
                    type="submit"
                    class="btn-register"
                >
                    Simpan & Lanjutkan
                </button>

            </form>

        </div>

    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="../js/register.js"></script>

</body>
</html>
