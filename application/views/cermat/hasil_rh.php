<?php
$awal = $total ? ($hal - 1) * $per + 1 : 0;
$akhir = min($total, $hal * $per);
$url = function ($ubah = array()) use ($p, $q, $per) {
    $a = array_merge(array('p' => $p, 'q' => $q, 'per' => $per), $ubah);
    return '?' . http_build_query(array_filter($a, function ($v) { return $v !== '' && $v !== null; }));
};
$angka = function ($x) { return $x === null ? '' : number_format($x, 2, '.', ','); };
$kab = $this->session->userdata('role') === 'kabupaten';
$kanan = ! empty($bulan);

// judul kolom mengikuti sheet RH di Excel; warna tabel mengikuti tampilan aplikasi
$kepala = array(
    array('Kode Komoditas', ''), array('Kode Baru', ''), array('Nama Komoditas', ''),
    array('Rata-Rata Geometrik Kode Lama', ''), array('Rata-Rata Geometrik Bulan Sebelumnya', ''),
    array('Rata-Rata Geometrik Kode Baru', ''), array('Rata-Rata Geometrik Bulan Sebelumnya', ''),
    array('Rata-Rata Geometrik', ''), array('Rata-Rata Geometrik Bulan Sebelumnya', ''), array('RHt', ''),
);
if ($kanan)
{
    $kepala[] = array('Column1', '');
    foreach (array('Kode Komoditas', 'Kode Baru', 'Kode Gabung', 'Nama Komoditas') as $h) $kepala[] = array($h, '');
    foreach ($bulan as $b) $kepala[] = array($b, '');
}
?>
<form method="get" action="<?= site_url('cermat/hasil_rh') ?>" class="bar">
  <input type="hidden" name="p" value="<?= html_escape($p) ?>">
  <input type="text" name="q" value="<?= html_escape($q) ?>" placeholder="Cari kode / nama komoditas...">
  <select name="per" title="Baris per halaman">
    <?php foreach (array(25, 50, 100) as $n): ?><option value="<?= $n ?>" <?= $per === $n ? 'selected' : '' ?>><?= $n ?> baris</option><?php endforeach; ?></select>
  <button class="btn" type="submit">Terapkan</button>
  <a class="btn alt" href="<?= site_url('cermat/hasil_rh?p=' . $p) ?>">Reset</a>
  <?php if ( ! $kab): ?><a class="btn alt" href="<?= site_url('cermat/unduh_rh/' . $p) ?>">Unduh RH (Excel)</a><?php endif; ?>
</form>

<p class="hint"><?= $total ? 'Menampilkan ' . number_format($awal, 0, ',', '.') . '-' . number_format($akhir, 0, ',', '.') . ' dari ' . number_format($total, 0, ',', '.') . ' komoditas' : 'Tidak ada komoditas yang cocok.' ?></p>

<div class="tw"><table>
  <thead><tr>
    <?php foreach ($kepala as $i => $h): ?><th style="text-transform:none;letter-spacing:0;font-size:13px;vertical-align:bottom<?= ($i >= 3 && $i <= 10) || $i >= 15 ? ';text-align:right' : '' ?>"><?= html_escape($h[0]) ?></th><?php endforeach; ?>
  </tr></thead>
  <tbody>
  <?php foreach ($rh as $r): ?>
    <tr>
      <td><?= html_escape($r['lama']) ?></td>
      <td><?= html_escape($r['baru']) ?></td>
      <td><?= html_escape($r['nama']) ?></td>
      <?php foreach (array('d', 'e', 'f', 'g', 'h', 'i') as $c): ?><td style="text-align:right"><?= $angka($r[$c]) ?></td><?php endforeach; ?>
      <td style="text-align:right"><?= $angka($r['rht']) ?></td>
      <?php if ($kanan): ?>
      <td style="text-align:right"><?= (int) $r['k'] ?></td>
      <td><?= html_escape($r['l']) ?></td>
      <td><?= html_escape($r['m']) ?></td>
      <td><?= html_escape($r['n']) ?></td>
      <td><?= html_escape($r['o']) ?></td>
      <?php foreach ($r['bln'] as $v): ?><td style="text-align:right"><?= $angka($v) ?></td><?php endforeach; ?>
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