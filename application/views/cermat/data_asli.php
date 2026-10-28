<?php
$awal = $total ? ($hal - 1) * $per + 1 : 0;
$akhir = min($total, $hal * $per);
$qs = function ($ubah = array()) use ($f, $per, $p) {
    $a = array_merge(array('p' => $p, 'q' => $f['q'], 'kab' => $f['kab'], 'per' => $per), $ubah);
    return '?' . http_build_query(array_filter($a, function ($v) { return $v !== '' && $v !== null; }));
};
?>
<?php if ( ! $ada): ?>
  <div class="card empty">Data asli periode ini belum tersimpan di aplikasi.<br>
    Unggah ulang file Excel-nya lewat <a href="<?= site_url('cermat/unggah') ?>">Unggah Data</a> agar isi sheet Data bisa ditampilkan di sini.</div>
<?php else: ?>

<form method="get" action="<?= site_url('cermat/data_asli') ?>" class="bar">
  <input type="hidden" name="p" value="<?= html_escape($p) ?>">
  <input type="text" name="q" value="<?= html_escape($f['q']) ?>" placeholder="Cari di semua kolom...">
  <?php if ( ! empty($kab_nilai)): ?>
  <select name="kab"><option value="">Semua Kabupaten</option>
    <?php foreach ($kab_nilai as $v): ?><option value="<?= html_escape($v) ?>" <?= (string) $f['kab'] === (string) $v ? 'selected' : '' ?>><?= html_escape($v) ?> - <?= html_escape(Cermat_harga::nama_kab((int) $v)) ?></option><?php endforeach; ?></select>
  <?php endif; ?>
  <select name="per" title="Baris per halaman">
    <?php foreach (array(25, 50, 100) as $n): ?><option value="<?= $n ?>" <?= $per === $n ? 'selected' : '' ?>><?= $n ?> baris</option><?php endforeach; ?></select>
  <button class="btn" type="submit">Terapkan</button>
  <a class="btn alt" href="<?= site_url('cermat/data_asli?p=' . $p) ?>">Reset</a>
</form>

<p class="hint"><?= $total ? 'Menampilkan ' . number_format($awal, 0, ',', '.') . '-' . number_format($akhir, 0, ',', '.') . ' dari ' . number_format($total, 0, ',', '.') . ' baris' : 'Tidak ada baris yang cocok.' ?></p>

<div class="tw"><table>
  <thead><tr>
    <?php foreach ($kolom as $nm): ?><th style="text-transform:none;letter-spacing:0;font-size:13px"><?= html_escape($nm) ?></th><?php endforeach; ?>
  </tr></thead>
  <tbody>
  <?php foreach ($baris as $r): ?>
    <tr>
      <?php foreach ($r as $v): ?><td<?= ($v !== null && is_numeric($v)) ? ' style="text-align:right"' : '' ?>><?= html_escape((string) $v) ?></td><?php endforeach; ?>
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
<?php endif; ?>