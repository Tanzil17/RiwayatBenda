<?php
require '../config.php';
need('admin');
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk();
  $a = $_POST['aksi'] ?? '';
  if ($a === 'tambah' && trim($_POST['nama'] ?? '') !== '') {
    [$foto, $err] = simpan_foto($_FILES['foto'] ?? []);
    if (!$err) {
      $kode = 'BRG-' . strtoupper(bin2hex(random_bytes(3)));
      $pdo->prepare('INSERT INTO barang(kode,nama,kategori,lokasi,deskripsi,foto,interval_hari) VALUES(?,?,?,?,?,?,?)')->execute([$kode, trim($_POST['nama']), trim($_POST['kategori']), trim($_POST['lokasi']), trim($_POST['deskripsi']), $foto, max(1, (int)$_POST['interval'])]);
      $msg = "Barang ditambahkan dengan kode $kode.";
    }
  } elseif ($a === 'foto') {
    $id = (int)$_POST['barang_id'];
    $o = $pdo->prepare('SELECT foto FROM barang WHERE id=?');
    $o->execute([$id]);
    $lama = $o->fetchColumn();
    [$foto, $err] = simpan_foto($_FILES['foto'] ?? [], $lama ?: null);
    if (!$err) {
      $pdo->prepare('UPDATE barang SET deskripsi=?, foto=? WHERE id=?')->execute([trim($_POST['deskripsi']), $foto, $id]);
      $msg = 'Foto dan deskripsi diperbarui.';
    }
  } elseif ($a === 'rawat') {
    $k = max(0, min(3, (int)$_POST['kondisi']));
    $id = (int)$_POST['barang_id'];
    $now = date('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO perawatan(barang_id,admin_id,tanggal,tindakan,kondisi_sesudah) VALUES(?,?,?,?,?)')->execute([$id, $_SESSION['u']['id'], $now, trim($_POST['tindakan']) ?: 'Pemeriksaan rutin', $k]);
    $pdo->prepare('UPDATE barang SET kondisi=? WHERE id=?')->execute([$k, $id]);
    if (!empty($_POST['tutup'])) $pdo->prepare("UPDATE tindak_lanjut SET status='selesai', selesai_at=? WHERE barang_id=? AND status='terbuka'")->execute([$now, $id]);
    $msg = 'Perawatan dicatat.';
  }
}
$all = $pdo->query(SQL_BARANG . ' ORDER BY tl_buka DESC, b.nama')->fetchAll();
$pinjam = count(array_filter($all, fn($b) => $b['status'] === 'dipinjam'));
$tl = array_sum(array_column($all, 'tl_buka'));
$telat = count(array_filter($all, fn($b) => sisa_hari($b) < 0));
head('Dashboard admin');
if ($msg) echo '<p class="ok">' . e($msg) . '</p>';
if ($err) echo '<p class="er">' . e($err) . '</p>';
$opsi = '';
foreach (KONDISI as $i => $n) $opsi .= "<option value=\"$i\">$n</option>";
?>
<div class="stats">
  <div class="stat"><b><?= count($all) ?></b><span class="m">Barang</span></div>
  <div class="stat"><b><?= $pinjam ?></b><span class="m">Sedang dipinjam</span></div>
  <div class="stat"><b><?= $tl ?></b><span class="m">Tindak lanjut terbuka</span></div>
  <div class="stat"><b><?= $telat ?></b><span class="m">Terlambat diperiksa</span></div>
</div>
<h2>Semua barang</h2>
<?php foreach ($all as $b): ?>
  <div class="c">
    <div class="row"><?= foto_html($b, 'thumb') ?><div class="g"><a href="../barang.php?kode=<?= urlencode($b['kode']) ?>"><b><?= e($b['nama']) ?></b></a> <span class="m"><?= e($b['kode']) ?> - <?= e($b['lokasi']) ?></span><br>
        <?= kb($b['kondisi']) ?> <span class="k"><?= e($b['status']) ?></span> <?= status_periksa($b) ?> <?php if ($b['tl_buka']) echo '<span class="k k2">' . $b['tl_buka'] . ' tindak lanjut terbuka</span>'; ?></div>
      <a href="qr.php?kode=<?= urlencode($b['kode']) ?>" class="b b2" style="margin:0">QR</a>
    </div>
    <details style="margin-top:10px">
      <summary class="m">Catat pemeriksaan atau perawatan</summary>
      <form method="post"><?= csrf() ?><input type="hidden" name="aksi" value="rawat"><input type="hidden" name="barang_id" value="<?= $b['id'] ?>">
        <label>Tindakan</label><textarea name="tindakan" rows="2" placeholder="mis. ganti kabel, bersihkan lensa"></textarea><label>Kondisi sesudah</label><select name="kondisi"><?= $opsi ?></select>
        <label><input type="checkbox" name="tutup" value="1" style="width:auto" checked> Tutup semua tindak lanjut barang ini</label><button>Simpan</button>
      </form>
    </details>
    <details style="margin-top:6px">
      <summary class="m">Ganti foto dan deskripsi</summary>
      <form method="post" enctype="multipart/form-data"><?= csrf() ?><input type="hidden" name="aksi" value="foto"><input type="hidden" name="barang_id" value="<?= $b['id'] ?>">
        <label>Deskripsi</label><textarea name="deskripsi" rows="2"><?= e($b['deskripsi']) ?></textarea><label>Foto baru (JPG/PNG/WebP, maks 2 MB)</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp"><button>Simpan</button>
      </form>
    </details>
  </div>
<?php endforeach; ?>
<h2>Tambah barang</h2>
<form method="post" class="c" enctype="multipart/form-data"><?= csrf() ?><input type="hidden" name="aksi" value="tambah">
  <label>Nama</label><input name="nama" required><label>Kategori</label><input name="kategori"><label>Lokasi simpan</label><input name="lokasi">
  <label>Deskripsi barang</label><textarea name="deskripsi" rows="2"></textarea><label>Foto barang (JPG/PNG/WebP, maks 2 MB)</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
  <label>Interval pemeriksaan (hari)</label><input type="number" name="interval" value="30" min="1"><button>Tambah barang</button>
</form>
<?php foot();
