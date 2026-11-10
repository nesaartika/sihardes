<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php $nama_app = config_item('nama_app') ?: 'SIHARDES'; ?>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20viewBox%3D%220%200%2048%2048%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20role%3D%22img%22%20aria-label%3D%22Logo%20SIHARDES%22%3E%3Cdefs%3E%3ClinearGradient%20id%3D%22f%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%231d5fd0%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%230aa5e8%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect%20width%3D%2248%22%20height%3D%2248%22%20rx%3D%2213%22%20fill%3D%22url%28%23f%29%22/%3E%3Crect%20x%3D%2210%22%20y%3D%2227%22%20width%3D%225%22%20height%3D%2211%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Crect%20x%3D%2221.5%22%20y%3D%2221%22%20width%3D%225%22%20height%3D%2217%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Crect%20x%3D%2233%22%20y%3D%2215%22%20width%3D%225%22%20height%3D%2223%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Cpath%20d%3D%22M9%2025l9-8%208%205%2013-11%22%20fill%3D%22none%22%20stroke%3D%22%23fff%22%20stroke-width%3D%223%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22/%3E%3Ccircle%20cx%3D%2239%22%20cy%3D%2211%22%20r%3D%223.2%22%20fill%3D%22%237cf5c6%22/%3E%3C/svg%3E">
<title><?= html_escape($nama_app) ?> - BPS Provinsi Kalimantan Barat</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/app.css') ?>">
<script>document.documentElement.setAttribute('data-bs-theme', window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');</script>
<style>
.side .brand{padding:16px 16px 14px}
.side .brand-baris{display:flex;align-items:center;gap:12px}
.side .brand-logo{width:44px;height:44px;flex:none}
.side .brand-logo svg{width:44px;height:44px;display:block}
.side .brand-teks{min-width:0;flex:1}
.side .brand-teks b{display:block;margin:0;font-size:19px;font-weight:700;letter-spacing:.05em;line-height:1.1;color:#fff}
.side .brand-teks .brand-sub{margin:4px 0 0;padding:0;border:0;opacity:1;font-size:11.5px;line-height:1.3;color:#aebfdb}
/* HP/tablet: sidebar muncul dari samping lewat tombol garis tiga */
.btn-menu{display:none;flex:none;width:42px;height:42px;padding:0;border:1px solid var(--br);border-radius:10px;background:var(--card);color:var(--tx);align-items:center;justify-content:center;cursor:pointer}
.btn-menu svg{width:22px;height:22px}
.side-overlay{display:none}
@media (max-width:900px){
  .btn-menu{display:inline-flex}
  .side{position:fixed;inset:0 auto 0 0;width:270px;max-width:82vw;height:100%;overflow-y:auto;z-index:1050;transform:translateX(-105%);transition:transform .25s ease;box-shadow:none}
  .side.buka{transform:translateX(0);box-shadow:8px 0 30px rgba(0,0,0,.35)}
  .side-overlay{display:block;position:fixed;inset:0;background:rgba(7,20,41,.55);z-index:1040;opacity:0;visibility:hidden;transition:opacity .25s}
  .side-overlay.buka{opacity:1;visibility:visible}
  .top{padding:10px 14px;flex-wrap:wrap}
  .top h1{font-size:17px;flex:1;min-width:0}
  .wrap{margin-left:0}
}
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
// Menu samping SELALU sama untuk tiap peran (tidak berubah menurut periode/file yang dibuka atau diunggah).
$nm_user = (string) $this->session->userdata('nama');
$nama_peran = $akun_kab ? 'BPS ' . ((int) $this->session->userdata('kab') < 70 ? 'Kab. ' : '') . Cermat_harga::nama_kab((int) $this->session->userdata('kab')) : 'Admin';
$judul = array('dashboard' => 'Dashboard', 'rekap' => 'Rekap Konfirmasi Harga', 'komoditas' => 'Komoditas', 'data' => 'Data', 'rh' => 'RH',
               'unggah' => 'Unggah Data', 'riwayat' => 'Riwayat Periode', 'aktivitas' => 'Log Aktivitas', 'pengguna' => 'Kelola Pengguna');
?>
<div class="side-overlay" id="sideOverlay"></div>
<aside class="side" id="sidebar">
  <div class="brand"><div class="brand-baris"><div class="brand-logo"><svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo SIHARDES"><defs><linearGradient id="lgS" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1d5fd0"/><stop offset="1" stop-color="#0aa5e8"/></linearGradient></defs><rect width="48" height="48" rx="13" fill="url(#lgS)"/><rect x="10" y="27" width="5" height="11" rx="2.5" fill="#fff" opacity=".55"/><rect x="21.5" y="21" width="5" height="17" rx="2.5" fill="#fff" opacity=".55"/><rect x="33" y="15" width="5" height="23" rx="2.5" fill="#fff" opacity=".55"/><path d="M9 25l9-8 8 5 13-11" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="39" cy="11" r="3.2" fill="#7cf5c6"/></svg></div><div class="brand-teks"><b><?= html_escape(strtoupper($nama_app)) ?></b><div class="brand-sub">BPS Provinsi Kalimantan Barat</div></div></div></div>
  <nav class="menu-samping">
    <?php foreach ($menu_item as $k => $m): ?>
      <a class="<?= $menu === $k ? 'on' : '' ?>" href="<?= site_url($m[1]) . (in_array($k, array('unggah', 'aktivitas', 'pengguna')) ? '' : $q) ?>"><?= $ikon[$k] ?><?= $m[0] ?></a>
    <?php endforeach; ?>
  </nav>
  <small>Pengumpulan Fenomena<br>Perubahan Harga Perdesaan</small>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var sb = document.getElementById('sidebar'), ov = document.getElementById('sideOverlay'), bt = document.getElementById('btnMenu');
  if (!sb || !bt) return;
  function set(buka){ sb.classList.toggle('buka', buka); ov.classList.toggle('buka', buka); bt.setAttribute('aria-expanded', buka ? 'true' : 'false'); }
  bt.addEventListener('click', function(){ set(!sb.classList.contains('buka')); });
  ov.addEventListener('click', function(){ set(false); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') set(false); });
  sb.addEventListener('click', function(e){ if (e.target.closest('a')) set(false); });
  window.addEventListener('resize', function(){ if (window.innerWidth > 900) set(false); });
});
</script>
<div class="wrap">
  <header class="top bs">
    <button type="button" class="btn-menu" id="btnMenu" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
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