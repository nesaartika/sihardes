<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cermat_harga
 * ------------------------------------------------------------------
 * Otomatisasi pencermatan & rekapitulasi perubahan harga perdesaan
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

        $peta = $this->petakan_header(array_shift($rows));
        $baris = array();
        foreach ($rows as $r)
        {
            if ( ! isset($r[$peta['kode']]) || $r[$peta['kode']] === '') continue;
            $baris[] = array(
                'kab'      => isset($r[$peta['kab']]) ? (int) $r[$peta['kab']] : 0,
                'kec'      => isset($r[$peta['kec']]) ? trim((string) $r[$peta['kec']]) : '',
                'kode'     => $this->norm_kode($r[$peta['kode']]),
                'nama'     => isset($r[$peta['nama']]) ? trim((string) $r[$peta['nama']]) : '',
                'kualitas' => isset($r[$peta['kualitas']]) ? trim((string) $r[$peta['kualitas']]) : '',
                'ini'      => isset($r[$peta['ini']]) ? (float) $r[$peta['ini']] : 0.0,
                'lalu'     => isset($r[$peta['lalu']]) ? (float) $r[$peta['lalu']] : 0.0,
            );
        }
        return $baris;
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

    private function petakan_header($r)
    {
        $kunci = array(
            'kab'      => array('kabupaten'),
            'kec'      => array('nama kecamatan'),
            'kode'     => array('kode komoditas'),
            'nama'     => array('nama komoditas'),
            'kualitas' => array('nama kualitas'),
            'ini'      => array('harga bulan ini'),
            'lalu'     => array('harga bulan sebelumnya'),
        );
        $peta = array();
        foreach ($r as $i => $judul)
        {
            $j = strtolower(trim((string) $judul));
            foreach ($kunci as $k => $cocok)
            {
                if (in_array($j, $cocok, true) && ! isset($peta[$k])) $peta[$k] = $i;
            }
        }
        foreach (array_keys($kunci) as $k)
        {
            if ( ! isset($peta[$k]))
            {
                throw new RuntimeException("Kolom wajib tidak ditemukan pada header sheet Data: {$k}");
            }
        }
        return $peta;
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
    public function hitung(array $baris, array $opsi = array())
    {
        $o = array_merge(self::$opsi_default, $opsi);

        $kom = array();      // agregat per komoditas
        $tidak_valid = 0;

        foreach ($baris as $r)
        {
            if ($r['ini'] <= 0 || $r['lalu'] <= 0)
            {
                $tidak_valid++;
                continue;
            }
            $ln = log($r['ini'] / $r['lalu']);
            $k = $r['kode'];
            if ( ! isset($kom[$k]))
            {
                $kom[$k] = array('nama' => $r['nama'], 'sum' => 0.0, 'n' => 0, 'kec' => array());
            }
            $kom[$k]['sum'] += $ln;
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
            $prov = (exp($a['sum'] / $a['n']) - 1) * 100;
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

            $tampil = ! empty($daftar) || ($arah === 'stabil' && $o['tampil_stabil']);

            // Kelompokkan per kabupaten
            $per_kab = array();
            foreach ($daftar as $d) $per_kab[$d['kab']][] = $d;
            ksort($per_kab);
            foreach ($per_kab as &$lst)
            {
                usort($lst, function ($x, $y) { return abs($y['persen']) <=> abs($x['persen']); });
            }
            unset($lst);

            if ($tampil) $jml_tampil++;
            $hasil[] = array(
                'dokumen'   => $dok,
                'kode'      => $kode,
                'nama'      => $a['nama'],
                'label'     => $a['nama'] . ' (' . $kode . ')',
                'prov_pct'  => $prov,
                'arah'      => $arah,
                'jumlah'    => $a['n'],
                'tampil'    => $tampil,
                'kab'       => $per_kab,
                'kec_semua' => $semua,
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
                'jml_komoditas'   => count($kom),
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
        return isset(self::$kabupaten[$kode]) ? self::$kabupaten[$kode] : 'Kab. ' . $kode;
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
        foreach ($daftar as $d) $nama[] = $d['kec'];
        return self::nama_kab($kab) . ' (' . implode(', ', $nama) . ')';
    }

    /** Teks rincian persentase per kecamatan */
    public static function teks_rinci(array $daftar)
    {
        $t = array();
        foreach ($daftar as $d) $t[] = $d['kec'] . ' ' . self::fmt($d['persen']);
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

        $baris = array(array('Dokumen', 'Komoditas', 'Perubahan Harga Perdesaan Provinsi', 'Kabupaten', 'Alasan'));
        foreach ($hasil['komoditas'] as $k)
        {
            if (empty($k['tampil'])) continue;
            $pertama = true;
            $rows = empty($k['kab']) ? array(0 => array()) : $k['kab'];
            foreach ($rows as $kab => $daftar)
            {
                $baris[] = array(
                    $pertama ? $k['dokumen'] : null,
                    $pertama ? $k['label'] : null,
                    $pertama ? $k['arah'] : null,
                    $daftar ? self::teks_kabupaten($kab, $daftar) : null,
                    isset($alasan[$k['kode'] . '|' . $kab]) ? $alasan[$k['kode'] . '|' . $kab] : null,
                );
                $pertama = false;
            }
            $baris[] = array(); // baris kosong pemisah antarkomoditas
        }

        Xlsx_io::tulis($path_keluar, array(
            array('nama' => 'Perubahan Harga', 'baris' => $baris, 'header' => true, 'beku' => true, 'filter' => true, 'filter' => true,
                  'garis' => true, 'lebar' => array(11, 34, 21, 44, 80)),
        ));
    }
}