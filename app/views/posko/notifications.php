<?php
$typeIcon = [
    'report_submitted' => ['fa-inbox', 'text-sky-300'],
    'report_returned'  => ['fa-rotate-left', 'text-amber-300'],
    'report_approved'  => ['fa-circle-check', 'text-emerald-300'],
    'report_forwarded' => ['fa-share', 'text-indigo-300'],
    'high_priority'    => ['fa-triangle-exclamation', 'text-red-300'],
    'deadline_soon'    => ['fa-hourglass-half', 'text-amber-300'],
    'deadline_late'    => ['fa-clock', 'text-red-300'],
];
$csrf = \App\Core\Session::getCsrfToken();
?>
<div class="space-y-4">
  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C] flex items-center justify-between gap-3">
    <div>
      <span class="chip-digital inline-flex px-2 py-0.5 rounded text-[10px]">POSKO // NOTIFIKASI</span>
      <h2 class="mt-1 text-lg font-black text-slate-100 font-heading">Notifikasi Posko</h2>
      <p class="text-xs text-slate-400"><?= (int)$pager['total'] ?> notifikasi · hanya peristiwa yang menjadi kewenangan unit Anda.</p>
    </div>
    <form method="POST" action="<?= $baseUrl ?>/posko-notifikasi/readAll">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <button type="submit" class="text-xs px-3 py-2 rounded-lg border border-slate-700 text-slate-200 hover:bg-[#1B1610]">
        <i class="fas fa-check-double mr-1"></i>Tandai semua dibaca
      </button>
    </form>
  </div>

  <?php if (!$pager['items']): ?>
    <p class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-8 text-center text-xs text-slate-500">
      <i class="fas fa-bell-slash text-amber-400 text-2xl block mb-2"></i> Belum ada notifikasi.
    </p>
  <?php endif; ?>

  <ul class="space-y-2">
    <?php foreach ($pager['items'] as $n): ?>
      <?php [$icon, $tone] = $typeIcon[$n['type']] ?? ['fa-bell', 'text-slate-300']; $unread = $n['read_at'] === null; ?>
      <li>
        <form method="POST" action="<?= $baseUrl ?>/posko-notifikasi/open">
          <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
          <button type="submit" class="w-full text-left polri-glass-card rounded-2xl border <?= $unread ? 'border-amber-500/50' : 'border-[#3B2F1C]' ?> hover:border-amber-500/60 p-4 flex items-start gap-3 transition-colors">
            <i class="fas <?= $icon ?> <?= $tone ?> mt-1 w-5 text-center" aria-hidden="true"></i>
            <span class="flex-1 min-w-0">
              <span class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-bold <?= $unread ? 'text-slate-100' : 'text-slate-300' ?>"><?= htmlspecialchars($n['title']) ?></span>
                <span class="text-[10px] px-2 py-0.5 rounded-full border border-slate-600 text-slate-300"><?= htmlspecialchars($labels[$n['type']] ?? $n['type']) ?></span>
                <?php if ($unread): ?><span class="text-[10px] px-2 py-0.5 rounded-full bg-red-600 text-white">Baru</span><?php endif; ?>
              </span>
              <span class="block text-xs text-slate-400 mt-0.5"><?= htmlspecialchars((string)$n['body']) ?></span>
            </span>
            <span class="text-[11px] text-slate-500 flex-shrink-0"><?= date('d/m H:i', strtotime($n['created_at'])) ?></span>
          </button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php $pagerPath = '/posko-notifikasi'; require __DIR__ . '/../backoffice/_pagination.php'; ?>
</div>
