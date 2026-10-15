const { execFileSync } = require('child_process');
const fs = require('fs');
const { scenes } = require('./video/scenes.json');
const inputs = [], parts = [];
let off = 0; const srt = []; let n = 1;
const ts = t => { const ms = Math.round(t * 1000); const h = String(Math.floor(ms / 36e5)).padStart(2, '0'), m = String(Math.floor(ms / 6e4) % 60).padStart(2, '0'), s = String(Math.floor(ms / 1e3) % 60).padStart(2, '0'); return `${h}:${m}:${s},${String(ms % 1000).padStart(3, '0')}`; };
scenes.forEach((s, i) => {
  const dur = (s.end - s.start) / 1000 + 0.3;
  inputs.push('-i', s.file);
  parts.push(`[${i}:v]trim=0:${dur.toFixed(2)},setpts=PTS-STARTPTS,fps=30,format=yuv420p[v${i}]`);
  s.captions.forEach(c => srt.push(`${n++}\n${ts(off + c.t0)} --> ${ts(off + Math.min(c.t1, dur))}\n[${c.step}] ${c.title}\n${c.text}\n`));
  off += dur;
});
const fc = parts.join(';') + ';' + scenes.map((_, i) => `[v${i}]`).join('') + `concat=n=${scenes.length}:v=1:a=0[out]`;
execFileSync('ffmpeg', ['-y', '-loglevel', 'error', ...inputs, '-filter_complex', fc, '-map', '[out]', '-c:v', 'libx264', '-preset', 'medium', '-crf', '22', '-movflags', '+faststart', 'SIGAP-POLRI-Demo-Alur-Proses.mp4'], { stdio: 'inherit' });
fs.writeFileSync('SIGAP-POLRI-Demo-Alur-Proses.srt', srt.join('\n'));
console.log('durasi total ~', off.toFixed(1), 's');
