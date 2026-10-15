<?php
/**
 * Template PDF dokumen laporan posko (Dompdf; CSS sederhana tanpa flex/grid). Isi setara report_print.php.
 */
$e = fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tgl = fn(?string $dt): string => $dt ? (date('j', strtotime($dt)) . ' ' . $bulan[(int)date('n', strtotime($dt))] . ' ' . date('Y H:i', strtotime($dt)) . ' WIB') : '-';
$tglSaja = fn(?string $dt): string => $dt ? (date('j', strtotime($dt)) . ' ' . $bulan[(int)date('n', strtotime($dt))] . ' ' . date('Y', strtotime($dt))) : '-';
$late = $report['deadline_at'] && (($report['submitted_at'] ?: date('Y-m-d H:i:s')) > $report['deadline_at']);
$statusLabel = \App\Models\PoskoReport::STATUS_LABELS[$report['status']] ?? $report['status'];
$text = fn($s, string $empty): string => trim((string)$s) !== '' ? nl2br($e($s)) : $empty;
$sec = 1;
$roman = ['I', 'II', 'III', 'IV', 'V', 'VI'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Posko <?= $e($report['report_number'] ?: 'Draft-' . $report['id']) ?> (v<?= (int)$report['version'] ?>)</title>
<style>
  @page { margin: 18mm 16mm 16mm 16mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #0f172a; }
  .kop { border-bottom: 3px double #0f172a; padding-bottom: 6px; margin-bottom: 10px; }
  .kop td { vertical-align: middle; }
  .kop h2 { font-size: 9pt; margin: 0; letter-spacing: 1px; }
  .kop h1 { font-size: 12pt; margin: 2px 0; text-transform: uppercase; }
  .kop p { font-size: 7.5pt; margin: 0; color: #475569; }
  .meta { width: 100%; font-size: 8pt; color: #475569; border-bottom: 1px solid #cbd5e1; margin-bottom: 8px; }
  .title { text-align: center; margin-bottom: 12px; }
  .title h3 { font-size: 11pt; text-decoration: underline; margin: 0; text-transform: uppercase; }
  .title p { margin: 2px 0; font-size: 8.5pt; }
  .mono { font-family: 'DejaVu Sans Mono', monospace; }
  h4 { font-size: 8.5pt; text-transform: uppercase; border-bottom: 2px solid #0f172a; padding-bottom: 2px; margin: 12px 0 5px; }
  table.grid { width: 100%; border-collapse: collapse; }
  table.grid th, table.grid td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: top; }
  table.grid th { background: #f1f5f9; text-align: left; font-weight: bold; color: #334155; }
  .box { border: 1px solid #e2e8f0; background: #f8fafc; padding: 5px; margin: 2px 0 7px; text-align: justify; }
  .nil { border: 2px solid #115e59; background: #f0fdfa; padding: 6px; margin-top: 8px; }
  .center { text-align: center; } .right { text-align: right; } .bold { font-weight: bold; }
  .late { color: #b91c1c; font-weight: bold; }
  .sign { width: 100%; margin-top: 14px; border-top: 2px solid #0f172a; }
  .sign td { width: 50%; text-align: center; vertical-align: top; padding-top: 6px; }
  .foot { margin-top: 14px; border-top: 1px solid #cbd5e1; font-size: 7pt; color: #64748b; width: 100%; }
</style>
</head>
<body>

<table class="kop" width="100%"><tr>
  <td width="70"><img src="<?= $e(\App\Core\PoskoExport::logoPath()) ?>" width="60"></td>
  <td class="center">
    <h2>KEPOLISIAN NEGARA REPUBLIK INDONESIA</h2>
    <h1><?= $e($report['satker_name'] ?? 'SATUAN KERJA POLRI') ?></h1>
    <p>POS KOMANDO &amp; KOORDINASI OPERASIONAL PRESISI • LAPORAN BERJENJANG</p>
  </td>
  <td width="70"></td>
</tr></table>

<table class="meta"><tr>
  <td>KLASIFIKASI: <b><?= $report['priority'] === 'URGENT' ? 'SANGAT RAHASIA / SEGERA' : ($report['priority'] === 'HIGH' ? 'RAHASIA / PENTING' : 'BIASA / KILAT') ?></b></td>
  <td class="right">PENANDA VERSI: <b class="mono">v<?= (int)$report['version'] ?></b></td>
</tr></table>

<div class="title">
  <h3><?= (int)$report['is_recap'] ? 'Rekapitulasi Laporan Posko' : 'Laporan Situasi dan Operasional Posko' ?></h3>
  <p class="mono bold">NOMOR: <?= $e($report['report_number'] ?: '[DRAFT BELUM DIKIRIM #' . $report['id'] . ']') ?></p>
  <p><?= $e($report['type_name']) ?><?= (int)$report['is_nil'] ? ' <b>[LAPORAN NIHIL]</b>' : '' ?><?= (int)$report['is_recap'] ? ' <b>[REKAP BERJENJANG]</b>' : '' ?></p>
</div>

<h4><?= $roman[$sec++ - 1] ?>. Identitas &amp; Waktu Pelaporan</h4>
<table class="grid">
  <tr>
    <th width="22%">Satuan Kerja Pelapor</th><td width="28%" class="bold"><?= $e($report['satker_name']) ?> (<?= $e($report['satker_code']) ?>)</td>
    <th width="22%">Jenis &amp; Bentuk Laporan</th><td width="28%"><?= $e($report['type_name']) ?> (<?= (int)$report['is_recap'] ? 'Rekapitulasi' : 'Laporan Sumber' ?>)</td>
  </tr>
  <tr>
    <th>Periode / Waktu Kejadian</th>
    <td class="bold"><?= $e($report['period_start']
        ? \App\Core\PoskoSchedule::label($report['period_unit'], $report['period_start'], $report['period_end'])
        : ($report['event_time'] ? date('d/m/Y H:i', strtotime($report['event_time'])) . ' WIB' : '-')) ?></td>
    <th>Tingkat Prioritas</th><td class="bold"><?= $e($report['priority']) ?></td>
  </tr>
  <tr>
    <th>Batas Waktu (Tenggat)</th><td><?= $tgl($report['deadline_at']) ?><?= $late ? ' <span class="late">(TERLAMBAT)</span>' : '' ?></td>
    <th>Waktu Dikirim</th><td><?= $tgl($report['submitted_at']) ?></td>
  </tr>
  <tr>
    <th>Status Dokumen</th><td class="bold"><?= $e($statusLabel) ?><?= (int)$report['needs_review'] ? ' · Perlu Ditinjau Ulang' : '' ?></td>
    <th>Wilayah Kejadian / Pantauan</th><td><?= $e($report['region'] ?: '-') ?></td>
  </tr>
  <?php if (!empty($report['reference_ticket'])): ?>
  <tr><th>Aduan / Tiket Terkait</th><td colspan="3" class="mono bold"><?= $e($report['reference_ticket']) ?></td></tr>
  <?php endif; ?>
</table>

<?php if ((int)$report['is_nil']): ?>
<div class="nil">
  <b>PERNYATAAN RESMI LAPORAN NIHIL:</b> Satuan kerja <b><?= $e($report['satker_name']) ?></b> menyatakan bahwa pada periode pelaporan ini
  <b>tidak terdapat kejadian / situasi menonjol (NIHIL)</b> sesuai kewenangan dan kriteria jenis laporan.
  <?php if (!empty($report['nil_reason'])): ?><br><b>Alasan / Keterangan Nihil:</b> <?= nl2br($e($report['nil_reason'])) ?><?php endif; ?>
</div>
<?php endif; ?>

<h4><?= $roman[$sec++ - 1] ?>. Uraian Situasi &amp; Operasional</h4>
<b>1. Ringkasan Situasi:</b><div class="box"><?= $text($report['situation_summary'], '—') ?></div>
<b>2. Uraian Rinci Kejadian / Kegiatan:</b><div class="box"><?= $text($report['description'], '—') ?></div>
<b>3. Kendala di Lapangan:</b><div class="box"><?= $text($report['obstacles'], 'Tidak ada kendala') ?></div>
<b>4. Tindak Lanjut yang Dilakukan:</b><div class="box"><?= $text($report['follow_up'], 'Sesuai SOP') ?></div>
<b>5. Kebutuhan Bantuan / Eskalasi:</b><div class="box"><?= $text($report['assistance_needed'], 'Tidak diperlukan') ?></div>

<?php if (!empty($fields)): ?>
<h4><?= $roman[$sec++ - 1] ?>. <?= (int)$report['is_recap'] ? 'Rekapitulasi Indikator Operasional (Agregat)' : 'Data &amp; Indikator Operasional' ?></h4>
<table class="grid">
  <tr><th class="center" width="8%">No.</th><th>Nama Indikator / Data</th><th class="right" width="22%">Nilai / Jumlah</th><th class="center" width="16%">Satuan</th></tr>
  <?php $no = 1; foreach ($fields as $f):
      $v = $values[$f['field_key']] ?? null;
      if (!$v && !(int)$f['is_active']) continue;
      $val = $v === null ? '-' : ($v['value_number'] !== null
          ? rtrim(rtrim(number_format((float)$v['value_number'], 2, ',', '.'), '0'), ',')
          : $v['value_text']); ?>
  <tr>
    <td class="center"><?= $no++ ?></td>
    <td><?= $e($f['label']) ?></td>
    <td class="right mono bold"><?= $e($val) ?></td>
    <td class="center"><?= $e($f['unit_label'] ?: '-') ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ((int)$report['is_recap'] && !empty($recapSources)): ?>
<h4><?= $roman[$sec++ - 1] ?>. Rincian Laporan Sumber Kontributor (<?= count($recapSources) ?> unit)</h4>
<table class="grid">
  <tr><th class="center" width="6%">No.</th><th>Satuan Kerja Sumber</th><th>Nomor Laporan</th><th class="center">Bentuk</th><th class="center">Versi</th><th class="center">Status</th></tr>
  <?php foreach ($recapSources as $i => $src): ?>
  <tr>
    <td class="center"><?= $i + 1 ?></td>
    <td class="bold"><?= $e($src['satker_name']) ?></td>
    <td class="mono"><?= $e($src['report_number'] ?: 'Draft #' . $src['id']) ?></td>
    <td class="center"><?= (int)$src['is_recap'] ? 'Rekap' : 'Sumber' ?><?= (int)$src['is_nil'] ? ' (Nihil)' : '' ?></td>
    <td class="center mono">v<?= (int)$src['version'] ?><?= (int)$src['source_version'] !== (int)$src['version'] ? ' *' : '' ?></td>
    <td class="center"><?= $e(\App\Models\PoskoReport::STATUS_LABELS[$src['status']] ?? $src['status']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<h4><?= $roman[$sec++ - 1] ?>. Daftar Lampiran Dokumen / Foto</h4>
<?php if (!empty($attachments)): ?>
  <ul><?php foreach ($attachments as $a): ?><li><b><?= $e($a['file_name_original']) ?></b> (<?= number_format((int)$a['file_size'] / 1024, 1) ?> KB)</li><?php endforeach; ?></ul>
<?php else: ?>
  <p><i>Tidak ada berkas lampiran tambahan yang disertakan pada laporan ini.</i></p>
<?php endif; ?>

<p class="right" style="margin-top:14px"><?= $e($report['region'] ?: $report['satker_name']) ?>, <?= $tglSaja($report['submitted_at'] ?: date('Y-m-d')) ?></p>
<table class="sign"><tr>
  <td>
    <b>YANG MEMBUAT LAPORAN</b><br><span style="font-size:8pt">Operator Posko <?= $e($report['satker_name']) ?></span>
    <br><br><br><br>
    <b><u><?= $e(strtoupper($report['creator_name'] ?: 'Operator Posko')) ?></u></b><br><?= $e($report['creator_rank'] ?: 'Bintara / Perwira Polri') ?>
  </td>
  <td>
    <b>MENGETAHUI / VERIFIKATOR</b><br><span style="font-size:8pt"><?= $e($report['holder_name'] ?: $report['satker_name']) ?></span>
    <br><br><br><br>
    <b><u><?= in_array($report['status'], ['APPROVED', 'FORWARDED'], true) ? 'PEJABAT BERWENANG' : '(DALAM PROSES VERIFIKASI)' ?></u></b><br>Status: <b><?= $e($statusLabel) ?></b>
  </td>
</tr></table>

<table class="foot"><tr>
  <td>SIGAP-POLRI Posko Operasional Presisi · Dokumen Elektronik</td>
  <td class="right">Diekspor: <?= date('d/m/Y H:i:s') ?> WIB oleh <?= $e(\App\Core\Auth::user()['name'] ?? 'Sistem') ?></td>
</tr></table>

</body>
</html>
