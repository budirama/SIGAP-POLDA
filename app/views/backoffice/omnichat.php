<div id="omnichat" class="space-y-4" data-base="<?= htmlspecialchars($baseUrl) ?>" data-csrf="<?= htmlspecialchars($csrfToken) ?>"
     data-can-act="<?= $canAct ? '1' : '0' ?>">

  <!-- Header -->
  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C] flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <span class="chip-digital inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px]">OMNICHAT // KANAL: LIVE CHAT</span>
      <h2 class="mt-2 text-lg font-black text-slate-100 font-heading">Omnichat Command Center</h2>
      <p class="text-xs text-slate-400 mt-1">Layani percakapan warga secara langsung, ambil alih antrean, dan konversi aduan menjadi tiket laporan resmi.</p>
      <?php if (!$canAct): ?>
        <p class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-sky-300 bg-sky-950/60 border border-sky-800/60 px-2.5 py-1 rounded-lg">
          <i class="fas fa-eye"></i> Mode pengawasan (hanya lihat)
        </p>
      <?php endif; ?>
    </div>
    <div class="flex items-center gap-3">
      <div class="px-4 py-2 rounded-xl bg-[#0C0A08] border border-slate-800 text-center">
        <div class="text-xs text-slate-400">Antrean Menunggu</div>
        <div id="ocWaiting" class="text-lg font-black text-amber-400 font-mono">0</div>
      </div>
      <label class="flex items-center gap-2 text-[11px] text-slate-400 cursor-pointer">
        <input type="checkbox" id="ocSound" class="accent-amber-500"> Bunyi notifikasi
      </label>
    </div>
  </div>

  <div class="grid lg:grid-cols-[320px_1fr_300px] gap-4 min-h-[600px]">

    <!-- Daftar percakapan -->
    <aside id="ocListPane" class="polri-glass-card rounded-2xl border border-[#3B2F1C] flex flex-col overflow-hidden">
      <div class="p-3 border-b border-[#3B2F1C] flex flex-wrap gap-1.5 text-[11px]" role="tablist" aria-label="Filter percakapan">
        <?php foreach (['open' => 'Aktif', 'WAITING' => 'Menunggu', 'mine' => 'Milik Saya', 'BOT' => 'Asisten Virtual', 'CLOSED' => 'Selesai'] as $scope => $label): ?>
          <button type="button" role="tab" data-scope="<?= $scope ?>"
                  class="oc-tab px-2.5 py-1 rounded-lg border border-slate-700 text-slate-300 hover:bg-[#1B1610]"><?= $label ?></button>
        <?php endforeach; ?>
      </div>
      <ul id="ocList" class="flex-1 overflow-y-auto divide-y divide-[#3B2F1C]/60 max-h-[70vh]" aria-label="Daftar percakapan"></ul>
      <p id="ocListEmpty" class="p-6 text-center text-xs text-slate-500" hidden>Belum ada percakapan di daftar ini.</p>
    </aside>

    <!-- Ruang percakapan -->
    <section id="ocThreadPane" class="polri-glass-card rounded-2xl border border-[#3B2F1C] flex flex-col overflow-hidden min-h-[500px]">
      <div id="ocEmpty" class="flex-1 flex flex-col items-center justify-center text-center text-slate-500 text-xs p-8">
        <i class="fas fa-comments text-4xl text-amber-500/40 mb-3"></i>
        Pilih percakapan di sebelah kiri untuk mulai melayani warga.
      </div>
      <div id="ocThread" class="flex-1 min-h-0 flex flex-col" hidden>
        <header class="px-4 py-3 border-b border-[#3B2F1C] flex items-center gap-3">
          <button type="button" id="ocBack" class="lg:hidden p-2 text-slate-400" aria-label="Kembali ke daftar"><i class="fas fa-arrow-left"></i></button>
          <div class="flex-1 min-w-0">
            <p id="ocVisitor" class="text-sm font-bold text-slate-100 truncate"></p>
            <p id="ocMeta" class="text-[11px] text-slate-400 truncate"></p>
          </div>
          <span id="ocStatusBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full border"></span>
        </header>
        <div id="ocMessages" class="flex-1 min-h-0 overflow-y-auto p-4 space-y-3 text-xs max-h-[60vh]" aria-live="polite"></div>
        <form id="ocSend" class="p-3 border-t border-[#3B2F1C] flex items-end gap-2">
          <label for="ocInput" class="sr-only">Balasan</label>
          <textarea id="ocInput" rows="2" maxlength="1000" placeholder="Tulis balasan… (Enter kirim, Shift+Enter baris baru)"
                    class="flex-1 resize-none bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500"></textarea>
          <button type="submit" class="btn-gold h-10 px-4 rounded-xl font-bold text-xs flex-shrink-0">
            <i class="fas fa-paper-plane mr-1"></i> Kirim
          </button>
        </form>
        <p id="ocError" class="px-4 pb-2 text-[11px] text-red-300" role="alert"></p>
      </div>
    </section>

    <!-- Panel info & aksi -->
    <aside id="ocSide" class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-4 space-y-4 text-xs" hidden>
      <div>
        <h3 class="font-bold text-amber-400 uppercase tracking-wider text-[10px] mb-2">Data Warga</h3>
        <dl class="space-y-1.5 text-slate-300">
          <div class="flex justify-between gap-2"><dt class="text-slate-500">Nama</dt><dd id="ocInfoName" class="text-right"></dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">No. HP</dt><dd id="ocInfoPhone" class="text-right font-mono"></dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">Kanal</dt><dd class="text-right"><i class="fas fa-comments text-amber-400"></i> Live Chat</dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">Mulai</dt><dd id="ocInfoStarted" class="text-right"></dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">Petugas</dt><dd id="ocInfoAgent" class="text-right"></dd></div>
        </dl>
      </div>

      <div id="ocActions" class="space-y-2">
        <button type="button" id="ocClaim" class="w-full py-2 rounded-xl bg-blue-900/80 hover:bg-blue-800 text-blue-100 font-bold">
          <i class="fas fa-hand mr-1"></i> Ambil Alih Percakapan
        </button>
        <button type="button" id="ocClose" class="w-full py-2 rounded-xl bg-red-950/60 hover:bg-red-900/70 border border-red-800/50 text-red-200 font-bold">
          <i class="fas fa-circle-xmark mr-1"></i> Tutup Percakapan
        </button>
      </div>

      <div id="ocTicketInfo" class="p-3 rounded-xl border border-emerald-700/60 bg-emerald-950/40 text-emerald-200" hidden>
        Sudah menjadi tiket <b id="ocTicketNo" class="font-mono"></b>
      </div>

      <form id="ocConvert" class="space-y-2 border-t border-[#3B2F1C] pt-4">
        <h3 class="font-bold text-amber-400 uppercase tracking-wider text-[10px]">Buat Tiket Laporan</h3>
        <p class="text-[11px] text-slate-500">Kronologi diambil dari pesan warga. PIN pelacakan hanya dapat dibuka oleh warga di jendela chat-nya.</p>
        <select name="category_id" required class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-slate-100 outline-none focus:border-amber-500">
          <option value="">-- Kategori Perkara --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input name="title" required maxlength="200" placeholder="Judul laporan"
               class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-slate-100 outline-none focus:border-amber-500">
        <input name="incident_location" required maxlength="255" placeholder="Lokasi kejadian"
               class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-slate-100 outline-none focus:border-amber-500">
        <button type="submit" class="btn-gold w-full py-2 rounded-xl font-bold">
          <i class="fas fa-ticket mr-1"></i> Konversi ke Tiket Resmi
        </button>
      </form>
    </aside>
  </div>
</div>
<script src="<?= $baseUrl ?>/assets/js/omnichat.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/omnichat.js') ?: 1 ?>" defer></script>
