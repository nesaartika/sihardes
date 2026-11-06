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
        $galat = $this->session->flashdata('galat_login');

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
                    if ($u['role'] === 'kabupaten') $u['wajib_ganti'] = 0;   // sandi kabupaten ditetapkan admin provinsi
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
        $this->config->load('cermat', FALSE, TRUE);
        $this->load->view('auth/login', array('galat' => $galat, 'sso_aktif' => (bool) $this->config->item('sso_aktif')));
    }

    // ------------------------------------------------------------------
    // Login SSO BPS (OpenID Connect, alur authorization code)
    // ------------------------------------------------------------------

    private function sso_konf()
    {
        $this->config->load('cermat', FALSE, TRUE);
        $c = array();
        foreach (array('aktif', 'url', 'realm', 'client_id', 'client_secret', 'redirect_uri') as $k) $c[$k] = $this->config->item('sso_' . $k);
        $c['url'] = rtrim((string) $c['url'], '/');
        if ((string) $c['redirect_uri'] === '') $c['redirect_uri'] = site_url('auth/sso_callback');
        $c['dasar'] = $c['url'] . '/realms/' . rawurlencode((string) $c['realm']) . '/protocol/openid-connect';
        return $c;
    }

    private function sso_galat($pesan)
    {
        $this->session->set_flashdata('galat_login', $pesan);
        redirect('auth/login');
    }

    private function sso_http($url, $post = null, $bearer = null)
    {
        $ch = curl_init($url);
        $hdr = array('Accept: application/json');
        if ($bearer) $hdr[] = 'Authorization: Bearer ' . $bearer;
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => $hdr));
        if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
        $isi = curl_exec($ch); $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return ($isi !== false && $kode === 200) ? json_decode($isi, true) : null;
    }

    /** Arahkan pengguna ke halaman login SSO BPS */
    public function sso_login()
    {
        $c = $this->sso_konf();
        if (empty($c['aktif']) || (string) $c['client_id'] === '') $this->sso_galat('Login SSO BPS belum diaktifkan.');
        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('sso_state', $state);
        redirect($c['dasar'] . '/auth?' . http_build_query(array(
            'client_id' => $c['client_id'], 'response_type' => 'code', 'scope' => 'openid profile email',
            'redirect_uri' => $c['redirect_uri'], 'state' => $state,
        )), 'location', 302);
    }

    /** Tujuan kembali dari SSO: tukar kode dengan token, ambil email, cocokkan dengan akun terdaftar */
    public function sso_callback()
    {
        $c = $this->sso_konf();
        $state = (string) $this->session->userdata('sso_state');
        $this->session->unset_userdata('sso_state');
        $kode = (string) $this->input->get('code');

        if ($this->input->get('error')) $this->sso_galat('Login SSO dibatalkan atau ditolak.');
        if ($kode === '' || $state === '' || ! hash_equals($state, (string) $this->input->get('state'))) $this->sso_galat('Permintaan SSO tidak valid. Coba lagi.');

        $tok = $this->sso_http($c['dasar'] . '/token', array_filter(array(
            'grant_type' => 'authorization_code', 'code' => $kode, 'redirect_uri' => $c['redirect_uri'],
            'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
        ), 'strlen'));
        if (empty($tok['access_token'])) $this->sso_galat('Gagal menghubungi SSO BPS. Periksa pengaturan client SSO.');

        $info = $this->sso_http($c['dasar'] . '/userinfo', null, $tok['access_token']);
        $email = strtolower(trim((string) (isset($info['email']) ? $info['email'] : '')));
        if ($email === '') $this->sso_galat('SSO tidak mengirim email Anda.');

        $domain = strtolower(trim((string) $this->config->item('domain_email')));
        if ($domain !== '' && substr($email, -strlen('@' . $domain)) !== '@' . $domain) $this->sso_galat('Email harus berdomain @' . $domain . '.');

        $u = $this->pengguna->cari_username($email);
        if ( ! $u)
        {
            $this->catatan->catat('login_gagal', 'SSO: email belum terdaftar', mb_substr($email, 0, 50));
            $this->sso_galat('Email ' . $email . ' belum terdaftar di aplikasi. Hubungi admin provinsi.');
        }

        $this->session->sess_regenerate(TRUE);
        $this->session->set_userdata(array(
            'user_id' => (int) $u['id'], 'username' => $u['username'], 'nama' => $u['nama'],
            'role' => $u['role'], 'kab' => (int) $u['kab'], 'wajib_ganti' => 0, 'gagal' => 0, 'blok_sampai' => 0,   // sandi dikelola SSO
        ));
        $this->pengguna->catat_login($u['id']);
        $this->catatan->catat('login', 'Berhasil masuk (SSO BPS)', $u['username']);
        redirect($u['role'] === 'kabupaten' ? 'cermat/rekap' : '/');
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
        // akun kabupaten tidak mengubah sandi sendiri: hubungi admin provinsi
        if ($this->session->userdata('role') === 'kabupaten') redirect('cermat/rekap');
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