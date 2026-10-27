<?php
// Halaman tujuan QR code: barang.php?kode=BRG-XXXX
require 'config.php';
need();
$u = $_SESSION['u'];
$adm = $u['role'] === 'admin';
$s = $pdo->prepare('SELECT * FROM barang WHERE kode=?');
$s->execute([$_GET['kode'] ?? '']);
$b = $s->fetch();
if (!$b) {
  http_response_code(404);
  head('Tidak ditemukan');
  echo '<p class="c">Kode barang tidak dikenal.</p>';
  foot();
  exit;
}
$tgl = fn($x) => date('d/m/Y', strtotime($x));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  chk();
  $a = $_POST['aksi'] ?? '';
  $k = max(0, min(3, (int)($_POST['kondisi'] ?? 0)));
  $cat = trim($_POST['catatan'] ?? '');
  $now = date('Y-m-d H:i:s');
  $ok = 0;
  $wa = null;
  $why = '';
  $pdo->beginTransaction();
  $ls = $pdo->prepare('SELECT * FROM barang WHERE id=? FOR UPDATE');
  $ls->execute([$b['id']]);
  $b = $ls->fetch();   // kunci baris: cegah pinjam ganda
  $ps = $pdo->prepare('SELECT * FROM peminjaman WHERE barang_id=? AND tgl_kembali IS NULL');
  $ps->execute([$b['id']]);
  $p = $ps->fetch();
  if ($a === 'pinjam' && !$p && bisa_pinjam($b)) {
    $r = $_POST['rencana'] ?? '';
    $d = DateTime::createFromFormat('Y-m-d', $r);
    if ($d && $d->format('Y-m-d') === $r && $r >= date('Y-m-d') && $r <= date('Y-m-d', strtotime('+30 days'))) {
      $k = min($k, 1);   // barang yang sudah rusak tidak diambil, cukup dilaporkan
      $pdo->prepare('INSERT INTO peminjaman(barang_id,user_id,tgl_ambil,tgl_rencana,kondisi_ambil,catatan_ambil) VALUES(?,?,?,?,?,?)')->execute([$b['id'], $u['id'], $now, $r, $k, $cat]);
      $pdo->prepare("UPDATE barang SET status='dipinjam', kondisi=? WHERE id=?")->execute([$k, $b['id']]);
      $ok = 1;
      $wa = [$u['id'], "Halo {NAMA},\nPeminjaman berhasil dicatat.\n\n*Barang*: {$b['nama']}\n*Diambil*: " . date('d/m/Y H:i') . "\n*Kondisi saat diambil*: " . KONDISI[$k] . "\n*Wajib dikembalikan*: " . $tgl($r) . "\n\nMohon kembalikan tepat waktu dan laporkan jika ada kerusakan. Terima kasih."];
    }
  } elseif ($a === 'kembali' && $p && ($p['user_id'] == $u['id'] || $adm)) {
    $pdo->prepare('UPDATE peminjaman SET tgl_kembali=?, kondisi_kembali=?, catatan_kembali=? WHERE id=?')->execute([$now, $k, $cat, $p['id']]);
    $pdo->prepare("UPDATE barang SET status='tersedia', kondisi=? WHERE id=?")->execute([$k, $b['id']]);
    if ($k > $p['kondisi_ambil'] || $k >= 2)   // kondisi memburuk: buat tindak lanjut otomatis
      $pdo->prepare('INSERT INTO tindak_lanjut(barang_id,peminjaman_id,judul,created_at) VALUES(?,?,?,?)')->execute([$b['id'], $p['id'], $cat ?: 'Periksa kondisi: ' . KONDISI[$k], $now]);
    $ok = 1;
    $wa = [$p['user_id'], "Halo {NAMA},\nPengembalian berhasil dicatat.\n\n*Barang*: {$b['nama']}\n*Dikembalikan*: " . date('d/m/Y H:i') . "\n*Kondisi saat kembali*: " . KONDISI[$k] . ($cat !== '' ? "\n*Catatan*: $cat" : '') . "\n\nTerima kasih sudah menjaga barang bersama."];
  } elseif ($a === 'lapor' && $cat !== '') {
    $pdo->prepare('INSERT INTO tindak_lanjut(barang_id,peminjaman_id,judul,created_at) VALUES(?,?,?,?)')->execute([$b['id'], $p['id'] ?? null, 'Laporan: ' . $cat, $now]);
    if ($k > $b['kondisi']) $pdo->prepare('UPDATE barang SET kondisi=? WHERE id=?')->execute([$k, $b['id']]);
    $ok = 1;
  }
  if (!$ok) {   // jelaskan kenapa aksi tidak diproses
    if ($a === 'pinjam') $why = $p ? 'sudah' : (!bisa_pinjam($b) ? 'tidakaman' : 'tanggal');
    elseif ($a === 'kembali') $why = !$p ? 'belum' : 'bukan';
    elseif ($a === 'lapor') $why = 'catatan';
    else $why = 'lain';
  }
  $pdo->commit();
  $sent = 0;
  if ($wa) {
    $w = $pdo->prepare('SELECT nama,no_wa FROM users WHERE id=?');
    $w->execute([$wa[0]]);
    $w = $w->fetch();
    $sent = (int)kirim_wa($w['no_wa'], str_replace('{NAMA}', $w['nama'], $wa[1]));
  }
  header('Location: barang.php?kode=' . urlencode($b['kode']) . '&ok=' . $ok . '&wa=' . $sent . '&why=' . $why);
  exit;
}

