<?php
use App\Models\LostFound;

$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$romawi = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
$tanggal = fn(string $dt): string => date('j', strtotime($dt)) . ' ' . $bulan[(int)date('n', strtotime($dt))] . ' ' . date('Y', strtotime($dt));
$issued = $item['verified_at'] ?: $item['created_at'];
$nomor = sprintf('SKTLK/%d/%s/%s/SPKT', (int)$item['id'], $romawi[(int)date('n', strtotime($issued))], date('Y', strtotime($issued)));

$rows = [];
foreach ([
    'brand' => 'Merk / Tipe', 'color' => 'Warna', 'plate_number' => 'Nomor Polisi', 'chassis_number' => 'Nomor Rangka',
    'engine_number' => 'Nomor Mesin', 'serial_number' => 'IMEI / No. Seri / No. Dokumen', 'person_name' => 'Nama',
    'animal_species' => 'Jenis Hewan',
] as $field => $label) {
    if (!empty($item[$field])) {
        $rows[] = [$label, $item[$field]];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>SKTLK - <?= htmlspecialchars($item['code']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      body { background: white !important; color: black !important; }
      .no-print { display: none !important; }
      .print-shadow { box-shadow: none !important; border: none !important; }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-8 font-serif">

  <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print bg-[#14100C] text-slate-100 p-4 rounded-xl shadow-lg border border-amber-500/30">
    <span class="text-xs font-sans font-bold">SURAT KETERANGAN TANDA LAPOR KEHILANGAN (ELEKTRONIK)</span>
    <div class="flex items-center gap-3">
      <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-sans font-bold rounded-lg text-xs">Cetak / Simpan PDF</button>
      <button onclick="window.close()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-sans font-semibold rounded-lg text-xs">Tutup</button>
    </div>
  </div>

  <div class="max-w-3xl mx-auto bg-white p-8 sm:p-12 shadow-2xl rounded border border-slate-300 print-shadow text-sm leading-relaxed">
    <div class="text-center border-b-4 border-double border-slate-800 pb-3 mb-6">
      <p class="font-bold tracking-wide">KEPOLISIAN NEGARA REPUBLIK INDONESIA</p>
      <p class="font-bold">SENTRA PELAYANAN KEPOLISIAN TERPADU</p>
    </div>

    <div class="text-center mb-6">
      <p class="font-bold underline text-base">SURAT KETERANGAN TANDA LAPOR KEHILANGAN</p>
      <p>Nomor: <?= htmlspecialchars($nomor) ?></p>
    </div>

    <p class="mb-3">Yang bertanda tangan di bawah ini menerangkan bahwa pada tanggal <?= $tanggal($item['created_at']) ?> telah datang melapor:</p>
    <table class="mb-4 ml-6">
      <tr><td class="pr-4 align-top w-40">Nama</td><td>: <?= htmlspecialchars($item['reporter_name']) ?></td></tr>
      <tr><td class="pr-4 align-top">NIK</td><td>: <?= htmlspecialchars($item['reporter_nik'] ?: '-') ?></td></tr>
      <tr><td class="pr-4 align-top">No. Telepon</td><td>: <?= htmlspecialchars($item['reporter_phone']) ?></td></tr>
    </table>

    <p class="mb-3">Melaporkan telah kehilangan <b><?= htmlspecialchars(LostFound::CATEGORIES[$item['item_category']][0]) ?></b> berupa:</p>
    <table class="mb-4 ml-6">
      <tr><td class="pr-4 align-top w-40">Uraian</td><td>: <?= htmlspecialchars($item['title']) ?></td></tr>
      <?php foreach ($rows as [$label, $value]): ?>
        <tr><td class="pr-4 align-top"><?= $label ?></td><td>: <?= htmlspecialchars((string)$value) ?></td></tr>
      <?php endforeach; ?>
      <tr><td class="pr-4 align-top">Ciri-ciri</td><td class="whitespace-pre-line">: <?= htmlspecialchars($item['description']) ?></td></tr>
      <tr><td class="pr-4 align-top">Waktu Kehilangan</td><td>: <?= $tanggal($item['event_date']) ?></td></tr>
      <tr><td class="pr-4 align-top">Tempat Kehilangan</td><td>: <?= htmlspecialchars($item['event_location']) ?></td></tr>
    </table>

    <p class="mb-3">
      Surat keterangan ini diberikan untuk keperluan pengurusan kembali dokumen/barang yang hilang dan
      <b>bukan merupakan pengganti</b> dokumen/barang tersebut. Apabila di kemudian hari keterangan pelapor
      tidak benar, pelapor bertanggung jawab sesuai ketentuan hukum yang berlaku.
    </p>
    <p class="mb-8">Kode Registrasi Hilang &amp; Temu: <b class="font-mono"><?= htmlspecialchars($item['code']) ?></b></p>

    <div class="flex justify-end">
      <div class="text-center w-64">
        <p>Dikeluarkan, <?= $tanggal($issued) ?></p>
        <p class="font-bold">a.n. KEPALA SPKT</p>
        <p class="font-bold mb-16">PETUGAS PELAYANAN</p>
        <p class="font-bold underline"><?= htmlspecialchars($item['verifier_name'] ?: '........................') ?></p>
        <p><?= htmlspecialchars(trim(($item['verifier_rank'] ?? '') . ' NRP ' . ($item['verifier_nrp'] ?? ''))) ?></p>
      </div>
    </div>
  </div>
</body>
</html>
