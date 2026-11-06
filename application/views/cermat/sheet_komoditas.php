<?php
/**
 * Semua Komoditas untuk file yang bukan tabel harga: semua sheet jadi satu daftar, kolom Dokumen = nama sheet (HD, HKD, HK, ...).
 * Variabel: $g = ['dok' => [nama sheet], 'baris' => [...], 'gagal' => [sheet => sebab]], $p, $periode
 */
$lbl = array('naik' => 'Naik', 'turun' => 'Turun', 'stabil' => 'Stabil');
$warna = array(); foreach ($g['dok'] as $i => $d) $warna[$d] = $i % 2 ? 'hkd' : 'hd';
$wilayah = array();
foreach ($g['baris'] as $r) foreach ($r['wil'] as $w => $_) $wilayah[$w] = 1;
$wilayah = array_keys($wilayah); sort($wilayah);
?>
<div class="card" style="margin-bottom:14px">
  <b>Semua komoditas pada file <?= html_escape($periode['file_asal']) ?></b> (<?= html_escape($periode['nama']) ?>)
  <div class="hint">Setiap sheet di file menjadi satu <b>dokumen</b> (<?= html_escape(implode(', ', $g['dok'])) ?>). Pilih dokumennya di saringan; formatnya mengikuti isi tiap sheet.
    Isi aslinya ada di menu <a href="<?= site_url('cermat/data_asli?p=' . $p) ?>">Data</a>, ringkasannya di <a href="<?= site_url('cermat/rekap?p=' . $p) ?>">Rekap</a>.</div>
</div>
<?php foreach ($g['gagal'] as $nm => $sebab): ?>
  <div class="card" style="border-left:4px solid var(--warn);padding:10px 14px;margin-bottom:10px">Sheet <b><?= html_escape($nm) ?></b> tidak masuk daftar: <?= html_escape($sebab) ?> Isinya tetap ada di menu Data.</div>
<?php endforeach; ?>

<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><?php foreach ($g['dok'] as $d): ?><option><?= html_escape($d) ?></option><?php endforeach; ?></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <select id="fwil"><option value="">Semua wilayah</option><?php foreach ($wilayah as $w): ?><option value="<?= html_escape($w) ?>"><?= html_escape($w) ?></option><?php endforeach; ?></select>
  <label style="display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" id="fsemua"> Tampilkan juga yang belum berisi data</label>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
</form>
<p class="hint" id="cnt"></p>

<?php if ( ! $g['baris']): ?>
<div class="card empty">Belum ada komoditas yang bisa dibaca dari sheet pada file ini.</div>
<?php else: ?>
<div class="tw"><table id="tbl">
  <thead><tr><th style="width:80px">Dokumen</th><th>Kode</th><th style="min-width:210px">Komoditas</th><th>Arah</th><th>Perubahan</th><th style="min-width:340px">Wilayah</th><th style="min-width:200px">Keterangan</th></tr></thead>
  <tbody>
  <?php foreach ($g['baris'] as $r):
      $tok = ','; foreach ($r['wil'] as $w => $a) $tok .= $w . '=' . $a . ','; ?>
    <tr data-dok="<?= html_escape($r['dok']) ?>" data-arah="<?= (string) $r['arah'] ?>" data-ada="<?= $r['ada'] ? 1 : 0 ?>" data-wil="<?= html_escape($tok) ?>"
        data-q="<?= html_escape(strtolower($r['nama'] . ' ' . $r['kode'])) ?>">
      <td><span class="badge <?= $warna[$r['dok']] ?>"><?= html_escape($r['dok']) ?></span></td>
      <td><?= html_escape($r['kode']) ?></td>
      <td><?= html_escape($r['nama']) ?></td>
      <td><?= $r['arah'] !== null ? '<span class="badge ' . $r['arah'] . '">' . $lbl[$r['arah']] . '</span>' : '<span class="hint">-</span>' ?></td>
      <td class="<?= (string) $r['arah'] ?>"><?= html_escape($r['nilai']) ?></td>
      <td>
        <?php if ( ! $r['ada']): ?><span class="hint">belum ada data</span><?php endif; ?>
        <?php foreach ($r['naik'] as $x): ?><span class="badge naik" style="margin:1px 2px 1px 0"><?= html_escape(trim($x[0] . ' ' . $x[1])) ?></span><?php endforeach; ?>
        <?php foreach ($r['turun'] as $x): ?><span class="badge turun" style="margin:1px 2px 1px 0"><?= html_escape(trim($x[0] . ' ' . $x[1])) ?></span><?php endforeach; ?>
        <?php if ($r['stabil']): ?><span class="hint"><?= (int) $r['stabil'] ?> wilayah stabil</span><?php endif; ?>
      </td>
      <td><?= html_escape($r['ket']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<script>
(function(){
  var tbl = document.getElementById('tbl'); if (!tbl) return;
  var rows = [].slice.call(tbl.querySelectorAll('tbody tr'));
  var q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'), fw = document.getElementById('fwil'),
      fs = document.getElementById('fsemua'), cnt = document.getElementById('cnt');
  function saring(){
    var n = 0, kq = q.value.trim().toLowerCase();
    rows.forEach(function(r){
      var w = r.dataset.wil, ok = (!kq || r.dataset.q.indexOf(kq) > -1) && (!fd.value || r.dataset.dok === fd.value) && (fs.checked || r.dataset.ada === '1');
      if (ok && fw.value) { var i = w.indexOf(',' + fw.value + '='); if (i < 0) ok = false; else if (fa.value && w.substr(i + fw.value.length + 2, fa.value.length) !== fa.value) ok = false; }
      else if (ok && fa.value) ok = r.dataset.arah === fa.value;
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' dari ' + rows.length + ' baris';
  }
  document.getElementById('ffilter').addEventListener('submit', saring);
  [fd, fa, fw, fs].forEach(function(e){ e.addEventListener('change', saring); });
  document.getElementById('breset').addEventListener('click', function(){ q.value = ''; fd.value = ''; fa.value = ''; fw.value = ''; fs.checked = false; saring(); });
  saring();
})();
</script>