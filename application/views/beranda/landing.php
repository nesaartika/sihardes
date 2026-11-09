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
  --pri:#1d5fd0;--pri-tx:#fff;--navy:#0e2748;--on-navy:#e6edf8;--on-navy-mut:#a9b9d3;
  --naik:#14803c;--turun:#d6342c;--stb:#5a6779;--warn:#b45309;--warnbg:#fff4e0;--ok:#14803c;--okbg:rgba(20,128,60,.12);
  --chip:#fff;
}
@media (prefers-color-scheme:dark){
  :root{
    --bg:#0f1520;--bg2:#13203a;--card:#182131;--tx:#e8edf5;--mut:#93a0b4;--br:#2a3548;
    --pri:#6aa3ff;--pri-tx:#0a1220;--navy:#0a1220;
    --naik:#4ade80;--turun:#ff7b72;--stb:#93a0b4;--warn:#fbbf24;--warnbg:#3a2c10;--ok:#4ade80;--okbg:rgba(74,222,128,.14);
    --chip:#182131;
  }
}
*{box-sizing:border-box}
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

.atas-isi{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:20px;padding-bottom:20px}
.merek{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--tx);line-height:1.25}
.logo{width:40px;height:40px;border-radius:11px;background:var(--navy);display:grid;place-items:center;flex:none}
.logo svg{width:22px;height:22px;stroke:#fff}
.merek b{display:block;font-size:17px;font-weight:700;color:var(--tx)}
.merek small{display:block;font-size:12px;color:var(--mut)}

main{flex:1;display:flex;align-items:center}
.hero-isi{width:100%;max-width:1120px;margin:0 auto;padding:24px 24px 56px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr);gap:56px;align-items:center}
.hero h1{margin:0 0 28px;font-size:clamp(2rem,4vw,3rem);line-height:1.18;font-weight:700;letter-spacing:-.01em;color:var(--tx)}
.aksi{display:flex;flex-wrap:wrap;align-items:center;gap:14px}
.fitur{list-style:none;margin:30px 0 0;padding:0;display:flex;flex-wrap:wrap;gap:10px}
.fitur li{display:flex;align-items:center;gap:8px;padding:7px 14px;background:var(--chip);border:1px solid var(--br);border-radius:99px;font-size:14px;color:var(--mut)}
.fitur li::before{content:"";width:8px;height:8px;border-radius:50%;background:var(--pri)}

.buku{margin:0;background:var(--card);border:1px solid var(--br);border-radius:16px;box-shadow:0 24px 50px rgba(14,39,72,.16);overflow:hidden}
.buku-kepala{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--br)}
.buku-kepala b{font-size:15px}
.buku-kepala span{font-size:12px;color:var(--mut)}
.gulir{overflow-x:auto}
.buku table{width:100%;border-collapse:collapse;font-variant-numeric:tabular-nums}
.buku th,.buku td{padding:13px 14px;text-align:left;border-bottom:1px solid var(--br);white-space:nowrap;font-size:14px}
.buku th:first-child,.buku td:first-child{padding-left:20px}
.buku th:last-child,.buku td:last-child{padding-right:20px}
.buku thead th{font-size:12px;font-weight:600;color:var(--mut);background:var(--bg)}
.buku tbody th{font-weight:600}
.buku .n{text-align:right}
.buku tbody tr:last-child th,.buku tbody tr:last-child td{border-bottom:0}
.naik{color:var(--naik);font-weight:600}.turun{color:var(--turun);font-weight:600}.stabil{color:var(--stb);font-weight:600}
.n span[aria-hidden]{font-size:10px;margin-right:2px}
.lencana{display:inline-block;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600}
.lencana.perlu{background:var(--warnbg);color:var(--warn)}
.lencana.wajar{background:var(--okbg);color:var(--ok)}
.tandai th,.tandai td{background:rgba(180,83,9,.06)}
.tandai th:first-child{box-shadow:inset 4px 0 0 var(--warn)}
.buku figcaption{padding:13px 20px 15px;font-size:13px;color:var(--mut);border-top:1px solid var(--br)}

.kaki{background:var(--navy);color:var(--on-navy-mut);font-size:14px}
.kaki-isi{display:flex;justify-content:space-between;align-items:center;gap:12px 24px;flex-wrap:wrap;padding-top:22px;padding-bottom:22px}
.kaki b{color:#fff;margin-right:8px}
.kaki nav{display:flex;gap:20px}
.kaki a{color:var(--on-navy);text-decoration:none;font-weight:600}
.kaki a:hover{text-decoration:underline}

@media (max-width:900px){
  .hero-isi{grid-template-columns:1fr;gap:36px;padding-bottom:40px}
}
@media (max-width:520px){
  .lebar,.hero-isi{padding-left:18px;padding-right:18px}
  .merek small{display:none}
}
</style>
</head>
<body>
<a class="lewati" href="#isi">Lewati ke isi</a>

<header>
  <div class="lebar atas-isi">
    <a class="merek" href="<?= site_url('') ?>">
      <span class="logo" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg></span>
      <span><b>Fenomena Harga</b><small>BPS Provinsi Kalimantan Barat</small></span>
    </a>
    <a class="tbl tbl-kecil" href="<?= site_url('auth/login') ?>">Login</a>
  </div>
</header>

<main id="isi" class="hero">
  <div class="hero-isi">
    <div>
      <h1>Perubahan harga perdesaan, dicermati dalam satu tempat.</h1>
      <ul class="fitur">
        <li>Unggah file Hitung RH</li>
        <li>Dihitung otomatis</li>
        <li>Rekap siap diunduh</li>
      </ul>
    </div>

    <figure class="buku" aria-labelledby="buku-ket">
      <div class="buku-kepala">
        <b>Rekap konfirmasi</b>
        <span>Contoh tampilan, angka rekaan</span>
      </div>
      <div class="gulir">
  </div>
</main>

<footer class="kaki">
  <div class="lebar kaki-isi">
    <span>Badan Pusat Statistik Provinsi Kalimantan Barat &copy; <?= date('Y') ?></span>
    <nav aria-label="Tautan footer">
      <a href="<?= site_url('auth/login') ?>">Login</a>
      <a href="https://kalbar.bps.go.id" rel="noopener" target="_blank">kalbar.bps.go.id</a>
    </nav>
  </div>
</footer>
</body>
</html>