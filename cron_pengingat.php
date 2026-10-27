<?php
// Jalankan tiap hari lewat cron / Task Scheduler:  php cron_pengingat.php
// Mengirim pengingat WhatsApp untuk pinjaman yang jatuh tempo besok atau sudah terlambat.
if (PHP_SAPI !== 'cli') exit('Hanya untuk CLI');
require __DIR__ . '/config.php';
$rows = $pdo->query("SELECT p.tgl_rencana, b.nama AS barang, u.nama, u.no_wa FROM peminjaman p JOIN barang b ON b.id=p.barang_id JOIN users u ON u.id=p.user_id
  WHERE p.tgl_kembali IS NULL AND p.tgl_rencana IS NOT NULL AND p.tgl_rencana <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)")->fetchAll();
foreach ($rows as $r) {
  $telat = $r['tgl_rencana'] < date('Y-m-d');
  $tgl = date('d/m/Y', strtotime($r['tgl_rencana']));
  $pesan = "Halo {$r['nama']},\n" . ($telat ? "Pengembalian *{$r['barang']}* sudah melewati batas ($tgl)." : "Pengingat: *{$r['barang']}* harus dikembalikan besok ($tgl).") . "\nMohon kembalikan lewat pindai QR pada barang. Terima kasih.";
  echo $r['nama'] . ': ' . (kirim_wa($r['no_wa'], $pesan) ? 'terkirim' : 'gagal') . "\n";
}
