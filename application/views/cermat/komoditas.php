<?php
$dipilih = 0; $info = array();
foreach ($komoditas as $k)
{
    if ( ! empty($k['dipilih'])) $dipilih++;
    $info[$k['kode']] = $k['kab_info'];     // [kab => [jumlah kecamatan berubah, sudah diminta?]]
}
?>
<div class="bar" style="gap:10px">
  <b>1. Kabupaten:</b>
  <select id="fkab" style="min-width:230px;font-weight:700">
    <option value="">Semua kabupaten</option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?>
  </select>
  <b>2. Komoditas:</b>
  <span class="chip" id="chipDipilih">Dikonfirmasi: <?= $dipilih ?> komoditas</span>
</div>
<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option><option value="tanpa">Kosong (tanpa data)</option></select>
  <select id="fpilih"><option value="">Semua status</option><option value="1">Sudah dicentang</option><option value="0">Belum dicentang</option></select>
  <label style="display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" id="fsemua"> Tampilkan juga yang tidak berubah</label>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
</form>
<p class="hint" id="cnt"></p>

<div class="tw"><table id="tbl">
  <thead><tr>
    <th style="width:58px;text-align:center"><input type="checkbox" id="semua" title="Centang / hapus centang semua yang tampil"></th>
    <th>Dokumen</th><th>Kode</th><th style="min-width:220px">Komoditas</th><th>Arah</th><th>Perubahan</th><th style="min-width:240px">Kabupaten yang diminta mengisi</th>
  </tr></thead>
  <tbody>
  <?php foreach ($komoditas as $k): ?>
    <tr data-dok="<?= $k['dokumen'] ?>" data-arah="<?= (int) $k['jumlah'] === 0 ? 'tanpa' : $k['arah'] ?>" data-kode="<?= html_escape($k['kode']) ?>" data-q="<?= html_escape(strtolower($k['nama'] . ' ' . $k['kode'])) ?>">
      <td style="text-align:center"><input type="checkbox" class="pilih" style="width:18px;height:18px"<?= (int) $k['jumlah'] === 0 ? ' disabled' : '' ?>></td>
      <td><span class="badge <?= strtolower($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
      <td><?= html_escape($k['kode']) ?></td>
      <td><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['nama']) ?></a><?php if ( ! empty($k['satuan'])): ?> <span class="hint">(<?= html_escape($k['satuan']) ?>)</span><?php endif; ?></td>
      <?php $tanpa = (int) $k['jumlah'] === 0; ?>
      <td><?php if ( ! $tanpa): ?><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span><?php endif; ?></td>
      <td class="<?= $tanpa ? '' : $k['arah'] ?>"><?= $tanpa ? '' : Cermat_harga::fmt($k['prov_pct'], 2) ?></td>
      <td class="siapa"></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<script>
(function(){
  var URL = <?= json_encode(site_url('cermat/pilih_komoditas?p=' . $p)) ?>;
  var INFO = <?= json_encode($info, JSON_FORCE_OBJECT) ?>, NAMA = <?= json_encode($daftar_kab) ?>;
  var tbl = document.getElementById('tbl'), rows = [].slice.call(tbl.querySelectorAll('tbody tr')),
      q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'),
      fp = document.getElementById('fpilih'), fk = document.getElementById('fkab'), fs = document.getElementById('fsemua'),
      cnt = document.getElementById('cnt'), semua = document.getElementById('semua');
  var f = {q:'', dok:'', arah:'', pilih:'', semua:false};

  // keadaan satu baris menurut kabupaten yang dipilih: [ada data?, berubah?, sudah diminta?]
  function keadaan(r){
    var inf = INFO[r.dataset.kode] || {}, kab = fk.value;
    if (kab) { var x = inf[kab]; return x ? [true, x[0] > 0, !!x[1]] : [false, false, false]; }
    var ada = false, ubah = false, minta = false;
    Object.keys(inf).forEach(function(k){ ada = true; if (inf[k][0] > 0) ubah = true; if (inf[k][1]) minta = true; });
    return [ada, ubah, minta];
  }
  function gambar(r){   // isi kotak centang dan keterangan kabupaten
    var inf = INFO[r.dataset.kode] || {}, st = keadaan(r), kab = fk.value, nama = [];
    Object.keys(inf).forEach(function(k){ if (inf[k][1]) nama.push(NAMA[k]); });
    r.querySelector('input.pilih').checked = st[2];
    r.querySelector('td.siapa').textContent = nama.length ? nama.join(', ') : '-';
    r.dataset.pilih = st[2] ? '1' : '0';
    if (kab && inf[kab] && inf[kab][0] > 0) r.querySelector('td.siapa').textContent += (nama.length ? '' : '') + '   [' + inf[kab][0] + ' kecamatan berubah di ' + NAMA[kab] + ']';
  }
  function terlihat(){ return rows.filter(function(r){ return r.style.display !== 'none'; }); }
  function saring(){
    var n = 0;
    rows.forEach(function(r){
      var st = keadaan(r), tanpa = r.dataset.arah === 'tanpa';
      // komoditas tanpa data: tampil bila dicari / "tampilkan juga..." dicentang, dan tidak saat satu kabupaten dipilih
      var ok = (tanpa ? (!fk.value && (f.semua || f.q !== '')) : (st[0] && (f.semua || st[1] || st[2]))) &&
               (!f.q || r.dataset.q.indexOf(f.q) > -1) && (!f.dok || r.dataset.dok === f.dok) && (!f.arah || r.dataset.arah === f.arah) &&
               (!f.pilih || r.dataset.pilih === f.pilih);
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' komoditas' + (fk.value ? ' untuk ' + NAMA[fk.value] : '');
    var v = terlihat(); semua.checked = v.length > 0 && v.every(function(r){ return r.dataset.pilih === '1'; });
  }
  function segar(){ rows.forEach(gambar); saring(); }
  function terapkan(){ f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, pilih: fp.value, semua: fs.checked}; segar(); }
  document.getElementById('ffilter').addEventListener('submit', terapkan);
  fk.addEventListener('change', terapkan);
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; fp.value = ''; fs.checked = false; terapkan();
  });

  function kirim(kode, nilai, opsi){
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
      document.getElementById('chipDipilih').textContent = 'Dikonfirmasi: ' + j.dipilih + ' komoditas';
      Object.keys(j.peta).forEach(function(kd){     // samakan dengan keadaan di server
        var inf = INFO[kd] || {};
        Object.keys(inf).forEach(function(k){ inf[k][1] = j.peta[kd].indexOf(+k) > -1 ? 1 : 0; });
      });
      segar();
    }).catch(function(e){
      document.body.style.cursor = '';
      if (e === 0) return;
      alert('Perubahan belum tersimpan. Muat ulang halaman lalu coba lagi.'); location.reload();
    });
  }
  tbl.addEventListener('change', function(e){
    var el = e.target; if (el === semua) { massal(semua.checked); return; }
    if (!el.classList.contains('pilih')) return;
    kirim([el.closest('tr').dataset.kode], el.checked, fk.value ? {kab: fk.value} : {});
  });
  function massal(nilai){
    var t = terlihat().filter(function(r){ return r.dataset.arah !== 'tanpa'; }); if (!t.length) { alert('Tidak ada komoditas yang tampil.'); return; }
    kirim(t.map(function(r){ return r.dataset.kode; }), nilai, {kab: fk.value, massal: true});
  }
  segar();
})();
</script>