<?php
/**
 * View: Modul 7 Peta Kerawanan & Catatan Solusi Bhabinkamtibmas
 * Reorientasi Peran Bhabin: Problem Solving Nyata & Restorative Justice vs Seremonial
 */
?>
<div class="space-y-8">
  <!-- Header Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold tracking-wider uppercase mb-2">
        <i class="fas fa-handshake"></i> MODUL 7: REORIENTASI PERAN BHABINKAMTIBMAS
      </div>
      <h1 class="text-2xl md:text-3xl font-heading font-black text-slate-100 tracking-tight">
        Catatan Problem Solving &amp; Peta Kerawanan Presisi
      </h1>
      <p class="text-xs md:text-sm text-slate-400 font-light mt-1">
        Mengganti pelaporan semu berbasis foto seremonial/sambang menjadi catatan terukur <strong class="text-amber-400 font-semibold">Penyelesaian Damai (Restorative Justice)</strong> di tingkat desa.
      </p>
    </div>
    
    <div>
      <a href="<?= $baseUrl ?>/oneservice" class="px-4 py-2.5 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C] transition-all inline-flex items-center gap-1.5">
        <i class="fas fa-arrow-left text-amber-500"></i> Hub Terpadu
      </a>
    </div>
  </div>

  <!-- Notifikasi / Hasil Input -->
  <?php if (!empty($message)): ?>
    <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-check text-emerald-400 text-base"></i>
      <div>
        <p class="font-bold">Problem Solving Berhasil Tercatat</p>
        <p class="text-emerald-200"><?= htmlspecialchars($message) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-exclamation text-red-400 text-base"></i>
      <div>
        <p class="font-bold">Laporan Ditolak Sistem</p>
        <p class="text-red-200"><?= htmlspecialchars($error) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Kolom Kiri: Formulir Pencatatan Problem Solving Bhabin -->
    <div class="lg:col-span-5 polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-2xl space-y-5">
      <div class="border-b border-[#3B2F1C] pb-3">
        <h2 class="text-base font-heading font-black text-slate-100 flex items-center gap-2">
          <i class="fas fa-scale-unbalanced-flip text-amber-400"></i> Catat Penyelesaian Konflik Warga
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">Sistem memvalidasi mufakat damai nyata, bukan foto seremonial.</p>
      </div>

      <form action="<?= $baseUrl ?>/oneservice/bhabin" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Desa / Kelurahan Binaan</label>
          <input type="text" name="village_name" required placeholder="Contoh: Desa Sukamaju, Kec. Cilincing"
                 class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Kategori Masalah / Konflik</label>
          <select name="conflict_category" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
            <option value="Sengketa Batas Tanah Warga">Sengketa Batas Tanah / Pekarangan</option>
            <option value="Perselisihan Bertetangga / Keributan">Perselisihan Bertetangga / Keributan Warga</option>
            <option value="Utang Piutang / Wanprestasi Ringan">Utang Piutang / Tipiring</option>
            <option value="Kenakalan Remaja / Mediasi Sekolah">Kenakalan Remaja / Tawuran Pelajar</option>
            <option value="Pencurian Ringan (Ganti Rugi)">Pencurian Ringan / Tipiring (Restorative Justice)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Pihak-Pihak yang Terlibat</label>
          <input type="text" name="parties_involved" required placeholder="Bpk. Ahmad (Pihak 1) dan Ibu Siti (Pihak 2)"
                 class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">No. Kontak Perwakilan Warga</label>
          <input type="text" name="citizen_phone" required placeholder="08xxxxxxxxxx (Untuk survey kepuasan masyarakat)"
                 class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Hasil Mediasi &amp; Butir Kesepakatan Damai</label>
          <textarea name="mediation_outcome" required rows="4"
                    placeholder="Uraikan secara konkret poin perdamaian, komitmen ganti rugi jika ada, serta saksi RT/RW setempat..."
                    class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs leading-relaxed"></textarea>
          <p class="text-[11px] text-amber-400 mt-1 font-semibold">
            <i class="fas fa-triangle-exclamation mr-1"></i> Wajib minimal 20 karakter berisi substansi perdamaian nyata.
          </p>
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-xl btn-gold text-slate-950 font-black text-xs shadow-lg flex items-center justify-center gap-2">
          <i class="fas fa-check-circle"></i>
          <span>Simpan Catatan Restorative Justice</span>
        </button>
      </form>
    </div>

    <!-- Kolom Kanan: Daftar Penyelesaian Damai Terverifikasi -->
    <div class="lg:col-span-7 space-y-6">
      <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl shadow-2xl overflow-hidden space-y-4">
        <div class="ribbon-merah-putih"></div>
        <div class="p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
            <h2 class="text-base font-heading font-black text-slate-100 flex items-center gap-2">
              <i class="fas fa-dove text-amber-400"></i> Mediasi Damai Bhabinkamtibmas Terkini
            </h2>
            <span class="text-xs text-slate-400">Outcome-based Police</span>
          </div>

          <div class="space-y-4">
            <?php if (empty($resolutions)): ?>
              <p class="text-xs text-slate-500 py-6 text-center italic">
                Belum ada mediasi damai yang tercatat.
              </p>
            <?php else: ?>
              <?php foreach ($resolutions as $r): ?>
                <div class="p-4 rounded-xl bg-[#1B1610] border border-[#3B2F1C] space-y-2">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                    <div class="flex items-center gap-2">
                      <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold text-[10px] border border-emerald-500/30">
                        DAMAI (RJ)
                      </span>
                      <span class="font-bold text-slate-100"><?= htmlspecialchars($r['conflict_category']) ?></span>
                    </div>
                    <span class="text-slate-400 font-mono text-[11px]">
                      <?= date('d M Y, H:i', strtotime($r['recorded_at'])) ?>
                    </span>
                  </div>

                  <div class="text-xs text-slate-300">
                    <span class="text-slate-400">Lokasi:</span> <strong class="text-slate-100"><?= htmlspecialchars($r['village_name']) ?></strong> (<?= htmlspecialchars($r['satker_name'] ?? 'Polres') ?>)
                  </div>

                  <div class="text-xs text-slate-300">
                    <span class="text-slate-400">Pihak Berdamai:</span> <?= htmlspecialchars($r['parties_involved']) ?>
                  </div>

                  <div class="text-xs text-slate-200 bg-[#0C0A08] p-2.5 rounded-lg border border-[#3B2F1C] leading-relaxed">
                    <?= nl2br(htmlspecialchars($r['mediation_outcome'])) ?>
                  </div>

                  <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                    <span>Petugas Bhabin: <?= htmlspecialchars($r['bhabin_name'] ?? $r['bhabin_nrp']) ?></span>
                    <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($r['tracking_number']) ?>" class="text-amber-400 font-mono hover:underline">
                      <?= htmlspecialchars($r['tracking_number']) ?>
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
