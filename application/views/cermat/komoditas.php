<div class="bar">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <span class="hint" id="cnt"></span>
</div>
<div class="tw"><table id="tbl">
  <thead><tr><th>Dokumen</th><th>Kode</th><th>Komoditas</th><th>Arah</th><th>Perubahan Provinsi</th><th>Observasi</th><th>Kecamatan Dikonfirmasi</th></tr></thead>
  <tbody>
  <?php foreach ($komoditas as $k): $nf = 0; foreach ($k['kab'] as $l) $nf += count($l); ?>
    <tr data-dok="<?= $k['dokumen'] ?>" data-arah="<?= $k['arah'] ?>" data-q="<?= html_escape(strtolower($k['nama'] . ' ' . $k['kode'])) ?>">
      <td><span class="badge <?= strtolower($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
      <td><?= html_escape($k['kode']) ?></td>
      <td><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['nama']) ?></a></td>
      <td><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span></td>
      <td class="<?= $k['arah'] ?>"><?= Cermat_harga::fmt($k['prov_pct'], 2) ?></td>
      <td><?= $k['jumlah'] ?></td>
      <td><?= $nf ?: '-' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<script>
(function(){
  var rows = [].slice.call(document.querySelectorAll('#tbl tbody tr')), q = document.getElementById('q'),
      fd = document.getElementById('fdok'), fa = document.getElementById('farah'), cnt = document.getElementById('cnt');
  function saring(){
    var t = q.value.trim().toLowerCase(), n = 0;
    rows.forEach(function(r){
      var ok = (!t || r.dataset.q.indexOf(t) > -1) && (!fd.value || r.dataset.dok === fd.value) && (!fa.value || r.dataset.arah === fa.value);
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = n + ' dari ' + rows.length + ' komoditas';
  }
  [q,fd,fa].forEach(function(e){ e.addEventListener('input', saring); e.addEventListener('change', saring); });
  saring();
})();
</script>
