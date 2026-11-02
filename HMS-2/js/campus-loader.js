(function () {
  'use strict';

  window.Campus3D = window.Campus3D || {
    focusView: function () {},
    setLightingMode: function () {},
    toggleRotation: function () {}
  };

  function loadCampusScene(container) {
    if (!container || container.dataset.sceneLoading === 'true' || container.querySelector('canvas')) return;
    container.dataset.sceneLoading = 'true';

    const loadButton = container.querySelector('.mobile-scene-load');
    if (loadButton) {
      loadButton.disabled = true;
      loadButton.textContent = 'Loading interactive 3D view...';
    }

    const engine = document.createElement('script');
    engine.src = '../js/3d_campus_hero.js';
    engine.defer = true;
    engine.onerror = function () {
      container.dataset.sceneLoading = 'false';
      if (loadButton) {
        loadButton.disabled = false;
        loadButton.textContent = 'Unable to load 3D view. Try again.';
      }
      console.error('Unable to load the campus 3D scene.');
    };

    const three = document.createElement('script');
    three.src = '../js/vendor/three.min.js';
    three.defer = true;
    three.onload = function () {
      document.body.appendChild(engine);
    };
    three.onerror = function () {
      container.dataset.sceneLoading = 'false';
      if (loadButton) {
        loadButton.disabled = false;
        loadButton.textContent = 'Unable to load 3D view. Try again.';
      }
      console.error('Unable to load the Three.js renderer.');
    };
    document.body.appendChild(three);
  }

  function init() {
    const container = document.getElementById('hero-3d-canvas-container');
    if (!container) return;

    const isMobile = window.matchMedia('(max-width: 768px), (pointer: coarse)').matches;
    const rotationButton = document.getElementById('btn-toggle-rotate');
    const shouldRotate = !isMobile && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (rotationButton && !shouldRotate) {
      rotationButton.classList.remove('active');
      rotationButton.innerHTML = "<i class='bx bx-pause-circle'></i> Auto-Spin: OFF";
      rotationButton.setAttribute('aria-pressed', 'false');
    }

    if (isMobile) {
      const loadButton = container.querySelector('.mobile-scene-load');
      if (!loadButton) {
        loadCampusScene(container);
        return;
      }

      container.classList.add('mobile-opt-in');
      loadButton.addEventListener('click', () => loadCampusScene(container));
      return;
    }

    if (!('IntersectionObserver' in window)) {
      loadCampusScene(container);
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        observer.disconnect();
        loadCampusScene(container);
      }
    }, { rootMargin: isMobile ? '0px' : '300px 0px', threshold: isMobile ? 0.25 : 0.01 });
    observer.observe(container);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
