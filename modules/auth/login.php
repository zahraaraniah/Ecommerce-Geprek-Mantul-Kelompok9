<?php
/**
 * FILE INI: LOGIN PELANGGAN & ADMIN (OTOMATIS SESUAI PERAN)
 */
require_once __DIR__ . '/../../config/config.php';
$pageTitle = "Masuk";

if (!empty($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/admin/dashboard.php');
}
if (is_login()) {
    redirect(BASE_URL . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? AND status_aktif = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['kata_sandi'])) {
        session_regenerate_id(true);

        if (in_array($user['peran'], ['superadmin', 'gudang', 'kasir'], true)) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_nama'] = $user['nama_lengkap'];
            $_SESSION['admin_peran'] = $user['peran'];
            redirect(BASE_URL . '/admin/dashboard.php');
        } else {
            $_SESSION['pengguna_id'] = $user['id'];
            $_SESSION['pengguna_nama'] = $user['nama_lengkap'];
            $_SESSION['pengguna_peran'] = $user['peran'];
            set_flash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
            redirect(BASE_URL . '/index.php');
        }
    } else {
        set_flash('danger', 'Email atau password salah, atau akun tidak aktif.');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="form-box">
  <h2>Masuk ke Akun</h2>
  <?php show_flash(); ?>
  <form method="POST">
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="btn-primary" style="width:100%">Masuk</button>
  </form>
  <div class="form-footer">
    Belum punya akun? <a href="<?= BASE_URL ?>/modules/auth/register.php">Daftar di sini</a>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>