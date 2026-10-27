<?php
require 'config.php';
if (!empty($_SESSION['u'])) {
  header('Location: ' . BASE . ($_SESSION['u']['role'] === 'admin' ? '/admin/index.php' : '/dashboard.php'));
  exit;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = $pdo->prepare('SELECT * FROM users WHERE email=?');
  $s->execute([trim($_POST['email'] ?? '')]);
  $u = $s->fetch();
  if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
    session_regenerate_id(true);
    $_SESSION['u'] = ['id' => $u['id'], 'nama' => $u['nama'], 'role' => $u['role']];
    $next = $_SESSION['next'] ?? '';
    unset($_SESSION['next']);
    // Arahkan sesuai peran; tautan QR (barang.php) tetap diprioritaskan
    $tujuan = $u['role'] === 'admin' ? '/admin/index.php' : '/dashboard.php';
    header('Location: ' . ($next && strpos($next, BASE . '/barang.php') === 0 ? $next : BASE . $tujuan));
    exit;
  }
  $err = 'Email atau kata sandi salah.';
}
auth_open('Masuk', 'Gunakan akun Anda untuk melanjutkan.');
if ($err) echo '<p class="er">' . e($err) . '</p>';
?>
<form method="post"><label>Email</label><input type="email" name="email" required autofocus>
  <label>Kata sandi</label><input type="password" name="password" required><button>Masuk</button>
</form>
<p class="m" style="margin-top:16px">Belum punya akun? <a href="register.php">Daftar sebagai peminjam</a></p>
<?php auth_close();
