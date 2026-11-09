<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fenomena Harga - BPS Provinsi Kalimantan Barat</title>
<meta name="description" content="Aplikasi pencermatan dan rekapitulasi perubahan harga perdesaan, BPS Provinsi Kalimantan Barat.">
<style>
:root{
  --bg:#f3f6fb;--bg2:#e3ecfb;--card:#fff;--tx:#14233b;--mut:#5a6779;--br:#dde4ef;
  --pri:#1d5fd0;--pri-tx:#fff;--pri-soft:#e8f0fe;--navy:#0e2748;--on-navy:#e6edf8;--on-navy-mut:#a9b9d3;
}
@media (prefers-color-scheme:dark){
  :root{
    --bg:#0f1520;--bg2:#13203a;--card:#182131;--tx:#e8edf5;--mut:#93a0b4;--br:#2a3548;
    --pri:#6aa3ff;--pri-tx:#0a1220;--pri-soft:#1c2b47;--navy:#0a1220;
  }
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;min-height:100vh;display:flex;flex-direction:column;color:var(--tx);
  background:radial-gradient(1100px 520px at 85% -10%,var(--bg2),transparent 70%),var(--bg);
  font:16px/1.6 "Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif}
a{color:var(--pri)}
:focus-visible{outline:3px solid var(--pri);outline-offset:2px}
.lebar{width:100%;max-width:1120px;margin:0 auto;padding:0 24px}
.lewati{position:absolute;left:12px;top:-60px;background:var(--card);color:var(--tx);padding:8px 14px;border-radius:8px;z-index:50}
.lewati:focus{top:12px}

.tbl{display:inline-block;padding:12px 26px;border-radius:10px;font-weight:600;font-size:15px;text-decoration:none;line-height:1.2;background:var(--pri);color:var(--pri-tx);box-shadow:0 6px 16px rgba(29,95,208,.28)}
.tbl:hover{filter:brightness(1.07)}
.tbl-kecil{padding:9px 20px;box-shadow:none}
.tbl-garis{background:transparent;color:var(--pri);border:1px solid var(--br);box-shadow:none}
.tbl-garis:hover{filter:none;border-color:var(--pri)}

.atas-isi{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:20px;padding-bottom:20px}
.merek{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--tx);line-height:1.25}
.logo{width:40px;height:40px;border-radius:11px;background:var(--navy);display:grid;place-items:center;flex:none}
.logo svg{width:22px;height:22px;stroke:#fff}
.merek b{display:block;font-size:17px;font-weight:700;color:var(--tx)}
.merek small{display:block;font-size:12px;color:var(--mut)}

main{flex:1}
.hero-isi{width:100%;max-width:1120px;margin:0 auto;padding:40px 24px 64px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,.9fr);gap:56px;align-items:center}
.hero h1{margin:0 0 18px;font-size:clamp(2rem,4vw,3rem);line-height:1.18;font-weight:700;letter-spacing:-.01em;color:var(--tx)}
.hero p.lead{margin:0 0 30px;max-width:34em;font-size:18px;color:var(--mut)}
.aksi{display:flex;flex-wrap:wrap;align-items:center;gap:14px}

/* Cara kerja */
.alur{margin:0;padding:8px 0;list-style:none;background:var(--card);border:1px solid var(--br);border-radius:16px;box-shadow:0 24px 50px rgba(14,39,72,.14)}
.alur li{display:flex;gap:16px;padding:20px 24px;position:relative}
.alur li+li{border-top:1px solid var(--br)}
.alur .no{flex:none;width:40px;height:40px;border-radius:12px;background:var(--pri-soft);color:var(--pri);display:grid;place-items:center}
.alur .no svg{width:20px;height:20px}
.alur h3{margin:0 0 2px;font-size:16px}
.alur p{margin:0;font-size:14px;color:var(--mut)}
.alur small{display:block;margin-bottom:2px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--pri)}

/* Fitur */
.bagian{padding:8px 0 72px}
.bagian h2{margin:0 0 8px;font-size:clamp(1.5rem,2.6vw,2rem);line-height:1.25;letter-spacing:-.01em}
.bagian .sub{margin:0 0 32px;max-width:40em;color:var(--mut)}
.kisi{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px}
.kartu{background:var(--card);border:1px solid var(--br);border-radius:14px;padding:22px}
.kartu .ik{width:42px;height:42px;border-radius:12px;background:var(--pri-soft);color:var(--pri);display:grid;place-items:center;margin-bottom:14px}
.kartu .ik svg{width:21px;height:21px}
.kartu h3{margin:0 0 6px;font-size:16px}
.kartu p{margin:0;font-size:14px;color:var(--mut)}

