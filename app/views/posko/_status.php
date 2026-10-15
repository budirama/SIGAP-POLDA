<?php
/**
 * Badge status & prioritas laporan posko. Pakai: echo $poskoStatusBadge($status); echo $poskoPriorityBadge($priority);
 */
$poskoStatusBadge = function (string $status): string {
    $map = [
        'DRAFT'          => 'border-slate-600 text-slate-300 bg-slate-800/40',
        'SUBMITTED'      => 'border-sky-700/60 text-sky-300 bg-sky-950/40',
        'IN_REVIEW'      => 'border-indigo-700/60 text-indigo-300 bg-indigo-950/40',
        'NEEDS_REVISION' => 'border-amber-600/60 text-amber-300 bg-amber-950/40',
        'APPROVED'       => 'border-emerald-600/60 text-emerald-300 bg-emerald-950/40',
        'FORWARDED'      => 'border-teal-600/60 text-teal-300 bg-teal-950/40',
        'CANCELLED'      => 'border-red-800/60 text-red-300 bg-red-950/40',
    ];
    $label = \App\Models\PoskoReport::STATUS_LABELS[$status] ?? $status;
    return '<span class="inline-flex px-2 py-0.5 rounded-full border text-[10px] font-bold ' . ($map[$status] ?? '') . '">'
        . htmlspecialchars($label) . '</span>';
};
$poskoPriorityBadge = function (string $priority): string {
    return match ($priority) {
        'CRITICAL' => '<span class="inline-flex px-2 py-0.5 rounded-full border border-red-600 text-red-200 bg-red-950/60 text-[10px] font-black">KRITIS</span>',
        'HIGH' => '<span class="inline-flex px-2 py-0.5 rounded-full border border-orange-600/70 text-orange-200 bg-orange-950/50 text-[10px] font-bold">TINGGI</span>',
        default => '',
    };
};
