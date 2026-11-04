<?php // Form klaim barang temuan
require_once __DIR__ . '/_init.php';

$id = trim($_GET['id'] ?? $_POST['id'] ?? '');

// Tanpa id: arahkan user memilih barang dulu
if ($id === '') {
    flash_set('info', 'Pilih barang temuan yang ingin diklaim, lalu tekan tombol Klaim.');
    header('Location: cari-barang.php?tipe=found');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT i.*, c.nama_kategori
     FROM items i JOIN categories c ON c.id = i.categories_id
     WHERE i.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();

if (!$item || $item['status'] !== 'Terbit') {
    flash_set('warning', 'Barang tidak ditemukan atau belum bisa diklaim.');
    header('Location: cari-barang.php');
    exit;
}
if ($item['tipe'] !== 'found') {
    flash_set('warning', 'Hanya barang yang berstatus "Ditemukan" yang dapat diklaim.');
    header('Location: detail-barang.php?id=' . urlencode($item['id']));
    exit;
}
if ($item['users_id'] === $currentUser['id']) {
    flash_set('warning', 'Anda tidak dapat mengklaim laporan Anda sendiri.');
    header('Location: detail-barang.php?id=' . urlencode($item['id']));
    exit;
}

// Cegah klaim ganda yang masih aktif
$stmtDup = $pdo->prepare(
    "SELECT status_klaim FROM claims
     WHERE items_id = :item AND users_id = :uid AND status_klaim IN ('Menunggu','Disetujui') LIMIT 1"
);
$stmtDup->execute([':item' => $item['id'], ':uid' => $currentUser['id']]);
if ($stmtDup->fetch()) {
    flash_set('info', 'Anda sudah memiliki klaim aktif untuk barang ini.');
    header('Location: detail-barang.php?id=' . urlencode($item['id']));
    exit;
}

$errors = [];
$bukti  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    $bukti = trim($_POST['bukti_kepemilikan'] ?? '');
    $len   = mb_strlen($bukti);
    if ($len < 20) {
        $errors[] = 'Bukti kepemilikan minimal 20 karakter agar admin dapat memverifikasi.';
    } elseif ($len > 1000) {
        $errors[] = 'Bukti kepemilikan maksimal 1000 karakter.';
    }

    // Lampiran foto (opsional)
    $fileExt = null;
    $file    = $_FILES['lampiran_foto'] ?? null;
    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload foto gagal. Coba lagi dengan file yang lebih kecil.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $errors[] = 'Format foto harus JPG, PNG, atau WEBP.';
            } else {
                $fileExt = $allowed[$mime];
            }
        }
    }

    if (!$errors) {
        $newId = null;
        for ($try = 0; $try < 3 && $newId === null; $try++) {
            $last = $pdo->query("SELECT id FROM claims WHERE id LIKE 'CLM%' ORDER BY CAST(SUBSTRING(id, 4) AS UNSIGNED) DESC LIMIT 1")->fetchColumn();
            $candidate = $last ? 'CLM' . str_pad((int)substr($last, 3) + 1, 3, '0', STR_PAD_LEFT) : 'CLM001';

            $namaFile = null;
            if ($fileExt) {
                $namaFile = 'bukti_' . strtolower($candidate) . '.' . $fileExt;
            }

            try {
                $ins = $pdo->prepare(
                    "INSERT INTO claims (id, bukti_kepemilikan, lampiran_foto, status_klaim, items_id, users_id)
                     VALUES (:id, :bukti, :foto, 'Menunggu', :item, :uid)"
                );
                $ins->execute([
                    ':id'    => $candidate,
                    ':bukti' => $bukti,
                    ':foto'  => $namaFile,
                    ':item'  => $item['id'],
                    ':uid'   => $currentUser['id'],
                ]);
                $newId = $candidate;
            } catch (PDOException $ex) {
                if ($ex->getCode() !== '23000') throw $ex; // selain ID bentrok, lempar ulang
            }
        }

        if ($newId === null) {
            $errors[] = 'Gagal menyimpan klaim. Silakan coba lagi.';
        } else {
            if ($fileExt) {
                $dir = __DIR__ . '/../uploads/bukti';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                move_uploaded_file($_FILES['lampiran_foto']['tmp_name'], $dir . '/' . $namaFile);
            }
            flash_set('success', 'Klaim ' . $newId . ' berhasil diajukan. Admin akan memverifikasi bukti Anda.');
            header('Location: detail-barang.php?id=' . urlencode($item['id']));
            exit;
        }
    }
}

$pageTitle  = 'Klaim Barang';
$activeMenu = 'klaim';
$heading    = 'Klaim Barang';
$subheading = 'Ajukan klaim untuk barang yang kamu kenali.';
require __DIR__ . '/_header.php';
?>

        <section class="claim-container">
            <div class="claim-header">
                <h2>Form Pengajuan Klaim</h2>
                <p>Jelaskan bukti kepemilikan agar admin dapat memverifikasi bahwa barang ini milik Anda.</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Ringkasan barang yang diklaim -->
            <div class="d-flex gap-3 align-items-center mb-4 p-3 border rounded">
                <img src="<?= e(foto_url($item['foto'])) ?>" alt="" style="width:90px;height:90px;object-fit:cover;border-radius:8px;">
                <div>
                    <span class="status-badge ditemukan">Ditemukan</span>
                    <h5 class="mb-1 mt-1"><?= e($item['judul']) ?></h5>
                    <small class="text-muted"><?= e($item['nama_kategori']) ?> &middot; <?= e($item['lokasi']) ?> &middot; <?= e(tgl_indo($item['tanggal_kejadian'])) ?></small>
                </div>
            </div>

            <form method="post" action="klaim.php?id=<?= urlencode($item['id']) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($item['id']) ?>">

                <div class="form-group">
                    <label for="bukti">Bukti Kepemilikan</label>
                    <textarea id="bukti" name="bukti_kepemilikan" class="form-control" rows="6" maxlength="1000" required
                        placeholder="Sebutkan ciri khusus yang tidak tercantum di deskripsi publik, isi/bagian dalam barang, kapan dan di mana terakhir terlihat, dll."><?= e($bukti) ?></textarea>
                    <small class="text-muted">Minimal 20 karakter, maksimal 1000.</small>
                </div>

                <div class="form-group">
                    <label for="lampiran">Lampiran Foto (opsional)</label>
                    <input type="file" id="lampiran" name="lampiran_foto" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">Contoh: foto barang serupa, struk, atau kotak pembelian. JPG/PNG/WEBP, maks. 2 MB.</small>
                </div>

                <div class="form-actions">
                    <a href="detail-barang.php?id=<?= urlencode($item['id']) ?>" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-submit">Ajukan Klaim</button>
                </div>
            </form>
        </section>

<?php require __DIR__ . '/_footer.php'; ?>
