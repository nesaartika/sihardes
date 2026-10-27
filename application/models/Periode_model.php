<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penyimpanan hasil pencermatan di MySQL/MariaDB.
 * Tabel: kabupaten, periode, komoditas, kecamatan, harga, alasan  (lihat database/struktur.sql)
 */
class Periode_model extends CI_Model
{
    public static $bulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                                 'September', 'Oktober', 'November', 'Desember');

    public function __construct()
    {
        parent::__construct();
        $this->siapkan_database();
    }

    // ------------------------------------------------------------------
    // Persiapan: buat database & tabel otomatis bila belum ada
    // ------------------------------------------------------------------

    private function siapkan_database()
    {
        $db = array();
        include APPPATH . 'config/database.php';
        $c = $db['default'];

        try
        {
            mysqli_report(MYSQLI_REPORT_OFF);
            $m = @new mysqli($c['hostname'], $c['username'], $c['password']);
            if ($m->connect_errno)
            {
                throw new RuntimeException($m->connect_error);
            }
            $m->query('CREATE DATABASE IF NOT EXISTS `' . $m->real_escape_string($c['database']) . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
            $m->close();
        }
        catch (Throwable $e)
        {
            show_error('MySQL tidak dapat dihubungi. Nyalakan <b>MySQL</b> di XAMPP Control Panel, lalu muat ulang halaman.<br>'
                . 'Cek juga pengaturan di <code>application/config/database.php</code>.<br><small>' . html_escape($e->getMessage()) . '</small>',
                500, 'Database belum aktif');
        }

        $this->load->database();
        if ( ! $this->db->table_exists('periode') || ! $this->db->table_exists('alasan'))
        {
            $this->buat_struktur();
        }
        // Database versi lama: periode yang sama sempat dibatasi unik -> longgarkan agar beberapa file per periode dapat disimpan
        if ($this->db->query("SHOW INDEX FROM periode WHERE Key_name = 'uq_bulan_tahun'")->num_rows() > 0)
        {
            $this->db->query('ALTER TABLE periode DROP INDEX uq_bulan_tahun, ADD INDEX idx_bulan_tahun (tahun, bulan)');
        }
        // Database masih kosong: muat data awal (September 2026) bila tersedia
        if ($this->db->count_all('periode') === 0 && is_file(FCPATH . 'database/data_awal.sql'))
        {
            $this->muat_data_awal();
        }
    }

    /** Memuat database/data_awal.sql per pernyataan (kecil-kecil) agar tidak melewati max_allowed_packet MySQL */
    private function muat_data_awal()
    {
        $conn = $this->db->conn_id;
        $fh = fopen(FCPATH . 'database/data_awal.sql', 'r');
        if ( ! $fh) return;

        // bersihkan sisa data setengah jalan dari percobaan sebelumnya
        $conn->query('SET FOREIGN_KEY_CHECKS=0');
        foreach (array('harga', 'kecamatan', 'komoditas', 'alasan', 'periode') as $t) $conn->query('DELETE FROM `' . $t . '`');

        $conn->begin_transaction();
        $stmt = '';
        $gagal = null;
        while (($baris = fgets($fh)) !== false)
        {
            $stmt .= $baris;
            if (substr(rtrim($baris), -1) !== ';') continue;   // pernyataan belum selesai
            if ( ! $conn->query($stmt)) { $gagal = $conn->error; break; }
            $stmt = '';
        }
        fclose($fh);

        if ($gagal !== null)
        {
            $conn->rollback();
            show_error('Gagal memuat data awal: ' . html_escape($gagal), 500, 'Database');
        }
        $conn->commit();
        $conn->query('SET FOREIGN_KEY_CHECKS=1');
    }

    private function buat_struktur()
    {
        $sql = file_get_contents(FCPATH . 'database/struktur.sql');
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (explode(";\n", $sql) as $q)
        {
            $q = trim($q);
            if ($q === '' || stripos($q, 'CREATE DATABASE') === 0 || stripos($q, 'USE ') === 0) continue;
            $this->db->query($q);
        }
    }

    // ------------------------------------------------------------------
    // Simpan
    // ------------------------------------------------------------------

    /** Memecah "Oktober 2026" menjadi [bulan(1-12), tahun] */
    public static function pecah_nama($nama)
    {
        if (preg_match('/^(\w+)\s+(\d{4})$/u', trim($nama), $m))
        {
            $i = array_search(ucfirst(strtolower($m[1])), self::$bulan, true);
            if ($i !== false) return array($i + 1, (int) $m[2]);
        }
        return array(0, 0);
    }

    /**
     * @param array $data ['id','nama','file_asal','dibuat'(d-m-Y H:i),'hasil'=>[ringkas,komoditas],'alasan'=>[kode|kab=>teks]]
     */
    public function simpan($id, array $data)
    {
        list($bulan, $tahun) = self::pecah_nama($data['nama']);
        $r = $data['hasil']['ringkas'];
        $dibuat = DateTime::createFromFormat('d-m-Y H:i', $data['dibuat']);

        $this->db->trans_start();

        $this->db->insert('periode', array(
            'id' => $id, 'nama' => $data['nama'], 'bulan' => $bulan, 'tahun' => $tahun,
            'file_asal' => $data['file_asal'], 'dibuat' => $dibuat ? $dibuat->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            'ambang_kec' => $r['opsi']['ambang_kec'], 'jml_baris' => $r['jml_baris'], 'jml_komoditas' => $r['jml_komoditas'],
            'naik' => $r['naik'], 'turun' => $r['turun'], 'stabil' => $r['stabil'],
            'perlu_konfirmasi' => $r['perlu_konfirmasi'], 'tidak_valid' => $r['tidak_valid'],
        ));

        $id_kom = (int) $this->db->query('SELECT COALESCE(MAX(id),0) m FROM komoditas')->row()->m;
        $id_kec = (int) $this->db->query('SELECT COALESCE(MAX(id),0) m FROM kecamatan')->row()->m;

        $b_kom = array(); $b_kec = array(); $b_hrg = array();
        foreach ($data['hasil']['komoditas'] as $urut => $k)
        {
            $id_kom++;
            $b_kom[] = array('id' => $id_kom, 'periode_id' => $id, 'urut' => $urut, 'kode' => $k['kode'], 'nama' => $k['nama'],
                'dokumen' => $k['dokumen'], 'prov_pct' => round($k['prov_pct'], 4), 'arah' => $k['arah'],
                'jumlah' => $k['jumlah'], 'tampil' => $k['tampil'] ? 1 : 0);
            foreach ($k['kec_semua'] as $c)
            {
                $id_kec++;
                $b_kec[] = array('id' => $id_kec, 'komoditas_id' => $id_kom, 'kab' => $c['kab'], 'kecamatan' => $c['kec'],
                    'persen' => round($c['persen'], 4), 'n' => $c['n'], 'flag' => $c['flag'] ? 1 : 0);
                foreach ($c['rinci'] as $q)
                {
                    $b_hrg[] = array('kecamatan_id' => $id_kec, 'kualitas' => $q[0], 'harga_lalu' => $q[1], 'harga_ini' => $q[2]);
                }
            }
        }
        $this->sisip('komoditas', $b_kom);
        $this->sisip('kecamatan', $b_kec);
        $this->sisip('harga', $b_hrg);

        $b_als = array();
        foreach ($data['alasan'] as $kunci => $teks)
        {
            list($kode, $kab) = explode('|', $kunci);
            $b_als[] = array('periode_id' => $id, 'kode' => $kode, 'kab' => (int) $kab, 'teks' => $teks);
        }
        $this->sisip('alasan', $b_als);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    private function sisip($tabel, array $baris)
    {
        foreach (array_chunk($baris, 500) as $potong) $this->db->insert_batch($tabel, $potong);
    }

    // ------------------------------------------------------------------
    // Baca
    // ------------------------------------------------------------------

    /** Memuat satu periode dalam struktur yang dipakai view (tanpa rincian kualitas). */
    public function muat($id)
    {
        if ( ! $this->id_valid($id)) return null;
        $p = $this->db->get_where('periode', array('id' => $id))->row_array();
        if ( ! $p) return null;

        $ringkas = array(
            'jml_baris' => (int) $p['jml_baris'], 'jml_komoditas' => (int) $p['jml_komoditas'],
            'naik' => (int) $p['naik'], 'turun' => (int) $p['turun'], 'stabil' => (int) $p['stabil'],
            'perlu_konfirmasi' => (int) $p['perlu_konfirmasi'], 'tidak_valid' => (int) $p['tidak_valid'],
            'opsi' => array_merge(Cermat_harga::$opsi_default, array('ambang_kec' => (float) $p['ambang_kec'])),
        );

        $kom = $this->db->order_by('urut')->get_where('komoditas', array('periode_id' => $id))->result_array();
        $kec = $this->db->query(
            'SELECT k.* FROM kecamatan k JOIN komoditas c ON c.id = k.komoditas_id WHERE c.periode_id = ? ORDER BY k.id', array($id)
        )->result_array();

        $per_kom = array();
        foreach ($kec as $c)
        {
            $persen = (float) $c['persen'];
            $per_kom[$c['komoditas_id']][] = array(
                'kab' => (int) $c['kab'], 'kec' => $c['kecamatan'], 'persen' => $persen, 'n' => (int) $c['n'],
                'flag' => (bool) $c['flag'], 'outlier' => abs($persen) >= 50, 'rinci' => array(),
            );
        }

        $hasil = array();
        foreach ($kom as $k)
        {
            $semua = isset($per_kom[$k['id']]) ? $per_kom[$k['id']] : array();
            $per_kab = array();
            foreach ($semua as $c) if ($c['flag']) $per_kab[$c['kab']][] = $c;
            ksort($per_kab);
            foreach ($per_kab as &$lst)
            {
                usort($lst, function ($x, $y) { return abs($y['persen']) <=> abs($x['persen']); });
            }
            unset($lst);

            $hasil[] = array(
                'dokumen' => $k['dokumen'], 'kode' => $k['kode'], 'nama' => $k['nama'],
                'label' => $k['nama'] . ' (' . $k['kode'] . ')', 'prov_pct' => (float) $k['prov_pct'],
                'arah' => $k['arah'], 'jumlah' => (int) $k['jumlah'], 'tampil' => (bool) $k['tampil'],
                'kab' => $per_kab, 'kec_semua' => $semua,
            );
        }

        return array(
            'id' => $p['id'], 'nama' => $p['nama'], 'file_asal' => $p['file_asal'],
            'dibuat' => date('d-m-Y H:i', strtotime($p['dibuat'])),
            'hasil' => array('ringkas' => $ringkas, 'komoditas' => $hasil),
            'alasan' => $this->alasan($id),
        );
    }

    /** Rincian harga per kualitas untuk satu komoditas: ["kab|Kecamatan" => [[kualitas, lalu, ini], ...]] */
    public function rinci_komoditas($periode_id, $kode)
    {
        $rows = $this->db->query(
            'SELECT k.kab, k.kecamatan, h.kualitas, h.harga_lalu, h.harga_ini
               FROM harga h
               JOIN kecamatan k ON k.id = h.kecamatan_id
               JOIN komoditas c ON c.id = k.komoditas_id
              WHERE c.periode_id = ? AND c.kode = ?
              ORDER BY h.id', array($periode_id, $kode)
        )->result_array();
        $out = array();
        foreach ($rows as $r)
        {
            $out[$r['kab'] . '|' . $r['kecamatan']][] = array($r['kualitas'], (float) $r['harga_lalu'], (float) $r['harga_ini']);
        }
        return $out;
    }

    /**
     * Baris data asli (sheet Data) untuk satu periode, dengan filter dan paginasi.
     * @param array $f ['q'=>teks, 'dok'=>HD|HKD, 'kab'=>int, 'ubah'=>naik|turun|tetap]
     * @return array ['baris'=>[...], 'total'=>int]
     */
    public function data_asli($periode_id, array $f, $limit, $offset)
    {
        $dari = ' FROM harga h JOIN kecamatan k ON k.id = h.kecamatan_id JOIN komoditas c ON c.id = k.komoditas_id WHERE c.periode_id = ?';
        $par = array($periode_id);
        if ( ! empty($f['dok']) && in_array($f['dok'], array('HD', 'HKD'), true)) { $dari .= ' AND c.dokumen = ?'; $par[] = $f['dok']; }
        if ( ! empty($f['kab'])) { $dari .= ' AND k.kab = ?'; $par[] = (int) $f['kab']; }
        if ( ! empty($f['q']))
        {
            $like = '%' . $this->db->escape_like_str($f['q']) . '%';
            $dari .= ' AND (c.nama LIKE ? OR c.kode LIKE ? OR k.kecamatan LIKE ? OR h.kualitas LIKE ?)';
            array_push($par, $like, $like, $like, $like);
        }
        if (isset($f['ubah']) && $f['ubah'] === 'naik')  $dari .= ' AND h.harga_ini > h.harga_lalu';
        if (isset($f['ubah']) && $f['ubah'] === 'turun') $dari .= ' AND h.harga_ini < h.harga_lalu';
        if (isset($f['ubah']) && $f['ubah'] === 'tetap') $dari .= ' AND h.harga_ini = h.harga_lalu';

        $total = (int) $this->db->query('SELECT COUNT(*) AS n' . $dari, $par)->row()->n;
        $baris = $this->db->query(
            'SELECT k.kab, k.kecamatan, c.kode, c.nama, c.dokumen, h.kualitas, h.harga_lalu, h.harga_ini' . $dari .
            ' ORDER BY c.urut, k.kab, k.kecamatan, h.id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset, $par
        )->result_array();
        return array('baris' => $baris, 'total' => $total);
    }

    /** Kode kabupaten yang ada pada satu periode. */
    public function kabupaten_periode($periode_id)
    {
        $out = array();
        foreach ($this->db->query('SELECT DISTINCT k.kab FROM kecamatan k JOIN komoditas c ON c.id = k.komoditas_id
                                    WHERE c.periode_id = ? ORDER BY k.kab', array($periode_id))->result_array() as $r) $out[] = (int) $r['kab'];
        return $out;
    }

    public function alasan($periode_id)
    {
        $out = array();
        foreach ($this->db->get_where('alasan', array('periode_id' => $periode_id))->result_array() as $a)
        {
            $out[$a['kode'] . '|' . $a['kab']] = $a['teks'];
        }
        return $out;
    }

    /** Daftar periode, terbaru dahulu. */
    public function daftar()
    {
        $rows = $this->db->query(
            'SELECT p.*, (SELECT COUNT(*) FROM alasan a WHERE a.periode_id = p.id) AS terisi
               FROM periode p ORDER BY p.tahun DESC, p.bulan DESC, p.id DESC'
        )->result_array();
        $out = array();
        foreach ($rows as $p)
        {
            $out[] = array(
                'id' => $p['id'], 'nama' => $p['nama'], 'file_asal' => $p['file_asal'],
                'dibuat' => date('d-m-Y H:i', strtotime($p['dibuat'])),
                'terisi' => (int) $p['terisi'],
                'ringkas' => array(
                    'jml_komoditas' => (int) $p['jml_komoditas'], 'naik' => (int) $p['naik'], 'turun' => (int) $p['turun'],
                    'stabil' => (int) $p['stabil'], 'perlu_konfirmasi' => (int) $p['perlu_konfirmasi'],
                ),
            );
        }
        return $out;
    }

    public function terbaru()
    {
        $r = $this->db->query('SELECT id FROM periode ORDER BY tahun DESC, bulan DESC, id DESC LIMIT 1')->row();
        return $r ? $r->id : null;
    }

    public function cari_nama($nama)
    {
        list($bulan, $tahun) = self::pecah_nama($nama);
        $r = $this->db->get_where('periode', array('bulan' => $bulan, 'tahun' => $tahun))->row();
        return $r ? $r->id : null;
    }

    // ------------------------------------------------------------------
    // Ubah
    // ------------------------------------------------------------------

    public function set_alasan($periode_id, $kode, $kab, $teks)
    {
        $w = array('periode_id' => $periode_id, 'kode' => $kode, 'kab' => (int) $kab);
        if ($teks === '')
        {
            $this->db->delete('alasan', $w);
        }
        else
        {
            $this->db->query('INSERT INTO alasan (periode_id, kode, kab, teks) VALUES (?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE teks = VALUES(teks)', array($periode_id, $kode, (int) $kab, $teks));
        }
        return (int) $this->db->where('periode_id', $periode_id)->count_all_results('alasan');
    }

    public function hapus($id)
    {
        if ($this->id_valid($id)) $this->db->delete('periode', array('id' => $id)); // anak tabel ikut terhapus (CASCADE)
    }

    private function id_valid($id)
    {
        return (bool) preg_match('/^[0-9]{8}_[0-9]{6}$/', (string) $id);
    }
}
