<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Pengaturan aplikasi SIHARDES.
 * domain_email       : akun yang dibuat admin harus memakai email dengan domain ini (kosongkan untuk menerima semua domain).
 * simpan_semua_sheet : FALSE (bawaan) = untuk file tabel harga hanya sheet Data dan RH yang disimpan; menu Data hanya berisi Data.
 *                      TRUE = semua sheet ikut disimpan (lebih lambat; menu Data tetap hanya menampilkan Data).
 * sandi_minimal      : panjang minimal kata sandi yang ditetapkan admin.
 */
$config['domain_email']       = 'bps.go.id';
$config['simpan_semua_sheet'] = FALSE;
$config['sandi_minimal']      = 8;