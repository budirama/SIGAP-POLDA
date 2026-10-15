<?php
use App\Config\Constants;

$levelIcon = [4 => 'fa-landmark', 3 => 'fa-building-shield', 2 => 'fa-building', 1 => 'fa-house-flag'];
?>
<div class="space-y-6">

  <!-- Identitas posko -->
  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C] flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-4">
      <div class="h-14 w-14 rounded-2xl bg-amber-500/15 border border-amber-500/30 text-amber-300 flex items-center justify-center text-2xl flex-shrink-0">
        <i class="fas <?= $levelIcon[(int)$unit['level']] ?? 'fa-sitemap' ?>"></i>
      </div>
      <div>
        <span class="chip-digital inline-flex px-2 py-0.5 rounded text-[10px]">POSKO TINGKAT <?= strtoupper(Constants::levelLabel((int)$unit['level'])) ?></span>
        <h2 class="mt-1 text-lg font-black text-slate-100 font-heading"><?= htmlspecialchars($unit['name']) ?></h2>
        <p class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($unit['code']) ?><?= !empty($unit['address']) ? ' · ' . htmlspecialchars($unit['address']) : '' ?></p>
      </div>
    </div>
    <div class="text-xs text-slate-300 md:text-right">
      <p class="text-slate-500">Anda masuk sebagai</p>
      <p class="font-bold text-amber-300"><?= htmlspecialchars($roleLabel) ?></p>
      <?php if (!$isHome): ?>
        <a href="<?= $baseUrl ?>/posko" class="inline-flex items-center gap-1 mt-1 text-amber-400 hover:text-amber-300"><i class="fas fa-arrow-left text-[10px]"></i> Kembali ke posko saya</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Rantai komando -->
  <nav aria-label="Rantai komando" class="flex flex-wrap items-center gap-2 text-xs">
    <?php foreach ($ancestors as $a): ?>
      <?php if ($a['linkable']): ?>
        <a href="<?= $baseUrl ?>/posko/unit/<?= (int)$a['id'] ?>" class="px-2.5 py-1 rounded-lg border border-slate-700 text-slate-300 hover:bg-[#1B1610]"><?= htmlspecialchars($a['name']) ?></a>
      <?php else: ?>
        <span class="px-2.5 py-1 rounded-lg border border-slate-800 text-slate-500"><?= htmlspecialchars($a['name']) ?></span>
      <?php endif; ?>
      <i class="fas fa-chevron-right text-[9px] text-slate-600" aria-hidden="true"></i>
    <?php endforeach; ?>
    <span class="px-2.5 py-1 rounded-lg border border-amber-500/50 bg-amber-500/10 text-amber-300 font-bold" aria-current="page"><?= htmlspecialchars($unit['name']) ?></span>
  </nav>

  <!-- Ringkasan wilayah -->
  <div class="grid grid-cols-3 gap-3">
    <?php foreach ([Constants::LEVEL_POLDA => 'Polda', Constants::LEVEL_POLRES => 'Polres', Constants::LEVEL_POLSEK => 'Polsek'] as $lvl => $label): ?>
      <?php if ($lvl < (int)$unit['level']): ?>
        <div class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-4">
          <p class="text-[11px] text-slate-400">Posko <?= $label ?> di wilayah ini</p>
          <p class="text-2xl font-black text-amber-400 font-mono"><?= (int)$levelCounts[$lvl] ?></p>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ((int)$unit['level'] === Constants::LEVEL_POLSEK): ?>
      <div class="col-span-3 polri-glass-card rounded-2xl border border-[#3B2F1C] p-4 text-xs text-slate-400">
        Posko Polsek adalah tingkat pelaporan paling awal; laporan dikirim ke Polres induk untuk diverifikasi.
      </div>
    <?php endif; ?>
  </div>

  <!-- Unit di bawahnya -->
  <?php if ($children): ?>
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
      <header class="px-5 py-3 border-b border-[#3B2F1C] flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-100">Unit di Bawah <?= htmlspecialchars($unit['name']) ?></h3>
        <span class="text-[11px] text-slate-400"><?= count($children) ?> unit</span>
      </header>
      <ul class="divide-y divide-[#3B2F1C]/60">
        <?php foreach ($children as $c): ?>
          <li>
            <a href="<?= $baseUrl ?>/posko/unit/<?= (int)$c['id'] ?>" class="flex items-center gap-3 px-5 py-3 hover:bg-[#1B1610] transition-colors">
              <i class="fas <?= $levelIcon[(int)$c['level']] ?? 'fa-sitemap' ?> text-amber-400 w-4"></i>
              <span class="flex-1 min-w-0">
                <span class="block text-sm text-slate-100 truncate"><?= htmlspecialchars($c['name']) ?></span>
                <span class="block text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($c['code']) ?></span>
              </span>
              <?php if (!(int)$c['is_active']): ?>
                <span class="text-[10px] px-2 py-0.5 rounded-full border border-slate-600 text-slate-400">Nonaktif</span>
              <?php endif; ?>
              <span class="hidden sm:inline text-[11px] text-slate-400"><?= (int)$c['child_count'] ?> sub-unit · <?= (int)$c['user_count'] ?> akun</span>
              <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <div class="polri-glass-card rounded-2xl border border-dashed border-[#3B2F1C] p-5 text-xs text-slate-400">
    <i class="fas fa-person-digging text-amber-400 mr-1"></i>
    Input laporan, verifikasi berjenjang, rekapitulasi, dan monitoring kepatuhan dikembangkan pada tahap berikutnya
    (lihat <span class="font-mono">docs/POSKO_00_ROADMAP.md</span>).
  </div>
</div>
