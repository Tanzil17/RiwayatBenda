# RiwayatBenda
### Setiap Barang Punya Cerita

**RiwayatBenda** adalah aplikasi web untuk mencatat riwayat pemakaian, perubahan kondisi, dan perawatan barang bersama menggunakan QR Code. Aplikasi ini membantu keluarga, organisasi, dan komunitas mengelola barang secara lebih teratur agar kerusakan dapat diketahui lebih awal dan barang dapat digunakan lebih lama.

🌐 **Website:** https://riwayatbenda.my.id/

## Latar Belakang

Barang yang digunakan secara bergantian sering kali tidak memiliki catatan pemakaian dan perawatan yang jelas. Informasi mengenai siapa yang terakhir menggunakan barang, kapan barang diperiksa, bagian mana yang rusak, serta siapa yang bertanggung jawab menindaklanjutinya sering kali hanya mengandalkan ingatan atau percakapan di grup.

Akibatnya, kerusakan kecil dapat terlewat hingga barang tidak dapat digunakan lagi. Kondisi tersebut berpotensi meningkatkan biaya penggantian barang dan menambah jumlah limbah.

RiwayatBenda hadir sebagai solusi digital untuk menghubungkan data peminjaman, kondisi barang, dan tindak lanjut perawatan dalam satu sistem yang dapat diakses melalui browser ponsel tanpa perlu memasang aplikasi khusus.

## Tujuan

- Mempermudah pencatatan peminjaman dan pengembalian barang.
- Menyediakan riwayat kondisi barang pada setiap periode pemakaian.
- Membantu pengurus mengetahui barang yang aman digunakan, sedang dipinjam, dalam pemeliharaan, atau rusak.
- Membuat tindak lanjut perawatan lebih jelas dan terpantau.
- Mendorong penggunaan barang dalam jangka waktu lebih lama untuk mengurangi limbah dan biaya penggantian.

## Fitur Utama

- **Profil Barang:** Menyimpan informasi, deskripsi, foto, dan status setiap barang.
- **QR Code per Barang:** Memudahkan pengguna membuka profil serta riwayat barang melalui pemindaian QR Code.
- **Peminjaman dan Pengembalian:** Mencatat periode pemakaian, kondisi awal, kondisi akhir, dan tanggal pengembalian.
- **Status Kondisi Barang:** Menampilkan status barang, seperti aman digunakan, sedang dipinjam, dalam pemeliharaan, atau rusak.
- **Riwayat Pemakaian:** Menghubungkan informasi peminjam dengan perubahan kondisi barang.
- **Tindak Lanjut Perawatan:** Membantu pengurus mencatat pekerjaan perawatan ketika ditemukan masalah pada barang.
- **Notifikasi WhatsApp:** Mengirimkan informasi terkait peminjaman dan batas waktu pengembalian melalui integrasi Fonnte.
- **Dashboard Admin:** Memudahkan admin memantau kondisi barang serta mengelola data dan perawatan.
- **Laporan Bulanan:** Menyediakan ringkasan pemakaian dan perawatan barang yang dapat diunduh dalam format CSV.

Ketersediaan setiap fitur mengikuti implementasi pada versi website yang sedang digunakan.

## Cara Kerja

RiwayatBenda menggunakan alur kerja yang terdiri dari empat langkah:

1. **Pindai QR Code** — Pengguna memindai QR Code pada barang untuk membuka profil dan riwayatnya.
2. **Pinjam** — Pengguna mencatat kondisi awal barang dan tanggal pengembalian.
3. **Kembalikan** — Pengguna melaporkan kondisi barang setelah digunakan. Jika ditemukan perubahan kondisi, informasi tersebut menjadi dasar tindak lanjut.
4. **Rawat** — Admin memeriksa barang, mencatat tindakan perawatan, dan memperbarui status tindak lanjut.

Ringkasan peminjaman dan informasi batas pengembalian juga dapat dikirimkan kepada peminjam melalui WhatsApp.

## Teknologi yang Digunakan

| Teknologi | Kegunaan |
|---|---|
| PHP | Pengembangan logika aplikasi web |
| MySQL | Penyimpanan data barang, pengguna, peminjaman, dan perawatan |
| QR Code | Identifikasi barang dan akses ke profil barang |
| Fonnte API | Integrasi notifikasi WhatsApp |
| HTML, CSS, dan JavaScript | Pengembangan antarmuka dan interaksi pengguna |
| Web Hosting | Menjalankan aplikasi agar dapat diakses melalui internet |

