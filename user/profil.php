<?php // Halaman profil user
require_once __DIR__ . '/_init.php';

$stmtLap = $pdo->prepare("SELECT COUNT(*) FROM items WHERE users_id = :uid");
$stmtLap->execute([':uid' => $currentUser['id']]);
$totalLaporan = (int)$stmtLap->fetchColumn();

$stmtKl = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(status_klaim = 'Disetujui'), 0) AS disetujui
     FROM claims WHERE users_id = :uid"
);
$stmtKl->execute([':uid' => $currentUser['id']]);
$klaim = $stmtKl->fetch();

$pageTitle  = 'Profil Saya';
$activeMenu = 'profil';
$heading    = 'Profil Saya';
$subheading = 'Kelola informasi akun kamu.';
require __DIR__ . '/_header.php';
?>

        <section class="profile-page">
            <div class="profile-header">
                <div class="profile-photo"><?= e($inisial) ?></div>
                <div>
                    <h2><?= e($currentUser['nama']) ?></h2>
                    <p><?= $currentUser['nomor_identitas'] ? 'NIM ' . e($currentUser['nomor_identitas']) : 'NIM belum diisi' ?></p>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-card-header">
                    <div>
                        <h2>Informasi Pribadi</h2>
                        <p>Informasi akun yang kamu daftarkan.</p>
                    </div>
                    <a href="edit-profil.php" class="btn-edit">Edit Profil</a>
                </div>

                <div class="row g-3 profile-info-grid">
                    <div class="col-md-6">
                        <div class="info-item"><span>Nama Lengkap</span><strong><?= e($currentUser['nama']) ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item"><span>NIM / Nomor Identitas</span><strong><?= e($currentUser['nomor_identitas'] ?: '-') ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item"><span>Email</span><strong><?= e($currentUser['email']) ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item"><span>Nomor WhatsApp</span><strong><?= e($currentUser['no_telp'] ?: '-') ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item"><span>Bergabung Sejak</span><strong><?= e(tgl_indo($currentUser['created_at'])) ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <span>Aktivitas</span>
                            <strong><?= $totalLaporan ?> laporan &middot; <?= (int)$klaim['total'] ?> klaim (<?= (int)$klaim['disetujui'] ?> disetujui)</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<?php require __DIR__ . '/_footer.php'; ?>
