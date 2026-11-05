<?php
$dipilih = 0;
foreach ($komoditas as $k) if ( ! empty($k['dipilih'])) $dipilih++;
?>
<div class="card" style="margin-bottom:14px">
  <b>Arahkan komoditas dan kabupaten yang harus mengisi alasan.</b>
  <div class="hint">Centang kotak di kolom pertama untuk meminta konfirmasi satu komoditas ke kabupaten yang kecamatannya berubah (bertanda oranye), atau centang kabupaten tertentu saja di kolom paling kanan. Yang dicentang langsung muncul di Rekap Konfirmasi dan di akun kabupaten terkait; yang tidak dicentang tidak muncul.</div>
</div>
<div class="bar">
  <span class="chip" id="chipDipilih">Dikonfirmasi: <?= $dipilih ?> dari <?= count($komoditas) ?> komoditas</span>
  <button class="btn alt" type="button" id="bPilih">Pilih semua yang tampil</button>
  <button class="btn alt" type="button" id="bKosong">Kosongkan yang tampil</button>
  <span class="hint" id="infoKab"></span>
</div>
<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <select id="fkab"><option value="">Semua kabupaten</option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select>
  <select id="fpilih"><option value="">Semua pilihan</option><option value="1">Dikonfirmasi</option><option value="0">Tidak dikonfirmasi</option></select>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
</form>
<p class="hint" id="cnt"></p>
<div class="tw"><table id="tbl">
  <thead><tr><th style="width:60px;text-align:center"><input type="checkbox" id="semua" title="Centang / hapus centang semua yang tampil"></th><th>Dokumen</th><th>Kode</th><th>Komoditas</th><th>Arah</th><th>Perubahan Provinsi</th><th>Obs.</th><th style="min-width:300px">Kabupaten yang diminta mengisi</th></tr></thead>
  <tbody>
  <?php foreach ($komoditas as $k):
      $adaflag = false; foreach ($k['kab_info'] as $inf) if ($inf[0] > 0) $adaflag = true; ?>
    <tr data-dok="<?= $k['dokumen'] ?>" data-arah="<?= $k['arah'] ?>" data-pilih="<?= ! empty($k['dipilih']) ? 1 : 0 ?>"
        data-kode="<?= html_escape($k['kode']) ?>" data-q="<?= html_escape(strtolower($k['nama'] . ' ' . $k['kode'])) ?>">
      <td style="text-align:center"><input type="checkbox" class="pilih" <?= ! empty($k['dipilih']) ? 'checked' : '' ?> title="Minta konfirmasi komoditas ini"></td>
      <td><span class="badge <?= strtolower($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
      <td><?= html_escape($k['kode']) ?></td>
      <td><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['nama']) ?></a><?php if ( ! empty($k['satuan'])): ?> <span class="hint">(<?= html_escape($k['satuan']) ?>)</span><?php endif; ?></td>
      <td><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span></td>
      <td class="<?= $k['arah'] ?>"><?= Cermat_harga::fmt($k['prov_pct'], 2) ?></td>
      <td><?= $k['jumlah'] ?></td>
      <td>
        <div class="chips">
        <?php foreach ($k['kab_info'] as $kb => $inf): ?>
          <label class="<?= $inf[0] > 0 ? 'flag' : '' ?> <?= $inf[1] ? 'on' : '' ?>" title="<?= $inf[0] > 0 ? $inf[0] . ' kecamatan berubah melewati ambang' : 'Tidak ada kecamatan yang melewati ambang' ?>">
            <input type="checkbox" class="kab" data-kab="<?= (int) $kb ?>" data-flag="<?= $inf[0] > 0 ? 1 : 0 ?>" <?= $inf[1] ? 'checked' : '' ?>>
            <?= html_escape(Cermat_harga::nama_kab($kb)) ?><?= $inf[0] > 0 ? ' (' . $inf[0] . ')' : '' ?>
          </label>
        <?php endforeach; ?>
        </div>
        <?php if ( ! $adaflag): ?><div class="hint">Tidak ada kecamatan di atas ambang; bisa tetap diarahkan secara manual.</div><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<script>
