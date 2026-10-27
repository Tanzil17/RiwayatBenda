<?php
require 'config.php';
$err = '';
$v = ['nama' => '', 'email' => '', 'wa' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $v = ['nama' => trim($_POST['nama'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'wa' => trim($_POST['wa'] ?? '')];
  $p = $_POST['password'] ?? '';
  $wa = nomor_wa($v['wa']);
  if ($v['nama'] === '' || !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err = 'Isi nama dan email yang valid.';
  elseif ($wa === '') $err = 'Nomor WhatsApp tidak valid. Contoh: 081234567890.';
  elseif (strlen($p) < 6) $err = 'Kata sandi minimal 6 karakter.';
  else try {
    // Peran selalu "user". Akun admin hanya dibuat oleh master admin lewat database.
    $pdo->prepare("INSERT INTO users(nama,email,no_wa,password,role) VALUES(?,?,?,?, 'user')")->execute([$v['nama'], $v['email'], $wa, password_hash($p, PASSWORD_DEFAULT)]);
    header('Location: index.php');
    exit;
  } catch (PDOException $x) {
    $err = 'Email sudah terdaftar.';
  }
}
auth_open('Daftar', 'Buat akun peminjam. Nomor WhatsApp dipakai untuk mengirim ringkasan peminjaman dan batas pengembalian.');
if ($err) echo '<p class="er">' . e($err) . '</p>';
?>
<form method="post"><label>Nama lengkap</label><input name="nama" value="<?= e($v['nama']) ?>" required>
  <label>Email</label><input type="email" name="email" value="<?= e($v['email']) ?>" required>
  <label>Nomor WhatsApp</label><input type="tel" name="wa" value="<?= e($v['wa']) ?>" placeholder="081234567890" required>
  <label>Kata sandi</label><input type="password" name="password" required><button>Buat akun</button>
</form>
<p class="m" style="margin-top:16px">Sudah punya akun? <a href="index.php">Masuk</a></p>
<?php auth_close();
