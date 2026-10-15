<div class="space-y-6 max-w-6xl mx-auto">
  
  <!-- Breadcrumb & Back -->
  <div class="flex items-center justify-between">
    <nav class="flex items-center gap-2 text-xs text-slate-400">
      <a href="<?= $baseUrl ?><?= htmlspecialchars($backUrl) ?>" class="hover:text-amber-400">Kembali</a>
      <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
      <span class="text-amber-400 font-mono font-bold"><?= htmlspecialchars($report['ticket_number']) ?></span>
    </nav>

    <div class="flex items-center gap-2">
      <a href="<?= $baseUrl ?>/report/sp2hpPrint?ticket=<?= urlencode($report['ticket_number']) ?>" target="_blank"
         class="px-3 py-1.5 bg-blue-950 hover:bg-blue-900 border border-blue-700 text-blue-200 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5">
        <i class="fas fa-print text-amber-400"></i>
        <span>Pratinjau Cetak SP2HP</span>
      </a>
    </div>
  </div>

  <!-- Kapolri Priority Alert -->
  <?php if ($report['is_kapolda_attention']): ?>
    <div class="p-4 rounded-2xl bg-red-950/80 border border-red-600 text-red-200 text-xs flex items-center gap-3 animate-kapolda-pulse shadow-xl">
      <img src="<?= $baseUrl ?>/assets/img/badge-kapolda.svg" alt="Kapolri" class="w-8 h-8 flex-shrink-0">
      <div>
        <div class="font-black text-sm text-red-100 uppercase tracking-wide">PERINTAH ATENSI KHUSUS KAPOLRI</div>
        <p class="text-xs text-red-200 mt-0.5">
          Perkara ini diawasi langsung oleh Kapolri. Mohon tuntaskan tahapan penyelidikan dan laporkan perkembangannya segera.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Top Case Summary -->
  <div class="polri-glass-card rounded-2xl p-6 border border-[#3B2F1C] shadow-2xl">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/60 pb-5">
      <div>
        <div class="flex items-center gap-2 mb-1.5">
          <span class="font-mono text-base font-black text-amber-400"><?= htmlspecialchars($report['ticket_number']) ?></span>
          <?= \App\Config\Constants::getStatusBadge($report['status']) ?>
          <?= \App\Config\Constants::getChannelBadge($report['source_channel']) ?>
        </div>
        <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">
          <?= htmlspecialchars($report['title']) ?>
        </h2>
      </div>

      <div class="text-right">
        <span class="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">Tingkat Kedaruratan:</span>
        <span class="px-3 py-1 rounded-full text-xs font-black inline-block mt-1 <?= $report['urgency_level'] === 'EMERGENCY' ? 'bg-red-950 text-red-300 border border-red-800' : 'bg-slate-800 text-slate-200' ?>">
          <?= $report['urgency_level'] ?>
        </span>
      </div>
    </div>

    <!-- Meta Details Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-5 text-xs">
      <div>
        <span class="text-slate-500 block text-[11px]">Kategori Perkara:</span>
        <span class="font-bold text-slate-200 mt-0.5 block"><?= htmlspecialchars($report['category_name']) ?></span>
      </div>

      <div>
        <span class="text-slate-500 block text-[11px]">Waktu Kejadian:</span>
        <span class="font-bold text-slate-200 mt-0.5 block"><?= date('d F Y', strtotime($report['incident_date'])) ?></span>
      </div>

      <div>
        <span class="text-slate-500 block text-[11px]">Identitas Pelapor:</span>
        <span class="font-bold text-slate-200 mt-0.5 block">
          <?= htmlspecialchars($report['reporter_name_raw']) ?>
          <?php if ($report['is_anonymous']): ?>
            <span class="text-[9px] text-amber-400 font-mono">(Whistleblower)</span>
          <?php endif; ?>
        </span>
        <span class="text-[10px] text-slate-400 font-mono block">Kontak: <?= htmlspecialchars($report['reporter_phone']) ?></span>
      </div>

      <div>
        <span class="text-slate-500 block text-[11px]">Satker Penanggung Jawab:</span>
        <span class="font-bold text-amber-400 mt-0.5 block"><?= htmlspecialchars($report['satker_name'] ?? 'Mabes Polri') ?></span>
      </div>
    </div>

    <!-- Description & Location -->
    <div class="mt-5 pt-4 border-t border-slate-800/80 text-xs space-y-3">
      <div>
        <b class="text-slate-400">Tempat Kejadian Perkara (TKP):</b>
        <p class="text-slate-200 mt-0.5"><?= htmlspecialchars($report['incident_location']) ?></p>
        <?php if (!empty($report['latitude']) && !empty($report['longitude'])): ?>
          <span class="text-[10px] font-mono text-slate-500">Koordinat: <?= $report['latitude'] ?>, <?= $report['longitude'] ?></span>
        <?php endif; ?>
      </div>

      <div class="bg-[#0C0A08] p-4 rounded-xl border border-slate-800 text-slate-300 leading-relaxed">
        <b class="text-amber-400 block mb-1">Kronologi yang Dilaporkan:</b>
        <?= nl2br(htmlspecialchars($report['description'])) ?>
      </div>
    </div>

    <!-- Bukti-bukti Lampiran -->
    <?php if (!empty($attachments)): ?>
      <div class="mt-5 pt-4 border-t border-slate-800/80">
        <b class="text-xs text-slate-400 block mb-2">Lampiran Bukti Digital (<?= count($attachments) ?> File):</b>
        <div class="flex flex-wrap gap-3">
          <?php foreach ($attachments as $att): ?>
            <a href="<?= $baseUrl ?>/file/attachment/<?= (int)$att['id'] ?>" target="_blank"
               class="p-2.5 rounded-xl bg-[#0C0A08] border border-slate-700 hover:border-amber-500 text-xs text-slate-300 flex items-center gap-2 transition-colors">
              <i class="fas fa-paperclip text-amber-400"></i>
              <span class="max-w-[150px] truncate"><?= htmlspecialchars($att['file_name_original']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <?php if (!$canHandle): ?>
    <div class="p-3 rounded-xl bg-sky-950/60 border border-sky-800/60 text-sky-200 text-xs flex items-center gap-2">
      <i class="fas fa-eye"></i> Mode baca: Anda dapat memantau berkas perkara ini, tetapi tindakan penyidikan hanya oleh penyidik/Kanit penangan.
    </div>
  <?php else: ?>
  <!-- Workspace Forms: Terbitkan SP2HP & Selesaikan Kasus -->
  <div class="grid lg:grid-cols-12 gap-6">
    
    <!-- Left: Form Penerbitan SP2HP -->
    <div class="lg:col-span-7 polri-glass-card rounded-2xl p-6 border border-[#3B2F1C]">
      <h3 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading border-b border-slate-700 pb-3 mb-4">
        <i class="fas fa-file-shield text-amber-500"></i>
        Terbitkan SP2HP Elektronik Baru
      </h3>

      <form action="<?= $baseUrl ?>/investigator/updateSp2hp" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">

        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Nomor Dokumen SP2HP:</label>
            <input type="text" name="sp2hp_number" value="<?= htmlspecialchars(\App\Config\Constants::documentNumber('SP2HP', $report['satker_code'] ?? null)) ?>" maxlength="100" required
                   class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 font-mono outline-none focus:border-amber-500">
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Update Status Laporan:</label>
            <select name="status" class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-amber-500">
              <option value="INVESTIGATING" selected>Sedang Dalam Penyelidikan</option>
              <option value="CLOSED">Selesai / Ditutup</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">
            Catatan Resmi untuk Pelapor (Dikirim via WA & Web) <span class="text-rose-400">*</span>:
          </label>
          <textarea name="public_notes" rows="4" required
                    placeholder="Contoh: Penyidik telah melakukan olah TKP dan memeriksa 2 orang saksi. Rekaman CCTV telah diamankan dan sedang dianalisis oleh Laboratorium Forensik..."
                    class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-3 text-xs text-slate-100 leading-relaxed outline-none focus:border-amber-500"></textarea>
          <p class="text-[10px] text-slate-500 mt-1">Catatan ini akan dapat dibaca langsung oleh pelapor di portal tracking mandiri.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-amber-400 mb-1 flex items-center gap-1.5">
            <i class="fas fa-lock text-amber-400"></i> Catatan Internal Intelijen Polisi (Rahasia Kedinasan):
          </label>
          <textarea name="internal_notes" rows="2"
                    placeholder="Catatan taktis internal: Identitas target terduga pelaku a.n X, nomor plat kendaraan D, koordinat persembunyian..."
                    class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-3 text-xs text-slate-300 leading-relaxed outline-none focus:border-amber-500"></textarea>
          <p class="text-[10px] text-amber-400/80 mt-1">TIDAK AKAN DITAMPILKAN KEPADA PELAPOR. Hanya dapat diakses oleh Kasatker, Kapolri, dan Itwasum.</p>
        </div>

        <button type="submit"
                class="w-full py-2.5 px-4 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2">
          <i class="fas fa-paper-plane"></i>
          <span>Terbitkan SP2HP Elektronik</span>
        </button>
      </form>
    </div>

    <!-- Right: Penutupan Kasus & Quick Actions -->
    <div class="lg:col-span-5 space-y-6">
      
      <!-- Penutupan Kasus (BAP) -->
      <div class="polri-glass-card rounded-2xl p-6 border border-emerald-500/30">
        <h3 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading border-b border-slate-700 pb-3 mb-3">
          <i class="fas fa-box-archive text-emerald-400"></i>
          Penyelesaian Perkara (BAP)
        </h3>
        <p class="text-xs text-slate-400 mb-4 leading-relaxed">
          Gunakan aksi ini apabila seluruh tahapan penyelidikan telah tuntas, pelaku tertangkap, atau tercapai kesepakatan damai (Restorative Justice).
        </p>

        <?php if ($report['status'] === 'CLOSED'): ?>
          <div class="p-3 bg-emerald-950/60 border border-emerald-800 rounded-xl text-emerald-300 text-xs font-bold text-center">
            ✓ Kasus Ini Telah Selesai (CLOSED)
          </div>
        <?php else: ?>
          <form action="<?= $baseUrl ?>/investigator/closeCase" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menutup dan menyelesaikan perkara ini?');" class="space-y-3">
            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Ringkasan Hasil Akhir (Berita Acara):</label>
              <textarea name="resolution_notes" rows="3" required
                        placeholder="Pelaku berhasil diamankan beserta barang bukti sepeda motor utuh..."
                        class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-emerald-500">Perkara telah selesai ditangani secara profesional dan barang bukti telah diamankan.</textarea>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-black rounded-xl text-xs transition-colors shadow">
              Tutup Perkara & Terbitkan Berita Acara
            </button>
          </form>
        <?php endif; ?>
      </div>

    </div>

  </div>
  <?php endif; ?>

  <!-- Ruang Klarifikasi & Komunikasi Dua Arah (Polisi Bersama Masyarakat) -->
  <div id="ruang-klarifikasi" class="polri-glass-card rounded-2xl p-6 border border-amber-500/40 shadow-2xl relative overflow-hidden">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-700/60 pb-4 mb-5">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-red-950 to-amber-950 border border-amber-500/50 flex items-center justify-center text-amber-400 text-lg shadow-lg flex-shrink-0">
          <i class="fas fa-comments"></i>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-base font-bold text-slate-100 font-heading">Ruang Klarifikasi & Komunikasi Dua Arah</h3>
            <span class="text-[9px] font-black px-2 py-0.5 rounded bg-red-950 text-red-300 border border-red-700 uppercase">POLISI BERSAMA MASYARAKAT</span>
          </div>
          <p class="text-xs text-slate-400 mt-0.5">
            Komunikasi interaktif antara Pelapor (<?= htmlspecialchars($report['reporter_name_raw']) ?>) dan Tim Penyidik. Diawasi oleh Kanit, Kasatker & Itwasum.
          </p>
        </div>
      </div>
      <div class="text-[11px] text-slate-400">
        Status Supervisi: <span class="text-emerald-400 font-bold"><i class="fas fa-eye mr-1"></i>Audit Aktif</span>
      </div>
    </div>

    <!-- Percakapan Case Messages -->
    <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 mb-6" id="messagesContainer">
      <?php if (empty($messages)): ?>
        <div class="p-6 text-center rounded-xl bg-[#0C0A08] border border-slate-800 text-slate-400 text-xs">
          <i class="fas fa-handshake-angle text-2xl text-amber-500/40 mb-2 block"></i>
          <p class="font-medium text-slate-300">Belum ada pertukaran pesan klarifikasi dengan pelapor.</p>
          <p class="text-[11px] text-slate-500 mt-1">Gunakan formulir di bawah ini untuk meminta keterangan tambahan atau mengundang pelapor secara transparan.</p>
        </div>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
          <?php $isPolice = ($msg['sender_type'] === 'POLICE'); ?>
          <div class="flex gap-3 <?= $isPolice ? 'justify-end' : 'justify-start' ?>">
            
            <!-- Avatar Warga -->
            <?php if (!$isPolice): ?>
              <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-600 flex items-center justify-center text-slate-300 text-xs flex-shrink-0 shadow">
                <i class="fas fa-user"></i>
              </div>
            <?php endif; ?>

            <div class="max-w-xl <?= $isPolice ? 'bg-[#1B1610] border-amber-500/40' : 'bg-slate-800/90 border-slate-700' ?> p-4 rounded-2xl border text-xs shadow-md">
              <div class="flex items-center justify-between gap-3 mb-1.5 border-b border-slate-700/50 pb-1">
                <span class="font-bold <?= $isPolice ? 'text-amber-400' : 'text-slate-200' ?> flex items-center gap-1.5">
                  <?php if ($isPolice): ?>
                    <i class="fas fa-shield-halved text-[10px]"></i>
                  <?php else: ?>
                    <i class="fas fa-user text-[10px]"></i>
                  <?php endif; ?>
                  <?= htmlspecialchars($msg['sender_name']) ?>
                </span>
                <span class="text-[10px] text-slate-500 font-mono"><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?> WIB</span>
              </div>
              
              <p class="text-slate-200 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($msg['message_text']) ?></p>

              <?php if (!empty($msg['attachment_path'])): ?>
                <div class="mt-2.5 pt-2 border-t border-slate-700/60">
                  <a href="<?= $baseUrl ?>/file/message/<?= (int)$msg['id'] ?>" target="_blank"
                     class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-900 hover:bg-slate-950 text-amber-400 text-[11px] font-semibold transition-colors border border-amber-500/30">
                    <i class="fas fa-paperclip"></i> Unduh / Buka Dokumen Lampiran
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <!-- Avatar Polisi -->
            <?php if ($isPolice): ?>
              <div class="w-8 h-8 rounded-full bg-red-950 border border-amber-500/60 flex items-center justify-center text-amber-400 text-xs flex-shrink-0 shadow">
                <i class="fas fa-user-shield"></i>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Form Kirim Pesan Penyidik ke Pelapor -->
    <?php if ($canHandle): ?>
    <form action="<?= $baseUrl ?>/investigator/sendMessage" method="POST" enctype="multipart/form-data" class="bg-[#0C0A08] p-4 rounded-xl border border-slate-700/80 space-y-3">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="report_id" value="<?= $report['id'] ?>">

      <div>
        <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center justify-between">
          <span>Kirim Pesan Resmi / Permintaan Keterangan Tambahan ke Pelapor:</span>
          <span class="text-[10px] text-amber-400">Pesan ini akan langsung tampil di halaman tracking tiket pelapor</span>
        </label>
        <textarea name="message_text" rows="3" required
                  placeholder="Contoh: Yth. Pelapor, mohon dapat melampirkan salinan mutasi rekening koran atau hadir untuk memberikan keterangan tambahan pada hari..."
                  class="w-full bg-[#14100C] border border-slate-700 focus:border-amber-500 rounded-xl p-3 text-xs text-slate-100 placeholder-slate-500 outline-none"><?= htmlspecialchars($messageDraft ?? '') ?></textarea>
      </div>

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
        <div>
          <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs cursor-pointer border border-slate-600 transition-colors">
            <i class="fas fa-paperclip text-amber-400"></i>
            <span id="investigatorFileLabel">Lampirkan Surat Undangan / Dokumen (PDF/Foto)</span>
            <input type="file" name="clarification_file" class="hidden" onchange="document.getElementById('investigatorFileLabel').textContent = this.files[0] ? this.files[0].name : 'Lampirkan Surat Undangan / Dokumen'">
          </label>
        </div>

        <button type="submit"
                class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black text-xs transition-all shadow-md flex items-center justify-center gap-2">
          <i class="fas fa-paper-plane"></i>
          <span>Kirim Pesan ke Pelapor</span>
        </button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- Complete Timeline (Public + Internal Notes) -->
  <div class="polri-glass-card rounded-2xl p-6 border border-[#3B2F1C]">
    <h3 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading border-b border-slate-700 pb-3 mb-5">
      <i class="fas fa-list-check text-blue-400"></i>
      Histori Audit Lengkap Penanganan Perkara
    </h3>

    <div class="space-y-4">
      <?php foreach ($timeline as $t): ?>
        <div class="p-4 rounded-xl bg-[#0C0A08] border border-slate-800 text-xs space-y-2">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-100 text-sm"><?= htmlspecialchars($t['title']) ?></span>
            <span class="text-[10px] text-slate-500 font-mono"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?> WIB</span>
          </div>

          <?php if (!empty($t['public_notes'])): ?>
            <div class="text-slate-300">
              <b class="text-blue-400 text-[11px]">Catatan Publik:</b>
              <p class="mt-0.5"><?= nl2br(htmlspecialchars($t['public_notes'])) ?></p>
            </div>
          <?php endif; ?>

          <?php if (!empty($t['internal_notes'])): ?>
            <div class="p-2.5 rounded-lg bg-amber-950/30 border border-amber-800/40 text-amber-200">
              <b class="text-amber-400 text-[11px] flex items-center gap-1"><i class="fas fa-lock"></i> Catatan Rahasia Kedinasan:</b>
              <p class="mt-0.5"><?= nl2br(htmlspecialchars($t['internal_notes'])) ?></p>
            </div>
          <?php endif; ?>

          <div class="text-[10px] text-slate-500 pt-1 border-t border-slate-800/80">
            Dicatat oleh: <b><?= htmlspecialchars(empty($t['actor_name']) ? 'Sistem SIGAP-POLRI' : trim(($t['actor_rank'] ?? '') . ' ' . $t['actor_name'])) ?></b>
            <?php if (!empty($t['actor_role'])): ?>(<?= htmlspecialchars(\App\Config\Constants::getRoleLabel($t['actor_role'])) ?>)<?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>