/* Ajakan akhir */
.ajak{background:var(--navy);color:var(--on-navy);border-radius:20px;padding:40px 36px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
.ajak h2{margin:0 0 6px;color:#fff;font-size:clamp(1.3rem,2.4vw,1.75rem)}
.ajak p{margin:0;color:var(--on-navy-mut)}
.ajak .tbl{background:#fff;color:#0e2748;box-shadow:none}

.kaki{background:var(--navy);color:var(--on-navy-mut);font-size:14px}
.kaki-isi{display:flex;justify-content:space-between;align-items:center;gap:12px 24px;flex-wrap:wrap;padding-top:22px;padding-bottom:22px}
.kaki nav{display:flex;gap:20px}
.kaki a{color:var(--on-navy);text-decoration:none;font-weight:600}
.kaki a:hover{text-decoration:underline}

@media (max-width:900px){
  .hero-isi{grid-template-columns:1fr;gap:36px;padding-bottom:40px}
}
@media (max-width:520px){
  .lebar,.hero-isi{padding-left:18px;padding-right:18px}
  .merek small{display:none}
  .ajak{padding:28px 22px}
  .alur li{padding:18px}
}
</style>
</head>
<body>
<a class="lewati" href="#isi">Lewati ke isi</a>

<?php
  $tujuan = $sudah_masuk ? site_url('cermat') : site_url('auth/login');
  $teks   = $sudah_masuk ? 'Buka dashboard' : 'Login';
?>
<header>
  <div class="lebar atas-isi">
    <a class="merek" href="<?= site_url('') ?>">
      <span class="logo" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg></span>
      <span><b>Fenomena Harga</b><small>BPS Provinsi Kalimantan Barat</small></span>
    </a>
    <a class="tbl tbl-kecil" href="<?= $tujuan ?>"><?= $teks ?></a>
  </div>
</header>

<main id="isi">
  <section class="hero" aria-labelledby="judul">
    <div class="hero-isi">
      <div>
        <h1 id="judul">Perubahan harga perdesaan, dicermati dalam satu tempat.</h1>
        <p class="lead">Unggah file Hitung RH, biarkan aplikasi menghitung perubahan harga tiap komoditas, lalu tinjau kecamatan yang perlu dikonfirmasi.</p>
        <div class="aksi">
          <a class="tbl" href="<?= $tujuan ?>"><?= $sudah_masuk ? 'Buka dashboard' : 'Masuk untuk mulai' ?></a>
          <a class="tbl tbl-garis" href="#fitur">Lihat fitur</a>
        </div>
      </div>

      <ol class="alur" aria-label="Cara kerja">
        <li>
          <span class="no" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 20h16"/></svg></span>
          <div><small>Langkah 1</small><h3>Unggah file Hitung RH</h3><p>Pilih file .xlsx dan tentukan bulan serta tahunnya.</p></div>
        </li>
        <li>
          <span class="no" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8"/><path d="M8 12h2M14 12h2M8 16h2M14 16h2"/></svg></span>
          <div><small>Langkah 2</small><h3>Dihitung otomatis</h3><p>Perubahan harga provinsi dan kecamatan terhitung tanpa rumus manual.</p></div>
        </li>
        <li>
          <span class="no" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12"/><path d="M7 11l5 5 5-5"/><path d="M4 20h16"/></svg></span>
          <div><small>Langkah 3</small><h3>Rekap siap diunduh</h3><p>Isi alasan perubahan, lalu unduh hasilnya sebagai file Excel.</p></div>
        </li>
      </ol>
    </div>
  </section>

  <section id="fitur" class="bagian" aria-labelledby="judul-fitur">
    <div class="lebar">
      <h2 id="judul-fitur">Pencermatan harga tanpa kerja berulang</h2>
      <p class="sub">Dirancang untuk petugas BPS yang tiap bulan memeriksa perubahan harga perdesaan di seluruh kabupaten/kota.</p>
      <div class="kisi">
        <div class="kartu">
          <div class="ik" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg></div>
          <h3>Dashboard perubahan harga</h3>
          <p>Lihat komoditas yang naik, turun, dan stabil dalam satu halaman.</p>
        </div>
        <div class="kartu">
          <div class="ik" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg></div>
          <h3>Daftar konfirmasi</h3>
          <p>Kecamatan yang searah dengan provinsi dan berubah 5% atau lebih ditandai otomatis.</p>
        </div>
        <div class="kartu">
          <div class="ik" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg></div>
          <h3>Catatan alasan</h3>
          <p>Petugas mengisi alasan perubahan per komoditas dan kabupaten, lengkap dengan pengisinya.</p>
        </div>
        <div class="kartu">
          <div class="ik" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
          <h3>Riwayat tiap bulan</h3>
          <p>Hasil setiap periode tersimpan dan bisa dibuka kembali kapan saja.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="bagian" aria-labelledby="judul-ajak">
    <div class="lebar">
      <div class="ajak">
        <div>
          <h2 id="judul-ajak">Siap mencermati harga bulan ini?</h2>
          <p>Masuk dengan akun petugas untuk mengunggah data dan melihat hasilnya.</p>
        </div>
        <a class="tbl" href="<?= $tujuan ?>"><?= $teks ?></a>
      </div>
    </div>
  </section>
</main>

<footer class="kaki">
  <div class="lebar kaki-isi">
    <span>Badan Pusat Statistik Provinsi Kalimantan Barat &copy; <?= date('Y') ?></span>
    <nav aria-label="Tautan footer">
      <a href="<?= $tujuan ?>"><?= $teks ?></a>
      <a href="https://kalbar.bps.go.id" rel="noopener" target="_blank">kalbar.bps.go.id</a>
    </nav>
  </div>
</footer>
</body>
</html>