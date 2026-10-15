<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>SP2HP Digital - <?= htmlspecialchars($report['ticket_number']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      body { background: white !important; color: black !important; }
      .no-print { display: none !important; }
      .print-shadow { box-shadow: none !important; border: none !important; }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-8 font-serif">

  <!-- Floating Print Controls (Hidden when printed) -->
  <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print bg-[#0A192F] text-slate-100 p-4 rounded-xl shadow-lg border border-amber-500/30">
    <div class="flex items-center gap-2">
      <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
      <span class="text-xs font-sans font-bold">DOKUMEN RESMI SP2HP-ELEKTRONIK (KEPOLISIAN DAERAH)</span>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="window.print()" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-sans font-bold rounded-lg text-xs transition-colors flex items-center gap-1.5 shadow">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        Cetak / Simpan PDF
      </button>
      <button onclick="window.close()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-sans font-semibold rounded-lg text-xs">
        Tutup
      </button>
    </div>
  </div>

  <!-- Document Paper (A4 Style) -->
  <div class="max-w-3xl mx-auto bg-white p-8 sm:p-12 shadow-2xl rounded border border-slate-300 print-shadow relative">
    
    <?php
      $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      $tanggal = fn(string $dt): string => date('j', strtotime($dt)) . ' ' . $bulan[(int)date('n', strtotime($dt))] . ' ' . date('Y', strtotime($dt));
    ?>
    <!-- Watermark Background -->
    <div class="absolute inset-0 flex items-center justify-center opacity-[0.04] pointer-events-none">
      <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat.png" alt="Polisi Bersama Masyarakat" class="w-96 h-auto">
    </div>

    <!-- Official Kop Surat Polri -->
    <div class="border-b-4 border-double border-slate-900 pb-3 mb-6 text-center relative">
      <div class="flex items-center justify-center gap-4 mb-2">
        <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat.png" alt="Polisi Bersama Masyarakat" class="w-20 h-auto object-contain">
        <div>
          <h2 class="text-sm font-black tracking-widest uppercase text-slate-900">KEPOLISIAN NEGARA REPUBLIK INDONESIA</h2>
          <h1 class="text-base font-black tracking-wider uppercase text-slate-900"><?= htmlspecialchars($report['satker_name'] ?? 'KEPOLISIAN DAERAH') ?></h1>
          <p class="text-[11px] font-sans text-slate-600">Sentra Pelayanan Kepolisian Terpadu (SPKT) • Presisi Melindungi, Mengayomi & Melayani</p>
        </div>
      </div>
    </div>

    <!-- Header Dokumen SP2HP -->
    <div class="text-center mb-6">
      <h3 class="text-sm font-black underline uppercase tracking-wider">SURAT PEMBERITAHUAN PERKEMBANGAN HASIL PENYELIDIKAN</h3>
      <p class="text-xs font-mono font-bold mt-1">Nomor: <?= htmlspecialchars($sp2hp['sp2hp_number']) ?></p>
    </div>

    <!-- Letter Meta -->
    <div class="text-xs space-y-1 mb-6 font-sans">
      <div class="grid grid-cols-12">
        <div class="col-span-2 font-bold">Klasifikasi</div>
        <div class="col-span-10">: BIASA</div>
      </div>
      <div class="grid grid-cols-12">
        <div class="col-span-2 font-bold">Lampiran</div>
        <div class="col-span-10">: -</div>
      </div>
      <div class="grid grid-cols-12">
        <div class="col-span-2 font-bold">Perihal</div>
        <div class="col-span-10 font-bold">: Pemberitahuan Perkembangan Hasil Penyelidikan Perkara (SP2HP-E)</div>
      </div>
    </div>

    <!-- Penerima -->
    <div class="text-xs font-sans mb-6">
      <p class="font-bold">Kepada Yth.</p>
      <p>Sdr/i. <b><?= htmlspecialchars($report['reporter_name_raw']) ?></b></p>
      <p>di Tempat</p>
    </div>

    <!-- Isi Dokumen -->
    <div class="text-xs font-sans leading-relaxed space-y-4 text-justify">
      <p>
        1. Rujukan:
      </p>
      <div class="pl-4 space-y-1">
        <p>a. Undang-Undang Nomor 2 Tahun 2002 tentang Kepolisian Negara Republik Indonesia;</p>
        <p>b. Undang-Undang Nomor 8 Tahun 1981 tentang Kitab Undang-Undang Hukum Acara Pidana (KUHAP);</p>
        <p>c. Laporan Pengaduan Masyarakat dengan Nomor Registrasi: <b><?= htmlspecialchars($report['ticket_number']) ?></b> tanggal <?= $tanggal($report['created_at']) ?> tentang dugaan tindak pidana <i>"<?= htmlspecialchars($report['title']) ?>"</i> yang terjadi pada tanggal <?= $tanggal($report['incident_date']) ?>.</p>
      </div>

      <p>
        2. Sehubungan dengan rujukan tersebut di atas, bersama ini kami beritahukan bahwa laporan pengaduan Saudara/i telah ditangani oleh tim penyidik <b><?= htmlspecialchars($report['satker_name'] ?? 'Satker Terkait') ?></b> dengan rincian tahapan sebagai berikut:
      </p>

      <!-- Timeline Perkembangan -->
      <div class="border border-slate-300 rounded p-4 bg-slate-50 space-y-3">
        <?php foreach ($timeline as $idx => $t): ?>
          <div class="border-b border-slate-200 last:border-b-0 pb-2">
            <div class="flex justify-between font-bold text-[11px] text-slate-800">
              <span><?= ($idx + 1) ?>. <?= htmlspecialchars($t['title']) ?></span>
              <span class="font-mono text-slate-500"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?> WIB</span>
            </div>
            <p class="text-slate-700 mt-1 pl-3 text-[11px]"><?= htmlspecialchars($t['public_notes']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <p>
        3. Penyidik akan terus melakukan langkah-langkah penyelidikan secara profesional, transparan, dan akuntabel sesuai ketentuan perundang-undangan yang berlaku. Apabila Saudara/i memiliki keterangan tambahan atau informasi baru, dapat menghubungi langsung Penyidik Pelaksana:
      </p>
      <div class="pl-4 text-[11px] font-sans">
        <p>Nama Penyidik : <b><?= htmlspecialchars($report['investigator_name']) ?></b></p>
        <p>Pangkat       : <?= htmlspecialchars($report['investigator_rank'] ?? '-') ?></p>
        <p>Satuan Kerja  : <?= htmlspecialchars($report['satker_name'] ?? 'Polda') ?></p>
      </div>

      <p>
        4. Demikian surat pemberitahuan ini disampaikan untuk menjadi maklum atas perhatian dan kerjasamanya diucapkan terima kasih.
      </p>
    </div>

    <!-- Tanda Tangan & Keterangan Dokumen -->
    <div class="mt-8 pt-4 border-t border-slate-300 font-sans grid grid-cols-2 text-xs items-end">

      <!-- Keterangan Dokumen Elektronik -->
      <div class="text-[9px] text-slate-500 pr-4">
        Dokumen ini dicetak dari sistem SIGAP-POLDA. Perkembangan perkara dapat dicocokkan pada portal pelacakan
        dengan Nomor Tiket <b class="font-mono"><?= htmlspecialchars($report['ticket_number']) ?></b> dan PIN Keamanan pelapor.
      </div>

      <!-- TTD Petugas -->
      <div class="text-center space-y-1">
        <p><?= $tanggal($sp2hp['created_at']) ?></p>
        <p class="font-bold uppercase">a.n. KEPALA SATUAN KERJA PENYIDIK</p>
        <p class="text-[10px] text-slate-500">Penyidik Pembantu Pelaksana,</p>
        <div class="h-12"></div>
        <p class="font-bold underline uppercase"><?= htmlspecialchars($report['investigator_name']) ?></p>
        <p class="text-[10px] text-slate-600"><?= htmlspecialchars($report['investigator_rank'] ?? '') ?></p>
      </div>

    </div>

  </div>

</body>
</html>
