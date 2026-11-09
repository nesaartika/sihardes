<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fenomena Harga - BPS Provinsi Kalimantan Barat</title>
<meta name="description" content="Aplikasi pencermatan dan rekapitulasi perubahan harga perdesaan, BPS Provinsi Kalimantan Barat.">
<style>
/* Halaman depan Fenomena Harga. Mandiri: tidak memakai app.css (layout aplikasi berbasis sidebar). */
:root{
  --bg:#f3f5f9;--card:#fff;--tx:#1b2433;--mut:#5d6a7d;--br:#e2e7ef;
  --pri:#1d5fd0;--pri-tx:#fff;--navy:#0e2748;--navy2:#16365f;--on-navy:#e6edf8;--on-navy-mut:#a9b9d3;--aksen:#8db8ff;
  --naik:#14803c;--turun:#d6342c;--stb:#5d6a7d;--warn:#b45309;--warnbg:#fff4e0;--ok:#14803c;--okbg:rgba(20,128,60,.12);
  --alt:#e9eef6;
}
@media (prefers-color-scheme:dark){
  :root{
    --bg:#0f1520;--card:#182131;--tx:#e8edf5;--mut:#93a0b4;--br:#2a3548;
    --pri:#6aa3ff;--pri-tx:#0a1220;--navy:#0a1220;--navy2:#142038;--on-navy:#e8edf5;--on-navy-mut:#93a0b4;
    --naik:#4ade80;--turun:#ff7b72;--stb:#93a0b4;--warn:#fbbf24;--warnbg:#3a2c10;--ok:#4ade80;--okbg:rgba(74,222,128,.14);
    --alt:#121b2a;
  }
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--tx);font:16px/1.6 "Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif}
a{color:var(--pri)}
:focus-visible{outline:3px solid var(--aksen);outline-offset:2px}
.lebar{width:100%;max-width:1120px;margin:0 auto;padding:0 24px}

.lewati{position:absolute;left:12px;top:-60px;background:var(--card);color:var(--tx);padding:8px 14px;border-radius:8px;z-index:50}
.lewati:focus{top:12px}

/* tombol */
.tbl{display:inline-block;padding:11px 20px;border-radius:8px;font-weight:700;font-size:15px;text-decoration:none;line-height:1.2}
.tbl-utama{background:var(--pri);color:var(--pri-tx)}
.tbl-utama:hover{filter:brightness(1.08)}
.tbl-terang{background:transparent;color:var(--on-navy);border:1px solid rgba(255,255,255,.35);padding:9px 18px}
.tbl-terang:hover{background:rgba(255,255,255,.1)}
.hero .tbl-utama{background:#fff;color:var(--navy)}
@media (prefers-color-scheme:dark){.hero .tbl-utama{background:var(--pri);color:var(--pri-tx)}}

/* bilah atas + hero berbagi satu latar navy */
.atas{background:var(--navy)}
.atas-isi{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:16px;padding-bottom:16px}
.merek{display:flex;flex-direction:column;text-decoration:none;color:#fff;line-height:1.25}
.merek b{font-size:17px;letter-spacing:.2px}
.merek span{font-size:12px;color:var(--on-navy-mut)}

.hero{background:var(--navy);color:var(--on-navy);padding:40px 0 72px}
.hero-isi{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr);gap:48px;align-items:center}
.hero h1{margin:0 0 16px;font-size:clamp(2rem,4.4vw,3.25rem);line-height:1.1;font-weight:800;letter-spacing:-.025em;color:#fff;max-width:15em}
.lead{margin:0 0 28px;font-size:1.1rem;color:var(--on-navy-mut);max-width:34em}
.aksi{display:flex;flex-wrap:wrap;align-items:center;gap:14px 18px}
.catatan{font-size:14px;color:var(--on-navy-mut)}

/* tabel contoh: elemen utama halaman */
.buku{margin:0;background:var(--card);color:var(--tx);border-radius:14px;box-shadow:0 18px 40px rgba(0,0,0,.35);overflow:hidden}
.buku-kepala{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid var(--br)}
.buku-kepala b{font-size:15px}
.buku-kepala span{font-size:12px;color:var(--mut)}
.gulir{overflow-x:auto}
.buku table{width:100%;border-collapse:collapse;font-variant-numeric:tabular-nums}
.buku th,.buku td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--br);white-space:nowrap;font-size:14px}
.buku thead th{font-size:12px;font-weight:700;color:var(--mut);background:var(--bg)}
.buku tbody th{font-weight:700}
.buku .n{text-align:right}
.buku tbody tr:last-child th,.buku tbody tr:last-child td{border-bottom:0}
.naik{color:var(--naik);font-weight:700}.turun{color:var(--turun);font-weight:700}.stabil{color:var(--stb);font-weight:700}
.n span[aria-hidden]{font-size:10px;margin-right:2px}
.lencana{display:inline-block;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:700}
.lencana.perlu{background:var(--warnbg);color:var(--warn)}
.lencana.wajar{background:var(--okbg);color:var(--ok)}
.tandai th,.tandai td{box-shadow:inset 0 0 0 100vmax rgba(180,83,9,.06)}
.tandai th:first-child{box-shadow:inset 4px 0 0 var(--warn),inset 0 0 0 100vmax rgba(180,83,9,.06)}
.buku figcaption{padding:12px 18px 14px;font-size:13px;color:var(--mut);border-top:1px solid var(--br)}
/* satu gerak saat halaman dibuka: baris yang ditandai menyala sekali */
@media (prefers-reduced-motion:no-preference){
  .tandai th,.tandai td{animation:nyala 1.4s ease-out .5s both}
  @keyframes nyala{from{box-shadow:inset 0 0 0 100vmax rgba(180,83,9,0)}to{box-shadow:inset 0 0 0 100vmax rgba(180,83,9,.06)}}
  .tandai th:first-child{animation-name:nyala1}
  @keyframes nyala1{from{box-shadow:inset 0 0 0 var(--warn),inset 0 0 0 100vmax rgba(180,83,9,0)}to{box-shadow:inset 4px 0 0 var(--warn),inset 0 0 0 100vmax rgba(180,83,9,.06)}}
}

