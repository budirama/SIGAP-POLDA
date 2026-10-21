<div class="py-12 max-w-2xl mx-auto px-4">
  <div class="polri-glass-card rounded-2xl p-8 border border-emerald-600/40 shadow-xl text-center space-y-5">
    <div class="w-16 h-16 rounded-full bg-emerald-950 border border-emerald-600 text-emerald-300 flex items-center justify-center mx-auto text-2xl">
      <i class="fas fa-check"></i>
    </div>
    <div>
      <h1 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Laporan <?= $item['report_type'] === 'LOST' ? 'Kehilangan' : 'Temuan' ?> Terdaftar</h1>
      <p class="text-xs text-slate-400 mt-1">
        <?= $item['status'] === 'PENDING'
          ? 'Petugas akan memverifikasi laporan sebelum ditayangkan di galeri Hilang &amp; Temu.'
          : 'Laporan sudah terverifikasi dan tayang di galeri Hilang &amp; Temu.' ?>
      </p>
    </div>

    <div class="grid sm:grid-cols-2 gap-3 text-left">
      <div class="p-4 rounded-xl bg-[#0C0A08] border border-[#3B2F1C]">
        <div class="text-[10px] text-slate-400 uppercase font-bold">Kode Laporan</div>
        <div class="text-lg font-black font-mono text-amber-400" id="lfCode"><?= htmlspecialchars($item['code']) ?></div>
      </div>
      <div class="p-4 rounded-xl bg-[#0C0A08] border border-[#3B2F1C]">
        <div class="text-[10px] text-slate-400 uppercase font-bold">PIN Keamanan</div>
        <div class="text-lg font-black font-mono text-slate-100"><?= htmlspecialchars($pin) ?></div>
      </div>
    </div>

    <p class="text-[11px] text-amber-200 bg-amber-950/40 border border-amber-700/50 rounded-xl p-3">
      <i class="fas fa-triangle-exclamation mr-1"></i> Simpan kode &amp; PIN ini. PIN hanya ditampilkan sekali dan diperlukan untuk
      memantau status<?= $item['report_type'] === 'LOST' ? ' serta mencetak Surat Keterangan Tanda Lapor Kehilangan (SKTLK)' : '' ?>.
    </p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="<?= $baseUrl ?>/hilangtemu/status?code=<?= urlencode($item['code']) ?>" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-slate-950 text-xs font-black">
        <i class="fas fa-ticket mr-1"></i> Lihat Status
      </a>
      <a href="<?= $baseUrl ?>/hilangtemu" class="px-5 py-2.5 rounded-xl bg-[#1B1610] border border-[#3B2F1C] text-slate-200 text-xs font-bold">
        Kembali ke Galeri
      </a>
    </div>
  </div>
</div>
