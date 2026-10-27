<?php
$r = $ringkas;
$persen_isi = $total_konfirmasi > 0 ? min(100, round($terisi / $total_konfirmasi * 100)) : 0;
?>
<div class="cards">
  <div class="card kpi"><div class="l">Baris Data</div><div class="v"><?= number_format($r['jml_baris'], 0, ',', '.') ?></div><div class="s">dari sheet Data</div></div>
  <div class="card kpi"><div class="l">Komoditas</div><div class="v"><?= number_format($r['jml_komoditas'], 0, ',', '.') ?></div><div class="s"><?= $dash['jml_kecamatan'] ?> kecamatan terpantau</div></div>
  <div class="card kpi"><div class="l">Naik</div><div class="v naik"><?= $r['naik'] ?></div><div class="s">komoditas tingkat provinsi</div></div>
  <div class="card kpi"><div class="l">Turun</div><div class="v turun"><?= $r['turun'] ?></div><div class="s">komoditas tingkat provinsi</div></div>
  <div class="card kpi"><div class="l">Stabil</div><div class="v stabil"><?= $r['stabil'] ?></div><div class="s">komoditas tingkat provinsi</div></div>
  <div class="card kpi"><div class="l">Perlu Konfirmasi</div><div class="v"><?= $r['perlu_konfirmasi'] ?></div><div class="s"><?= $total_konfirmasi ?> baris kabupaten</div></div>
</div>

<div class="grid g3">
  <div class="card"><h2>Arah Perubahan Harga Provinsi</h2><div class="chart"><canvas id="cArah"></canvas></div></div>
  <div class="card"><h2>Kecamatan Perlu Konfirmasi per Kabupaten</h2><div class="chart"><canvas id="cKab"></canvas></div></div>
</div>

<div class="grid g2">
  <div class="card"><h2>10 Komoditas Naik Tertinggi (%)</h2><div class="chart"><canvas id="cNaik"></canvas></div></div>
  <div class="card"><h2>10 Komoditas Turun Terdalam (%)</h2><div class="chart"><canvas id="cTurun"></canvas></div></div>
</div>

<div class="grid g2">
  <div class="card">
    <h2>Progres Pengisian Alasan</h2>
    <div style="font-size:26px;font-weight:700"><?= $terisi ?> <span class="hint" style="font-size:14px">dari <?= $total_konfirmasi ?> baris</span></div>
    <div class="progress"><i style="width:<?= $persen_isi ?>%"></i></div>
    <p><a class="btn" href="<?= site_url('cermat/rekap?p=' . $p) ?>">Isi di Rekap Konfirmasi</a></p>
  </div>
  <div class="card">
    <h2>Progres Alasan per Kabupaten</h2>
    <div class="tw" style="max-height:230px"><table>
      <thead><tr><th>Kabupaten</th><th>Terisi</th><th style="width:40%">Progres</th></tr></thead>
      <tbody>
      <?php foreach ($progres_kab as $nm => $v): $pc = $v[0] ? round($v[1] / $v[0] * 100) : 0; ?>
        <tr><td><?= html_escape($nm) ?></td><td><?= $v[1] ?> / <?= $v[0] ?></td>
            <td><div class="progress" style="margin:6px 0"><i style="width:<?= $pc ?>%"></i></div></td></tr>
      <?php endforeach; ?>
      </tbody></table></div>
  </div>
</div>

<script src="<?= base_url('assets/js/chart.umd.js') ?>"></script>
<script>
(function(){
  var css = getComputedStyle(document.documentElement);
  var tx = css.getPropertyValue('--tx').trim(), br = css.getPropertyValue('--br').trim();
  Chart.defaults.color = css.getPropertyValue('--mut').trim();
  Chart.defaults.borderColor = br;
  Chart.defaults.font.family = 'Arial, Helvetica, sans-serif';
  var naik = css.getPropertyValue('--naik').trim(), turun = css.getPropertyValue('--turun').trim(), stb = css.getPropertyValue('--stb').trim(), pri = css.getPropertyValue('--pri').trim();
  var opt = {responsive:true, maintainAspectRatio:false};

  new Chart(document.getElementById('cArah'), {type:'doughnut',
    data:{labels:['Naik','Turun','Stabil'], datasets:[{data:[<?= $r['naik'] ?>,<?= $r['turun'] ?>,<?= $r['stabil'] ?>], backgroundColor:[naik,turun,stb], borderWidth:0}]},
    options:Object.assign({cutout:'62%', plugins:{legend:{position:'bottom'}}}, opt)});

  var kab = <?= json_encode($dash['per_kab']) ?>;
  new Chart(document.getElementById('cKab'), {type:'bar',
    data:{labels:Object.keys(kab), datasets:[{label:'Baris konfirmasi', data:Object.values(kab), backgroundColor:pri, borderRadius:4}]},
    options:Object.assign({plugins:{legend:{display:false}}, scales:{x:{ticks:{maxRotation:55,minRotation:30}}}}, opt)});

  function horiz(id, arr, color){
    new Chart(document.getElementById(id), {type:'bar',
      data:{labels:arr.map(function(a){return a[0]}), datasets:[{data:arr.map(function(a){return a[1]}), backgroundColor:color, borderRadius:4}]},
      options:Object.assign({indexAxis:'y', plugins:{legend:{display:false}}}, opt)});
  }
  horiz('cNaik', <?= json_encode($dash['top_naik']) ?>, naik);
  horiz('cTurun', <?= json_encode($dash['top_turun']) ?>, turun);
})();
</script>
