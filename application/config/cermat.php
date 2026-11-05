<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Pengaturan aplikasi CERMAT.
 * domain_email       : akun yang dibuat admin harus memakai email dengan domain ini (kosongkan untuk menerima semua domain).
 * simpan_semua_sheet : TRUE = semua sheet pada file yang diunggah disimpan dan tampil sebagai tab di menu Data (sama dengan file asli).
 *                      Ubah ke FALSE bila proses unggah terlalu lama di server (hanya sheet Data dan RH yang disimpan).
 * sandi_minimal      : panjang minimal kata sandi yang ditetapkan admin.
 */
$config['domain_email']       = 'bps.go.id';
$config['simpan_semua_sheet'] = TRUE;
$config['sandi_minimal']      = 8;