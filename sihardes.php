<?php
/**
 * CLI SIHARDES Harga (tanpa browser)
 *
 *   php sihardes.php "D:\\data\\2610_Hitung_RH_Oktober_2026_Kalbar.xlsx" ["Oktober 2026"]
 *
 * Periode dibaca dari nama file bila tidak ditulis. Hasil disimpan ke database MySQL
 * (sama dengan unggahan lewat web) dan langsung tampil di dashboard.
 */
if (PHP_SAPI !== 'cli') { exit('Jalankan lewat terminal.'); }

$berkas = isset($argv[1]) ? $argv[1] : '';
if ($berkas === '' || ! is_file($berkas))
{
    echo ($berkas === '' ? '' : "File tidak ditemukan: {$berkas}\n\n");
    echo "Pemakaian:\n  php sihardes.php \"<file.xlsx>\" [\"Bulan Tahun\"]\n";
    exit(1);
}

// Argumen dititipkan lewat variabel global karena CodeIgniter memecah path (/, \\, spasi) pada URI
$GLOBALS['sihardes_cli'] = array('berkas' => $berkas, 'nama' => isset($argv[2]) ? trim($argv[2]) : '');
$_SERVER['argv'] = array(__FILE__, 'sihardes', 'jalankan');
$_SERVER['argc'] = 3;
chdir(__DIR__);
require __DIR__ . '/index.php';
