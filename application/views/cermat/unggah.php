<?php
$bulan = array('Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
$tahun_ini = (int) date('Y');
$bln_default = (int) date('n') - 1;
?>
<div class="card f" style="max-width:640px">
  <h2>Unggah Data</h2>
  <ol class="hint" style="margin:0 0 14px 18px;padding:0;line-height:1.7">
    <li><b>Unggah data</b> - file Excel yang berisi data harga mentah per kecamatan (sheet <b>Data</b>), sebelum menjadi RH.</li>
    <li><b>Hasil RH</b> - aplikasi menghitung RH sendiri dari data tersebut (menu Hasil RH).</li>
    <li><b>Rekap Konfirmasi</b> - dari arah RH, aplikasi mencari kabupaten dan kecamatan yang berpengaruh.</li>
  </ol>
  <?php if ( ! empty($galat)): ?><div class="err"><?= html_escape($galat) ?></div><?php endif; ?>
  <?= form_open_multipart('cermat/proses') ?>
    <label for="berkas">File Excel Data (.xlsx)</label>
    <input type="file" name="berkas" id="berkas" accept=".xlsx" required>

    <div style="display:flex;gap:14px;flex-wrap:wrap">
      <div style="flex:1;min-width:180px"><label for="bulan">Bulan</label>
        <select name="bulan" id="bulan" style="width:100%">
          <?php foreach ($bulan as $i => $b): ?><option value="<?= $b ?>" <?= $i === $bln_default ? 'selected' : '' ?>><?= $b ?></option><?php endforeach; ?>
        </select></div>
      <div style="flex:1;min-width:140px"><label for="tahun">Tahun</label>
        <select name="tahun" id="tahun" style="width:100%">
          <?php for ($t = $tahun_ini + 1; $t >= $tahun_ini - 4; $t--): ?><option value="<?= $t ?>" <?= $t === $tahun_ini ? 'selected' : '' ?>><?= $t ?></option><?php endfor; ?>
        </select></div>
    </div>
    <p class="hint">Yang dibaca adalah sheet <b>Data</b> (kolom Kabupaten, Nama Kecamatan, Kode Komoditas, Nama Komoditas, Nama Kualitas, Harga Bulan Ini, Harga Bulan Sebelumnya). Sheet lain di file ikut tersimpan dan bisa dilihat di Data Asli. File tidak harus sudah berisi hasil RH.</p>
    <p class="hint">Bulan dan tahun terisi otomatis dari nama file bila terbaca. Periode yang sama boleh diunggah lebih dari sekali; setiap file tersimpan sendiri dan data lama tidak dihapus (lihat Riwayat Periode).</p>
    <p><button class="btn" type="submit" onclick="if(document.getElementById('berkas').value)this.textContent='Memproses...'">Proses Data</button></p>
  <?= form_close() ?>
</div>
<script>
document.getElementById('berkas').addEventListener('change', function(){
  var n = (this.files[0] ? this.files[0].name : '');
  var m = n.match(/(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)[ _\-]*(\d{4})/i);
  if (!m) return;
  var b = m[1].charAt(0).toUpperCase() + m[1].slice(1).toLowerCase();
  document.getElementById('bulan').value = b;
  document.getElementById('tahun').value = m[2];
});
</script>