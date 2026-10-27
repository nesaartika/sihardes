<?php
$awal = $total ? ($hal - 1) * $per + 1 : 0;
$akhir = min($total, $hal * $per);
$qs = function ($ubah = array()) use ($f, $per, $p) {
    $a = array_merge(array('p' => $p, 'q' => $f['q'], 'dok' => $f['dok'], 'kab' => $f['kab'] ?: '', 'ubah' => $f['ubah'], 'per' => $per), $ubah);
    return '?' . http_build_query(array_filter($a, function ($v) { return $v !== '' && $v !== null; }));
};
?>
<div class="card" style="margin-bottom:14px">
  <b>Data asli periode <?= html_escape($periode['nama']) ?></b>
  <span class="hint"> &middot; sumber: <?= html_escape($periode['file_asal']) ?> (sheet Data), diunggah <?= $periode['dibuat'] ?></span>
  <div class="hint">Setiap baris adalah harga satu komoditas pada satu kualitas di satu kecamatan. Dari baris-baris inilah perubahan harga provinsi dan daftar konfirmasi dihitung.</div>
</div>

<form method="get" action="<?= site_url('cermat/data_asli') ?>" class="bar">
  <input type="hidden" name="p" value="<?= html_escape($p) ?>">
  <input type="text" name="q" value="<?= html_escape($f['q']) ?>" placeholder="Cari komoditas / kode / kecamatan / kualitas...">
  <select name="dok"><option value="">Semua dokumen</option>
    <option value="HD" <?= $f['dok'] === 'HD' ? 'selected' : '' ?>>HD</option><option value="HKD" <?= $f['dok'] === 'HKD' ? 'selected' : '' ?>>HKD</option></select>
  <select name="kab"><option value="">Semua kabupaten</option>
    <?php foreach ($kab_ada as $kd): ?><option value="<?= $kd ?>" <?= (int) $f['kab'] === $kd ? 'selected' : '' ?>><?= html_escape(Cermat_harga::nama_kab($kd)) ?></option><?php endforeach; ?></select>
  <select name="ubah"><option value="">Semua perubahan</option>
    <option value="naik" <?= $f['ubah'] === 'naik' ? 'selected' : '' ?>>Naik</option>
    <option value="turun" <?= $f['ubah'] === 'turun' ? 'selected' : '' ?>>Turun</option>
    <option value="tetap" <?= $f['ubah'] === 'tetap' ? 'selected' : '' ?>>Tetap</option></select>
  <select name="per" title="Baris per halaman">
    <?php foreach (array(25, 50, 100) as $n): ?><option value="<?= $n ?>" <?= $per === $n ? 'selected' : '' ?>><?= $n ?> baris</option><?php endforeach; ?></select>
  <button class="btn" type="submit">Terapkan</button>
  <a class="btn alt" href="<?= site_url('cermat/data_asli?p=' . $p) ?>">Reset</a>
</form>

<p class="hint"><?= $total ? 'Menampilkan ' . number_format($awal, 0, ',', '.') . '-' . number_format($akhir, 0, ',', '.') . ' dari ' . number_format($total, 0, ',', '.') . ' baris' : 'Tidak ada baris yang cocok.' ?></p>

<div class="tw"><table>
  <thead><tr><th>Kabupaten</th><th>Kecamatan</th><th>Dok</th><th>Kode</th><th>Komoditas</th><th>Kualitas</th>
    <th style="text-align:right">Harga Bulan Lalu</th><th style="text-align:right">Harga Bulan Ini</th><th style="text-align:right">Perubahan</th></tr></thead>
  <tbody>
  <?php foreach ($baris as $b):
      $lalu = (float) $b['harga_lalu']; $ini = (float) $b['harga_ini'];
      $pc = $lalu > 0 ? ($ini / $lalu - 1) * 100 : 0;
      $cls = $pc > 0 ? 'naik' : ($pc < 0 ? 'turun' : 'stabil'); ?>
    <tr>
      <td><?= html_escape(Cermat_harga::nama_kab((int) $b['kab'])) ?></td>
      <td><?= html_escape($b['kecamatan']) ?></td>
      <td><span class="badge <?= strtolower($b['dokumen']) ?>"><?= $b['dokumen'] ?></span></td>
      <td><?= html_escape($b['kode']) ?></td>
      <td><a href="<?= site_url('cermat/detail/' . $b['kode'] . '?p=' . $p) ?>"><?= html_escape($b['nama']) ?></a></td>
      <td><?= html_escape($b['kualitas']) ?></td>
      <td style="text-align:right"><?= number_format($lalu, 0, ',', '.') ?></td>
      <td style="text-align:right"><?= number_format($ini, 0, ',', '.') ?></td>
      <td style="text-align:right" class="<?= $cls ?>"><?= Cermat_harga::fmt($pc, 2) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<?php if ($halaman > 1):
    $dari = max(1, $hal - 2); $sampai = min($halaman, $hal + 2); ?>
<div class="pg">
  <?php if ($hal > 1): ?><a href="<?= $qs(array('hal' => 1)) ?>">&laquo; Awal</a><a href="<?= $qs(array('hal' => $hal - 1)) ?>">&lsaquo; Sebelumnya</a><?php endif; ?>
  <?php for ($i = $dari; $i <= $sampai; $i++): ?>
    <?php if ($i === $hal): ?><span class="on"><?= $i ?></span><?php else: ?><a href="<?= $qs(array('hal' => $i)) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>
  <?php if ($hal < $halaman): ?><a href="<?= $qs(array('hal' => $hal + 1)) ?>">Berikutnya &rsaquo;</a><a href="<?= $qs(array('hal' => $halaman)) ?>">Akhir &raquo;</a><?php endif; ?>
  <span class="hint">Halaman <?= $hal ?> dari <?= $halaman ?></span>
</div>
<?php endif; ?>
