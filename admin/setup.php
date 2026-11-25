<?php
/**
 * =====================================================================
 * FILE: admin/setup.php -- INSTALASI / MIGRASI DATABASE ADMIN (jalankan 1x)
 * =====================================================================
 * Membuat tabel tambahan yang dibutuhkan fitur ERP, menambah kolom
 * kategori.gambar, mengisi bagan akun keuangan, hak akses awal, dan
 * pengaturan awal. AMAN dijalankan berulang (tidak menimpa data).
 * Tabel milik situs user TIDAK diubah, kecuali menambah 1 kolom: kategori.gambar.
 *
 * BOLEH DIEDIT : $EMAIL_ADMIN, $NAMA_ADMIN, $PASS_ADMIN sebelum dijalankan.
 * JANGAN DIUBAH: daftar CREATE TABLE & seed akun_rekening (dipakai laporan keuangan).
 * Setelah selesai, HAPUS file setup.php ini dari server.
 * =====================================================================
 */
$EMAIL_ADMIN = 'admin@geprekmantul.id'; $NAMA_ADMIN = 'Admin Geprek Mantul'; $PASS_ADMIN = 'admin123';

require_once __DIR__ . '/../config/config.php';
$sudahAdmin = (int)$pdo->query("SELECT COUNT(*) FROM pengguna WHERE peran='superadmin'")->fetchColumn();
$ada = array_map('strtolower', $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN));
$terpasang = !array_diff(['hak_akses', 'pengaturan', 'pengiriman', 'pembayaran', 'metode_pembayaran', 'pengembalian', 'crm_leads', 'kunjungan'], $ada);
// Instalasi pertama (tabel belum ada) boleh dijalankan siapa saja. Setelah terpasang, hanya superadmin yang login.
if ($terpasang && ($_SESSION['admin_peran'] ?? '') !== 'superadmin') {
    http_response_code(403); die('Setup sudah pernah dijalankan. Login sebagai superadmin dulu bila ingin menjalankannya lagi, atau hapus file setup.php ini.');
}
$log = [];
$run = function ($label, $sql) use ($pdo, &$log) { try { $pdo->exec($sql); $log[] = "✔ $label"; } catch (Exception $e) { $log[] = "✘ $label: " . $e->getMessage(); } };

