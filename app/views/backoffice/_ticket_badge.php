<?php
/**
 * Status tiket disposisi terakhir sebuah laporan.
 * Butuh: $ticket (baris report_dispositions atau null).
 */
if (empty($ticket)) {
    return;
}

$overdue = $ticket['status'] === 'PENDING' && strtotime($ticket['ack_deadline']) < time();
$tone = match (true) {
    $overdue                          => 'bg-red-950 text-red-300 border-red-700 animate-pulse',
    $ticket['status'] === 'PENDING'   => 'bg-amber-950 text-amber-300 border-amber-700',
    $ticket['status'] === 'ACCEPTED'  => 'bg-emerald-950 text-emerald-300 border-emerald-700',
    $ticket['status'] === 'RETURNED'  => 'bg-rose-950 text-rose-300 border-rose-700',
    default                           => 'bg-slate-800 text-slate-300 border-slate-700',
};
?>
<span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded border text-[10px] font-bold <?= $tone ?>"
      title="<?= htmlspecialchars('Batas terima: ' . date('d/m/Y H:i', strtotime($ticket['ack_deadline']))) ?>">
  <i class="fas fa-ticket"></i>
  <?= $overdue ? 'Terlambat diterima' : \App\Models\Disposition::STATUSES[$ticket['status']] ?>
  <?php if ($ticket['status'] === 'PENDING' && !$overdue): ?>
    · s.d. <?= date('d/m H:i', strtotime($ticket['ack_deadline'])) ?>
  <?php endif; ?>
</span>
