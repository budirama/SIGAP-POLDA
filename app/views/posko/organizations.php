<?php
$typeLabel = ['POLDA' => 'Polda', 'POLRES' => 'Polres', 'POLSEK' => 'Polsek'];
$field = 'w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2 text-slate-100 outline-none focus:border-amber-500';
?>
<div class="space-y-6">

  <!-- Navigasi hierarki -->
  <nav aria-label="Hierarki organisasi" class="flex flex-wrap items-center gap-2 text-xs">
    <?php foreach ($ancestors as $a): ?>
      <a href="<?= $baseUrl ?>/posko/organizations/<?= (int)$a['id'] ?>" class="px-2.5 py-1 rounded-lg border border-slate-700 text-slate-300 hover:bg-[#1B1610]"><?= htmlspecialchars($a['name']) ?></a>
      <i class="fas fa-chevron-right text-[9px] text-slate-600" aria-hidden="true"></i>
    <?php endforeach; ?>
    <span class="px-2.5 py-1 rounded-lg border border-amber-500/50 bg-amber-500/10 text-amber-300 font-bold" aria-current="page"><?= htmlspecialchars($unit['name']) ?></span>
  </nav>

  <div class="grid lg:grid-cols-[1fr_320px] gap-6 items-start">

    <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] overflow-hidden">
      <header class="px-5 py-3 border-b border-[#3B2F1C] flex items-center justify-between">
        <h2 class="text-sm font-bold text-slate-100">
          <?= $childType ? 'Daftar ' . $typeLabel[$childType] . ' di bawah ' . htmlspecialchars($unit['name']) : 'Polsek tidak memiliki sub-unit' ?>
        </h2>
        <span class="text-[11px] text-slate-400"><?= count($children) ?> unit</span>
      </header>

      <ul class="divide-y divide-[#3B2F1C]/60">
        <?php if (!$children): ?>
          <li class="p-8 text-center text-xs text-slate-500">Belum ada sub-unit.</li>
        <?php endif; ?>
        <?php foreach ($children as $c): ?>
          <li class="px-5 py-3">
            <details class="group">
              <summary class="flex items-center gap-3 cursor-pointer list-none">
                <span class="flex-1 min-w-0">
                  <span class="block text-sm <?= (int)$c['is_active'] ? 'text-slate-100' : 'text-slate-500 line-through' ?> truncate"><?= htmlspecialchars($c['name']) ?></span>
                  <span class="block text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($c['code']) ?> · <?= (int)$c['child_count'] ?> sub-unit · <?= (int)$c['user_count'] ?> akun</span>
                </span>
                <?php if ($c['type'] !== 'POLSEK'): ?>
                  <a href="<?= $baseUrl ?>/posko/organizations/<?= (int)$c['id'] ?>" class="text-[11px] text-amber-400 hover:text-amber-300 px-2" onclick="event.stopPropagation()">Buka <i class="fas fa-chevron-right text-[9px]"></i></a>
                <?php endif; ?>
                <span class="text-[11px] text-slate-400 group-open:text-amber-300"><i class="fas fa-pen-to-square"></i></span>
              </summary>

              <div class="mt-3 grid sm:grid-cols-3 gap-3 text-xs">
                <form method="POST" action="<?= $baseUrl ?>/posko/orgUpdate" class="sm:col-span-2 space-y-2">
                  <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                  <input type="hidden" name="satker_id" value="<?= (int)$c['id'] ?>">
                  <div class="grid grid-cols-2 gap-2">
                    <input name="code" value="<?= htmlspecialchars($c['code']) ?>" required maxlength="50" aria-label="Kode satuan" class="<?= $field ?> font-mono">
                    <input name="name" value="<?= htmlspecialchars($c['name']) ?>" required maxlength="150" aria-label="Nama satuan" class="<?= $field ?>">
                  </div>
                  <input name="address" value="<?= htmlspecialchars((string)$c['address']) ?>" maxlength="255" placeholder="Alamat" aria-label="Alamat" class="<?= $field ?>">
                  <button class="px-3 py-1.5 rounded-lg bg-[#241D14] border border-slate-700 text-slate-200 font-bold">Simpan</button>
                </form>
                <div class="space-y-2">
                  <form method="POST" action="<?= $baseUrl ?>/posko/orgMove" class="space-y-2">
                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="satker_id" value="<?= (int)$c['id'] ?>">
                    <select name="parent_id" aria-label="Pindah ke induk" class="<?= $field ?>">
                      <?php foreach ($moveTargets as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (int)$t['id'] === (int)$c['parent_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="w-full px-3 py-1.5 rounded-lg bg-blue-950/60 border border-blue-800/60 text-blue-200 font-bold"
                            onclick="return confirm('Pindahkan unit ini beserta seluruh sub-unitnya?');">Pindah Induk</button>
                  </form>
                  <form method="POST" action="<?= $baseUrl ?>/posko/orgToggle"
                        onsubmit="return confirm('<?= (int)$c['is_active'] ? 'Nonaktifkan' : 'Aktifkan kembali' ?> unit ini?');">
                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="satker_id" value="<?= (int)$c['id'] ?>">
                    <button class="w-full px-3 py-1.5 rounded-lg border <?= (int)$c['is_active'] ? 'border-red-800/60 text-red-300 bg-red-950/40' : 'border-emerald-700/60 text-emerald-300 bg-emerald-950/40' ?> font-bold">
                      <?= (int)$c['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </button>
                  </form>
                </div>
              </div>
            </details>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <?php if ($childType): ?>
      <section class="polri-glass-card rounded-2xl border border-[#3B2F1C] p-5">
        <h2 class="text-sm font-bold text-slate-100 mb-1">Tambah <?= $typeLabel[$childType] ?></h2>
        <p class="text-[11px] text-slate-400 mb-4">Induk: <?= htmlspecialchars($unit['name']) ?></p>
        <form method="POST" action="<?= $baseUrl ?>/posko/orgStore" class="space-y-3 text-xs">
          <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
          <input type="hidden" name="type" value="<?= $childType ?>">
          <input type="hidden" name="parent_id" value="<?= (int)$unit['id'] ?>">
          <label class="block"><span class="block text-slate-300 mb-1">Nama satuan</span>
            <input name="name" required maxlength="150" placeholder="<?= $typeLabel[$childType] ?> …" class="<?= $field ?>"></label>
          <label class="block"><span class="block text-slate-300 mb-1">Kode satuan</span>
            <input name="code" required maxlength="50" placeholder="<?= $childType ?>-…" class="<?= $field ?> font-mono uppercase"></label>
          <label class="block"><span class="block text-slate-300 mb-1">Alamat <span class="text-slate-500">(opsional)</span></span>
            <input name="address" maxlength="255" class="<?= $field ?>"></label>
          <button class="btn-gold w-full py-2.5 rounded-xl font-extrabold"><i class="fas fa-plus mr-1"></i> Tambah Satuan</button>
        </form>
      </section>
    <?php endif; ?>
  </div>
</div>
