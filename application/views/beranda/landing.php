<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?> - BPS Provinsi Kalimantan Barat</title>
<meta name="description" content="Fenomena perubahan harga perdesaan pada BPS Provinsi Kalimantan Barat.">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
:root{
  --bg:#f3f6fb;--bg2:#e3ecfb;--card:#fff;--tx:#14233b;--mut:#5a6779;--br:#dde4ef;
  --pri:#1d5fd0;--navy:#0e2748;--naik:#14803c;--turun:#d6342c;--warn:#b45309;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;min-height:100vh;display:flex;flex-direction:column;color:var(--tx);background:var(--bg);
  font:16px/1.6 "Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif}
a{text-decoration:none}
:focus-visible{outline:3px solid var(--pri);outline-offset:2px}

/* latar terang bergaya aplikasi BPS: biru muda lembut + pola titik halus */
.latar{position:relative;flex:1;display:flex;flex-direction:column;overflow:hidden;
  background:radial-gradient(1000px 520px at 88% -8%,#d3e3fb,transparent 70%),radial-gradient(700px 420px at -5% 105%,#dff3ff,transparent 70%),var(--bg)}
.latar::before{content:"";position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(rgba(29,95,208,.13) 1.2px,transparent 1.2px);background-size:26px 26px;
  -webkit-mask-image:linear-gradient(180deg,#000 0%,transparent 80%);mask-image:linear-gradient(180deg,#000 0%,transparent 80%)}
.cahaya{position:absolute;border-radius:50%;filter:blur(60px);pointer-events:none;opacity:.5}
.c1{width:360px;height:360px;background:#b7d4ff;top:-110px;right:-70px;animation:geser 16s ease-in-out infinite}
.c2{width:300px;height:300px;background:#c9f2e0;bottom:-120px;left:-90px;opacity:.55;animation:geser 19s ease-in-out infinite reverse}
.c3{display:none}
@keyframes geser{0%,100%{transform:translate(0,0)}50%{transform:translate(26px,-22px)}}

.lebar{width:100%;max-width:1160px;margin:0 auto;padding:0 24px;position:relative;z-index:2}
.atas-isi{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:20px;padding-bottom:20px}
.merek{display:flex;align-items:center;gap:12px;color:var(--tx);line-height:1.25}
.logo{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex:none;background:var(--navy);box-shadow:0 6px 16px rgba(14,39,72,.25)}
.logo svg{width:22px;height:22px;stroke:#fff}
.merek b{display:block;font-size:18px;font-weight:800;letter-spacing:.03em;color:var(--navy)}
.merek small{display:block;font-size:12px;color:var(--mut)}

.tbl{display:inline-flex;align-items:center;gap:10px;padding:13px 28px;border-radius:10px;font-weight:700;font-size:15px;line-height:1.2;
  background:var(--pri);color:#fff;box-shadow:0 8px 20px rgba(29,95,208,.30);transition:transform .15s,box-shadow .15s,filter .15s}
.tbl:hover{transform:translateY(-2px);filter:brightness(1.07);box-shadow:0 12px 24px rgba(29,95,208,.38);color:#fff}
.tbl svg{width:18px;height:18px}
.tbl-kecil{padding:9px 22px;box-shadow:none}

.hero{flex:1;display:flex;align-items:center;position:relative;z-index:2}
.hero-isi{width:100%;max-width:1160px;margin:0 auto;padding:20px 24px 64px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr);gap:48px;align-items:center}
.pil{display:inline-flex;align-items:center;gap:9px;padding:7px 16px;border-radius:99px;background:#fff;border:1px solid var(--br);
  font-size:13px;font-weight:600;color:var(--pri);margin-bottom:20px;box-shadow:0 2px 8px rgba(14,39,72,.06)}
.pil i{width:9px;height:9px;border-radius:50%;background:#00c853;animation:denyut 2s infinite}
@keyframes denyut{0%{box-shadow:0 0 0 0 rgba(0,200,83,.55)}70%{box-shadow:0 0 0 10px rgba(0,200,83,0)}100%{box-shadow:0 0 0 0 rgba(0,200,83,0)}}
.hero h1{margin:0 0 16px;font-size:clamp(2.1rem,4.4vw,3.3rem);line-height:1.15;font-weight:800;letter-spacing:-.01em;color:var(--navy)}
.hero h1 span{color:var(--pri);background:linear-gradient(90deg,#1d5fd0,#0aa5e8);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero p.lead2{color:var(--mut);font-size:17px;margin:0 0 28px;max-width:500px}
.aksi{display:flex;flex-wrap:wrap;align-items:center;gap:14px}

.ilus-bungkus{position:relative;justify-self:center;width:100%;max-width:540px}
.ilus-bungkus::before{content:"";position:absolute;inset:7% 3%;border-radius:34px;background:linear-gradient(145deg,#ffffff,#e3ecfb);border:1px solid var(--br);transform:rotate(3deg);box-shadow:0 20px 40px rgba(14,39,72,.08)}
.ilus{position:relative;width:100%;height:auto;overflow:visible;animation:melayang 7s ease-in-out infinite}
@keyframes melayang{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
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




.kaki{background:var(--navy);color:#a9b9d3;font-size:14px;position:relative;z-index:2}
.kaki-isi{display:flex;justify-content:space-between;align-items:center;gap:12px 24px;flex-wrap:wrap;padding-top:20px;padding-bottom:20px}
.kaki a{color:#e6edf8;font-weight:600}
.kaki a:hover{text-decoration:underline}

@media (max-width:900px){
  .hero-isi{grid-template-columns:1fr;gap:34px;padding-bottom:46px}
  .ilus-bungkus{max-width:440px}
}
@media (max-width:520px){
  .lebar,.hero-isi{padding-left:18px;padding-right:18px}
  .merek small{display:none}
}
@media (prefers-reduced-motion:reduce){.cahaya,.ilus,.pil i{animation:none}}
</style>
</head>
<body>
<div class="latar">
  <span class="cahaya c1"></span><span class="cahaya c2"></span><span class="cahaya c3"></span>

  <header>
    <div class="lebar atas-isi">
      <a class="merek" href="<?= site_url('') ?>">
        <span class="logo" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg></span>
        <span><b><?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></b><small>BPS Provinsi Kalimantan Barat</small></span>
      </a>
      <a class="tbl tbl-kecil" href="<?= site_url('auth/login') ?>">Login</a>
    </div>
  </header>

  <main class="hero" id="isi">
    <div class="hero-isi">
      <div>
        <span class="pil"><i></i> Fenomena Perubahan Harga Perdesaan</span>
        <h1>Selamat Datang di <span><?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></span></h1>
        <p class="lead2">Pantau fenomena perubahan harga perdesaan di Kalimantan Barat bersama BPS Provinsi Kalimantan Barat.</p>
        <div class="aksi">
          <a class="tbl" href="<?= site_url('auth/login') ?>">Mulai Sekarang <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
      </div>
      <div class="ilus-bungkus">
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
    </div>
  </main>
</div>

<footer class="kaki">
  <div class="lebar kaki-isi">
    <span>Badan Pusat Statistik Provinsi Kalimantan Barat &copy; <?= date('Y') ?></span>
    <a href="https://kalbar.bps.go.id" rel="noopener" target="_blank">kalbar.bps.go.id</a>
  </div>
</footer>
</body>
</html>