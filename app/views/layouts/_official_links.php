<?php
/**
 * Tautan portal resmi Polri (ditampilkan di bar atas & footer).
 * $officialLinksVariant: 'topbar' | 'footer'
 */
$officialLinks = [
    ['https://www.polri.go.id/',           'Polri.go.id',        'fa-building-shield'],
    ['https://penerimaan.polri.go.id/',    'Penerimaan Polri',   'fa-user-plus'],
    ['https://humas.polri.go.id/',         'Humas Polri',        'fa-bullhorn'],
    ['https://propam.polri.go.id/',        'Propam Polri',       'fa-scale-balanced'],
    ['https://korlantas.polri.go.id/',     'Korlantas Polri',    'fa-traffic-light'],
];
$variant = $officialLinksVariant ?? 'footer';
?>
<?php if ($variant === 'topbar'): ?>
  <nav aria-label="Portal resmi Polri" class="hidden lg:flex items-center gap-3">
    <?php foreach (array_slice($officialLinks, 0, 3) as [$href, $label]): ?>
      <a href="<?= $href ?>" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-amber-300 transition-colors"><?= htmlspecialchars($label) ?></a>
      <span class="text-slate-700" aria-hidden="true">|</span>
    <?php endforeach; ?>
  </nav>
<?php else: ?>
  <ul class="space-y-2 text-xs">
    <?php foreach ($officialLinks as [$href, $label, $icon]): ?>
      <li>
        <a href="<?= $href ?>" target="_blank" rel="noopener noreferrer" class="group inline-flex items-center gap-2 hover:text-amber-400 transition-colors">
          <i class="fas <?= $icon ?> w-4 text-amber-500/70 group-hover:text-amber-400"></i>
          <span><?= htmlspecialchars($label) ?></span>
          <i class="fas fa-arrow-up-right-from-square text-[9px] text-slate-600 group-hover:text-amber-400"></i>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
