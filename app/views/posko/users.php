<?php
use App\Config\Constants;
?>
<div class="space-y-6">

  <?php if ($tempPass): ?>
    <div class="p-4 rounded-2xl border border-amber-500/60 bg-amber-500/10 text-amber-100 text-xs" role="status">
      <p class="font-bold mb-1"><i class="fas fa-key mr-1"></i> Kata sandi sementara (hanya ditampilkan sekali)</p>
      <p class="font-mono text-base tracking-wider select-all"><?= htmlspecialchars($tempPass) ?></p>
      <p class="mt-1 text-amber-200/80">Sampaikan langsung kepada pemilik akun. Sistem akan meminta penggantian pada login pertama.</p>
    </div>
  <?php endif; ?>

  <div class="grid lg:grid-cols-[1fr_340px] gap-6 items-start">

    <!-- Daftar akun -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
      <header class="p-4 border-b border-[#3B2F1C] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-sm font-bold text-slate-100">Akun Posko di Wilayah Anda</h2>
          <p class="text-[11px] text-slate-400"><?= (int)$pager['total'] ?> akun</p>
        </div>
        <form method="GET" action="<?= $baseUrl ?>/posko/users" class="flex gap-2 text-xs">
          <label for="userSearch" class="sr-only">Cari akun</label>
          <input id="userSearch" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, NRP, email, unit…"
                 class="w-56 bg-[#0C0A08] border border-slate-700 rounded-xl px-3 py-2 text-slate-100 outline-none focus:border-amber-500">
          <button class="px-3 py-2 rounded-xl bg-[#241D14] border border-slate-700 text-slate-200 font-bold">Cari</button>
        </form>
      </header>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-[#14100C] text-slate-400 uppercase text-[10px] tracking-wider">
            <tr>
              <th class="p-3">Personel</th>
              <th class="p-3">Unit & Role</th>
              <th class="p-3">Status</th>
              <th class="p-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/80">
            <?php if (!$pager['items']): ?>
              <tr><td colspan="4" class="p-8 text-center text-slate-500">Tidak ada akun yang cocok.</td></tr>
            <?php endif; ?>
            <?php foreach ($pager['items'] as $u): ?>
              <tr>
                <td class="p-3">
                  <p class="font-bold text-slate-100"><?= htmlspecialchars(trim(($u['rank'] ?? '') . ' ' . $u['name'])) ?></p>
                  <p class="text-[10px] text-slate-500 font-mono">NRP <?= htmlspecialchars($u['nrp']) ?> · <?= htmlspecialchars($u['email']) ?></p>
                </td>
                <td class="p-3">
                  <p class="text-slate-200"><?= htmlspecialchars($u['satker_name']) ?></p>
                  <p class="text-[10px] text-amber-300"><?= htmlspecialchars(Constants::poskoRoleLabel($u['role'], (int)$u['satker_level'])) ?></p>
                </td>
                <td class="p-3">
                  <?php if ((int)$u['is_active']): ?>
                    <span class="px-2 py-0.5 rounded-full border border-emerald-600/60 text-emerald-300 text-[10px]">Aktif</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full border border-slate-600 text-slate-400 text-[10px]">Nonaktif</span>
                  <?php endif; ?>
                  <?php if ((int)$u['must_change_password']): ?>
                    <span class="block mt-1 text-[10px] text-amber-300">Menunggu ganti sandi</span>
                  <?php endif; ?>
                </td>
                <td class="p-3">
                  <div class="flex justify-end gap-2">
                    <form method="POST" action="<?= $baseUrl ?>/posko/userReset" onsubmit="return confirm('Reset kata sandi akun ini?');">
                      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <button class="px-2.5 py-1.5 rounded-lg bg-[#241D14] border border-slate-700 text-slate-200" title="Reset kata sandi"><i class="fas fa-key"></i></button>
                    </form>
                    <?php if ((int)$u['id'] !== (int)$authUser['id']): ?>
                      <form method="POST" action="<?= $baseUrl ?>/posko/userToggle"
                            onsubmit="return confirm('<?= (int)$u['is_active'] ? 'Nonaktifkan' : 'Aktifkan kembali' ?> akun ini?');">
                        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button class="px-2.5 py-1.5 rounded-lg border <?= (int)$u['is_active'] ? 'border-red-800/60 text-red-300 bg-red-950/40' : 'border-emerald-700/60 text-emerald-300 bg-emerald-950/40' ?>"
                                title="<?= (int)$u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                          <i class="fas <?= (int)$u['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php $pagerPath = '/posko/users'; require __DIR__ . '/../backoffice/_pagination.php'; ?>
    </section>

    <!-- Buat akun -->
    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5">
      <h2 class="text-sm font-bold text-slate-100 mb-1">Buat Akun Posko</h2>
      <p class="text-[11px] text-slate-400 mb-4">Hanya untuk unit di wilayah Anda. Tingkat posko mengikuti unit yang dipilih.</p>
      <form method="POST" action="<?= $baseUrl ?>/posko/userStore" class="space-y-3 text-xs">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <?php
        $field = 'w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-slate-100 outline-none focus:border-amber-500';
        ?>
        <label class="block"><span class="block text-slate-300 mb-1">Nama lengkap</span>
          <input name="name" required maxlength="100" class="<?= $field ?>"></label>
        <div class="grid grid-cols-2 gap-2">
          <label class="block"><span class="block text-slate-300 mb-1">Pangkat</span>
            <input name="rank" maxlength="50" placeholder="Bripka" class="<?= $field ?>"></label>
          <label class="block"><span class="block text-slate-300 mb-1">NRP</span>
            <input name="nrp" required inputmode="numeric" maxlength="20" class="<?= $field ?>"></label>
        </div>
        <label class="block"><span class="block text-slate-300 mb-1">Email dinas</span>
          <input name="email" type="email" required maxlength="100" class="<?= $field ?>"></label>
        <label class="block"><span class="block text-slate-300 mb-1">No. HP dinas</span>
          <input name="phone" required inputmode="tel" maxlength="16" class="<?= $field ?>"></label>
        <label class="block"><span class="block text-slate-300 mb-1">Unit posko</span>
          <select name="satker_id" required class="<?= $field ?>">
            <option value="">-- Pilih unit --</option>
            <?php foreach ([4 => 'Pusat', 3 => 'Polda', 2 => 'Polres', 1 => 'Polsek'] as $lvl => $label): ?>
              <?php $group = array_filter($units, fn($s) => (int)$s['level'] === $lvl); ?>
              <?php if ($group): ?>
                <optgroup label="<?= $label ?>">
                  <?php foreach ($group as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endif; ?>
            <?php endforeach; ?>
          </select></label>
        <label class="block"><span class="block text-slate-300 mb-1">Role</span>
          <select name="role" required class="<?= $field ?>">
            <option value="ROLE_POSKO_OPERATOR">Operator Posko — input, verifikasi & teruskan laporan</option>
            <option value="ROLE_POSKO_EXECUTIVE">Executive Posko — melihat dashboard & laporan</option>
            <option value="ROLE_POSKO_ADMIN">Admin Posko — kelola akun di wilayahnya</option>
          </select></label>
        <label class="block"><span class="block text-slate-300 mb-1">Jabatan <span class="text-slate-500">(opsional)</span></span>
          <input name="position" maxlength="100" class="<?= $field ?>"></label>
        <button class="btn-gold w-full py-2.5 rounded-xl font-extrabold"><i class="fas fa-user-plus mr-1"></i> Buat Akun</button>
      </form>
    </section>
  </div>
</div>
