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
                `role` VARCHAR(30) NOT NULL DEFAULT 'petugas_provinsi',
                `wajib_ganti` TINYINT(1) NOT NULL DEFAULT 1,
                `aktif` TINYINT(1) NOT NULL DEFAULT 1,
                `terakhir_login` DATETIME DEFAULT NULL,
                `dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if ($this->db->count_all('pengguna') === 0)
        {
            $this->db->insert('pengguna', array(
                'username' => self::USER_AWAL, 'nama' => 'Petugas Provinsi',
                'password_hash' => password_hash(self::SANDI_AWAL, PASSWORD_DEFAULT),
                'role' => 'petugas_provinsi', 'wajib_ganti' => 1,
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
}
