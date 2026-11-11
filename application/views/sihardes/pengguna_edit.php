<div class="card f" style="max-width:640px">
  <h2>Edit akun</h2>
  <?php if ( ! empty($galat)): ?><div class="err"><?= html_escape($galat) ?></div><?php endif; ?>
  <?= form_open('sihardes/pengguna_edit/' . $u['id']) ?>
    <label for="email">Email / nama pengguna</label>
    <input type="text" name="email" id="email" value="<?= html_escape($f['email']) ?>" required style="width:100%">
    <label for="nama">Nama</label>
    <input type="text" name="nama" id="nama" value="<?= html_escape($f['nama']) ?>" required style="width:100%">
    <label for="role">Role</label>
    <select name="role" id="role" style="width:100%" <?= $saya ? 'disabled' : '' ?>>
      <option value="kabupaten" <?= $f['role'] === 'kabupaten' ? 'selected' : '' ?>>Kabupaten</option>
      <option value="admin" <?= $f['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <?php if ($saya): ?><input type="hidden" name="role" value="<?= html_escape($f['role']) ?>"><?php endif; ?>
    <div id="boxkab">
      <label for="kab">Kabupaten</label>
      <select name="kab" id="kab" style="width:100%"><option value="">Pilih kabupaten</option>
        <?php foreach ($kab_daftar as $kd => $nm): ?><option value="<?= $kd ?>" <?= (int) $f['kab'] === (int) $kd ? 'selected' : '' ?>><?= html_escape($nm) ?></option><?php endforeach; ?></select>
    </div>
    <p class="hint">Kata sandi tidak berubah di sini. Untuk membuat kata sandi baru, pakai tombol Reset sandi di daftar akun.<?= $domain !== '' ? ' Email harus berdomain @' . html_escape($domain) . '.' : '' ?></p>
    <p><button class="btn" type="submit">Simpan</button> <a class="btn alt" href="<?= site_url('sihardes/pengguna') ?>">Batal</a></p>
  <?= form_close() ?>
</div>
<script>
(function(){
  var r = document.getElementById('role'), b = document.getElementById('boxkab');
  function atur(){ b.style.display = r.value === 'kabupaten' ? '' : 'none'; }
  r.addEventListener('change', atur); atur();
})();
</script>