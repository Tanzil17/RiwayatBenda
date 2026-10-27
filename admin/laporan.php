<?php
require '../config.php';
need('admin');
$bulan = preg_match('/^\d{4}-\d{2}$/', $_GET['bulan'] ?? '') ? $_GET['bulan'] : date('Y-m');
$dari = "$bulan-01 00:00:00";
$sampai = date('Y-m-d 00:00:00', strtotime("$dari +1 month"));
$q = function ($sql, $p = []) use ($pdo) {
  $s = $pdo->prepare($sql);
  $s->execute($p);
  return $s->fetchAll();
};
$pakai = $q('SELECT b.kode, b.nama, u.nama AS peminjam, p.tgl_ambil, p.tgl_kembali, p.kondisi_ambil, p.kondisi_kembali, p.catatan_ambil, p.catatan_kembali
  FROM peminjaman p JOIN barang b ON b.id=p.barang_id JOIN users u ON u.id=p.user_id WHERE p.tgl_ambil>=? AND p.tgl_ambil<? ORDER BY p.tgl_ambil', [$dari, $sampai]);
if (isset($_GET['csv'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header("Content-Disposition: attachment; filename=laporan-$bulan.csv");
  $o = fopen('php://output', 'w');
  fputcsv($o, ['Kode', 'Barang', 'Peminjam', 'Ambil', 'Kembali', 'Kondisi ambil', 'Kondisi kembali', 'Catatan ambil', 'Catatan kembali']);
  foreach ($pakai as $r) fputcsv($o, [$r['kode'], $r['nama'], $r['peminjam'], $r['tgl_ambil'], $r['tgl_kembali'], KONDISI[$r['kondisi_ambil']], $r['kondisi_kembali'] === null ? '' : KONDISI[$r['kondisi_kembali']], $r['catatan_ambil'], $r['catatan_kembali']]);
  exit;
}
$memburuk = array_filter($pakai, fn($r) => $r['kondisi_kembali'] !== null && $r['kondisi_kembali'] > $r['kondisi_ambil']);
$rawat = $q('SELECT r.*, b.nama, u.nama AS admin FROM perawatan r JOIN barang b ON b.id=r.barang_id JOIN users u ON u.id=r.admin_id WHERE tanggal>=? AND tanggal<? ORDER BY tanggal', [$dari, $sampai]);
$tl = $q('SELECT t.*, b.nama FROM tindak_lanjut t JOIN barang b ON b.id=t.barang_id WHERE t.created_at>=? AND t.created_at<? ORDER BY t.created_at', [$dari, $sampai]);
$tlSelesai = count(array_filter($tl, fn($t) => $t['status'] === 'selesai'));
$telat = array_filter($pdo->query(SQL_BARANG)->fetchAll(), fn($b) => sisa_hari($b) < 0);
$perBarang = [];
foreach ($pakai as $r) $perBarang[$r['nama']] = ($perBarang[$r['nama']] ?? 0) + 1;
arsort($perBarang);
head('Laporan bulanan');
?>
<form class="c np" method="get"><input type="month" name="bulan" value="<?= e($bulan) ?>"><button>Tampilkan</button> <a class="b b2" href="?bulan=<?= e($bulan) ?>&csv=1">Unduh CSV</a> <button type="button" onclick="print()">Cetak</button></form>
<div class="c"><b>Ringkasan <?= e($bulan) ?></b>
  <table>
    <tr>
      <td>Jumlah peminjaman</td>
      <td><?= count($pakai) ?></td>
    </tr>
    <tr>
      <td>Pengembalian dengan kondisi memburuk</td>
      <td><?= count($memburuk) ?></td>
    </tr>
    <tr>
      <td>Perawatan dilakukan</td>
      <td><?= count($rawat) ?></td>
    </tr>
    <tr>
      <td>Tindak lanjut dibuat / selesai</td>
      <td><?= count($tl) ?> / <?= $tlSelesai ?></td>
    </tr>
    <tr>
      <td>Barang terlambat diperiksa (saat ini)</td>
      <td><?= count($telat) ?></td>
    </tr>
  </table>
</div>
<h2>Pemakaian per barang</h2>
<div class="c"><?php foreach ($perBarang as $n => $j) echo e($n) . ': ' . $j . 'x<br>';
                if (!$perBarang) echo '<span class="m">Tidak ada pemakaian.</span>'; ?></div>
<h2>Rincian peminjaman</h2>
<div class="c">
  <table>
    <tr>
      <th>Barang</th>
      <th>Peminjam</th>
      <th>Ambil / kembali</th>
      <th>Kondisi</th>
    </tr>
    <?php foreach ($pakai as $r): ?><tr>
        <td><?= e($r['nama']) ?></td>
        <td><?= e($r['peminjam']) ?></td>
        <td><?= e($r['tgl_ambil']) ?><br><span class="m"><?= e($r['tgl_kembali'] ?: 'belum kembali') ?></span></td>
        <td><?= kb($r['kondisi_ambil']) ?> &rarr; <?= $r['kondisi_kembali'] === null ? '-' : kb($r['kondisi_kembali']) ?></td>
      </tr><?php endforeach; ?>
  </table>
</div>
<h2>Perawatan</h2>
<div class="c"><?php foreach ($rawat as $r) echo e($r['tanggal']) . ' - ' . e($r['nama']) . ': ' . e($r['tindakan']) . ' (' . e($r['admin']) . ')<br>';
                if (!$rawat) echo '<span class="m">Tidak ada.</span>'; ?></div>
<?php foot();
