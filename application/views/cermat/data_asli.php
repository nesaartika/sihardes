<?php
$awal = $total ? ($hal - 1) * $per + 1 : 0;
$akhir = min($total, $hal * $per);
$url = function ($ubah = array()) use ($p, $sheet, $q, $per, $f) {
    $a = array_merge(array('p' => $p, 'sheet' => $sheet, 'q' => $q, 'kab' => isset($f['kab']) ? $f['kab'] : '', 'per' => $per), $ubah);
    return '?' . http_build_query(array_filter($a, function ($v) { return $v !== '' && $v !== null; }));
};
?>
<?php if (empty($tabs)): ?>
  <div class="card empty">Data periode ini belum tersimpan di aplikasi.<br>
    Unggah ulang file Excel-nya lewat <a href="<?= site_url('cermat/unggah') ?>">Unggah Data</a>.</div>
<?php else: ?>

<?php if (count($tabs) > 1): ?>
<div class="tabs">
  <?php foreach ($tabs as $t): ?>
    <a class="<?= $t === $sheet ? 'on' : '' ?>" href="<?= site_url('cermat/data_asli?p=' . $p . '&sheet=' . rawurlencode($t)) ?>"><?= html_escape($t) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="get" action="<?= site_url('cermat/data_asli') ?>" class="bar">
  <input type="hidden" name="p" value="<?= html_escape($p) ?>">
  <input type="hidden" name="sheet" value="<?= html_escape($sheet) ?>">
  <input type="text" name="q" value="<?= html_escape($q) ?>" placeholder="Cari di semua kolom...">
  <?php if ($mode === 'data' && empty($kunci_kab) && ! empty($kab_nilai)): ?>
  <select name="kab"><option value="">Semua Kabupaten</option>
    <?php foreach ($kab_nilai as $v): ?><option value="<?= html_escape($v) ?>" <?= (string) $f['kab'] === (string) $v ? 'selected' : '' ?>><?= html_escape($v) ?> - <?= html_escape(Cermat_harga::nama_kab((int) $v)) ?></option><?php endforeach; ?></select>
  <?php elseif ($mode === 'data' && ! empty($kunci_kab)): ?>
  <span class="chip"><?= html_escape(Cermat_harga::nama_kab((int) $kunci_kab)) ?></span>
  <?php endif; ?>
  <select name="per" title="Baris per halaman">
    <?php foreach (array(25, 50, 100) as $n): ?><option value="<?= $n ?>" <?= $per === $n ? 'selected' : '' ?>><?= $n ?> baris</option><?php endforeach; ?></select>
  <button class="btn" type="submit">Terapkan</button>
  <a class="btn alt" href="<?= site_url('cermat/data_asli?p=' . $p . '&sheet=' . rawurlencode($sheet)) ?>">Reset</a>
</form>

<p class="hint"><?= $total ? 'Menampilkan ' . number_format($awal, 0, ',', '.') . '-' . number_format($akhir, 0, ',', '.') . ' dari ' . number_format($total, 0, ',', '.') . ' baris' : 'Tidak ada baris yang cocok.' ?></p>

<?php if ($mode === 'data' && empty($ada)): ?>
  <div class="card empty">Data sheet Data periode ini belum tersimpan.<br>Unggah ulang file Excel-nya lewat <a href="<?= site_url('cermat/unggah') ?>">Unggah Data</a>.</div>

<?php elseif ($mode === 'data'): ?>
<div class="tw"><table>
  <thead><tr>
    <?php foreach ($kolom as $i => $nm): if (strcasecmp(trim($nm), 'Column1') === 0) continue; ?><th style="text-transform:none;letter-spacing:0;font-size:13px"><?= html_escape($nm) ?></th><?php endforeach; ?>
  </tr></thead>
  <tbody>
  <?php foreach ($baris as $r): ?>
    <tr><?php foreach ($r as $i => $v): if (strcasecmp(trim((string) $kolom[$i]), 'Column1') === 0) continue; ?><td<?= ($v !== null && is_numeric($v)) ? ' style="text-align:right"' : '' ?>><?= html_escape((string) $v) ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<?php else: /* sheet lain: tampil seperti grid Excel */ ?>
<div class="tw"><table>
  <thead><tr><th style="text-transform:none;width:50px">#</th>
    <?php for ($i = 0; $i < $kolom_jml; $i++): ?><th style="text-transform:none;letter-spacing:0;font-size:13px"><?= Cermat_harga::huruf_kolom($i) ?></th><?php endfor; ?>
  </tr></thead>
  <tbody>
  <?php foreach ($baris as $b): ?>
    <tr><td class="hint"><?= (int) $b['r'] ?></td>
      <?php for ($i = 0; $i < $kolom_jml; $i++): $v = isset($b['v'][$i]) ? $b['v'][$i] : null; ?>
        <td<?= ($v !== null && is_numeric($v)) ? ' style="text-align:right"' : '' ?>><?= html_escape((string) $v) ?></td>
      <?php endfor; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="hint">Nilai ditampilkan apa adanya dari file (huruf kolom dan nomor baris mengikuti Excel). Sel berumus ditampilkan sebagai hasil hitung terakhir yang tersimpan di file.</p>
<?php endif; ?>

<?php if ( ! empty($halaman) && $halaman > 1):
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
<?php endif; ?>