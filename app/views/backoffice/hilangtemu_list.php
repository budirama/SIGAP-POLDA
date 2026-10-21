<?php
use App\Models\LostFound;

$selectCls = 'bg-[#0C0A08] border border-slate-700 focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-slate-100 outline-none';
$statusTone = [
    'PENDING'   => 'bg-amber-950 text-amber-300 border-amber-700',
    'PUBLISHED' => 'bg-blue-950 text-blue-300 border-blue-700',
    'MATCHED'   => 'bg-indigo-950 text-indigo-300 border-indigo-700',
    'RESOLVED'  => 'bg-emerald-950 text-emerald-300 border-emerald-700',
    'REJECTED'  => 'bg-red-950 text-red-300 border-red-800',
];
$tabUrl = fn(string $status) => htmlspecialchars($baseUrl . '/hilangtemu/admin?' . http_build_query(array_filter(
    ['status' => $status, 'type' => $filters['type'], 'category' => $filters['category'], 'q' => $filters['q']],
    fn($v) => $v !== ''
)));
$currentTab = $filters['status'] === '' ? 'ALL' : $filters['status'];
?>
<div class="space-y-6">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 polri-glass-card p-6 rounded-2xl border border-[#3B2F1C]">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-950/80 border border-amber-700/60 text-amber-300 text-xs font-bold mb-2">
        <i class="fas fa-magnifying-glass-location"></i> Layanan Kehilangan dan Temuan
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Verifikasi, Pencocokan &amp; Serah Terima</h2>
      <p class="text-xs text-slate-400 mt-1">Laporan kehilangan / temuan kendaraan, orang, barang berharga, dokumen, dan hewan dari masyarakat &amp; loket SPKT.</p>
    </div>
    <?php if ($canAct): ?>
      <div class="flex gap-2">
        <a href="<?= $baseUrl ?>/hilangtemu/lapor?type=FOUND" target="_blank" class="px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 text-xs font-black whitespace-nowrap"><i class="fas fa-plus mr-1"></i> Input Temuan</a>
        <a href="<?= $baseUrl ?>/hilangtemu/lapor?type=LOST" target="_blank" class="px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-black whitespace-nowrap"><i class="fas fa-plus mr-1"></i> Input Kehilangan</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tab status -->
  <div class="flex flex-wrap gap-2 text-xs">
    <?php foreach (['PENDING', 'PUBLISHED', 'MATCHED', 'RESOLVED', 'REJECTED'] as $s): ?>
      <a href="<?= $tabUrl($s) ?>" class="px-3 py-1.5 rounded-xl border font-bold <?= $currentTab === $s ? 'bg-amber-500 text-slate-950 border-amber-500' : 'border-slate-700 text-slate-300 hover:bg-[#1B1610]' ?>">
        <?= LostFound::STATUSES[$s] ?> <span class="opacity-70">(<?= (int)$counts[$s] ?>)</span>
      </a>
    <?php endforeach; ?>
    <a href="<?= $tabUrl('ALL') ?>" class="px-3 py-1.5 rounded-xl border font-bold <?= $currentTab === 'ALL' ? 'bg-amber-500 text-slate-950 border-amber-500' : 'border-slate-700 text-slate-300 hover:bg-[#1B1610]' ?>">Semua</a>
  </div>

  <form method="GET" action="<?= $baseUrl ?>/hilangtemu/admin" class="flex flex-col sm:flex-row gap-2">
    <input type="hidden" name="status" value="<?= htmlspecialchars($currentTab) ?>">
    <select name="type" class="<?= $selectCls ?>">
      <option value="">Semua jenis</option>
      <?php foreach (LostFound::TYPES as $k => $l): ?><option value="<?= $k ?>" <?= $filters['type'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <select name="category" class="<?= $selectCls ?>">
      <option value="">Semua kategori</option>
      <?php foreach (LostFound::CATEGORIES as $k => [$l]): ?><option value="<?= $k ?>" <?= $filters['category'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Kode, plat, IMEI, nama, lokasi…" class="flex-1 <?= $selectCls ?>">
    <button class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-black">Filter</button>
  </form>

  <div class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#14100C] text-slate-400 border-b border-slate-800 uppercase font-semibold text-[10px] tracking-wider">
          <tr>
            <th class="p-4">Kode</th>
            <th class="p-4">Jenis &amp; Objek</th>
            <th class="p-4">Waktu &amp; Lokasi</th>
            <th class="p-4">Pelapor</th>
            <th class="p-4">Status</th>
            <th class="p-4 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/70">
          <?php if (empty($pager['items'])): ?>
            <tr><td colspan="6" class="p-8 text-center text-slate-500">Tidak ada laporan pada filter ini.</td></tr>
          <?php endif; ?>
          <?php foreach ($pager['items'] as $it): ?>
            <tr class="hover:bg-[#1B1610]/60">
              <td class="p-4 font-mono font-bold text-amber-400 whitespace-nowrap"><?= htmlspecialchars($it['code']) ?></td>
              <td class="p-4">
                <span class="px-1.5 py-0.5 rounded text-[10px] font-black <?= $it['report_type'] === 'LOST' ? 'bg-rose-600 text-white' : 'bg-emerald-500 text-slate-950' ?>"><?= LostFound::TYPES[$it['report_type']] ?></span>
                <span class="text-slate-400 ml-1"><?= LostFound::CATEGORIES[$it['item_category']][0] ?></span>
                <div class="text-slate-100 font-semibold mt-1"><?= htmlspecialchars($it['title']) ?></div>
                <?php if ((int)$it['suggested_matches'] > 0): ?>
                  <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-indigo-950 text-indigo-300 border border-indigo-700 text-[10px] font-bold"><i class="fas fa-link"></i> <?= (int)$it['suggested_matches'] ?> saran cocok</span>
                <?php endif; ?>
                <?php if ((int)$it['pending_claims'] > 0): ?>
                  <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-amber-950 text-amber-300 border border-amber-700 text-[10px] font-bold"><i class="fas fa-hand"></i> <?= (int)$it['pending_claims'] ?> klaim</span>
                <?php endif; ?>
              </td>
              <td class="p-4 text-slate-300">
                <?= date('d/m/Y', strtotime($it['event_date'])) ?>
                <div class="text-slate-500"><?= htmlspecialchars($it['event_location']) ?></div>
              </td>
              <td class="p-4 text-slate-300">
                <?= htmlspecialchars($it['reporter_name']) ?>
                <div class="text-slate-500"><?= htmlspecialchars($it['reporter_phone']) ?></div>
                <?php if (!empty($it['created_by_officer_id'])): ?><div class="text-[10px] text-emerald-400">Input loket</div><?php endif; ?>
              </td>
              <td class="p-4"><span class="px-2 py-0.5 rounded border font-bold text-[10px] <?= $statusTone[$it['status']] ?>"><?= LostFound::STATUSES[$it['status']] ?></span></td>
              <td class="p-4 text-right">
                <a href="<?= $baseUrl ?>/hilangtemu/detail/<?= (int)$it['id'] ?>" class="px-3 py-1.5 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-300 font-bold whitespace-nowrap">Buka</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php $pagerPath = '/hilangtemu/admin'; require __DIR__ . '/_pagination.php'; ?>
  </div>
</div>
