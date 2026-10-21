<?php
use App\Models\OneService\OpsDrill;

$input = 'bg-[#0C0A08] border border-slate-700 focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-slate-100 outline-none';
$active = array_filter($filters, fn($v) => $v !== '');
$url = fn(array $override = []) => htmlspecialchars($baseUrl . '/oneservice/daftar?' . http_build_query(array_filter(
    array_merge($filters, $override), fn($v) => $v !== '' && $v !== null
)));
$statusTone = [
    'INCOMING' => 'text-sky-300', 'DISPATCHED' => 'text-blue-300', 'VERIFIED_SPKT' => 'text-indigo-300',
    'INVESTIGATION' => 'text-amber-300', 'RESOLVED' => 'text-emerald-300', 'CLOSED' => 'text-emerald-400', 'REJECTED' => 'text-red-300',
];
?>
<div class="space-y-6">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 polri-glass-card p-6 rounded-2xl border border-[#3B2F1C]">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-950/80 border border-amber-700/60 text-amber-300 text-xs font-bold mb-2">
        <i class="fas fa-fingerprint"></i> Satu Nomor, Satu Jejak
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Daftar Perkara Terpadu</h2>
      <p class="text-xs text-slate-400 mt-1">Ringkasan per satker → daftar perkara → detail nomor lacak. Data pribadi pelapor disamarkan; setiap pembukaan detail tercatat di jejak audit UU PDP.</p>
    </div>
    <div class="flex gap-2">
      <a href="<?= $baseUrl ?>/oneservice/dashboard" class="px-4 py-2 rounded-xl bg-[#1B1610] border border-slate-700 text-xs font-bold text-slate-200 whitespace-nowrap"><i class="fas fa-chart-pie mr-1 text-emerald-400"></i> Dashboard 100 Hari</a>
    </div>
  </div>

  <?php if ($readOnly): ?>
    <p class="text-[11px] font-semibold text-sky-300 bg-sky-950/60 border border-sky-800/60 px-3 py-2 rounded-xl"><i class="fas fa-eye mr-1"></i> Mode pantau pimpinan: seluruh data dapat dilihat hingga detail; perubahan dilakukan oleh satuan pelaksana.</p>
  <?php endif; ?>

  <!-- Ringkasan per satker -->
  <details class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden" <?= $filters['satker'] === '' ? 'open' : '' ?>>
    <summary class="cursor-pointer p-4 text-sm font-black text-slate-100"><i class="fas fa-sitemap text-amber-400 mr-1"></i> Ringkasan per Satker Penanggung Jawab (<?= count($summary) ?>)</summary>
    <div class="overflow-x-auto max-h-80 overflow-y-auto border-t border-slate-800">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#14100C] text-slate-400 uppercase text-[10px] tracking-wider sticky top-0">
          <tr><th class="p-3">Satker</th><th class="p-3 text-right">Total</th><th class="p-3 text-right">Tuntas</th><th class="p-3 text-right">Macet (SLA)</th><th class="p-3 text-right">Darurat</th><th class="p-3 text-right">Kelompok Rentan</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-800/70">
          <?php foreach ($summary as $s): ?>
            <tr class="hover:bg-[#1B1610]/60">
              <td class="p-3"><a href="<?= $url(['satker' => (int)$s['id'], 'page' => null]) ?>" class="font-semibold text-slate-100 hover:text-amber-300"><?= htmlspecialchars($s['name']) ?></a></td>
              <td class="p-3 text-right font-mono"><a href="<?= $url(['satker' => (int)$s['id'], 'status' => '', 'stalled' => '', 'page' => null]) ?>" class="hover:text-amber-300"><?= (int)$s['total'] ?></a></td>
              <td class="p-3 text-right font-mono text-emerald-300"><a href="<?= $url(['satker' => (int)$s['id'], 'status' => 'TUNTAS', 'page' => null]) ?>"><?= (int)$s['tuntas'] ?></a></td>
              <td class="p-3 text-right font-mono <?= (int)$s['stalled'] > 0 ? 'text-red-300 font-bold' : 'text-slate-500' ?>"><a href="<?= $url(['satker' => (int)$s['id'], 'stalled' => '1', 'page' => null]) ?>"><?= (int)$s['stalled'] ?></a></td>
              <td class="p-3 text-right font-mono"><a href="<?= $url(['satker' => (int)$s['id'], 'priority' => 'P1_EMERGENCY', 'page' => null]) ?>"><?= (int)$s['darurat'] ?></a></td>
              <td class="p-3 text-right font-mono text-purple-300"><a href="<?= $url(['satker' => (int)$s['id'], 'vulnerable' => '1', 'page' => null]) ?>"><?= (int)$s['rentan'] ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>

  <!-- Filter -->
  <form method="GET" action="<?= $baseUrl ?>/oneservice/daftar" class="polri-glass-card p-4 rounded-2xl border border-slate-800 flex flex-wrap gap-2 items-center">
    <?php if ($filters['satker'] !== ''): ?><input type="hidden" name="satker" value="<?= (int)$filters['satker'] ?>"><?php endif; ?>
    <select name="status" class="<?= $input ?>">
      <option value="">Semua status</option>
      <option value="TUNTAS" <?= $filters['status'] === 'TUNTAS' ? 'selected' : '' ?>>Tuntas (selesai + ditutup)</option>
      <?php foreach (OpsDrill::STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <select name="channel" class="<?= $input ?>">
      <option value="">Semua kanal</option>
      <?php foreach (OpsDrill::CHANNELS as $k => $l): ?><option value="<?= $k ?>" <?= $filters['channel'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <select name="priority" class="<?= $input ?>">
      <option value="">Semua prioritas</option>
      <?php foreach (OpsDrill::PRIORITIES as $k => $l): ?><option value="<?= $k ?>" <?= $filters['priority'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <label class="text-xs text-slate-300 flex items-center gap-1"><input type="checkbox" name="stalled" value="1" <?= $filters['stalled'] ? 'checked' : '' ?>> Macet SLA</label>
    <label class="text-xs text-slate-300 flex items-center gap-1"><input type="checkbox" name="vulnerable" value="1" <?= $filters['vulnerable'] ? 'checked' : '' ?>> Kelompok rentan</label>
    <input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="POL-…, LP-POLRI-…, nama, kategori" class="flex-1 min-w-[12rem] <?= $input ?>">
    <button class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-black">Terapkan</button>
    <?php if ($active): ?><a href="<?= $baseUrl ?>/oneservice/daftar" class="text-xs text-slate-400 hover:text-amber-300">Reset</a><?php endif; ?>
  </form>

  <?php if ($filters['satker'] !== ''):
    $satkerName = '';
    foreach ($summary as $s) { if ((int)$s['id'] === (int)$filters['satker']) { $satkerName = $s['name']; } } ?>
    <p class="text-xs text-slate-300">Satker: <b class="text-amber-300"><?= htmlspecialchars($satkerName ?: '#' . (int)$filters['satker']) ?></b>
      · <a href="<?= $url(['satker' => '', 'page' => null]) ?>" class="text-slate-400 hover:text-amber-300">semua satker</a></p>
  <?php endif; ?>

  <!-- Daftar -->
  <div class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#14100C] text-slate-400 border-b border-slate-800 uppercase font-semibold text-[10px] tracking-wider">
          <tr><th class="p-4">Nomor Lacak</th><th class="p-4">Perkara</th><th class="p-4">Satker</th><th class="p-4">Status & SLA</th><th class="p-4"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-800/70">
          <?php if (empty($pager['items'])): ?>
            <tr><td colspan="5" class="p-8 text-center text-slate-500">Tidak ada perkara pada filter ini.</td></tr>
          <?php endif; ?>
          <?php foreach ($pager['items'] as $t): ?>
            <tr class="hover:bg-[#1B1610]/60 <?= (int)$t['is_stalled'] ? 'bg-red-950/10' : '' ?>">
              <td class="p-4 whitespace-nowrap">
                <a href="<?= $baseUrl ?>/oneservice/detail/<?= urlencode($t['tracking_number']) ?>" class="font-mono font-bold text-amber-400 hover:underline"><?= htmlspecialchars($t['tracking_number']) ?></a>
                <div class="text-[10px] text-slate-500"><?= OpsDrill::CHANNELS[$t['source_channel']] ?? $t['source_channel'] ?> · <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></div>
                <?php if ($t['external_reference_no']): ?><div class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($t['external_reference_no']) ?></div><?php endif; ?>
              </td>
              <td class="p-4 max-w-md">
                <div class="text-[10px] text-slate-400"><?= htmlspecialchars($t['incident_category']) ?> · <?= OpsDrill::PRIORITIES[$t['priority_level']] ?? $t['priority_level'] ?></div>
                <div class="text-slate-100 line-clamp-2"><?= htmlspecialchars(mb_substr($t['incident_summary'], 0, 200)) ?></div>
                <div class="mt-1 flex flex-wrap gap-1 text-[10px]">
                  <?php if ((int)$t['is_vulnerable_group']): ?><span class="px-1.5 rounded bg-purple-950 text-purple-300 border border-purple-800">Kelompok rentan</span><?php endif; ?>
                  <?php if ((int)$t['is_whistleblower']): ?><span class="px-1.5 rounded bg-slate-800 text-slate-300">Whistleblower</span><?php endif; ?>
                </div>
              </td>
              <td class="p-4 text-slate-300"><?= htmlspecialchars($t['satker_name'] ?? '-') ?></td>
              <td class="p-4">
                <span class="font-bold <?= $statusTone[$t['current_status']] ?? 'text-slate-300' ?>"><?= OpsDrill::STATUSES[$t['current_status']] ?? $t['current_status'] ?></span>
                <?php if ((int)$t['is_stalled']): ?><span class="ml-1 px-1.5 rounded bg-red-950 text-red-300 border border-red-800 text-[10px] font-bold">MACET</span><?php endif; ?>
                <div class="text-[10px] text-slate-500"><?= (int)$t['milestone_count'] ?> SP2HP<?= $t['sla_deadline'] ? ' · SLA ' . date('d/m H:i', strtotime($t['sla_deadline'])) : '' ?></div>
              </td>
              <td class="p-4 text-right"><a href="<?= $baseUrl ?>/oneservice/detail/<?= urlencode($t['tracking_number']) ?>" class="px-3 py-1.5 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-300 font-bold whitespace-nowrap">Detail</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php $pagerPath = '/oneservice/daftar'; require dirname(__DIR__) . '/backoffice/_pagination.php'; ?>
  </div>
</div>
