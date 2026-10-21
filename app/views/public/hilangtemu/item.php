<?php
use App\Models\LostFound;

[$catLabel, $catIcon] = LostFound::CATEGORIES[$item['item_category']];
$isLost = $item['report_type'] === 'LOST';
$input = 'w-full bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 rounded-xl px-3 py-2.5 text-xs text-slate-100 placeholder-slate-600 outline-none';
$v = fn(string $k) => htmlspecialchars((string)($old[$k] ?? ''));
?>
<div class="py-10 max-w-5xl mx-auto px-4 space-y-6">

  <nav class="flex items-center gap-2 text-xs text-slate-400">
    <a href="<?= $baseUrl ?>/hilangtemu" class="hover:text-amber-400 transition-colors">Hilang &amp; Temu</a>
    <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
    <span class="text-amber-400 font-semibold font-mono"><?= htmlspecialchars($item['code']) ?></span>
  </nav>

  <div class="grid lg:grid-cols-5 gap-6">
    <!-- Foto -->
    <div class="lg:col-span-2 space-y-3">
      <?php if ($photos): ?>
        <?php foreach ($photos as $i => $p): ?>
          <a href="<?= $baseUrl ?>/hilangtemu/foto/<?= (int)$p['id'] ?>" target="_blank" class="block rounded-2xl overflow-hidden border border-[#3B2F1C] <?= $i > 0 ? 'w-1/3 inline-block' : '' ?>">
            <img src="<?= $baseUrl ?>/hilangtemu/foto/<?= (int)$p['id'] ?>" alt="Foto <?= $i + 1 ?>" class="w-full object-cover <?= $i === 0 ? 'aspect-[4/3]' : 'aspect-square' ?>">
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="aspect-[4/3] rounded-2xl bg-[#1B1610] border border-[#3B2F1C] flex items-center justify-center">
          <i class="fas <?= $catIcon ?> text-5xl text-slate-700"></i>
        </div>
      <?php endif; ?>
    </div>

    <!-- Detail -->
    <div class="lg:col-span-3 polri-glass-card rounded-2xl p-6 border border-[#3B2F1C] space-y-4">
      <div class="flex flex-wrap items-center gap-2">
        <span class="px-2 py-0.5 rounded text-[10px] font-black <?= $isLost ? 'bg-rose-600 text-white' : 'bg-emerald-500 text-slate-950' ?>"><?= $isLost ? 'DICARI' : 'DITEMUKAN' ?></span>
        <span class="text-[11px] text-amber-400 font-bold"><i class="fas <?= $catIcon ?>"></i> <?= $catLabel ?></span>
        <?php if ($item['status'] === 'MATCHED'): ?>
          <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-500 text-slate-950">PROSES SERAH TERIMA</span>
        <?php endif; ?>
      </div>
      <h1 class="text-xl sm:text-2xl font-black text-slate-100 font-heading"><?= htmlspecialchars($item['title']) ?></h1>
      <p class="text-sm text-slate-300 whitespace-pre-line leading-relaxed"><?= htmlspecialchars($item['description']) ?></p>

      <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
        <dt class="text-slate-500"><?= $isLost ? 'Tanggal hilang' : 'Tanggal ditemukan' ?></dt>
        <dd class="text-slate-200"><?= date('d/m/Y', strtotime($item['event_date'])) ?></dd>
        <dt class="text-slate-500">Lokasi</dt>
        <dd class="text-slate-200"><?= htmlspecialchars($item['event_location']) ?></dd>
        <?php foreach (LostFound::publicIdentifiers($item) as [$label, $value]): ?>
          <dt class="text-slate-500"><?= $label ?></dt>
          <dd class="text-slate-200 font-mono"><?= htmlspecialchars($value) ?></dd>
        <?php endforeach; ?>
        <?php if (!$isLost && !empty($item['custody_location'])): ?>
          <dt class="text-slate-500">Disimpan di</dt>
          <dd class="text-slate-200"><?= htmlspecialchars($item['custody_location']) ?></dd>
        <?php endif; ?>
      </dl>

      <?php if ($isLost): ?>
        <div class="p-4 rounded-xl bg-amber-950/30 border border-amber-700/50 text-xs text-amber-100 space-y-2">
          <p class="font-bold"><i class="fas fa-eye mr-1"></i> Anda melihat / menemukan <?= $item['item_category'] === 'ORANG' ? 'orang' : 'objek' ?> ini?</p>
          <p>Hubungi <a href="tel:110" class="font-black underline">Call Center 110</a> dengan menyebut kode <b class="font-mono"><?= htmlspecialchars($item['code']) ?></b>,
            atau laporkan temuan Anda agar dicocokkan petugas.</p>
          <a href="<?= $baseUrl ?>/hilangtemu/lapor?type=FOUND&category=<?= urlencode($item['item_category']) ?>" class="inline-block px-4 py-2 rounded-lg bg-emerald-500 text-slate-950 font-black">
            <i class="fas fa-hand-holding-heart mr-1"></i> Laporkan Temuan
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$isLost && $item['status'] === 'PUBLISHED'): ?>
    <!-- Klaim kepemilikan -->
    <form action="<?= $baseUrl ?>/hilangtemu/klaim" method="POST" enctype="multipart/form-data" class="polri-glass-card rounded-2xl p-6 border border-emerald-700/40 space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
      <div>
        <h2 class="text-lg font-black text-slate-100"><i class="fas fa-hand mr-1 text-emerald-400"></i> Ini milik saya — ajukan klaim</h2>
        <p class="text-xs text-slate-400">Petugas memverifikasi ciri khusus &amp; bukti kepemilikan. Serah terima dilakukan di kantor polisi dengan KTP asli.</p>
      </div>
      <div class="grid sm:grid-cols-3 gap-3">
        <input name="claimant_name" required maxlength="100" value="<?= $v('claimant_name') ?>" placeholder="Nama lengkap" class="<?= $input ?>">
        <input name="claimant_nik" required inputmode="numeric" maxlength="16" value="<?= $v('claimant_nik') ?>" placeholder="NIK (16 digit)" class="<?= $input ?> font-mono">
        <input name="claimant_phone" required inputmode="tel" maxlength="20" value="<?= $v('claimant_phone') ?>" placeholder="Nomor HP / WhatsApp" class="<?= $input ?>">
      </div>
      <textarea name="proof_description" required rows="4" maxlength="3000" class="<?= $input ?>"
                placeholder="Sebutkan ciri khusus yang tidak tertera di halaman ini (isi dompet, wallpaper HP, goresan, nomor rangka lengkap, dsb.)"><?= $v('proof_description') ?></textarea>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Bukti kepemilikan <span class="text-slate-500">(opsional: BPKB/STNK, nota, dus, foto lama — JPG/PNG/WEBP/PDF)</span></label>
        <input type="file" name="proof_file" accept="image/jpeg,image/png,image/webp,application/pdf"
               class="w-full text-xs text-slate-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-emerald-500 file:text-slate-950 file:font-bold">
      </div>
      <button class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black"><i class="fas fa-paper-plane mr-1"></i> Kirim Klaim</button>
    </form>
  <?php endif; ?>
</div>
