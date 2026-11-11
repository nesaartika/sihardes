# SIHARDES (MySQL)
Aplikasi Fenomena Perubahan Harga Perdesaan - BPS Provinsi Kalimantan Barat
CodeIgniter 3.1.13 + MySQL/MariaDB.

Petugas provinsi mengunggah file Excel yang berisi tabel harga (contoh: file Hitung RH bulanan). Aplikasi mengolah
tabel harga tersebut menjadi RH per komoditas, menentukan arah perubahan harga provinsi dari RH itu, lalu menyusun
rekap konfirmasi (kabupaten dan kecamatan yang perlu dijelaskan). Setelah itu tiap kabupaten/kota mengisi alasan
perubahan, dan hasilnya diunduh sebagai Excel.

## Menjalankan (XAMPP)
1. Salin folder `sihardes` ke `C:\xampp\htdocs\`.
2. Di XAMPP Control Panel nyalakan **Apache** dan **MySQL**.
3. Buka `http://localhost/sihardes/`

Saat pertama dibuka, aplikasi otomatis membuat database `sihardes` beserta tabelnya. Data awal (bila ada)
dimuat dari `database/data_awal.sql`. Database bisa dilihat di phpMyAdmin (`http://localhost/phpmyadmin`).

Pengaturan koneksi: `application/config/database.php` (bawaan XAMPP: user `root`, tanpa password).

## Login
Semua halaman wajib login. Akun awal dibuat otomatis saat pertama dijalankan:
- Pengguna: `admin`
- Kata sandi: `admin123` (wajib diganti pada login pertama)

Akun kabupaten dibuat admin lewat menu *Pengguna*. Akun kabupaten hanya dapat membuka Rekap, Data, RH, dan mengisi alasan.
Pengaturan domain email akun dan panjang minimal sandi ada di `application/config/sihardes.php`.

## Alur kerja
1. Admin provinsi login, lalu membuka menu *Unggah Data*.
2. Pilih file Excel, jenis data, dan periode (periode dibaca otomatis dari nama file bila tidak diisi).
3. Aplikasi membaca sheet Data, mengolahnya menjadi RH, lalu menyusun rekap konfirmasi dari RH tersebut.
4. Admin memilih komoditas yang dikonfirmasi dan membagi penugasan ke tiap kabupaten/kota.
5. Akun kabupaten mengisi alasan perubahan pada menu *Rekap*.
6. Admin mengunduh rekap (Excel) yang sudah berisi alasan.

## Bulan berikutnya
- **Web:** menu *Unggah Data* -> pilih file (.xlsx) -> pilih bulan & tahun -> Proses Data.
- **Terminal (VS Code):**
  ```
  php sihardes.php "D:\data\<nama_file>.xlsx"
  php sihardes.php "D:\data\<nama_file>.xlsx" "Oktober 2026"
  ```
  (contoh nama file: `2610_Hitung_RH_Oktober_2026_Kalbar.xlsx`)

  Periode dibaca dari nama file bila tidak ditulis. Hasil masuk database yang sama dan tampil di dashboard.
  Jika php tidak dikenali di terminal, gunakan `C:\xampp\php\php.exe sihardes.php "..."`.

## Format file Excel
Aplikasi tidak terikat pada satu file tertentu. File apa pun dapat diproses selama memuat tabel harga dengan judul
kolom berikut di 10 baris pertama sebuah sheet (urutan kolom dan nama sheet bebas, sheet bernama `Data` dicek lebih dulu):

| Kolom | Wajib |
|---|---|
| Kabupaten (angka 61xx atau nama, mis. "Kab. Sambas") | Ya |
| Kode Komoditas | Ya |
| Nama Komoditas | Ya |
| Harga Bulan Ini | Ya |
| Harga Bulan Sebelumnya | Ya |
| Nama Kecamatan | Tidak |
| Nama Kualitas | Tidak |
| Satuan | Tidak |

Periode (bulan dan tahun) dibaca dari nama file, mis. `September 2026`, `September_26`, `Sep-2026`, awalan `2609`
(YYMM), `2026-09`, atau `09_2026`. Bila tidak terbaca, periode diisi manual di halaman *Unggah Data*.

## Cara hitung
Rekap konfirmasi tidak diambil langsung dari RH. Data diolah lebih dulu menjadi RH, dan setelah RH terbentuk
barulah rekap konfirmasi disusun. Urutan ini sama dengan file Excel sumbernya: sheet **Data** diolah menjadi
sheet **RH** (rumus `GEOMEAN` dari Data per komoditas, lalu `RHt = harga ini / harga lalu x 100 - 100`),
kemudian RH dipakai untuk rekap konfirmasi. Aplikasi mengerjakan urutan yang sama secara otomatis
(`Sihardes_harga::hitung()` di application/libraries/Sihardes_harga.php).

