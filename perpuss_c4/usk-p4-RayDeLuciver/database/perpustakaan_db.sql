-- Database Schema untuk Sistem Perpustakaan C4
-- (Disesuaikan agar kompatibel dengan MySQL/MariaDB di XAMPP)

CREATE DATABASE IF NOT EXISTS perpustakaan_db;
USE perpustakaan_db;

CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Siswa') NOT NULL DEFAULT 'Siswa',
    nama_lengkap VARCHAR(100),
    alamat TEXT,
    nomor_telepon VARCHAR(20),
    email VARCHAR(100),
    tanggal_daftar DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_aktif ENUM('Aktif', 'Tidak Aktif') DEFAULT 'Aktif',
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buku (
    id_buku INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    penulis VARCHAR(100),
    pengarang VARCHAR(100),
    penerbit VARCHAR(100),
    tahun_terbit YEAR,
    isbn VARCHAR(20) UNIQUE,
    jumlah_stok INT DEFAULT 0,
    stok_tersedia INT DEFAULT 0,
    kategori VARCHAR(50),
    deskripsi TEXT,
    tanggal_tambah DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_judul (judul),
    INDEX idx_penulis (penulis),
    INDEX idx_stok (stok_tersedia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS peminjaman (
    id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna INT NOT NULL,
    id_buku INT NOT NULL,
    tanggal_peminjaman DATETIME DEFAULT CURRENT_TIMESTAMP,
    tanggal_jatuh_tempo DATE NOT NULL,
    tanggal_pengembalian_aktual DATETIME NULL,
    status_peminjaman ENUM('Dipinjam', 'Dikembalikan', 'Terlambat', 'Hilang') DEFAULT 'Dipinjam',
    denda DECIMAL(10, 2) DEFAULT 0.00,
    keterangan TEXT,
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_pengguna (id_pengguna),
    INDEX idx_buku (id_buku),
    INDEX idx_status (status_peminjaman),
    INDEX idx_tanggal_peminjaman (tanggal_peminjaman)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NULL,
    id_pengguna INT NOT NULL,
    id_buku INT NOT NULL,
    jenis_transaksi ENUM('Peminjaman', 'Pengembalian', 'Perpanjangan', 'Denda') NOT NULL,
    tanggal_transaksi DATETIME DEFAULT CURRENT_TIMESTAMP,
    jumlah DECIMAL(10, 2) DEFAULT 0.00,
    keterangan TEXT,
    dibuat_oleh INT NULL COMMENT 'ID Admin yang melakukan transaksi',
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id_pengguna) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_pengguna (id_pengguna),
    INDEX idx_buku (id_buku),
    INDEX idx_jenis (jenis_transaksi),
    INDEX idx_tanggal (tanggal_transaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data
INSERT INTO pengguna (username, password, role, nama_lengkap) VALUES
('admin', MD5('admin123'), 'Admin', 'Administrator Sistem'),
('admin2', MD5('admin123'), 'Admin', 'Admin Kedua')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO pengguna (username, password, role, nama_lengkap, alamat, nomor_telepon, email) VALUES
('siswa001', MD5('siswa123'), 'Siswa', 'Ahmad Fauzi', 'Jl. Merdeka No. 123', '081234567890', 'ahmad@email.com'),
('siswa002', MD5('siswa123'), 'Siswa', 'Siti Nurhaliza', 'Jl. Sudirman No. 456', '081234567891', 'siti@email.com'),
('siswa003', MD5('siswa123'), 'Siswa', 'Budi Santoso', 'Jl. Gatot Subroto No. 789', '081234567892', 'budi@email.com')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO buku (judul, penulis, pengarang, penerbit, tahun_terbit, isbn, jumlah_stok, stok_tersedia, kategori) VALUES
('Pemrograman PHP untuk Pemula', 'John Doe', 'John Doe', 'Penerbit Informatika', 2023, '978-1234567890', 5, 5, 'Teknologi'),
('Database MySQL Lengkap', 'Jane Smith', 'Jane Smith', 'Penerbit Komputer', 2022, '978-1234567891', 3, 3, 'Teknologi'),
('Algoritma dan Struktur Data', 'Ahmad Rizki', 'Ahmad Rizki', 'Penerbit Ilmu', 2024, '978-1234567892', 4, 4, 'Teknologi'),
('Sejarah Indonesia Modern', 'Prof. Soekarno', 'Prof. Soekarno', 'Penerbit Sejarah', 2021, '978-1234567893', 2, 2, 'Sejarah'),
('Matematika Dasar', 'Dr. Budi', 'Dr. Budi', 'Penerbit Pendidikan', 2023, '978-1234567894', 6, 6, 'Pendidikan')
ON DUPLICATE KEY UPDATE isbn = isbn;

-- Views
CREATE OR REPLACE VIEW v_peminjaman_aktif AS
SELECT 
    p.id_peminjaman,
    pg.nama_lengkap AS nama_peminjam,
    pg.username,
    b.judul AS judul_buku,
    b.penulis,
    p.tanggal_peminjaman,
    p.tanggal_jatuh_tempo,
    p.status_peminjaman,
    p.denda,
    DATEDIFF(CURDATE(), p.tanggal_jatuh_tempo) AS hari_terlambat
FROM peminjaman p
INNER JOIN pengguna pg ON p.id_pengguna = pg.id_pengguna
INNER JOIN buku b ON p.id_buku = b.id_buku
WHERE p.status_peminjaman = 'Dipinjam';

CREATE OR REPLACE VIEW v_buku_tersedia AS
SELECT 
    id_buku,
    judul,
    penulis,
    pengarang,
    penerbit,
    tahun_terbit,
    jumlah_stok,
    stok_tersedia,
    kategori
FROM buku
WHERE stok_tersedia > 0;

