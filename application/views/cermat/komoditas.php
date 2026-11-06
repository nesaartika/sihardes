<?php
$dipilih = 0;
foreach ($komoditas as $k) if ( ! empty($k['dipilih'])) $dipilih++;
?>
<div class="card" style="margin-bottom:14px">
  <b>Arahkan komoditas dan kabupaten yang harus mengisi alasan.</b>
  <div class="hint">Periode yang sedang diatur: <b><?= html_escape($periode['nama']) ?></b> (<?= html_escape($periode['file_asal']) ?>). Akun kabupaten melihat pengaturan ini bila membuka periode yang sama.</div>
  <div class="hint">Setiap kabupaten adalah satu kolom. Kotak <b>oranye</b> = kabupaten itu punya kecamatan yang berubah melewati ambang. Cara cepat:
    (1) saring komoditas (dokumen, arah, atau cari nama), (2) klik kotak di <b>judul kolom kabupaten</b> untuk menugaskan semua komoditas yang tampil ke kabupaten itu, atau
    klik kotak di <b>awal baris</b> untuk menugaskan satu komoditas ke semua kabupaten terkait. Perubahan langsung tersimpan dan muncul di akun kabupaten.</div>
</div>
<div class="bar">
  <span class="chip" id="chipDipilih">Dikonfirmasi: <?= $dipilih ?> dari <?= count($komoditas) ?> komoditas</span>
  <button class="btn" type="button" id="bPilih">&#10003; Centang semua yang tampil</button>
  <button class="btn alt" type="button" id="bKosong">&#10005; Hapus semua centang yang tampil</button>
  <span class="hint">"Semua" = semua komoditas yang sedang tampil sesuai penyaringan di bawah.</span>
</div>
<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <select id="fpilih"><option value="">Semua pilihan</option><option value="1">Sudah dicentang</option><option value="0">Belum dicentang</option></select>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
</form>
<p class="hint" id="cnt"></p>
<div class="tw"><table id="tbl" class="matriks">
  <thead>
    <tr>
      <th style="width:52px;text-align:center"><input type="checkbox" id="semua" title="Centang / hapus centang semua komoditas yang tampil"><div class="hint" style="font-size:10px">Semua</div></th>
      <th>Dokumen</th><th>Kode</th><th style="min-width:200px">Komoditas</th><th>Arah</th><th>Perubahan</th>
      <?php foreach ($daftar_kab as $kd => $nm): ?>
        <th class="kolkab"><?= html_escape($nm) ?><br><input type="checkbox" class="kolom" data-kab="<?= (int) $kd ?>" title="Centang / hapus semua komoditas yang tampil untuk <?= html_escape($nm) ?>"></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($komoditas as $k): ?>
    <tr data-dok="<?= $k['dokumen'] ?>" data-arah="<?= $k['arah'] ?>" data-pilih="<?= ! empty($k['dipilih']) ? 1 : 0 ?>"
        data-kode="<?= html_escape($k['kode']) ?>" data-q="<?= html_escape(strtolower($k['nama'] . ' ' . $k['kode'])) ?>">
      <td style="text-align:center"><input type="checkbox" class="pilih" <?= ! empty($k['dipilih']) ? 'checked' : '' ?> title="Minta konfirmasi komoditas ini ke semua kabupaten terkait"></td>
      <td><span class="badge <?= strtolower($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
      <td><?= html_escape($k['kode']) ?></td>
      <td><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['nama']) ?></a><?php if ( ! empty($k['satuan'])): ?> <span class="hint">(<?= html_escape($k['satuan']) ?>)</span><?php endif; ?></td>
      <td><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span></td>
      <td class="<?= $k['arah'] ?>"><?= Cermat_harga::fmt($k['prov_pct'], 2) ?></td>
      <?php foreach ($daftar_kab as $kd => $nm):
          $inf = isset($k['kab_info'][$kd]) ? $k['kab_info'][$kd] : null; ?>
        <td class="selkab <?= $inf && $inf[0] > 0 ? 'flag' : '' ?>">
          <?php if ($inf): ?>
            <input type="checkbox" class="kab" data-kab="<?= (int) $kd ?>" data-flag="<?= $inf[0] > 0 ? 1 : 0 ?>" <?= $inf[1] ? 'checked' : '' ?>
                   title="<?= html_escape($nm) ?>: <?= $inf[0] > 0 ? $inf[0] . ' kecamatan berubah melewati ambang' : 'tidak ada kecamatan di atas ambang' ?>">
            <?php if ($inf[0] > 0): ?><span class="jml"><?= $inf[0] ?></span><?php endif; ?>
          <?php else: ?><span class="hint">-</span><?php endif; ?>
        </td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<style>
  table.matriks th.kolkab{text-align:center;font-size:10.5px;text-transform:none;letter-spacing:0;min-width:62px;max-width:78px;white-space:normal;line-height:1.2;padding:6px 3px;vertical-align:bottom}
  table.matriks td.selkab{text-align:center;padding:6px 3px;white-space:nowrap}
  table.matriks td.selkab.flag{background:rgba(180,83,9,.12)}
  table.matriks td.selkab .jml{font-size:11px;color:var(--warn);font-weight:700;margin-left:2px}
  table.matriks input[type=checkbox]{width:16px;height:16px;cursor:pointer}
  .tw{max-height:68vh}
