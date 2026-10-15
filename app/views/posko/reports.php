<?php
use App\Core\PoskoSchedule;
use App\Models\PoskoReport;

require __DIR__ . '/_status.php';
$field = 'bg-[#0C0A08] border border-slate-700 rounded-xl px-3 py-2 text-slate-100 outline-none focus:border-amber-500';
?>
<div class="space-y-4">

  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C] flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <span class="chip-digital inline-flex px-2 py-0.5 rounded text-[10px]">POSKO // LAPORAN</span>
      <h2 class="mt-1 text-lg font-black text-slate-100 font-heading">Laporan Posko</h2>
      <p class="text-xs text-slate-400">Laporan unit Anda dan unit di bawahnya. Draft hanya terlihat oleh unit pembuatnya.</p>
    </div>
    <?php if ($canCreate): ?>
      <a href="<?= $baseUrl ?>/posko-laporan/create" class="btn-gold px-4 py-2.5 rounded-xl font-extrabold text-sm inline-flex items-center gap-2">
        <i class="fas fa-file-circle-plus"></i> Buat Laporan
      </a>
    <?php endif; ?>
  </div>

  <form method="GET" action="<?= $baseUrl ?>/posko-laporan" class="polri-glass-card p-4 rounded-2xl border border-[#3B2F1C] flex flex-wrap gap-2 text-xs">
    <label class="sr-only" for="fType">Jenis</label>
    <select id="fType" name="type" class="<?= $field ?>">
      <option value="">Semua jenis</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= (int)$t['id'] ?>" <?= (int)$filters['type'] === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="sr-only" for="fStatus">Status</label>
    <select id="fStatus" name="status" class="<?= $field ?>">
      <option value="">Semua status</option>
      <?php foreach (PoskoReport::STATUS_LABELS as $k => $label): ?>
        <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($units): ?>
      <label class="sr-only" for="fUnit">Unit</label>
      <select id="fUnit" name="satker" class="<?= $field ?> max-w-[16rem]">
        <option value="">Semua unit</option>
        <?php foreach ($units as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= (int)$filters['satker'] === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <label class="sr-only" for="fMonth">Bulan</label>
    <input id="fMonth" type="month" name="month" value="<?= htmlspecialchars($filters['month']) ?>" class="<?= $field ?>">
    <label class="sr-only" for="fSearch">Cari</label>
    <input id="fSearch" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Nomor, ringkasan, unit…" class="<?= $field ?> flex-1 min-w-[10rem]">
    <button class="px-4 py-2 rounded-xl bg-[#241D14] border border-slate-700 text-slate-200 font-bold">Filter</button>
    <a href="<?= $baseUrl ?>/posko-laporan" class="px-3 py-2 text-slate-400 hover:text-slate-200">Reset</a>
  </form>

  <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#14100C] text-slate-400 uppercase text-[10px] tracking-wider">
          <tr>
            <th class="p-3">Nomor & Jenis</th>
            <th class="p-3">Unit</th>
            <th class="p-3">Periode / Kejadian</th>
            <th class="p-3">Tenggat</th>
            <th class="p-3">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/80">
          <?php if (!$pager['items']): ?>
            <tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada laporan yang cocok.</td></tr>
          <?php endif; ?>
          <?php foreach ($pager['items'] as $r): ?>
            <?php $late = $r['deadline_at'] && (($r['submitted_at'] ?: date('Y-m-d H:i:s')) > $r['deadline_at']); ?>
            <tr class="hover:bg-[#1B1610]">
              <td class="p-3">
                <a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$r['id'] ?>" class="font-mono font-bold text-amber-300 hover:underline">
                  <?= htmlspecialchars($r['report_number'] ?: 'DRAFT #' . $r['id']) ?>
                </a>
                <p class="text-slate-300"><?= htmlspecialchars($r['type_name']) ?> <?= $poskoPriorityBadge($r['priority']) ?></p>
              </td>
              <td class="p-3 text-slate-300"><?= htmlspecialchars($r['satker_name']) ?></td>
              <td class="p-3 text-slate-300">
                <?= htmlspecialchars($r['period_start']
                    ? PoskoSchedule::label($r['period_unit'], $r['period_start'], $r['period_end'])
                    : ($r['event_time'] ? date('d/m/Y H:i', strtotime($r['event_time'])) : '-')) ?>
              </td>
              <td class="p-3 <?= $late ? 'text-red-300 font-bold' : 'text-slate-400' ?>">
                <?= $r['deadline_at'] ? date('d/m/Y H:i', strtotime($r['deadline_at'])) : '-' ?>
                <?php if ($late): ?><span class="block text-[10px]">Terlambat</span><?php endif; ?>
              </td>
              <td class="p-3"><?= $poskoStatusBadge($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php $pagerPath = '/posko-laporan'; require __DIR__ . '/../backoffice/_pagination.php'; ?>
  </section>
</div>
