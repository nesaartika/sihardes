<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Akun pengguna. Saat ini hanya satu akun (petugas provinsi); kolom `role` disiapkan
 * agar nanti dapat ditambah peran lain tanpa mengubah struktur tabel.
 */
class Pengguna_model extends CI_Model
{
    const USER_AWAL = 'admin';
    const SANDI_AWAL = 'admin123';

    public function __construct()
    {
        parent::__construct();
        // memastikan database & tabel utama sudah dibuat (sekaligus membuka koneksi)
        $this->load->model('periode_model');

        if ( ! $this->db->table_exists('pengguna'))
        {
            $this->db->query("CREATE TABLE IF NOT EXISTS `pengguna` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(50) NOT NULL,
                `nama` VARCHAR(100) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `role` VARCHAR(30) NOT NULL DEFAULT 'admin',
                `kab` TINYINT UNSIGNED DEFAULT NULL,
                `wajib_ganti` TINYINT(1) NOT NULL DEFAULT 1,
                `aktif` TINYINT(1) NOT NULL DEFAULT 1,
                `terakhir_login` DATETIME DEFAULT NULL,
                `dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        // Database versi lama: tambah kolom kabupaten dan samakan nama role
        if ( ! $this->db->field_exists('kab', 'pengguna'))
        {
            $this->db->query('ALTER TABLE pengguna ADD COLUMN kab TINYINT UNSIGNED NULL AFTER role');
        }
        $this->db->query("UPDATE pengguna SET role = 'admin' WHERE role = 'petugas_provinsi'");

        if ($this->db->count_all('pengguna') === 0)
        {
            $this->db->insert('pengguna', array(
                'username' => self::USER_AWAL, 'nama' => 'Admin',
                'password_hash' => password_hash(self::SANDI_AWAL, PASSWORD_DEFAULT),
                'role' => 'admin', 'wajib_ganti' => 1,
            ));
        }
    }

    public function cari_username($username)
    {
        return $this->db->get_where('pengguna', array('username' => $username, 'aktif' => 1))->row_array();
    }

    public function cari_id($id)
    {
        return $this->db->get_where('pengguna', array('id' => (int) $id, 'aktif' => 1))->row_array();
    }

    public function catat_login($id)
    {
        $this->db->update('pengguna', array('terakhir_login' => date('Y-m-d H:i:s')), array('id' => (int) $id));
    }

    public function ganti_sandi($id, $sandi_baru)
    {
        $this->db->update('pengguna', array(
            'password_hash' => password_hash($sandi_baru, PASSWORD_DEFAULT), 'wajib_ganti' => 0,
        ), array('id' => (int) $id));
    }

    // ---------------------------------------------------------------
    // Kelola akun (khusus admin)
    // ---------------------------------------------------------------

    public function daftar()
    {
        return $this->db->order_by('role', 'ASC')->order_by('kab', 'ASC')->order_by('username', 'ASC')->get('pengguna')->result_array();
    }

    public function email_ada($username)
    {
        return (bool) $this->db->get_where('pengguna', array('username' => $username))->row_array();
    }

    public function tambah($username, $nama, $role, $kab, $sandi)
    {
        $this->db->insert('pengguna', array(
            'username' => $username, 'nama' => $nama, 'role' => $role, 'kab' => $kab ?: null,
            'password_hash' => password_hash($sandi, PASSWORD_DEFAULT), 'wajib_ganti' => 1, 'aktif' => 1,
        ));
        return (int) $this->db->insert_id();
    }

    public function set_aktif($id, $aktif)
    {
        $this->db->update('pengguna', array('aktif' => $aktif ? 1 : 0), array('id' => (int) $id));
    }

    /** Mengatur kata sandi sementara: pemilik akun wajib menggantinya saat masuk berikutnya. */
    public function reset_sandi($id, $sandi)
    {
        $this->db->update('pengguna', array('password_hash' => password_hash($sandi, PASSWORD_DEFAULT), 'wajib_ganti' => 1), array('id' => (int) $id));
    }

    public function email_ada_selain($username, $id)
    {
        return (bool) $this->db->get_where('pengguna', array('username' => $username, 'id !=' => (int) $id))->row_array();
    }

    public function ubah($id, $username, $nama, $role, $kab)
    {
        $this->db->update('pengguna', array('username' => $username, 'nama' => $nama, 'role' => $role, 'kab' => $role === 'kabupaten' ? ($kab ?: null) : null), array('id' => (int) $id));
    }

    public function hapus($id)
    {
        $this->db->delete('pengguna', array('id' => (int) $id));
    }

    /** Jumlah akun admin yang aktif, tidak termasuk akun $kecuali. */
    public function jml_admin_aktif($kecuali = 0)
    {
        return (int) $this->db->where('role', 'admin')->where('aktif', 1)->where('id !=', (int) $kecuali)->count_all_results('pengguna');
    }

    public function ambil($id)
    {
        return $this->db->get_where('pengguna', array('id' => (int) $id))->row_array();
    }

    public static function sandi_acak($panjang = 10)
    {
        $huruf = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i = 0; $i < $panjang; $i++) $s .= $huruf[random_int(0, strlen($huruf) - 1)];
        return $s;
    }
}