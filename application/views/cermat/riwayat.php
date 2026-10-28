<?php if (empty($daftar)): ?>
  <div class="card empty">Belum ada periode. <a href="<?= site_url('cermat/unggah') ?>">Unggah data pertama</a>.</div>
<?php else: ?>
<div class="tw"><table>
  <thead><tr><th>Periode</th><th>File Asal</th><th>Dibuat</th><th>Komoditas</th><th>Perlu Konfirmasi</th><th>Alasan Terisi</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($daftar as $d): $r = $d['ringkas']; ?>
    <tr>
      <td><b><?= html_escape($d['nama']) ?></b> <?= $d['id'] === $p ? '<span class="pill">aktif</span>' : '' ?></td>
      <td><?= html_escape($d['file_asal']) ?></td>
      <td><?= $d['dibuat'] ?></td>
      <td><?= $r['jml_komoditas'] ?> <span class="hint">(<?= $r['naik'] ?>&uarr; <?= $r['turun'] ?>&darr; <?= $r['stabil'] ?>=)</span></td>
      <td><?= $r['perlu_konfirmasi'] ?></td>
      <td><?= $d['terisi'] ?></td>
      <td style="white-space:nowrap">
        <a class="btn alt" href="<?= site_url('cermat?p=' . $d['id']) ?>">Buka</a>
        <a class="btn alt" href="<?= site_url('cermat/unduh/' . $d['id']) ?>">Excel</a>
        <?= form_open('cermat/hapus/' . $d['id'], array('style' => 'display:inline', 'class' => 'hapus',
              'data-nama' => html_escape($d['nama']), 'data-file' => html_escape($d['file_asal']))) ?>
          <input type="hidden" name="konfirmasi" value="">
          <button class="btn red" type="submit">Hapus</button>
        <?= form_close() ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<script>
document.querySelectorAll('form.hapus').forEach(function(f){
  f.addEventListener('submit', function(e){
    var nama = f.dataset.nama, file = f.dataset.file;
    var t = prompt('Hapus periode "' + nama + '" (file: ' + file + ')?\n' +
                   'Seluruh data dan alasan di dalamnya akan hilang dan tidak bisa dikembalikan.\n\n' +
                   'Ketik persis "' + nama + '" untuk melanjutkan:');
    if (t === null) { e.preventDefault(); return; }
    if (t.trim().toLowerCase() !== nama.toLowerCase()) { alert('Nama periode tidak sama. Penghapusan dibatalkan.'); e.preventDefault(); return; }
    f.elements['konfirmasi'].value = t.trim();
  });
});
</script>