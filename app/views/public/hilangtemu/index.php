<?php
use App\Models\LostFound;

$selectCls = 'bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 rounded-xl px-3 py-2.5 text-xs text-slate-100 outline-none';
$filterUrl = function (array $override) use ($baseUrl, $filters): string {
    $q = array_filter(array_merge($filters, $override), fn($v) => $v !== '' && $v !== null);
    return htmlspecialchars($baseUrl . '/hilangtemu' . ($q ? '?' . http_build_query($q) : ''));
};
?>
<div class="py-10 max-w-7xl mx-auto px-4 space-y-8">

  <nav class="flex items-center gap-2 text-xs text-slate-400">
    <a href="<?= $baseUrl ?>/" class="hover:text-amber-400 transition-colors">Beranda</a>
    <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
    <span class="text-amber-400 font-semibold">Kehilangan dan Temuan</span>
  </nav>

  <!-- Hero -->
  <div class="polri-glass-card rounded-2xl p-6 sm:p-8 border border-amber-500/30 shadow-xl grid lg:grid-cols-5 gap-6 items-center">
    <div class="lg:col-span-3 space-y-3">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold">
        <i class="fas fa-magnifying-glass-location"></i> Layanan Kehilangan dan Temuan Polri
      </div>
      <h1 class="text-2xl sm:text-3xl font-black text-slate-100 font-heading">Kehilangan sesuatu? Menemukan sesuatu?</h1>
      <p class="text-sm text-slate-400 leading-relaxed">
        Laporkan kendaraan, orang hilang, barang berharga, dokumen, maupun hewan peliharaan. Sistem mencocokkan otomatis
        laporan kehilangan dengan temuan, lalu petugas memverifikasi dan memfasilitasi serah terima kepada pemilik sah.
      </p>
    </div>
    <div class="lg:col-span-2 grid grid-cols-2 gap-3">
      <a href="<?= $baseUrl ?>/hilangtemu/lapor?type=LOST" class="p-4 rounded-2xl bg-rose-950/40 border border-rose-700/50 hover:border-rose-400 transition-colors">
        <i class="fas fa-circle-exclamation text-rose-300 text-xl mb-2"></i>
        <div class="text-sm font-black text-slate-100">Lapor Kehilangan</div>
        <div class="text-[11px] text-slate-400">Dapat SKTLK setelah diverifikasi</div>
      </a>
      <a href="<?= $baseUrl ?>/hilangtemu/lapor?type=FOUND" class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-700/50 hover:border-emerald-400 transition-colors">
        <i class="fas fa-hand-holding-heart text-emerald-300 text-xl mb-2"></i>
        <div class="text-sm font-black text-slate-100">Lapor Temuan</div>
        <div class="text-[11px] text-slate-400">Bantu kembalikan ke pemiliknya</div>
      </a>
      <a href="<?= $baseUrl ?>/hilangtemu/status" class="col-span-2 p-3 rounded-xl bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 text-xs font-bold text-slate-200 flex items-center justify-center gap-2">
        <i class="fas fa-ticket text-amber-400"></i> Cek status laporan saya (Kode + PIN)
      </a>
    </div>
  </div>

  <!-- Kategori -->
  <div class="flex flex-wrap gap-2">
    <a href="<?= $filterUrl(['category' => '', 'page' => null]) ?>"
       class="px-3 py-1.5 rounded-full text-xs font-bold border <?= $filters['category'] === '' ? 'bg-amber-500 text-slate-950 border-amber-500' : 'border-slate-700 text-slate-300 hover:border-amber-500/50' ?>">Semua</a>
    <?php foreach (LostFound::CATEGORIES as $key => [$label, $icon]): ?>
      <a href="<?= $filterUrl(['category' => $key, 'page' => null]) ?>"
         class="px-3 py-1.5 rounded-full text-xs font-bold border flex items-center gap-1.5 <?= $filters['category'] === $key ? 'bg-amber-500 text-slate-950 border-amber-500' : 'border-slate-700 text-slate-300 hover:border-amber-500/50' ?>">
        <i class="fas <?= $icon ?>"></i> <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Filter -->
  <form method="GET" action="<?= $baseUrl ?>/hilangtemu" class="grid sm:grid-cols-12 gap-3">
    <input type="hidden" name="category" value="<?= htmlspecialchars($filters['category']) ?>">
    <select name="type" class="sm:col-span-3 <?= $selectCls ?>" aria-label="Jenis laporan">
      <option value="">Kehilangan &amp; Temuan</option>
      <?php foreach (LostFound::TYPES as $key => $label): ?>
        <option value="<?= $key ?>" <?= $filters['type'] === $key ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Cari plat nomor, merk, nama, lokasi, atau kode HT-…"
           class="sm:col-span-7 <?= $selectCls ?> placeholder-slate-600">
    <button class="sm:col-span-2 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-slate-950 text-xs font-black"><i class="fas fa-magnifying-glass mr-1"></i> Cari</button>
  </form>

  <!-- Galeri -->
  <?php if (empty($pager['items'])): ?>
    <div class="polri-glass-card rounded-2xl p-10 text-center border border-slate-800 text-slate-400 text-sm">
      <i class="fas fa-box-open text-3xl text-slate-600 mb-3"></i>
      <p>Belum ada laporan yang sesuai dengan pencarian Anda.</p>
    </div>
  <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <?php foreach ($pager['items'] as $it):
        [$catLabel, $catIcon] = LostFound::CATEGORIES[$it['item_category']]; ?>
        <a href="<?= $baseUrl ?>/hilangtemu/item/<?= (int)$it['id'] ?>" class="group rounded-2xl bg-[#1B1610] border border-[#3B2F1C] hover:border-amber-500/50 overflow-hidden flex flex-col transition-colors">
          <div class="aspect-[4/3] bg-[#0C0A08] flex items-center justify-center relative">
            <?php if (!empty($it['cover_photo_id'])): ?>
              <img src="<?= $baseUrl ?>/hilangtemu/foto/<?= (int)$it['cover_photo_id'] ?>" alt="" loading="lazy" class="w-full h-full object-cover">
            <?php else: ?>
              <i class="fas <?= $catIcon ?> text-4xl text-slate-700"></i>
            <?php endif; ?>
            <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-black <?= $it['report_type'] === 'LOST' ? 'bg-rose-600 text-white' : 'bg-emerald-500 text-slate-950' ?>">
              <?= $it['report_type'] === 'LOST' ? 'DICARI' : 'DITEMUKAN' ?>
            </span>
            <?php if ($it['status'] === 'MATCHED'): ?>
              <span class="absolute top-2 right-2 px-2 py-0.5 rounded text-[10px] font-black bg-amber-500 text-slate-950">PROSES SERAH TERIMA</span>
            <?php endif; ?>
          </div>
          <div class="p-4 space-y-1.5 flex-1">
            <div class="text-[10px] text-amber-400 font-bold flex items-center gap-1.5"><i class="fas <?= $catIcon ?>"></i> <?= $catLabel ?></div>
            <h3 class="text-sm font-bold text-slate-100 group-hover:text-amber-300 line-clamp-2"><?= htmlspecialchars($it['title']) ?></h3>
            <p class="text-[11px] text-slate-400"><i class="fas fa-location-dot mr-1 text-slate-500"></i><?= htmlspecialchars($it['event_location']) ?></p>
            <p class="text-[11px] text-slate-500"><i class="far fa-calendar mr-1"></i><?= date('d/m/Y', strtotime($it['event_date'])) ?> · <span class="font-mono"><?= htmlspecialchars($it['code']) ?></span></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="polri-glass-card rounded-2xl border border-slate-800">
      <?php $pagerPath = '/hilangtemu'; require dirname(__DIR__, 2) . '/backoffice/_pagination.php'; ?>
    </div>
  <?php endif; ?>

  <p class="text-[11px] text-slate-500 text-center">
    Melihat orang hilang atau kendaraan curian yang dicari? Segera hubungi <a href="tel:110" class="text-rose-400 font-bold">Call Center 110</a>
    atau kantor polisi terdekat. Jangan bertindak sendiri.
  </p>
</div>
