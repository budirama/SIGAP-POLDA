<?php
/**
 * Navigasi halaman daftar laporan.
 * Butuh: $pager (hasil Report::paginateFilteredReports), $pagerPath (mis. '/spkt'), $baseUrl.
 * Parameter query lain (filter, pencarian) dipertahankan.
 */
if (($pager['pages'] ?? 1) <= 1) {
    return;
}

$pageUrl = function (int $page) use ($baseUrl, $pagerPath): string {
    $query = array_filter(
        array_merge(array_diff_key($_GET, ['page' => true]), ['page' => $page]),
        fn($v) => is_scalar($v) && $v !== ''
    );
    return htmlspecialchars($baseUrl . $pagerPath . '?' . http_build_query($query));
};

$current = (int)$pager['page'];
$last = (int)$pager['pages'];
$from = ($current - 1) * (int)$pager['perPage'] + 1;
$to = min($current * (int)$pager['perPage'], (int)$pager['total']);
$window = range(max(1, $current - 2), min($last, $current + 2));
$btn = 'min-w-[2.25rem] px-3 py-2 rounded-lg border text-center transition-colors';
?>
<nav class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-800 text-xs" aria-label="Navigasi halaman">
  <p class="text-slate-400">
    Menampilkan <b class="text-slate-200"><?= $from ?>–<?= $to ?></b> dari <b class="text-slate-200"><?= (int)$pager['total'] ?></b> laporan
  </p>
  <div class="flex flex-wrap items-center gap-1.5">
    <?php if ($current > 1): ?>
      <a href="<?= $pageUrl($current - 1) ?>" class="<?= $btn ?> border-slate-700 text-slate-300 hover:bg-[#1B1610]" aria-label="Halaman sebelumnya"><i class="fas fa-chevron-left"></i></a>
    <?php endif; ?>

    <?php if ($window[0] > 1): ?>
      <a href="<?= $pageUrl(1) ?>" class="<?= $btn ?> border-slate-700 text-slate-300 hover:bg-[#1B1610]">1</a>
      <?php if ($window[0] > 2): ?><span class="px-1 text-slate-500">…</span><?php endif; ?>
    <?php endif; ?>

    <?php foreach ($window as $p): ?>
      <?php if ($p === $current): ?>
        <span class="<?= $btn ?> border-amber-500/60 bg-amber-500/15 text-amber-300 font-bold" aria-current="page"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= $pageUrl($p) ?>" class="<?= $btn ?> border-slate-700 text-slate-300 hover:bg-[#1B1610]"><?= $p ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if (end($window) < $last): ?>
      <?php if (end($window) < $last - 1): ?><span class="px-1 text-slate-500">…</span><?php endif; ?>
      <a href="<?= $pageUrl($last) ?>" class="<?= $btn ?> border-slate-700 text-slate-300 hover:bg-[#1B1610]"><?= $last ?></a>
    <?php endif; ?>

    <?php if ($current < $last): ?>
      <a href="<?= $pageUrl($current + 1) ?>" class="<?= $btn ?> border-slate-700 text-slate-300 hover:bg-[#1B1610]" aria-label="Halaman berikutnya"><i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>
  </div>
</nav>
