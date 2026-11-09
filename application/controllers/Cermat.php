<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cermat extends MY_Controller
{
    private $dir_upload;
    private $aktif = null;   // id periode aktif
    private $data  = null;   // isi periode aktif
    private $catatan_unggah = null;   // pesan bila rekap tidak dapat dibuat dari file yang diunggah

    public function __construct()
    {
        parent::__construct();
        $this->load->library('cermat_harga');
        $this->load->model('periode_model', 'periode');
        $this->load->model('catatan_model', 'catatan');
        $this->dir_upload = FCPATH . 'writable/upload/';
        if ( ! is_dir($this->dir_upload)) @mkdir($this->dir_upload, 0775, true);
        $this->config->load('cermat', FALSE, TRUE);

        // Akun kabupaten hanya boleh membuka Rekap, Data, RH (kabupatennya sendiri untuk Data), dan menyimpan alasan
        if ( ! is_cli() && $this->kab_user() > 0)
        {
            if ( ! in_array($this->router->fetch_method(), array('rekap', 'data_asli', 'hasil_rh', 'simpan_alasan'), true)) redirect('cermat/rekap');
        }
    }

    /** Kode kabupaten milik akun yang sedang login (0 = admin / bukan akun kabupaten). */
    private function kab_user()
    {
        if (is_cli() || ! isset($this->session)) return 0;
        return $this->session->userdata('role') === 'kabupaten' ? (int) $this->session->userdata('kab') : 0;
    }

    // ---------------------------------------------------------------
    // Halaman
    // ---------------------------------------------------------------

    public function index()
    {
        if (is_cli()) { echo "Untuk terminal gunakan: php cermat.php \"<file.xlsx>\" [\"Bulan Tahun\"]\n"; return; }
        if ( ! $this->buka_periode()) { redirect('cermat/unggah'); }
        if ( ! $this->punya_hasil()) return $this->kosong('dashboard');
        $d = $this->data;
        $this->render('cermat/dashboard', 'dashboard', array(
            'ringkas' => $d['hasil']['ringkas'],
            'dash'    => $this->cermat_harga->dashboard($d['hasil']),
            'terisi'  => $this->hitung_terisi($d),
            'total_konfirmasi' => $this->hitung_baris_konfirmasi($d['hasil']),
            'progres_kab' => $this->progres_kabupaten($d),
        ));
    }

    public function unggah()
    {
        $this->buka_periode();
        $this->render('cermat/unggah', 'unggah', array('opsi' => Cermat_harga::$opsi_default, 'galat' => null));
    }

    public function rekap()
    {
        if ( ! $this->buka_periode())
        {
            if ($this->kab_user()) return $this->render('cermat/kosong', 'rekap', array());
            redirect('cermat/unggah');
        }
        if ( ! $this->punya_hasil())    // file bukan tabel harga: rekap dibuat dari tiap sheet (admin); akun kabupaten tetap tidak melihat isi file ini
            return $this->rekap_sheet();
        $this->lengkapi_otomatis();
        $ku = $this->kab_user();
        $komoditas = $this->data['hasil']['komoditas'];
        if ($ku)   // akun kabupaten: hanya baris kabupatennya sendiri
        {
            $baru = array();
            foreach ($komoditas as $k)
            {
                if ( ! isset($k['kab'][$ku])) continue;
                $k['kab'] = array($ku => $k['kab'][$ku]);
                $baru[] = $k;
            }
            $komoditas = $baru;
        }
        $kab = array();
        foreach ($komoditas as $k) foreach (array_keys($k['kab']) as $b) $kab[$b] = Cermat_harga::nama_kab($b);
        ksort($kab);
        $this->render('cermat/rekap', 'rekap', array(
            'komoditas'  => $komoditas,
            'alasan'     => $this->data['alasan'],
            'meta'       => $this->data['alasan_meta'],
            'daftar_kab' => $kab,
            'kunci_kab'  => $ku,
            'ambang'     => $this->data['hasil']['ringkas']['opsi']['ambang_kec'],
        ));
    }

    public function komoditas()
    {
        if ( ! $this->buka_periode()) redirect('cermat/unggah');
        if ( ! $this->punya_hasil()) return $this->komoditas_sheet();    // file bukan tabel harga: daftar komoditas dibuat dari tiap sheet
        $this->lengkapi_otomatis();
        $kom = $this->data['hasil']['komoditas'];
        $kab_semua = array();
        foreach ($kom as $i => $k)
        {
            $info = array();    // kabupaten yang punya data untuk komoditas ini: [kab => [jumlah kecamatan perlu konfirmasi, ditugaskan?]]
            foreach ($k['kec_semua'] as $c)
            {
                if ( ! isset($info[$c['kab']])) $info[$c['kab']] = array(0, ! empty($this->data['penugasan'][$k['kode']][$c['kab']]));
                if ($c['flag']) $info[$c['kab']][0]++;
                $kab_semua[$c['kab']] = Cermat_harga::nama_kab($c['kab']);
            }
            if ( ! empty($this->data['penugasan'][$k['kode']]))     // ditugaskan ke kabupaten yang tidak punya data komoditas ini (mis. komoditas kosong)
            {
                foreach ($this->data['penugasan'][$k['kode']] as $kb => $_) if ( ! isset($info[$kb])) $info[$kb] = array(0, true);
            }
            ksort($info);
            $kom[$i]['kab_info'] = $info;
        }
        ksort($kab_semua);
        $this->render('cermat/komoditas', 'komoditas', array('komoditas' => $kom, 'daftar_kab' => $kab_semua));
    }

    /**
     * Periode yang diunggah sebelum perbaikan ini belum memuat komoditas yang hanya ada di sheet RH (tanpa baris harga di sheet Data).
     * Dilengkapi otomatis satu kali dari file Excel asli, lalu ditandai; alasan dan penugasan yang sudah ada tidak disentuh.
     */
    private function lengkapi_otomatis()
    {
        if ($this->kab_user() || ! $this->aktif || ! $this->data || ! empty($this->data['komoditas_lengkap'])) return;
        $f = ! empty($this->data['berkas_asli']) ? $this->dir_upload . basename($this->data['berkas_asli']) : '';
        if ($f === '' || ! is_file($f)) return;      // file asli tidak ada: tombol "Lengkapi komoditas kosong" tetap bisa dipakai setelah unggah ulang
        $daftar = $this->cermat_harga->baca_daftar_komoditas($f);
        if ($daftar) $this->periode->tambah_komoditas_kosong($this->aktif, $daftar);
        else         $this->periode->tandai_lengkap($this->aktif);
        $this->buka_periode();                        // muat ulang agar komoditas baru ikut tampil
    }

    /** Melengkapi periode yang sedang dibuka dengan komoditas kosong dari sheet RH file aslinya (tanpa unggah ulang, alasan tidak hilang). */
    public function lengkapi_komoditas()
    {
        if ($this->input->method() !== 'post' || ! $this->buka_periode() || $this->kab_user()) redirect('cermat/komoditas');
        $id = $this->aktif;
        $d = $this->periode->muat($id);
        $f = ($d && ! empty($d['berkas_asli'])) ? $this->dir_upload . basename($d['berkas_asli']) : '';
        if ($f === '' || ! is_file($f))
        {
            $this->session->set_flashdata('pesan', 'File Excel asli periode ini tidak ditemukan di folder writable/upload, jadi tidak bisa dilengkapi. Unggah ulang file Excel-nya.');
            redirect('cermat/komoditas?p=' . $id);
        }
        $daftar = $this->cermat_harga->baca_daftar_komoditas($f);
        if ( ! $daftar)
        {
            $this->session->set_flashdata('pesan', 'Daftar komoditas pada sheet RH tidak terbaca dari file asli (sheet RH tidak ada atau kolom Kode Baru / Nama Komoditas tidak ditemukan).');
            redirect('cermat/komoditas?p=' . $id);
        }
        $n = $this->periode->tambah_komoditas_kosong($id, $daftar);
        $this->catatan->catat('unggah', $d['nama'] . ' - melengkapi ' . $n . ' komoditas tanpa data dari sheet RH');
        $this->session->set_flashdata('pesan', $n > 0 ? $n . ' komoditas tanpa data ditambahkan (dari ' . count($daftar) . ' komoditas di sheet RH). Cari di kolom pencarian atau centang "Tampilkan juga yang tidak berubah".'
                                                      : 'Semua ' . count($daftar) . ' komoditas di sheet RH sudah tercatat pada periode ini.');
        redirect('cermat/komoditas?p=' . $id);
    }

    public function detail($kode = '')
    {
        if ( ! $this->buka_periode()) redirect('cermat/unggah');
        foreach ($this->data['hasil']['komoditas'] as $k)
        {
            if ((string) $k['kode'] === (string) $kode)
            {
                $rinci = $this->periode->rinci_komoditas($this->aktif, $k['kode']);
                foreach ($k['kec_semua'] as $i => $c)
                {
                    $kk = $c['kab'] . '|' . $c['kec'];
                    $k['kec_semua'][$i]['rinci'] = isset($rinci[$kk]) ? $rinci[$kk] : array();
                }
                return $this->render('cermat/detail', 'komoditas', array('k' => $k));
            }
        }
        show_404();
    }

    public function aktivitas()
    {
        $this->buka_periode();
        $this->render('cermat/aktivitas', 'aktivitas', array('log' => $this->catatan->terbaru(500)));
    }

    /** Data asli: isi semua sheet di file Excel apa adanya (satu tab per sheet) */
    public function data_asli()
    {
        if ( ! $this->buka_periode())
        {
            if ($this->kab_user()) return $this->render('cermat/kosong', 'data', array());
            redirect('cermat/unggah');
        }
        $ku  = $this->kab_user();
        $per = (int) $this->input->get('per');
        if ( ! in_array($per, array(25, 50, 100), true)) $per = 50;
        $hal = max(1, (int) $this->input->get('hal'));
        $q   = trim((string) $this->input->get('q'));

        // daftar tab: Data, lalu sheet lain sesuai urutan di file
        $meta = $this->periode->daftar_sheet($this->aktif);
        $ada_data = false; foreach ($meta as $m) if ($m['nama'] === 'Data') $ada_data = true;
        if ( ! $meta)   // periode lama: hanya sheet Data yang tersimpan
        {
            $ada_data = true; $meta = array(array('nama' => 'Data', 'baris' => 0, 'kolom' => 0));
        }
        // Menu Data hanya berisi sheet Data (khusus data). File yang bukan tabel harga: semua sheet tampil sebagai tab.
        $tabs = array();
        if ($ada_data) $tabs[] = 'Data';
        else foreach ($meta as $m) $tabs[] = $m['nama'];
        $sheet = (string) $this->input->get('sheet');
        if ( ! in_array($sheet, $tabs, true)) $sheet = $tabs ? $tabs[0] : 'Data';

        $data = array('tabs' => $tabs, 'sheet' => $sheet, 'q' => $q, 'per' => $per, 'hal' => $hal, 'kunci_kab' => $ku,
                      'mode' => 'data', 'f' => array('q' => $q, 'kab' => $ku ? (string) $ku : trim((string) $this->input->get('kab'))));

        if ($sheet !== 'Data' && $tabs)
        {
            $d = $this->periode->data_sheet($this->aktif, $sheet, $q, $per, ($hal - 1) * $per);
            $halaman = max(1, (int) ceil($d['total'] / $per));
            if ($hal > $halaman) { $hal = $halaman; $d = $this->periode->data_sheet($this->aktif, $sheet, $q, $per, ($hal - 1) * $per); }
            $kol = 0; foreach ($meta as $m) if ($m['nama'] === $sheet) $kol = (int) $m['kolom'];
            $data = array_merge($data, array('mode' => 'sheet', 'baris' => $d['baris'], 'total' => $d['total'], 'kolom_jml' => $kol, 'hal' => $hal, 'halaman' => $halaman));
        }
        else
        {
            $d = $this->periode->data_mentah($this->aktif, $data['f'], $per, ($hal - 1) * $per);
            $halaman = max(1, (int) ceil($d['total'] / $per));
            if ($hal > $halaman) { $hal = $halaman; $d = $this->periode->data_mentah($this->aktif, $data['f'], $per, ($hal - 1) * $per); }
            $data = array_merge($data, array('kolom' => $d['kolom'], 'baris' => $d['baris'], 'total' => $d['total'], 'ada' => $d['ada'],
                                             'kab_nilai' => $d['kab_nilai'], 'hal' => $hal, 'halaman' => $halaman));
        }
        $this->render('cermat/data_asli', 'data', $data);
    }

    /** Hasil RH: dihitung aplikasi dari data mentah (sheet Data); menjadi dasar arah naik/turun/stabil di Rekap */
    public function hasil_rh()
    {
        if ( ! $this->buka_periode()) { if ($this->kab_user()) return $this->render('cermat/kosong', 'rh', array()); redirect('cermat/unggah'); }
        if ( ! $this->punya_hasil()) return $this->kosong('rh');
        $per = (int) $this->input->get('per');
        if ( ! in_array($per, array(25, 50, 100), true)) $per = 50;
        $hal = max(1, (int) $this->input->get('hal'));
        $q   = trim((string) $this->input->get('q'));

        $tabel = $this->periode->tabel_rh($this->aktif);
        $rh = $tabel['baris'];
        if ($q !== '') $rh = array_values(array_filter($rh, function ($r) use ($q) {
            return stripos($r['lama'], $q) !== false || stripos($r['baru'], $q) !== false || stripos($r['nama'], $q) !== false;
        }));
        $total = count($rh); $halaman = max(1, (int) ceil($total / $per)); if ($hal > $halaman) $hal = $halaman;
        $this->render('cermat/hasil_rh', 'rh', array(
            'rh' => array_slice($rh, ($hal - 1) * $per, $per), 'bulan' => $tabel['bulan'], 'q' => $q, 'per' => $per,
            'hal' => $hal, 'halaman' => $halaman, 'total' => $total,
        ));
    }

    /** Unduh hasil RH (hitungan aplikasi dari sheet Data) sebagai Excel dengan susunan kolom seperti sheet RH */
    public function unduh_rh($id = '')
    {
        $d = $this->periode->muat($id);
        if ( ! $d) show_404();
        require_once APPPATH . 'libraries/Xlsx_io.php';
        $tabel = $this->periode->tabel_rh($id);
        $kanan = ! empty($tabel['bulan']);
        $kepala = array('Kode Komoditas', 'Kode Baru', 'Nama Komoditas',
                        'Rata-Rata Geometrik Kode Baru', 'Rata-Rata Geometrik Bulan Sebelumnya', 'Rata-Rata Geometrik', 'Rata-Rata Geometrik Bulan Sebelumnya', 'RHt');
        if ($kanan) $kepala = array_merge($kepala, array('Column1', 'Kode Komoditas', 'Kode Baru', 'Kode Gabung', 'Nama Komoditas'), $tabel['bulan']);
        $baris = array($kepala);
        $n = function ($x) { return $x === null ? null : (float) $x; };
        $t = function ($x) { return $x !== '' ? $x : null; };
        foreach ($tabel['baris'] as $r)
        {
            $b = array($t($r['lama']), $t($r['baru']), $r['nama'], $n($r['f']), $n($r['g']), $n($r['h']), $n($r['i']), $n($r['rht']));
            if ($kanan) $b = array_merge($b, array((int) $r['k'], $t($r['l']), $t($r['m']), $t($r['n']), $t($r['o'])), $r['bln']);
            $baris[] = $b;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'rh');
        Xlsx_io::tulis($tmp, array(array('nama' => 'RH', 'baris' => $baris, 'gaya_rh' => true, 'geser' => 2,
                                         'lebar' => array(17.6, 12, 38, 16, 16, 16, 16, 11, 9, 17.6, 12, 12, 38, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9))));
        $nama = 'RH_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $d['nama']) . '.xlsx';
        $isi = file_get_contents($tmp); @unlink($tmp);
        $this->catatan->catat('unduh', $d['nama'] . ' - ' . $nama);
        $this->load->helper('download');
        force_download($nama, $isi);
    }

    // ---------------------------------------------------------------
    // File bukan tabel harga (mis. sheet HD, HKD, HPB, HK, Rekonsiliasi):
    // Komoditas dan Rekap dibuat dari tiap sheet, formatnya mengikuti isi sheet itu (lihat library Sheet_rekap)
    // ---------------------------------------------------------------

    /** Model satu sheet (komoditas + nilai per wilayah) dari isi sheet yang tersimpan saat unggah. */
    private function model_sheet($id, $nama)
    {
        $d = $this->periode->data_sheet($id, $nama, '', 30000, 0);
        return $this->sheet_rekap->susun($this->sheet_rekap->analisis($d['baris']));
    }

    /**
     * Semua sheet digabung menjadi satu daftar; kolom "Dokumen" = nama sheet (HD, HKD, HK, HPB, ...), apa pun isi file-nya.
     * @return array|null null bila periode ini tidak punya sheet tersimpan (periode lama)
     */
    private function gabung_sheet()
    {
        $this->load->library('sheet_rekap');
        $meta = $this->periode->daftar_sheet($this->aktif);
        if ( ! $meta) return null;
        $dok = array(); $baris = array(); $gagal = array();
        foreach ($meta as $s)
        {
            $m = $this->model_sheet($this->aktif, $s['nama']);
            if ($m['layout'] === 'tidak_ada') { $gagal[$s['nama']] = $m['sebab']; continue; }
            $dok[] = $s['nama'];
            foreach ($this->sheet_rekap->gabung($m, $s['nama']) as $r) $baris[] = $r;
        }
        return array('dok' => $dok, 'baris' => $baris, 'gagal' => $gagal);
    }

    private function komoditas_sheet()
    {
        $g = $this->gabung_sheet();
        if ($g === null) return $this->kosong('komoditas');
        $this->render('cermat/sheet_komoditas', 'komoditas', array('g' => $g));
    }

    private function rekap_sheet()
    {
        $g = $this->gabung_sheet();
        if ($g === null) return $this->kosong('rekap');
        $this->render('cermat/sheet_rekap', 'rekap', array('g' => $g));
    }

    /** Unduh rekap sheet (?sheet=) atau semua sheet sebagai Excel: tiap bagian rekap menjadi satu sheet, susunan kolom sama dengan yang tampil di Rekap. */
    public function unduh_rekap($id = '')
    {
        if ($this->kab_user()) show_404();
        $d = $this->periode->muat($id);
        if ( ! $d) show_404();
        $this->load->library('sheet_rekap');
        require_once APPPATH . 'libraries/Xlsx_io.php';

        $pilih = (string) $this->input->get('sheet');
        $sheets = array(); $dipakai = array();
        foreach ($this->periode->daftar_sheet($id) as $s)
        {
            if ($pilih !== '' && $s['nama'] !== $pilih) continue;
            $m = $this->model_sheet($id, $s['nama']);
            if ($m['layout'] === 'tidak_ada') continue;
            $rk = $this->sheet_rekap->rekap($m);
            foreach ($rk['bagian'] as $b)
            {
                if ( ! $b['baris']) continue;
                $nm = trim(mb_substr(preg_replace('/[\[\]\*\?\/\\\\:]+/', ' ', $s['nama'] . ' ' . $b['pendek']), 0, 28));
                $asli = $nm; $i = 2;
                while (isset($dipakai[strtolower($nm)])) $nm = $asli . ' ' . $i++;
                $dipakai[strtolower($nm)] = 1;
                $baris = Sheet_rekap::ke_baris($b);
                $lebar = array();
                foreach ($baris as $r) foreach ($r as $ci => $v)
                {
                    $w = min(60, max(10, mb_strlen((string) $v) + 2));
                    if ( ! isset($lebar[$ci]) || $w > $lebar[$ci]) $lebar[$ci] = $w;
                }
                ksort($lebar);
                $sheets[] = array('nama' => $nm, 'baris' => $baris, 'header' => true, 'beku' => true, 'filter' => true, 'garis' => true, 'lebar' => array_values($lebar));
            }
        }
        // sheet pertama: daftar gabungan semua dokumen (sama dengan halaman Rekap)
        $g = $this->gabung_sheet();
        if ($g && $g['baris'])
        {
            $gb = array(array('Dokumen', 'Kode', 'Komoditas', 'Arah', 'Perubahan', 'Wilayah naik', 'Wilayah turun', 'Jumlah wilayah stabil', 'Keterangan'));
            $nm = function ($l) { $t = array(); foreach ($l as $x) $t[] = $x[0] . ($x[1] !== '' ? ' ' . $x[1] : ''); return implode(', ', $t); };
            foreach ($g['baris'] as $r)
            {
                if ($pilih !== '' && $r['dok'] !== $pilih) continue;
                if ( ! $r['ada']) continue;
                $gb[] = array($r['dok'], $r['kode'], $r['nama'], $r['arah'] !== null ? ucfirst($r['arah']) : '', $r['nilai'], $nm($r['naik']), $nm($r['turun']), $r['stabil'], $r['ket']);
            }
            array_unshift($sheets, array('nama' => 'Rekap Gabungan', 'baris' => $gb, 'header' => true, 'beku' => true, 'filter' => true, 'garis' => true,
                                         'lebar' => array(12, 12, 32, 9, 12, 50, 50, 12, 50)));
        }
        if ( ! $sheets) show_404();

        $tmp = tempnam(sys_get_temp_dir(), 'rkp');
        Xlsx_io::tulis($tmp, $sheets);
        $nama = 'Rekap_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $d['nama']) . ($pilih !== '' ? '_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $pilih) : '') . '.xlsx';
        $isi = file_get_contents($tmp);
        @unlink($tmp);
        $this->catatan->catat('unduh', $d['nama'] . ' - ' . $nama);
        $this->load->helper('download');
        force_download($nama, $isi);
    }

    // ---------------------------------------------------------------
    // Kelola pengguna (admin)
    // ---------------------------------------------------------------

    public function pengguna()
    {
        $this->load->model('pengguna_model', 'pengguna');
        $this->buka_periode();
        $sandi = $this->session->flashdata('sandi_baru');
        $this->render('cermat/pengguna', 'pengguna', array(
            'akun' => $this->pengguna->daftar(), 'kab_daftar' => Cermat_harga::$kabupaten,
            'sandi_baru' => $sandi ? json_decode($sandi, true) : null,
            'domain' => (string) $this->config->item('domain_email'), 'galat' => $this->session->flashdata('galat_akun'),
        ));
    }

    public function pengguna_tambah()
    {
        if ($this->input->method() !== 'post') redirect('cermat/pengguna');
        $this->load->model('pengguna_model', 'pengguna');
        $email = strtolower(trim((string) $this->input->post('email')));
        $nama  = trim((string) $this->input->post('nama'));
        $role  = (string) $this->input->post('role');
        $kab   = (int) $this->input->post('kab');
        $domain = strtolower((string) $this->config->item('domain_email'));

        $galat = null;
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) $galat = 'Format email tidak valid.';
        elseif ($domain !== '' && substr($email, -strlen('@' . $domain)) !== '@' . $domain) $galat = 'Email harus memakai domain @' . $domain . '.';
        elseif ($nama === '') $galat = 'Nama harus diisi.';
        elseif ( ! in_array($role, array('admin', 'kabupaten'), true)) $galat = 'Role tidak valid.';
        elseif ($role === 'kabupaten' && ! isset(Cermat_harga::$kabupaten[$kab])) $galat = 'Pilih kabupaten untuk akun kabupaten.';
        elseif ($this->pengguna->email_ada($email)) $galat = 'Email itu sudah terdaftar.';

        if ($galat) { $this->session->set_flashdata('galat_akun', $galat); redirect('cermat/pengguna'); }

        $sandi = (string) $this->input->post('sandi');    // wajib diisi admin
        if (strlen($sandi) < $this->sandi_minimal())
        {
            $this->session->set_flashdata('galat_akun', 'Kata sandi minimal ' . $this->sandi_minimal() . ' karakter.');
            redirect('cermat/pengguna');
        }
        $this->pengguna->tambah($email, $nama, $role, $role === 'kabupaten' ? $kab : null, $sandi);
        $this->catatan->catat('buat_akun', $email . ' (' . ($role === 'kabupaten' ? Cermat_harga::nama_kab($kab) : 'admin') . ')');
        $this->session->set_flashdata('sandi_baru', json_encode(array('email' => $email, 'sandi' => $sandi)));
        redirect('cermat/pengguna');
    }

    public function pengguna_aktif($id = 0)
    {
        if ($this->input->method() === 'post')
        {
            $this->load->model('pengguna_model', 'pengguna');
            $u = $this->pengguna->ambil($id);
            if ($u && (int) $u['id'] !== (int) $this->session->userdata('user_id'))
            {
                $baru = $u['aktif'] ? 0 : 1;
                $this->pengguna->set_aktif($id, $baru);
                $this->catatan->catat('ubah_akun', $u['username'] . ($baru ? ' diaktifkan' : ' dinonaktifkan'));
            }
        }
        redirect('cermat/pengguna');
    }

    /** Edit akun: GET menampilkan form, POST menyimpan perubahan. */
    public function pengguna_edit($id = 0)
    {
        $this->load->model('pengguna_model', 'pengguna');
        $u = $this->pengguna->ambil($id);
        if ( ! $u) show_404();
        $saya = (int) $u['id'] === (int) $this->session->userdata('user_id');
        $galat = null;
        $f = array('email' => $u['username'], 'nama' => $u['nama'], 'role' => $u['role'], 'kab' => (int) $u['kab']);

        if ($this->input->method() === 'post')
        {
            $f = array('email' => strtolower(trim((string) $this->input->post('email'))), 'nama' => trim((string) $this->input->post('nama')),
                       'role' => (string) $this->input->post('role'), 'kab' => (int) $this->input->post('kab'));
            $domain = strtolower((string) $this->config->item('domain_email'));
            if ( ! filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $galat = 'Format email tidak valid.';
            elseif ($domain !== '' && substr($f['email'], -strlen('@' . $domain)) !== '@' . $domain) $galat = 'Email harus memakai domain @' . $domain . '.';
            elseif ($f['nama'] === '') $galat = 'Nama harus diisi.';
            elseif ( ! in_array($f['role'], array('admin', 'kabupaten'), true)) $galat = 'Role tidak valid.';
            elseif ($f['role'] === 'kabupaten' && ! isset(Cermat_harga::$kabupaten[$f['kab']])) $galat = 'Pilih kabupaten untuk akun kabupaten.';
            elseif ($this->pengguna->email_ada_selain($f['email'], $u['id'])) $galat = 'Email itu sudah dipakai akun lain.';
            elseif ($u['role'] === 'admin' && $f['role'] !== 'admin' && ($saya || $this->pengguna->jml_admin_aktif($u['id']) < 1))
                $galat = $saya ? 'Role akun Anda sendiri tidak bisa diubah.' : 'Harus ada minimal satu admin aktif.';

            if ( ! $galat)
            {
                $this->pengguna->ubah($u['id'], $f['email'], $f['nama'], $f['role'], $f['kab']);
                $this->catatan->catat('edit_akun', $u['username'] . ($f['email'] !== $u['username'] ? ' -> ' . $f['email'] : '') . ' (' . ($f['role'] === 'kabupaten' ? Cermat_harga::nama_kab($f['kab']) : 'admin') . ')');
                if ($saya) $this->session->set_userdata(array('username' => $f['email'], 'nama' => $f['nama']));
                redirect('cermat/pengguna');
            }
        }
        $this->buka_periode();
        $this->render('cermat/pengguna_edit', 'pengguna', array(
            'u' => $u, 'f' => $f, 'saya' => $saya, 'galat' => $galat, 'kab_daftar' => Cermat_harga::$kabupaten,
            'domain' => (string) $this->config->item('domain_email'),
        ));
    }

    public function pengguna_hapus($id = 0)
    {
        if ($this->input->method() === 'post')
        {
            $this->load->model('pengguna_model', 'pengguna');
            $u = $this->pengguna->ambil($id);
            if ($u && (int) $u['id'] !== (int) $this->session->userdata('user_id'))
            {
                if ($u['role'] === 'admin' && $u['aktif'] && $this->pengguna->jml_admin_aktif($u['id']) < 1)
                {
                    $this->session->set_flashdata('galat_akun', 'Harus ada minimal satu admin aktif.');
                }
                else
                {
                    $this->pengguna->hapus($u['id']);
                    $this->catatan->catat('hapus_akun', $u['username']);
                }
            }
        }
        redirect('cermat/pengguna');
    }

    public function aktivitas_reset()
    {
        if ($this->input->method() === 'post')
        {
            $this->catatan->kosongkan();
            $this->catatan->catat('reset_log', 'Log aktivitas dikosongkan');
        }
        redirect('cermat/aktivitas');
    }

    private function sandi_minimal()
    {
        return max(6, (int) ($this->config->item('sandi_minimal') ?: 8));
    }

    /** Admin menetapkan kata sandi akun (terutama akun kabupaten): POST sandi. */
    public function pengguna_sandi($id = 0)
    {
        if ($this->input->method() === 'post')
        {
            $this->load->model('pengguna_model', 'pengguna');
            $u = $this->pengguna->ambil($id);
            $sandi = (string) $this->input->post('sandi');
            if ( ! $u) { /* akun tidak ada */ }
            elseif (strlen($sandi) < $this->sandi_minimal()) $this->session->set_flashdata('galat_akun', 'Kata sandi minimal ' . $this->sandi_minimal() . ' karakter.');
            else
            {
                $this->pengguna->reset_sandi($id, $sandi);
                $this->catatan->catat('reset_sandi', $u['username'] . ' (ditetapkan admin)');
                $this->session->set_flashdata('sandi_baru', json_encode(array('email' => $u['username'], 'sandi' => $sandi)));
            }
        }
        redirect('cermat/pengguna');
    }

    public function riwayat()
    {
        $this->buka_periode();
        $this->render('cermat/riwayat', 'riwayat', array('daftar' => $this->periode->daftar()));
    }

    // ---------------------------------------------------------------
    // Aksi
    // ---------------------------------------------------------------

    public function proses()
    {
        if ($this->input->method() !== 'post') redirect('cermat/unggah');

        if (empty($_FILES['berkas']['name']))
            return $this->galat('File belum dipilih, atau ukuran file melebihi post_max_size di php.ini (ubah menjadi 40M lalu restart Apache).');
        if ($_FILES['berkas']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['berkas']['error'] === UPLOAD_ERR_FORM_SIZE)
            return $this->galat('Ukuran file melebihi upload_max_filesize di php.ini (ubah menjadi 40M lalu restart Apache).');
        if ($_FILES['berkas']['error'] !== UPLOAD_ERR_OK)
            return $this->galat('File gagal diunggah (kode ' . $_FILES['berkas']['error'] . ').');
        if (strtolower(pathinfo($_FILES['berkas']['name'], PATHINFO_EXTENSION)) !== 'xlsx')
            return $this->galat('Format file harus .xlsx');

        // periode: dari isian lanjutan bila diisi, kalau kosong dibaca otomatis dari nama file
        $bulan = (string) $this->input->post('bulan');
        $tahun = (int) $this->input->post('tahun');
        if ($bulan === '' || $tahun === 0)
        {
            $p = Cermat_harga::baca_periode($_FILES['berkas']['name']);
            if ($p === null)
                return $this->galat('Bulan dan tahun tidak terbaca dari nama file. Pilih bulan dan tahun secara manual di formulir, atau ubah nama file, misalnya Harmonisasi_Kalbar_September_2026.xlsx');
            if ($bulan === '')  $bulan = $p[0];
            if ($tahun === 0)   $tahun = $p[1];
        }
        $daftar_bulan = array('Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
        if ( ! in_array($bulan, $daftar_bulan, true) || $tahun < 2000 || $tahun > 2100)
            return $this->galat('Bulan dan tahun periode tidak valid.');
        $opsi = array();
        $ambang = (float) str_replace(',', '.', (string) $this->input->post('ambang'));
        if ($ambang > 0 && $ambang <= 100) $opsi['ambang_kec'] = $ambang;    // batas perubahan kecamatan (%), bawaan 5
        $nama = $bulan . ' ' . $tahun;

        $aman   = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($_FILES['berkas']['name'], PATHINFO_FILENAME));
        $simpan = $this->dir_upload . date('Ymd_His') . '_' . $aman . '.xlsx';
        if ( ! move_uploaded_file($_FILES['berkas']['tmp_name'], $simpan))
            return $this->galat('Tidak dapat menyimpan file ke folder writable/upload.');

        try
        {
            $id = $this->olah($simpan, $_FILES['berkas']['name'], $opsi, $nama);
        }
        catch (Exception $e)
        {
            return $this->galat($e->getMessage());
        }
        $this->session->set_userdata('periode_pilihan', $id);     // admin yang mengunggah langsung membuka periode barunya
        if ($this->catatan_unggah)
        {
            $this->session->set_flashdata('pesan', 'File tersimpan. ' . $this->catatan_unggah);
        }
        $r = $this->periode->muat($id);
        $this->catatan->catat('unggah', $nama . ' - ' . $_FILES['berkas']['name'] . ' (' . $r['hasil']['ringkas']['jml_komoditas'] . ' komoditas)');
        redirect($r['hasil']['ringkas']['jml_komoditas'] > 0 ? 'cermat?p=' . $id : 'cermat/data_asli?p=' . $id);
    }

    /**
     * Mengarahkan komoditas dan kabupaten yang harus mengisi alasan (AJAX).
     * Parameter: kode[]; dipilih = 1|0; kab (opsional: hanya kabupaten itu); massal = 1 (tombol "pilih semua": komoditas tanpa kecamatan
     * yang perlu dikonfirmasi dilewati).
     * Tanpa kab: dipilih=1 menugaskan semua kabupaten yang kecamatannya perlu dikonfirmasi (bila tidak ada dan bukan massal: semua kabupaten
     * yang punya data); dipilih=0 membatalkan semua penugasan komoditas itu.
     */
    public function pilih_komoditas()
    {
        $this->output->set_content_type('application/json');
        if ($this->input->method() !== 'post' || ! $this->buka_periode() || $this->kab_user())
        {
            return $this->output->set_status_header(400)->set_output(json_encode(array('ok' => false)));
        }
        $kode = $this->input->post('kodes');           // daftar dipisah koma (aman dari batas max_input_vars PHP)
        $kode = ($kode !== null && $kode !== '') ? explode(',', (string) $kode) : $this->input->post('kode');
        if ( ! is_array($kode)) $kode = array($kode);
        $bersih = array();
        foreach ($kode as $c) { $c = preg_replace('/[^A-Za-z0-9]/', '', (string) $c); if ($c !== '') $bersih[] = $c; }
        $bersih = array_values(array_unique($bersih));
        $nilai  = $this->input->post('dipilih') ? true : false;
        $massal = $this->input->post('massal') ? true : false;
        $kab_p  = $this->input->post('kab');
        $kab    = ($kab_p !== null && $kab_p !== '') ? (int) $kab_p : null;

        // kabupaten yang punya data per komoditas: [kode => [kab => jumlah kecamatan perlu konfirmasi]]
        $ada = array(); $dikenal = array();
        foreach ($this->data['hasil']['komoditas'] as $k)
        {
            $dikenal[$k['kode']] = 1;
            foreach ($k['kec_semua'] as $c)
            {
                if ( ! isset($ada[$k['kode']][$c['kab']])) $ada[$k['kode']][$c['kab']] = 0;
                if ($c['flag']) $ada[$k['kode']][$c['kab']]++;
            }
        }
        $pasangan = array();
        foreach ($bersih as $c)
        {
            if ( ! isset($dikenal[$c])) continue;
            if ( ! isset($ada[$c])) $ada[$c] = array();
            if ($kab !== null)
            {
                // komoditas kosong / kabupaten tanpa data tetap boleh diarahkan lewat kotak satuan (alasan diisi tanpa angka)
                if (( ! $massal || ! $ada[$c]) && isset(Cermat_harga::$kabupaten[$kab])) { $pasangan[] = array($c, $kab); continue; }     // komoditas kosong selalu boleh
                // tombol massal per kabupaten hanya menugaskan komoditas yang kecamatannya berubah (oranye); kotak satuan boleh yang mana saja
                if (isset($ada[$c][$kab]) && ( ! $massal || ! $nilai || $ada[$c][$kab] > 0)) $pasangan[] = array($c, $kab);
                continue;
            }
            if ($nilai)
            {
                $target = array_keys(array_filter($ada[$c]));
                if ( ! $target && ! $massal) $target = array_keys($ada[$c]);
                if ( ! $target && ! $ada[$c]) $target = array_keys(Cermat_harga::$kabupaten);      // komoditas kosong (tanpa data kabupaten): diarahkan ke semua kabupaten
                foreach ($target as $kb) $pasangan[] = array($c, (int) $kb);
            }
            elseif (isset($this->data['penugasan'][$c]))
            {
                foreach (array_keys($this->data['penugasan'][$c]) as $kb) $pasangan[] = array($c, (int) $kb);
            }
        }
        $total = $pasangan ? $this->periode->set_penugasan($this->aktif, $pasangan, $nilai) : $this->periode->hitung_dipilih($this->aktif);

        $peta = array();
        $semua = $this->periode->penugasan($this->aktif);
        foreach ($bersih as $c) $peta[$c] = isset($semua[$c]) ? array_map('intval', array_keys($semua[$c])) : array();
        $ket = $kab !== null ? Cermat_harga::nama_kab($kab) : 'semua kabupaten terkait';
        $this->catatan->catat('pilih_komoditas', $this->data['nama'] . ' - ' . count($bersih) . ' komoditas ' . ($nilai ? 'diarahkan ke ' : 'dibatalkan dari ') . $ket . ' (' . count($pasangan) . ' penugasan; komoditas dikonfirmasi sekarang: ' . $total . ')');
        $this->output->set_output(json_encode(array('ok' => true, 'dipilih' => $total, 'peta' => $peta)));
    }

    /** Simpan alasan (AJAX) */
    public function simpan_alasan()
    {
        $this->output->set_content_type('application/json');
        if ($this->input->method() !== 'post' || ! $this->buka_periode())
        {
            return $this->output->set_status_header(400)->set_output(json_encode(array('ok' => false)));
        }
        $kode  = preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input->post('kode'));
        $teks  = trim((string) $this->input->post('teks'));
        $kab   = (int) $this->input->post('kab');
        if ($this->kab_user() > 0 && $kab !== $this->kab_user())
        {
            return $this->output->set_status_header(403)->set_output(json_encode(array('ok' => false, 'pesan' => 'Bukan kabupaten Anda.')));
        }
        // hanya komoditas x kabupaten yang diarahkan admin provinsi yang boleh diisi
        if (empty($this->data['penugasan'][$kode][$kab]))
        {
            return $this->output->set_status_header(403)->set_output(json_encode(array('ok' => false, 'pesan' => 'Komoditas ini tidak diminta untuk dikonfirmasi oleh kabupaten tersebut.')));
        }
        $lama  = isset($this->data['alasan'][$kode . '|' . $kab]);
        $oleh  = trim((string) $this->input->post('oleh'));     // "Diisi oleh": diketik sendiri oleh pengisi
        $terisi = $this->periode->set_alasan($this->aktif, $kode, $kab, $teks, $oleh);
        $jenis = $teks === '' ? 'dikosongkan' : ($lama ? 'diubah' : 'diisi');
        $this->catatan->catat('isi_alasan', $this->data['nama'] . ' - ' . $kode . ' - ' . Cermat_harga::nama_kab($kab) . ' (' . $jenis . ')');
        $this->output->set_output(json_encode(array('ok' => true, 'terisi' => $terisi,
            'waktu' => ($teks === '' && $oleh === '') ? '' : date('d-m-Y H:i'))));
    }

    public function hapus($id = '')
    {
        if ($this->input->method() === 'post')
        {
            $d = $this->periode->muat($id);
            if ($d)
            {
                if ( ! empty($d['berkas_asli']) && is_file($this->dir_upload . basename($d['berkas_asli']))) @unlink($this->dir_upload . basename($d['berkas_asli']));
                $this->periode->hapus($id);
                $this->catatan->catat('hapus_periode', $d['nama'] . ' - ' . $d['file_asal']);
            }
        }
        redirect('cermat/riwayat');
    }

    public function unduh($id = '')
    {
        $d = $this->periode->muat($id);
        if ( ! $d) show_404();
        $this->terapkan_pilihan($d);
        $tmp = tempnam(sys_get_temp_dir(), 'cmt');
        $this->cermat_harga->tulis_xlsx($d['hasil'], $tmp, '', $d['alasan']);
        $this->load->helper('download');
        $nama = 'Perubahan Sementara Harga Perdesaan_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $d['nama']) . '.xlsx';
        $isi = file_get_contents($tmp);
        @unlink($tmp);
        $this->catatan->catat('unduh', $d['nama'] . ' - ' . $nama);
        force_download($nama, $isi);
    }

    /**
     * Unduh isi file apa adanya (menu Data): satu sheet bila ?sheet= diisi, selain itu semua sheet yang tersimpan.
     * Dipakai untuk file yang bukan tabel harga, sehingga formatnya mengikuti file yang diunggah (bukan Rekap Konfirmasi).
     */
    public function unduh_data($id = '')
    {
        if ($this->kab_user()) show_404();
        $d = $this->periode->muat($id);
        if ( ! $d) show_404();

        // tanpa ?sheet=: kembalikan file Excel yang diunggah apa adanya (format sama persis dengan aslinya)
        if ((string) $this->input->get('sheet') === '' && ! empty($d['berkas_asli']))
        {
            $f = $this->dir_upload . basename($d['berkas_asli']);
            if (is_file($f))
            {
                $this->catatan->catat('unduh', $d['nama'] . ' - ' . $d['file_asal'] . ' (file asli)');
                $this->load->helper('download');
                force_download($d['file_asal'], file_get_contents($f));
            }
        }
        require_once APPPATH . 'libraries/Xlsx_io.php';

        // sel tersimpan sebagai teks; angka murni dikembalikan menjadi angka (kode dengan awalan 0 atau lebih dari 15 digit tetap teks)
        $sel = function ($v) {
            if ($v === null || $v === '') return null;
            if (is_string($v) && preg_match('/^-?(0|[1-9][0-9]{0,14})(\.[0-9]+)?$/', $v)) return strpos($v, '.') !== false ? (float) $v : (int) $v;
            return $v;
        };
        $pilih = (string) $this->input->get('sheet');
        $meta  = $this->periode->daftar_sheet($id);
        if ( ! $meta) $meta = array(array('nama' => 'Data'));    // periode lama: hanya sheet Data yang tersimpan

        $sheets = array();
        foreach ($meta as $m)
        {
            if ($pilih !== '' && $m['nama'] !== $pilih) continue;
            $baris = array();
            if ($m['nama'] === 'Data')
            {
                $x = $this->periode->data_mentah($id, array('q' => '', 'kab' => ''), 1000000, 0);
                if (empty($x['ada'])) continue;
                $baris[] = $x['kolom'];
                foreach ($x['baris'] as $r) $baris[] = array_map($sel, $r);
            }
            else
            {
                $x = $this->periode->data_sheet($id, $m['nama'], '', 1000000, 0);
                foreach ($x['baris'] as $r)    // nomor baris asli dipertahankan; baris kosong diisi array kosong
                {
                    while (count($baris) < $r['r'] - 1) $baris[] = array();
                    $baris[$r['r'] - 1] = array_map($sel, is_array($r['v']) ? $r['v'] : array());
                }
            }
            $sheets[] = array('nama' => $m['nama'], 'baris' => $baris, 'beku' => $m['nama'] === 'Data');
        }
        if ( ! $sheets) show_404();

        $tmp = tempnam(sys_get_temp_dir(), 'dta');
        Xlsx_io::tulis($tmp, $sheets);
        $this->load->helper('download');
        $nama = 'Data_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $d['nama']) . ($pilih !== '' ? '_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $pilih) : '') . '.xlsx';
        $isi = file_get_contents($tmp);
        @unlink($tmp);
        $this->catatan->catat('unduh', $d['nama'] . ' - ' . $nama);
        force_download($nama, $isi);
    }

    // ---------------------------------------------------------------
    // Terminal: dipanggil lewat  php cermat.php "file.xlsx" "Oktober 2026"
    // ---------------------------------------------------------------

    public function jalankan()
    {
        if ( ! is_cli() || empty($GLOBALS['cermat_cli'])) show_404();
        $a = $GLOBALS['cermat_cli'];
        $berkas = $a['berkas'];
        $nama = $a['nama'] !== '' ? $a['nama'] : $this->tebak_periode(basename($berkas));
        list($bln, $thn) = Periode_model::pecah_nama($nama);
        if ( ! $bln)
        {
            echo "Periode tidak dikenali dari nama file. Tulis periodenya, mis.:\n";
            echo "  php cermat.php \"{$berkas}\" \"Oktober 2026\"\n";
            return;
        }
        echo "Membaca {$berkas} ...\n";
        try
        {
            $id = $this->olah($berkas, basename($berkas), array(), $nama);
        }
        catch (Exception $e)
        {
            echo 'GAGAL: ' . $e->getMessage() . "\n";
            return;
        }
        if ($this->catatan_unggah) echo "Catatan: {$this->catatan_unggah}\n";
        $d = $this->periode->muat($id);
        $r = $d['hasil']['ringkas'];
        $this->catatan->catat('unggah', $nama . ' - ' . basename($berkas) . ' (' . $r['jml_komoditas'] . ' komoditas)', 'terminal');
        echo "{$r['jml_baris']} baris | {$r['jml_komoditas']} komoditas (naik {$r['naik']}, turun {$r['turun']}, stabil {$r['stabil']})\n";
        echo "Perlu konfirmasi: {$r['perlu_konfirmasi']} komoditas.\n";
        echo "Periode \"{$nama}\" tersimpan di database MySQL. Buka dashboard di browser.\n";
    }

    // ---------------------------------------------------------------
    // Internal
    // ---------------------------------------------------------------

    private function olah($path, $file_asal, array $opsi, $nama)
    {
        $opsi = array_filter($opsi, function ($v) { return $v !== 0.0; });
        require_once APPPATH . 'libraries/Xlsx_io.php';
        $sheet_data = $this->cermat_harga->cari_sheet_harga($path);   // sheet berisi tabel harga (nama bebas), atau null

        if ($sheet_data !== null)
        {
            try
            {
                $baris = $this->cermat_harga->baca_xlsx($path, $sheet_data);
                $hasil = $this->cermat_harga->hitung($baris, $opsi, $this->cermat_harga->baca_daftar_komoditas($path));
                unset($baris);
            }
            catch (RuntimeException $e)    // sheet Data berformat lain: tetap disimpan apa adanya, rekap tidak dibuat
            {
                $hasil = $this->cermat_harga->hitung(array(), $opsi);
                $this->catatan_unggah = $e->getMessage();
            }
            $mentah = $this->cermat_harga->baca_mentah($path, $sheet_data);
        }
        else    // file lain (bukan tabel harga): semua sheet disimpan dan tampil sebagai tab di menu Data; RH dan rekap tidak dibuat
        {
            $hasil = $this->cermat_harga->hitung(array(), $opsi);
            $mentah = null;
            $this->catatan_unggah = 'File ini bukan tabel harga (kolom Kabupaten, Nama Kecamatan, Kode Komoditas, Nama Komoditas, Nama Kualitas, Harga Bulan Ini, Harga Bulan Sebelumnya), '
                . 'jadi RH tidak dibuat. Menu Data, Komoditas, dan Rekap dibuat dari tiap sheet menurut formatnya masing-masing.';
        }
        $jenis = $this->deteksi_jenis($file_asal, $sheet_data !== null);
        // file tabel harga: yang disimpan hanya Data (data awal) dan sheet RH (daftar kode & tabel bulanan untuk menu RH); file lain: semua sheet
        // file tabel harga: bila simpan_semua_sheet = TRUE semua sheet disimpan (tab Data sama dengan file asli); bila FALSE hanya sheet RH
        $semua_sheet = $this->config->item('simpan_semua_sheet') === TRUE;
        $lain = $this->cermat_harga->baca_sheet_lain($path, $sheet_data !== null ? $sheet_data : '', 120, ($sheet_data !== null && ! $semua_sheet) ? array('RH') : null);

        if ($nama === '') $nama = $this->tebak_periode($file_asal);
        $id = date('Ymd_His');
        $this->periode->simpan($id, array(
            'id' => $id, 'nama' => $nama, 'file_asal' => $file_asal, 'dibuat' => date('d-m-Y H:i'),
            'hasil' => $hasil, 'alasan' => array(), 'jenis' => $jenis, 'berkas_asli' => $this->simpan_berkas_asli($path),
        ));
        if ($mentah) $this->periode->simpan_mentah($id, $mentah);
        // daftar tab mengikuti urutan sheet pada file yang diunggah
        $per_nama = array();
        foreach ($lain as $sh) $per_nama[strtolower($sh['nama'])] = $sh;
        $meta = array();
        foreach (Xlsx_io::daftar_sheet($path) as $nm)
        {
            if ($mentah && $sheet_data !== null && strcasecmp($nm, $sheet_data) === 0)
                $meta[] = array('nama' => 'Data', 'baris' => count($mentah['baris']), 'kolom' => count($mentah['kolom']));
            elseif (isset($per_nama[strtolower($nm)]))
                $meta[] = array('nama' => $per_nama[strtolower($nm)]['nama'], 'baris' => count($per_nama[strtolower($nm)]['baris']), 'kolom' => $per_nama[strtolower($nm)]['kolom']);
        }
        $this->periode->simpan_sheets($id, $lain, $meta);
        return $id;
    }

    private function tebak_periode($file)
    {
        $p = Cermat_harga::baca_periode($file);
        return $p ? $p[0] . ' ' . $p[1] : pathinfo($file, PATHINFO_FILENAME);
    }

    /** Jenis data ditebak dari isi file: tabel harga = Harga Perdesaan; kata kunci pada nama file untuk jenis lain; selain itu Lainnya. */
    private function deteksi_jenis($file, $tabel_harga)
    {
        $n = strtolower($file);
        if (strpos($n, 'konsumen') !== false) return 'Harga Konsumen';
        if (strpos($n, 'perkotaan') !== false) return 'Harga Perkotaan';
        return $tabel_harga ? 'Harga Perdesaan' : 'Lainnya';
    }

    /** Nama file asli (di folder upload) yang disimpan bersama periode; file dari terminal disalin dulu ke folder upload. */
    private function simpan_berkas_asli($path)
    {
        $dir = realpath($this->dir_upload);
        if ($dir !== false && dirname(realpath($path)) === $dir) return basename($path);
        $nama = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($path, PATHINFO_FILENAME)) . '.xlsx';
        return @copy($path, $this->dir_upload . $nama) ? $nama : null;
    }

    /** Teks siapa yang mengisi alasan, mis. "Budi (BPS Kab. Sambas)" atau "Admin (Admin Provinsi)". */
    private function label_pengguna()
    {
        $nama = trim((string) $this->session->userdata('nama'));
        $ku = $this->kab_user();
        if ($ku)
        {
            $kn = Cermat_harga::nama_kab($ku);
            return stripos($nama, $kn) !== false ? $nama : $nama . ' (BPS ' . ($ku < 70 ? 'Kab. ' : '') . $kn . ')';
        }
        return stripos($nama, 'admin') !== false ? $nama : $nama . ' (Admin Provinsi)';
    }

    /** Apakah periode aktif berisi tabel harga (ada komoditas)? File lain (bukan tabel harga) hanya punya menu Data. */
    private function punya_hasil()
    {
        return ! empty($this->data['hasil']['komoditas']);
    }

    /** Menu samping tidak berubah-ubah: halaman yang belum bisa dibuka menampilkan keterangan, bukan pindah halaman. */
    private function kosong($menu)
    {
        $this->render('cermat/kosong', $menu, array('tanpa_tabel_harga' => true));
    }

    /**
     * Muat periode aktif. Urutan: ?p= (pilihan yang diklik pengguna, diingat di sesi), lalu pilihan terakhir di sesi, lalu yang terbaru.
     * Jadi unggahan baru dari admin tidak menggeser periode yang sedang dibuka pengguna lain.
     */
    private function buka_periode()
    {
        // Akun kabupaten otomatis ikut unggahan terbaru: pilihan periode lama di sesi gugur bila sejak itu ada unggahan baru.
        $ku = $this->kab_user();
        $terbaru = $this->periode->terbaru();
        if ($ku && ! is_cli() && (string) $this->session->userdata('periode_pilihan_ref') !== (string) $terbaru)
        {
            $this->session->unset_userdata(array('periode_pilihan', 'periode_pilihan_ref'));
        }

        $id = (string) $this->input->get('p');
        $d = $id !== '' ? $this->periode->muat($id) : null;
        if ($d)
        {
            // hanya pilihan yang diklik pengguna di daftar periode (pilih=1) yang diingat; tautan menu tidak mengunci periode
            if ( ! is_cli() && $this->input->get('pilih'))
            {
                $this->session->set_userdata('periode_pilihan', $id);
                if ($ku) $this->session->set_userdata('periode_pilihan_ref', (string) $terbaru);
            }
        }
        else
        {
            $id = is_cli() ? '' : (string) $this->session->userdata('periode_pilihan');
            $d = $id !== '' ? $this->periode->muat($id) : null;
            if ( ! $d)
            {
                $id = $terbaru;
                $d = $id ? $this->periode->muat($id) : null;
            }
        }
        if ( ! $d) { $this->aktif = null; $this->data = null; return false; }
        $this->aktif = $id;
        $this->terapkan_pilihan($d);
        $this->data = $d;
        return true;
    }

    /**
     * Hanya komoditas x kabupaten yang diarahkan admin provinsi yang dikonfirmasi: yang tidak diarahkan tidak punya baris kabupaten
     * (tetap muncul di Rekap admin dan Excel dengan kolom Kabupaten/Alasan kosong; akun kabupaten hanya melihat yang diarahkan ke kabupatennya).
     * Data aslinya disimpan di 'kab_asli'.
     * Bila admin mengarahkan kabupaten yang tidak punya kecamatan di atas ambang, semua kecamatan kabupaten itu ditampilkan.
     */
    private function terapkan_pilihan(array &$d)
    {
        $pen = isset($d['penugasan']) ? $d['penugasan'] : array();
        $n = 0;
        foreach ($d['hasil']['komoditas'] as &$k)
        {
            $k['kab_asli'] = $k['kab'];
            $baru = array();
            foreach ((isset($pen[$k['kode']]) ? $pen[$k['kode']] : array()) as $kb => $_)
            {
                if (isset($k['kab_asli'][$kb])) { $baru[$kb] = $k['kab_asli'][$kb]; continue; }
                $lst = array();
                foreach ($k['kec_semua'] as $c) if ((int) $c['kab'] === (int) $kb) $lst[] = $c;
                usort($lst, function ($x, $y) { return abs($y['persen']) <=> abs($x['persen']); });
                $baru[$kb] = $lst;      // bisa kosong (komoditas tanpa data / kabupaten tanpa data): tetap diminta mengisi alasan
            }
            ksort($baru);
            $k['kab'] = $baru;
            $k['dipilih'] = ! empty($baru);
            $k['tampil'] = TRUE;                 // semua komoditas pada file tetap tampil; kolom kabupaten kosong bila tidak diarahkan
            if ( ! empty($baru)) $n++;           // yang dihitung "perlu konfirmasi" hanya yang diarahkan ke kabupaten
        }
        unset($k);
        $d['hasil']['ringkas']['perlu_konfirmasi'] = $n;
    }

    private function hitung_terisi($d)
    {
        $ada = array();
        foreach ($d['hasil']['komoditas'] as $k) foreach ($k['kab'] as $kb => $_) $ada[$k['kode'] . '|' . $kb] = 1;
        return count(array_intersect_key($d['alasan'], $ada));
    }

    private function hitung_baris_konfirmasi($hasil)
    {
        $n = 0;
        foreach ($hasil["komoditas"] as $k) $n += count($k["kab"]);
        return $n;
    }

    /** Progres pengisian alasan per kabupaten: [nama => [total, terisi]] */
    private function progres_kabupaten($d)
    {
        $out = array();
        foreach ($d['hasil']['komoditas'] as $k)
        {
            foreach ($k['kab'] as $kab => $lst)
            {
                $nm = Cermat_harga::nama_kab($kab);
                if ( ! isset($out[$nm])) $out[$nm] = array(0, 0);
                $out[$nm][0]++;
                if ( ! empty($d['alasan'][$k['kode'] . '|' . $kab])) $out[$nm][1]++;
            }
        }
        ksort($out);
        return $out;
    }

    private function render($view, $menu, array $data = array())
    {
        $data['menu']    = $menu;
        $data['p']       = $this->aktif;
        $data['periode'] = $this->data;
        $data['daftar_periode'] = $this->periode->daftar();
        $data['csrf'] = array($this->security->get_csrf_token_name(), $this->security->get_csrf_hash());
        $this->load->view('layout/head', $data);
        $this->load->view($view, $data);
        $this->load->view('layout/foot', $data);
    }

    private function galat($pesan)
    {
        $this->buka_periode();
        $this->render('cermat/unggah', 'unggah', array('opsi' => Cermat_harga::$opsi_default, 'galat' => $pesan));
    }
}