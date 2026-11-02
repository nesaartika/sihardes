<p><a href="<?= site_url('cermat/komoditas?p=' . $p) ?>">&larr; Semua komoditas</a></p>
<div class="cards">
  <div class="card kpi"><div class="l">Komoditas</div><div class="v" style="font-size:20px"><?= html_escape($k['nama']) ?></div>
    <div class="s"><span class="badge <?= strtolower($k['dokumen']) ?>"><?= $k['dokumen'] ?></span> <?= html_escape($k['kode']) ?><?= ! empty($k['satuan']) ? ' &middot; Satuan: ' . html_escape($k['satuan']) : '' ?></div></div>
  <div class="card kpi"><div class="l">Perubahan Provinsi</div><div class="v <?= $k['arah'] ?>"><?= Cermat_harga::fmt($k['prov_pct'], 2) ?></div><div class="s"><span class="badge <?= $k['arah'] ?>"><?= $k['arah'] ?></span></div></div>
  <div class="card kpi"><div class="l">Observasi</div><div class="v"><?= $k['jumlah'] ?></div><div class="s">kombinasi kecamatan x kualitas</div></div>
  <div class="card kpi"><div class="l">Kecamatan</div><div class="v"><?= count($k['kec_semua']) ?></div><div class="s"><?= array_sum(array_map('count', isset($k['kab_asli']) ? $k['kab_asli'] : $k['kab'])) ?> perlu konfirmasi</div></div>
</div>
<div class="tw"><table>
  <thead><tr><th>Kabupaten</th><th>Kecamatan</th><th>Perubahan</th><th>Observasi</th><th>Status</th><th>Rincian Kualitas</th></tr></thead>
  <tbody>
  <?php foreach ($k['kec_semua'] as $c): $cls = $c['persen'] > 0 ? 'naik' : ($c['persen'] < 0 ? 'turun' : 'stabil'); ?>
    <tr>
      <td><?= html_escape(Cermat_harga::nama_kab($c['kab'])) ?></td>
      <td><?= html_escape($c['kec']) ?></td>
      <td class="<?= $cls ?>"><?= Cermat_harga::fmt($c['persen'], 1) ?></td>
      <td><?= $c['n'] ?></td>
      <td>
        <?php if ($c['flag']): ?><span class="pill">Perlu konfirmasi</span>
        <?php else: ?><span class="hint">-</span><?php endif; ?>
      </td>
      <td><details><summary><?= count($c['rinci']) ?> kualitas</summary>
        <?php foreach ($c['rinci'] as $q): $pc = $q[1] > 0 ? ($q[2] / $q[1] - 1) * 100 : 0; ?>
          <div class="hint"><?= html_escape($q[0]) ?>: <?= number_format($q[1], 0, ',', '.') ?> &rarr; <?= number_format($q[2], 0, ',', '.') ?> (<?= Cermat_harga::fmt($pc, 1) ?>)</div>
        <?php endforeach; ?></details></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>