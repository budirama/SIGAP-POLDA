<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Laporan Posko - <?= htmlspecialchars($report['report_number'] ?: ('Draft-' . $report['id'])) ?> (v<?= (int)$report['version'] ?>)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      body { background: white !important; color: black !important; font-size: 11pt; }
      .no-print { display: none !important; }
      .print-shadow { box-shadow: none !important; border: none !important; padding: 0 !important; }
      .page-break { page-break-after: always; }
      a { text-decoration: none !important; color: inherit !important; }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-8 font-serif">

  <!-- Top Floating Bar (No-Print) -->
  <div class="max-w-4xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 no-print bg-[#14100C] text-slate-100 p-4 rounded-xl shadow-lg border border-amber-500/40 font-sans">
    <div class="flex items-center gap-3">
      <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
      <div>
        <p class="text-xs font-bold text-amber-300 tracking-wider uppercase">DOKUMEN RESMI PELAPORAN POSKO PRESISI POLRI</p>
        <p class="text-[11px] text-slate-400">Nomor: <?= htmlspecialchars($report['report_number'] ?: 'DRAFT #' . $report['id']) ?> · Versi <?= (int)$report['version'] ?></p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-xs transition flex items-center gap-1.5 shadow">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        Cetak / Simpan PDF
      </button>
      <a href="<?= $baseUrl ?>/posko-laporan/show/<?= (int)$report['id'] ?>" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold rounded-lg text-xs transition">
        Kembali
      </a>
    </div>
  </div>

  <!-- Document Sheet (A4 Style) -->
  <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 shadow-2xl rounded border border-slate-300 print-shadow relative">

    <?php
      $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      $formatTgl = fn(?string $dt): string => $dt ? (date('j', strtotime($dt)) . ' ' . $bulan[(int)date('n', strtotime($dt))] . ' ' . date('Y', strtotime($dt)) . ' ' . date('H:i', strtotime($dt)) . ' WIB') : '-';
      $formatTglSaja = fn(?string $dt): string => $dt ? (date('j', strtotime($dt)) . ' ' . $bulan[(int)date('n', strtotime($dt))] . ' ' . date('Y', strtotime($dt))) : '-';
      $late = $report['deadline_at'] && (($report['submitted_at'] ?: date('Y-m-d H:i:s')) > $report['deadline_at']);
    ?>

    <!-- Official Kop Surat Polri -->
    <div class="border-b-4 border-double border-slate-900 pb-3 mb-6 text-center relative">
      <div class="flex items-center justify-center gap-4 mb-2">
        <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Tribrata Polri" class="w-20 h-auto object-contain">
        <div>
          <h2 class="text-xs font-black tracking-widest uppercase text-slate-900 font-sans">KEPOLISIAN NEGARA REPUBLIK INDONESIA</h2>
          <h1 class="text-base font-black tracking-wider uppercase text-slate-900 font-sans"><?= htmlspecialchars($report['satker_name'] ?? 'SATUAN KERJA POLRI') ?></h1>
          <p class="text-[11px] font-sans text-slate-600">POS KOMANDO & KOORDINASI OPERASIONAL PRESISI • LAPORAN BERJENJANG</p>
        </div>
      </div>
    </div>

    <!-- Klasifikasi & Judul Dokumen -->
    <div class="flex justify-between items-center text-xs font-sans mb-3 text-slate-600 border-b border-slate-200 pb-2">
      <div>
        <span>KLASIFIKASI: </span>
        <b class="text-slate-900"><?= $report['priority'] === 'URGENT' ? 'SANGAT RAHASIA / SEGERA' : ($report['priority'] === 'HIGH' ? 'RAHASIA / PENTING' : 'BIASA / KILAT') ?></b>
      </div>
      <div class="text-right">
        <span>PENANDA VERSI: </span>
        <b class="text-slate-900 font-mono">v<?= (int)$report['version'] ?></b>
      </div>
    </div>

    <div class="text-center mb-6">
      <h3 class="text-base font-black underline uppercase tracking-wider font-sans">
        <?= (int)$report['is_recap'] ? 'REKAPITULASI LAPORAN POSKO' : 'LAPORAN SITUASI DAN OPERASIONAL POSKO' ?>
      </h3>
      <p class="text-xs font-mono font-bold mt-1">
        NOMOR: <?= htmlspecialchars($report['report_number'] ?: ('[DRAFT BELUM DIKIRIM #' . $report['id'] . ']')) ?>
      </p>
      <p class="text-xs font-sans text-slate-700 mt-0.5">
        <?= htmlspecialchars($report['type_name']) ?>
        <?php if ((int)$report['is_nil']): ?><span class="font-bold text-teal-800">[LAPORAN NIHIL]</span><?php endif; ?>
        <?php if ((int)$report['is_recap']): ?><span class="font-bold text-amber-800">[REKAP BERJENJANG]</span><?php endif; ?>
      </p>
    </div>

    <!-- Bagian I: Identitas & Waktu -->
    <section class="mb-5 font-sans">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b-2 border-slate-900 pb-1 mb-2">I. IDENTITAS & WAKTU PELAPORAN</h4>
      <table class="w-full text-xs border border-slate-300">
        <tbody class="divide-y divide-slate-300">
          <tr>
            <th class="p-2 bg-slate-100 text-left w-1/4 font-semibold text-slate-700">Satuan Kerja Pelapor</th>
            <td class="p-2 font-bold text-slate-900 w-1/4"><?= htmlspecialchars($report['satker_name']) ?> (<?= htmlspecialchars($report['satker_code']) ?>)</td>
            <th class="p-2 bg-slate-100 text-left w-1/4 font-semibold text-slate-700">Jenis & Bentuk Laporan</th>
            <td class="p-2 text-slate-900 w-1/4"><?= htmlspecialchars($report['type_name']) ?> (<?= (int)$report['is_recap'] ? 'Rekapitulasi' : 'Laporan Sumber' ?>)</td>
          </tr>
          <tr>
            <th class="p-2 bg-slate-100 text-left font-semibold text-slate-700">Periode / Waktu Kejadian</th>
            <td class="p-2 font-bold text-slate-900">
              <?= htmlspecialchars($report['period_start']
                  ? \App\Core\PoskoSchedule::label($report['period_unit'], $report['period_start'], $report['period_end'])
                  : ($report['event_time'] ? date('d/m/Y H:i', strtotime($report['event_time'])) . ' WIB' : '-')) ?>
            </td>
            <th class="p-2 bg-slate-100 text-left font-semibold text-slate-700">Tingkat Prioritas</th>
            <td class="p-2 font-bold text-slate-900"><?= htmlspecialchars($report['priority']) ?></td>
          </tr>
          <tr>
            <th class="p-2 bg-slate-100 text-left font-semibold text-slate-700">Batas Waktu (Tenggat)</th>
            <td class="p-2 text-slate-900">
              <?= $formatTgl($report['deadline_at']) ?>
              <?php if ($late): ?><span class="text-red-700 font-bold">(TERLAMBAT)</span><?php endif; ?>
            </td>
            <th class="p-2 bg-slate-100 text-left font-semibold text-slate-700">Waktu Dikirim</th>
            <td class="p-2 text-slate-900"><?= $formatTgl($report['submitted_at']) ?></td>
          </tr>
          <tr>
            <th class="p-2 bg-slate-100 text-left font-semibold text-slate-700">Status Dokumen</th>
            <td class="p-2 font-bold text-slate-900">
              <?= \App\Models\PoskoReport::STATUS_LABELS[$report['status']] ?? $report['status'] ?>
              <?= (int)$report['needs_review'] ? ' · Perlu Ditinjau Ulang' : '' ?>
            </td>
            <th class="p-2 bg-slate-100 text-left font-semibold text-slate-700">Wilayah Kejadian / Pantauan</th>
            <td class="p-2 text-slate-900"><?= htmlspecialchars($report['region'] ?: '-') ?></td>
          </tr>
          <?php if (!empty($report['reference_ticket'])): ?>
          <tr>
            <th class="p-2 bg-slate-100 text-left font-semibold text-slate-700">Aduan / Tiket Terkait</th>
            <td colspan="3" class="p-2 font-mono font-bold text-slate-900"><?= htmlspecialchars($report['reference_ticket']) ?></td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <!-- Bagian Khusus: Laporan Nihil -->
    <?php if ((int)$report['is_nil']): ?>
    <section class="mb-5 font-sans p-3 border-2 border-teal-800 bg-teal-50/60 rounded">
      <h4 class="text-xs font-bold uppercase tracking-wider text-teal-900 mb-1">PERNYATAAN RESMI LAPORAN NIHIL:</h4>
      <p class="text-xs text-teal-950 leading-relaxed">
        Satuan kerja <b><?= htmlspecialchars($report['satker_name']) ?></b> secara resmi menyatakan bahwa pada periode pelaporan ini <b>TIDAK TERDAPAT KEJADIAN / SITUASI MENONJOL (NIHIL)</b> sesuai dengan kewenangan dan kriteria jenis laporan.
      </p>
      <?php if (!empty($report['nil_reason'])): ?>
        <p class="text-xs text-teal-950 mt-1"><b>Alasan / Keterangan Nihil:</b> <?= nl2br(htmlspecialchars($report['nil_reason'])) ?></p>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- Bagian II: Ringkasan Situasi & Narasi -->
    <section class="mb-5 font-sans">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b-2 border-slate-900 pb-1 mb-2">II. URAIAN SITUASI & OPERASIONAL</h4>
      <div class="space-y-3 text-xs leading-relaxed text-justify">
        <div>
          <b class="text-slate-800">1. Ringkasan Situasi (Executive Summary):</b>
          <div class="mt-1 p-2 bg-slate-50 border border-slate-200 rounded text-slate-900">
            <?= !empty(trim((string)$report['situation_summary'])) ? nl2br(htmlspecialchars($report['situation_summary'])) : '—' ?>
          </div>
        </div>
        <div>
          <b class="text-slate-800">2. Uraian Rinci Kejadian / Kegiatan:</b>
          <div class="mt-1 p-2 bg-slate-50 border border-slate-200 rounded text-slate-900">
            <?= !empty(trim((string)$report['description'])) ? nl2br(htmlspecialchars($report['description'])) : '—' ?>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
          <div>
            <b class="text-slate-800">3. Kendala di Lapangan:</b>
            <div class="mt-1 p-2 bg-slate-50 border border-slate-200 rounded text-slate-900 min-h-[4rem]">
              <?= !empty(trim((string)$report['obstacles'])) ? nl2br(htmlspecialchars($report['obstacles'])) : 'Tidak ada kendala' ?>
            </div>
          </div>
          <div>
            <b class="text-slate-800">4. Tindak Lanjut yang Dilakukan:</b>
            <div class="mt-1 p-2 bg-slate-50 border border-slate-200 rounded text-slate-900 min-h-[4rem]">
              <?= !empty(trim((string)$report['follow_up'])) ? nl2br(htmlspecialchars($report['follow_up'])) : 'Sesuai SOP' ?>
            </div>
          </div>
          <div>
            <b class="text-slate-800">5. Kebutuhan Bantuan / Eskalasi:</b>
            <div class="mt-1 p-2 bg-slate-50 border border-slate-200 rounded text-slate-900 min-h-[4rem]">
              <?= !empty(trim((string)$report['assistance_needed'])) ? nl2br(htmlspecialchars($report['assistance_needed'])) : 'Tidak diperlukan' ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Bagian III: Data & Indikator Operasional -->
    <?php if (!empty($fields)): ?>
    <section class="mb-5 font-sans">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b-2 border-slate-900 pb-1 mb-2">
        III. <?= (int)$report['is_recap'] ? 'REKAPITULASI INDIKATOR OPERASIONAL (AGREGAT)' : 'DATA & INDIKATOR OPERASIONAL' ?>
      </h4>
      <table class="w-full text-xs border border-slate-300">
        <thead class="bg-slate-100 text-slate-800">
          <tr>
            <th class="p-2 border border-slate-300 text-center w-12">No.</th>
            <th class="p-2 border border-slate-300 text-left">Nama Indikator / Data</th>
            <th class="p-2 border border-slate-300 text-right w-40">Nilai / Jumlah</th>
            <th class="p-2 border border-slate-300 text-center w-28">Satuan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-300">
          <?php $no = 1; foreach ($fields as $f): ?>
            <?php
              $v = $values[$f['field_key']] ?? null;
              if (!$v && !(int)$f['is_active']) continue;
              $valStr = $v === null ? '-' : ($v['value_number'] !== null
                  ? rtrim(rtrim(number_format((float)$v['value_number'], 2, ',', '.'), '0'), ',')
                  : $v['value_text']);
            ?>
            <tr>
              <td class="p-2 border border-slate-300 text-center"><?= $no++ ?></td>
              <td class="p-2 border border-slate-300 font-medium text-slate-900"><?= htmlspecialchars($f['label']) ?></td>
              <td class="p-2 border border-slate-300 text-right font-mono font-bold text-slate-900"><?= htmlspecialchars($valStr) ?></td>
              <td class="p-2 border border-slate-300 text-center text-slate-700"><?= htmlspecialchars($f['unit_label'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
    <?php endif; ?>

    <!-- Bagian IV: Rincian Laporan Sumber (Jika Rekap) -->
    <?php if ((int)$report['is_recap'] && !empty($recapSources)): ?>
    <section class="mb-5 font-sans">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b-2 border-slate-900 pb-1 mb-2">
        IV. RINCIAN LAPORAN SUMBER KONTRIBUTOR (<?= count($recapSources) ?> UNIT)
      </h4>
      <table class="w-full text-xs border border-slate-300">
        <thead class="bg-slate-100 text-slate-800">
          <tr>
            <th class="p-2 border border-slate-300 text-center w-10">No.</th>
            <th class="p-2 border border-slate-300 text-left">Satuan Kerja Sumber</th>
            <th class="p-2 border border-slate-300 text-left">Nomor Laporan</th>
            <th class="p-2 border border-slate-300 text-center w-24">Bentuk</th>
            <th class="p-2 border border-slate-300 text-center w-20">Versi</th>
            <th class="p-2 border border-slate-300 text-center w-24">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-300">
          <?php foreach ($recapSources as $idx => $src): ?>
            <tr>
              <td class="p-2 border border-slate-300 text-center"><?= $idx + 1 ?></td>
              <td class="p-2 border border-slate-300 font-bold text-slate-900"><?= htmlspecialchars($src['satker_name']) ?></td>
              <td class="p-2 border border-slate-300 font-mono text-slate-800"><?= htmlspecialchars($src['report_number'] ?: 'Draft #' . $src['id']) ?></td>
              <td class="p-2 border border-slate-300 text-center">
                <?= (int)$src['is_recap'] ? 'Rekap' : 'Sumber' ?>
                <?= (int)$src['is_nil'] ? ' (Nihil)' : '' ?>
              </td>
              <td class="p-2 border border-slate-300 text-center font-mono">
                v<?= (int)$src['version'] ?>
                <?php if ((int)$src['source_version'] !== (int)$src['version']): ?>
                  <span class="text-amber-800 font-bold">*</span>
                <?php endif; ?>
              </td>
              <td class="p-2 border border-slate-300 text-center"><?= \App\Models\PoskoReport::STATUS_LABELS[$src['status']] ?? $src['status'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
    <?php endif; ?>

    <!-- Bagian V: Lampiran Berkas Fisik -->
    <section class="mb-6 font-sans">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 border-b-2 border-slate-900 pb-1 mb-2">
        <?= (int)$report['is_recap'] && !empty($recapSources) ? 'V' : 'IV' ?>. DAFTAR LAMPIRAN DOKUMEN / FOTO
      </h4>
      <?php if (!empty($attachments)): ?>
        <ul class="list-disc list-inside text-xs space-y-1 text-slate-800">
          <?php foreach ($attachments as $a): ?>
            <li>
              <b><?= htmlspecialchars($a['file_name_original']) ?></b>
              (<?= number_format((int)$a['file_size'] / 1024, 1) ?> KB)
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-xs italic text-slate-500">Tidak ada berkas lampiran tambahan yang disertakan pada laporan ini.</p>
      <?php endif; ?>
    </section>

    <!-- Lembar Pengesahan Tanda Tangan -->
    <section class="mt-8 pt-4 border-t-2 border-slate-900 font-sans">
      <div class="text-right text-xs mb-6 text-slate-800">
        <?= htmlspecialchars($report['region'] ?: ($report['satker_name'] ?? 'Tempat')) ?>, <?= $formatTglSaja($report['submitted_at'] ?: date('Y-m-d')) ?>
      </div>
      <div class="grid grid-cols-2 gap-8 text-xs text-center">
        <!-- Pembuat Laporan -->
        <div class="flex flex-col justify-between h-36">
          <div>
            <p class="font-bold uppercase text-slate-900">YANG MEMBUAT LAPORAN</p>
            <p class="text-slate-600 text-[11px]">Operator Posko <?= htmlspecialchars($report['satker_name']) ?></p>
          </div>
          <div>
            <p class="font-bold text-slate-900 underline uppercase"><?= htmlspecialchars($report['creator_name'] ?: 'OPERATOR POSKO') ?></p>
            <p class="text-slate-700 text-[11px]"><?= htmlspecialchars($report['creator_rank'] ?: 'BINTARA / PERWIRA POLRI') ?></p>
          </div>
        </div>

        <!-- Pejabat Verifikator / Pimpinan Satker -->
        <div class="flex flex-col justify-between h-36">
          <div>
            <p class="font-bold uppercase text-slate-900">MENGETAHUI / VERIFIKATOR</p>
            <p class="text-slate-600 text-[11px]"><?= htmlspecialchars($report['holder_name'] ?: $report['satker_name']) ?></p>
          </div>
          <div>
            <p class="font-bold text-slate-900 underline uppercase">
              <?= $report['status'] === 'APPROVED' || $report['status'] === 'FORWARDED' ? 'PEJABAT BERWENANG' : '(DALAM PROSES VERIFIKASI)' ?>
            </p>
            <p class="text-slate-700 text-[11px]">
              Status: <b><?= \App\Models\PoskoReport::STATUS_LABELS[$report['status']] ?? $report['status'] ?></b>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer Catatan Otentikasi -->
    <div class="mt-8 pt-3 border-t border-slate-300 flex justify-between items-center text-[10px] text-slate-500 font-sans">
      <span>SIGAP-POLRI Posko Operasional Presisi · Dokumen Elektronik Sah</span>
      <span>Dicetak: <?= date('d/m/Y H:i:s') ?> WIB oleh <?= htmlspecialchars(\App\Core\Auth::user()['name'] ?? 'Sistem') ?></span>
    </div>

  </div>

</body>
</html>
