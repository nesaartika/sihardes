</main>
<style>
.kaki-app{margin-top:auto;border-top:1px solid var(--br);background:var(--card);padding:14px 24px;display:flex;justify-content:space-between;align-items:center;gap:8px 20px;flex-wrap:wrap;font-size:12px;color:var(--mut)}
.kaki-app b{color:var(--tx)}
.kaki-app nav{display:flex;gap:16px;flex-wrap:wrap}
.kaki-app a{font-weight:600}
@media (max-width:900px){
  .kaki-app{flex-direction:column;justify-content:center;text-align:center;gap:4px;padding:12px 16px;font-size:11.5px;line-height:1.5}
  .kaki-app nav{justify-content:center}
}
</style>
  <footer class="kaki-app">
    <span>Badan Pusat Statistik Provinsi Kalimantan Barat <?= date('Y') ?></span>
    <nav aria-label="Tautan footer">
      <a href="https://kalbar.bps.go.id" rel="noopener" target="_blank">kalbar.bps.go.id</a>
    </nav>
  </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>