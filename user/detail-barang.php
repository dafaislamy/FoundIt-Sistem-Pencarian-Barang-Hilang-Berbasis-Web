<?php
// user/detail-barang.php
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

$id_item = $_GET['id'] ?? '';

if (empty($id_item)) {
    header("Location: cari-barang.php");
    exit;
}

try {
    // Query mengambil detail barang beserta nama kategorinya
    $sql = "SELECT i.*, c.nama_kategori 
            FROM items i 
            JOIN categories c ON i.categories_id = c.id 
            WHERE i.id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id_item]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Barang tidak ditemukan!");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Barang - <?= htmlspecialchars($item['JUDUL']); ?></title>
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>
    <div class="container">
        <p><a href="cari-barang.php">&larr; Kembali ke Pencarian</a></p>
        
        <h2><?= htmlspecialchars($item['JUDUL']); ?></h2>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
            <div style="flex: 1; min-width: 280px;">
                <?php if (!empty($item['FOTO'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($item['FOTO']); ?>" alt="Foto Barang" style="width: 100%; max-height: 350px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                <?php else: ?>
                    <div style="width: 100%; height: 250px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 8px;">Tidak ada foto</div>
                <?php endif; ?>
            </div>

            <div style="flex: 1.5; min-width: 280px;">
                <p><strong>Tipe Laporan:</strong> 
                    <span style="padding: 3px 8px; border-radius: 4px; background: <?= $item['TIPE'] === 'lost' ? '#ffcccc' : '#ccffcc'; ?>;">
                        <?= $item['TIPE'] === 'lost' ? 'Barang Hilang' : 'Barang Ditemukan'; ?>
                    </span>
                </p>
                <p><strong>Kategori:</strong> <?= htmlspecialchars($item['NAMA_KATEGORI']); ?></p>
                <p><strong>Lokasi Kejadian:</strong> <?= htmlspecialchars($item['LOKASI']); ?></p>
                <p><strong>Tanggal Kejadian:</strong> <?= date('d F Y', strtotime($item['TANGGAL_KEJADIAN'])); ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($item['STATUS']); ?></p>
                
                <?php if ($item['TIPE'] === 'found' && !empty($item['NAMA_PENEMU'])): ?>
                    <hr>
                    <p><strong>Penemu:</strong> <?= htmlspecialchars($item['NAMA_PENEMU']); ?></p>
                <?php endif; ?>

                <hr>
                <p><strong>Deskripsi & Ciri-ciri:</strong></p>
                <p style="white-space: pre-line; background: #f9f9f9; padding: 12px; border-radius: 6px; border: 1px solid #eee;"><?= htmlspecialchars($item['DESKRIPSI']); ?></p>

                <!-- Tombol Klaim jika barang tipe 'found' dan statusnya masih 'Terbit' -->
                <?php if ($item['TIPE'] === 'found' && $item['STATUS'] === 'Terbit'): ?>
                    <div style="margin-top: 20px;">
                        <?php if (is_logged_in()): ?>
                            <a href="klaim.php?items_id=<?= $item['ID']; ?>" class="btn-primary" style="display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">Ajukan Klaim Kepemilikan</a>
                        <?php else: ?>
                            <p><em>Silakan <a href="../login.php">Login</a> terlebih dahulu untuk mengajukan klaim barang ini.</em></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>