<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller dasar: semua halaman aplikasi mewajibkan login.
 * Controller yang boleh diakses tanpa login (Auth) menimpa $wajib_login = FALSE.
 */
class MY_Controller extends CI_Controller
{
    protected $wajib_login = TRUE;

    public function __construct()
    {
        parent::__construct();
        if (is_cli()) return;            // perintah terminal (sihardes.php) tidak memakai sesi

        $this->mulai_sesi();
        if ($this->wajib_login) $this->periksa_login();
    }

    protected function mulai_sesi()
    {
        $dir = FCPATH . 'writable/sessions';
        if ( ! is_dir($dir)) @mkdir($dir, 0775, true);
        $this->config->set_item('sess_save_path', $dir);
        $this->load->library('session');
    }

    private function periksa_login()
    {
        if ( ! $this->session->userdata('user_id'))
        {
            if ($this->input->method() === 'post')   // permintaan AJAX (simpan alasan) saat sesi habis
            {
                $this->output->set_status_header(401)->set_content_type('application/json')
                     ->set_output(json_encode(array('ok' => false, 'pesan' => 'Sesi habis, silakan login lagi.')))->_display();
                exit;
            }
            redirect('auth/login');
        }
        // akun admin yang masih memakai kata sandi bawaan/sementara wajib menggantinya (akun kabupaten tidak: sandinya dari admin provinsi)
        if ($this->session->userdata('wajib_ganti') && $this->session->userdata('role') !== 'kabupaten') redirect('auth/ganti_sandi');
    }
}