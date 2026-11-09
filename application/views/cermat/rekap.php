<style>
tr .stat{margin-top:4px;font-size:12px;font-weight:600}
tr .stat.ok{color:var(--ok)} tr .stat.blm{color:var(--warn)} tr .stat.kosong{color:var(--stb);font-weight:400}
tr.terkunci textarea.alasan,tr.terkunci input.pengisi{background:rgba(20,128,60,.07);border:1px solid var(--ok);resize:none;cursor:default}
td.oleh .aksi{display:flex;gap:6px;margin-top:6px;flex-wrap:wrap}
td.oleh .aksi button{padding:4px 12px;font-size:12.5px;line-height:1.4;cursor:pointer}
tr.terkunci .b-simpan,tr.terkunci .b-batal,tr:not(.terkunci) .b-edit,tr:not(.mengedit) .b-batal{display:none}
</style>
<?php
$tampil = array_filter($komoditas, function ($k) { return ! empty($k['tampil']); });
$admin = empty($kunci_kab);
$sheet = ! empty($periode['mode_sheet']);      // file bukan tabel harga: komoditas x wilayah dibentuk dari sheet
$dok_daftar = array();
foreach ($komoditas as $k) $dok_daftar[$k['dokumen']] = 1;
$dok_daftar = array_keys($dok_daftar);
// hitung total / belum diisi, juga per kabupaten (untuk ringkasan admin)
$total = 0; $belum = 0; $per_kab = array();
foreach ($tampil as $k)
{
    foreach ($k['kab'] as $kb => $_)
    {
        $total++;
        $isi = isset($alasan[$k['kode'] . '|' . $kb]) && trim($alasan[$k['kode'] . '|' . $kb]) !== '';
        if ( ! $isi) $belum++;
        if ( ! isset($per_kab[$kb])) $per_kab[$kb] = array(0, 0);
        $per_kab[$kb][0]++; if ($isi) $per_kab[$kb][1]++;
    }
}
ksort($per_kab);
?>
<?php if ($total > 0): ?>
<div class="peringatan" id="bnr" data-total="<?= $total ?>" <?= $belum === 0 ? 'style="display:none"' : '' ?>>
  <span id="bnrTeks">Masih ada <?= $belum ?> alasan yang belum diisi dari <?= $total ?> yang diminta.</span>
  <button type="button" class="btn" id="bLompat" <?= $belum === 0 ? 'style="display:none"' : '' ?>>Ke alasan yang belum diisi</button>
</div>
<?php endif; ?>

<?php if ($admin && $per_kab): ?>
<div class="ringkas-kab" title="Progres pengisian alasan per kabupaten">
  <?php foreach ($per_kab as $kb => $v): ?>
    <span class="chip <?= $v[0] === $v[1] ? 'lengkap' : 'kurang' ?>"><?= html_escape(Cermat_harga::nama_kab($kb)) ?>: <?= $v[1] ?>/<?= $v[0] ?></span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari komoditas / kode / kecamatan...">
  <select id="fdok"><option value="">Semua dokumen</option><?php foreach ($dok_daftar as $dk): ?><option><?= html_escape($dk) ?></option><?php endforeach; ?></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option><option value="tanpa">Kosong (tanpa data)</option></select>
  <?php if ($admin): ?>
  <select id="fkab"><option value="">Semua <?= $sheet ? 'wilayah' : 'kabupaten' ?></option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select>
  <?php else: ?>
  <input type="hidden" id="fkab" value="">
  <span class="chip"><?= html_escape(Cermat_harga::nama_kab((int) $kunci_kab)) ?></span>
  <?php endif; ?>
  <select id="fstatus"><option value="">Semua status alasan</option><option value="belum">Belum diisi</option><option value="sudah">Sudah diisi</option></select>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
  <?php if ($admin): ?><a class="btn alt" href="<?= site_url('cermat/komoditas?p=' . $p) ?>">Atur komoditas &amp; <?= $sheet ? 'wilayah' : 'kabupaten' ?> yang mengisi</a><?php endif; ?>
  <?php if ($admin && $sheet): ?><a class="btn alt" href="<?= site_url('cermat/rekap?ringkasan=1&p=' . $p) ?>" title="Ringkasan naik / turun per sheet (baca-saja)">Ringkasan per sheet</a><?php endif; ?>
</form>
<p class="hint" id="cnt"></p>

