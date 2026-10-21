/**
 * SIGAP-POLRI: Konsol Omnichat agen Command Center (polling JSON ke /omnichat/*)
 */
(function () {
  const root = document.getElementById('omnichat');
  if (!root) return;

  const base = root.dataset.base;
  const csrf = root.dataset.csrf;
  const canAct = root.dataset.canAct === '1';
  const $ = (id) => document.getElementById(id);

  const LIST_EVERY = 5000, THREAD_EVERY = 3000;
  let scope = 'open', currentId = null, lastId = 0, conv = null;
  let listTimer = null, threadTimer = null, knownWaiting = null;

  try { scope = localStorage.getItem('ocScope') || 'open'; } catch (e) { /* abaikan */ }
  try { $('ocSound').checked = localStorage.getItem('ocSound') === '1'; } catch (e) { /* abaikan */ }

  async function call(path, data) {
    const opts = { headers: { 'X-SIGAP-Poll': data ? '' : '1', 'X-CSRF-Token': csrf, 'Accept': 'application/json' } };
    if (data) {
      opts.method = 'POST';
      opts.body = new URLSearchParams(data);
    }
    const res = await fetch(base + path, opts);
    if (res.redirected || !(res.headers.get('content-type') || '').includes('json')) {
      // Sesi berakhir / akses ditolak: kembali ke halaman login
      window.location.reload();
      throw new Error('Sesi berakhir.');
    }
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Terjadi kesalahan.');
    return json;
  }

  function beep() {
    if (!$('ocSound').checked) return;
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.frequency.value = 880;
      gain.gain.value = 0.08;
      osc.connect(gain).connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.18);
    } catch (e) { /* abaikan */ }
  }

  const STATUS = {
    BOT: ['Asisten Virtual', 'border-sky-600/60 text-sky-300 bg-sky-500/10'],
    WAITING: ['Menunggu', 'border-amber-500/60 text-amber-300 bg-amber-500/10'],
    ACTIVE: ['Aktif', 'border-emerald-600/60 text-emerald-300 bg-emerald-500/10'],
    CLOSED: ['Selesai', 'border-slate-600 text-slate-400 bg-slate-800/40'],
  };

  function renderTabs() {
    document.querySelectorAll('.oc-tab').forEach((t) => {
      const on = t.dataset.scope === scope;
      t.setAttribute('aria-selected', String(on));
      t.classList.toggle('bg-amber-500/15', on);
      t.classList.toggle('border-amber-500/60', on);
      t.classList.toggle('text-amber-300', on);
    });
  }

  function renderList(data) {
    $('ocWaiting').textContent = data.waiting;
    const nav = document.getElementById('navChatWaiting');
    if (nav) { nav.textContent = data.waiting; nav.hidden = data.waiting === 0; }
    if (knownWaiting !== null && data.waiting > knownWaiting) beep();
    knownWaiting = data.waiting;

    const ul = $('ocList');
    ul.innerHTML = '';
    $('ocListEmpty').hidden = data.conversations.length > 0;
    data.conversations.forEach((c) => {
      const li = document.createElement('li');
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'w-full text-left p-3 hover:bg-[#1B1610] transition-colors flex gap-3 ' + (c.id === currentId ? 'bg-amber-500/10' : '');
      btn.addEventListener('click', () => openThread(c.id));

      const avatar = document.createElement('span');
      avatar.className = 'h-9 w-9 flex-shrink-0 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 font-bold flex items-center justify-center';
      avatar.textContent = (c.visitor || '?').trim().charAt(0).toUpperCase();

      const body = document.createElement('span');
      body.className = 'flex-1 min-w-0';
      const top = document.createElement('span');
      top.className = 'flex items-center justify-between gap-2';
      const name = document.createElement('span');
      name.className = 'text-xs font-bold text-slate-100 truncate';
      name.textContent = c.visitor;
      const time = document.createElement('span');
      time.className = 'text-[10px] text-slate-500 flex-shrink-0';
      time.textContent = c.time;
      top.append(name, time);

      const preview = document.createElement('span');
      preview.className = 'block text-[11px] text-slate-400 truncate mt-0.5';
      preview.textContent = (c.last_sender === 'agent' ? 'Anda/Petugas: ' : c.last_sender === 'bot' ? 'Bot: ' : '') + c.last_body;

      const meta = document.createElement('span');
      meta.className = 'flex items-center gap-1.5 mt-1 text-[10px]';
      const [label, cls] = STATUS[c.status];
      const st = document.createElement('span');
      st.className = 'px-1.5 rounded-full border ' + cls;
      st.textContent = label;
      meta.appendChild(st);
      if (c.urgent && c.status !== 'CLOSED') {
        const ur = document.createElement('span');
        ur.className = 'px-1.5 rounded-full bg-red-700 text-white font-bold animate-pulse';
        ur.textContent = 'DARURAT';
        meta.appendChild(ur);
      }
      if (c.agent) {
        const ag = document.createElement('span');
        ag.className = 'text-slate-500 truncate';
        ag.textContent = c.mine ? 'oleh Anda' : c.agent;
        meta.appendChild(ag);
      }
      if (c.ticket) {
        const tk = document.createElement('span');
        tk.className = 'text-emerald-300 font-mono';
        tk.textContent = c.ticket;
        meta.appendChild(tk);
      }
      if (c.unanswered > 0 && c.status !== 'CLOSED') {
        const un = document.createElement('span');
        un.className = 'ml-auto px-1.5 rounded-full bg-red-600 text-white font-bold';
        un.textContent = c.unanswered;
        meta.appendChild(un);
      }
      body.append(top, preview, meta);
      btn.append(avatar, body);
      li.appendChild(btn);
      ul.appendChild(li);
    });
  }

  async function loadList() {
    clearTimeout(listTimer);
    try {
      renderList(await call('/omnichat/conversations?scope=' + encodeURIComponent(scope)));
    } catch (e) { /* coba lagi pada siklus berikutnya */ }
    listTimer = setTimeout(loadList, LIST_EVERY);
  }

  function bubble(m) {
    const wrap = document.createElement('div');
    if (m.from === 'system') {
      wrap.className = 'text-center text-[11px] text-slate-500 italic';
      wrap.textContent = m.body + ' · ' + m.time;
      return wrap;
    }
    const agent = m.from === 'agent', bot = m.from === 'bot';
    wrap.className = 'flex ' + (agent || bot ? 'justify-end' : 'justify-start');
    const box = document.createElement('div');
    box.className = 'max-w-[75%] rounded-2xl px-3 py-2 ' + (agent
      ? 'bg-amber-500/90 text-slate-950 rounded-br-sm'
      : bot ? 'bg-sky-950/60 border border-sky-800/60 text-slate-200 rounded-br-sm'
      : 'bg-[#241D14] border border-[#3B2F1C] text-slate-100 rounded-bl-sm');
    const who = document.createElement('p');
    who.className = 'text-[10px] font-bold mb-0.5 ' + (agent ? 'text-slate-800' : bot ? 'text-sky-300' : 'text-amber-300');
    who.textContent = agent ? (m.name || 'Petugas') : bot ? (m.name || 'Asisten Virtual') : (conv ? conv.visitor : 'Warga');
    const text = document.createElement('p');
    text.className = 'whitespace-pre-line break-words';
    text.textContent = m.body;
    const time = document.createElement('p');
    time.className = 'text-[9px] mt-1 text-right ' + (agent ? 'text-slate-800' : 'text-slate-500');
    time.textContent = m.time;
    box.append(who, text, time);
    wrap.appendChild(box);
    return wrap;
  }

  function renderConversation(c) {
    conv = c;
    $('ocVisitor').textContent = c.visitor;
    $('ocMeta').textContent = 'Live Chat #' + c.id + (c.urgent ? ' · DARURAT' : '')
      + (c.agent ? ' · ' + c.agent : c.status === 'BOT' ? ' · dilayani Asisten Virtual' : ' · belum ditangani');
    const [label, cls] = STATUS[c.status];
    const badge = $('ocStatusBadge');
    badge.textContent = label;
    badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full border ' + cls;

    $('ocInfoName').textContent = c.visitor;
    $('ocInfoPhone').textContent = c.phone || '-';
    $('ocInfoStarted').textContent = c.started;
    $('ocInfoAgent').textContent = c.agent || '-';

    const open = c.status !== 'CLOSED';
    $('ocSend').hidden = !canAct || !open || (c.status === 'ACTIVE' && !c.mine);
    $('ocClaim').hidden = !canAct || (c.status !== 'WAITING' && c.status !== 'BOT');
    $('ocClose').hidden = !canAct || !open || !c.mine;
    $('ocTicketInfo').hidden = !c.ticket;
    $('ocTicketNo').textContent = c.ticket || '';
    $('ocConvert').hidden = !canAct || !!c.ticket || !c.mine;
    const titleField = $('ocConvert').elements.namedItem('title');
    if (!$('ocConvert').hidden && !titleField.value) {
      titleField.value = 'Aduan Live Chat: ' + c.visitor;
    }
  }

  async function loadThread() {
    clearTimeout(threadTimer);
    if (currentId === null) return;
    const id = currentId;
    try {
      const data = await call('/omnichat/messages/' + id + '?after=' + lastId);
      if (id !== currentId) return; // percakapan sudah diganti
      renderConversation(data.conversation);
      const list = $('ocMessages');
      const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
      data.messages.forEach((m) => {
        if (m.id <= lastId) return;
        lastId = m.id;
        list.appendChild(bubble(m));
      });
      if (nearBottom) list.scrollTop = list.scrollHeight;
    } catch (e) {
      $('ocError').textContent = e.message;
    }
    threadTimer = setTimeout(loadThread, THREAD_EVERY);
  }

  function openThread(id) {
    currentId = id;
    lastId = 0;
    $('ocMessages').innerHTML = '';
    $('ocError').textContent = '';
    $('ocConvert').reset();
    $('ocEmpty').hidden = true;
    $('ocThread').hidden = false;
    $('ocSide').hidden = false;
    if (window.matchMedia('(max-width: 1023px)').matches) $('ocListPane').hidden = true;
    loadThread().then(() => { $('ocMessages').scrollTop = $('ocMessages').scrollHeight; });
    loadList();
  }

  async function action(path, data) {
    $('ocError').textContent = '';
    try {
      const res = await call(path, Object.assign({ conversation_id: currentId }, data || {}));
      await loadThread();
      loadList();
      return res;
    } catch (e) {
      $('ocError').textContent = e.message;
      return null;
    }
  }

  // Event
  document.querySelectorAll('.oc-tab').forEach((t) => t.addEventListener('click', () => {
    scope = t.dataset.scope;
    try { localStorage.setItem('ocScope', scope); } catch (e) { /* abaikan */ }
    renderTabs();
    loadList();
  }));

  $('ocSound').addEventListener('change', (e) => {
    try { localStorage.setItem('ocSound', e.target.checked ? '1' : '0'); } catch (ex) { /* abaikan */ }
  });

  $('ocSend').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = $('ocInput');
    const message = input.value.trim();
    if (!message) return;
    input.disabled = true;
    const res = await action('/omnichat/send', { message });
    input.disabled = false;
    if (res) input.value = '';
    input.focus();
    $('ocMessages').scrollTop = $('ocMessages').scrollHeight;
  });

  $('ocInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      $('ocSend').requestSubmit();
    }
  });

  $('ocClaim').addEventListener('click', () => action('/omnichat/claim'));
  $('ocClose').addEventListener('click', () => {
    if (confirm('Tutup percakapan ini? Warga tidak dapat membalas lagi.')) action('/omnichat/close');
  });
  $('ocConvert').addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await action('/omnichat/convert', Object.fromEntries(new FormData(e.target)));
    if (res && res.ticket) alert('Tiket laporan dibuat: ' + res.ticket);
  });
  $('ocBack').addEventListener('click', () => {
    $('ocListPane').hidden = false;
    $('ocListPane').scrollIntoView({ behavior: 'smooth' });
  });

  renderTabs();
  loadList();
})();
