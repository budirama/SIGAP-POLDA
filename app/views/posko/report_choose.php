<?php
use App\Models\PoskoReportType;

$icons = ['ROUTINE' => 'fa-calendar-day', 'PERIODIC' => 'fa-calendar-week', 'SPECIAL_EVENT' => 'fa-triangle-exclamation', 'INCIDENTAL' => 'fa-bolt'];
?>
<div class="space-y-4">
  <div>
    <h2 class="text-lg font-black text-slate-100 font-heading">Pilih Jenis Laporan</h2>
    <p class="text-xs text-slate-400">Jenis yang tampil adalah jenis yang berlaku untuk tingkat posko Anda.</p>
  </div>

  <?php if (!$types): ?>
    <p class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-8 text-center text-xs text-slate-500">
      Belum ada jenis laporan aktif untuk tingkat posko Anda. Hubungi Admin Posko Pusat.
    </p>
  <?php endif; ?>

  <div class="grid sm:grid-cols-2 gap-4">
    <?php foreach ($types as $t): ?>
      <a href="<?= $baseUrl ?>/posko-laporan/create?type=<?= (int)$t['id'] ?>"
         class="polri-glass-card rounded-2xl border border-[#3B2F1C] hover:border-amber-500/60 p-5 flex gap-4 transition-colors">
        <span class="h-12 w-12 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 flex items-center justify-center text-xl flex-shrink-0">
          <i class="fas <?= $icons[$t['kind']] ?? 'fa-file-lines' ?>"></i>
        </span>
        <span>
          <span class="block text-sm font-bold text-slate-100"><?= htmlspecialchars($t['name']) ?></span>
          <span class="block text-[11px] text-amber-300"><?= PoskoReportType::KINDS[$t['kind']] ?> · <?= PoskoReportType::PERIOD_UNITS[$t['period_unit']] ?></span>
          <?php if (!empty($t['description'])): ?>
            <span class="block mt-1 text-[11px] text-slate-400"><?= htmlspecialchars($t['description']) ?></span>
          <?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
