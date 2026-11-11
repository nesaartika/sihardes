<?php
$label = array('login' => 'Masuk', 'login_gagal' => 'Gagal masuk', 'logout' => 'Keluar', 'ganti_sandi' => 'Ganti sandi',
               'unggah' => 'Unggah data', 'isi_alasan' => 'Isi alasan', 'unduh' => 'Unduh Excel', 'hapus_periode' => 'Hapus periode',
               'buat_akun' => 'Buat akun', 'reset_sandi' => 'Reset sandi', 'ubah_akun' => 'Ubah akun', 'pilih_komoditas' => 'Pilih komoditas', 'reset_log' => 'Reset log', 'edit_akun' => 'Edit akun', 'hapus_akun' => 'Hapus akun');
?>
<div class="bar">
  <input type="text" id="q" placeholder="Cari keterangan atau pengguna...">
  <select id="faksi"><option value="">Semua aktivitas</option>
    <?php foreach ($label as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select>
  <span class="hint" id="cnt"></span>
  <?= form_open('cermat/aktivitas_reset', array('style' => 'display:inline;margin-left:auto', 'onsubmit' => "return confirm('Hapus semua log aktivitas? Tindakan ini tidak bisa dibatalkan.')")) ?>
    <button class="btn red" type="submit">Reset log</button>
  <?= form_close() ?>
</div>
<div class="tw"><table id="tbl">
  <thead><tr><th style="width:160px">Waktu</th><th style="width:120px">Pengguna</th><th style="width:150px">Aktivitas</th><th>Keterangan</th><th style="width:110px">IP</th></tr></thead>
  <tbody>
  <?php if (empty($log)): ?>
    <tr><td colspan="5" class="empty">Belum ada aktivitas tercatat.</td></tr>
  <?php endif; ?>
  <?php foreach ($log as $l): $b = in_array($l['aksi'], array('hapus_periode', 'login_gagal'), true) ? 'bahaya' : ($l['aksi'] === 'unggah' ? 'hd' : 'stabil'); ?>
    <tr data-aksi="<?= html_escape($l['aksi']) ?>" data-q="<?= html_escape(strtolower($l['username'] . ' ' . $l['keterangan'])) ?>">
      <td><?= date('d-m-Y H:i:s', strtotime($l['waktu'])) ?></td>
      <td><?= html_escape($l['username']) ?></td>
      <td><span class="badge <?= $b ?>"><?= html_escape(isset($label[$l['aksi']]) ? $label[$l['aksi']] : $l['aksi']) ?></span></td>
      <td><?= html_escape($l['keterangan']) ?></td>
      <td class="hint"><?= html_escape($l['ip']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="hint">Menampilkan maksimal 500 aktivitas terbaru.</p>
<script>
(function(){
  var rows = [].slice.call(document.querySelectorAll('#tbl tbody tr[data-aksi]')), q = document.getElementById('q'),
      fa = document.getElementById('faksi'), cnt = document.getElementById('cnt');
  function saring(){
    var t = q.value.trim().toLowerCase(), n = 0;
    rows.forEach(function(r){
      var ok = (!t || r.dataset.q.indexOf(t) > -1) && (!fa.value || r.dataset.aksi === fa.value);
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = n + ' dari ' + rows.length + ' aktivitas';
  }
  q.addEventListener('input', saring); fa.addEventListener('change', saring); saring();
})();
</script>