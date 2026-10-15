<?php
use App\Models\PoskoReportType;

$field = 'w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-slate-100 outline-none focus:border-amber-500';
$t = $selected ?? [
    'id' => 0, 'code' => '', 'name' => '', 'kind' => 'ROUTINE', 'period_unit' => 'DAY', 'deadline_offset_days' => 0,
    'deadline_time' => '23:59:00', 'deadline_hours' => null, 'requires_approval' => 1, 'allows_nil' => 0,
    'applies_to_levels' => '1,2,3', 'description' => '', 'sort_order' => 0, 'is_active' => 1,
];
$levels = explode(',', (string)$t['applies_to_levels']);
?>
<div class="grid lg:grid-cols-[280px_1fr] gap-6 items-start">

  <!-- Daftar jenis -->
  <aside class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
    <header class="px-4 py-3 border-b border-[#3B2F1C] flex items-center justify-between">
      <h2 class="text-sm font-bold text-slate-100">Jenis Laporan</h2>
      <a href="<?= $baseUrl ?>/posko-jenis" class="text-[11px] text-amber-400 hover:text-amber-300"><i class="fas fa-plus"></i> Baru</a>
    </header>
    <ul class="divide-y divide-[#3B2F1C]/60 text-xs">
      <?php foreach ($types as $row): ?>
        <li>
          <a href="<?= $baseUrl ?>/posko-jenis/index/<?= (int)$row['id'] ?>"
             class="block px-4 py-3 hover:bg-[#1B1610] <?= $selected && (int)$selected['id'] === (int)$row['id'] ? 'bg-amber-500/10' : '' ?>">
            <span class="block font-bold <?= (int)$row['is_active'] ? 'text-slate-100' : 'text-slate-500 line-through' ?>"><?= htmlspecialchars($row['name']) ?></span>
            <span class="block text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($row['code']) ?> · <?= PoskoReportType::PERIOD_UNITS[$row['period_unit']] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </aside>

  <div class="space-y-6">
    <!-- Pengaturan jenis -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5">
      <h2 class="text-sm font-bold text-slate-100 mb-4"><?= $selected ? 'Ubah ' . htmlspecialchars($t['name']) : 'Jenis Laporan Baru' ?></h2>
      <form method="POST" action="<?= $baseUrl ?>/posko-jenis/save-type" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <label class="block"><span class="block text-slate-300 mb-1">Kode</span>
          <input name="code" required maxlength="40" value="<?= htmlspecialchars($t['code']) ?>" class="<?= $field ?> font-mono uppercase"></label>
        <label class="block sm:col-span-2"><span class="block text-slate-300 mb-1">Nama</span>
          <input name="name" required maxlength="120" value="<?= htmlspecialchars($t['name']) ?>" class="<?= $field ?>"></label>
        <label class="block"><span class="block text-slate-300 mb-1">Kategori</span>
          <select name="kind" class="<?= $field ?>">
            <?php foreach (PoskoReportType::KINDS as $k => $l): ?><option value="<?= $k ?>" <?= $t['kind'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
          </select></label>
        <label class="block"><span class="block text-slate-300 mb-1">Periode</span>
          <select name="period_unit" class="<?= $field ?>">
            <?php foreach (PoskoReportType::PERIOD_UNITS as $k => $l): ?><option value="<?= $k ?>" <?= $t['period_unit'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
          </select></label>
        <label class="block"><span class="block text-slate-300 mb-1">Urutan tampil</span>
          <input type="number" name="sort_order" value="<?= (int)$t['sort_order'] ?>" class="<?= $field ?>"></label>
        <label class="block"><span class="block text-slate-300 mb-1">Tenggat: + hari setelah akhir periode</span>
          <input type="number" name="deadline_offset_days" min="0" max="31" value="<?= (int)$t['deadline_offset_days'] ?>" class="<?= $field ?>"></label>
        <label class="block"><span class="block text-slate-300 mb-1">Tenggat: pukul (WIB)</span>
          <input type="time" name="deadline_time" value="<?= htmlspecialchars(substr((string)$t['deadline_time'], 0, 5)) ?>" class="<?= $field ?>"></label>
        <label class="block"><span class="block text-slate-300 mb-1">Tanpa periode: tenggat (jam setelah kejadian)</span>
          <input type="number" name="deadline_hours" min="1" max="720" value="<?= htmlspecialchars((string)$t['deadline_hours']) ?>" class="<?= $field ?>"></label>
        <fieldset class="sm:col-span-2 lg:col-span-3 flex flex-wrap gap-4 text-slate-300">
          <legend class="text-slate-300 mb-1">Berlaku untuk tingkat</legend>
          <?php foreach (['1' => 'Polsek', '2' => 'Polres', '3' => 'Polda', '4' => 'Pusat'] as $k => $l): ?>
            <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="levels[]" value="<?= $k ?>" <?= in_array($k, $levels, true) ? 'checked' : '' ?> class="accent-amber-500"> <?= $l ?></label>
          <?php endforeach; ?>
        </fieldset>
        <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap gap-4 text-slate-300">
          <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="requires_approval" value="1" <?= (int)$t['requires_approval'] ? 'checked' : '' ?> class="accent-amber-500"> Wajib disetujui sebelum diteruskan</label>
          <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="allows_nil" value="1" <?= (int)$t['allows_nil'] ? 'checked' : '' ?> class="accent-amber-500"> Boleh dinyatakan nihil</label>
          <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="is_active" value="1" <?= (int)$t['is_active'] ? 'checked' : '' ?> class="accent-amber-500"> Aktif</label>
        </div>
        <label class="block sm:col-span-2 lg:col-span-3"><span class="block text-slate-300 mb-1">Keterangan</span>
          <input name="description" maxlength="255" value="<?= htmlspecialchars((string)$t['description']) ?>" class="<?= $field ?>"></label>
        <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
          <button class="btn-gold px-5 py-2.5 rounded-xl font-extrabold"><i class="fas fa-floppy-disk mr-1"></i> Simpan Jenis</button>
        </div>
      </form>
    </section>

    <?php if ($selected): ?>
      <!-- Isian -->
      <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 text-xs">
        <h2 class="text-sm font-bold text-slate-100 mb-1">Isian Data — <?= htmlspecialchars($selected['name']) ?></h2>
        <p class="text-[11px] text-slate-400 mb-4">Kunci isian tidak dapat diubah setelah dibuat agar nilai laporan lama tetap terbaca. Nonaktifkan isian yang tidak dipakai lagi.</p>

        <div class="space-y-3">
          <?php foreach (array_merge($fields, [null]) as $f): ?>
            <details class="rounded-xl border border-slate-800 bg-[#0C0A08]" <?= $f === null && !$fields ? 'open' : '' ?>>
              <summary class="px-4 py-3 cursor-pointer flex items-center gap-3">
                <?php if ($f): ?>
                  <span class="flex-1 <?= (int)$f['is_active'] ? 'text-slate-100' : 'text-slate-500 line-through' ?>"><?= htmlspecialchars($f['label']) ?></span>
                  <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($f['field_key']) ?> · <?= PoskoReportType::FIELD_TYPES[$f['type']] ?><?= (int)$f['required'] ? ' · wajib' : '' ?><?= $f['aggregate'] !== 'NONE' ? ' · ' . PoskoReportType::AGGREGATES[$f['aggregate']] : '' ?></span>
                <?php else: ?>
                  <span class="flex-1 text-amber-300 font-bold"><i class="fas fa-plus mr-1"></i> Tambah isian</span>
                <?php endif; ?>
              </summary>
              <form method="POST" action="<?= $baseUrl ?>/posko-jenis/save-field" class="px-4 pb-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="report_type_id" value="<?= (int)$selected['id'] ?>">
                <input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
                <label class="block"><span class="block text-slate-300 mb-1">Kunci</span>
                  <input name="field_key" maxlength="50" value="<?= htmlspecialchars($f['field_key'] ?? '') ?>" <?= $f ? 'disabled' : 'required' ?> placeholder="jumlah_kejadian" class="<?= $field ?> font-mono"></label>
                <label class="block sm:col-span-2"><span class="block text-slate-300 mb-1">Label</span>
                  <input name="label" required maxlength="120" value="<?= htmlspecialchars($f['label'] ?? '') ?>" class="<?= $field ?>"></label>
                <label class="block"><span class="block text-slate-300 mb-1">Satuan</span>
                  <input name="unit_label" maxlength="30" value="<?= htmlspecialchars($f['unit_label'] ?? '') ?>" placeholder="kejadian" class="<?= $field ?>"></label>
                <label class="block"><span class="block text-slate-300 mb-1">Tipe</span>
                  <select name="type" class="<?= $field ?>">
                    <?php foreach (PoskoReportType::FIELD_TYPES as $k => $l): ?><option value="<?= $k ?>" <?= ($f['type'] ?? 'number') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                  </select></label>
                <label class="block"><span class="block text-slate-300 mb-1">Rekap</span>
                  <select name="aggregate" class="<?= $field ?>">
                    <?php foreach (PoskoReportType::AGGREGATES as $k => $l): ?><option value="<?= $k ?>" <?= ($f['aggregate'] ?? 'NONE') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                  </select></label>
                <label class="block"><span class="block text-slate-300 mb-1">Urutan</span>
                  <input type="number" name="sort_order" value="<?= (int)($f['sort_order'] ?? (count($fields) + 1) * 10) ?>" class="<?= $field ?>"></label>
                <div class="flex flex-col justify-end gap-1 text-slate-300">
                  <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="required" value="1" <?= (int)($f['required'] ?? 0) ? 'checked' : '' ?> class="accent-amber-500"> Wajib</label>
                  <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="is_active" value="1" <?= (int)($f['is_active'] ?? 1) ? 'checked' : '' ?> class="accent-amber-500"> Aktif</label>
                </div>
                <label class="block sm:col-span-2 lg:col-span-4"><span class="block text-slate-300 mb-1">Opsi (khusus tipe Pilihan, satu per baris)</span>
                  <textarea name="options" rows="2" class="<?= $field ?>"><?= htmlspecialchars($f['options'] ?? '') ?></textarea></label>
                <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                  <button class="px-4 py-2 rounded-xl bg-[#241D14] border border-slate-700 text-slate-100 font-bold">Simpan Isian</button>
                </div>
              </form>
            </details>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
</div>
