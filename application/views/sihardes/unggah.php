<?php
$bulan = Sihardes_harga::$nama_bulan;
$tahun_ini = (int) date('Y');
?>
<div class="card f" style="max-width:640px">
  <h2>Unggah Data</h2>
  <?php if ( ! empty($galat)): ?><div class="err"><?= html_escape($galat) ?></div><?php endif; ?>
  <?= form_open_multipart('sihardes/proses') ?>
    <label for="berkas">File Excel Data (.xlsx)</label>
    <input type="file" name="berkas" id="berkas" accept=".xlsx" required>
    <p class="hint" id="info">Pilih file, lalu bulan dan tahun akan terisi otomatis dari nama file. Periode yang sama boleh diunggah lebih dari sekali; data lama tidak dihapus (lihat Riwayat Periode).</p>

    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:6px">
      <div style="flex:1;min-width:180px"><label for="bulan">Bulan</label>
        <select name="bulan" id="bulan" style="width:100%">
          <option value="">Pilih bulan</option>
          <?php foreach ($bulan as $b): ?><option value="<?= $b ?>"><?= $b ?></option><?php endforeach; ?>
        </select></div>
      <div style="flex:1;min-width:140px"><label for="tahun">Tahun</label>
        <select name="tahun" id="tahun" style="width:100%">
          <option value="">Pilih tahun</option>
          <?php for ($t = $tahun_ini + 1; $t >= $tahun_ini - 4; $t--): ?><option value="<?= $t ?>"><?= $t ?></option><?php endfor; ?>
        </select></div>
    </div>
    <p class="hint">Terisi otomatis bila nama file terbaca; bisa diubah sendiri jika kurang tepat.</p>
    <p><button class="btn" type="submit" onclick="if(document.getElementById('berkas').value)this.textContent='Memproses...'">Proses Data</button></p>
  <?= form_close() ?>
</div>
<script>
(function(){
  // Aturan baca sama dengan Sihardes_harga::baca_periode() di server
  var ALIAS = <?= json_encode(Sihardes_harga::$alias_bulan) ?>, NAMA = <?= json_encode(array_values($bulan)) ?>;
  var peta = {}, alt = [];
  Object.keys(ALIAS).forEach(function(no){ ALIAS[no].forEach(function(a){ peta[a] = +no; alt.push(a); }); });
  alt.sort(function(x, y){ return y.length - x.length; });
  var reNama = new RegExp('(?:^|[^A-Za-z])(' + alt.join('|') + ')(?![A-Za-z])[ _\\-.]*(\\d{4}|\\d{2})(?!\\d)', 'i');

  function baca(file){
    var nm = file.replace(/\.[^.]*$/, ''), m;
    if ((m = nm.match(reNama))) { var th = +m[2]; if (th < 100) th += 2000; return [NAMA[peta[m[1].toLowerCase()] - 1], th]; }
    if ((m = nm.match(/^(\d{2})(0[1-9]|1[0-2])(?!\d)/))) return [NAMA[+m[2] - 1], 2000 + (+m[1])];
    if ((m = nm.match(/(?:^|\D)(20\d{2})[ _\-.](0[1-9]|1[0-2])(?!\d)/))) return [NAMA[+m[2] - 1], +m[1]];
    if ((m = nm.match(/(?:^|\D)(0[1-9]|1[0-2])[ _\-.](20\d{2})(?!\d)/))) return [NAMA[+m[1] - 1], +m[2]];
    return null;
  }

  var fl = document.getElementById('berkas'), info = document.getElementById('info'),
      sb = document.getElementById('bulan'), st = document.getElementById('tahun');
  fl.addEventListener('change', function(){
    var n = this.files[0] ? this.files[0].name : '';
    if (!n) return;
    var p = baca(n);
    if (p) {
      sb.value = p[0];
      if (![].some.call(st.options, function(o){ return o.value === String(p[1]); })) st.add(new Option(p[1], p[1]));
      st.value = String(p[1]);
      info.textContent = 'Periode terbaca dari nama file: ' + p[0] + ' ' + p[1] + '. Bulan dan tahun sudah terisi otomatis.';
      info.style.color = '';
    } else {
      sb.value = ''; st.value = '';
      info.textContent = 'Bulan dan tahun tidak terbaca dari nama file. Pilih bulan dan tahun di bawah ini.';
      info.style.color = 'var(--warn)';
    }
  });
})();
</script>