// Merekam video demo alur SIGAP-POLRI per adegan (Playwright), dengan caption & kursor.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://localhost/SIGAP-Polda/public';
const ONLY = !!process.env.TICKET;
const OUT = path.join(__dirname, ONLY ? 'video2' : 'video');
const EXE = path.join(process.env.LOCALAPPDATA, 'ms-playwright', 'chromium-1243', 'chrome-win64', 'chrome.exe');
const W = 1280, H = 720;
fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

const scenes = [];   // {name, file, start, end, captions:[{t0,t1,step,title,text}]}
const state = ONLY ? { ticket: process.env.TICKET, pin: process.env.PIN } : {};
const sleep = ms => new Promise(r => setTimeout(r, ms));

const OVERLAY = `
(() => {
  const css = \`
  #demoCursor{position:fixed;z-index:2147483647;width:22px;height:22px;border-radius:50%;
    background:rgba(250,204,21,.55);border:2px solid #fff;box-shadow:0 0 10px rgba(0,0,0,.6);
    pointer-events:none;transform:translate(-50%,-50%);transition:left .12s,top .12s;left:-50px;top:-50px}
  #demoCursor.click{background:rgba(239,68,68,.8);width:30px;height:30px}
  #demoCap{position:fixed;z-index:2147483646;left:50%;bottom:18px;transform:translateX(-50%);width:min(1120px,92vw);
    background:rgba(8,12,24,.92);border:1px solid rgba(250,204,21,.6);border-radius:14px;padding:12px 18px;
    color:#f8fafc;font:15px/1.45 'Segoe UI',system-ui,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.5);pointer-events:none}
  #demoCap b.s{display:inline-block;background:#facc15;color:#111;border-radius:6px;padding:1px 8px;margin-right:8px;font-size:12px;font-weight:800}
  #demoCap b.t{color:#fde68a;font-size:16px}
  #demoCap div{margin-top:4px;color:#e2e8f0}
  #demoRole{position:fixed;z-index:2147483646;right:14px;top:14px;background:#7f1d1d;color:#fff;border:1px solid #fca5a5;
    border-radius:999px;padding:5px 14px;font:700 13px 'Segoe UI',sans-serif;pointer-events:none;box-shadow:0 4px 14px rgba(0,0,0,.4)}\`;
  const add = () => {
    if (document.getElementById('demoCursor') || !document.body) return;
    const st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);
    const c = document.createElement('div'); c.id = 'demoCursor'; document.body.appendChild(c);
    document.addEventListener('mousemove', e => { c.style.left = e.clientX + 'px'; c.style.top = e.clientY + 'px'; }, true);
    document.addEventListener('mousedown', () => c.classList.add('click'), true);
    document.addEventListener('mouseup', () => setTimeout(() => c.classList.remove('click'), 150), true);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add); else add();
})();`;

let scene = null;
let track;

async function startScene(browser, name, role) {
  const ctx = await browser.newContext({
    viewport: { width: W, height: H },
    recordVideo: { dir: OUT, size: { width: W, height: H } },
    locale: 'id-ID',
    geolocation: { latitude: -6.2088, longitude: 106.8456 }, permissions: ['geolocation'],
  });
  await ctx.addInitScript(OVERLAY);
  const page = await ctx.newPage();
  page.on('dialog', d => d.accept().catch(() => {}));
  page.setDefaultTimeout(15000);
  scene = { name, role, ctx, page, start: Date.now(), captions: [], cur: null };
  scenes.push(scene);
  return page;
}

async function endScene() {
  closeCaption();
  scene.end = Date.now();
  const video = scene.page.video();
  await scene.ctx.close();
  scene.file = await video.path();
  delete scene.ctx; delete scene.page; delete scene.cur;
}

function closeCaption() {
  if (scene && scene.cur) { scene.cur.t1 = (Date.now() - scene.start) / 1000; scene.captions.push(scene.cur); scene.cur = null; }
}

