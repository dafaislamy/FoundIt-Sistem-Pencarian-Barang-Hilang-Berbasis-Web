<?php
require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['is_logged_in'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION['is_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$pesan = '';
$search = trim($_GET['search'] ?? '');

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sukses') {
        $pesan = 'Pengguna baru berhasil ditambahkan.';
    }
    if ($_GET['status'] === 'dihapus') {
        $pesan = 'Data pengguna berhasil dihapus.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $nama = trim($_POST['nama'] ?? '');
    $nim = trim($_POST['nomor_identitas'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $password_hash = password_hash('123456', PASSWORD_DEFAULT);

    if ($nama === '' || $nim === '' || $email === '' || $no_telp === '') {
        $error = 'Semua data pengguna baru harus diisi.';
    } else {
        try {
            $max_id = $conn->query("
                SELECT MAX(ID)
                FROM USERS
                WHERE REGEXP_LIKE(ID, '^USR[0-9]+$')
            ")->fetchColumn();
            $num = $max_id ? ((int) substr((string) $max_id, 3) + 1) : 1;
            $id = 'USR' . str_pad($num, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("
                INSERT INTO USERS (ID, NAMA, NOMOR_IDENTITAS, EMAIL, PASSWORD, NO_TELP, ROLE)
                VALUES (:id, :nama, :nim, :email, :password, :no_telp, 'user')
            ");
            $stmt->execute([
                ':id' => $id,
                ':nama' => $nama,
                ':nim' => $nim,
                ':email' => $email,
                ':password' => $password_hash,
                ':no_telp' => $no_telp
            ]);

            header("Location: admin-kelola-user.php?status=sukses");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Gagal menambahkan pengguna (pastikan email atau NIM belum terdaftar).';
        }
    }
}

if (isset($_GET['hapus']) && trim($_GET['hapus']) !== '') {
    $id_hapus = trim($_GET['hapus']);
    try {
        $stmt = $conn->prepare("DELETE FROM USERS WHERE ID = :id AND LOWER(TRIM(ROLE)) = 'user'");
        $stmt->execute([':id' => $id_hapus]);

        header("Location: admin-kelola-user.php?status=dihapus");
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'Gagal menghapus pengguna.';
    }
}

$users = [];
try {
    $sql = "
        SELECT ID, NAMA, NOMOR_IDENTITAS, EMAIL, NO_TELP, CREATED_AT
        FROM USERS
        WHERE LOWER(TRIM(ROLE)) = 'user'
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (UPPER(NAMA) LIKE UPPER(:search) OR UPPER(NOMOR_IDENTITAS) LIKE UPPER(:search) OR UPPER(EMAIL) LIKE UPPER(:search))";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY CREATED_AT DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Gagal memuat daftar pengguna.';
}

$namaAdmin = $_SESSION['nama'] ?? 'Administrator';
$inisialAdmin = strtoupper(substr($namaAdmin, 0, 1)) ?: 'A';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - FoundIt Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php">Dashboard</a>
            <a href="admin-kelola-kategori.php">Kelola Kategori</a>
            <a href="admin-kelola-laporan.php">Kelola Laporan Barang</a>
            <a href="admin-kelola-user.php" class="active">Kelola User</a>
            <a href="admin-ubah-status.php">Ubah Status Barang</a>
            <a href="admin-verifikasi-klaim.php">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php">Verifikasi Laporan</a>
        </nav>
        <a href="../logout.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Kelola Pengguna</h1>
                <p>Manajemen data akun mahasiswa dan pengguna aplikasi FoundIt.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white"><?= htmlspecialchars($inisialAdmin, ENT_QUOTES, 'UTF-8'); ?></div>
                <div>
                    <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="card border-0 shadow-sm p-4 mb-4 rounded-3">
            <form method="get" action="admin-kelola-user.php" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Cari nama, NIM, atau email pengguna..." autocomplete="off">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Cari</button>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalTambahUser">+ Tambah</button>
                </div>
            </form>
        </section>

        <section class="card border-0 shadow-sm rounded-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>NIM &amp; Nama</th>
                            <th>Kontak</th>
                            <th>Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $usr): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) $usr['NAMA'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="text-muted small">NIM: <?= htmlspecialchars((string) $usr['NOMOR_IDENTITAS'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $usr['EMAIL'], ENT_QUOTES, 'UTF-8'); ?><br>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($usr['NO_TELP'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td><?= !empty($usr['CREATED_AT']) ? htmlspecialchars(date('d M Y', strtotime((string) $usr['CREATED_AT'])), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                    <td class="text-end">
                                        <a href="admin-kelola-user.php?hapus=<?= urlencode((string) $usr['ID']); ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Hapus pengguna ini?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada data pengguna.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal fade" id="modalTambahUser" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" action="admin-kelola-user.php" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nomor_identitas">NIM</label>
                        <input type="text" id="nomor_identitas" name="nomor_identitas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="no_telp">No. Telepon / WhatsApp</label>
                        <input type="text" id="no_telp" name="no_telp" class="form-control" required>
                    </div>
                    <small class="text-muted">Password awal default: <code>123456</code></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" value="1" class="btn btn-primary">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>