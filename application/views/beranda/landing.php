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

.ilus{width:100%;height:auto;max-width:520px;justify-self:center;overflow:visible}
.ilus .i-bayang{fill:var(--navy);opacity:.10}
.ilus .i-kartu2{fill:var(--bg2);stroke:var(--br);stroke-width:1.5}
.ilus .i-kartu{fill:var(--card);stroke:var(--br);stroke-width:1.5;filter:drop-shadow(0 22px 30px rgba(14,39,72,.16))}
.ilus .i-garis{fill:var(--navy);opacity:.85}
.ilus .i-garis2{fill:var(--br)}
.ilus .i-grid path{stroke:var(--br);stroke-width:1.5;stroke-dasharray:4 6}
.ilus .i-bar{fill:var(--bg2)}
.ilus .i-bar2{fill:var(--pri);opacity:.35}
.ilus .i-area{fill:var(--pri);opacity:.12}
.ilus .i-tren{stroke:var(--pri)}
.ilus .i-titik circle{fill:var(--card);stroke:var(--pri);stroke-width:4}
.ilus .i-pri{fill:var(--pri)}
.ilus .i-putih{stroke:#fff}
.ilus .i-putihf{fill:#fff}
.ilus .i-naik{fill:var(--naik)}
.ilus .i-turun{fill:var(--turun)}
.ilus .i-warn{fill:var(--warn)}
.ilus .i-padi{stroke:var(--naik)}
.ilus .i-padi path:nth-child(n+2){fill:var(--naik);fill-opacity:.18}
.ilus .i-koin{fill:var(--warn);opacity:.9}
.ilus .i-koin2{fill:none;stroke:#fff;stroke-width:3;opacity:.8}
.ilus .i-naik,.ilus .i-turun,.ilus .i-warn{filter:drop-shadow(0 10px 14px rgba(14,39,72,.22))}

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

    <svg class="ilus" viewBox="0 0 520 440" role="img" aria-label="Ilustrasi grafik perubahan harga komoditas perdesaan">
      <ellipse class="i-bayang" cx="260" cy="410" rx="200" ry="14"/>
      <!-- panel belakang -->
      <rect class="i-kartu2" x="70" y="40" width="380" height="300" rx="22" transform="rotate(-4 260 190)"/>
      <!-- panel utama -->
      <rect class="i-kartu" x="50" y="60" width="400" height="300" rx="22"/>
      <rect class="i-garis" x="82" y="92" width="96" height="12" rx="6"/>
      <rect class="i-garis2" x="82" y="114" width="64" height="8" rx="4"/>
      <circle class="i-pri" cx="416" cy="100" r="16"/>
      <path class="i-putih" d="M409 104l5-6 4 4 6-7" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
      <!-- garis bantu -->
      <g class="i-grid"><path d="M82 190h336M82 240h336M82 290h336"/></g>
      <!-- batang -->
      <rect class="i-bar" x="96"  y="250" width="30" height="60"  rx="7"/>
      <rect class="i-bar" x="148" y="226" width="30" height="84"  rx="7"/>
      <rect class="i-bar" x="200" y="262" width="30" height="48"  rx="7"/>
      <rect class="i-bar2" x="252" y="200" width="30" height="110" rx="7"/>
      <rect class="i-bar" x="304" y="236" width="30" height="74"  rx="7"/>
      <rect class="i-bar" x="356" y="214" width="30" height="96"  rx="7"/>
      <!-- area + garis tren -->
      <path class="i-area" d="M96 232 L148 200 L200 238 L267 160 L319 196 L371 150 L371 310 L96 310Z"/>
      <path class="i-tren" d="M96 232 L148 200 L200 238 L267 160 L319 196 L371 150" fill="none" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
      <g class="i-titik"><circle cx="96" cy="232" r="6"/><circle cx="148" cy="200" r="6"/><circle cx="200" cy="238" r="6"/><circle cx="267" cy="160" r="8"/><circle cx="319" cy="196" r="6"/><circle cx="371" cy="150" r="6"/></g>
      <!-- lencana naik -->
      <g transform="translate(396 238)">
        <rect class="i-naik" x="0" y="0" width="74" height="74" rx="20"/>
        <path class="i-putih" d="M37 54V22M23 36l14-14 14 14" fill="none" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
      </g>
      <!-- lencana turun -->
      <g transform="translate(14 258)">
        <rect class="i-turun" x="0" y="0" width="64" height="64" rx="18"/>
        <path class="i-putih" d="M32 20v26M20 34l12 12 12-12" fill="none" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/>
      </g>
      <!-- lencana peringatan -->
      <g transform="translate(60 14)">
        <rect class="i-warn" x="0" y="0" width="52" height="52" rx="15"/>
        <path class="i-putih" d="M26 14v16" fill="none" stroke-width="5" stroke-linecap="round"/><circle class="i-putihf" cx="26" cy="38" r="3.2"/>
      </g>
      <!-- tunas padi -->
      <g class="i-padi" fill="none" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M118 400c0-30 2-52 10-76"/><path d="M128 324c-16 2-22 14-24 26c14-2 22-12 24-26z"/><path d="M127 348c14 0 22 10 22 22c-14 0-22-8-22-22z"/><path d="M122 376c-12-2-20 4-24 14c12 4 22 0 24-14z"/>
        <path d="M168 400c0-22 2-38 8-56"/><path d="M176 344c-12 2-16 10-18 20c12-2 16-8 18-20z"/><path d="M174 370c10 0 16 7 16 16c-10 0-16-6-16-16z"/>
      </g>
      <!-- cabai & koin -->
      <circle class="i-koin" cx="400" cy="396" r="22"/><circle class="i-koin2" cx="400" cy="396" r="14"/>
      <circle class="i-koin" cx="446" cy="404" r="14"/><circle class="i-koin2" cx="446" cy="404" r="8"/>
    </svg>
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