Sumber data adalah tabel harga di dalam file Excel yang diunggah (satu baris = satu kabupaten, kecamatan,
komoditas, dan kualitas, dengan harga bulan lalu dan harga bulan ini).

### Tahap 0 - Penyaringan baris
Baris yang harga bulan ini atau harga bulan lalunya kosong atau <= 0 tidak ikut dihitung. Jumlahnya ditampilkan
sebagai `tidak_valid` pada ringkasan.

### Tahap 1 - Data menjadi RH (tingkat provinsi)
Untuk setiap komoditas, seluruh baris valid dari semua kabupaten, kecamatan, dan kualitas dikumpulkan, lalu:

```
G bulan ini  = rata-rata geometrik semua harga bulan ini
G bulan lalu = rata-rata geometrik semua harga bulan lalu
RHt          = (G bulan ini / G bulan lalu - 1) x 100
```

RHt inilah perubahan harga komoditas di tingkat provinsi.

### Tahap 2 - RH menentukan arah perubahan provinsi
```
|RHt| < ambang_stabil  -> stabil   (bawaan 0,005)
RHt > 0                -> naik
RHt < 0                -> turun
```

### Tahap 3 - Arah provinsi dan data kecamatan menjadi rekap konfirmasi
Hanya terjadi **setelah RH jadi**. Untuk setiap kecamatan pada komoditas itu dihitung perubahan harganya sendiri
(rata-rata geometrik dari seluruh kualitas di kecamatan tersebut):

```
perubahan kecamatan = (rata-rata geometrik harga ini / rata-rata geometrik harga lalu - 1) x 100
```

Kecamatan **masuk daftar konfirmasi** bila memenuhi ketiganya:
1. arah provinsi **bukan** stabil,
2. perubahan kecamatan **searah** provinsi (provinsi naik dan kecamatan naik, atau provinsi turun dan kecamatan turun),
3. besar perubahan kecamatan >= `ambang_kec` (bawaan 5%).

Kecamatan yang perubahannya >= `ambang_outlier` (bawaan 50%) diberi tanda "periksa kemungkinan salah entri".
Kecamatan yang lolos dikelompokkan per kabupaten dan diurutkan dari perubahan terbesar. Kabupaten yang punya
kecamatan lolos itulah yang diminta mengisi alasan.

### Contoh
Satu komoditas, empat baris data (satu kecamatan satu baris):

| Kabupaten | Kecamatan | Harga bulan lalu | Harga bulan ini | Perubahan |
|---|---|---|---|---|
| A | Kec 1 | 10.000 | 10.600 | +6,0% |
| A | Kec 2 | 10.000 | 10.200 | +2,0% |
| B | Kec 3 | 12.000 | 12.900 | +7,5% |
| B | Kec 4 | 11.000 | 11.000 | 0,0% |

- Tahap 1: G bulan ini = 11.129,41 dan G bulan lalu = 10.718,73, sehingga **RHt = +3,83%**.
- Tahap 2: RHt > 0, jadi arah provinsi **naik**.
- Tahap 3: Kec 1 (+6,0%) dan Kec 3 (+7,5%) searah naik dan >= 5%, jadi masuk daftar konfirmasi.
  Kec 2 (+2,0%) kurang dari 5% dan Kec 4 (0%) tidak searah, jadi tidak masuk.
  Maka **Kabupaten A (Kec 1)** dan **Kabupaten B (Kec 3)** yang diminta mengisi alasan.

### Ambang bawaan
Dapat diubah di `Sihardes_harga::$opsi_default`.

| Opsi | Bawaan | Arti |
|---|---|---|
| `ambang_kec` | `5.0` | % perubahan minimum kecamatan agar masuk daftar konfirmasi |
| `ambang_outlier` | `50.0` | % perubahan yang ditandai "periksa kemungkinan salah entri" |
| `ambang_stabil` | `0.005` | nilai mutlak RHt di bawah ini dianggap stabil (%) |
| `tampil_stabil` | `false` | tampilkan juga komoditas yang stabil |

### Catatan
- Semua komoditas yang ada di file tetap tampil di rekap dan Excel. Yang disebut "perlu konfirmasi" hanya komoditas
  yang punya kecamatan lolos. Komoditas yang arahnya stabil tidak punya daftar konfirmasi, kecuali opsi
  `tampil_stabil` diaktifkan.
