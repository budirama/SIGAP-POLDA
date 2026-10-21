<?php
use App\Models\PinDesk;

$input = 'w-full bg-[#0C0A08] border border-slate-700 focus:border-amber-500 rounded-xl px-3 py-2.5 text-xs text-slate-100 outline-none';
$trackUrl = [
    PinDesk::TYPE_LP  => '/report/track',
    PinDesk::TYPE_HT  => '/hilangtemu/status',
    PinDesk::TYPE_POL => '/oneservice/track',
];
?>
<div class="space-y-6 max-w-4xl">

  <div class="polri-glass-card p-6 rounded-2xl border border-[#3B2F1C]">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-950/80 border border-amber-700/60 text-amber-300 text-xs font-bold mb-2">
      <i class="fas fa-key"></i> Meja SPKT
    </div>
    <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Layanan PIN Pelapor</h2>
    <p class="text-xs text-slate-400 mt-1">
      Bantu warga yang kehilangan PIN selama notifikasi SMS/WhatsApp belum aktif. Verifikasi identitas pelapor terlebih dahulu
      (KTP/NIK &amp; nomor HP sesuai laporan). Setiap lihat/reset PIN tercatat di Jejak Audit atas nama Anda.
    </p>
  </div>

  <form method="GET" action="<?= $baseUrl ?>/pin" class="flex gap-2">
    <input name="ticket" value="<?= htmlspecialchars($ticket) ?>" required placeholder="LP-POLRI-202610-0018 · HT-202610-00001 · POL-202610-WEB-4102C"
           class="flex-1 <?= $input ?> font-mono uppercase">
    <button class="px-5 rounded-xl bg-amber-500 text-slate-950 text-xs font-black"><i class="fas fa-magnifying-glass mr-1"></i> Cari</button>
  </form>

  <?php if ($error): ?>
    <div class="p-3 rounded-xl bg-red-950/70 border border-red-800 text-red-300 text-xs"><i class="fas fa-circle-exclamation mr-1"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($notFound): ?>
    <div class="p-4 rounded-xl bg-[#1B1610] border border-slate-800 text-xs text-slate-400">Tiket <b class="font-mono text-slate-200"><?= htmlspecialchars($ticket) ?></b> tidak ditemukan.</div>
  <?php endif; ?>

  <?php if ($result): ?>
    <div class="p-6 rounded-2xl border-2 border-emerald-600/70 bg-emerald-950/30 text-center space-y-2">
      <div class="text-xs font-bold text-emerald-300 uppercase tracking-wider">
        <?= $result['action'] === 'reset' ? 'PIN baru berhasil dibuat — PIN lama tidak berlaku lagi' : 'PIN pelapor saat ini' ?>
      </div>
      <div class="text-4xl font-black font-mono tracking-[0.4em] text-slate-50"><?= htmlspecialchars((string)$result['pin']) ?></div>
      <p class="text-[11px] text-emerald-200/80">
        Sampaikan langsung kepada pelapor yang identitasnya telah diverifikasi. Jangan dicatat di tempat lain.
        Pelapor dapat memantau di <span class="font-mono"><?= htmlspecialchars($baseUrl . $trackUrl[$item['type']]) ?></span>.
      </p>
    </div>
  <?php endif; ?>

  <?php if ($item): ?>
    <div class="polri-glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
      <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="font-mono font-black text-amber-400 text-base"><?= htmlspecialchars($item['ticket']) ?></span>
        <span class="px-2 py-0.5 rounded-full border border-slate-700 text-slate-300"><?= htmlspecialchars($item['type_label']) ?></span>
        <span class="px-2 py-0.5 rounded-full border border-amber-500/40 bg-amber-500/10 text-amber-300 font-bold"><?= htmlspecialchars((string)$item['status']) ?></span>
      </div>
      <p class="text-sm font-bold text-slate-100"><?= htmlspecialchars((string)$item['title']) ?></p>

      <div>
        <h3 class="text-xs font-black text-slate-300 uppercase tracking-wider mb-2">Cocokkan dengan identitas pelapor</h3>
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-1.5 text-xs">
          <dt class="text-slate-500">Nama pelapor</dt><dd class="text-slate-100"><?= htmlspecialchars((string)$item['reporter_name']) ?><?= (int)$item['is_anonymous'] ? ' <span class="text-amber-300">(identitas dirahasiakan)</span>' : '' ?></dd>
          <dt class="text-slate-500">NIK (tersamar)</dt><dd class="text-slate-100 font-mono"><?= htmlspecialchars($item['nik_masked']) ?></dd>
          <dt class="text-slate-500">No. HP (tersamar)</dt><dd class="text-slate-100 font-mono"><?= htmlspecialchars($item['phone_masked']) ?></dd>
          <dt class="text-slate-500">Tanggal lapor</dt><dd class="text-slate-100"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></dd>
          <?php if (!empty($item['linked'])): ?>
            <dt class="text-slate-500">Tiket terhubung</dt><dd class="text-slate-100 font-mono"><?= htmlspecialchars($item['linked']) ?> <span class="text-slate-500 font-sans">(PIN sama, ikut diperbarui saat reset)</span></dd>
          <?php endif; ?>
        </dl>
      </div>

      <form method="POST" class="space-y-3 border-t border-slate-800 pt-4">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="ticket" value="<?= htmlspecialchars($item['ticket']) ?>">
        <label class="flex items-start gap-2 text-xs text-slate-200">
          <input type="checkbox" name="identity_verified" value="1" required class="mt-0.5">
          <span>Saya telah memverifikasi identitas pelapor secara langsung (KTP/NIK dan nomor HP cocok dengan data di atas).</span>
        </label>
        <textarea name="reason" required minlength="10" maxlength="300" rows="2" class="<?= $input ?>"
                  placeholder="Alasan / dasar permintaan, mis. Pelapor datang ke SPKT membawa KTP, PIN hilang karena HP rusak"></textarea>
        <div class="flex flex-wrap gap-2">
          <?php if ($item['can_reveal']): ?>
            <button formaction="<?= $baseUrl ?>/pin/reveal" class="px-4 py-2 rounded-xl bg-sky-700 hover:bg-sky-600 text-white text-xs font-black"><i class="fas fa-eye mr-1"></i> Lihat PIN</button>
          <?php endif; ?>
          <button formaction="<?= $baseUrl ?>/pin/reset" onclick="return confirm('Buat PIN baru? PIN lama langsung tidak berlaku.')"
                  class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black"><i class="fas fa-rotate mr-1"></i> Reset PIN</button>
        </div>
        <?php if (!$item['can_reveal']): ?>
          <p class="text-[11px] text-slate-500"><i class="fas fa-lock mr-1"></i> PIN tiket ini disimpan terenkripsi sehingga hanya dapat di-reset, tidak dapat dilihat.</p>
        <?php endif; ?>
      </form>
    </div>
  <?php endif; ?>
</div>
