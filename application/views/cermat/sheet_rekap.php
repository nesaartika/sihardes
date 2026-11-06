<?php
/**
 * Rekap untuk file yang bukan tabel harga: semua sheet jadi satu daftar, kolom Dokumen = nama sheet (HD, HKD, HK, ...).
 * Variabel: $g = ['dok' => [...], 'baris' => [...], 'gagal' => [...]], $p, $periode
 */
$lbl = array('naik' => 'Naik', 'turun' => 'Turun', 'stabil' => 'Stabil');
$warna = array(); foreach ($g['dok'] as $i => $d) $warna[$d] = $i % 2 ? 'hkd' : 'hd';
$tampil = array(); $ring = array();
foreach ($g['dok'] as $d) $ring[$d] = array('n' => 0, 'naik' => 0, 'turun' => 0, 'stabil' => 0, 'kosong' => 0);
foreach ($g['baris'] as $r)
{
    $ring[$r['dok']]['n']++;
    if ( ! $r['ada']) { $ring[$r['dok']]['kosong']++; continue; }
    if ($r['arah'] !== null) $ring[$r['dok']][$r['arah']]++;
    $tampil[] = $r;
}
$wilayah = array();
foreach ($tampil as $r) foreach ($r['wil'] as $w => $_) $wilayah[$w] = 1;
$wilayah = array_keys($wilayah); sort($wilayah);
$daftar = function ($l, $kelas) { $h = ''; foreach ($l as $x) $h .= '<span class="badge ' . $kelas . '" style="margin:1px 2px 1px 0">' . html_escape(trim($x[0] . ' ' . $x[1])) . '</span>'; return $h; };
?>
<?php foreach ($g['gagal'] as $nm => $sebab): ?>
  <div class="card" style="border-left:4px solid var(--warn);padding:10px 14px;margin-bottom:10px">Sheet <b><?= html_escape($nm) ?></b> tidak masuk rekap: <?= html_escape($sebab) ?> Isinya tetap ada di menu Data.</div>
<?php endforeach; ?>

<form class="bar" id="ffilter" onsubmit="return false">
  <input type="text" id="q" placeholder="Cari nama / kode komoditas...">
  <select id="fdok"><option value="">Semua dokumen</option><?php foreach ($g['dok'] as $d): ?><option><?= html_escape($d) ?></option><?php endforeach; ?></select>
  <select id="farah"><option value="">Semua arah</option><option value="naik">Naik</option><option value="turun">Turun</option><option value="stabil">Stabil</option></select>
  <select id="fwil"><option value="">Semua wilayah</option><?php foreach ($wilayah as $w): ?><option value="<?= html_escape($w) ?>"><?= html_escape($w) ?></option><?php endforeach; ?></select>
  <button class="btn" type="submit">Terapkan</button>
  <button class="btn alt" type="button" id="breset">Reset</button>
  <?php if ($this->session->userdata('role') !== 'kabupaten'): ?><a class="btn alt" href="<?= site_url('cermat/unduh_rekap/' . $p) ?>">Unduh rekap (Excel)</a><?php endif; ?>
</form>
<p class="hint" id="cnt"></p>

<?php if ( ! $tampil): ?>
<div class="card empty">Belum ada komoditas yang berisi data pada file ini.</div>
<?php else: ?>
<div class="tw"><table id="tbl">
  <thead><tr><th style="width:80px">Dokumen</th><th style="min-width:210px">Komoditas</th><th>Perubahan Provinsi / Rata-rata</th><th style="min-width:230px">Wilayah naik</th><th style="min-width:230px">Wilayah turun</th><th>Stabil</th><th style="min-width:200px">Keterangan</th></tr></thead>
  <tbody>
  <?php foreach ($tampil as $r):
      $tok = ','; foreach ($r['wil'] as $w => $a) $tok .= $w . '=' . $a . ','; ?>
    <tr data-dok="<?= html_escape($r['dok']) ?>" data-arah="<?= (string) $r['arah'] ?>" data-wil="<?= html_escape($tok) ?>" data-q="<?= html_escape(strtolower($r['nama'] . ' ' . $r['kode'])) ?>">
      <td><span class="badge <?= $warna[$r['dok']] ?>"><?= html_escape($r['dok']) ?></span></td>
      <td><?= html_escape($r['nama']) ?><?php if ($r['kode'] !== ''): ?> <span class="hint">(<?= html_escape($r['kode']) ?>)</span><?php endif; ?></td>
      <td><?= $r['arah'] !== null ? '<span class="badge ' . $r['arah'] . '">' . $lbl[$r['arah']] . '</span>' : '' ?><div class="hint"><?= html_escape($r['nilai']) ?></div></td>
      <td><?= $daftar($r['naik'], 'naik') ?></td>
      <td><?= $daftar($r['turun'], 'turun') ?></td>
      <td class="stabil"><?= $r['stabil'] ?: '' ?></td>
      <td><?= html_escape($r['ket']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="hint">Komoditas yang belum berisi data di sheetnya tidak ditampilkan di sini (ada di menu Semua Komoditas).</p>
<?php endif; ?>
<script>
(function(){
  var tbl = document.getElementById('tbl'); if (!tbl) return;
  var rows = [].slice.call(tbl.querySelectorAll('tbody tr'));
  var q = document.getElementById('q'), fd = document.getElementById('fdok'), fa = document.getElementById('farah'), fw = document.getElementById('fwil'), cnt = document.getElementById('cnt');
  function saring(){
    var n = 0, kq = q.value.trim().toLowerCase();
    rows.forEach(function(r){
      var w = r.dataset.wil, ok = (!kq || r.dataset.q.indexOf(kq) > -1) && (!fd.value || r.dataset.dok === fd.value);
      if (ok && fw.value) { var i = w.indexOf(',' + fw.value + '='); if (i < 0) ok = false; else if (fa.value && w.substr(i + fw.value.length + 2, fa.value.length) !== fa.value) ok = false; }
      else if (ok && fa.value) ok = r.dataset.arah === fa.value;
      r.style.display = ok ? '' : 'none'; if (ok) n++;
    });
    cnt.textContent = 'Menampilkan ' + n + ' dari ' + rows.length + ' baris';
  }
  document.getElementById('ffilter').addEventListener('submit', saring);
  [fd, fa, fw].forEach(function(e){ e.addEventListener('change', saring); });
  document.getElementById('breset').addEventListener('click', function(){ q.value = ''; fd.value = ''; fa.value = ''; fw.value = ''; saring(); });
  saring();
})();
</script>