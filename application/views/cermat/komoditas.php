<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
</form>
<p class="hint" id="cnt"></p>
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
  var f = {q:'', dok:'', arah:''};   // filter yang sedang diterapkan

  function saring(){
    var n = 0;
    rows.forEach(function(r){
      var ok = (!f.q || r.dataset.q.indexOf(f.q) > -1) && (!f.dok || r.dataset.dok === f.dok) && (!f.arah || r.dataset.arah === f.arah);
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' dari ' + rows.length + ' komoditas';
  }
  document.getElementById('ffilter').addEventListener('submit', function(){
    f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value}; saring();
  });
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; f = {q:'', dok:'', arah:''}; saring();
  });
  saring();
})();
</script>
