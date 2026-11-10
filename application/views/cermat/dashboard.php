<?php
$r = $ringkas;
$persen_isi = $total_konfirmasi > 0 ? min(100, round($terisi / $total_konfirmasi * 100)) : 0;
$kpi = array(
  array('Komoditas Dikonfirmasi', number_format($r['jml_komoditas'], 0, ',', '.'), $dash['jml_kecamatan'] . ' kecamatan terpantau', ''),
  array('Kabupaten Terlibat', count($progres_kab), 'yang diminta mengisi alasan', ''),
  array('Naik', $r['naik'], 'komoditas tingkat provinsi', 'text-success'),
  array('Turun', $r['turun'], 'komoditas tingkat provinsi', 'text-danger'),
  array('Stabil', $r['stabil'], 'komoditas tingkat provinsi', 'text-warning'),
  array('Baris Konfirmasi', $total_konfirmasi, 'komoditas x kabupaten', ''),
);
?>
<div class="bs">
<?php if ($r['jml_komoditas'] === 0): ?>
  <div class="card"><div class="card-body text-center py-5">
    <b>Belum ada komoditas yang dicentang untuk dikonfirmasi.</b><br>
    <span class="text-secondary">Dashboard hanya menampilkan komoditas yang dicentang di menu Semua Komoditas.</span><br>
    <a class="btn btn-primary mt-3" href="<?= site_url('cermat/komoditas?p=' . $p) ?>">Buka Semua Komoditas</a>
  </div></div>
</div>
<?php return; endif; ?>

<div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-3">
  <?php foreach ($kpi as $x): ?>
  <div class="col"><div class="card h-100"><div class="card-body">
    <div class="text-secondary text-uppercase fw-bold" style="font-size:12px"><?= $x[0] ?></div>
    <div class="fs-2 fw-bold <?= $x[3] ?>"><?= $x[1] ?></div>
    <div class="text-secondary" style="font-size:12px"><?= $x[2] ?></div>
  </div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2>Arah Perubahan Harga Provinsi - HD</h2><div class="chart"><canvas id="cArahHD"></canvas></div></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2>Arah Perubahan Harga Provinsi - HKD</h2><div class="chart"><canvas id="cArahHKD"></canvas></div></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12"><div class="card"><div class="card-body"><h2>Kecamatan Perlu Konfirmasi per Kabupaten</h2><div class="chart"><canvas id="cKab"></canvas></div></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2>HD: 10 Komoditas Naik Tertinggi (%)</h2><div class="chart"><canvas id="cNaikHD"></canvas></div></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2>HD: 10 Komoditas Turun Terdalam (%)</h2><div class="chart"><canvas id="cTurunHD"></canvas></div></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2>HKD: 10 Komoditas Naik Tertinggi (%)</h2><div class="chart"><canvas id="cNaikHKD"></canvas></div></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2>HKD: 10 Komoditas Turun Terdalam (%)</h2><div class="chart"><canvas id="cTurunHKD"></canvas></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-5"><div class="card h-100"><div class="card-body">
    <h2>Progres Pengisian Alasan</h2>
    <div class="fs-3 fw-bold"><?= $terisi ?> <span class="text-secondary fs-6 fw-normal">dari <?= $total_konfirmasi ?> baris</span></div>
    <div class="progress my-2"><div class="progress-bar" style="width:<?= $persen_isi ?>%"></div></div>
    <div class="text-secondary small mb-3"><?= $persen_isi ?>% selesai</div>
    <a class="btn btn-primary btn-sm" href="<?= site_url('cermat/rekap?p=' . $p) ?>">Isi di Rekap Konfirmasi</a>
  </div></div></div>
  <div class="col-lg-7"><div class="card h-100"><div class="card-body">
    <h2>Progres Alasan per Kabupaten</h2>
    <div class="table-responsive" style="max-height:260px">
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th>Kabupaten</th><th>Terisi</th><th style="width:40%">Progres</th></tr></thead>
        <tbody>
        <?php foreach ($progres_kab as $nm => $v): $pc = $v[0] ? round($v[1] / $v[0] * 100) : 0; ?>
          <tr><td><?= html_escape($nm) ?></td><td><?= $v[1] ?> / <?= $v[0] ?></td>
              <td><div class="progress"><div class="progress-bar" style="width:<?= $pc ?>%"></div></div></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div></div></div>
</div>
</div>

<script src="<?= base_url('assets/js/chart.umd.js') ?>"></script>
<script>
(function(){
  var css = getComputedStyle(document.documentElement);
  Chart.defaults.color = css.getPropertyValue('--mut').trim();
  Chart.defaults.borderColor = css.getPropertyValue('--br').trim();
  Chart.defaults.font.family = 'Arial, Helvetica, sans-serif';
  // palet cerah
  var C = {naik:'#00e676', turun:'#ff1744', stabil:'#ffd600', hd:'#00b0ff', hkd:'#ff9100'};
  var warnaNaik  = ['#00e676','#1de9b6','#69f0ae','#00e5ff','#76ff03','#64ffda','#18ffff','#b2ff59','#00c853','#00bfa5'];
  var warnaTurun = ['#ff1744','#ff4081','#ff5252','#ff6e40','#f50057','#ff9100','#ff80ab','#e040fb','#ff3d00','#d500f9'];
  var opt = {responsive:true, maintainAspectRatio:false};
  var arah = <?= json_encode($dash['arah']) ?>, top = <?= json_encode($dash['top']) ?>, kab = <?= json_encode($dash['per_kab']) ?>;

  function donat(id, a){
    new Chart(document.getElementById(id), {type:'doughnut',
      data:{labels:['Naik','Turun','Stabil'], datasets:[{data:[a.naik, a.turun, a.stabil], backgroundColor:[C.naik, C.turun, C.stabil], borderWidth:2, borderColor:'#fff'}]},
      options:Object.assign({cutout:'58%', plugins:{legend:{position:'bottom'}}}, opt)});
  }
  donat('cArahHD', arah.HD); donat('cArahHKD', arah.HKD);

  var nama = Object.keys(kab);
  new Chart(document.getElementById('cKab'), {type:'bar',
    data:{labels:nama, datasets:[
      {label:'HD',  data:nama.map(function(n){return kab[n].HD}),  backgroundColor:C.hd,  borderRadius:4},
      {label:'HKD', data:nama.map(function(n){return kab[n].HKD}), backgroundColor:C.hkd, borderRadius:4}]},
    options:Object.assign({plugins:{legend:{position:'bottom'}}, scales:{x:{stacked:true, ticks:{maxRotation:55, minRotation:30}}, y:{stacked:true}}}, opt)});

  function horiz(id, arr, warna){
    new Chart(document.getElementById(id), {type:'bar',
      data:{labels:arr.map(function(a){return a[0]}), datasets:[{data:arr.map(function(a){return a[1]}), backgroundColor:arr.map(function(a,i){return warna[i % warna.length]}), borderRadius:5}]},
      options:Object.assign({indexAxis:'y', plugins:{legend:{display:false}}}, opt)});
  }
  horiz('cNaikHD', top.HD.naik, warnaNaik);   horiz('cTurunHD', top.HD.turun, warnaTurun);
  horiz('cNaikHKD', top.HKD.naik, warnaNaik); horiz('cTurunHKD', top.HKD.turun, warnaTurun);
})();
</script>