// Tampilkan caption di halaman (dipasang ulang setiap navigasi melalui showCaption()).
async function caption(page, step, title, text, hold = 0) {
  closeCaption();
  scene.cur = { t0: (Date.now() - scene.start) / 1000, step, title, text };
  await paintCaption(page);
  if (hold) await sleep(hold);
}
async function paintCaption(page) {
  const c = scene.cur; const role = scene.role;
  await page.evaluate(({ c, role }) => {
    const esc = s => String(s).replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
    let el = document.getElementById('demoCap');
    if (!el) { el = document.createElement('div'); el.id = 'demoCap'; document.body.appendChild(el); }
    el.innerHTML = c ? `<b class="s">${esc(c.step)}</b><b class="t">${esc(c.title)}</b><div>${esc(c.text)}</div>` : '';
    el.style.display = c ? 'block' : 'none';
    if (role) {
      let r = document.getElementById('demoRole');
      if (!r) { r = document.createElement('div'); r.id = 'demoRole'; document.body.appendChild(r); }
      r.textContent = role;
    }
  }, { c, role }).catch(() => {});
}
async function go(page, url) {
  await page.goto(BASE + url, { waitUntil: 'load' });
  await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
  await paintCaption(page);
}
async function afterNav(page) {
  await page.waitForLoadState('load').catch(() => {});
  await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
  await paintCaption(page);
}
async function click(page, loc, wait = 700) {
  await loc.scrollIntoViewIfNeeded().catch(() => {});
  const b = await loc.boundingBox();
  if (b) await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2, { steps: 15 });
  await sleep(250);
  await loc.click();
  await sleep(wait);
}
async function type(page, loc, text, delay = 25) {
  await click(page, loc, 150);
  await loc.fill('');
  await loc.pressSequentially(text, { delay });
}
async function scrollTo(page, loc) {
  await loc.evaluate(el => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
  await sleep(900);
}
async function smoothScroll(page, px, ms = 1800) {
  await page.evaluate(({ px, ms }) => new Promise(res => {
    const s = window.scrollY, t0 = performance.now();
    const f = t => { const k = Math.min(1, (t - t0) / ms); window.scrollTo(0, s + px * (k < .5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2)); k < 1 ? requestAnimationFrame(f) : res(); };
    requestAnimationFrame(f);
  }), { px, ms });
}
async function titleCard(page, kicker, title, sub, ms = 3500) {
  await page.setContent(`<html><body style="margin:0;height:100vh;display:flex;align-items:center;justify-content:center;
    background:radial-gradient(circle at 30% 20%,#3b0a0a,#0b0f1c 70%);font-family:'Segoe UI',sans-serif;color:#fff">
    <div style="text-align:center;max-width:980px;padding:0 40px">
      <div style="color:#facc15;letter-spacing:.3em;font-weight:800;font-size:16px">${kicker}</div>
      <div style="font-size:46px;font-weight:900;margin:14px 0 18px;line-height:1.15">${title}</div>
      <div style="height:4px;width:160px;margin:0 auto 18px;background:linear-gradient(90deg,#dc2626 50%,#fff 50%)"></div>
      <div style="font-size:20px;color:#cbd5e1;line-height:1.5">${sub}</div>
    </div></body></html>`);
  await sleep(ms);
}
async function login(page, identifier) {
  await go(page, '/auth/login');
  await sleep(600);
  await type(page, page.locator('#identifier'), identifier, 20);
  await type(page, page.locator('#password'), 'password123', 30);
  await click(page, page.locator('form[action$="/auth/authenticate"] button[type=submit]'), 300);
  await afterNav(page);
  await sleep(1200);
}

(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true });
  let page;
  const step = async (label, fn) => {
    try { await fn(); }
    catch (e) {
      console.error(`[GAGAL] ${label}: ${e.message.split('\n')[0]}`);
      if (scene && scene.page) await scene.page.screenshot({ path: path.join(OUT, `err-${label.replace(/\W+/g, '_')}.png`) }).catch(() => {});
    }
  };

  if (!ONLY) {
  // ===== 0. Pembuka & portal publik =====
  page = await startScene(browser, '00-pembuka', null);
  await titleCard(page, 'VIDEO DEMO ALUR PROSES', 'SIGAP-POLRI<br><span style="font-size:28px;font-weight:600">Sistem Integrasi Pengaduan &amp; Dumas Terpadu Polri</span>',
    'Dari laporan warga → verifikasi SPKT → atensi pimpinan → disposisi Satker → penyidikan &amp; SP2HP-E → laporan selesai &amp; survei kepuasan', 5000);
  scene.role = 'Warga / Publik';
  await step('landing', async () => {
    await go(page, '/');
    await caption(page, 'Langkah 1', 'Portal Publik Warga', 'Halaman depan SIGAP: akses pengaduan satu pintu, alur penanganan yang transparan, dan hotline darurat 110.', 3500);
    await smoothScroll(page, 650); await sleep(2000);
    await smoothScroll(page, 700); await sleep(2000);
    await smoothScroll(page, 700); await sleep(1800);
  });
  await endScene();

  // ===== 1. Warga membuat laporan =====
  page = await startScene(browser, '01-buat-laporan', 'Warga Pelapor');
  await step('form-laporan', async () => {
    await go(page, '/report/create');
    await caption(page, 'Langkah 2', 'Formulir Pengaduan Masyarakat', 'Warga mengisi identitas pelapor: NIK, nama, nomor WhatsApp, dan tingkat kedaruratan.', 1500);
    await type(page, page.locator('#reporter_nik'), '3171012304950001');
    await type(page, page.locator('#reporter_name'), 'Budi Santoso');
    await type(page, page.locator('#reporter_phone'), '081298765432');
    await page.selectOption('#urgency_level', 'HIGH'); await sleep(800);
    await scrollTo(page, page.locator('#is_anonymous'));
    await caption(page, 'Langkah 2', 'Perlindungan Whistleblower', 'Opsional: pelapor dapat merahasiakan identitasnya. Pada demo ini identitas tetap ditampilkan.', 3000);
    await scrollTo(page, page.locator('#category_id'));
    await caption(page, 'Langkah 2', 'Detail Kejadian', 'Pilih kategori perkara, isi judul, tanggal & jam kejadian, serta kronologi singkat.', 800);
    await page.selectOption('#category_id', { index: 1 }); await sleep(700);
    await type(page, page.locator('#title'), 'Pencurian Sepeda Motor di Depan Minimarket', 20);
    await page.locator('#incident_date').fill(new Date(Date.now() - 864e5).toISOString().slice(0, 10));
    await page.locator('#incident_time').fill('21:30');
    await type(page, page.locator('#description'),
      'Sepeda motor Honda Beat hitam B 1234 XYZ yang saya parkir di depan minimarket hilang sekitar pukul 21.30 WIB. Rekaman CCTV toko memperlihatkan dua orang pelaku menggunakan kunci T.', 8);
    await scrollTo(page, page.locator('#incident_location'));
    await caption(page, 'Langkah 2', 'Lokasi TKP & Pinpoint Peta', 'Alamat TKP diisi, lalu titik koordinat ditandai langsung pada peta interaktif.', 600);
    await type(page, page.locator('#incident_location'), 'Jl. Kemang Raya No. 12, Mampang Prapatan, Jakarta Selatan', 15);
    await scrollTo(page, page.locator('#tkpMap'));
    const mb = await page.locator('#tkpMap').boundingBox();
    await page.mouse.move(mb.x + mb.width * 0.52, mb.y + mb.height * 0.55, { steps: 15 });
    await page.mouse.click(mb.x + mb.width * 0.52, mb.y + mb.height * 0.55);
    await sleep(1800);
    await page.locator('#proof_files').setInputFiles(path.join(__dirname, 'bukti-cctv.png')).catch(() => {});
    await scrollTo(page, page.locator('#reportForm button[type=submit]'));
    await caption(page, 'Langkah 2', 'Unggah Bukti & Kirim', 'Bukti foto/rekaman dilampirkan, kemudian laporan dikirim ke Command Center SPKT.', 2500);
    await click(page, page.locator('#reportForm button[type=submit]'), 300);
    await afterNav(page);
    state.ticket = (await page.locator('#ticketTxt').innerText()).trim();
    state.pin = (await page.locator('div.font-mono.tracking-widest').first().innerText()).trim();
    console.log('Tiket:', state.ticket, 'PIN:', state.pin);
    await caption(page, 'Langkah 3', 'Nomor Tiket & PIN Pelacakan Terbit', `Sistem menerbitkan Nomor Tiket ${state.ticket} dan PIN rahasia 6 digit untuk melacak perkembangan laporan.`, 5500);
  });
  await endScene();

  // ===== 2. Warga melacak status awal =====
  page = await startScene(browser, '02-lacak-awal', 'Warga Pelapor');
  track = async (cap) => {
    await go(page, '/report/track');
    await caption(page, ...cap, 800);
    await type(page, page.locator('#ticketInput'), state.ticket, 30);
    await type(page, page.locator('#pinInput'), state.pin, 60);
    await click(page, page.locator('form[action$="/report/track"] button[type=submit]'), 300);
    await afterNav(page);
  };
  await step('lacak-awal', async () => {
    await track(['Langkah 4', 'Lacak Status Laporan', 'Warga memasukkan Nomor Tiket & PIN untuk memantau progres laporan kapan saja tanpa perlu login.']);
    await caption(page, 'Langkah 4', 'Status: Menunggu Verifikasi', 'Laporan tercatat dan menunggu diverifikasi petugas SPKT. Linimasa penanganan tampil secara transparan.', 3000);
    await smoothScroll(page, 500); await sleep(2500);
  });
  await endScene();

  // ===== 3. Humas: aduan media sosial =====
  page = await startScene(browser, '03-humas-medsos', 'Humas / Patroli Siber');
  await step('humas', async () => {
    await caption(page, 'Langkah 5', 'Kanal Media Sosial (Omnichannel)', 'Selain portal, aduan juga masuk dari X/Twitter, Instagram, Facebook & TikTok. Petugas Humas login ke backoffice.', 0);
    await login(page, 'humas@sigap.polri.go.id');
    await go(page, '/social');
    await caption(page, 'Langkah 5', 'Workspace Social Media Ingestion', 'Mention & postingan viral yang menandai akun Polri dikumpulkan di satu layar beserta sentimennya.', 3500);
    await smoothScroll(page, 350); await sleep(1500);
    const btn = page.locator('button[onclick^="openConvertModal"]').first();
    if (await btn.count()) {
      await caption(page, 'Langkah 5', 'Konversi 1-Klik Menjadi Tiket Resmi', 'Postingan yang valid dikonversi menjadi tiket laporan resmi, lalu masuk antrean verifikasi SPKT.', 600);
      await click(page, btn, 1500);
      await click(page, page.locator('#modalConvert button[type=submit]'), 300);
      await afterNav(page);
      await sleep(3000);
    }
  });
  await endScene();

  // ===== 4. SPKT verifikasi & disposisi =====
  page = await startScene(browser, '04-spkt', 'Operator SPKT');
  await step('spkt', async () => {
    await caption(page, 'Langkah 6', 'Meja Verifikasi SPKT', 'Operator SPKT (Command Center) login untuk memverifikasi laporan yang masuk.', 0);
    await login(page, 'spkt@sigap.polri.go.id');
    await go(page, '/spkt');
    await caption(page, 'Langkah 6', 'Antrean Laporan Masuk', 'Semua laporan dari portal, media sosial & live chat masuk ke sini. Petugas dapat memfilter dan mencari tiket.', 3500);
    await type(page, page.locator('input[name=search]'), state.ticket, 30);
    await click(page, page.locator('form[action$="/spkt"] button[type=submit]'), 300);
    await afterNav(page); await sleep(1500);
    await caption(page, 'Langkah 6', 'Verifikasi & Disposisi', 'Laporan layak ditindaklanjuti didisposisikan ke Satker; laporan hoaks/tidak layak dapat ditolak beserta alasannya.', 1500);
    const row = page.locator('tr', { hasText: state.ticket });
    await click(page, row.locator('button[onclick^="openDispatchModal"]'), 1200);
    await page.selectOption('#dispatch_satker_id', '2'); await sleep(1000);
    await caption(page, 'Langkah 6', 'Disposisi ke Dittipidum Bareskrim', 'Perkara pencurian (kriminal umum) diteruskan ke Dittipidum Bareskrim disertai catatan instruksi awal.', 2500);
    await click(page, page.locator('#modalDispatch button[type=submit]'), 300);
    await afterNav(page);
    await caption(page, 'Langkah 6', 'Disposisi Terkirim', 'Status laporan berubah menjadi terdisposisi dan otomatis masuk ke Inbox Satker tujuan.', 3500);
  });
  await endScene();

  // ===== 5. Kapolri: dashboard eksekutif & atensi =====
  page = await startScene(browser, '05-kapolri', 'Kapolri (Pimpinan)');
  await step('kapolri', async () => {
    await caption(page, 'Langkah 7', 'Situational Command Center', 'Pimpinan memantau seluruh perkara secara real-time melalui dashboard eksekutif.', 0);
    await login(page, 'kapolri@sigap.polri.go.id');
    await go(page, '/executive');
    await caption(page, 'Langkah 7', 'KPI & Peta Sebaran Kejadian (GIS)', 'Clearance rate, waktu respons, watchlist Satker yang melewati SLA, dan peta heatmap titik kejadian.', 4000);
    await smoothScroll(page, 450); await sleep(3000);
    await smoothScroll(page, 450); await sleep(2500);
    await caption(page, 'Langkah 7', 'Beri Atensi Pimpinan', 'Untuk perkara yang perlu dikawal, pimpinan memberi atensi dengan instruksi tertulis kepada Satker.', 600);
    await click(page, page.locator('button[onclick*="modalAttentionDirective"][onclick*="remove"]').first(), 1200);
    const val = await page.locator('#attention_report_id option', { hasText: state.ticket }).first().getAttribute('value');
    await page.selectOption('#attention_report_id', val); await sleep(800);
    await type(page, page.locator('#modalAttentionDirective textarea[name=directive_text]'),
      'Segera ungkap pelaku curanmor dan amankan barang bukti. Laporkan perkembangan dalam 1x24 jam.', 15);
    await click(page, page.locator('#modalAttentionDirective button[type=submit]'), 300);
    await afterNav(page);
    await caption(page, 'Langkah 7', 'Atensi Tercatat', 'Perkara ditandai prioritas (bintang merah) dan instruksi masuk ke inbox Satker penanggung jawab.', 4000);
  });
  await endScene();

  // ===== 6. Kanit: inbox satker & sprintgas =====
  page = await startScene(browser, '06-kanit', 'Kanit Dittipidum');
  await step('kanit', async () => {
    await caption(page, 'Langkah 8', 'Inbox Satuan Kerja', 'Kanit Dittipidum menerima disposisi SPKT beserta instruksi atensi dari pimpinan.', 0);
    await login(page, 'kanit.tipidum@sigap.polri.go.id');
    await go(page, '/satker');
    await caption(page, 'Langkah 8', 'Instruksi Atensi & Disposisi Masuk', 'Instruksi pimpinan tampil di bagian atas; daftar perkara terdisposisi berada di bawahnya.', 4000);
    const row = page.locator('tr', { hasText: state.ticket });
    await scrollTo(page, row);
    await caption(page, 'Langkah 8', 'Terbitkan Surat Perintah Tugas (Sprintgas)', 'Kanit menunjuk Penyidik Pelaksana; nomor Sprintgas dibuat otomatis oleh sistem.', 800);
    await click(page, row.locator('button[onclick^="openAssignModal"]'), 1200);
    await page.selectOption('#assign_investigator_id', '7'); await sleep(1500);
    await click(page, page.locator('#modalAssign button[type=submit]'), 300);
    await afterNav(page);
    await caption(page, 'Langkah 8', 'Penyidik Ditugaskan', 'Perkara kini menjadi tanggung jawab Ipda Rian Prasetya sebagai penyidik pelaksana.', 3500);
  });
  await endScene();

  }
  // ===== 7. Penyidik: lembar kerja, klarifikasi, SP2HP =====
  page = await startScene(browser, '07-penyidik', 'Penyidik Pelaksana');
  await step('penyidik', async () => {
    await caption(page, 'Langkah 9', 'Workspace Penyidik', 'Penyidik melihat daftar perkara aktif yang ditugaskan kepadanya.', 0);
    await login(page, 'penyidik.rian@sigap.polri.go.id');
    await go(page, '/investigator');
    await sleep(2500);
    const href = await page.evaluate(t => [...document.querySelectorAll('a[href*="/investigator/detail/"]')]
      .find(a => { let el = a; while (el && !(el.innerText || '').includes(t)) el = el.parentElement; return el && el.querySelectorAll('a[href*="/investigator/detail/"]').length === 1; })
      ?.getAttribute('href'), state.ticket);
    const link = page.locator(`a[href="${href}"]`).first();
    state.caseUrl = href.replace(/^.*\/public/, '');
    await click(page, link, 300); await afterNav(page);
    await caption(page, 'Langkah 9', 'Lembar Kerja Perkara', 'Detail laporan, bukti, lokasi, dan riwayat penanganan tersaji dalam satu lembar kerja.', 4000);
    const msg = page.locator('form[action$="/investigator/sendMessage"] textarea[name=message_text]');
    await scrollTo(page, msg);
    await caption(page, 'Langkah 9', 'Ruang Klarifikasi Dua Arah', 'Penyidik dapat langsung meminta keterangan tambahan kepada pelapor tanpa harus memanggil ke kantor.', 400);
    await type(page, msg, 'Selamat siang Pak Budi, mohon kirimkan foto STNK dan rekaman CCTV lengkap dari pihak minimarket.', 12);
    await click(page, page.locator('form[action$="/investigator/sendMessage"] button[type=submit]'), 300);
    await afterNav(page); await sleep(1500);
    const pub = page.locator('form[action$="/investigator/updateSp2hp"] textarea[name=public_notes]');
    await scrollTo(page, pub);
    await caption(page, 'Langkah 10', 'Penerbitan SP2HP Elektronik', 'Catatan publik dikirim ke pelapor sebagai SP2HP; catatan internal bersifat rahasia dan hanya terlihat oleh petugas.', 400);
    await type(page, pub, 'Penyidik telah melakukan olah TKP dan memeriksa 2 orang saksi. Rekaman CCTV sedang dianalisis untuk identifikasi pelaku.', 10);
    await type(page, page.locator('form[action$="/investigator/updateSp2hp"] textarea[name=internal_notes]'), 'Ciri pelaku cocok dengan residivis curanmor wilayah Mampang. Koordinasi dengan Resmob.', 10);
    await click(page, page.locator('form[action$="/investigator/updateSp2hp"] button[type=submit]'), 300);
    await afterNav(page);
    await caption(page, 'Langkah 10', 'SP2HP Terkirim ke Pelapor', 'Status perkara menjadi Dalam Penyelidikan dan pelapor dapat langsung melihat perkembangannya.', 3500);
  });
  await endScene();

  // ===== 8. Warga: lihat SP2HP, balas klarifikasi, cetak SP2HP =====
  page = await startScene(browser, '08-warga-sp2hp', 'Warga Pelapor');
  await step('warga-sp2hp', async () => {
    await track(['Langkah 11', 'Warga Memantau Perkembangan', 'Pelapor kembali membuka halaman lacak dengan Nomor Tiket & PIN.']);
    await caption(page, 'Langkah 11', 'Linimasa & SP2HP Elektronik', 'Setiap tahapan (verifikasi, disposisi, penugasan, SP2HP) tercatat lengkap dengan waktu dan nomor dokumennya.', 2500);
    await smoothScroll(page, 450); await sleep(3000);
    const msg = page.locator('form[action$="/report/sendMessage"] textarea[name=message_text]');
    await scrollTo(page, msg);
    await caption(page, 'Langkah 11', 'Membalas Permintaan Klarifikasi', 'Pelapor membalas pesan penyidik dan dapat melampirkan bukti tambahan.', 400);
    await type(page, msg, 'Baik Pak, foto STNK dan rekaman CCTV dari minimarket sudah saya lampirkan. Terima kasih.', 12);
    await click(page, page.locator('form[action$="/report/sendMessage"] button[type=submit]'), 300);
    await afterNav(page); await sleep(2000);
    await go(page, '/report/sp2hpPrint?ticket=' + encodeURIComponent(state.ticket));
    await caption(page, 'Langkah 11', 'Dokumen Resmi SP2HP', 'SP2HP dapat dipratinjau dan dicetak sebagai dokumen resmi berformat A4.', 3000);
    await smoothScroll(page, 500); await sleep(2500);
  });
  await endScene();

  // ===== 9. Penyidik menutup perkara =====
  page = await startScene(browser, '09-tutup-perkara', 'Penyidik Pelaksana');
  await step('tutup', async () => {
    await caption(page, 'Langkah 12', 'Penyelesaian Perkara', 'Setelah pelaku teridentifikasi dan barang bukti ditemukan, penyidik menutup perkara.', 0);
    await login(page, 'penyidik.rian@sigap.polri.go.id');
    await go(page, state.caseUrl);
    const res = page.locator('form[action$="/investigator/closeCase"] textarea[name=resolution_notes]');
    await scrollTo(page, res);
    await caption(page, 'Langkah 12', 'Tutup Perkara dengan Berita Acara', 'Penyidik mengisi ringkasan hasil penanganan; pelapor otomatis menerima pemberitahuan penyelesaian.', 400);
    await type(page, res, 'Pelaku berhasil diamankan dan sepeda motor B 1234 XYZ telah dikembalikan kepada pelapor. Berkas dilimpahkan ke JPU.', 10);
    await click(page, page.locator('form[action$="/investigator/closeCase"] button[type=submit]'), 300);
    await afterNav(page);
    await caption(page, 'Langkah 12', 'Perkara Selesai', 'Status perkara berubah menjadi Selesai / Ditutup.', 3500);
  });
  await endScene();

  // ===== 10. Warga: survei kepuasan =====
  page = await startScene(browser, '10-skm', 'Warga Pelapor');
  await step('skm', async () => {
    await track(['Langkah 13', 'Survei Kepuasan Masyarakat (SKM)', 'Setelah perkara selesai, pelapor diminta menilai layanan Polri.']);
    const fb = page.locator('form[action$="/report/submitFeedback"]');
    await scrollTo(page, fb);
    await caption(page, 'Langkah 13', 'Penilaian Bintang 1–5', 'Pelapor menilai kecepatan, keadilan, dan memberikan komentar sebagai bahan evaluasi kinerja Satker.', 600);
    await click(page, fb.locator('label:has(input[name=rating_stars][value="5"])').first(), 600).catch(() => {});
    await type(page, fb.locator('textarea[name=comments]'), 'Penanganan cepat, transparan dan penyidik sangat responsif. Terima kasih Polri!', 15);
    await click(page, fb.locator('button[type=submit]'), 300);
    await afterNav(page);
    await caption(page, 'Langkah 13', 'Terima Kasih', 'Nilai SKM tercatat dan menjadi indikator kinerja pada dashboard pimpinan.', 4000);
  });
  await endScene();

  if (!ONLY) {
  // ===== 11. Live chat warga & Omnichat petugas =====
  page = await startScene(browser, '11-livechat-warga', 'Warga / Publik');
  await step('livechat', async () => {
    await go(page, '/');
    await caption(page, 'Langkah 14', 'Live Chat SPKT', 'Warga juga dapat berkonsultasi langsung dengan petugas melalui widget Live Chat tanpa login.', 1500);
    await click(page, page.locator('#lcToggle'), 1000);
    await type(page, page.locator('#lcStart input[name=name]'), 'Rina Marlina', 25);
    await type(page, page.locator('#lcStart input[name=phone]'), '081377788899', 25);
    await type(page, page.locator('#lcStart textarea[name=message]'), 'Selamat malam, saya ingin melaporkan penipuan jual beli online. Bagaimana caranya?', 15);
    await click(page, page.locator('#lcStart button[type=submit]'), 2500);
    await caption(page, 'Langkah 14', 'Chat Masuk Antrean', 'Percakapan langsung masuk ke antrean konsol Omnichat petugas Command Center.', 3500);
  });
  scene.end = Date.now(); const lcScene = scene; closeCaption();
  const warga = { ctx: lcScene.ctx, page: lcScene.page };

  page = await startScene(browser, '12-omnichat', 'Operator SPKT');
  await step('omnichat', async () => {
    await caption(page, 'Langkah 15', 'Konsol Omnichat', 'Operator SPKT melihat antrean chat, mengambil alih percakapan, dan membalas warga.', 0);
    await login(page, 'spkt@sigap.polri.go.id');
    await go(page, '/omnichat'); await sleep(2500);
    await click(page, page.locator('#ocList li', { hasText: 'Rina' }).first(), 1500);
    await click(page, page.locator('#ocClaim'), 1500);
    await type(page, page.locator('#ocInput'), 'Selamat malam Ibu Rina, silakan ceritakan kronologinya. Percakapan ini dapat langsung kami jadikan tiket laporan resmi.', 10);
    await page.keyboard.press('Enter'); await sleep(2000);
    await caption(page, 'Langkah 15', 'Konversi Chat Menjadi Tiket', 'Jika memenuhi unsur, chat dikonversi menjadi tiket resmi (kanal LIVE_CHAT) dan mengikuti alur verifikasi yang sama.', 4500);
  });
  await endScene();
  lcScene.ctx = warga.ctx; lcScene.page = warga.page;
  { const v = warga.page.video(); await warga.ctx.close(); lcScene.file = await v.path(); delete lcScene.ctx; delete lcScene.page; }

  // ===== 12. Itwasum: audit & penutup =====
  page = await startScene(browser, '13-penutup', 'Itwasum (Pengawas)');
  await step('audit', async () => {
    await caption(page, 'Langkah 16', 'Pengawasan & Jejak Audit', 'Itwasum memantau kepatuhan: setiap akses dan tindakan personil tercatat di jejak audit.', 0);
    await login(page, 'itwasum@sigap.polri.go.id');
    await go(page, '/audit'); await sleep(3500);
    await smoothScroll(page, 400); await sleep(2000);
  });
  closeCaption(); scene.role = null;
  await titleCard(page, 'SIGAP-POLRI', 'Polisi Bersama Masyarakat',
    'Pengaduan satu pintu • Verifikasi & disposisi terukur • SP2HP elektronik transparan<br>Pengawasan pimpinan real-time • Survei kepuasan masyarakat', 5000);
  await endScene();

  }
  await browser.close();
  fs.writeFileSync(path.join(OUT, 'scenes.json'), JSON.stringify({ state, scenes }, null, 2));
  console.log('Selesai.', scenes.length, 'adegan');
})();
