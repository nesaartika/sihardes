<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if ( ! class_exists('Cermat_harga')) require_once APPPATH . 'libraries/Cermat_harga.php';

/**
 * Sheet_rekap
 * ------------------------------------------------------------------
 * Menyusun menu Komoditas dan Rekap dari file Excel yang BUKAN tabel harga
 * (mis. sheet HD, HKD, HPB, HK, atau Rekonsiliasi). Setiap sheet dibaca sendiri-sendiri
 * dan formatnya dikenali dari isinya, jadi sheet yang berbeda format tetap bisa dirangkum:
 *
 *   matriks (arah)  : baris = komoditas, kolom = wilayah, isi sel NAIK / TURUN / STABIL     (contoh: sheet HD, HKD, HPB, HK)
 *   matriks (angka) : baris = komoditas, kolom = wilayah, isi sel berupa angka
 *   daftar          : satu baris = satu komoditas di satu wilayah, dengan kolom angka (Inflasi, Andil, ...)
 *                     dan kolom teks (Alasan, Detail, ...)                                  (contoh: sheet Rekonsiliasi)
 *   tidak_ada       : sheet tidak punya baris judul / kolom komoditas yang bisa dikenali (tetap bisa dilihat di menu Data)
 *
 * Alur:  analisis()  ->  susun()  ->  komoditas tampil dari model;  rekap() ->  bagian-bagian tabel rekap.
 * Tidak ada yang disimpan ke database: semua dihitung dari isi sheet yang sudah tersimpan saat unggah.
 */
class Sheet_rekap
{
    /** Batas selisih dianggap stabil (sama dengan ambang_stabil di Cermat_harga) */
    const AMBANG_STABIL = 0.005;

    // ------------------------------------------------------------------
    // Alat kecil
    // ------------------------------------------------------------------

