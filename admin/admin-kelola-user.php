<?php
include 'koneksi.php';

// TAMBAH PENGGUNA BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $nama = trim($_POST['nama']);
    $nim = trim($_POST['nomor_identitas']);
    $email = trim($_POST['email']);
    $no_telp = trim($_POST['no_telp']);
    $password = password_hash('123456', PASSWORD_DEFAULT); // Default password

    // Auto generator USRxxx
    $max_id = $conn->query("SELECT MAX(id) FROM users")->fetchColumn();
    $num = (int) substr($max_id, 3) + 1;
    $id = 'USR' . str_pad($num, 3, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO users (id, nama, nomor_identitas, email, password, no_telp, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
    $stmt->execute([$id, $nama, $nim, $email, $password, $no_telp]);

    header("Location: admin-kelola-user.php");
    exit();
}

// HAPUS PENGGUNA
if (isset($_GET['hapus'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['hapus']]);
    header("Location: admin-kelola-user.php");
    exit();
}

// SEARCH USER
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE role = 'user'";
if (!empty($search)) {
    $query .= " AND (nama LIKE '%$search%' OR nomor_identitas LIKE '%$search%' OR email LIKE '%$search%')";
}
$query .= " ORDER BY created_at DESC";
$users = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
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
        <a href="../login.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Kelola Pengguna</h1>
                <p>Manajemen data akun mahasiswa dan pengguna aplikasi FoundIt.</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <section class="card border-0 shadow-sm p-4 mb-4 rounded-3">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, NIM, atau email pengguna..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalTambahUser">+ Tambah Pengguna</button>
                </div>
            </form>
        </section>

        <!-- TABEL PENGGUNA -->
        <section class="card border-0 shadow-sm rounded-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>NIM & Nama</th>
                            <th>Email</th>
                            <th>Kontak / No Telp</th>
                            <th>Tgl Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($u['nama']) ?></strong>
                                <div class="text-muted small">NIM: <?= htmlspecialchars($u['nomor_identitas'] ?? '-') ?></div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['no_telp'] ?? '-') ?></td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="admin-kelola-user.php?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus user ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- MODAL TAMBAH USER -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Pengguna Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">NIM / Nomor Identitas</label><input type="text" name="nomor_identitas" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">No HP / WhatsApp</label><input type="text" name="no_telp" class="form-control" required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_user" class="btn btn-primary">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>