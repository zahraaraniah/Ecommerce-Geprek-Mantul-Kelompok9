<?php
/**
 * =====================================================================
 * FILE INI: HALAMAN BERANDA
 * =====================================================================
 */
require_once __DIR__ . '/config/config.php';
$pageTitle = "Beranda";

$kategori = $pdo->query("SELECT * FROM kategori ORDER BY id")->fetchAll();
$produkUnggulan = $pdo->query("SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.kategori_id=k.id WHERE p.status='aktif' ORDER BY p.dibuat_pada DESC LIMIT 8")->fetchAll();

// Ambil promo aktif yang sedang berlaku hari ini
$stmtPromo = $pdo->query("SELECT * FROM promo WHERE status='aktif' AND CURDATE() BETWEEN tanggal_mulai AND tanggal_berakhir ORDER BY id DESC LIMIT 1");
$promoAktif = $stmtPromo->fetch();

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero-text">
    <h1>Ayam Geprek Mantul, Pedasnya Bikin Nagih! </h1>
    <p>Crispy di luar, juicy di dalam, sambal bawang bikin nagih. Pesan sekarang dan rasakan sensai pedasnya!</p>
    <a href="<?= BASE_URL ?>/modules/katalog/index.php" class="btn-primary">Lihat Menu</a>
    <a href="<?= BASE_URL ?>/modules/pages/faq.php" class="btn-secondary">Cara Pemesanan</a>
  </div>
  <div class="hero-img">
    <img src="<?= BASE_URL ?>/assets/img/AyamGeprek.jpg" alt="Banner Ayam Geprek">
  </div>
</section>

<?php if ($promoAktif): ?>
<div class="promo-banner">
  <div>
    <h3>🎉 <?= clean($promoAktif['judul']) ?></h3>
    <p>Gunakan kode <span class="kode"><?= clean($promoAktif['kode_promo']) ?></span> saat checkout. Berlaku sampai <?= date('d M Y', strtotime($promoAktif['tanggal_berakhir'])) ?>.</p>
  </div>
</div>
<details class="promo-sk">
  <summary>Lihat Syarat &amp; Ketentuan Promo</summary>
  <p style="margin-top:10px;white-space:pre-line;"><?= nl2br(clean($promoAktif['syarat_ketentuan'])) ?></p>
</details>
<?php endif; ?>

<h2 class="section-title">Kategori Menu</h2>
<p class="section-sub">Pilih kategori sesuai selera</p>
<div class="cat-list">
  <a href="<?= BASE_URL ?>/modules/katalog/index.php" class="cat-chip">Semua</a>
  <?php foreach ($kategori as $k): ?>
    <a href="<?= BASE_URL ?>/modules/katalog/index.php?kategori=<?= $k['id'] ?>" class="cat-chip"><?= clean($k['nama_kategori']) ?></a>
  <?php endforeach; ?>
</div>

<h2 class="section-title">Pilihan Menu</h2>
<p class="section-sub">Beberapa pilihan menu kami</p>
<div class="product-grid">
  <?php foreach ($produkUnggulan as $p): ?>
    <a href="<?= BASE_URL ?>/modules/katalog/detail.php?id=<?= $p['id'] ?>" class="product-card">
      <div class="product-thumb">
         <img 
          src="<?= BASE_URL ?>uploads/produk/<?= clean($p['gambar']) ?>" alt="<?= clean($p['nama_produk']) ?>">
      </div>
      <div class="product-info">
        <div class="product-cat"><?= clean($p['nama_kategori']) ?></div>
        <h3><?= clean($p['nama_produk']) ?></h3>
        <div class="product-price"><?= rupiah($p['harga_jual']) ?></div>
        <div class="stok-info <?= $p['stok'] <= 0 ? 'stok-habis' : '' ?>">
          <?= $p['stok'] > 0 ? 'Stok: ' . $p['stok'] : 'Stok Habis' ?>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
