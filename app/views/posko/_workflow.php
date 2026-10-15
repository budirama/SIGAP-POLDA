<?php
/**
 * Panel tindakan workflow & linimasa riwayat laporan posko.
 * Butuh: $report, $actions (string[]), $history, $versionCount, $baseUrl, $csrfToken
 */
$actionLabels = [
    'start_review' => ['Mulai Verifikasi', 'fa-magnifying-glass', 'bg-indigo-950/60 border-indigo-700/60 text-indigo-200'],
    'approve'      => ['Setujui', 'fa-circle-check', 'bg-emerald-950/60 border-emerald-700/60 text-emerald-200'],
    'forward'      => ['Teruskan ke Tingkat Atas', 'fa-arrow-up-from-bracket', 'bg-teal-950/60 border-teal-700/60 text-teal-200'],
    'acknowledge'  => ['Tandai Diterima', 'fa-inbox', 'bg-sky-950/60 border-sky-700/60 text-sky-200'],
    'return'       => ['Kembalikan untuk Perbaikan', 'fa-rotate-left', 'bg-amber-950/60 border-amber-700/60 text-amber-200'],
    'revise'       => ['Ajukan Revisi', 'fa-code-branch', 'bg-amber-950/60 border-amber-700/60 text-amber-200'],
    'cancel'       => ['Batalkan Laporan', 'fa-ban', 'bg-red-950/60 border-red-800/60 text-red-200'],
];
$routes = ['start_review' => 'start-review', 'return' => 'return-report'];
$needsNote = ['return' => 'Catatan koreksi (wajib)', 'revise' => 'Alasan revisi (wajib)', 'cancel' => 'Alasan pembatalan (wajib)'];
$historyLabels = [
    'create' => 'Draft dibuat', 'submit' => 'Dikirim', 'resubmit' => 'Dikirim ulang', 'start_review' => 'Verifikasi dimulai',
    'approve' => 'Disetujui', 'return' => 'Dikembalikan untuk perbaikan', 'forward' => 'Diteruskan', 'acknowledge' => 'Diterima',
    'revise' => 'Revisi dibuka', 'cancel' => 'Dibatalkan',
];
$urgent = in_array($report['priority'], ['HIGH', 'CRITICAL'], true) && in_array($report['status'], ['SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED'], true);
?>
<?php if ($urgent): ?>
  <div class="p-4 rounded-2xl border border-red-700 bg-red-950/50 text-red-100 text-xs flex items-start gap-3" role="alert">
    <i class="fas fa-bolt text-red-300 mt-0.5"></i>
    <p><b>LAPORAN MENDESAK.</b> Laporan berprioritas <?= $report['priority'] === 'CRITICAL' ? 'kritis' : 'tinggi' ?> diproses segera
      dan diteruskan berjenjang tanpa menunggu rekapitulasi rutin. Setiap penerimaan tercatat pada riwayat.</p>
  </div>
<?php endif; ?>

<?php if ($report['holder_satker_id'] && in_array($report['status'], ['SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED'], true)): ?>
  <p class="text-xs text-slate-400"><i class="fas fa-location-dot text-amber-400 mr-1"></i>
    Saat ini dipegang: <b class="text-slate-200"><?= htmlspecialchars($report['holder_name']) ?></b></p>
<?php endif; ?>

<?php if ($actions): ?>
  <section class="polri-glass-card rounded-2xl border border-amber-500/40 p-5">
    <h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-3">Tindakan Anda</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
      <?php foreach ($actions as $a): ?>
        <?php [$label, $icon, $cls] = $actionLabels[$a]; ?>
        <form method="POST" action="<?= $baseUrl ?>/posko-laporan/<?= $routes[$a] ?? $a ?>" class="space-y-2"
              onsubmit="return confirm('<?= htmlspecialchars($label, ENT_QUOTES) ?>?');">
          <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
          <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
          <?php if (isset($needsNote[$a])): ?>
            <label class="sr-only" for="note-<?= $a ?>"><?= $needsNote[$a] ?></label>
            <textarea id="note-<?= $a ?>" name="notes" rows="2" required maxlength="2000" placeholder="<?= $needsNote[$a] ?>"
                      class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-slate-100 outline-none focus:border-amber-500"></textarea>
          <?php elseif (in_array($a, ['approve', 'forward'], true)): ?>
            <label class="sr-only" for="note-<?= $a ?>">Catatan (opsional)</label>
            <input id="note-<?= $a ?>" name="notes" maxlength="2000" placeholder="Catatan (opsional)"
                   class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-slate-100 outline-none focus:border-amber-500">
          <?php endif; ?>
          <button class="w-full px-3 py-2 rounded-xl border font-bold <?= $cls ?>"><i class="fas <?= $icon ?> mr-1"></i> <?= $label ?></button>
        </form>
      <?php endforeach; ?>
    </div>
    <?php if (in_array('forward', $actions, true) && $report['status'] !== 'APPROVED' && $report['status'] !== 'FORWARDED'): ?>
      <p class="mt-3 text-[11px] text-slate-400">Jenis laporan ini dapat diteruskan tanpa persetujuan terlebih dahulu (sesuai konfigurasi jenis).</p>
    <?php endif; ?>
  </section>
<?php endif; ?>

<section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Riwayat Tindakan</h3>
    <?php if ($versionCount > 0): ?>
      <a href="<?= $baseUrl ?>/posko-laporan/versions/<?= (int)$report['id'] ?>" class="text-[11px] text-amber-400 hover:text-amber-300">
        <i class="fas fa-code-compare mr-1"></i> <?= (int)$versionCount ?> versi tersimpan
      </a>
    <?php endif; ?>
  </div>
  <ol class="relative border-l border-[#3B2F1C] ml-2 space-y-4 text-xs">
    <?php foreach ($history as $h): ?>
      <li class="ml-4">
        <span class="absolute -left-[5px] mt-1.5 h-2.5 w-2.5 rounded-full <?= in_array($h['action'], ['return', 'cancel'], true) ? 'bg-amber-400' : 'bg-emerald-400' ?>"></span>
        <p class="text-slate-100 font-bold">
          <?= htmlspecialchars($historyLabels[$h['action']] ?? $h['action']) ?>
          <?php if ($h['action'] === 'forward' && $h['holder_to_name']): ?><span class="font-normal text-slate-400">ke <?= htmlspecialchars($h['holder_to_name']) ?></span><?php endif; ?>
          <?php if ((int)$h['version'] > 1): ?><span class="ml-1 text-[10px] font-normal text-amber-300">v<?= (int)$h['version'] ?></span><?php endif; ?>
        </p>
        <p class="text-[11px] text-slate-400">
          <?= htmlspecialchars(trim(($h['actor_rank'] ?? '') . ' ' . ($h['actor_name'] ?? 'Sistem'))) ?>
          <?= $h['actor_satker_name'] ? '· ' . htmlspecialchars($h['actor_satker_name']) : '' ?>
          · <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?> WIB
        </p>
        <?php if ($h['notes']): ?>
          <p class="mt-1 p-2 rounded-lg bg-[#0C0A08] border border-slate-800 text-slate-300 whitespace-pre-line"><?= htmlspecialchars($h['notes']) ?></p>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
    <?php if (!$history): ?><li class="ml-4 text-slate-500">Belum ada riwayat.</li><?php endif; ?>
  </ol>
</section>
