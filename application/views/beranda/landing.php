<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fenomena Harga - BPS Provinsi Kalimantan Barat</title>
<meta name="description" content="Aplikasi pencermatan dan rekapitulasi perubahan harga perdesaan, BPS Provinsi Kalimantan Barat.">
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/landing.css') ?>">
</head>
<body>
<a class="lewati" href="#isi">Lewati ke isi</a>

<header class="atas">
  <div class="lebar atas-isi">
    <a class="merek" href="<?= site_url('') ?>"><b>Fenomena Harga</b><span>BPS Provinsi Kalimantan Barat</span></a>
    <?php if ($sudah_masuk): ?>
      <a class="tbl tbl-terang" href="<?= site_url('cermat') ?>">Buka dashboard</a>
    <?php else: ?>
      <a class="tbl tbl-terang" href="<?= site_url('auth/login') ?>">Masuk</a>
    <?php endif; ?>
  </div>
</header>

<main id="isi">
  <section class="hero">
    <div class="lebar hero-isi">
      <div class="hero-teks">
        <h1>Perubahan harga perdesaan, dicermati dalam satu tempat.</h1>
        <p class="lead">Unggah file Hitung RH, lihat komoditas yang berubah, dan catat alasannya per kabupaten. Rekap konfirmasi siap diunduh sebagai Excel.</p>
        <div class="aksi">
          <?php if ($sudah_masuk): ?>
            <a class="tbl tbl-utama" href="<?= site_url('cermat') ?>">Buka dashboard</a>
            <span class="catatan">Masuk sebagai <?= html_escape($nama !== '' ? $nama : 'pengguna') ?></span>
          <?php else: ?>
            <a class="tbl tbl-utama" href="<?= site_url('auth/login') ?>">Masuk ke aplikasi</a>
            <span class="catatan">Akun dibuat oleh admin provinsi.</span>
          <?php endif; ?>
        </div>
      </div>

      <figure class="buku" aria-labelledby="buku-ket">
        <div class="buku-kepala">
          <b>Rekap konfirmasi</b>
          <span>Contoh tampilan, angka rekaan</span>
        </div>
        <div class="gulir">
          <table>
            <thead>
              <tr><th scope="col">Komoditas</th><th scope="col" class="n">Provinsi</th><th scope="col" class="n">Kecamatan terjauh</th><th scope="col">Status</th></tr>
            </thead>
            <tbody>
              <tr class="tandai">
                <th scope="row">Beras</th>
                <td class="n naik"><span aria-hidden="true">&#9650;</span> +1,8%</td>
                <td class="n naik"><span aria-hidden="true">&#9650;</span> +6,2%</td>
                <td><span class="lencana perlu">Perlu konfirmasi</span></td>
              </tr>
              <tr class="tandai">
                <th scope="row">Cabai rawit</th>
                <td class="n turun"><span aria-hidden="true">&#9660;</span> &minus;7,4%</td>
                <td class="n turun"><span aria-hidden="true">&#9660;</span> &minus;9,1%</td>
                <td><span class="lencana perlu">Perlu konfirmasi</span></td>
              </tr>
              <tr>
                <th scope="row">Minyak goreng</th>
                <td class="n naik"><span aria-hidden="true">&#9650;</span> +0,6%</td>
                <td class="n naik"><span aria-hidden="true">&#9650;</span> +1,1%</td>
                <td><span class="lencana wajar">Wajar</span></td>
              </tr>
              <tr>
                <th scope="row">Garam</th>
                <td class="n stabil">0,0%</td>
                <td class="n turun"><span aria-hidden="true">&#9660;</span> &minus;0,9%</td>
                <td><span class="lencana wajar">Wajar</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <figcaption id="buku-ket">Kecamatan yang searah dengan provinsi dan berubah 5% atau lebih masuk daftar konfirmasi.</figcaption>
      </figure>
    </div>
  </section>

  <section class="blok" aria-labelledby="alur">
    <div class="lebar">
      <h2 id="alur">Dari file Excel sampai rekap, empat langkah</h2>
      <ol class="langkah">
        <li>
          <h3>Unggah file</h3>
          <p>Pilih file Hitung RH bulanan (.xlsx), lalu tentukan bulan dan tahunnya.</p>
        </li>
        <li>
          <h3>Perubahan dihitung</h3>
          <p>Perubahan provinsi per komoditas memakai rata-rata geometrik, sama dengan kolom RHt di sheet RH.</p>
        </li>
        <li>
          <h3>Alasan dicatat</h3>
          <p>Komoditas dan kecamatan yang masuk daftar konfirmasi diberi alasan oleh petugas kabupaten.</p>
        </li>
        <li>
          <h3>Rekap diunduh</h3>
          <p>Rekap konfirmasi dan hasil RH diunduh sebagai Excel dalam format yang sudah dipakai.</p>
        </li>
      </ol>
    </div>
  </section>

  <section class="blok blok-alt" aria-labelledby="peran">
    <div class="lebar">
      <h2 id="peran">Setiap akun melihat yang perlu dilihat</h2>
      <div class="dua">
        <div class="peran">
          <h3>Admin provinsi</h3>
          <ul>
            <li>Mengunggah data bulanan dan memilih periode</li>
            <li>Memantau dashboard, daftar komoditas, dan riwayat periode</li>
            <li>Mengelola akun pengguna dan melihat log aktivitas</li>
          </ul>
        </div>
        <div class="peran">
          <h3>Akun kabupaten</h3>
          <ul>
            <li>Membuka Data, RH, dan Rekap Konfirmasi untuk kabupatennya</li>
            <li>Mengisi dan menyimpan alasan perubahan harga</li>
            <li>Meminta kata sandi atau akun baru ke admin provinsi</li>
          </ul>
        </div>
      </div>
      <div class="penutup">
        <?php if ($sudah_masuk): ?>
          <a class="tbl tbl-utama" href="<?= site_url('cermat') ?>">Buka dashboard</a>
        <?php else: ?>
          <a class="tbl tbl-utama" href="<?= site_url('auth/login') ?>">Masuk ke aplikasi</a>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<footer class="kaki">
  <div class="lebar kaki-isi">
    <div class="kaki-merek">
      <b>Fenomena Harga</b>
      <p>Pengumpulan Fenomena Perubahan Harga Perdesaan</p>
      <p>Badan Pusat Statistik Provinsi Kalimantan Barat</p>
    </div>
    <nav class="kaki-tautan" aria-label="Tautan footer">
      <?php if ($sudah_masuk): ?>
        <a href="<?= site_url('cermat') ?>">Dashboard</a>
      <?php else: ?>
        <a href="<?= site_url('auth/login') ?>">Masuk</a>
      <?php endif; ?>
      <a href="https://kalbar.bps.go.id" rel="noopener" target="_blank">kalbar.bps.go.id</a>
    </nav>
  </div>
  <div class="lebar"><div class="kaki-bawah">&copy; <?= date('Y') ?> BPS Provinsi Kalimantan Barat</div></div>
</footer>
</body>
</html>