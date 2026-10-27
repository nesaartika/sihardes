<?php $tampil = array_filter($komoditas, function ($k) { return ! empty($k['tampil']); }); ?>
<div class="bar">
  <input type="text" id="q" placeholder="Cari komoditas / kode / kecamatan...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <select id="fkab"><option value="">Semua kabupaten</option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select>
  <label><input type="checkbox" id="fkosong"> Alasan belum diisi</label>
  <span class="hint" id="cnt"></span>
</div>
<div class="tw">
<table id="tbl">
  <thead><tr><th style="width:70px">Dokumen</th><th style="width:240px">Komoditas</th><th style="width:150px">Perubahan Harga Perdesaan Provinsi</th><th>Kabupaten</th><th style="width:330px">Alasan</th></tr></thead>
  <?php foreach ($tampil as $k):
      $rows = empty($k['kab']) ? array(0 => array()) : $k['kab'];
      $jml = count($rows); $pertama = true;
      $teks = strtolower($k['label']);
      foreach ($k['kec_semua'] as $c) if ($c['flag']) $teks .= ' ' . strtolower($c['kec']);
  ?>
  <tbody class="grp" data-dok="<?= $k['dokumen'] ?>" data-arah="<?= $k['arah'] ?>" data-kab=",<?= implode(',', array_keys($k['kab'])) ?>," data-q="<?= html_escape($teks) ?>">
    <?php foreach ($rows as $kab => $daftar):
        $kunci = $k['kode'] . '|' . $kab; $isi = isset($alasan[$kunci]) ? $alasan[$kunci] : ''; ?>
    <tr data-kosong="<?= $isi === '' ? 1 : 0 ?>">
      <?php if ($pertama): ?>
        <td rowspan="<?= $jml ?>"><span class="badge <?= strtolower($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
        <td rowspan="<?= $jml ?>"><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['label']) ?></a></td>
        <td rowspan="<?= $jml ?>"><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span><div class="hint"><?= Cermat_harga::fmt($k['prov_pct'], 2) ?></div></td>
      <?php $pertama = false; endif; ?>
      <td>
        <?php if ($daftar): ?>
          <?= html_escape(Cermat_harga::teks_kabupaten($kab, $daftar)) ?>
          <div class="hint"><?= html_escape(Cermat_harga::teks_rinci($daftar)) ?></div>
        <?php endif; ?>
      </td>
      <td><textarea class="alasan" rows="2" data-kode="<?= html_escape($k['kode']) ?>" data-kab="<?= (int) $kab ?>" placeholder="Tulis alasan..."><?= html_escape($isi) ?></textarea></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <?php endforeach; ?>
</table>
</div>
<script>
(function(){
  var P = <?= json_encode($p) ?>, URL = <?= json_encode(site_url('cermat/simpan_alasan?p=' . $p)) ?>;
  var grp = [].slice.call(document.querySelectorAll('#tbl tbody.grp'));
  var q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'),
      fk = document.getElementById('fkab'), fo = document.getElementById('fkosong'), cnt = document.getElementById('cnt');
  function saring(){
    var t = q.value.trim().toLowerCase(), n = 0;
    grp.forEach(function(g){
      var ok = (!t || g.dataset.q.indexOf(t) > -1) && (!fd.value || g.dataset.dok === fd.value) &&
               (!fa.value || g.dataset.arah === fa.value) && (!fk.value || g.dataset.kab.indexOf(','+fk.value+',') > -1);
      if (ok && fo.checked) ok = !!g.querySelector('tr[data-kosong="1"]');
      g.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = n + ' komoditas ditampilkan';
  }
  [q,fd,fa,fk,fo].forEach(function(e){ e.addEventListener('input', saring); e.addEventListener('change', saring); });
  saring();
  document.getElementById('tbl').addEventListener('change', function(e){
    var el = e.target; if (!el.classList.contains('alasan')) return;
    var fm = new FormData(); fm.append('kode', el.dataset.kode); fm.append('kab', el.dataset.kab); fm.append('teks', el.value);
    fetch(URL, {method:'POST', body:fm}).then(function(r){return r.json()}).then(function(j){
      el.classList.toggle('ok', !!j.ok); el.closest('tr').dataset.kosong = el.value.trim() ? 0 : 1;
      setTimeout(function(){ el.classList.remove('ok') }, 1500);
    });
  });
})();
</script>
