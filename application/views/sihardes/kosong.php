<?php if ( ! empty($tanpa_tabel_harga)): ?>
<div class="card empty">
  <?php if (isset($menu) && $menu === 'rh'): ?>
    <b>RH tidak dibuat untuk file yang sedang dibuka.</b><br>
    RH dihitung dari tabel harga (kolom Kabupaten, Kecamatan, Kode Komoditas, dan harga), sedangkan file ini berformat lain.<br>
    Komoditas dan Rekap tetap dibuat dari tiap sheet, dan isi lengkapnya ada di menu <a href="<?= site_url('cermat/data_asli' . (empty($p) ? '' : '?p=' . $p)) ?>">Data</a>.
  <?php elseif (isset($menu) && $menu === 'dashboard'): ?>
    <b>Dashboard dibuat dari tabel harga.</b><br>
    File yang sedang dibuka berformat lain, jadi lihat isinya di menu
    <a href="<?= site_url('cermat/komoditas' . (empty($p) ? '' : '?p=' . $p)) ?>">Komoditas</a>,
    <a href="<?= site_url('cermat/rekap' . (empty($p) ? '' : '?p=' . $p)) ?>">Rekap</a>, atau
    <a href="<?= site_url('cermat/data_asli' . (empty($p) ? '' : '?p=' . $p)) ?>">Data</a>.
  <?php elseif ($this->session->userdata('role') === 'kabupaten'): ?>
    <b>Belum ada yang perlu Anda konfirmasi pada file yang sedang dibuka.</b><br>
    File ini bukan tabel harga. Pilih file lain pada pilihan di bagian atas, atau tunggu admin provinsi mengunggah data periode berikutnya.
  <?php else: ?>
    <b>Halaman ini belum bisa ditampilkan untuk file yang sedang dibuka.</b><br>
    Periode ini belum menyimpan isi sheet-nya. Unggah ulang file Excel-nya lewat <a href="<?= site_url('cermat/unggah') ?>">Unggah Data</a>,
    atau pilih file lain pada pilihan di bagian atas.
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card empty">
  <b>Belum ada data yang dapat dikonfirmasi.</b><br>
  Admin provinsi belum mengunggah data periode ini. Silakan coba lagi nanti.
</div>
<?php endif; ?>