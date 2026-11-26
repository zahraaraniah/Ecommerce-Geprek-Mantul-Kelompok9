<?php
/**
 * FILE: admin/kategori/index.php -- KELOLA KATEGORI + GAMBAR KATEGORI
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('kategori');

function tampil_gambar_kategori($k) {
    $val = $k['gambar'] ?? '';
    $slug = strtolower(trim($k['slug'] ?? $k['nama_kategori'] ?? ''));

    // 1. Jika ada file di assets/img/ yang cocok dengan nama file di DB
    if (!empty($val)) {
        if (strpos($val, 'http') === 0 || strpos($val, 'data:image/') === 0) {
            return '<img src="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '" alt="Kategori" style="width:48px;height:48px;object-fit:cover;border-radius:10px;">';
        }
        $fileDiAssets = __DIR__ . '/../../assets/img/' . basename($val);
        if (file_exists($fileDiAssets)) {
            return '<img src="' . BASE_URL . '/assets/img/' . htmlspecialchars(basename($val), ENT_QUOTES, 'UTF-8') . '" alt="Kategori" style="width:48px;height:48px;object-fit:cover;border-radius:10px;">';
        }
        if (gambar_url('kategori', $val)) {
            return img_tag('kategori', $val);
        }
    }

    // 2. Fallback otomatis memakai foto menu yang sudah ada di assets/img/ GitHub
    $bawaan = [
        'menu-utama'  => 'ayam-geprek.jpg',
        'paket-hemat' => 'paket-hemat.jpg',
        'minuman'     => 'teh-pucuk.jpg',
        'tambahan'    => 'extra-sambal.jpg',
    ];
    foreach ($bawaan as $kunci => $namaFile) {
        if (strpos($slug, $kunci) !== false && file_exists(__DIR__ . '/../../assets/img/' . $namaFile)) {
            return '<img src="' . BASE_URL . '/assets/img/' . $namaFile . '" alt="Kategori" style="width:48px;height:48px;object-fit:cover;border-radius:10px;">';
        }
    }

    return img_tag('kategori', '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['aksi'] ?? '') === 'hapus') {
            $c = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id=?");
            $c->execute([$_POST['id']]);
            if ($c->fetchColumn() > 0) throw new Exception('Kategori masih dipakai oleh produk, tidak bisa dihapus.');
            $g = $pdo->prepare("SELECT gambar FROM kategori WHERE id=?");
            $g->execute([$_POST['id']]);
            @hapus_gambar('kategori', $g->fetchColumn());
            $pdo->prepare("DELETE FROM kategori WHERE id=?")->execute([$_POST['id']]);
            set_flash('success', 'Kategori dihapus.');
        } else {
            $nama = trim($_POST['nama_kategori']);
            if ($nama === '') throw new Exception('Nama kategori wajib diisi.');
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-');
            $gambar = @upload_gambar('gambar', 'kategori');

            // Jika upload server gagal (Wasmer read-only), simpan nama file aslinya jika ada di assets/img/
            if (!$gambar && !empty($_FILES['gambar']['name'])) {
                $namaAsli = basename($_FILES['gambar']['name']);
                if (file_exists(__DIR__ . '/../../assets/img/' . $namaAsli)) {
                    $gambar = $namaAsli;
                }
            }

            if (!empty($_POST['id'])) {
                $o = $pdo->prepare("SELECT gambar FROM kategori WHERE id=?");
                $o->execute([$_POST['id']]);
                $lama = $o->fetchColumn();
                $pdo->prepare("UPDATE kategori SET nama_kategori=?, slug=?, deskripsi=?, gambar=? WHERE id=?")
                    ->execute([$nama, $slug, trim($_POST['deskripsi']), $gambar ?: $lama, $_POST['id']]);
                if ($gambar && $gambar !== $lama) @hapus_gambar('kategori', $lama);
                set_flash('success', 'Kategori diperbarui.');
            } else {
                $pdo->prepare("INSERT INTO kategori (nama_kategori,slug,deskripsi,gambar) VALUES (?,?,?,?)")
                    ->execute([$nama, $slug, trim($_POST['deskripsi']), $gambar]);
                set_flash('success', 'Kategori ditambahkan.');
            }
        }
    } catch (PDOException $e) {
        set_flash('danger', 'Gagal menyimpan: nama kategori sudah ada.');
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    redirect(ADMIN_URL . '/kategori/index.php');
}

$e = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM kategori WHERE id=?");
    $s->execute([(int) $_GET['edit']]);
    $e = $s->fetch();
}
$list = $pdo->query("SELECT k.*, (SELECT COUNT(*) FROM produk p WHERE p.kategori_id=k.id) jml FROM kategori k ORDER BY k.id")->fetchAll();
page_start('Kelola Kategori', 'kategori', 'Kelompokkan menu agar mudah dicari pelanggan.');
?>
<div class="grid2" style="grid-template-columns:minmax(300px,420px) 1fr">
  <div class="panel">
    <h2><?= $e ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
    <form method="POST" action="<?= ADMIN_URL ?>/kategori/index.php" enctype="multipart/form-data">
      <?= csrf() ?>
      <?php if ($e): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
      <div class="form-group">
        <label>Nama Kategori</label>
        <input name="nama_kategori" value="<?= clean($e['nama_kategori'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="3"><?= clean($e['deskripsi'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Gambar Kategori</label>
        <?php if ($e): ?>
          <div style="margin-bottom:8px"><?= tampil_gambar_kategori($e) ?></div>
        <?php endif; ?>
        <input type="file" name="gambar" accept="image/*">
      </div>
      <button type="submit" class="btn btn-merah">Simpan</button>
      <?php if ($e): ?><a class="btn btn-abu" href="<?= ADMIN_URL ?>/kategori/index.php">Batal</a><?php endif; ?>
    </form>
  </div>
  <div class="panel">
    <h2>Daftar Kategori</h2>
    <div class="tbl">
      <table>
        <tr>
          <th class="c">Gambar</th>
          <th>Nama</th>
          <th>Deskripsi</th>
          <th class="c">Produk</th>
          <th class="c">Aksi</th>
        </tr>
        <?php foreach ($list as $k): ?>
          <tr>
            <td class="c"><div style="display:inline-block"><?= tampil_gambar_kategori($k) ?></div></td>
            <td><strong><?= clean($k['nama_kategori']) ?></strong></td>
            <td><?= clean($k['deskripsi']) ?></td>
            <td class="c"><?= $k['jml'] ?></td>
            <td class="c">
              <div class="aksi-grup">
                <a class="btn btn-abu btn-sm" href="<?= ADMIN_URL ?>/kategori/index.php?edit=<?= (int) $k['id'] ?>">Edit</a>
                <?= aksi('', 'Hapus', 'btn-bahaya', ['aksi' => 'hapus', 'id' => $k['id']], 'Hapus kategori ini?') ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
</div>
<?php page_end(); ?>
