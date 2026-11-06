-- CERMAT Harga - struktur database MySQL/MariaDB
-- Dapat diimpor lewat phpMyAdmin (tab Import). Aplikasi juga membuat struktur ini otomatis saat pertama dibuka.
CREATE DATABASE IF NOT EXISTS `cermat_harga` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cermat_harga`;

CREATE TABLE IF NOT EXISTS `kabupaten` (
  `kode` TINYINT UNSIGNED NOT NULL,
  `nama` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `periode` (
  `id` VARCHAR(15) NOT NULL,
  `nama` VARCHAR(40) NOT NULL,
  `bulan` TINYINT UNSIGNED NOT NULL,
  `tahun` SMALLINT UNSIGNED NOT NULL,
  `file_asal` VARCHAR(255) DEFAULT NULL,
  `dibuat` DATETIME NOT NULL,
  `ambang_kec` DECIMAL(6,2) NOT NULL DEFAULT 5.00,
  `jml_baris` INT NOT NULL DEFAULT 0,
  `jml_komoditas` INT NOT NULL DEFAULT 0,
  `naik` INT NOT NULL DEFAULT 0,
  `turun` INT NOT NULL DEFAULT 0,
  `stabil` INT NOT NULL DEFAULT 0,
  `perlu_konfirmasi` INT NOT NULL DEFAULT 0,
  `tidak_valid` INT NOT NULL DEFAULT 0,
  `berkas_asli` VARCHAR(255) DEFAULT NULL,
  `penugasan_siap` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_bulan_tahun` (`tahun`,`bulan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `komoditas` (
  `id` INT UNSIGNED NOT NULL,
  `periode_id` VARCHAR(15) NOT NULL,
  `urut` SMALLINT UNSIGNED NOT NULL,
  `kode` VARCHAR(12) NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `dokumen` ENUM('HD','HKD') NOT NULL,
  `prov_pct` DECIMAL(12,4) NOT NULL,
  `arah` ENUM('naik','turun','stabil') NOT NULL,
  `jumlah` INT NOT NULL,
  `tampil` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_periode_kode` (`periode_id`,`kode`),
  CONSTRAINT `fk_komoditas_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kecamatan` (
  `id` INT UNSIGNED NOT NULL,
  `komoditas_id` INT UNSIGNED NOT NULL,
  `kab` TINYINT UNSIGNED NOT NULL,
  `kecamatan` VARCHAR(80) NOT NULL,
  `persen` DECIMAL(12,4) NOT NULL,
  `n` INT NOT NULL,
  `flag` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_komoditas` (`komoditas_id`),
  CONSTRAINT `fk_kecamatan_komoditas` FOREIGN KEY (`komoditas_id`) REFERENCES `komoditas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `harga` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kecamatan_id` INT UNSIGNED NOT NULL,
  `kualitas` VARCHAR(120) NOT NULL,
  `harga_lalu` DECIMAL(15,2) NOT NULL,
  `harga_ini` DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kecamatan` (`kecamatan_id`),
  CONSTRAINT `fk_harga_kecamatan` FOREIGN KEY (`kecamatan_id`) REFERENCES `kecamatan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `alasan` (
  `periode_id` VARCHAR(15) NOT NULL,
  `kode` VARCHAR(12) NOT NULL,
  `kab` TINYINT UNSIGNED NOT NULL,
  `teks` TEXT NOT NULL,
  `oleh` VARCHAR(120) DEFAULT NULL,
  `diubah` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`periode_id`,`kode`,`kab`),
  CONSTRAINT `fk_alasan_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `penugasan` (
  `periode_id` VARCHAR(15) NOT NULL,
  `kode` VARCHAR(12) NOT NULL,
  `kab` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`periode_id`,`kode`,`kab`),
  KEY `idx_kab` (`periode_id`,`kab`),
  CONSTRAINT `fk_penugasan_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `kabupaten` (`kode`,`nama`) VALUES
 (1,'Sambas'),(2,'Bengkayang'),(3,'Landak'),(4,'Mempawah'),(5,'Sanggau'),(6,'Ketapang'),(7,'Sintang'),
 (8,'Kapuas Hulu'),(9,'Sekadau'),(10,'Melawi'),(11,'Kayong Utara'),(12,'Kubu Raya'),(71,'Kota Pontianak'),(72,'Kota Singkawang');