$ps = $pdo->prepare('SELECT p.*, u.nama FROM peminjaman p JOIN users u ON u.id=p.user_id WHERE p.barang_id=? ORDER BY p.tgl_ambil DESC');
$ps->execute([$b['id']]);
$riwayat = $ps->fetchAll();
$aktif = null;
foreach ($riwayat as $r) if (!$r['tgl_kembali']) $aktif = $r;
$opsi = function ($sel, $maks = 3) {
  $h = '';
  foreach (KONDISI as $i => $n) if ($i <= $maks) $h .= '<option value="' . $i . '"' . ($i == $sel ? ' selected' : '') . '>' . $n . '</option>';
  return $h;
};

head($b['nama']);
echo '<a class="back np" href="' . BASE . ($adm ? '/admin/index.php' : '/dashboard.php') . '">' . ic('back') . ($adm ? 'Semua barang' : 'Kembali ke beranda') . '</a>';
if (isset($_GET['ok'])) echo $_GET['ok'] ? '<p class="ok">Tersimpan.' . (!empty($_GET['wa']) ? ' Ringkasan sudah dikirim ke WhatsApp Anda.' : (WA_TOKEN === '' ? '' : ' Notifikasi WhatsApp belum terkirim.')) . '</p>' : '<p class="er">' . e((['sudah' => 'Barang ini sudah dipinjam (mungkin permintaan Anda sudah berhasil, cek bagian Pinjaman saya).', 'tidakaman' => 'Barang ini belum bisa dipinjam karena rusak atau sedang dalam pemeliharaan.', 'tanggal' => 'Tanggal pengembalian tidak valid. Pilih maksimal 30 hari dari hari ini.', 'belum' => 'Barang ini sudah dikembalikan atau belum dipinjam.', 'bukan' => 'Hanya peminjam yang bisa mengembalikan barang ini.', 'catatan' => 'Isi dulu apa yang perlu ditindaklanjuti.'][$_GET['why'] ?? ''] ?? 'Aksi tidak dapat diproses.')) . '</p>';
echo '<div class="det"><div>' . foto_html($b) . '</div><div><div class="c" style="margin-top:0">' . lb($b) . ($adm ? ' ' . kb($b['kondisi']) : '') . '<p style="margin:10px 0 4px">' . nl2br(e($b['deskripsi'] ?: 'Belum ada deskripsi.')) . '</p><div class="m">' . e($b['kategori']) . ' - ' . e($b['lokasi']) . ' - ' . e($b['kode']) . '</div></div>';
if (!$aktif && bisa_pinjam($b)): ?>
  <form method="post" class="c" id="pinjam"><?= csrf() ?><input type="hidden" name="aksi" value="pinjam">
    <h3>Pinjam barang ini</h3>
    <label>Kondisi fisik saat diambil</label><select name="kondisi"><?= $opsi($b['kondisi'], 1) ?></select>
    <label>Tanggal pengembalian</label><input type="date" name="rencana" required min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
    <label>Catatan (mis. kabel lengkap, ada lecet)</label><textarea name="catatan" rows="2"></textarea><button>Pinjam</button>
    <p class="m">Ringkasan dan batas pengembalian dikirim ke WhatsApp Anda.</p>
  </form>
