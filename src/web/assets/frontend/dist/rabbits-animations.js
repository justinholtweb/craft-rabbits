/**
 * Rabbits Animation Engine
 * Uses CSS transitions + Web Animations API (WAAPI)
 */
(function() {
  'use strict';

  const RabbitsAnimate = {
    init() {
      this.observeScrollAnimations();
      this.bindClickAnimations();
      this.bindHoverAnimations();
      this.runLoadAnimations();
    },

    observeScrollAnimations() {
      const targets = document.querySelectorAll('[data-rabbits-animate="scroll-into-view"]');
      if (!targets.length) return;

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.animate(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 });

      targets.forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
      });
    },

    bindClickAnimations() {
      document.querySelectorAll('[data-rabbits-animate="click"]').forEach(el => {
        el.addEventListener('click', () => this.animate(el));
      });
    },

    bindHoverAnimations() {
      document.querySelectorAll('[data-rabbits-animate="hover"]').forEach(el => {
        el.addEventListener('mouseenter', () => this.animate(el));
      });
    },

    runLoadAnimations() {
      document.querySelectorAll('[data-rabbits-animate="page-load"]').forEach(el => {
        el.style.opacity = '0';
        requestAnimationFrame(() => this.animate(el));
      });
    },

    animate(el) {
      const keyframesData = el.dataset.rabbitsKeyframes;
      const optionsData = el.dataset.rabbitsOptions;

      if (!keyframesData) return;

      try {
        const keyframes = JSON.parse(keyframesData);
        const options = optionsData ? JSON.parse(optionsData) : { duration: 600 };

        el.animate(keyframes, options);

        // Set final state
        const lastFrame = keyframes[keyframes.length - 1];
        Object.entries(lastFrame).forEach(([key, value]) => {
          if (key !== 'offset') {
            el.style[key] = value;
          }
        });
      } catch (e) {
        console.warn('Rabbits animation error:', e);
      }
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => RabbitsAnimate.init());
  } else {
    RabbitsAnimate.init();
  }
})();
