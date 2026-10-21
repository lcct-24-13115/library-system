/* SALRC login — vanilla JS, no build step.
   (1) opens the book (auto, or on click) and tilts it with the pointer,
   (2) Three.js background: a floating library — drifting books, flapping open books,
       and glowing motes that flare when the book opens.
   Everything fails safe: no WebGL, no CDN or reduced motion simply leaves the poster. */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;
  var THREE_URL = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';

  function ready(fn) {
    if (doc.readyState !== 'loading') fn(); else doc.addEventListener('DOMContentLoaded', fn);
  }

  /* ------------------------------------------------------------ 1. The book */
  function initBook() {
    var scene = doc.getElementById('book-scene');
    var page = doc.getElementById('book-page');
    var cover = doc.getElementById('book-cover-btn');
    var tilt = doc.getElementById('book-tilt');
    if (!scene || !page || !cover || !tilt) return;

    var autoTimer = 0;
    var closeBtn = doc.getElementById('book-close-btn');
    var hint = doc.querySelector('.cv-hint');
    function isClosed() { return root.classList.contains('book-closed'); }

    function focusFirst() {
      var bad = page.querySelector('.err');
      var el = bad ? bad.closest('.lf-field').querySelector('input') : doc.getElementById('email');
      if (!el) return;
      try { el.focus({ preventScroll: true }); } catch (e) { el.focus(); }
    }

    function openBook() {
      if (!isClosed()) return;
      clearTimeout(autoTimer);
      root.classList.remove('book-closed');
      cover.setAttribute('aria-expanded', 'true');
      window.dispatchEvent(new CustomEvent('salrc:book-open'));
      setTimeout(function () {
        if (isClosed()) return;               // closed again before the animation ended
        page.removeAttribute('inert');
        page.removeAttribute('aria-hidden');
        focusFirst();
      }, 1350);
    }

    function closeBook() {
      if (isClosed()) return;
      clearTimeout(autoTimer);
      root.classList.add('book-closed');
      cover.setAttribute('aria-expanded', 'false');
      page.setAttribute('inert', '');
      page.setAttribute('aria-hidden', 'true');
      if (hint) hint.textContent = 'Click to open';
      window.dispatchEvent(new CustomEvent('salrc:book-close'));
      try { cover.focus({ preventScroll: true }); } catch (e) { cover.focus(); }
    }

    cover.addEventListener('click', openBook);
    if (closeBtn) closeBtn.addEventListener('click', closeBook);
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !isClosed() && page.contains(doc.activeElement)) closeBook();
    });

    if (isClosed()) {
      // keep the form out of reach (tab order, clicks, screen readers) until the cover is open
      page.setAttribute('inert', '');
      page.setAttribute('aria-hidden', 'true');
      autoTimer = setTimeout(openBook, 1700);
    } else {
      focusFirst();
    }

    // Pointer tilt + moving sheen on the cover.
    if (!reduce && finePointer) {
      var raf = 0, nx = 0, ny = 0;
      window.addEventListener('pointermove', function (e) {
        nx = e.clientX / window.innerWidth - 0.5;
        ny = e.clientY / window.innerHeight - 0.5;
        if (raf) return;
        raf = requestAnimationFrame(function () {
          raf = 0;
          var closed = isClosed();
          var maxY = closed ? 10 : 2.6, maxX = closed ? 7 : 3;
          tilt.style.setProperty('--try', (nx * 2 * maxY).toFixed(2) + 'deg');
          tilt.style.setProperty('--trx', (-ny * 2 * maxX).toFixed(2) + 'deg');
          scene.style.setProperty('--sx', ((nx + 0.5) * 100).toFixed(0) + '%');
          scene.style.setProperty('--sy', ((ny + 0.5) * 100).toFixed(0) + '%');
        });
      }, { passive: true });
    }
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
    if (reduce) return;
    var canvas = doc.createElement('canvas');
    canvas.id = 'bg3d';
    canvas.setAttribute('aria-hidden', 'true');
    var bg = doc.querySelector('.login-bg');
    if (bg && bg.parentNode) bg.parentNode.insertBefore(canvas, bg.nextSibling);
    else doc.body.insertBefore(canvas, doc.body.firstChild);

    var start = function () {
      loadThree(function () {
        try { build(canvas); } catch (e) { canvas.remove(); }
      }, function () { canvas.remove(); });
    };
    if ('requestIdleCallback' in window) requestIdleCallback(start, { timeout: 1200 }); else setTimeout(start, 250);
  }

  function build(canvas) {
    var T = window.THREE, renderer;
    try {
      renderer = new T.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true, powerPreference: 'high-performance' });
    } catch (e) { canvas.remove(); return; }

    var small = window.innerWidth < 760;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.setSize(window.innerWidth, window.innerHeight, false);

    var FOV = 45;
    var scene = new T.Scene();
    scene.fog = new T.Fog(0x0a1433, 15, 46);
    var camera = new T.PerspectiveCamera(FOV, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.set(0, 0, 18);

    // Warm key light (candle / lamp), cool rim from the right, soft fill.
    scene.add(new T.AmbientLight(0xb9cdf5, 0.62));
    var key = new T.DirectionalLight(0xfff0cf, 1.2);
    key.position.set(-7, 9, 11);
    scene.add(key);
    var rim = new T.PointLight(0x4f8cff, 1.7, 70);
    rim.position.set(11, -2, 6);
    scene.add(rim);

    /* ---- shared geometry + materials ---- */
    var unit = new T.BoxGeometry(1, 1, 1);
    function mat(color, rough, metal, emissive) {
      return new T.MeshStandardMaterial({ color: color, roughness: rough, metalness: metal || 0, emissive: emissive || 0x000000 });
    }
    var coverMats = [0x1e3a8a, 0x2563eb, 0x1d4ed8, 0x12307a, 0x3b6fd8, 0x5b86e0].map(function (c) { return mat(c, 0.55, 0.08); });
    var pageMat = mat(0xf3ecd9, 0.92, 0);
    var pageMatWarm = mat(0xf8efd3, 0.9, 0, 0x1a1405);
    var goldMat = mat(0xd9b45b, 0.38, 0.35, 0x3a2b07);

    function box(w, h, d, m, x, y, z) {
      var mesh = new T.Mesh(unit, m);
      mesh.scale.set(w, h, d);
      mesh.position.set(x || 0, y || 0, z || 0);
      return mesh;
    }

    // Closed hardback: boards, spine, page block and a gold title plate.
    function closedBook(w, h, d, cm) {
      var g = new T.Group();
      g.add(box(w - 0.08, h - 0.1, d - 0.1, pageMat, 0.03, 0, 0));
      g.add(box(w, h, 0.06, cm, 0, 0, d / 2 - 0.03));
      g.add(box(w, h, 0.06, cm, 0, 0, -d / 2 + 0.03));
      g.add(box(0.09, h, d, cm, -w / 2 + 0.045, 0, 0));
      g.add(box(0.1, 0.05, d + 0.02, goldMat, -w / 2 + 0.045, h * 0.3, 0));
      g.add(box(0.1, 0.05, d + 0.02, goldMat, -w / 2 + 0.045, -h * 0.3, 0));
      g.add(box(w * 0.58, h * 0.3, 0.012, goldMat, 0.05, h * 0.12, d / 2 + 0.004));
      g.add(box(w * 0.78, 0.03, 0.012, goldMat, 0.04, h * 0.38, d / 2 + 0.004));
      g.add(box(w * 0.78, 0.03, 0.012, goldMat, 0.04, -h * 0.38, d / 2 + 0.004));
      return g;
    }

    // Open book that flaps its pages like wings (pivot = spine).
    function flapBook(w, h, cm) {
      var g = new T.Group();
      var L = new T.Group(), R = new T.Group();
      L.add(box(w, h, 0.05, cm, -w / 2, 0, 0));
      L.add(box(w - 0.12, h - 0.12, 0.02, pageMatWarm, -w / 2, 0, 0.035));
      R.add(box(w, h, 0.05, cm, w / 2, 0, 0));
      R.add(box(w - 0.12, h - 0.12, 0.02, pageMatWarm, w / 2, 0, 0.035));
      g.add(L); g.add(R);
      g.userData.L = L; g.userData.R = R;
      return g;
    }

    function halfH(z) { return Math.tan(FOV * Math.PI / 360) * (18 - z); }

    var drifters = [];
    var nClosed = small ? 6 : 13;
    var nFlap = small ? 3 : 6;
    var i, g, s, z;

    for (i = 0; i < nClosed; i++) {
      s = 0.55 + Math.random() * 0.75;
      g = closedBook(1.3, 1.8, 0.34 + Math.random() * 0.22, coverMats[i % coverMats.length]);
      g.scale.set(s, s, s);
      z = -24 + Math.random() * 20;
      g.position.set((Math.random() - 0.5) * 2 * (halfH(z) * 1.7), (Math.random() - 0.5) * 2 * halfH(z), z);
      g.rotation.set(Math.random() * 6, Math.random() * 6, Math.random() * 6);
      g.userData = {
        kind: 'closed', vy: 0.12 + Math.random() * 0.3, z: z,
        rx: (Math.random() - 0.5) * 0.35, ry: (Math.random() - 0.5) * 0.5, rz: (Math.random() - 0.5) * 0.2,
        ph: Math.random() * 6.28, sway: 0.2 + Math.random() * 0.4
      };
      scene.add(g);
      drifters.push(g);
    }

    for (i = 0; i < nFlap; i++) {
      s = 0.6 + Math.random() * 0.7;
      g = flapBook(1.15, 1.6, coverMats[(i + 2) % coverMats.length]);
      g.scale.set(s, s, s);
      z = -20 + Math.random() * 15;
      g.position.set((Math.random() - 0.5) * 2 * (halfH(z) * 1.6), (Math.random() - 0.5) * 2 * halfH(z), z);
      var data = g.userData;
      data.kind = 'flap'; data.vy = 0.2 + Math.random() * 0.35; data.z = z;
      data.f = 1.7 + Math.random() * 1.1; data.ph = Math.random() * 6.28;
      data.sway = 0.4 + Math.random() * 0.6; data.x0 = g.position.x;
      scene.add(g);
      drifters.push(g);
    }

    /* ---- glowing motes (soft sprites, additive) ---- */
    var sprite = (function () {
      var c = doc.createElement('canvas');
      c.width = c.height = 64;
      var x = c.getContext('2d');
      var gr = x.createRadialGradient(32, 32, 0, 32, 32, 32);
      gr.addColorStop(0, 'rgba(255,255,255,1)');
      gr.addColorStop(0.25, 'rgba(255,255,255,.55)');
      gr.addColorStop(1, 'rgba(255,255,255,0)');
      x.fillStyle = gr;
      x.fillRect(0, 0, 64, 64);
      return new T.CanvasTexture(c);
    })();

    var N = small ? 260 : 680;
    var mp = new Float32Array(N * 3), mc = new Float32Array(N * 3), mv = new Float32Array(N), mph = new Float32Array(N);
    var tint = [new T.Color(0x9cc4ff), new T.Color(0xdbe9ff), new T.Color(0xffd98a)];
    for (i = 0; i < N; i++) {
      mp[i * 3] = (Math.random() - 0.5) * 56;
      mp[i * 3 + 1] = (Math.random() - 0.5) * 30;
      mp[i * 3 + 2] = -30 + Math.random() * 40;
      var r = Math.random();
      var col = r < 0.6 ? tint[0] : (r < 0.9 ? tint[1] : tint[2]);
      mc[i * 3] = col.r; mc[i * 3 + 1] = col.g; mc[i * 3 + 2] = col.b;
      mv[i] = 0.08 + Math.random() * 0.4;
      mph[i] = Math.random() * 6.28;
    }
    var mg = new T.BufferGeometry();
    mg.setAttribute('position', new T.BufferAttribute(mp, 3));
    mg.setAttribute('color', new T.BufferAttribute(mc, 3));
    var motesMat = new T.PointsMaterial({
      size: 0.24, map: sprite, vertexColors: true, transparent: true, opacity: 0.85,
      depthWrite: false, blending: T.AdditiveBlending, sizeAttenuation: true, fog: false
    });
    scene.add(new T.Points(mg, motesMat));

    /* ---- reactions ---- */
    var pulse = 0;
    window.addEventListener('salrc:book-open', function () { pulse = 1; });

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

    var last = 0;
    function frame(t) {
      requestAnimationFrame(frame);
      if (doc.hidden) { last = t; return; }
      var dt = Math.min((t - last) / 1000 || 0.016, 0.05);
      last = t;
      var time = t * 0.001;

      pulse = Math.max(0, pulse - dt * 0.55);
      cx += (tx - cx) * 0.04; cy += (ty - cy) * 0.04;
      camera.position.x = cx * 1.8;
      camera.position.y = -cy * 1.1;
      camera.position.z = 18 - pulse * 1.4;
      camera.lookAt(0, 0, 0);
      rim.intensity = 1.7 + pulse * 2.4;
      motesMat.size = 0.24 + pulse * 0.2;

      for (var k = 0; k < drifters.length; k++) {
        var b = drifters[k], d = b.userData;
        b.position.y += d.vy * dt * (1 + pulse * 2.5);
        var lim = halfH(d.z) + 2.6 * b.scale.x + 1.5;
        if (b.position.y > lim) b.position.y = -lim;
        if (d.kind === 'closed') {
          b.rotation.x += d.rx * dt; b.rotation.y += d.ry * dt; b.rotation.z += d.rz * dt;
          b.position.x += Math.sin(time * 0.4 + d.ph) * d.sway * dt;
        } else {
          var a = 0.55 + 0.5 * Math.sin(time * d.f + d.ph);
          d.L.rotation.y = a; d.R.rotation.y = -a;
          b.position.x = d.x0 + Math.sin(time * 0.3 + d.ph) * d.sway * 2;
          b.rotation.y = Math.sin(time * 0.35 + d.ph) * 0.55;
          b.rotation.z = Math.sin(time * 0.5 + d.ph) * 0.18;
          b.rotation.x = -0.15 + Math.sin(time * 0.45 + d.ph) * 0.12;
        }
      }

      var p = mg.attributes.position.array;
      for (var j = 0; j < N; j++) {
        var q = j * 3;
        p[q + 1] += mv[j] * dt * (1 + pulse * 3.5);
        p[q] += Math.sin(time * 0.6 + mph[j]) * 0.0035;
        if (p[q + 1] > 15) p[q + 1] = -15;
      }
      mg.attributes.position.needsUpdate = true;

      renderer.render(scene, camera);
    }
    canvas.classList.add('is-ready');
    requestAnimationFrame(frame);
  }

  ready(function () {
    initBook();
    initBackground();
  });
})();
