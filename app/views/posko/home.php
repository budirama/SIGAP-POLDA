<?php
use App\Config\Constants;

require __DIR__ . '/_status.php';
$levelIcon = [4 => 'fa-landmark', 3 => 'fa-building-shield', 2 => 'fa-building', 1 => 'fa-house-flag'];
$uid = (int)$unit['id'];
$trendMax = max(1, max(array_column($trend, 'count') ?: [0]));
$trendTotal = array_sum(array_column($trend, 'count'));
$cardDefs = [
    ['required', 'Harus dibuat',        'fa-clipboard-list', 'text-slate-200', '/posko-kepatuhan?unit=' . $uid],
    ['sent',     'Sudah dikirim',       'fa-paper-plane',    'text-sky-300',   '/posko-kepatuhan?unit=' . $uid],
    ['pending',  'Menunggu verifikasi', 'fa-hourglass-half', 'text-indigo-300', '/posko-laporan?status=SUBMITTED'],
    ['revision', 'Perlu perbaikan',     'fa-rotate-left',    'text-amber-300', '/posko-laporan?status=NEEDS_REVISION'],
    ['late',     'Terlambat',           'fa-clock',          'text-red-300',   '/posko-kepatuhan?unit=' . $uid],
];
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

  <!-- Periode aktif -->
  <?php if ($periods): ?>
    <p class="text-[11px] text-slate-400 flex flex-wrap gap-x-4 gap-y-1">
      <span class="text-slate-500">Periode pelaporan:</span>
      <?php foreach ($periods as $p): ?>
        <span><span class="text-slate-300"><?= htmlspecialchars($p['type']) ?></span> · <?= htmlspecialchars($p['label']) ?></span>
      <?php endforeach; ?>
    </p>
  <?php endif; ?>

  <!-- Kartu status pelaporan -->
  <section aria-label="Status pelaporan" id="poskoCards" class="grid grid-cols-2 lg:grid-cols-5 gap-3">
    <?php foreach ($cardDefs as [$key, $label, $icon, $tone, $href]): ?>
      <a href="<?= $baseUrl . $href ?>" data-card="<?= $key ?>"
         class="polri-glass-card rounded-2xl border <?= $key === 'late' && $cards['late'] > 0 ? 'border-red-700/70' : 'border-[#3B2F1C]' ?> hover:border-amber-500/60 p-4 transition-colors">
        <span class="flex items-center justify-between text-[11px] text-slate-400">
          <?= $label ?> <i class="fas <?= $icon ?> <?= $tone ?>" aria-hidden="true"></i>
        </span>
        <span class="block mt-1 text-2xl font-black font-mono <?= $tone ?>"><?= (int)$cards[$key] ?></span>
        <?php if ($key === 'sent' && $cards['nil'] > 0): ?>
          <span class="text-[10px] text-teal-300"><?= (int)$cards['nil'] ?> di antaranya nihil terkonfirmasi</span>
        <?php elseif ($key === 'late' && $cards['missing'] > 0): ?>
          <span class="text-[10px] text-red-300"><?= (int)$cards['missing'] ?> belum masuk (bukan nihil)</span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </section>

  <div class="grid lg:grid-cols-3 gap-4">
    <!-- Tren pelaporan -->
    <section class="lg:col-span-2 polri-glass-card rounded-2xl border border-[#3B2F1C] p-5" aria-label="Tren pelaporan">
      <header class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-bold text-slate-100">Tren Laporan Terkirim</h3>
        <span class="text-[11px] text-slate-400"><?= count($trend) ?> hari terakhir · <?= (int)$trendTotal ?> laporan</span>
      </header>
      <div class="flex items-end gap-1 h-32" id="poskoTrend" role="img" aria-label="Grafik batang jumlah laporan terkirim per hari">
        <?php foreach ($trend as $t): ?>
          <div class="flex-1 min-w-0 flex flex-col items-center justify-end h-full" title="<?= date('d/m/Y', strtotime($t['date'])) ?>: <?= (int)$t['count'] ?> laporan">
            <span class="text-[9px] text-slate-400 font-mono"><?= $t['count'] > 0 ? (int)$t['count'] : '' ?></span>
            <span class="w-full rounded-t bg-gradient-to-t from-amber-700 to-amber-400 <?= $t['count'] === 0 ? 'opacity-25' : '' ?>"
                  style="height: <?= $t['count'] === 0 ? 2 : max(6, (int)round($t['count'] / $trendMax * 100)) ?>%"></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="flex gap-1 mt-1" aria-hidden="true">
        <?php foreach ($trend as $i => $t): ?>
          <span class="flex-1 min-w-0 text-center text-[9px] text-slate-500 font-mono <?= $i % 2 ? 'hidden sm:block' : '' ?>"><?= date('d', strtotime($t['date'])) ?></span>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Tindakan pengguna -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5" aria-label="Memerlukan tindakan Anda">
      <h3 class="text-sm font-bold text-slate-100 mb-3">Memerlukan Tindakan Anda</h3>
      <?php if ($actions === null): ?>
        <p class="text-xs text-slate-500">Tindakan verifikasi tersedia pada beranda posko unit Anda sendiri.</p>
      <?php elseif (!$actions['inbox_total'] && !$actions['revision']): ?>
        <p class="text-xs text-slate-400"><i class="fas fa-circle-check text-emerald-400 mr-1"></i>Tidak ada tindakan yang menunggu.</p>
      <?php else: ?>
        <ul class="space-y-2 text-xs">
          <?php foreach ($actions['inbox'] as $a): ?>
            <li><a class="flex justify-between gap-2 hover:text-amber-300" href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$a['id'] ?>">
              <span class="truncate text-slate-200"><?= htmlspecialchars($a['report_number']) ?> · <?= htmlspecialchars($a['satker_name']) ?></span>
              <?= $poskoStatusBadge($a['status']) ?></a></li>
          <?php endforeach; ?>
          <?php foreach ($actions['revision'] as $a): ?>
            <li><a class="flex justify-between gap-2 hover:text-amber-300" href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$a['id'] ?>">
              <span class="truncate text-slate-200"><?= htmlspecialchars($a['report_number']) ?> · <?= htmlspecialchars($a['type_name']) ?></span>
              <?= $poskoStatusBadge('NEEDS_REVISION') ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($actions['inbox_total'] > count($actions['inbox'])): ?>
          <a href="<?= $baseUrl ?>/posko-laporan/inbox" class="block mt-3 text-[11px] text-amber-400 hover:text-amber-300">Lihat semua <?= (int)$actions['inbox_total'] ?> antrean →</a>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>

  <div class="grid lg:grid-cols-2 gap-4">
    <!-- Kejadian penting -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden" aria-label="Kejadian penting">
      <header class="px-5 py-3 border-b border-[#3B2F1C] flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-100"><i class="fas fa-triangle-exclamation text-red-300 mr-1"></i>Kejadian Penting</h3>
        <span class="text-[11px] text-slate-400">prioritas tinggi &amp; kritis</span>
      </header>
      <?php if (!$events): ?>
        <p class="p-5 text-xs text-slate-500">Tidak ada kejadian prioritas tinggi yang sedang berjalan.</p>
      <?php else: ?>
        <ul class="divide-y divide-[#3B2F1C]/60">
          <?php foreach ($events as $e): ?>
            <li><a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$e['id'] ?>" class="block px-5 py-3 hover:bg-[#1B1610]">
              <span class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-bold text-amber-300"><?= htmlspecialchars($e['report_number']) ?></span>
                <?= $poskoPriorityBadge($e['priority']) ?> <?= $poskoStatusBadge($e['status']) ?></span>
              <span class="block text-sm text-slate-100 mt-0.5"><?= htmlspecialchars($e['type_name']) ?> — <?= htmlspecialchars($e['satker_name']) ?></span>
              <span class="block text-[11px] text-slate-400 truncate"><?= htmlspecialchars((string)$e['situation_summary']) ?></span>
              <?php if ($e['holder_name']): ?><span class="block text-[10px] text-slate-500">Dipegang: <?= htmlspecialchars($e['holder_name']) ?></span><?php endif; ?>
            </a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <!-- Laporan terlambat & terbaru -->
    <div class="space-y-4">
      <section class="polri-glass-card rounded-2xl border <?= $cards['late_items'] ? 'border-red-700/60' : 'border-[#3B2F1C]' ?> overflow-hidden" aria-label="Laporan terlambat">
        <header class="px-5 py-3 border-b border-[#3B2F1C] flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-100"><i class="fas fa-clock text-red-300 mr-1"></i>Laporan Terlambat</h3>
          <a href="<?= $baseUrl ?>/posko-kepatuhan?unit=<?= $uid ?>" class="text-[11px] text-amber-400 hover:text-amber-300">Matriks kepatuhan →</a>
        </header>
        <?php if (!$cards['late_items']): ?>
          <p class="p-5 text-xs text-slate-500">Tidak ada unit yang terlambat pada periode fokus.</p>
        <?php else: ?>
          <ul class="divide-y divide-[#3B2F1C]/60">
            <?php foreach ($cards['late_items'] as $l): ?>
              <li class="px-5 py-2.5 text-xs flex justify-between gap-3">
                <span class="truncate text-slate-200"><?= htmlspecialchars($l['unit']) ?> <span class="text-slate-500">· <?= htmlspecialchars($l['type']) ?> · <?= htmlspecialchars($l['period']) ?></span></span>
                <span class="flex-shrink-0 <?= $l['code'] === 'MISSING_LATE' ? 'text-red-300' : 'text-amber-300' ?>"><?= $l['code'] === 'MISSING_LATE' ? 'Belum masuk' : 'Terlambat dikirim' ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden" aria-label="Laporan terbaru">
        <header class="px-5 py-3 border-b border-[#3B2F1C]"><h3 class="text-sm font-bold text-slate-100">Laporan Terbaru</h3></header>
        <?php if (!$recent): ?>
          <p class="p-5 text-xs text-slate-500">Belum ada laporan yang dikirim di wilayah ini.</p>
        <?php else: ?>
          <ul class="divide-y divide-[#3B2F1C]/60">
            <?php foreach ($recent as $r): ?>
              <li><a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$r['id'] ?>" class="flex items-center justify-between gap-3 px-5 py-2.5 text-xs hover:bg-[#1B1610]">
                <span class="truncate"><span class="font-mono text-amber-300"><?= htmlspecialchars($r['report_number']) ?></span> <span class="text-slate-300"><?= (int)$r['is_recap'] ? 'Rekap ' : '' ?><?= htmlspecialchars($r['type_name']) ?> — <?= htmlspecialchars($r['satker_name']) ?></span></span>
                <?= $poskoStatusBadge($r['status']) ?>
              </a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </div>
  </div>

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
              <span class="hidden sm:flex items-center gap-1.5 text-[10px] flex-shrink-0" data-child-status="<?= (int)$c['id'] ?>">
                <?php if (isset($childStatus[(int)$c['id']])): $cs = $childStatus[(int)$c['id']]; ?>
                  <span class="px-2 py-0.5 rounded-full border border-sky-700/60 text-sky-200" title="Sudah dikirim / wajib periode fokus"><?= (int)$cs['sent'] ?>/<?= (int)$cs['required'] ?> terkirim</span>
                  <?php if ($cs['pending']): ?><span class="px-2 py-0.5 rounded-full border border-indigo-700/60 text-indigo-200"><?= (int)$cs['pending'] ?> verifikasi</span><?php endif; ?>
                  <?php if ($cs['revision']): ?><span class="px-2 py-0.5 rounded-full border border-amber-600/70 text-amber-200"><?= (int)$cs['revision'] ?> perbaikan</span><?php endif; ?>
                  <?php if ($cs['late']): ?><span class="px-2 py-0.5 rounded-full border border-red-700 text-red-200 bg-red-950/60"><?= (int)$cs['late'] ?> terlambat</span><?php endif; ?>
                <?php endif; ?>
              </span>
              <span class="hidden lg:inline text-[11px] text-slate-400"><?= (int)$c['child_count'] ?> sub-unit · <?= (int)$c['user_count'] ?> akun</span>
              <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

</div>
