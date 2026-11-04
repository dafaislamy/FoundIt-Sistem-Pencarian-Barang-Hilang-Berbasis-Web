<?php // Cari barang pencarian & filter tipe/kategori & data dari SQL
require_once __DIR__ . '/_init.php';

$q      = trim($_GET['q'] ?? '');
$tipe   = $_GET['tipe'] ?? 'semua';
$kat    = $_GET['kategori'] ?? 'semua';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

if (!in_array($tipe, ['semua', 'lost', 'found'], true)) $tipe = 'semua';

$categories = $pdo->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori")->fetchAll();
$validKat   = array_column($categories, 'id');
if ($kat !== 'semua' && !in_array($kat, $validKat, true)) $kat = 'semua';

// Hanya barang yang sudah diverifikasi admin (status Terbit)
$where  = ["i.status = 'Terbit'"];
$params = [];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = "(i.judul LIKE :q1 OR i.deskripsi LIKE :q2 OR i.lokasi LIKE :q3)";
    $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;
}
if ($tipe !== 'semua') {
    $where[] = "i.tipe = :tipe";
    $params[':tipe'] = $tipe;
}
if ($kat !== 'semua') {
    $where[] = "i.categories_id = :kat";
    $params[':kat'] = $kat;
}
$whereSql = implode(' AND ', $where);

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM items i WHERE $whereSql");
$stmtCount->execute($params);
$total      = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$sql = "SELECT i.id, i.judul, i.deskripsi, i.tipe, i.lokasi, i.tanggal_kejadian, i.foto, i.users_id,
               c.nama_kategori
        FROM items i
        JOIN categories c ON c.id = i.categories_id
        WHERE $whereSql
        ORDER BY i.created_at DESC, i.id DESC
        LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

// Query string untuk link pagination
function url_halaman(int $p): string {
    $qs = $_GET;
    $qs['page'] = $p;
    return 'cari-barang.php?' . http_build_query($qs);
}

$pageTitle  = 'Cari Barang';
$activeMenu = 'cari';
$heading    = 'Daftar Barang';
$subheading = 'Jelajahi laporan barang hilang dan ditemukan di kampus';
require __DIR__ . '/_header.php';
?>

        <section class="search-box">
            <div class="search-title">
                <h2>Cari Barang</h2>
                <p>Temukan barang berdasarkan nama, tipe, atau kategori.</p>
            </div>

            <form method="get" action="cari-barang.php" class="search-filter-wrapper">
                <div class="search-input-wrapper">
                    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama, deskripsi, atau lokasi..." aria-label="Cari barang" autocomplete="off">
                </div>

                <select name="tipe" class="filter-select" aria-label="Filter tipe" onchange="this.form.submit()">
                    <option value="semua"<?= $tipe === 'semua' ? ' selected' : '' ?>>Semua Status</option>
                    <option value="lost"<?= $tipe === 'lost' ? ' selected' : '' ?>>Hilang</option>
                    <option value="found"<?= $tipe === 'found' ? ' selected' : '' ?>>Ditemukan</option>
                </select>

                <select name="kategori" class="filter-select" aria-label="Filter kategori" onchange="this.form.submit()">
                    <option value="semua">Semua Kategori</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c['id']) ?>"<?= $kat === $c['id'] ? ' selected' : '' ?>><?= e($c['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="reset-filter">Cari</button>
                <a href="cari-barang.php" class="reset-filter text-decoration-none">Reset Filter</a>
            </form>
        </section>

        <section class="result-section">
            <div class="result-header">
                <div>
                    <h2>Daftar Barang</h2>
                    <p role="status"><?= $total > 0 ? 'Menampilkan ' . count($items) . ' dari ' . $total . ' barang' : 'Tidak ada barang yang cocok' ?></p>
                </div>
            </div>

            <?php if ($total === 0): ?>
                <div class="no-result">
                    <div aria-hidden="true">🔎</div>
                    <h3>Barang tidak ditemukan</h3>
                    <p>Coba gunakan kata kunci atau filter yang berbeda.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($items as $it): ?>
                        <div class="col">
                            <div class="barang-card">
                                <div class="barang-image">
                                    <img src="<?= e(foto_url($it['foto'])) ?>" alt="<?= e($it['judul']) ?>" loading="lazy">
                                </div>
                                <div class="barang-content">
                                    <span class="status-badge <?= class_tipe($it['tipe']) ?>"><?= label_tipe($it['tipe']) ?></span>
                                    <h3><?= e($it['judul']) ?></h3>
                                    <p><?= e($it['lokasi']) ?> &middot; <?= e(tgl_indo($it['tanggal_kejadian'])) ?></p>
                                    <span class="category"><?= e($it['nama_kategori']) ?></span>

                                    <div class="barang-actions">
                                        <a href="detail-barang.php?id=<?= urlencode($it['id']) ?>" class="btn-detail">Lihat Detail</a>
                                        <?php if ($it['tipe'] === 'found' && $it['users_id'] !== $currentUser['id']): ?>
                                            <a href="klaim.php?id=<?= urlencode($it['id']) ?>" class="btn-klaim">Klaim</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Halaman">
                        <ul class="pagination justify-content-center">
                            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                <a class="page-link" href="<?= e(url_halaman($page - 1)) ?>">&laquo;</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                                    <a class="page-link" href="<?= e(url_halaman($p)) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                                <a class="page-link" href="<?= e(url_halaman($page + 1)) ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

<?php require __DIR__ . '/_footer.php'; ?>
