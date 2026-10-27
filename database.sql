CREATE DATABASE IF NOT EXISTS riwayatbenda CHARACTER SET utf8mb4;
USE riwayatbenda;
CREATE TABLE users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    no_wa VARCHAR(20) NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE barang(
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(120) NOT NULL,
    kategori VARCHAR(60),
    lokasi VARCHAR(80),
    deskripsi TEXT,
    foto VARCHAR(80) NULL,
    interval_hari INT NOT NULL DEFAULT 30,
    kondisi TINYINT NOT NULL DEFAULT 0,
    status ENUM('tersedia', 'dipinjam') NOT NULL DEFAULT 'tersedia'
);
CREATE TABLE peminjaman(
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT NOT NULL,
    user_id INT NOT NULL,
    tgl_ambil DATETIME NOT NULL,
    tgl_rencana DATE NULL,
    kondisi_ambil TINYINT NOT NULL,
    catatan_ambil TEXT,
    tgl_kembali DATETIME NULL,
    kondisi_kembali TINYINT NULL,
    catatan_kembali TEXT,
    FOREIGN KEY(barang_id) REFERENCES barang(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE perawatan(
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT NOT NULL,
    admin_id INT NOT NULL,
    tanggal DATETIME NOT NULL,
    tindakan TEXT NOT NULL,
    kondisi_sesudah TINYINT NOT NULL,
    FOREIGN KEY(barang_id) REFERENCES barang(id),
    FOREIGN KEY(admin_id) REFERENCES users(id)
);
CREATE TABLE tindak_lanjut(
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT NOT NULL,
    peminjaman_id INT NULL,
    judul VARCHAR(200) NOT NULL,
    status ENUM('terbuka', 'selesai') NOT NULL DEFAULT 'terbuka',
    created_at DATETIME NOT NULL,
    selesai_at DATETIME NULL,
    FOREIGN KEY(barang_id) REFERENCES barang(id),
    FOREIGN KEY(peminjaman_id) REFERENCES peminjaman(id)
);
-- Master admin (kata sandi awal: admin123, segera ganti)
INSERT INTO users(nama, email, no_wa, password, role)
VALUES (
        'Master Admin',
        'admin@riwayatbenda.local',
        '',
        '$2y$10$bhFEnAI5ZbYswiLw4KlehutlXo5nhNcRLN.15so0jc4g0xhvyiDEm',
        'admin'
    );
INSERT INTO barang(
        kode,
        nama,
        kategori,
        lokasi,
        deskripsi,
        interval_hari
    )
VALUES (
        'BRG-PROYEKTOR',
        'Proyektor Epson EB-X06',
        'Elektronik',
        'Sekretariat BEM',
        'Proyektor 3600 lumen, lengkap dengan kabel HDMI dan tas.',
        30
    ),
    (
        'BRG-SOUND',
        'Sound system portabel',
        'Elektronik',
        'Ruang UKM',
        'Speaker aktif dengan 2 mikrofon dan kabel XLR.',
        21
    ),
    (
        'BRG-TENDA',
        'Tenda lipat 3x3 m',
        'Perlengkapan acara',
        'Gudang kampus',
        'Tenda lipat rangka besi, muat 8 orang.',
        60
    );