<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CERMAT Harga - BPS Provinsi Kalimantan Barat</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<?php
$q = $p ? '?p=' . $p : '';
$ikon = array(
 'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
 'rekap'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
 'komoditas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v4H3zM3 10h18v4H3zM3 17h18v4H3z"/></svg>',
 'unggah'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>',
 'riwayat'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
);
$menu_item = array(
 'dashboard' => array('Dashboard', ''),
 'rekap'     => array('Rekap Konfirmasi', 'cermat/rekap'),
 'komoditas' => array('Semua Komoditas', 'cermat/komoditas'),
 'unggah'    => array('Unggah Data', 'cermat/unggah'),
 'riwayat'   => array('Riwayat Periode', 'cermat/riwayat'),
);
$judul = array('dashboard' => 'Dashboard', 'rekap' => 'Rekap Konfirmasi Harga', 'komoditas' => 'Komoditas',
               'unggah' => 'Unggah Data', 'riwayat' => 'Riwayat Periode');
?>
<aside class="side">
  <div class="brand"><b>CERMAT HARGA</b><span>BPS Provinsi Kalimantan Barat</span></div>
  <nav class="nav">
    <?php foreach ($menu_item as $k => $m): ?>
      <a class="<?= $menu === $k ? 'on' : '' ?>" href="<?= site_url($m[1]) . (in_array($k, array('unggah')) ? '' : $q) ?>"><?= $ikon[$k] ?><?= $m[0] ?></a>
    <?php endforeach; ?>
  </nav>
  <small>Survei Harga Perdesaan<br>Pencermatan &amp; Rekapitulasi Perubahan Harga</small>
</aside>
<div class="wrap">
  <header class="top">
    <h1><?= $judul[$menu] ?></h1>
    <?php if ( ! empty($daftar_periode)): ?>
      <select onchange="location.href=location.pathname+'?p='+this.value" title="Periode">
        <?php foreach ($daftar_periode as $pd): ?>
          <option value="<?= $pd['id'] ?>" <?= $pd['id'] === $p ? 'selected' : '' ?>><?= html_escape($pd['nama']) ?> &middot; <?= html_escape(mb_strimwidth($pd['file_asal'], 0, 38, '...')) ?> &middot; <?= $pd['dibuat'] ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($p): ?><a class="btn alt" href="<?= site_url('cermat/unduh/' . $p) ?>">Unduh Excel</a><?php endif; ?>
    <?php endif; ?>
  </header>
  <main>
