<?php
/**
 * View: Modul 2 Triase & CAD 110 (Computer-Aided Dispatch) Presisi
 * Menangani respon cepat panggilan darurat 110, alokasi unit patroli & pelacak waktu tiba di TKP
 */
?>
<div class="space-y-8">
  <!-- Header & Breadcrumb -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-bold tracking-wider uppercase mb-2">
        <i class="fas fa-headset animate-pulse"></i> MODUL 2: COMPUTER-AIDED DISPATCH (CAD 110)
      </div>
      <h1 class="text-2xl md:text-3xl font-heading font-black text-slate-100 tracking-tight">
        Triase &amp; Command Center 110 Presisi
      </h1>
      <p class="text-xs md:text-sm text-slate-400 font-light mt-1">
        Target Kinerja Kapolri: <strong class="text-amber-400 font-semibold">Time to Dispatch &lt; 2 menit</strong> dan <strong class="text-emerald-400 font-semibold">Arrival at Scene &lt; 15 menit</strong>.
      </p>
    </div>
    
    <div class="flex items-center gap-3">
      <button onclick="document.getElementById('modalNewCall').classList.remove('hidden')"
              class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white text-xs font-black tracking-wide shadow-lg shadow-red-600/30 transition-all flex items-center gap-2">
        <i class="fas fa-phone-volume"></i>
        <span>Panggilan 110 Baru</span>
      </button>
      <a href="<?= $baseUrl ?>/oneservice" class="px-4 py-2.5 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C] transition-all">
        <i class="fas fa-arrow-left mr-1.5 text-amber-500"></i> Hub Terpadu
      </a>
    </div>
  </div>

  <!-- Notifikasi Pesan / Error -->
  <?php if (!empty($message)): ?>
    <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-check text-emerald-400 text-base"></i>
      <div>
        <p class="font-bold">Aksi Berhasil</p>
        <p class="text-emerald-200"><?= htmlspecialchars($message) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-exclamation text-red-400 text-base"></i>
      <div>
        <p class="font-bold">Aksi Gagal Dilakukan</p>
        <p class="text-red-200"><?= htmlspecialchars($error) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Indikator Metrik Cepat CAD 110 -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg relative overflow-hidden">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Panggilan Masuk</span>
        <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fas fa-phone-flip text-sm"></i></span>
      </div>
      <div class="text-2xl font-heading font-black text-slate-100"><?= number_format($stats['total_calls']) ?></div>
      <div class="text-[11px] text-slate-400 mt-1">
        Unit Tiba di TKP: <span class="text-emerald-400 font-semibold"><?= $stats['total_arrived'] ?> lokasi</span>
      </div>
    </div>

    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg relative overflow-hidden">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rata-Rata Respon (Menit)</span>
        <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"><i class="fas fa-stopwatch text-sm"></i></span>
      </div>
      <div class="text-2xl font-heading font-black text-emerald-400"><?= $stats['avg_response_minutes'] ?> <span class="text-sm font-normal text-slate-400">menit</span></div>
      <div class="text-[11px] text-slate-400 mt-1">
        Presisi Detik: <span class="font-mono text-slate-300"><?= $stats['avg_response_seconds'] ?> detik</span>
      </div>
    </div>

    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg relative overflow-hidden">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kepatuhan SLA (&lt; 15 Mnt)</span>
        <span class="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20"><i class="fas fa-trophy text-sm"></i></span>
      </div>
      <div class="text-2xl font-heading font-black text-amber-300"><?= $stats['sla_compliance_rate'] ?>%</div>
      <div class="text-[11px] text-slate-400 mt-1">
        Batas Standar Presisi: &le; 900 detik
      </div>
    </div>

    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-5 shadow-lg relative overflow-hidden">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status Integrasi</span>
        <span class="p-2 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20"><i class="fas fa-tower-broadcast text-sm"></i></span>
      </div>
      <div class="text-sm font-heading font-black text-amber-400 uppercase tracking-wider flex items-center gap-1.5 mt-1">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
        <?= \App\Core\OpsAdapter::isSandbox() ? 'MOCK / SANDBOX AKTIF' : 'LIVE REST CAD 110' ?>
      </div>
      <div class="text-[11px] text-slate-400 mt-2">
        Auto Transactional Outbox Event Bus
      </div>
    </div>
  </div>

  <!-- Tabel Antrean Panggilan & CAD Dispatch -->
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl shadow-2xl overflow-hidden">
    <div class="ribbon-merah-putih"></div>
    <div class="p-6 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#3B2F1C] pb-4">
        <div>
          <h2 class="text-lg font-heading font-black text-slate-100 flex items-center gap-2">
            <i class="fas fa-satellite-dish text-red-500"></i> Antrean Panggilan Darurat &amp; Dispatch CAD Terkini
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">Daftar panggilan darurat 110 yang masuk secara real-time dari operator telepon.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="border-b border-[#3B2F1C] text-amber-500/90 uppercase font-bold tracking-wider text-[11px] bg-[#1B1610]">
              <th class="py-3 px-4">Nomor Lacak / Waktu</th>
              <th class="py-3 px-4">Penelpon &amp; Kategori</th>
              <th class="py-3 px-4">Urgensi</th>
              <th class="py-3 px-4">Unit Penugasan (CAD)</th>
              <th class="py-3 px-4">Waktu Respon</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-center">Aksi Cepat</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#3B2F1C]/60">
            <?php if (empty($calls)): ?>
              <tr>
                <td colspan="7" class="py-8 text-center text-slate-500 italic">
                  Belum ada data panggilan 110. Klik tombol "Panggilan 110 Baru" untuk membuat simulasi.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($calls as $call): ?>
                <tr class="hover:bg-[#1B1610]/60 transition-colors">
                  <td class="py-3.5 px-4">
                    <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($call['tracking_number']) ?>" 
                       class="font-mono font-bold text-amber-400 hover:underline block">
                      <?= htmlspecialchars($call['tracking_number']) ?>
                    </a>
                    <span class="text-[10px] text-slate-400">
                      <i class="far fa-clock mr-0.5"></i> <?= date('d/m/Y H:i', strtotime($call['call_received_at'])) ?>
                    </span>
                  </td>

                  <td class="py-3.5 px-4">
                    <div class="font-semibold text-slate-200"><?= htmlspecialchars($call['caller_name'] ?? 'Penelpon 110') ?></div>
                    <div class="text-[11px] text-slate-400 font-mono"><?= \App\Core\OpsPdp::maskPhone($call['caller_phone']) ?></div>
                    <div class="text-[11px] text-slate-300 mt-0.5 max-w-xs truncate" title="<?= htmlspecialchars($call['incident_summary']) ?>">
                      <?= htmlspecialchars($call['incident_summary']) ?>
                    </div>
                  </td>

                  <td class="py-3.5 px-4">
                    <?php if ($call['urgency_level'] === 'P1_EMERGENCY'): ?>
                      <span class="px-2 py-0.5 rounded-full bg-red-500/20 border border-red-500/40 text-red-300 font-bold text-[10px]">
                        P1 EMERGENCY
                      </span>
                    <?php elseif ($call['urgency_level'] === 'P2_URGENT'): ?>
                      <span class="px-2 py-0.5 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 font-bold text-[10px]">
                        P2 URGENT
                      </span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 rounded-full bg-blue-500/20 border border-blue-500/40 text-blue-300 font-bold text-[10px]">
                        P3 ROUTINE
                      </span>
                    <?php endif; ?>
                  </td>

                  <td class="py-3.5 px-4">
                    <?php if (!empty($call['unit_callsign'])): ?>
                      <div class="font-bold text-slate-200 flex items-center gap-1.5">
                        <i class="fas fa-car-side text-amber-400"></i>
                        <span><?= htmlspecialchars($call['unit_callsign']) ?></span>
                      </div>
                      <div class="text-[10px] text-slate-400"><?= htmlspecialchars($call['patrol_vehicle_type'] ?? 'SAMAPTA') ?></div>
                    <?php else: ?>
                      <span class="text-slate-500 italic">Belum didisposisi</span>
                    <?php endif; ?>
                  </td>

                  <td class="py-3.5 px-4">
                    <?php if ($call['response_time_seconds'] !== null): ?>
                      <div class="font-mono font-bold <?= $call['response_time_seconds'] <= 900 ? 'text-emerald-400' : 'text-red-400' ?>">
                        <?= round($call['response_time_seconds'] / 60, 1) ?> menit
                      </div>
                      <div class="text-[10px] text-slate-400 font-mono"><?= $call['response_time_seconds'] ?> detik</div>
                    <?php else: ?>
                      <span class="text-slate-500 italic">-</span>
                    <?php endif; ?>
                  </td>

                  <td class="py-3.5 px-4">
                    <?php
                      $statBadges = [
                        'CALL_RECEIVED'   => 'bg-[#1B1610] text-slate-300 border border-[#3B2F1C]',
                        'DISPATCHED'      => 'bg-amber-500/20 text-amber-300 border border-amber-500/40',
                        'ON_SCENE'        => 'bg-emerald-600/30 text-emerald-300 border border-emerald-500/40',
                        'RESOLVED_ONSITE' => 'bg-green-600/30 text-green-300 border border-green-500/40',
                        'ESCALATED_SPKT'  => 'bg-purple-600/30 text-purple-300 border border-purple-500/40'
                      ];
                      $badgeCls = $statBadges[$call['status']] ?? 'bg-[#1B1610] text-slate-300 border border-[#3B2F1C]';
                    ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $badgeCls ?>">
                      <?= htmlspecialchars($call['status']) ?>
                    </span>
                  </td>

                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <?php if ($call['status'] === 'CALL_RECEIVED'): ?>
                        <button onclick="openDispatchModal('<?= htmlspecialchars($call['tracking_number']) ?>')"
                                class="px-2.5 py-1 rounded-lg btn-gold text-slate-950 font-black text-[11px] shadow-md transition-all flex items-center gap-1">
                          <i class="fas fa-paper-plane"></i> Dispatch
                        </button>
                      <?php elseif ($call['status'] === 'DISPATCHED'): ?>
                        <button onclick="openArriveModal('<?= htmlspecialchars($call['tracking_number']) ?>')"
                                class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] transition-all flex items-center gap-1">
                          <i class="fas fa-location-dot"></i> Tiba di TKP
                        </button>
                      <?php else: ?>
                        <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($call['tracking_number']) ?>"
                           class="px-2.5 py-1 rounded-lg bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-[11px] border border-[#3B2F1C]">
                          <i class="fas fa-eye text-amber-500"></i> Detail
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal 1: Registrasi Panggilan 110 Baru -->
<div id="modalNewCall" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
    <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
      <div class="flex items-center gap-2 text-amber-400">
        <i class="fas fa-phone-volume text-xl"></i>
        <h3 class="text-base font-heading font-black text-slate-100">Konsol Operator Panggilan 110</h3>
      </div>
      <button type="button" onclick="document.getElementById('modalNewCall').classList.add('hidden')" class="text-slate-400 hover:text-white">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/oneservice/dispatch" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
      <input type="hidden" name="action" value="create_call">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nomor Telepon Penelpon</label>
          <input type="text" name="caller_phone" required placeholder="Contoh: 081234567890"
                 class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Penelpon</label>
          <input type="text" name="caller_name" placeholder="Nama atau Anonim"
                 class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tingkat Urgensi</label>
          <select name="urgency_level" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
            <option value="P1_EMERGENCY">P1 - Darurat / Ancam Nyawa</option>
            <option value="P2_URGENT">P2 - Mendesak / Butuh Respon Cepat</option>
            <option value="P3_ROUTINE">P3 - Rutin / Pelayanan</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Kategori Kejadian</label>
          <input type="text" name="incident_category" value="DARURAT_110" required
                 class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Satker Yurisdiksi Terdekat</label>
        <select name="satker_id" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
          <?php foreach ($satkers as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Alamat TKP Kejadian</label>
        <input type="text" name="address" required placeholder="Jl. Sudirman No. 45, dekat lampu merah..."
               class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Ringkasan Laporan Panggilan</label>
        <textarea name="call_summary" required rows="3" placeholder="Informasi singkat keterangan korban/saksi..."
                  class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalNewCall').classList.add('hidden')"
                class="px-4 py-2 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C]">
          Batal
        </button>
        <button type="submit"
                class="px-5 py-2 rounded-xl btn-gold text-slate-950 font-black text-xs shadow-lg">
          Registrasi &amp; Simpan Tiket
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 2: Disposisi Unit Patroli (CAD) -->
<div id="modalDispatch" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
    <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
      <div class="flex items-center gap-2 text-amber-400">
        <i class="fas fa-paper-plane text-lg"></i>
        <h3 class="text-base font-heading font-black text-slate-100">Disposisi Cepat Unit Patroli</h3>
      </div>
      <button type="button" onclick="document.getElementById('modalDispatch').classList.add('hidden')" class="text-slate-400 hover:text-white">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/oneservice/dispatch" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
      <input type="hidden" name="action" value="dispatch_unit">
      <input type="hidden" id="dispatchTrackingInput" name="tracking_number" value="">

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nomor Lambung / Unit Callsign</label>
        <input type="text" name="unit_callsign" required value="PATROLI-SAMAPTA-01"
               class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-bold">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Jenis Kendaraan Operasional</label>
        <select name="vehicle_type" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
          <option value="SAMAPTA_CAR">Mobil Patroli Samapta (Roda 4)</option>
          <option value="LANTAS_MOTOR">Motor Quick Response Lantas (Roda 2)</option>
          <option value="BHABIN_MOBILE">Bhabinkamtibmas Quick Mobile</option>
          <option value="WATER_PATROL">Kapal Patroli Cepat Polairud</option>
        </select>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalDispatch').classList.add('hidden')"
                class="px-4 py-2 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C]">
          Batal
        </button>
        <button type="submit"
                class="px-5 py-2 rounded-xl btn-gold text-slate-950 text-xs font-black shadow-lg">
          Kirim Penugasan CAD
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal 3: Konfirmasi Tiba di TKP -->
<div id="modalArrive" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
    <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
      <div class="flex items-center gap-2 text-emerald-400">
        <i class="fas fa-location-dot text-lg"></i>
        <h3 class="text-base font-heading font-black text-slate-100">Konfirmasi Tiba di TKP</h3>
      </div>
      <button type="button" onclick="document.getElementById('modalArrive').classList.add('hidden')" class="text-slate-400 hover:text-white">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/oneservice/dispatch" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
      <input type="hidden" name="action" value="mark_arrived">
      <input type="hidden" id="arriveTrackingInput" name="tracking_number" value="">

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Catatan Singkat Situasi TKP</label>
        <textarea name="scene_notes" required rows="3"
                  placeholder="Contoh: Unit tiba di lokasi, situasi telah terkendali dan korban diamankan untuk penanganan medis."
                  class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalArrive').classList.add('hidden')"
                class="px-4 py-2 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C]">
          Batal
        </button>
        <button type="submit"
                class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30">
          Konfirmasi Tiba Sekarang
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openDispatchModal(trackNum) {
  document.getElementById('dispatchTrackingInput').value = trackNum;
  document.getElementById('modalDispatch').classList.remove('hidden');
}
function openArriveModal(trackNum) {
  document.getElementById('arriveTrackingInput').value = trackNum;
  document.getElementById('modalArrive').classList.remove('hidden');
}
</script>
