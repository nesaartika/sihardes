<?php
$dipilih = 0; $info = array();
$sheet = ! empty($periode['mode_sheet']);      // file bukan tabel harga: komoditas x wilayah dibentuk dari sheet
$dok_daftar = array();
foreach ($komoditas as $k) $dok_daftar[$k['dokumen']] = 1;
$dok_daftar = array_keys($dok_daftar);
foreach ($komoditas as $k)
{
    if ( ! empty($k['dipilih'])) $dipilih++;
    $info[$k['kode']] = $k['kab_info'];     // [kab => [jumlah kecamatan berubah, sudah diminta?]]
}
?>
<div class="bs">
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <b>1. <?= $sheet ? 'Wilayah' : 'Kabupaten' ?>:</b>
  <select id="fkab" class="form-select form-select-sm fw-bold" style="min-width:230px">
    <option value="">Semua <?= $sheet ? 'wilayah' : 'kabupaten' ?></option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?>
  </select>
  <b class="ms-2">2. Komoditas:</b>
  <span class="chip" id="chipDipilih">Dikonfirmasi: <?= $dipilih ?> komoditas</span>
  <?php if ( ! $sheet): ?>
  <?= form_open('cermat/lengkapi_komoditas?p=' . $p, array('style' => 'display:inline;margin:0')) ?>
    <button class="btn btn-outline-primary btn-sm" type="submit" title="Masukkan komoditas di sheet RH yang belum ada pada periode ini (nilainya kosong)">Lengkapi komoditas kosong</button>
  <?= form_close() ?>
  <?php else: ?>
  <a class="btn btn-outline-primary btn-sm" href="<?= site_url('cermat/rekap?ringkasan=1&p=' . $p) ?>" title="Ringkasan naik / turun per sheet (baca-saja)">Ringkasan per sheet</a>
  <?php endif; ?>
</div>
<form class="row g-2 align-items-center mb-2" id="ffilter" onsubmit="return false">
  <div class="col-12 col-md-auto"><input type="text" class="form-control form-control-sm" id="q" placeholder="Cari nama / kode komoditas..." style="min-width:250px"></div>
  <div class="col-auto"><select class="form-select form-select-sm" id="fdok"><option value="">Semua dokumen</option><?php foreach ($dok_daftar as $dk): ?><option><?= html_escape($dk) ?></option><?php endforeach; ?></select></div>
  <div class="col-auto"><select class="form-select form-select-sm" id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option><option value="tanpa">Kosong (tanpa data)</option></select></div>
  <div class="col-auto"><select class="form-select form-select-sm" id="fpilih"><option value="">Semua status</option><option value="1">Sudah dicentang</option><option value="0">Belum dicentang</option></select></div>
  <div class="col-auto"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="fsemua" checked><label class="form-check-label" for="fsemua">Tampilkan juga yang tidak berubah &amp; yang kosong (tanpa data)</label></div></div>
  <div class="col-auto d-flex gap-2">
    <button class="btn btn-primary btn-sm" type="submit">Terapkan</button>
    <button class="btn btn-outline-secondary btn-sm" type="button" id="breset">Reset</button>
  </div>
</form>
<p class="text-secondary small mb-2" id="cnt"></p>

<div class="tw"><table id="tbl" class="table table-sm align-middle mb-0">
  <thead><tr>
    <th style="width:58px;text-align:center"><input type="checkbox" class="form-check-input" id="semua" title="Centang / hapus centang semua yang tampil"></th>
    <th>Dokumen</th><th>Kode</th><th style="min-width:220px">Komoditas</th><th>Arah</th><th>Perubahan</th><th style="min-width:240px"><?= $sheet ? 'Wilayah' : 'Kabupaten' ?> yang diminta mengisi</th>
  </tr></thead>
  <tbody>
  <?php foreach ($komoditas as $k): ?>
    <tr data-dok="<?= $k['dokumen'] ?>" data-arah="<?= (int) $k['jumlah'] === 0 ? 'tanpa' : $k['arah'] ?>" data-kode="<?= html_escape($k['kode']) ?>" data-q="<?= html_escape(strtolower($k['nama'] . ' ' . $k['kode'])) ?>">
      <td style="text-align:center"><input type="checkbox" class="form-check-input pilih" style="width:18px;height:18px"></td>
      <td><span class="badge <?= Cermat_harga::kelas_dok($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
      <td><?= html_escape(isset($k['kode_asli']) ? $k['kode_asli'] : $k['kode']) ?></td>
      <td><?php if ( ! empty($k['dari_sheet'])): ?><?= html_escape($k['nama']) ?><?php else: ?><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['nama']) ?></a><?php endif; ?><?php if ( ! empty($k['satuan'])): ?> <span class="hint">(<?= html_escape($k['satuan']) ?>)</span><?php endif; ?></td>
      <?php $tanpa = (int) $k['jumlah'] === 0; ?>
      <td><?php if ( ! $tanpa): ?><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span><?php endif; ?></td>
      <td class="<?= $tanpa ? '' : $k['arah'] ?>"><?= $tanpa ? '' : (isset($k['nilai_teks']) ? html_escape($k['nilai_teks']) : Cermat_harga::fmt($k['prov_pct'], 2)) ?></td>
      <td class="siapa"></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<script>
