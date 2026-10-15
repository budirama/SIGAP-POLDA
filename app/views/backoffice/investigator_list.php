<div class="space-y-6">
  
  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 polri-glass-card p-6 rounded-2xl border border-[#1E355B]">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-yellow-950/80 border border-yellow-700/60 text-yellow-300 text-xs font-bold mb-2">
        <i class="fas fa-user-edit text-amber-400"></i> Penyidik Pelaksana Lapangan
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">
        Kasus Dalam Penanganan Saya
      </h2>
      <p class="text-xs text-slate-400 mt-1">
        Laporan perkara yang ditugaskan kepada Anda berdasarkan Surat Perintah Penugasan (Sprintgas). Terbitkan SP2HP secara berkala kepada pelapor.
      </p>
    </div>

    <div>
      <span class="text-xs font-bold text-amber-400 bg-amber-500/10 px-3 py-1.5 rounded-xl border border-amber-500/30">
        <?= count($reports) ?> Kasus Terdata
      </span>
    </div>
  </div>

  <!-- Cards Grid -->
  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($reports)): ?>
      <div class="col-span-full text-center py-12 polri-glass-card rounded-2xl text-slate-500 text-xs">
        <i class="fas fa-folder-open text-3xl mb-2 text-slate-600 block"></i>
        Saat ini tidak ada laporan perkara yang sedang Anda tangani.
      </div>
    <?php else: ?>
      <?php foreach ($reports as $r): ?>
        <div class="polri-glass-card rounded-2xl p-5 border <?= $r['is_kapolda_attention'] ? 'border-red-600 animate-kapolda-pulse' : 'border-[#1E355B]' ?> flex flex-col justify-between">
          
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="font-mono font-bold text-amber-400 text-xs"><?= htmlspecialchars($r['ticket_number']) ?></span>
              <?= \App\Config\Constants::getStatusBadge($r['status']) ?>
            </div>

            <?php if ($r['is_kapolda_attention']): ?>
              <div class="mb-2 px-2 py-0.5 rounded bg-red-950 text-red-300 border border-red-800 text-[10px] font-black flex items-center gap-1">
                <i class="fas fa-star text-amber-400"></i> ATENSI KHUSUS KAPOLDA
              </div>
            <?php endif; ?>

            <h3 class="text-sm font-bold text-slate-100 mb-1 line-clamp-2"><?= htmlspecialchars($r['title']) ?></h3>
            <p class="text-[11px] text-slate-400 line-clamp-2 mb-3 leading-relaxed"><?= htmlspecialchars($r['description']) ?></p>

            <div class="text-[11px] text-slate-400 space-y-1 border-t border-slate-800/80 pt-2 mb-4">
              <div><b class="text-slate-300">Pelapor:</b> <?= htmlspecialchars($r['is_anonymous'] ? 'Whistleblower (Disamarkan)' : $r['reporter_name_raw']) ?></div>
              <div><b class="text-slate-300">TKP:</b> <?= htmlspecialchars($r['incident_location']) ?></div>
            </div>
          </div>

          <div>
            <a href="<?= $baseUrl ?>/investigator/detail/<?= $r['id'] ?>"
               class="w-full py-2.5 px-4 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black rounded-xl text-xs transition-all shadow flex items-center justify-center gap-2">
              <i class="fas fa-file-pen"></i>
              <span>Buka Lembar Kerja & Update SP2HP</span>
            </a>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