RiwayatBenda dirancang menggunakan teknologi web yang relatif sederhana sehingga dapat diterapkan tanpa memerlukan sensor IoT yang mahal.

## Pengguna Sistem

**1. User**

Pengguna dapat mengakses informasi barang, melakukan proses peminjaman dan pengembalian sesuai hak akses, serta mencatat kondisi barang yang digunakan.

**2. Admin**

Admin bertugas mengelola data barang, memantau aktivitas peminjaman, memeriksa kondisi barang, mencatat perawatan, menindaklanjuti masalah, dan melihat laporan.

## Dampak terhadap SDGs

### SDG 12 — Responsible Consumption and Production

RiwayatBenda mendukung Tujuan Pembangunan Berkelanjutan ke-12, khususnya target 12.5, melalui upaya pencegahan dan pengurangan limbah. Pencatatan kondisi serta perawatan membantu kerusakan kecil diketahui lebih awal sehingga barang berpotensi digunakan lebih lama dan tidak cepat diganti.

Dampak yang diharapkan meliputi:
- Memperpanjang masa penggunaan barang.
- Mengurangi barang yang dibuang akibat kerusakan yang terlambat ditangani.
- Menekan kebutuhan pembelian barang pengganti.
- Mendorong kebiasaan pemakaian dan perawatan barang secara bertanggung jawab.

### SDG 9 — Industry, Innovation and Infrastructure

RiwayatBenda juga mendukung inovasi digital yang sederhana dan terjangkau bagi komunitas melalui penggunaan aplikasi web, basis data, dan QR Code.

## Keunggulan

Dibandingkan pencatatan menggunakan buku atau percakapan grup, RiwayatBenda menghubungkan riwayat pemakaian dengan perubahan kondisi dan tindak lanjut perawatan. Dibandingkan spreadsheet inventaris biasa, sistem ini dirancang agar informasi peminjaman dan perawatan berada dalam satu alur yang saling terhubung.

Pendekatan ini berfokus pada **riwayat kondisi setiap barang**, bukan hanya daftar inventaris.

## Rencana Pengembangan

Pengembangan berikutnya direncanakan mencakup:
- Penambahan foto kondisi barang saat peminjaman dan pengembalian.
- Pemindaian QR Code langsung melalui aplikasi.
- Penyempurnaan pengingat terjadwal secara otomatis.
- Penambahan ekspor laporan dalam format PDF.
- Uji coba di organisasi kampus dan komunitas.
- Dukungan untuk banyak organisasi.
- Evaluasi dampak penggunaan sistem terhadap masa pakai barang.

## Menjalankan Proyek Secara Lokal

Untuk menjalankan aplikasi dari kode sumber:

1. Pasang lingkungan server lokal yang mendukung PHP dan MySQL, misalnya XAMPP.
2. Salin folder proyek ke direktori web server.
3. Buat basis data MySQL melalui phpMyAdmin.
4. Impor struktur dan data awal dari berkas `database.sql` jika tersedia.
5. Sesuaikan konfigurasi koneksi basis data pada berkas konfigurasi aplikasi.
6. Atur konfigurasi integrasi WhatsApp jika fitur notifikasi akan digunakan.
7. Jalankan Apache dan MySQL, kemudian buka aplikasi melalui browser lokal.

Nama berkas, struktur folder, dan konfigurasi dapat disesuaikan dengan versi kode sumber yang disertakan dalam repositori.

## Keamanan

- Gunakan hak akses sesuai peran pengguna.
- Validasi data yang diterima dari pengguna.
- Terapkan perlindungan CSRF pada formulir yang sesuai.
- Validasi berkas foto sebelum diunggah.
- Simpan token API dan kredensial basis data di konfigurasi privat, bukan di repositori publik.

## Kontribusi dan Pengembangan

RiwayatBenda dapat dikembangkan lebih lanjut untuk mendukung pengelolaan barang bersama di lingkungan kampus, organisasi, keluarga, maupun komunitas. Pengembangan diarahkan pada kemudahan penggunaan, keterlacakan kondisi barang, dan perawatan yang lebih teratur.

## Informasi Proyek

- **Nama:** RiwayatBenda
- **Slogan:** Setiap Barang Punya Cerita
- **Kategori:** Aplikasi web pengelolaan barang dan riwayat perawatan
- **Fokus SDGs:** SDG 12 — Konsumsi dan Produksi yang Bertanggung Jawab
- **SDGs Pendukung:** SDG 9 — Industri, Inovasi, dan Infrastruktur
- **Website:** https://riwayatbenda.my.id/

---

**RiwayatBenda — Barang terawat, riwayat jelas, bumi lebih ringan.**
