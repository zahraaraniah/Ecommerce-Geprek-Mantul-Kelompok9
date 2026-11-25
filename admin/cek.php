<?php
/**
 * FILE: admin/cek.php -- ALAT DIAGNOSA PEMASANGAN ADMIN
 * Buka lewat browser untuk melihat apa yang kurang (versi PHP, ekstensi, config, BASE_URL, database, folder upload).
 * HAPUS file ini setelah admin berjalan normal (menampilkan info server).
 */
header('Content-Type: text/html; charset=utf-8');
function baris($ok, $judul, $detail = '') { echo '<tr><td style="width:36px;font-size:20px">' . ($ok === true ? '✅' : ($ok === false ? '❌' : 'ℹ️')) . '</td><td><b>' . $judul . '</b><br><small>' . $detail . '</small></td></tr>'; }
echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:Arial;max-width:780px;margin:30px auto;line-height:1.55"><h2>Diagnosa Admin Geprek Mantul</h2><table cellpadding="8" style="border-collapse:collapse;width:100%">';
baris(version_compare(PHP_VERSION, '7.4', '>='), 'Versi PHP ' . PHP_VERSION, 'Minimal PHP 7.4 (disarankan 8.x).');
foreach (['pdo_mysql', 'session'] as $e) baris(extension_loaded($e), "Ekstensi PHP: $e", extension_loaded($e) ? 'Aktif' : 'Belum aktif. Aktifkan di php.ini (XAMPP: Config &gt; PHP &gt; hapus tanda ; pada extension=' . $e . ').');
$cfg = __DIR__ . '/../config/config.php';
baris(is_file($cfg), 'File config situs user ditemukan', is_file($cfg) ? 'OK: folder admin sejajar dengan config/.' : 'Tidak ditemukan di ' . htmlspecialchars(realpath(__DIR__ . '/..') ?: dirname(__DIR__)) . '/config/. Pindahkan folder admin agar sejajar dengan folder config/, includes/, modules/.');
foreach (['includes/functions.php' => 'includes/functions.php (user)', 'modules' => 'folder modules (user)'] as $f => $n) baris(file_exists(__DIR__ . '/../' . $f), "Ada $n", 'Tanda folder admin berada di tempat yang benar.');
echo '</table>'; flush();
if (!is_file($cfg)) { echo '<p><b>Perbaiki dulu poin ❌ di atas.</b></p>'; exit; }
echo '<p><small>Memuat config situs user... bila halaman berhenti di sini dengan pesan "Koneksi database gagal", periksa config/database.php (host, user, password, nama database).</small></p>'; flush();
require_once $cfg;
echo '<table cellpadding="8" style="border-collapse:collapse;width:100%">';
$folder = basename(__DIR__);
$saran = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
baris(defined('BASE_URL'), 'BASE_URL di config.php = "' . (defined('BASE_URL') ? BASE_URL : '-') . '"', 'Berdasarkan alamat yang Anda buka sekarang, BASE_URL yang cocok adalah <b>"' . htmlspecialchars($saran) . '"</b> (kosong "" bila situs ada di root domain).');
baris(defined('BASE_URL') && BASE_URL === $saran, 'BASE_URL cocok dengan alamat saat ini', 'Jika tidak cocok, tampilan akan polos (CSS tidak termuat) dan tautan menu salah. Ubah BASE_URL di config/config.php.');
baris(isset($pdo), 'Koneksi database', isset($pdo) ? 'Terhubung ke database <b>' . htmlspecialchars($pdo->query('SELECT DATABASE()')->fetchColumn()) . '</b>.' : 'Gagal.');
if (isset($pdo)) {
    $ada = array_map('strtolower', $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN));
    $milikUser = ['pengguna', 'kategori', 'produk', 'pesanan', 'detail_pesanan', 'mutasi_stok', 'akun_rekening', 'jurnal_keuangan', 'keranjang', 'faq', 'pesan_kontak', 'promo'];
    $tambahan = ['hak_akses', 'pengaturan', 'tarif_pengiriman', 'pengiriman', 'metode_pembayaran', 'pembayaran', 'pengembalian', 'detail_pengembalian', 'crm_leads', 'crm_interaksi', 'kunjungan'];
    $k1 = array_diff($milikUser, $ada); $k2 = array_diff($tambahan, $ada);
    baris(!$k1, 'Tabel situs user lengkap', $k1 ? 'Belum ada: <code>' . implode(', ', $k1) . '</code>. Import database.sql situs user dulu.' : 'OK');
    baris(!$k2, 'Tabel tambahan admin lengkap', $k2 ? 'Belum ada: <code>' . implode(', ', $k2) . '</code>. <b>Buka <a href="setup.php">setup.php</a> satu kali.</b>' : 'OK');
    if (in_array('pengguna', $ada)) { $n = (int)$pdo->query("SELECT COUNT(*) FROM pengguna WHERE peran IN ('superadmin','gudang','kasir') AND status_aktif=1")->fetchColumn();
        baris($n > 0, "Akun staf aktif: $n", $n ? 'Bisa login di <a href="login.php">login.php</a>.' : 'Belum ada akun staf. Jalankan setup.php untuk membuat superadmin.'); }
}
$up = dirname(__DIR__) . '/uploads';
baris(is_dir($up) && is_writable($up), 'Folder upload gambar: ' . htmlspecialchars($up), is_dir($up) ? (is_writable($up) ? 'Bisa ditulis.' : 'Folder ada tapi TIDAK bisa ditulis (atur izin 775).') : 'Belum ada. Dibuat otomatis saat upload pertama, atau buat manual.');
echo '</table><p>Uji tampilan: jika logo di bawah muncul, folder admin terbaca browser:<br><img src="assets/logo_default.png" height="60" alt="(logo tidak muncul: cek BASE_URL / nama folder)"></p>';
echo '<p style="color:#b00"><b>Setelah selesai, hapus cek.php dan setup.php dari server.</b></p>';
