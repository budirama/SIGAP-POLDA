/**
 * SIGAP-POLRI: Widget Live Chat warga (polling JSON ke /chat/*)
 * Pesan Asisten Virtual (from: 'bot') dapat membawa tautan & tombol cepat (meta).
 */
(function () {
  const root = document.getElementById('livechat');
  if (!root) return;

  const base = root.dataset.base;
  const csrf = root.dataset.csrf;
  const $ = (id) => document.getElementById(id);
  const panel = $('lcPanel'), toggle = $('lcToggle'), startForm = $('lcStart'), room = $('lcRoom');
  const list = $('lcMessages'), input = $('lcInput'), badge = $('lcBadge');

  const POLL_OPEN = 4000, POLL_HIDDEN = 15000;
  let lastId = 0, timer = null, active = false, unread = 0, busy = false;

  function setOpen(open) {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    if (open) {
      unread = 0;
      renderBadge();
      (active ? input : startForm.querySelector('input'))?.focus();
      list.scrollTop = list.scrollHeight;
    }
    try { sessionStorage.setItem('lcOpen', open ? '1' : '0'); } catch (e) { /* abaikan */ }
    schedule();
  }

  function renderBadge() {
    badge.hidden = unread === 0;
    badge.textContent = String(unread);
  }

  async function call(path, data) {
    const opts = { headers: { 'X-SIGAP-Poll': data ? '' : '1', 'X-CSRF-Token': csrf, 'Accept': 'application/json' } };
    if (data) {
      opts.method = 'POST';
      opts.body = new URLSearchParams(data);
    }
    const res = await fetch(base + path, opts);
    const json = await res.json().catch(() => ({ error: 'Gagal menghubungi server.' }));
    if (!res.ok) throw new Error(json.error || 'Terjadi kesalahan.');
    return json;
  }

  // Tautan dari basis pengetahuan: hanya path internal, https, atau tel:
  function linkButton(link) {
    const url = String(link.url || '');
    let href = null;
    if (url.startsWith('/')) href = base + url;
    else if (/^(https:\/\/|tel:)/i.test(url)) href = url;
    if (!href) return null;
    const a = document.createElement('a');
    a.href = href;
    if (href.startsWith('https://')) { a.target = '_blank'; a.rel = 'noopener'; }
    a.className = 'mt-2 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-500 text-slate-950 font-bold text-[11px]';
    a.textContent = (link.label || 'Buka tautan') + ' →';
    return a;
  }

  function quickReplies(items) {
    const box = document.createElement('div');
    box.className = 'lc-quick flex flex-wrap gap-1.5 pl-1';
    items.forEach((q) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'px-2.5 py-1 rounded-full border border-amber-500/50 text-amber-200 hover:bg-amber-500/15 text-[11px]';
      b.textContent = q.label;
      b.dataset.intent = q.intent;
      b.addEventListener('click', () => sendMessage(q.label, q.intent));
      box.appendChild(b);
    });
    return box;
  }

  function bubble(m) {
    const wrap = document.createElement('div');
    if (m.from === 'system') {
      wrap.className = 'text-center text-[11px] text-slate-400 italic px-4';
      wrap.textContent = m.body;
      return wrap;
    }
    const mine = m.from === 'visitor';
    wrap.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');
    const box = document.createElement('div');
    box.className = 'max-w-[80%] rounded-2xl px-3 py-2 ' + (mine
      ? 'bg-amber-500/90 text-slate-950 rounded-br-sm'
      : 'bg-[#241D14] border border-[#3B2F1C] text-slate-100 rounded-bl-sm');
    if (!mine && m.name) {
      const who = document.createElement('p');
      who.className = 'text-[10px] font-bold text-amber-300 mb-0.5';
      who.textContent = m.name;
      box.appendChild(who);
    }
    const text = document.createElement('p');
    text.className = 'whitespace-pre-line break-words';
    text.textContent = m.body;
    box.appendChild(text);
    const link = m.meta && m.meta.link ? linkButton(m.meta.link) : null;
    if (link) box.appendChild(link);
    const time = document.createElement('p');
    time.className = 'text-[9px] mt-1 text-right ' + (mine ? 'text-slate-800' : 'text-slate-500');
    time.textContent = m.time;
    box.appendChild(time);
    wrap.appendChild(box);
    if (m.from === 'bot') {
      wrap.classList.add('lc-bot');
      if (m.name) box.firstChild.classList.replace('text-amber-300', 'text-sky-300');
    }
    return wrap;
  }

  function apply(state) {
    if (!state.active) {
      showStart();
      return;
    }
    active = true;
    startForm.hidden = true;
    room.hidden = false;
    room.style.display = 'flex';

    const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 60;
    const fresh = (state.messages || []).filter((m) => m.id > lastId);
    if (fresh.length) list.querySelectorAll('.lc-quick').forEach((el) => el.remove()); // tombol cepat lama tidak berlaku lagi
    fresh.forEach((m) => {
      lastId = m.id;
      list.appendChild(bubble(m));
      if ((m.from === 'agent' || m.from === 'bot') && panel.hidden) unread++;
    });
    const lastBot = fresh.length ? fresh[fresh.length - 1] : null;
    if (lastBot && lastBot.from === 'bot' && lastBot.meta && (lastBot.meta.quick_replies || []).length && state.status === 'BOT') {
      list.appendChild(quickReplies(lastBot.meta.quick_replies));
    }
    if (nearBottom) list.scrollTop = list.scrollHeight;
    renderBadge();

    const closed = state.status === 'CLOSED';
    $('lcStatus').textContent = closed ? 'Percakapan telah ditutup'
      : state.status === 'BOT' ? 'Asisten Virtual SIGAP • ketik "petugas" untuk bicara dengan petugas'
      : state.agent ? 'Terhubung dengan ' + state.agent
      : 'Menunggu petugas Command Center…';
    // Tombol tetap disembunyikan bila tombol cepat yang tampil sudah memuat "Bicara dengan petugas"
    const quickHasAgent = !!list.querySelector('.lc-quick [data-intent="PETUGAS"]');
    $('lcHandoff').hidden = state.status !== 'BOT' || quickHasAgent;
    $('lcSend').hidden = closed;
    $('lcEnd').textContent = closed ? 'Mulai chat baru' : 'Akhiri chat';

    $('lcTicket').hidden = !state.ticket;
    if (state.ticket) $('lcTicketNo').textContent = state.ticket;
  }

  function showStart() {
    active = false;
    lastId = 0;
    list.innerHTML = '';
    room.hidden = true;
    room.style.display = 'none';
    startForm.hidden = false;
    $('lcHandoff').hidden = true;
    $('lcStatus').textContent = root.dataset.bot === '1' ? 'Asisten Virtual & petugas siap membantu 24 jam' : 'Petugas Command Center siap membantu';
  }

  async function poll() {
    if (busy) return schedule();
    try {
      apply(await call('/chat/poll?after=' + lastId));
      $('lcError').textContent = '';
    } catch (e) {
      $('lcError').textContent = e.message;
    }
    schedule();
  }

  function schedule() {
    clearTimeout(timer);
    if (!active && panel.hidden) return; // belum ada chat & panel tertutup: tidak perlu polling
    timer = setTimeout(poll, panel.hidden ? POLL_HIDDEN : POLL_OPEN);
  }

  startForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const err = $('lcStartError');
    err.classList.add('hidden');
    busy = true;
    try {
      apply(await call('/chat/start', Object.fromEntries(new FormData(startForm))));
      startForm.reset();
      input.focus();
    } catch (ex) {
      err.textContent = ex.message;
      err.classList.remove('hidden');
    } finally {
      busy = false;
      schedule();
    }
  });

  async function sendMessage(message, intent) {
    busy = true;
    input.disabled = true;
    try {
      const data = { message, after: lastId };
      if (intent) data.intent = intent;
      apply(await call('/chat/send', data));
      if (!intent) input.value = '';
      list.scrollTop = list.scrollHeight;
      $('lcError').textContent = '';
    } catch (ex) {
      $('lcError').textContent = ex.message;
    } finally {
      busy = false;
      input.disabled = false;
      input.focus();
      schedule();
    }
  }

  $('lcSend').addEventListener('submit', (e) => {
    e.preventDefault();
    const message = input.value.trim();
    if (message) sendMessage(message, null);
  });

  $('lcHandoffBtn').addEventListener('click', async () => {
    try {
      apply(await call('/chat/handoff', { after: lastId }));
      list.scrollTop = list.scrollHeight;
    } catch (ex) {
      $('lcError').textContent = ex.message;
    }
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      $('lcSend').requestSubmit();
    }
  });

  $('lcEnd').addEventListener('click', async () => {
    const closed = $('lcSend').hidden;
    if (!closed && !confirm('Akhiri percakapan ini?')) return;
    try { await call('/chat/end', {}); } catch (e) { /* tetap reset tampilan */ }
    showStart();
  });

  $('lcTicketBtn').addEventListener('click', async () => {
    try {
      const res = await call('/chat/ticket', {});
      window.location.href = base + res.url;
    } catch (ex) {
      $('lcError').textContent = ex.message;
    }
  });

  toggle.addEventListener('click', () => setOpen(panel.hidden));
  $('lcClose').addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) setOpen(false); });

  // Pulihkan percakapan yang sedang berjalan di sesi ini
  let reopen = false;
  try { reopen = sessionStorage.getItem('lcOpen') === '1'; } catch (e) { /* abaikan */ }
  call('/chat/poll?after=0').then((state) => {
    apply(state);
    if (reopen) setOpen(true); else schedule();
  }).catch(() => { if (reopen) setOpen(true); });
})();
