<div class="space-y-6">
  
  <!-- 1. Executive KPI Cards Row -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    
    <!-- Total Laporan -->
    <div class="polri-glass-card rounded-2xl p-4 border border-blue-900/60 shadow-lg relative overflow-hidden">
      <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Laporan Masuk</div>
      <div class="text-3xl font-black text-slate-100 font-heading mt-1"><?= number_format($stats['total_reports']) ?></div>
      <div class="text-[10px] text-blue-400 mt-1 flex items-center gap-1">
        <i class="fas fa-arrows-split-up-and-left"></i> Multi-channel Terintegrasi
      </div>
    </div>

    <!-- Clearance Rate -->
    <div class="polri-glass-card rounded-2xl p-4 border border-emerald-900/60 shadow-lg relative overflow-hidden">
      <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Clearance Rate (Selesai)</div>
      <div class="text-3xl font-black text-emerald-400 font-heading mt-1"><?= $stats['clearance_rate'] ?>%</div>
      <div class="text-[10px] text-emerald-300 mt-1 flex items-center gap-1">
        <i class="fas fa-circle-check"></i> <?= $stats['closed_reports'] ?> Perkara Tuntas
      </div>
    </div>

    <!-- Active Investigating -->
    <div class="polri-glass-card rounded-2xl p-4 border border-amber-900/60 shadow-lg relative overflow-hidden">
      <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Penyelidikan Lapangan</div>
      <div class="text-3xl font-black text-amber-400 font-heading mt-1"><?= number_format($stats['investigating']) ?></div>
      <div class="text-[10px] text-amber-300 mt-1 flex items-center gap-1">
        <i class="fas fa-person-running"></i> Personil Bergerak
      </div>
    </div>

    <!-- Atensi Kapolri -->
    <div class="polri-glass-card rounded-2xl p-4 border border-red-800 shadow-lg relative overflow-hidden bg-red-950/20">
      <div class="text-[11px] font-bold text-red-300 uppercase tracking-wider flex items-center gap-1">
        <span>Atensi Kapolri</span>
        <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
      </div>
      <div class="text-3xl font-black text-red-400 font-heading mt-1"><?= number_format($stats['kapolda_attention']) ?></div>
      <div class="text-[10px] text-red-300 mt-1 flex items-center gap-1 font-bold">
        <i class="fas fa-star text-amber-400"></i> Prioritas 1x24 Jam
      </div>
    </div>

    <!-- Medsos Origin -->
    <div class="polri-glass-card rounded-2xl p-4 border border-sky-900/60 shadow-lg relative overflow-hidden col-span-2 sm:col-span-1">
      <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Asal Media Sosial</div>
      <div class="text-3xl font-black text-sky-400 font-heading mt-1"><?= number_format($stats['social_reports']) ?></div>
      <div class="text-[10px] text-sky-300 mt-1 flex items-center gap-1">
        <i class="fab fa-x-twitter"></i> Aduan Viral Netizen
      </div>
    </div>

  </div>

  <!-- 2. Main GIS Heatmap & Atensi Kapolri Split Section -->
  <div class="grid lg:grid-cols-12 gap-6">
    
    <!-- Left: Leaflet GIS Interactive Crime Map -->
    <div class="lg:col-span-8 polri-glass-card rounded-2xl p-5 border border-[#3B2F1C] shadow-2xl flex flex-col">
      
      <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
        <div>
          <h2 class="text-base font-black text-slate-100 font-heading flex items-center gap-2">
            <i class="fas fa-map-location-dot text-amber-400"></i>
            Peta Sebaran Kriminalitas & Titik Gangguan Kamtibmas (GIS Map)
          </h2>
          <p class="text-[11px] text-slate-400">Visualisasi sebaran lokus perkara berdasarkan koordinat GPS Tempat Kejadian Perkara.</p>
        </div>

        <!-- Legend Pills -->
        <div class="hidden sm:flex items-center gap-2 text-[10px]">
          <span class="px-2 py-0.5 rounded bg-red-950 text-red-300 border border-red-700 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span> Atensi Kapolri
          </span>
          <span class="px-2 py-0.5 rounded bg-amber-950 text-amber-300 border border-amber-700">
            Penyelidikan
          </span>
          <span class="px-2 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-700">
            Tuntas
          </span>
        </div>
      </div>

      <!-- Map Container -->
      <div id="gisHeatmap" class="w-full h-[460px] rounded-xl border border-slate-800 overflow-hidden relative shadow-inner"></div>

      <div class="mt-3 flex items-center justify-between text-[11px] text-slate-400">
        <span>Klik pada sematan pin di peta untuk melihat ringkasan kasus dan memberikan <b>Atensi Kapolri</b>.</span>
        <span class="font-mono text-amber-400 font-bold"><?= count($heatmapPoints) ?> Titik Koordinat Aktif</span>
      </div>

    </div>

    <!-- Right: Daftar Laporan Atensi Kapolri & Intervensi Pimpinan -->
    <div class="lg:col-span-4 polri-glass-card rounded-2xl p-5 border border-red-900/50 shadow-2xl flex flex-col justify-between">
      
      <div>
        <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <img src="<?= $baseUrl ?>/assets/img/badge-kapolda.svg" alt="Kapolri" class="w-6 h-6">
            <h3 class="text-sm font-black text-red-200 font-heading uppercase tracking-wide">
              Laporan Atensi Pimpinan
            </h3>
          </div>
          <span class="px-2 py-0.5 rounded bg-red-950 text-red-400 border border-red-700 text-[10px] font-black">
            <?= count($attentionReports) ?> Kasus
          </span>
        </div>

        <!-- Attention Reports Scroll List -->
        <div class="space-y-3 max-h-[440px] overflow-y-auto pr-1">
          <?php if (empty($attentionReports)): ?>
            <div class="text-center py-10 text-slate-500 text-xs">
              <i class="fas fa-star text-2xl mb-2 text-slate-700 block"></i>
              Belum ada perkara yang dibubuhi Atensi Kapolri saat ini.
            </div>
          <?php else: ?>
            <?php foreach ($attentionReports as $ar): ?>
              <div class="p-3.5 rounded-xl bg-[#070504] border border-red-700/60 hover:border-red-500 transition-colors space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-mono text-xs font-black text-amber-400"><?= htmlspecialchars($ar['ticket_number']) ?></span>
                  <span class="text-[9px] font-black text-red-400 animate-pulse">★ ATENSI</span>
                </div>
                <h4 class="text-xs font-bold text-slate-100 line-clamp-2"><?= htmlspecialchars($ar['title']) ?></h4>
                <div class="text-[10px] text-slate-400">
                  <span>Satker: <b class="text-slate-200"><?= htmlspecialchars($ar['satker_name'] ?? 'Mabes Polri') ?></b></span>
                  <span class="block text-slate-500">Penyidik: <?= htmlspecialchars($ar['investigator_name'] ?? 'Menunggu Personel') ?></span>
                </div>
                <?php $dir = $directiveStatus[$ar['id']] ?? null; if ($dir): ?>
                  <div class="text-[10px] p-2 rounded-lg bg-slate-900/80 border border-slate-800">
                    <?php [$dirClass, $dirLabel] = match ($dir['status']) {
                        'PENDING' => ['text-red-300', 'Instruksi belum ditanggapi Satker'],
                        'ACKNOWLEDGED' => ['text-amber-300', 'Instruksi diterima Satker'],
                        default => ['text-emerald-300', 'Instruksi diselesaikan'],
                    }; ?>
                    <b class="<?= $dirClass ?>"><?= $dirLabel ?></b>
                    <?php if (!empty($dir['response_notes'])): ?>
                      <span class="block text-slate-400 mt-0.5 line-clamp-2">"<?= htmlspecialchars($dir['response_notes']) ?>"</span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10px]">
                  <span class="text-slate-500"><?= date('d/m H:i', strtotime($ar['created_at'])) ?></span>
                  <a href="<?= $baseUrl ?>/investigator/detail/<?= $ar['id'] ?>" class="text-amber-400 font-bold hover:underline">
                    Lihat Progres &rarr;
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Action: Berikan Atensi pada Kasus Baru -->
      <div class="pt-4 mt-4 border-t border-slate-800">
        <button onclick="document.getElementById('modalAttentionDirective').classList.remove('hidden')"
                class="w-full py-2.5 px-4 bg-gradient-to-r from-red-600 via-rose-600 to-red-600 hover:from-red-500 hover:to-rose-500 text-white font-black rounded-xl text-xs transition-all shadow-lg flex items-center justify-center gap-2">
          <i class="fas fa-star text-amber-300"></i>
          <span>Beri Instruksi Atensi Kapolri</span>
        </button>
      </div>

    </div>

  </div>

  <!-- 3. Monitoring Kinerja Satuan Kerja & Bottleneck Watchlist -->
  <div class="polri-glass-card rounded-2xl p-6 border border-[#3B2F1C] shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
      <div>
        <h3 class="text-base font-black text-slate-100 font-heading flex items-center gap-2">
          <i class="fas fa-gauge-high text-amber-400"></i>
          Evaluasi Kinerja Respons & SLA Satuan Kerja Mabes & Polda Jajaran
        </h3>
        <p class="text-xs text-slate-400 mt-0.5">Pemantauan kepatuhan Service Level Agreement (SLA) untuk mencegah tunggakan perkara.</p>
      </div>
    </div>

    <?php if ((int)$spktBacklog['active_cases'] > 0): ?>
      <div class="mb-4 p-3 rounded-xl border text-xs flex items-center justify-between <?= (int)$spktBacklog['overdue_cases'] > 0 ? 'bg-red-950/40 border-red-800 text-red-200' : 'bg-[#0C0A08] border-slate-800 text-slate-300' ?>">
        <span><i class="fas fa-inbox mr-1.5"></i> Meja SPKT: <b><?= (int)$spktBacklog['active_cases'] ?></b> laporan belum didisposisikan</span>
        <?php if ((int)$spktBacklog['overdue_cases'] > 0): ?>
          <span class="font-bold"><?= (int)$spktBacklog['overdue_cases'] ?> lewat SLA</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($slaWatchlist as $w):
        $overdue = (int)$w['overdue_cases'];
        $dueSoon = (int)$w['due_soon_cases'];
        $active = (int)$w['active_cases'];
        [$badgeClass, $badgeText] = match (true) {
            $overdue > 0 => ['bg-red-950 text-red-300 border-red-800', "{$overdue} Lewat SLA"],
            $dueSoon > 0 => ['bg-amber-950 text-amber-300 border-amber-700', "{$dueSoon} Mendekati SLA"],
            default      => ['bg-emerald-950 text-emerald-300 border-emerald-800', 'Sesuai SLA'],
        };
      ?>
        <div class="p-4 rounded-xl bg-[#0C0A08] border border-slate-800 flex items-center justify-between">
          <div>
            <div class="text-xs font-bold text-slate-200"><?= htmlspecialchars($w['name']) ?></div>
            <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($w['code']) ?> • <?= htmlspecialchars($w['type']) ?></span>
          </div>
          <div class="text-right">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold border <?= $badgeClass ?>">
              <?= $badgeText ?>
            </span>
            <span class="text-[10px] text-slate-400 block mt-1 font-mono"><?= $active ?> kasus aktif</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Modal Dialog Instruksi Atensi Kapolri -->
