<?php
use App\Models\LostFound;

$v = fn(string $k, string $default = '') => htmlspecialchars((string)($old[$k] ?? $default));
$input = 'w-full bg-[#0C0A08] border border-[#3B2F1C] focus:border-amber-500 rounded-xl px-3 py-2.5 text-xs sm:text-sm text-slate-100 placeholder-slate-600 outline-none';
$label = 'block text-xs font-semibold text-slate-300 mb-1';
?>
<div class="py-10 max-w-4xl mx-auto px-4">

  <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
    <a href="<?= $baseUrl ?>/" class="hover:text-amber-400 transition-colors">Beranda</a>
    <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
    <a href="<?= $baseUrl ?>/hilangtemu" class="hover:text-amber-400 transition-colors">Hilang &amp; Temu</a>
    <i class="fas fa-chevron-right text-[10px] text-slate-600"></i>
    <span class="text-amber-400 font-semibold">Buat Laporan</span>
  </nav>

  <form action="<?= $baseUrl ?>/hilangtemu/store" method="POST" enctype="multipart/form-data" id="lfForm"
        class="polri-glass-card rounded-2xl p-6 sm:p-8 border border-amber-500/30 shadow-xl space-y-6">
    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

    <div>
      <h1 class="text-xl sm:text-2xl font-black text-slate-100 font-heading">Formulir Hilang &amp; Temu</h1>
      <p class="text-xs text-slate-400 mt-1">
        <?php if ($isOfficer): ?>
          <span class="text-emerald-300 font-bold"><i class="fas fa-user-shield"></i> Input petugas loket:</span> laporan langsung terverifikasi &amp; tayang.
        <?php else: ?>
          Laporan diverifikasi petugas sebelum ditayangkan. Data kontak Anda tidak pernah ditampilkan ke publik.
        <?php endif; ?>
      </p>
    </div>

    <!-- 1. Jenis & kategori -->
    <fieldset class="space-y-3">
      <legend class="text-xs font-black text-amber-400 uppercase tracking-wider mb-2">1. Jenis Laporan</legend>
      <div class="grid grid-cols-2 gap-3">
        <?php foreach (['LOST' => ['Saya kehilangan', 'fa-circle-exclamation', 'rose'], 'FOUND' => ['Saya menemukan', 'fa-hand-holding-heart', 'emerald']] as $key => [$text, $icon, $c]): ?>
          <label class="cursor-pointer">
            <input type="radio" name="report_type" value="<?= $key ?>" class="peer sr-only" <?= $type === $key ? 'checked' : '' ?>>
            <div class="p-4 rounded-xl border border-slate-700 peer-checked:border-<?= $c ?>-400 peer-checked:bg-<?= $c ?>-950/40 text-sm font-bold text-slate-200 flex items-center gap-2">
              <i class="fas <?= $icon ?> text-<?= $c ?>-300"></i> <?= $text ?>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
      <div>
        <label class="<?= $label ?>" for="lfCategory">Kategori objek</label>
        <select name="item_category" id="lfCategory" class="<?= $input ?>">
          <?php foreach (LostFound::CATEGORIES as $key => [$text]): ?>
            <option value="<?= $key ?>" <?= $category === $key ? 'selected' : '' ?>><?= $text ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </fieldset>

    <!-- 2. Detail objek -->
    <fieldset class="space-y-3">
      <legend class="text-xs font-black text-amber-400 uppercase tracking-wider mb-2">2. Detail &amp; Ciri-ciri</legend>
      <div>
        <label class="<?= $label ?>" for="lfTitle">Judul singkat</label>
        <input type="text" id="lfTitle" name="title" required maxlength="150" value="<?= $v('title') ?>" class="<?= $input ?>"
               placeholder="Contoh: Honda Beat hitam hilang di parkiran pasar">
      </div>

      <!-- Kendaraan -->
      <div class="grid sm:grid-cols-3 gap-3" data-cats="KENDARAAN">
        <div><label class="<?= $label ?>">Plat nomor</label><input name="plate_number" maxlength="20" value="<?= $v('plate_number') ?>" class="<?= $input ?> uppercase font-mono" placeholder="B 1234 XYZ"></div>
        <div><label class="<?= $label ?>">Nomor rangka</label><input name="chassis_number" maxlength="40" value="<?= $v('chassis_number') ?>" class="<?= $input ?> uppercase font-mono"></div>
        <div><label class="<?= $label ?>">Nomor mesin</label><input name="engine_number" maxlength="40" value="<?= $v('engine_number') ?>" class="<?= $input ?> uppercase font-mono"></div>
      </div>

      <!-- Barang / dokumen -->
      <div data-cats="BARANG_BERHARGA DOKUMEN LAINNYA">
        <label class="<?= $label ?>">IMEI / nomor seri / nomor dokumen <span class="text-slate-500">(opsional)</span></label>
        <input name="serial_number" maxlength="60" value="<?= $v('serial_number') ?>" class="<?= $input ?> font-mono" placeholder="Contoh: IMEI 35xxxxxxxxxxxxx atau No. KTP/SIM/Paspor">
      </div>

      <!-- Orang -->
      <div class="grid sm:grid-cols-3 gap-3" data-cats="ORANG">
        <div><label class="<?= $label ?>">Nama orang</label><input name="person_name" maxlength="100" value="<?= $v('person_name') ?>" class="<?= $input ?>" placeholder="Bila diketahui"></div>
        <div><label class="<?= $label ?>">Perkiraan usia</label><input type="number" name="person_age" min="0" max="120" value="<?= $v('person_age') ?>" class="<?= $input ?>"></div>
        <div><label class="<?= $label ?>">Jenis kelamin</label>
          <select name="person_gender" class="<?= $input ?>">
            <option value="">-</option>
            <option value="L" <?= ($old['person_gender'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= ($old['person_gender'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
      </div>

      <!-- Hewan -->
      <div data-cats="HEWAN">
        <label class="<?= $label ?>">Jenis hewan</label>
        <input name="animal_species" maxlength="60" value="<?= $v('animal_species') ?>" class="<?= $input ?>" placeholder="Contoh: Kucing persia, anjing golden retriever, sapi">
      </div>

      <div class="grid sm:grid-cols-2 gap-3" data-cats="KENDARAAN BARANG_BERHARGA DOKUMEN HEWAN LAINNYA">
        <div><label class="<?= $label ?>">Merk / tipe / ras</label><input name="brand" maxlength="100" value="<?= $v('brand') ?>" class="<?= $input ?>" placeholder="Contoh: Honda Beat 2021, iPhone 13"></div>
        <div><label class="<?= $label ?>">Warna</label><input name="color" maxlength="50" value="<?= $v('color') ?>" class="<?= $input ?>"></div>
      </div>

      <div>
        <label class="<?= $label ?>" for="lfDesc">Deskripsi &amp; ciri khusus</label>
        <textarea id="lfDesc" name="description" required rows="5" maxlength="5000" class="<?= $input ?>"
                  placeholder="Ciri fisik, pakaian terakhir, tanda khusus, isi tas/dompet, stiker, goresan, kalung hewan, dsb."><?= $v('description') ?></textarea>
        <p class="text-[10px] text-slate-500 mt-1" data-cats="BARANG_BERHARGA DOKUMEN LAINNYA KENDARAAN HEWAN">
          <i class="fas fa-shield-halved"></i> Untuk laporan temuan, sebaiknya rahasiakan satu ciri unik — petugas akan menanyakannya kepada pengaju klaim.
        </p>
      </div>

      <div>
        <label class="<?= $label ?>">Foto (maks. 4, JPG/PNG/WEBP)</label>
        <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple
               class="w-full text-xs text-slate-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-amber-500 file:text-slate-950 file:font-bold">
      </div>
    </fieldset>

    <!-- 3. Waktu & lokasi -->
    <fieldset class="space-y-3">
      <legend class="text-xs font-black text-amber-400 uppercase tracking-wider mb-2">3. Waktu &amp; Lokasi</legend>
      <div class="grid sm:grid-cols-3 gap-3">
        <div>
          <label class="<?= $label ?>" for="lfDate"><span data-type-text="LOST">Tanggal hilang</span><span data-type-text="FOUND">Tanggal ditemukan</span></label>
          <input type="date" id="lfDate" name="event_date" required max="<?= date('Y-m-d') ?>" value="<?= $v('event_date', date('Y-m-d')) ?>" class="<?= $input ?>">
        </div>
        <div class="sm:col-span-2">
          <label class="<?= $label ?>" for="lfLoc">Lokasi (alamat / patokan)</label>
          <div class="flex gap-2">
            <input type="text" id="lfLoc" name="event_location" required maxlength="255" value="<?= $v('event_location') ?>" class="<?= $input ?>">
            <button type="button" id="lfGeo" title="Gunakan lokasi saya" class="px-3 rounded-xl bg-[#1B1610] border border-[#3B2F1C] text-amber-400"><i class="fas fa-location-crosshairs"></i></button>
          </div>
          <input type="hidden" name="latitude" id="lfLat" value="<?= $v('latitude') ?>">
          <input type="hidden" name="longitude" id="lfLng" value="<?= $v('longitude') ?>">
          <p id="lfGeoInfo" class="text-[10px] text-emerald-400 mt-1 <?= empty($old['latitude']) ? 'hidden' : '' ?>"><i class="fas fa-check"></i> Koordinat GPS terlampir</p>
        </div>
      </div>
      <div data-type="FOUND">
        <label class="<?= $label ?>">Barang temuan saat ini disimpan di</label>
        <input name="custody_location" maxlength="255" value="<?= $v('custody_location') ?>" class="<?= $input ?>"
               placeholder="<?= $isOfficer ? 'Contoh: Ruang barang temuan SPKT Polres …' : 'Contoh: Saya simpan sendiri / sudah diserahkan ke Polsek …' ?>">
      </div>
    </fieldset>

    <!-- 4. Pelapor -->
    <fieldset class="space-y-3">
      <legend class="text-xs font-black text-amber-400 uppercase tracking-wider mb-2">4. Data Pelapor <span class="normal-case text-slate-500 font-semibold">(rahasia, hanya untuk petugas)</span></legend>
      <div class="grid sm:grid-cols-3 gap-3">
        <div><label class="<?= $label ?>">Nama lengkap</label><input name="reporter_name" required maxlength="100" value="<?= $v('reporter_name') ?>" class="<?= $input ?>"></div>
        <div><label class="<?= $label ?>">Nomor HP / WhatsApp</label><input name="reporter_phone" required inputmode="tel" maxlength="20" value="<?= $v('reporter_phone') ?>" class="<?= $input ?>" placeholder="08xxxxxxxxxx"></div>
        <div><label class="<?= $label ?>">NIK <span class="text-slate-500">(wajib untuk SKTLK)</span></label><input name="reporter_nik" inputmode="numeric" maxlength="16" value="<?= $v('reporter_nik') ?>" class="<?= $input ?> font-mono"></div>
      </div>
    </fieldset>

    <div class="p-3 rounded-xl bg-red-950/40 border border-red-800/60 text-[11px] text-red-200">
      <i class="fas fa-triangle-exclamation mr-1"></i> Untuk orang hilang yang membutuhkan pencarian segera, anak di bawah umur, atau kendaraan yang dirampas dengan kekerasan,
      hubungi <a href="tel:110" class="font-black underline">Call Center 110</a> dan buat laporan polisi di SPKT. Laporan palsu dapat dikenai sanksi pidana (Pasal 220 KUHP).
    </div>

    <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-black text-sm">
      <i class="fas fa-paper-plane mr-1"></i> Kirim Laporan
    </button>
  </form>
</div>

<script>
(function () {
  var form = document.getElementById('lfForm');
  var cat = document.getElementById('lfCategory');
  function sync() {
    var c = cat.value;
    var t = (form.querySelector('input[name=report_type]:checked') || {}).value || 'LOST';
    form.querySelectorAll('[data-cats]').forEach(function (el) {
      el.classList.toggle('hidden', el.getAttribute('data-cats').split(' ').indexOf(c) === -1);
    });
    form.querySelectorAll('[data-type]').forEach(function (el) { el.classList.toggle('hidden', el.getAttribute('data-type') !== t); });
    form.querySelectorAll('[data-type-text]').forEach(function (el) { el.classList.toggle('hidden', el.getAttribute('data-type-text') !== t); });
  }
  cat.addEventListener('change', sync);
  form.querySelectorAll('input[name=report_type]').forEach(function (r) { r.addEventListener('change', sync); });
  sync();

  document.getElementById('lfGeo').addEventListener('click', function () {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function (pos) {
      document.getElementById('lfLat').value = pos.coords.latitude.toFixed(6);
      document.getElementById('lfLng').value = pos.coords.longitude.toFixed(6);
      document.getElementById('lfGeoInfo').classList.remove('hidden');
    });
  });
})();
</script>
