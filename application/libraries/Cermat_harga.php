<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cermat_harga
 * ------------------------------------------------------------------
 * Pengumpulan fenomena perubahan harga perdesaan
 * BPS Provinsi Kalimantan Barat.
 *
 * Alur:
 *   1. baca_xlsx()   : membaca sheet "Data" dari file "Hitung RH ... Kalbar.xlsx"
 *   2. hitung()      : menentukan arah perubahan provinsi (rata-rata geometrik)
 *                      dan kecamatan/kabupaten yang perlu dikonfirmasi
 *   3. tulis_xlsx()  : menulis rekap dengan format seperti spreadsheet
 *                      "Perubahan Sementara Harga Perdesaan"
 */
class Cermat_harga
{
    /** Kode kabupaten/kota BPS Provinsi Kalimantan Barat (61xx) */
    public static $kabupaten = array(
        1  => 'Sambas',
        2  => 'Bengkayang',
        3  => 'Landak',
        4  => 'Mempawah',
        5  => 'Sanggau',
        6  => 'Ketapang',
        7  => 'Sintang',
        8  => 'Kapuas Hulu',
        9  => 'Sekadau',
        10 => 'Melawi',
        11 => 'Kayong Utara',
        12 => 'Kubu Raya',
        71 => 'Kota Pontianak',
        72 => 'Kota Singkawang',
    );

    /**
     * Wilayah di luar 14 kabupaten/kota Kalbar yang muncul pada file berformat lain (mis. provinsi / kabupaten daerah lain di sheet Rekonsiliasi).
     * Diberi nomor 100 ke atas agar bisa disimpan di kolom kab seperti kabupaten biasa. Diisi saat periode dibuka: [nomor => nama].
     */
    public static $kab_extra = array();

    /** Opsi bawaan */
    public static $opsi_default = array(
        'ambang_kec'     => 5.0,   // % perubahan minimum kecamatan agar masuk daftar konfirmasi
        'ambang_outlier' => 50.0,  // % perubahan yang ditandai "periksa kemungkinan salah entri"
        'ambang_stabil'  => 0.005, // |RHt| di bawah ini dianggap stabil (%)
        'tampil_stabil'  => false, // tampilkan juga komoditas yang stabil
        'sheet'          => 'Data',
    );

    // ------------------------------------------------------------------
    // 1. MEMBACA FILE EXCEL
    // ------------------------------------------------------------------

