<?php
$bulan = array('Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
$tahun_ini = (int) date('Y');
?>
<div class="card f" style="max-width:640px">
  <h2>Unggah Data</h2>
  <?php if ( ! empty($galat)): ?><div class="err"><?= html_escape($galat) ?></div><?php endif; ?>
  <?= form_open_multipart('cermat/proses') ?>
    <label for="berkas">File Excel Data (.xlsx)</label>
    <input type="file" name="berkas" id="berkas" accept=".xlsx" required>
    <p class="hint" id="info">Jenis data dan periode (bulan &amp; tahun) dikenali otomatis dari file. Periode yang sama boleh diunggah lebih dari sekali; data lama tidak dihapus (lihat Riwayat Periode).</p>

    <details id="lanjutan" style="margin:14px 0">
      <summary style="cursor:pointer;font-weight:700;color:var(--pri)">Pengaturan lanjutan</summary>
      <div style="display:flex;gap:14px;flex-wrap:wrap">
        <div style="flex:1;min-width:180px"><label for="bulan">Bulan</label>
          <select name="bulan" id="bulan" style="width:100%">
            <option value="">Otomatis dari nama file</option>
            <?php foreach ($bulan as $b): ?><option value="<?= $b ?>"><?= $b ?></option><?php endforeach; ?>
          </select></div>
        <div style="flex:1;min-width:140px"><label for="tahun">Tahun</label>
          <select name="tahun" id="tahun" style="width:100%">
            <option value="">Otomatis</option>
            <?php for ($t = $tahun_ini + 1; $t >= $tahun_ini - 4; $t--): ?><option value="<?= $t ?>"><?= $t ?></option><?php endfor; ?>
          </select></div>
      </div>
      <label for="ambang">Batas perubahan kecamatan (%)</label>
      <input type="number" name="ambang" id="ambang" value="<?= Cermat_harga::$opsi_default['ambang_kec'] ?>" min="0.1" max="100" step="0.1" style="width:100%">
      <p class="hint">Kecamatan masuk daftar konfirmasi bila harganya berubah searah dengan provinsi dan sebesar batas ini atau lebih.</p>
    </details>
    <p><button class="btn" type="submit" onclick="if(document.getElementById('berkas').value)this.textContent='Memproses...'">Proses Data</button></p>
  <?= form_close() ?>
</div>
<script>
document.getElementById('berkas').addEventListener('change', function(){
  var n = (this.files[0] ? this.files[0].name : ''), info = document.getElementById('info'), m, bln, thn;
  var nm = n.replace(/\.xlsx$/i, '');
  var d = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  if ((m = nm.match(/(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)[ _\-]*(\d{4}|\d{2})(?!\d)/i))) {
    bln = m[1].charAt(0).toUpperCase() + m[1].slice(1).toLowerCase(); thn = +m[2]; if (thn < 100) thn += 2000;
  } else if ((m = nm.match(/^(\d{2})(0[1-9]|1[0-2])(?!\d)/))) {
    bln = d[+m[2] - 1]; thn = 2000 + (+m[1]);
  }
  if (bln) { info.textContent = 'Periode terdeteksi: ' + bln + ' ' + thn + '. Jenis data dikenali otomatis dari isi file.'; info.style.color = ''; }
  else if (n) { info.textContent = 'Bulan dan tahun tidak terbaca dari nama file. Buka "Pengaturan lanjutan" lalu pilih bulan dan tahun.'; info.style.color = 'var(--warn)'; document.getElementById('lanjutan').open = true; }
});
</script>