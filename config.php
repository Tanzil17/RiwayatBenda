<?php
if (PHP_VERSION_ID < 70400) exit('RiwayatBenda butuh PHP 7.4 atau lebih baru. Versi Anda: ' . PHP_VERSION);
session_start();

// ---- Pengaturan database: ubah di sini bila MySQL Anda memakai password ----
const DB_HOST = 'localhost', DB_NAME = 'riwayatbenda', DB_USER = 'root', DB_PASS = '';

// BASE (alamat folder aplikasi di browser) dihitung dari alamat halaman yang sedang dibuka,
// jadi tetap benar di XAMPP/Windows maupun saat nama folder diganti.
function deteksi_base($scriptName, $scriptFile, $dir)
{
  $sf = str_replace('\\', '/', $scriptFile);
  $dir = rtrim(str_replace('\\', '/', $dir), '/');
  $rel = stripos($sf, $dir . '/') === 0 ? substr($sf, strlen($dir) + 1) : basename($sf);
  $b = str_replace('\\', '/', $scriptName);
  for ($i = 0; $i <= substr_count($rel, '/'); $i++) $b = dirname($b);
  return ($b === '/' || $b === '.' || $b === '' || $b === '\\') ? '' : rtrim($b, '/');
}
define('BASE', deteksi_base($_SERVER['SCRIPT_NAME'] ?? '', (string)(realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: ($_SERVER['SCRIPT_FILENAME'] ?? '')), __DIR__));

// Pesan galat yang mudah dipahami (bukan layar putih)
set_exception_handler(function ($x) {
  $m = $x->getMessage();
  http_response_code(500);
  if (stripos($m, 'could not find driver') !== false) $tip = 'Ekstensi pdo_mysql belum aktif. Aktifkan di php.ini (hapus tanda ; pada extension=pdo_mysql) lalu restart Apache.';
  elseif (strpos($m, '2002') !== false || stripos($m, 'refused') !== false) $tip = 'MySQL belum menyala. Nyalakan MySQL di XAMPP/Laragon, lalu muat ulang.';
  elseif (strpos($m, '1045') !== false || stripos($m, 'Access denied') !== false) $tip = 'Username atau password MySQL salah. Sesuaikan DB_USER dan DB_PASS di config.php.';
  elseif (strpos($m, '1049') !== false || strpos($m, '1146') !== false || stripos($m, "doesn't exist") !== false || stripos($m, 'Unknown database') !== false) $tip = 'Database belum dibuat. Impor database.sql lewat phpMyAdmin (tab Import).';
  elseif (strpos($m, '1054') !== false || stripos($m, 'Unknown column') !== false) $tip = 'Struktur database masih versi lama. Hapus database riwayatbenda di phpMyAdmin lalu impor ulang database.sql.';
  else $tip = 'Terjadi kesalahan. Kirim pesan teknis di bawah ini agar mudah dicari penyebabnya.';
  echo '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font:16px/1.6 system-ui,sans-serif;max-width:620px;margin:40px auto;padding:0 18px"><h2>Aplikasi belum bisa dibuka</h2><p><b>' . htmlspecialchars($tip) . '</b></p><pre style="background:#f3f3f3;padding:12px;white-space:pre-wrap;font-size:13px">' . htmlspecialchars($m) . '</pre></body>';
});

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
const WA_TOKEN = '';            // isi token Fonnte (lihat panduan). Kosong = notifikasi WhatsApp nonaktif
const KONDISI = ['Baik', 'Cukup', 'Perlu perbaikan', 'Rusak'];
const SQL_BARANG = "SELECT b.*, NULLIF(GREATEST(
  COALESCE((SELECT MAX(GREATEST(tgl_ambil, COALESCE(tgl_kembali, tgl_ambil))) FROM peminjaman WHERE barang_id=b.id),'2000-01-01'),
  COALESCE((SELECT MAX(tanggal) FROM perawatan WHERE barang_id=b.id),'2000-01-01')),'2000-01-01') AS terakhir,
  (SELECT COUNT(*) FROM tindak_lanjut WHERE barang_id=b.id AND status='terbuka') AS tl_buka FROM barang b";

