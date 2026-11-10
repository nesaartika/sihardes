<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php $nama_app = config_item('nama_app') ?: 'SIHARDES'; $nama_app_ket = config_item('nama_app_ket') ?: 'Sistem Informasi Harga Perdesaan'; ?>
<title><?= html_escape($nama_app) ?> - BPS Provinsi Kalimantan Barat</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/app.css') ?>">
<script>document.documentElement.setAttribute('data-bs-theme', window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');</script>
<style>
/* Penyesuaian Bootstrap 5 (berlaku di dalam elemen .bs; halaman lain tetap memakai app.css) */
body{font-size:14px}
.bs .btn{background:var(--bs-btn-bg);color:var(--bs-btn-color);border:var(--bs-btn-border-width) solid var(--bs-btn-border-color);padding:var(--bs-btn-padding-y) var(--bs-btn-padding-x);font-size:var(--bs-btn-font-size);font-weight:var(--bs-btn-font-weight);border-radius:var(--bs-btn-border-radius)}
.bs .btn:hover{background:var(--bs-btn-hover-bg);color:var(--bs-btn-hover-color);border-color:var(--bs-btn-hover-border-color)}
.bs .btn:disabled{opacity:.65}
.bs .card{padding:0;border:var(--bs-card-border-width) solid var(--bs-card-border-color);border-radius:var(--bs-card-border-radius);background:var(--bs-card-bg);box-shadow:0 1px 3px rgba(16,24,40,.08)}
.bs .progress{height:10px;margin:0;background:var(--bs-progress-bg);border-radius:99px}
.bs .progress .progress-bar{background:#00c853}
.bs h2,.bs .h2x{font-size:15px;margin:0 0 12px;font-weight:700}
.bs .form-control,.bs .form-select{background-color:var(--inp);color:var(--tx);border-color:var(--br)}
.bs .form-control.alasan{background-color:rgba(250,204,21,.10)}
.bs .form-select{width:auto;max-width:100%}
.menu-samping{padding:12px 10px;flex:1;display:flex;flex-direction:column}
.menu-samping a{display:flex;gap:10px;align-items:center;padding:10px 12px;margin-bottom:3px;border-radius:8px;color:#cfd9ea;font-weight:600;min-height:42px}
.menu-samping a:hover{background:var(--side2);color:#fff}
.menu-samping a.on{background:var(--pri);color:#fff}
.menu-samping svg{width:18px;height:18px;flex:none}
.bs.top .form-select.pilih-file{width:320px;max-width:34vw;text-overflow:ellipsis}
</style>
<script>window.CSRF = <?= json_encode(isset($csrf) ? array('n' => $csrf[0], 'v' => $csrf[1]) : null) ?>;</script>
</head>
<body>
<?php
$q = '';   // tautan menu tidak membawa ?p= : periode mengikuti pilihan yang diklik di daftar atas (atau yang terbaru)
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
 'dashboard' => array('Dashboard', 'cermat'),
 'unggah'    => array('Unggah Data', 'cermat/unggah'),
 'rh'        => array('RH', 'cermat/hasil_rh'),
 'rekap'     => array('Rekap Konfirmasi', 'cermat/rekap'),
 'komoditas' => array('Semua Komoditas', 'cermat/komoditas'),
 'riwayat'   => array('Riwayat Periode', 'cermat/riwayat'),
 'aktivitas' => array('Log Aktivitas', 'cermat/aktivitas'),
 'pengguna'  => array('Kelola Pengguna', 'cermat/pengguna'),
);
$role = $this->session->userdata('role');
$akun_kab = ($role === 'kabupaten');
if ($akun_kab) $menu_item = array_intersect_key($menu_item, array_flip(array('rh', 'rekap')));   // akun kabupaten: RH dan Rekap
// Menu samping SELALU sama untuk tiap peran (tidak berubah menurut periode/file yang dibuka atau diunggah).
$nm_user = (string) $this->session->userdata('nama');
$nama_peran = $akun_kab ? 'BPS ' . ((int) $this->session->userdata('kab') < 70 ? 'Kab. ' : '') . Cermat_harga::nama_kab((int) $this->session->userdata('kab')) : 'Admin';
$judul = array('dashboard' => 'Dashboard', 'rekap' => 'Rekap Konfirmasi Harga', 'komoditas' => 'Komoditas', 'data' => 'Data', 'rh' => 'RH',
               'unggah' => 'Unggah Data', 'riwayat' => 'Riwayat Periode', 'aktivitas' => 'Log Aktivitas', 'pengguna' => 'Kelola Pengguna');
?>
<aside class="side">
  <div class="brand"><b><?= html_escape(strtoupper($nama_app)) ?></b><span><?= html_escape($nama_app_ket) ?><br>BPS Provinsi Kalimantan Barat</span></div>
  <nav class="menu-samping">
    <?php foreach ($menu_item as $k => $m): ?>
      <a class="<?= $menu === $k ? 'on' : '' ?>" href="<?= site_url($m[1]) . (in_array($k, array('unggah', 'aktivitas', 'pengguna')) ? '' : $q) ?>"><?= $ikon[$k] ?><?= $m[0] ?></a>
    <?php endforeach; ?>
  </nav>
  <small>Pengumpulan Fenomena<br>Perubahan Harga Perdesaan</small>
</aside>
<div class="wrap">
  <header class="top bs">
    <h1><?= isset($judul_halaman) ? html_escape($judul_halaman) : $judul[$menu] ?></h1>
    <span class="hint" style="white-space:nowrap">
      <?= html_escape($nm_user) ?><?= ($akun_kab ? stripos($nm_user, Cermat_harga::nama_kab((int) $this->session->userdata('kab'))) === false : strcasecmp($nm_user, 'Admin') !== 0) ? ' (' . html_escape($nama_peran) . ')' : '' ?> &middot;
      <?php if ( ! $akun_kab): ?><a href="<?= site_url('auth/ganti_sandi') ?>">Ganti sandi</a> &middot;<?php endif; ?>
      <a href="<?= site_url('auth/logout') ?>">Keluar</a>
    </span>
    <?php if ( ! empty($daftar_periode)): ?>
      <select class="form-select form-select-sm pilih-file" onchange="location.href=location.pathname+'?pilih=1&p='+this.value" title="Pilih file / periode yang dibuka. Pilihan ini hanya berubah bila Anda memilihnya sendiri.">
        <?php foreach ($daftar_periode as $pd): ?>
          <option value="<?= $pd['id'] ?>" <?= $pd['id'] === $p ? 'selected' : '' ?>><?= html_escape($pd['nama']) ?><?= ($pd['jenis'] !== 'Harga Perdesaan') ? ' (' . html_escape($pd['jenis']) . ')' : '' ?> &middot; <?= html_escape(mb_strimwidth($pd['file_asal'], 0, 38, '...')) ?> &middot; <?= $pd['dibuat'] ?></option>
        <?php endforeach; ?>
      </select>
      <?php
      // tombol unduh mengikuti halaman: RH -> format sheet RH; Data / file bukan tabel harga -> file asli yang diunggah; selain itu -> Rekap Konfirmasi
      $mode_sheet = ! empty($periode['mode_sheet']);      // file bukan tabel harga, tetap dikonfirmasi lewat Komoditas & Rekap
      $tanpa_hasil = empty($periode['hasil']['komoditas']) || $mode_sheet;
      if ($menu === 'rh')                      { $unduh = site_url('cermat/unduh_rh/' . $p);  $unduh_ket = 'Unduh RH (format sheet RH)'; }
      elseif ($menu === 'rekap' && $mode_sheet && ! $this->input->get('ringkasan')) { $unduh = site_url('cermat/unduh/' . $p); $unduh_ket = 'Unduh Rekap Konfirmasi'; }
      elseif ($menu === 'rekap' && $tanpa_hasil) { $sh = (string) $this->input->get('sheet'); $unduh = site_url('cermat/unduh_rekap/' . $p) . ($sh !== '' ? '?sheet=' . rawurlencode($sh) : ''); $unduh_ket = $sh !== '' ? 'Unduh rekap sheet ' . $sh : 'Unduh rekap semua sheet'; }
      elseif ($menu === 'data' || $tanpa_hasil) { $sh = (string) $this->input->get('sheet'); if ($sh === '' && ! $tanpa_hasil) $sh = 'Data'; $unduh = site_url('cermat/unduh_data/' . $p) . ($sh !== '' ? '?sheet=' . rawurlencode($sh) : ''); $unduh_ket = $sh !== '' ? 'Unduh sheet ' . $sh . ' apa adanya' : 'Unduh file Excel asli (format sama dengan yang diunggah)'; }
      else                                     { $unduh = site_url('cermat/unduh/' . $p);     $unduh_ket = 'Unduh Rekap Konfirmasi'; }
      ?>
      <?php if ($p && ! $akun_kab): ?><a class="btn btn-outline-primary btn-sm" href="<?= $unduh ?>" title="<?= html_escape($unduh_ket) ?>">Unduh Excel</a><?php endif; ?>
    <?php endif; ?>
  </header>
  <main>
  <?php if ($pesan = $this->session->flashdata('pesan')): ?>
    <div class="alert alert-success py-2 mb-3"><?= html_escape($pesan) ?></div>
  <?php endif; ?>