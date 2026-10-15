<?php
use App\Core\PoskoSchedule;

require __DIR__ . '/_status.php';
$text = fn(?string $v) => trim((string)$v) !== '' ? nl2br(htmlspecialchars($v)) : '<span class="text-slate-600">—</span>';
$late = $report['deadline_at'] && (($report['submitted_at'] ?: date('Y-m-d H:i:s')) > $report['deadline_at']);
?>
<div class="space-y-6">

  <?php if ($canEdit): ?>
    <div class="p-4 rounded-2xl border <?= $missing ? 'border-amber-600/60 bg-amber-950/30' : 'border-emerald-700/60 bg-emerald-950/30' ?> flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
      <div>
        <p class="font-bold <?= $missing ? 'text-amber-200' : 'text-emerald-200' ?>">
          <i class="fas <?= $missing ? 'fa-circle-exclamation' : 'fa-circle-check' ?> mr-1"></i>
          <?= $missing ? 'Pratinjau — laporan belum lengkap' : ($report['status'] === 'NEEDS_REVISION' ? 'Pratinjau — siap dikirim ulang' : 'Pratinjau — laporan siap dikirim') ?>
        </p>
        <?php if ($missing): ?>
          <p class="text-amber-200/80 mt-1">Lengkapi: <?= htmlspecialchars(implode(', ', $missing)) ?>.</p>
        <?php else: ?>
          <p class="text-emerald-200/80 mt-1">Laporan akan diterima unit induk langsung persis seperti tampilan di bawah untuk diverifikasi. Setelah dikirim, isi tidak dapat diubah tanpa proses perbaikan.</p>
        <?php endif; ?>
      </div>
      <div class="flex gap-2 flex-shrink-0">
        <a href="<?= $baseUrl ?>/posko-laporan/edit/<?= (int)$report['id'] ?>" class="px-4 py-2 rounded-xl bg-[#241D14] border border-slate-700 text-slate-100 font-bold"><i class="fas fa-pen mr-1"></i> Ubah</a>
        <?php if (!$missing): ?>
          <form method="POST" action="<?= $baseUrl ?>/posko-laporan/submit" onsubmit="return confirm('Kirim laporan ini sekarang?');">
            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
            <button class="btn-gold px-4 py-2 rounded-xl font-extrabold"><i class="fas fa-paper-plane mr-1"></i> Kirim Laporan</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Kop laporan -->
  <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
      <div>
        <p class="font-mono text-amber-300 font-bold text-sm"><?= htmlspecialchars($report['report_number'] ?: 'Belum bernomor (draft #' . $report['id'] . ')') ?></p>
        <h2 class="text-lg font-black text-slate-100 font-heading"><?= htmlspecialchars($report['type_name']) ?></h2>
        <p class="text-xs text-slate-400"><?= htmlspecialchars($report['satker_name']) ?> · dibuat oleh <?= htmlspecialchars(trim(($report['creator_rank'] ?? '') . ' ' . ($report['creator_name'] ?? '-'))) ?></p>
      </div>
      <div class="flex flex-wrap items-center gap-2"><?= $poskoStatusBadge($report['status']) ?> <?= $poskoPriorityBadge($report['priority']) ?></div>
    </div>

    <dl class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
      <div class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800">
        <dt class="text-slate-500"><?= $report['period_start'] ? 'Periode' : 'Waktu kejadian' ?></dt>
        <dd class="text-slate-100 font-bold"><?= htmlspecialchars($report['period_start']
            ? PoskoSchedule::label($report['period_unit'], $report['period_start'], $report['period_end'])
            : ($report['event_time'] ? date('d/m/Y H:i', strtotime($report['event_time'])) . ' WIB' : '-')) ?></dd>
      </div>
      <div class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800">
        <dt class="text-slate-500">Tenggat</dt>
        <dd class="font-bold <?= $late ? 'text-red-300' : 'text-slate-100' ?>"><?= $report['deadline_at'] ? date('d/m/Y H:i', strtotime($report['deadline_at'])) . ' WIB' : '-' ?><?= $late ? ' · terlambat' : '' ?></dd>
      </div>
      <div class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800">
        <dt class="text-slate-500">Dikirim</dt>
        <dd class="text-slate-100 font-bold"><?= $report['submitted_at'] ? date('d/m/Y H:i', strtotime($report['submitted_at'])) . ' WIB' : 'Belum' ?></dd>
      </div>
      <div class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800">
        <dt class="text-slate-500">Wilayah kejadian</dt>
        <dd class="text-slate-100 font-bold"><?= htmlspecialchars($report['region'] ?: '-') ?></dd>
      </div>
    </dl>
  </section>

  <?php require __DIR__ . '/_workflow.php'; ?>

  <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 space-y-4 text-sm">
    <div><h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-1">Ringkasan Situasi</h3><p class="text-slate-200"><?= $text($report['situation_summary']) ?></p></div>
    <div><h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-1">Uraian Kegiatan / Kejadian</h3><p class="text-slate-200"><?= $text($report['description']) ?></p></div>
    <div class="grid md:grid-cols-3 gap-4">
      <div><h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-1">Kendala</h3><p class="text-slate-300 text-xs"><?= $text($report['obstacles']) ?></p></div>
      <div><h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-1">Tindak Lanjut</h3><p class="text-slate-300 text-xs"><?= $text($report['follow_up']) ?></p></div>
      <div><h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-1">Kebutuhan Bantuan / Eskalasi</h3><p class="text-slate-300 text-xs"><?= $text($report['assistance_needed']) ?></p></div>
    </div>
    <?php if ($report['reference_ticket']): ?>
      <p class="text-xs text-slate-400">Tiket aduan terkait: <span class="font-mono text-slate-200"><?= htmlspecialchars($report['reference_ticket']) ?></span></p>
    <?php endif; ?>
  </section>

  <?php if ($fields): ?>
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
      <h3 class="px-5 py-3 border-b border-[#3B2F1C] text-[11px] font-bold uppercase tracking-wider text-amber-400">Data Laporan</h3>
      <table class="w-full text-xs">
        <tbody class="divide-y divide-slate-800/80">
          <?php foreach ($fields as $f): ?>
            <?php
            $v = $values[$f['field_key']] ?? null;
            if (!$v && !(int)$f['is_active']) continue; // isian nonaktif tanpa nilai tidak ditampilkan
            $shown = $v === null ? null : ($v['value_number'] !== null
                ? rtrim(rtrim(number_format((float)$v['value_number'], 2, ',', '.'), '0'), ',')
                : $v['value_text']);
            ?>
            <tr>
              <th class="p-3 text-left font-normal text-slate-400 w-1/2"><?= htmlspecialchars($f['label']) ?></th>
              <td class="p-3 text-slate-100 font-bold">
                <?= $shown === null ? '<span class="text-slate-600">—</span>' : nl2br(htmlspecialchars($shown)) ?>
                <?php if ($shown !== null && $f['unit_label']): ?><span class="text-slate-500 font-normal"><?= htmlspecialchars($f['unit_label']) ?></span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>

  <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 text-xs">
    <h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-3">Lampiran</h3>
    <?php if (!$attachments): ?><p class="text-slate-500">Tidak ada lampiran.</p><?php endif; ?>
    <ul class="grid sm:grid-cols-2 gap-2">
      <?php foreach ($attachments as $a): ?>
        <li><a href="<?= $baseUrl ?>/posko-laporan/attachment/<?= (int)$a['id'] ?>" target="_blank"
               class="flex items-center gap-2 p-2 rounded-lg bg-[#0C0A08] border border-slate-800 text-slate-200 hover:border-amber-500/50">
          <i class="fas fa-paperclip text-amber-400"></i> <span class="truncate"><?= htmlspecialchars($a['file_name_original'] ?: 'lampiran') ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <a href="<?= $baseUrl ?>/posko-laporan" class="inline-flex items-center gap-1 text-xs text-amber-400 hover:text-amber-300"><i class="fas fa-arrow-left text-[10px]"></i> Kembali ke daftar laporan</a>
</div>
