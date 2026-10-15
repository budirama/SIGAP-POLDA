<div class="space-y-6">
  
  <!-- Header & Simulator Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 polri-glass-card p-6 rounded-2xl border border-[#1E355B]">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-950/80 border border-sky-700/60 text-sky-300 text-xs font-bold mb-2">
        <i class="fab fa-x-twitter"></i> Tim Humas & Patroli Siber POLRI
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">
        Omnichannel Social Listening & Triage
      </h2>
      <p class="text-xs text-slate-400 mt-1">
        Pantau aduan viral masyarakat dari X/Twitter, Instagram, TikTok, dan Facebook. Konversi menjadi Laporan Polisi Resmi dalam 1 klik.
      </p>
    </div>

    <!-- Quick Webhook Simulation Trigger (development) -->
    <?php if (\App\Config\Config::isDevelopment()): ?>
    <div>
      <button onclick="document.getElementById('modalSimulate').classList.remove('hidden')"
              class="px-4 py-2.5 bg-gradient-to-r from-sky-600 to-blue-600 hover:from-sky-500 hover:to-blue-500 text-white font-bold rounded-xl text-xs transition-all shadow-md flex items-center gap-2">
        <i class="fas fa-plus-circle"></i>
        <span>Simulasikan Aduan Medsos Baru</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Feed Grid -->
  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($feeds)): ?>
      <div class="col-span-full text-center py-12 polri-glass-card rounded-2xl text-slate-500 text-xs">
        <i class="fas fa-inbox text-3xl mb-2 text-slate-600 block"></i>
        Tidak ada postingan aduan media sosial dalam antrean.
      </div>
    <?php else: ?>
      <?php foreach ($feeds as $feed): ?>
        <div class="polri-glass-card rounded-2xl p-5 border <?= $feed['status'] === 'NEW' ? 'border-sky-500/50 shadow-lg shadow-sky-950/20' : 'border-slate-800 opacity-80' ?> flex flex-col justify-between">
          
          <div>
            <!-- Header Card (Platform & Time) -->
            <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-2.5">
              <div class="flex items-center gap-2">
                <?= \App\Config\Constants::getChannelBadge($feed['platform']) ?>
                <span class="text-[10px] text-slate-500 font-mono"><?= date('d M H:i', strtotime($feed['created_at'])) ?></span>
              </div>
              <div>
                <?php if ($feed['status'] === 'NEW'): ?>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">Perlu Triage</span>
                <?php elseif ($feed['status'] === 'CONVERTED_TO_TICKET'): ?>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✓ Jadi Tiket</span>
                <?php else: ?>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-400">Diabaikan</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Author Info -->
            <div class="flex items-center gap-2.5 mb-3">
              <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 overflow-hidden flex items-center justify-center font-bold text-xs text-slate-300 flex-shrink-0">
                <?php if (!empty($feed['author_avatar'])): ?>
                  <img src="<?= htmlspecialchars($feed['author_avatar']) ?>" alt="Avatar" class="w-full h-full object-cover">
                <?php else: ?>
                  <?= htmlspecialchars(strtoupper(substr($feed['author_username'], 0, 2))) ?>
                <?php endif; ?>
              </div>
              <div class="overflow-hidden">
                <div class="text-xs font-bold text-slate-200 truncate"><?= htmlspecialchars($feed['author_name'] ?: $feed['author_username']) ?></div>
                <div class="text-[11px] text-sky-400 truncate">@<?= htmlspecialchars($feed['author_username']) ?></div>
              </div>
            </div>

            <!-- Post Content -->
            <p class="text-xs text-slate-300 leading-relaxed bg-[#070F1E] p-3 rounded-xl border border-slate-800/80 mb-3 font-sans">
              <?= nl2br(htmlspecialchars($feed['post_content'])) ?>
            </p>

            <!-- Original Post Link -->
            <div class="mb-3">
              <?php if (\App\Models\SocialFeed::isSafeUrl($feed['post_url'])): ?>
              <a href="<?= htmlspecialchars($feed['post_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-[11px] text-slate-400 hover:text-sky-300 flex items-center gap-1 transition-colors">
                <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                <span class="truncate">Buka Tautan Post Asli</span>
              </a>
              <?php else: ?>
              <span class="text-[11px] text-slate-500 italic">Tautan postingan tidak valid</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Actions -->
          <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
            <?php if ($feed['status'] === 'NEW'): ?>
              <button onclick="openConvertModal(<?= htmlspecialchars(json_encode($feed)) ?>)"
                      class="flex-1 py-2 px-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-slate-950 font-bold rounded-xl text-xs transition-all shadow flex items-center justify-center gap-1.5">
                <i class="fas fa-shield-plus text-xs"></i>
                <span>Konversi ke Tiket</span>
              </button>

              <form action="<?= $baseUrl ?>/social/dismiss" method="POST" onsubmit="return confirm('Abaikan postingan ini sebagai spam?');">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
                <button type="submit" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-rose-400 rounded-xl text-xs transition-colors" title="Abaikan Spam">
                  <i class="fas fa-trash-can"></i>
                </button>
              </form>
            <?php elseif (!empty($feed['converted_ticket'])): ?>
              <div class="w-full flex items-center justify-between text-xs">
                <span class="text-slate-400 text-[11px]">Tiket Resmi:</span>
                <a href="<?= $baseUrl ?>/report/track?ticket=<?= urlencode($feed['converted_ticket']) ?>" target="_blank" rel="noopener noreferrer" class="font-mono font-bold text-amber-400 hover:underline">
                  <?= htmlspecialchars($feed['converted_ticket']) ?>
                </a>
              </div>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- Modal Konversi ke Tiket Resmi -->
