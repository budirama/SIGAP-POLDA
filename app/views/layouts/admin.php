<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Backoffice Kedinasan') ?> - SIGAP POLRI</title>
  <?php require __DIR__ . "/_head.php"; ?>
  
  <!-- Leaflet GIS Map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-[#0C0A08] text-slate-100 min-h-screen flex selection:bg-amber-500 selection:text-slate-950">

  <!-- Latar gelap menu off-canvas (layar kecil) -->
  <div id="sidebarBackdrop" class="hidden fixed inset-0 z-40 bg-black/60 md:hidden" onclick="toggleSidebar(false)"></div>

  <!-- Left Sidebar Navigation: off-canvas di HP, menempel di desktop -->
  <aside id="sidebar" aria-label="Menu navigasi"
         class="w-64 bg-[#14100C] border-r border-[#3B2F1C] flex flex-col justify-between flex-shrink-0 h-screen overflow-y-auto fixed inset-y-0 left-0 z-50 -translate-x-full transition-transform duration-200 md:sticky md:top-0 md:translate-x-0">
    
    <div>
      <!-- Ribbon Accent -->
      <div class="ribbon-merah-putih"></div>

      <!-- Brand & Police Logo -->
      <div class="p-4 border-b border-[#3B2F1C]/80 flex items-center gap-3">
        <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="h-12 w-auto object-contain drop-shadow">
        <div>
          <div class="font-black text-sm tracking-wider text-slate-100 flex items-center gap-1.5 font-heading">
            <span>SIGAP-POLRI</span>
          </div>
          <p class="text-[9px] text-red-400 font-bold uppercase tracking-wider">BERSAMA MASYARAKAT</p>
          <p class="text-[8px] text-amber-400 font-semibold tracking-tight">MELINDUNGI • MENGAYOMI</p>
        </div>
      </div>

      <!-- Officer Badge Summary -->
      <div class="p-4 mx-3 my-3 rounded-xl bg-[#1B1610] border border-slate-800 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold text-sm">
          <i class="fas fa-shield-halved"></i>
        </div>
        <div class="overflow-hidden">
          <div class="text-xs font-bold text-slate-100 truncate"><?= htmlspecialchars($authUser['name'] ?? 'Personil') ?></div>
          <div class="text-[10px] text-amber-400 font-semibold truncate"><?= htmlspecialchars($authUser['rank'] ?? 'Polri') ?> • <?= htmlspecialchars($authUser['position'] ?? 'Staf') ?></div>
          <div class="text-[9px] text-slate-400 truncate"><?= htmlspecialchars($authUser['satker_name'] ?? 'Mabes Polri') ?></div>
        </div>
      </div>

      <!-- Navigation Menus Based on Role -->
      <div class="px-3 py-2 space-y-1 text-xs font-semibold">
        
        <?php if (!\App\Core\PoskoAccess::isPoskoUser()): ?>
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 py-1 mt-2">MENU OPERASIONAL</div>
        <?php endif; ?>

        <!-- Executive Kapolri -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_EXECUTIVE, \App\Config\Constants::ROLE_SUPERADMIN])): ?>
          <a href="<?= $baseUrl ?>/executive" 
             class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-amber-400 hover:bg-amber-500/10 border border-amber-500/20 transition-all font-bold">
            <i class="fas fa-chart-line text-sm text-amber-400"></i>
            <span>Dashboard Kapolri</span>
          </a>
        <?php endif; ?>

        <!-- SPKT Operator -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SPKT_OPERATOR, \App\Config\Constants::ROLE_SUPERADMIN, \App\Config\Constants::ROLE_INTERNAL_AFFAIRS])): ?>
          <a href="<?= $baseUrl ?>/spkt" 
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-desktop text-blue-400 w-4"></i>
            <span>Triage SPKT Sentral</span>
          </a>
        <?php endif; ?>

        <!-- Humas / Cyber Patrol Medsos -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_CYBER_HUMAS, \App\Config\Constants::ROLE_SPKT_OPERATOR, \App\Config\Constants::ROLE_SUPERADMIN])): ?>
          <a href="<?= $baseUrl ?>/social" 
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fab fa-x-twitter text-sky-400 w-4"></i>
            <span>Social Media Ingestion</span>
          </a>
        <?php endif; ?>

        <!-- Satker Admin / Kanit / Kasatker -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SATKER_HEAD, \App\Config\Constants::ROLE_SATKER_ADMIN, \App\Config\Constants::ROLE_SUPERADMIN])): ?>
          <a href="<?= $baseUrl ?>/satker" 
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-inbox text-indigo-400 w-4"></i>
            <span class="flex-1">Disposisi Satker & Kanit</span>
            <?php $ticketPending = \App\Core\Auth::satkerId() ? (new \App\Models\Disposition())->countPendingForSatker((int)\App\Core\Auth::satkerId()) : 0; ?>
            <?php if ($ticketPending > 0): ?>
              <span class="px-1.5 min-w-[1.25rem] text-center rounded-full bg-red-600 text-white text-[10px] leading-5" title="Tiket menunggu diterima"><?= $ticketPending ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>

        <!-- Penyidik Pelaksana -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_INVESTIGATOR, \App\Config\Constants::ROLE_SATKER_ADMIN, \App\Config\Constants::ROLE_SUPERADMIN])): ?>
          <a href="<?= $baseUrl ?>/investigator" 
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-user-edit text-yellow-400 w-4"></i>
            <span>Workspace Penyidik</span>
          </a>
        <?php endif; ?>

        <!-- Omnichat: kanal chat warga -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SPKT_OPERATOR, \App\Config\Constants::ROLE_CYBER_HUMAS, \App\Config\Constants::ROLE_SUPERADMIN, \App\Config\Constants::ROLE_INTERNAL_AFFAIRS])): ?>
          <?php $chatWaiting = (new \App\Models\Chat())->countWaiting(); ?>
          <a href="<?= $baseUrl ?>/omnichat"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-comments text-amber-400 w-4"></i>
            <span class="flex-1">Omnichat Live Chat</span>
            <span id="navChatWaiting" class="px-1.5 min-w-[1.25rem] text-center rounded-full bg-red-600 text-white text-[10px] leading-5"
                  <?= $chatWaiting > 0 ? '' : 'hidden' ?>><?= $chatWaiting ?></span>
          </a>
          <a href="<?= $baseUrl ?>/chatbot"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-robot text-sky-400 w-4"></i>
            <span>Basis Pengetahuan Chatbot</span>
          </a>
        <?php endif; ?>

        <!-- Kehilangan dan Temuan: verifikasi, pencocokan, klaim & serah terima -->
        <?php if ((!class_exists(\App\Core\ModuleManager::class) || \App\Core\ModuleManager::isActive('op_lostfound')) && \App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SPKT_OPERATOR, \App\Config\Constants::ROLE_SATKER_HEAD, \App\Config\Constants::ROLE_SATKER_ADMIN, \App\Config\Constants::ROLE_CYBER_HUMAS, \App\Config\Constants::ROLE_EXECUTIVE, \App\Config\Constants::ROLE_INTERNAL_AFFAIRS, \App\Config\Constants::ROLE_SUPERADMIN])): ?>
          <a href="<?= $baseUrl ?>/hilangtemu/admin"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-magnifying-glass-location text-emerald-400 w-4"></i>
            <span>Kehilangan dan Temuan</span>
          </a>
        <?php endif; ?>

        <!-- Satu Nomor, Satu Jejak v1.3 Presisi -->
        <?php if (\App\Core\ModuleManager::isActive('ONESERVICE') && \App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SPKT_OPERATOR, \App\Config\Constants::ROLE_CYBER_HUMAS, \App\Config\Constants::ROLE_SATKER_HEAD, \App\Config\Constants::ROLE_SATKER_ADMIN, \App\Config\Constants::ROLE_INVESTIGATOR, \App\Config\Constants::ROLE_INTERNAL_AFFAIRS, \App\Config\Constants::ROLE_EXECUTIVE, \App\Config\Constants::ROLE_SUPERADMIN])): ?>
          <div class="text-[10px] font-bold text-amber-500/80 uppercase tracking-widest px-3 py-1 mt-4">SATU NOMOR, SATU JEJAK</div>
          <?php if (\App\Core\ModuleManager::isActive('oneservice_hub')): ?>
          <a href="<?= $baseUrl ?>/oneservice"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-fingerprint text-amber-400 w-4"></i>
            <span>Hub Layanan Terpadu</span>
          </a>
          <?php endif; ?>
          <?php if (\App\Core\ModuleManager::isActive('oneservice_dashboard')): ?>
          <a href="<?= $baseUrl ?>/oneservice/dashboard"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-chart-pie text-emerald-400 w-4"></i>
            <span>Akuntabilitas 100 Hari</span>
          </a>
          <?php endif; ?>
          <?php if (\App\Core\ModuleManager::isActive('oneservice_cases')): ?>
          <a href="<?= $baseUrl ?>/oneservice/daftar"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-magnifying-glass-chart text-sky-400 w-4"></i>
            <span>Daftar &amp; Detail Perkara</span>
          </a>
          <?php endif; ?>
        <?php endif; ?>

        <!-- Management Posko (pelaporan berjenjang) -->
        <?php if (\App\Core\Auth::hasRole(array_merge(\App\Config\Constants::POSKO_ROLES, [\App\Config\Constants::ROLE_SUPERADMIN]))): ?>
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 py-1 mt-4">MANAGEMENT POSKO</div>
          <a href="<?= $baseUrl ?>/posko"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-sitemap text-amber-400 w-4"></i>
            <span>Beranda Posko</span>
          </a>
          <a href="<?= $baseUrl ?>/posko-laporan"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-file-lines text-amber-400 w-4"></i>
            <span>Laporan Posko</span>
          </a>
          <a href="<?= $baseUrl ?>/posko-kepatuhan"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-table-cells text-amber-400 w-4"></i>
            <span>Kepatuhan Pelaporan</span>
          </a>
          <?php if (\App\Core\PoskoAccess::isOperational() && (int)\App\Core\PoskoAccess::level() >= \App\Config\Constants::LEVEL_POLRES): ?>
            <a href="<?= $baseUrl ?>/posko-rekap/create"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
              <i class="fas fa-layer-group text-amber-400 w-4"></i>
              <span>Susun Rekap</span>
            </a>
          <?php endif; ?>
          <?php if (\App\Core\PoskoAccess::isOperational()): ?>
            <?php $poskoQueue = count((new \App\Models\PoskoReport())->inbox((int)\App\Core\Auth::satkerId())); ?>
            <a href="<?= $baseUrl ?>/posko-laporan/inbox"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
              <i class="fas fa-inbox text-amber-400 w-4"></i>
              <span class="flex-1">Antrean Verifikasi</span>
              <?php if ($poskoQueue > 0): ?>
                <span class="px-1.5 min-w-[1.25rem] text-center rounded-full bg-red-600 text-white text-[10px] leading-5"><?= $poskoQueue ?></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>
          <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_POSKO_ADMIN, \App\Config\Constants::ROLE_SUPERADMIN])): ?>
            <a href="<?= $baseUrl ?>/posko/users"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
              <i class="fas fa-users-gear text-amber-400 w-4"></i>
              <span>Pengguna Posko</span>
            </a>
          <?php endif; ?>
          <?php if (\App\Core\PoskoAccess::canManageOrganization()): ?>
            <a href="<?= $baseUrl ?>/posko/organizations"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
              <i class="fas fa-building-shield text-amber-400 w-4"></i>
              <span>Organisasi Posko</span>
            </a>
            <a href="<?= $baseUrl ?>/posko-jenis"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
              <i class="fas fa-list-check text-amber-400 w-4"></i>
              <span>Jenis Laporan</span>
            </a>
          <?php endif; ?>
          <?php if (\App\Core\PoskoAccess::isPoskoUser()): ?>
            <a href="<?= $baseUrl ?>/posko/password"
               class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
              <i class="fas fa-key text-amber-400 w-4"></i>
              <span>Ganti Kata Sandi</span>
            </a>
          <?php endif; ?>
        <?php endif; ?>

        <!-- Profil Pejabat: nama, pangkat & jabatan (identitas login tetap) -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SUPERADMIN])): ?>
          <a href="<?= $baseUrl ?>/pejabat"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-id-badge text-amber-400 w-4"></i>
            <span>Profil Pejabat</span>
          </a>
        <?php endif; ?>

        <!-- Jejak Audit -->
        <?php if (\App\Core\Auth::hasRole([\App\Config\Constants::ROLE_SUPERADMIN, \App\Config\Constants::ROLE_INTERNAL_AFFAIRS])): ?>
          <a href="<?= $baseUrl ?>/audit"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-slate-100 hover:bg-[#1B1610] transition-colors">
            <i class="fas fa-clipboard-list text-slate-300 w-4"></i>
            <span>Jejak Audit</span>
          </a>
        <?php endif; ?>

        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 py-1 mt-4">AKSES CEPAT</div>
        
        <a href="<?= $baseUrl ?>/report/track" target="_blank"
           class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-[#1B1610] transition-colors">
          <i class="fas fa-magnifying-glass text-slate-400 w-4"></i>
          <span>Portal Pelacakan Warga</span>
        </a>

        <?php if (\App\Config\Config::isDevelopment()): ?>
        <a href="<?= $baseUrl ?>/auth/login"
           class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-amber-400 hover:bg-[#1B1610] transition-colors">
          <i class="fas fa-arrows-rotate text-amber-500 w-4"></i>
          <span>Ganti Role Pengujian</span>
        </a>
        <?php endif; ?>

      </div>
    </div>

    <!-- Sidebar Bottom: Logout -->
    <div class="p-4 border-t border-[#3B2F1C]/80">
      <a href="<?= $baseUrl ?>/auth/logout" 
         class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-red-950/40 hover:bg-red-900/60 border border-red-800/40 text-red-300 text-xs font-bold transition-all">
        <i class="fas fa-right-from-bracket"></i>
        <span>Keluar Sistem Dinas</span>
      </a>
    </div>

  </aside>

  <!-- Right Content Area -->
  <div class="flex-1 flex flex-col min-w-0">
    
    <!-- Top Bar -->
    <header class="h-16 bg-[#14100C]/90 border-b border-[#3B2F1C] px-4 sm:px-8 flex items-center justify-between sticky top-0 z-40 backdrop-blur-md">
      
      <!-- Left Page Title -->
      <div class="flex items-center gap-3">
        <button type="button" id="sidebarToggle" class="md:hidden text-slate-300 p-2" onclick="toggleSidebar()"
                aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu navigasi">
          <i class="fas fa-bars text-lg"></i>
        </button>
        <div>
          <h1 class="text-sm sm:text-base font-extrabold text-slate-100 font-heading">
            <?= htmlspecialchars($pageTitle ?? 'Dashboard Operasional') ?>
          </h1>
          <p class="text-[10px] text-slate-400 hidden sm:block">Sentra Pelayanan Kepolisian Terpadu (SPKT) Polri</p>
        </div>
      </div>

      <!-- Right Quick Tools -->
      <div class="flex items-center gap-3">
        <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-950/80 text-emerald-400 border border-emerald-800 text-[10px] font-bold">
          <i class="fas fa-circle text-[8px] animate-pulse"></i> LIVE SERVER
        </span>

        <?php if (\App\Core\PoskoAccess::isPoskoUser()): ?>
          <?php $poskoUnread = (new \App\Models\PoskoNotification())->unreadCount((int)\App\Core\Auth::id()); ?>
          <a href="<?= $baseUrl ?>/posko-notifikasi" id="poskoBell" class="relative text-slate-300 hover:text-amber-300 p-2 rounded-lg border border-[#3B2F1C] bg-[#1B1610]"
             aria-label="Notifikasi posko" data-summary="<?= $baseUrl ?>/posko-notifikasi/summary">
            <i class="fas fa-bell"></i>
            <span id="poskoBellCount" class="absolute -top-1.5 -right-1.5 px-1.5 min-w-[1.1rem] text-center rounded-full bg-red-600 text-white text-[10px] leading-4 <?= $poskoUnread > 0 ? '' : 'hidden' ?>"><?= $poskoUnread > 99 ? '99+' : $poskoUnread ?></span>
          </a>
        <?php endif; ?>

        <a href="<?= $baseUrl ?>/auth/logout" class="text-xs text-rose-400 hover:text-rose-300 font-bold px-3 py-1.5 rounded-lg bg-rose-950/40 border border-rose-800/40 flex items-center gap-1.5 transition-colors">
          <i class="fas fa-power-off"></i> <span class="hidden sm:inline">Logout</span>
        </a>
      </div>

    </header>

    <!-- Flash Notifications -->
    <?php if (\App\Core\Session::hasFlash('success')): ?>
      <div class="p-4 mx-4 sm:mx-8 mt-4 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-xs flex items-center gap-2 shadow-lg">
        <i class="fas fa-circle-check text-emerald-400"></i>
        <span><?= htmlspecialchars(\App\Core\Session::getFlash('success')[0]) ?></span>
      </div>
    <?php endif; ?>

    <?php if (\App\Core\Session::hasFlash('error')): ?>
      <div class="p-4 mx-4 sm:mx-8 mt-4 rounded-xl bg-red-950/80 border border-red-800 text-red-300 text-xs flex items-center gap-2 shadow-lg">
        <i class="fas fa-circle-exclamation text-red-400"></i>
        <span><?= htmlspecialchars(\App\Core\Session::getFlash('error')[0]) ?></span>
      </div>
    <?php endif; ?>

    <!-- Main Container -->
    <main class="hud-grid relative isolate flex-1 p-4 sm:p-8">
      <?= $content ?>
    </main>

  </div>

  <script>
    function toggleSidebar(open) {
      const sidebar = document.getElementById('sidebar');
      const show = open ?? sidebar.classList.contains('-translate-x-full');
      sidebar.classList.toggle('-translate-x-full', !show);
      document.getElementById('sidebarBackdrop').classList.toggle('hidden', !show);
      document.getElementById('sidebarToggle').setAttribute('aria-expanded', String(show));
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') toggleSidebar(false); });

    // Lonceng notifikasi posko: polling ringan; header X-SIGAP-Poll agar tidak dihitung sebagai aktivitas (batas idle tetap berlaku)
    (function () {
      const bell = document.getElementById('poskoBell');
      if (!bell) return;
      const badge = document.getElementById('poskoBellCount');
      const refresh = () => fetch(bell.dataset.summary, { headers: { 'X-SIGAP-Poll': '1', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => r.ok ? r.json() : null)
        .then(d => {
          if (!d) return;
          badge.textContent = d.unread > 99 ? '99+' : d.unread;
          badge.classList.toggle('hidden', d.unread < 1);
        }).catch(() => {});
      setInterval(() => { if (!document.hidden) refresh(); }, 60000);
    })();
  </script>

</body>
</html>
