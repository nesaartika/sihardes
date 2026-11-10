<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20viewBox%3D%220%200%2048%2048%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20role%3D%22img%22%20aria-label%3D%22Logo%20SIHARDES%22%3E%3Cdefs%3E%3ClinearGradient%20id%3D%22f%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%231d5fd0%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%230aa5e8%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect%20width%3D%2248%22%20height%3D%2248%22%20rx%3D%2213%22%20fill%3D%22url%28%23f%29%22/%3E%3Crect%20x%3D%2210%22%20y%3D%2227%22%20width%3D%225%22%20height%3D%2211%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Crect%20x%3D%2221.5%22%20y%3D%2221%22%20width%3D%225%22%20height%3D%2217%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Crect%20x%3D%2233%22%20y%3D%2215%22%20width%3D%225%22%20height%3D%2223%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Cpath%20d%3D%22M9%2025l9-8%208%205%2013-11%22%20fill%3D%22none%22%20stroke%3D%22%23fff%22%20stroke-width%3D%223%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22/%3E%3Ccircle%20cx%3D%2239%22%20cy%3D%2211%22%20r%3D%223.2%22%20fill%3D%22%237cf5c6%22/%3E%3C/svg%3E">
<title>Login - <?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
:root{--bg:#f3f6fb;--bg2:#e3ecfb;--card:#fff;--tx:#14233b;--mut:#5a6779;--br:#dde4ef;--pri:#1d5fd0;--navy:#0e2748;--naik:#14803c;--turun:#d6342c;--warn:#b45309}
html,body{height:100%}
body{margin:0;color:var(--tx);background:var(--bg);font:15px/1.6 "Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif}
.halaman{min-height:100vh;display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr)}
.kiri{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:center;padding:48px 56px;
  background:radial-gradient(800px 480px at 90% -5%,#cfe1fb,transparent 70%),radial-gradient(600px 400px at -10% 105%,#d7f3e8,transparent 70%),linear-gradient(160deg,#eaf1fd,#f6f9ff)}
.kiri::before{content:"";position:absolute;inset:0;pointer-events:none;background-image:radial-gradient(rgba(29,95,208,.13) 1.2px,transparent 1.2px);background-size:26px 26px;
  -webkit-mask-image:linear-gradient(180deg,#000,transparent 85%);mask-image:linear-gradient(180deg,#000,transparent 85%)}
.kiri>*{position:relative}
.merek{display:flex;align-items:center;gap:14px;color:var(--navy);margin-bottom:26px}
.merek svg{width:56px;height:56px;display:block;filter:drop-shadow(0 8px 14px rgba(29,95,208,.35))}
.merek b{display:block;font-size:28px;font-weight:800;letter-spacing:.03em;line-height:1.1}
.merek small{display:block;color:var(--mut);font-size:13px}
.kiri h2{font-size:clamp(1.4rem,2.4vw,2rem);font-weight:800;color:var(--navy);line-height:1.25;margin:0 0 8px;max-width:440px}
.kiri p{color:var(--mut);max-width:430px;margin:0 0 20px}
.ilus-bungkus{width:100%;max-width:400px}
.ilus{width:100%;height:auto;overflow:visible;animation:melayang 7s ease-in-out infinite}
@keyframes melayang{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
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





.kanan{display:flex;align-items:center;justify-content:center;padding:32px 24px;background:#fff}
.kotak{width:100%;max-width:400px}
.kotak .merek-hp{display:none}
.kotak h1{font-size:1.7rem;font-weight:800;color:var(--navy);margin:0 0 4px}
.kotak .sub{color:var(--mut);margin-bottom:22px}
.form-control{padding:.7rem .9rem;border-color:var(--br);border-radius:10px}
.form-control:focus{border-color:var(--pri);box-shadow:0 0 0 .22rem rgba(29,95,208,.18)}
.input-group .btn-lihat{border:1px solid var(--br);border-left:0;background:#fff;color:var(--mut);border-radius:0 10px 10px 0}
.input-group .form-control{border-radius:10px 0 0 10px}
.btn-masuk{background:var(--pri);border:0;color:#fff;font-weight:700;padding:.8rem;border-radius:10px;box-shadow:0 8px 20px rgba(29,95,208,.28)}
.btn-masuk:hover{background:#1750b3;color:#fff}
.kaki{margin-top:22px;padding-top:16px;border-top:1px solid var(--br);display:flex;justify-content:space-between;font-size:13px;color:var(--mut)}
.kaki a{color:var(--pri);font-weight:600;text-decoration:none}
@media (max-width:900px){
  .halaman{grid-template-columns:1fr}
  .kiri{display:none}
  .kanan{background:linear-gradient(160deg,#eaf1fd,#f6f9ff)}
  .kotak{background:#fff;border-radius:18px;padding:28px 24px;box-shadow:0 14px 36px rgba(14,39,72,.12)}
  .kotak .merek-hp{display:flex;align-items:center;gap:12px;margin-bottom:18px;color:var(--navy)}
  .kotak .merek-hp svg{width:44px;height:44px}
  .kotak .merek-hp b{font-size:20px;font-weight:800;letter-spacing:.03em}
}
@media (prefers-reduced-motion:reduce){.ilus{animation:none}}
</style>
</head>
<body>
<div class="halaman">
  <section class="kiri">
    <div class="merek"><svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo SIHARDES"><defs><linearGradient id="lgA" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1d5fd0"/><stop offset="1" stop-color="#0aa5e8"/></linearGradient></defs><rect width="48" height="48" rx="13" fill="url(#lgA)"/><rect x="10" y="27" width="5" height="11" rx="2.5" fill="#fff" opacity=".55"/><rect x="21.5" y="21" width="5" height="17" rx="2.5" fill="#fff" opacity=".55"/><rect x="33" y="15" width="5" height="23" rx="2.5" fill="#fff" opacity=".55"/><path d="M9 25l9-8 8 5 13-11" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="39" cy="11" r="3.2" fill="#7cf5c6"/></svg><div><b><?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></b><small>BPS Provinsi Kalimantan Barat</small></div></div>
    <h2>Fenomena Perubahan Harga Perdesaan</h2>
    <p><?= html_escape(config_item('nama_app_ket') ?: 'Sistem Informasi Harga Perdesaan') ?>. Masuk untuk melihat dan mengisi alasan perubahan harga.</p>
    <div class="ilus-bungkus"><svg class="ilus" viewBox="0 0 520 440" role="img" aria-label="Ilustrasi grafik perubahan harga komoditas perdesaan">
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
    </svg></div>
  </section>

  <section class="kanan">
    <div class="kotak">
      <div class="merek-hp"><svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo SIHARDES"><defs><linearGradient id="lgB" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1d5fd0"/><stop offset="1" stop-color="#0aa5e8"/></linearGradient></defs><rect width="48" height="48" rx="13" fill="url(#lgB)"/><rect x="10" y="27" width="5" height="11" rx="2.5" fill="#fff" opacity=".55"/><rect x="21.5" y="21" width="5" height="17" rx="2.5" fill="#fff" opacity=".55"/><rect x="33" y="15" width="5" height="23" rx="2.5" fill="#fff" opacity=".55"/><path d="M9 25l9-8 8 5 13-11" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="39" cy="11" r="3.2" fill="#7cf5c6"/></svg><b><?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></b></div>
      <h1>Login</h1>
      <p class="sub">Masukkan akun Anda untuk melanjutkan.</p>
      <?php if ( ! empty($galat)): ?><div class="alert alert-danger py-2 small"><?= html_escape($galat) ?></div><?php endif; ?>
      <?= form_open('auth/login') ?>
        <div class="mb-3">
          <label for="username" class="form-label fw-semibold">Email/Username</label>
          <input type="text" class="form-control" name="username" id="username" autocomplete="username" required autofocus>
        </div>
        <div class="mb-2">
          <label for="password" class="form-label fw-semibold">Password</label>
          <div class="input-group">
            <input type="password" class="form-control" name="password" id="password" autocomplete="current-password" required>
            <button type="button" class="btn btn-lihat" id="lihat" aria-label="Lihat password" title="Lihat password">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button class="btn btn-masuk w-100 mt-3" type="submit">Login</button>
      <?= form_close() ?>
      <div class="kaki"><a href="<?= site_url('') ?>">&larr; Halaman awal</a><span>BPS Provinsi Kalimantan Barat</span></div>
    </div>
  </section>
</div>
<script>
document.getElementById('lihat').addEventListener('click',function(){var p=document.getElementById('password');p.type=p.type==='password'?'text':'password';});
</script>
</body>
</html>