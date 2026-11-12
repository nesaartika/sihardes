<style>
tr .stat{margin-top:4px;font-size:12px;font-weight:600}
tr .stat.ok{color:var(--ok)} tr .stat.blm{color:var(--warn)} tr .stat.kosong{color:var(--stb);font-weight:400}
tr.terkunci textarea.alasan,tr.terkunci input.pengisi{background:rgba(20,128,60,.07);border:1px solid var(--ok);resize:none;cursor:default}
td.oleh .aksi{display:flex;gap:6px;margin-top:6px;flex-wrap:wrap}
td.oleh .aksi button{padding:4px 12px;font-size:12.5px;line-height:1.4;cursor:pointer}
tr.terkunci .b-simpan,tr.terkunci .b-batal,tr:not(.terkunci) .b-edit,tr:not(.mengedit) .b-batal{display:none}
.bs tr.kab-head th{padding:10px}
.bs td.oleh .aksi .btn{padding:3px 10px;font-size:12.5px}
.cetak-teks,.cetak-judul{display:none}
.hint.lalu{margin-top:4px;font-size:12px}
.cepat{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.cepat .chipb{border:0.5px solid var(--bd,#ccc);background:transparent;color:inherit;border-radius:999px;padding:2px 10px;font-size:12px;cursor:pointer}
tr.terkunci .cepat,.pkunci .cepat,.pkunci .b-edit,.pkunci .b-hapus,.pkunci .b-simpan{display:none!important}
@page{size:A4 landscape;margin:12mm}
@media print{
  .side,header.top,#ffilter,#cnt,#bnr,.btn-menu,td.oleh .aksi,.cepat,.stat,.hint.lalu,.aviso-kunci{display:none!important}
  main{margin:0!important;padding:0!important;width:100%!important;max-width:100%!important}
  textarea.alasan{display:none!important}
  .cetak-teks{display:block;white-space:pre-wrap}
  .cetak-judul{display:block;font-weight:700;font-size:15px;margin-bottom:8px}
  input.pengisi{border:none!important;background:none!important;padding:0!important;box-shadow:none!important}
  tr{break-inside:avoid}
  .tw{overflow:visible!important}
}
</style>
<?php
$alasan_bawaan = array('Pengaruh cuaca / curah hujan', 'Musim panen', 'Musim tanam', 'Gagal panen / serangan hama', 'Pasokan dari luar daerah berkurang', 'Pasokan melimpah', 'Kenaikan ongkos angkut / BBM', 'Menjelang hari besar keagamaan', 'Permintaan meningkat', 'Mengikuti tren harga nasional');
$periode_terkunci = ! empty($periode_terkunci);
$alasan_lalu = isset($alasan_lalu) && is_array($alasan_lalu) ? $alasan_lalu : array();
?>
<?php
// Hanya komoditas yang dicentang (diarahkan ke kabupaten) yang tampil; dikelompokkan per kabupaten, urut menurut kode kabupaten
$tampil = array_filter($komoditas, function ($k) { return ! empty($k['kab']); });
$admin = empty($kunci_kab);
$sheet = ! empty($periode['mode_sheet']);      // file bukan tabel harga: komoditas x wilayah dibentuk dari sheet
$dok_daftar = array();
foreach ($tampil as $k) $dok_daftar[$k['dokumen']] = 1;
$dok_daftar = array_keys($dok_daftar);
$per = array(); $total = 0; $belum = 0;
foreach ($tampil as $k)
{
    foreach ($k['kab'] as $kb => $daftar)
    {
        $isi = isset($alasan[$k['kode'] . '|' . $kb]) && trim($alasan[$k['kode'] . '|' . $kb]) !== '';
        $total++; if ( ! $isi) $belum++;
        $per[$kb][] = array($k, $daftar);
    }
}
ksort($per);
?>
<div class="bs<?= ( ! $admin && $periode_terkunci) ? ' pkunci' : '' ?>">
<div class="cetak-judul">Rekap Konfirmasi - <?= html_escape(isset($periode['nama']) ? $periode['nama'] : '') ?> &middot; dicetak <?= date('d-m-Y H:i') ?></div>
<?php if ($periode_terkunci): ?>
<div class="alert alert-warning py-2 mb-3 aviso-kunci"><?= $admin ? 'Periode ini dikunci: akun kabupaten tidak bisa lagi mengubah alasan. Admin masih bisa mengedit.' : 'Periode ini sudah dikunci oleh admin provinsi, alasan tidak bisa diubah lagi.' ?></div>
<?php endif; ?>
<form class="row g-2 align-items-center mb-3" id="ffilter" onsubmit="return false">
  <div class="col-12 col-md-auto"><input type="text" class="form-control form-control-sm" id="q" placeholder="Cari komoditas / kode / kecamatan..." style="min-width:250px"></div>
  <div class="col-auto"><select class="form-select form-select-sm" id="fdok"><option value="">Semua dokumen</option><?php foreach ($dok_daftar as $dk): ?><option><?= html_escape($dk) ?></option><?php endforeach; ?></select></div>
  <div class="col-auto"><select class="form-select form-select-sm" id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option><option value="tanpa">Kosong (tanpa data)</option></select></div>
  <?php if ($admin): ?>
  <div class="col-auto"><select class="form-select form-select-sm" id="fkab"><option value="">Semua <?= $sheet ? 'wilayah' : 'kabupaten' ?></option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select></div>
  <?php else: ?>
  <input type="hidden" id="fkab" value="">
  <div class="col-auto"><span class="chip"><?= html_escape(Sihardes_harga::nama_kab((int) $kunci_kab)) ?></span></div>
  <?php endif; ?>
  <div class="col-auto d-flex gap-2">
    <button class="btn btn-primary btn-sm" type="submit">Terapkan</button>
    <button class="btn btn-outline-secondary btn-sm" type="button" id="breset">Reset</button>
    <?php if ($admin): ?><a class="btn btn-outline-primary btn-sm" href="<?= site_url('sihardes/komoditas?p=' . $p) ?>">Atur komoditas &amp; <?= $sheet ? 'wilayah' : 'kabupaten' ?> yang mengisi</a><?php endif; ?>
  </div>
</form>

<p class="text-secondary small mb-2" id="cnt"></p>

<?php if ( ! $tampil): ?>
<div class="card"><div class="card-body text-center py-5">
  <?php if ($admin): ?>
    <b>Belum ada komoditas yang dicentang untuk dikonfirmasi.</b><br>
    Centang komoditas dan kabupaten yang harus mengisi alasan di <a href="<?= site_url('sihardes/komoditas?p=' . $p) ?>">Semua Komoditas</a>; hasilnya langsung muncul di sini dan di akun kabupaten terkait.
  <?php else: ?>
    <b>Belum ada komoditas yang perlu Anda konfirmasi.</b><br>
    Admin provinsi belum mengarahkan komoditas untuk kabupaten Anda pada periode ini.
  <?php endif; ?>
</div></div>
<?php else: ?>
<div class="tw">
<table id="tbl" class="table table-sm align-middle mb-0">
  <thead><tr><th style="width:70px">Dokumen</th><th style="width:210px">Komoditas</th><th style="width:140px"><?= $sheet ? 'Arah / Perubahan' : 'Perubahan Harga Perdesaan Provinsi' ?></th><th><?= $sheet ? 'Kecamatan / Rincian' : 'Kecamatan' ?></th><th style="width:340px">Alasan</th><th style="width:190px">Diisi oleh (ketik nama)</th></tr></thead>
  <?php foreach ($per as $kb => $baris):
      $n_isi = 0;
      foreach ($baris as $b) { $kk = $b[0]['kode'] . '|' . $kb; if (isset($alasan[$kk]) && trim($alasan[$kk]) !== '') $n_isi++; }
  ?>
  <tbody class="grp" data-kab="<?= (int) $kb ?>">
    <tr class="kab-head table-primary">
      <th colspan="6" style="position:static;text-transform:none;font-size:14px;letter-spacing:0;color:inherit">
        <?= html_escape(Sihardes_harga::nama_kab($kb)) ?>
        <span class="fw-normal text-secondary ms-2"><?= count($baris) ?> komoditas &middot; <span class="n-isi"><?= $n_isi ?></span> terisi</span>
      </th>
    </tr>
    <?php foreach ($baris as $b):
        $k = $b[0]; $daftar = $b[1];
        $kunci = $k['kode'] . '|' . $kb; $isi = isset($alasan[$kunci]) ? $alasan[$kunci] : '';
        $m = isset($meta[$kunci]) ? $meta[$kunci] : null; $terkunci = trim($isi) !== '';
        $teks = strtolower($k['label'] . ' ' . $k['kode']);
        foreach ($k['kec_semua'] as $c) if ($c['kab'] == $kb) $teks .= ' ' . strtolower($c['kec']);
        $kec = array(); foreach ($daftar as $d) if (trim((string) $d['kec']) !== '') $kec[] = $d['kec'];
    ?>
    <tr class="dat <?= $terkunci ? 'terkunci' : '' ?>" data-status="<?= $terkunci ? 'sudah' : 'belum' ?>" data-dok="<?= $k['dokumen'] ?>" data-arah="<?= (int) $k['jumlah'] === 0 ? 'tanpa' : $k['arah'] ?>" data-q="<?= html_escape($teks) ?>">
      <td><span class="badge <?= Sihardes_harga::kelas_dok($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
      <td><?php if ( ! empty($k['dari_sheet'])): ?><?= html_escape($k['label']) ?><?php else: ?><a href="<?= site_url('sihardes/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['label']) ?></a><?php endif; ?><?php if ( ! empty($k['satuan'])): ?><div class="hint">Satuan: <?= html_escape($k['satuan']) ?></div><?php endif; ?></td>
      <td><?php if ((int) $k['jumlah'] > 0): ?><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span><div class="hint"><?= isset($k['nilai_teks']) ? html_escape($k['nilai_teks']) : Sihardes_harga::fmt($k['prov_pct'], 2) ?></div><?php endif; ?></td>
      <td>
        <?= $kec ? html_escape(implode(', ', $kec)) : '<span class="text-secondary">-</span>' ?>
        <div class="hint"><?= html_escape(Sihardes_harga::teks_rinci($daftar)) ?></div>
      </td>
      <td>
        <textarea class="form-control form-control-sm alasan" rows="2" data-kode="<?= html_escape($k['kode']) ?>" data-kab="<?= (int) $kb ?>" placeholder="Tulis alasan..." data-lalu="<?= html_escape(isset($alasan_lalu[$kunci]) ? $alasan_lalu[$kunci] : '') ?>" <?= ($terkunci || ( ! $admin && $periode_terkunci)) ? 'readonly' : '' ?>><?= html_escape($isi) ?></textarea>
        <div class="cetak-teks"><?= nl2br(html_escape($isi)) ?></div>
        <div class="stat <?= $terkunci ? 'ok' : 'kosong' ?>"><?= $terkunci ? '&#10003; Tersimpan' . ($m && $m['waktu'] !== '' ? ' &middot; ' . html_escape($m['waktu']) : '') : 'Belum diisi' ?></div>
        <?php if ( ! empty($alasan_lalu[$kunci])): ?><div class="hint lalu">Periode lalu: <?= html_escape(mb_strimwidth($alasan_lalu[$kunci], 0, 140, '...')) ?></div><?php endif; ?>
      </td>
      <td class="oleh">
        <input type="text" class="form-control form-control-sm pengisi" maxlength="120" placeholder="Ketik nama pengisi" value="<?= html_escape($m ? $m['oleh'] : '') ?>" <?= $terkunci ? 'readonly' : '' ?>>
        <div class="aksi">
          <button type="button" class="btn btn-primary btn-sm b-simpan" data-aksi="simpan">Simpan</button>
          <button type="button" class="btn btn-outline-primary btn-sm b-edit" data-aksi="edit">Edit</button>
          <button type="button" class="btn btn-outline-secondary btn-sm b-batal" data-aksi="batal">Batal</button>
          <button type="button" class="btn btn-danger btn-sm b-hapus" data-aksi="hapus" title="Kosongkan alasan dan nama pengisi">Hapus</button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
</div>
<script>
(function(){
  var URL = <?= json_encode(site_url('sihardes/simpan_alasan?p=' . $p)) ?>;
  var grp = [].slice.call(document.querySelectorAll('#tbl tbody.grp'));
  var q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'),
      fk = document.getElementById('fkab'), cnt = document.getElementById('cnt'),
      bnr = document.getElementById('bnr'), bnrTeks = document.getElementById('bnrTeks'), bLompat = document.getElementById('bLompat');
  var f = {q:'', dok:'', arah:'', kab:'', status:''};   // filter yang sedang diterapkan

  // Hanya kabupaten yang difilter yang muncul; tiap kabupaten dibuka sebagai satu kelompok (judul + barisnya)
  function saring(){
    var nb = 0, nk = 0;
    grp.forEach(function(g){
      var kabOk = !f.kab || g.dataset.kab === f.kab, lihat = 0;
      [].forEach.call(g.querySelectorAll('tr.dat'), function(tr){
        var ok = kabOk && (!f.q || tr.dataset.q.indexOf(f.q) > -1) && (!f.dok || tr.dataset.dok === f.dok) &&
                 (!f.arah || tr.dataset.arah === f.arah) && (!f.status || tr.dataset.status === f.status);
        tr.style.display = ok ? '' : 'none'; if (ok) lihat++;
      });
      g.style.display = lihat ? '' : 'none';
      if (lihat) { nb += lihat; nk++; }
    });
    if (cnt) cnt.textContent = grp.length ? 'Menampilkan ' + nb + ' baris dari ' + nk + ' ' + <?= json_encode($sheet ? 'wilayah' : 'kabupaten') ?> : '';
  }
  document.getElementById('ffilter').addEventListener('submit', function(){
    f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, kab: fk.value, status: ''};
    saring();
  });
  fk.addEventListener('change', function(){ document.getElementById('ffilter').dispatchEvent(new Event('submit')); });
  document.getElementById('breset').addEventListener('click', function(){
    document.getElementById('ffilter').reset();      // semua isian filter kembali ke awal
    f = {q:'', dok:'', arah:'', kab:'', status:''}; saring();
  });
  saring();

  function ringkasKab(tr){   // perbarui "n terisi" pada judul kabupaten
    var g = tr.closest('tbody'); if (!g) return;
    var n = g.querySelectorAll('tr.dat[data-status="sudah"]').length, el = g.querySelector('.n-isi'); if (el) el.textContent = n;
  }
  // peringatan alasan yang belum diisi
  function hitung(){
    if (!bnr) return;
    var total = +bnr.dataset.total, belum = document.querySelectorAll('#tbl tr.dat[data-status="belum"]').length;
    bnr.style.setProperty('display', belum === 0 ? 'none' : '', belum === 0 ? 'important' : '');   // semua terisi = peringatan hilang
    bnrTeks.textContent = 'Masih ada ' + belum + ' alasan yang belum diisi dari ' + total + ' yang diminta.';
  }
  if (bLompat) bLompat.addEventListener('click', function(){
    var tr = document.querySelector('#tbl tr.dat[data-status="belum"]');
    if (!tr) return;
    if (tr.style.display === 'none' || tr.closest('tbody').style.display === 'none') document.getElementById('breset').click();
    tr.scrollIntoView({behavior:'smooth', block:'center'});
    var ta = tr.querySelector('textarea'); if (ta) setTimeout(function(){ ta.focus(); }, 350);
  });

  var tbl = document.getElementById('tbl');
  function bagian(tr){ return {ta: tr.querySelector('textarea.alasan'), pg: tr.querySelector('input.pengisi'), st: tr.querySelector('.stat')}; }
  function kunci(tr, ya){
    var x = bagian(tr); x.ta.readOnly = ya; x.pg.readOnly = ya;
    tr.classList.toggle('terkunci', ya); if (ya) tr.classList.remove('mengedit');
  }
  function tampil(tr){
    var x = bagian(tr), a = tr._asli, s = x.st;
    if (tr.classList.contains('terkunci')) { s.className = 'stat ok'; s.innerHTML = '&#10003; Tersimpan' + (a.w ? ' &middot; ' + a.w : ''); }
    else if (x.ta.value !== a.t || x.pg.value !== a.o) { s.className = 'stat blm'; s.textContent = 'Belum disimpan - klik Simpan'; }
    else if (tr.classList.contains('mengedit')) { s.className = 'stat blm'; s.textContent = 'Sedang diedit'; }
    else { s.className = 'stat kosong'; s.textContent = 'Belum diisi'; }
  }
  if (tbl)
  {
    [].forEach.call(tbl.querySelectorAll('tbody tr.dat'), function(tr){
      var x = bagian(tr), w = x.st.textContent.split('\u00b7')[1];
      tr._asli = {t: x.ta.value, o: x.pg.value, w: w ? w.trim() : ''};
    });
    tbl.addEventListener('input', function(e){
      var el = e.target; if (el.readOnly || (!el.classList.contains('alasan') && !el.classList.contains('pengisi'))) return;
      tampil(el.closest('tr'));
    });
    tbl.addEventListener('click', function(e){
      var b = e.target.closest('button[data-aksi]'); if (!b) return;
      var tr = b.closest('tr'), x = bagian(tr), aksi = b.dataset.aksi;
      if (aksi === 'edit') { kunci(tr, false); tr.classList.add('mengedit'); tampil(tr); x.ta.focus(); return; }
      if (aksi === 'batal') { x.ta.value = tr._asli.t; x.pg.value = tr._asli.o; kunci(tr, tr.dataset.status === 'sudah'); tampil(tr); return; }

      // kirim ke server (teks & oleh kosong = hapus data tersimpan)
      var kirim = function(teks, oleh, sukses){
        var fm = new FormData(); fm.append('kode', x.ta.dataset.kode); fm.append('kab', x.ta.dataset.kab); fm.append('teks', teks); fm.append('oleh', oleh);
        if (window.CSRF) fm.append(window.CSRF.n, window.CSRF.v);
        var tulis = b.textContent; b.disabled = true; b.textContent = aksi === 'hapus' ? 'Menghapus...' : 'Menyimpan...';
        var selesai = function(){ b.disabled = false; b.textContent = tulis; };
        fetch(URL, {method:'POST', body:fm}).then(function(r){
          if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
          return r.json();
        }).then(function(j){
          selesai();
          if (!j.ok) { x.ta.classList.add('salah'); alert(j.pesan || 'Gagal diproses. Muat ulang halaman lalu coba lagi.'); return; }
          x.ta.classList.remove('salah');
          sukses(j);
        }).catch(function(err){
          selesai(); if (err === 0) return;
          x.ta.classList.add('salah');
          alert('Belum berhasil (koneksi terputus atau sesi berakhir). Muat ulang halaman lalu coba lagi.');
        });
      };

      if (aksi === 'hapus') {
        var tersimpan = tr.dataset.status === 'sudah';
        if (!tersimpan) { x.ta.value = ''; x.pg.value = ''; tampil(tr); return; }   // belum pernah disimpan: cukup kosongkan isian
        if (!confirm('Hapus alasan dan nama pengisi yang sudah tersimpan? Isinya akan kosong kembali.')) return;
        kirim('', '', function(){
          x.ta.value = ''; x.pg.value = '';
          tr._asli = {t: '', o: '', w: ''};
          tr.dataset.status = 'belum';
          kunci(tr, false); tr.classList.remove('mengedit'); tampil(tr); hitung(); ringkasKab(tr);
        });
        return;
      }

      // simpan
      var teks = x.ta.value.trim(), oleh = x.pg.value.trim();
      if (teks === '') { alert('Alasan belum ditulis.'); x.ta.focus(); return; }
      if (oleh === '') { alert('Nama pengisi belum diketik (kolom "Diisi oleh").'); x.pg.focus(); return; }
      kirim(teks, oleh, function(j){
        x.ta.value = teks; x.pg.value = oleh;
        tr._asli = {t: teks, o: oleh, w: j.waktu || ''};
        tr.dataset.status = 'sudah';
        kunci(tr, true); tampil(tr); hitung(); ringkasKab(tr);
      });
    });

    // alasan bawaan + alasan periode lalu (muncul saat kolom alasan difokuskan)
    var BAWAAN = <?= json_encode($alasan_bawaan, JSON_UNESCAPED_UNICODE) ?>;
    tbl.addEventListener('focusin', function(e){
      var ta = e.target; if (!ta.classList || !ta.classList.contains('alasan') || ta.readOnly) return;
      var tr = ta.closest('tr'); if (tr.querySelector('.cepat')) return;
      var c = document.createElement('div'); c.className = 'cepat';
      var daftar = BAWAAN.map(function(t){ return {l: t, t: t, ganti: false}; });
      if (ta.dataset.lalu) daftar.unshift({l: 'Pakai alasan periode lalu', t: ta.dataset.lalu, ganti: true});
      daftar.forEach(function(d){
        var b = document.createElement('button'); b.type = 'button'; b.className = 'chipb'; b.textContent = d.l;
        b.dataset.t = d.t; b.dataset.ganti = d.ganti ? '1' : ''; c.appendChild(b);
      });
      ta.insertAdjacentElement('afterend', c);
    });
    tbl.addEventListener('click', function(e){
      var b = e.target.closest('.chipb'); if (!b) return;
      var x = bagian(b.closest('tr')); if (x.ta.readOnly) return;
      var sekarang = x.ta.value.trim();
      x.ta.value = (b.dataset.ganti === '1' || sekarang === '') ? b.dataset.t : sekarang.replace(/[\s;,.]+$/, '') + '; ' + b.dataset.t;
      x.ta.dispatchEvent(new Event('input', {bubbles: true})); x.ta.focus();
    });

    // peringatan bila ada isian yang belum disimpan
    window.addEventListener('beforeunload', function(e){
      var ada = false;
      [].forEach.call(tbl.querySelectorAll('tr.dat'), function(tr){
        var x = bagian(tr), a = tr._asli; if (!a) return;
        if (x.ta.value !== a.t || x.pg.value !== a.o) ada = true;
      });
      if (ada) { e.preventDefault(); e.returnValue = ''; }
    });
  }
})();
</script>