$run('tabel hak_akses', "CREATE TABLE IF NOT EXISTS hak_akses (id INT AUTO_INCREMENT PRIMARY KEY, peran VARCHAR(20) NOT NULL, menu VARCHAR(30) NOT NULL, UNIQUE KEY u_peran_menu (peran, menu)) ENGINE=InnoDB");
$run('tabel pengaturan', "CREATE TABLE IF NOT EXISTS pengaturan (kunci VARCHAR(60) PRIMARY KEY, nilai TEXT) ENGINE=InnoDB");
$run('tabel tarif_pengiriman', "CREATE TABLE IF NOT EXISTS tarif_pengiriman (id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL, biaya DECIMAL(12,2) NOT NULL DEFAULT 0, keterangan VARCHAR(255) DEFAULT NULL, aktif TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB");
$run('tabel pengiriman', "CREATE TABLE IF NOT EXISTS pengiriman (id INT AUTO_INCREMENT PRIMARY KEY, pesanan_id INT NOT NULL UNIQUE, kurir VARCHAR(50) NOT NULL DEFAULT 'Kurir Internal', nomor_resi VARCHAR(50) DEFAULT NULL, biaya DECIMAL(12,2) NOT NULL DEFAULT 0, status ENUM('disiapkan','dikirim','terkirim','gagal') NOT NULL DEFAULT 'disiapkan', tanggal_kirim DATETIME DEFAULT NULL, tanggal_terima DATETIME DEFAULT NULL, catatan VARCHAR(255) DEFAULT NULL, dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP, diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_kirim_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE) ENGINE=InnoDB");
$run('tabel metode_pembayaran', "CREATE TABLE IF NOT EXISTS metode_pembayaran (id INT AUTO_INCREMENT PRIMARY KEY, kode VARCHAR(30) NOT NULL UNIQUE, nama VARCHAR(100) NOT NULL, keterangan VARCHAR(255) DEFAULT NULL, aktif TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB");
$run('tabel pembayaran', "CREATE TABLE IF NOT EXISTS pembayaran (id INT AUTO_INCREMENT PRIMARY KEY, pesanan_id INT NOT NULL, metode VARCHAR(30) NOT NULL, jumlah DECIMAL(12,2) NOT NULL DEFAULT 0, status ENUM('menunggu','diterima','ditolak') NOT NULL DEFAULT 'diterima', tanggal_bayar DATETIME DEFAULT CURRENT_TIMESTAMP, bukti VARCHAR(255) DEFAULT NULL, catatan VARCHAR(255) DEFAULT NULL, dicatat_oleh INT DEFAULT NULL, dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_bayar_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE, CONSTRAINT fk_bayar_admin FOREIGN KEY (dicatat_oleh) REFERENCES pengguna(id) ON DELETE SET NULL) ENGINE=InnoDB");
$run('tabel pengembalian', "CREATE TABLE IF NOT EXISTS pengembalian (id INT AUTO_INCREMENT PRIMARY KEY, no_pengembalian VARCHAR(40) NOT NULL UNIQUE, pesanan_id INT NOT NULL, pengguna_id INT NOT NULL, jenis ENUM('pengembalian','penukaran') NOT NULL DEFAULT 'pengembalian', alasan TEXT NOT NULL, status ENUM('diajukan','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diajukan', nominal_refund DECIMAL(12,2) NOT NULL DEFAULT 0, catatan_admin TEXT DEFAULT NULL, diproses_oleh INT DEFAULT NULL, dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP, diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_ret_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE, CONSTRAINT fk_ret_user FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE) ENGINE=InnoDB");
$run('tabel detail_pengembalian', "CREATE TABLE IF NOT EXISTS detail_pengembalian (id INT AUTO_INCREMENT PRIMARY KEY, pengembalian_id INT NOT NULL, produk_id INT NOT NULL, kuantitas INT NOT NULL DEFAULT 1, kondisi ENUM('layak_jual','rusak') NOT NULL DEFAULT 'layak_jual', produk_pengganti_id INT DEFAULT NULL, CONSTRAINT fk_dret_ret FOREIGN KEY (pengembalian_id) REFERENCES pengembalian(id) ON DELETE CASCADE, CONSTRAINT fk_dret_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT, CONSTRAINT fk_dret_ganti FOREIGN KEY (produk_pengganti_id) REFERENCES produk(id) ON DELETE SET NULL) ENGINE=InnoDB");
$run('tabel crm_leads', "CREATE TABLE IF NOT EXISTS crm_leads (id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL, kontak VARCHAR(100) DEFAULT NULL, sumber VARCHAR(50) DEFAULT NULL, status ENUM('baru','dihubungi','prospek','menjadi_pelanggan','tidak_berminat') NOT NULL DEFAULT 'baru', pengguna_id INT DEFAULT NULL, catatan TEXT DEFAULT NULL, dibuat_oleh INT DEFAULT NULL, dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$run('tabel crm_interaksi', "CREATE TABLE IF NOT EXISTS crm_interaksi (id INT AUTO_INCREMENT PRIMARY KEY, pengguna_id INT DEFAULT NULL, lead_id INT DEFAULT NULL, jenis ENUM('whatsapp','telepon','email','tatap_muka','lainnya') NOT NULL DEFAULT 'whatsapp', ringkasan TEXT NOT NULL, tindak_lanjut DATE DEFAULT NULL, dibuat_oleh INT DEFAULT NULL, dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_int_user FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE, CONSTRAINT fk_int_lead FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE CASCADE) ENGINE=InnoDB");
$run('tabel kunjungan', "CREATE TABLE IF NOT EXISTS kunjungan (id INT AUTO_INCREMENT PRIMARY KEY, tanggal DATE NOT NULL, waktu DATETIME DEFAULT CURRENT_TIMESTAMP, pengunjung_hash CHAR(32) NOT NULL, halaman VARCHAR(255) DEFAULT NULL, pengguna_id INT DEFAULT NULL, KEY k_tanggal (tanggal)) ENGINE=InnoDB");

// Kolom gambar pada kategori (satu-satunya perubahan pada tabel milik situs user)
$ada = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='kategori' AND COLUMN_NAME='gambar'")->fetchColumn();
if (!$ada) $run('kolom kategori.gambar', "ALTER TABLE kategori ADD COLUMN gambar VARCHAR(255) DEFAULT NULL"); else $log[] = '✔ kolom kategori.gambar sudah ada';

// Bagan akun keuangan
$akun = [['1101','Kas & Bank','Aset','Debit'],['1201','Persediaan Barang','Aset','Debit'],['4101','Penjualan','Pendapatan','Kredit'],
 ['4102','Potongan Penjualan (Promo)','Pendapatan','Debit'],['4103','Pendapatan Ongkos Kirim','Pendapatan','Kredit'],['4104','Retur Penjualan','Pendapatan','Debit'],
 ['5101','Harga Pokok Penjualan (HPP)','Beban_Pokok','Debit'],['6101','Beban Bahan & Operasional Dapur','Beban_Operasional','Debit'],
 ['6102','Beban Gaji','Beban_Operasional','Debit'],['6103','Beban Listrik, Air & Gas','Beban_Operasional','Debit'],['6104','Beban Sewa','Beban_Operasional','Debit'],
 ['6105','Beban Pemasaran','Beban_Operasional','Debit'],['6199','Beban Lain-lain','Beban_Operasional','Debit']];
$st = $pdo->prepare("INSERT IGNORE INTO akun_rekening (kode_akun,nama_akun,kelompok_akun,posisi_normal) VALUES (?,?,?,?)");
foreach ($akun as $a) $st->execute($a); $log[] = '✔ bagan akun keuangan (akun_rekening)';

// Hak akses awal
$st = $pdo->prepare("INSERT IGNORE INTO hak_akses (peran,menu) VALUES (?,?)");
$def = ['gudang' => ['dashboard','produk','kategori','stok','pengiriman','pengembalian'], 'kasir' => ['dashboard','pesanan','pelanggan','pembayaran','pengiriman','pengembalian','kontak','crm']];
if ((int)$pdo->query("SELECT COUNT(*) FROM hak_akses")->fetchColumn() === 0) foreach ($def as $p => $ms) foreach ($ms as $m) $st->execute([$p, $m]);
$log[] = '✔ hak akses awal';

// Pengaturan awal, metode & tarif
$st = $pdo->prepare("INSERT IGNORE INTO pengaturan (kunci,nilai) VALUES (?,?)");
foreach (['nama_toko'=>'Geprek Mantul','tagline'=>'Ayam geprek renyah, sambel nampol!','email_kontak'=>'halo@geprekmantul.id','telepon'=>'0812-3456-7890',
  'whatsapp'=>'6281234567890','alamat'=>'','jam_operasional'=>'Setiap hari, 10.00 - 21.00 WIB','instagram'=>'@geprekmantul','logo'=>'','favicon'=>''] as $k => $v) $st->execute([$k, $v]);
$st = $pdo->prepare("INSERT IGNORE INTO metode_pembayaran (kode,nama,keterangan,aktif) VALUES (?,?,?,1)");
$st->execute(['transfer_bank','Transfer Bank','Isi nomor rekening di sini, contoh: BCA 1234567890 a.n. Geprek Mantul']);
$st->execute(['COD','COD (Bayar di Tempat)','Dibayar tunai kepada kurir saat pesanan diterima']);
if (!(int)$pdo->query("SELECT COUNT(*) FROM tarif_pengiriman")->fetchColumn())
    $pdo->exec("INSERT INTO tarif_pengiriman (nama,biaya,keterangan) VALUES ('Dalam kota (Kurir Internal)',8000,'Jarak sampai 5 km'),('Luar area (Ojek Online)',15000,'Jarak di atas 5 km')");
$log[] = '✔ pengaturan awal, metode pembayaran, tarif pengiriman';

if ($sudahAdmin === 0) {
    $pdo->prepare("INSERT INTO pengguna (nama_lengkap,email,kata_sandi,peran) VALUES (?,?,?,'superadmin')")->execute([$NAMA_ADMIN, $EMAIL_ADMIN, password_hash($PASS_ADMIN, PASSWORD_DEFAULT)]);
    $log[] = "✔ superadmin dibuat: $EMAIL_ADMIN / $PASS_ADMIN";
} else { $log[] = 'ℹ Sudah ada akun superadmin di database, jadi tidak dibuat baru. Login memakai akun superadmin yang sudah ada.'; }
@mkdir(__DIR__ . '/../uploads', 0775, true);
?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Setup Admin</title></head>
<body style="font-family:Arial;max-width:680px;margin:40px auto;line-height:1.8">
<h2>Setup Admin Geprek Mantul</h2><?php foreach ($log as $l) echo '<div>' . htmlspecialchars($l) . '</div>'; ?>
<p style="color:#b00;margin-top:18px"><strong>PENTING:</strong> hapus file <code>setup.php</code> ini setelah selesai, lalu ganti password superadmin.</p>
<p><a href="login.php">Masuk ke Admin &rarr;</a></p></body></html>