(function(){
  var URL = <?= json_encode(site_url('cermat/pilih_komoditas?p=' . $p)) ?>, TOTAL = <?= count($komoditas) ?>;
  var tbl = document.getElementById('tbl'), rows = [].slice.call(tbl.querySelectorAll('tbody tr')), q = document.getElementById('q'),
      fd = document.getElementById('fdok'), fa = document.getElementById('farah'), fk = document.getElementById('fkab'),
      fp = document.getElementById('fpilih'), cnt = document.getElementById('cnt'), semua = document.getElementById('semua'),
      infoKab = document.getElementById('infoKab');
  var f = {q:'', dok:'', arah:'', kab:'', pilih:''};

  function chip(r, kab){ return r.querySelector('input.kab[data-kab="' + kab + '"]'); }
  function segarkan(r){   // sinkronkan kotak utama + penanda baris dengan centang kabupaten
    var n = r.querySelectorAll('input.kab:checked').length;
    r.querySelector('input.pilih').checked = n > 0;
    r.dataset.pilih = n > 0 ? '1' : '0';
    [].forEach.call(r.querySelectorAll('.chips label'), function(l){ l.classList.toggle('on', l.querySelector('input').checked); });
  }
  function terlihat(){ return rows.filter(function(r){ return r.style.display !== 'none'; }); }

  function saring(){
    var n = 0;
    rows.forEach(function(r){
      var ok = (!f.q || r.dataset.q.indexOf(f.q) > -1) && (!f.dok || r.dataset.dok === f.dok) && (!f.arah || r.dataset.arah === f.arah) &&
               (!f.pilih || r.dataset.pilih === f.pilih);
      if (ok && f.kab) { var c = chip(r, f.kab); ok = !!c && (c.dataset.flag === '1' || c.checked); }
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' dari ' + rows.length + ' komoditas';
    infoKab.textContent = f.kab ? 'Tombol pilih semua / kosongkan berlaku untuk kabupaten ' + fk.options[fk.selectedIndex].text + ' saja.' : '';
  }
  document.getElementById('ffilter').addEventListener('submit', function(){
    f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, kab: fk.value, pilih: fp.value}; saring();
  });
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; fk.value = ''; fp.value = ''; f = {q:'', dok:'', arah:'', kab:'', pilih:''}; saring();
  });

  function kirim(kode, nilai, opsi, selesai){
    var fm = new FormData(); fm.append('kodes', kode.join(',')); fm.append('dipilih', nilai ? '1' : '0');
    if (opsi.kab) fm.append('kab', opsi.kab);
    if (opsi.massal) fm.append('massal', '1');
    if (window.CSRF) fm.append(window.CSRF.n, window.CSRF.v);
    fetch(URL, {method:'POST', body:fm}).then(function(r){
      if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
      return r.json();
    }).then(function(j){
      if (!j.ok) throw 1;
      document.getElementById('chipDipilih').textContent = 'Dikonfirmasi: ' + j.dipilih + ' dari ' + TOTAL + ' komoditas';
      // samakan semua centang dengan keadaan di server
      Object.keys(j.peta).forEach(function(kd){
        var r = rows.filter(function(x){ return x.dataset.kode === kd; })[0]; if (!r) return;
        [].forEach.call(r.querySelectorAll('input.kab'), function(c){ c.checked = j.peta[kd].indexOf(+c.dataset.kab) > -1; });
        segarkan(r);
      });
      if (selesai) selesai();
    }).catch(function(e){
      if (e === 0) return;
      alert('Perubahan belum tersimpan. Muat ulang halaman lalu coba lagi.'); location.reload();
    });
  }

  tbl.addEventListener('change', function(e){
    var el = e.target, tr = el.closest('tr'); if (!tr) return;
    if (el.classList.contains('pilih')) kirim([tr.dataset.kode], el.checked, {});                 // kotak utama: semua kabupaten terkait
    else if (el.classList.contains('kab')) kirim([tr.dataset.kode], el.checked, {kab: el.dataset.kab});   // satu kabupaten
  });

  function massal(nilai){
    var kab = f.kab, target = terlihat();
    if (kab) target = target.filter(function(r){ var c = chip(r, kab); return c && (nilai ? c.dataset.flag === '1' : c.checked); });
    if (!target.length) { alert('Tidak ada komoditas yang cocok untuk diubah.'); return; }
    kirim(target.map(function(r){ return r.dataset.kode; }), nilai, {kab: kab, massal: true});
  }
  document.getElementById('bPilih').addEventListener('click', function(){ massal(true); });
  document.getElementById('bKosong').addEventListener('click', function(){ massal(false); });
  semua.addEventListener('change', function(){ massal(semua.checked); });
  saring();
})();
</script>