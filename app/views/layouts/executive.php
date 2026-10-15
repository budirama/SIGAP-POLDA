<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard Eksekutif Kapolri') ?> - SIGAP POLRI</title>
  <?php require __DIR__ . "/_head.php"; ?>
  
  <!-- Leaflet GIS Map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-[#090705] text-slate-100 min-h-screen flex flex-col selection:bg-amber-500 selection:text-slate-950 font-sans">

  <!-- Top Tactical Command Bar -->
  <div class="ribbon-merah-putih"></div>
  <header class="bg-[#110D09]/95 border-b border-amber-500/30 px-6 py-3 flex items-center justify-between sticky top-0 z-50 backdrop-blur-xl shadow-2xl">
    
    <!-- Left: Kapolri Emblem & Title -->
    <div class="flex items-center gap-4">
      <div class="relative">
        <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="h-14 w-auto object-contain drop-shadow">
      </div>
      <div>
        <div class="flex items-center gap-2">
          <span class="font-black text-lg tracking-wider text-slate-100 font-heading">EXECUTIVE COMMAND CENTER</span>
          <span class="text-[10px] font-black px-2 py-0.5 rounded bg-red-950 text-red-400 border border-red-700/80 tracking-widest animate-pulse">KAPOLRI</span>
        </div>
        <p class="text-xs text-amber-400 font-bold tracking-wide">
          POLISI BERSAMA MASYARAKAT • MELINDUNGI, MENGAYOMI DAN MELAYANI
        </p>
      </div>
    </div>

    <!-- Center: Live Tactical Digital Clock -->
    <div class="hidden lg:flex items-center gap-3 px-5 py-2 rounded-2xl bg-[#070504] border border-amber-500/20 font-mono">
      <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></div>
      <div class="text-xs text-slate-400">WAKTU OPERASIONAL (WIB):</div>
      <div id="liveClock" class="text-sm font-black text-amber-400 font-mono tracking-wider">--:--:-- WIB</div>
    </div>

    <!-- Right: Profile & Actions -->
    <div class="flex items-center gap-3">
      <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SPKT_OPERATOR, \App\Config\Constants::ROLE_INTERNAL_AFFAIRS])): ?>
      <a href="<?= $baseUrl ?>/spkt" class="px-3.5 py-2 rounded-xl bg-blue-950/80 hover:bg-blue-900 border border-blue-800 text-blue-200 text-xs font-bold transition-all flex items-center gap-2">
        <i class="fas fa-desktop text-amber-400"></i>
        <span class="hidden sm:inline">Meja SPKT</span>
      </a>
      <?php endif; ?>

      <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_CYBER_HUMAS, \App\Config\Constants::ROLE_SPKT_OPERATOR])): ?>
      <a href="<?= $baseUrl ?>/social" class="px-3.5 py-2 rounded-xl bg-sky-950/80 hover:bg-sky-900 border border-sky-800 text-sky-200 text-xs font-bold transition-all flex items-center gap-2">
        <i class="fab fa-x-twitter text-sky-400"></i>
        <span class="hidden sm:inline">Radar Medsos</span>
      </a>
      <?php endif; ?>

      <a href="<?= $baseUrl ?>/auth/logout" class="px-3 py-2 rounded-xl bg-red-950/60 hover:bg-red-900/80 border border-red-800/60 text-red-300 text-xs font-bold transition-all flex items-center gap-1.5" title="Logout">
        <i class="fas fa-power-off"></i>
      </a>
    </div>

  </header>

  <!-- Flash Messages -->
  <?php if (\App\Core\Session::hasFlash('success')): ?>
    <div class="p-3 mx-6 mt-4 rounded-xl bg-emerald-950/90 border border-emerald-700 text-emerald-300 text-xs flex items-center gap-2 shadow-xl">
      <i class="fas fa-circle-check text-emerald-400 text-sm"></i>
      <span class="font-bold"><?= htmlspecialchars(\App\Core\Session::getFlash('success')[0]) ?></span>
    </div>
  <?php endif; ?>

  <?php if (\App\Core\Session::hasFlash('error')): ?>
    <div class="p-3 mx-6 mt-4 rounded-xl bg-red-950/90 border border-red-700 text-red-300 text-xs flex items-center gap-2 shadow-xl">
      <i class="fas fa-circle-exclamation text-red-400 text-sm"></i>
      <span class="font-bold"><?= htmlspecialchars(\App\Core\Session::getFlash('error')[0]) ?></span>
    </div>
  <?php endif; ?>

  <!-- Main Executive Canvas -->
  <main class="hud-grid relative isolate flex-1 p-4 sm:p-6 lg:p-8">
    <?= $content ?>
  </main>

  <script>
    function updateClock() {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      const s = String(now.getSeconds()).padStart(2, '0');
      const clockEl = document.getElementById('liveClock');
      if (clockEl) {
        clockEl.innerText = `${h}:${m}:${s} WIB`;
      }
    }
    setInterval(updateClock, 1000);
    updateClock();
  </script>

</body>
</html>