(function(){
  var URL = <?= json_encode(site_url('cermat/pilih_komoditas?p=' . $p)) ?>;
  var INFO = <?= json_encode($info, JSON_FORCE_OBJECT) ?>, NAMA = <?= json_encode(array_map(function ($kd) { return Cermat_harga::nama_kab($kd); }, array_combine(array_keys($daftar_kab), array_keys($daftar_kab)))) ?>;
  var tbl = document.getElementById('tbl'), rows = [].slice.call(tbl.querySelectorAll('tbody tr')),
      q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'),
      fp = document.getElementById('fpilih'), fk = document.getElementById('fkab'), fs = document.getElementById('fsemua'),
      cnt = document.getElementById('cnt'), semua = document.getElementById('semua');
  var f = {q:'', dok:'', arah:'', pilih:'', semua:true};   // bawaan: semua komoditas di file tampil
  var sibuk = false;                                         // cegah klik beruntun saat permintaan masih berjalan

  function urut(o){ return Object.keys(o).sort(function(a, b){ return a - b; }); }   // urut menurut kode kabupaten

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
    urut(inf).forEach(function(k){ if (inf[k][1] && (!kab || k === kab)) nama.push(NAMA[k] || k); });   // kabupaten difilter: hanya itu yang muncul
    var cb = r.querySelector('input.pilih'); cb.checked = st[2];
    cb.disabled = false;
    cb.title = (r.dataset.arah === 'tanpa' && !kab) ? 'Komoditas kosong: dicentang = semua kabupaten diminta mengisi alasan' : '';
    var total = Object.keys(NAMA).length, sel = r.querySelector('td.siapa');
    sel.textContent = nama.length ? (!kab && nama.length >= total ? 'Semua wilayah' : nama.join(', ')) : '-';
    r.dataset.pilih = st[2] ? '1' : '0';
    if (kab && inf[kab] && inf[kab][0] > 0) sel.textContent += '   [' + inf[kab][0] + ' kecamatan berubah]';
  }
  function terlihat(){ return rows.filter(function(r){ return r.style.display !== 'none'; }); }
  function saring(){
    var n = 0, kab = fk.value;
    rows.forEach(function(r){
      var st = keadaan(r), tanpa = r.dataset.arah === 'tanpa', ok;
      if (kab) ok = st[0] && (tanpa ? true : (f.semua || st[1] || st[2]));      // kabupaten dipilih: hanya komoditas milik kabupaten itu
      else     ok = tanpa ? (f.semua || f.q !== '') : (st[0] && (f.semua || st[1] || st[2]));
      ok = ok && (!f.q || r.dataset.q.indexOf(f.q) > -1) && (!f.dok || r.dataset.dok === f.dok) && (!f.arah || r.dataset.arah === f.arah) &&
           (!f.pilih || r.dataset.pilih === f.pilih);
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' komoditas' + (kab ? ' untuk ' + NAMA[kab] : '');
    // kotak "pilih semua": centang bila semua yang tampil tercentang, setengah bila sebagian
    var v = terlihat(), c = v.filter(function(r){ return r.dataset.pilih === '1'; }).length;
    semua.checked = v.length > 0 && c === v.length;
    semua.indeterminate = c > 0 && c < v.length;
    semua.disabled = v.length === 0;
  }
  function segar(){ rows.forEach(gambar); saring(); }
  function terapkan(){ f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, pilih: fp.value, semua: fs.checked}; segar(); }
  document.getElementById('ffilter').addEventListener('submit', terapkan);
  fk.addEventListener('change', terapkan);
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; fp.value = ''; fs.checked = true; terapkan();
  });

  function kunciUI(ya){ sibuk = ya; document.body.style.cursor = ya ? 'progress' : ''; semua.disabled = ya; [].forEach.call(tbl.querySelectorAll('input.pilih'), function(c){ c.disabled = ya; }); }
  function kirim(kode, nilai, opsi){
    if (sibuk) return;
    var fm = new FormData(); fm.append('kodes', kode.join(',')); fm.append('dipilih', nilai ? '1' : '0');
    if (opsi.kab) fm.append('kab', opsi.kab);
    if (opsi.massal) fm.append('massal', '1');
    if (window.CSRF) fm.append(window.CSRF.n, window.CSRF.v);
    kunciUI(true);
    fetch(URL, {method:'POST', body:fm}).then(function(r){
      if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
      return r.json();
    }).then(function(j){
      if (!j.ok) throw 1;
      document.getElementById('chipDipilih').textContent = 'Dikonfirmasi: ' + j.dipilih + ' komoditas';
      Object.keys(j.peta).forEach(function(kd){     // samakan dengan keadaan di server
        var inf = INFO[kd] || (INFO[kd] = {});
        j.peta[kd].forEach(function(k){ if (!inf[k]) inf[k] = [0, 1]; });     // kabupaten tanpa data (komoditas kosong) yang baru diarahkan
        Object.keys(inf).forEach(function(k){ inf[k][1] = j.peta[kd].indexOf(+k) > -1 ? 1 : 0; });
      });
      kunciUI(false); segar();
    }).catch(function(e){
      kunciUI(false);
      if (e === 0) return;
      alert('Perubahan belum tersimpan. Muat ulang halaman lalu coba lagi.'); location.reload();
    });
  }
  tbl.addEventListener('change', function(e){
    var el = e.target;
    if (el === semua) { massal(semua.checked); return; }
    if (!el.classList.contains('pilih')) return;
    kirim([el.closest('tr').dataset.kode], el.checked, fk.value ? {kab: fk.value} : {});
  });
  function massal(nilai){
    var t = terlihat(); if (!t.length) { alert('Tidak ada komoditas yang tampil.'); segar(); return; }
    kirim(t.map(function(r){ return r.dataset.kode; }), nilai, {kab: fk.value, massal: true});
  }
  segar();
})();
</script>