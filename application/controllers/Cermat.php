<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cermat extends MY_Controller
{
    private $dir_upload;
    private $aktif = null;   // id periode aktif
    private $data  = null;   // isi periode aktif

    public function __construct()
    {
        parent::__construct();
        $this->load->library('cermat_harga');
        $this->load->model('periode_model', 'periode');
        $this->load->model('catatan_model', 'catatan');
        $this->dir_upload = FCPATH . 'writable/upload/';
        if ( ! is_dir($this->dir_upload)) @mkdir($this->dir_upload, 0775, true);
        $this->config->load('cermat', FALSE, TRUE);

        // Akun kabupaten hanya boleh membuka Rekap, Data Asli (kabupatennya sendiri), dan menyimpan alasan
        if ( ! is_cli() && $this->kab_user() > 0)
        {
            if ( ! in_array($this->router->fetch_method(), array('rekap', 'data_asli', 'simpan_alasan'), true)) redirect('cermat/rekap');
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
        $d = $this->data;
        $this->render('cermat/dashboard', 'dashboard', array(
            'ringkas' => $d['hasil']['ringkas'],
            'dash'    => $this->cermat_harga->dashboard($d['hasil']),
            'terisi'  => count($d['alasan']),
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
        if ( ! $this->buka_periode()) { redirect($this->kab_user() ? 'auth/logout' : 'cermat/unggah'); }
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
            'daftar_kab' => $kab,
            'kunci_kab'  => $ku,
        ));
    }

    public function komoditas()
    {
        if ( ! $this->buka_periode()) redirect('cermat/unggah');
        $this->render('cermat/komoditas', 'komoditas', array('komoditas' => $this->data['hasil']['komoditas']));
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

    /** Data asli: isi semua sheet di file Excel apa adanya (satu tab per sheet), ditambah tab Hasil RH hitungan aplikasi */
    public function data_asli()
    {
        if ( ! $this->buka_periode()) { redirect($this->kab_user() ? 'auth/logout' : 'cermat/unggah'); }
        $ku  = $this->kab_user();
        $per = (int) $this->input->get('per');
        if ( ! in_array($per, array(25, 50, 100), true)) $per = 50;
        $hal = max(1, (int) $this->input->get('hal'));
        $q   = trim((string) $this->input->get('q'));

        // daftar tab: Data, Hasil RH (aplikasi), lalu sheet lain sesuai urutan di file
        $meta = $this->periode->daftar_sheet($this->aktif);
        $ada_data = false; foreach ($meta as $m) if ($m['nama'] === 'Data') $ada_data = true;
        if ( ! $meta)   // periode lama: hanya sheet Data yang tersimpan
        {
            $ada_data = true; $meta = array(array('nama' => 'Data', 'baris' => 0, 'kolom' => 0));
        }
        $tabs = array();
        if ($ada_data) { $tabs[] = 'Data'; if ( ! $ku) $tabs[] = 'Hasil RH'; }
        if ( ! $ku) foreach ($meta as $m) if ($m['nama'] !== 'Data') $tabs[] = $m['nama'];
        $sheet = (string) $this->input->get('sheet');
        if ( ! in_array($sheet, $tabs, true)) $sheet = $tabs ? $tabs[0] : 'Data';

        $data = array('tabs' => $tabs, 'sheet' => $sheet, 'q' => $q, 'per' => $per, 'hal' => $hal, 'kunci_kab' => $ku,
                      'mode' => 'data', 'f' => array('q' => $q, 'kab' => $ku ? (string) $ku : trim((string) $this->input->get('kab'))));

        if ($sheet === 'Hasil RH')
        {
            $rh = $this->periode->hasil_rh($this->aktif);
            if ($q !== '') $rh = array_values(array_filter($rh, function ($r) use ($q) {
                return stripos($r['kode'], $q) !== false || stripos($r['nama'], $q) !== false;
            }));
            $total = count($rh); $halaman = max(1, (int) ceil($total / $per)); if ($hal > $halaman) $hal = $halaman;
            $data = array_merge($data, array('mode' => 'rh', 'rh' => array_slice($rh, ($hal - 1) * $per, $per), 'total' => $total, 'hal' => $hal, 'halaman' => $halaman));
        }
        elseif ($sheet !== 'Data' && $tabs)
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

    /** Unduh hasil RH (hitungan aplikasi dari sheet Data) sebagai Excel dengan susunan kolom seperti sheet RH */
    public function unduh_rh($id = '')
    {
        $d = $this->periode->muat($id);
        if ( ! $d) show_404();
        require_once APPPATH . 'libraries/Xlsx_io.php';
        $baris = array(array('Kode Baru', 'Nama Komoditas', 'Rata-Rata Geometrik', 'Rata-Rata Geometrik Bulan Sebelumnya', 'RHt'));
        foreach ($this->periode->hasil_rh($id) as $r) $baris[] = array((string) $r['kode'], $r['nama'], (float) $r['g_ini'], (float) $r['g_lalu'], (float) $r['rht']);
        $tmp = tempnam(sys_get_temp_dir(), 'rh');
        Xlsx_io::tulis($tmp, array(array('nama' => 'RH', 'baris' => $baris, 'header' => true, 'beku' => true, 'filter' => true,
                                         'garis' => true, 'lebar' => array(16, 38, 22, 34, 12))));
        $nama = 'Hasil RH_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $d['nama']) . '.xlsx';
        $isi = file_get_contents($tmp); @unlink($tmp);
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

        $sandi = Pengguna_model::sandi_acak();
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

    public function pengguna_reset($id = 0)
    {
        if ($this->input->method() === 'post')
        {
            $this->load->model('pengguna_model', 'pengguna');
            $u = $this->pengguna->ambil($id);
            if ($u)
            {
                $sandi = Pengguna_model::sandi_acak();
                $this->pengguna->reset_sandi($id, $sandi);
                $this->catatan->catat('reset_sandi', $u['username']);
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

        $aman   = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($_FILES['berkas']['name'], PATHINFO_FILENAME));
        $simpan = $this->dir_upload . date('Ymd_His') . '_' . $aman . '.xlsx';
        if ( ! move_uploaded_file($_FILES['berkas']['tmp_name'], $simpan))
            return $this->galat('Tidak dapat menyimpan file ke folder writable/upload.');

        $daftar_bulan = array('Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
        $bulan = (string) $this->input->post('bulan');
        $tahun = (int) $this->input->post('tahun');
        if ( ! in_array($bulan, $daftar_bulan, true) || $tahun < 2000 || $tahun > 2100)
            return $this->galat('Pilih bulan dan tahun periode.');
        $opsi = array();
        $nama = $bulan . ' ' . $tahun;

        try
        {
            $id = $this->olah($simpan, $_FILES['berkas']['name'], $opsi, $nama);
        }
        catch (Exception $e)
        {
            return $this->galat($e->getMessage());
        }
        $r = $this->periode->muat($id);
        $this->catatan->catat('unggah', $nama . ' - ' . $_FILES['berkas']['name'] . ' (' . $r['hasil']['ringkas']['jml_komoditas'] . ' komoditas)');
        redirect('cermat?p=' . $id);
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
        $lama  = isset($this->data['alasan'][$kode . '|' . $kab]);
        $terisi = $this->periode->set_alasan($this->aktif, $kode, $kab, $teks);
        $jenis = $teks === '' ? 'dikosongkan' : ($lama ? 'diubah' : 'diisi');
        $this->catatan->catat('isi_alasan', $this->data['nama'] . ' - ' . $kode . ' - ' . Cermat_harga::nama_kab($kab) . ' (' . $jenis . ')');
        $this->output->set_output(json_encode(array('ok' => true, 'terisi' => $terisi)));
    }

    public function hapus($id = '')
    {
        if ($this->input->method() === 'post')
        {
            $d = $this->periode->muat($id);
            if ($d)
            {
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
        $tmp = tempnam(sys_get_temp_dir(), 'cmt');
        $this->cermat_harga->tulis_xlsx($d['hasil'], $tmp, '', $d['alasan']);
        $this->load->helper('download');
        $nama = 'Perubahan Sementara Harga Perdesaan_' . preg_replace('/[^A-Za-z0-9 ]+/', '', $d['nama']) . '.xlsx';
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
        $semua = Xlsx_io::daftar_sheet($path);
        $ada_data = false; foreach ($semua as $n) if (strcasecmp($n, 'Data') === 0) $ada_data = true;

        if ($ada_data)
        {
            $baris = $this->cermat_harga->baca_xlsx($path);
            $hasil = $this->cermat_harga->hitung($baris, $opsi);
            unset($baris);
            $mentah = $this->cermat_harga->baca_mentah($path);
        }
        else    // file tanpa sheet Data (mis. file lain): semua sheet disimpan dan tampil sebagai tab, rekap kosong
        {
            $hasil = $this->cermat_harga->hitung(array(), $opsi);
            $mentah = null;
        }
        $lain = $this->cermat_harga->baca_sheet_lain($path, 'Data');

        if ($nama === '') $nama = $this->tebak_periode($file_asal);
        $id = date('Ymd_His');
        $this->periode->simpan($id, array(
            'id' => $id, 'nama' => $nama, 'file_asal' => $file_asal, 'dibuat' => date('d-m-Y H:i'),
            'hasil' => $hasil, 'alasan' => array(),
        ));
        $meta = array();
        if ($mentah)
        {
            $this->periode->simpan_mentah($id, $mentah);
            $meta[] = array('nama' => 'Data', 'baris' => count($mentah['baris']), 'kolom' => count($mentah['kolom']));
        }
        foreach ($lain as $sh) $meta[] = array('nama' => $sh['nama'], 'baris' => count($sh['baris']), 'kolom' => $sh['kolom']);
        $this->periode->simpan_sheets($id, $lain, $meta);
        return $id;
    }

    private function tebak_periode($file)
    {
        if (preg_match('/(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)[ _\-]*(\d{4})/i', $file, $m))
            return ucfirst(strtolower($m[1])) . ' ' . $m[2];
        return pathinfo($file, PATHINFO_FILENAME);
    }

    /** Muat periode aktif dari ?p= atau yang terbaru. */
    private function buka_periode()
    {
        $id = $this->input->get('p');
        if ( ! $id) $id = $this->periode->terbaru();
        $d = $id ? $this->periode->muat($id) : null;
        if ( ! $d) { $this->aktif = null; $this->data = null; return false; }
        $this->aktif = $id;
        $this->data = $d;
        return true;
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
