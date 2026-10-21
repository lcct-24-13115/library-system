/* SALRC 3D layer — vanilla JS, no build step.
   Pieces: (1) pointer tilt for [data-tilt], (2) Three.js floating-book background,
   (3) Table/Shelf view switch, (4) 3D book modal. Every piece fails safe:
   no WebGL, reduced motion or a blocked CDN simply leaves the CSS-only look. */
(function () {
  'use strict';

  var doc = document;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;
  var THREE_URL = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';

  function ready(fn) {
    if (doc.readyState !== 'loading') fn(); else doc.addEventListener('DOMContentLoaded', fn);
  }

  /* ---------------------------------------------------------------- 1. Tilt */
  function initTilt() {
    if (reduce || !finePointer) return;
    doc.querySelectorAll('[data-tilt]').forEach(function (el) {
      var max = parseFloat(el.getAttribute('data-tilt')) || 6;
      var frame = 0;
      el.addEventListener('pointermove', function (e) {
        var r = el.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width;
        var py = (e.clientY - r.top) / r.height;
        if (frame) cancelAnimationFrame(frame);
        frame = requestAnimationFrame(function () {
          el.classList.add('is-tilting');
          el.style.setProperty('--ry', ((px - 0.5) * 2 * max).toFixed(2) + 'deg');
          el.style.setProperty('--rx', ((0.5 - py) * 2 * max).toFixed(2) + 'deg');
          el.style.setProperty('--mx', (px * 100).toFixed(1) + '%');
          el.style.setProperty('--my', (py * 100).toFixed(1) + '%');
        });
      });
      el.addEventListener('pointerleave', function () {
        if (frame) cancelAnimationFrame(frame);
        el.classList.remove('is-tilting');
        el.style.setProperty('--rx', '0deg');
        el.style.setProperty('--ry', '0deg');
      });
    });
  }

  /* ------------------------------------------------- 2. Three.js background */
  function loadThree(done, fail) {
    if (window.THREE) return done();
    var s = doc.createElement('script');
    s.src = THREE_URL;
    s.async = true;
    s.onload = done;
    s.onerror = fail;
    doc.head.appendChild(s);
  }

  function initBackground() {
    var mode = doc.body.getAttribute('data-bg3d'); // 'login' | 'app' | null
    if (!mode) {
      if (doc.body.classList.contains('salrc') || doc.body.classList.contains('salrc-portal')) mode = 'app';
      else return;
    }
    if (reduce) return;

    var canvas = doc.createElement('canvas');
    canvas.id = 'bg3d';
    canvas.setAttribute('aria-hidden', 'true');
    doc.body.insertBefore(canvas, doc.body.firstChild);

    // Wait until the browser is idle so the page paints first.
    var start = function () { loadThree(function () { build(canvas, mode); }, function () { canvas.remove(); }); };
    if ('requestIdleCallback' in window) requestIdleCallback(start, { timeout: 1500 }); else setTimeout(start, 300);
  }

  function build(canvas, mode) {
    var T = window.THREE, renderer;
    try {
      renderer = new T.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true, powerPreference: 'low-power' });
    } catch (e) { canvas.remove(); return; }

    var small = window.innerWidth < 760;
    var dark = mode === 'login';
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.setSize(window.innerWidth, window.innerHeight, false);

    var scene = new T.Scene();
    var camera = new T.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.z = 18;

    scene.add(new T.AmbientLight(0xffffff, dark ? 0.75 : 0.95));
    var key = new T.DirectionalLight(0xffffff, dark ? 1.0 : 0.7);
    key.position.set(6, 10, 10);
    scene.add(key);
    var rim = new T.PointLight(0x60a5fa, dark ? 1.4 : 0.6, 60);
    rim.position.set(-10, -4, 8);
    scene.add(rim);

    // Floating books: pages on +x/+y/-y, coloured spine (-x) and covers (±z).
    var palette = dark
      ? [0x2563eb, 0x60a5fa, 0x1d4ed8, 0x38bdf8, 0x3b82f6, 0x93c5fd]
      : [0x93c5fd, 0x60a5fa, 0xbfdbfe, 0x7dd3fc, 0xa5b4fc];
    var opacity = dark ? 0.5 : 0.5;
    var paper = new T.MeshStandardMaterial({ color: 0xf1f5fb, roughness: 0.9, transparent: true, opacity: opacity });
    var count = dark ? (small ? 5 : 9) : (small ? 6 : 13);
    var books = [];
    for (var i = 0; i < count; i++) {
      var col = new T.MeshStandardMaterial({ color: palette[i % palette.length], roughness: 0.5, metalness: 0.08, transparent: true, opacity: opacity });
      var geo = new T.BoxGeometry(1.2, 1.7, 0.38 + Math.random() * 0.3);
      var m = new T.Mesh(geo, [paper, col, paper, paper, col, col]);
      var s = dark ? 0.35 + Math.random() * 0.35 : 0.7 + Math.random() * 1.1;
      m.scale.set(s, s, s);
      m.position.set((Math.random() - 0.5) * 34, (Math.random() - 0.5) * 20, dark ? -18 + Math.random() * 8 : -10 + Math.random() * 11);
      m.rotation.set(Math.random() * 6, Math.random() * 6, Math.random() * 6);
      m.userData = { sx: (Math.random() - 0.5) * 0.004, sy: (Math.random() - 0.5) * 0.006, ph: Math.random() * 6.28, amp: 0.2 + Math.random() * 0.5, y0: m.position.y };
      scene.add(m);
      books.push(m);
    }

    // Particle grid (login: animated wave; app: sparse dust).
    var cols = dark ? (small ? 24 : 46) : 18, rows = dark ? (small ? 14 : 24) : 10, n = cols * rows;
    var pos = new Float32Array(n * 3);
    for (var r = 0; r < rows; r++) {
      for (var c = 0; c < cols; c++) {
        var k = (r * cols + c) * 3;
        pos[k] = (c - cols / 2) * (dark ? 0.9 : 2.2);
        pos[k + 1] = -8.5 + (dark ? 0 : (r - rows / 2) * 0.1);
        pos[k + 2] = (r - rows / 2) * (dark ? 1.1 : 2.6);
        if (!dark) pos[k + 1] = (Math.random() - 0.5) * 18;
      }
    }
    var pg = new T.BufferGeometry();
    pg.setAttribute('position', new T.BufferAttribute(pos, 3));
    var points = new T.Points(pg, new T.PointsMaterial({ color: dark ? 0x93c5fd : 0x60a5fa, size: dark ? 0.09 : 0.07, transparent: true, opacity: dark ? 0.85 : 0.45, sizeAttenuation: true }));
    scene.add(points);

    // Pointer parallax.
    var tx = 0, ty = 0, cx = 0, cy = 0;
    window.addEventListener('pointermove', function (e) {
      tx = (e.clientX / window.innerWidth - 0.5) * 2;
      ty = (e.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });

    window.addEventListener('resize', function () {
      renderer.setSize(window.innerWidth, window.innerHeight, false);
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
    });

    var last = 0, minDelta = dark ? 0 : 33; // app pages run at ~30fps to stay light
    function frame(t) {
      requestAnimationFrame(frame);
      if (doc.hidden || t - last < minDelta) return;
      last = t;
      var time = t * 0.001;
      cx += (tx - cx) * 0.04; cy += (ty - cy) * 0.04;
      camera.position.x = cx * 1.6;
      camera.position.y = -cy * 1.0;
      camera.lookAt(0, 0, 0);
      for (var i = 0; i < books.length; i++) {
        var b = books[i], d = b.userData;
        b.rotation.x += d.sx; b.rotation.y += d.sy;
        b.position.y = d.y0 + Math.sin(time * 0.6 + d.ph) * d.amp;
      }
      if (dark) {
        var p = pg.attributes.position.array;
        for (var j = 0; j < n; j++) {
          var kk = j * 3;
          p[kk + 1] = -8.5 + Math.sin(time * 0.9 + p[kk] * 0.35 + p[kk + 2] * 0.4) * 0.75;
        }
        pg.attributes.position.needsUpdate = true;
      } else {
        points.rotation.y = time * 0.01;
      }
      renderer.render(scene, camera);
    }
    requestAnimationFrame(frame);
  }

  /* ----------------------------------------------- 3. Table / Shelf switch */
  function initViewSwitch() {
    doc.querySelectorAll('[data-viewswitch]').forEach(function (box) {
      var key = 'salrc.view.' + box.getAttribute('data-viewswitch');
      var buttons = box.querySelectorAll('[data-set-view]');
      var scope = doc.querySelectorAll('[data-view]');
      function apply(view) {
        scope.forEach(function (el) { el.hidden = el.getAttribute('data-view') !== view; });
        buttons.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-set-view') === view ? 'true' : 'false'); });
      }
      var saved = null;
      try { saved = localStorage.getItem(key); } catch (e) { /* storage blocked */ }
      apply(saved === 'table' || saved === 'shelf' ? saved : (box.getAttribute('data-default') || 'shelf'));
      buttons.forEach(function (b) {
        b.addEventListener('click', function () {
          var v = b.getAttribute('data-set-view');
          apply(v);
          try { localStorage.setItem(key, v); } catch (e) { /* ignore */ }
        });
      });
    });
  }

  /* -------------------------------------------------------- 4. Book modal */
  function initBookModal() {
    var modal = doc.getElementById('book3d-modal');
    if (!modal) return;
    var stage = modal.querySelector('[data-b3m-stage]');
    var opener = null, openTimer = 0;

    function field(name) { return modal.querySelector('[data-b3m="' + name + '"]'); }
    function setText(name, value) { var el = field(name); if (el) el.textContent = value || ''; }

    function close() {
      modal.classList.remove('is-open');
      doc.body.classList.remove('b3m-lock');
      clearTimeout(openTimer);
      stage.textContent = '';
      if (opener) { opener.focus(); opener = null; }
    }

    function open(btn) {
      var data;
      try { data = JSON.parse(btn.getAttribute('data-book')); } catch (e) { return; }
      opener = btn;

      setText('title', data.title + (data.subtitle ? ': ' + data.subtitle : ''));
      setText('author', data.author || '—');
      setText('category', data.category || 'General');
      setText('call', data.call || '—');
      setText('accession', data.accession || '—');
      setText('copies', data.available + ' of ' + data.total + ' available');
      setText('publisher', [data.publisher, data.year].filter(Boolean).join(', ') || '—');
      setText('summary', data.summary || '');

      var status = field('status');
      status.className = 'b3m__status ' + (data.available > 0 ? 'is-on' : 'is-off');
      status.textContent = data.available > 0 ? 'Available to borrow' : 'All copies on loan';

      var actions = field('actions');
      actions.textContent = '';
      (data.actions || []).forEach(function (a) {
        var link = doc.createElement('a');
        link.href = a.url;
        link.textContent = a.label;
        link.className = 'btn3d' + (a.ghost ? ' btn3d--ghost' : '');
        actions.appendChild(link);
      });

      // Clone the shelf book into the stage, then swing the cover open.
      stage.textContent = '';
      var source = btn.querySelector('.book3d');
      var clone = source.cloneNode(true);
      clone.style.cssText = btn.parentNode.getAttribute('style') || '';
      stage.appendChild(clone);

      modal.classList.add('is-open');
      doc.body.classList.add('b3m-lock');
      modal.querySelector('.b3m__close').focus();
      openTimer = setTimeout(function () { clone.classList.add('book-open'); }, reduce ? 0 : 450);
    }

    doc.addEventListener('click', function (e) {
      var btn = e.target.closest && e.target.closest('.book3d-btn');
      if (btn) { e.preventDefault(); open(btn); return; }
      if (e.target.closest && (e.target.closest('[data-b3m-close]'))) close();
    });
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
  }

  /* Scan boxes (Issue / Return): add the viewfinder + laser class without touching those views. */
  function initScanPanels() {
    doc.querySelectorAll('input[name="accession_number"]').forEach(function (input) {
      var card = input.closest('.bg-white');
      if (card) card.classList.add('scan-panel');
    });
  }

  ready(function () {
    initScanPanels();
    initTilt();
    initViewSwitch();
    initBookModal();
    initBackground();
  });
})();
