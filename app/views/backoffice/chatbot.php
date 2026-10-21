<?php
use App\Models\Chatbot;

$input = 'w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500';
$e = $editing ?? [];
$v = fn(string $k, string $default = '') => htmlspecialchars((string)($e[$k] ?? $default));
$isEdit = !empty($e['id']);
$total = max(1, (int)$stats['total']);
?>
<div class="space-y-6">

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 polri-glass-card p-6 rounded-2xl border border-[#3B2F1C]">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-950/80 border border-sky-700/60 text-sky-300 text-xs font-bold mb-2">
        <i class="fas fa-robot"></i> <?= Chatbot::BOT_NAME ?>
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Basis Pengetahuan Chatbot Konsultasi</h2>
      <p class="text-xs text-slate-400 mt-1">
        Chatbot menjawab warga di Live Chat berdasarkan kata kunci di bawah, lalu meneruskan ke petugas bila diminta,
        bila darurat, atau bila pertanyaan tidak dipahami. Tidak ada data percakapan yang dikirim ke layanan luar.
      </p>
    </div>
  </div>

  <!-- Statistik 30 hari -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <?php foreach ([
        ['Percakapan dilayani bot', (int)$stats['total'], 'text-slate-100', null],
        ['Selesai tanpa petugas', (int)$stats['bot_only'], 'text-emerald-300', round((int)$stats['bot_only'] / $total * 100) . '%'],
        ['Diteruskan ke petugas', (int)$stats['handoff'], 'text-amber-300', round((int)$stats['handoff'] / $total * 100) . '%'],
        ['Terdeteksi darurat', (int)$stats['urgent'], 'text-red-300', null],
    ] as [$label, $value, $tone, $pct]): ?>
      <div class="polri-glass-card p-4 rounded-2xl border border-slate-800">
        <div class="text-[11px] text-slate-400"><?= $label ?> <span class="text-slate-600">· 30 hari</span></div>
        <div class="text-2xl font-black font-mono <?= $tone ?>"><?= $value ?><?php if ($pct): ?> <span class="text-xs text-slate-500"><?= $pct ?></span><?php endif; ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid lg:grid-cols-2 gap-6">
    <!-- Uji pertanyaan -->
    <div class="polri-glass-card p-5 rounded-2xl border border-slate-800 space-y-3">
      <h3 class="text-sm font-black text-slate-100"><i class="fas fa-vial text-sky-400 mr-1"></i> Uji Pertanyaan</h3>
      <form id="cbTest" class="flex gap-2">
        <input name="q" maxlength="500" required placeholder="Contoh: motor saya dicuri tadi malam" class="flex-1 <?= $input ?>">
        <button class="px-4 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold">Uji</button>
      </form>
      <div id="cbTestResult" class="text-xs text-slate-300 hidden p-3 rounded-xl bg-[#0C0A08] border border-slate-800"></div>
    </div>

    <!-- Pertanyaan belum terjawab -->
    <div id="misses" class="polri-glass-card p-5 rounded-2xl border border-slate-800 space-y-2">
      <h3 class="text-sm font-black text-slate-100"><i class="fas fa-circle-question text-amber-400 mr-1"></i> Pertanyaan Belum Terjawab (<?= count($misses) ?>)</h3>
      <?php if (!$misses): ?><p class="text-xs text-slate-500">Semua pertanyaan warga sudah dapat dijawab.</p><?php endif; ?>
      <div class="max-h-64 overflow-y-auto space-y-2">
        <?php foreach ($misses as $m): ?>
          <div class="p-2.5 rounded-xl bg-[#0C0A08] border border-slate-800 text-[11px] flex items-start justify-between gap-2">
            <div>
              <p class="text-slate-200">"<?= htmlspecialchars($m['question']) ?>"</p>
              <p class="text-slate-500"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></p>
            </div>
            <?php if ($canAct): ?>
              <div class="flex gap-1 shrink-0">
                <a href="<?= $baseUrl ?>/chatbot?from_miss=<?= urlencode($m['question']) ?>&miss=<?= (int)$m['id'] ?>#form" class="px-2 py-1 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-300 font-bold">Buat jawaban</a>
                <form action="<?= $baseUrl ?>/chatbot/resolve" method="POST">
                  <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                  <input type="hidden" name="miss_id" value="<?= (int)$m['id'] ?>">
                  <button class="px-2 py-1 rounded-lg border border-slate-700 text-slate-400" title="Abaikan">✕</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Daftar intent -->
  <div class="polri-glass-card rounded-2xl border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-[#14100C] text-slate-400 border-b border-slate-800 uppercase font-semibold text-[10px] tracking-wider">
          <tr><th class="p-3">Topik</th><th class="p-3">Kata kunci</th><th class="p-3">Aksi</th><th class="p-3 text-right">Dipakai</th><th class="p-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-800/70">
          <?php foreach ($intents as $i): ?>
            <tr class="<?= (int)$i['is_active'] ? '' : 'opacity-50' ?>">
              <td class="p-3">
                <div class="font-bold text-slate-100"><?= htmlspecialchars($i['label']) ?></div>
                <div class="font-mono text-[10px] text-amber-400"><?= htmlspecialchars($i['code']) ?>
                  <?= (int)$i['show_in_menu'] ? '<span class="ml-1 px-1 rounded bg-sky-950 text-sky-300">MENU</span>' : '' ?>
                  <?= (int)$i['is_active'] ? '' : '<span class="ml-1 px-1 rounded bg-slate-800 text-slate-400">NONAKTIF</span>' ?></div>
              </td>
              <td class="p-3 text-slate-400 max-w-md"><div class="line-clamp-2"><?= htmlspecialchars($i['keywords']) ?></div></td>
              <td class="p-3">
                <span class="<?= $i['action'] === 'EMERGENCY' ? 'text-red-300 font-bold' : ($i['action'] === 'HANDOFF' ? 'text-amber-300' : 'text-slate-400') ?>"><?= Chatbot::ACTIONS[$i['action']] ?></span>
              </td>
              <td class="p-3 text-right font-mono text-slate-300"><?= (int)$i['hit_count'] ?></td>
              <td class="p-3 text-right">
                <?php if ($canAct): ?>
                  <a href="<?= $baseUrl ?>/chatbot?edit=<?= (int)$i['id'] ?>#form" class="px-2.5 py-1 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-300 font-bold">Ubah</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($canAct): ?>
  <!-- Formulir intent -->
  <form id="form" action="<?= $baseUrl ?>/chatbot/save" method="POST" class="polri-glass-card p-6 rounded-2xl border border-amber-600/40 space-y-4">
    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= (int)($e['id'] ?? 0) ?>">
    <input type="hidden" name="miss_id" value="<?= (int)($_GET['miss'] ?? 0) ?>">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-black text-slate-100"><?= $isEdit ? 'Ubah jawaban ' . $v('code') : 'Tambah jawaban baru' ?></h3>
      <?php if ($isEdit): ?><a href="<?= $baseUrl ?>/chatbot#form" class="text-[11px] text-slate-400 hover:text-amber-300">+ Buat baru</a><?php endif; ?>
    </div>
    <?php if ($prefill !== ''): ?>
      <p class="text-[11px] text-amber-200 bg-amber-950/40 border border-amber-700/50 rounded-xl p-2.5">Pertanyaan warga: "<?= htmlspecialchars($prefill) ?>" — tambahkan kata kuncinya di bawah.</p>
    <?php endif; ?>
    <div class="grid sm:grid-cols-3 gap-3">
      <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Kode (unik)</span>
        <input name="code" required maxlength="40" value="<?= $v('code') ?>" placeholder="CONTOH_TOPIK" class="<?= $input ?> font-mono uppercase"></label>
      <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Teks tombol</span>
        <input name="label" required maxlength="60" value="<?= $v('label') ?>" class="<?= $input ?>"></label>
      <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Aksi</span>
        <select name="action" class="<?= $input ?>">
          <?php foreach (Chatbot::ACTIONS as $k => $label): ?>
            <option value="<?= $k ?>" <?= ($e['action'] ?? 'NONE') === $k ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select></label>
    </div>
    <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Kata / frasa kunci (pisahkan dengan koma)</span>
      <textarea name="keywords" required rows="2" maxlength="2000" class="<?= $input ?>"><?= $v('keywords', $prefill) ?></textarea></label>
    <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Jawaban</span>
      <textarea name="answer" required rows="5" maxlength="2000" class="<?= $input ?>"><?= $v('answer') ?></textarea></label>
    <div class="grid sm:grid-cols-3 gap-3">
      <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Tautan (opsional)</span>
        <input name="link_url" maxlength="255" value="<?= $v('link_url') ?>" placeholder="/report/create atau https://…" class="<?= $input ?> font-mono"></label>
      <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Teks tautan</span>
        <input name="link_label" maxlength="60" value="<?= $v('link_label') ?>" class="<?= $input ?>"></label>
      <label class="block"><span class="block text-[11px] font-semibold text-slate-300 mb-1">Tombol lanjutan (kode, pisah koma)</span>
        <input name="quick_replies" maxlength="255" value="<?= $v('quick_replies') ?>" placeholder="CARA_LAPOR,PETUGAS" class="<?= $input ?> font-mono uppercase"></label>
    </div>
    <div class="flex flex-wrap items-center gap-5 text-xs text-slate-300">
      <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= ($e['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif</label>
      <label class="flex items-center gap-2"><input type="checkbox" name="show_in_menu" value="1" <?= !empty($e['show_in_menu']) ? 'checked' : '' ?>> Tampil di menu pembuka</label>
      <label class="flex items-center gap-2">Urutan <input type="number" name="sort_order" min="0" max="9999" value="<?= $v('sort_order', '100') ?>" class="w-20 <?= $input ?>"></label>
      <button class="ml-auto px-5 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-slate-950 font-black">Simpan</button>
    </div>
  </form>
  <?php if ($isEdit): ?>
    <form action="<?= $baseUrl ?>/chatbot/delete" method="POST" onsubmit="return confirm('Hapus intent ini?')" class="text-right -mt-3">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
      <button class="text-[11px] text-red-300 hover:text-red-200"><i class="fas fa-trash mr-1"></i> Hapus intent ini</button>
    </form>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
  document.getElementById('cbTest').addEventListener('submit', async (e) => {
    e.preventDefault();
    const q = e.target.elements.q.value;
    const out = document.getElementById('cbTestResult');
    const res = await fetch('<?= $baseUrl ?>/chatbot/test?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    out.classList.remove('hidden');
    out.textContent = '';
    const head = document.createElement('p');
    head.className = 'font-bold ' + (data.matched ? 'text-emerald-300' : 'text-red-300');
    head.textContent = data.matched
      ? 'Cocok: ' + data.label + ' (' + data.code + ') · skor ' + data.score
      : 'Tidak cocok (skor di bawah ' + data.min_score + ') — chatbot akan meminta warga mengulang / menawarkan petugas.';
    out.appendChild(head);
    if (data.matched) {
      const ans = document.createElement('p');
      ans.className = 'mt-1 whitespace-pre-line text-slate-300';
      ans.textContent = data.answer;
      out.appendChild(ans);
    }
  });
</script>
