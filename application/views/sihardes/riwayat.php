<?php if (empty($daftar)): ?>
  <div class="card empty">Belum ada periode. <a href="<?= site_url('cermat/unggah') ?>">Unggah data pertama</a>.</div>
<?php else: ?>
<div class="tw"><table>
  <thead><tr><th>Periode</th><th>File Asal</th><th>Dibuat</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($daftar as $d): ?>
    <tr>
      <td><b><?= html_escape($d['nama']) ?></b> <span class="hint"><?= html_escape($d['jenis']) ?></span> <?= $d['id'] === $p ? '<span class="pill">aktif</span>' : '' ?></td>
      <td><?= html_escape($d['file_asal']) ?></td>
      <td style="white-space:nowrap"><?= $d['dibuat'] ?></td>
      <td style="white-space:nowrap">
        <a class="btn alt" href="<?= site_url('cermat/data_asli?p=' . $d['id']) ?>">Buka</a>
        <a class="btn alt" href="<?= site_url('cermat/unduh/' . $d['id']) ?>">Excel</a>
        <?= form_open('cermat/hapus/' . $d['id'], array('style' => 'display:inline', 'class' => 'hapus',
              'data-nama' => html_escape($d['nama']), 'data-file' => html_escape($d['file_asal']))) ?>
          <button class="btn red" type="submit">Hapus</button>
        <?= form_close() ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<style>
  .mdl{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:100}
  .mdl[hidden]{display:none}
  .mdl-box{background:var(--card);color:var(--tx);border-radius:12px;padding:22px;width:92%;max-width:420px;box-shadow:0 10px 30px rgba(0,0,0,.3)}
  .mdl-box h3{margin:0 0 8px;font-size:17px}
  .mdl-box p{margin:0 0 18px;color:var(--mut)}
  .mdl-aksi{display:flex;justify-content:flex-end;gap:10px}
</style>
<div class="mdl" id="mdl" hidden>
  <div class="mdl-box" role="dialog" aria-modal="true" aria-labelledby="mdlJudul">
    <h3 id="mdlJudul">Hapus periode?</h3>
    <p id="mdlIsi"></p>
    <div class="mdl-aksi">
      <button type="button" class="btn alt" id="mdlBatal">Batal</button>
      <button type="button" class="btn red" id="mdlHapus">Hapus</button>
    </div>
  </div>
</div>
<script>
(function(){
  var mdl = document.getElementById('mdl'), isi = document.getElementById('mdlIsi'), form = null;
  function tutup(){ mdl.hidden = true; form = null; }
  document.querySelectorAll('form.hapus').forEach(function(f){
    f.addEventListener('submit', function(e){
      e.preventDefault();
      form = f;
      isi.textContent = 'Periode "' + f.dataset.nama + '" (file: ' + f.dataset.file + ') beserta seluruh data dan alasannya akan dihapus dan tidak bisa dikembalikan.';
      mdl.hidden = false;
      document.getElementById('mdlBatal').focus();
    });
  });
  document.getElementById('mdlBatal').addEventListener('click', tutup);
  document.getElementById('mdlHapus').addEventListener('click', function(){ if (form) form.submit(); });
  mdl.addEventListener('click', function(e){ if (e.target === mdl) tutup(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutup(); });
})();
</script>