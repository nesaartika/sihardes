# CERMAT Harga (MySQL)
Aplikasi Otomatisasi Pencermatan dan Rekapitulasi Perubahan Harga - BPS Provinsi Kalimantan Barat
CodeIgniter 3.1.13 + MySQL/MariaDB. Tidak perlu composer.

## Menjalankan (XAMPP)
1. Salin folder `cermat_harga` ke `C:\xampp\htdocs\`.
2. Di XAMPP Control Panel nyalakan **Apache** dan **MySQL**.
3. Buka `http://localhost/cermat_harga/`

Saat pertama dibuka, aplikasi otomatis membuat database `cermat_harga` beserta tabelnya, lalu memuat data
**September 2026** dari `database/data_awal.sql`. Database bisa dilihat di phpMyAdmin (`http://localhost/phpmyadmin`).

Pengaturan koneksi: `application/config/database.php` (bawaan XAMPP: user `root`, tanpa password).

## Bulan berikutnya
- **Web:** menu *Unggah Data* -> pilih file Hitung RH (.xlsx) -> pilih bulan & tahun -> Proses Data.
- **Terminal (VS Code):**
  ```
  php cermat.php "D:\data\2610_Hitung_RH_Oktober_2026_Kalbar.xlsx"
  php cermat.php "D:\data\file.xlsx" "Oktober 2026"
  ```
  Periode dibaca dari nama file bila tidak ditulis. Hasil masuk database yang sama dan tampil di dashboard.
  Jika php tidak dikenali di terminal, gunakan `C:\xampp\php\php.exe cermat.php "..."`.

## Tabel database
| Tabel | Isi |
|---|---|
| `kabupaten` | kode dan nama kabupaten/kota |
| `periode` | satu baris per bulan-tahun, beserta ringkasan (naik, turun, stabil, perlu konfirmasi) |
| `komoditas` | komoditas per periode: dokumen (HD/HKD), perubahan provinsi, arah |
| `kecamatan` | perubahan per kecamatan untuk tiap komoditas, penanda perlu konfirmasi |
| `harga` | rincian harga bulan lalu dan bulan ini per kualitas |
| `alasan` | alasan yang diisi petugas per periode, komoditas, dan kabupaten |

Struktur lengkap: `database/struktur.sql` (dapat diimpor di phpMyAdmin).

## Cara hitung
- Sumber: sheet **Data**. Perubahan provinsi per komoditas = rata-rata geometrik (harga ini / harga sebelumnya) - 1,
  identik dengan kolom RHt sheet RH.
- HD = kode 5 karakter (IA001, XA008, ...); HKD = kode 9 digit.
- Kecamatan masuk daftar konfirmasi bila searah provinsi dan berubah >= 5%
  (`Cermat_harga::$opsi_default['ambang_kec']` di application/libraries/Cermat_harga.php).

## Struktur kode
```
application/libraries/Cermat_harga.php   hitung perubahan harga
application/libraries/Xlsx_io.php        baca & tulis .xlsx (mandiri)
application/controllers/Cermat.php       halaman web + perintah terminal
application/models/Periode_model.php     akses database MySQL
application/views/                       dashboard, rekap, komoditas, detail, unggah, riwayat
database/                                struktur.sql, data_awal.sql
cermat.php                               perintah terminal
```

## GitHub (versi kode)
Folder ini sudah siap dijadikan repositori. File data Excel (`*.xlsx`), hasil olahan, dan log tidak ikut terkirim
(lihat `.gitignore`), jadi data BPS tidak bocor ke GitHub.

**Pertama kali** (buat dulu repositori kosong di github.com, lalu di terminal VS Code pada folder proyek):
```
git init
git add .
git commit -m "Versi awal CERMAT Harga"
git branch -M main
git remote add origin https://github.com/USERNAME/cermat_harga.git
git push -u origin main
```

**Setiap ada revisi:**
```
git add .
git commit -m "jelaskan perubahan"
git push
```
atau cukup `push.bat "jelaskan perubahan"` di Windows.

**Di komputer lain / mengambil revisi terbaru:**
```
git clone https://github.com/USERNAME/cermat_harga.git     (sekali saja)
git pull                                                   (setiap ada revisi)
```
Setelah `git pull`, nyalakan Apache + MySQL lalu buka `http://localhost/cermat_harga/`,
atau jalankan `php cermat.php "file.xlsx"` untuk memproses file bulan baru.