- Komoditas yang seluruh harganya kosong atau nol tetap tampil dengan keterangan "tanpa data".
- Bila file memuat sheet RH, sheet itu **tidak dipakai sebagai sumber hitungan**. Sheet itu hanya dipakai untuk
  daftar komoditas dan sebagai pembanding hasil hitungan aplikasi (menu *RH*). Komoditas yang ada di Data tetapi
  belum terdaftar di sheet RH tetap dihitung dari Data.
- HD = kode komoditas 5 karakter (IA001, XA008, ...); HKD = kode 9 digit. Di rekap, HD ditampilkan lebih dulu
  lalu HKD, masing-masing urut kode.
- Jika file tidak memiliki tabel harga yang dikenali, aplikasi menampilkan pesan kolom yang tidak ditemukan.

## Menu
| Menu | Fungsi |
|---|---|
| Dashboard | Ringkasan periode: komoditas naik/turun, jumlah konfirmasi, jumlah terisi, progres kabupaten |
| Unggah Data | Unggah file Excel, pilih jenis data dan periode |
| Komoditas | Daftar komoditas, pilih komoditas yang dikonfirmasi, penugasan kabupaten |
| Rekap | Tabel rekap konfirmasi: arah provinsi, kabupaten, dan kolom alasan |
| Data | Isi tabel harga apa adanya, bisa dicari dan difilter |
| RH | Hasil RH per komoditas, dibandingkan dengan sheet RH di file |
| Riwayat | Daftar periode yang pernah diunggah |
| Aktivitas | Catatan login dan aksi penting (admin) |
| Pengguna | Kelola akun admin dan kabupaten (admin) |

Unduhan: rekap (.xlsx), RH (.xlsx), data (.xlsx), dan file asli yang diunggah.

## Tabel database
| Tabel | Isi |
|---|---|
| `kabupaten` | kode dan nama kabupaten/kota |
| `periode` | satu baris per bulan-tahun, beserta ringkasan (naik, turun, stabil, perlu konfirmasi) |
| `komoditas` | komoditas per periode: dokumen (HD/HKD), perubahan provinsi, arah |
| `kecamatan` | perubahan per kecamatan untuk tiap komoditas, penanda perlu konfirmasi |
| `harga` | rincian harga bulan lalu dan bulan ini per kualitas |
| `alasan` | alasan yang diisi petugas per periode, komoditas, dan kabupaten |
| `data_excel` | isi tabel harga apa adanya (menu Data) |
| `sheet_excel` | isi sheet lain dari file Excel |
| `satuan_komoditas` | satuan tiap komoditas per periode |
| `penugasan` | pembagian komoditas yang dikonfirmasi ke tiap kabupaten |
| `pengguna` | akun admin dan kabupaten |

Struktur lengkap: `database/struktur.sql` (dapat diimpor di phpMyAdmin).

## Struktur kode
```
application/libraries/Sihardes_harga.php hitung RH dan rekap konfirmasi
application/libraries/Sheet_rekap.php    rekap dari sheet non-harga
application/libraries/Xlsx_io.php        baca & tulis .xlsx (mandiri)
application/controllers/Sihardes.php     halaman web + perintah terminal
application/controllers/Auth.php         login, logout, ganti sandi
application/models/Periode_model.php     akses database MySQL
application/models/Pengguna_model.php    akun dan peran
application/views/                       dashboard, rekap, komoditas, detail, unggah, riwayat, pengguna
database/                                struktur.sql, data_awal.sql
sihardes.php                             perintah terminal
```

## Catatan wilayah
Daftar 14 kabupaten/kota Kalimantan Barat dan kodenya (6101-6112, 6171, 6172) ada di dalam kode
(`Sihardes_harga::$kabupaten`). Wilayah di luar 14 itu yang muncul pada file berformat lain diberi nomor 100 ke atas
sebagai wilayah tambahan.

## GitHub (versi kode)
Folder ini sudah siap dijadikan repositori. File data Excel (`*.xlsx`), hasil olahan, dan log tidak ikut terkirim
(lihat `.gitignore`), jadi data BPS tidak bocor ke GitHub.

**Pertama kali** (buat dulu repositori kosong di github.com, lalu di terminal VS Code pada folder proyek):
```
git init
git add .
git commit -m "Versi awal SIHARDES"
git branch -M main
git remote add origin https://github.com/USERNAME/sihardes.git
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
git clone https://github.com/USERNAME/sihardes.git         (sekali saja)
git pull                                                   (setiap ada revisi)
```
Setelah `git pull`, nyalakan Apache + MySQL lalu buka `http://localhost/sihardes/`,
atau jalankan `php sihardes.php "file.xlsx"` untuk memproses file bulan baru.