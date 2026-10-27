<?php
require 'config.php';
need();
$u = $_SESSION['u'];
if ($u['role'] === 'admin') {
  header('Location: ' . BASE . '/admin/index.php');
  exit;
}
$q = trim($_GET['q'] ?? '');
$kat = trim($_GET['kat'] ?? '');
$aman = isset($_GET['aman']);
$all = $pdo->query(SQL_BARANG . ' ORDER BY b.nama')->fetchAll();
$cats = array_values(array_unique(array_filter(array_column($all, 'kategori'))));
$list = array_filter($all, fn($b) => ($q === '' || stripos($b['nama'] . ' ' . $b['kode'] . ' ' . $b['kategori'], $q) !== false) && ($kat === '' || $b['kategori'] === $kat) && (!$aman || bisa_pinjam($b)));
$siap = count(array_filter($all, 'bisa_pinjam'));
$url = fn($o) => '?' . http_build_query(array_filter(array_merge(['q' => $q, 'kat' => $kat, 'aman' => $aman ? 1 : ''], $o), fn($v) => $v !== '' && $v !== null));
$mine = $pdo->prepare('SELECT p.*, b.nama, b.kode, b.foto FROM peminjaman p JOIN barang b ON b.id=p.barang_id WHERE p.user_id=? AND p.tgl_kembali IS NULL');
$mine->execute([$u['id']]);
$mine = $mine->fetchAll();
$jam = (int)date('G');
$sapa = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
head('Beranda', false);
?>
<section class="hero">
  <h1><?= $sapa ?>, <?= e(explode(' ', $u['nama'])[0]) ?> 👋</h1>
  <p><?= $siap ?> dari <?= count($all) ?> barang siap dipinjam hari ini. Mau pinjam apa?</p>
  <form class="sr" method="get"><?= ic('search') ?><input name="q" placeholder="Cari proyektor, tenda, sound..." value="<?= e($q) ?>" aria-label="Cari barang"><?php if ($kat) echo '<input type="hidden" name="kat" value="' . e($kat) . '">';
                                                                                                                                                                if ($aman) echo '<input type="hidden" name="aman" value="1">'; ?><button>Cari</button></form>
</section>

<h2 id="pinjaman" style="margin-top:8px">Pinjaman saya</h2>
<?php if (!$mine) echo '<div class="c empty"><div>' . ic('clock') . '</div>Belum ada barang yang sedang Anda pinjam.</div>';
foreach ($mine as $r) {
  $sisa = (int)floor((strtotime($r['tgl_rencana'] ?: $r['tgl_ambil']) - strtotime(date('Y-m-d'))) / 86400);
  $lbl = $sisa < 0 ? '<span class="lb l3">Terlambat ' . (-$sisa) . ' hari</span>' : ($sisa === 0 ? '<span class="lb l2">Kembali hari ini</span>' : '<span class="lb l1">' . $sisa . ' hari lagi</span>');
  echo '<div class="c row">' . foto_html($r, 'thumb') . '<div class="g"><b>' . e($r['nama']) . '</b><div class="m">Wajib kembali ' . e(date('d/m/Y', strtotime($r['tgl_rencana'] ?: $r['tgl_ambil']))) . '</div>' . $lbl . '</div><a class="b" style="margin:0" href="barang.php?kode=' . urlencode($r['kode']) . '#kembali">Kembalikan</a></div>';
} ?>

<h2>Daftar barang</h2>
<div class="chips"><a class="chip<?= $kat === '' ? ' on' : '' ?>" href="<?= e($url(['kat' => ''])) ?>">Semua</a>
  <?php foreach ($cats as $c) echo '<a class="chip' . ($kat === $c ? ' on' : '') . '" href="' . e($url(['kat' => $c])) . '">' . e($c) . '</a>'; ?>
  <a class="chip ok2<?= $aman ? ' on' : '' ?>" href="<?= e($url(['aman' => $aman ? '' : 1])) ?>">✓ Siap dipinjam</a>
</div>
<?php if (!$list) echo '<div class="c empty"><div>' . ic('box') . '</div>Tidak ada barang yang cocok.<br><a href="dashboard.php">Tampilkan semua barang</a></div>'; ?>
<div class="grid"><?php foreach ($list as $b) { ?>
    <div class="c item"><a class="cv" href="barang.php?kode=<?= urlencode($b['kode']) ?>">
        <div class="pw"><?= foto_html($b) ?><?= lb($b) ?></div>
        <div class="bd">
          <h3><?= e($b['nama']) ?></h3>
          <div class="m"><?= e($b['kategori']) ?> · <?= e($b['lokasi']) ?></div>
          <p class="m" style="margin:8px 0 0"><?= e(mb_strimwidth((string)$b['deskripsi'], 0, 80, '...')) ?></p>
          <div class="cta"><span><?= bisa_pinjam($b) ? 'Pinjam sekarang' : 'Lihat detail' ?></span><span>→</span></div>
        </div>
      </a></div>
  <?php } ?>
</div>
<?php foot();
