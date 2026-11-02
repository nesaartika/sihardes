<?php
$awal = $total ? ($hal - 1) * $per + 1 : 0;
$akhir = min($total, $hal * $per);
$url = function ($ubah = array()) use ($p, $q, $per) {
    $a = array_merge(array('p' => $p, 'q' => $q, 'per' => $per), $ubah);
    return '?' . http_build_query(array_filter($a, function ($v) { return $v !== '' && $v !== null; }));
};
$ada_satuan = false; foreach ($rh as $r) if ($r['satuan'] !== '') { $ada_satuan = true; break; }
$th = 'style="text-transform:none;letter-spacing:0;font-size:13px';
?>
<form method="get" action="<?= site_url('cermat/hasil_rh') ?>" class="bar">
  <input type="hidden" name="p" value="<?= html_escape($p) ?>">
  <input type="text" name="q" value="<?= html_escape($q) ?>" placeholder="Cari kode / nama komoditas...">
  <select name="per" title="Baris per halaman">
    <?php foreach (array(25, 50, 100) as $n): ?><option value="<?= $n ?>" <?= $per === $n ? 'selected' : '' ?>><?= $n ?> baris</option><?php endforeach; ?></select>
  <button class="btn" type="submit">Terapkan</button>
  <a class="btn alt" href="<?= site_url('cermat/hasil_rh?p=' . $p) ?>">Reset</a>
  <?php if ($this->session->userdata('role') !== 'kabupaten'): ?><a class="btn alt" href="<?= site_url('cermat/unduh_rh/' . $p) ?>">Unduh RH (Excel)</a><?php endif; ?>
</form>

<p class="hint">RH dihitung aplikasi dari data mentah (sheet Data di menu <a href="<?= site_url('cermat/data_asli?p=' . $p) ?>">Data</a>): rata-rata geometrik harga seluruh kecamatan dan kualitas untuk tiap komoditas, lalu RHt = (bulan ini / bulan sebelumnya - 1) x 100. Arah naik / turun / stabil dari RHt inilah yang dipakai untuk mencari kabupaten dan kecamatan di <a href="<?= site_url('cermat/rekap?p=' . $p) ?>">Rekap Konfirmasi</a>.</p>

<?php if ( ! empty($banding)): ?>
<div class="card" style="margin-bottom:12px;padding:10px 14px;border-left:4px solid <?= $banding['cocok'] === $banding['ditemukan'] ? 'var(--turun)' : 'var(--warn)' ?>">
  <b>Dicocokkan dengan sheet RH di file:</b>
  <?= number_format($banding['cocok'], 0, ',', '.') ?> dari <?= number_format($banding['ditemukan'], 0, ',', '.') ?> komoditas yang ada di sheet RH sama persis
  (selisih RHt terbesar <?= $banding['selisih_maks'] < 1e-9 ? 'praktis nol' : number_format($banding['selisih_maks'], 6, ',', '.') ?>).
  <span class="hint">Sheet RH hanya memuat sebagian komoditas, sisanya hanya ada di hasil aplikasi.</span>
</div>
<?php endif; ?>

<p class="hint"><?= $total ? 'Menampilkan ' . number_format($awal, 0, ',', '.') . '-' . number_format($akhir, 0, ',', '.') . ' dari ' . number_format($total, 0, ',', '.') . ' komoditas' : 'Tidak ada komoditas yang cocok.' ?></p>

<div class="tw"><table>
  <thead><tr>
    <th <?= $th ?>">Kode Baru</th>
    <th <?= $th ?>">Nama Komoditas</th>
    <?php if ($ada_satuan): ?><th <?= $th ?>">Satuan</th><?php endif; ?>
    <th <?= $th ?>;text-align:right">Rata-Rata Geometrik</th>
    <th <?= $th ?>;text-align:right">Rata-Rata Geometrik Bulan Sebelumnya</th>
    <th <?= $th ?>;text-align:right">RHt</th>
    <th <?= $th ?>">Arah</th>
    <?php if ( ! empty($banding)): ?>
    <th <?= $th ?>;text-align:right">RHt di sheet RH (file)</th>
    <th <?= $th ?>;text-align:right">Selisih</th>
    <?php endif; ?>
  </tr></thead>
  <tbody>
  <?php foreach ($rh as $r):
      $stb = Cermat_harga::$opsi_default['ambang_stabil'];
      $arah = abs($r['rht']) < $stb ? 'stabil' : ($r['rht'] > 0 ? 'naik' : 'turun'); ?>
    <tr>
      <td><?= html_escape($r['kode']) ?></td>
      <td><?php if ($this->session->userdata('role') !== 'kabupaten'): ?><a href="<?= site_url('cermat/detail/' . $r['kode'] . '?p=' . $p) ?>"><?= html_escape($r['nama']) ?></a><?php else: echo html_escape($r['nama']); endif; ?></td>
      <?php if ($ada_satuan): ?><td><?= html_escape($r['satuan']) ?></td><?php endif; ?>
      <td style="text-align:right"><?= number_format($r['g_ini'], 2, '.', ',') ?></td>
      <td style="text-align:right"><?= number_format($r['g_lalu'], 2, '.', ',') ?></td>
      <td style="text-align:right"><?= number_format($r['rht'], 2, '.', ',') ?></td>
      <td><span class="badge <?= $arah ?>"><?= $arah ?></span></td>
      <?php if ( ! empty($banding)): $ada = isset($banding['peta'][$r['kode']]); ?>
      <td style="text-align:right"><?= $ada ? number_format($banding['peta'][$r['kode']], 2, '.', ',') : '<span class="hint">-</span>' ?></td>
      <td style="text-align:right"><?= $ada ? ($banding['selisih'][$r['kode']] < 0.005 ? '<span class="turun">sama</span>' : '<span class="naik">' . number_format($banding['selisih'][$r['kode']], 4, '.', ',') . '</span>') : '<span class="hint">-</span>' ?></td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<?php if ($halaman > 1):
    $dari = max(1, $hal - 2); $sampai = min($halaman, $hal + 2); ?>
<div class="pg">
  <?php if ($hal > 1): ?><a href="<?= $url(array('hal' => 1)) ?>">&laquo; Awal</a><a href="<?= $url(array('hal' => $hal - 1)) ?>">&lsaquo; Sebelumnya</a><?php endif; ?>
  <?php for ($i = $dari; $i <= $sampai; $i++): ?>
    <?php if ($i === $hal): ?><span class="on"><?= $i ?></span><?php else: ?><a href="<?= $url(array('hal' => $i)) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>
  <?php if ($hal < $halaman): ?><a href="<?= $url(array('hal' => $hal + 1)) ?>">Berikutnya &rsaquo;</a><a href="<?= $url(array('hal' => $halaman)) ?>">Akhir &raquo;</a><?php endif; ?>
  <span class="hint">Halaman <?= $hal ?> dari <?= $halaman ?></span>
</div>
<?php endif; ?>