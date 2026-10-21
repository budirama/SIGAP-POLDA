<?php
/**
 * View: Modul 4 & Modul 1 Manajemen Perkara & Penerbitan SP2HP Elektronik
 * SLA Countdown, SLA Watchdog Alert, & Sinkronisasi e-MP / Kejaksaan
 */
?>
<div class="space-y-8">
  <!-- Header & Aksi Cepat -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold tracking-wider uppercase mb-2">
        <i class="fas fa-folder-tree"></i> MODUL 1 &amp; 4: CASE MANAGEMENT &amp; SP2HP PRESISI
      </div>
      <h1 class="text-2xl md:text-3xl font-heading font-black text-slate-100 tracking-tight">
        Manajemen Timeline Perkara &amp; SP2HP Elektronik
      </h1>
      <p class="text-xs md:text-sm text-slate-400 font-light mt-1">
        Pemantauan berkala tenggat SLA penyidikan dan otomatisasi pengiriman SP2HP A1–A5 ke pelapor.
      </p>
    </div>
    
    <div class="flex items-center gap-3">
      <form action="<?= $baseUrl ?>/oneservice/cases" method="POST" class="inline">
        <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
        <input type="hidden" name="action" value="run_watchdog">
        <button type="submit"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white text-xs font-bold tracking-wide shadow-lg shadow-amber-600/20 transition-all flex items-center gap-2">
          <i class="fas fa-stopwatch animate-spin"></i>
          <span>Jalankan SLA Watchdog</span>
        </button>
      </form>
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
        <p class="font-bold">Berhasil</p>
        <p class="text-emerald-200"><?= htmlspecialchars($message) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-exclamation text-red-400 text-base"></i>
      <div>
        <p class="font-bold">Gagal</p>
        <p class="text-red-200"><?= htmlspecialchars($error) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Tabel Kasus Aktif -->
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl shadow-2xl overflow-hidden">
    <div class="ribbon-merah-putih"></div>
    <div class="p-6 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#3B2F1C] pb-4">
        <div>
          <h2 class="text-lg font-heading font-black text-slate-100 flex items-center gap-2">
            <i class="fas fa-briefcase text-amber-400"></i> Daftar Perkara Aktif &amp; Monitoring Kepatuhan SP2HP
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">Perkara berstatus STALLED otomatis diprioritaskan di atas untuk gelar perkara khusus pimpinan.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="border-b border-[#3B2F1C] text-amber-500/90 uppercase font-bold tracking-wider text-[11px] bg-[#1B1610]">
              <th class="py-3 px-4">Nomor Lacak Global</th>
              <th class="py-3 px-4">Uraian Kasus &amp; Pelapor</th>
              <th class="py-3 px-4">Penyidik / Satker</th>
              <th class="py-3 px-4">Status &amp; SLA</th>
              <th class="py-3 px-4 text-center">Berkas SP2HP</th>
              <th class="py-3 px-4 text-center">Tindakan Penyidik</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#3B2F1C]/60">
            <?php if (empty($cases)): ?>
              <tr>
                <td colspan="6" class="py-8 text-center text-slate-500 italic">
                  Belum ada data perkara yang terdaftar dalam modul OneService.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($cases as $c): ?>
                <tr class="hover:bg-[#1B1610]/60 transition-colors <?= $c['is_stalled'] ? 'bg-red-950/20' : '' ?>">
                  <td class="py-3.5 px-4 font-mono">
                    <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($c['tracking_number']) ?>" 
                       class="font-bold text-amber-400 hover:underline block text-sm">
                      <?= htmlspecialchars($c['tracking_number']) ?>
                    </a>
                    <span class="text-[10px] text-slate-400">
                      Reg: <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                    </span>
                    <?php if ($c['is_stalled']): ?>
                      <span class="inline-block px-2 py-0.5 mt-1 rounded bg-red-600/30 border border-red-500/40 text-red-300 text-[10px] font-black uppercase animate-pulse">
                        <i class="fas fa-triangle-exclamation"></i> STALLED
                      </span>
                    <?php endif; ?>
                  </td>

                  <td class="py-3.5 px-4">
                    <div class="font-bold text-slate-200"><?= htmlspecialchars($c['incident_category']) ?></div>
                    <div class="text-[11px] text-slate-300 max-w-xs truncate" title="<?= htmlspecialchars($c['incident_summary']) ?>">
                      <?= htmlspecialchars($c['incident_summary']) ?>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-1">
                      Pelapor: <span class="text-slate-200"><?= htmlspecialchars($c['citizen_name']) ?></span>
                    </div>
                  </td>

                  <td class="py-3.5 px-4">
                    <div class="font-semibold text-slate-200"><?= htmlspecialchars($c['satker_name'] ?? 'Satker Terkait') ?></div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                      <i class="fas fa-user text-amber-400 mr-1"></i> <?= htmlspecialchars($c['officer_name'] ?? 'Belum Ditugaskan') ?>
                    </div>
                  </td>

                  <td class="py-3.5 px-4">
                    <?php
                      $statCls = match($c['current_status']) {
                        'RESOLVED', 'CLOSED' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                        'INVESTIGATION'      => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                        default              => 'bg-blue-500/20 text-blue-300 border-blue-500/40'
                      };
                    ?>
                    <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold <?= $statCls ?>">
                      <?= htmlspecialchars($c['current_status']) ?>
                    </span>
                    <?php if (!empty($c['sla_deadline'])): ?>
                      <div class="text-[10px] mt-1 <?= $c['is_stalled'] ? 'text-red-400 font-bold' : 'text-slate-400' ?>">
                        SLA: <?= date('d/m/Y H:i', strtotime($c['sla_deadline'])) ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-1 rounded-full bg-[#1B1610] text-amber-400 font-mono font-bold text-xs border border-[#3B2F1C]">
                      <?= (int)$c['milestone_count'] ?> berkas
                    </span>
                  </td>

                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button onclick="openSp2hpModal('<?= htmlspecialchars($c['tracking_number']) ?>', '<?= htmlspecialchars($c['incident_category']) ?>')"
                              class="px-3 py-1.5 rounded-lg btn-gold text-slate-950 font-bold text-xs shadow-md transition-all flex items-center gap-1">
                        <i class="fas fa-file-circle-plus"></i> Terbitkan SP2HP
                      </button>
                      <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($c['tracking_number']) ?>"
                         class="p-1.5 rounded-lg bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs border border-[#3B2F1C]" title="Buka Detail">
                        <i class="fas fa-external-link-alt text-amber-500"></i>
                      </a>
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

<!-- Modal Penerbitan SP2HP Elektronik -->
<div id="modalSp2hp" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
    <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
      <div class="flex items-center gap-2 text-amber-400">
        <i class="fas fa-stamp text-xl"></i>
        <h3 class="text-base font-heading font-black text-slate-100">Terbitkan SP2HP Elektronik</h3>
      </div>
      <button type="button" onclick="document.getElementById('modalSp2hp').classList.add('hidden')" class="text-slate-400 hover:text-white">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/oneservice/cases" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
      <input type="hidden" name="action" value="issue_sp2hp">
      <input type="hidden" id="sp2hpTrackingInput" name="tracking_number" value="">

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nomor Lacak Global</label>
        <div id="sp2hpDisplayTrack" class="font-mono text-sm font-bold text-amber-400 bg-[#0C0A08] px-3 py-2 rounded-xl border border-[#3B2F1C]">
          -
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tahap SP2HP</label>
          <select name="stage_code" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
            <option value="A1">SP2HP A1 - Penerimaan Laporan</option>
            <option value="A2" selected>SP2HP A2 - Perkembangan Lidik</option>
            <option value="A3">SP2HP A3 - Peningkatan Sidik</option>
            <option value="A4">SP2HP A4 - Berkas Pelimpahan JPU</option>
            <option value="A5">SP2HP A5 - Perkara Tuntas / Henti</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Koordinasi JPU (Kejaksaan)</label>
          <select name="jpu_status" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
            <option value="">- Belum Ada Berkas JPU -</option>
            <option value="BERKAS_TAHAP_1">Tahap 1: Pengiriman Berkas Perkara</option>
            <option value="P19_PETUNJUK">P-19: Pengembalian Petunjuk JPU</option>
            <option value="P21_LENGKAP">P-21: Berkas Dinyatakan Lengkap</option>
            <option value="PELIMPAHAN_TERSANGKA">Tahap 2: Penyerahan Tersangka &amp; BB</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Judul Perkembangan</label>
        <input type="text" name="title" required value="Pemeriksaan Saksi-Saksi Tambahan &amp; Gelar Perkara"
               class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Uraian Publik (Terlihat oleh Pelapor Warga)</label>
        <textarea name="public_notes" required rows="3"
                  placeholder="Informasi resmi yang dapat dibaca pelapor di portal lacak..."
                  class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs"></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Catatan Internal Penyidik (Rahasia)</label>
        <textarea name="internal_notes" rows="2"
                  placeholder="Catatan taktis untuk Kanit / Kasat Reskrim..."
                  class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalSp2hp').classList.add('hidden')"
                class="px-4 py-2 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C]">
          Batal
        </button>
        <button type="submit"
                class="px-5 py-2 rounded-xl btn-gold text-slate-950 text-xs font-black shadow-lg">
          Terbitkan &amp; Kirim Notifikasi Pelapor
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openSp2hpModal(trackNum, category) {
  document.getElementById('sp2hpTrackingInput').value = trackNum;
  document.getElementById('sp2hpDisplayTrack').innerText = trackNum + ' (' + category + ')';
  document.getElementById('modalSp2hp').classList.remove('hidden');
}
</script>
