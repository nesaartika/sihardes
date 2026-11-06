-- CERMAT Harga - migrasi revisi (hasil konsultasi pembimbing lapangan)
-- OPSIONAL: aplikasi menjalankan perubahan ini otomatis saat pertama dibuka. Pakai file ini hanya bila ingin menerapkannya
-- manual lewat phpMyAdmin (tab SQL) pada database yang sudah ada. Aman dijalankan berulang (MariaDB / XAMPP).
-- Bila memakai MySQL biasa (bukan MariaDB), hapus kata "IF NOT EXISTS" pada baris ALTER TABLE.

-- Alasan: mencatat siapa yang mengisi
ALTER TABLE `alasan` ADD COLUMN IF NOT EXISTS `oleh` VARCHAR(120) NULL AFTER `teks`;

-- Periode: file Excel asli (untuk unduhan yang sama persis dengan yang diunggah) dan penanda penugasan
ALTER TABLE `periode` ADD COLUMN IF NOT EXISTS `berkas_asli` VARCHAR(255) NULL;
ALTER TABLE `periode` ADD COLUMN IF NOT EXISTS `penugasan_siap` TINYINT(1) NOT NULL DEFAULT 0;

-- Penugasan: komoditas mana yang harus diisi alasannya oleh kabupaten mana (diatur admin provinsi)
CREATE TABLE IF NOT EXISTS `penugasan` (
  `periode_id` VARCHAR(15) NOT NULL,
  `kode` VARCHAR(12) NOT NULL,
  `kab` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`periode_id`,`kode`,`kab`),
  KEY `idx_kab` (`periode_id`,`kab`),
  CONSTRAINT `fk_penugasan_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Akun kabupaten tidak diminta ganti sandi (sandinya ditetapkan admin provinsi)
UPDATE `pengguna` SET `wajib_ganti` = 0 WHERE `role` = 'kabupaten';
