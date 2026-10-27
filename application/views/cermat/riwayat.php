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
        <?= form_open('cermat/hapus/' . $d['id'], array('style' => 'display:inline', 'onsubmit' => 'return konfirmasiHapus(this, ' . json_encode($d['nama']) . ')')) ?>
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
function konfirmasiHapus(form, nama){
  var t = prompt('Menghapus periode akan menghapus seluruh data dan alasan di dalamnya, dan tidak bisa dikembalikan.\n\nKetik persis "' + nama + '" untuk melanjutkan:');
  if (t === null) return false;
  if (t.trim().toLowerCase() !== nama.toLowerCase()) { alert('Nama periode tidak sama. Penghapusan dibatalkan.'); return false; }
  form.elements['konfirmasi'].value = t.trim();
  return true;
}
</script>
