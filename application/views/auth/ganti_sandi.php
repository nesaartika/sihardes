<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ganti Kata Sandi - <?= html_escape(config_item('nama_app') ?: 'SIHARDES') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  body{align-items:center;justify-content:center;min-height:100vh;background:var(--side)}
  .kotak{width:100%;max-width:420px;margin:20px}
  .kotak h1{font-size:20px;margin:0 0 2px}
  .kotak p.sub{margin:0 0 14px;color:var(--mut);font-size:13px}
  .kotak label{display:block;font-weight:700;margin:12px 0 4px}
  .kotak input{width:100%;padding:10px;border:1px solid var(--br);border-radius:8px;background:var(--inp);color:var(--tx);font-size:14px}
  .kotak .btn{margin-top:18px;padding:10px 18px}
  .info{background:var(--warnbg);color:var(--warn);padding:10px;border-radius:8px;margin-bottom:12px;font-size:13px}
</style>
</head>
<body>
<div class="card kotak">
  <h1>Ganti Kata Sandi</h1>
  <p class="sub">Masuk sebagai <b><?= html_escape($this->session->userdata('nama')) ?></b> (<?= html_escape($this->session->userdata('username')) ?>)</p>
  <?php if ( ! empty($wajib)): ?><div class="info">Kata sandi masih bawaan. Demi keamanan, ganti dulu sebelum memakai aplikasi.</div><?php endif; ?>
  <?php if ( ! empty($galat)): ?><div class="err"><?= html_escape($galat) ?></div><?php endif; ?>
  <?= form_open('auth/ganti_sandi') ?>
    <label for="sandi_lama">Kata sandi lama</label>
    <input type="password" name="sandi_lama" id="sandi_lama" autocomplete="current-password" required>
    <label for="sandi_baru">Kata sandi baru (minimal 8 karakter)</label>
    <input type="password" name="sandi_baru" id="sandi_baru" autocomplete="new-password" required>
    <label for="sandi_ulang">Ulangi kata sandi baru</label>
    <input type="password" name="sandi_ulang" id="sandi_ulang" autocomplete="new-password" required>
    <button class="btn" type="submit">Simpan</button>
    <?php if (empty($wajib)): ?> <a class="btn alt" href="<?= site_url('cermat') ?>">Batal</a><?php endif; ?>
    <a href="<?= site_url('auth/logout') ?>" style="float:right;margin-top:26px;font-size:13px">Keluar</a>
  <?= form_close() ?>
</div>
</body>
</html>