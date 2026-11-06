<?php
$tampil = array_filter($komoditas, function ($k) { return ! empty($k['tampil']); });
$admin = empty($kunci_kab);
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
<div class="peringatan <?= $belum === 0 ? 'selesai' : '' ?>" id="bnr" data-total="<?= $total ?>">
  <span id="bnrTeks"><?= $belum === 0 ? 'Semua alasan sudah terisi (' . $total . ' dari ' . $total . ').' : 'Masih ada ' . $belum . ' alasan yang belum diisi dari ' . $total . ' yang diminta.' ?></span>
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
  <select id="fdok"><option value="">Semua dokumen</option><option>HD</option><option>HKD</option></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <?php if ($admin): ?>
  <select id="fkab"><option value="">Semua kabupaten</option>
    <?php foreach ($daftar_kab as $kd => $nm): ?><option value="<?= $kd ?>"><?= html_escape($nm) ?></option><?php endforeach; ?></select>
  <?php else: ?>
  <input type="hidden" id="fkab" value="">
  <span class="chip"><?= html_escape(Cermat_harga::nama_kab((int) $kunci_kab)) ?></span>
  <?php endif; ?>
  <select id="fstatus"><option value="">Semua status alasan</option><option value="belum">Belum diisi</option><option value="sudah">Sudah diisi</option></select>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
  <?php if ($admin): ?><a class="btn alt" href="<?= site_url('cermat/komoditas?p=' . $p) ?>">Atur komoditas &amp; kabupaten yang mengisi</a><?php endif; ?>
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
  <thead><tr><th style="width:70px">Dokumen</th><th style="width:210px">Komoditas</th><th style="width:130px">Perubahan Harga Perdesaan Provinsi</th><th>Kabupaten</th><th style="width:340px">Alasan</th><th style="width:190px">Diisi oleh (ketik nama)</th></tr></thead>
  <?php foreach ($tampil as $k):
      $rows = $k['kab'];
      $jml = count($rows); $pertama = true;
      $teks = strtolower($k['label']);
      foreach ($k['kec_semua'] as $c) if ($c['flag']) $teks .= ' ' . strtolower($c['kec']);
  ?>
  <tbody class="grp" data-dok="<?= $k['dokumen'] ?>" data-arah="<?= $k['arah'] ?>" data-kab=",<?= implode(',', array_keys($k['kab'])) ?>," data-q="<?= html_escape($teks) ?>">
    <?php foreach ($rows as $kab => $daftar):
        $kunci = $k['kode'] . '|' . $kab; $isi = isset($alasan[$kunci]) ? $alasan[$kunci] : '';
        $m = isset($meta[$kunci]) ? $meta[$kunci] : null; ?>
    <tr data-status="<?= trim($isi) === '' ? 'belum' : 'sudah' ?>">
      <?php if ($pertama): ?>
        <td rowspan="<?= $jml ?>"><span class="badge <?= strtolower($k['dokumen']) ?>"><?= $k['dokumen'] ?></span></td>
        <td rowspan="<?= $jml ?>"><a href="<?= site_url('cermat/detail/' . $k['kode'] . '?p=' . $p) ?>"><?= html_escape($k['label']) ?></a><?php if ( ! empty($k['satuan'])): ?><div class="hint">Satuan: <?= html_escape($k['satuan']) ?></div><?php endif; ?></td>
        <td rowspan="<?= $jml ?>"><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span><div class="hint"><?= Cermat_harga::fmt($k['prov_pct'], 2) ?></div></td>
      <?php $pertama = false; endif; ?>
      <td>
        <?= html_escape(Cermat_harga::teks_kabupaten($kab, $daftar)) ?>
        <div class="hint"><?= html_escape(Cermat_harga::teks_rinci($daftar)) ?></div>
      </td>
      <td><textarea class="alasan" rows="2" data-kode="<?= html_escape($k['kode']) ?>" data-kab="<?= (int) $kab ?>" placeholder="Tulis alasan..."><?= html_escape($isi) ?></textarea></td>
      <td class="oleh"><input type="text" class="pengisi" maxlength="120" placeholder="Ketik nama pengisi" value="<?= html_escape($m ? $m['oleh'] : '') ?>"><span class="waktu"><?= $m && $m['waktu'] !== '' ? html_escape($m['waktu']) : '' ?></span></td>
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
        var kabTr = tr.querySelector('textarea.alasan').dataset.kab;
        var tampil = (!f.kab || kabTr === f.kab) && (!f.status || tr.dataset.status === f.status);
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
    bnr.classList.toggle('selesai', belum === 0);
    bnrTeks.textContent = belum === 0 ? 'Semua alasan sudah terisi (' + total + ' dari ' + total + ').'
                                      : 'Masih ada ' + belum + ' alasan yang belum diisi dari ' + total + ' yang diminta.';
    bLompat.style.display = belum === 0 ? 'none' : '';
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
  if (tbl) tbl.addEventListener('change', function(e){
    var el = e.target; if (!el.classList.contains('alasan') && !el.classList.contains('pengisi')) return;
    var tr = el.closest('tr'), ta = tr.querySelector('textarea.alasan'), pg = tr.querySelector('input.pengisi'), w = tr.querySelector('.waktu');
    var fm = new FormData(); fm.append('kode', ta.dataset.kode); fm.append('kab', ta.dataset.kab); fm.append('teks', ta.value); fm.append('oleh', pg.value);
    if (window.CSRF) fm.append(window.CSRF.n, window.CSRF.v);
    fetch(URL, {method:'POST', body:fm}).then(function(r){
      if (r.status === 401) { alert('Sesi habis. Silakan login lagi.'); location.reload(); throw 0; }
      return r.json();
    }).then(function(j){
      if (!j.ok) { el.classList.add('salah'); alert(j.pesan || 'Gagal disimpan. Muat ulang halaman lalu coba lagi.'); return; }
      el.classList.remove('salah');
      tr.dataset.status = ta.value.trim() !== '' ? 'sudah' : 'belum';
      w.textContent = j.waktu || '';
      el.classList.add('ok'); setTimeout(function(){ el.classList.remove('ok'); }, 1500);
      hitung();
    }).catch(function(err){
      if (err === 0) return;
      el.classList.add('salah');
      alert('Belum tersimpan (koneksi terputus atau sesi berakhir). Muat ulang halaman lalu isi kembali.');
    });
  });
})();
</script>