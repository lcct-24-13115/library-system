/* SALRC login — vanilla JS, no build step.
   Opens the book (auto, or on click), closes it again with the "Close book" button,
   and tilts the closed cover with the pointer. */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;

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
      tilt.style.setProperty('--try', '0deg');   // open book sits still
      tilt.style.setProperty('--trx', '0deg');
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
          // Tilt only the closed cover. An open book stays still so buttons and fields
          // never slide away from the cursor.
          var maxY = closed ? 10 : 0, maxX = closed ? 7 : 0;
          tilt.style.setProperty('--try', (nx * 2 * maxY).toFixed(2) + 'deg');
          tilt.style.setProperty('--trx', (-ny * 2 * maxX).toFixed(2) + 'deg');
          scene.style.setProperty('--sx', ((nx + 0.5) * 100).toFixed(0) + '%');
          scene.style.setProperty('--sy', ((ny + 0.5) * 100).toFixed(0) + '%');
        });
      }, { passive: true });
    }
  }

  ready(initBook);
})();
