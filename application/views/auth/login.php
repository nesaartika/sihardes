<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20viewBox%3D%220%200%2048%2048%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20role%3D%22img%22%20aria-label%3D%22Logo%20SIHARDES%22%3E%3Cdefs%3E%3ClinearGradient%20id%3D%22f%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%231d5fd0%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%230aa5e8%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect%20width%3D%2248%22%20height%3D%2248%22%20rx%3D%2213%22%20fill%3D%22url%28%23f%29%22/%3E%3Crect%20x%3D%2210%22%20y%3D%2227%22%20width%3D%225%22%20height%3D%2211%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Crect%20x%3D%2221.5%22%20y%3D%2221%22%20width%3D%225%22%20height%3D%2217%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Crect%20x%3D%2233%22%20y%3D%2215%22%20width%3D%225%22%20height%3D%2223%22%20rx%3D%222.5%22%20fill%3D%22%23fff%22%20opacity%3D%22.55%22/%3E%3Cpath%20d%3D%22M9%2025l9-8%208%205%2013-11%22%20fill%3D%22none%22%20stroke%3D%22%23fff%22%20stroke-width%3D%223%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22/%3E%3Ccircle%20cx%3D%2239%22%20cy%3D%2211%22%20r%3D%223.2%22%20fill%3D%22%237cf5c6%22/%3E%3C/svg%3E">
<title>Login - <?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
:root{--tx:#14233b;--mut:#5a6779;--br:#dde4ef;--pri:#1d5fd0;--navy:#0e2748}
body{min-height:100vh;margin:0;color:var(--tx);font:15px/1.6 "Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;
  background:radial-gradient(900px 480px at 90% -10%,#d3e3fb,transparent 70%),radial-gradient(700px 420px at -5% 110%,#d7f3e8,transparent 70%),#f3f6fb}
.kotak{width:100%;max-width:400px;background:#fff;border:1px solid var(--br);border-radius:18px;padding:32px 28px;box-shadow:0 16px 40px rgba(14,39,72,.10)}
.merek{text-align:center;margin-bottom:22px}
.merek svg{width:64px;height:64px;display:block;margin:0 auto 10px;filter:drop-shadow(0 8px 14px rgba(29,95,208,.35))}
.merek b{display:block;font-size:24px;font-weight:800;letter-spacing:.04em;color:var(--navy);line-height:1.2}
.merek small{display:block;color:var(--mut);font-size:13px}
.form-control{padding:.7rem .9rem;border-color:var(--br);border-radius:10px}
.form-control:focus{border-color:var(--pri);box-shadow:0 0 0 .22rem rgba(29,95,208,.18)}
.input-group .btn-lihat{border:1px solid var(--br);border-left:0;background:#fff;color:var(--mut);border-radius:0 10px 10px 0}
.input-group .form-control{border-radius:10px 0 0 10px}
.btn-masuk{background:var(--pri);border:0;color:#fff;font-weight:700;padding:.8rem;border-radius:10px;box-shadow:0 8px 20px rgba(29,95,208,.28)}
.btn-masuk:hover{background:#1750b3;color:#fff}
.kaki{margin-top:20px;text-align:center;font-size:13px}
.kaki a{color:var(--pri);font-weight:600;text-decoration:none}
</style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
<div class="kotak">
  <div class="merek">
    <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Logo SIHARDES"><defs><linearGradient id="lgB" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1d5fd0"/><stop offset="1" stop-color="#0aa5e8"/></linearGradient></defs><rect width="48" height="48" rx="13" fill="url(#lgB)"/><rect x="10" y="27" width="5" height="11" rx="2.5" fill="#fff" opacity=".55"/><rect x="21.5" y="21" width="5" height="17" rx="2.5" fill="#fff" opacity=".55"/><rect x="33" y="15" width="5" height="23" rx="2.5" fill="#fff" opacity=".55"/><path d="M9 25l9-8 8 5 13-11" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="39" cy="11" r="3.2" fill="#7cf5c6"/></svg>
    <b><?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></b>
    <small>BPS Provinsi Kalimantan Barat</small>
  </div>
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
  <div class="kaki"><a href="<?= site_url('') ?>">&larr; Halaman awal</a></div>
</div>
<script>
document.getElementById('lihat').addEventListener('click',function(){var p=document.getElementById('password');p.type=p.type==='password'?'text':'password';});
</script>
</body>
</html>