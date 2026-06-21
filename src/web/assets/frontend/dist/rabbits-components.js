/**
 * Rabbits Interactive Components Runtime
 * Vanilla JS, no dependencies. Drives sliders (slideshow/carousel),
 * popups/modals, accordions, and tabs from data-rabbits-* hooks.
 */
(function () {
  'use strict';

  function initSliders(root) {
    root.querySelectorAll('[data-rabbits-slider]').forEach(function (el) {
      if (el._rabbitsSlider) return;
      el._rabbitsSlider = true;

      var track = el.querySelector('[data-rabbits-slider-track]');
      var slides = track ? Array.prototype.slice.call(track.children) : [];
      if (!track || !slides.length) return;

      var perView = parseInt(el.getAttribute('data-per-view')) || 1;
      var loop = el.getAttribute('data-loop') === 'true';
      var autoplay = el.getAttribute('data-autoplay') === 'true';
      var interval = parseInt(el.getAttribute('data-interval')) || 5000;
      var index = 0;
      var maxIndex = Math.max(0, slides.length - perView);

      slides.forEach(function (s) { s.style.flex = '0 0 ' + (100 / perView) + '%'; });

      function go(i) {
        if (i < 0) index = loop ? maxIndex : 0;
        else if (i > maxIndex) index = loop ? 0 : maxIndex;
        else index = i;
        track.style.transform = 'translateX(-' + (index * (100 / perView)) + '%)';
        dots.forEach(function (dot, di) { dot.classList.toggle('is-active', di === index); });
      }

      var prevBtn = el.querySelector('[data-rabbits-slider-prev]');
      var nextBtn = el.querySelector('[data-rabbits-slider-next]');
      if (prevBtn) prevBtn.addEventListener('click', function () { go(index - 1); restart(); });
      if (nextBtn) nextBtn.addEventListener('click', function () { go(index + 1); restart(); });

      var dots = [];
      var dotsWrap = el.querySelector('[data-rabbits-slider-dots]');
      if (dotsWrap) {
        for (var d = 0; d <= maxIndex; d++) {
          var dot = document.createElement('button');
          dot.type = 'button';
          dot.className = 'rabbits-slider__dot';
          (function (di) { dot.addEventListener('click', function () { go(di); restart(); }); })(d);
          dotsWrap.appendChild(dot);
          dots.push(dot);
        }
      }

      var timer = null;
      function start() { if (autoplay && maxIndex > 0) timer = setInterval(function () { go(index + 1); }, interval); }
      function stop() { if (timer) { clearInterval(timer); timer = null; } }
      function restart() { stop(); start(); }
      el.addEventListener('mouseenter', stop);
      el.addEventListener('mouseleave', start);

      go(0);
      start();
    });
  }

  function initPopups(root) {
    root.querySelectorAll('[data-rabbits-popup]').forEach(function (el) {
      if (el._rabbitsPopup) return;
      el._rabbitsPopup = true;

      var id = el.getAttribute('data-rabbits-popup');
      var overlay = el.querySelector('[data-rabbits-popup-overlay]');
      if (!overlay) return;
      var trigger = el.getAttribute('data-trigger') || 'click';
      var delay = parseInt(el.getAttribute('data-delay')) || 0;

      function open() { overlay.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
      function close() { overlay.classList.remove('is-open'); document.body.style.overflow = ''; }

      overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
      el.querySelectorAll('[data-rabbits-popup-close]').forEach(function (b) { b.addEventListener('click', close); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
      document.querySelectorAll('[data-rabbits-popup-open="' + id + '"]').forEach(function (b) {
        b.addEventListener('click', function (e) { e.preventDefault(); open(); });
      });

      if (trigger === 'page-load') {
        setTimeout(open, delay);
      } else if (trigger === 'exit-intent') {
        var armed = true;
        document.addEventListener('mouseout', function (e) {
          if (armed && !e.relatedTarget && e.clientY <= 0) { armed = false; open(); }
        });
      }
    });
  }

  function initAccordions(root) {
    root.querySelectorAll('[data-rabbits-accordion]').forEach(function (el) {
      if (el._rabbitsAccordion) return;
      el._rabbitsAccordion = true;

      var multiple = el.getAttribute('data-multiple') === 'true';
      el.querySelectorAll('[data-rabbits-accordion-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var item = btn.closest('.rabbits-accordion__item');
          if (!item) return;
          var willOpen = !item.classList.contains('is-open');
          if (!multiple) {
            el.querySelectorAll('.rabbits-accordion__item').forEach(function (it) {
              it.classList.remove('is-open');
              var b = it.querySelector('[data-rabbits-accordion-toggle]');
              if (b) b.setAttribute('aria-expanded', 'false');
            });
          }
          item.classList.toggle('is-open', willOpen);
          btn.setAttribute('aria-expanded', String(willOpen));
        });
      });
    });
  }

  function initTabs(root) {
    root.querySelectorAll('[data-rabbits-tabs]').forEach(function (el) {
      if (el._rabbitsTabs) return;
      el._rabbitsTabs = true;

      var tabs = Array.prototype.slice.call(el.querySelectorAll('[data-rabbits-tab]'));
      var panels = Array.prototype.slice.call(el.querySelectorAll('[data-rabbits-tab-panel]'));

      function activate(i) {
        tabs.forEach(function (t) { t.classList.toggle('is-active', t.getAttribute('data-rabbits-tab') === String(i)); });
        panels.forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-rabbits-tab-panel') === String(i)); });
      }
      tabs.forEach(function (t) {
        t.addEventListener('click', function () { activate(t.getAttribute('data-rabbits-tab')); });
      });
      activate('0');
    });
  }

  function initAlerts(root) {
    root.querySelectorAll('[data-rabbits-alert]').forEach(function (el) {
      if (el._rabbitsAlert) return;
      el._rabbitsAlert = true;
      el.querySelectorAll('[data-rabbits-alert-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          el.style.transition = 'opacity 0.2s ease';
          el.style.opacity = '0';
          setTimeout(function () { el.remove(); }, 200);
        });
      });
    });
  }

  function initCounters(root) {
    var counters = root.querySelectorAll('[data-rabbits-counter]');
    if (!counters.length) return;

    function run(el) {
      var end = parseFloat(el.getAttribute('data-end')) || 0;
      var duration = parseInt(el.getAttribute('data-duration')) || 2000;
      var prefix = el.getAttribute('data-prefix') || '';
      var suffix = el.getAttribute('data-suffix') || '';
      var decimals = (String(end).split('.')[1] || '').length;
      var startTime = null;
      function tick(ts) {
        if (startTime === null) startTime = ts;
        var p = Math.min((ts - startTime) / duration, 1);
        var value = (end * p).toFixed(decimals);
        el.textContent = prefix + value + suffix;
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          run(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    counters.forEach(function (el) {
      if (el._rabbitsCounter) return;
      el._rabbitsCounter = true;
      observer.observe(el);
    });
  }

  function initAll(root) {
    root = root || document;
    initSliders(root);
    initPopups(root);
    initAccordions(root);
    initTabs(root);
    initAlerts(root);
    initCounters(root);
  }

  window.RabbitsComponents = { init: initAll };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(document); });
  } else {
    initAll(document);
  }
})();
