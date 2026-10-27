<?php
$tampil = array_filter($komoditas, function ($k) { return ! empty($k['tampil']); });
$jml_baris = 0; $jml_isi = 0;
foreach ($tampil as $k)
{
    $rows = empty($k['kab']) ? array(0 => 1) : $k['kab'];
    foreach ($rows as $kab => $_) { $jml_baris++; if (isset($alasan[$k['kode'] . '|' . $kab])) $jml_isi++; }
}
?>
<div class="bar">
  <span class="chip" id="chipTotal">Total: <?= $jml_baris ?> baris</span>
  <span class="chip" style="color:var(--turun)" id="chipSudah">Sudah diisi: <?= $jml_isi ?></span>
  <span class="chip" style="color:var(--warn)" id="chipBelum">Belum diisi: <?= $jml_baris - $jml_isi ?></span>
</div>
<div class="bar">
  <input type="text" id="q" placeholder="Cari komoditas / kode / kecamatan...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <select id="fkab"><option value="">Semua kabupaten</option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select>
  <select id="fstatus"><option value="">Semua status</option><option value="belum">Belum diisi</option><option value="sudah">Sudah diisi</option></select>
  <span class="hint" id="cnt"></span>
</div>
<div class="tw">
<table id="tbl">
  <thead><tr><th style="width:70px">Dokumen</th><th style="width:230px">Komoditas</th><th style="width:140px">Perubahan Harga Perdesaan Provinsi</th><th>Kabupaten</th><th style="width:320px">Alasan</th><th style="width:120px">Status</th></tr></thead>
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
      <td><span class="badge st <?= $isi === '' ? 'belum' : 'sudah' ?>"><?= $isi === '' ? 'Belum diisi' : 'Sudah diisi' ?></span></td>
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
      fk = document.getElementById('fkab'), fs = document.getElementById('fstatus'), cnt = document.getElementById('cnt');

  function hitungStatus(){
    var rows = document.querySelectorAll('#tbl tbody.grp tr'), sudah = 0;
    rows.forEach(function(r){ if (r.dataset.status === 'sudah') sudah++; });
    document.getElementById('chipTotal').textContent = 'Total: ' + rows.length + ' baris';
    document.getElementById('chipSudah').textContent = 'Sudah diisi: ' + sudah;
    document.getElementById('chipBelum').textContent = 'Belum diisi: ' + (rows.length - sudah);
  }
  function saring(){
    var t = q.value.trim().toLowerCase(), n = 0;
    grp.forEach(function(g){
      var ok = (!t || g.dataset.q.indexOf(t) > -1) && (!fd.value || g.dataset.dok === fd.value) &&
               (!fa.value || g.dataset.arah === fa.value) && (!fk.value || g.dataset.kab.indexOf(','+fk.value+',') > -1);
      if (ok && fs.value) ok = !!g.querySelector('tr[data-status="' + fs.value + '"]');
      g.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = n + ' komoditas ditampilkan';
  }
  [q,fd,fa,fk,fs].forEach(function(e){ e.addEventListener('input', saring); e.addEventListener('change', saring); });
  saring();

  document.getElementById('tbl').addEventListener('change', function(e){
    var el = e.target; if (!el.classList.contains('alasan')) return;
    var fm = new FormData(); fm.append('kode', el.dataset.kode); fm.append('kab', el.dataset.kab); fm.append('teks', el.value);
    fetch(URL, {method:'POST', body:fm}).then(function(r){
      if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
      return r.json();
    }).then(function(j){
      var tr = el.closest('tr'), isi = el.value.trim() !== '', b = tr.querySelector('.badge.st');
      tr.dataset.status = isi ? 'sudah' : 'belum';
      b.className = 'badge st ' + (isi ? 'sudah' : 'belum'); b.textContent = isi ? 'Sudah diisi' : 'Belum diisi';
      el.classList.toggle('ok', !!j.ok); setTimeout(function(){ el.classList.remove('ok') }, 1500);
      hitungStatus(); saring();
    });
  });
})();
</script>
