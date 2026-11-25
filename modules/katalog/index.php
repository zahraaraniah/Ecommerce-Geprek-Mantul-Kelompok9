<?php
/**
 * FILE INI: KATALOG (daftar menu + fitur search & filter kategori)
 */
require_once __DIR__ . '/../../config/config.php';
$pageTitle = "Katalog Menu";

$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY id")->fetchAll();

$where = ["p.status = 'aktif'"];
$params = [];

if (!empty($_GET['kategori'])) {
    $where[] = "p.kategori_id = ?";
    $params[] = $_GET['kategori'];
}
if (!empty($_GET['q'])) {
    $where[] = "(p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $params[] = '%' . $_GET['q'] . '%';
    $params[] = '%' . $_GET['q'] . '%';
}

$sql = "SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.kategori_id = k.id
        WHERE " . implode(' AND ', $where) . " ORDER BY p.dibuat_pada DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Katalog Menu</h1>
<p class="section-sub">
  <?php if (!empty($_GET['q'])): ?>
    Hasil pencarian untuk "<?= clean($_GET['q']) ?>" (<?= count($produkList) ?> menu)
  <?php else: ?>
    Semua menu Geprek Mantul, siap diantar ke rumahmu.
  <?php endif; ?>
</p>

<div class="cat-list">
  <a href="<?= BASE_URL ?>/modules/katalog/index.php" class="cat-chip <?= empty($_GET['kategori']) ? 'active' : '' ?>">Semua</a>
  <?php foreach ($kategoriList as $k): ?>
    <a href="<?= BASE_URL ?>/modules/katalog/index.php?kategori=<?= $k['id'] ?>"
       class="cat-chip <?= (($_GET['kategori'] ?? '') == $k['id']) ? 'active' : '' ?>">
       <?= clean($k['nama_kategori']) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="product-grid">
  <?php if (empty($produkList)): ?>
    <p>Menu tidak ditemukan.</p>
  <?php endif; ?>
  <?php foreach ($produkList as $p): ?>
    <a href="<?= BASE_URL ?>/modules/katalog/detail.php?id=<?= $p['id'] ?>" class="product-card">
      <div class="product-thumb">
        src="<?= BASE_URL ?>/uploads/produk/<?= clean($p['gambar']) ?>" alt="<?= clean($p['nama_produk']) ?>"> 
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
