<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    protected $wajib_login = FALSE;      // halaman login tentu boleh dibuka tanpa login

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pengguna_model', 'pengguna');
        $this->load->model('catatan_model', 'catatan');
    }

    public function index()
    {
        redirect('auth/login');
    }

    public function login()
    {
        if ($this->session->userdata('user_id')) redirect('/');
        $galat = null;

        if ($this->input->method() === 'post')
        {
            $blok = (int) $this->session->userdata('blok_sampai');
            if ($blok > time())
            {
                $galat = 'Terlalu banyak percobaan. Coba lagi dalam ' . ($blok - time()) . ' detik.';
            }
            else
            {
                $u = $this->pengguna->cari_username(trim((string) $this->input->post('username')));
                if ($u && password_verify((string) $this->input->post('password'), $u['password_hash']))
                {
                    $this->session->sess_regenerate(TRUE);
                    $this->session->set_userdata(array(
                        'user_id' => (int) $u['id'], 'username' => $u['username'], 'nama' => $u['nama'],
                        'role' => $u['role'], 'kab' => (int) $u['kab'], 'wajib_ganti' => (int) $u['wajib_ganti'], 'gagal' => 0, 'blok_sampai' => 0,
                    ));
                    $this->pengguna->catat_login($u['id']);
                    $this->catatan->catat('login', 'Berhasil masuk', $u['username']);
                    redirect($u['wajib_ganti'] ? 'auth/ganti_sandi' : ($u['role'] === 'kabupaten' ? 'cermat/rekap' : '/'));
                }

                $this->catatan->catat('login_gagal', 'Percobaan masuk gagal', mb_substr(trim((string) $this->input->post('username')), 0, 50));
                usleep(600000);          // memperlambat tebak-tebakan kata sandi
                $gagal = (int) $this->session->userdata('gagal') + 1;
                $this->session->set_userdata('gagal', $gagal);
                if ($gagal >= 5)
                {
                    $this->session->set_userdata(array('gagal' => 0, 'blok_sampai' => time() + 60));
                    $galat = 'Terlalu banyak percobaan. Coba lagi dalam 60 detik.';
                }
                else
                {
                    $galat = 'Nama pengguna atau kata sandi salah.';
                }
            }
        }
        $this->load->view('auth/login', array('galat' => $galat));
    }

    public function logout()
    {
        if ($this->session->userdata('user_id')) $this->catatan->catat('logout', 'Keluar');
        $this->session->sess_destroy();
        redirect('auth/login');
    }

    public function ganti_sandi()
    {
        if ( ! $this->session->userdata('user_id')) redirect('auth/login');
        $galat = null;
        $wajib = (bool) $this->session->userdata('wajib_ganti');

        if ($this->input->method() === 'post')
        {
            $u = $this->pengguna->cari_id($this->session->userdata('user_id'));
            $lama = (string) $this->input->post('sandi_lama');
            $baru = (string) $this->input->post('sandi_baru');
            $ulang = (string) $this->input->post('sandi_ulang');

            if ( ! $u || ! password_verify($lama, $u['password_hash'])) $galat = 'Kata sandi lama salah.';
            elseif (strlen($baru) < 8)                                   $galat = 'Kata sandi baru minimal 8 karakter.';
            elseif ($baru !== $ulang)                                    $galat = 'Konfirmasi kata sandi tidak sama.';
            elseif ($baru === $lama || $baru === Pengguna_model::SANDI_AWAL) $galat = 'Gunakan kata sandi baru yang berbeda dari sebelumnya.';
            else
            {
                $this->pengguna->ganti_sandi($u['id'], $baru);
                $this->catatan->catat('ganti_sandi', 'Kata sandi diganti');
                $this->session->set_userdata('wajib_ganti', 0);
                $this->session->set_flashdata('pesan', 'Kata sandi berhasil diganti.');
                redirect('/');
            }
        }
        $this->load->view('auth/ganti_sandi', array('galat' => $galat, 'wajib' => $wajib));
    }
}
