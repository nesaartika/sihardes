<?php
$bisa = 0; $dipilih = 0;
foreach ($komoditas as $k) { if ( ! empty($k['kab_asli'])) { $bisa++; if ($k['dipilih']) $dipilih++; } }
?>
<div class="card" style="margin-bottom:14px">
  <b>Pilih komoditas yang akan dikonfirmasi ke kabupaten.</b>
  <div class="hint">Centang komoditas yang perlu ditanyakan alasannya. Hanya yang dicentang yang muncul di Rekap Konfirmasi, di akun kabupaten, dan di Excel hasil. Komoditas tanpa kecamatan yang berubah tidak perlu dikonfirmasi, jadi tidak bisa dicentang.</div>
</div>
<div class="bar">
  <span class="chip" id="chipDipilih">Dipilih: <?= $dipilih ?> dari <?= $bisa ?> komoditas</span>
  <button class="btn alt" type="button" id="bPilih">Pilih semua yang tampil</button>
  <button class="btn alt" type="button" id="bKosong">Kosongkan yang tampil</button>
</div>
<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <select id="fpilih"><option value="">Semua pilihan</option><option value="1">Dipilih</option><option value="0">Tidak dipilih</option></select>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
</form>
<p class="hint" id="cnt"></p>
<div class="tw"><table id="tbl">
  <thead><tr><th style="width:90px">Konfirmasi</th><th>Dokumen</th><th>Kode</th><th>Komoditas</th><th>Arah</th><th>Perubahan Provinsi</th><th>Observasi</th><th>Kecamatan Dikonfirmasi</th></tr></thead>
  <tbody>
  <?php foreach ($komoditas as $k):
      $nf = 0; foreach ($k['kab_asli'] as $l) $nf += count($l); ?>
    <tr data-dok="<?= $k['dokumen'] ?>" data-arah="<?= $k['arah'] ?>" data-bisa="<?= $nf > 0 ? 1 : 0 ?>" data-pilih="<?= $nf > 0 ? ($k['dipilih'] ? 1 : 0) : '-' ?>"
        data-kode="<?= html_escape($k['kode']) ?>" data-q="<?= html_escape(strtolower($k['nama'] . ' ' . $k['kode'])) ?>">
      <td style="text-align:center"><?php if ($nf > 0): ?><input type="checkbox" class="pilih" <?= $k['dipilih'] ? 'checked' : '' ?> title="Konfirmasi komoditas ini"><?php else: ?><span class="hint">-</span><?php endif; ?></td>
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
  var URL = <?= json_encode(site_url('cermat/pilih_komoditas?p=' . $p)) ?>, BISA = <?= (int) $bisa ?>;
  var rows = [].slice.call(document.querySelectorAll('#tbl tbody tr')), q = document.getElementById('q'),
      fd = document.getElementById('fdok'), fa = document.getElementById('farah'), fp = document.getElementById('fpilih'), cnt = document.getElementById('cnt');
  var f = {q:'', dok:'', arah:'', pilih:''};

  function saring(){
    var n = 0;
    rows.forEach(function(r){
      var ok = (!f.q || r.dataset.q.indexOf(f.q) > -1) && (!f.dok || r.dataset.dok === f.dok) && (!f.arah || r.dataset.arah === f.arah) &&
               (!f.pilih || r.dataset.pilih === f.pilih);
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' dari ' + rows.length + ' komoditas';
  }
  document.getElementById('ffilter').addEventListener('submit', function(){
    f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, pilih: fp.value}; saring();
  });
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; fp.value = ''; f = {q:'', dok:'', arah:'', pilih:''}; saring();
  });

  function kirim(kode, nilai, selesai){
    var fm = new FormData(); kode.forEach(function(c){ fm.append('kode[]', c); }); fm.append('dipilih', nilai ? '1' : '0');
    fetch(URL, {method:'POST', body:fm}).then(function(r){
      if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
      return r.json();
    }).then(function(j){
      document.getElementById('chipDipilih').textContent = 'Dipilih: ' + j.dipilih + ' dari ' + BISA + ' komoditas';
      if (selesai) selesai();
    });
  }
  document.getElementById('tbl').addEventListener('change', function(e){
    var cb = e.target; if (!cb.classList.contains('pilih')) return;
    var tr = cb.closest('tr');
    kirim([tr.dataset.kode], cb.checked, function(){ tr.dataset.pilih = cb.checked ? '1' : '0'; });
  });
  function massal(nilai){
    var target = rows.filter(function(r){ return r.style.display !== 'none' && r.dataset.bisa === '1'; });
    if (!target.length) return;
    kirim(target.map(function(r){ return r.dataset.kode; }), nilai, function(){
      target.forEach(function(r){ r.dataset.pilih = nilai ? '1' : '0'; r.querySelector('input.pilih').checked = nilai; });
    });
  }
  document.getElementById('bPilih').addEventListener('click', function(){ massal(true); });
  document.getElementById('bKosong').addEventListener('click', function(){ massal(false); });
  saring();
})();
</script>