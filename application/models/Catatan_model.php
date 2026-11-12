<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Log aktivitas pengguna.
 * CATATAN: bila di komputer kamu sudah ada Catatan_model.php asli, JANGAN ditimpa dengan file ini.
 * Cukup ganti fungsi kosongkan() di file asli dengan versi di bawah (sesuaikan nama tabel).
 */
class Catatan_model extends CI_Model
{
    const TABEL = 'log_aktivitas';   // sesuaikan bila nama tabel log kamu berbeda

    public function __construct()
    {
        parent::__construct();
        $this->load->model('periode_model');   // memastikan koneksi database terbuka

        if ( ! $this->db->table_exists(self::TABEL))
        {
            $this->db->query('CREATE TABLE IF NOT EXISTS `' . self::TABEL . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `waktu` DATETIME NOT NULL,
                `username` VARCHAR(50) DEFAULT NULL,
                `aksi` VARCHAR(40) NOT NULL,
                `keterangan` TEXT,
                `ip` VARCHAR(45) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `waktu` (`waktu`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
    }

    public function catat($aksi, $keterangan = '', $username = null)
    {
        if ($username === null)
        {
            $username = isset($this->session) ? (string) $this->session->userdata('username') : '';
        }
        $this->db->insert(self::TABEL, array(
            'waktu' => date('Y-m-d H:i:s'), 'username' => $username, 'aksi' => $aksi,
            'keterangan' => $keterangan, 'ip' => $this->input->ip_address(),
        ));
    }

    public function terbaru($n = 500)
    {
        return $this->db->order_by('id', 'DESC')->limit((int) $n)->get(self::TABEL)->result_array();
    }

    /** $hari = 0: hapus semua; $hari > 0: hapus hanya yang lebih lama dari sekian hari. Mengembalikan jumlah baris terhapus. */
    public function kosongkan($hari = 0)
    {
        $hari = (int) $hari;
        if ($hari > 0)
        {
            $this->db->where('waktu <', date('Y-m-d H:i:s', strtotime('-' . $hari . ' days')))->delete(self::TABEL);
            return (int) $this->db->affected_rows();
        }
        $n = (int) $this->db->count_all(self::TABEL);
        $this->db->truncate(self::TABEL);
        return $n;
    }
}