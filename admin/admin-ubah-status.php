<?php
include 'koneksi.php';

// PROSES UPDATE STATUS BARANG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $item_id = $_POST['item_id'];
    $status_baru = $_POST['status_baru'];

    $stmt = $conn->prepare("UPDATE items SET status = ? WHERE id = ?");
    $stmt->execute([$status_baru, $item_id]);

    header("Location: admin-ubah-status.php");
    exit();
}

$items = $conn->query("SELECT items.*, categories.nama_kategori FROM items JOIN categories ON items.categories_id = categories.id ORDER BY items.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Status Barang - FoundIt Admin</title>
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
            <a href="admin-kelola-user.php">Kelola User</a>
            <a href="admin-ubah-status.php" class="active">Ubah Status Barang</a>
            <a href="admin-verifikasi-klaim.php">Verifikasi Klaim</a>
            <a href="admin-verifikasi-laporan.php">Verifikasi Laporan</a>
        </nav>
        <a href="../login.php" class="logout">Keluar</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Ubah Status Barang</h1>
                <p>Perbarui siklus status barang (Pending, Terbit, Selesai, Ditolak, Arsip).</p>
            </div>
            <div class="user-profile">
                <div class="profile-icon bg-primary text-white">A</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Super Admin</small>
                </div>
            </div>
        </header>

        <section class="card border-0 shadow-sm rounded-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Lokasi Kejadian</th>
                            <th>Status Saat Ini</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['judul']) ?></strong>
                                <div class="text-muted small">ID: #<?= $item['id'] ?></div>
                            </td>
                            <td><?= htmlspecialchars($item['nama_kategori']) ?></td>
                            <td><?= htmlspecialchars($item['lokasi']) ?></td>
                            <td><span class="badge bg-primary"><?= $item['status'] ?></span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary btn-trigger-modal" 
                                    data-bs-toggle="modal" data-bs-target="#modalUpdateStatus" 
                                    data-id="<?= $item['id'] ?>" data-nama="<?= htmlspecialchars($item['judul']) ?>" data-status="<?= $item['status'] ?>">
                                    Perbarui Status
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- MODAL DIALOG UPDATE STATUS BARANG -->
    <div class="modal fade" id="modalUpdateStatus" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Pembaruan Status Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="item_id" id="modalItemId">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Barang yang Dipilih</label>
                            <input type="text" id="modalItemNama" class="form-control bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Status Baru</label>
                            <select name="status_baru" id="modalStatusSelect" class="form-select" required>
                                <option value="Pending">Pending (Verifikasi Admin)</option>
                                <option value="Terbit">Terbit (Ditampilkan di Publik)</option>
                                <option value="Selesai">Selesai (Sudah Dikembalikan)</option>
                                <option value="Ditolak">Ditolak</option>
                                <option value="Arsip">Arsip</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Simpan Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tangkap data tombol ke dalam modal
        document.querySelectorAll('.btn-trigger-modal').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modalItemId').value = this.getAttribute('data-id');
                document.getElementById('modalItemNama').value = this.getAttribute('data-nama') + ' (' + this.getAttribute('data-id') + ')';
                document.getElementById('modalStatusSelect').value = this.getAttribute('data-status');
            });
        });
    </script>
</body>
</html>