<?php
use App\Models\LostFound;

$isLost = $item['report_type'] === 'LOST';
$input = 'w-full bg-[#0C0A08] border border-slate-700 focus:border-amber-500 rounded-xl px-3 py-2 text-xs text-slate-100 outline-none';
$hidden = '<input type="hidden" name="_csrf_token" value="' . $csrfToken . '"><input type="hidden" name="item_id" value="' . (int)$item['id'] . '">';
$fields = [
    'brand' => 'Merk / Tipe', 'color' => 'Warna', 'plate_number' => 'Plat Nomor', 'chassis_number' => 'No. Rangka',
    'engine_number' => 'No. Mesin', 'serial_number' => 'IMEI / No. Seri', 'person_name' => 'Nama Orang',
    'person_age' => 'Usia', 'person_gender' => 'Jenis Kelamin', 'animal_species' => 'Jenis Hewan', 'custody_location' => 'Disimpan di',
];
$matchTone = ['SUGGESTED' => 'border-indigo-700 bg-indigo-950/30', 'CONFIRMED' => 'border-emerald-600 bg-emerald-950/30', 'DISMISSED' => 'border-slate-800 opacity-60'];
$claimTone = ['PENDING' => 'text-amber-300', 'APPROVED' => 'text-emerald-300', 'REJECTED' => 'text-red-300'];
?>
<div class="space-y-6">

  <a href="<?= $baseUrl ?>/hilangtemu/admin" class="text-xs text-slate-400 hover:text-amber-400"><i class="fas fa-arrow-left mr-1"></i> Kembali ke daftar</a>

  <div class="polri-glass-card p-6 rounded-2xl border border-[#3B2F1C] flex flex-col lg:flex-row lg:items-start justify-between gap-4">
    <div class="space-y-1">
      <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="font-mono font-bold text-amber-400"><?= htmlspecialchars($item['code']) ?></span>
        <span class="px-1.5 py-0.5 rounded text-[10px] font-black <?= $isLost ? 'bg-rose-600 text-white' : 'bg-emerald-500 text-slate-950' ?>"><?= LostFound::TYPES[$item['report_type']] ?></span>
        <span class="text-slate-400"><?= LostFound::CATEGORIES[$item['item_category']][0] ?></span>
        <span class="px-2 py-0.5 rounded-full border border-amber-500/40 bg-amber-500/10 text-amber-300 font-bold"><?= LostFound::STATUSES[$item['status']] ?></span>
      </div>
      <h2 class="text-xl font-black text-slate-100 font-heading"><?= htmlspecialchars($item['title']) ?></h2>
      <p class="text-[11px] text-slate-500">
        Dilaporkan <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
        <?= !empty($item['officer_name']) ? ' · input loket oleh ' . htmlspecialchars($item['officer_name']) : '' ?>
        <?= !empty($item['verifier_name']) ? ' · diverifikasi ' . htmlspecialchars($item['verifier_name']) : '' ?>
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if (in_array($item['status'], LostFound::PUBLIC_STATUSES, true)): ?>
        <a href="<?= $baseUrl ?>/hilangtemu/item/<?= (int)$item['id'] ?>" target="_blank" class="px-3 py-2 rounded-xl bg-[#1B1610] border border-slate-700 text-xs font-bold text-slate-200"><i class="fas fa-globe mr-1"></i> Tampilan Publik</a>
      <?php endif; ?>
      <?php if ($isLost && !in_array($item['status'], ['PENDING', 'REJECTED'], true)): ?>
        <a href="<?= $baseUrl ?>/hilangtemu/sktlk/<?= (int)$item['id'] ?>" target="_blank" class="px-3 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-black"><i class="fas fa-print mr-1"></i> SKTLK</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <!-- Detail -->
    <div class="lg:col-span-2 space-y-6">
      <div class="polri-glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
        <p class="text-sm text-slate-200 whitespace-pre-line"><?= htmlspecialchars($item['description']) ?></p>
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-xs">
          <dt class="text-slate-500"><?= $isLost ? 'Tanggal hilang' : 'Tanggal ditemukan' ?></dt><dd class="text-slate-200"><?= date('d/m/Y', strtotime($item['event_date'])) ?></dd>
          <dt class="text-slate-500">Lokasi</dt>
          <dd class="text-slate-200">
            <?= htmlspecialchars($item['event_location']) ?>
            <?php if (!empty($item['latitude'])): ?>
              <a href="https://www.google.com/maps?q=<?= (float)$item['latitude'] ?>,<?= (float)$item['longitude'] ?>" target="_blank" rel="noopener" class="text-amber-400 ml-1"><i class="fas fa-map-location-dot"></i></a>
            <?php endif; ?>
          </dd>
          <?php foreach ($fields as $f => $label): if (empty($item[$f])) continue; ?>
            <dt class="text-slate-500"><?= $label ?></dt>
            <dd class="text-slate-200 font-mono"><?= htmlspecialchars($f === 'person_gender' ? ($item[$f] === 'L' ? 'Laki-laki' : 'Perempuan') : (string)$item[$f]) ?></dd>
          <?php endforeach; ?>
          <dt class="text-slate-500">Pelapor</dt>
          <dd class="text-slate-200"><?= htmlspecialchars($item['reporter_name']) ?> · <a href="tel:<?= htmlspecialchars($item['reporter_phone']) ?>" class="text-amber-400"><?= htmlspecialchars($item['reporter_phone']) ?></a><?= $item['reporter_nik'] ? ' · NIK ' . htmlspecialchars($item['reporter_nik']) : '' ?></dd>
          <?php if (!empty($item['officer_note'])): ?>
            <dt class="text-slate-500">Catatan petugas</dt><dd class="text-slate-200"><?= htmlspecialchars($item['officer_note']) ?></dd>
          <?php endif; ?>
        </dl>
        <?php if ($photos): ?>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($photos as $p): ?>
              <a href="<?= $baseUrl ?>/hilangtemu/foto/<?= (int)$p['id'] ?>" target="_blank"><img src="<?= $baseUrl ?>/hilangtemu/foto/<?= (int)$p['id'] ?>" alt="" class="w-28 h-28 object-cover rounded-xl border border-slate-700"></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Pencocokan -->
      <div class="polri-glass-card p-6 rounded-2xl border border-slate-800 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-black text-slate-100"><i class="fas fa-link text-indigo-400 mr-1"></i> Saran Kecocokan Otomatis (<?= count($matches) ?>)</h3>
          <?php if ($canAct && in_array($item['status'], LostFound::OPEN_STATUSES, true)): ?>
            <form action="<?= $baseUrl ?>/hilangtemu/rematch" method="POST"><?= $hidden ?>
              <button class="text-[11px] px-3 py-1.5 rounded-lg border border-slate-700 text-slate-300 hover:bg-[#1B1610]"><i class="fas fa-rotate mr-1"></i> Cocokkan ulang</button>
            </form>
          <?php endif; ?>
        </div>
        <?php if (!$matches): ?>
          <p class="text-xs text-slate-500">Belum ada laporan <?= $isLost ? 'temuan' : 'kehilangan' ?> yang mirip (skor ≥ <?= LostFound::MATCH_THRESHOLD ?>).</p>
        <?php endif; ?>
        <?php foreach ($matches as $m): ?>
          <div class="p-4 rounded-xl border <?= $matchTone[$m['status']] ?> space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
              <a href="<?= $baseUrl ?>/hilangtemu/detail/<?= (int)$m['other_id'] ?>" class="font-mono font-bold text-amber-400 hover:underline"><?= htmlspecialchars($m['other_code']) ?></a>
              <span class="font-black <?= $m['score'] >= 70 ? 'text-emerald-300' : 'text-indigo-300' ?>">Skor <?= (int)$m['score'] ?>/100 · <?= $m['status'] ?></span>
            </div>
            <div class="text-sm text-slate-100 font-semibold"><?= htmlspecialchars($m['other_title']) ?></div>
            <div class="text-[11px] text-slate-400">
              <?= date('d/m/Y', strtotime($m['other_event_date'])) ?> · <?= htmlspecialchars($m['other_location']) ?> ·
              Pelapor: <?= htmlspecialchars($m['other_reporter']) ?> (<?= htmlspecialchars($m['other_phone']) ?>) · <?= LostFound::STATUSES[$m['other_status']] ?>
            </div>
            <?php if ($m['reasons']): ?><div class="text-[11px] text-indigo-200"><i class="fas fa-circle-info mr-1"></i><?= htmlspecialchars($m['reasons']) ?></div><?php endif; ?>
            <?php if ($canAct && $m['status'] === 'SUGGESTED'): ?>
              <form action="<?= $baseUrl ?>/hilangtemu/match" method="POST" class="flex gap-2"><?= $hidden ?>
                <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
                <button name="decision" value="confirm" class="px-3 py-1.5 rounded-lg bg-emerald-500 text-slate-950 text-[11px] font-black">Konfirmasi Cocok</button>
                <button name="decision" value="dismiss" class="px-3 py-1.5 rounded-lg border border-slate-700 text-slate-300 text-[11px] font-bold">Bukan</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!$isLost): ?>
      <!-- Klaim -->
      <div class="polri-glass-card p-6 rounded-2xl border border-slate-800 space-y-3">
        <h3 class="text-sm font-black text-slate-100"><i class="fas fa-hand text-amber-400 mr-1"></i> Klaim Kepemilikan (<?= count($claims) ?>)</h3>
        <?php if (!$claims): ?><p class="text-xs text-slate-500">Belum ada klaim dari warga.</p><?php endif; ?>
        <?php foreach ($claims as $c): ?>
          <div class="p-4 rounded-xl bg-[#0C0A08] border border-slate-800 space-y-2 text-xs">
            <div class="flex flex-wrap justify-between gap-2">
              <span class="font-bold text-slate-100"><?= htmlspecialchars($c['claimant_name']) ?> · NIK <?= htmlspecialchars($c['claimant_nik']) ?> ·
                <a href="tel:<?= htmlspecialchars($c['claimant_phone']) ?>" class="text-amber-400"><?= htmlspecialchars($c['claimant_phone']) ?></a></span>
              <span class="font-black <?= $claimTone[$c['status']] ?>"><?= $c['status'] ?></span>
            </div>
            <p class="text-slate-300 whitespace-pre-line"><?= htmlspecialchars($c['proof_description']) ?></p>
            <div class="text-[11px] text-slate-500">
              Diajukan <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
              <?php if ($c['proof_file_path']): ?> · <a href="<?= $baseUrl ?>/hilangtemu/bukti/<?= (int)$c['id'] ?>" target="_blank" class="text-amber-400"><i class="fas fa-paperclip"></i> Bukti kepemilikan</a><?php endif; ?>
              <?php if ($c['reviewer_name']): ?> · diputus <?= htmlspecialchars($c['reviewer_name']) ?><?= $c['review_note'] ? ': ' . htmlspecialchars($c['review_note']) : '' ?><?php endif; ?>
            </div>
            <?php if ($canAct && $c['status'] === 'PENDING'): ?>
              <form action="<?= $baseUrl ?>/hilangtemu/claim" method="POST" class="flex flex-col sm:flex-row gap-2"><?= $hidden ?>
                <input type="hidden" name="claim_id" value="<?= (int)$c['id'] ?>">
                <input name="review_note" maxlength="500" placeholder="Catatan (wajib bila ditolak)" class="flex-1 <?= $input ?>">
                <button name="decision" value="approve" class="px-3 py-2 rounded-lg bg-emerald-500 text-slate-950 text-[11px] font-black">Setujui</button>
                <button name="decision" value="reject" class="px-3 py-2 rounded-lg bg-red-700 text-white text-[11px] font-black">Tolak</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Aksi -->
    <div class="space-y-4">
      <?php if ($canAct): ?>
        <?php if ($item['status'] === 'PENDING'): ?>
          <form action="<?= $baseUrl ?>/hilangtemu/verify" method="POST" class="polri-glass-card p-5 rounded-2xl border border-emerald-700/50 space-y-3"><?= $hidden ?>
            <h3 class="text-sm font-black text-emerald-300"><i class="fas fa-check-double mr-1"></i> Verifikasi &amp; Tayangkan</h3>
            <p class="text-[11px] text-slate-400">Pastikan laporan wajar &amp; tidak memuat data sensitif di judul/deskripsi. Kontak pelapor tidak ditayangkan.</p>
            <?php if (!$isLost): ?>
              <input name="custody_location" maxlength="255" value="<?= htmlspecialchars((string)$item['custody_location']) ?>" placeholder="Lokasi penyimpanan barang temuan" class="<?= $input ?>">
            <?php endif; ?>
            <button class="w-full py-2 rounded-xl bg-emerald-500 text-slate-950 text-xs font-black">Verifikasi</button>
          </form>
        <?php endif; ?>

        <?php if (in_array($item['status'], ['PUBLISHED', 'MATCHED'], true)): ?>
          <form action="<?= $baseUrl ?>/hilangtemu/resolve" method="POST" class="polri-glass-card p-5 rounded-2xl border border-amber-600/50 space-y-3"><?= $hidden ?>
            <h3 class="text-sm font-black text-amber-300"><i class="fas fa-handshake mr-1"></i> Serah Terima / Selesai</h3>
            <p class="text-[11px] text-slate-400">Catat penerima, waktu, dan tempat serah terima. Laporan pasangan yang terkonfirmasi ikut diselesaikan.</p>
            <textarea name="officer_note" rows="3" maxlength="1000" required placeholder="Contoh: Diserahkan kepada pemilik a.n. … (KTP …) di SPKT Polres … pada …" class="<?= $input ?>"></textarea>
            <button class="w-full py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-black">Tandai Selesai</button>
          </form>
        <?php endif; ?>

        <?php if (in_array($item['status'], ['PENDING', 'PUBLISHED'], true)): ?>
          <form action="<?= $baseUrl ?>/hilangtemu/reject" method="POST" class="polri-glass-card p-5 rounded-2xl border border-red-800/60 space-y-3"><?= $hidden ?>
            <h3 class="text-sm font-black text-red-300"><i class="fas fa-ban mr-1"></i> Tolak / Turunkan</h3>
            <textarea name="officer_note" rows="2" maxlength="1000" required placeholder="Alasan (dapat dilihat pelapor)" class="<?= $input ?>"></textarea>
            <button class="w-full py-2 rounded-xl bg-red-700 text-white text-xs font-black">Tolak</button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <div class="polri-glass-card p-5 rounded-2xl border border-slate-800 text-xs text-slate-400"><i class="fas fa-eye mr-1"></i> Mode pantau (hanya baca).</div>
      <?php endif; ?>
    </div>
  </div>
</div>
