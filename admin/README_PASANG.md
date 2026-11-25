# Admin Geprek Mantul (versi sementara) - Langkah Pemasangan

## Langkah 1 - Letakkan folder dengan benar
Folder ini harus SEJAJAR dengan folder `config/`, `includes/`, `modules/` milik situs user:

    htdocs/geprek-mantul/
      admin/       <-- folder ini (nama bebas: admin, admin2, dll)
      config/
      includes/
      modules/
      index.php

Jangan membuka file dengan klik dua kali (alamat file:///...). PHP hanya jalan lewat server
(XAMPP/Laragon/hosting). Pastikan Apache dan MySQL sudah menyala.

## Langkah 2 - Cek BASE_URL
Buka `config/config.php` situs user. `BASE_URL` harus sesuai alamat di browser.
Contoh alamat http://localhost/geprek-mantul/ -> `define('BASE_URL', '/geprek-mantul');`
Alamat di root domain -> `define('BASE_URL', '');`

## Langkah 3 - Jalankan diagnosa
Buka: http://localhost/geprek-mantul/admin/cek.php
Semua baris harus bertanda centang. Baris silang menjelaskan apa yang harus diperbaiki.

## Langkah 4 - Siapkan database (sekali saja)
Buka: http://localhost/geprek-mantul/admin/setup.php
Setup membuat tabel tambahan, menambah kolom kategori.gambar, mengisi bagan akun keuangan,
hak akses awal, dan pengaturan awal. Bila belum ada akun superadmin, dibuat:
admin@geprekmantul.id / admin123 (ubah di bagian atas setup.php bila perlu).
Bila database sudah punya superadmin, setup memakai akun itu (tidak dibuat baru).

## Langkah 5 - Login
Buka: http://localhost/geprek-mantul/admin/login.php

## Langkah 6 - Bersihkan
Hapus `setup.php` dan `cek.php` dari server setelah admin berjalan, lalu ganti password admin.

## Status modul
SUDAH JADI dan diuji: Dashboard, Pesanan (ubah status langsung, progress, konfirmasi pembayaran),
Produk (foto), Kategori (gambar), Stok (peringatan stok habis), Pelanggan, Pengiriman + Biaya Pengiriman,
jurnal keuangan otomatis, hak akses per peran.
BELUM DIBUAT (menampilkan halaman "Sedang dikembangkan"): Pembayaran, Pengembalian & Penukaran,
Pengguna & Hak Akses, Pengaturan Website, Laporan Keuangan, CRM, Analitik, Promo, FAQ, Pesan Masuk.

## Foto produk
Foto disimpan di folder `uploads/` di root project. Jika situs user memakai folder foto lain,
ubah UPLOAD_FS dan UPLOAD_URL di includes/bootstrap.php.
