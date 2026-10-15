<?php
use App\Core\PoskoSchedule;
use App\Models\PoskoReportType;

$field = 'w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-sm text-slate-100 outline-none focus:border-amber-500';
$val = fn(string $k) => htmlspecialchars((string)($input[$k] ?? ''));
$fieldVal = fn(string $k) => htmlspecialchars((string)($input['values'][$k] ?? ''));
$req = '<span class="text-red-400" title="Wajib saat dikirim">*</span>';
?>
<div class="space-y-6">

  <div class="polri-glass-card p-5 rounded-2xl border border-[#3B2F1C] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <span class="chip-digital inline-flex px-2 py-0.5 rounded text-[10px]"><?= htmlspecialchars($type['code']) ?></span>
      <h2 class="mt-1 text-lg font-black text-slate-100 font-heading"><?= htmlspecialchars($type['name']) ?></h2>
      <p class="text-xs text-slate-400">
        Unit pelapor: <b class="text-slate-200"><?= htmlspecialchars($report['satker_name'] ?? $unitName) ?></b>
        · <?= $report ? 'Draft #' . (int)$report['id'] : 'Draft baru' ?>
        <?php if ($report && $report['status'] === 'NEEDS_REVISION'): ?> · <span class="text-amber-300">Perlu perbaikan</span><?php endif; ?>
      </p>
    </div>
    <p class="text-[11px] text-slate-500"><?= $req ?> wajib diisi sebelum dikirim. Draft boleh disimpan belum lengkap.</p>
  </div>

  <?php if (!empty($revisionNote)): ?>
    <div class="p-4 rounded-2xl border border-amber-600/60 bg-amber-950/40 text-xs text-amber-100" role="alert">
      <p class="font-bold"><i class="fas fa-rotate-left mr-1"></i>
        <?= $revisionNote['action'] === 'revise' ? 'Alasan revisi' : 'Catatan koreksi' ?> dari
        <?= htmlspecialchars(trim(($revisionNote['actor_rank'] ?? '') . ' ' . ($revisionNote['actor_name'] ?? ''))) ?>
        (<?= htmlspecialchars($revisionNote['actor_satker_name'] ?? '-') ?>)</p>
      <p class="mt-1 whitespace-pre-line"><?= htmlspecialchars((string)$revisionNote['notes']) ?></p>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= $baseUrl ?>/posko-laporan/save" class="space-y-6">
    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= (int)($report['id'] ?? 0) ?>">
    <input type="hidden" name="report_type_id" value="<?= (int)$type['id'] ?>">

    <!-- Identitas & periode -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 grid sm:grid-cols-2 gap-4 text-xs">
      <h3 class="sm:col-span-2 text-[11px] font-bold uppercase tracking-wider text-amber-400">Identitas Laporan</h3>
      <?php if ($type['period_unit'] === 'NONE'): ?>
        <label class="block"><span class="block text-slate-300 mb-1">Waktu kejadian <?= $req ?></span>
          <input type="datetime-local" name="event_time" value="<?= $val('event_time') ?>" max="<?= date('Y-m-d\TH:i') ?>" class="<?= $field ?>"></label>
        <p class="self-end text-[11px] text-slate-400">Tenggat pelaporan: <?= (int)$type['deadline_hours'] ?> jam setelah kejadian.</p>
      <?php elseif ($type['period_unit'] === 'MONTH'): ?>
        <label class="block"><span class="block text-slate-300 mb-1">Bulan laporan <?= $req ?></span>
          <input type="month" name="period_month" value="<?= $val('period_month') ?>" max="<?= date('Y-m') ?>" class="<?= $field ?>"></label>
      <?php else: ?>
        <label class="block"><span class="block text-slate-300 mb-1"><?= $type['period_unit'] === 'WEEK' ? 'Tanggal dalam minggu laporan' : 'Tanggal laporan' ?> <?= $req ?></span>
          <input type="date" name="period_date" value="<?= $val('period_date') ?>" max="<?= date('Y-m-d') ?>" class="<?= $field ?>"></label>
        <?php if ($type['period_unit'] === 'WEEK'): ?>
          <p class="self-end text-[11px] text-slate-400">Periode mingguan dihitung Senin–Minggu dari tanggal yang dipilih.</p>
        <?php endif; ?>
      <?php endif; ?>
      <label class="block"><span class="block text-slate-300 mb-1">Wilayah kejadian <?= $req ?></span>
        <input name="region" maxlength="255" value="<?= $val('region') ?>" placeholder="Kecamatan / kelurahan / lokasi" class="<?= $field ?>"></label>
      <label class="block"><span class="block text-slate-300 mb-1">Prioritas</span>
        <select name="priority" class="<?= $field ?>">
          <?php foreach (['NORMAL' => 'Normal', 'HIGH' => 'Tinggi', 'CRITICAL' => 'Kritis'] as $k => $l): ?>
            <option value="<?= $k ?>" <?= ($input['priority'] ?? 'NORMAL') === $k ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select></label>
      <label class="block sm:col-span-2"><span class="block text-slate-300 mb-1">Nomor tiket aduan terkait <span class="text-slate-500">(opsional)</span></span>
        <input name="reference_ticket" maxlength="50" value="<?= $val('reference_ticket') ?>" placeholder="LP-POLRI-202610-0001" class="<?= $field ?> font-mono uppercase"></label>
    </section>

    <!-- Narasi -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 space-y-4 text-xs">
      <h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Situasi & Kegiatan</h3>
      <label class="block"><span class="block text-slate-300 mb-1">Ringkasan situasi <?= $req ?></span>
        <textarea name="situation_summary" rows="2" maxlength="5000" class="<?= $field ?>"><?= $val('situation_summary') ?></textarea></label>
      <label class="block"><span class="block text-slate-300 mb-1">Uraian kegiatan / kejadian <?= $req ?></span>
        <textarea name="description" rows="4" maxlength="5000" class="<?= $field ?>"><?= $val('description') ?></textarea></label>
      <div class="grid sm:grid-cols-3 gap-4">
        <label class="block"><span class="block text-slate-300 mb-1">Kendala & permasalahan</span>
          <textarea name="obstacles" rows="3" maxlength="5000" class="<?= $field ?>"><?= $val('obstacles') ?></textarea></label>
        <label class="block"><span class="block text-slate-300 mb-1">Tindak lanjut</span>
          <textarea name="follow_up" rows="3" maxlength="5000" class="<?= $field ?>"><?= $val('follow_up') ?></textarea></label>
        <label class="block"><span class="block text-slate-300 mb-1">Kebutuhan bantuan / eskalasi</span>
          <textarea name="assistance_needed" rows="3" maxlength="5000" class="<?= $field ?>"><?= $val('assistance_needed') ?></textarea></label>
      </div>
    </section>

    <!-- Data kuantitatif sesuai jenis -->
    <?php if ($fields): ?>
      <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 text-xs">
        <h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-4">Data <?= htmlspecialchars($type['name']) ?></h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <?php foreach ($fields as $f): ?>
            <?php $name = 'values[' . htmlspecialchars($f['field_key']) . ']'; ?>
            <label class="block <?= $f['type'] === 'textarea' ? 'sm:col-span-2 lg:col-span-3' : '' ?>">
              <span class="block text-slate-300 mb-1">
                <?= htmlspecialchars($f['label']) ?> <?= (int)$f['required'] ? $req : '' ?>
                <?php if ($f['unit_label']): ?><span class="text-slate-500">(<?= htmlspecialchars($f['unit_label']) ?>)</span><?php endif; ?>
              </span>
              <?php if ($f['type'] === 'number'): ?>
                <input type="number" name="<?= $name ?>" min="0" step="any" value="<?= $fieldVal($f['field_key']) ?>" class="<?= $field ?> font-mono">
              <?php elseif ($f['type'] === 'select'): ?>
                <select name="<?= $name ?>" class="<?= $field ?>">
                  <option value="">-- Pilih --</option>
                  <?php foreach (PoskoReportType::optionsOf($f) as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>" <?= ($input['values'][$f['field_key']] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php elseif ($f['type'] === 'textarea'): ?>
                <textarea name="<?= $name ?>" rows="3" maxlength="5000" class="<?= $field ?>"><?= $fieldVal($f['field_key']) ?></textarea>
              <?php elseif ($f['type'] === 'date'): ?>
                <input type="date" name="<?= $name ?>" value="<?= $fieldVal($f['field_key']) ?>" class="<?= $field ?>">
              <?php else: ?>
                <input name="<?= $name ?>" maxlength="255" value="<?= $fieldVal($f['field_key']) ?>" class="<?= $field ?>">
              <?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <div class="flex flex-wrap justify-end gap-3">
      <a href="<?= $baseUrl ?>/posko-laporan<?= $report ? '/show/' . (int)$report['id'] : '' ?>" class="px-4 py-2.5 rounded-xl text-slate-400 hover:text-slate-200 text-sm">Batal</a>
      <button name="next" value="draft" class="px-5 py-2.5 rounded-xl bg-[#241D14] border border-slate-700 text-slate-100 font-bold text-sm">
        <i class="fas fa-floppy-disk mr-1"></i> Simpan Draft
      </button>
      <button name="next" value="preview" class="btn-gold px-5 py-2.5 rounded-xl font-extrabold text-sm">
        <i class="fas fa-eye mr-1"></i> Simpan & Pratinjau
      </button>
    </div>
  </form>

  <?php if (!$report && (int)$type['allows_nil'] && $type['period_unit'] !== 'NONE'): ?>
    <!-- Nyatakan nihil (BRD 2.3.5): pernyataan resmi, langsung dikirim ke induk -->
    <form method="POST" action="<?= $baseUrl ?>/posko-laporan/nil" class="polri-glass-card rounded-2xl border border-teal-700/50 p-5 text-xs space-y-3"
          onsubmit="return confirm('Nyatakan NIHIL untuk periode ini? Pernyataan langsung dikirim ke unit induk.');">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="report_type_id" value="<?= (int)$type['id'] ?>">
      <h3 class="text-[11px] font-bold uppercase tracking-wider text-teal-300">Tidak ada kejadian? Nyatakan Nihil</h3>
      <p class="text-slate-400">Gunakan hanya bila memang tidak ada kejadian/kegiatan yang perlu dilaporkan. Laporan yang tidak dikirim tidak pernah dianggap nihil.</p>
      <div class="grid sm:grid-cols-3 gap-3">
        <?php if ($type['period_unit'] === 'MONTH'): ?>
          <label class="block"><span class="block text-slate-300 mb-1">Bulan</span><input type="month" name="period_month" value="<?= $val('period_month') ?>" max="<?= date('Y-m') ?>" class="<?= $field ?>"></label>
        <?php else: ?>
          <label class="block"><span class="block text-slate-300 mb-1">Tanggal periode</span><input type="date" name="period_date" value="<?= $val('period_date') ?>" max="<?= date('Y-m-d') ?>" class="<?= $field ?>"></label>
        <?php endif; ?>
        <label class="block sm:col-span-2"><span class="block text-slate-300 mb-1">Alasan / keterangan nihil <span class="text-red-400">*</span></span>
          <input name="nil_reason" required maxlength="1000" placeholder="Mis. tidak ada kejadian menonjol; situasi aman terkendali" class="<?= $field ?>"></label>
      </div>
      <button class="px-4 py-2 rounded-xl border border-teal-600/70 bg-teal-950/50 text-teal-100 font-bold"><i class="fas fa-circle-minus mr-1"></i> Nyatakan Nihil</button>
    </form>
  <?php endif; ?>

  <!-- Lampiran (tersedia setelah draft tersimpan) -->
  <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5 text-xs">
    <h3 class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-3">Lampiran Foto / Dokumen</h3>
    <?php if (!$report): ?>
      <p class="text-slate-500">Simpan draft terlebih dahulu untuk menambahkan lampiran.</p>
    <?php else: ?>
      <ul class="space-y-2 mb-4">
        <?php if (!$attachments): ?><li class="text-slate-500">Belum ada lampiran.</li><?php endif; ?>
        <?php foreach ($attachments as $a): ?>
          <li class="flex items-center gap-3 p-2 rounded-lg bg-[#0C0A08] border border-slate-800">
            <i class="fas fa-paperclip text-amber-400"></i>
            <a href="<?= $baseUrl ?>/posko-laporan/attachment/<?= (int)$a['id'] ?>" target="_blank" class="flex-1 truncate text-slate-200 hover:underline"><?= htmlspecialchars($a['file_name_original'] ?: 'lampiran') ?></a>
            <span class="text-[10px] text-slate-500 font-mono"><?= number_format($a['size_bytes'] / 1024, 1) ?> KB</span>
            <form method="POST" action="<?= $baseUrl ?>/posko-laporan/delete-attachment" onsubmit="return confirm('Hapus lampiran ini?');">
              <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
              <input type="hidden" name="attachment_id" value="<?= (int)$a['id'] ?>">
              <button class="text-red-300 hover:text-red-200 px-2" aria-label="Hapus lampiran"><i class="fas fa-trash"></i></button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
      <form method="POST" action="<?= $baseUrl ?>/posko-laporan/upload" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
        <label class="sr-only" for="files">Berkas lampiran</label>
        <input id="files" type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf,video/mp4"
               class="text-slate-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-[#241D14] file:text-slate-100">
        <button class="px-4 py-2 rounded-xl bg-[#241D14] border border-slate-700 text-slate-100 font-bold"><i class="fas fa-upload mr-1"></i> Unggah</button>
        <span class="text-[10px] text-slate-500">JPG/PNG/WEBP/PDF/MP4, maks. 20 MB per berkas, maks. 10 lampiran.</span>
      </form>
    <?php endif; ?>
  </section>
</div>