<?php if ( ! $tampil): ?>
<div class="card empty">
  <?php if ($admin): ?>
    <b>Belum ada komoditas yang diarahkan untuk dikonfirmasi.</b><br>
    Pilih komoditas dan kabupaten yang harus mengisi alasan di <a href="<?= site_url('cermat/komoditas?p=' . $p) ?>">Semua Komoditas</a>; hasilnya langsung muncul di akun kabupaten terkait.
  <?php else: ?>
    <b>Belum ada komoditas yang perlu Anda konfirmasi.</b><br>
    Admin provinsi belum mengarahkan komoditas untuk kabupaten Anda pada periode ini.
  <?php endif; ?>
</div>
<?php else: ?>
<div class="tw">
<table id="tbl">
  <thead><tr><th style="width:70px">Dokumen</th><th style="width:210px">Komoditas</th><th style="width:130px"><?= $sheet ? 'Arah / Perubahan' : 'Perubahan Harga Perdesaan Provinsi' ?></th><th><?= $sheet ? 'Wilayah' : 'Kabupaten' ?></th><th style="width:340px">Alasan</th><th style="width:190px">Diisi oleh (ketik nama)</th></tr></thead>
  <?php foreach ($tampil as $k):
      $rows = $k['kab'];
      $jml = count($rows); $pertama = true;
      $teks = strtolower($k['label']);
      foreach ($k['kec_semua'] as $c) if ($c['flag']) $teks .= ' ' . strtolower($c['kec'] . ' ' . Cermat_harga::nama_kab($c['kab']));
  ?>
  <tbody class="grp" data-dok="<?= $k['dokumen'] ?>" data-arah="<?= (int) $k['jumlah'] === 0 ? 'tanpa' : $k['arah'] ?>" data-kab=",<?= implode(',', array_keys($k['kab'])) ?>," data-q="<?= html_escape($teks) ?>">
    <?php if ( ! $rows): ?>
    <tr class="tanpa-data">
      <td><span class="badge <?= Cermat_harga::kelas_dok($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
      <td><?php if ( ! empty($k['dari_sheet'])): ?><?= html_escape($k['label']) ?><?php else: ?><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['label']) ?></a><?php endif; ?><?php if ( ! empty($k['satuan'])): ?><div class="hint">Satuan: <?= html_escape($k['satuan']) ?></div><?php endif; ?></td>
      <td><?php if ((int) $k['jumlah'] > 0): ?><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span><div class="hint"><?= isset($k['nilai_teks']) ? html_escape($k['nilai_teks']) : Cermat_harga::fmt($k['prov_pct'], 2) ?></div><?php endif; ?></td>
      <td></td><td></td><td></td>
    </tr>
    <?php endif; ?>
    <?php foreach ($rows as $kab => $daftar):
        $kunci = $k['kode'] . '|' . $kab; $isi = isset($alasan[$kunci]) ? $alasan[$kunci] : '';
        $m = isset($meta[$kunci]) ? $meta[$kunci] : null; $terkunci = trim($isi) !== ''; ?>
    <tr class="<?= $terkunci ? 'terkunci' : '' ?>" data-status="<?= $terkunci ? 'sudah' : 'belum' ?>">
      <?php if ($pertama): ?>
        <td rowspan="<?= $jml ?>"><span class="badge <?= Cermat_harga::kelas_dok($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
        <td rowspan="<?= $jml ?>"><?php if ( ! empty($k['dari_sheet'])): ?><?= html_escape($k['label']) ?><?php else: ?><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['label']) ?></a><?php endif; ?><?php if ( ! empty($k['satuan'])): ?><div class="hint">Satuan: <?= html_escape($k['satuan']) ?></div><?php endif; ?></td>
        <td rowspan="<?= $jml ?>"><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span><div class="hint"><?= isset($k['nilai_teks']) ? html_escape($k['nilai_teks']) : Cermat_harga::fmt($k['prov_pct'], 2) ?></div></td>
      <?php $pertama = false; endif; ?>
      <td>
        <?= html_escape(Cermat_harga::teks_kabupaten($kab, $daftar)) ?>
        <div class="hint"><?= html_escape(Cermat_harga::teks_rinci($daftar)) ?></div>
      </td>
      <td>
        <textarea class="alasan" rows="2" data-kode="<?= html_escape($k['kode']) ?>" data-kab="<?= (int) $kab ?>" placeholder="Tulis alasan..." <?= $terkunci ? 'readonly' : '' ?>><?= html_escape($isi) ?></textarea>
        <div class="stat <?= $terkunci ? 'ok' : 'kosong' ?>"><?= $terkunci ? '&#10003; Tersimpan' . ($m && $m['waktu'] !== '' ? ' &middot; ' . html_escape($m['waktu']) : '') : 'Belum diisi' ?></div>
      </td>
      <td class="oleh">
        <input type="text" class="pengisi" maxlength="120" placeholder="Ketik nama pengisi" value="<?= html_escape($m ? $m['oleh'] : '') ?>" <?= $terkunci ? 'readonly' : '' ?>>
        <div class="aksi">
          <button type="button" class="btn b-simpan" data-aksi="simpan">Simpan</button>
          <button type="button" class="btn alt b-edit" data-aksi="edit">Edit</button>
          <button type="button" class="btn alt b-batal" data-aksi="batal">Batal</button>
          <button type="button" class="btn red b-hapus" data-aksi="hapus" title="Kosongkan alasan dan nama pengisi">Hapus</button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<script>
