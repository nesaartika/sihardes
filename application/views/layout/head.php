<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fenomena Harga - BPS Provinsi Kalimantan Barat</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<?php
$q = $p ? '?p=' . $p : '';
$ikon = array(
 'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
 'rekap'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
 'komoditas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v4H3zM3 10h18v4H3zM3 17h18v4H3z"/></svg>',
 'data'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>',
 'unggah'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>',
 'aktivitas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8M8 9h2"/></svg>',
 'pengguna'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
 'rh'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg>',
 'riwayat'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
);
$menu_item = array(
 'dashboard' => array('Dashboard', ''),
 'unggah'    => array('Unggah Data', 'cermat/unggah'),
 'data'      => array('Data', 'cermat/data_asli'),
 'rh'        => array('RH', 'cermat/hasil_rh'),
 'rekap'     => array('Rekap Konfirmasi', 'cermat/rekap'),
 'komoditas' => array('Semua Komoditas', 'cermat/komoditas'),
 'riwayat'   => array('Riwayat Periode', 'cermat/riwayat'),
 'aktivitas' => array('Log Aktivitas', 'cermat/aktivitas'),
 'pengguna'  => array('Kelola Pengguna', 'cermat/pengguna'),
);
$role = $this->session->userdata('role');
$akun_kab = ($role === 'kabupaten');
if ($akun_kab) $menu_item = array_intersect_key($menu_item, array_flip(array('data', 'rh', 'rekap')));   // akun kabupaten: Data, RH, dan Rekap
// file periode ini bukan tabel harga (tidak ada RH/rekap): menu hanya yang relevan
if ( ! empty($periode) && empty($periode['hasil']['komoditas'])) $menu_item = array_diff_key($menu_item, array_flip(array('dashboard', 'rh', 'rekap', 'komoditas')));
$nm_user = (string) $this->session->userdata('nama');
$nama_peran = $akun_kab ? 'BPS ' . ((int) $this->session->userdata('kab') < 70 ? 'Kab. ' : '') . Cermat_harga::nama_kab((int) $this->session->userdata('kab')) : 'Admin';
$judul = array('dashboard' => 'Dashboard', 'rekap' => 'Rekap Konfirmasi Harga', 'komoditas' => 'Komoditas', 'data' => 'Data', 'rh' => 'RH',
               'unggah' => 'Unggah Data', 'riwayat' => 'Riwayat Periode', 'aktivitas' => 'Log Aktivitas', 'pengguna' => 'Kelola Pengguna');
?>
<aside class="side">
  <div class="brand"><b>FENOMENA HARGA</b><span>BPS Provinsi Kalimantan Barat</span></div>
  <nav class="nav">
    <?php foreach ($menu_item as $k => $m): ?>
      <a class="<?= $menu === $k ? 'on' : '' ?>" href="<?= site_url($m[1]) . (in_array($k, array('unggah', 'aktivitas', 'pengguna')) ? '' : $q) ?>"><?= $ikon[$k] ?><?= $m[0] ?></a>
    <?php endforeach; ?>
  </nav>
  <small>Pengumpulan Fenomena<br>Perubahan Harga Perdesaan</small>
</aside>
<div class="wrap">
  <header class="top">
    <h1><?= $judul[$menu] ?></h1>
    <span class="hint" style="white-space:nowrap">
      <?= html_escape($nm_user) ?><?= ($akun_kab ? stripos($nm_user, Cermat_harga::nama_kab((int) $this->session->userdata('kab'))) === false : strcasecmp($nm_user, 'Admin') !== 0) ? ' (' . html_escape($nama_peran) . ')' : '' ?> &middot;
      <a href="<?= site_url('auth/ganti_sandi') ?>">Ganti sandi</a> &middot;
      <a href="<?= site_url('auth/logout') ?>">Keluar</a>
    </span>
    <?php if ( ! empty($daftar_periode)): ?>
      <select onchange="location.href=location.pathname+'?p='+this.value" title="Periode">
        <?php foreach ($daftar_periode as $pd): ?>
          <option value="<?= $pd['id'] ?>" <?= $pd['id'] === $p ? 'selected' : '' ?>><?= html_escape($pd['nama']) ?><?= ($pd['jenis'] !== 'Harga Perdesaan') ? ' (' . html_escape($pd['jenis']) . ')' : '' ?> &middot; <?= html_escape(mb_strimwidth($pd['file_asal'], 0, 38, '...')) ?> &middot; <?= $pd['dibuat'] ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($p && ! $akun_kab): ?><a class="btn alt" href="<?= site_url('cermat/unduh/' . $p) ?>">Unduh Excel</a><?php endif; ?>
    <?php endif; ?>
  </header>
  <main>
  <?php if ($pesan = $this->session->flashdata('pesan')): ?>
    <div class="card" style="border-left:4px solid var(--turun);padding:10px 14px;margin-bottom:14px"><?= html_escape($pesan) ?></div>
  <?php endif; ?>