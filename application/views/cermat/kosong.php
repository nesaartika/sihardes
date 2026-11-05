<?php if ( ! empty($tanpa_tabel_harga)): ?>
<div class="card empty">
  <b>Halaman ini belum tersedia untuk file yang sedang dibuka.</b><br>
  File periode ini bukan tabel harga (tidak ada kolom Kabupaten, Kecamatan, Kode Komoditas, dan harga), jadi RH, Rekap Konfirmasi, dan Komoditas tidak dibuat.<br>
  Isi file tetap bisa dilihat di menu <a href="<?= site_url('cermat/data_asli' . (empty($p) ? '' : '?p=' . $p)) ?>">Data</a>, atau pilih file lain pada pilihan di bagian atas.
</div>
<?php else: ?>
<div class="card empty">
  <b>Belum ada data yang dapat dikonfirmasi.</b><br>
  Admin provinsi belum mengunggah data periode ini. Silakan coba lagi nanti.
</div>
<?php endif; ?>