<div id="modalConvert" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-[#0E1E38] border border-amber-500/40 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
    <div class="flex items-center justify-between border-b border-slate-700 pb-3 mb-4">
      <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
        <i class="fas fa-file-circle-check text-amber-400"></i>
        Konversi Aduan Medsos Jadi Tiket Resmi
      </h3>
      <button onclick="document.getElementById('modalConvert').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/social/convert" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="feed_id" id="convert_feed_id">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Judul Laporan Resmi:</label>
        <input type="text" name="title" id="convert_title" required
               class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Kategori Perkara:</label>
        <select name="category_id" class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500">
          <option value="1">Kriminal Umum (Begal, Curanmor, Premanisme, Pungli)</option>
          <option value="2">Kejahatan Siber & Finansial</option>
          <option value="4">Lalu Lintas (Kemacetan, Balap Liar, Kecelakaan)</option>
          <option value="5">Dumas Propam (Perilaku Anggota)</option>
          <option value="6">Gangguan Keamanan & Ketertiban Lingkungan</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Perkiraan Lokasi TKP:</label>
        <input type="text" name="incident_location" value="Wilayah Hukum Polri" required
               class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500">
      </div>

      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('modalConvert').classList.add('hidden')" 
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold">
          Batal
        </button>
        <button type="submit" 
                class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 rounded-xl text-xs font-extrabold shadow-md">
          Terbitkan Laporan Polisi
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Simulasi Webhook Medsos -->
<div id="modalSimulate" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-[#0E1E38] border border-sky-500/40 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
    <div class="flex items-center justify-between border-b border-slate-700 pb-3 mb-4">
      <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
        <i class="fas fa-satellite-dish text-sky-400"></i>
        Simulasi Webhook Ingestion Medsos
      </h3>
      <button onclick="document.getElementById('modalSimulate').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/social/simulate" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Platform Asal:</label>
        <select name="platform" class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none">
          <option value="TWITTER_X">X (Twitter)</option>
          <option value="INSTAGRAM">Instagram</option>
          <option value="TIKTOK">TikTok</option>
          <option value="FACEBOOK">Facebook</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Username Pengunggah (@):</label>
        <input type="text" name="username" value="warga_peduli_jkt" required
               class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Isi Postingan / Tag Mention:</label>
        <textarea name="content" rows="3" required
                  class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none">Tolong @HumasPolda ditindak tegas aksi balap liar dan knalpot brong di kawasan Jl. Asia Afrika tiap tengah malam sangat mengganggu warga!</textarea>
      </div>

      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('modalSimulate').classList.add('hidden')" 
                class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
          Tutup
        </button>
        <button type="submit" 
                class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow">
          Kirim Simulasi Webhook
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openConvertModal(feed) {
    document.getElementById('convert_feed_id').value = feed.id;
    document.getElementById('convert_title').value = "Aduan Medsos " + feed.platform + " dari @" + feed.author_username;
    document.getElementById('modalConvert').classList.remove('hidden');
  }
</script>
