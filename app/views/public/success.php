<div class="py-16 max-w-2xl mx-auto px-4 text-center">
  
  <div class="polri-glass-card rounded-3xl p-8 sm:p-10 border border-emerald-500/40 shadow-2xl relative overflow-hidden">
    
    <!-- Top Green Glow -->
    <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

    <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-3xl mb-6 shadow-lg shadow-emerald-500/10">
      <i class="fas fa-check-double"></i>
    </div>

    <h1 class="text-2xl sm:text-3xl font-black text-slate-100 font-heading">
      Laporan Anda Berhasil Terdaftar!
    </h1>
    
    <p class="text-slate-300 text-xs sm:text-sm mt-2 max-w-md mx-auto leading-relaxed">
      Laporan telah masuk ke antrean verifikasi petugas SPKT Command Center Polri. Harap simpan data kredensial di bawah ini untuk melacak perkembangan perkara.
    </p>

    <!-- Ticket & PIN Credentials Box -->
    <div class="my-6 p-6 rounded-2xl bg-[#070F1E] border border-amber-500/30 text-left space-y-4 shadow-inner">
      
      <div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nomor Registrasi Laporan (Nomor Tiket):</span>
        <div class="text-xl sm:text-2xl font-black text-amber-400 font-mono tracking-wider flex items-center justify-between mt-0.5">
          <span id="ticketTxt"><?= htmlspecialchars($report['ticket_number']) ?></span>
          <button onclick="navigator.clipboard.writeText('<?= $report['ticket_number'] ?>'); alert('Nomor tiket berhasil disalin!');" 
                  class="text-xs text-slate-400 hover:text-amber-400 px-2.5 py-1 rounded bg-slate-800 border border-slate-700">
            <i class="fas fa-copy"></i> Salin
          </button>
        </div>
      </div>

      <div class="border-t border-slate-800 pt-3 flex items-center justify-between">
        <div>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PIN Keamanan Pelacakan:</span>
          <div class="text-xl font-black text-slate-100 font-mono tracking-widest">
            <?= htmlspecialchars($report['security_pin']) ?>
          </div>
        </div>
        <div class="text-[11px] text-amber-400/90 font-medium bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-lg">
          <i class="fas fa-lock text-xs mr-1"></i> Jangan bagikan PIN ini
        </div>
      </div>

      <div class="border-t border-slate-800 pt-3">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Judul Aduan:</span>
        <p class="text-xs font-semibold text-slate-200 mt-0.5">
          <?= htmlspecialchars($report['title']) ?>
        </p>
      </div>

    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
      <a href="<?= $baseUrl ?>/report/track?ticket=<?= urlencode($report['ticket_number']) ?>"
         class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black rounded-xl text-xs transition-all shadow-lg flex items-center justify-center gap-2">
        <i class="fas fa-magnifying-glass"></i>
        <span>Lacak Progres Laporan Sekarang</span>
      </a>

      <a href="https://api.whatsapp.com/send?text=Nomor+Tiket+Laporan+Kepolisian+Saya%3A+<?= urlencode($report['ticket_number']) ?>" target="_blank" rel="noopener noreferrer"
         class="w-full sm:w-auto px-5 py-3 bg-emerald-950 hover:bg-emerald-900 border border-emerald-700 text-emerald-300 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2">
        <i class="fab fa-whatsapp text-sm"></i>
        <span>Simpan Catatan ke WhatsApp</span>
      </a>
    </div>

    <div class="mt-6 text-center">
      <a href="<?= $baseUrl ?>/" class="text-xs text-slate-400 hover:text-slate-200 transition-colors">
        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Halaman Utama
      </a>
    </div>

  </div>

</div>
