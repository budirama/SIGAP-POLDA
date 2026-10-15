<div class="py-10 max-w-4xl mx-auto px-4">
  
  <!-- Breadcrumb -->
  <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
    <a href="<?= $baseUrl ?>/" class="hover:text-amber-400 transition-colors">Beranda</a>
    <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
    <span class="text-amber-400 font-semibold">Formulir Pengaduan Masyarakat</span>
  </nav>

  <!-- Header Card -->
  <div class="polri-glass-card rounded-2xl p-6 sm:p-8 mb-8 border border-amber-500/30 relative overflow-hidden shadow-2xl">
    <div class="absolute top-0 right-0 p-6 opacity-15 pointer-events-none">
      <img src="<?= $baseUrl ?>/assets/img/logo-polisi-masyarakat.png" alt="Polisi Bersama Masyarakat" class="w-36 h-auto">
    </div>

    <div class="flex items-start gap-4">
      <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl flex-shrink-0 border border-amber-500/40">
        <i class="fas fa-file-signature"></i>
      </div>
      <div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-100 font-heading">Formulir Laporan Pengaduan</h1>
        <p class="text-xs sm:text-sm text-slate-300 mt-1 leading-relaxed">
          Silakan isi data kronologi perkara selengkap mungkin. Laporan Anda akan langsung masuk ke antrean verifikasi petugas SPKT Polri dan diteruskan ke Satuan Kerja terkait.
        </p>
      </div>
    </div>
  </div>

  <!-- Form Container -->
  <form action="<?= $baseUrl ?>/report/store" method="POST" enctype="multipart/form-data" class="space-y-8" id="reportForm">
    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

    <!-- Section 1: Identitas Pelapor -->
    <div class="polri-glass-card rounded-2xl p-6 border border-[#1E355B]">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-5">
        <h2 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading">
          <span class="w-6 h-6 rounded-lg bg-blue-900 text-blue-300 flex items-center justify-center text-xs font-bold">1</span>
          Identitas Pelapor
        </h2>
        <span class="text-[11px] text-slate-400">* Wajib diisi</span>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <!-- NIK -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="reporter_nik">
            NIK KTP (16 Digit) <span class="text-rose-400">*</span>
          </label>
          <input type="text" id="reporter_nik" name="reporter_nik" maxlength="16" required
                 value="<?= htmlspecialchars($old['reporter_nik'] ?? $authUser['nik'] ?? '') ?>" inputmode="numeric" pattern="\d{16}"
                 placeholder="3171012304950001"
                 class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 outline-none">
        </div>

        <!-- Nama Lengkap -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="reporter_name">
            Nama Lengkap Sesuai KTP <span class="text-rose-400">*</span>
          </label>
          <input type="text" id="reporter_name" name="reporter_name" required
                 value="<?= htmlspecialchars($old['reporter_name'] ?? $authUser['name'] ?? '') ?>" maxlength="150"
                 placeholder="Contoh: Budi Santoso"
                 class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 outline-none">
        </div>

        <!-- Nomor WhatsApp -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="reporter_phone">
            Nomor WhatsApp Aktif <span class="text-rose-400">*</span>
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-400 text-sm"><i class="fab fa-whatsapp"></i></span>
            <input type="tel" id="reporter_phone" name="reporter_phone" required
                   value="<?= htmlspecialchars($old['reporter_phone'] ?? $authUser['phone'] ?? '') ?>"
                   placeholder="081298765432"
                   class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl pl-9 pr-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 outline-none">
          </div>
          <p class="text-[10px] text-slate-400 mt-1">Dipakai penyidik untuk menghubungi Anda bila perlu klarifikasi. Nomor tiket &amp; PIN ditampilkan setelah laporan terkirim &mdash; mohon dicatat.</p>
        </div>

        <!-- Urgency Level -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="urgency_level">
            Tingkat Kedaruratan
          </label>
          <select id="urgency_level" name="urgency_level"
                  class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 outline-none">
            <?php foreach (['NORMAL' => 'Normal (Pelaporan Standar)', 'HIGH' => 'Tinggi (Pelaku Masih di Lokasi / Ancaman Jiwa)', 'EMERGENCY' => 'Darurat (Butuh Tindakan Cepat 1x24 Jam)'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= ($old['urgency_level'] ?? 'NORMAL') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Whistleblower Protection Box -->
      <div class="mt-4 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-start gap-3">
        <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" <?= !empty($old['is_anonymous']) ? 'checked' : '' ?>
               class="mt-1 w-4 h-4 rounded text-amber-500 focus:ring-amber-400 bg-slate-900 border-slate-700">
        <div>
          <label for="is_anonymous" class="text-xs font-bold text-amber-300 cursor-pointer">
            <i class="fas fa-user-secret mr-1"></i> Rahasiakan Identitas Saya (Whistleblower Protection)
          </label>
          <p class="text-[11px] text-slate-400 mt-0.5">
            Nama dan NIK Anda akan disamarkan dari pihak terlapor, publik, maupun di dokumen terbuka demi keselamatan pelapor. Hanya pejabat berwenang (Kanit/Direktur) yang dapat memverifikasi identitas Anda untuk Berita Acara.
          </p>
        </div>
      </div>

    </div>

    <!-- Section 2: Informasi Perkara -->
    <div class="polri-glass-card rounded-2xl p-6 border border-[#1E355B]">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-5">
        <h2 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading">
          <span class="w-6 h-6 rounded-lg bg-amber-900 text-amber-300 flex items-center justify-center text-xs font-bold">2</span>
          Rincian Peristiwa / Tindak Pidana
        </h2>
      </div>

      <div class="space-y-4">
        <!-- Kategori -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="category_id">
            Kategori Perkara / Gangguan <span class="text-rose-400">*</span>
          </label>
          <select id="category_id" name="category_id" required
                  class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 outline-none">
            <option value="">-- Pilih Jenis Pengaduan / Perkara --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= (string)($old['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?> (SLA Respon: <?= $cat['sla_hours'] ?> Jam)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Judul Laporan -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="title">
            Judul Ringkas Peristiwa <span class="text-rose-400">*</span>
          </label>
          <input type="text" id="title" name="title" required maxlength="200"
                 value="<?= htmlspecialchars($old['title'] ?? '') ?>"
                 placeholder="Contoh: Pencurian Sepeda Motor Honda Beat di Parkiran Ruko Cengkareng"
                 class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 outline-none">
        </div>

        <!-- Tanggal & Waktu Kejadian -->
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1" for="incident_date">
              Tanggal Kejadian <span class="text-rose-400">*</span>
            </label>
            <input type="date" id="incident_date" name="incident_date" required max="<?= date('Y-m-d') ?>"
                   value="<?= htmlspecialchars($old['incident_date'] ?? date('Y-m-d')) ?>"
                   class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1" for="incident_time">
              Perkiraan Waktu (WIB)
            </label>
            <input type="time" id="incident_time" name="incident_time" value="<?= htmlspecialchars($old['incident_time'] ?? date('H:i')) ?>"
                   class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 outline-none">
          </div>
        </div>

        <!-- Kronologi Lengkap -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="description">
            Kronologi Lengkap Peristiwa <span class="text-rose-400">*</span>
          </label>
          <textarea id="description" name="description" rows="5" required
                    placeholder="Uraikan apa yang terjadi, siapa pelakunya (jika mengenali/melihat ciri fisik), barang apa saja yang hilang/dirusak, serta saksi-saksi yang berada di lokasi kejadian..."
                    class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 outline-none leading-relaxed"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Section 3: Lokasi TKP & Peta Spasial -->
    <div class="polri-glass-card rounded-2xl p-6 border border-[#1E355B]">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-5">
        <h2 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading">
          <span class="w-6 h-6 rounded-lg bg-emerald-900 text-emerald-300 flex items-center justify-center text-xs font-bold">3</span>
          Tempat Kejadian Perkara (TKP)
        </h2>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1" for="incident_location">
            Alamat / Patokan Lokasi TKP <span class="text-rose-400">*</span>
          </label>
          <input type="text" id="incident_location" name="incident_location" required maxlength="1000"
                 value="<?= htmlspecialchars($old['incident_location'] ?? '') ?>"
                 placeholder="Contoh: Jl. Daan Mogot KM 12 depan Halte TransJakarta Pesing, Cengkareng"
                 class="w-full bg-[#070F1E] border border-[#1E355B] focus:border-amber-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-600 outline-none">
        </div>

        <!-- Peta Leaflet Interaktif -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-semibold text-slate-300">
              Pinpoint Titik Koordinat GPS (Klik pada peta untuk menandai TKP):
            </label>
            <button type="button" id="btnGetLocation" class="text-xs text-amber-400 hover:text-amber-300 flex items-center gap-1 font-semibold">
              <i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya
            </button>
          </div>
          
          <div id="tkpMap" class="w-full h-64 rounded-xl border border-slate-700 overflow-hidden shadow-inner"></div>

          <!-- Hidden Coordinates Input -->
          <div class="grid grid-cols-2 gap-2 mt-2">
            <input type="text" id="latitude" name="latitude" readonly placeholder="Latitude" 
                   value="-6.22350000" class="bg-black/40 border border-slate-800 rounded-lg px-2.5 py-1 text-xs text-slate-400 font-mono">
            <input type="text" id="longitude" name="longitude" readonly placeholder="Longitude" 
                   value="106.80990000" class="bg-black/40 border border-slate-800 rounded-lg px-2.5 py-1 text-xs text-slate-400 font-mono">
          </div>
        </div>
      </div>
    </div>

    <!-- Section 4: Upload Bukti Digital -->
    <div class="polri-glass-card rounded-2xl p-6 border border-[#1E355B]">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-5">
        <h2 class="text-base font-bold text-slate-100 flex items-center gap-2 font-heading">
          <span class="w-6 h-6 rounded-lg bg-purple-900 text-purple-300 flex items-center justify-center text-xs font-bold">4</span>
          Lampiran Bukti Digital (Foto TKP / Dokumen / Video)
        </h2>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-2">
          Pilih Berkas Lampiran (Format: JPG, PNG, WEBP, MP4, PDF • Maksimal 5 File):
        </label>
        
        <div class="border-2 border-dashed border-slate-700 hover:border-amber-500/50 rounded-2xl p-8 text-center cursor-pointer transition-colors bg-[#070F1E]/50"
             onclick="document.getElementById('proof_files').click()">
          <input type="file" id="proof_files" name="proof_files[]" multiple accept="image/*,video/mp4,.pdf" class="hidden" onchange="previewFiles(this)">
          <i class="fas fa-cloud-arrow-up text-3xl text-amber-500 mb-2"></i>
          <p class="text-xs font-bold text-slate-200">Klik atau geser file ke sini untuk mengunggah bukti</p>
          <p class="text-[11px] text-slate-500 mt-1">Foto kerusakan, rekaman CCTV, foto surat/kwitansi, atau bukti chat</p>
        </div>

        <div id="filePreviewList" class="mt-3 space-y-2"></div>
      </div>
    </div>

    <!-- Statement & Submit Button -->
    <div class="pt-2 space-y-4">
      <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 text-[11px] text-slate-400 leading-relaxed">
        <p>
          <i class="fas fa-scale-balanced text-amber-400 mr-1.5"></i>
          <b>Pernyataan Hukum:</b> Dengan mengirimkan laporan ini, saya menyatakan bahwa informasi yang saya berikan adalah benar sesuai kenyataan yang saya alami/ketahui. Laporan palsu dapat dikenakan sanksi pidana berdasarkan Pasal 220 KUHP.
        </p>
      </div>

      <button type="submit"
              class="w-full py-4 px-6 bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black rounded-xl text-base transition-all shadow-xl hover:shadow-amber-500/30 flex items-center justify-center gap-2">
        <i class="fas fa-paper-plane"></i>
        <span>Kirimkan Laporan Pengaduan</span>
      </button>
    </div>

  </form>
</div>

<!-- Leaflet Map Script Init -->
<script>
  let map, marker;
  // Titik awal: peta Indonesia (pelapor memperbesar ke lokasi atau memakai GPS)
  const defaultLat = -2.5;
  const defaultLng = 118.0;

  document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi Map Leaflet
    map = L.map('tkpMap').setView([defaultLat, defaultLng], 5);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Default Marker
    marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
    
    // Event Klik di Map
    map.on('click', function(e) {
      updateCoordinates(e.latlng.lat, e.latlng.lng);
    });

    // Event Geser Marker
    marker.on('dragend', function(e) {
      const pos = e.target.getLatLng();
      updateCoordinates(pos.lat, pos.lng);
    });

    // Tombol Gunakan Lokasi Saya
    document.getElementById('btnGetLocation').addEventListener('click', function() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          updateCoordinates(lat, lng);
          map.setView([lat, lng], 15);
        }, function(err) {
          alert("Gagal membaca GPS gawai Anda. Silakan klik langsung pada peta.");
        });
      }
    });
  });

  function updateCoordinates(lat, lng) {
    marker.setLatLng([lat, lng]);
    document.getElementById('latitude').value = lat.toFixed(8);
    document.getElementById('longitude').value = lng.toFixed(8);
  }

  function previewFiles(input) {
    const list = document.getElementById('filePreviewList');
    list.innerHTML = '';
    if (input.files && input.files.length > 0) {
      for (let i = 0; i < input.files.length; i++) {
        const file = input.files[i];
        const item = document.createElement('div');
        item.className = 'flex items-center justify-between p-2 rounded-lg bg-[#070F1E] border border-slate-800 text-xs text-slate-300';
        item.innerHTML = `<span><i class="fas fa-file text-amber-400 mr-2"></i> <span class="file-name"></span></span><span class="text-slate-500 font-mono text-[10px]">${(file.size / 1024).toFixed(1)} KB</span>`;
        item.querySelector('.file-name').textContent = file.name;
        list.appendChild(item);
      }
    }
  }
</script>