</style>
<script>
(function(){
  var URL = <?= json_encode(site_url('cermat/pilih_komoditas?p=' . $p)) ?>, TOTAL = <?= count($komoditas) ?>;
  var tbl = document.getElementById('tbl'), rows = [].slice.call(tbl.querySelectorAll('tbody tr')), q = document.getElementById('q'),
      fd = document.getElementById('fdok'), fa = document.getElementById('farah'), fp = document.getElementById('fpilih'),
      cnt = document.getElementById('cnt'), semua = document.getElementById('semua');
  var kolom = [].slice.call(tbl.querySelectorAll('input.kolom'));
  var f = {q:'', dok:'', arah:'', pilih:''};

  function terlihat(){ return rows.filter(function(r){ return r.style.display !== 'none'; }); }
  function segarkan(r){   // kotak utama dan penanda baris mengikuti centang kabupaten
    var n = r.querySelectorAll('input.kab:checked').length;
    r.querySelector('input.pilih').checked = n > 0;
    r.dataset.pilih = n > 0 ? '1' : '0';
  }
  function statusKepala(){   // kotak judul kolom: tercentang bila semua sel oranye yang tampil sudah dicentang
    var v = terlihat();
    kolom.forEach(function(h){
      var sel = v.map(function(r){ return r.querySelector('input.kab[data-kab="' + h.dataset.kab + '"][data-flag="1"]'); }).filter(Boolean);
      h.checked = sel.length > 0 && sel.every(function(c){ return c.checked; });
      h.indeterminate = !h.checked && sel.some(function(c){ return c.checked; });
    });
    semua.checked = v.length > 0 && v.every(function(r){ return r.dataset.pilih === '1'; });
  }
  function saring(){
    var n = 0;
    rows.forEach(function(r){
      var ok = (!f.q || r.dataset.q.indexOf(f.q) > -1) && (!f.dok || r.dataset.dok === f.dok) && (!f.arah || r.dataset.arah === f.arah) &&
               (!f.pilih || r.dataset.pilih === f.pilih);
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' dari ' + rows.length + ' komoditas';
    statusKepala();
  }
  document.getElementById('ffilter').addEventListener('submit', function(){
    f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, pilih: fp.value}; saring();
  });
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; fp.value = ''; f = {q:'', dok:'', arah:'', pilih:''}; saring();
  });

  function kirim(kode, nilai, opsi, selesai){
    var fm = new FormData(); fm.append('kodes', kode.join(',')); fm.append('dipilih', nilai ? '1' : '0');
    if (opsi.kab) fm.append('kab', opsi.kab);
    if (opsi.massal) fm.append('massal', '1');
    if (window.CSRF) fm.append(window.CSRF.n, window.CSRF.v);
    document.body.style.cursor = 'progress';
    fetch(URL, {method:'POST', body:fm}).then(function(r){
      if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
      return r.json();
    }).then(function(j){
      document.body.style.cursor = '';
      if (!j.ok) throw 1;
      document.getElementById('chipDipilih').textContent = 'Dikonfirmasi: ' + j.dipilih + ' dari ' + TOTAL + ' komoditas';
      Object.keys(j.peta).forEach(function(kd){   // samakan centang dengan keadaan di server
        var r = rows.filter(function(x){ return x.dataset.kode === kd; })[0]; if (!r) return;
        [].forEach.call(r.querySelectorAll('input.kab'), function(c){ c.checked = j.peta[kd].indexOf(+c.dataset.kab) > -1; });
        segarkan(r);
      });
      statusKepala();
      if (selesai) selesai();
    }).catch(function(e){
      document.body.style.cursor = '';
      if (e === 0) return;
      alert('Perubahan belum tersimpan. Muat ulang halaman lalu coba lagi.'); location.reload();
    });
  }

  tbl.addEventListener('change', function(e){
    var el = e.target, tr = el.closest('tr'); if (!tr) return;
    if (el.classList.contains('kolom')) { massalKab(el.dataset.kab, el.checked); return; }
    if (el === semua) { massal(semua.checked); return; }
    if (el.classList.contains('pilih')) kirim([tr.dataset.kode], el.checked, {});                      // semua kabupaten terkait
    else if (el.classList.contains('kab')) kirim([tr.dataset.kode], el.checked, {kab: el.dataset.kab}); // satu kabupaten
  });

  function massal(nilai){         // semua komoditas yang tampil (kabupaten yang berubah saja)
    var t = terlihat(); if (!t.length) { alert('Tidak ada komoditas yang tampil.'); return; }
    kirim(t.map(function(r){ return r.dataset.kode; }), nilai, {massal: true});
  }
  function massalKab(kab, nilai){ // satu kabupaten untuk semua komoditas yang tampil
    var t = terlihat().filter(function(r){
      var c = r.querySelector('input.kab[data-kab="' + kab + '"]');
      return c && (nilai ? c.dataset.flag === '1' && !c.checked : c.checked);
    });
    if (!t.length) { statusKepala(); return; }
    kirim(t.map(function(r){ return r.dataset.kode; }), nilai, {kab: kab, massal: true});
  }
  document.getElementById('bPilih').addEventListener('click', function(){ massal(true); });
  document.getElementById('bKosong').addEventListener('click', function(){ massal(false); });
  saring();
})();
</script>