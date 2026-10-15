<?php
use App\Core\PoskoSchedule;

require __DIR__ . '/_status.php';
$field = 'w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-sm text-slate-100 outline-none focus:border-amber-500';
$val = fn(string $k) => htmlspecialchars((string)($input[$k] ?? ''));
$fmt = fn($v) => $v === null ? '—' : rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ',');
?>
<div class="space-y-6">

  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C]">
    <span class="chip-digital inline-flex px-2 py-0.5 rounded text-[10px]">POSKO // REKAP WILAYAH</span>
    <h2 class="mt-1 text-lg font-black text-slate-100 font-heading"><?= $recap ? 'Ubah Narasi Rekap' : 'Susun Rekap Wilayah' ?></h2>
    <p class="text-xs text-slate-400">Nilai agregat dihitung otomatis dari laporan sumber yang telah disetujui (unit anak) dan laporan unit Anda sendiri yang telah dikirim. Narasi & analisis ditulis petugas dan disimpan terpisah dari angka.</p>
  </div>

  <?php if (!$recap): ?>
    <!-- Pilih jenis & periode -->
    <form method="GET" action="<?= $baseUrl ?>/posko-rekap/create" class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 grid sm:grid-cols-3 gap-3 text-xs items-end">
      <label class="block"><span class="block text-slate-300 mb-1">Jenis laporan</span>
        <select name="type" class="<?= $field ?>" onchange="this.form.submit()">
          <option value="">-- Pilih jenis --</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $type && (int)$type['id'] === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select></label>
      <?php if ($type): ?>
        <?php if ($type['period_unit'] === 'MONTH'): ?>
          <label class="block"><span class="block text-slate-300 mb-1">Bulan</span>
            <input type="month" name="period_month" value="<?= $val('period_month') ?>" max="<?= date('Y-m') ?>" class="<?= $field ?>"></label>
        <?php else: ?>
          <label class="block"><span class="block text-slate-300 mb-1"><?= $type['period_unit'] === 'WEEK' ? 'Tanggal dalam minggu' : 'Tanggal' ?></span>
            <input type="date" name="period_date" value="<?= $val('period_date') ?>" max="<?= date('Y-m-d') ?>" class="<?= $field ?>"></label>
        <?php endif; ?>
        <button class="px-4 py-2.5 rounded-xl bg-[#241D14] border border-slate-700 text-slate-100 font-bold"><i class="fas fa-calculator mr-1"></i> Hitung Pratinjau</button>
      <?php endif; ?>
    </form>
  <?php endif; ?>

  <?php if ($type && $period): ?>
    <p class="text-xs text-slate-400">Periode: <b class="text-slate-200"><?= htmlspecialchars(PoskoSchedule::label($type['period_unit'], $period['start'], $period['end'])) ?></b></p>

    <?php if ($existing): ?>
      <div class="p-4 rounded-2xl border border-sky-700/60 bg-sky-950/40 text-xs text-sky-100">
        Rekap unit Anda untuk periode ini sudah ada.
        <a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$existing['id'] ?>" class="underline font-bold">Buka rekap</a>
      </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-6 items-start">
      <!-- Sumber -->
      <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
        <h3 class="px-5 py-3 border-b border-[#3B2F1C] text-[11px] font-bold uppercase tracking-wider text-amber-400">Laporan Sumber (<?= count($sources) ?>)</h3>
        <ul class="divide-y divide-slate-800/80 text-xs">
          <?php if (!$sources): ?><li class="p-5 text-slate-500">Belum ada laporan sumber yang disetujui untuk periode ini.</li><?php endif; ?>
          <?php foreach ($sources as $s): ?>
            <li class="px-5 py-3 flex items-center gap-3">
              <span class="flex-1 min-w-0">
                <a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$s['id'] ?>" class="font-mono text-amber-300 hover:underline"><?= htmlspecialchars((string)$s['report_number']) ?></a>
                <span class="block text-slate-300"><?= htmlspecialchars($s['satker_name']) ?>
                  <?php if ((int)$s['is_recap']): ?><span class="text-[10px] text-teal-300">· rekap</span><?php endif; ?>
                  <?php if ((int)$s['is_nil']): ?><span class="text-[10px] text-teal-300">· nihil</span><?php endif; ?>
                </span>
              </span>
              <?php if (isset($s['source_version']) && (int)$s['source_version'] !== (int)$s['version']): ?>
                <span class="text-[10px] text-amber-300">direvisi (v<?= (int)$s['source_version'] ?>→v<?= (int)$s['version'] ?>)</span>
              <?php endif; ?>
              <?= $poskoStatusBadge($s['status']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>

      <!-- Agregat -->
      <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
        <h3 class="px-5 py-3 border-b border-[#3B2F1C] text-[11px] font-bold uppercase tracking-wider text-amber-400">Nilai Agregat (otomatis)</h3>
        <table class="w-full text-xs">
          <tbody class="divide-y divide-slate-800/80">
            <?php foreach ($fields as $f): ?>
              <?php if ($f['type'] !== 'number' || $f['aggregate'] === 'NONE') continue; ?>
              <tr>
                <th class="p-3 text-left font-normal text-slate-400"><?= htmlspecialchars($f['label']) ?> <span class="text-slate-600">(<?= ['SUM' => 'jumlah', 'AVG' => 'rata-rata', 'MAX' => 'maksimum'][$f['aggregate']] ?>)</span></th>
                <td class="p-3 text-right font-mono font-bold text-slate-100"><?= $fmt($values[$f['field_key']] ?? null) ?> <span class="text-slate-500 font-normal"><?= htmlspecialchars((string)$f['unit_label']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>

    <?php if (!$existing && ($sources || $recap)): ?>
      <!-- Narasi petugas -->
      <form method="POST" action="<?= $baseUrl ?>/posko-rekap/<?= $recap ? 'save' : 'store' ?>" class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 space-y-4 text-xs">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <?php if ($recap): ?>
          <input type="hidden" name="id" value="<?= (int)$recap['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="report_type_id" value="<?= (int)$type['id'] ?>">
          <input type="hidden" name="period_start" value="<?= htmlspecialchars($period['start']) ?>">
        <?php endif; ?>
        <h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Narasi & Analisis Petugas</h3>
        <div class="grid sm:grid-cols-2 gap-4">
          <label class="block"><span class="block text-slate-300 mb-1">Wilayah</span>
            <input name="region" maxlength="255" value="<?= $val('region') ?: htmlspecialchars($authUser['satker_name'] ?? '') ?>" class="<?= $field ?>"></label>
          <label class="block"><span class="block text-slate-300 mb-1">Prioritas</span>
            <select name="priority" class="<?= $field ?>">
              <?php foreach (['NORMAL' => 'Normal', 'HIGH' => 'Tinggi', 'CRITICAL' => 'Kritis'] as $k => $l): ?>
                <option value="<?= $k ?>" <?= ($input['priority'] ?? 'NORMAL') === $k ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select></label>
        </div>
        <label class="block"><span class="block text-slate-300 mb-1">Ringkasan situasi wilayah <span class="text-red-400">*</span></span>
          <textarea name="situation_summary" rows="2" maxlength="5000" class="<?= $field ?>"><?= $val('situation_summary') ?></textarea></label>
        <label class="block"><span class="block text-slate-300 mb-1">Analisis / narasi rekap <span class="text-red-400">*</span></span>
          <textarea name="description" rows="4" maxlength="5000" class="<?= $field ?>"><?= $val('description') ?></textarea></label>
        <div class="grid sm:grid-cols-3 gap-4">
          <label class="block"><span class="block text-slate-300 mb-1">Kendala</span><textarea name="obstacles" rows="3" class="<?= $field ?>"><?= $val('obstacles') ?></textarea></label>
          <label class="block"><span class="block text-slate-300 mb-1">Tindak lanjut</span><textarea name="follow_up" rows="3" class="<?= $field ?>"><?= $val('follow_up') ?></textarea></label>
          <label class="block"><span class="block text-slate-300 mb-1">Kebutuhan bantuan</span><textarea name="assistance_needed" rows="3" class="<?= $field ?>"><?= $val('assistance_needed') ?></textarea></label>
        </div>
        <div class="flex justify-end">
          <button class="btn-gold px-5 py-2.5 rounded-xl font-extrabold text-sm"><i class="fas fa-floppy-disk mr-1"></i> <?= $recap ? 'Simpan Narasi' : 'Simpan Draft Rekap' ?></button>
        </div>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
