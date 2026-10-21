<?php
/**
 * View: Pelacak Status Kasus "Satu Nomor, Satu Jejak" (v1.3 Presisi)
 * Akses Publik & Internal Berbasis Nomor Pelacakan Global + PIN 6-Digit
 */
?>
<div class="space-y-8 max-w-5xl mx-auto">
  <!-- Header Bar -->
  <div class="text-center space-y-3">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold tracking-wider uppercase font-heading">
      <i class="fas fa-shield-halved"></i> SISTEM INTEGRASI SATU NOMOR, SATU JEJAK
    </div>
    <h1 class="text-3xl md:text-4xl font-black text-slate-100 font-heading tracking-tight">
      Pelacak Layanan Terpadu &amp; SP2HP
    </h1>
    <p class="text-slate-400 text-sm md:text-base max-w-2xl mx-auto font-light">
      Pantau langsung setiap tahapan perkembangan penanganan laporan Anda dari panggilan darurat 110, berkas SPKT, penyelidikan Reskrim, hingga koordinasi JPU Kejaksaan secara transparan.
    </p>
  </div>

  <!-- Form Pencarian Kasus -->
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 md:p-8 shadow-2xl relative overflow-hidden">
    <div class="relative z-10">
      <form action="<?= $baseUrl ?>/oneservice/track" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div class="md:col-span-7">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2 font-heading">
              <i class="fas fa-barcode text-amber-400 mr-1.5"></i> Nomor Pelacakan Global
            </label>
            <input type="text" name="ticket" required
                   placeholder="Contoh: POL-202610-110-8924A atau POL-202610-SPKT-7712B"
                   value="<?= htmlspecialchars($trackingNumber ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 font-mono text-base placeholder-slate-600 tracking-wider transition-all">
            <p class="text-[11px] text-slate-400 mt-1">Nomor tercantum pada lembar tanda terima SPKT atau SMS resmi dari Mabes/Polda.</p>
          </div>
          <div class="md:col-span-3">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2 font-heading">
              <i class="fas fa-key text-amber-400 mr-1.5"></i> PIN Keamanan (6 Digit)
            </label>
            <input type="text" name="pin" required maxlength="6" pattern="\d{6}"
                   placeholder="6 Angka Rahasia"
                   value="<?= htmlspecialchars($pin ?? '') ?>"
                   class="w-full px-4 py-3 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 font-mono text-center text-base tracking-widest placeholder-slate-600 transition-all">
            <p class="text-[11px] text-slate-400 mt-1">Dikirim ke nomor HP pelapor.</p>
          </div>
          <div class="md:col-span-2 flex items-end">
            <button type="submit"
                    class="btn-gold w-full py-3 px-4 rounded-xl text-xs font-black tracking-wide shadow-lg shadow-amber-500/20 transition-all flex items-center justify-center gap-2">
              <i class="fas fa-search"></i>
              <span>Lacak</span>
            </button>
          </div>
        </div>
      </form>
    </div>
    <div class="absolute -right-6 -bottom-6 opacity-5 pointer-events-none">
      <i class="fas fa-fingerprint text-9xl text-amber-400"></i>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-sm flex items-start gap-3 shadow-lg">
      <i class="fas fa-circle-exclamation text-red-400 mt-0.5 text-base"></i>
      <div>
        <p class="font-bold">Akses Pelacakan Ditolak</p>
        <p class="text-xs text-red-200 mt-0.5"><?= htmlspecialchars($error) ?></p>
        <p class="text-[11px] text-red-300/80 mt-2">
          Periksa kembali nomor pelacakan dan PIN Anda. Jika Anda kehilangan PIN, hubungi layanan operator SPKT terdekat dengan membawa kartu identitas asli (KTP).
        </p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($caseData)): ?>
    <!-- Kartu Informasi Utama Kasus -->
    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 md:p-8 shadow-2xl space-y-6">
      
      <!-- Top Row: Identitas Kasus & Badges -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#3B2F1C] pb-6">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest font-heading">Global Tracking ID</span>
            <?php if ($caseData['is_stalled']): ?>
              <span class="px-2 py-0.5 rounded-full bg-red-500/20 border border-red-500/40 text-red-400 text-[10px] font-extrabold uppercase animate-pulse">
                <i class="fas fa-triangle-exclamation"></i> Terdeteksi Macet / Melewati SLA
              </span>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-2xl md:text-3xl font-black font-mono text-amber-400 tracking-wider select-all" id="trackNumTxt">
              <?= htmlspecialchars($caseData['tracking_number']) ?>
            </span>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($caseData['tracking_number']) ?>'); alert('Nomor lacak disalin!');"
                    class="p-1.5 rounded-lg bg-[#1B1610] hover:bg-[#241D14] text-slate-300 hover:text-white text-xs border border-[#3B2F1C]" title="Salin Nomor">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Kanal Asal -->
          <span class="px-3 py-1 rounded-full bg-[#1B1610] text-slate-300 border border-[#3B2F1C] text-xs font-semibold">
            <i class="fas fa-network-wired text-amber-400 mr-1"></i> Kanal: <?= htmlspecialchars($caseData['source_channel']) ?>
          </span>

          <!-- Prioritas -->
          <?php
            $priorityColors = [
              'P1_EMERGENCY' => 'bg-red-500/20 text-red-300 border-red-500/40',
              'P2_URGENT'    => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
              'P3_ROUTINE'   => 'bg-blue-500/20 text-blue-300 border-blue-500/40'
            ];
            $pColor = $priorityColors[$caseData['priority_level']] ?? 'bg-slate-700 text-slate-300 border-slate-600';
          ?>
          <span class="px-3 py-1 rounded-full border text-xs font-bold <?= $pColor ?>">
            <?= htmlspecialchars($caseData['priority_level']) ?>
          </span>

          <!-- Status Saat Ini -->
          <?php
            $statusColors = [
              'INCOMING'       => 'bg-sky-500/20 text-sky-300 border-sky-500/40',
              'DISPATCHED'     => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40',
              'VERIFIED_SPKT'  => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
              'INVESTIGATION'  => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
              'RESOLVED'       => 'bg-green-500/20 text-green-300 border-green-500/40',
              'CLOSED'         => 'bg-slate-700 text-slate-300 border-slate-600',
              'REJECTED'       => 'bg-red-900/40 text-red-300 border-red-700/60'
            ];
            $sColor = $statusColors[$caseData['current_status']] ?? 'bg-slate-800 text-slate-300 border-slate-700';
          ?>
          <span class="px-3 py-1 rounded-full border text-xs font-extrabold flex items-center gap-1.5 <?= $sColor ?>">
            <span class="w-2 h-2 rounded-full bg-current animate-ping"></span>
            STATUS: <?= htmlspecialchars($caseData['current_status']) ?>
          </span>
        </div>
      </div>

      <!-- Detail Kasus Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <div class="space-y-1">
          <span class="text-xs text-slate-400 font-bold uppercase tracking-wider font-heading">Kategori Perkara</span>
          <p class="text-slate-100 font-bold text-base"><?= htmlspecialchars($caseData['incident_category']) ?></p>
          <p class="text-xs text-slate-400 mt-1"><?= nl2br(htmlspecialchars($caseData['incident_summary'])) ?></p>
        </div>

        <div class="space-y-1">
          <span class="text-xs text-slate-400 font-bold uppercase tracking-wider font-heading">Satuan Kerja Penangan</span>
          <p class="text-slate-100 font-bold"><?= htmlspecialchars($caseData['satker_name'] ?? 'Polda / Satker Terkait') ?></p>
          <div class="text-xs text-slate-400 mt-1 space-y-0.5">
            <p><i class="fas fa-user-shield text-amber-400 mr-1"></i> Penyidik: <?= htmlspecialchars($caseData['officer_name'] ?? 'Dalam Penugasan Kanit') ?></p>
            <?php if (!empty($caseData['external_reference_no'])): ?>
              <p><i class="fas fa-link text-blue-400 mr-1"></i> No. Register e-MP: <span class="font-mono text-slate-300"><?= htmlspecialchars($caseData['external_reference_no']) ?></span></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="space-y-1">
          <span class="text-xs text-slate-400 font-bold uppercase tracking-wider font-heading">Target Waktu &amp; SLA Layanan</span>
          <div class="text-xs text-slate-300 space-y-1">
            <p>Didaftarkan: <span class="text-slate-100 font-medium"><?= date('d M Y, H:i', strtotime($caseData['created_at'])) ?> WIB</span></p>
            <?php if (!empty($caseData['sla_deadline'])): ?>
              <p>Tenggat SLA SP2HP: 
                <span class="font-semibold <?= $caseData['is_stalled'] ? 'text-red-400 font-bold' : 'text-emerald-400' ?>">
                  <?= date('d M Y, H:i', strtotime($caseData['sla_deadline'])) ?> WIB
                </span>
              </p>
            <?php endif; ?>
            <?php if (!empty($caseData['closed_at'])): ?>
              <p class="text-green-400"><i class="fas fa-check-circle mr-1"></i> Perkara Tuntas: <?= date('d M Y, H:i', strtotime($caseData['closed_at'])) ?> WIB</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Data Pelapor & Kepatuhan UU PDP (AES-256 Protected) -->
      <div class="bg-[#1B1610] border border-[#3B2F1C] rounded-xl p-4 md:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
          <div class="flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 text-xs">
              <i class="fas fa-user-lock"></i>
            </span>
            <span class="text-xs font-bold text-slate-300 uppercase tracking-wider font-heading">
              Data Pribadi Pelapor (Dilindungi Enkripsi AES-256 UU PDP)
            </span>
          </div>

          <?php if (\App\Core\Auth::check()): ?>
            <button onclick="openPdpModal('<?= htmlspecialchars($caseData['tracking_number']) ?>')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#14100C] hover:bg-[#241D14] text-amber-400 text-xs font-semibold border border-amber-500/30 transition-all">
              <i class="fas fa-eye"></i> Buka Data Pribadi (Dual-Control Audit)
            </button>
          <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <span class="text-slate-400">Nama Pelapor / Korban:</span>
            <p class="text-slate-100 font-medium mt-0.5" id="pdpName">
              <?= $caseData['is_whistleblower'] ? '🛡️ IDENTITAS WHISTLEBLOWER DIRAHSIAKAN' : htmlspecialchars($caseData['citizen_name']) ?>
            </p>
          </div>
          <div>
            <span class="text-slate-400">Nomor Induk Kependudukan (NIK):</span>
            <p class="text-slate-100 font-mono font-medium mt-0.5 tracking-wider" id="pdpNik">
              <?= htmlspecialchars($caseData['citizen_nik_masked']) ?>
            </p>
          </div>
          <div>
            <span class="text-slate-400">Nomor Kontak:</span>
            <p class="text-slate-100 font-mono font-medium mt-0.5" id="pdpPhone">
              <?= htmlspecialchars($caseData['citizen_phone_masked']) ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline Terpadu: SP2HP Elektronik & Event Bus -->
    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 md:p-8 shadow-2xl space-y-6">
      <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-4">
        <div>
          <h2 class="text-xl font-black text-slate-100 font-heading flex items-center gap-2">
            <i class="fas fa-timeline text-amber-400"></i> Rekam Jejak Perkara &amp; SP2HP Elektronik
          </h2>
          <p class="text-xs text-slate-400 mt-1">
            Semua perubahan status tercatat secara permanen (*immutable*) dalam Transactional Outbox Event Bus Polri.
          </p>
        </div>
        <button onclick="window.print()" class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#1B1610] hover:bg-[#241D14] text-xs text-slate-300 font-semibold border border-[#3B2F1C]">
          <i class="fas fa-print"></i> Cetak Lembar Progres
        </button>
      </div>

      <?php if (empty($timeline)): ?>
        <p class="text-xs text-slate-400 py-6 text-center italic">Belum ada pembaruan milestone perkara.</p>
      <?php else: ?>
        <div class="relative pl-6 md:pl-8 space-y-8 before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#3B2F1C]">
          <?php foreach ($timeline as $idx => $item): ?>
            <?php
              $isMilestone = ($item['item_type'] ?? '') === 'MILESTONE';
              $dotBg = $isMilestone ? 'bg-amber-500 ring-4 ring-amber-500/20' : 'bg-blue-500 ring-4 ring-blue-500/20';
              $icon = $isMilestone ? 'fa-file-shield' : 'fa-bolt';
            ?>
            <div class="relative group">
              <!-- Timeline Marker Icon -->
              <div class="absolute -left-6 md:-left-8 top-1 w-6 h-6 rounded-full <?= $dotBg ?> flex items-center justify-center text-[10px] text-slate-950 font-bold">
                <i class="fas <?= $icon ?>"></i>
              </div>

              <!-- Card Content -->
              <div class="bg-[#1B1610] border border-[#3B2F1C] rounded-xl p-5 hover:border-amber-500/40 transition-all shadow-md">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                  <div class="flex items-center gap-2">
                    <?php if ($isMilestone): ?>
                      <span class="px-2 py-0.5 rounded-md bg-amber-500/20 border border-amber-500/40 text-amber-300 font-mono text-xs font-black">
                        SP2HP <?= htmlspecialchars($item['stage_code']) ?>
                      </span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 rounded-md bg-blue-500/20 border border-blue-500/40 text-blue-300 font-mono text-[11px] font-bold">
                        EVENT LOG
                      </span>
                    <?php endif; ?>
                    <h3 class="text-base font-black text-slate-100 font-heading"><?= htmlspecialchars($item['title']) ?></h3>
                  </div>
                  <span class="text-xs text-slate-400 font-mono">
                    <i class="far fa-clock mr-1"></i> <?= date('d M Y, H:i', strtotime($item['event_time'])) ?> WIB
                  </span>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                  <?= nl2br(htmlspecialchars($item['description_public'] ?? '')) ?>
                </p>

                <?php if ($isMilestone && !empty($item['qr_verification_hash'])): ?>
                  <div class="mt-4 pt-3 border-t border-[#3B2F1C] flex flex-wrap items-center justify-between gap-3 text-[11px]">
                    <div class="flex items-center gap-2 text-slate-400">
                      <i class="fas fa-qrcode text-amber-400 text-sm"></i>
                      <span>Kode Verifikasi Anti-Pemalsuan:</span>
                      <span class="font-mono text-amber-300 bg-[#0C0A08] px-2 py-0.5 rounded border border-[#3B2F1C]">
                        <?= substr(htmlspecialchars($item['qr_verification_hash']), 0, 16) ?>...
                      </span>
                    </div>
                    <span class="text-slate-400 font-mono">
                      Penyidik NRP: <?= htmlspecialchars($item['officer_nrp'] ?? '-') ?>
                    </span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Modal Buka Data Pribadi (UU PDP Dual-Control) -->
