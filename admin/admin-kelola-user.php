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
    <link rel="stylesheet" href="../css/main.css?v=<?= filemtime(__DIR__ . '/../css/main.css') ?? time(); ?>">
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo"><span>Found</span>It</div>
        <nav>
            <a href="admin-dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="admin-kelola-kategori.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <span>Kelola Kategori</span>
            </a>
            <a href="admin-kelola-laporan.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                <span>Kelola Laporan</span>
            </a>
            <a href="admin-kelola-user.php" class="active">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Kelola User</span>
            </a>
            <a href="admin-ubah-status.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                <span>Ubah Status</span>
            </a>
            <a href="admin-verifikasi-klaim.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                <span>Verifikasi Klaim</span>
            </a>
            <a href="admin-verifikasi-laporan.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span>Verifikasi Laporan</span>
            </a>
        </nav>
        <a href="../logout.php" class="logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            <span>Keluar</span>
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <header class="admin-topbar">
            <div class="admin-topbar-title">
                <h1>Kelola Pengguna</h1>
                <p>Manajemen data akun mahasiswa dan pengguna aplikasi FoundIt.</p>
            </div>
            <div class="admin-user-badge">
                <div class="admin-avatar">
                    <?= htmlspecialchars($inisialAdmin, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="admin-user-info">
                    <strong><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>Super Admin</span>
                </div>
            </div>
        </header>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                <div><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>
        <!-- FILTER & ACTION BAR -->
        <section class="admin-filter-card">
            <form method="get" action="admin-kelola-user.php" class="admin-filter-bar">
                <div style="flex: 3; min-width: 250px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" class="admin-filter-input w-100" placeholder="Cari nama, NIM, atau email pengguna..." autocomplete="off">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="admin-btn-filter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <span>Cari</span>
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="admin-kelola-user.php" class="admin-btn-reset">Reset</a>
                    <?php endif; ?>
                </div>
                <div class="ms-auto">
                    <button type="button" class="admin-btn-filter" style="background-color: #16a34a;" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Tambah Pengguna</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- TABEL DATA PENGGUNA -->
        <section class="admin-card-table">
            <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center" style="background-color: #ffffff;">
                <h6 class="mb-0 fw-bold" style="color: #1e293b;">Daftar Mahasiswa &amp; Pengguna</h6>
                <span class="badge-pill-soft terbit"><?= count($users); ?> Total Pengguna</span>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>NIM / Identitas</th>
                            <th>Kontak</th>
                            <th>Tanggal Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $usr): ?>
                                <?php
                                    $initial = strtoupper(substr(trim((string) $usr['NAMA']), 0, 1)) ?: 'U';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="admin-avatar" style="width: 36px; height: 36px; font-size: 14px; background: linear-gradient(135deg, #64748b, #94a3b8); box-shadow: none;">
                                                <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div>
                                                <strong style="color: #1e293b;"><?= htmlspecialchars((string) $usr['NAMA'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <div class="text-muted small">ID: <?= htmlspecialchars((string) $usr['ID'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code style="background-color: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-weight: 600;">
                                            <?= htmlspecialchars((string) $usr['NOMOR_IDENTITAS'], ENT_QUOTES, 'UTF-8'); ?>
                                        </code>
                                    </td>
                                    <td>
                                        <div style="color: #1e293b; font-weight: 500;"><?= htmlspecialchars((string) $usr['EMAIL'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars((string) ($usr['NO_TELP'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?= !empty($usr['CREATED_AT']) ? htmlspecialchars(date('d M Y', strtotime((string) $usr['CREATED_AT'])), ENT_QUOTES, 'UTF-8') : '-'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="admin-kelola-user.php?hapus=<?= urlencode((string) $usr['ID']); ?>"
                                           class="btn-action-sm btn-action-outline-danger"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data pengguna ini? Tindakan ini tidak dapat dibatalkan.');">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            <span>Hapus</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="admin-empty-state">
                                        <div class="admin-empty-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="23" y2="14"/><line x1="23" y1="8" x2="17" y2="14"/></svg>
                                        </div>
                                        <div class="admin-empty-title">Tidak Ada Pengguna Ditemukan</div>
                                        <div class="admin-empty-text">Tidak ada akun mahasiswa yang cocok dengan kriteria pencarian Anda.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- MODAL TAMBAH PENGGUNA -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="admin-kelola-user.php" class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header border-bottom py-3 px-4" style="background-color: #f8fafc;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-circle-icon stat-circle-blue" style="width: 34px; height: 34px; border-radius: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </div>
                        <h5 class="modal-title fw-bold" id="modalTambahUserTitle" style="color: #1e293b; font-size: 17px;">Tambah Pengguna Baru</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #475569;" for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="admin-filter-input w-100" placeholder="Contoh: Ahmad Rizki Pratama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #475569;" for="nomor_identitas">Nomor Identitas (NIM)</label>
                        <input type="text" id="nomor_identitas" name="nomor_identitas" class="admin-filter-input w-100" placeholder="Contoh: 21051204001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #475569;" for="email">Alamat Email</label>
                        <input type="email" id="email" name="email" class="admin-filter-input w-100" placeholder="nama@mahasiswa.ac.id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px; color: #475569;" for="no_telp">No. Telepon / WhatsApp</label>
                        <input type="text" id="no_telp" name="no_telp" class="admin-filter-input w-100" placeholder="Contoh: 081234567890" required>
                    </div>
                    <div class="p-3 rounded-3" style="background-color: #f1f5f9;">
                        <small class="text-muted d-block">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            Password default awal akun adalah <code>123456</code>. Mahasiswa dapat memperbaruinya di profil akun.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4" style="background-color: #f8fafc;">
                    <button type="button" class="admin-btn-reset" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_user" value="1" class="admin-btn-filter">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>