/* bagian isi */
.blok{padding:64px 0}
.blok-alt{background:var(--alt)}
.blok h2{margin:0 0 28px;font-size:clamp(1.4rem,2.6vw,1.9rem);line-height:1.25;letter-spacing:-.015em;max-width:24em}
.blok h3{margin:0 0 6px;font-size:1.05rem}
.blok p{margin:0;color:var(--mut)}

.langkah{list-style:none;margin:44px 0 0;padding:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;counter-reset:l}
.langkah li{position:relative;padding:20px 22px 4px 0;border-top:3px solid var(--br);counter-increment:l}
.langkah li::before{content:counter(l);position:absolute;top:-3px;left:0;transform:translateY(-100%);font-weight:800;color:var(--pri);font-size:14px;padding-bottom:6px}
.langkah li:first-child{border-top-color:var(--pri)}
.langkah p{font-size:15px}

.dua{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.peran{background:var(--card);border:1px solid var(--br);border-radius:12px;padding:22px 24px}
.peran ul{margin:10px 0 0;padding-left:20px;color:var(--mut)}
.peran li{margin:6px 0}
.penutup{margin-top:32px}

/* footer */
.kaki{background:var(--navy);color:var(--on-navy-mut);padding:40px 0 22px}
.kaki-isi{display:flex;justify-content:space-between;gap:32px;flex-wrap:wrap}
.kaki-merek b{display:block;color:#fff;font-size:17px;margin-bottom:6px}
.kaki-merek p{margin:0;font-size:14px}
.kaki-tautan{display:flex;flex-direction:column;gap:8px;font-size:14px}
.kaki-tautan a{color:var(--on-navy);text-decoration:none}
.kaki-tautan a:hover{text-decoration:underline}
.kaki-bawah{margin-top:28px;padding-top:16px;border-top:1px solid rgba(255,255,255,.12);font-size:13px}

@media (max-width:900px){
  .hero{padding:28px 0 48px}
  .hero-isi{grid-template-columns:1fr;gap:32px}
  .langkah{grid-template-columns:repeat(2,minmax(0,1fr));row-gap:36px}
  .langkah li{margin-top:22px}
  .dua{grid-template-columns:1fr}
  .blok{padding:48px 0}
}
@media (max-width:520px){
  .lebar{padding:0 18px}
  .langkah{grid-template-columns:1fr}
}

</style>
</head>
<body>
<a class="lewati" href="#isi">Lewati ke isi</a>

<header class="atas">
  <div class="lebar atas-isi">
    <a class="merek" href="<?= site_url('') ?>"><b>Fenomena Harga</b><span>BPS Provinsi Kalimantan Barat</span></a>
    
      <a class="tbl tbl-terang" href="<?= site_url('auth/login') ?>">Masuk</a>
    
  </div>
</header>

<main id="isi">
  <section class="hero">
    <div class="lebar hero-isi">
      <div class="hero-teks">
        <h1>Perubahan harga perdesaan, dicermati dalam satu tempat.</h1>
        <p class="lead">Unggah file Hitung RH, lihat komoditas yang berubah, dan catat alasannya per kabupaten. Rekap konfirmasi siap diunduh sebagai Excel.</p>
        <div class="aksi">
          
            <a class="tbl tbl-utama" href="<?= site_url('auth/login') ?>">Masuk ke aplikasi</a>
            <span class="catatan">Akun dibuat oleh admin provinsi.</span>
          
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
      
        <a href="<?= site_url('auth/login') ?>">Masuk</a>
      
      <a href="https://kalbar.bps.go.id" rel="noopener" target="_blank">kalbar.bps.go.id</a>
    </nav>
  </div>
  <div class="lebar"><div class="kaki-bawah">&copy; <?= date('Y') ?> BPS Provinsi Kalimantan Barat</div></div>
</footer>
</body>
</html>