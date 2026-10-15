<!-- Hero Section -->
<section class="relative overflow-hidden py-16 lg:py-24 border-b border-[#1E355B]/60">
  <!-- Background Glow & Grid Accent -->
  <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-blue-900/25 via-[#070F1E] to-[#070F1E] -z-10"></div>
  <div class="absolute top-1/4 right-5 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>

  <div class="max-w-7xl mx-auto px-4">
    <div class="grid lg:grid-cols-12 gap-12 items-center">
      
      <!-- Left Content -->
      <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
        
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#0E1E38] border border-amber-500/30 text-amber-400 text-xs font-bold tracking-wide shadow-md">
          <i class="fas fa-shield-halved text-amber-400"></i>
          <span>SIGAP POLRI • Pelayanan Terpadu & Transparan</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-100 tracking-tight leading-[1.15] font-heading">
          Laporkan Tindak Pidana & Pantau <span class="text-gold-gradient">Progres SP2HP</span> Secara Terbuka.
        </h1>

        <p class="text-slate-300 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto lg:mx-0 font-normal">
          Sistem terintegrasi resmi Kepolisian Daerah untuk menerima laporan kriminalitas, siber, narkoba, lalu lintas, hingga dumas propam. Setiap tahapan penanganan dapat Anda lacak secara transparan dari ponsel Anda.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
          <a href="<?= $baseUrl ?>/report/create" 
             class="w-full sm:w-auto px-7 py-3.5 bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black rounded-xl text-sm transition-all shadow-xl hover:shadow-amber-500/25 flex items-center justify-center gap-2.5">
            <i class="fas fa-bullhorn"></i>
            <span>Buat Laporan Sekarang</span>
          </a>

          <a href="<?= $baseUrl ?>/report/track" 
             class="w-full sm:w-auto px-6 py-3.5 bg-[#0E1E38] hover:bg-[#132442] text-slate-200 border border-[#1E355B] hover:border-amber-500/40 font-bold rounded-xl text-sm transition-all flex items-center justify-center gap-2">
            <i class="fas fa-magnifying-glass text-amber-400"></i>
            <span>Lacak Status Tiket Anda</span>
          </a>
        </div>

        <!-- Quick Track Input Bar -->
        <div class="pt-4 max-w-lg mx-auto lg:mx-0">
          <form action="<?= $baseUrl ?>/report/track" method="GET" class="flex items-center gap-2 p-1.5 bg-[#0A192F]/90 border border-amber-500/30 rounded-2xl shadow-xl">
            <div class="pl-3 text-slate-500"><i class="fas fa-ticket text-amber-500"></i></div>
            <input type="text" name="ticket" required placeholder="Contoh: LP-POLRI-202610-0001" 
                   class="bg-transparent flex-1 text-xs text-slate-100 placeholder-slate-500 outline-none px-2 py-1.5 font-mono uppercase">
            <button type="submit" class="px-4 py-2 bg-blue-900/80 hover:bg-blue-800 text-amber-300 font-bold rounded-xl text-xs transition-colors flex items-center gap-1.5">
              <span>Cek</span> <i class="fas fa-arrow-right text-[10px]"></i>
            </button>
          </form>
          <p class="text-[11px] text-slate-400 mt-2 flex items-center gap-1.5 justify-center lg:justify-start">
            <i class="fas fa-user-shield text-emerald-400"></i>
            <span>Tersedia opsi <b>Whistleblower</b>: Identitas Anda dapat dirahasiakan penuh.</span>
          </p>
        </div>

      </div>

      <!-- Right Emblem & Highlights -->
      <div class="lg:col-span-5 flex justify-center">
        <div class="relative w-full max-w-md">
          
          <!-- Outer Decorative Card -->
          <div class="polri-glass-card rounded-3xl p-8 relative overflow-hidden shadow-2xl border border-amber-500/30">
            <div class="absolute -top-10 -right-10 w-36 h-36 bg-red-600/10 rounded-full blur-2xl"></div>
            
            <div class="text-center space-y-4">
              <div class="w-44 h-auto mx-auto relative group">
                <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat.png" alt="Polisi Bersama Masyarakat" class="w-full h-auto object-contain filter drop-shadow-[0_12px_24px_rgba(217,119,6,0.35)] transition-transform duration-300 group-hover:scale-105">
              </div>

              <div>
                <div class="inline-block px-3 py-1 rounded-full bg-red-950/80 border border-red-700/60 text-red-300 text-[10px] font-black tracking-widest uppercase mb-1">
                  KEMITRAAN & TRANSPARANSI
                </div>
                <h3 class="text-xl font-black text-slate-100 font-heading">POLISI BERSAMA MASYARAKAT</h3>
                <p class="text-xs text-amber-400 font-bold tracking-wider">MELINDUNGI • MENGAYOMI • MELAYANI</p>
              </div>

              <div class="space-y-2.5 pt-2 text-left">
                <div class="p-3 rounded-xl bg-[#070F1E]/80 border border-slate-800 flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-blue-950 text-blue-400 flex items-center justify-center font-bold text-xs"><i class="fas fa-mobile-screen"></i></div>
                  <div>
                    <div class="text-xs font-bold text-slate-200">Pantau Progres Kapan Saja</div>
                    <div class="text-[11px] text-slate-400">Cek SP2HP terbaru dengan Nomor Tiket &amp; PIN Keamanan</div>
                  </div>
                </div>

                <div class="p-3 rounded-xl bg-[#070F1E]/80 border border-slate-800 flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-amber-950 text-amber-400 flex items-center justify-center font-bold text-xs"><i class="fas fa-map-location-dot"></i></div>
                  <div>
                    <div class="text-xs font-bold text-slate-200">Integrasi Peta TKP Spasial</div>
                    <div class="text-[11px] text-slate-400">Akurasi lokasi kejadian langsung di peta digital</div>
                  </div>
                </div>

                <div class="p-3 rounded-xl bg-[#070F1E]/80 border border-slate-800 flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-emerald-950 text-emerald-400 flex items-center justify-center font-bold text-xs"><i class="fas fa-file-shield"></i></div>
                  <div>
                    <div class="text-xs font-bold text-slate-200">Unduh Dokumen SP2HP Digital</div>
                    <div class="text-[11px] text-slate-400">Cetak atau simpan sebagai PDF setelah SP2HP diterbitkan</div>
                  </div>
                </div>
              </div>

            </div>

          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- Stats Bar -->
<section class="border-b border-[#1E355B]/60 bg-[#0A192F]/40 py-8">
  <div class="max-w-7xl mx-auto px-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
      
      <div class="p-4 rounded-2xl bg-[#0E1E38]/60 border border-[#1E355B]/50">
        <div class="text-2xl sm:text-3xl font-black text-slate-100 font-heading"><?= number_format($stats['total_reports']) ?></div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Total Laporan Masuk</div>
      </div>

      <div class="p-4 rounded-2xl bg-[#0E1E38]/60 border border-[#1E355B]/50">
        <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-heading"><?= $stats['clearance_rate'] ?>%</div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Tingkat Penyelesaian (Clearance)</div>
      </div>

      <div class="p-4 rounded-2xl bg-[#0E1E38]/60 border border-[#1E355B]/50">
        <div class="text-2xl sm:text-3xl font-black text-amber-400 font-heading"><?= number_format($stats['investigating']) ?></div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Dalam Penyelidikan Aktif</div>
      </div>

      <div class="p-4 rounded-2xl bg-[#0E1E38]/60 border border-[#1E355B]/50">
        <div class="text-2xl sm:text-3xl font-black text-amber-300 font-heading flex items-center justify-center gap-1">
          <span><?= $stats['avg_rating'] !== null ? number_format($stats['avg_rating'], 1) : '–' ?></span>
          <i class="fas fa-star text-amber-400 text-lg"></i>
        </div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Indeks Kepuasan Masyarakat</div>
      </div>

    </div>
  </div>
</section>

<!-- Alur Pelayanan 4 Langkah -->
<section id="alur-pelayanan" class="py-16 lg:py-24 max-w-7xl mx-auto px-4">
  <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
    <div class="text-xs font-bold text-amber-400 uppercase tracking-widest">TRANSPARAN & AKUNTABEL</div>
    <h2 class="text-3xl sm:text-4xl font-black text-slate-100 font-heading">Alur Penanganan Laporan Anda</h2>
    <p class="text-sm text-slate-400">Dari saat Anda menekan tombol kirim hingga penyelesaian berkas perkara oleh satuan kerja kepolisian.</p>
  </div>

  <div class="grid md:grid-cols-4 gap-6 relative">
    
    <!-- Step 1 -->
    <div class="p-6 rounded-2xl bg-[#0E1E38] border border-[#1E355B] relative hover:border-amber-500/50 transition-all">
      <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center font-black text-base mb-4">
        1
      </div>
      <h3 class="text-base font-bold text-slate-100 mb-2">Input Laporan & Bukti</h3>
      <p class="text-xs text-slate-400 leading-relaxed">
        Isi kronologi, tandai koordinat lokasi TKP pada peta, serta lampirkan foto/video barang bukti. Anda langsung mendapatkan Nomor Tiket & PIN.
      </p>
    </div>

    <!-- Step 2 -->
    <div class="p-6 rounded-2xl bg-[#0E1E38] border border-[#1E355B] relative hover:border-amber-500/50 transition-all">
      <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center font-black text-base mb-4">
        2
      </div>
      <h3 class="text-base font-bold text-slate-100 mb-2">Verifikasi & Disposisi SPKT</h3>
      <p class="text-xs text-slate-400 leading-relaxed">
        Operator Command Center memverifikasi keabsahan laporan dan mendisposisikan perkara ke Satker Mabes Polri atau Polda wilayah hukum terkait.
      </p>
    </div>

    <!-- Step 3 -->
    <div class="p-6 rounded-2xl bg-[#0E1E38] border border-[#1E355B] relative hover:border-amber-500/50 transition-all">
      <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-black text-base mb-4">
        3
      </div>
      <h3 class="text-base font-bold text-slate-100 mb-2">Penyelidikan & SP2HP-E</h3>
      <p class="text-xs text-slate-400 leading-relaxed">
        Penyidik yang ditunjuk bergerak ke lapangan, memeriksa saksi, dan secara berkala mengunggah Surat Pemberitahuan Perkembangan Hasil Penyelidikan.
      </p>
    </div>

    <!-- Step 4 -->
    <div class="p-6 rounded-2xl bg-[#0E1E38] border border-[#1E355B] relative hover:border-amber-500/50 transition-all">
      <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-black text-base mb-4">
        4
      </div>
      <h3 class="text-base font-bold text-slate-100 mb-2">Penyelesaian & SKM</h3>
      <p class="text-xs text-slate-400 leading-relaxed">
        Kasus dituntaskan dengan Berita Acara (BAP). Pelapor menerima hasil akhir dan memberikan penilaian kepuasan pelayanan (SKM).
      </p>
    </div>

  </div>
</section>

<!-- Call to Action Banner -->
<section class="max-w-7xl mx-auto px-4 pb-16">
  <div class="rounded-3xl p-8 lg:p-12 bg-gradient-to-r from-blue-950 via-[#0A192F] to-slate-900 border border-amber-500/30 text-center relative overflow-hidden shadow-2xl">
    <div class="max-w-2xl mx-auto space-y-4">
      <h3 class="text-2xl sm:text-3xl font-black text-slate-100 font-heading">
        Mengalami Atau Menyaksikan Tindak Pidana?
      </h3>
      <p class="text-xs sm:text-sm text-slate-300">
        Jangan ragu untuk melapor. Kepolisian Daerah siap melindungi, mengayomi, dan melayani setiap laporan masyarakat secara profesional.
      </p>
      <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
        <a href="<?= $baseUrl ?>/report/create" class="px-6 py-3 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black rounded-xl text-xs transition-all shadow-lg">
          <i class="fas fa-pen-to-square mr-1.5"></i> Buat Pengaduan Sekarang
        </a>
        <a href="tel:110" class="px-5 py-3 bg-rose-950/80 hover:bg-rose-900 border border-rose-800 text-rose-200 font-bold rounded-xl text-xs transition-all">
          <i class="fas fa-phone-alt mr-1.5"></i> Hubungi Call Center 110
        </a>
      </div>
    </div>
  </div>
</section>
