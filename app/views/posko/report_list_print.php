<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Daftar Rekapitulasi Laporan Posko</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      body { background: white !important; color: black !important; font-size: 10pt; }
      .no-print { display: none !important; }
      .print-shadow { box-shadow: none !important; border: none !important; padding: 0 !important; }
      a { text-decoration: none !important; color: inherit !important; }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-8 font-serif">

  <!-- Top Floating Bar (No-Print) -->
  <div class="max-w-6xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 no-print bg-[#14100C] text-slate-100 p-4 rounded-xl shadow-lg border border-amber-500/40 font-sans">
    <div class="flex items-center gap-3">
      <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
      <div>
        <p class="text-xs font-bold text-amber-300 tracking-wider uppercase">CETAK DAFTAR LAPORAN POSKO PRESISI</p>
        <p class="text-[11px] text-slate-400">Total data tercetak: <?= count($items) ?> laporan</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-xs transition flex items-center gap-1.5 shadow">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        Cetak / Simpan PDF
      </button>
      <a href="<?= $baseUrl ?>/posko-laporan?<?= http_build_query($filters) ?>" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold rounded-lg text-xs transition">
        Kembali
      </a>
    </div>
  </div>

  <!-- Document Sheet (Landscape / Wide A4) -->
  <div class="max-w-6xl mx-auto bg-white p-8 sm:p-10 shadow-2xl rounded border border-slate-300 print-shadow relative font-sans">

    <!-- Official Kop Surat Polri -->
    <div class="border-b-4 border-double border-slate-900 pb-3 mb-5 text-center relative">
      <div class="flex items-center justify-center gap-4 mb-2">
        <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Tribrata Polri" class="w-16 h-auto object-contain">
        <div>
          <h2 class="text-xs font-black tracking-widest uppercase text-slate-900">KEPOLISIAN NEGARA REPUBLIK INDONESIA</h2>
          <h1 class="text-sm font-black tracking-wider uppercase text-slate-900"><?= htmlspecialchars($currentSatkerName) ?></h1>
          <p class="text-[10px] text-slate-600">POS KOMANDO OPERASIONAL PRESISI • REKAPITULASI LAPORAN RESMI</p>
        </div>
      </div>
    </div>

    <!-- Judul & Kriteria Filter -->
    <div class="text-center mb-5">
      <h3 class="text-sm font-black underline uppercase tracking-wider text-slate-900">
        DAFTAR REKAPITULASI LAPORAN POSKO
      </h3>
      <p class="text-xs text-slate-700 mt-1">
        <?php if (!empty($filterDesc)): ?>
          Filter: <?= htmlspecialchars($filterDesc) ?> · 
        <?php endif; ?>
        Total: <b><?= count($items) ?></b> data laporan
      </p>
    </div>

    <!-- Tabel Daftar Laporan -->
    <table class="w-full text-[11px] border border-slate-300 mb-6">
      <thead class="bg-slate-100 text-slate-800 uppercase text-[9px] tracking-wider">
        <tr>
          <th class="p-2 border border-slate-300 text-center w-8">No</th>
          <th class="p-2 border border-slate-300 text-left">Nomor Laporan</th>
          <th class="p-2 border border-slate-300 text-left">Satuan Kerja</th>
          <th class="p-2 border border-slate-300 text-left">Jenis Laporan</th>
          <th class="p-2 border border-slate-300 text-center w-16">Bentuk</th>
          <th class="p-2 border border-slate-300 text-left">Periode / Kejadian</th>
          <th class="p-2 border border-slate-300 text-center w-14">Prioritas</th>
          <th class="p-2 border border-slate-300 text-center w-24">Status</th>
          <th class="p-2 border border-slate-300 text-left w-28">Waktu Kirim</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-300">
        <?php if (!$items): ?>
          <tr><td colspan="9" class="p-6 text-center text-slate-500 italic">Tidak ada laporan yang sesuai kriteria filter.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $idx => $r): ?>
          <tr class="hover:bg-slate-50">
            <td class="p-2 border border-slate-300 text-center"><?= $idx + 1 ?></td>
            <td class="p-2 border border-slate-300 font-mono font-bold text-slate-900"><?= htmlspecialchars($r['report_number'] ?: ('DRAFT #' . $r['id'])) ?></td>
            <td class="p-2 border border-slate-300 text-slate-800"><?= htmlspecialchars($r['satker_name']) ?></td>
            <td class="p-2 border border-slate-300 text-slate-800"><?= htmlspecialchars($r['type_name']) ?></td>
            <td class="p-2 border border-slate-300 text-center">
              <?= (int)$r['is_recap'] ? 'Rekap' : 'Sumber' ?>
              <?= (int)$r['is_nil'] ? '<span class="text-teal-700 font-bold block">(Nihil)</span>' : '' ?>
            </td>
            <td class="p-2 border border-slate-300 text-slate-800">
              <?= htmlspecialchars($r['period_start']
                  ? \App\Core\PoskoSchedule::label($r['period_unit'], $r['period_start'], $r['period_end'])
                  : ($r['event_time'] ? date('d/m/Y H:i', strtotime($r['event_time'])) : '-')) ?>
            </td>
            <td class="p-2 border border-slate-300 text-center font-bold text-slate-800"><?= htmlspecialchars($r['priority']) ?></td>
            <td class="p-2 border border-slate-300 text-center font-medium">
              <?= \App\Models\PoskoReport::STATUS_LABELS[$r['status']] ?? $r['status'] ?>
            </td>
            <td class="p-2 border border-slate-300 text-slate-700">
              <?= $r['submitted_at'] ? date('d/m/Y H:i', strtotime($r['submitted_at'])) : '-' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Tanda Tangan Cetak -->
    <div class="mt-8 pt-4 border-t-2 border-slate-900 grid grid-cols-2 gap-8 text-xs text-center">
      <div class="h-28 flex flex-col justify-between">
        <div>
          <p class="font-bold uppercase text-slate-900">OPERATOR POSKO</p>
          <p class="text-slate-600 text-[11px]"><?= htmlspecialchars($currentSatkerName) ?></p>
        </div>
        <div>
          <p class="font-bold underline uppercase text-slate-900"><?= htmlspecialchars(\App\Core\Auth::user()['name'] ?? 'OPERATOR') ?></p>
          <p class="text-slate-600 text-[11px]"><?= htmlspecialchars(\App\Core\Auth::user()['rank'] ?? 'BINTARA/PERWIRA') ?></p>
        </div>
      </div>
      <div class="h-28 flex flex-col justify-between">
        <div>
          <p class="font-bold uppercase text-slate-900">KA POSKO / PIMPINAN SATKER</p>
          <p class="text-slate-600 text-[11px]"><?= htmlspecialchars($currentSatkerName) ?></p>
        </div>
        <div>
          <p class="font-bold underline uppercase text-slate-900">(...........................................)</p>
          <p class="text-slate-600 text-[11px]">Tanda Tangan & Cap Dinas</p>
        </div>
      </div>
    </div>

    <!-- Catatan Cetak -->
    <div class="mt-6 pt-2 border-t border-slate-200 flex justify-between text-[10px] text-slate-500">
      <span>Dicetak secara elektronik dari SIGAP-POLRI Posko Management System</span>
      <span>Waktu cetak: <?= date('d/m/Y H:i:s') ?> WIB</span>
    </div>

  </div>

</body>
</html>