function e($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function kb($k)
{
  return '<span class="k k' . (int)$k . '">' . KONDISI[(int)$k] . '</span>';
}
function need($role = null)
{
  if (empty($_SESSION['u'])) {
    $_SESSION['next'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . BASE . '/index.php');
    exit;
  }
  if ($role && $_SESSION['u']['role'] !== $role) {
    http_response_code(403);
    exit('Akses ditolak: halaman ini khusus admin.');
  }
}
function csrf()
{
  $_SESSION['t'] ??= bin2hex(random_bytes(16));
  return '<input type="hidden" name="t" value="' . $_SESSION['t'] . '">';
}
function chk()
{
  if (!hash_equals($_SESSION['t'] ?? '', $_POST['t'] ?? '')) {
    http_response_code(400);
    exit('Token tidak valid, muat ulang halaman.');
  }
}
function sisa_hari($b)
{
  if (empty($b['terakhir'])) return null;
  return (int)round((strtotime($b['terakhir']) + $b['interval_hari'] * 86400 - time()) / 86400);
}
function status_periksa($b)
{
  $n = sisa_hari($b);
  if ($n === null) return '<span class="k k1">Belum pernah diperiksa</span>';
  return $n < 0 ? '<span class="k k3">Terlambat periksa ' . (-$n) . ' hari</span>' : '<span class="k">Periksa ' . $n . ' hari lagi</span>';
}

// Aturan peminjaman: hanya barang tersedia dengan kondisi Baik/Cukup yang aman dipinjam
function bisa_pinjam($b)
{
  return $b['status'] === 'tersedia' && (int)$b['kondisi'] <= 1;
}
function label_user($b)
{
  if ($b['status'] === 'dipinjam') return ['Sedang dipinjam', 'l1'];
  if ((int)$b['kondisi'] >= 3) return ['Rusak', 'l3'];
  if ((int)$b['kondisi'] === 2) return ['Pemeliharaan', 'l2'];
  return ['Aman dipinjam', 'l0'];
}
function lb($b)
{
  [$t, $c] = label_user($b);
  return '<span class="lb ' . $c . '">' . $t . '</span>';
}

// WhatsApp: nomor dinormalkan ke format 62xxxxxxxx
function nomor_wa($s)
{
  $d = preg_replace('/\D/', '', (string)$s);
  if ($d === '') return '';
  if ($d[0] === '0') $d = '62' . substr($d, 1);
  elseif ($d[0] === '8') $d = '62' . $d;
  return (strpos($d, '62') === 0 && strlen($d) >= 10 && strlen($d) <= 15) ? $d : '';
}
function kirim_wa($no, $pesan)
{   // API Fonnte: POST /send, header Authorization berisi token saja
  if (WA_TOKEN === '' || $no === '') return false;
  $c = curl_init('https://api.fonnte.com/send');
  curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_HTTPHEADER => ['Authorization: ' . WA_TOKEN], CURLOPT_POSTFIELDS => ['target' => $no, 'message' => $pesan]]);
  $r = curl_exec($c);
  curl_close($c);
  $j = json_decode((string)$r, true);
  return !empty($j['status']);
}

// Foto barang: 1 foto, maks 2 MB, JPG/PNG/WebP
function foto_html($b, $cls = 'ph')
{
  if (!empty($b['foto'])) return '<img class="' . $cls . '" src="' . e(BASE . '/uploads/' . rawurlencode($b['foto'])) . '" alt="' . e($b['nama']) . '" loading="lazy">';
  return '<div class="' . $cls . ' none"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/></svg></div>';
}
function simpan_foto($f, $lama = null)
{   // return [namaFile, pesanError]
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [$lama, ''];
  if ($f['error'] !== UPLOAD_ERR_OK) return [$lama, 'Unggah foto gagal.'];
  if ($f['size'] > 2 * 1024 * 1024) return [$lama, 'Ukuran foto maksimal 2 MB.'];
  $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
  if (!$ext || !getimagesize($f['tmp_name'])) return [$lama, 'Format foto harus JPG, PNG, atau WebP.'];
  $dir = __DIR__ . '/uploads';
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $nama = bin2hex(random_bytes(8)) . '.' . $ext;
  if (!move_uploaded_file($f['tmp_name'], "$dir/$nama")) return [$lama, 'Foto tidak dapat disimpan.'];
  if ($lama) @unlink("$dir/" . basename($lama));
  return [$nama, ''];
}

