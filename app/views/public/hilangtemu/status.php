<?php
use App\Models\LostFound;

$input = 'w-full bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 rounded-xl px-3 py-2.5 text-xs sm:text-sm text-slate-100 placeholder-slate-600 outline-none font-mono';
$steps = ['PENDING' => 'Diterima', 'PUBLISHED' => 'Diverifikasi & Tayang', 'MATCHED' => 'Cocok / Serah Terima', 'RESOLVED' => 'Selesai'];
?>
<div class="py-10 max-w-4xl mx-auto px-4 space-y-6">

  <nav class="flex items-center gap-2 text-xs text-slate-400">
    <a href="<?= $baseUrl ?>/hilangtemu" class="hover:text-amber-400 transition-colors">Hilang &amp; Temu</a>
    <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
    <span class="text-amber-400 font-semibold">Cek Status</span>
  </nav>

  <div class="polri-glass-card rounded-2xl p-6 border border-amber-500/30 shadow-xl">
    <h1 class="text-xl font-black text-slate-100 font-heading mb-1">Cek Status Laporan Hilang &amp; Temu</h1>
    <p class="text-xs text-slate-400 mb-4">Masukkan kode laporan (HT-…) dan PIN 6 digit yang Anda terima saat melapor.</p>

    <?php if (!empty($errorMessage)): ?>
      <div class="mb-4 p-3 bg-red-950/70 border border-red-800 rounded-xl text-red-300 text-xs"><i class="fas fa-circle-exclamation mr-1"></i><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form action="<?= $baseUrl ?>/hilangtemu/status" method="POST" autocomplete="off" class="grid sm:grid-cols-12 gap-3">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input name="code" required value="<?= htmlspecialchars($code) ?>" placeholder="HT-202610-00001" class="sm:col-span-6 <?= $input ?> uppercase">
      <input type="password" name="pin" required inputmode="numeric" maxlength="6" placeholder="PIN" class="sm:col-span-3 <?= $input ?>">
      <button class="sm:col-span-3 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-slate-950 text-xs font-black"><i class="fas fa-magnifying-glass mr-1"></i> Cek</button>
    </form>
  </div>

  <?php if ($item): ?>
    <div class="polri-glass-card rounded-2xl p-6 border border-[#3B2F1C] space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <div class="text-[11px] font-mono text-amber-400 font-bold"><?= htmlspecialchars($item['code']) ?> · <?= LostFound::TYPES[$item['report_type']] ?> · <?= LostFound::CATEGORIES[$item['item_category']][0] ?></div>
          <h2 class="text-lg font-black text-slate-100"><?= htmlspecialchars($item['title']) ?></h2>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-black <?= $item['status'] === 'REJECTED' ? 'bg-red-950 text-red-300 border border-red-800' : 'bg-amber-500/15 text-amber-300 border border-amber-500/40' ?>">
          <?= LostFound::STATUSES[$item['status']] ?>
        </span>
      </div>

      <?php if ($item['status'] !== 'REJECTED'): ?>
        <ol class="grid grid-cols-4 gap-2 text-[10px] sm:text-xs">
          <?php $reached = true; foreach ($steps as $key => $label):
            $current = $key === $item['status']; ?>
            <li class="p-2 rounded-lg text-center border <?= $reached ? 'border-amber-500/60 bg-amber-500/10 text-amber-200 font-bold' : 'border-slate-800 text-slate-500' ?>"><?= $label ?></li>
          <?php if ($current) { $reached = false; } endforeach; ?>
        </ol>
      <?php endif; ?>

      <?php if ($item['status'] === 'REJECTED' && !empty($item['officer_note'])): ?>
        <p class="text-xs text-red-200 bg-red-950/40 border border-red-800/60 rounded-xl p-3"><b>Alasan:</b> <?= htmlspecialchars($item['officer_note']) ?></p>
      <?php elseif ($item['status'] === 'RESOLVED' && !empty($item['officer_note'])): ?>
        <p class="text-xs text-emerald-200 bg-emerald-950/40 border border-emerald-800/60 rounded-xl p-3"><b>Catatan petugas:</b> <?= htmlspecialchars($item['officer_note']) ?></p>
      <?php elseif ($confirmed > 0 || $item['status'] === 'MATCHED'): ?>
        <p class="text-xs text-amber-100 bg-amber-950/40 border border-amber-700/50 rounded-xl p-3">
          <i class="fas fa-bell mr-1"></i> Petugas menemukan kecocokan untuk laporan Anda. Anda akan dihubungi melalui nomor HP terdaftar
          untuk verifikasi dan serah terima. Siapkan KTP asli dan bukti kepemilikan.
        </p>
      <?php endif; ?>

      <div class="flex flex-wrap gap-3">
        <?php if (in_array($item['status'], LostFound::PUBLIC_STATUSES, true)): ?>
          <a href="<?= $baseUrl ?>/hilangtemu/item/<?= (int)$item['id'] ?>" class="px-4 py-2 rounded-xl bg-[#1B1610] border border-[#3B2F1C] text-xs font-bold text-slate-200">
            <i class="fas fa-eye mr-1 text-amber-400"></i> Lihat di Galeri
          </a>
        <?php endif; ?>
        <?php if ($item['report_type'] === 'LOST' && !in_array($item['status'], ['PENDING', 'REJECTED'], true)): ?>
          <a href="<?= $baseUrl ?>/hilangtemu/sktlk/<?= (int)$item['id'] ?>" target="_blank" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-slate-950 text-xs font-black">
            <i class="fas fa-print mr-1"></i> Cetak SKTLK
          </a>
        <?php elseif ($item['report_type'] === 'LOST' && $item['status'] === 'PENDING'): ?>
          <span class="text-[11px] text-slate-400"><i class="fas fa-hourglass-half mr-1"></i> SKTLK dapat dicetak setelah laporan diverifikasi petugas.</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
