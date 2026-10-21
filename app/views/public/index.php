<?php $appVersion = \App\Config\Config::APP_VERSION; ?>
<!-- Hero Section -->
<section class="hud-grid relative isolate overflow-hidden py-16 lg:py-24 border-b border-[#3B2F1C]/60">
  <!-- Latar: glow logo, jaringan data digital & lantai grid 3D -->
  <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-900/25 via-[#0C0A08] to-[#0C0A08] -z-10"></div>
  <div class="absolute top-1/4 right-5 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>
  <div class="absolute -bottom-20 -left-10 w-80 h-80 bg-red-700/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>
  <canvas class="fx-network" aria-hidden="true"></canvas>
  <div class="grid-floor" aria-hidden="true"></div>

  <div class="max-w-7xl mx-auto px-4">
    <div class="grid lg:grid-cols-12 gap-12 items-center">

      <!-- Left Content -->
      <div class="lg:col-span-7 space-y-6 text-center lg:text-left">

        <a href="#yang-baru" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#1B1610] border border-amber-500/30 text-amber-400 text-xs font-bold tracking-wide shadow-md hover:border-amber-400/60 transition-colors">
          <span class="px-1.5 py-0.5 rounded bg-amber-500 text-slate-950 text-[10px] font-black">BARU</span>
          <span>SIGAP v<?= htmlspecialchars($appVersion) ?> • Management Posko Berjenjang</span>
          <i class="fas fa-arrow-right text-[10px]"></i>
        </a>
        <div class="flex flex-wrap justify-center lg:justify-start gap-2 text-[10px]">
          <span class="chip-digital px-2 py-0.5 rounded">SP2HP-E // ONLINE</span>
          <span class="chip-digital px-2 py-0.5 rounded">LIVE CHAT SPKT</span>
          <span class="chip-digital px-2 py-0.5 rounded">POSKO 4 TINGKAT</span>
          <span class="chip-digital px-2 py-0.5 rounded" data-live-clock>--:--:-- WIB</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-100 tracking-tight leading-[1.15] font-heading">
          Satu Platform untuk <span class="text-gold-gradient">Pelayanan Warga</span> &amp; Pelaporan Posko.
        </h1>

        <p class="text-slate-300 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto lg:mx-0 font-normal">
          Laporkan tindak pidana dan pantau progres SP2HP secara terbuka, hubungi petugas SPKT lewat Live Chat,
          sementara satuan Polri dari Polsek hingga Mabes melaporkan kegiatan posko secara berjenjang dan terukur.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
          <a href="<?= $baseUrl ?>/report/create"
             class="btn-gold w-full sm:w-auto px-7 py-3.5 font-black rounded-xl text-sm transition-all shadow-xl hover:shadow-amber-500/25 flex items-center justify-center gap-2.5">
            <i class="fas fa-bullhorn"></i>
            <span>Buat Laporan Sekarang</span>
          </a>

          <button type="button" data-open-livechat
             class="w-full sm:w-auto px-6 py-3.5 bg-[#1B1610] hover:bg-[#241D14] text-slate-200 border border-[#3B2F1C] hover:border-amber-500/40 font-bold rounded-xl text-sm transition-all flex items-center justify-center gap-2">
            <i class="fas fa-comments text-amber-400"></i>
            <span>Chat Petugas SPKT</span>
          </button>
        </div>

        <!-- Quick Track Input Bar -->
        <div class="pt-4 max-w-lg mx-auto lg:mx-0">
          <form action="<?= $baseUrl ?>/report/track" method="GET" class="flex items-center gap-2 p-1.5 bg-[#14100C]/90 border border-amber-500/30 rounded-2xl shadow-xl">
            <div class="pl-3 text-slate-500"><i class="fas fa-ticket text-amber-500"></i></div>
            <label for="heroTicket" class="sr-only">Nomor tiket laporan</label>
            <input id="heroTicket" type="text" name="ticket" required placeholder="Lacak tiket: LP-POLRI-202610-0001"
                   class="bg-transparent flex-1 min-w-0 text-xs text-slate-100 placeholder-slate-500 outline-none px-2 py-1.5 font-mono uppercase">
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

      <!-- Right Emblem & Two Pillars -->
      <div class="lg:col-span-5 flex justify-center">
        <div class="relative w-full max-w-md">

          <div class="polri-glass-card hud-corners scanline tilt-3d rounded-3xl p-8 relative overflow-hidden shadow-2xl border border-amber-500/30" data-tilt="6">
            <div class="absolute -top-10 -right-10 w-36 h-36 bg-red-600/10 rounded-full blur-2xl"></div>

            <div class="text-center space-y-4">
              <!-- Emblem 3D: logo melayang dengan cincin orbit data -->
              <div class="emblem-3d tilt-pop">
                <div class="emblem-3d__stage">
                  <div class="emblem-3d__glow"></div>
                  <div class="emblem-3d__ring emblem-3d__ring--3"></div>
                  <div class="emblem-3d__ring emblem-3d__ring--1"></div>
                  <div class="emblem-3d__ring emblem-3d__ring--2"></div>
                  <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="emblem-3d__logo">
                </div>
              </div>

              <div>
                <div class="inline-block px-3 py-1 rounded-full bg-red-950/80 border border-red-700/60 text-red-300 text-[10px] font-black tracking-widest uppercase mb-1">
                  KEMITRAAN &amp; TRANSPARANSI
                </div>
                <h3 class="text-xl font-black text-slate-100 font-heading">POLISI BERSAMA MASYARAKAT</h3>
                <p class="text-xs text-amber-400 font-bold tracking-wider">MELINDUNGI • MENGAYOMI • MELAYANI</p>
              </div>

              <div class="grid grid-cols-2 gap-2.5 pt-2 text-left">
                <a href="#layanan-warga" class="p-3 rounded-xl bg-[#0C0A08]/80 border border-slate-800 hover:border-amber-500/40 transition-colors">
                  <div class="w-8 h-8 rounded-lg bg-amber-950 text-amber-400 flex items-center justify-center text-xs mb-2"><i class="fas fa-people-group"></i></div>
                  <div class="text-xs font-bold text-slate-200">Layanan Warga</div>
                  <div class="text-[11px] text-slate-400 leading-snug">Aduan, SP2HP-E, Live Chat &amp; SKM</div>
                </a>
                <a href="#management-posko" class="p-3 rounded-xl bg-[#0C0A08]/80 border border-slate-800 hover:border-amber-500/40 transition-colors">
                  <div class="w-8 h-8 rounded-lg bg-blue-950 text-blue-300 flex items-center justify-center text-xs mb-2"><i class="fas fa-sitemap"></i></div>
                  <div class="text-xs font-bold text-slate-200">Management Posko</div>
                  <div class="text-[11px] text-slate-400 leading-snug">Polsek → Polres → Polda → Mabes</div>
                </a>
              </div>

            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- Stats Bar -->
<section class="border-b border-[#3B2F1C]/60 bg-[#14100C]/40 py-8">
  <div class="max-w-7xl mx-auto px-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-center">

      <div class="p-4 rounded-2xl bg-[#1B1610]/60 border border-[#3B2F1C]/50">
        <div class="text-2xl sm:text-3xl font-black text-slate-100 font-heading"><?= number_format($stats['total_reports']) ?></div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Total Laporan Masuk</div>
      </div>

      <div class="p-4 rounded-2xl bg-[#1B1610]/60 border border-[#3B2F1C]/50">
        <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-heading"><?= $stats['clearance_rate'] ?>%</div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Tingkat Penyelesaian (Clearance)</div>
      </div>

      <div class="p-4 rounded-2xl bg-[#1B1610]/60 border border-[#3B2F1C]/50">
        <div class="text-2xl sm:text-3xl font-black text-amber-400 font-heading"><?= number_format($stats['investigating']) ?></div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Dalam Penyelidikan Aktif</div>
      </div>

      <div class="p-4 rounded-2xl bg-[#1B1610]/60 border border-[#3B2F1C]/50">
        <div class="text-2xl sm:text-3xl font-black text-amber-300 font-heading flex items-center justify-center gap-1">
          <span><?= $stats['avg_rating'] !== null ? number_format($stats['avg_rating'], 1) : '–' ?></span>
          <i class="fas fa-star text-amber-400 text-lg"></i>
        </div>
        <div class="text-xs text-slate-400 mt-1 font-medium">Indeks Kepuasan Masyarakat</div>
      </div>

    </div>
  </div>
</section>

<!-- Layanan Warga -->
<section id="layanan-warga" class="scroll-mt-20 py-16 lg:py-24 max-w-7xl mx-auto px-4">
  <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
    <div class="text-xs font-bold text-amber-400 uppercase tracking-widest">LAYANAN WARGA</div>
    <h2 class="text-3xl sm:text-4xl font-black text-slate-100 font-heading">Melapor, Bertanya, dan Memantau dari Mana Saja</h2>
    <p class="text-sm text-slate-400">Tanpa antre di kantor polisi. Seluruh kanal dapat diakses dari ponsel dan terhubung langsung ke Command Center SPKT.</p>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php
    $services = [
      ['fa-pen-to-square', 'amber', 'Pengaduan Online + Peta TKP', 'Formulir bertahap dengan penanda lokasi kejadian di peta digital serta lampiran foto/video barang bukti.', $baseUrl . '/report/create', 'Buat laporan'],
      ['fa-magnifying-glass', 'blue', 'Lacak Status &amp; SP2HP-E', 'Pantau setiap tahapan dengan Nomor Tiket &amp; PIN 6 digit, lalu cetak SP2HP resmi begitu diterbitkan.', $baseUrl . '/report/track', 'Lacak tiket'],
      ['fa-comments', 'emerald', 'Live Chat SPKT', 'Tanya langsung petugas Command Center tanpa login. Percakapan dapat dikonversi menjadi tiket resmi.', null, 'Mulai chat'],
      ['fa-user-secret', 'rose', 'Perlindungan Whistleblower', 'Identitas pelapor dapat dirahasiakan penuh dari pihak mana pun di luar petugas berwenang.', $baseUrl . '/report/create', 'Lapor rahasia'],
      ['fa-people-arrows', 'indigo', 'Ruang Klarifikasi Penyidik', 'Komunikasi dua arah dengan penyidik yang menangani perkara Anda langsung dari halaman pelacakan.', $baseUrl . '/report/track', 'Buka tiket'],
      ['fa-magnifying-glass-location', 'emerald', 'Kehilangan dan Temuan', 'Laporkan kehilangan atau temuan kendaraan, orang, barang berharga, dokumen, dan hewan. Dicocokkan otomatis &amp; dapat SKTLK.', $baseUrl . '/hilangtemu', 'Buka galeri'],
      ['fa-star', 'amber', 'Survei Kepuasan (SKM)', 'Nilai pelayanan setelah perkara selesai. Masukan Anda menjadi indikator kinerja satuan.', $baseUrl . '/report/track', 'Beri penilaian'],
    ];
    $tone = [
      'amber'   => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
      'blue'    => 'bg-blue-500/15 text-blue-300 border-blue-500/30',
      'emerald' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
      'rose'    => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
      'indigo'  => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
    ];
    foreach ($services as [$icon, $color, $title, $desc, $href, $cta]): ?>
      <div class="reveal tilt-3d hud-corners p-6 rounded-2xl bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 transition-all flex flex-col" data-tilt="6">
        <div class="w-11 h-11 rounded-xl border flex items-center justify-center mb-4 <?= $tone[$color] ?>"><i class="fas <?= $icon ?>"></i></div>
        <h3 class="text-base font-bold text-slate-100 mb-2"><?= $title ?></h3>
        <p class="text-xs text-slate-400 leading-relaxed flex-1"><?= $desc ?></p>
        <?php if ($href): ?>
          <a href="<?= $href ?>" class="mt-4 text-xs font-bold text-amber-400 hover:text-amber-300 inline-flex items-center gap-1.5"><?= $cta ?> <i class="fas fa-arrow-right text-[10px]"></i></a>
        <?php else: ?>
          <button type="button" data-open-livechat class="mt-4 self-start text-xs font-bold text-amber-400 hover:text-amber-300 inline-flex items-center gap-1.5"><?= $cta ?> <i class="fas fa-arrow-right text-[10px]"></i></button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Alur Pelayanan 4 Langkah -->
<section id="alur-pelayanan" class="scroll-mt-20 py-16 lg:py-20 border-y border-[#3B2F1C]/60 bg-[#14100C]/40">
  <div class="max-w-7xl mx-auto px-4">
    <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
      <div class="text-xs font-bold text-amber-400 uppercase tracking-widest">TRANSPARAN &amp; AKUNTABEL</div>
      <h2 class="text-3xl sm:text-4xl font-black text-slate-100 font-heading">Alur Penanganan Laporan Anda</h2>
      <p class="text-sm text-slate-400">Dari saat Anda menekan tombol kirim hingga penyelesaian berkas perkara oleh satuan kerja kepolisian.</p>
    </div>

    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-6">
      <?php
      $steps = [
        ['amber', 'Input Laporan &amp; Bukti', 'Lapor lewat formulir, Live Chat, atau media sosial. Anda langsung mendapatkan Nomor Tiket &amp; PIN.'],
        ['blue', 'Verifikasi &amp; Disposisi SPKT', 'Operator Command Center memverifikasi laporan dan mendisposisikannya ke Satker Mabes Polri atau Polda terkait.'],
        ['indigo', 'Penyelidikan &amp; SP2HP-E', 'Penyidik yang ditunjuk bekerja di lapangan dan secara berkala menerbitkan SP2HP elektronik kepada pelapor.'],
        ['emerald', 'Penyelesaian &amp; SKM', 'Perkara dituntaskan dengan Berita Acara. Pelapor menerima hasil akhir dan memberi penilaian kepuasan.'],
      ];
      foreach ($steps as $i => [$color, $title, $desc]): ?>
        <div class="reveal tilt-3d hud-corners p-6 rounded-2xl bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 transition-all" data-tilt="7">
          <div class="w-10 h-10 rounded-xl border flex items-center justify-center font-black text-base mb-4 <?= $tone[$color] ?>"><?= $i + 1 ?></div>
          <h3 class="text-base font-bold text-slate-100 mb-2"><?= $title ?></h3>
          <p class="text-xs text-slate-400 leading-relaxed"><?= $desc ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Management Posko -->
<section id="management-posko" class="scroll-mt-20 py-16 lg:py-24 max-w-7xl mx-auto px-4">
  <div class="grid lg:grid-cols-12 gap-10 lg:gap-12 items-center">

    <div class="lg:col-span-5 space-y-5">
      <div class="inline-flex items-center gap-2 text-xs font-bold text-amber-400 uppercase tracking-widest">
        <i class="fas fa-tower-broadcast"></i> MANAGEMENT POSKO <span class="px-1.5 py-0.5 rounded bg-amber-500 text-slate-950 text-[10px]">v<?= htmlspecialchars($appVersion) ?></span>
      </div>
      <h2 class="text-3xl sm:text-4xl font-black text-slate-100 font-heading leading-tight">Pelaporan Operasional Berjenjang, Dari Polsek Hingga Mabes</h2>
      <p class="text-sm text-slate-400 leading-relaxed">
        Laporan kegiatan posko mengalir dari bawah ke atas: diverifikasi, disetujui, dan direkapitulasi di setiap tingkat,
        sehingga pimpinan memantau kepatuhan pelaporan seluruh wilayah secara terukur dalam satu dasbor.
      </p>

      <div class="grid grid-cols-3 gap-3">
        <?php foreach (['POLDA' => 'Polda', 'POLRES' => 'Polres', 'POLSEK' => 'Polsek'] as $key => $label): ?>
          <div class="p-3 rounded-xl bg-[#1B1610]/70 border border-[#3B2F1C] text-center">
            <div class="text-xl sm:text-2xl font-black text-slate-100 font-heading"><?= number_format($units[$key]) ?></div>
            <div class="text-[11px] text-slate-400"><?= $label ?> terhubung</div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 pt-1">
        <a href="<?= $baseUrl ?>/auth/login" class="btn-gold px-6 py-3 font-black rounded-xl text-xs inline-flex items-center justify-center gap-2 shadow-lg">
          <i class="fas fa-right-to-bracket"></i> Masuk Portal Posko
        </a>
        <span class="text-[11px] text-slate-500 self-center"><i class="fas fa-lock mr-1"></i> Khusus akun dinas posko terdaftar</span>
      </div>
    </div>

    <div class="lg:col-span-7">
      <!-- Hierarki 4 tingkat -->
      <div class="polri-glass-card hud-corners rounded-3xl p-5 sm:p-6 border border-amber-500/25 shadow-2xl">
        <div class="text-[10px] font-black tracking-widest text-slate-400 uppercase mb-4">Alur laporan posko</div>
        <ol class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <?php
          $levels = [
            ['Polsek', 'Tingkat 1', 'Input laporan &amp; laporan nihil', 'fa-house-flag'],
            ['Polres', 'Tingkat 2', 'Verifikasi &amp; rekap Polsek', 'fa-building-shield'],
            ['Polda', 'Tingkat 3', 'Persetujuan &amp; rekap wilayah', 'fa-landmark-flag'],
            ['Mabes', 'Tingkat 4', 'Dasbor nasional &amp; kepatuhan', 'fa-star'],
          ];
          foreach ($levels as $i => [$name, $tier, $role, $icon]): ?>
            <li class="relative p-4 rounded-2xl bg-[#0C0A08]/80 border border-[#3B2F1C]">
              <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-400 border border-amber-500/30 flex items-center justify-center text-sm"><i class="fas <?= $icon ?>"></i></div>
                <span class="text-[10px] font-mono text-slate-500"><?= $tier ?></span>
              </div>
              <div class="text-sm font-black text-slate-100 font-heading"><?= $name ?></div>
              <div class="text-[11px] text-slate-400 leading-snug mt-0.5"><?= $role ?></div>
              <?php if ($i < 3): ?>
                <i class="fas fa-chevron-right hidden sm:block absolute -right-2.5 top-1/2 -translate-y-1/2 text-amber-500/70 text-xs" aria-hidden="true"></i>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
        <div class="mt-4 flex flex-wrap gap-2 text-[10px]">
          <span class="px-2 py-1 rounded-md bg-emerald-950/70 border border-emerald-800/60 text-emerald-300"><i class="fas fa-check mr-1"></i>Kirim → Verifikasi → Setujui → Teruskan</span>
          <span class="px-2 py-1 rounded-md bg-amber-950/70 border border-amber-800/60 text-amber-300"><i class="fas fa-rotate-left mr-1"></i>Kembalikan untuk revisi (berversi)</span>
          <span class="px-2 py-1 rounded-md bg-red-950/70 border border-red-800/60 text-red-300"><i class="fas fa-bolt mr-1"></i>Eskalasi mendesak tetap berjenjang</span>
        </div>
      </div>

      <!-- Kapabilitas -->
      <div class="grid sm:grid-cols-2 gap-3 mt-4">
        <?php
        $features = [
          ['fa-file-pen', 'Jenis laporan terkonfigurasi', 'Harian, mingguan, kejadian khusus &amp; insidentil dengan formulir dan tenggat sendiri.'],
          ['fa-layer-group', 'Rekap berjenjang &amp; nihil', 'Rekapitulasi otomatis per tingkat; satuan tanpa kejadian cukup menyatakan nihil.'],
          ['fa-gauge-high', 'Monitoring kepatuhan', 'Pantau satuan yang tepat waktu, terlambat, atau belum melapor beserta drill-down.'],
          ['fa-file-export', 'Cetak resmi &amp; ekspor', 'Dokumen cetak resmi serta ekspor Excel (.xlsx) dan PDF untuk pimpinan.'],
          ['fa-bell', 'Notifikasi &amp; email', 'Pemberitahuan dalam aplikasi dan email saat laporan perlu ditindaklanjuti.'],
          ['fa-shield-halved', 'Akses dibatasi wilayah', 'Setiap akun hanya melihat data satuannya dan satuan di bawahnya; semua aksi tercatat di jejak audit.'],
        ];
        foreach ($features as [$icon, $title, $desc]): ?>
          <div class="reveal p-4 rounded-xl bg-[#1B1610] border border-[#3B2F1C] flex gap-3">
            <div class="w-8 h-8 shrink-0 rounded-lg bg-blue-950 text-blue-300 flex items-center justify-center text-xs"><i class="fas <?= $icon ?>"></i></div>
            <div>
              <div class="text-xs font-bold text-slate-200"><?= $title ?></div>
              <div class="text-[11px] text-slate-400 leading-relaxed"><?= $desc ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</section>

<!-- Yang Baru di v1.2 -->
<section id="yang-baru" class="scroll-mt-20 max-w-7xl mx-auto px-4 pb-16">
  <div class="rounded-3xl p-6 sm:p-8 bg-[#14100C] border border-[#3B2F1C]">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-6">
      <div>
        <div class="text-xs font-bold text-amber-400 uppercase tracking-widest">CATATAN RILIS</div>
        <h2 class="text-2xl font-black text-slate-100 font-heading">Yang Baru di SIGAP v<?= htmlspecialchars($appVersion) ?></h2>
      </div>
      <p class="text-xs text-slate-500">Pembaruan Oktober 2026</p>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
      <?php
      $news = [
        ['fa-sitemap', 'Management Posko', 'Modul baru pelaporan posko 4 tingkat dengan role per tingkat dan pengelolaan organisasi.'],
        ['fa-diagram-project', 'Workflow berjenjang', 'Verifikasi, pengembalian, revisi berversi, persetujuan, dan eskalasi mendesak.'],
        ['fa-chart-column', 'Dasbor &amp; kepatuhan', 'Rekap berjenjang, laporan nihil, dasbor per tingkat, dan notifikasi.'],
        ['fa-comments', 'Live Chat &amp; Omnichat', 'Warga dapat chat dengan SPKT; petugas mengelola antrean dan mengonversi chat menjadi tiket.'],
      ];
      foreach ($news as [$icon, $title, $desc]): ?>
        <div class="p-4 rounded-2xl bg-[#1B1610]/70 border border-[#3B2F1C]">
          <i class="fas <?= $icon ?> text-amber-400 mb-2"></i>
          <div class="font-bold text-slate-200 mb-1"><?= $title ?></div>
          <p class="text-slate-400 leading-relaxed"><?= $desc ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Call to Action Banner -->
<section class="max-w-7xl mx-auto px-4">
  <div class="rounded-3xl p-8 lg:p-12 hud-grid scanline bg-gradient-to-r from-[#4A0E0E] via-[#14100C] to-[#4A0E0E] border border-amber-500/30 text-center relative isolate overflow-hidden shadow-2xl">
    <div class="max-w-2xl mx-auto space-y-4">
      <h3 class="text-2xl sm:text-3xl font-black text-slate-100 font-heading">
        Mengalami Atau Menyaksikan Tindak Pidana?
      </h3>
      <p class="text-xs sm:text-sm text-slate-300">
        Jangan ragu untuk melapor. Polri siap melindungi, mengayomi, dan melayani setiap laporan masyarakat secara profesional.
      </p>
      <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
        <a href="<?= $baseUrl ?>/report/create" class="btn-gold px-6 py-3 font-black rounded-xl text-xs transition-all shadow-lg">
          <i class="fas fa-pen-to-square mr-1.5"></i> Buat Pengaduan Sekarang
        </a>
        <button type="button" data-open-livechat class="px-5 py-3 bg-[#1B1610] hover:bg-[#241D14] border border-amber-500/40 text-amber-200 font-bold rounded-xl text-xs transition-all">
          <i class="fas fa-comments mr-1.5"></i> Chat Petugas SPKT
        </button>
        <a href="tel:110" class="px-5 py-3 bg-rose-950/80 hover:bg-rose-900 border border-rose-800 text-rose-200 font-bold rounded-xl text-xs transition-all">
          <i class="fas fa-phone-alt mr-1.5"></i> Hubungi Call Center 110
        </a>
      </div>
    </div>
  </div>
</section>

<script>
  // Tombol "Chat Petugas SPKT" membuka widget Live Chat yang sudah ada di layout
  document.querySelectorAll('[data-open-livechat]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = document.getElementById('lcPanel');
      var toggle = document.getElementById('lcToggle');
      if (!toggle) { window.location.href = '<?= $baseUrl ?>/report/create'; return; }
      if (!panel || panel.hidden) toggle.click();
    });
  });
</script>
