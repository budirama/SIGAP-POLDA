<?php
/**
 * Widget Live Chat warga (kanal chat SPKT Polri). Logika di public/assets/js/livechat.js.
 * Butuh: $baseUrl, $csrfToken.
 */
?>
<div id="livechat" data-base="<?= htmlspecialchars($baseUrl) ?>" data-csrf="<?= htmlspecialchars($csrfToken) ?>"
     class="fixed bottom-4 right-4 z-[60] flex flex-col items-end gap-3 print:hidden">

  <!-- Panel chat -->
  <section id="lcPanel" role="dialog" aria-label="Live Chat SPKT Polri" hidden
           class="w-[calc(100vw-2rem)] max-w-sm h-[min(560px,calc(100vh-7rem))] flex flex-col rounded-2xl overflow-hidden border border-amber-500/40 bg-[#14100C] shadow-2xl">
    <header class="px-4 py-3 bg-gradient-to-r from-[#4A0E0E] via-[#14100C] to-[#14100C] border-b border-[#3B2F1C] flex items-center gap-3">
      <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="" class="h-9 w-9 object-contain">
      <div class="flex-1 min-w-0">
        <p class="text-sm font-extrabold text-slate-100 font-heading">Live Chat SPKT Polri</p>
        <p id="lcStatus" class="text-[11px] text-amber-300 truncate">Petugas Command Center siap membantu</p>
      </div>
      <button type="button" id="lcClose" class="p-2 text-slate-400 hover:text-slate-100" aria-label="Tutup jendela chat">
        <i class="fas fa-xmark"></i>
      </button>
    </header>

    <!-- Formulir mulai -->
    <form id="lcStart" class="flex-1 overflow-y-auto p-4 space-y-3 text-xs">
      <p class="text-slate-300 leading-relaxed">
        Sampaikan pertanyaan atau aduan Anda. Untuk keadaan darurat, segera hubungi <b class="text-red-300">110</b>.
      </p>
      <label class="block">
        <span class="block font-semibold text-slate-300 mb-1">Nama</span>
        <input name="name" required maxlength="100" autocomplete="name"
               class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-slate-100 outline-none focus:border-amber-500">
      </label>
      <label class="block">
        <span class="block font-semibold text-slate-300 mb-1">No. HP <span class="text-slate-500 font-normal">(opsional)</span></span>
        <input name="phone" inputmode="tel" maxlength="16" autocomplete="tel"
               class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-slate-100 outline-none focus:border-amber-500">
      </label>
      <label class="block">
        <span class="block font-semibold text-slate-300 mb-1">Pesan</span>
        <textarea name="message" required maxlength="1000" rows="4"
                  class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-slate-100 outline-none focus:border-amber-500"></textarea>
      </label>
      <p id="lcStartError" class="text-red-300 hidden" role="alert"></p>
      <button type="submit" class="btn-gold w-full py-2.5 rounded-xl font-extrabold">
        <i class="fas fa-paper-plane mr-1"></i> Mulai Chat
      </button>
      <p class="text-[10px] text-slate-500">Percakapan dicatat oleh sistem dan dapat ditindaklanjuti menjadi laporan resmi.</p>
    </form>

    <!-- Ruang percakapan -->
    <div id="lcRoom" class="flex-1 min-h-0 flex-col" hidden>
      <div id="lcMessages" class="flex-1 min-h-0 overflow-y-auto p-4 space-y-3 text-xs" aria-live="polite"></div>
      <div id="lcTicket" class="mx-4 mb-2 p-3 rounded-xl border border-emerald-700/60 bg-emerald-950/50 text-emerald-200 text-xs" hidden>
        <p>Tiket laporan: <b id="lcTicketNo" class="font-mono"></b></p>
        <button type="button" id="lcTicketBtn" class="mt-2 w-full py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-bold">
          Lihat Tiket &amp; PIN
        </button>
      </div>
      <form id="lcSend" class="p-3 border-t border-[#3B2F1C] flex items-end gap-2">
        <label for="lcInput" class="sr-only">Tulis pesan</label>
        <textarea id="lcInput" rows="1" maxlength="1000" placeholder="Tulis pesan…"
                  class="flex-1 resize-none max-h-28 bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500"></textarea>
        <button type="submit" class="btn-gold h-10 w-10 rounded-xl flex-shrink-0" aria-label="Kirim pesan">
          <i class="fas fa-paper-plane"></i>
        </button>
      </form>
      <div class="px-3 pb-3 flex justify-between items-center text-[10px] text-slate-500">
        <span id="lcError" class="text-red-300" role="alert"></span>
        <button type="button" id="lcEnd" class="hover:text-red-300">Akhiri chat</button>
      </div>
    </div>
  </section>

  <!-- Tombol pembuka -->
  <button type="button" id="lcToggle" aria-controls="lcPanel" aria-expanded="false"
          class="btn-gold relative h-14 pl-4 pr-5 rounded-full shadow-2xl flex items-center gap-2 font-extrabold text-sm">
    <i class="fas fa-comments text-lg"></i>
    <span class="hidden sm:inline">Live Chat SPKT</span>
    <span id="lcBadge" class="absolute -top-1 -right-1 h-5 min-w-[1.25rem] px-1 rounded-full bg-red-600 text-white text-[10px] leading-5 text-center" hidden>0</span>
  </button>
</div>
<script src="<?= $baseUrl ?>/assets/js/livechat.js?v=<?= @filemtime(dirname(__DIR__, 3) . '/public/assets/js/livechat.js') ?: 1 ?>" defer></script>