function ic($n)
{
  $p = [
    'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'qr' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h3v3h-3zM20 14v6h-4"/>',
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    'out' => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
    'back' => '<path d="M15 5l-7 7 7 7"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
    'box' => '<path d="M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10"/>'
  ];
  return '<svg class="ic" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$n] . '</svg>';
}
function nav_items()
{
  $u = $_SESSION['u'] ?? null;
  if (!$u) return [];
  $s = $_SERVER['SCRIPT_NAME'];
  $has = fn($x) => substr($s, -strlen($x)) === $x;
  if ($u['role'] === 'admin') return [
    [BASE . '/admin/index.php', 'Beranda', 'home', $has('admin/index.php')],
    [BASE . '/admin/qr.php', 'QR', 'qr', $has('admin/qr.php')],
    [BASE . '/admin/laporan.php', 'Laporan', 'chart', $has('admin/laporan.php')],
    [BASE . '/logout.php', 'Keluar', 'out', false]
  ];
  return [
    [BASE . '/dashboard.php', 'Beranda', 'home', $has('dashboard.php') || $has('barang.php')],
    [BASE . '/dashboard.php#pinjaman', 'Pinjaman saya', 'clock', false],
    [BASE . '/logout.php', 'Keluar', 'out', false]
  ];
}
// Logo: letakkan file assets/logo.png (atau .svg/.jpg/.webp) untuk mengganti ikon bawaan
function logo_file()
{
  foreach (['svg', 'png', 'webp', 'jpg', 'jpeg'] as $x) if (is_file(__DIR__ . "/assets/logo.$x")) return "logo.$x";
  return null;
}
function logo_html()
{
  if ($f = logo_file()) return '<img class="logo" src="' . BASE . '/assets/' . $f . '?v=' . @filemtime(__DIR__ . '/assets/' . $f) . '" alt="">';
  return '<svg class="mk" viewBox="0 0 24 24" fill="none" stroke="#f2b84b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/></svg>';
}
function favicon_tag()
{
  if ($f = logo_file()) return '<link rel="icon" href="' . BASE . '/assets/' . $f . '?v=' . @filemtime(__DIR__ . '/assets/' . $f) . '">';
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="#0b3b39"/><g fill="none" stroke="#f2b84b" stroke-width="1.8" stroke-linejoin="round" transform="translate(3 3) scale(.75)"><path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/></g></svg>';
  return '<link rel="icon" href="data:image/svg+xml,' . rawurlencode($svg) . '">';
}
function head($t, $h1 = true)
{
  $u = $_SESSION['u'] ?? null;
  $a = $u && $u['role'] === 'admin';
  echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>' . e($t) . ' - RiwayatBenda</title>
  ' . favicon_tag() . '<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="' . BASE . '/assets/style.css?v=' . @filemtime(__DIR__ . '/assets/style.css') . '"></head><body><div class="top"><div class="in"><a class="brand" href="' . BASE . ($a ? '/admin/index.php' : '/dashboard.php') . '">' . logo_html() . 'RiwayatBenda</a>';
  if ($u) {
    echo '<nav>';
    foreach (nav_items() as $n) echo '<a href="' . e($n[0]) . '"' . ($n[3] ? ' class="on"' : '') . '>' . ic($n[2]) . '<span>' . e($n[1]) . '</span></a>';
    echo '<span class="me"><span class="av">' . e(mb_strtoupper(mb_substr($u['nama'], 0, 1))) . '</span>' . e(explode(' ', $u['nama'])[0]) . '</span></nav>';
  }
  echo '</div></div><main class="w">' . ($h1 ? '<h1>' . e($t) . '</h1>' : '');
}
function js_submit()
{   // cegah tekan ganda: tombol dinonaktifkan sementara formulir dikirim
  echo '<script>addEventListener("pageshow",function(){document.querySelectorAll("button[data-t]").forEach(function(b){b.disabled=false;b.textContent=b.dataset.t})});document.addEventListener("submit",function(e){var b=e.target.querySelector("button");if(b&&!b.disabled){b.dataset.t=b.textContent;setTimeout(function(){b.disabled=true;b.textContent="Memproses..."},0)}});</script>';
}
function foot()
{
  echo '</main>';
  if ($n = nav_items()) {
    echo '<nav class="bn">';
    foreach ($n as $i) echo '<a href="' . e($i[0]) . '"' . ($i[3] ? ' class="on"' : '') . '>' . ic($i[2]) . '<span>' . e($i[1]) . '</span></a>';
    echo '</nav>';
  }
  js_submit();
  echo '</body></html>';
}

function auth_open($judul, $sub)
{
  echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($judul) . ' - RiwayatBenda</title>
  ' . favicon_tag() . '<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="' . BASE . '/assets/style.css?v=' . @filemtime(__DIR__ . '/assets/style.css') . '"></head><body><div class="auth"><section class="side"><div class="brand">' . logo_html() . 'RiwayatBenda</div>
  <div><h2>Tahu kondisi barang sebelum Anda memakainya.</h2><p>Catat pengambilan, pengembalian, dan kondisi fisik barang bersama dengan satu pindaian QR.</p></div>
  <svg viewBox="0 0 70 70" fill="#f2b84b"><path fill-rule="evenodd" d="M0 0h21v21H0zM4 4v13h13V4zM49 0h21v21H49zM53 4v13h13V4zM0 49h21v21H0zM4 53v13h13V53z"/><path d="M7 7h7v7H7zM56 7h7v7h-7zM7 56h7v7H7zM28 0h7v7h-7zM42 7h7v7h-7zM28 14h7v7h-7zM28 28h14v7H28zM49 28h7v14h-7zM63 35h7v14h-7zM35 42h7v14h-7zM49 56h14v7H49zM28 49h7v7h-7zM0 28h14v7H0zM14 35h7v7h-7z"/></svg>
  <ul><li>Barang aman, rusak, atau dalam pemeliharaan langsung terlihat</li><li>Ringkasan peminjaman dikirim lewat WhatsApp</li><li>Riwayat perawatan tersimpan untuk semua pengurus</li></ul></section>
  <section class="fw"><div class="fb"><h1>' . e($judul) . '</h1><p class="sub">' . e($sub) . '</p>';
}
function auth_close()
{
  echo '</div></section></div>';
  js_submit();
  echo '</body></html>';
}
