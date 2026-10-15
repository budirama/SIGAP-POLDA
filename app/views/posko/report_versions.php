<?php
$labels = [
    'period_start' => 'Awal periode', 'period_end' => 'Akhir periode', 'event_time' => 'Waktu kejadian', 'region' => 'Wilayah kejadian',
    'situation_summary' => 'Ringkasan situasi', 'description' => 'Uraian', 'obstacles' => 'Kendala', 'follow_up' => 'Tindak lanjut',
    'assistance_needed' => 'Kebutuhan bantuan', 'priority' => 'Prioritas', 'reference_ticket' => 'Tiket aduan terkait',
];
foreach ($fields as $f) {
    $labels['v:' . $f['field_key']] = $f['label'];
}
$flatten = function (array $snap): array {
    $out = [];
    foreach ($snap['general'] ?? [] as $k => $v) {
        if (in_array($k, ['period_key', 'deadline_at'], true)) {
            continue;
        }
        $out[$k] = $v;
    }
    foreach ($snap['values'] ?? [] as $k => $v) {
        $out['v:' . $k] = is_numeric($v) ? rtrim(rtrim((string)$v, '0'), '.') : $v;
    }
    $out['attachments'] = implode(', ', $snap['attachments'] ?? []);
    return $out;
};
$labels['attachments'] = 'Lampiran';
$snaps = array_map(fn($v) => $v + ['data' => $flatten(json_decode($v['snapshot_json'], true) ?: [])], $versions);
?>
<div class="space-y-4">
  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C]">
    <h2 class="text-lg font-black text-slate-100 font-heading">Riwayat Versi</h2>
    <p class="text-xs text-slate-400"><span class="font-mono text-amber-300"><?= htmlspecialchars((string)$report['report_number']) ?></span> · <?= htmlspecialchars($report['type_name']) ?> · <?= htmlspecialchars($report['satker_name']) ?></p>
    <p class="text-[11px] text-slate-500 mt-1">Setiap pengiriman menyimpan snapshot isi laporan. Baris berwarna menandai perubahan dari versi sebelumnya.</p>
  </div>

  <?php foreach ($snaps as $i => $v): ?>
    <?php $prev = $snaps[$i - 1]['data'] ?? null; ?>
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
      <header class="px-5 py-3 border-b border-[#3B2F1C] flex flex-wrap items-center justify-between gap-2 text-xs">
        <span class="font-bold text-slate-100">Versi <?= (int)$v['version'] ?></span>
        <span class="text-slate-400">dikirim <?= date('d/m/Y H:i', strtotime($v['created_at'])) ?> WIB oleh <?= htmlspecialchars((string)$v['changed_by_name']) ?></span>
      </header>
      <?php if ($v['reason']): ?>
        <p class="px-5 pt-3 text-xs text-amber-200"><b>Alasan revisi:</b> <?= htmlspecialchars($v['reason']) ?></p>
      <?php endif; ?>
      <table class="w-full text-xs">
        <tbody class="divide-y divide-slate-800/80">
          <?php foreach ($labels as $k => $label): ?>
            <?php
            $val = (string)($v['data'][$k] ?? '');
            $changed = $prev !== null && $val !== (string)($prev[$k] ?? '');
            if ($val === '' && !$changed) continue;
            ?>
            <tr class="<?= $changed ? 'bg-amber-500/10' : '' ?>">
              <th class="p-3 text-left font-normal text-slate-400 w-1/3"><?= htmlspecialchars($label) ?></th>
              <td class="p-3 text-slate-100 whitespace-pre-line">
                <?= $val !== '' ? htmlspecialchars($val) : '<span class="text-slate-600">—</span>' ?>
                <?php if ($changed): ?>
                  <span class="block mt-1 text-[11px] text-slate-500 line-through"><?= htmlspecialchars((string)($prev[$k] ?? '—')) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endforeach; ?>

  <a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$report['id'] ?>" class="inline-flex items-center gap-1 text-xs text-amber-400 hover:text-amber-300"><i class="fas fa-arrow-left text-[10px]"></i> Kembali ke laporan</a>
</div>