    /**
     * Membaca sheet "Data" dan mengembalikan array baris dengan kunci baku.
     *
     * @return array [ ['kab'=>int,'kec'=>string,'kode'=>string,'nama'=>string,
     *                  'kualitas'=>string,'ini'=>float,'lalu'=>float], ... ]
     */
    public function baca_xlsx($path, $sheet = 'Data')
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);
        require_once APPPATH . 'libraries/Xlsx_io.php';

        $rows = Xlsx_io::baca($path, $sheet);
        if (empty($rows)) throw new RuntimeException('Sheet "' . $sheet . '" kosong.');

        $h = $this->cari_header($rows);
        if ($h === null) $this->petakan_header($rows[0]);       // melempar pesan kolom yang tidak ditemukan
        list($idx, $peta) = $h;
        $rows = array_slice($rows, $idx + 1);
        $baris = array();
        foreach ($rows as $r)
        {
            if ( ! isset($r[$peta['kode']]) || $r[$peta['kode']] === '') continue;
            $kec = isset($peta['kec'], $r[$peta['kec']]) ? trim((string) $r[$peta['kec']]) : '';
            $baris[] = array(
                'kab'      => isset($r[$peta['kab']]) ? $this->norm_kab($r[$peta['kab']]) : 0,
                'kec'      => $kec !== '' ? $kec : '-',      // tanpa kolom kecamatan: dihitung per kabupaten
                'kode'     => $this->norm_kode($r[$peta['kode']]),
                'nama'     => isset($r[$peta['nama']]) ? trim((string) $r[$peta['nama']]) : '',
                'kualitas' => (isset($peta['kualitas']) && isset($r[$peta['kualitas']])) ? trim((string) $r[$peta['kualitas']]) : '',
                'ini'      => isset($r[$peta['ini']]) ? (float) $r[$peta['ini']] : 0.0,
                'lalu'     => isset($r[$peta['lalu']]) ? (float) $r[$peta['lalu']] : 0.0,
                'satuan'   => (isset($peta['satuan']) && isset($r[$peta['satuan']])) ? trim((string) $r[$peta['satuan']]) : '',
            );
        }
        return $baris;
    }

    /**
     * Daftar semua komoditas pada sheet RH (kolom Kode Baru / Kode Komoditas dan Nama Komoditas), termasuk yang
     * tidak punya baris harga di sheet Data. Dipakai agar komoditas tanpa data tetap muncul (dengan isian kosong).
     * @return array [kode => nama]; kosong bila sheet RH tidak ada / formatnya lain
     */
    public function baca_daftar_komoditas($path)
    {
        $out = array();
        try
        {
            require_once APPPATH . 'libraries/Xlsx_io.php';
            $rows = Xlsx_io::baca($path, 'RH');
            $ik = null; $in = null; $mulai = 0;
            foreach (array_slice($rows, 0, 10, true) as $i => $r)
            {
                $kb = $kl = $nm = null;
                foreach ($r as $c => $v)
                {
                    $t = strtolower(trim((string) $v));
                    if ($t === 'kode baru' && $kb === null) $kb = $c;
                    if ($t === 'kode komoditas' && $kl === null) $kl = $c;
                    if ($t === 'nama komoditas' && $nm === null) $nm = $c;
                }
                if ($nm !== null && ($kb !== null || $kl !== null)) { $ik = $kb !== null ? $kb : $kl; $in = $nm; $mulai = $i + 1; break; }
            }
            if ($ik === null) return $out;
            foreach (array_slice($rows, $mulai, null, true) as $r)
            {
                if ( ! isset($r[$ik]) || $r[$ik] === '' || ! isset($r[$in])) continue;
                $nama = trim((string) $r[$in]);
                if ($nama === '') continue;
                $kode = $this->norm_kode($r[$ik]);
                if ($kode !== '' && ! isset($out[$kode])) $out[$kode] = $nama;
            }
        }
        catch (Exception $e) { return array(); }
        catch (Error $e) { return array(); }
        return $out;
    }

    /**
     * Mencari sheet yang berisi tabel harga (judul kolom Kabupaten, Nama Kecamatan, Kode Komoditas, Nama Komoditas,
     * Nama Kualitas, Harga Bulan Ini, Harga Bulan Sebelumnya), apa pun nama sheet-nya. Sheet "Data" dicek lebih dulu.
     * @return string|null nama sheet, atau null bila file tidak berisi tabel harga
     */
    public function cari_sheet_harga($path)
    {
        @ini_set('memory_limit', '1024M');
        require_once APPPATH . 'libraries/Xlsx_io.php';
        $nama = Xlsx_io::daftar_sheet($path);
        usort($nama, function ($a, $b) { return (strcasecmp($b, 'Data') === 0) <=> (strcasecmp($a, 'Data') === 0); });
        foreach ($nama as $n)
        {
            try
            {
                $r = Xlsx_io::baca($path, $n, false, 10);
                if ($r && $this->cari_header($r) !== null) return $n;
            }
            catch (Throwable $e) { /* bukan tabel harga: coba sheet berikutnya */ }
        }
        return null;
    }

    /**
     * Membaca isi sheet apa adanya (semua kolom, urutan baris asli, nilai tanpa diubah).
     * @return array ['kolom' => [judul, ...], 'baris' => [ ['r' => nomorBarisExcel, 'v' => [nilai, ...]], ... ]]
     */
    public function baca_mentah($path, $sheet = 'Data', $maks_kolom = 15)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);
        require_once APPPATH . 'libraries/Xlsx_io.php';

        $rows = Xlsx_io::baca($path, $sheet, true);
        if (empty($rows)) throw new RuntimeException('Sheet "' . $sheet . '" kosong.');

        $awal = array();
        foreach (array_slice($rows, 0, 10) as $x) $awal[] = $x['v'];
        $h = $this->cari_header($awal);
        if ($h !== null && $h[0] > 0) $rows = array_slice($rows, $h[0]);     // buang baris judul di atas tabel
        $head = array_shift($rows);
        $n = min($maks_kolom, max(array_keys($head['v'])) + 1);
        $kolom = array();
        for ($i = 0; $i < $n; $i++) $kolom[] = isset($head['v'][$i]) ? (string) $head['v'][$i] : '';

        $baris = array();
        foreach ($rows as $r)
        {
            $v = array();
            for ($i = 0; $i < $n; $i++) $v[] = isset($r['v'][$i]) ? (string) $r['v'][$i] : null;
            $baris[] = array('r' => $r['r'], 'v' => $v);
        }
        return array('kolom' => $kolom, 'baris' => $baris);
    }

    /**
     * Membaca semua sheet selain $kecuali apa adanya (tanpa judul kolom; kolom diberi huruf seperti Excel).
     * @return array [ ['nama'=>, 'kolom'=>int, 'baris'=>[ ['r'=>no, 'v'=>[nilai|null,...]], ... ]], ... ]
     */
    public function baca_sheet_lain($path, $kecuali = 'Data', $maks_kolom = 120, $hanya = null)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);
        require_once APPPATH . 'libraries/Xlsx_io.php';

        $out = array();
        foreach (Xlsx_io::daftar_sheet($path) as $nama)
        {
            if (strcasecmp($nama, $kecuali) === 0) continue;
            if ($hanya !== null && ! in_array(strtolower($nama), array_map('strtolower', $hanya), true)) continue;
            $rows = Xlsx_io::baca($path, $nama, true);
            $n = 0;
            foreach ($rows as $r) $n = max($n, max(array_keys($r['v'])) + 1);
            $n = min($n, $maks_kolom);
            $baris = array();
            foreach ($rows as $r)
            {
                $v = array();
                for ($i = 0; $i < $n; $i++) $v[] = isset($r['v'][$i]) ? (string) $r['v'][$i] : null;
                while ($v && end($v) === null) array_pop($v);      // buang sel kosong di ujung baris
                $baris[] = array('r' => $r['r'], 'v' => $v);
            }
            $out[] = array('nama' => $nama, 'kolom' => $n, 'baris' => $baris);
        }
        return $out;
    }

    /** Nama bulan dan singkatannya (huruf kecil) yang dikenali pada nama file, per nomor bulan */
    public static $alias_bulan = array(
        1 => array('januari', 'january', 'jan'),            2 => array('februari', 'pebruari', 'february', 'feb'),
        3 => array('maret', 'march', 'mar'),                4 => array('april', 'apr'),
        5 => array('mei', 'may'),                           6 => array('juni', 'june', 'jun'),
        7 => array('juli', 'july', 'jul'),                  8 => array('agustus', 'august', 'agu', 'agt', 'aug'),
        9 => array('september', 'sept', 'sep'),             10 => array('oktober', 'october', 'okt', 'oct'),
        11 => array('november', 'nopember', 'nov'),         12 => array('desember', 'december', 'des', 'dec'),
    );

    public static $nama_bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');

    /**
     * Membaca bulan dan tahun dari nama file. Dikenali: "September 2026", "September_26", "Mar_2026_new", "Sep-2026",
     * awalan "2609" (YYMM), "2026-09" dan "09_2026". Urutan pembacaan sama dengan skrip di halaman Unggah Data.
     * @return array|null [nama bulan Indonesia, tahun] atau null bila tidak terbaca
     */
    public static function baca_periode($file)
    {
        $nm = pathinfo((string) $file, PATHINFO_FILENAME);
        $peta = array();
        foreach (self::$alias_bulan as $no => $daftar) foreach ($daftar as $a) $peta[$a] = $no;
        $alt = array_keys($peta);
        usort($alt, function ($x, $y) { return strlen($y) <=> strlen($x); });
        if (preg_match('/(?:^|[^A-Za-z])(' . implode('|', $alt) . ')(?![A-Za-z])[ _\-.]*(\d{4}|\d{2})(?!\d)/i', $nm, $m))
        {
            $th = (int) $m[2];
            if ($th < 100) $th += 2000;
            return array(self::$nama_bulan[$peta[strtolower($m[1])]], $th);
        }
        if (preg_match('/^(\d{2})(0[1-9]|1[0-2])(?!\d)/', $nm, $m)) return array(self::$nama_bulan[(int) $m[2]], 2000 + (int) $m[1]);
        if (preg_match('/(?<!\d)(20\d{2})[ _\-.](0[1-9]|1[0-2])(?!\d)/', $nm, $m)) return array(self::$nama_bulan[(int) $m[2]], (int) $m[1]);
        if (preg_match('/(?<!\d)(0[1-9]|1[0-2])[ _\-.](20\d{2})(?!\d)/', $nm, $m)) return array(self::$nama_bulan[(int) $m[1]], (int) $m[2]);
        return null;
    }

    /** Huruf kolom Excel: 0 => A, 25 => Z, 26 => AA */
    public static function huruf_kolom($i)
    {
        $s = '';
        for ($i = $i + 1; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
        return $s;
    }

    /** Nama kolom yang dikenali untuk tiap isian (urutan = prioritas). Kolom kec, kualitas, dan satuan bersifat opsional. */
    private static $alias = array(
        'kab'      => array('kabupaten', 'kab/kota', 'kabupaten/kota', 'nama kabupaten', 'kab', 'kota'),
        'kec'      => array('nama kecamatan', 'kecamatan'),
        'kode'     => array('kode komoditas', 'kode komoditi', 'kode barang', 'kode'),
        'nama'     => array('nama komoditas', 'nama komoditi', 'nama barang', 'komoditas', 'komoditi'),
        'kualitas' => array('nama kualitas', 'kualitas', 'nama spesifikasi', 'spesifikasi'),
        'ini'      => array('harga bulan ini', 'harga saat ini', 'harga sekarang', 'harga ini', 'harga'),
        'lalu'     => array('harga bulan sebelumnya', 'harga bulan lalu', 'harga sebelumnya', 'harga lalu'),
        'satuan'   => array('satuan'),
    );

    private function petakan_header($r)
    {
        $judul = array();
        foreach ($r as $i => $v) $judul[strtolower(trim((string) $v))][] = $i;
        $peta = array();
        foreach (self::$alias as $k => $cocok)
        {
            foreach ($cocok as $c) if (isset($judul[$c])) { $peta[$k] = $judul[$c][0]; break; }
        }
        $nama = array('kab' => 'Kabupaten', 'kode' => 'Kode Komoditas', 'nama' => 'Nama Komoditas', 'ini' => 'Harga Bulan Ini', 'lalu' => 'Harga Bulan Sebelumnya');
        $hilang = array();
        foreach ($nama as $k => $n) if ( ! isset($peta[$k])) $hilang[] = $n;
        if ($hilang)
        {
            throw new RuntimeException('Kolom yang diperlukan tidak ditemukan: ' . implode(', ', $hilang) . '.');
        }
        return $peta;
    }

    /** Mencari baris judul tabel harga di 10 baris pertama. @return array|null [indeks baris, peta kolom] */
    private function cari_header(array $rows)
    {
        foreach (array_slice($rows, 0, 10, true) as $i => $r)
        {
            try { return array($i, $this->petakan_header($r)); }
            catch (RuntimeException $e) { /* bukan baris judul */ }
        }
        return null;
    }

    /** Kode kabupaten dari angka (61xx -> xx tetap angka) atau dari nama ("Kab. Sambas", "Kota Pontianak"). */
    private function norm_kab($v)
    {
        if (is_numeric($v)) return (int) $v;
        $t = strtolower(trim((string) $v));
        $t = trim(preg_replace('/^(kabupaten|kab\.?)\s+/', '', $t));
        foreach (self::$kabupaten as $kode => $nm) if (strtolower($nm) === $t) return $kode;
        return 0;
    }

    private function norm_kode($v)
    {
        if (is_numeric($v)) return sprintf('%.0f', $v);
        return strtoupper(trim((string) $v));
    }

    // ------------------------------------------------------------------
    // 2. PERHITUNGAN
    // ------------------------------------------------------------------

    /**
     * @param array $baris hasil baca_xlsx()
     * @param array $opsi  lihat self::$opsi_default
     * @return array ['ringkas'=>[...], 'komoditas'=>[...]]
     */
    /**
     * Alur pengolahan (sesuai alur di BPS):
     *   1. Sheet Data  -> HASIL RH per komoditas: rata-rata geometrik harga bulan lalu dan bulan ini, lalu RHt = (ini / lalu - 1) x 100
     *   2. HASIL RH    -> arah perubahan di tingkat provinsi (naik / turun / stabil) menurut RHt
     *   3. Arah + data kecamatan di sheet Data -> kabupaten dan kecamatan yang perlu dikonfirmasi (isi tabel konfirmasi)
     */
    public function hitung(array $baris, array $opsi = array(), array $daftar = array())
    {
        $o = array_merge(self::$opsi_default, $opsi);

        $kom = array();      // agregat per komoditas
        $tidak_valid = 0;
        $semua_kom = array();   // semua komoditas yang ada di file, termasuk yang harganya kosong / nol
        foreach ($daftar as $kd => $nm) $semua_kom[$kd] = array('nama' => $nm, 'sat' => array());   // daftar komoditas dari sheet RH

        foreach ($baris as $r)
        {
            if ( ! isset($semua_kom[$r['kode']])) $semua_kom[$r['kode']] = array('nama' => $r['nama'], 'sat' => array());
            if ($semua_kom[$r['kode']]['nama'] === '' && $r['nama'] !== '') $semua_kom[$r['kode']]['nama'] = $r['nama'];
            if ( ! empty($r['satuan'])) $semua_kom[$r['kode']]['sat'][$r['satuan']] = 1;

            if ($r['ini'] <= 0 || $r['lalu'] <= 0)
            {
                $tidak_valid++;
                continue;
            }
            $ln = log($r['ini'] / $r['lalu']);
            $k = $r['kode'];
            if ( ! isset($kom[$k]))
            {
                $kom[$k] = array('nama' => $r['nama'], 'sum' => 0.0, 'si' => 0.0, 'sl' => 0.0, 'n' => 0, 'kec' => array(), 'sat' => array());
            }
            if ( ! empty($r['satuan'])) $kom[$k]['sat'][$r['satuan']] = 1;
            $kom[$k]['sum'] += $ln;
            $kom[$k]['si']  += log($r['ini']);
            $kom[$k]['sl']  += log($r['lalu']);
            $kom[$k]['n']++;

            $kk = $r['kab'] . '|' . $r['kec'];
            if ( ! isset($kom[$k]['kec'][$kk]))
            {
                $kom[$k]['kec'][$kk] = array('kab' => $r['kab'], 'kec' => $r['kec'], 'sum' => 0.0, 'n' => 0, 'rinci' => array());
            }
            $kom[$k]['kec'][$kk]['sum'] += $ln;
            $kom[$k]['kec'][$kk]['n']++;
            $kom[$k]['kec'][$kk]['rinci'][] = array($r['kualitas'], $r['lalu'], $r['ini']);
        }

        $hasil = array();
        $jml_naik = $jml_turun = $jml_stabil = $jml_tampil = 0;

        foreach ($kom as $kode => $a)
        {
            // Tahap 1: Hasil RH (rata-rata geometrik bulan ini dan bulan lalu, lalu RHt)
            $g_ini  = exp($a['si'] / $a['n']);
            $g_lalu = exp($a['sl'] / $a['n']);
            $prov   = ($g_ini / $g_lalu - 1) * 100;     // RHt
            // Tahap 2: arah perubahan provinsi menurut RHt
            if (abs($prov) < $o['ambang_stabil']) { $arah = 'stabil'; $jml_stabil++; }
            elseif ($prov > 0)                     { $arah = 'naik';   $jml_naik++; }
            else                                   { $arah = 'turun';  $jml_turun++; }

            $dok = self::jenis_dokumen($kode);

            // Semua kecamatan + penanda kecamatan yang perlu dikonfirmasi
            $daftar = array();
            $semua  = array();
            foreach ($a['kec'] as $c)
            {
                $pk = (exp($c['sum'] / $c['n']) - 1) * 100;
                $searah = ($arah === 'naik' && $pk > 0) || ($arah === 'turun' && $pk < 0);
                $flag = ($arah !== 'stabil') && $searah && abs($pk) >= $o['ambang_kec'];
                $item = array(
                    'kab'     => $c['kab'],
                    'kec'     => self::judul($c['kec']),
                    'persen'  => $pk,
                    'n'       => $c['n'],
                    'flag'    => $flag,
                    'outlier' => abs($pk) >= $o['ambang_outlier'],
                    'rinci'   => $c['rinci'],
                );
                $semua[] = $item;
                if ($flag) $daftar[] = $item;
            }
            usort($semua, function ($x, $y) {
                return $x['kab'] <=> $y['kab'] ?: strcmp($x['kec'], $y['kec']);
            });

            // Semua komoditas di file selalu ikut ditampilkan (rekap, Excel, daftar komoditas); yang perlu dikonfirmasi hanya dihitung terpisah
            $tampil = true;
            $perlu  = ! empty($daftar) || ($arah === 'stabil' && $o['tampil_stabil']);

            // Kelompokkan per kabupaten
            $per_kab = array();
            foreach ($daftar as $d) $per_kab[$d['kab']][] = $d;
            ksort($per_kab);
            foreach ($per_kab as &$lst)
            {
                usort($lst, function ($x, $y) { return abs($y['persen']) <=> abs($x['persen']); });
            }
            unset($lst);

            if ($perlu) $jml_tampil++;
            $hasil[] = array(
                'dokumen'   => $dok,
                'kode'      => $kode,
                'nama'      => $a['nama'],
                'label'     => $a['nama'] . ' (' . $kode . ')',
                'satuan'    => implode(' / ', array_keys($a['sat'])),
                'prov_pct'  => $prov,       // RHt
                'g_ini'     => $g_ini,      // rata-rata geometrik bulan ini
                'g_lalu'    => $g_lalu,     // rata-rata geometrik bulan sebelumnya
                'arah'      => $arah,
                'jumlah'    => $a['n'],
                'tampil'    => $tampil,
                'kab'       => $per_kab,
                'kec_semua' => $semua,
            );
        }

        // Komoditas yang ada di file tetapi seluruh harganya kosong / nol: tetap dimasukkan (tanpa angka perubahan)
        $jml_tanpa = 0;
        foreach ($semua_kom as $kode => $a)
        {
            if (isset($kom[$kode])) continue;
            $jml_tanpa++;      // tanpa data: tetap tampil (arah dikosongkan), tetapi tidak dihitung "perlu konfirmasi"
            $hasil[] = array(
                'dokumen'   => self::jenis_dokumen($kode),
                'kode'      => $kode,
                'nama'      => $a['nama'],
                'label'     => $a['nama'] . ' (' . $kode . ')',
                'satuan'    => implode(' / ', array_keys($a['sat'])),
                'prov_pct'  => 0.0,
                'g_ini'     => null,
                'g_lalu'    => null,
                'arah'      => 'stabil',      // jumlah = 0 menandai "tanpa data"; tampilan menulis "tanpa data", bukan stabil
                'jumlah'    => 0,
                'tampil'    => true,
                'kab'       => array(),
                'kec_semua' => array(),
            );
        }

        // HD dahulu, lalu HKD; dalam dokumen urut kode
        usort($hasil, function ($x, $y) {
            if ($x['dokumen'] !== $y['dokumen']) return $x['dokumen'] === 'HD' ? -1 : 1;
            return strcmp($x['kode'], $y['kode']);
        });

        return array(
            'ringkas' => array(
                'jml_baris'       => count($baris),
                'jml_komoditas'   => count($kom) + $jml_tanpa,
                'tanpa_data'      => $jml_tanpa,
                'naik'            => $jml_naik,
                'turun'           => $jml_turun,
                'stabil'          => $jml_stabil,
                'perlu_konfirmasi' => $jml_tampil,
                'tidak_valid'     => $tidak_valid,
                'opsi'            => $o,
            ),
            'komoditas' => $hasil,
        );
    }

    /** Data siap pakai untuk dashboard */
    public function dashboard(array $hasil)
    {
        $per_kab = array(); $kec = array();
        $arah = array('HD' => array('naik' => 0, 'turun' => 0, 'stabil' => 0), 'HKD' => array('naik' => 0, 'turun' => 0, 'stabil' => 0));
        $naik = array('HD' => array(), 'HKD' => array()); $turun = array('HD' => array(), 'HKD' => array());

        foreach ($hasil['komoditas'] as $k)
        {
            $dok = $k['dokumen'];
            if ((int) $k['jumlah'] === 0) continue;      // komoditas tanpa data tidak ikut statistik arah
            $arah[$dok][$k['arah']]++;
            foreach ($k['kec_semua'] as $c) $kec[$c['kab'] . '|' . $c['kec']] = 1;
            if ($k['jumlah'] >= 3)
            {
                if ($k['arah'] === 'naik')  $naik[$dok][]  = array($k['nama'], round($k['prov_pct'], 2));
                if ($k['arah'] === 'turun') $turun[$dok][] = array($k['nama'], round($k['prov_pct'], 2));
            }
            foreach ($k['kab'] as $kab => $lst)
            {
                $nm = self::nama_kab($kab);
                if ( ! isset($per_kab[$nm])) $per_kab[$nm] = array('HD' => 0, 'HKD' => 0);
                $per_kab[$nm][$dok] += count($lst);
            }
        }
        uasort($per_kab, function ($x, $y) { return ($y['HD'] + $y['HKD']) <=> ($x['HD'] + $x['HKD']); });

        $top = array();
        foreach (array('HD', 'HKD') as $dok)
        {
            usort($naik[$dok],  function ($x, $y) { return $y[1] <=> $x[1]; });
            usort($turun[$dok], function ($x, $y) { return $x[1] <=> $y[1]; });
            $top[$dok] = array('naik' => array_slice($naik[$dok], 0, 10), 'turun' => array_slice($turun[$dok], 0, 10));
        }
        return array(
            'per_kab'       => $per_kab,   // [kabupaten => ['HD' => n, 'HKD' => n]]
            'arah'          => $arah,      // [dokumen => ['naik','turun','stabil']]
            'top'           => $top,       // [dokumen => ['naik' => [...], 'turun' => [...]]]
            'jml_kecamatan' => count($kec),
        );
    }

    public static function jenis_dokumen($kode)
    {
        return ctype_digit($kode) ? 'HKD' : 'HD';
    }

    public static function nama_kab($kode)
    {
        if (isset(self::$kabupaten[$kode])) return self::$kabupaten[$kode];
        return isset(self::$kab_extra[$kode]) ? self::$kab_extra[$kode] : 'Kab. ' . $kode;
    }

    /** Nomor wilayah yang sah untuk penugasan: kabupaten Kalbar atau wilayah tambahan dari file berformat lain */
    public static function kab_dikenal($kode)
    {
        return isset(self::$kabupaten[$kode]) || isset(self::$kab_extra[$kode]);
    }

    /** Nama wilayah ("KAB SAMBAS", "Kabupaten Sambas", "sambas") -> nomor kabupaten Kalbar; 0 bila bukan salah satu dari 14 kabupaten/kota */
    public static function kab_dari_nama($nama)
    {
        $t = strtolower(trim(preg_replace('/\s+/', ' ', (string) $nama)));
        $t = trim(preg_replace('/^(kabupaten|kab\.?)\s+/', '', $t));
        foreach (self::$kabupaten as $kode => $nm) if (strtolower($nm) === $t) return $kode;
        return 0;
    }

    /** Kelas warna lencana dokumen: HD / HKD tetap, nama sheet lain (HPB, HK, ...) berganti-ganti agar mudah dibedakan */
    public static function kelas_dok($dok)
    {
        $d = strtolower((string) $dok);
        if ($d === 'hd' || $d === 'hkd') return $d;
        return (crc32($d) % 2) ? 'hkd' : 'hd';
    }

    public static function judul($s)
    {
        return mb_convert_case(mb_strtolower($s, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    public static function fmt($angka, $desimal = 1, $tanda = true)
    {
        $s = number_format(abs($angka), $desimal, ',', '.');
        if ( ! $tanda) return $s;
        return ($angka < 0 ? '-' : '+') . $s . '%';
    }

    /** Teks kolom "Kabupaten": "Sambas (Pemangkat, Teluk Keramat)" */
    public static function teks_kabupaten($kab, array $daftar)
    {
        $nama = array();
        foreach ($daftar as $d) if (trim((string) $d['kec']) !== '') $nama[] = $d['kec'];
        if ( ! $nama) return self::nama_kab($kab);      // tanpa kecamatan (komoditas kosong): hanya nama kabupaten
        return self::nama_kab($kab) . ' (' . implode(', ', $nama) . ')';
    }

    /** Teks rincian persentase per kecamatan */
    public static function teks_rinci(array $daftar)
    {
        $t = array();
        foreach ($daftar as $d) $t[] = trim($d['kec'] . ' ' . (isset($d['label']) ? $d['label'] : self::fmt($d['persen'])));
        return implode('; ', $t);
    }

    // ------------------------------------------------------------------
    // 3. TULIS EXCEL (format mengikuti spreadsheet pembimbing)
    // ------------------------------------------------------------------

    /**
     * Excel hasil: satu sheet, lima kolom, sama dengan spreadsheet "Perubahan Sementara Harga Perdesaan"
     * Dokumen | Komoditas | Perubahan Harga Perdesaan Provinsi | Kabupaten | Alasan
     */
    public function tulis_xlsx(array $hasil, $path_keluar, $judul = '', array $alasan = array())
    {
        require_once APPPATH . 'libraries/Xlsx_io.php';

        $ada_sat = false;
        foreach ($hasil['komoditas'] as $k) if ( ! empty($k['tampil']) && ! empty($k['satuan'])) { $ada_sat = true; break; }
        $baris = array($ada_sat ? array('Dokumen', 'Komoditas', 'Satuan', 'Perubahan Harga Perdesaan Provinsi', 'Kabupaten', 'Alasan')
                                : array('Dokumen', 'Komoditas', 'Perubahan Harga Perdesaan Provinsi', 'Kabupaten', 'Alasan'));
        foreach ($hasil['komoditas'] as $k)
        {
            if (empty($k['tampil'])) continue;
            $pertama = true;
            $rows = empty($k['kab']) ? array(0 => array()) : $k['kab'];
            foreach ($rows as $kab => $daftar)
            {
                $sel = array(
                    $pertama ? $k['dokumen'] : null,
                    $pertama ? $k['label'] : null,
                    $pertama ? ((int) $k['jumlah'] === 0 ? null : $k['arah']) : null,
                    $kab ? self::teks_kabupaten($kab, $daftar) : null,
                    isset($alasan[$k['kode'] . '|' . $kab]) ? $alasan[$k['kode'] . '|' . $kab] : null,
                );
                if ($ada_sat) array_splice($sel, 2, 0, array($pertama && ! empty($k['satuan']) ? $k['satuan'] : null));
                $baris[] = $sel;
                $pertama = false;
            }
            $baris[] = array(); // baris kosong pemisah antarkomoditas
        }

        Xlsx_io::tulis($path_keluar, array(
            array('nama' => 'Perubahan Harga', 'baris' => $baris, 'header' => true, 'beku' => true, 'filter' => true, 'filter' => true,
                  'garis' => true, 'lebar' => $ada_sat ? array(11, 34, 12, 21, 44, 80) : array(11, 34, 21, 44, 80)),
        ));
    }
}