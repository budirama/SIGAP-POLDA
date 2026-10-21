<?php
/**
 * View: Hub Terpadu "Satu Nomor, Satu Jejak" (One Police Service v1.3)
 */
?>
<div class="space-y-8">
<div class="space-y-8">
  <!-- Banner Komando & Filosofi -->
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#14100C] via-[#1B1610] to-[#241D14] border border-[#3B2F1C] p-8 shadow-2xl text-slate-100">
    <div class="ribbon-merah-putih absolute top-0 left-0 right-0"></div>
    <div class="relative z-10 max-w-4xl pt-2">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-bold tracking-wider uppercase mb-4">
        <i class="fas fa-fingerprint"></i> PROGRAM PRIORITAS 3.4 & SAS COMMANDER'S WISH
      </div>
      <h1 class="text-3xl md:text-4xl font-black tracking-tight text-slate-100 font-heading mb-3">
        Satu Nomor, Satu Jejak
      </h1>
      <p class="text-slate-300 text-base md:text-lg leading-relaxed mb-6 font-light">
        <strong class="text-amber-400 font-semibold">"Struktur internal Polri bukan beban masyarakat"</strong> — Setiap interaksi warga (panggilan 110, loket SPKT, aduan siber, hingga Dumas) mendapatkan satu nomor pelacakan global yang hidup dari awal hingga putusan inkracht.
      </p>
      
      <div class="flex flex-wrap gap-4">
        <a href="<?= $baseUrl ?>/oneservice/track" class="btn-gold inline-flex items-center gap-2 px-6 py-3 rounded-xl text-xs font-black shadow-lg shadow-amber-500/20 transition-all">
          <i class="fas fa-search"></i> Pelacak Status Kasus
        </a>
        <a href="<?= $baseUrl ?>/oneservice/dashboard" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-amber-300 border border-amber-500/30 text-xs font-bold transition-all">
          <i class="fas fa-chart-line"></i> Dashboard Akuntabilitas 100 Hari
        </a>
      </div>
    </div>
    <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
      <i class="fas fa-shield-halved text-[260px] text-amber-400"></i>
    </div>
  </div>

  <!-- Ringkasan Kinerja Cepat 6 Indikator -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-heading">Total Interaksi Warga</span>
        <span class="p-2 rounded-lg bg-blue-500/10 text-blue-400"><i class="fas fa-users text-sm"></i></span>
      </div>
      <div class="text-2xl font-black text-slate-100 font-mono"><?= number_format($report['warga_dilayani']['total_interaksi']) ?></div>
      <div class="text-xs text-slate-400 mt-1">
        Kanal Terbanyak: <span class="text-amber-400 font-semibold">SPKT & 110</span>
      </div>
    </div>

    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-heading">Waktu Respons 110</span>
        <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400"><i class="fas fa-stopwatch text-sm"></i></span>
      </div>
      <div class="text-2xl font-black text-emerald-400 font-mono"><?= $report['kecepatan_respons']['avg_response_minutes'] ?> Menit</div>
      <div class="text-xs text-slate-400 mt-1">
        Kepatuhan SLA (<15m): <span class="text-emerald-400 font-bold"><?= $report['kecepatan_respons']['sla_compliance_rate'] ?>%</span>
      </div>
    </div>

    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-heading">SP2HP Tepat Waktu</span>
        <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400"><i class="fas fa-file-shield text-sm"></i></span>
      </div>
      <div class="text-2xl font-black text-amber-300 font-mono"><?= $report['masalah_selesai']['sp2hp_on_time_rate'] ?>%</div>
      <div class="text-xs text-slate-400 mt-1">
        Total SP2HP Terbit: <span class="text-slate-200 font-semibold"><?= $report['masalah_selesai']['total_sp2hp_terbit'] ?> berkas</span>
      </div>
    </div>

    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-heading">Kasus Stalled / Macet</span>
        <span class="p-2 rounded-lg bg-red-500/10 text-red-400"><i class="fas fa-triangle-exclamation text-sm"></i></span>
      </div>
      <div class="text-2xl font-black text-red-400 font-mono"><?= $report['belum_berhasil']['total_kasus_mangkrak'] ?> Kasus</div>
      <div class="text-xs text-slate-400 mt-1">
        Atensi Gelar Perkara Khusus Pimpinan
      </div>
    </div>
  </div>

  <!-- Menu 8 Modul Terpadu -->
  <div>
    <h2 class="text-xl font-black text-slate-100 font-heading mb-4 flex items-center gap-2">
      <i class="fas fa-th-large text-amber-400"></i> 8 Modul Quick Win & Program Prioritas
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      
      <!-- Modul 1 -->
      <a href="<?= $baseUrl ?>/oneservice/track" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-ticket-simple"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 1</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">Pelacak & SP2HP Otomatis</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Satu nomor lacak per laporan, trigger otomatis pengiriman SP2HP Elektronik saat status perkara berganti.
          </p>
        </div>
      </a>

      <!-- Modul 2 -->
      <a href="<?= $baseUrl ?>/oneservice/dispatch" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-headset"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 2</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">Triase & CAD 110</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Klasifikasi urgensi panggilan P1/P2/P3, penugasan unit patroli terdekat berbasis radius koordinat GPS.
          </p>
        </div>
      </a>

      <!-- Modul 3 -->
      <a href="<?= $baseUrl ?>/oneservice/spkt" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-hospital-user"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 3</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">SPKT Ramah Korban</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Formulir terstandar SOP No-Rejection, jalur khusus PPA & difabel, serta proxy entry untuk warga rentan.
          </p>
        </div>
      </a>

      <!-- Modul 4 -->
      <a href="<?= $baseUrl ?>/oneservice/cases" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-folder-tree"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 4</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">Manajemen Perkara</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Integrasi e-MP, koordinasi pra-penuntutan dengan JPU, rantai bukti digital (SHA-256), dan SLA watchdog.
          </p>
        </div>
      </a>

      <!-- Modul 5 -->
      <a href="<?= $baseUrl ?>/oneservice/dumas" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-user-shield"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 5</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">Dumas & Integritas</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Satu pintu pengaduan WBS/Propam, perlindungan whistleblower, dan radar anomali lonjakan pungli loket.
          </p>
        </div>
      </a>

      <!-- Modul 6 -->
      <a href="<?= $baseUrl ?>/oneservice/cyber" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-ban"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 6</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">Siber & Blokir Rekening</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Penanganan aduan penipuan daring, sinyal freeze darurat ke Bank/OJK, dan clustering sindikat rekening bersama.
          </p>
        </div>
      </a>

      <!-- Modul 7 -->
      <a href="<?= $baseUrl ?>/oneservice/bhabin" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-handshake-angle"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 7</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">Solusi Bhabinkamtibmas</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Pencatatan masalah warga yang tuntas (Restorative Justice) bukan seremonial, terintegrasi ke peta kerawanan.
          </p>
        </div>
      </a>

      <!-- Modul 8 -->
      <a href="<?= $baseUrl ?>/oneservice/dashboard" class="polri-glass-card group bg-[#14100C] hover:bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 rounded-2xl p-6 transition-all shadow-md flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-amber-400/10 text-amber-300 border border-amber-500/20 flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
            <i class="fas fa-gauge-high"></i>
          </div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-1">MODUL 8</div>
          <h3 class="text-lg font-black text-slate-100 font-heading mb-2">Dashboard 100 Hari</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Menjawab 6 pertanyaan akuntabilitas halaman 41 paparan Kapolri secara transparan dan terukur bagi publik.
          </p>
        </div>
      </a>

    </div>
  </div>
</div>
