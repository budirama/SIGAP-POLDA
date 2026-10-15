<?php
/**
 * Template PDF daftar laporan posko terfilter (Dompdf, A4 lanskap). Isi setara report_list_print.php.
 */
$e = fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$user = \App\Core\Auth::user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Daftar Rekapitulasi Laporan Posko</title>
<style>
  @page { margin: 14mm 12mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; color: #0f172a; }
  .kop { width: 100%; border-bottom: 3px double #0f172a; padding-bottom: 5px; margin-bottom: 8px; }
  .kop h2 { font-size: 9pt; margin: 0; letter-spacing: 1px; }
  .kop h1 { font-size: 11pt; margin: 2px 0; text-transform: uppercase; }
  .kop p { font-size: 7pt; margin: 0; color: #475569; }
  .title { text-align: center; margin-bottom: 8px; }
  .title h3 { font-size: 10pt; text-decoration: underline; margin: 0; }
  .title p { margin: 2px 0; }
  table.grid { width: 100%; border-collapse: collapse; }
  table.grid th, table.grid td { border: 1px solid #cbd5e1; padding: 3px 4px; vertical-align: top; }
  table.grid th { background: #f1f5f9; font-size: 7pt; text-transform: uppercase; }
  thead { display: table-header-group; }
  tr { page-break-inside: avoid; }
  .center { text-align: center; } .right { text-align: right; } .mono { font-family: 'DejaVu Sans Mono', monospace; font-weight: bold; }
  .note { border: 1px solid #f59e0b; background: #fffbeb; padding: 4px; margin-bottom: 6px; }
  .sign { width: 100%; margin-top: 14px; border-top: 2px solid #0f172a; page-break-inside: avoid; }
  .sign td { width: 50%; text-align: center; padding-top: 6px; }
  .foot { width: 100%; margin-top: 10px; border-top: 1px solid #cbd5e1; font-size: 7pt; color: #64748b; }
</style>
</head>
<body>

<table class="kop"><tr>
  <td width="60"><img src="<?= $e(\App\Core\PoskoExport::logoPath()) ?>" width="50"></td>
  <td class="center">
    <h2>KEPOLISIAN NEGARA REPUBLIK INDONESIA</h2>
    <h1><?= $e($currentSatkerName) ?></h1>
    <p>POS KOMANDO OPERASIONAL PRESISI • REKAPITULASI LAPORAN RESMI</p>
  </td>
  <td width="60"></td>
</tr></table>

<div class="title">
  <h3>DAFTAR REKAPITULASI LAPORAN POSKO</h3>
  <p><?= $filterDesc !== '' ? 'Filter: ' . $e($filterDesc) . ' · ' : '' ?>Total: <b><?= (int)$total ?></b> data laporan</p>
</div>

<?php if ($total > count($items)): ?>
<div class="note">PDF memuat <?= count($items) ?> laporan pertama dari <?= (int)$total ?>. Gunakan <b>Ekspor Excel</b> untuk data lengkap, atau persempit filter.</div>
<?php endif; ?>

<table class="grid">
  <thead><tr>
    <th class="center" width="3%">No</th><th>Nomor Laporan</th><th>Satuan Kerja</th><th>Jenis Laporan</th>
    <th class="center">Bentuk</th><th>Periode / Kejadian</th><th class="center">Prioritas</th>
    <th class="center">Status</th><th class="center">Versi</th><th>Waktu Kirim</th>
  </tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="10" class="center"><i>Tidak ada laporan yang sesuai kriteria filter.</i></td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $r): // urutan kolom = PoskoExport::HEADERS ?>
    <tr>
      <td class="center"><?= (int)$r[0] ?></td>
      <td class="mono"><?= $e($r[1]) ?></td>
      <td><?= $e($r[2]) ?></td>
      <td><?= $e($r[4]) ?></td>
      <td class="center"><?= $e($r[5]) ?></td>
      <td><?= $e($r[6]) ?></td>
      <td class="center"><?= $e($r[7]) ?></td>
      <td class="center"><?= $e($r[8]) ?></td>
      <td class="center"><?= $e($r[13]) ?></td>
      <td><?= $e($r[10]) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<table class="sign"><tr>
  <td><b>OPERATOR POSKO</b><br><?= $e($currentSatkerName) ?><br><br><br><br>
      <b><u><?= $e(strtoupper($user['name'] ?? 'Operator')) ?></u></b><br><?= $e($user['rank'] ?? 'Bintara / Perwira') ?></td>
  <td><b>KA POSKO / PIMPINAN SATKER</b><br><?= $e($currentSatkerName) ?><br><br><br><br>
      <b>(...........................................)</b><br>Tanda Tangan &amp; Cap Dinas</td>
</tr></table>

<table class="foot"><tr>
  <td>Diekspor secara elektronik dari SIGAP-POLRI Posko Management System</td>
  <td class="right">Waktu ekspor: <?= date('d/m/Y H:i:s') ?> WIB</td>
</tr></table>

</body>
</html>