<?php elseif ($aktif && ($aktif['user_id'] == $u['id'] || $adm)): ?>
  <form method="post" class="c" id="kembali"><?= csrf() ?><input type="hidden" name="aksi" value="kembali">
    <h3>Kembalikan barang</h3>
    <p class="m">Dipinjam <?= e($aktif['nama']) ?>, wajib kembali <?= e($tgl($aktif['tgl_rencana'] ?: $aktif['tgl_ambil'])) ?>.</p>
    <label>Kondisi fisik saat dikembalikan</label><select name="kondisi"><?= $opsi($aktif['kondisi_ambil']) ?></select>
    <label>Catatan bagian yang rusak atau berubah</label><textarea name="catatan" rows="2"></textarea><button>Kembalikan</button>
  </form>
<?php elseif ($aktif): echo '<p class="c">Barang sedang dipinjam orang lain.</p>';
else: echo '<p class="c">Barang ini belum bisa dipinjam karena ' . ((int)$b['kondisi'] >= 3 ? 'rusak' : 'sedang dalam pemeliharaan') . '.</p>';
endif; ?>
<details class="c">
  <summary>Laporkan kerusakan atau kondisi fisik</summary>
  <form method="post"><?= csrf() ?><input type="hidden" name="aksi" value="lapor">
    <label>Kondisi sekarang</label><select name="kondisi"><?= $opsi($b['kondisi']) ?></select><label>Apa yang perlu ditindaklanjuti?</label><textarea name="catatan" rows="2" required></textarea><button>Kirim laporan</button>
  </form>
</details>
</div>
</div>
<?php if ($adm):
  $ts = $pdo->prepare('SELECT * FROM tindak_lanjut WHERE barang_id=? ORDER BY created_at DESC');
  $ts->execute([$b['id']]);
  $rs = $pdo->prepare('SELECT r.*, u.nama FROM perawatan r JOIN users u ON u.id=r.admin_id WHERE barang_id=? ORDER BY tanggal DESC');
  $rs->execute([$b['id']]); ?>
  <h2>Tindak lanjut</h2>
  <div class="c"><?php $n = 0;
                  foreach ($ts as $t) {
                    $n++;
                    echo '<div>' . ($t['status'] === 'selesai' ? '<s>' . e($t['judul']) . '</s> <span class="k k0">selesai</span>' : e($t['judul']) . ' <span class="k k2">terbuka</span>') . ' <span class="m">' . e($t['created_at']) . '</span></div>';
                  }
                  if (!$n) echo '<span class="m">Belum ada.</span>'; ?></div>
  <h2>Riwayat pemakaian</h2>
  <div class="c">
    <table>
      <tr>
        <th>Peminjam</th>
        <th>Ambil</th>
        <th>Kondisi</th>
        <th>Catatan</th>
      </tr>
      <?php foreach ($riwayat as $r): ?><tr>
          <td><?= e($r['nama']) ?></td>
          <td><?= e($r['tgl_ambil']) ?><br><span class="m">kembali: <?= e($r['tgl_kembali'] ?: 'belum') ?></span></td>
          <td><?= kb($r['kondisi_ambil']) ?> &rarr; <?= $r['tgl_kembali'] ? kb($r['kondisi_kembali']) : '-' ?></td>
          <td class="m"><?= e($r['catatan_ambil']) ?><br><?= e($r['catatan_kembali']) ?></td>
        </tr><?php endforeach; ?>
    </table>
  </div>
  <h2>Riwayat perawatan</h2>
  <div class="c"><?php $n = 0;
                  foreach ($rs as $r) {
                    $n++;
                    echo '<div>' . e($r['tanggal']) . ' - ' . e($r['tindakan']) . ' (oleh ' . e($r['nama']) . ', sesudah: ' . KONDISI[$r['kondisi_sesudah']] . ')</div>';
                  }
                  if (!$n) echo '<span class="m">Belum ada.</span>'; ?></div>
<?php endif;
foot();
