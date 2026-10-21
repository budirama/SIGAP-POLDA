<?php
/**
 * View: Modul 8 Dashboard Akuntabilitas 100 Hari Polri Presisi
 * Menjawab 6 Pertanyaan Wajib Halaman 41 Paparan Prioritas Kapolri
 */
?>
<div class="space-y-8">
  <!-- Banner Utama Dashboard Akuntabilitas -->
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-8 shadow-2xl text-white relative overflow-hidden">
    <div class="ribbon-merah-putih"></div>
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold tracking-wider uppercase mb-3">
          <i class="fas fa-landmark"></i> PAPARAN PRIORITAS KAPOLRI HALAMAN 41 • HASIL KERJA NYATA
        </div>
        <h1 class="text-3xl md:text-4xl font-heading font-black tracking-tight text-slate-100 mb-2">
          Dashboard Akuntabilitas 100 Hari Presisi
        </h1>
        <p class="text-slate-300 text-sm md:text-base leading-relaxed font-light">
          Transparansi menyeluruh kinerja kepolisian berbasis integrasi <strong class="text-amber-400 font-semibold">"Satu Nomor, Satu Jejak"</strong> — Mengukur hasil nyata (*outcome*) pelayanan, bukan sekadar laporan seremonial kegiatan.
        </p>
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-3">
        <button onclick="window.print()" class="w-full sm:w-auto px-4 py-3 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-200 text-xs font-bold border border-[#3B2F1C] transition-all flex items-center justify-center gap-2">
          <i class="fas fa-file-pdf text-amber-500"></i> Cetak Laporan Eksekutif
        </button>
        <a href="<?= $baseUrl ?>/oneservice" class="w-full sm:w-auto px-4 py-3 rounded-xl btn-gold text-slate-950 text-xs font-black transition-all flex items-center justify-center gap-2 shadow-lg">
          <i class="fas fa-th-large"></i> Hub Layanan
        </a>
      </div>
    </div>
    <div class="absolute -right-8 -bottom-8 opacity-5 pointer-events-none">
      <i class="fas fa-chart-pie text-[240px] text-amber-400"></i>
    </div>
  </div>

  <!-- Indikator 6 Pertanyaan Wajib Halaman 41 -->
  <div class="space-y-6">
    <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
      <h2 class="text-xl font-heading font-black text-slate-100 flex items-center gap-2.5">
        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-sm font-black border border-amber-500/30">
          41
        </span>
        Jawaban Strategis 6 Pertanyaan Akuntabilitas Publik
      </h2>
      <span class="text-xs text-slate-400 font-mono">Standar Mabes Polri Presisi</span>
    </div>

    <!-- Grid 6 Pertanyaan -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- PERTANYAAN 1: BERAPA WARGA YANG DILAYANI? -->
      <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex items-start justify-between gap-3 border-b border-[#3B2F1C] pb-3">
          <div>
            <div class="text-[11px] font-black text-amber-400 uppercase tracking-widest">PERTANYAAN 1</div>
            <h3 class="text-lg font-heading font-black text-slate-100">Berapa warga yang dilayani?</h3>
          </div>
          <span class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fas fa-users text-lg"></i></span>
        </div>

        <div class="flex items-baseline gap-3">
          <span class="text-4xl font-heading font-black text-slate-100"><?= number_format($report['warga_dilayani']['total_interaksi']) ?></span>
          <span class="text-xs text-slate-400 font-medium">total interaksi warga tercatat</span>
        </div>

        <div class="space-y-2 pt-2">
          <span class="text-xs font-bold text-amber-500/80 uppercase tracking-wider block">Sebaran Kanal Layanan Terpadu:</span>
          <div class="grid grid-cols-3 gap-2 text-xs">
            <div class="bg-[#1B1610] p-2.5 rounded-xl border border-[#3B2F1C]">
              <span class="text-slate-400 block text-[10px]">Panggilan 110:</span>
              <strong class="text-slate-100 font-mono"><?= number_format($report['warga_dilayani']['channel_breakdown']['110_call']) ?></strong>
            </div>
            <div class="bg-[#1B1610] p-2.5 rounded-xl border border-[#3B2F1C]">
              <span class="text-slate-400 block text-[10px]">Loket SPKT:</span>
              <strong class="text-slate-100 font-mono"><?= number_format($report['warga_dilayani']['channel_breakdown']['spkt_loket']) ?></strong>
            </div>
            <div class="bg-[#1B1610] p-2.5 rounded-xl border border-[#3B2F1C]">
              <span class="text-slate-400 block text-[10px]">Aduan Siber:</span>
              <strong class="text-slate-100 font-mono"><?= number_format($report['warga_dilayani']['channel_breakdown']['cyber_crime']) ?></strong>
            </div>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-[#1B1610] border border-purple-500/30 flex items-center justify-between text-xs">
          <span class="text-purple-300 font-semibold"><i class="fas fa-hand-holding-heart mr-1.5"></i> Kelompok Rentan &amp; Jalur Khusus PPA:</span>
          <span class="font-mono font-bold text-purple-200"><?= number_format($report['warga_dilayani']['kelompok_rentan_ppa']) ?> Warga</span>
        </div>
      </div>

      <!-- PERTANYAAN 2: BERAPA CEPAT RESPONS POLRI? -->
      <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex items-start justify-between gap-3 border-b border-[#3B2F1C] pb-3">
          <div>
            <div class="text-[11px] font-black text-emerald-400 uppercase tracking-widest">PERTANYAAN 2</div>
            <h3 class="text-lg font-heading font-black text-slate-100">Berapa cepat respons Polri?</h3>
          </div>
          <span class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><i class="fas fa-stopwatch text-lg"></i></span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-slate-400 block">Rata-Rata Tiba di TKP (110):</span>
            <div class="text-3xl font-heading font-black text-emerald-400"><?= $report['kecepatan_respons']['avg_response_minutes'] ?> <span class="text-sm font-normal text-slate-400">menit</span></div>
            <span class="text-[10px] text-slate-400 font-mono"><?= $report['kecepatan_respons']['avg_response_seconds'] ?> detik presisi</span>
          </div>

          <div>
            <span class="text-xs text-slate-400 block">Kepatuhan Standar SLA:</span>
            <div class="text-3xl font-heading font-black text-amber-300"><?= $report['kecepatan_respons']['sla_compliance_rate'] ?>%</div>
            <span class="text-[10px] text-emerald-400 font-semibold"><i class="fas fa-check-circle mr-1"></i> Target &lt; 15 menit</span>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-[#1B1610] border border-[#3B2F1C] text-xs text-slate-300 space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-slate-400">Total Panggilan 110 Terdokumentasi:</span>
            <strong class="text-slate-100 font-mono"><?= number_format($report['kecepatan_respons']['panggilan_110_total']) ?></strong>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-400">Time to Dispatch Rata-Rata Operator:</span>
            <strong class="text-emerald-400 font-mono">&lt; 90 detik</strong>
          </div>
        </div>
      </div>

      <!-- PERTANYAAN 3: BERAPA MASALAH YANG SELESAI? -->
      <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex items-start justify-between gap-3 border-b border-[#3B2F1C] pb-3">
          <div>
            <div class="text-[11px] font-black text-amber-400 uppercase tracking-widest">PERTANYAAN 3</div>
            <h3 class="text-lg font-heading font-black text-slate-100">Berapa masalah yang selesai?</h3>
          </div>
          <span class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fas fa-certificate text-lg"></i></span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-slate-400 block">Perkara Tuntas (Clearance):</span>
            <div class="text-3xl font-heading font-black text-amber-400"><?= number_format($report['masalah_selesai']['total_tuntas']) ?></div>
            <span class="text-[11px] text-slate-400">Clearance Rate: <strong class="text-slate-100"><?= $report['masalah_selesai']['clearance_rate'] ?>%</strong></span>
          </div>

          <div>
            <span class="text-xs text-slate-400 block">Kepatuhan SP2HP Tepat Waktu:</span>
            <div class="text-3xl font-heading font-black text-emerald-400"><?= $report['masalah_selesai']['sp2hp_on_time_rate'] ?>%</div>
            <span class="text-[11px] text-slate-400">Total SP2HP Terbit: <strong class="text-slate-100"><?= $report['masalah_selesai']['total_sp2hp_terbit'] ?> berkas</strong></span>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-[#1B1610] border border-emerald-500/30 flex items-center justify-between text-xs">
          <span class="text-emerald-300 font-semibold"><i class="fas fa-handshake mr-1.5"></i> Restorative Justice Damai Bhabin:</span>
          <span class="font-mono font-bold text-emerald-200"><?= number_format($report['masalah_selesai']['restorative_justice_bhabin']) ?> Kasus Selesai</span>
        </div>
      </div>

      <!-- PERTANYAAN 4: BERAPA KELUHAN YANG DIPERBAIKI? -->
      <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex items-start justify-between gap-3 border-b border-[#3B2F1C] pb-3">
          <div>
            <div class="text-[11px] font-black text-purple-400 uppercase tracking-widest">PERTANYAAN 4</div>
            <h3 class="text-lg font-heading font-black text-slate-100">Berapa keluhan yang diperbaiki?</h3>
          </div>
          <span class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20"><i class="fas fa-shield-virus text-lg"></i></span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-xs text-slate-400 block">Total Dumas Masuk:</span>
            <div class="text-3xl font-heading font-black text-slate-100"><?= number_format($report['keluhan_diperbaiki']['total_aduan_dumas']) ?></div>
            <span class="text-[11px] text-slate-400">Pengawasan Divpropam &amp; Itwasum</span>
          </div>

          <div>
            <span class="text-xs text-slate-400 block">Keluhan Ditindak Lanjut:</span>
            <div class="text-3xl font-heading font-black text-purple-400"><?= number_format($report['keluhan_diperbaiki']['selesai_ditindak']) ?></div>
            <span class="text-[11px] text-emerald-400 font-semibold">Tuntas Sidang Kode Etik</span>
          </div>
        </div>

        <div class="p-3 rounded-xl bg-[#1B1610] border border-[#3B2F1C] flex items-center justify-between text-xs">
          <span class="text-slate-300 font-semibold"><i class="fas fa-triangle-exclamation text-amber-400 mr-1.5"></i> Anomali Loket Layanan Terdeteksi:</span>
          <span class="font-mono font-bold text-amber-400"><?= (int)$report['keluhan_diperbaiki']['anomali_loket_terdeteksi'] ?> Titik Loket</span>
        </div>
      </div>

      <!-- PERTANYAAN 5: APA YANG BELUM BERHASIL? (TRANSPARANSI DEFISIT) -->
      <div class="polri-glass-card bg-[#14100C] border border-red-500/40 rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex items-start justify-between gap-3 border-b border-[#3B2F1C] pb-3">
          <div>
            <div class="text-[11px] font-black text-red-400 uppercase tracking-widest">PERTANYAAN 5 (TRANSPARANSI DEFISIT)</div>
            <h3 class="text-lg font-heading font-black text-slate-100">Apa yang belum berhasil?</h3>
          </div>
          <span class="p-2.5 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20"><i class="fas fa-triangle-exclamation text-lg"></i></span>
        </div>

        <p class="text-xs text-slate-300 leading-relaxed font-light">
          Polri mengakui secara terbuka perkara-perkara yang belum memenuhi standar waktu penyidikan demi akuntabilitas nyata kepada masyarakat:
        </p>

        <div class="flex items-center justify-between p-3 rounded-xl bg-red-950/40 border border-red-500/30">
          <span class="text-xs font-bold text-red-300">Total Kasus Mangkrak / Melewati Batas SLA:</span>
          <span class="text-xl font-black font-mono text-red-400"><?= number_format($report['belum_berhasil']['total_kasus_mangkrak']) ?> Perkara</span>
        </div>

        <?php if (!empty($report['belum_berhasil']['satker_watchlist_sla'])): ?>
          <div class="space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Watchlist Satuan Kerja Beban Tertinggi:</span>
            <div class="space-y-1.5">
              <?php foreach ($report['belum_berhasil']['satker_watchlist_sla'] as $w): ?>
                <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-[#1B1610] border border-[#3B2F1C]">
                  <span class="text-slate-100"><?= htmlspecialchars($w['satker_name']) ?></span>
                  <span class="font-mono font-bold text-red-400"><?= (int)$w['stalled_count'] ?> kasus macet</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- PERTANYAAN 6: APA YANG AKAN DIPERBAIKI? (ACTIONABLE COMMITMENT) -->
      <div class="polri-glass-card bg-[#14100C] border border-amber-500/40 rounded-2xl p-6 shadow-xl space-y-4">
        <div class="flex items-start justify-between gap-3 border-b border-[#3B2F1C] pb-3">
          <div>
            <div class="text-[11px] font-black text-amber-400 uppercase tracking-widest">PERTANYAAN 6 (KOMITMEN AKSI NYATA)</div>
            <h3 class="text-lg font-heading font-black text-slate-100">Apa yang akan diperbaiki?</h3>
          </div>
          <span class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fas fa-list-check text-lg"></i></span>
        </div>

        <p class="text-xs text-slate-300 leading-relaxed font-light">
          Rencana aksi konkret pimpinan Polri menindaklanjuti data defisit di lapangan:
        </p>

        <div class="space-y-3">
          <?php foreach ($report['rencana_perbaikan'] as $idx => $plan): ?>
            <div class="p-3.5 rounded-xl bg-[#1B1610] border border-[#3B2F1C] space-y-1">
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-amber-400 font-mono">TARGET #<?= $idx + 1 ?>: <?= htmlspecialchars($plan['satker_target']) ?></span>
              </div>
              <p class="text-xs text-slate-300"><strong class="text-red-400">Kendala:</strong> <?= htmlspecialchars($plan['problem_identified']) ?></p>
              <p class="text-xs text-emerald-300 font-medium"><strong class="text-slate-100">Solusi:</strong> <?= htmlspecialchars($plan['action_commitment']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</div>