<div id="modalAttentionDirective" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-[#1B1610] border border-red-600 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
    <div class="flex items-center justify-between border-b border-slate-700 pb-3 mb-4">
      <h3 class="text-base font-bold text-red-200 flex items-center gap-2">
        <i class="fas fa-star text-amber-400"></i>
        Terbitkan Instruksi Khusus Atensi Kapolri
      </h3>
      <button onclick="document.getElementById('modalAttentionDirective').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
        <i class="fas fa-xmark text-lg"></i>
      </button>
    </div>

    <form action="<?= $baseUrl ?>/executive/giveAttention" method="POST" class="space-y-4">
      <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Pilih Perkara / Laporan Polisi:</label>
        <select name="report_id" id="attention_report_id" required
                class="w-full bg-[#0C0A08] border border-slate-700 rounded-xl p-2.5 text-xs text-slate-100 outline-none focus:border-red-500">
          <option value="">-- Pilih Kasus yang Membutuhkan Atensi Prioritas --</option>
          <?php foreach ($recentReports as $rep): ?>
            <option value="<?= $rep['id'] ?>">
              <?= htmlspecialchars($rep['ticket_number'] . ' - ' . $rep['title'] . ' (' . $rep['status'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-red-300 mb-1">
          Instruksi Tertulis dari Kapolri (Prioritas Bintang Merah):
        </label>
        <textarea name="directive_text" rows="4" required
                  class="w-full bg-[#0C0A08] border border-red-800/80 rounded-xl p-3 text-xs text-slate-100 outline-none focus:border-red-500 leading-relaxed">ATENSI KAPOLRI: Berikan prioritas utama penyelidikan perkara ini. Koordinasikan jajaran opsnal dan CCTV terkait, tangkap pelaku dalam 1x24 jam dan laporkan progres setiap 6 jam!</textarea>
        <p class="text-[10px] text-slate-400 mt-1">Instruksi akan langsung bertanda bintang merah pada akun Kasatker dan Penyidik terkait.</p>
      </div>

      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('modalAttentionDirective').classList.add('hidden')"
                class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-bold">
          Batal
        </button>
        <button type="submit"
                class="px-5 py-2 bg-gradient-to-r from-red-600 to-rose-600 text-white rounded-xl text-xs font-black shadow-lg">
          Kirim Instruksi Prioritas
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Peta sebaran kasus: Google Maps bila GOOGLE_MAPS_API_KEY diisi, selain itu Leaflet + OpenStreetMap -->
<?php $gmapsKey = \App\Config\Config::googleMapsKey(); ?>
<script>
  (function () {
    const center = { lat: -2.5, lng: 118.0 }; // Cakupan nasional (seluruh wilayah hukum Polri)
    const points = <?= json_encode($heatmapPoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    const markerStyle = (p) => {
      const isAttention = p.is_kapolda_attention == 1;
      let color = '#F59E0B'; // Amber default
      if (isAttention) color = '#EF4444'; // Merah: Atensi Kapolri
      if (p.status === 'CLOSED') color = '#10B981'; // Hijau: selesai
      return { color, radius: isAttention ? 10 : 7 };
    };
    const popupHtml = (p, color) => `
      <div style="font-family: sans-serif; font-size: 11px; color: #2A2218; max-width: 200px;">
        <b style="color: ${color};">${esc(p.ticket_number)}</b><br>
        <b>${esc(p.title)}</b><br>
        <span style="color: #64748b;">${esc(p.incident_location)}</span><br>
        <span style="font-size: 9px; color: #94a3b8;">Status: ${esc(p.status)}</span>
      </div>`;
    const located = points.filter(p => p.latitude && p.longitude);

    function initLeafletMap() {
      document.getElementById('gisHeatmap').innerHTML = '';
      const map = L.map('gisHeatmap').setView([center.lat, center.lng], 5);
      // Tile OpenStreetMap digelapkan via CSS (.map-tiles-dark) agar serasi dengan tema Command Center
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        className: 'map-tiles-dark',
        maxZoom: 19
      }).addTo(map);

      located.forEach(p => {
        const { color, radius } = markerStyle(p);
        L.circleMarker([p.latitude, p.longitude], {
          radius, fillColor: color, color: '#FFFFFF', weight: 1.5, opacity: 1, fillOpacity: 0.85
        }).addTo(map).bindPopup(popupHtml(p, color));
      });
    }

    // Dipanggil oleh script Google Maps (callback): tema gelap Command Center
    window.initGoogleGisMap = function () {
      const map = new google.maps.Map(document.getElementById('gisHeatmap'), {
        center, zoom: 5,
        mapTypeControl: false,
        streetViewControl: false,
        styles: [
          { elementType: 'geometry', stylers: [{ color: '#14100C' }] },
          { elementType: 'labels.text.fill', stylers: [{ color: '#B8A37A' }] },
          { elementType: 'labels.text.stroke', stylers: [{ color: '#0C0A08' }] },
          { featureType: 'administrative', elementType: 'geometry.stroke', stylers: [{ color: '#3B2F1C' }] },
          { featureType: 'poi', stylers: [{ visibility: 'off' }] },
          { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2A2218' }] },
          { featureType: 'road', elementType: 'labels', stylers: [{ visibility: 'off' }] },
          { featureType: 'transit', stylers: [{ visibility: 'off' }] },
          { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#070504' }] }
        ]
      });
      const info = new google.maps.InfoWindow();

      located.forEach(p => {
        const { color, radius } = markerStyle(p);
        const marker = new google.maps.Marker({
          position: { lat: Number(p.latitude), lng: Number(p.longitude) },
          map,
          title: String(p.ticket_number ?? ''),
          icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: radius, fillColor: color, fillOpacity: 0.85, strokeColor: '#FFFFFF', strokeWeight: 1.5
          }
        });
        marker.addListener('click', () => {
          info.setContent(popupHtml(p, color));
          info.open({ map, anchor: marker });
        });
      });
    };

    // Key ditolak Google (salah/dibatasi domain): kembali ke peta Leaflet
    window.gm_authFailure = initLeafletMap;

    <?php if ($gmapsKey === ''): ?>
    document.addEventListener('DOMContentLoaded', initLeafletMap);
    <?php endif; ?>
  })();
</script>

<?php if ($gmapsKey !== ''): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($gmapsKey) ?>&amp;callback=initGoogleGisMap&amp;loading=async&amp;language=id&amp;region=ID" async defer></script>
<?php endif; ?>
