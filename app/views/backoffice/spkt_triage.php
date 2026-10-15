<div class="space-y-6">
  
  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 polri-glass-card p-6 rounded-2xl border border-[#1E355B]">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-950/80 border border-blue-700/60 text-blue-300 text-xs font-bold mb-2">
        <i class="fas fa-desktop text-amber-400"></i> Command Center SPKT POLDA
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">
        Meja Triage Laporan & Disposisi Terpusat
      </h2>
      <p class="text-xs text-slate-400 mt-1">
        Verifikasi kelayakan aduan masyarakat dari Portal Web maupun Eskalasi Media Sosial. Tentukan disposisi ke Direktorat atau Polres jajaran.
      </p>
      <?php if (!$canAct): ?>
        <p class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-sky-300 bg-sky-950/60 border border-sky-800/60 px-2.5 py-1 rounded-lg">
          <i class="fas fa-eye"></i> Mode pengawasan (hanya lihat)
        </p>
      <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div class="flex items-center gap-3">
      <div class="px-4 py-2 rounded-xl bg-[#070F1E] border border-slate-800 text-center">
        <div class="text-xs text-slate-400">Total Tiket</div>
        <div class="text-lg font-black text-amber-400 font-mono"><?= (int)$pager['total'] ?></div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Bar -->
  <div class="polri-glass-card p-4 rounded-xl border border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
    <form action="<?= $baseUrl ?>/spkt" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
      <select name="status" onchange="this.form.submit()" 
              class="bg-[#070F1E] border border-slate-700 rounded-xl px-3 py-2 text-slate-200 outline-none focus:border-amber-500">
        <option value="">-- Semua Status --</option>
        <option value="SUBMITTED" <?= $statusFilter === 'SUBMITTED' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
        <option value="DISPATCHED" <?= $statusFilter === 'DISPATCHED' ? 'selected' : '' ?>>Didisposisikan ke Satker</option>
        <option value="ASSIGNED" <?= $statusFilter === 'ASSIGNED' ? 'selected' : '' ?>>Penunjukan Penyidik</option>
        <option value="INVESTIGATING" <?= $statusFilter === 'INVESTIGATING' ? 'selected' : '' ?>>Proses Penyelidikan</option>
        <option value="CLOSED" <?= $statusFilter === 'CLOSED' ? 'selected' : '' ?>>Selesai (BAP)</option>
        <option value="REJECTED" <?= $statusFilter === 'REJECTED' ? 'selected' : '' ?>>Ditolak</option>
      </select>

      <div class="relative flex-1 sm:w-64">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari tiket, judul, pelapor..."
               class="w-full bg-[#070F1E] border border-slate-700 rounded-xl pl-8 pr-3 py-2 text-slate-200 placeholder-slate-500 outline-none focus:border-amber-500">
        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-500"><i class="fas fa-search text-xs"></i></span>
      </div>

      <button type="submit" class="px-4 py-2 bg-blue-900/80 hover:bg-blue-800 text-blue-200 font-bold rounded-xl transition-colors">
        Filter
      </button>

      <?php if (!empty($statusFilter) || !empty($search)): ?>
        <a href="<?= $baseUrl ?>/spkt" class="px-3 py-2 text-slate-400 hover:text-slate-200">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Reports Table -->
  <div class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#0A192F] text-slate-400 border-b border-slate-800 uppercase font-semibold text-[10px] tracking-wider">
          <tr>
            <th class="p-4">No. Tiket & Kanal</th>
            <th class="p-4">Pelapor</th>
            <th class="p-4">Perkara & Judul</th>
            <th class="p-4">Lokus TKP & Waktu</th>
            <th class="p-4">Satker Ditunjuk</th>
            <th class="p-4">Status</th>
            <th class="p-4 text-center">Aksi Triage</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/80">
          <?php if (empty($reports)): ?>
            <tr>
              <td colspan="7" class="p-8 text-center text-slate-500">
                Tidak ada laporan yang sesuai dengan kriteria filter saat ini.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($reports as $r): ?>
              <tr class="hover:bg-slate-900/60 transition-colors <?= $r['is_kapolda_attention'] ? 'bg-red-950/20' : '' ?>">
                
                <!-- Ticket & Channel -->
                <td class="p-4">
                  <div class="font-mono font-bold text-amber-400"><?= htmlspecialchars($r['ticket_number']) ?></div>
                  <div class="mt-1 flex items-center gap-1.5">
                    <?= \App\Config\Constants::getChannelBadge($r['source_channel']) ?>
                    <?php if ($r['is_kapolda_attention']): ?>
                      <span class="px-1.5 py-0.5 rounded bg-red-950 text-red-400 border border-red-800 font-extrabold text-[9px] animate-pulse">
                        ★ ATENSI
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Pelapor (Whistleblower Protection Checked) -->
                <td class="p-4">
                  <div class="font-bold text-slate-200">
                    <?php if ($r['is_anonymous']): ?>
                      <span class="text-amber-400 flex items-center gap-1">
                        <i class="fas fa-user-secret text-xs"></i> 
                        <?= htmlspecialchars(substr($r['reporter_name_raw'], 0, 1) . '*** D***') ?>
                      </span>
                      <span class="text-[9px] text-slate-500 font-mono block">Whistleblower</span>
                    <?php else: ?>
                      <?= htmlspecialchars($r['reporter_name_raw']) ?>
                      <span class="text-[10px] text-slate-400 font-mono block"><?= htmlspecialchars($r['reporter_phone']) ?></span>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Perkara & Judul -->
                <td class="p-4 max-w-xs">
                  <span class="text-[10px] text-slate-400 font-medium block"><?= htmlspecialchars($r['category_name']) ?></span>
                  <span class="font-semibold text-slate-200 line-clamp-2"><?= htmlspecialchars($r['title']) ?></span>
                </td>

                <!-- Lokus TKP -->
                <td class="p-4 text-slate-300">
                  <div class="truncate max-w-[180px]"><?= htmlspecialchars($r['incident_location']) ?></div>
                  <span class="text-[10px] text-slate-500 block font-mono"><?= date('d/m/Y', strtotime($r['incident_date'])) ?></span>
                </td>

                <!-- Satker -->
                <td class="p-4">
                  <?php if (!empty($r['satker_name'])): ?>
                    <span class="font-bold text-sky-400 block"><?= htmlspecialchars($r['satker_name']) ?></span>
                    <span class="text-[10px] text-slate-500">
                      <?= $r['investigator_name'] ? htmlspecialchars($r['investigator_rank'] . ' ' . $r['investigator_name']) : 'Menunggu Personel' ?>
                    </span>
                  <?php else: ?>
                    <span class="text-amber-500/80 italic text-[11px]">Belum Didisposisikan</span>
                  <?php endif; ?>
                </td>

                <!-- Status -->
                <td class="p-4">
                  <?= \App\Config\Constants::getStatusBadge($r['status']) ?>
                </td>

                <!-- Actions -->
                <td class="p-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    
                    <?php if ($canAct && in_array($r['status'], \App\Config\Constants::DISPATCHABLE_STATUSES, true)): ?>
                    <!-- Tombol Disposisi -->
                    <button onclick="openDispatchModal(<?= htmlspecialchars(json_encode(['id' => (int)$r['id'], 'ticket_number' => $r['ticket_number'], 'title' => $r['title'], 'current_satker_id' => $r['current_satker_id'], 'assigned_investigator_id' => $r['assigned_investigator_id']])) ?>)"
                            class="px-2.5 py-1.5 bg-blue-900/60 hover:bg-blue-800 text-blue-200 font-bold rounded-lg transition-colors flex items-center gap-1"
                            title="Disposisi ke Satker">
                      <i class="fas fa-share-nodes text-xs"></i>
                      <span class="hidden lg:inline text-[11px]">Disposisi</span>
                    </button>
                    <?php endif; ?>

                    <?php if ($canAct && in_array($r['status'], \App\Config\Constants::REJECTABLE_STATUSES, true)): ?>
                      <!-- Tombol Tolak -->
                      <button onclick="openRejectModal(<?= htmlspecialchars(json_encode(['id' => (int)$r['id'], 'ticket_number' => $r['ticket_number'], 'title' => $r['title'], 'current_satker_id' => $r['current_satker_id'], 'assigned_investigator_id' => $r['assigned_investigator_id']])) ?>)"
                              class="px-2.5 py-1.5 bg-rose-950/70 hover:bg-rose-900 text-rose-200 font-bold rounded-lg transition-colors flex items-center gap-1"
                              title="Tolak Laporan">
                        <i class="fas fa-ban text-xs"></i>
                        <span class="hidden lg:inline text-[11px]">Tolak</span>
                      </button>
                    <?php endif; ?>

                    <!-- Tombol Cek Detail -->
                    <a href="<?= $baseUrl ?>/report/track?ticket=<?= urlencode($r['ticket_number']) ?>" target="_blank"
                       class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-colors"
                       title="Lihat Detail Perkembangan">
                      <i class="fas fa-eye text-xs"></i>
                    </a>

                  </div>
                </td>

              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php $pagerPath = '/spkt'; require __DIR__ . '/_pagination.php'; ?>
  </div>

</div>

<!-- Modal Disposisi Satker -->
<div id="modalDispatch" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-[#0E1E38] border border-blue-500/40 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
    <div class="flex items-center justify-between border-b border-slate-700 pb-3 mb-4">
      <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
        <i class="fas fa-sitemap text-amber-400"></i>
        Disposisi Laporan ke Satker / Polres
      </h3>
      <button onclick="document.getElementById('modalDispatch').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/spkt/dispatch" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="report_id" id="dispatch_report_id">

      <div>
        <label class="block text-xs font-semibold text-slate-400 mb-1">Nomor Registrasi Tiket:</label>
        <div id="dispatch_ticket_label" class="font-mono font-bold text-amber-400 text-sm"></div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Satuan Kerja Tujuan Disposisi:</label>
        <select name="satker_id" id="dispatch_satker_id" required
                class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500">
          <option value="">-- Pilih Direktorat Polda atau Polres Jajaran --</option>
          <optgroup label="DIREKTORAT POLDA METRO">
            <?php foreach ($satkers as $s): if ($s['type'] === 'DIREKTORAT'): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endif; endforeach; ?>
          </optgroup>
          <optgroup label="POLRES METRO / POLRESTA JAJARAN">
            <?php foreach ($satkers as $s): if ($s['type'] === 'POLRES'): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endif; endforeach; ?>
          </optgroup>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Catatan Disposisi dari SPKT:</label>
        <textarea name="dispatch_notes" rows="3" required
                  placeholder="Instruksi awal: Lakukan penyelidikan lapangan, kroscek TKP dan hubungi pelapor dalam 24 jam..."
                  class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500">Lakukan penyelidikan lapangan dan segera terbitkan Surat Perintah Penugasan (Sprintgas) serta hubungi pelapor.</textarea>
      </div>

      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('modalDispatch').classList.add('hidden')"
                class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
          Batal
        </button>
        <button type="submit"
                class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md">
          Kirim Disposisi Satker
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tolak Laporan -->
<div id="modalReject" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-[#0E1E38] border border-rose-500/40 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
    <div class="flex items-center justify-between border-b border-slate-700 pb-3 mb-4">
      <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
        <i class="fas fa-ban text-rose-400"></i>
        Tolak Laporan
      </h3>
      <button onclick="document.getElementById('modalReject').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/spkt/reject" method="POST" class="space-y-4" onsubmit="return confirm('Tolak laporan ini? Alasan penolakan akan terlihat oleh pelapor.');">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="report_id" id="reject_report_id">

      <p class="text-xs text-slate-300 font-mono" id="reject_ticket_label"></p>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1" for="rejection_reason">Alasan Penolakan (terlihat oleh pelapor):</label>
        <textarea name="rejection_reason" id="rejection_reason" rows="3" required
                  placeholder="Contoh: Laporan tidak memenuhi unsur tindak pidana / terindikasi hoaks."
                  class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-rose-500"></textarea>
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" onclick="document.getElementById('modalReject').classList.add('hidden')"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">Batal</button>
        <button type="submit"
                class="px-4 py-2 bg-rose-700 hover:bg-rose-600 text-white rounded-xl text-xs font-bold">Tolak Laporan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openRejectModal(report) {
    document.getElementById('reject_report_id').value = report.id;
    document.getElementById('reject_ticket_label').innerText = report.ticket_number + ' - ' + report.title;
    document.getElementById('modalReject').classList.remove('hidden');
  }

  function openDispatchModal(report) {
    document.getElementById('dispatch_report_id').value = report.id;
    document.getElementById('dispatch_ticket_label').innerText = report.ticket_number + ' - ' + report.title;
    if (report.current_satker_id) {
      document.getElementById('dispatch_satker_id').value = report.current_satker_id;
    }
    document.getElementById('modalDispatch').classList.remove('hidden');
  }
</script>
