/**
 * SIGAP-POLDA: efek visual hi-tech (tanpa dependensi)
 * - .tilt-3d     : kartu miring 3D mengikuti kursor
 * - .fx-network  : kanvas jaringan data (node emas tersambung)
 * - .reveal      : animasi muncul saat digulir
 * Semua efek nonaktif bila pengguna memilih "kurangi gerakan".
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function initReveal() {
    var items = document.querySelectorAll('.reveal');
    if (!items.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('is-visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    items.forEach(function (el) { io.observe(el); });
  }

  function initTilt() {
    if (reduceMotion || !window.matchMedia('(hover: hover)').matches) return;
    document.querySelectorAll('.tilt-3d').forEach(function (el) {
      var max = parseFloat(el.dataset.tilt || '8');
      el.addEventListener('pointermove', function (ev) {
        var r = el.getBoundingClientRect();
        var x = (ev.clientX - r.left) / r.width - 0.5;
        var y = (ev.clientY - r.top) / r.height - 0.5;
        el.style.setProperty('--ry', (x * max * 2).toFixed(2) + 'deg');
        el.style.setProperty('--rx', (-y * max * 2).toFixed(2) + 'deg');
      });
      el.addEventListener('pointerleave', function () {
        el.style.setProperty('--rx', '0deg');
        el.style.setProperty('--ry', '0deg');
      });
    });
  }

  function initNetwork(canvas) {
    var ctx = canvas.getContext('2d');
    if (!ctx) return;
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var nodes = [];
    var w = 0, h = 0, raf = 0, running = true;

    function resize() {
      var r = canvas.getBoundingClientRect();
      w = r.width; h = r.height;
      canvas.width = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      var count = Math.max(18, Math.min(60, Math.round((w * h) / 22000)));
      nodes = [];
      for (var i = 0; i < count; i++) {
        nodes.push({
          x: Math.random() * w, y: Math.random() * h,
          vx: (Math.random() - 0.5) * 0.35, vy: (Math.random() - 0.5) * 0.35,
          r: Math.random() * 1.6 + 0.8,
          red: Math.random() < 0.12
        });
      }
    }

    function frame() {
      ctx.clearRect(0, 0, w, h);
      var link = 130;
      for (var i = 0; i < nodes.length; i++) {
        var a = nodes[i];
        if (!reduceMotion) {
          a.x += a.vx; a.y += a.vy;
          if (a.x < 0 || a.x > w) a.vx *= -1;
          if (a.y < 0 || a.y > h) a.vy *= -1;
        }
        for (var j = i + 1; j < nodes.length; j++) {
          var b = nodes[j];
          var dx = a.x - b.x, dy = a.y - b.y;
          var d = Math.sqrt(dx * dx + dy * dy);
          if (d < link) {
            ctx.strokeStyle = 'rgba(247,194,27,' + (0.22 * (1 - d / link)).toFixed(3) + ')';
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
          }
        }
        ctx.fillStyle = a.red ? 'rgba(200,16,46,0.9)' : 'rgba(252,211,77,0.85)';
        ctx.beginPath(); ctx.arc(a.x, a.y, a.r, 0, Math.PI * 2); ctx.fill();
      }
      if (running && !reduceMotion) raf = requestAnimationFrame(frame);
    }

    resize();
    frame();
    window.addEventListener('resize', function () { cancelAnimationFrame(raf); resize(); frame(); });
    document.addEventListener('visibilitychange', function () {
      running = !document.hidden;
      cancelAnimationFrame(raf);
      if (running) frame();
    });
  }

  function initClock() {
    document.querySelectorAll('[data-live-clock]').forEach(function (el) {
      function tick() {
        el.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false, timeZone: 'Asia/Jakarta' }) + ' WIB';
      }
      tick();
      setInterval(tick, 1000);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initReveal();
    initTilt();
    initClock();
    document.querySelectorAll('canvas.fx-network').forEach(initNetwork);
  });
})();
