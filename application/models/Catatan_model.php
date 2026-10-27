<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Log aktivitas: siapa melakukan apa, dan kapan (tabel log_aktivitas). */
class Catatan_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('periode_model');     // memastikan database sudah siap

        if ( ! $this->db->table_exists('log_aktivitas'))
        {
            $this->db->query("CREATE TABLE IF NOT EXISTS `log_aktivitas` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `waktu` DATETIME NOT NULL,
                `username` VARCHAR(50) NOT NULL,
                `aksi` VARCHAR(30) NOT NULL,
                `keterangan` VARCHAR(255) NOT NULL DEFAULT '',
                `ip` VARCHAR(45) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`),
                KEY `idx_waktu` (`waktu`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /**
     * @param string      $aksi       login | login_gagal | logout | ganti_sandi | unggah | isi_alasan | unduh | hapus_periode
     * @param string      $keterangan teks singkat
     * @param string|null $username   bila kosong diambil dari sesi login (atau "terminal" untuk CLI)
     */
    public function catat($aksi, $keterangan = '', $username = null)
    {
        if ($username === null)
        {
            $ci = get_instance();
            $username = is_cli() ? 'terminal' : (isset($ci->session) && $ci->session->userdata('username') ? $ci->session->userdata('username') : '-');
        }
        $this->db->insert('log_aktivitas', array(
            'waktu' => date('Y-m-d H:i:s'),
            'username' => mb_substr($username, 0, 50),
            'aksi' => $aksi,
            'keterangan' => mb_substr($keterangan, 0, 255),
            'ip' => is_cli() ? 'cli' : $this->input->ip_address(),
        ));
    }

    public function terbaru($batas = 500)
    {
        return $this->db->order_by('id', 'DESC')->limit((int) $batas)->get('log_aktivitas')->result_array();
    }
}
