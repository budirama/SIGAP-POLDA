<?php
/**
 * View: Modul 3 Penerimaan SPKT Ramah Korban (v1.3 Presisi)
 * Standar Zero-Rejection, Jalur PPA / Kelompok Rentan & Proxy Entry Petugas Loket
 */
?>
<div class="space-y-8 max-w-4xl mx-auto">
  <!-- Header Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold tracking-wider uppercase mb-2">
        <i class="fas fa-hand-holding-heart"></i> MODUL 3: PENERIMAAN SPKT RAMAH KORBAN
      </div>
      <h1 class="text-2xl md:text-3xl font-heading font-black text-slate-100 tracking-tight">
        Loket SPKT Presisi &amp; Jalur Khusus PPA
      </h1>
      <p class="text-xs md:text-sm text-slate-400 font-light mt-1">
        Penerimaan laporan tatap muka dengan prinsip <strong class="text-amber-400 font-semibold">Zero-Rejection</strong> dan fasilitas <strong class="text-emerald-400 font-semibold">Proxy Entry</strong> untuk warga rentan.
      </p>
    </div>
    
    <div>
      <a href="<?= $baseUrl ?>/oneservice" class="px-4 py-2.5 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C] transition-all inline-flex items-center gap-1.5">
        <i class="fas fa-arrow-left text-amber-500"></i> Hub Terpadu
      </a>
    </div>
  </div>

  <!-- Banner Zero-Rejection Standard -->
  <div class="polri-glass-card bg-[#14100C] border border-emerald-500/40 rounded-2xl p-6 shadow-xl relative overflow-hidden">
    <div class="ribbon-merah-putih"></div>
    <div class="relative z-10 flex items-start gap-4">
      <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-2xl flex-shrink-0">
        <i class="fas fa-shield-heart"></i>
      </div>
      <div>
        <h3 class="text-base font-heading font-black text-slate-100 mb-1">
          Standar Operasional: Dilarang Menolak Laporan Warga (Zero-Rejection)
        </h3>
        <p class="text-xs text-slate-300 leading-relaxed font-light">
          Setiap petugas jaga SPKT wajib menerima dan meregistrasi pengaduan warga. Apabila peristiwa hukum berada di luar yurisdiksi kewenangan atau ranah hukum perdata, sistem secara otomatis memfasilitasi <strong class="text-amber-400">Disposisi Rujukan Antar-Satker</strong> tanpa memulangkan warga dengan tangan kosong.
        </p>
      </div>
    </div>
  </div>

  <!-- Notifikasi Sukses / Tiket Baru Diterbitkan -->
  <?php if (!empty($ticketInfo)): ?>
    <div class="polri-glass-card bg-[#14100C] border-2 border-amber-500 rounded-2xl p-6 md:p-8 shadow-2xl space-y-5 animate-fade-in relative overflow-hidden">
      <div class="ribbon-merah-putih"></div>
      <div class="flex items-center gap-3 text-amber-400 border-b border-[#3B2F1C] pb-4">
        <i class="fas fa-circle-check text-2xl"></i>
        <div>
          <h2 class="text-lg font-heading font-black text-slate-100">Laporan Berhasil Diregistrasi di SPKT!</h2>
          <p class="text-xs text-slate-300">Nomor pelacakan global dan PIN keamanan telah diterbitkan secara atomik.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-[#1B1610] p-5 rounded-xl border border-[#3B2F1C]">
        <div>
          <span class="text-xs font-bold text-amber-500/80 uppercase tracking-widest block mb-1">Nomor Pelacakan Global</span>
          <div class="flex items-center gap-2">
            <span class="text-2xl font-black font-mono text-amber-400 tracking-wider">
              <?= htmlspecialchars($ticketInfo['tracking_number']) ?>
            </span>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($ticketInfo['tracking_number']) ?>'); alert('Nomor disalin!');"
                    class="p-1.5 rounded-lg bg-[#241D14] hover:bg-[#3B2F1C] text-slate-300 text-xs border border-[#3B2F1C]">
              <i class="fas fa-copy"></i>
            </button>
          </div>
          <p class="text-[11px] text-slate-400 mt-1">Berlaku permanen dari SPKT hingga putusan inkracht.</p>
        </div>

        <div>
          <span class="text-xs font-bold text-emerald-400/80 uppercase tracking-widest block mb-1">PIN Keamanan Pelapor</span>
          <span class="text-2xl font-black font-mono text-emerald-400 tracking-widest">
            <?= htmlspecialchars($ticketInfo['security_pin']) ?>
          </span>
          <p class="text-[11px] text-slate-400 mt-1">Telah dikirimkan via notifikasi SMS ke nomor ponsel pelapor.</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
        <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($ticketInfo['tracking_number']) ?>&pin=<?= urlencode($ticketInfo['security_pin']) ?>"
           class="px-5 py-2.5 rounded-xl btn-gold text-slate-950 font-black text-xs transition-all inline-flex items-center gap-2 shadow-lg">
          <i class="fas fa-search"></i> Buka Halaman Pelacak Kasus
        </a>
        <button onclick="window.print()" class="px-4 py-2.5 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 font-semibold text-xs border border-[#3B2F1C] transition-all inline-flex items-center gap-2">
          <i class="fas fa-print text-amber-500"></i> Cetak Tanda Bukti Lapor Sementara (STPL)
        </button>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-exclamation text-red-400 text-base"></i>
      <div>
        <p class="font-bold">Gagal Mendaftarkan Laporan</p>
        <p class="text-red-200"><?= htmlspecialchars($error) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Formulir Input SPKT Ramah Korban -->
  <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 md:p-8 shadow-2xl space-y-6">
    <div class="border-b border-[#3B2F1C] pb-4">
      <h2 class="text-lg font-heading font-black text-slate-100 flex items-center gap-2">
        <i class="fas fa-file-signature text-amber-400"></i> Formulir Registrasi Penerimaan Laporan (Proxy Entry)
      </h2>
      <p class="text-xs text-slate-400 mt-1">
        Petugas SPKT memasukkan data atas nama warga. Laporan langsung menerima Global Tracking ID secara otomatis.
      </p>
    </div>

    <form action="<?= $baseUrl ?>/oneservice/spkt" method="POST" class="space-y-6">
      <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

      <!-- Checkbox Jalur Khusus & Whistleblower -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-[#1B1610] border border-[#3B2F1C]">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="is_vulnerable_group" value="1"
                 class="mt-1 w-4 h-4 rounded text-amber-500 bg-[#0C0A08] border-[#3B2F1C] focus:ring-amber-500">
          <div>
            <span class="text-xs font-bold text-amber-400 block">Jalur Khusus PPA &amp; Kelompok Rentan</span>
            <span class="text-[11px] text-slate-400">Centang jika pelapor/korban adalah perempuan, anak, lansia, atau penyandang disabilitas.</span>
          </div>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="is_whistleblower" value="1"
                 class="mt-1 w-4 h-4 rounded text-emerald-500 bg-[#0C0A08] border-[#3B2F1C] focus:ring-emerald-500">
          <div>
            <span class="text-xs font-bold text-emerald-400 block">Perlindungan Whistleblower (Kerahasiaan)</span>
            <span class="text-[11px] text-slate-400">Identitas disamarkan dan dienkripsi AES-256 tingkat basis data.</span>
          </div>
        </label>
      </div>

      <!-- Section: Data Pelapor / Korban -->
      <div class="space-y-4">
        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5 border-b border-[#3B2F1C] pb-2">
          <i class="fas fa-id-card text-amber-400"></i> 1. Identitas Pelapor / Korban
        </h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Lengkap Sesuai KTP</label>
            <input type="text" name="citizen_name" required placeholder="Nama Pelapor"
                   class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nomor Induk Kependudukan (NIK)</label>
            <input type="text" name="citizen_nik" required maxlength="16" placeholder="16 Digit NIK"
                   class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nomor Ponsel (Untuk SMS Lacak)</label>
            <input type="text" name="citizen_phone" required placeholder="08xxxxxxxxxx"
                   class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
          </div>
        </div>
      </div>

      <!-- Section: Data Kejadian / Perkara -->
      <div class="space-y-4">
        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5 border-b border-[#3B2F1C] pb-2">
          <i class="fas fa-scale-balanced text-amber-400"></i> 2. Uraian Peristiwa &amp; Klasifikasi
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Klasifikasi Perkara</label>
            <select name="incident_category" class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
              <option value="TINDAK_PIDANA_UMUM">Tindak Pidana Umum (Pencurian, Penganiayaan)</option>
              <option value="KEKERASAN_PPA">Kekerasan Dalam Rumah Tangga / Perlindungan Anak</option>
              <option value="PENIPUAN_PENGGELAPAN">Penipuan &amp; Penggelapan</option>
              <option value="SENGKETA_WARGA">Sengketa Warga / Konflik Sosial</option>
              <option value="LAKA_LANTAS">Kecelakaan Lalu Lintas</option>
              <option value="LAINNYA">Lain-lain / Kehilangan Berkas</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Skala Prioritas</label>
            <select name="priority_level" class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
              <option value="P3_ROUTINE">P3 - Rutin (SLA 48 Jam)</option>
              <option value="P2_URGENT">P2 - Mendesak (SLA 24 Jam)</option>
              <option value="P1_EMERGENCY">P1 - Darurat PPA / Ancam Jiwa (SLA 6 Jam)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Satker Penangan</label>
            <select name="satker_id" class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
              <?php foreach ($satkers as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Tempat Kejadian Perkara (TKP) / Alamat</label>
          <input type="text" name="incident_address" required placeholder="Jl. Raya No. 12, RT 02/05..."
                 class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Uraian Singkat Kejadian</label>
          <textarea name="incident_summary" required rows="4"
                    placeholder="Jelaskan waktu kejadian, kronologi singkat, dan kerugian yang dialami pelapor..."
                    class="w-full px-3 py-2.5 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs"></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#3B2F1C]">
        <button type="reset" class="px-4 py-2.5 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C]">
          Reset Formulir
        </button>
        <button type="submit"
                class="px-6 py-2.5 rounded-xl btn-gold text-slate-950 font-black text-xs shadow-lg flex items-center gap-2">
          <i class="fas fa-check-to-slot"></i>
          <span>Registrasi Laporan &amp; Terbitkan Nomor Lacak</span>
        </button>
      </div>
    </form>
  </div>
</div>
