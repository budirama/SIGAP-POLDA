<?php
use App\Core\PoskoSchedule;
use App\Models\PoskoCompliance;

$short = fn(array $p) => $type['period_unit'] === 'DAY' ? date('d/m', strtotime($p['start'])) : PoskoSchedule::label($type['period_unit'], $p['start'], $p['end']);
?>
<div class="space-y-4">
  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C] flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div>
      <span class="chip-digital inline-flex px-2 py-0.5 rounded text-[10px]">POSKO // KEPATUHAN</span>
      <h2 class="mt-1 text-lg font-black text-slate-100 font-heading">Kepatuhan Pelaporan — <?= htmlspecialchars($unit['name']) ?></h2>
      <p class="text-xs text-slate-400">Unit yang belum mengirim ditampilkan sebagai <b>Belum dibuat</b> (sebelum tenggat) atau <b>Tidak masuk</b> (lewat tenggat), bukan nihil. Nihil hanya bila dinyatakan resmi oleh petugas.</p>
    </div>
    <form method="GET" action="<?= $baseUrl ?>/posko-kepatuhan" class="flex gap-2 text-xs">
      <input type="hidden" name="unit" value="<?= (int)$unit['id'] ?>">
      <label class="sr-only" for="cType">Jenis laporan</label>
      <select id="cType" name="type" onchange="this.form.submit()" class="bg-[#0C0A08] border border-slate-700 rounded-xl px-3 py-2 text-slate-100">
        <?php foreach ($types as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $type && (int)$type['id'] === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="px-3 py-2 rounded-xl bg-[#241D14] border border-slate-700 text-slate-100">Tampilkan</button></noscript>
    </form>
  </div>

  <nav aria-label="Wilayah" class="flex flex-wrap items-center gap-2 text-xs">
    <?php foreach ($ancestors as $a): ?>
      <?php if ($a['linkable']): ?>
        <a href="<?= $baseUrl ?>/posko-kepatuhan?unit=<?= (int)$a['id'] ?>&type=<?= (int)($type['id'] ?? 0) ?>" class="px-2.5 py-1 rounded-lg border border-slate-700 text-slate-300 hover:bg-[#1B1610]"><?= htmlspecialchars($a['name']) ?></a>
      <?php else: ?>
        <span class="px-2.5 py-1 rounded-lg border border-slate-800 text-slate-500"><?= htmlspecialchars($a['name']) ?></span>
      <?php endif; ?>
      <i class="fas fa-chevron-right text-[9px] text-slate-600" aria-hidden="true"></i>
    <?php endforeach; ?>
    <span class="px-2.5 py-1 rounded-lg border border-amber-500/50 bg-amber-500/10 text-amber-300 font-bold"><?= htmlspecialchars($unit['name']) ?></span>
  </nav>

  <?php if (!$type): ?>
    <p class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-8 text-center text-xs text-slate-500">Belum ada jenis laporan berperiode yang aktif.</p>
  <?php elseif (!$rows): ?>
    <p class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-8 text-center text-xs text-slate-500">Tidak ada unit di wilayah ini yang wajib membuat <?= htmlspecialchars($type['name']) ?>.</p>
  <?php else: ?>
    <!-- Ringkasan periode fokus -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-4">
      <p class="text-[11px] text-slate-400 mb-3">Ringkasan periode <b class="text-slate-200"><?= htmlspecialchars(PoskoSchedule::label($type['period_unit'], $focus['start'], $focus['end'])) ?></b>
        (tenggat <?= $focus['deadline'] ? date('d/m H:i', strtotime($focus['deadline'])) . ' WIB' : '-' ?>) · <?= count($rows) ?> unit wajib lapor · <b class="text-red-300"><?= (int)$lateCount ?> terlambat</b></p>
      <div class="flex flex-wrap gap-2 text-xs">
        <?php foreach (PoskoCompliance::STATUS as $code => [$label, $cls]): ?>
          <?php if ($summary[$code] > 0): ?>
            <span class="px-2.5 py-1 rounded-full border <?= $cls ?>"><?= $label ?>: <b><?= (int)$summary[$code] ?></b></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-[#14100C] text-slate-400 text-[10px] uppercase tracking-wider">
            <tr>
              <th class="p-3 text-left sticky left-0 bg-[#14100C]">Unit</th>
              <?php foreach ($periods as $p): ?><th class="p-3 text-center whitespace-nowrap"><?= htmlspecialchars($short($p)) ?></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/80">
            <?php foreach ($rows as $r): ?>
              <tr>
                <th class="p-3 text-left font-normal sticky left-0 bg-[#14100C]/95">
                  <?php if ($r['has_children'] && (int)$r['id'] !== (int)$unit['id']): ?>
                    <a href="<?= $baseUrl ?>/posko-kepatuhan?unit=<?= (int)$r['id'] ?>&type=<?= (int)$type['id'] ?>" class="text-slate-100 hover:text-amber-300"><?= htmlspecialchars($r['name']) ?> <i class="fas fa-chevron-right text-[9px] text-slate-500"></i></a>
                  <?php else: ?>
                    <span class="text-slate-100"><?= htmlspecialchars($r['name']) ?></span>
                  <?php endif; ?>
                  <?php if ((int)$r['id'] === (int)$unit['id']): ?><span class="ml-1 text-[10px] text-amber-300">(unit ini)</span><?php endif; ?>
                </th>
                <?php foreach ($periods as $p): ?>
                  <?php $cell = $matrix[(int)$r['id']][$p['start']]; [$label, $cls] = PoskoCompliance::STATUS[$cell['code']]; ?>
                  <td class="p-2 text-center">
                    <?php $badge = '<span class="inline-block px-2 py-1 rounded-lg border text-[10px] font-bold ' . $cls . '">' . $label . ($cell['late'] && $cell['code'] !== 'MISSING_LATE' ? ' · terlambat' : '') . '</span>'; ?>
                    <?php if ($cell['report_id']): ?>
                      <a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$cell['report_id'] ?>" title="Buka laporan"><?= $badge ?></a>
                    <?php else: ?>
                      <?= $badge ?>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>
