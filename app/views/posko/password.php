<?php $field = 'w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-sm text-slate-100 outline-none focus:border-amber-500'; ?>
<div class="max-w-md mx-auto polri-glass-card rounded-2xl border border-[#3B2F1C] p-6">
  <h2 class="text-base font-black text-slate-100 font-heading mb-1"><i class="fas fa-key text-amber-400 mr-1"></i> Ganti Kata Sandi</h2>
  <?php if ($forced): ?>
    <p class="mb-4 p-3 rounded-xl border border-amber-500/50 bg-amber-500/10 text-amber-200 text-xs">
      Anda memakai kata sandi sementara dari Admin Posko. Ganti sekarang untuk melanjutkan.
    </p>
  <?php else: ?>
    <p class="text-xs text-slate-400 mb-4">Minimal 10 karakter, memuat huruf dan angka.</p>
  <?php endif; ?>
  <form method="POST" action="<?= $baseUrl ?>/posko/password" class="space-y-3 text-xs">
    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
    <label class="block"><span class="block text-slate-300 mb-1">Kata sandi saat ini</span>
      <input type="password" name="current_password" required autocomplete="current-password" class="<?= $field ?>"></label>
    <label class="block"><span class="block text-slate-300 mb-1">Kata sandi baru</span>
      <input type="password" name="new_password" required minlength="10" autocomplete="new-password" class="<?= $field ?>"></label>
    <label class="block"><span class="block text-slate-300 mb-1">Ulangi kata sandi baru</span>
      <input type="password" name="confirm_password" required minlength="10" autocomplete="new-password" class="<?= $field ?>"></label>
    <button class="btn-gold w-full py-2.5 rounded-xl font-extrabold">Simpan Kata Sandi</button>
  </form>
</div>
