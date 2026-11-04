<?php // Detail barang berdasarkan id
require_once __DIR__ . '/_init.php';

$id = trim($_GET['id'] ?? '');

$stmt = $pdo->prepare(
    "SELECT i.*, c.nama_kategori
     FROM items i
     JOIN categories c ON c.id = i.categories_id
     WHERE i.id = :id
       AND (i.status = 'Terbit' OR i.users_id = :uid)
     LIMIT 1"
);
$stmt->execute([':id' => $id, ':uid' => $currentUser['id']]);
$item = $stmt->fetch();

if (!$item) {
    flash_set('warning', 'Barang tidak ditemukan atau belum dipublikasikan.');
    header('Location: cari-barang.php');
    exit;
}

$milikSendiri = ($item['users_id'] === $currentUser['id']);

// Klaim terakhir milik user ini untuk barang tersebut
$stmtKlaim = $pdo->prepare(
    "SELECT id, status_klaim, created_at FROM claims
     WHERE items_id = :item AND users_id = :uid
     ORDER BY created_at DESC, id DESC LIMIT 1"
);
$stmtKlaim->execute([':item' => $item['id'], ':uid' => $currentUser['id']]);
$klaimSaya = $stmtKlaim->fetch();

$bisaKlaim = $item['tipe'] === 'found'
          && $item['status'] === 'Terbit'
          && !$milikSendiri
          && !($klaimSaya && in_array($klaimSaya['status_klaim'], ['Menunggu', 'Disetujui'], true));

$pageTitle  = 'Detail Barang';
$activeMenu = 'cari';
$heading    = 'Detail Barang';
$subheading = 'Informasi lengkap mengenai barang.';
require __DIR__ . '/_header.php';
?>

        <section class="detail-container">
            <div class="row g-4">

                <div class="col-lg-5">
                    <div class="detail-image">
                        <img src="<?= e(foto_url($item['foto'])) ?>" alt="<?= e($item['judul']) ?>">
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="detail-content">

                        <div class="detail-status">
                            <span class="status-badge <?= class_tipe($item['tipe']) ?>"><?= label_tipe($item['tipe']) ?></span>
                        </div>

                        <h2><?= e($item['judul']) ?></h2>
                        <p class="detail-description"><?= nl2br(e($item['deskripsi'])) ?></p>

                        <div class="row g-3 detail-info">
                            <div class="col-md-6">
                                <div class="detail-item"><span>Kategori</span><strong><?= e($item['nama_kategori']) ?></strong></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item"><span>Lokasi</span><strong><?= e($item['lokasi']) ?></strong></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item"><span>Tanggal <?= $item['tipe'] === 'found' ? 'Ditemukan' : 'Kehilangan' ?></span><strong><?= e(tgl_indo($item['tanggal_kejadian'])) ?></strong></div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item"><span>Status Laporan</span><strong><?= e($item['status']) ?></strong></div>
                            </div>
                            <?php if ($item['tipe'] === 'found' && !empty($item['nama_penemu'])): ?>
                                <div class="col-md-6">
                                    <div class="detail-item"><span>Ditemukan oleh</span><strong><?= e($item['nama_penemu']) ?></strong></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($klaimSaya): ?>
                            <div class="alert alert-info mt-3 mb-0">
                                Klaim Anda untuk barang ini berstatus
                                <strong><?= e($klaimSaya['status_klaim']) ?></strong>
                                (diajukan <?= e(tgl_indo($klaimSaya['created_at'])) ?>).
                            </div>
                        <?php elseif ($milikSendiri): ?>
                            <div class="alert alert-secondary mt-3 mb-0">Ini adalah laporan yang Anda buat sendiri.</div>
                        <?php elseif ($item['tipe'] === 'lost'): ?>
                            <div class="alert alert-secondary mt-3 mb-0">
                                Barang ini dilaporkan hilang. Jika Anda menemukannya, gunakan menu
                                <a href="lapor-ditemukan.html">Lapor Barang Ditemukan</a>.
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-3 flex-wrap detail-actions mt-3">
                            <a href="cari-barang.php" class="btn-secondary">Kembali</a>
                            <?php if ($bisaKlaim): ?>
                                <a href="klaim.php?id=<?= urlencode($item['id']) ?>" class="btn-primary">Ajukan Klaim</a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </div>
        </section>

<?php require __DIR__ . '/_footer.php'; ?>
