<?php
/**
 * View: Modul 5 Dumas Terpadu & Peringatan Dini Integritas
 * Integrasi Dumas Presisi, Propam, WBS & Early Warning Anomaly Detection
 */
?>
<div class="space-y-8">
  <!-- Header Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/10 border border-purple-500/30 text-purple-400 text-xs font-bold tracking-wider uppercase mb-2">
        <i class="fas fa-shield-virus"></i> MODUL 5: DUMAS TERPADU &amp; INTEGRITAS PRESISI
      </div>
      <h1 class="text-2xl md:text-3xl font-heading font-black text-slate-100 tracking-tight">
        Dumas Terpadu &amp; Peringatan Dini Integritas
      </h1>
      <p class="text-xs md:text-sm text-slate-400 font-light mt-1">
        Satu pintu penanganan aduan masyarakat terkait perilaku personil dan <strong class="text-amber-400 font-semibold">Deteksi Dini Anomali Pungli</strong> pada loket layanan.
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
        <p class="font-bold">Aduan Dumas Diterima</p>
        <p class="text-emerald-200"><?= htmlspecialchars($message) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs flex items-center gap-3 shadow-lg">
      <i class="fas fa-circle-exclamation text-red-400 text-base"></i>
      <div>
        <p class="font-bold">Gagal Mendaftarkan Dumas</p>
        <p class="text-red-200"><?= htmlspecialchars($error) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Early Warning Anomaly Box: Deteksi Lonjakan Aduan Loket -->
  <?php if (!empty($anomalies)): ?>
    <div class="polri-glass-card bg-[#14100C] border-2 border-red-500/50 rounded-2xl p-6 shadow-xl space-y-4 relative overflow-hidden">
      <div class="ribbon-merah-putih"></div>
      <div class="flex items-center gap-2 text-red-400">
        <i class="fas fa-bell-exclamation text-xl animate-bounce"></i>
        <h3 class="text-base font-heading font-black text-slate-100">Peringatan Dini: Terdeteksi Anomali Dugaan Pelanggaran Loket</h3>
      </div>
      <p class="text-xs text-slate-300 leading-relaxed font-light">
        Sistem intelijen pengawasan mendeteksi adanya konsentrasi aduan masyarakat berulang pada loket layanan tertentu. Satuan Itwasda dan Bidpropam Polda diinstruksikan melakukan verifikasi lapangan mendadak (Sidak).
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
        <?php foreach ($anomalies as $a): ?>
          <div class="p-4 rounded-xl bg-[#1B1610] border border-red-500/40 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-slate-100 truncate"><?= htmlspecialchars($a['satker_name']) ?></span>
              <span class="px-2 py-0.5 rounded-full bg-red-600/40 text-red-300 text-[10px] font-black">
                <?= (int)$a['incident_count'] ?> Aduan
              </span>
            </div>
            <div class="text-xs text-amber-400 font-bold">
              Loket: <?= htmlspecialchars($a['reported_service_loket']) ?>
            </div>
            <div class="text-[11px] text-red-300 font-mono">
              Dugaan: <?= htmlspecialchars($a['allegation_type']) ?>
            </div>
            <div class="text-[10px] text-slate-400 pt-1 border-t border-[#3B2F1C]">
              Rekomendasi: Sidak Bidpropam &amp; Audit Rekaman CCTV
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Kolom Kiri: Formulir Lapor Dumas Presisi -->
    <div class="lg:col-span-5 polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl p-6 shadow-2xl space-y-5">
      <div class="border-b border-[#3B2F1C] pb-3">
        <h2 class="text-base font-heading font-black text-slate-100 flex items-center gap-2">
          <i class="fas fa-bullhorn text-amber-400"></i> Formulir Pengaduan Dumas Presisi
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">Aduan langsung diteruskan ke Divpropam / Itwasum Polri.</p>
      </div>

      <form action="<?= $baseUrl ?>/oneservice/dumas" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

        <!-- Mode Whistleblower -->
        <div class="p-3.5 rounded-xl bg-[#1B1610] border border-purple-500/30">
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="is_whistleblower" value="1" checked
                   class="mt-1 w-4 h-4 rounded text-purple-500 bg-[#0C0A08] border-[#3B2F1C] focus:ring-purple-500">
            <div>
              <span class="text-xs font-bold text-purple-300 block">Mode Whistleblower (Kerahasiaan Dijamin)</span>
              <span class="text-[11px] text-slate-400">Identitas Anda dienkripsi penuh at-rest demi keamanan pelapor dari intimidasi.</span>
            </div>
          </label>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Satker yang Dilaporkan</label>
          <select name="reported_satker_id" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
            <?php foreach ($satkers as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Loket / Unit Layanan</label>
          <input type="text" name="reported_service_loket" required placeholder="Contoh: Loket SIM, Ruang Sidik Reskrim, Loket SKCK, SPKT"
                 class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Jenis Dugaan Pelanggaran</label>
          <select name="allegation_type" class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
            <option value="PUNGLI">Pungli (Pungutan Liar / Tarif Tidak Resmi)</option>
            <option value="PEMERASAN">Pemerasan / Permintaan Uang Perkara</option>
            <option value="PENOLAKAN_LAPORAN">Penolakan Laporan Warga di SPKT</option>
            <option value="PENYIDIKAN_MANGKRAK">Penyidikan Mangkrak / Tanpa SP2HP</option>
            <option value="AROGANSI">Arogansi / Sikap Kasar Oknum Personil</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Uraian Lengkap Kejadian</label>
          <textarea name="summary" required rows="4"
                    placeholder="Sebutkan tanggal kejadian, nama/ciri oknum personil (jika tahu), nominal uang yang diminta, dan bukti yang dimiliki..."
                    class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs leading-relaxed"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-[#3B2F1C]">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Pelapor (Opsional)</label>
            <input type="text" name="reporter_name" placeholder="Boleh samaran"
                   class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">No. HP (Untuk Lacak)</label>
            <input type="text" name="reporter_phone" placeholder="08xxxxxxxx"
                   class="w-full px-3 py-2 rounded-xl bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-slate-100 text-xs font-mono">
          </div>
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-xl btn-gold text-slate-950 font-black text-xs shadow-lg flex items-center justify-center gap-2">
          <i class="fas fa-paper-plane"></i>
          <span>Kirim Aduan Dumas Terpadu</span>
        </button>
      </form>
    </div>

    <!-- Kolom Kanan: Daftar Kasus Dumas & Investigasi Propam -->
    <div class="lg:col-span-7 polri-glass-card bg-[#14100C] border border-[#3B2F1C] rounded-2xl shadow-2xl overflow-hidden space-y-4">
      <div class="ribbon-merah-putih"></div>
      <div class="p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-[#3B2F1C] pb-3">
          <h2 class="text-base font-heading font-black text-slate-100 flex items-center gap-2">
            <i class="fas fa-list-check text-amber-400"></i> Monitoring Penanganan Dumas Presisi
          </h2>
          <span class="text-xs text-slate-400">Supervisi Itwasum</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-[#3B2F1C] text-amber-500/90 uppercase font-bold tracking-wider text-[11px] bg-[#1B1610]">
                <th class="py-3 px-3">No. Lacak / Waktu</th>
                <th class="py-3 px-3">Satker &amp; Loket</th>
                <th class="py-3 px-3">Dugaan</th>
                <th class="py-3 px-3">Status Pengawasan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#3B2F1C]/60">
              <?php if (empty($cases)): ?>
                <tr>
                  <td colspan="4" class="py-8 text-center text-slate-500 italic">
                    Belum ada aduan Dumas yang terdaftar.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($cases as $c): ?>
                  <tr class="hover:bg-[#1B1610]/60 transition-colors">
                    <td class="py-3 px-3">
                      <a href="<?= $baseUrl ?>/oneservice/track?ticket=<?= urlencode($c['tracking_number']) ?>"
                         class="font-mono font-bold text-amber-400 hover:underline block">
                        <?= htmlspecialchars($c['tracking_number']) ?>
                      </a>
                      <span class="text-[10px] text-slate-400">
                        <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                      </span>
                    </td>

                    <td class="py-3 px-3">
                      <div class="font-bold text-slate-100"><?= htmlspecialchars($c['satker_name']) ?></div>
                      <div class="text-[10px] text-slate-300"><?= htmlspecialchars($c['reported_service_loket'] ?? 'Loket Layanan') ?></div>
                    </td>

                    <td class="py-3 px-3">
                      <span class="font-mono font-bold text-amber-300"><?= htmlspecialchars($c['allegation_type']) ?></span>
                      <div class="text-[10px] text-slate-400 truncate max-w-xs" title="<?= htmlspecialchars($c['incident_summary']) ?>">
                        <?= htmlspecialchars($c['incident_summary']) ?>
                      </div>
                    </td>

                    <td class="py-3 px-3">
                      <?php
                        $dCls = match($c['investigation_status']) {
                          'NEW'                  => 'bg-blue-500/20 text-blue-300 border-blue-500/40',
                          'VERIFIED_ITWAS'       => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                          'INVESTIGATED_PROPAM'  => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
                          'SANCTION_RECOMMENDED' => 'bg-red-500/20 text-red-300 border-red-500/40',
                          default                => 'bg-[#1B1610] text-slate-300 border-[#3B2F1C]'
                        };
                      ?>
                      <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold <?= $dCls ?>">
                        <?= htmlspecialchars($c['investigation_status']) ?>
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
