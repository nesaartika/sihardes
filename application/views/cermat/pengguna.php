<?php $peran = array('admin' => 'Admin', 'kabupaten' => 'Kabupaten'); ?>
<?php if ( ! empty($sandi_baru)): ?>
  <div class="card" style="border-left:4px solid var(--ok);margin-bottom:14px">
    <b>Kata sandi sementara untuk <?= html_escape($sandi_baru['email']) ?></b><br>
    <span style="font-size:20px;font-family:monospace;letter-spacing:1px"><?= html_escape($sandi_baru['sandi']) ?></span>
    <div class="hint">Catat dan berikan kepada yang bersangkutan sekarang. Kata sandi ini tidak akan ditampilkan lagi, dan wajib diganti saat masuk pertama kali.</div>
  </div>
<?php endif; ?>
<?php if ( ! empty($galat)): ?><div class="err"><?= html_escape($galat) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:16px">
  <h2>Tambah akun</h2>
  <?= form_open('cermat/pengguna_tambah', array('class' => 'bar', 'style' => 'margin:0')) ?>
    <input type="text" name="email" placeholder="email<?= $domain !== '' ? '@' . html_escape($domain) : '' ?>" required style="min-width:260px">
    <input type="text" name="nama" placeholder="Nama pemilik akun" required>
    <select name="role" id="role">
      <option value="kabupaten">Kabupaten</option><option value="admin">Admin</option>
    </select>
    <select name="kab" id="kab"><option value="">Pilih kabupaten</option>
      <?php foreach ($kab_daftar as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select>
    <button class="btn" type="submit">Buat akun</button>
  <?= form_close() ?>
  <div class="hint">Kata sandi sementara dibuat otomatis dan ditampilkan satu kali setelah akun dibuat.<?= $domain !== '' ? ' Email harus berdomain @' . html_escape($domain) . '.' : '' ?></div>
</div>

<div class="tw"><table>
  <thead><tr><th>Email / Nama pengguna</th><th>Nama</th><th>Role</th><th>Kabupaten</th><th>Status</th><th>Terakhir masuk</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($akun as $a): $saya = (int) $a['id'] === (int) $this->session->userdata('user_id'); ?>
    <tr>
      <td><?= html_escape($a['username']) ?><?= $saya ? ' <span class="pill">Anda</span>' : '' ?></td>
      <td><?= html_escape($a['nama']) ?></td>
      <td><span class="badge <?= $a['role'] === 'admin' ? 'hd' : 'hkd' ?>"><?= html_escape(isset($peran[$a['role']]) ? $peran[$a['role']] : $a['role']) ?></span></td>
      <td><?= $a['kab'] ? html_escape(Cermat_harga::nama_kab((int) $a['kab'])) : '-' ?></td>
      <td><?= $a['aktif'] ? '<span class="badge sudah">Aktif</span>' : '<span class="badge bahaya">Nonaktif</span>' ?>
          <?= $a['wajib_ganti'] ? '<div class="hint">belum ganti sandi</div>' : '' ?></td>
      <td class="hint"><?= $a['terakhir_login'] ? date('d-m-Y H:i', strtotime($a['terakhir_login'])) : 'belum pernah' ?></td>
      <td style="white-space:nowrap">
        <?= form_open('cermat/pengguna_reset/' . $a['id'], array('style' => 'display:inline')) ?>
          <button class="btn alt" type="submit" title="Buat kata sandi sementara baru">Reset sandi</button>
        <?= form_close() ?>
        <a class="btn alt" href="<?= site_url('cermat/pengguna_edit/' . $a['id']) ?>">Edit</a>
        <?php if ( ! $saya): ?>
        <?= form_open('cermat/pengguna_aktif/' . $a['id'], array('style' => 'display:inline')) ?>
          <button class="btn <?= $a['aktif'] ? 'red' : 'alt' ?>" type="submit"><?= $a['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
        <?= form_close() ?>
        <?= form_open('cermat/pengguna_hapus/' . $a['id'], array('style' => 'display:inline', 'onsubmit' => "return confirm('Hapus akun " . html_escape($a['username']) . "? Tindakan ini tidak bisa dibatalkan.')")) ?>
          <button class="btn red" type="submit">Hapus</button>
        <?= form_close() ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<script>
(function(){
  var r = document.getElementById('role'), k = document.getElementById('kab');
  function atur(){ k.style.display = r.value === 'kabupaten' ? '' : 'none'; }
  r.addEventListener('change', atur); atur();
})();
</script>