    private static function n($s)
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
    }

    private static function kosong($v)
    {
        return $v === null || trim((string) $v) === '';
    }

    private static function is_angka($v)
    {
        if (self::kosong($v)) return false;
        $s = str_replace(array(' ', '%'), '', (string) $v);
        if (preg_match('/^-?\d+,\d+$/', $s)) $s = str_replace(',', '.', $s);
        return is_numeric($s);
    }

    private static function ke_angka($v)
    {
        $s = str_replace(array(' ', '%'), '', (string) $v);
        if (preg_match('/^-?\d+,\d+$/', $s)) $s = str_replace(',', '.', $s);
        return (float) $s;
    }

    /** naik / turun / stabil dari teks sel; null bila bukan status. "Tetap" dianggap stabil. */
    private static function status($v)
    {
        $t = self::n($v);
        if ($t === 'tetap') return 'stabil';
        return in_array($t, array('naik', 'turun', 'stabil'), true) ? $t : null;
    }

    private static function huruf($i)
    {
        $s = '';
        for ($i = $i + 1; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
        return $s;
    }

    private static function arah_dari($x)
    {
        if ($x === null) return null;
        if (abs($x) < self::AMBANG_STABIL) return 'stabil';
        return $x > 0 ? 'naik' : 'turun';
    }

    /** Angka untuk tampilan: bilangan bulat tanpa desimal; selain itu 2 desimal (format Indonesia). $pct = beri tanda dan %. */
    public static function fmt($x, $pct = false)
    {
        if ($x === null) return '';
        if ($pct) return Cermat_harga::fmt($x, 2);
        $d = (abs($x - round($x)) < 0.0000001) ? 0 : 2;
        return number_format($x, $d, ',', '.');
    }

    private function kolom_exact(array $n, array $daftar, array $kecuali = array())
    {
        foreach ($daftar as $c)
        {
            foreach ($n as $j => $t) if ($t === $c && ! in_array($j, $kecuali, true)) return $j;
        }
        return null;
    }

    // ------------------------------------------------------------------
    // 1. MENGENALI FORMAT SHEET
    // ------------------------------------------------------------------

    /** Baris judul: baris pertama (dari 12 baris teratas) yang berisi minimal 3 judul teks. */
    private function cari_header(array $g)
    {
        $maks = min(count($g), 12);
        for ($i = 0; $i < $maks; $i++)
        {
            $teks = 0; $isi = 0;
            foreach ($g[$i]['v'] as $c)
            {
                if (self::kosong($c)) continue;
                $isi++;
                if ( ! self::is_angka($c)) $teks++;
            }
            if ($teks >= 3 && $teks >= 0.8 * $isi) return $i;
        }
        return null;
    }

    /**
     * @param array $rows isi sheet seperti disimpan: [ ['r' => nomor baris Excel, 'v' => [sel, ...]], ... ]
     * @return array hasil pengenalan (layout, peran kolom, baris data)
     */
    public function analisis(array $rows)
    {
        $g = array();
        foreach ($rows as $r) $g[] = array('r' => (int) $r['r'], 'v' => (isset($r['v']) && is_array($r['v'])) ? $r['v'] : array());

        $hi = $this->cari_header($g);
        if ($hi === null) return array('layout' => 'tidak_ada', 'sebab' => 'Baris judul kolom tidak ditemukan di sheet ini.');

        $lebar = 0;
        foreach ($g as $x) $lebar = max($lebar, count($x['v']));
        $data = array();
        foreach (array_slice($g, $hi + 1) as $x)
        {
            foreach ($x['v'] as $c) if ( ! self::kosong($c)) { $data[] = $x; break; }
        }
        if ( ! $data) return array('layout' => 'tidak_ada', 'sebab' => 'Sheet ini hanya berisi judul kolom, belum ada isinya.');

        // judul dan profil tiap kolom
        $head = array(); $prof = array();
        for ($j = 0; $j < $lebar; $j++)
        {
            $head[$j] = isset($g[$hi]['v'][$j]) ? trim((string) $g[$hi]['v'][$j]) : '';
            $prof[$j] = array('isi' => 0, 'angka' => 0, 'arah' => 0, 'beda' => array());
        }
        foreach ($data as $x)
        {
            foreach ($x['v'] as $j => $c)
            {
                if ($j >= $lebar || self::kosong($c)) continue;
                $prof[$j]['isi']++;
                if (self::is_angka($c)) $prof[$j]['angka']++;
                if (self::status($c) !== null) $prof[$j]['arah']++;
                if (count($prof[$j]['beda']) < 3000) $prof[$j]['beda'][strtolower(trim((string) $c))] = 1;
            }
        }
        $ada = array();   // kolom yang dipakai: punya judul atau punya isi
        for ($j = 0; $j < $lebar; $j++)
        {
            if ($head[$j] === '' && $prof[$j]['isi'] === 0) continue;
            if ($head[$j] === '') $head[$j] = 'Kolom ' . self::huruf($j);
            $ada[] = $j;
        }
        $n = array();
        foreach ($ada as $j) $n[$j] = self::n($head[$j]);

        // peran kolom
        $nama = $this->kolom_exact($n, array('nama komoditas', 'nama komoditi', 'komoditas', 'komoditi', 'nama barang', 'barang', 'uraian', 'jenis komoditas', 'nama'));
        $kode = null;
        foreach ($n as $j => $t) if (strpos($t, 'kode') !== false && preg_match('/komoditas|komoditi|barang/', $t)) { $kode = $j; break; }
        if ($kode === null) $kode = $this->kolom_exact($n, array('kode', 'kd'));
        $wil = $this->kolom_exact($n, array('nama wilayah', 'wilayah', 'kabupaten/kota', 'kab/kota', 'nama kabupaten', 'kabupaten', 'nama kab/kota',
                                            'kota', 'nama provinsi', 'provinsi', 'nama kecamatan', 'kecamatan'));
        if ($nama === null)   // tanpa kolom "komoditas": pakai kolom teks pertama yang isinya beragam (bukan nomor urut)
        {
            foreach ($ada as $j)
            {
                if ($prof[$j]['isi'] < 2 || $prof[$j]['angka'] > 0.3 * $prof[$j]['isi'] || preg_match('/^(no\.?|nomor)$/', $n[$j])) continue;
                $b = count($prof[$j]['beda']);
                if ($b >= 2 && $b <= 0.9 * $prof[$j]['isi'] + 1) { $nama = $j; break; }
            }
            if ($nama === null) return array('layout' => 'tidak_ada', 'sebab' => 'Kolom komoditas (atau kolom pengelompok) tidak ditemukan di sheet ini.');
        }

        $peran = array('nama' => $nama, 'kode' => $kode, 'wil' => $wil);
        $baris = array();
        foreach ($data as $x) if ( ! self::kosong(isset($x['v'][$nama]) ? $x['v'][$nama] : null)) $baris[] = $x;
        $a = array('layout' => 'daftar', 'r_header' => $g[$hi]['r'], 'head' => $head, 'n' => $n, 'prof' => $prof, 'ada' => $ada,
                   'peran' => $peran, 'baris' => $baris);

        // matriks: banyak kolom wilayah (nama kabupaten / Kalbar / Provinsi) atau banyak kolom berisi NAIK-TURUN-STABIL
        $reg = array('kalbar' => 1, 'kalimantan barat' => 1, 'provinsi' => 1, 'prov' => 1, 'prov.' => 1);
        foreach (Cermat_harga::$kabupaten as $k => $nm)
        {
            $l = strtolower($nm);
            foreach (array($l, 'kab. ' . $l, 'kab ' . $l, 'kabupaten ' . $l, 'kota ' . $l) as $v) $reg[$v] = 1;
            if ($k > 70) $reg[preg_replace('/^kota /', '', $l)] = 1;
        }
        $kolom_wil = array();
        foreach ($ada as $j)
        {
            if ($j === $nama || $j === $kode) continue;
            $p = $prof[$j];
            $is_arah = $p['isi'] > 0 && $p['arah'] >= 0.7 * $p['isi'];
            $is_reg  = isset($reg[$n[$j]]);
            if ($is_arah || $is_reg) $kolom_wil[] = $j;
        }
        if (count($kolom_wil) >= 3)
        {
            $isi = 0; $arah = 0; $angka = 0;
            foreach ($kolom_wil as $j) { $isi += $prof[$j]['isi']; $arah += $prof[$j]['arah']; $angka += $prof[$j]['angka']; }
            $mode = null;
            if ($isi === 0 || $arah >= 0.5 * $isi) $mode = 'arah';
            elseif ($angka >= 0.5 * $isi) $mode = 'angka';
            if ($mode !== null)
            {
                $prov = null;
                foreach ($kolom_wil as $j) if (in_array($n[$j], array('kalbar', 'kalimantan barat', 'provinsi', 'prov', 'prov.'), true)) { $prov = $j; break; }
                $info = array(); $teks = array();
                foreach ($ada as $j)
                {
                    if (in_array($j, $kolom_wil, true) || $j === $nama || $j === $kode) continue;
                    if (preg_match('/^(kategori|satuan|jenis|kelompok|dokumen|golongan|sektor|sub ?kelompok)$/', $n[$j])) $info[] = $j;
                    elseif (preg_match('/alasan|detail|catatan|keterangan|^ket\.?$|sebab|penyebab/', $n[$j])) $teks[] = $j;
                }
                $a['layout'] = 'matriks'; $a['mode'] = $mode;
                $a['kolom_wil'] = array_values(array_filter($kolom_wil, function ($j) use ($prov) { return $j !== $prov; }));
                $a['prov'] = $prov; $a['info'] = $info; $a['teks'] = $teks;
                return $a;
            }
        }

        // daftar: kolom angka dan kolom teks keterangan
        $angka = array(); $teks = array(); $info = array();
        foreach ($ada as $j)
        {
            if (in_array($j, array($nama, $kode, $wil), true)) continue;
            $t = $n[$j]; $p = $prof[$j];
            if (strpos($t, 'kode') !== false || preg_match('/^(no\.?|nomor|tahun|bulan|periode|id)$/', $t)) continue;
            if (preg_match('/^(kategori|satuan|jenis|kelompok|dokumen|golongan|sektor)$/', $t)) { $info[] = $j; continue; }
            if ($p['isi'] > 0 && $p['angka'] >= 0.7 * $p['isi']) $angka[] = $j;
            elseif ($p['isi'] > 0 && preg_match('/alasan|detail|catatan|keterangan|^ket\.?$|sebab|penyebab/', $t)) $teks[] = $j;
        }
        $utama = null;
        foreach ($angka as $j) if (preg_match('/inflasi|perubahan|persen|%|rht|selisih|growth/', $n[$j])) { $utama = $j; break; }
        if ($utama === null && $angka) $utama = $angka[0];
        $a['angka'] = $angka; $a['teks'] = $teks; $a['info'] = $info; $a['utama'] = $utama;
        $a['pct'] = $utama !== null && (bool) preg_match('/inflasi|perubahan|persen|%|rht|selisih|growth/', $n[$utama]);
        return $a;
    }

    // ------------------------------------------------------------------
    // 2. MODEL: komoditas dan nilainya per wilayah
    // ------------------------------------------------------------------

    private function sel(array $x, $j)
    {
        return ($j !== null && isset($x['v'][$j]) && ! self::kosong($x['v'][$j])) ? trim((string) $x['v'][$j]) : '';
    }

    /**
     * Mengubah hasil analisis() menjadi model siap tampil.
     * @return array ['layout','mode','sebab','item'=>[...], ...] ; item bergantung layout (lihat view sheet_komoditas)
     */
    public function susun(array $a)
    {
        if ($a['layout'] === 'tidak_ada') return array('layout' => 'tidak_ada', 'mode' => '-', 'sebab' => $a['sebab'], 'item' => array());
        $H = $a['head']; $P = $a['peran'];
        $m = array('layout' => $a['layout'], 'mode' => 'daftar', 'sebab' => '', 'kolom_nama' => $H[$P['nama']],
                   'ada_kode' => $P['kode'] !== null, 'kolom_kode' => $P['kode'] !== null ? $H[$P['kode']] : '',
                   'info_kol' => array(), 'teks_kol' => array(), 'jml_baris' => count($a['baris']), 'item' => array());
        foreach ($a['info'] as $j) $m['info_kol'][] = $H[$j];
        foreach ($a['teks'] as $j) $m['teks_kol'][] = $H[$j];

        if ($a['layout'] === 'matriks')
        {
            $m['mode'] = $a['mode'];
            $m['prov'] = $a['prov'] !== null ? $H[$a['prov']] : null;
            $m['wil'] = array();
            foreach ($a['kolom_wil'] as $j) $m['wil'][] = $H[$j];
            $no = 0;
            foreach ($a['baris'] as $x)
            {
                $it = array('no' => ++$no, 'r' => $x['r'], 'nama' => $this->sel($x, $P['nama']), 'kode' => $this->sel($x, $P['kode']),
                            'info' => array(), 'teks' => array(), 'prov' => null, 'wil' => array());
                foreach ($a['info'] as $j) $it['info'][$H[$j]] = $this->sel($x, $j);
                foreach ($a['teks'] as $j) $it['teks'][$H[$j]] = $this->sel($x, $j);
                if ($a['prov'] !== null)
                {
                    $v = $this->sel($x, $a['prov']);
                    $it['prov'] = $a['mode'] === 'arah' ? self::status($v) : (self::is_angka($v) ? self::ke_angka($v) : null);
                }
                foreach ($a['kolom_wil'] as $j)
                {
                    $v = $this->sel($x, $j);
                    if ($v === '') continue;
                    if ($a['mode'] === 'arah') { $s = self::status($v); if ($s !== null) $it['wil'][$H[$j]] = $s; }
                    elseif (self::is_angka($v)) $it['wil'][$H[$j]] = self::ke_angka($v);
                }
                $m['item'][] = $it;
            }
            return $m;
        }

        // daftar: baris dikelompokkan per komoditas
        $m['ada_wil'] = $P['wil'] !== null;
        $m['kolom_wil'] = $P['wil'] !== null ? $H[$P['wil']] : '';
        $m['angka_kol'] = array();
        foreach ($a['angka'] as $j) $m['angka_kol'][] = $H[$j];
        $m['utama'] = $a['utama'] !== null ? $H[$a['utama']] : null;
        $m['pct'] = $a['pct'];
        $grup = array(); $urut = array();
        foreach ($a['baris'] as $x)
        {
            $nm = $this->sel($x, $P['nama']); $kd = $this->sel($x, $P['kode']);
            $kunci = $kd !== '' ? $kd . '|' . strtolower($nm) : strtolower($nm);
            if ( ! isset($grup[$kunci]))
            {
                $grup[$kunci] = array('nama' => $nm, 'kode' => $kd, 'info' => array(), 'baris' => array());
                foreach ($a['info'] as $j) $grup[$kunci]['info'][$H[$j]] = $this->sel($x, $j);
                $urut[] = $kunci;
            }
            $b = array('r' => $x['r'], 'wil' => $this->sel($x, $P['wil']), 'angka' => array(), 'teks' => array());
            foreach ($a['angka'] as $j) { $v = $this->sel($x, $j); $b['angka'][$H[$j]] = self::is_angka($v) ? self::ke_angka($v) : null; }
            foreach ($a['teks'] as $j) $b['teks'][$H[$j]] = $this->sel($x, $j);
            $grup[$kunci]['baris'][] = $b;
        }
        $no = 0;
        foreach ($urut as $kunci)
        {
            $gp = $grup[$kunci];
            $gp['no'] = ++$no; $gp['n'] = count($gp['baris']);
            $gp['sum'] = array(); $gp['avg'] = array(); $gp['min'] = null; $gp['maks'] = null; $gp['arah'] = null;
            foreach ($m['angka_kol'] as $kol)
            {
                $v = array();
                foreach ($gp['baris'] as $b) if ($b['angka'][$kol] !== null) $v[] = $b['angka'][$kol];
                $gp['sum'][$kol] = $v ? array_sum($v) : null;
                $gp['avg'][$kol] = $v ? array_sum($v) / count($v) : null;
                if ($kol === $m['utama'] && $v) { $gp['min'] = min($v); $gp['maks'] = max($v); }
            }
            if ($m['utama'] !== null) $gp['arah'] = self::arah_dari($gp['avg'][$m['utama']]);
            $m['item'][] = $gp;
        }
        return $m;
    }

    /** Nama wilayah yang muncul (untuk pilihan filter) */
    public function daftar_wilayah(array $m)
    {
        if ($m['layout'] === 'matriks') return $m['wil'];
        $w = array();
        if ( ! empty($m['ada_wil']))
            foreach ($m['item'] as $it) foreach ($it['baris'] as $b) if ($b['wil'] !== '') $w[$b['wil']] = 1;
        $w = array_keys($w);
        sort($w);
        return $w;
    }

    // ------------------------------------------------------------------
    // 3. REKAP: kartu ringkasan + tabel
    // ------------------------------------------------------------------

    private static function sel_arah($status)
    {
        return array('t' => ucfirst($status), 'k' => $status, 'badge' => true);
    }

    /**
     * @return array ['kartu' => [[label, nilai, keterangan], ...],
     *                'bagian' => [ ['judul','pendek','ket','kolom'=>[...],'baris'=>[[sel,...],...]], ... ]]
     * sel = teks/angka, atau ['t' => teks, 'v' => angka (untuk Excel), 'k' => kelas warna, 'badge' => bool]
     */
    public function rekap(array $m)
    {
        if ($m['layout'] === 'matriks') return $m['mode'] === 'arah' ? $this->rekap_matriks_arah($m) : $this->rekap_matriks_angka($m);
        if ($m['layout'] === 'daftar')  return $this->rekap_daftar($m);
        return array('kartu' => array(), 'bagian' => array());
    }

    private function rekap_matriks_arah(array $m)
    {
        $cnt = array('naik' => 0, 'turun' => 0, 'stabil' => 0, 'kosong' => 0);
        $per_wil = array(); foreach ($m['wil'] as $w) $per_wil[$w] = array('naik' => 0, 'turun' => 0, 'stabil' => 0);
        $prov_cnt = array('naik' => 0, 'turun' => 0, 'stabil' => 0);
        $baris = array(); $tanpa = 0;
        foreach ($m['item'] as $it)
        {
            $naik = array(); $turun = array(); $stabil = 0;
            foreach ($it['wil'] as $w => $s)
            {
                $per_wil[$w][$s]++;
                if ($s === 'naik') $naik[] = $w; elseif ($s === 'turun') $turun[] = $w; else $stabil++;
            }
            if ($it['prov'] !== null) $prov_cnt[$it['prov']]++;
            if ($it['prov'] === null && ! $it['wil']) { $tanpa++; $cnt['kosong']++; continue; }
            $row = array(array('t' => $it['nama'], 'v' => $it['nama']));
            foreach ($m['info_kol'] as $k) $row[] = $it['info'][$k];
            if ($m['prov'] !== null) $row[] = $it['prov'] !== null ? self::sel_arah($it['prov']) : '';
            $row[] = $naik ? array('t' => implode(', ', $naik), 'k' => 'naik') : '';
            $row[] = $turun ? array('t' => implode(', ', $turun), 'k' => 'turun') : '';
            $row[] = array('t' => (string) $stabil, 'v' => $stabil);
            foreach ($m['teks_kol'] as $k) $row[] = $it['teks'][$k];
            $baris[] = $row;
        }
        $kolom = array($m['kolom_nama']);
        foreach ($m['info_kol'] as $k) $kolom[] = $k;
        if ($m['prov'] !== null) $kolom[] = $m['prov'];
        $kolom[] = 'Wilayah naik'; $kolom[] = 'Wilayah turun'; $kolom[] = 'Jumlah wilayah stabil';
        foreach ($m['teks_kol'] as $k) $kolom[] = $k;

        $b2 = array();
        if ($m['prov'] !== null)
        {
            $isi = array_sum($prov_cnt);
            $b2[] = array(array('t' => $m['prov'] . ' (provinsi)', 'v' => $m['prov'] . ' (provinsi)'), $prov_cnt['naik'], $prov_cnt['turun'], $prov_cnt['stabil'], count($m['item']) - $isi);
        }
        foreach ($per_wil as $w => $c)
        {
            $isi = $c['naik'] + $c['turun'] + $c['stabil'];
            $b2[] = array($w, $c['naik'], $c['turun'], $c['stabil'], count($m['item']) - $isi);
            $cnt['naik'] += $c['naik']; $cnt['turun'] += $c['turun']; $cnt['stabil'] += $c['stabil'];
        }

        if ($m['prov'] !== null)
        {
            $kartu = array(array('Komoditas', count($m['item']), 'pada sheet ini'),
                           array('Naik (' . $m['prov'] . ')', $prov_cnt['naik'], 'komoditas'), array('Turun (' . $m['prov'] . ')', $prov_cnt['turun'], 'komoditas'),
                           array('Stabil (' . $m['prov'] . ')', $prov_cnt['stabil'], 'komoditas'),
                           array('Belum ada status', count($m['item']) - array_sum($prov_cnt), 'komoditas'));
        }
        else
        {
            $kartu = array(array('Komoditas', count($m['item']), 'pada sheet ini'), array('Naik', $cnt['naik'], 'sel wilayah'),
                           array('Turun', $cnt['turun'], 'sel wilayah'), array('Stabil', $cnt['stabil'], 'sel wilayah'),
                           array('Belum ada status', $tanpa, 'komoditas'));
        }
        return array('kartu' => $kartu, 'bagian' => array(
            array('judul' => 'Perubahan per komoditas', 'pendek' => 'Komoditas',
                  'ket' => 'Komoditas yang sudah berisi status' . ($tanpa ? ' (' . $tanpa . ' komoditas belum berisi status, tidak ditampilkan)' : '') . '. Yang stabil cukup dihitung jumlahnya.',
                  'kolom' => $kolom, 'baris' => $baris),
            array('judul' => 'Rekap per wilayah', 'pendek' => 'Wilayah', 'ket' => 'Jumlah komoditas naik, turun, dan stabil di tiap wilayah.',
                  'kolom' => array('Wilayah', 'Naik', 'Turun', 'Stabil', 'Belum ada status'), 'baris' => $b2),
        ));
    }

    private function rekap_matriks_angka(array $m)
    {
        $kol = array_merge($m['prov'] !== null ? array($m['prov']) : array(), $m['wil']);
        $per = array(); foreach ($kol as $k) $per[$k] = array();
        $b_kom = array(); $semua = array();
        foreach ($m['item'] as $it)
        {
            $v = $it['wil'];
            if ($m['prov'] !== null && $it['prov'] !== null) $per[$m['prov']][] = $it['prov'];
            foreach ($it['wil'] as $w => $x) { $per[$w][] = $x; $semua[] = $x; }
            $b_kom[] = array($it['nama'], count($v), $v ? array('t' => self::fmt(array_sum($v) / count($v)), 'v' => array_sum($v) / count($v)) : '',
                             $v ? self::fmt(min($v)) : '', $v ? self::fmt(max($v)) : '');
        }
        $b_kol = array();
        foreach ($per as $k => $v)
            $b_kol[] = array($k, count($v), $v ? self::fmt(array_sum($v) / count($v)) : '', $v ? self::fmt(min($v)) : '', $v ? self::fmt(max($v)) : '', $v ? self::fmt(array_sum($v)) : '');
        return array(
            'kartu' => array(array('Komoditas', count($m['item']), 'pada sheet ini'), array('Kolom wilayah', count($kol), ''),
                             array('Sel berisi angka', count($semua), ''), array('Rata-rata semua sel', $semua ? self::fmt(array_sum($semua) / count($semua)) : '-', '')),
            'bagian' => array(
                array('judul' => 'Rekap per kolom', 'pendek' => 'Kolom', 'ket' => 'Ringkasan angka tiap kolom wilayah.',
                      'kolom' => array('Kolom', 'Terisi', 'Rata-rata', 'Terendah', 'Tertinggi', 'Jumlah'), 'baris' => $b_kol),
                array('judul' => 'Rekap per komoditas', 'pendek' => 'Komoditas', 'ket' => 'Ringkasan angka tiap komoditas pada semua kolom wilayah.',
                      'kolom' => array($m['kolom_nama'], 'Terisi', 'Rata-rata', 'Terendah', 'Tertinggi'), 'baris' => $b_kom),
            ));
    }

    private function rekap_daftar(array $m)
    {
        $u = $m['utama']; $pct = $m['pct']; $ada_wil = ! empty($m['ada_wil']);
        $alasan_kol = null;
        foreach ($m['teks_kol'] as $k) if (preg_match('/alasan|sebab|penyebab/i', $k)) { $alasan_kol = $k; break; }
        $nilai = function ($x) use ($pct) { return $x === null ? '' : array('t' => Sheet_rekap::fmt($x, $pct), 'v' => $x); };

        $arah = array('naik' => 0, 'turun' => 0, 'stabil' => 0);
        $per_wil = array(); $per_alasan = array(); $baris_total = 0;
        $kolom = array(); if ($m['ada_kode']) $kolom[] = $m['kolom_kode'];
        $kolom[] = $m['kolom_nama'];
        foreach ($m['info_kol'] as $k) $kolom[] = $k;
        if ($u !== null) $kolom[] = 'Arah';
        $kolom[] = $ada_wil ? 'Jumlah wilayah' : 'Jumlah baris';
        if ($u !== null) { $kolom[] = 'Rata-rata ' . $u; $kolom[] = $u . ' terendah'; $kolom[] = $u . ' tertinggi'; }
        foreach ($m['angka_kol'] as $k) if ($k !== $u) $kolom[] = 'Total ' . $k;
        if ($u !== null && $ada_wil) $kolom[] = 'Wilayah (' . $u . ')';
        if ($alasan_kol !== null) $kolom[] = $alasan_kol . ' (jumlah)';

        $b1 = array();
        foreach ($m['item'] as $it)
        {
            $baris_total += $it['n'];
            if ($it['arah'] !== null) $arah[$it['arah']]++;
            $row = array();
            if ($m['ada_kode']) $row[] = $it['kode'];
            $row[] = $it['nama'];
            foreach ($m['info_kol'] as $k) $row[] = $it['info'][$k];
            if ($u !== null) $row[] = $it['arah'] !== null ? self::sel_arah($it['arah']) : '';
            $row[] = array('t' => (string) $it['n'], 'v' => $it['n']);
            if ($u !== null) { $row[] = $nilai($it['avg'][$u]); $row[] = $nilai($it['min']); $row[] = $nilai($it['maks']); }
            foreach ($m['angka_kol'] as $k) if ($k !== $u) $row[] = $it['sum'][$k] === null ? '' : array('t' => self::fmt($it['sum'][$k]), 'v' => $it['sum'][$k]);

            $daftar_wil = array(); $tally = array();
            foreach ($it['baris'] as $b)
            {
                $v = $u !== null ? $b['angka'][$u] : null;
                if ($ada_wil && $b['wil'] !== '')
                {
                    if ($v !== null) $daftar_wil[] = array($b['wil'], $v);
                    if ( ! isset($per_wil[$b['wil']])) $per_wil[$b['wil']] = array('kom' => array(), 'naik' => 0, 'turun' => 0, 'stabil' => 0, 'jum' => 0.0, 'n' => 0);
                    $pw =& $per_wil[$b['wil']];
                    $pw['kom'][$it['nama']] = 1;
                    if ($v !== null) { $pw[self::arah_dari($v)]++; $pw['jum'] += $v; $pw['n']++; }
                    unset($pw);
                }
                if ($alasan_kol !== null && $b['teks'][$alasan_kol] !== '')
                {
                    foreach (preg_split('/\s*,\s*/', $b['teks'][$alasan_kol]) as $al)
                    {
                        if ($al === '') continue;
                        $tally[$al] = (isset($tally[$al]) ? $tally[$al] : 0) + 1;
                        if ( ! isset($per_alasan[$al])) $per_alasan[$al] = array('n' => 0, 'kom' => array());
                        $per_alasan[$al]['n']++; $per_alasan[$al]['kom'][$it['nama']] = 1;
                    }
                }
            }
            if ($u !== null && $ada_wil)
            {
                usort($daftar_wil, function ($x, $y) { return abs($y[1]) <=> abs($x[1]); });
                $t = array();
                foreach ($daftar_wil as $d) $t[] = $d[0] . ' ' . Sheet_rekap::fmt($d[1], $pct);
                $row[] = implode('; ', $t);
            }
            if ($alasan_kol !== null)
            {
                arsort($tally);
                $t = array();
                foreach ($tally as $al => $c) $t[] = $al . ' (' . $c . ')';
                $row[] = implode('; ', $t);
            }
            $b1[] = $row;
        }

        $kartu = array(array('Komoditas', count($m['item']), 'pada sheet ini'), array('Baris data', $baris_total, ''));
        if ($ada_wil) $kartu[] = array('Wilayah', count($per_wil), '');
        if ($u !== null)
        {
            $kartu[] = array('Naik', $arah['naik'], 'komoditas menurut ' . $u);
            $kartu[] = array('Turun', $arah['turun'], 'komoditas menurut ' . $u);
            $kartu[] = array('Stabil', $arah['stabil'], 'komoditas menurut ' . $u);
        }

        $bagian = array(array('judul' => 'Rekap per komoditas', 'pendek' => 'Komoditas',
            'ket' => 'Satu baris per komoditas' . ($u !== null ? '; arah ditentukan dari rata-rata ' . $u . ' di semua wilayah.' : '.'), 'kolom' => $kolom, 'baris' => $b1));
        if ($ada_wil)
        {
            ksort($per_wil);
            $b2 = array();
            foreach ($per_wil as $w => $c)
                $b2[] = array_merge(array($w, count($c['kom'])), $u !== null
                    ? array($c['naik'], $c['turun'], $c['stabil'], $c['n'] ? $nilai($c['jum'] / $c['n']) : '') : array());
            $bagian[] = array('judul' => 'Rekap per wilayah', 'pendek' => 'Wilayah', 'ket' => 'Jumlah komoditas yang tercatat di tiap ' . strtolower($m['kolom_wil']) . '.',
                'kolom' => array_merge(array($m['kolom_wil'], 'Jumlah komoditas'), $u !== null ? array('Naik', 'Turun', 'Stabil', 'Rata-rata ' . $u) : array()), 'baris' => $b2);
        }
        if ($alasan_kol !== null && $per_alasan)
        {
            uasort($per_alasan, function ($x, $y) { return $y['n'] <=> $x['n']; });
            $b3 = array();
            foreach ($per_alasan as $al => $c) $b3[] = array($al, $c['n'], implode(', ', array_keys($c['kom'])));
            $bagian[] = array('judul' => 'Rekap ' . strtolower($alasan_kol), 'pendek' => $alasan_kol,
                'ket' => 'Pilihan yang dipisah koma dihitung satu per satu.', 'kolom' => array($alasan_kol, 'Jumlah baris', 'Komoditas terkait'), 'baris' => $b3);
        }
        return array('kartu' => $kartu, 'bagian' => $bagian);
    }

    // ------------------------------------------------------------------
    // 3b. DAFTAR GABUNGAN: semua sheet menjadi satu daftar, kolom "Dokumen" = nama sheet (seperti HD / HKD)
    // ------------------------------------------------------------------

    /**
     * Baris seragam untuk satu sheet, apa pun formatnya.
     * @return array [ ['dok','kode','nama','arah','nilai','naik'=>[[wilayah,label]],'turun'=>[...],'stabil'=>int,'ket','wil'=>[wilayah=>arah],'ada'=>bool], ... ]
     */
    public function gabung(array $m, $dok)
    {
        $out = array();
        if ($m['layout'] === 'matriks')
        {
            foreach ($m['item'] as $it)
            {
                $naik = array(); $turun = array(); $st = 0; $wil = array();
                foreach ($it['wil'] as $w => $v)
                {
                    $a = $m['mode'] === 'arah' ? $v : self::arah_dari($v);
                    $lb = $m['mode'] === 'arah' ? '' : self::fmt($v);
                    $wil[$w] = $a;
                    if ($a === 'naik') $naik[] = array($w, $lb); elseif ($a === 'turun') $turun[] = array($w, $lb); else $st++;
                }
                if ($m['mode'] === 'arah') { $arah = $it['prov']; $nilai = ''; $ada = $it['prov'] !== null || $it['wil']; }
                else
                {
                    $v = array_values($it['wil']); $avg = $v ? array_sum($v) / count($v) : null;
                    $arah = self::arah_dari($avg); $nilai = $avg === null ? '' : self::fmt($avg); $ada = (bool) $v;
                }
                $out[] = array('dok' => $dok, 'kode' => $it['kode'], 'nama' => $it['nama'], 'arah' => $arah, 'nilai' => $nilai, 'naik' => $naik,
                               'turun' => $turun, 'stabil' => $st, 'ket' => implode(' | ', array_filter($it['teks'], 'strlen')), 'wil' => $wil, 'ada' => (bool) $ada);
            }
            return $out;
        }
        if ($m['layout'] !== 'daftar') return $out;
        $u = $m['utama']; $pct = $m['pct'];
        $alasan = null;
        foreach ($m['teks_kol'] as $k) if (preg_match('/alasan|sebab|penyebab/i', $k)) { $alasan = $k; break; }
        foreach ($m['item'] as $it)
        {
            $naik = array(); $turun = array(); $st = 0; $wil = array(); $tally = array();
            foreach ($it['baris'] as $b)
            {
                $v = $u !== null ? $b['angka'][$u] : null;
                if ($v !== null)
                {
                    $a = self::arah_dari($v);
                    if ($b['wil'] !== '') $wil[$b['wil']] = $a;
                    if ($a === 'naik') $naik[] = array($b['wil'], self::fmt($v, $pct)); elseif ($a === 'turun') $turun[] = array($b['wil'], self::fmt($v, $pct)); else $st++;
                }
                elseif ($b['wil'] !== '') $wil[$b['wil']] = 'stabil';
                if ($alasan !== null && $b['teks'][$alasan] !== '')
                    foreach (preg_split('/\s*,\s*/', $b['teks'][$alasan]) as $al) if ($al !== '') $tally[$al] = (isset($tally[$al]) ? $tally[$al] : 0) + 1;
            }
            arsort($tally); $t = array();
            foreach ($tally as $al => $c) $t[] = $al . ' (' . $c . ')';
            $out[] = array('dok' => $dok, 'kode' => $it['kode'], 'nama' => $it['nama'], 'arah' => $it['arah'],
                           'nilai' => ($u !== null && $it['avg'][$u] !== null) ? self::fmt($it['avg'][$u], $pct) : '',
                           'naik' => $naik, 'turun' => $turun, 'stabil' => $st, 'ket' => implode('; ', $t), 'wil' => $wil, 'ada' => true);
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // 4. UNTUK EXCEL
    // ------------------------------------------------------------------

    /** Satu bagian rekap menjadi baris Excel (baris pertama = judul kolom); angka tetap angka. */
    public static function ke_baris(array $bagian)
    {
        $out = array($bagian['kolom']);
        foreach ($bagian['baris'] as $r)
        {
            $b = array();
            foreach ($r as $c)
            {
                if (is_array($c)) $b[] = isset($c['v']) ? (is_numeric($c['v']) ? $c['v'] + 0 : $c['v']) : $c['t'];
                else $b[] = $c;
            }
            $out[] = $b;
        }
        return $out;
    }
}