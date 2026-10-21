<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 - Modul Dinonaktifkan | SIGAP POLDA</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/custom-polri.css">
</head>
<body class="bg-[#0C0A08] text-slate-200 min-h-screen flex items-center justify-center p-4 antialiased">
  <div class="max-w-md w-full polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl shadow-2xl overflow-hidden relative text-center">
    <div class="ribbon-merah-putih"></div>
    <div class="p-8 space-y-6">
      <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-3xl shadow-lg shadow-amber-500/10">
        <i class="fas fa-lock"></i>
      </div>

      <div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-bold uppercase tracking-wider mb-2">
          <i class="fas fa-shield-halved"></i> Akses Modul Ditangguhkan
        </div>
        <h1 class="text-2xl font-heading font-black text-slate-100 tracking-tight">
          <?= htmlspecialchars($moduleName ?? 'Modul Sistem') ?>
        </h1>
        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
          <?= htmlspecialchars($msg ?? 'Modul atau fitur ini sedang dinonaktifkan sementara oleh Administrator / Divisi IT untuk pemeliharaan sistem.') ?>
        </p>
      </div>

      <div class="p-4 rounded-xl bg-[#1B1610] border border-[#3B2F1C] text-left text-xs space-y-2">
        <div class="flex items-center justify-between text-slate-400">
          <span>Status Kebijakan:</span>
          <span class="text-amber-400 font-bold font-mono">NONAKTIF (DISABLED)</span>
        </div>
        <div class="flex items-center justify-between text-slate-400">
          <span>Otoritas Pengendali:</span>
          <span class="text-slate-200 font-semibold">Superadmin / IT Polda</span>
        </div>
      </div>

      <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="javascript:history.back()" 
           class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 font-bold text-xs border border-[#3B2F1C] transition-all flex items-center justify-center gap-2">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <a href="<?= $baseUrl ?>/spkt" 
           class="w-full sm:w-auto px-5 py-2.5 rounded-xl btn-gold text-slate-950 font-black text-xs transition-all flex items-center justify-center gap-2 shadow-lg">
          <i class="fas fa-house"></i> Beranda Operasional
        </a>
      </div>
    </div>
  </div>
</body>
</html>
