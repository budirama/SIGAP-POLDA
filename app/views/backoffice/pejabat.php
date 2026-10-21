<?php
use App\Config\Constants;

$input = 'w-full bg-[#0C0A08] border border-slate-700 focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-slate-100 outline-none';
$leaders = [Constants::ROLE_EXECUTIVE, Constants::ROLE_INTERNAL_AFFAIRS, Constants::ROLE_SATKER_HEAD, Constants::ROLE_POSKO_EXECUTIVE];
?>
<div class="space-y-6">

  <div class="polri-glass-card p-6 rounded-2xl border border-[#3B2F1C]">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-950/80 border border-amber-700/60 text-amber-300 text-xs font-bold mb-2">
      <i class="fas fa-id-badge"></i> Superadmin TIK
    </div>
    <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Profil Pejabat &amp; Personil</h2>
    <p class="text-xs text-slate-400 mt-1">
      Ubah nama, pangkat, dan jabatan akun — misalnya mengisi pejabat yang sedang menjabat saat sistem dioperasikan resmi.
      <b class="text-slate-300">Identitas login (email, NRP, no. HP) dan role tidak berubah.</b> Setiap perubahan tercatat di Jejak Audit.
    </p>
  </div>

  <?php if ($editing): ?>
    <form id="form" action="<?= $baseUrl ?>/pejabat/save" method="POST" class="polri-glass-card p-6 rounded-2xl border border-amber-600/50 space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-black text-slate-100"><i class="fas fa-pen mr-1 text-amber-400"></i> Ubah profil</h3>
        <a href="<?= $baseUrl ?>/pejabat" class="text-xs text-slate-400 hover:text-amber-300">Batal</a>
      </div>
      <div class="grid sm:grid-cols-3 gap-3 text-[11px]">
        <div class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800"><span class="text-slate-500 block">Login email (tetap)</span><span class="font-mono text-slate-200"><?= htmlspecialchars((string)$editing['email']) ?></span></div>
        <div class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800"><span class="text-slate-500 block">Login NRP (tetap)</span><span class="font-mono text-slate-200"><?= htmlspecialchars((string)$editing['nrp']) ?></span></div>
        <div class="p-3 rounded-xl bg-[#0C0A08] border border-slate-800"><span class="text-slate-500 block">Role (tetap)</span><span class="text-slate-200"><?= htmlspecialchars(Constants::getRoleLabel($editing['role'])) ?></span></div>
      </div>
      <div class="grid sm:grid-cols-4 gap-3">
        <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Pangkat</span>
          <select name="rank" class="<?= $input ?>">
            <option value="">— Tanpa pangkat —</option>
            <?php foreach ($ranks as $r): ?><option value="<?= htmlspecialchars($r) ?>" <?= ($editing['rank'] ?? '') === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option><?php endforeach; ?>
          </select></label>
        <label class="block sm:col-span-3"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Nama lengkap &amp; gelar</span>
          <input name="name" required minlength="3" maxlength="150" value="<?= htmlspecialchars($editing['name']) ?>" class="<?= $input ?>"></label>
      </div>
      <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Jabatan</span>
        <input name="position" maxlength="100" value="<?= htmlspecialchars((string)$editing['position']) ?>" placeholder="Contoh: Kepala Kepolisian Negara Republik Indonesia" class="<?= $input ?>"></label>
      <button class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-slate-950 text-xs font-black">Simpan Profil</button>
    </form>
  <?php endif; ?>

  <form method="GET" action="<?= $baseUrl ?>/pejabat" class="flex gap-2">
    <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, jabatan, satker, email, NRP…" class="flex-1 <?= $input ?>">
    <button class="px-4 rounded-xl bg-amber-500 text-slate-950 text-xs font-black">Cari</button>
  </form>

  <div class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#14100C] text-slate-400 border-b border-slate-800 uppercase font-semibold text-[10px] tracking-wider">
          <tr><th class="p-3">Pejabat / Personil</th><th class="p-3">Jabatan & Satker</th><th class="p-3">Role</th><th class="p-3">Login</th><th class="p-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-800/70">
          <?php if (!$people): ?><tr><td colspan="5" class="p-8 text-center text-slate-500">Tidak ada personil yang cocok.</td></tr><?php endif; ?>
          <?php foreach ($people as $p): ?>
            <tr class="hover:bg-[#1B1610]/60 <?= (int)$p['is_active'] ? '' : 'opacity-50' ?>">
              <td class="p-3">
                <div class="font-bold text-slate-100"><?= htmlspecialchars(trim(($p['rank'] ?? '') . ' ' . $p['name'])) ?></div>
                <?php if (in_array($p['role'], $leaders, true)): ?><span class="text-[10px] px-1.5 rounded bg-amber-950 text-amber-300 border border-amber-800">Pimpinan</span><?php endif; ?>
                <?php if (!(int)$p['is_active']): ?><span class="text-[10px] px-1.5 rounded bg-slate-800 text-slate-400">Nonaktif</span><?php endif; ?>
              </td>
              <td class="p-3"><div class="text-slate-200"><?= htmlspecialchars((string)$p['position']) ?></div><div class="text-[10px] text-slate-500"><?= htmlspecialchars((string)$p['satker_name']) ?></div></td>
              <td class="p-3 text-slate-400"><?= htmlspecialchars(Constants::getRoleLabel($p['role'])) ?></td>
              <td class="p-3 font-mono text-[10px] text-slate-400"><?= htmlspecialchars((string)$p['email']) ?><br><?= htmlspecialchars((string)$p['nrp']) ?></td>
              <td class="p-3 text-right"><a href="<?= $baseUrl ?>/pejabat?edit=<?= (int)$p['id'] ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>#form" class="px-3 py-1.5 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-300 font-bold whitespace-nowrap">Ubah</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
