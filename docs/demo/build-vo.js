// Menggabungkan adegan + voice-over narasi (edge-tts). Segmen video ditahan (freeze) bila narasi lebih panjang.
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const { scenes } = require('./video/scenes.json');

const VOICE = process.env.VOICE || 'id-ID-ArdiNeural';
const RATE = process.env.RATE || '+8%';
const VO = path.join(__dirname, 'vo');
fs.mkdirSync(VO, { recursive: true });
const OUT = 'SIGAP-POLRI-Demo-Alur-Proses-VO';

// Penyesuaian ucapan agar TTS membaca dengan wajar.
const speak = s => s
  .replace(/LP-POLRI-\d{6}-\d{4}/g, 'laporan')
  .replace(/Nomor Tiket laporan/g, 'Nomor Tiket')
  .replace(/\(kanal LIVE_CHAT\)\s*/g, '')
  .replace(/1–5/g, 'satu sampai lima')
  .replace(/1x24/g, 'satu kali dua puluh empat')
  .replace(/X\/Twitter/g, 'X atau Twitter')
  .replace(/Survei Kepuasan Masyarakat \(SKM\)/g, 'Survei Kepuasan Masyarakat')
  .replace(/&/g, 'dan')
  .replace(/\s*\/\s*/g, ' atau ')
  .replace(/:/g, ',');

const dur = f => parseFloat(execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', f]).toString());

// Narasi tambahan untuk kartu judul pembuka & penutup.
const extra = {
  '00-pembuka': [{ t0: 0.4, title: '', text: 'Inilah SIGAP Polri, Sistem Integrasi Pengaduan dan Dumas Terpadu Polri. Video ini menunjukkan perjalanan satu laporan warga, dari pelaporan hingga perkara selesai.' }],
  '13-penutup': [{ t0: null, title: '', text: 'SIGAP Polri. Pengaduan satu pintu, penanganan terukur, dan transparan bagi masyarakat. Polisi bersama masyarakat.' }],
};

const segs = []; let k = 0;
scenes.forEach((s, si) => {
  const sd = (s.end - s.start) / 1000 + 0.3;
  let caps = s.captions.map(c => ({ t0: c.t0, title: c.title, text: c.text }));
  for (const e of extra[s.name] || []) caps.push({ ...e, t0: e.t0 ?? sd - 5.3 });
  caps = caps.filter(c => c.t0 < sd).sort((a, b) => a.t0 - b.t0);
  const cuts = [0, ...caps.map(c => c.t0), sd];
  for (let i = 0; i < cuts.length - 1; i++) {
    const a = cuts[i], b = cuts[i + 1];
    if (b - a < 0.05) continue;
    const seg = { si, a, b, cap: i > 0 ? caps[i - 1] : null, pad: 0 };
    if (seg.cap) {
      const f = path.join(VO, `n${String(k++).padStart(3, '0')}.mp3`);
      const txt = speak((seg.cap.title ? seg.cap.title + '. ' : '') + seg.cap.text);
      if (!fs.existsSync(f)) execFileSync('python', ['-m', 'edge_tts', '--voice', VOICE, `--rate=${RATE}`, '--text', txt, '--write-media', f]);
      seg.audio = f; seg.adur = dur(f);
      seg.pad = Math.max(0, seg.adur + 0.5 - (b - a));
    }
    segs.push(seg);
  }
});

// Bangun filter ffmpeg.
const inputs = scenes.flatMap(s => ['-i', s.file]);
const aIn = segs.filter(s => s.audio);
aIn.forEach(s => inputs.push('-i', s.audio));
const vf = [], af = [];
let t = 0; const srt = []; let n = 1;
const ts = x => { const ms = Math.round(x * 1000); const p = (v, l = 2) => String(v).padStart(l, '0'); return `${p(Math.floor(ms / 36e5))}:${p(Math.floor(ms / 6e4) % 60)}:${p(Math.floor(ms / 1e3) % 60)},${p(ms % 1000, 3)}`; };
segs.forEach((s, i) => {
  vf.push(`[${s.si}:v]trim=${s.a.toFixed(3)}:${s.b.toFixed(3)},setpts=PTS-STARTPTS,fps=30,format=yuv420p` +
    (s.pad > 0 ? `,tpad=stop_mode=clone:stop_duration=${s.pad.toFixed(3)}` : '') + `[s${i}]`);
  const len = s.b - s.a + s.pad;
  if (s.audio) {
    const ai = scenes.length + aIn.indexOf(s);
    const d = Math.round((t + 0.15) * 1000);
    af.push(`[${ai}:a]aresample=48000,adelay=${d}|${d}[a${af.length}]`);
    srt.push(`${n++}\n${ts(t)} --> ${ts(t + len)}\n${s.cap.title ? s.cap.title + '\n' : ''}${s.cap.text}\n`);
  }
  t += len;
});
const fc = [...vf, segs.map((_, i) => `[s${i}]`).join('') + `concat=n=${segs.length}:v=1:a=0[v]`,
  ...af, af.map((_, i) => `[a${i}]`).join('') + `amix=inputs=${af.length}:normalize=0:dropout_transition=0,apad,atrim=0:${t.toFixed(3)},loudnorm=I=-16:TP=-1.5[a]`].join(';');
fs.writeFileSync('vo-filter.txt', fc);
execFileSync('ffmpeg', ['-y', '-loglevel', 'error', ...inputs, '-/filter_complex', 'vo-filter.txt', '-map', '[v]', '-map', '[a]',
  '-c:v', 'libx264', '-preset', 'medium', '-crf', '22', '-c:a', 'aac', '-b:a', '160k', '-ar', '48000', '-movflags', '+faststart', OUT + '.mp4'], { stdio: 'inherit' });
fs.writeFileSync(OUT + '.srt', srt.join('\n'));
const frozen = segs.reduce((x, s) => x + s.pad, 0);
console.log(`segmen ${segs.length}, narasi ${aIn.length}, durasi ${t.toFixed(1)} s (freeze tambahan ${frozen.toFixed(1)} s)`);
