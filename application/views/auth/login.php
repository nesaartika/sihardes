<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - Fenomena Harga</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  body{align-items:center;justify-content:center;min-height:100vh;background:var(--side)}
  .kotak{width:100%;max-width:380px;margin:20px}
  .kotak h1{font-size:20px;margin:0 0 2px}
  .kotak p.sub{margin:0 0 18px;color:var(--mut);font-size:13px}
  .kotak label{display:block;font-weight:700;margin:12px 0 4px}
  .kotak input{width:100%;padding:10px;border:1px solid var(--br);border-radius:8px;background:var(--inp);color:var(--tx);font-size:14px}
  .kotak .btn{width:100%;margin-top:18px;padding:11px}
</style>
</head>
<body>
<div class="card kotak">
  <h1>Fenomena Harga</h1>
  <p class="sub">Pengumpulan Fenomena Perubahan Harga Perdesaan<br>BPS Provinsi Kalimantan Barat</p>
  <?php if ( ! empty($galat)): ?><div class="err"><?= html_escape($galat) ?></div><?php endif; ?>
  <?= form_open('auth/login') ?>
    <label for="username">Email atau nama pengguna</label>
    <input type="text" name="username" id="username" autocomplete="username" required autofocus>
    <label for="password">Kata sandi</label>
    <input type="password" name="password" id="password" autocomplete="current-password" required>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400;margin-top:10px"><input type="checkbox" id="lihat" style="width:auto"> Lihat password</label>
    <button class="btn" type="submit">Masuk</button>
  <?= form_close() ?>
  <a class="btn alt" href="<?= site_url('auth/sso_login') ?>" style="display:block;text-align:center;margin-top:10px;padding:11px;box-sizing:border-box">Login dengan SSO BPS</a>
</div>
<script>document.getElementById('lihat').addEventListener('change',function(){document.getElementById('password').type=this.checked?'text':'password';});</script>
</body>
</html>