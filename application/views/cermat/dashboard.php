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

<div class="grid g2">
  <div class="card"><h2>Arah Perubahan Harga Provinsi - HD</h2><div class="chart"><canvas id="cArahHD"></canvas></div></div>
  <div class="card"><h2>Arah Perubahan Harga Provinsi - HKD</h2><div class="chart"><canvas id="cArahHKD"></canvas></div></div>
</div>

<div class="grid">
  <div class="card"><h2>Kecamatan Perlu Konfirmasi per Kabupaten</h2><div class="chart"><canvas id="cKab"></canvas></div></div>
</div>

<div class="grid g2">
  <div class="card"><h2>HD: 10 Komoditas Naik Tertinggi (%)</h2><div class="chart"><canvas id="cNaikHD"></canvas></div></div>
  <div class="card"><h2>HD: 10 Komoditas Turun Terdalam (%)</h2><div class="chart"><canvas id="cTurunHD"></canvas></div></div>
</div>

<div class="grid g2">
  <div class="card"><h2>HKD: 10 Komoditas Naik Tertinggi (%)</h2><div class="chart"><canvas id="cNaikHKD"></canvas></div></div>
  <div class="card"><h2>HKD: 10 Komoditas Turun Terdalam (%)</h2><div class="chart"><canvas id="cTurunHKD"></canvas></div></div>
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
  Chart.defaults.color = css.getPropertyValue('--mut').trim();
  Chart.defaults.borderColor = css.getPropertyValue('--br').trim();
  Chart.defaults.font.family = 'Arial, Helvetica, sans-serif';
  var naik = css.getPropertyValue('--naik').trim(), turun = css.getPropertyValue('--turun').trim(),
      stb = css.getPropertyValue('--stb').trim(), pri = css.getPropertyValue('--pri').trim();
  var opt = {responsive:true, maintainAspectRatio:false};
  var arah = <?= json_encode($dash['arah']) ?>, top = <?= json_encode($dash['top']) ?>, kab = <?= json_encode($dash['per_kab']) ?>;

  function donat(id, a){
    new Chart(document.getElementById(id), {type:'doughnut',
      data:{labels:['Naik','Turun','Stabil'], datasets:[{data:[a.naik, a.turun, a.stabil], backgroundColor:[naik, turun, stb], borderWidth:0}]},
      options:Object.assign({cutout:'62%', plugins:{legend:{position:'bottom'}}}, opt)});
  }
  donat('cArahHD', arah.HD); donat('cArahHKD', arah.HKD);

  var nama = Object.keys(kab);
  new Chart(document.getElementById('cKab'), {type:'bar',
    data:{labels:nama, datasets:[
      {label:'HD',  data:nama.map(function(n){return kab[n].HD}),  backgroundColor:pri, borderRadius:3},
      {label:'HKD', data:nama.map(function(n){return kab[n].HKD}), backgroundColor:'#f59e0b', borderRadius:3}]},
    options:Object.assign({plugins:{legend:{position:'bottom'}}, scales:{x:{stacked:true, ticks:{maxRotation:55, minRotation:30}}, y:{stacked:true}}}, opt)});

  function horiz(id, arr, warna){
    new Chart(document.getElementById(id), {type:'bar',
      data:{labels:arr.map(function(a){return a[0]}), datasets:[{data:arr.map(function(a){return a[1]}), backgroundColor:warna, borderRadius:4}]},
      options:Object.assign({indexAxis:'y', plugins:{legend:{display:false}}}, opt)});
  }
  horiz('cNaikHD', top.HD.naik, naik);   horiz('cTurunHD', top.HD.turun, turun);
  horiz('cNaikHKD', top.HKD.naik, naik); horiz('cTurunHKD', top.HKD.turun, turun);
})();
</script>