<?php
// user/cari-barang.php
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Ambil parameter pencarian & filter jika ada
$keyword = $_GET['keyword'] ?? '';
$tipe    = $_GET['tipe'] ?? '';
$kategori= $_GET['kategori'] ?? '';

// Ambil daftar kategori untuk filter dropdown
try {
    $stmtCat = $conn->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori ASC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Query dasar untuk mencari barang yang statusnya 'Terbit' (sudah diverifikasi admin)
$sql = "SELECT i.*, c.nama_kategori 
        FROM items i 
        JOIN categories c ON i.categories_id = c.id 
        WHERE i.status = 'Terbit'";
$params = [];

// Tambahkan filter keyword (judul / deskripsi / lokasi)
if (!empty($keyword)) {
    $sql .= " AND (LOWER(i.judul) LIKE LOWER(:keyword) OR LOWER(i.deskripsi) LIKE LOWER(:keyword) OR LOWER(i.lokasi) LIKE LOWER(:keyword))";
    $params[':keyword'] = '%' . $keyword . '%';
}

// Tambahkan filter tipe (lost / found)
if (!empty($tipe)) {
    $sql .= " AND i.tipe = :tipe";
    $params[':tipe'] = $tipe;
}

// Tambahkan filter kategori
if (!empty($kategori)) {
    $sql .= " AND i.categories_id = :kategori";
    $params[':kategori'] = $kategori;
}

$sql .= " ORDER BY i.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $items = $stmt.fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $items = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Barang - FoundIt</title>
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>
    <div class="container">
        <h2>Cari Barang Hilang & Ditemukan</h2>
        <p><a href="dashboard.php">&larr; Kembali ke Dashboard</a></p>

        <!-- Form Pencarian & Filter -->
        <form action="cari-barang.php" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari nama barang/lokasi..." class="form-control" style="flex: 2; min-width: 200px;">
            
            <select name="tipe" class="form-control" style="flex: 1; min-width: 130px;">
                <option value="">-- Semua Tipe --</option>
                <option value="lost" <?= $tipe === 'lost' ? 'selected' : ''; ?>>Barang Hilang</option>
                <option value="found" <?= $tipe === 'found' ? 'selected' : ''; ?>>Barang Ditemukan</option>
            </select>

            <select name="kategori" class="form-control" style="flex: 1; min-width: 130px;">
                <option value="">-- Semua Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['ID']; ?>" <?= $kategori === $cat['ID'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['NAMA_KATEGORI']); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-primary" style="flex: 0.5;">Cari</button>
        </form>

        <!-- Daftar Hasil Pencarian -->
        <div class="item-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                    <div class="card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                        <?php if (!empty($item['FOTO'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($item['FOTO']); ?>" alt="Foto Barang" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px;">
                        <?php endif; ?>
                        <span style="display: inline-block; padding: 3px 8px; font-size: 12px; background: <?= $item['TIPE'] === 'lost' ? '#ffcccc' : '#ccffcc'; ?>; color: #333; margin-top: 8px; border-radius: 3px;">
                            <?= $item['TIPE'] === 'lost' ? 'Hilang' : 'Ditemukan'; ?>
                        </span>
                        <h3><?= htmlspecialchars($item['JUDUL']); ?></h3>
                        <p><strong>Kategori:</strong> <?= htmlspecialchars($item['NAMA_KATEGORI']); ?></p>
                        <p><strong>Lokasi:</strong> <?= htmlspecialchars($item['LOKASI']); ?></p>
                        <p><a href="detail-barang.php?id=<?= $item['ID']; ?>">Lihat Detail &rarr;</a></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Tidak ada barang yang ditemukan.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>