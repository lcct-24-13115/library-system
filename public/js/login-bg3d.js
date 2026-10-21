/* SALRC login — 3D background. Raw WebGL, no libraries, no CDN.
   A softly lit library hall behind the book:
     • warm light beams that fall from the top-left (the "Veritas" light of the painting),
     • drifting golden / blue dust at many depths,
     • large out-of-focus bokeh lights far back,
     • the camera glides with the pointer, so the light and dust have real depth.
   The book opening / closing makes the light flare.
   Fails safe: no WebGL or "reduce motion" simply leaves the plain poster. */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var FOV = 45 * Math.PI / 180;

  /* ------------------------------------------------------------ tiny matrix helpers */
  function perspective(aspect, near, far) {
    var f = 1 / Math.tan(FOV / 2), nf = 1 / (near - far);
    return new Float32Array([f / aspect, 0, 0, 0, 0, f, 0, 0, 0, 0, (far + near) * nf, -1, 0, 0, 2 * far * near * nf, 0]);
  }
  function lookAt(ex, ey, ez) {          // looking at the origin, y up
    var zx = ex, zy = ey, zz = ez, l = Math.sqrt(zx * zx + zy * zy + zz * zz);
    zx /= l; zy /= l; zz /= l;
    var xx = zz, xy = 0, xz = -zx;       // up × z, with up = (0,1,0)
    l = Math.sqrt(xx * xx + xz * xz); xx /= l; xz /= l;
    var yx = zy * xz - zz * xy, yy = zz * xx - zx * xz, yz = zx * xy - zy * xx;
    return new Float32Array([
      xx, yx, zx, 0, xy, yy, zy, 0, xz, yz, zz, 0,
      -(xx * ex + xy * ey + xz * ez), -(yx * ex + yy * ey + yz * ez), -(zx * ex + zy * ey + zz * ez), 1
    ]);
  }

  /* ------------------------------------------------------------ shaders */
  var VS_POINTS = [
    'attribute vec3 aPos; attribute vec4 aRnd;',
    'uniform mat4 uProj, uView;',
    'uniform float uTime, uRise, uPulse, uScale, uBokeh, uMaxPt;',
    'varying vec3 vCol; varying float vA, vBokeh;',
    'void main(){',
    '  float H = 17.0;',
    '  float y = mod(aPos.y + uRise * aRnd.x + H, 2.0 * H) - H;',
    '  vec3 p = vec3(aPos.x + sin(uTime * 0.45 + aRnd.y * 6.2831) * 0.7, y, aPos.z);',
    '  vec4 mv = uView * vec4(p, 1.0);',
    '  gl_Position = uProj * mv;',
    '  gl_PointSize = min(aRnd.z * uScale / max(-mv.z, 0.1), uMaxPt);',
    '  float tw = 0.65 + 0.35 * sin(uTime * 1.6 + aRnd.y * 40.0);',
    '  float edge = smoothstep(-H, -H + 4.0, y) * (1.0 - smoothstep(H - 4.0, H, y));',
    '  vec3 gold = vec3(1.0, 0.82, 0.45), blue = vec3(0.62, 0.78, 1.0), white = vec3(0.93, 0.96, 1.0);',
    '  vCol = aRnd.w < 0.45 ? blue : (aRnd.w < 0.78 ? white : gold);',
    '  vA = tw * edge * (0.85 + uPulse * 0.9) * mix(1.0, 0.5, uBokeh);',
    '  vBokeh = uBokeh;',
    '}'
  ].join('\n');

  var FS_POINTS = [
    'precision mediump float;',
    'varying vec3 vCol; varying float vA, vBokeh;',
    'void main(){',
    '  float r = length(gl_PointCoord - 0.5) * 2.0;',
    '  if (r > 1.0) discard;',
    '  float a;',
    '  if (vBokeh > 0.5) a = 0.12 * smoothstep(1.0, 0.75, r) + 0.2 * smoothstep(0.7, 0.95, r) * smoothstep(1.0, 0.93, r);',
    '  else a = pow(1.0 - r, 2.2);',
    '  a *= vA;',
    '  gl_FragColor = vec4(vCol * a, a);',
    '}'
  ].join('\n');

  var VS_BEAM = [
    'attribute vec3 aPos; attribute vec2 aUV; attribute vec2 aBeam;',
    'uniform mat4 uProj, uView;',
    'varying vec2 vUV; varying vec2 vB;',
    'void main(){ gl_Position = uProj * uView * vec4(aPos, 1.0); vUV = aUV; vB = aBeam; }'
  ].join('\n');

  var FS_BEAM = [
    'precision mediump float;',
    'uniform float uTime, uPulse; varying vec2 vUV; varying vec2 vB;',
    'void main(){',
    '  float across = exp(-pow((vUV.x - 0.5) * 3.4, 2.0));',
    '  float along = smoothstep(0.0, 0.12, vUV.y) * (1.0 - smoothstep(0.4, 1.0, vUV.y));',
    '  float flick = 0.62 + 0.38 * sin(uTime * 0.33 + vB.y);',
    '  float a = across * along * vB.x * flick * (1.0 + uPulse * 1.3);',
    '  gl_FragColor = vec4(vec3(1.0, 0.86, 0.55) * a, a);',
    '}'
  ].join('\n');

  function compile(gl, type, src) {
    var s = gl.createShader(type);
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(s));
    return s;
  }
  function program(gl, vs, fs) {
    var p = gl.createProgram();
    gl.attachShader(p, compile(gl, gl.VERTEX_SHADER, vs));
    gl.attachShader(p, compile(gl, gl.FRAGMENT_SHADER, fs));
    gl.linkProgram(p);
    if (!gl.getProgramParameter(p, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(p));
    return p;
  }
  function buffer(gl, data) {
    var b = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, b);
    gl.bufferData(gl.ARRAY_BUFFER, data, gl.STATIC_DRAW);
    return b;
  }

  /* ------------------------------------------------------------ the scene */
  function build(gl, canvas) {
    var small = window.innerWidth < 760;
    var maxPt = gl.getParameter(gl.ALIASED_POINT_SIZE_RANGE)[1] || 64;

    var pPoints = program(gl, VS_POINTS, FS_POINTS);
    var pBeam = program(gl, VS_BEAM, FS_BEAM);
    var loc = function (p, n) { return gl.getUniformLocation(p, n); };
    var U = {
      pts: { proj: loc(pPoints, 'uProj'), view: loc(pPoints, 'uView'), time: loc(pPoints, 'uTime'), rise: loc(pPoints, 'uRise'),
             pulse: loc(pPoints, 'uPulse'), scale: loc(pPoints, 'uScale'), bokeh: loc(pPoints, 'uBokeh'), maxPt: loc(pPoints, 'uMaxPt') },
      beam: { proj: loc(pBeam, 'uProj'), view: loc(pBeam, 'uView'), time: loc(pBeam, 'uTime'), pulse: loc(pBeam, 'uPulse') }
    };

    function rnd(a, b) { return a + Math.random() * (b - a); }

    // dust + bokeh share one layout: pos(3) + rnd(4) = 7 floats per point
    function makePoints(n, zMin, zMax, sizeMin, sizeMax, speedMin, speedMax, spread) {
      var d = new Float32Array(n * 7);
      for (var i = 0; i < n; i++) {
        var z = rnd(zMin, zMax), k = i * 7;
        var hw = Math.tan(FOV / 2) * (18 - z) * 1.9 * spread;
        d[k] = rnd(-hw, hw); d[k + 1] = rnd(-17, 17); d[k + 2] = z;
        d[k + 3] = rnd(speedMin, speedMax); d[k + 4] = Math.random(); d[k + 5] = rnd(sizeMin, sizeMax); d[k + 6] = Math.random();
      }
      return { buf: buffer(gl, d), n: n };
    }
    var dust = makePoints(small ? 260 : 620, -26, 9, 0.05, 0.2, 0.12, 0.55, 1);
    var bokeh = makePoints(small ? 8 : 16, -34, -10, 1.3, 3.0, 0.05, 0.16, 1);

    // light beams: long thin quads, each at its own depth, angled down and to the right
    var beams = (function () {
      var count = small ? 5 : 8, data = [], ang = 0.52;
      var dx = Math.sin(ang), dy = -Math.cos(ang), nx = Math.cos(ang), ny = Math.sin(ang), L = 76;
      for (var i = 0; i < count; i++) {
        var ox = rnd(-34, -2), oy = rnd(15, 21), oz = rnd(-28, 1), w = rnd(1.4, 5.2);
        var a = rnd(0.05, 0.12), ph = Math.random() * 6.28;
        var ex = ox + dx * L, ey = oy + dy * L;
        var v = [
          [ox - nx * w / 2, oy - ny * w / 2, 0, 0], [ox + nx * w / 2, oy + ny * w / 2, 1, 0],
          [ex - nx * w / 2, ey - ny * w / 2, 0, 1], [ex + nx * w / 2, ey + ny * w / 2, 1, 1]
        ];
        [0, 1, 2, 1, 3, 2].forEach(function (j) {
          data.push(v[j][0], v[j][1], oz, v[j][2], v[j][3], a, ph);
        });
      }
      return { buf: buffer(gl, new Float32Array(data)), n: data.length / 7 };
    })();

    gl.disable(gl.DEPTH_TEST);
    gl.enable(gl.BLEND);
    gl.blendFunc(gl.ONE, gl.ONE);                 // additive light
    gl.clearColor(0, 0, 0, 0);

    /* ---- input + state ---- */
    var pulse = 0, rise = 0;
    window.addEventListener('salrc:book-open', function () { pulse = 1; });
    window.addEventListener('salrc:book-close', function () { pulse = 0.6; });

    var tx = 0, ty = 0, cx = 0, cy = 0;
    window.addEventListener('pointermove', function (e) {
      tx = (e.clientX / window.innerWidth - 0.5) * 2;
      ty = (e.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });

    var proj, W = 0, H = 0;
    function resize() {
      var dpr = Math.min(window.devicePixelRatio || 1, 1.75);
      W = Math.max(1, Math.round(window.innerWidth * dpr));
      H = Math.max(1, Math.round(window.innerHeight * dpr));
      canvas.width = W; canvas.height = H;
      gl.viewport(0, 0, W, H);
      proj = perspective(W / H, 0.1, 120);
    }
    resize();
    window.addEventListener('resize', resize);

    canvas.addEventListener('webglcontextlost', function (e) { e.preventDefault(); canvas.remove(); });

    function drawPoints(set, bokehFlag, view, time) {
      gl.useProgram(pPoints);
      gl.bindBuffer(gl.ARRAY_BUFFER, set.buf);
      var aPos = gl.getAttribLocation(pPoints, 'aPos'), aRnd = gl.getAttribLocation(pPoints, 'aRnd');
      gl.enableVertexAttribArray(aPos); gl.vertexAttribPointer(aPos, 3, gl.FLOAT, false, 28, 0);
      gl.enableVertexAttribArray(aRnd); gl.vertexAttribPointer(aRnd, 4, gl.FLOAT, false, 28, 12);
      gl.uniformMatrix4fv(U.pts.proj, false, proj);
      gl.uniformMatrix4fv(U.pts.view, false, view);
      gl.uniform1f(U.pts.time, time);
      gl.uniform1f(U.pts.rise, rise);
      gl.uniform1f(U.pts.pulse, pulse);
      gl.uniform1f(U.pts.scale, (H / 2) / Math.tan(FOV / 2));
      gl.uniform1f(U.pts.bokeh, bokehFlag);
      gl.uniform1f(U.pts.maxPt, maxPt);
      gl.drawArrays(gl.POINTS, 0, set.n);
    }

    function drawBeams(view, time) {
      gl.useProgram(pBeam);
      gl.bindBuffer(gl.ARRAY_BUFFER, beams.buf);
      var aPos = gl.getAttribLocation(pBeam, 'aPos'), aUV = gl.getAttribLocation(pBeam, 'aUV'), aBeam = gl.getAttribLocation(pBeam, 'aBeam');
      gl.enableVertexAttribArray(aPos);  gl.vertexAttribPointer(aPos, 3, gl.FLOAT, false, 28, 0);
      gl.enableVertexAttribArray(aUV);   gl.vertexAttribPointer(aUV, 2, gl.FLOAT, false, 28, 12);
      gl.enableVertexAttribArray(aBeam); gl.vertexAttribPointer(aBeam, 2, gl.FLOAT, false, 28, 20);
      gl.uniformMatrix4fv(U.beam.proj, false, proj);
      gl.uniformMatrix4fv(U.beam.view, false, view);
      gl.uniform1f(U.beam.time, time);
      gl.uniform1f(U.beam.pulse, pulse);
      gl.drawArrays(gl.TRIANGLES, 0, beams.n);
    }

    var last = 0, first = true;
    function frame(t) {
      requestAnimationFrame(frame);
      if (doc.hidden) { last = t; return; }
      var dt = Math.min((t - last) / 1000 || 0.016, 0.05);
      last = t;
      var time = t * 0.001;

      pulse = Math.max(0, pulse - dt * 0.55);
      rise += dt * (1 + pulse * 2.6);
      cx += (tx - cx) * 0.045; cy += (ty - cy) * 0.045;

      // camera drifts on its own a little, and follows the pointer
      var ex = cx * 1.9 + Math.sin(time * 0.12) * 0.55;
      var ey = -cy * 1.1 + Math.cos(time * 0.1) * 0.3;
      var view = lookAt(ex, ey, 18 - pulse * 1.2);

      gl.clear(gl.COLOR_BUFFER_BIT);
      drawBeams(view, time);
      drawPoints(bokeh, 1, view, time);
      drawPoints(dust, 0, view, time);

      if (first) { first = false; canvas.classList.add('is-ready'); }
    }
    requestAnimationFrame(frame);
  }

  function start() {
    var canvas = doc.createElement('canvas');
    canvas.id = 'bg3d';
    canvas.setAttribute('aria-hidden', 'true');
    var bg = doc.querySelector('.login-bg');
    if (bg && bg.parentNode) bg.parentNode.insertBefore(canvas, bg.nextSibling);
    else doc.body.insertBefore(canvas, doc.body.firstChild);

    var gl = null;
    try { gl = canvas.getContext('webgl', { alpha: true, antialias: false, premultipliedAlpha: true, powerPreference: 'high-performance' }); } catch (e) { gl = null; }
    if (!gl) { if (window.console) console.warn('[login-bg3d] WebGL not available'); canvas.remove(); return; }
    try { build(gl, canvas); } catch (e) { if (window.console) console.warn('[login-bg3d]', e); canvas.remove(); }
  }

  if (doc.readyState !== 'loading') start(); else doc.addEventListener('DOMContentLoaded', start);
})();
