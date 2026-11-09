<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halaman depan (landing page). Terbuka tanpa login.
 * Dashboard aplikasi ada di /cermat.
 */
class Beranda extends MY_Controller
{
    protected $wajib_login = FALSE;

    public function index()
    {
        $this->load->view('beranda/login', array(
            'sudah_masuk' => (bool) $this->session->userdata('user_id'),
            'nama'        => (string) $this->session->userdata('nama'),
        ));
    }
}