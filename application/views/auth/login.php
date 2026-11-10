<?php $nama_app = config_item('nama_app') ?: 'SIHARDES'; $nama_app_ket = config_item('nama_app_ket') ?: 'Sistem Informasi Harga Perdesaan'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?= html_escape($nama_app) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script>document.documentElement.setAttribute('data-bs-theme', window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');</script>
<style>
  body{min-height:100vh;background:#0e2748}
  .kotak{width:100%;max-width:400px}
</style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
<div class="card kotak shadow">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-1"><a href="<?= site_url('') ?>" class="text-decoration-none text-body"><?= html_escape($nama_app) ?></a></h1>
    <p class="text-secondary small mb-3"><?= html_escape($nama_app_ket) ?><br>BPS Provinsi Kalimantan Barat</p>
    <?php if ( ! empty($galat)): ?><div class="alert alert-danger py-2 small"><?= html_escape($galat) ?></div><?php endif; ?>
    <?= form_open('auth/login') ?>
      <div class="mb-3">
        <label for="username" class="form-label fw-semibold">Email/Username</label>
        <input type="text" class="form-control" name="username" id="username" autocomplete="username" required autofocus>
      </div>
      <div class="mb-2">
        <label for="password" class="form-label fw-semibold">Password</label>
        <input type="password" class="form-control" name="password" id="password" autocomplete="current-password" required>
      </div>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="lihat">
        <label class="form-check-label" for="lihat">Lihat password</label>
      </div>
      <button class="btn btn-primary w-100" type="submit">Login</button>
    <?= form_close() ?>
    <a class="d-block text-center mt-3 pt-3 border-top small fw-semibold text-decoration-none" href="<?= site_url('') ?>">&larr; Halaman awal</a>
  </div>
</div>
<script>document.getElementById('lihat').addEventListener('change',function(){document.getElementById('password').type=this.checked?'text':'password';});</script>
</body>
</html>