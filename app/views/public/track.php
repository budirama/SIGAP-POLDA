<div class="py-10 max-w-5xl mx-auto px-4">
  
  <!-- Breadcrumb -->
  <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
    <a href="<?= $baseUrl ?>/" class="hover:text-amber-400 transition-colors">Beranda</a>
    <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
    <span class="text-amber-400 font-semibold">Pelacakan Perkembangan Perkara (SP2HP-E)</span>
  </nav>

  <!-- Search Card -->
  <div class="polri-glass-card rounded-2xl p-6 mb-8 border border-amber-500/30 shadow-xl">
    <h1 class="text-xl sm:text-2xl font-black text-slate-100 font-heading mb-2">
      Lacak Status & SP2HP Elektronik
    </h1>
    <p class="text-xs text-slate-400 mb-5">
      Masukkan Nomor Registrasi Laporan (Nomor Tiket) dan PIN Keamanan untuk melihat tahapan penanganan oleh penyidik kepolisian.
    </p>

    <?php if (!empty($errorMessage)): ?>
      <div class="mb-4 p-3 bg-red-950/70 border border-red-800 rounded-xl text-red-300 text-xs flex items-center gap-2">
        <i class="fas fa-circle-exclamation text-red-400"></i>
        <span><?= htmlspecialchars($errorMessage) ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= $baseUrl ?>/report/track" method="POST" autocomplete="off" class="grid sm:grid-cols-12 gap-3 items-end">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
      <div class="sm:col-span-6">
        <label class="block text-xs font-semibold text-slate-300 mb-1" for="ticketInput">
          Nomor Registrasi Tiket:
        </label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-amber-500"><i class="fas fa-ticket text-sm"></i></span>
          <input type="text" id="ticketInput" name="ticket" required
                 value="<?= htmlspecialchars($ticketNumber) ?>"
                 placeholder="Contoh: LP-POLDA-202610-0001"
                 class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl pl-9 pr-3 py-2.5 text-xs sm:text-sm text-slate-100 placeholder-slate-600 outline-none font-mono uppercase">
        </div>
      </div>

      <div class="sm:col-span-3">
        <label class="block text-xs font-semibold text-slate-300 mb-1" for="pinInput">
          PIN Keamanan:
        </label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500"><i class="fas fa-lock text-sm"></i></span>
          <input type="password" id="pinInput" name="pin" required inputmode="numeric" maxlength="6"
                 placeholder="6-Digit PIN"
                 class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl pl-9 pr-3 py-2.5 text-xs sm:text-sm text-slate-100 placeholder-slate-600 outline-none font-mono">
        </div>
      </div>

      <div class="sm:col-span-3">
        <button type="submit"
                class="w-full py-2.5 px-4 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black rounded-xl text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2">
          <i class="fas fa-magnifying-glass"></i>
          <span>Lacak Laporan</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Detail Laporan & Timeline (Jika ditemukan) -->
  <?php if (!empty($report)): ?>
    
    <!-- Top Status Banner -->
    <div class="polri-glass-card rounded-2xl p-6 mb-6 border <?= $report['is_kapolda_attention'] ? 'border-red-600 animate-kapolda-pulse' : 'border-[#1E355B]' ?> relative overflow-hidden">
      
      <!-- Kapolda Priority Flag Badge -->
      <?php if ($report['is_kapolda_attention']): ?>
        <div class="mb-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-950/90 border border-red-600 text-red-200 text-xs font-black shadow-lg">
          <img src="<?= $baseUrl ?>/assets/img/badge-kapolda.svg" alt="Atensi Kapolda" class="w-5 h-5">
          <span>PRIORITAS TINGGI: LAPORAN DALAM ATENSI KHUSUS KAPOLDA</span>
        </div>
      <?php endif; ?>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/60 pb-5">
        <div>
          <div class="flex flex-wrap items-center gap-2 mb-1.5">
            <span class="text-sm font-black text-amber-400 font-mono tracking-wide"><?= htmlspecialchars($report['ticket_number']) ?></span>
            <?= \App\Config\Constants::getStatusBadge($report['status']) ?>
            <?= \App\Config\Constants::getChannelBadge($report['source_channel']) ?>
          </div>
          <h2 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">
            <?= htmlspecialchars($report['title']) ?>
          </h2>
        </div>

        <div>
          <a href="<?= $baseUrl ?>/report/sp2hpPrint?ticket=<?= urlencode($report['ticket_number']) ?>" target="_blank"
             class="inline-flex items-center gap-2 px-4 py-2 bg-blue-950 hover:bg-blue-900 border border-blue-700/80 rounded-xl text-xs font-bold text-blue-200 transition-all shadow-md">
            <i class="fas fa-print text-amber-400"></i>
            <span>Cetak / Unduh SP2HP Digital</span>
          </a>
        </div>
      </div>

      <!-- Quick Info Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-5 text-xs">
        <div>
          <span class="text-slate-500 block text-[11px]">Kategori Perkara:</span>
          <span class="font-bold text-slate-200 mt-0.5 block"><?= htmlspecialchars($report['category_name']) ?></span>
        </div>

        <div>
          <span class="text-slate-500 block text-[11px]">Waktu Kejadian (TKP):</span>
          <span class="font-bold text-slate-200 mt-0.5 block"><?= date('d M Y', strtotime($report['incident_date'])) ?> <?= $report['incident_time'] ? substr($report['incident_time'], 0, 5) . ' WIB' : '' ?></span>
        </div>

        <div>
          <span class="text-slate-500 block text-[11px]">Satker Penanganan:</span>
          <span class="font-bold text-amber-400 mt-0.5 block"><?= htmlspecialchars($report['satker_name'] ?? 'Menunggu Disposisi') ?></span>
        </div>

        <div>
          <span class="text-slate-500 block text-[11px]">Penyidik Pelaksana:</span>
          <span class="font-bold text-slate-200 mt-0.5 block">
            <?= $report['investigator_name'] ? htmlspecialchars($report['investigator_rank'] . ' ' . $report['investigator_name']) : 'Belum Ditunjuk' ?>
          </span>
        </div>
      </div>

      <!-- Detail TKP & Kronologi -->
      <div class="mt-5 pt-4 border-t border-slate-800 text-xs text-slate-300 space-y-2">
        <p><b class="text-slate-400">Lokasi Kejadian:</b> <?= htmlspecialchars($report['incident_location']) ?></p>
        <p class="leading-relaxed bg-[#070F1E] p-3 rounded-xl border border-slate-800 text-slate-300">
          <b class="text-amber-400 block mb-1">Kronologi yang Dilaporkan:</b>
          <?= nl2br(htmlspecialchars($report['description'])) ?>
        </p>
      </div>

    </div>

    <!-- Timeline Perkembangan Perkara (SP2HP-E) -->
    <div class="polri-glass-card rounded-2xl p-6 sm:p-8 border border-[#1E355B] shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-4 mb-6">
        <div>
          <h3 class="text-lg font-black text-slate-100 font-heading flex items-center gap-2">
            <i class="fas fa-timeline text-amber-500"></i>
            Timeline Perkembangan Hasil Penyelidikan (SP2HP)
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">Catatan perkembangan resmi yang diterbitkan oleh tim penyidik kepolisian.</p>
        </div>
      </div>

      <?php if (empty($timeline)): ?>
        <div class="text-center py-8 text-slate-500 text-xs">
          Belum ada riwayat perkembangan untuk laporan ini.
        </div>
      <?php else: ?>
        <div class="relative border-l-2 border-slate-700 ml-3 sm:ml-4 space-y-6">
          <?php foreach ($timeline as $t): ?>
            <div class="relative pl-6 sm:pl-8 group">
              <!-- Dot Indicator -->
              <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-[#070F1E] border-2 <?= $t['is_sp2hp'] ? 'border-amber-400 bg-amber-500' : 'border-blue-500' ?> transition-transform group-hover:scale-125"></div>
              
              <div class="p-4 rounded-xl bg-[#070F1E] border border-slate-800 shadow-md">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-black text-slate-100"><?= htmlspecialchars($t['title']) ?></span>
                    <?php if ($t['is_sp2hp']): ?>
                      <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        <i class="fas fa-file-shield mr-1"></i> SP2HP-E
                      </span>
                    <?php endif; ?>
                  </div>
                  <span class="text-[10px] text-slate-500 font-mono">
                    <?= date('d M Y, H:i', strtotime($t['created_at'])) ?> WIB
                  </span>
                </div>

                <?php if (!empty($t['sp2hp_number'])): ?>
                  <div class="text-[11px] font-mono text-amber-400/90 mb-1">
                    No. Dokumen: <?= htmlspecialchars($t['sp2hp_number']) ?>
                  </div>
                <?php endif; ?>

                <p class="text-xs text-slate-300 leading-relaxed">
                  <?= nl2br(htmlspecialchars($t['public_notes'])) ?>
                </p>

                <div class="mt-2 text-[10px] text-slate-500 flex items-center gap-2 border-t border-slate-800/80 pt-2">
                  <i class="fas fa-user-check text-slate-400"></i>
                  <span>Diverifikasi oleh: <b><?= htmlspecialchars($t['actor_rank'] . ' ' . $t['actor_name']) ?></b></span>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>

    <!-- Ruang Klarifikasi & Komunikasi Dua Arah (Polisi Bersama Masyarakat) -->
    <div id="ruang-klarifikasi" class="mt-8 polri-glass-card rounded-2xl p-6 sm:p-8 border border-amber-500/40 shadow-2xl relative overflow-hidden">
      <!-- Top Accent Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-700/60 pb-4 mb-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-950 to-amber-950 border border-amber-500/50 flex items-center justify-center text-amber-400 text-xl shadow-lg flex-shrink-0">
            <i class="fas fa-comments"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-base sm:text-lg font-black text-slate-100 font-heading">Ruang Klarifikasi & Komunikasi Dua Arah</h3>
              <span class="text-[9px] font-black px-2 py-0.5 rounded bg-red-950 text-red-300 border border-red-700 uppercase">POLISI BERSAMA MASYARAKAT</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
              Saluran resmi komunikasi langsung antara Pelapor dan Tim Penyidik Polda yang menangani perkara Anda.
            </p>
          </div>
        </div>

        <div class="text-right">
          <?php if (!empty($report['investigator_name'])): ?>
            <div class="text-[11px] text-slate-400">Penyidik Penangan:</div>
            <div class="text-xs font-bold text-amber-400 flex items-center gap-1.5 justify-end">
              <i class="fas fa-user-shield"></i>
              <span><?= htmlspecialchars(($report['investigator_rank'] ? $report['investigator_rank'] . ' ' : '') . $report['investigator_name']) ?></span>
            </div>
          <?php else: ?>
            <span class="inline-block text-[10px] px-2 py-1 rounded bg-slate-800 text-slate-400 border border-slate-700">
              <i class="fas fa-clock mr-1"></i> Menunggu Penunjukan Penyidik
            </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Flash Notification -->
      <?php if (\App\Core\Session::hasFlash('success')): ?>
        <div class="mb-4 p-3 bg-emerald-950/70 border border-emerald-800 rounded-xl text-emerald-300 text-xs flex items-center gap-2">
          <i class="fas fa-circle-check text-emerald-400"></i>
          <span><?= htmlspecialchars(\App\Core\Session::getFlash('success')[0]) ?></span>
        </div>
      <?php endif; ?>
      <?php if (\App\Core\Session::hasFlash('error')): ?>
        <div class="mb-4 p-3 bg-red-950/70 border border-red-800 rounded-xl text-red-300 text-xs flex items-center gap-2">
          <i class="fas fa-circle-exclamation text-red-400"></i>
          <span><?= htmlspecialchars(\App\Core\Session::getFlash('error')[0]) ?></span>
        </div>
      <?php endif; ?>

      <!-- Thread Percakapan -->
      <div class="space-y-4 max-h-[420px] overflow-y-auto pr-2 mb-6" id="messagesContainer">
        <?php if (empty($messages)): ?>
          <div class="p-6 text-center rounded-xl bg-[#070F1E]/80 border border-slate-800 text-slate-400 text-xs">
            <i class="fas fa-shield-halved text-2xl text-amber-500/40 mb-2 block"></i>
            <p class="font-medium text-slate-300">Belum ada pesan klarifikasi dalam berkas ini.</p>
            <p class="text-[11px] text-slate-500 mt-1">Anda dapat mengajukan pertanyaan atau mengirim bukti susulan secara langsung melalui formulir di bawah.</p>
          </div>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
            <?php $isPolice = ($msg['sender_type'] === 'POLICE'); ?>
            <div class="flex gap-3 <?= $isPolice ? 'justify-start' : 'justify-end' ?>">
              
              <!-- Avatar Polisi -->
              <?php if ($isPolice): ?>
                <div class="w-8 h-8 rounded-full bg-red-950 border border-amber-500/60 flex items-center justify-center text-amber-400 text-xs flex-shrink-0 shadow">
                  <i class="fas fa-shield-halved"></i>
                </div>
              <?php endif; ?>

              <div class="max-w-lg <?= $isPolice ? 'bg-[#0E1E38] border-amber-500/40' : 'bg-slate-800/90 border-slate-700' ?> p-4 rounded-2xl border text-xs shadow-md">
                <div class="flex items-center justify-between gap-3 mb-1.5 border-b border-slate-700/50 pb-1">
                  <span class="font-bold <?= $isPolice ? 'text-amber-400' : 'text-slate-200' ?> flex items-center gap-1.5">
                    <?php if ($isPolice): ?>
                      <i class="fas fa-user-shield text-[10px]"></i>
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
                      <i class="fas fa-paperclip"></i> Lihat Lampiran Dokumen / Bukti
                    </a>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Avatar Warga -->
              <?php if (!$isPolice): ?>
                <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-600 flex items-center justify-center text-slate-300 text-xs flex-shrink-0 shadow">
                  <i class="fas fa-user"></i>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Form Kirim Pesan Klarifikasi (hanya pelapor; personil membalas lewat workspace kasus) -->
      <?php if ($isReporter): ?>
      <form action="<?= $baseUrl ?>/report/sendMessage" method="POST" enctype="multipart/form-data" class="bg-[#070F1E]/90 p-4 rounded-xl border border-slate-700/80 space-y-3">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="ticket_number" value="<?= htmlspecialchars($report['ticket_number']) ?>">

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center justify-between">
            <span>Kirim Pesan / Keterangan Klarifikasi Tambahan:</span>
            <span class="text-[10px] text-amber-400 font-normal">Tercatat dalam Berita Acara Perkara</span>
          </label>
          <textarea name="message_text" rows="3" required
                    placeholder="Tuliskan pertanyaan, tanggapan atas SP2HP, atau keterangan tambahan Anda..."
                    class="w-full bg-[#0A192F] border border-slate-700 focus:border-amber-500 rounded-xl p-3 text-xs text-slate-100 placeholder-slate-500 outline-none"></textarea>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
          <div>
            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs cursor-pointer border border-slate-600 transition-colors">
              <i class="fas fa-paperclip text-amber-400"></i>
              <span id="fileNameLabel">Lampirkan Bukti Baru (Foto / PDF)</span>
              <input type="file" name="clarification_file" class="hidden" onchange="document.getElementById('fileNameLabel').textContent = this.files[0] ? this.files[0].name : 'Lampirkan Bukti Baru'">
            </label>
          </div>

          <button type="submit"
                  class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black text-xs transition-all shadow-md flex items-center justify-center gap-2">
            <i class="fas fa-paper-plane"></i>
            <span>Kirim ke Tim Penyidik</span>
          </button>
        </div>
      </form>
      <?php else: ?>
        <p class="text-xs text-slate-400 italic bg-[#070F1E]/60 p-3 rounded-xl border border-slate-700/60">
          Mode tampilan personil: balasan klarifikasi dikirim melalui Workspace Kasus.
        </p>
      <?php endif; ?>
    </div>

    <!-- Feedback Survey Section (Jika Kasus Selesai / CLOSED) -->
    <?php if ($report['status'] === 'CLOSED'): ?>
      <div class="mt-8 polri-glass-card rounded-2xl p-6 sm:p-8 border border-emerald-500/40 shadow-2xl">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl flex-shrink-0 border border-emerald-500/30">
            <i class="fas fa-star"></i>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-black text-slate-100 font-heading">Survei Kepuasan Masyarakat (SKM)</h3>
            <p class="text-xs text-slate-400 mt-1">
              Kasus ini telah dinyatakan selesai. Pendapat Anda sangat berharga bagi peningkatan profesionalisme pelayanan Kepolisian Daerah.
            </p>

            <?php if (!empty($feedback)): ?>
              <div class="mt-4 p-4 rounded-xl bg-emerald-950/40 border border-emerald-800/60 text-xs">
                <div class="flex items-center gap-2 text-amber-400 text-sm mb-1">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="fas fa-star <?= $s <= $feedback['rating_stars'] ? 'text-amber-400' : 'text-slate-700' ?>"></i>
                  <?php endfor; ?>
                  <span class="text-slate-300 font-bold ml-2">(<?= $feedback['rating_stars'] ?> / 5 Bintang)</span>
                </div>
                <p class="text-slate-300 italic">"<?= htmlspecialchars($feedback['comments']) ?>"</p>
                <span class="text-[10px] text-emerald-400 font-semibold block mt-1">✓ Ulasan Anda telah tersimpan</span>
              </div>
            <?php elseif ($isReporter): ?>
              <form action="<?= $baseUrl ?>/report/submitFeedback" method="POST" class="mt-4 space-y-4">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="ticket_number" value="<?= htmlspecialchars($report['ticket_number']) ?>">

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Rating Kepuasan Pelayanan (1 - 5 Bintang):</label>
                  <div class="flex items-center gap-3 text-2xl text-amber-400 cursor-pointer" id="starPicker">
                    <i class="fas fa-star" onclick="setRating(1)"></i>
                    <i class="fas fa-star" onclick="setRating(2)"></i>
                    <i class="fas fa-star" onclick="setRating(3)"></i>
                    <i class="fas fa-star" onclick="setRating(4)"></i>
                    <i class="fas fa-star" onclick="setRating(5)"></i>
                    <input type="hidden" name="rating_stars" id="rating_stars" value="5">
                  </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs text-slate-300 mb-1">Kecepatan Respons Petugas:</label>
                    <select name="speed_score" class="w-full bg-[#070F1E] border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
                      <option value="5">Sangat Cepat (5)</option>
                      <option value="4">Cepat (4)</option>
                      <option value="3">Cukup (3)</option>
                      <option value="2">Kurang Cepat (2)</option>
                      <option value="1">Lambat (1)</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-xs text-slate-300 mb-1">Keadilan & Transparansi Informasi:</label>
                    <select name="fairness_score" class="w-full bg-[#070F1E] border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 outline-none">
                      <option value="5">Sangat Adil & Terbuka (5)</option>
                      <option value="4">Terbuka (4)</option>
                      <option value="3">Cukup (3)</option>
                      <option value="2">Kurang Terbuka (2)</option>
                      <option value="1">Tidak Terbuka (1)</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="block text-xs text-slate-300 mb-1">Catatan & Masukan Anda:</label>
                  <textarea name="comments" rows="3" required placeholder="Tuliskan pengalaman Anda selama penanganan perkara..."
                            class="w-full bg-[#070F1E] border border-slate-700 rounded-xl p-3 text-xs text-slate-200 outline-none focus:border-amber-500"></textarea>
                </div>

                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-bold rounded-xl text-xs transition-colors">
                  Kirimkan Ulasan SKM
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<script>
  function setRating(val) {
    document.getElementById('rating_stars').value = val;
    const stars = document.querySelectorAll('#starPicker i');
    stars.forEach((star, index) => {
      if (index < val) {
        star.classList.remove('text-slate-700');
        star.classList.add('text-amber-400');
      } else {
        star.classList.remove('text-amber-400');
        star.classList.add('text-slate-700');
      }
    });
  }
</script>
