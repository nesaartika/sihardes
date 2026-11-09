</main>
<?php
$peran_f = $this->session->userdata('role');
?>
<style>
.kaki-app{display:block;margin-top:auto;border-top:0;background:var(--side);color:#cfd9ea;padding:30px 24px 0;font-size:13px}
.kaki-app .kolom{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr) minmax(0,1fr);gap:28px 40px;max-width:1400px}
.kaki-app nav{display:block}
.kaki-app h4{margin:0 0 10px;font-size:13px;color:#fff;letter-spacing:.2px}
.kaki-app .nama{display:block;color:#fff;font-size:16px;font-weight:700;margin-bottom:6px}
.kaki-app p{margin:0 0 3px;opacity:.75;line-height:1.5}
.kaki-app ul{list-style:none;margin:0;padding:0}
.kaki-app li{margin:0 0 7px}
.kaki-app a{color:#cfd9ea;text-decoration:none;font-weight:600}
.kaki-app a:hover{color:#fff;text-decoration:underline}
.kaki-app .bawah{margin-top:24px;padding:14px 0;border-top:1px solid rgba(255,255,255,.12);font-size:12px;opacity:.7}
@media(max-width:900px){.kaki-app .kolom{grid-template-columns:1fr}}
</style>
  <footer class="kaki-app">
    <div class="kolom">
      <div>
        <b class="nama">Fenomena Harga</b>
        <p>Pengumpulan Fenomena Perubahan Harga Perdesaan</p>
        <p>Badan Pusat Statistik Provinsi Kalimantan Barat</p>
      </div>
      <nav aria-label="Menu aplikasi">
        <h4>Menu</h4>
        <ul>
          <?php foreach ($tautan_menu as $nm => $url): ?>
            <li><a href="<?= site_url($url) ?>"><?= $nm ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <nav aria-label="Tautan lain">
        <h4>Tautan</h4>
        <ul>
          <li><a href="https://kalbar.bps.go.id" rel="noopener" target="_blank">kalbar.bps.go.id</a></li>
          <li><a href="<?= site_url('auth/logout') ?>">Keluar</a></li>
        </ul>
      </nav>
    </div>
    <div class="bawah">&copy; <?= date('Y') ?> Badan Pusat Statistik Provinsi Kalimantan Barat</div>
  </footer>
</div>
</body>
</html>