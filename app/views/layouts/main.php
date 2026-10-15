<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Portal Pengaduan Masyarakat') ?> - SIGAP POLRI</title>
  <?php require __DIR__ . "/_head.php"; ?>
  
  <!-- Leaflet GIS Map CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-[#0C0A08] text-slate-100 min-h-screen flex flex-col selection:bg-amber-500 selection:text-slate-950">

  <!-- Sang Saka Merah Putih Ribbon Accent Line -->
  <div class="ribbon-merah-putih"></div>

  <!-- POLRI Official Top Banner -->
  <div class="polri-topbar border-b border-amber-500/20 px-4 py-1.5 text-[11px] text-slate-300">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
        <span class="font-bold text-amber-400 tracking-wider">POLISI BERSAMA MASYARAKAT</span>
        <span class="hidden sm:inline text-slate-400">• Melindungi, Mengayomi dan Melayani • Polri Presisi</span>
      </div>
      <div class="flex items-center gap-4">
        <?php $officialLinksVariant = 'topbar'; require __DIR__ . '/_official_links.php'; ?>
        <a href="tel:110" class="text-rose-400 hover:text-rose-300 font-bold flex items-center gap-1">
          <i class="fas fa-phone-volume"></i> Panggilan Darurat: 110
        </a>
      </div>
    </div>
  </div>

  <!-- Navbar Navigation -->
  <nav class="sticky top-0 z-50 polri-glass border-b border-[#3B2F1C]/80 px-4 py-2.5">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      
      <!-- Logo & Brand Name -->
      <a href="<?= $baseUrl ?>/" class="flex items-center gap-3 group">
        <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="h-12 w-auto object-contain drop-shadow transition-transform group-hover:scale-105">
        <div>
          <div class="flex items-center gap-2">
            <span class="font-black text-base sm:text-lg text-slate-50 tracking-wider font-heading whitespace-nowrap">SIGAP-POLRI</span>
            <span class="hidden lg:inline text-[9px] font-black px-1.5 py-0.5 rounded bg-red-950 text-red-300 border border-red-700/70 tracking-wider">BERSAMA MASYARAKAT</span>
          </div>
          <p class="hidden sm:block text-[10px] text-amber-400 tracking-wider font-bold">MELINDUNGI • MENGAYOMI • MELAYANI</p>
        </div>
      </a>

      <!-- Navigation Links -->
      <div class="hidden xl:flex items-center gap-5 text-sm font-semibold text-slate-300 whitespace-nowrap">
        <a href="<?= $baseUrl ?>/" class="hover:text-amber-400 transition-colors">Beranda</a>
        <a href="<?= $baseUrl ?>/#layanan-warga" class="hover:text-amber-400 transition-colors">Layanan Warga</a>
        <a href="<?= $baseUrl ?>/report/track" class="hover:text-amber-400 transition-colors">Lacak SP2HP</a>
        <a href="<?= $baseUrl ?>/#alur-pelayanan" class="hover:text-amber-400 transition-colors">Alur Pelayanan</a>
        <a href="<?= $baseUrl ?>/#management-posko" class="hover:text-amber-400 transition-colors flex items-center gap-1.5">Management Posko <span class="text-[9px] font-black px-1 py-0.5 rounded bg-amber-500 text-slate-950">BARU</span></a>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Menu ringkas untuk layar < xl -->
        <details class="xl:hidden relative group">
          <summary class="list-none cursor-pointer p-2 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-200 border border-[#3B2F1C] text-xs" aria-label="Buka menu navigasi">
            <i class="fas fa-bars text-sm"></i>
          </summary>
          <div class="absolute right-0 mt-2 w-60 p-2 rounded-2xl bg-[#14100C] border border-[#3B2F1C] shadow-2xl text-sm font-semibold text-slate-300 flex flex-col">
            <a href="<?= $baseUrl ?>/" class="px-3 py-2 rounded-lg hover:bg-[#1B1610] hover:text-amber-400">Beranda</a>
            <a href="<?= $baseUrl ?>/#layanan-warga" class="px-3 py-2 rounded-lg hover:bg-[#1B1610] hover:text-amber-400">Layanan Warga</a>
            <a href="<?= $baseUrl ?>/report/track" class="px-3 py-2 rounded-lg hover:bg-[#1B1610] hover:text-amber-400">Lacak SP2HP</a>
            <a href="<?= $baseUrl ?>/#alur-pelayanan" class="px-3 py-2 rounded-lg hover:bg-[#1B1610] hover:text-amber-400">Alur Pelayanan</a>
            <a href="<?= $baseUrl ?>/#management-posko" class="px-3 py-2 rounded-lg hover:bg-[#1B1610] hover:text-amber-400 flex items-center justify-between">Management Posko <span class="text-[9px] font-black px-1 py-0.5 rounded bg-amber-500 text-slate-950">BARU</span></a>
            <a href="<?= $baseUrl ?>/auth/login" class="px-3 py-2 rounded-lg hover:bg-[#1B1610] hover:text-amber-400 border-t border-[#3B2F1C] mt-1 pt-2.5"><i class="fas fa-user-shield text-amber-400 mr-1.5"></i>Login Personil &amp; Posko</a>
          </div>
        </details>
        <a href="<?= $baseUrl ?>/report/track" class="hidden sm:inline-flex whitespace-nowrap items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 text-xs font-bold text-slate-200 border border-slate-700 transition-all">
          <i class="fas fa-magnifying-glass text-amber-400"></i> Lacak Tiket
        </a>
        <a href="<?= $baseUrl ?>/report/create" class="btn-gold inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black transition-all shadow-md hover:shadow-amber-500/30">
          <i class="fas fa-pen-to-square"></i> <span class="whitespace-nowrap">Lapor<span class="hidden sm:inline"> Sekarang</span></span>
        </a>
        <a href="<?= $baseUrl ?>/auth/login" title="Login Dinas Backoffice" class="p-2 rounded-xl bg-[#1B1610] hover:bg-blue-950 text-amber-400 border border-amber-500/30 text-xs transition-all">
          <i class="fas fa-user-shield text-sm"></i>
        </a>
      </div>

    </div>
  </nav>

  <!-- Flash Messages -->
  <?php if (\App\Core\Session::hasFlash('success')): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
      <div class="p-3.5 bg-emerald-950/80 border border-emerald-800 rounded-xl text-emerald-300 text-xs flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-2.5">
          <i class="fas fa-circle-check text-emerald-400 text-base"></i>
          <span><?= htmlspecialchars(\App\Core\Session::getFlash('success')[0]) ?></span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (\App\Core\Session::hasFlash('error')): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
      <div class="p-3.5 bg-red-950/80 border border-red-800 rounded-xl text-red-300 text-xs flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-2.5">
          <i class="fas fa-circle-exclamation text-red-400 text-base"></i>
          <span><?= htmlspecialchars(\App\Core\Session::getFlash('error')[0]) ?></span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Content Slot -->
  <main class="flex-1">
    <?= $content ?>
  </main>

  <!-- Official Footer -->
  <footer class="hud-grid relative border-t border-[#3B2F1C] bg-[#0C0A08] mt-16 pt-12 pb-6 text-slate-400 text-xs">
    <div class="ribbon-merah-putih absolute top-0 inset-x-0 opacity-80"></div>
    <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-8 mb-8">
      
      <!-- Col 1: Identity -->
      <div class="md:col-span-2 space-y-4">
        <div class="flex items-center gap-3">
          <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="h-14 w-auto object-contain drop-shadow">
          <div>
            <h4 class="font-black text-sm text-slate-100 font-heading">POLISI BERSAMA MASYARAKAT</h4>
            <p class="text-[11px] text-amber-400 font-bold tracking-wider">MELINDUNGI • MENGAYOMI • MELAYANI</p>
            <p class="text-[10px] text-slate-400">Sentra Pelayanan Kepolisian Terpadu (SPKT) & Command Center Polri</p>
          </div>
        </div>
        <p class="text-slate-400 text-xs leading-relaxed max-w-lg">
          Layanan pelaporan masyarakat terpadu dengan transparansi penuh melalui SP2HP Elektronik dan komunikasi klarifikasi interaktif langsung bersama Tim Penyidik Polri.
        </p>
        <div class="pt-2">
          <img src="<?= $baseUrl ?>/assets/img/logo-presisi.svg" alt="Polri Presisi" class="h-10 object-contain">
        </div>
      </div>

      <!-- Col 2: Quick Links -->
      <div class="space-y-3">
        <h4 class="font-bold text-slate-200 text-xs uppercase tracking-wider text-amber-400">Layanan Masyarakat</h4>
        <ul class="space-y-2 text-xs">
          <li><a href="<?= $baseUrl ?>/report/create" class="hover:text-amber-400 transition-colors">Pengaduan Tindak Pidana</a></li>
          <li><a href="<?= $baseUrl ?>/report/track" class="hover:text-amber-400 transition-colors">Cek Status SP2HP Online</a></li>
          <li><a href="<?= $baseUrl ?>/auth/login" class="hover:text-amber-400 transition-colors">Portal Personil & Penyidik</a></li>
          <li><a href="<?= $baseUrl ?>/#management-posko" class="hover:text-amber-400 transition-colors">Management Posko Berjenjang</a></li>
          <li><a href="tel:110" class="hover:text-rose-400 transition-colors">Layanan Darurat Bebas Pulsa 110</a></li>
        </ul>
      </div>

      <!-- Col 3: Portal Resmi Polri -->
      <div class="space-y-3">
        <h4 class="font-bold text-xs uppercase tracking-wider text-amber-400">Portal Resmi Polri</h4>
        <?php $officialLinksVariant = 'footer'; require __DIR__ . '/_official_links.php'; ?>
      </div>

      <!-- Col 4: Direct Contact -->
      <div class="space-y-3">
        <h4 class="font-bold text-slate-200 text-xs uppercase tracking-wider text-amber-400">Posko Pengaduan</h4>
        <p class="text-xs leading-relaxed">
          SPKT Mabes Polri<br>
          Jl. Trunojoyo No. 3, Kebayoran Baru, Jakarta Selatan<br>
          Panggilan Darurat: 110 • Live Chat SPKT di pojok kanan bawah
        </p>
      </div>

    </div>

    <!-- Bottom Copyright -->
    <div class="border-t border-[#3B2F1C]/40 max-w-7xl mx-auto px-4 pt-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px]">
      <p>&copy; <?= date('Y') ?> Kepolisian Negara Republik Indonesia. Rastra Sewakotama.</p>
      <p class="text-slate-500 flex items-center gap-2"><span class="cube-3d" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></span> Dikembangkan bersama Divisi TIK Polri • <span class="font-mono">v<?= htmlspecialchars(\App\Config\Config::APP_VERSION) ?></span></p>
    </div>
  </footer>

  <script>
    // Tutup menu ringkas setelah link dipilih (link anchor tidak memuat ulang halaman)
    document.querySelectorAll('nav details a').forEach(function (a) {
      a.addEventListener('click', function () { a.closest('details').removeAttribute('open'); });
    });
  </script>

  <?php require __DIR__ . '/_livechat_widget.php'; ?>

</body>
</html>
