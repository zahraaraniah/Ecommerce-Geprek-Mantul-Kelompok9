<?php
require_once __DIR__ . '/../../config/config.php';
require_login();
$pageTitle = "Pesanan Saya";

$penggunaId = $_SESSION['pengguna_id'];

// Batalkan pesanan online milik pengguna ini yang sudah melewati batas waktu bayar
// (stok dikembalikan otomatis)
try {
    midtrans_expire_stale_orders($pdo, (int) $penggunaId, 5);
} catch (Throwable $e) {
    error_log('[MIDTRANS] expire_stale_orders gagal: ' . $e->getMessage());
}

$detailId = isset($_GET['detail']) ? (int) $_GET['detail'] : 0;
$detailItems = [];
$detailPesanan = null;
$notifBayar = null;

if ($detailId > 0) {
    $stmt2 = $pdo->prepare("SELECT * FROM pesanan WHERE id=? AND pengguna_id=?");
    $stmt2->execute([$detailId, $penggunaId]);
    $detailPesanan = $stmt2->fetch();

    // Perbarui status dari Midtrans bila masih menunggu pembayaran
    // (cadangan jika webhook belum masuk, mis. saat development di localhost)
    if ($detailPesanan && midtrans_bisa_bayar($detailPesanan)) {
        if (midtrans_sync_order($pdo, $detailPesanan) === 'updated') {
            $stmt2->execute([$detailId, $penggunaId]);
            $detailPesanan = $stmt2->fetch();
        }
    }

    if ($detailPesanan) {
        $stmt3 = $pdo->prepare("SELECT dp.*, p.nama_produk FROM detail_pesanan dp JOIN produk p ON dp.produk_id=p.id WHERE dp.pesanan_id = ?");
        $stmt3->execute([$detailId]);
        $detailItems = $stmt3->fetchAll();

        // Pesan setelah kembali dari popup pembayaran (berdasarkan status di database)
        if (isset($_GET['bayar'])) {
            if ($detailPesanan['status_midtrans'] === 'settlement') {
                $notifBayar = ['success', 'Pembayaran berhasil diterima. Pesanan Anda sedang diproses.'];
            } elseif ($detailPesanan['status_midtrans'] === 'pending') {
                $notifBayar = ['info', 'Pembayaran Anda menunggu penyelesaian. Selesaikan sesuai instruksi (kode VA / scan QRIS) sebelum batas waktu. Status akan diperbarui otomatis setelah pembayaran diterima.'];
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE pengguna_id = ? ORDER BY dibuat_pada DESC");
$stmt->execute([$penggunaId]);
$pesananList = $stmt->fetchAll();

$urlBayar = BASE_URL . '/modules/checkout/bayar.php';

include __DIR__ . '/../../includes/header.php';
?>

<div class="riwayat-page">

<?php if ($detailPesanan): ?>
  <?php
    [$label, $color] = status_label($detailPesanan['status_pesanan']);
    $status = $detailPesanan['status_pesanan'];
    $statusBayar = $detailPesanan['status_pembayaran'] ?? 'belum_lunas';
    $metode = $detailPesanan['metode_pembayaran'];
    $pesananDibatalkan = $status === 'dibatalkan';
    $pesananSelesai = $status === 'selesai';
  ?>

  <a href="<?= BASE_URL ?>/modules/akun/riwayat_pesanan.php" class="riwayat-back"> &larr; Kembali ke Pesanan Saya </a>
  <?php if ($notifBayar):
    $warnaNotif = [
      'success' => ['#e5f8eb', '#18763a'],
      'info' => ['#e8f3ff', '#185b91'],
    ][$notifBayar[0]] ?? ['#f3f4f6', '#374151'];
  ?>
  <div class="order-notification" role="status" style="background:<?= $warnaNotif[0] ?>;color:<?= $warnaNotif[1] ?>;">
    <?= clean($notifBayar[1]) ?>
  </div>
  <?php endif; ?>

  <section class="detail-order-card">
    <div class="detail-order-header">
      <div>
        <span class="order-caption">DETAIL PESANAN</span>
        <h1><?= clean($detailPesanan['no_pesanan']) ?></h1>
        <p> <?= date('d M Y, H:i', strtotime($detailPesanan['dibuat_pada'] ?? $detailPesanan['tanggal_pesanan'])) ?> WIB </p>
      </div>
      <span class="badge-status bg-<?= $color ?>"> <?= $label ?> </span>
    </div>
    
    <div class="order-notice">
      <?php if ($pesananDibatalkan): ?>
        Pesanan ini telah dibatalkan.
      <?php elseif ($pesananSelesai): ?>
        Pesanan telah selesai. Terima kasih sudah memesan di Geprek Mantul!
      <?php elseif ($status === 'dikirim'): ?>
        Pesanan sedang dalam perjalanan menuju alamat penerima.
      <?php elseif ($status === 'diproses'): ?>
        Pesanan sedang dipersiapkan oleh tim Geprek Mantul.
      <?php elseif ($statusBayar === 'lunas'): ?>
        Pembayaran telah diterima. Pesananmu sedang menunggu proses selanjutnya.
      <?php else: ?>
        Pesananmu sudah tercatat. Selesaikan pembayaran jika belum lunas.
      <?php endif; ?>
    </div>

    <?php if (!$pesananDibatalkan): ?>
      <?php
        $tahapan = [
          'menunggu_pembayaran' => 1,
          'diproses' => 2,
          'dikirim' => 3,
          'selesai' => 4,
        ];
        $tahapAktif = $tahapan[$status] ?? 1;
      ?>

      <div class="order-progress">
        <h3>Perjalanan Pesanan</h3>
        <div class="progress-list">
          <?php
            $langkah = [
              'Pesanan Dibuat',
              'Sedang Diproses',
              'Dalam Pengantaran',
              'Pesanan Selesai',
            ];
            foreach ($langkah as $i => $namaLangkah):
              $nomor = $i + 1;
              $aktif = $nomor <= $tahapAktif;
          ?>
            <div class="progress-step <?= $aktif ? 'is-active' : '' ?>">
              <span class="progress-circle"> <?= $aktif ? '&#10003;' : $nomor ?> </span>
              <span><?= $namaLangkah ?></span>
            </div>
            <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="detail-section">
      <h3>Informasi Penerima</h3>
      <div class="info-grid">
        <div>
          <span>Nama Penerima</span>
          <strong><?= clean($detailPesanan['nama_penerima']) ?></strong>
        </div>
        <div>
          <span>Nomor Telepon</span>
          <strong><?= clean($detailPesanan['no_telp_penerima']) ?></strong>
        </div>
        <div class="info-full">
          <span>Alamat Pengiriman</span>
          <strong><?= nl2br(clean($detailPesanan['alamat_pengiriman'])) ?></strong>
        </div>
        <?php if (!empty($detailPesanan['catatan'])): ?>
          <div class="info-full">
            <span>Catatan Pesanan</span>
            <strong><?= nl2br(clean($detailPesanan['catatan'])) ?></strong>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-section">
      <h3>Menu yang Dipesan</h3>
      <div class="order-menu-list">
        <?php foreach ($detailItems as $it): ?>
          <div class="order-menu-item">
            <div class="order-menu-info">
              <strong><?= clean($it['nama_produk']) ?></strong>
              <?php if (!empty($it['varian'])): ?>
                <span>Varian: <?= clean($it['varian']) ?></span>
              <?php endif; ?>
              <span>
                <?= (int) $it['kuantitas'] ?> ×
                <?= rupiah($it['harga_satuan']) ?>
              </span>
            </div>

            <strong><?= rupiah($it['subtotal']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="detail-section">
      <h3>Informasi Pembayaran</h3>
      <div class="payment-info">
        <div>
          <span>Metode Pembayaran</span>
          <strong><?= clean(midtrans_nama_metode($metode)) ?></strong>
        </div>
        <div>
          <span>Status Pembayaran</span>
          <strong><?= midtrans_badge_pembayaran($detailPesanan) ?></strong>
        </div>
        <?php if (!empty($detailPesanan['tipe_pembayaran_midtrans'])): ?>
          <div>
            <span>Channel Pembayaran</span>
            <strong>
              <?= clean(ucwords(str_replace(
                '_',
                ' ',
                $detailPesanan['tipe_pembayaran_midtrans']
              ))) ?>
            </strong>
          </div>
        <?php endif; ?>

        <?php if (!empty($detailPesanan['waktu_bayar'])): ?>
          <div>
            <span>Waktu Pembayaran</span>
            <strong>
              <?= date('d M Y, H:i', strtotime($detailPesanan['waktu_bayar'])) ?>
            </strong>
          </div>
        <?php endif; ?>
      </div>

      <?php if (midtrans_bisa_bayar($detailPesanan)): ?>
        <div class="payment-action">
          <div>
            <strong>Pembayaran belum selesai</strong>
            <p>
              Jika QRIS sebelumnya tertutup, buka kembali pembayaran
              untuk melanjutkan transaksi selama masih berlaku.
            </p>
          </div>

          <a href="<?= $urlBayar ?>?id=<?= (int) $detailPesanan['id'] ?>"
             class="btn-pay-order">
            Bayar Sekarang &rarr;
          </a>
        </div>

      <?php elseif (
        $metode === 'COD'
        && $statusBayar !== 'lunas'
        && !$pesananDibatalkan
      ): ?>
        <p class="payment-note">
          Lakukan pembayaran kepada kurir saat pesanan diterima.
        </p>
      <?php endif; ?>

      <div class="order-total">
        <div>
          <span>Subtotal</span>
          <span><?= rupiah($detailPesanan['total_belanja']) ?></span>
        </div>

        <?php if ($detailPesanan['potongan_promo'] > 0): ?>
          <div>
            <span>Potongan Promo</span>
            <span class="discount-value">
              - <?= rupiah($detailPesanan['potongan_promo']) ?>
            </span>
          </div>
        <?php endif; ?>

        <div>
          <span>Ongkos Kirim</span>
          <span><?= rupiah($detailPesanan['ongkir']) ?></span>
        </div>

        <div class="grand-total">
          <strong>Total Pembayaran</strong>
          <strong><?= rupiah($detailPesanan['total_bayar']) ?></strong>
        </div>
      </div>
    </div>
  </section>

<?php else: ?>
  <div class="riwayat-heading">
    <div>
      <h1 class="section-title">Pesanan Saya</h1>
      <p>Pantau status pesanan dan pembayaranmu di sini.</p>
    </div>
    <a href="<?= BASE_URL ?>/modules/katalog/index.php" class="btn-order-menu"> + Pesan Menu</a>
  </div>

  <?php if (empty($pesananList)): ?>
    <div class="empty-orders">
      <div class="empty-order-icon">🛍️</div>
      <h2>Belum Ada Pesanan</h2>
      <p> Yuk, pilih menu favoritmu dan nikmati ayam geprek dari Geprek Mantul! </p>
      <a href="<?= BASE_URL ?>/modules/katalog/index.php" class="btn-order-menu"> Lihat Menu </a>
    </div>

  <?php else: ?>
    <div class="order-list">
      <?php foreach ($pesananList as $p): ?>
        <?php
          [$label, $color] = status_label($p['status_pesanan']);
          $statusBayar = $p['status_pembayaran'] ?? 'belum_lunas';
        ?>

        <article class="order-card">
          <div class="order-card-top">
            <div>
              <span class="order-caption">NO. PESANAN</span>
              <h3><?= clean($p['no_pesanan']) ?></h3>
            <p> <?= date('d M Y, H:i', strtotime($p['dibuat_pada'] ?? $p['tanggal_pesanan'])) ?> WIB </p>
            </div>
            <span class="badge-status bg-<?= $color ?>">
              <?= $label ?>
            </span>
          </div>

          <div class="order-card-bottom">
            <div class="order-card-payment">
              <span>Total Pembayaran</span>
              <strong class="order-card-total">
                <?= rupiah($p['total_bayar']) ?>
              </strong>

              <small>
                <?= strip_tags(midtrans_badge_pembayaran($p)) ?>
                · <?= clean(midtrans_nama_metode($p['metode_pembayaran'])) ?>
              </small>
            </div>

            <div class="order-actions">
              <a href="?detail=<?= (int) $p['id'] ?>" class="btn-order-detail"> Detail Pesanan &rarr;</a>
              <?php if (midtrans_bisa_bayar($p)): ?>
                <a href="<?= $urlBayar ?>?id=<?= (int) $p['id'] ?>" class="btn-pay-order"> Bayar Sekarang </a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
