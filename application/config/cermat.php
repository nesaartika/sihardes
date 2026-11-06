<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Pengaturan aplikasi CERMAT.
 * domain_email       : akun yang dibuat admin harus memakai email dengan domain ini (kosongkan untuk menerima semua domain).
 * simpan_semua_sheet : FALSE (bawaan) = untuk file tabel harga hanya sheet Data dan RH yang disimpan; menu Data hanya berisi Data.
 *                      TRUE = semua sheet ikut disimpan (lebih lambat; menu Data tetap hanya menampilkan Data).
 * sandi_minimal      : panjang minimal kata sandi yang ditetapkan admin.
 */
$config['domain_email']       = 'bps.go.id';
$config['simpan_semua_sheet'] = FALSE;
$config['sandi_minimal']      = 8;

/*
 * Login SSO BPS (OpenID Connect / Keycloak). Isi setelah aplikasi didaftarkan sebagai client di SSO BPS.
 * sso_aktif         : TRUE = tombol "Login dengan SSO BPS" berfungsi.
 * sso_url           : alamat server Keycloak, tanpa garis miring di akhir.
 * sso_realm         : nama realm.
 * sso_client_id     : dari pengelola SSO BPS.
 * sso_client_secret : dari pengelola SSO BPS (kosongkan bila client bertipe public).
 * sso_redirect_uri  : harus SAMA PERSIS dengan yang didaftarkan, misal http://localhost/cermat_harga/auth/sso_callback
 *                     (kosongkan = dibuat otomatis dari base_url).
 */
$config['sso_aktif']         = FALSE;
$config['sso_url']           = 'https://accounts.bps.go.id';
$config['sso_realm']         = 'pegawai';
$config['sso_client_id']     = '';
$config['sso_client_secret'] = '';
$config['sso_redirect_uri']  = '';