<?php // Edit profil user dengan update tabel users 
require_once __DIR__ . '/_init.php';

$errors = [];
$form = [
    'nama'            => $currentUser['nama'],
    'nomor_identitas' => $currentUser['nomor_identitas'] ?? '',
    'email'           => $currentUser['email'],
    'no_telp'         => $currentUser['no_telp'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    foreach ($form as $k => $_) {
        $form[$k] = trim($_POST[$k] ?? '');
    }
    $passLama  = $_POST['password_lama'] ?? '';
    $passBaru  = $_POST['password_baru'] ?? '';
    $passKonf  = $_POST['password_konfirmasi'] ?? '';

    if ($form['nama'] === '' || mb_strlen($form['nama']) > 100) {
        $errors[] = 'Nama wajib diisi (maksimal 100 karakter).';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 100) {
        $errors[] = 'Format email tidak valid.';
    }
    if ($form['nomor_identitas'] !== '' && !preg_match('/^[A-Za-z0-9]{3,20}$/', $form['nomor_identitas'])) {
        $errors[] = 'NIM hanya boleh huruf/angka (3-20 karakter).';
    }
    if ($form['no_telp'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $form['no_telp'])) {
        $errors[] = 'Nomor WhatsApp tidak valid (8-20 digit).';
    }

    // Cek duplikasi email / NIM milik user lain
    if (!$errors) {
        $c = $pdo->prepare("SELECT id FROM users WHERE email = :v AND id <> :id LIMIT 1");
        $c->execute([':v' => $form['email'], ':id' => $currentUser['id']]);
        if ($c->fetch()) $errors[] = 'Email sudah digunakan akun lain.';

        if ($form['nomor_identitas'] !== '') {
            $c = $pdo->prepare("SELECT id FROM users WHERE nomor_identitas = :v AND id <> :id LIMIT 1");
            $c->execute([':v' => $form['nomor_identitas'], ':id' => $currentUser['id']]);
            if ($c->fetch()) $errors[] = 'NIM sudah digunakan akun lain.';
        }
    }

    // Ganti password (opsional)
    $hashBaru = null;
    if ($passBaru !== '' || $passKonf !== '' || $passLama !== '') {
        if (!password_cocok($passLama, $currentUser['password'])) {
            $errors[] = 'Password saat ini salah.';
        } elseif ($passBaru !== $passKonf) {
            $errors[] = 'Konfirmasi password baru tidak sama.';
        } elseif (strlen($passBaru) < 8 || !preg_match('/[A-Z]/', $passBaru) || !preg_match('/[^A-Za-z0-9]/', $passBaru)) {
            $errors[] = 'Password baru minimal 8 karakter, mengandung huruf kapital dan simbol.';
        } else {
            $hashBaru = password_hash($passBaru, PASSWORD_BCRYPT);
        }
    }

    if (!$errors) {
        $sql = "UPDATE users SET nama = :nama, nomor_identitas = :nim, email = :email, no_telp = :telp"
             . ($hashBaru ? ", password = :pass" : "")
             . " WHERE id = :id";
        $params = [
            ':nama'  => $form['nama'],
            ':nim'   => $form['nomor_identitas'] !== '' ? $form['nomor_identitas'] : null,
            ':email' => $form['email'],
            ':telp'  => $form['no_telp'] !== '' ? $form['no_telp'] : null,
            ':id'    => $currentUser['id'],
        ];
        if ($hashBaru) $params[':pass'] = $hashBaru;

        $pdo->prepare($sql)->execute($params);

        $_SESSION['nama']  = $form['nama'];
        $_SESSION['email'] = $form['email'];
        flash_set('success', 'Profil berhasil diperbarui.');
        header('Location: profil.php');
        exit;
    }
}

$pageTitle  = 'Edit Profil';
$activeMenu = 'profil';
$heading    = 'Edit Profil';
$subheading = 'Perbarui informasi akun kamu.';
require __DIR__ . '/_header.php';
?>

        <section class="edit-profile-card">
            <div class="edit-header">
                <h2>Informasi Pribadi</h2>
                <p>Ubah data yang ingin kamu perbarui.</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="edit-profil.php" autocomplete="off">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" class="form-control" maxlength="100" value="<?= e($form['nama']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nim">NIM / Nomor Identitas</label>
                            <input type="text" id="nim" name="nomor_identitas" class="form-control" maxlength="20" value="<?= e($form['nomor_identitas']) ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" maxlength="100" value="<?= e($form['email']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nohp">Nomor WhatsApp</label>
                            <input type="tel" id="nohp" name="no_telp" class="form-control" maxlength="20" value="<?= e($form['no_telp']) ?>">
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h5>Ganti Password <small class="text-muted fs-6">(kosongkan jika tidak ingin mengganti)</small></h5>

                <div class="form-group">
                    <label for="pl">Password Saat Ini</label>
                    <input type="password" id="pl" name="password_lama" class="form-control" autocomplete="current-password">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pb">Password Baru</label>
                            <input type="password" id="pb" name="password_baru" class="form-control" minlength="8" autocomplete="new-password">
                            <small class="text-muted">Min. 8 karakter, ada huruf kapital dan simbol.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pk">Konfirmasi Password Baru</label>
                            <input type="password" id="pk" name="password_konfirmasi" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="profil.php" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </section>

<?php require __DIR__ . '/_footer.php'; ?>
