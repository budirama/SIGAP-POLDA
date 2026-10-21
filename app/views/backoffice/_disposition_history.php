<?php
/**
 * Riwayat tiket disposisi sebuah laporan (SPKT → Satker → jajaran).
 * Butuh: $report.
 */
$history = (new \App\Models\Disposition())->historyForReport((int)$report['id']);
if (!$history) {
    return;
}
$tone = [
    'PENDING'   => 'text-amber-300',
    'ACCEPTED'  => 'text-emerald-300',
    'RETURNED'  => 'text-rose-300',
    'FORWARDED' => 'text-blue-300',
    'REVOKED'   => 'text-slate-400',
];
?>
<div class="polri-glass-card rounded-2xl p-6 border border-[#3B2F1C]">
  <h3 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading border-b border-slate-700 pb-3 mb-4">
    <i class="fas fa-ticket text-amber-400"></i> Riwayat Tiket Disposisi
  </h3>
  <ol class="space-y-2 text-xs">
    <?php foreach ($history as $d): ?>
      <li class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
          <span class="text-slate-400"><?= htmlspecialchars($d['from_satker_name'] ?? 'SPKT Polri') ?></span>
          <i class="fas fa-arrow-right text-slate-600 mx-1"></i>
          <span class="font-bold text-slate-100"><?= htmlspecialchars($d['to_satker_name']) ?></span>
          <?php if ($d['notes']): ?><div class="text-slate-500 mt-0.5"><?= htmlspecialchars($d['notes']) ?></div><?php endif; ?>
          <?php if ($d['response_notes']): ?><div class="text-slate-400 mt-0.5 italic">Tanggapan: <?= htmlspecialchars($d['response_notes']) ?></div><?php endif; ?>
        </div>
        <div class="text-right shrink-0">
          <span class="font-bold <?= $tone[$d['status']] ?>"><?= \App\Models\Disposition::STATUSES[$d['status']] ?></span>
          <?php if ($d['escalated_at']): ?><span class="ml-1 px-1.5 py-0.5 rounded bg-red-950 text-red-300 border border-red-800 text-[10px] font-bold">ESKALASI</span><?php endif; ?>
          <div class="text-[10px] text-slate-500 font-mono">
            <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
            <?= $d['responded_at'] ? ' → ' . date('d/m H:i', strtotime($d['responded_at'])) : ' · batas ' . date('d/m H:i', strtotime($d['ack_deadline'])) ?>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
</div>
