<?php
/**
 * View: Modul 6 Aduan Penipuan Daring & Blokir Cepat Rekening Bank
 * Integrasi CekRekening.id, OJK, dan Syndicate Clustering Anti-Fraud
 */
?>
<div class="space-y-8">
  <!-- Header Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 text-xs font-bold tracking-wider uppercase mb-2">
        <i class="fas fa-shield-virus"></i> MODUL 6: FAST-ACTION ANTI-FRAUD SIBER
      </div>
      <h1 class="text-2xl md:text-3xl font-heading font-black text-slate-100 tracking-tight">
        Aduan Penipuan Daring &amp; Blokir Rekening Bank
      </h1>
      <p class="text-xs md:text-sm text-slate-400 font-light mt-1">
        Koordinasi cepat dengan <strong class="text-amber-400 font-semibold">Otoritas Jasa Keuangan (OJK)</strong> dan konsorsium bank untuk pembekuan rekening penampung penipuan dalam hitungan jam.
      </p>
    </div>
    
    <div>
      <a href="<?= $baseUrl ?>/oneservice" class="px-4 py-2.5 rounded-xl bg-[#1B1610] hover:bg-[#241D14] text-slate-300 text-xs font-semibold border border-[#3B2F1C] transition-all inline-flex items-center gap-1.5">
        <i class="fas fa-arrow-left text-amber-500"></i> Hub Terpadu
      </a>
    </div>
  </div>

  <!-- Notifikasi / Hasil Pengaduan -->
  <?php if (!empty($message)): ?>
    <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-check text-emerald-400 text-base"></i>
      <div>
        <p class="font-bold">Aduan Siber Diterima</p>
        <p class="text-emerald-200"><?= htmlspecialchars($message) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-exclamation text-red-400 text-base"></i>
      <div>
        <p class="font-bold">Gagal Memproses Aduan</p>
        <p class="text-red-200"><?= htmlspecialchars($error) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Kluster Sindikat Terdeteksi (Syndicate Clustering) -->
  <?php if (!empty($clusters)): ?>
    <div class="polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-xl space-y-4 relative overflow-hidden">
      <div class="ribbon-merah-putih"></div>
      <div class="flex items-center gap-2 text-red-400">
        <i class="fas fa-network-wired text-xl animate-pulse"></i>
        <h3 class="text-base font-heading font-black text-slate-100">Deteksi Otomatis Kluster Rekening Sindikat</h3>
      </div>
      <p class="text-xs text-slate-300 font-light leading-relaxed">
        Algoritma mendeteksi rekening bank berikut telah digunakan berulang kali pada lebih dari 1 laporan korban di berbagai kota. Rekening ini otomatis ditandai prioritas tinggi penindakan Subdit Siber.
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
        <?php foreach ($clusters as $c): ?>
          <div class="p-4 rounded-xl bg-[#1B1610] border border-red-500/30 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-red-400 font-mono"><?= htmlspecialchars($c['bank_name']) ?></span>
              <span class="px-2 py-0.5 rounded-full bg-red-600/30 text-red-300 text-[10px] font-black">
                <?= (int)$c['report_count'] ?> Laporan Korban
              </span>
            </div>
            <div class="font-mono text-base font-black text-slate-100 tracking-wider">
              <?= htmlspecialchars($c['account_number']) ?>
            </div>
            <div class="text-xs text-slate-300 font-semibold truncate">
              a.n <?= htmlspecialchars($c['account_holder_name']) ?>
            </div>
            <div class="text-[11px] text-amber-400 font-mono font-bold pt-1 border-t border-[#3B2F1C]">
              Total Kerugian: Rp <?= number_format((float)$c['total_losses'], 0, ',', '.') ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Kolom Kiri: Formulir Lapor Cepat Penipuan Daring -->
    <div class="lg:col-span-5 polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-2xl space-y-5">
      <div class="border-b border-[#3B2F1C] pb-3">
        <h2 class="text-base font-heading font-black text-slate-100 flex items-center gap-2">
          <i class="fas fa-handcuffs text-amber-400"></i> Lapor Cepat Rekening Penipu
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">Permintaan darurat pembekuan rekening bank.</p>
      </div>

      <form action="<?= $baseUrl ?>/oneservice/cyber" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

        <div class="space-y-3">
          <h3 class="text-[11px] font-bold text-amber-500/80 uppercase tracking-wider">Data Rekening Pelaku / Penampung</h3>
          
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Bank / E-Wallet</label>
            <select name="bank_name" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
              <option value="BANK_BCA">BCA (Bank Central Asia)</option>
              <option value="BANK_BRI">BRI (Bank Rakyat Indonesia)</option>
              <option value="BANK_MANDIRI">Bank Mandiri</option>
              <option value="BANK_BNI">BNI (Bank Negara Indonesia)</option>
              <option value="BANK_BSI">BSI (Bank Syariah Indonesia)</option>
              <option value="BANK_JAGO">Bank Jago</option>
              <option value="SEABANK">SeaBank Indonesia</option>
              <option value="EWALLET_DANA">E-Wallet DANA</option>
              <option value="EWALLET_OVO">E-Wallet OVO</option>
              <option value="EWALLET_GOPAY">E-Wallet GoPay</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nomor Rekening / No. HP E-Wallet</label>
            <input type="text" name="account_number" required placeholder="Contoh: 1234567890"
                   class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono font-bold">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Pemilik Rekening Pelaku</label>
            <input type="text" name="account_holder_name" required placeholder="Nama tertera di mutasi..."
                   class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Modus Penipuan</label>
              <select name="fraud_scheme" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
                <option value="ONLINE_SHOP">Toko Online Fiktif</option>
                <option value="INVESTMENT_SCAM">Investasi Bodong</option>
                <option value="APK_PHISHING">Phishing / Undangan APK</option>
                <option value="IMPERSONATION">Catut Nama Pejabat / Teman</option>
                <option value="LOTTERY_SCAM">Undian Berhadiah Palsu</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nominal Kerugian (Rp)</label>
              <input type="number" name="loss_amount" required min="1000" placeholder="Rp"
                     class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
            </div>
          </div>
        </div>

        <div class="space-y-3 pt-2 border-t border-[#3B2F1C]">
          <h3 class="text-[11px] font-bold text-amber-500/80 uppercase tracking-wider">Data Korban Pelapor</h3>

          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Lengkap Korban</label>
            <input type="text" name="citizen_name" required placeholder="Nama Korban"
                   class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-300 uppercase mb-1">NIK Korban</label>
              <input type="text" name="citizen_nik" maxlength="16" placeholder="16 Digit NIK"
                     class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-300 uppercase mb-1">No. Ponsel Korban</label>
              <input type="text" name="citizen_phone" required placeholder="08xxxxxxxx"
                     class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
            </div>
          </div>
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-xl btn-gold text-slate-950 font-black text-xs shadow-lg flex items-center justify-center gap-2">
          <i class="fas fa-lock"></i>
          <span>Kirim Permohonan Blokir Rekening</span>
        </button>
      </form>
    </div>

    <!-- Kolom Kanan: Tabel Status Pemblokiran Rekening Terkini -->
    <div class="lg:col-span-7 polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl shadow-2xl overflow-hidden space-y-4">
      <div class="ribbon-merah-putih"></div>
      <div class="p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
          <h2 class="text-base font-heading font-black text-slate-100 flex items-center gap-2">
            <i class="fas fa-list-check text-amber-400"></i> Status Permohonan Blokir Rekening Bank
          </h2>
          <span class="text-xs text-slate-400 font-mono">Live Sync</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-[#3B2F1C] text-amber-500/90 uppercase font-bold tracking-wider text-[11px] bg-[#1B1610]">
                <th class="py-3 px-3">No. Lacak / Waktu</th>
                <th class="py-3 px-3">Rekening Bank</th>
                <th class="py-3 px-3">Kerugian</th>
                <th class="py-3 px-3">Status Blokir</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#3B2F1C]/60">
              <?php if (empty($frauds)): ?>
                <tr>
                  <td colspan="4" class="py-8 text-center text-slate-500 italic">
                    Belum ada laporan aduan penipuan daring.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($frauds as $f): ?>
                  <tr class="hover:bg-[#1B1610]/60 transition-colors">
                    <td class="py-3 px-3">
                      <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($f['tracking_number']) ?>"
                         class="font-mono font-bold text-amber-400 hover:underline block">
                        <?= htmlspecialchars($f['tracking_number']) ?>
                      </a>
                      <span class="text-[10px] text-slate-400">
                        <?= date('d/m/Y H:i', strtotime($f['created_at'])) ?>
                      </span>
                    </td>

                    <td class="py-3 px-3">
                      <div class="font-bold text-slate-100 font-mono"><?= htmlspecialchars($f['account_number']) ?></div>
                      <div class="text-[10px] text-slate-300"><?= htmlspecialchars($f['bank_name']) ?> (a.n <?= htmlspecialchars($f['account_holder_name']) ?>)</div>
                      <?php if (!empty($f['cluster_tag'])): ?>
                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded bg-red-900/40 text-red-300 font-mono text-[9px] border border-red-700/50">
                          <?= htmlspecialchars($f['cluster_tag']) ?>
                        </span>
                      <?php endif; ?>
                    </td>

                    <td class="py-3 px-3 font-mono font-semibold text-amber-300">
                      Rp <?= number_format((float)$f['loss_amount'], 0, ',', '.') ?>
                    </td>

                    <td class="py-3 px-3">
                      <?php
                        $freezeCls = match($f['freeze_status']) {
                          'FROZEN_CONFIRMED'   => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                          'REQUESTED_TO_BANK'  => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40',
                          'REJECTED_BY_BANK'   => 'bg-red-500/20 text-red-300 border-red-500/40',
                          default              => 'bg-[#1B1610] text-slate-300 border-[#3B2F1C]'
                        };
                      ?>
                      <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold <?= $freezeCls ?>">
                        <?= htmlspecialchars($f['freeze_status']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