<div id="pdpModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="polri-glass-card bg-[#14100C] border border-amber-500/40 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
    <div class="flex items-center gap-2 text-amber-400">
      <i class="fas fa-shield-cat text-2xl"></i>
      <h3 class="text-lg font-black text-slate-100 font-heading">Dual-Control Audit UU PDP</h3>
    </div>
    <p class="text-xs text-slate-300 leading-relaxed">
      Sesuai UU Perlindungan Data Pribadi No. 27/2022, akses data sensitif pelapor (NIK &amp; No. HP) wajib disertai alasan dinas resmi. Aksi ini akan dicatat dalam <code class="text-amber-300 font-mono">ops_audit_pdp_logs</code> dan diawasi oleh Divpropam.
    </p>

    <form id="pdpForm" onsubmit="submitPdpReveal(event)">
      <input type="hidden" id="pdpTrackingInput" name="tracking_number" value="">
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1 font-heading">Alasan Dinas Membuka Data</label>
          <textarea id="pdpJustification" required rows="3"
                    placeholder="Contoh: Konfirmasi identitas saksi pelapor untuk gelar perkara khusus tingkat Polsek."
                    class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] text-slate-100 text-xs placeholder-slate-600 focus:border-amber-500"></textarea>
        </div>
      </div>
      <div class="flex items-center justify-end gap-3 mt-5">
        <button type="button" onclick="closePdpModal()" class="px-4 py-2 rounded-xl bg-[#1B1610] text-slate-300 hover:bg-[#241D14] text-xs font-semibold border border-[#3B2F1C]">
          Batal
        </button>
        <button type="submit" class="btn-gold px-4 py-2 rounded-xl text-xs font-black">
          Buka &amp; Catat Audit
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openPdpModal(trackingNumber) {
  document.getElementById('pdpTrackingInput').value = trackingNumber;
  document.getElementById('pdpModal').classList.remove('hidden');
}
function closePdpModal() {
  document.getElementById('pdpModal').classList.add('hidden');
}
function submitPdpReveal(e) {
  e.preventDefault();
  const trackNum = document.getElementById('pdpTrackingInput').value;
  const reason = document.getElementById('pdpJustification').value;

  const fd = new FormData();
  fd.append('_csrf_token', '<?= \App\Core\Session::csrfToken() ?>');
  fd.append('tracking_number', trackNum);
  fd.append('justification', reason);

  fetch('<?= $baseUrl ?>/oneservice/apiRevealPii', {
    method: 'POST',
    body: fd
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('pdpNik').innerText = data.citizen_nik;
      document.getElementById('pdpNik').classList.add('text-amber-300', 'font-bold');
      document.getElementById('pdpPhone').innerText = data.citizen_phone;
      document.getElementById('pdpName').innerText = data.citizen_name;
      closePdpModal();
      alert('Data pribadi berhasil dibuka dan audit dinas resmi telah dicatat.');
    } else {
      alert('Gagal: ' + (data.error || 'Terjadi kesalahan'));
    }
  })
  .catch(err => {
    alert('Terjadi kesalahan jaringan.');
  });
}
</script>
