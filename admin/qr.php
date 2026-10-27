<?php
// Membuat QR code di browser (tanpa library PHP). Setiap QR berisi URL halaman barang.
require '../config.php';
need('admin');
if (!empty($_GET['kode'])) {
  $s = $pdo->prepare('SELECT kode,nama,lokasi FROM barang WHERE kode=?');
  $s->execute([$_GET['kode']]);
  $list = $s->fetchAll();
} else $list = $pdo->query('SELECT kode,nama,lokasi FROM barang ORDER BY nama')->fetchAll();
head('Cetak QR code');
?>
<p class="np"><button onclick="print()">Cetak</button> <span class="m">Tempel QR pada barang. Saat dipindai, HP langsung membuka halaman barang tersebut.</span></p>
<div style="display:flex;flex-wrap:wrap;gap:12px">
  <?php foreach ($list as $b): ?>
    <div class="c" style="width:200px;text-align:center">
      <div class="qr" data-kode="<?= e($b['kode']) ?>"></div><b><?= e($b['nama']) ?></b>
      <div class="m"><?= e($b['kode']) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<script src="<?= BASE ?>/assets/qrcode.js"></script>
<script>
  // Alamat dasar otomatis dari URL saat ini. Pakai IP/domain server (bukan localhost) agar bisa dipindai HP.
  var base = location.origin + '<?= BASE ?>/barang.php?kode=';
  document.querySelectorAll('.qr').forEach(function(el) {
    var q = qrcode(0, 'M');
    q.addData(base + encodeURIComponent(el.dataset.kode));
    q.make();
    el.innerHTML = q.createSvgTag(5, 0);
  });
</script>
<?php foot();
