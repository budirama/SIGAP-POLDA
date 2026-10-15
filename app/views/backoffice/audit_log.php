<div class="space-y-6">

  <!-- Page Header -->
  <div class="polri-glass-card p-6 rounded-2xl border border-[#1E355B]">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-700 text-slate-300 text-xs font-bold mb-2">
      <i class="fas fa-clipboard-list text-amber-400"></i> Pengawasan Internal
    </div>
    <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Jejak Audit Akses &amp; Tindakan Personil</h2>
    <p class="text-xs text-slate-400 mt-1">
      Mencatat login, akses berkas perkara (termasuk identitas whistleblower), unduhan file bukti, dan setiap perubahan status perkara.
    </p>

    <form action="<?= $baseUrl ?>/audit" method="GET" class="mt-4 flex flex-col sm:flex-row gap-3">
      <select name="action" onchange="this.form.submit()"
              class="bg-[#070F1E] border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
        <option value="">-- Semua Aksi --</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= htmlspecialchars($a) ?>" <?= $a === $action ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nomor tiket, nama personil, atau detail"
             class="flex-1 bg-[#070F1E] border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none focus:border-amber-500">
      <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-slate-950 font-bold rounded-xl text-xs">Cari</button>
    </form>
  </div>

  <div class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#0A192F] text-slate-400 border-b border-slate-800 uppercase font-semibold text-[10px] tracking-wider">
          <tr>
            <th class="p-3">Waktu (WIB)</th>
            <th class="p-3">Personil</th>
            <th class="p-3">Aksi</th>
            <th class="p-3">Perkara</th>
            <th class="p-3">Detail</th>
            <th class="p-3">IP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/80">
          <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="p-6 text-center text-slate-500">Belum ada jejak audit yang sesuai.</td></tr>
          <?php endif; ?>
          <?php foreach ($logs as $log): ?>
            <tr class="hover:bg-[#0E1E38]/60">
              <td class="p-3 font-mono text-slate-400 whitespace-nowrap"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['created_at']))) ?></td>
              <td class="p-3 text-slate-200">
                <?php if ($log['user_id']): ?>
                  <?= htmlspecialchars(trim(($log['user_rank'] ?? '') . ' ' . ($log['user_name'] ?? '#' . $log['user_id']))) ?>
                  <span class="block text-[10px] text-slate-500"><?= htmlspecialchars(\App\Config\Constants::getRoleLabel((string)$log['user_role'])) ?></span>
                <?php else: ?>
                  <span class="text-slate-500 italic">Publik / belum login</span>
                <?php endif; ?>
              </td>
              <td class="p-3 font-mono text-amber-300 whitespace-nowrap"><?= htmlspecialchars($log['action']) ?></td>
              <td class="p-3 font-mono text-slate-300 whitespace-nowrap"><?= htmlspecialchars($log['ticket_number'] ?? '-') ?></td>
              <td class="p-3 text-slate-400"><?= htmlspecialchars($log['detail'] ?? '') ?></td>
              <td class="p-3 font-mono text-slate-500 whitespace-nowrap"><?= htmlspecialchars($log['ip_address']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
