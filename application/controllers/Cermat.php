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
        if ( ! $this->buka_periode()) redirect('cermat/unggah');
        $kab = array();
        foreach ($this->data['hasil']['komoditas'] as $k)
            foreach (array_keys($k['kab']) as $b) $kab[$b] = Cermat_harga::nama_kab($b);
        ksort($kab);
        $this->render('cermat/rekap', 'rekap', array(
            'komoditas'  => $this->data['hasil']['komoditas'],
            'alasan'     => $this->data['alasan'],
            'daftar_kab' => $kab,
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
            $ketik = trim((string) $this->input->post('konfirmasi'));
            if ($d && strcasecmp($ketik, $d['nama']) === 0)
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
        $baris = $this->cermat_harga->baca_xlsx($path);
        $hasil = $this->cermat_harga->hitung($baris, $opsi);
        unset($baris);

        if ($nama === '') $nama = $this->tebak_periode($file_asal);
        $id = date('Ymd_His');
        $this->periode->simpan($id, array(
            'id' => $id, 'nama' => $nama, 'file_asal' => $file_asal, 'dibuat' => date('d-m-Y H:i'),
            'hasil' => $hasil, 'alasan' => array(),
        ));
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
