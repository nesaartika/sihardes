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
        // Database versi lama: simpan juga isi sheet Data apa adanya
        if ( ! $this->db->field_exists('kolom_excel', 'periode'))
        {
            $this->db->query('ALTER TABLE periode ADD COLUMN kolom_excel TEXT NULL');
        }
        if ( ! $this->db->table_exists('data_excel'))
        {
            $kol = '';
            for ($i = 1; $i <= 15; $i++) $kol .= '`c' . $i . '` VARCHAR(255) DEFAULT NULL, ';
            $this->db->query('CREATE TABLE IF NOT EXISTS `data_excel` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `periode_id` VARCHAR(15) NOT NULL,
                `baris` INT UNSIGNED NOT NULL, ' . $kol . '
                PRIMARY KEY (`id`),
                KEY `idx_periode_baris` (`periode_id`,`baris`),
                CONSTRAINT `fk_data_excel_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        // Database versi lama: simpan Hasil RH (rata-rata geometrik) per komoditas
        if ( ! $this->db->field_exists('g_ini', 'komoditas'))
        {
            $this->db->query('ALTER TABLE komoditas ADD COLUMN g_ini DOUBLE NULL, ADD COLUMN g_lalu DOUBLE NULL');
        }
        // Database versi lama: admin dapat memilih komoditas yang akan dikonfirmasi
        if ( ! $this->db->field_exists('dipilih', 'komoditas'))
        {
            $this->db->query('ALTER TABLE komoditas ADD COLUMN dipilih TINYINT(1) NOT NULL DEFAULT 1');
        }
        // Database versi lama: simpan semua sheet di file Excel
        if ( ! $this->db->field_exists('daftar_sheet', 'periode'))
        {
            $this->db->query('ALTER TABLE periode ADD COLUMN daftar_sheet TEXT NULL');
        }
        if ( ! $this->db->table_exists('sheet_excel'))
        {
            $this->db->query('CREATE TABLE IF NOT EXISTS `sheet_excel` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `periode_id` VARCHAR(15) NOT NULL,
                `sheet` VARCHAR(64) NOT NULL,
                `baris` INT UNSIGNED NOT NULL,
                `isi` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_sheet` (`periode_id`,`sheet`,`baris`),
                CONSTRAINT `fk_sheet_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        // Database versi lama: periode yang sama sempat dibatasi unik -> longgarkan agar beberapa file per periode dapat disimpan
        if ($this->db->query("SHOW INDEX FROM periode WHERE Key_name = 'uq_bulan_tahun'")->num_rows() > 0)
        {
            $this->db->query('ALTER TABLE periode DROP INDEX uq_bulan_tahun, ADD INDEX idx_bulan_tahun (tahun, bulan)');
        }
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
                'dokumen' => $k['dokumen'], 'prov_pct' => round($k['prov_pct'], 4),
                'g_ini' => isset($k['g_ini']) ? $k['g_ini'] : null, 'g_lalu' => isset($k['g_lalu']) ? $k['g_lalu'] : null, 'arah' => $k['arah'],
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
                'g_ini' => isset($k['g_ini']) && $k['g_ini'] !== null ? (float) $k['g_ini'] : null,
                'g_lalu' => isset($k['g_lalu']) && $k['g_lalu'] !== null ? (float) $k['g_lalu'] : null,
                'arah' => $k['arah'], 'jumlah' => (int) $k['jumlah'], 'tampil' => (bool) $k['tampil'],
                'dipilih' => isset($k['dipilih']) ? (bool) $k['dipilih'] : true,
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

    /** Menyimpan isi sheet Data apa adanya (lihat Cermat_harga::baca_mentah). */
    public function simpan_mentah($periode_id, array $mentah)
    {
        $this->db->trans_start();
        $this->db->update('periode', array('kolom_excel' => json_encode($mentah['kolom'], JSON_UNESCAPED_UNICODE)), array('id' => $periode_id));
        $n = count($mentah['kolom']);
        $batch = array();
        foreach ($mentah['baris'] as $b)
        {
            $row = array('periode_id' => $periode_id, 'baris' => $b['r']);
            for ($i = 0; $i < $n; $i++) $row['c' . ($i + 1)] = $b['v'][$i];
            $batch[] = $row;
        }
        foreach (array_chunk($batch, 500) as $potong) $this->db->insert_batch('data_excel', $potong);
        $this->db->trans_complete();
    }

    /**
     * Isi sheet Data persis seperti di Excel, dengan pencarian dan paginasi.
     * @param array $f ['q' => teks, 'kab' => nilai kolom Kabupaten]
     * @return array ['kolom'=>[...], 'baris'=>[[nilai,...],...], 'total'=>int, 'ada'=>bool, 'kab_nilai'=>[...]]
     */
    public function data_mentah($periode_id, array $f, $limit, $offset)
    {
        $p = $this->db->get_where('periode', array('id' => $periode_id))->row_array();
        $kolom = ($p && ! empty($p['kolom_excel'])) ? json_decode($p['kolom_excel'], true) : array();
        if (empty($kolom)) return array('kolom' => array(), 'baris' => array(), 'total' => 0, 'ada' => false, 'kab_nilai' => array());

        $n = count($kolom);
        $cols = array(); for ($i = 1; $i <= $n; $i++) $cols[] = '`c' . $i . '`';
        $idx_kab = null;
        foreach ($kolom as $i => $nm) if (strcasecmp(trim($nm), 'Kabupaten') === 0) { $idx_kab = $i + 1; break; }

        $dari = ' FROM data_excel WHERE periode_id = ?'; $par = array($periode_id);
        if ($f['kab'] !== '' && $idx_kab) { $dari .= ' AND `c' . $idx_kab . '` = ?'; $par[] = $f['kab']; }
        if ($f['q'] !== '')
        {
            $dari .= ' AND CONCAT_WS(\' \', ' . implode(', ', $cols) . ') LIKE ?';
            $par[] = '%' . $this->db->escape_like_str($f['q']) . '%';
        }
        $total = (int) $this->db->query('SELECT COUNT(*) AS n' . $dari, $par)->row()->n;
        $rows = $this->db->query('SELECT ' . implode(', ', $cols) . $dari . ' ORDER BY baris LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset, $par)->result_array();

        $baris = array();
        foreach ($rows as $r) $baris[] = array_values($r);

        $kab_nilai = array();
        if ($idx_kab)
        {
            foreach ($this->db->query('SELECT DISTINCT `c' . $idx_kab . '` AS v FROM data_excel WHERE periode_id = ?', array($periode_id))->result_array() as $r)
                if ($r['v'] !== null) $kab_nilai[] = $r['v'];
            sort($kab_nilai, SORT_NUMERIC);
        }
        return array('kolom' => $kolom, 'baris' => $baris, 'total' => $total, 'ada' => true, 'kab_nilai' => $kab_nilai);
    }

    /** Menyimpan semua sheet selain Data. $meta = daftar semua sheet (termasuk Data) untuk tab. */
    public function simpan_sheets($periode_id, array $sheets, array $meta)
    {
        $this->db->trans_start();
        $this->db->update('periode', array('daftar_sheet' => json_encode($meta, JSON_UNESCAPED_UNICODE)), array('id' => $periode_id));
        $batch = array();
        foreach ($sheets as $sh)
        {
            foreach ($sh['baris'] as $b)
            {
                $batch[] = array('periode_id' => $periode_id, 'sheet' => $sh['nama'], 'baris' => $b['r'],
                                 'isi' => json_encode($b['v'], JSON_UNESCAPED_UNICODE));
            }
        }
        foreach (array_chunk($batch, 500) as $potong) $this->db->insert_batch('sheet_excel', $potong);
        $this->db->trans_complete();
    }

    /** Daftar sheet pada file yang diunggah: [ ['nama'=>, 'baris'=>, 'kolom'=>], ... ] */
    public function daftar_sheet($periode_id)
    {
        $p = $this->db->select('daftar_sheet')->get_where('periode', array('id' => $periode_id))->row_array();
        $d = ($p && ! empty($p['daftar_sheet'])) ? json_decode($p['daftar_sheet'], true) : array();
        return is_array($d) ? $d : array();
    }

    /** Isi satu sheet (selain Data) apa adanya, dengan pencarian dan paginasi. */
    public function data_sheet($periode_id, $sheet, $q, $limit, $offset)
    {
        $dari = ' FROM sheet_excel WHERE periode_id = ? AND sheet = ?'; $par = array($periode_id, $sheet);
        if ($q !== '') { $dari .= ' AND isi LIKE ?'; $par[] = '%' . $this->db->escape_like_str($q) . '%'; }
        $total = (int) $this->db->query('SELECT COUNT(*) AS n' . $dari, $par)->row()->n;
        $rows = $this->db->query('SELECT baris, isi' . $dari . ' ORDER BY baris LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset, $par)->result_array();
        $baris = array();
        foreach ($rows as $r) $baris[] = array('r' => (int) $r['baris'], 'v' => json_decode($r['isi'], true));
        return array('baris' => $baris, 'total' => $total);
    }

    /**
     * Hasil RH dihitung dari sheet Data (tabel harga): rata-rata geometrik harga bulan lalu dan bulan ini per komoditas, dan RHt.
     * @return array [ ['kode','nama','dokumen','g_lalu','g_ini','rht'], ... ] sesuai urutan komoditas pada file
     */
    public function hasil_rh($periode_id)
    {
        // Periode baru: Hasil RH sudah dihitung dan disimpan saat unggah (tahap 1 pengolahan)
        $rows = $this->db->query('SELECT kode, nama, dokumen, g_lalu, g_ini FROM komoditas
                                   WHERE periode_id = ? AND g_ini IS NOT NULL AND g_lalu IS NOT NULL ORDER BY urut', array($periode_id))->result_array();
        if ( ! $rows)   // periode lama: hitung dari tabel harga
        {
            $rows = $this->db->query(
                'SELECT c.kode, c.nama, c.dokumen, EXP(AVG(LN(h.harga_lalu))) AS g_lalu, EXP(AVG(LN(h.harga_ini))) AS g_ini
                   FROM komoditas c
                   JOIN kecamatan k ON k.komoditas_id = c.id
                   JOIN harga h ON h.kecamatan_id = k.id
                  WHERE c.periode_id = ? AND h.harga_ini > 0 AND h.harga_lalu > 0
                  GROUP BY c.id, c.kode, c.nama, c.dokumen, c.urut
                  ORDER BY c.urut', array($periode_id))->result_array();
        }
        $out = array();
        foreach ($rows as $r)
        {
            $gl = (float) $r['g_lalu']; $gi = (float) $r['g_ini'];
            $out[] = array('kode' => $r['kode'], 'nama' => $r['nama'], 'dokumen' => $r['dokumen'],
                           'g_lalu' => $gl, 'g_ini' => $gi, 'rht' => $gl > 0 ? ($gi / $gl - 1) * 100 : 0.0);
        }
        return $out;
    }

    /**
     * Membandingkan Hasil RH aplikasi dengan sheet RH yang ada di file Excel (hasil hitung BPS).
     * @return array|null null bila file tidak punya sheet RH; selain itu ['peta' => [kode => rht_file], 'ditemukan', 'cocok', 'selisih_maks']
     */
    public function banding_rh_file($periode_id, array $hasil_rh)
    {
        $ada = false;
        foreach ($this->daftar_sheet($periode_id) as $m) if ($m['nama'] === 'RH') $ada = true;
        if ( ! $ada) return null;

        $rows = $this->db->query("SELECT isi FROM sheet_excel WHERE periode_id = ? AND sheet = 'RH' ORDER BY baris", array($periode_id))->result_array();
        $peta = array();
        foreach ($rows as $r)
        {
            $v = json_decode($r['isi'], true);
            if ( ! is_array($v)) continue;
            $kode = isset($v[1]) && $v[1] !== null && $v[1] !== '' ? (string) $v[1] : (isset($v[0]) && $v[0] !== null ? (string) $v[0] : '');
            if ($kode === '' || ! isset($v[9]) || ! is_numeric($v[9]) || ! isset($v[7]) || ! is_numeric($v[7])) continue;
            $peta[$kode] = (float) $v[9];
        }
        $ditemukan = 0; $cocok = 0; $maks = 0.0; $selisih = array();
        foreach ($hasil_rh as $r)
        {
            if ( ! isset($peta[$r['kode']])) continue;
            $ditemukan++;
            $d = abs($r['rht'] - $peta[$r['kode']]);
            $selisih[$r['kode']] = $d;
            if ($d < 0.005) $cocok++;
            if ($d > $maks) $maks = $d;
        }
        return array('peta' => $peta, 'selisih' => $selisih, 'ditemukan' => $ditemukan, 'cocok' => $cocok, 'selisih_maks' => $maks);
    }

    /** Menandai komoditas (menurut kode) sebagai dipilih / tidak dipilih untuk dikonfirmasi. */
    public function set_dipilih($periode_id, array $kode, $nilai)
    {
        foreach (array_chunk(array_values(array_unique($kode)), 500) as $potong)
        {
            $this->db->where('periode_id', $periode_id)->where_in('kode', $potong)->update('komoditas', array('dipilih' => $nilai ? 1 : 0));
        }
    }

    /** Jumlah komoditas yang dipilih untuk dikonfirmasi; sekaligus memperbarui ringkasan periode. */
    public function hitung_dipilih($periode_id)
    {
        $n = (int) $this->db->query('SELECT COUNT(*) AS n FROM komoditas WHERE periode_id = ? AND tampil = 1 AND dipilih = 1', array($periode_id))->row()->n;
        $this->db->update('periode', array('perlu_konfirmasi' => $n), array('id' => $periode_id));
        return $n;
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