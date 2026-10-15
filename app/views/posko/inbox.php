<?php
use App\Core\PoskoSchedule;

require __DIR__ . '/_status.php';
$need = [
    'SUBMITTED' => 'Perlu diverifikasi',
    'IN_REVIEW' => 'Sedang Anda verifikasi',
    'APPROVED'  => 'Disetujui — siap diteruskan',
    'FORWARDED' => 'Diteruskan dari bawah — terima / teruskan',
];
?>
<div class="space-y-4">
  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C]">
    <span class="chip-digital inline-flex px-2 py-0.5 rounded text-[10px]">POSKO // ANTREAN</span>
    <h2 class="mt-1 text-lg font-black text-slate-100 font-heading">Antrean Verifikasi — <?= htmlspecialchars($unitName) ?></h2>
    <p class="text-xs text-slate-400">Laporan yang sedang dipegang unit Anda. Laporan mendesak (prioritas tinggi/kritis) selalu di atas; alur tetap berjenjang ke unit induk.</p>
  </div>

  <?php if (!$items): ?>
    <p class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-8 text-center text-xs text-slate-500">
      <i class="fas fa-circle-check text-emerald-400 text-2xl block mb-2"></i> Tidak ada laporan yang menunggu tindakan unit Anda.
    </p>
  <?php endif; ?>

  <ul class="space-y-3">
    <?php foreach ($items as $r): ?>
      <?php $urgent = in_array($r['priority'], ['HIGH', 'CRITICAL'], true); ?>
      <li>
        <a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$r['id'] ?>"
           class="polri-glass-card rounded-2xl border <?= $urgent ? 'border-red-700/80' : 'border-[#3B2F1C]' ?> hover:border-amber-500/60 p-4 flex flex-col sm:flex-row sm:items-center gap-3 transition-colors">
          <span class="flex-1 min-w-0">
            <span class="flex flex-wrap items-center gap-2">
              <span class="font-mono font-bold text-amber-300 text-xs"><?= htmlspecialchars($r['report_number']) ?></span>
              <?= $poskoStatusBadge($r['status']) ?> <?= $poskoPriorityBadge($r['priority']) ?>
              <?php if ((int)$r['version'] > 1): ?><span class="text-[10px] text-amber-300">revisi v<?= (int)$r['version'] ?></span><?php endif; ?>
            </span>
            <span class="block text-sm text-slate-100 mt-1"><?= htmlspecialchars($r['type_name']) ?> — <?= htmlspecialchars($r['satker_name']) ?></span>
            <span class="block text-[11px] text-slate-400 truncate"><?= htmlspecialchars((string)$r['situation_summary']) ?></span>
          </span>
          <span class="text-[11px] text-slate-400 sm:text-right flex-shrink-0">
            <span class="block text-slate-200 font-bold"><?= $need[$r['status']] ?? '' ?></span>
            <?= htmlspecialchars($r['period_start'] ? PoskoSchedule::label($r['period_unit'], $r['period_start'], $r['period_end'])
                : ($r['event_time'] ? 'Kejadian ' . date('d/m H:i', strtotime($r['event_time'])) : '')) ?>
            · masuk <?= date('d/m H:i', strtotime($r['forwarded_at'] ?: $r['submitted_at'])) ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
