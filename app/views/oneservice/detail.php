<?php
use App\Models\OneService\OpsDrill;

$t = $track;
$card = 'polri-glass-card rounded-2xl p-5 border border-slate-800';
$h3 = 'text-sm font-black text-slate-100 mb-3 flex items-center gap-2';
$dt = fn(?string $v, string $fmt = 'd/m/Y H:i') => $v ? date($fmt, strtotime($v)) : '-';
$isWhistle = (int)$t['is_whistleblower'] === 1;
?>
<div class="space-y-6">

  <a href="<?= $baseUrl ?>/oneservice/daftar" class="text-xs text-slate-400 hover:text-amber-400"><i class="fas fa-arrow-left mr-1"></i> Kembali ke daftar perkara</a>

  <!-- Ringkasan -->
  <div class="polri-glass-card p-6 rounded-2xl border border-[#3B2F1C] space-y-3">
    <div class="flex flex-wrap items-center gap-2 text-xs">
      <span class="font-mono font-black text-amber-400 text-base"><?= htmlspecialchars($t['tracking_number']) ?></span>
      <span class="px-2 py-0.5 rounded-full border border-amber-500/40 bg-amber-500/10 text-amber-300 font-bold"><?= OpsDrill::STATUSES[$t['current_status']] ?? $t['current_status'] ?></span>
      <span class="px-2 py-0.5 rounded-full border border-slate-700 text-slate-300"><?= OpsDrill::PRIORITIES[$t['priority_level']] ?? $t['priority_level'] ?></span>
      <span class="px-2 py-0.5 rounded-full border border-slate-700 text-slate-300"><?= OpsDrill::CHANNELS[$t['source_channel']] ?? $t['source_channel'] ?></span>
      <?php if ((int)$t['is_stalled']): ?><span class="px-2 py-0.5 rounded-full bg-red-950 text-red-300 border border-red-800 font-bold">MACET — melewati SLA</span><?php endif; ?>
      <?php if ((int)$t['is_vulnerable_group']): ?><span class="px-2 py-0.5 rounded-full bg-purple-950 text-purple-300 border border-purple-800">Kelompok rentan / PPA</span><?php endif; ?>
    </div>
    <h2 class="text-lg sm:text-xl font-black text-slate-100 font-heading"><?= htmlspecialchars($t['incident_category']) ?></h2>
    <p class="text-sm text-slate-300 whitespace-pre-line"><?= htmlspecialchars($t['incident_summary']) ?></p>
    <dl class="grid sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-2 text-xs pt-2">
      <div><dt class="text-slate-500">Satker penanggung jawab</dt><dd class="text-slate-100 font-semibold"><?= htmlspecialchars($t['satker_name'] ?? '-') ?></dd></div>
      <div><dt class="text-slate-500">Petugas</dt><dd class="text-slate-100"><?= htmlspecialchars(trim(($t['officer_rank'] ?? '') . ' ' . ($t['officer_name'] ?? '')) ?: ($t['assigned_officer_nrp'] ?: 'Belum ditunjuk')) ?></dd></div>
      <div><dt class="text-slate-500">Diterima</dt><dd class="text-slate-100"><?= $dt($t['created_at']) ?></dd></div>
      <div><dt class="text-slate-500">Batas SLA</dt><dd class="<?= (int)$t['is_stalled'] ? 'text-red-300 font-bold' : 'text-slate-100' ?>"><?= $dt($t['sla_deadline']) ?></dd></div>
      <div class="sm:col-span-2"><dt class="text-slate-500">Lokasi</dt><dd class="text-slate-100"><?= htmlspecialchars($t['incident_address'] ?? '-') ?>
        <?php if (!empty($t['incident_lat'])): ?><a href="https://www.google.com/maps?q=<?= (float)$t['incident_lat'] ?>,<?= (float)$t['incident_lng'] ?>" target="_blank" rel="noopener" class="text-amber-400 ml-1"><i class="fas fa-map-location-dot"></i></a><?php endif; ?></dd></div>
      <div><dt class="text-slate-500">Tiket modul lama</dt><dd class="font-mono">
        <?php if ($legacyId): ?><a href="<?= $baseUrl ?>/investigator/detail/<?= (int)$legacyId ?>" class="text-amber-400 hover:underline"><?= htmlspecialchars($t['external_reference_no']) ?></a>
        <?php else: ?><span class="text-slate-400"><?= htmlspecialchars($t['external_reference_no'] ?: '-') ?></span><?php endif; ?></dd></div>
      <div><dt class="text-slate-500">Ditutup</dt><dd class="text-slate-100"><?= $dt($t['closed_at']) ?></dd></div>
    </dl>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

      <!-- Riwayat SP2HP & event -->
      <div class="<?= $card ?>">
        <h3 class="<?= $h3 ?>"><i class="fas fa-timeline text-amber-400"></i> Riwayat SP2HP (<?= count($milestones) ?>)</h3>
        <?php if (!$milestones): ?><p class="text-xs text-slate-500">Belum ada SP2HP diterbitkan.</p><?php endif; ?>
        <ol class="space-y-2">
          <?php foreach ($milestones as $m): ?>
            <li class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800 text-xs">
              <div class="flex flex-wrap justify-between gap-2">
                <span class="font-bold text-slate-100"><span class="font-mono text-amber-400"><?= htmlspecialchars($m['stage_code']) ?></span> · <?= htmlspecialchars($m['title']) ?></span>
                <span class="text-slate-500 font-mono"><?= $dt($m['issued_at']) ?> · NRP <?= htmlspecialchars((string)$m['officer_nrp']) ?></span>
              </div>
              <p class="text-slate-300 mt-1"><?= htmlspecialchars((string)$m['description_public']) ?></p>
              <?php if (!empty($m['description_internal'])): ?><p class="text-amber-200/80 mt-1 italic">Internal: <?= htmlspecialchars($m['description_internal']) ?></p><?php endif; ?>
              <?php if (!empty($m['jpu_coordination_status'])): ?><p class="text-slate-400 mt-1">Koordinasi JPU: <?= htmlspecialchars($m['jpu_coordination_status']) ?></p><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>

      <?php foreach ([
          ['Panggilan 110 & Respons TKP', 'fa-headset', $dispatch, ['caller_name' => 'Penelepon', 'urgency_level' => 'Urgensi', 'unit_callsign' => 'Unit', 'patrol_vehicle_type' => 'Kendaraan', 'call_received_at' => 'Diterima', 'dispatched_at' => 'Diberangkatkan', 'arrived_at' => 'Tiba di TKP', 'response_time_seconds' => 'Waktu respons (detik)', 'status' => 'Status', 'scene_notes' => 'Catatan TKP']],
          ['Aduan Siber & Blokir Rekening', 'fa-shield-virus', $cyber, ['bank_name' => 'Bank', 'account_number' => 'Rekening terlapor', 'account_holder_name' => 'Atas nama', 'fraud_scheme' => 'Modus', 'loss_amount' => 'Kerugian (Rp)', 'freeze_status' => 'Status blokir', 'freeze_requested_at' => 'Diminta', 'freeze_confirmed_at' => 'Dikonfirmasi', 'cluster_tag' => 'Kluster']],
          ['Dumas & Integritas Layanan', 'fa-bullhorn', $dumas, ['reported_satker' => 'Satker terlapor', 'reported_service_loket' => 'Loket', 'allegation_type' => 'Jenis dugaan', 'risk_score' => 'Skor risiko', 'investigation_status' => 'Status pemeriksaan', 'is_whistleblower_protected' => 'Pelapor dilindungi']],
          ['Penyelesaian Bhabinkamtibmas', 'fa-handshake', $bhabin, ['village_name' => 'Desa/Kelurahan', 'bhabin_nrp' => 'NRP Bhabin', 'conflict_category' => 'Kategori', 'parties_involved' => 'Pihak', 'mediation_outcome' => 'Hasil mediasi', 'is_peacefully_resolved' => 'Damai (RJ)', 'recorded_at' => 'Dicatat']],
      ] as [$title, $icon, $rows, $fields]): if (!$rows) continue; ?>
        <div class="<?= $card ?>">
          <h3 class="<?= $h3 ?>"><i class="fas <?= $icon ?> text-amber-400"></i> <?= $title ?></h3>
          <?php foreach ($rows as $r): ?>
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-1 text-xs p-3 rounded-xl bg-[#0C0A08] border border-slate-800 mb-2">
              <?php foreach ($fields as $k => $label): if (!isset($r[$k]) || $r[$k] === '') continue;
                $val = in_array($k, ['is_whistleblower_protected', 'is_peacefully_resolved'], true) ? ((int)$r[$k] ? 'Ya' : 'Tidak')
                     : ($k === 'loss_amount' ? number_format((float)$r[$k], 0, ',', '.') : (string)$r[$k]); ?>
                <dt class="text-slate-500"><?= $label ?></dt><dd class="text-slate-100 break-words"><?= htmlspecialchars($val) ?></dd>
              <?php endforeach; ?>
            </dl>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="<?= $card ?>">
        <h3 class="<?= $h3 ?>"><i class="fas fa-stream text-sky-400"></i> Jejak Event Sistem (<?= count($events) ?>)</h3>
        <?php if (!$events): ?><p class="text-xs text-slate-500">Belum ada event.</p><?php endif; ?>
        <ol class="space-y-1 text-[11px] max-h-72 overflow-y-auto">
          <?php foreach ($events as $e): ?>
            <li class="flex flex-wrap gap-2 p-2 rounded-lg bg-[#0C0A08] border border-slate-800">
              <span class="font-mono text-slate-500"><?= $dt($e['created_at'], 'd/m H:i:s') ?></span>
              <span class="font-bold text-sky-300"><?= htmlspecialchars($e['event_name']) ?></span>
              <span class="text-slate-400"><?= htmlspecialchars(trim(($e['actor_nrp_or_system'] ?? '') . ' ' . ($e['actor_role'] ?? ''))) ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>

    <!-- Pelapor & audit PDP -->
    <div class="space-y-6">
      <div class="<?= $card ?>">
        <h3 class="<?= $h3 ?>"><i class="fas fa-user-shield text-amber-400"></i> Pelapor (tersamar)</h3>
        <dl class="text-xs space-y-1.5">
          <div class="flex justify-between gap-2"><dt class="text-slate-500">Nama</dt><dd id="piiName" class="text-slate-100 text-right"><?= $isWhistle ? 'Dirahasiakan (whistleblower)' : htmlspecialchars($t['citizen_name']) ?></dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">NIK</dt><dd id="piiNik" class="text-slate-100 font-mono"><?= htmlspecialchars($t['citizen_nik_masked'] ?? '-') ?></dd></div>
          <div class="flex justify-between gap-2"><dt class="text-slate-500">No. HP</dt><dd id="piiPhone" class="text-slate-100 font-mono"><?= htmlspecialchars($t['citizen_phone']) ?></dd></div>
        </dl>
        <form id="piiForm" class="mt-4 space-y-2">
          <textarea id="piiReason" required minlength="10" rows="2" placeholder="Dasar kepentingan dinas membuka data pribadi (wajib, tercatat di audit UU PDP)"
                    class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-xs text-slate-100 outline-none focus:border-amber-500"></textarea>
          <button class="w-full py-2 rounded-xl bg-amber-500/15 border border-amber-500/50 text-amber-300 text-xs font-bold"><i class="fas fa-lock-open mr-1"></i> Buka data pribadi (beraudit)</button>
          <p id="piiMsg" class="text-[11px] text-slate-500"></p>
        </form>
      </div>

      <div class="<?= $card ?>">
        <h3 class="<?= $h3 ?>"><i class="fas fa-clipboard-list text-purple-400"></i> Log Akses Data Pribadi (<?= count($pdpLogs) ?>)</h3>
        <ol class="space-y-1.5 text-[11px] max-h-80 overflow-y-auto">
          <?php foreach ($pdpLogs as $l): ?>
            <li class="p-2 rounded-lg bg-[#0C0A08] border <?= (int)$l['is_out_of_jurisdiction'] ? 'border-red-800' : 'border-slate-800' ?>">
              <div class="flex justify-between gap-2"><span class="font-bold text-slate-200"><?= htmlspecialchars($l['viewer_name'] ?: $l['viewer_nrp']) ?></span><span class="font-mono text-slate-500"><?= $dt($l['accessed_at'], 'd/m H:i') ?></span></div>
              <div class="text-slate-400"><?= htmlspecialchars($l['accessed_data']) ?><?= (int)$l['is_out_of_jurisdiction'] ? ' · <span class="text-red-300 font-bold">di luar wilayah</span>' : '' ?></div>
              <div class="text-slate-500 italic"><?= htmlspecialchars((string)$l['justification']) ?></div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('piiForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const reason = document.getElementById('piiReason').value.trim();
    const msg = document.getElementById('piiMsg');
    const fd = new FormData();
    fd.append('_csrf_token', <?= json_encode($csrf) ?>);
    fd.append('tracking_number', <?= json_encode($t['tracking_number']) ?>);
    fd.append('justification', reason);
    try {
      const res = await fetch(<?= json_encode($baseUrl . '/oneservice/apiRevealPii') ?>, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      if (!data.success) throw new Error(data.error || 'Gagal membuka data');
      document.getElementById('piiNik').textContent = data.citizen_nik;
      document.getElementById('piiPhone').textContent = data.citizen_phone;
      document.getElementById('piiName').textContent = data.citizen_name;
      msg.textContent = 'Data dibuka dan tercatat di log akses UU PDP.';
      msg.className = 'text-[11px] text-emerald-300';
    } catch (err) {
      msg.textContent = err.message;
      msg.className = 'text-[11px] text-red-300';
    }
  });
</script>
