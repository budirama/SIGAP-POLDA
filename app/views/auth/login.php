<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Login Backoffice') ?> - SIGAP POLRI</title>
  <?php require __DIR__ . "/../layouts/_head.php"; ?>
</head>
<body class="bg-[#0C0A08] min-h-screen text-slate-200 flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">

  <!-- Sang Saka Merah Putih Ribbon Accent Line -->
  <div class="ribbon-merah-putih"></div>

  <!-- Top Simple Navigation -->
  <header class="border-b border-[#3B2F1C] bg-[#14100C]/80 backdrop-blur-md px-6 py-2.5 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="h-12 w-auto object-contain drop-shadow">
      <div>
        <div class="font-extrabold text-sm tracking-wider text-slate-100 flex items-center gap-2">
          <span>SIGAP-POLRI</span>
          <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-red-950 text-red-300 border border-red-700/60 uppercase">POLISI BERSAMA MASYARAKAT</span>
        </div>
        <p class="text-[10px] text-amber-400 font-semibold">MELINDUNGI • MENGAYOMI • MELAYANI</p>
      </div>
    </div>
    <div>
      <a href="<?= $baseUrl ?>/" class="text-xs text-amber-400 hover:text-amber-300 font-medium flex items-center gap-1.5 transition-colors">
        <i class="fas fa-arrow-left"></i> Kembali ke Portal Warga
      </a>
    </div>
  </header>

  <!-- Main Login Section -->
  <main class="hud-grid relative isolate overflow-hidden flex-1 flex items-center justify-center p-4 py-12">
    <canvas class="fx-network" aria-hidden="true"></canvas>
    <div class="w-full max-w-4xl grid md:grid-cols-12 gap-8 items-center">
      
      <!-- Left Column: Branding & Police Values -->
      <div class="md:col-span-6 space-y-6 text-center md:text-left">
        <div class="emblem-3d md:mx-0" style="width: 11rem;">
          <div class="emblem-3d__stage">
            <div class="emblem-3d__glow"></div>
            <div class="emblem-3d__ring emblem-3d__ring--1"></div>
            <div class="emblem-3d__ring emblem-3d__ring--2"></div>
            <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="emblem-3d__logo">
          </div>
        </div>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-950/80 border border-blue-800/60 text-blue-300 text-xs font-semibold">
          <i class="fas fa-shield-halved text-amber-400"></i> Portal Khusus Personil POLRI
        </div>
        <h1 class="text-3xl md:text-4xl font-black text-slate-100 leading-tight">
          Integritas & Kecepatan Respons <span class="text-gold-gradient">Polri Presisi</span>
        </h1>
        <p class="text-slate-400 text-sm leading-relaxed">
          Sistem komando dan kendali terpadu untuk monitoring, verifikasi berkas, disposisi satuan kerja, hingga pelaporan SP2HP Elektronik langsung ke masyarakat.
        </p>

        <!-- Quick Credentials Switcher (Developer & Review Mode) -->
        <?php if (\App\Config\Config::isDevelopment()): ?>
        <div class="bg-[#1B1610]/90 border border-amber-500/25 rounded-2xl p-4 text-left shadow-lg">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-3 border-b border-slate-700/60 pb-2">
            <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
              <i class="fas fa-bolt text-amber-400"></i> Login 1-Klik · Management Posko
            </span>
            <span class="text-[10px] bg-amber-500/10 text-amber-400 px-2 py-0.5 rounded border border-amber-500/20">Semua Password: password123</span>
          </div>
          <?php
          // Rantai komando posko dari Pusat sampai Polsek (akun demo seeder)
          $poskoChain = [
              ['Pusat', 'Mabes Polri', 'fa-landmark', [
                  ['poskoExecPusat', 'Executive'], ['poskoAdminPusat', 'Admin'], ['poskoOpPusat', 'Operator'],
              ]],
              ['Polda', 'Polda Jawa Timur', 'fa-building-shield', [
                  ['poskoExecJatim', 'Executive'], ['poskoAdminJatim', 'Admin'], ['poskoOpJatim', 'Operator'],
              ]],
              ['Polres', 'Polrestabes Surabaya', 'fa-building', [
                  ['poskoExecSby', 'Executive'], ['poskoAdminSby', 'Admin'], ['poskoOpSby', 'Operator'],
              ]],
              ['Polsek', 'Polsek Benowo · Gubeng', 'fa-house-flag', [
                  ['poskoOpBenowo', 'Op. Benowo'], ['poskoOpGubeng', 'Op. Gubeng'],
              ]],
          ];
          ?>
          <ol class="space-y-2 text-[11px]">
            <?php foreach ($poskoChain as [$level, $unit, $icon, $accounts]): ?>
            <li class="rounded-lg border border-amber-800/30 bg-amber-950/20 p-2">
              <div class="flex items-center gap-1.5 mb-1.5 text-amber-200">
                <i class="fas <?= $icon ?> text-amber-400 w-4 text-center" aria-hidden="true"></i>
                <span class="font-bold"><?= $level ?></span>
                <span class="text-slate-400 truncate">· <?= $unit ?></span>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <?php foreach ($accounts as [$key, $label]): ?>
                <a href="<?= $baseUrl ?>/auth/quickLogin/<?= $key ?>" class="px-2.5 py-1.5 bg-[#0C0A08] hover:bg-amber-900/50 border border-amber-700/40 rounded-md text-amber-100 font-semibold transition-all"><?= $label ?></a>
                <?php endforeach; ?>
              </div>
            </li>
            <?php endforeach; ?>
          </ol>

          <details class="mt-3 text-[11px]">
            <summary class="cursor-pointer text-slate-400 hover:text-slate-200 font-semibold">Role SIGAP lainnya (aduan warga)</summary>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
            <a href="<?= $baseUrl ?>/auth/quickLogin/kapolri" class="p-2 bg-red-950/40 hover:bg-red-900/60 border border-red-800/40 rounded-lg text-red-200 font-semibold transition-all flex items-center gap-1.5">
              <i class="fas fa-star text-amber-400"></i> Kapolri
            </a>
            <a href="<?= $baseUrl ?>/auth/quickLogin/spkt" class="p-2 bg-blue-950/40 hover:bg-blue-900/60 border border-blue-800/40 rounded-lg text-blue-200 font-semibold transition-all flex items-center gap-1.5">
              <i class="fas fa-desktop text-blue-400"></i> Ka SPKT
            </a>
            <a href="<?= $baseUrl ?>/auth/quickLogin/humas" class="p-2 bg-sky-950/40 hover:bg-sky-900/60 border border-sky-800/40 rounded-lg text-sky-200 font-semibold transition-all flex items-center gap-1.5">
              <i class="fab fa-x-twitter text-sky-400"></i> Humas Medsos
            </a>
            <a href="<?= $baseUrl ?>/auth/quickLogin/kasatker" class="p-2 bg-indigo-950/40 hover:bg-indigo-900/60 border border-indigo-800/40 rounded-lg text-indigo-200 font-semibold transition-all flex items-center gap-1.5">
              <i class="fas fa-user-shield text-indigo-400"></i> Dir Krimum
            </a>
            <a href="<?= $baseUrl ?>/auth/quickLogin/kanit" class="p-2 bg-cyan-950/40 hover:bg-cyan-900/60 border border-cyan-800/40 rounded-lg text-cyan-200 font-semibold transition-all flex items-center gap-1.5">
              <i class="fas fa-sitemap text-cyan-400"></i> Kanit Jatanras
            </a>
            <a href="<?= $baseUrl ?>/auth/quickLogin/penyidik" class="p-2 bg-yellow-950/40 hover:bg-yellow-900/60 border border-yellow-800/40 rounded-lg text-yellow-200 font-semibold transition-all flex items-center gap-1.5">
              <i class="fas fa-user-edit text-yellow-400"></i> Penyidik Rian
            </a>
          </div>
          </details>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right Column: Formal Login Form -->
      <div class="md:col-span-6">
        <div class="polri-glass-card hud-corners scanline rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
          
          <!-- Top Accent Light -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-700 via-amber-400 to-red-700"></div>

          <div class="text-center mb-6">
            <div class="w-20 h-20 mx-auto mb-3 p-2 bg-[#0C0A08] rounded-2xl border border-amber-500/30 flex items-center justify-center shadow-inner">
              <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat-transparent.png" alt="Polisi Bersama Masyarakat" class="h-16 w-auto object-contain drop-shadow">
            </div>
            <h2 class="text-xl font-bold text-slate-100">Autentikasi Personil POLRI</h2>
            <p class="text-xs text-amber-400 font-medium mt-1">Polisi Bersama Masyarakat • Melindungi, Mengayomi & Melayani</p>
          </div>

          <!-- Flash Notification -->
          <?php if (\App\Core\Session::hasFlash('error')): ?>
            <div class="mb-4 p-3 bg-red-950/70 border border-red-800/70 rounded-xl text-red-300 text-xs flex items-center gap-2">
              <i class="fas fa-circle-exclamation text-red-400 text-sm"></i>
              <span><?= htmlspecialchars(\App\Core\Session::getFlash('error')[0]) ?></span>
            </div>
          <?php endif; ?>

          <?php if (\App\Core\Session::hasFlash('success')): ?>
            <div class="mb-4 p-3 bg-emerald-950/70 border border-emerald-800/70 rounded-xl text-emerald-300 text-xs flex items-center gap-2">
              <i class="fas fa-circle-check text-emerald-400 text-sm"></i>
              <span><?= htmlspecialchars(\App\Core\Session::getFlash('success')[0]) ?></span>
            </div>
          <?php endif; ?>

          <form action="<?= $baseUrl ?>/auth/authenticate" method="POST" class="space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5" for="identifier">
                NRP / Email Kedinasan
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                  <i class="fas fa-id-badge text-sm"></i>
                </div>
                <input type="text" id="identifier" name="identifier" required placeholder="Contoh: 70050201 atau kapolri@sigap.polri.go.id"
                       class="w-full bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl pl-10 pr-3 py-2.5 text-sm text-slate-100 placeholder-slate-600 transition-all outline-none">
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-semibold text-slate-300" for="password">
                  Kata Sandi Dinas
                </label>
                <span class="text-[11px] text-amber-500/80">Demo: password123</span>
              </div>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                  <i class="fas fa-lock text-sm"></i>
                </div>
                <input type="password" id="password" name="password" required placeholder="••••••••"
                       class="w-full bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl pl-10 pr-3 py-2.5 text-sm text-slate-100 placeholder-slate-600 transition-all outline-none">
              </div>
            </div>

            <div class="pt-2">
              <button type="submit"
                      class="btn-gold w-full py-2.5 px-4 font-extrabold rounded-xl text-sm transition-all shadow-lg hover:shadow-amber-500/20 flex items-center justify-center gap-2">
                <i class="fas fa-right-to-bracket"></i>
                <span>Masuk Sistem Kedinasan</span>
              </button>
            </div>
          </form>

          <div class="mt-6 pt-4 border-t border-slate-800 text-center text-[11px] text-slate-500 flex items-center justify-center gap-2">
            <i class="fas fa-lock text-slate-600"></i>
            <span>Enkripsi TLS 1.3 • Audit Trail TIK Polri</span>
          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- Footer -->
  <footer class="border-t border-[#3B2F1C]/60 bg-[#0C0A08] py-4 text-center text-xs text-slate-500">
    <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
      <p>&copy; <?= date('Y') ?> Kepolisian Negara Republik Indonesia. Hak Cipta Dilindungi Undang-Undang.</p>
      <p class="text-amber-500/70 font-medium">Divisi TIK Polri • SIGAP V<?= \App\Config\Config::APP_VERSION ?></p>
    </div>
  </footer>

</body>
</html>
