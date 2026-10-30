<?php $tampil = array_filter($komoditas, function ($k) { return ! empty($k['tampil']); }); ?>
<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari komoditas / kode / kecamatan...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <?php if (empty($kunci_kab)): ?>
  <select id="fkab"><option value="">Semua kabupaten</option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select>
  <?php else: ?>
  <input type="hidden" id="fkab" value="">
  <span class="chip"><?= html_escape(Cermat_harga::nama_kab((int) $kunci_kab)) ?></span>
  <?php endif; ?>
  <label><input type="checkbox" id="fkosong"> Alasan belum diisi</label>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
</form>
<p class="hint" id="cnt"></p>
<div class="tw">
<table id="tbl">
  <thead><tr><th style="width:70px">Dokumen</th><th style="width:230px">Komoditas</th><th style="width:140px">Perubahan Harga Perdesaan Provinsi</th><th>Kabupaten</th><th style="width:380px">Alasan</th></tr></thead>
  <?php foreach ($tampil as $k):
      $rows = empty($k['kab']) ? array(0 => array()) : $k['kab'];
      $jml = count($rows); $pertama = true;
      $teks = strtolower($k['label']);
      foreach ($k['kec_semua'] as $c) if ($c['flag']) $teks .= ' ' . strtolower($c['kec']);
  ?>
  <tbody class="grp" data-dok="<?= $k['dokumen'] ?>" data-arah="<?= $k['arah'] ?>" data-kab=",<?= implode(',', array_keys($k['kab'])) ?>," data-q="<?= html_escape($teks) ?>">
    <?php foreach ($rows as $kab => $daftar):
        $kunci = $k['kode'] . '|' . $kab; $isi = isset($alasan[$kunci]) ? $alasan[$kunci] : ''; ?>
    <tr data-status="<?= $isi === '' ? 'belum' : 'sudah' ?>">
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
  var URL = <?= json_encode(site_url('cermat/simpan_alasan?p=' . $p)) ?>;
  var grp = [].slice.call(document.querySelectorAll('#tbl tbody.grp'));
  var q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'),
      fk = document.getElementById('fkab'), fo = document.getElementById('fkosong'), cnt = document.getElementById('cnt');
  var f = {q:'', dok:'', arah:'', kab:'', kosong:false};   // filter yang sedang diterapkan

  function saring(){
    var n = 0;
    grp.forEach(function(g){
      var ok = (!f.q || g.dataset.q.indexOf(f.q) > -1) && (!f.dok || g.dataset.dok === f.dok) &&
               (!f.arah || g.dataset.arah === f.arah) && (!f.kab || g.dataset.kab.indexOf(',' + f.kab + ',') > -1);
      if (ok && f.kosong) ok = !!g.querySelector('tr[data-status="belum"]');
      g.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' dari ' + grp.length + ' komoditas';
  }
  document.getElementById('ffilter').addEventListener('submit', function(){
    f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, kab: fk.value, kosong: fo.checked};
    saring();
  });
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; fk.value = ''; fo.checked = false;
    f = {q:'', dok:'', arah:'', kab:'', kosong:false}; saring();
  });
  saring();

  document.getElementById('tbl').addEventListener('change', function(e){
    var el = e.target; if (!el.classList.contains('alasan')) return;
    var fm = new FormData(); fm.append('kode', el.dataset.kode); fm.append('kab', el.dataset.kab); fm.append('teks', el.value);
    fetch(URL, {method:'POST', body:fm}).then(function(r){
      if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
      return r.json();
    }).then(function(j){
      var tr = el.closest('tr'), isi = el.value.trim() !== '';
      tr.dataset.status = isi ? 'sudah' : 'belum';
      el.classList.toggle('ok', !!j.ok); setTimeout(function(){ el.classList.remove('ok') }, 1500);
    });
  });
})();
</script>