(function(){
  var URL = <?= json_encode(site_url('cermat/simpan_alasan?p=' . $p)) ?>;
  var grp = [].slice.call(document.querySelectorAll('#tbl tbody.grp'));
  var q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'),
      fk = document.getElementById('fkab'), fs = document.getElementById('fstatus'), cnt = document.getElementById('cnt'),
      bnr = document.getElementById('bnr'), bnrTeks = document.getElementById('bnrTeks'), bLompat = document.getElementById('bLompat');
  var f = {q:'', dok:'', arah:'', kab:'', status:''};   // filter yang sedang diterapkan

  function saring(){
    var n = 0;
    grp.forEach(function(g){
      var ok = (!f.q || g.dataset.q.indexOf(f.q) > -1) && (!f.dok || g.dataset.dok === f.dok) &&
               (!f.arah || g.dataset.arah === f.arah) && (!f.kab || g.dataset.kab.indexOf(',' + f.kab + ',') > -1);
      // baris per kabupaten: saring menurut kabupaten dan status alasan
      var terlihat = 0;
      [].forEach.call(g.querySelectorAll('tr'), function(tr){
        var ta = tr.querySelector('textarea.alasan');
        var tampil = ta ? ((!f.kab || ta.dataset.kab === f.kab) && (!f.status || tr.dataset.status === f.status)) : (!f.kab && !f.status);   // baris komoditas tanpa data: tanpa kabupaten/alasan
        tr.style.display = tampil ? '' : 'none'; if (tampil) terlihat++;
      });
      if (ok && !terlihat) ok = false;
      g.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = grp.length ? 'Menampilkan ' + n + ' dari ' + grp.length + ' komoditas' : '';
  }
  document.getElementById('ffilter').addEventListener('submit', function(){
    f = {q: q.value.trim().toLowerCase(), dok: fd.value, arah: fa.value, kab: fk.value, status: fs.value};
    saring();
  });
  document.getElementById('breset').addEventListener('click', function(){
    q.value = ''; fd.value = ''; fa.value = ''; fk.value = ''; fs.value = '';
    f = {q:'', dok:'', arah:'', kab:'', status:''}; saring();
  });
  saring();

  // peringatan alasan yang belum diisi
  function hitung(){
    if (!bnr) return;
    var total = +bnr.dataset.total, belum = document.querySelectorAll('#tbl tr[data-status="belum"]').length;
    bnr.style.display = belum === 0 ? 'none' : '';   // semua terisi = peringatan hilang
    bnrTeks.textContent = 'Masih ada ' + belum + ' alasan yang belum diisi dari ' + total + ' yang diminta.';
  }
  if (bLompat) bLompat.addEventListener('click', function(){
    // buka filter bila menyembunyikan baris yang belum diisi, lalu lompat ke yang pertama
    var tr = document.querySelector('#tbl tr[data-status="belum"]');
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
    [].forEach.call(tbl.querySelectorAll('tbody tr:not(.tanpa-data)'), function(tr){
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
      if (aksi === 'batal') { x.ta.value = tr._asli.t; x.pg.value = tr._asli.o; kunci(tr, true); tampil(tr); return; }

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
        var tersimpan = tr._asli.t !== '' || tr._asli.o !== '';
        if (!tersimpan) { x.ta.value = ''; x.pg.value = ''; tampil(tr); return; }   // belum pernah disimpan: cukup kosongkan isian
        if (!confirm('Hapus alasan dan nama pengisi yang sudah tersimpan? Isinya akan kosong kembali.')) return;
        kirim('', '', function(){
          x.ta.value = ''; x.pg.value = '';
          tr._asli = {t: '', o: '', w: ''};
          tr.dataset.status = 'belum';
          kunci(tr, false); tr.classList.remove('mengedit'); tampil(tr); hitung();
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
        kunci(tr, true); tampil(tr); hitung();
      });
    });
  }
})();
</script>