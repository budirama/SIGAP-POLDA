<div class="space-y-6">
  
  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 polri-glass-card p-6 rounded-2xl border border-[#3B2F1C]">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-950/80 border border-indigo-700/60 text-indigo-300 text-xs font-bold mb-2">
        <i class="fas fa-inbox text-amber-400"></i> Satuan Kerja Operasional
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">
        Inbox Disposisi & Manajemen Kasus Satker
      </h2>
      <p class="text-xs text-slate-400 mt-1">
        Daftar perkara yang didisposisikan oleh SPKT Polda. Terbitkan Surat Perintah Penugasan (Sprintgas) ke Penyidik Pelaksana.
      </p>
    </div>

    <div>
      <span class="text-xs font-bold text-amber-400 bg-amber-500/10 px-3 py-1.5 rounded-xl border border-amber-500/30">
        <i class="fas fa-users-viewfinder mr-1"></i> <?= count($investigators) ?> Penyidik Terdaftar
      </span>
    </div>
  </div>

  <!-- Instruksi Atensi Kapolda yang belum selesai -->
  <?php if (!empty($directives)): ?>
  <div class="polri-glass-card rounded-2xl p-5 border border-red-800/70 bg-red-950/10 space-y-3">
    <h3 class="text-sm font-black text-red-300 flex items-center gap-2">
      <img src="<?= $baseUrl ?>/assets/img/badge-kapolda.svg" alt="" class="w-5 h-5">
      Instruksi Atensi Kapolda (<?= count($directives) ?>)
    </h3>
    <?php foreach ($directives as $d): ?>
      <div class="p-4 rounded-xl bg-[#0C0A08] border border-slate-800 space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px]">
          <span class="font-mono font-bold text-amber-400"><?= htmlspecialchars($d['ticket_number']) ?> · <?= htmlspecialchars($d['report_title']) ?></span>
          <span class="px-2 py-0.5 rounded font-bold border <?= $d['status'] === 'PENDING' ? 'bg-red-950 text-red-300 border-red-800' : 'bg-amber-950 text-amber-300 border-amber-700' ?>">
            <?= $d['status'] === 'PENDING' ? 'Belum Ditanggapi' : 'Diterima' ?>
          </span>
        </div>
        <p class="text-xs text-slate-200 italic">"<?= htmlspecialchars($d['directive_text']) ?>"</p>
        <p class="text-[10px] text-slate-500">
          <?= htmlspecialchars(trim(($d['creator_rank'] ?? '') . ' ' . $d['creator_name'])) ?> · <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
          <?php if (!empty($d['satker_name'])): ?> · Satker: <?= htmlspecialchars($d['satker_name']) ?><?php endif; ?>
        </p>
        <form action="<?= $baseUrl ?>/satker/directive" method="POST" class="flex flex-col sm:flex-row gap-2">
          <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
          <input type="hidden" name="directive_id" value="<?= (int)$d['id'] ?>">
          <input type="text" name="response_notes" maxlength="1000" placeholder="Laporan tanggapan kepada Kapolda"
                 class="flex-1 bg-[#14100C] border border-slate-700 rounded-xl p-2 text-xs text-slate-100 outline-none focus:border-amber-500">
          <?php if ($d['status'] === 'PENDING'): ?>
            <button type="submit" name="action" value="acknowledge" class="px-3 py-2 bg-amber-600 hover:bg-amber-500 text-slate-950 font-bold rounded-xl text-xs">Terima</button>
          <?php endif; ?>
          <button type="submit" name="action" value="resolve" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-bold rounded-xl text-xs">Selesaikan</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Reports List -->
  <div class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#14100C] text-slate-400 border-b border-slate-800 uppercase font-semibold text-[10px] tracking-wider">
          <tr>
            <th class="p-4">No. Tiket</th>
            <th class="p-4">Perkara & Judul</th>
            <th class="p-4">Lokus TKP</th>
            <th class="p-4">Prioritas & SLA</th>
            <th class="p-4">Penyidik Pelaksana</th>
            <th class="p-4">Status</th>
            <th class="p-4 text-center">Tindakan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/80">
          <?php if (empty($reports)): ?>
            <tr>
              <td colspan="7" class="p-8 text-center text-slate-500">
                Tidak ada laporan masuk di inbox Satuan Kerja ini.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($reports as $r): ?>
              <tr class="hover:bg-slate-900/60 transition-colors <?= $r['is_kapolda_attention'] ? 'bg-red-950/20' : '' ?>">
                
                <td class="p-4">
                  <span class="font-mono font-bold text-amber-400"><?= htmlspecialchars($r['ticket_number']) ?></span>
                  <?php if ($r['is_kapolda_attention']): ?>
                    <span class="block text-[9px] font-black text-red-400 animate-pulse mt-0.5">★ ATENSI KAPOLDA</span>
                  <?php endif; ?>
                </td>

                <td class="p-4 max-w-xs">
                  <span class="text-[10px] text-slate-400 font-medium block"><?= htmlspecialchars($r['category_name']) ?></span>
                  <span class="font-bold text-slate-100 line-clamp-2"><?= htmlspecialchars($r['title']) ?></span>
                </td>

                <td class="p-4 text-slate-300">
                  <div class="truncate max-w-[180px]"><?= htmlspecialchars($r['incident_location']) ?></div>
                  <span class="text-[10px] text-slate-500 font-mono block"><?= date('d M Y', strtotime($r['incident_date'])) ?></span>
                </td>

                <td class="p-4">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $r['urgency_level'] === 'EMERGENCY' ? 'bg-red-950 text-red-300 border border-red-800' : 'bg-slate-800 text-slate-300' ?>">
                    <?= $r['urgency_level'] ?>
                  </span>
                  <?php if (!empty($r['sla_deadline'])): ?>
                    <span class="text-[10px] text-slate-500 block mt-1 font-mono">Batas: <?= date('d/m H:i', strtotime($r['sla_deadline'])) ?></span>
                  <?php endif; ?>
                </td>

                <td class="p-4">
                  <?php if (!empty($r['investigator_name'])): ?>
                    <span class="font-bold text-slate-200 block"><?= htmlspecialchars($r['investigator_rank'] . ' ' . $r['investigator_name']) ?></span>
                    <span class="text-[10px] text-emerald-400">✓ Sudah Ditugaskan</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-700 animate-pulse">
                      Belum Ada Penyidik
                    </span>
                  <?php endif; ?>
                </td>

                <td class="p-4">
                  <?= \App\Config\Constants::getStatusBadge($r['status']) ?>
                </td>

                <td class="p-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <?php if (in_array($r['status'], ['DISPATCHED', 'ASSIGNED'], true)): ?>
                    <button onclick="openAssignModal(<?= htmlspecialchars(json_encode(['id' => (int)$r['id'], 'ticket_number' => $r['ticket_number'], 'title' => $r['title'], 'current_satker_id' => $r['current_satker_id'], 'assigned_investigator_id' => $r['assigned_investigator_id']])) ?>)"
                            class="px-3 py-1.5 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-bold rounded-lg text-xs shadow transition-all flex items-center gap-1">
                      <i class="fas fa-user-plus text-[10px]"></i>
                      <span><?= empty($r['investigator_name']) ? 'Tunjuk Penyidik' : 'Ganti' ?></span>
                    </button>
                    <?php endif; ?>

                    <a href="<?= $baseUrl ?>/investigator/detail/<?= $r['id'] ?>"
                       class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition-colors"
                       title="Buka Workspace Kasus">
                      <i class="fas fa-folder-open"></i>
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

<!-- Modal Penunjukan Penyidik (Sprintgas) -->
<div id="modalAssign" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-[#1B1610] border border-amber-500/40 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
    <div class="flex items-center justify-between border-b border-slate-700 pb-3 mb-4">
      <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
        <i class="fas fa-id-card-clip text-amber-400"></i>
        Penerbitan Surat Tugas (Sprintgas)
      </h3>
      <button onclick="document.getElementById('modalAssign').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/satker/assign" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="report_id" id="assign_report_id">

      <div>
        <label class="block text-xs font-semibold text-slate-400 mb-1">Kasus / Laporan Polisi:</label>
        <div id="assign_ticket_label" class="text-xs font-bold text-amber-400 font-mono"></div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Nomor Surat Perintah Tugas (Sprintgas):</label>
        <input type="text" name="sprintgas_number" value="SP.Gas/<?= random_int(100, 999) ?>/<?= date('m/Y') ?>/Ditreskrimum" required
               class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 font-mono outline-none focus:border-amber-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Pilih Personel Penyidik Pelaksana:</label>
        <select name="investigator_id" id="assign_investigator_id" required
                class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500">
          <option value="">-- Pilih Anggota Penyidik --</option>
          <?php foreach ($investigators as $inv): ?>
            <option value="<?= (int)$inv['id'] ?>" data-satker="<?= (int)$inv['satker_id'] ?>">
              <?= htmlspecialchars($inv['rank'] . ' ' . $inv['name'] . ' (NRP: ' . $inv['nrp'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('modalAssign').classList.add('hidden')"
                class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
          Batal
        </button>
        <button type="submit"
                class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-500 text-slate-950 rounded-xl text-xs font-extrabold shadow-md">
          Terbitkan Sprintgas
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openAssignModal(report) {
    document.getElementById('assign_report_id').value = report.id;
    document.getElementById('assign_ticket_label').innerText = report.ticket_number + ' - ' + report.title;
    const select = document.getElementById('assign_investigator_id');
    // Hanya penyidik dari Satker pemilik laporan yang dapat dipilih
    Array.from(select.options).forEach(opt => {
      if (opt.dataset.satker) {
        opt.hidden = opt.disabled = String(opt.dataset.satker) !== String(report.current_satker_id);
      }
    });
    select.value = report.assigned_investigator_id ? String(report.assigned_investigator_id) : '';
    document.getElementById('modalAssign').classList.remove('hidden');
  }
</script>
