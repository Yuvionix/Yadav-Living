/**
 * ==============================================================================
 * 🌟 YADAV LIVING SOCIETY & PG - 3D INTERACTIVE TWIN-CAMPUS ENGINE
 * ==============================================================================
 * Engineered by Yuvraj
 * 
 * Features:
 * - High-Performance WebGL 3D Architectural Scene (Yadav Living Society + PG + Gate)
 * - Cinematic Camera Tweening (Seamless glide when switching tabs)
 * - Interactive Mouse Orbit & Touch Drag Controls
 * - Dynamic Lighting Presets: Cyber Neon, Sunset Gold, Daylight Clear
 * - Wi-Fi Beacon Wave Simulation on Yadav PG Rooftop
 * - 3D Card Tilt Physics with Specular Glare Tracking
 * - Auto-Pausing via IntersectionObserver for 60+ FPS & Low Battery Impact
 * ==============================================================================
 */

(function () {
  'use strict';

  // Global controller handle for tab switching integration
  window.Campus3D = {
    focusView: function (viewName) {},
    setLightingMode: function (mode) {},
    toggleRotation: function () {}
  };

  function initCampusHero() {
    const container = document.getElementById('hero-3d-canvas-container');
    if (!container || typeof THREE === 'undefined') return;
    if (container.querySelector('canvas')) return; // Avoid re-initialization

    let width = container.clientWidth || 600;
    let height = container.clientHeight || 420;

    // -------------------------------------------------------------
    // 1. SCENE & CAMERA SETUP
    // -------------------------------------------------------------
    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x0a0f1d, 0.015);

    const camera = new THREE.PerspectiveCamera(42, width / height, 0.5, 500);
    const defaultCamPos = { x: 26, y: 22, z: 30 };
    const defaultTarget = { x: 0, y: 2, z: 0 };
    camera.position.set(defaultCamPos.x, defaultCamPos.y, defaultCamPos.z);
    camera.lookAt(defaultTarget.x, defaultTarget.y, defaultTarget.z);

    // Current camera target for smooth lerping
    const curTarget = { ...defaultTarget };
    const destTarget = { ...defaultTarget };
    const destCamPos = { ...defaultCamPos };

    // -------------------------------------------------------------
    // 2. RENDERER SETUP (Optimized for Mobile & Desktop)
    // -------------------------------------------------------------
    const isMobile = window.matchMedia('(max-width: 768px), (pointer: coarse)').matches;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const renderer = new THREE.WebGLRenderer({
      alpha: true,
      antialias: !isMobile && window.devicePixelRatio < 2,
      powerPreference: 'high-performance'
    });

    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isMobile ? 1 : 1.75));
    renderer.shadowMap.enabled = !isMobile;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);
    container.classList.add('scene-ready');

    // -------------------------------------------------------------
    // 3. DYNAMIC LIGHTING SUITE
    // -------------------------------------------------------------
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
    scene.add(ambientLight);

    const dirLight1 = new THREE.DirectionalLight(0x6366f1, 1.8);
    dirLight1.position.set(25, 40, 25);
    scene.add(dirLight1);

    const dirLight2 = new THREE.DirectionalLight(0x38bdf8, 1.2);
    dirLight2.position.set(-25, 15, -20);
    scene.add(dirLight2);

    const warmAccentLight = new THREE.PointLight(0xf59e0b, 2.5, 40);
    warmAccentLight.position.set(10, 8, 4);
    scene.add(warmAccentLight);

    // -------------------------------------------------------------
    // 4. ARCHITECTURAL MATERIALS
    // -------------------------------------------------------------
    const matTowerGlass = new THREE.MeshPhongMaterial({
      color: 0x1e1b4b,
      emissive: 0x0f172a,
      specular: 0x818cf8,
      shininess: 80,
      transparent: true,
      opacity: 0.92
    });

    const matTowerGlassB = new THREE.MeshPhongMaterial({
      color: 0x172554,
      emissive: 0x0c4a6e,
      specular: 0x38bdf8,
      shininess: 90,
      transparent: true,
      opacity: 0.94
    });

    const matPGBlock = new THREE.MeshPhongMaterial({
      color: 0x082f49,
      emissive: 0x0369a1,
      specular: 0xf59e0b,
      shininess: 75,
      transparent: true,
      opacity: 0.95
    });

    const matWire = new THREE.LineBasicMaterial({
      color: 0x818cf8,
      transparent: true,
      opacity: 0.65
    });

    const matGoldWire = new THREE.LineBasicMaterial({
      color: 0xfbbf24,
      transparent: true,
      opacity: 0.8
    });

    const matGoldAccent = new THREE.MeshPhongMaterial({
      color: 0xf59e0b,
      emissive: 0xd97706,
      specular: 0xffffff,
      shininess: 100
    });

    const matSkyAccent = new THREE.MeshPhongMaterial({
      color: 0x0ea5e9,
      emissive: 0x0284c7,
      specular: 0xffffff,
      shininess: 100
    });

    const matEmeraldAccent = new THREE.MeshPhongMaterial({
      color: 0x10b981,
      emissive: 0x059669,
      specular: 0xa7f3d0,
      shininess: 80
    });

    // -------------------------------------------------------------
    // 5. BUILDING FACTORY FUNCTIONS
    // -------------------------------------------------------------
    const worldGroup = new THREE.Group();
    scene.add(worldGroup);

    function createBuildingBlock(x, y, z, w, h, d, mat, wireMat) {
      const geom = new THREE.BoxGeometry(w, h, d);
      const mesh = new THREE.Mesh(geom, mat);
      mesh.position.set(x, y + h / 2, z);
      worldGroup.add(mesh);

      const edges = new THREE.EdgesGeometry(geom);
      const line = new THREE.LineSegments(edges, wireMat || matWire);
      line.position.copy(mesh.position);
      worldGroup.add(line);
      return mesh;
    }

    // A. Base Foundation Grid
    const groundGrid = new THREE.GridHelper(36, 18, 0x6366f1, 0x1e293b);
    groundGrid.position.y = -0.05;
    worldGroup.add(groundGrid);

    // Base glowing circle
    const baseCircleGeom = new THREE.CircleGeometry(16, 48);
    const baseCircleMat = new THREE.MeshBasicMaterial({
      color: 0x4338ca,
      transparent: true,
      opacity: 0.12,
      side: THREE.DoubleSide
    });
    const baseCircle = new THREE.Mesh(baseCircleGeom, baseCircleMat);
    baseCircle.rotation.x = -Math.PI / 2;
    baseCircle.position.y = -0.08;
    worldGroup.add(baseCircle);

    // B. YADAV LIVING SOCIETY (Left Wing)
    // Tower A (Main Society Skyscraper)
    const towerA = createBuildingBlock(-7, 0, -3, 6, 17, 6, matTowerGlass);
    createBuildingBlock(-7, 17, -3, 4, 1.5, 4, matSkyAccent); // Rooftop Penthouse
    createBuildingBlock(-7, 18.5, -3, 0.4, 3.5, 0.4, matGoldAccent); // Antenna Mast

    // Tower B (Society Block 2)
    const towerB = createBuildingBlock(-7, 0, 5, 5.5, 12, 5.5, matTowerGlassB);
    createBuildingBlock(-7, 12, 5, 3.5, 1.2, 3.5, matEmeraldAccent); // Green Garden Roof

    // Connecting Skybridge between Tower A and Tower B
    createBuildingBlock(-7, 8, 1, 3.2, 1.8, 3.2, matTowerGlass, matWire);

    // C. YADAV PG FOR STAY (Right Wing - Dedicated Student Coliving)
    // 4-Story Modern PG Accommodation Complex
    const pgMain = createBuildingBlock(7, 0, 0, 8.5, 9.5, 7.5, matPGBlock, matGoldWire);
    
    // PG Balcony / Floor Bands (Single, Double, Triple sharing wings)
    createBuildingBlock(7, 2.2, 3.85, 8.2, 0.35, 0.6, matGoldAccent, matGoldWire);
    createBuildingBlock(7, 4.8, 3.85, 8.2, 0.35, 0.6, matGoldAccent, matGoldWire);
    createBuildingBlock(7, 7.2, 3.85, 8.2, 0.35, 0.6, matGoldAccent, matGoldWire);

    // PG Solar Rooftop Canopy & Wi-Fi Communications Beacon
    createBuildingBlock(7, 9.5, 0, 6.5, 0.8, 5.5, matSkyAccent);
    const wifiMast = createBuildingBlock(7, 10.3, 0, 0.3, 4.0, 0.3, matGoldAccent);

    // Animated Wi-Fi Pulse Ring on PG Mast
    const wifiRingGeom = new THREE.TorusGeometry(1.4, 0.08, 8, 28);
    const wifiRingMat = new THREE.MeshBasicMaterial({
      color: 0x38bdf8,
      transparent: true,
      opacity: 0.8
    });
    const wifiRing = new THREE.Mesh(wifiRingGeom, wifiRingMat);
    wifiRing.rotation.x = Math.PI / 2;
    wifiRing.position.set(7, 13.5, 0);
    worldGroup.add(wifiRing);

    // D. MAIN GATE SECURITY & VISITOR REGISTRY (Central Foreground)
    createBuildingBlock(0, 0, 9, 3.5, 2.8, 3.2, matTowerGlass, matWire); // Guard Cabin
    createBuildingBlock(0, 2.8, 9, 4.2, 0.4, 3.8, matGoldAccent); // Roof Canopy
    createBuildingBlock(-3.2, 0, 9, 0.4, 2.2, 0.4, matSkyAccent); // Gate Pillar Left
    createBuildingBlock(3.2, 0, 9, 0.4, 2.2, 0.4, matSkyAccent);  // Gate Pillar Right
    createBuildingBlock(0, 1.1, 9, 5.8, 0.2, 0.2, matGoldAccent); // Security Boom Barrier

    // E. HOLOGRAPHIC ORBITAL RINGS
    const ring1Geom = new THREE.TorusGeometry(18, 0.08, 8, 64);
    const ring1Mat = new THREE.MeshBasicMaterial({
      color: 0x6366f1,
      transparent: true,
      opacity: 0.35
    });
    const ring1 = new THREE.Mesh(ring1Geom, ring1Mat);
    ring1.rotation.x = Math.PI / 2.2;
    ring1.rotation.y = Math.PI / 12;
    worldGroup.add(ring1);

    const ring2Geom = new THREE.TorusGeometry(15, 0.06, 8, 56);
    const ring2Mat = new THREE.MeshBasicMaterial({
      color: 0xf59e0b,
      transparent: true,
      opacity: 0.4
    });
    const ring2 = new THREE.Mesh(ring2Geom, ring2Mat);
    ring2.rotation.x = -Math.PI / 2.4;
    ring2.rotation.y = -Math.PI / 8;
    worldGroup.add(ring2);

    // F. FLOATING DIGITAL DATA PARTICLES
    const particleCount = isMobile ? 40 : 80;
    const particleGeom = new THREE.BufferGeometry();
    const particlePositions = new Float32Array(particleCount * 3);

    for (let i = 0; i < particleCount * 3; i += 3) {
      particlePositions[i] = (Math.random() - 0.5) * 40;
      particlePositions[i + 1] = Math.random() * 22;
      particlePositions[i + 2] = (Math.random() - 0.5) * 40;
    }

    particleGeom.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
    const particleMat = new THREE.PointsMaterial({
      color: 0x38bdf8,
      size: 0.35,
      transparent: true,
      opacity: 0.8
    });
    const particles = new THREE.Points(particleGeom, particleMat);
    worldGroup.add(particles);

    // -------------------------------------------------------------
    // 6. INTERACTIVE CAMERA TWEENING & VIEWS
    // -------------------------------------------------------------
    const cameraPresets = {
      all: {
        pos: { x: 26, y: 22, z: 30 },
        target: { x: 0, y: 3, z: 0 }
      },
      society: {
        pos: { x: -6, y: 18, z: 22 },
        target: { x: -7, y: 8, z: 0 }
      },
      pg: {
        pos: { x: 18, y: 15, z: 18 },
        target: { x: 7, y: 5, z: 0 }
      },
      gate: {
        pos: { x: 0, y: 8, z: 24 },
        target: { x: 0, y: 1.5, z: 9 }
      }
    };

    function renderUserUpdate() {
      if (!prefersReducedMotion && !isMobile) return;
      camera.position.set(destCamPos.x, destCamPos.y, destCamPos.z);
      curTarget.x = destTarget.x;
      curTarget.y = destTarget.y;
      curTarget.z = destTarget.z;
      camera.lookAt(curTarget.x, curTarget.y, curTarget.z);
      renderer.render(scene, camera);
    }

    window.Campus3D.focusView = function (presetName) {
      const preset = cameraPresets[presetName] || cameraPresets.all;
      destCamPos.x = preset.pos.x;
      destCamPos.y = preset.pos.y;
      destCamPos.z = preset.pos.z;
      destTarget.x = preset.target.x;
      destTarget.y = preset.target.y;
      destTarget.z = preset.target.z;

      // Update badge indicator
      const viewIndicator = document.getElementById('current-3d-view-label');
      if (viewIndicator) {
        if (presetName === 'society') viewIndicator.textContent = "Focus: Yadav Living Society";
        else if (presetName === 'pg') viewIndicator.textContent = "Focus: Yadav PG for Stay";
        else if (presetName === 'gate') viewIndicator.textContent = "Focus: Smart Security Gate";
        else viewIndicator.textContent = "Twin-Property Overview";
      }
      renderUserUpdate();
    };

    // -------------------------------------------------------------
    // 7. LIGHTING PRESETS
    // -------------------------------------------------------------
    window.Campus3D.setLightingMode = function (mode) {
      if (mode === 'sunset') {
        ambientLight.color.setHex(0xfef08a);
        ambientLight.intensity = 0.6;
        dirLight1.color.setHex(0xf97316);
        dirLight1.intensity = 2.2;
        dirLight2.color.setHex(0xec4899);
      } else if (mode === 'daylight') {
        ambientLight.color.setHex(0xffffff);
        ambientLight.intensity = 1.0;
        dirLight1.color.setHex(0x60a5fa);
        dirLight1.intensity = 1.6;
        dirLight2.color.setHex(0xe0f2fe);
      } else {
        // Default Cyber Neon
        ambientLight.color.setHex(0xffffff);
        ambientLight.intensity = 0.7;
        dirLight1.color.setHex(0x6366f1);
        dirLight1.intensity = 1.8;
        dirLight2.color.setHex(0x38bdf8);
      }
      renderUserUpdate();
    };

    // -------------------------------------------------------------
    // 8. INTERACTIVE DRAG TO ROTATE & MOUSE PARALLAX
    // -------------------------------------------------------------
    let isAutoRotate = !prefersReducedMotion && !isMobile;
    let isDragging = false;
    let previousMousePosition = { x: 0, y: 0 };
    let mouseParallax = { x: 0, y: 0 };

    function updateRotationButton() {
      const btn = document.getElementById('btn-toggle-rotate');
      if (!btn) return;
      btn.classList.toggle('active', isAutoRotate);
      btn.innerHTML = isAutoRotate
        ? "<i class='bx bx-sync bx-spin'></i> Auto-Spin: ON"
        : "<i class='bx bx-pause-circle'></i> Auto-Spin: OFF";
      btn.setAttribute('aria-pressed', String(isAutoRotate));
    }

    window.Campus3D.toggleRotation = function () {
      isAutoRotate = !isAutoRotate;
      updateRotationButton();
      scheduleRender();
    };

    container.addEventListener('keydown', function (event) {
      const step = 0.12;
      if (event.key === 'ArrowLeft') {
        worldGroup.rotation.y -= step;
      } else if (event.key === 'ArrowRight') {
        worldGroup.rotation.y += step;
      } else if (event.key === 'ArrowUp') {
        destCamPos.y = Math.max(8, destCamPos.y - 1);
      } else if (event.key === 'ArrowDown') {
        destCamPos.y = Math.min(38, destCamPos.y + 1);
      } else if (event.key === 'Home') {
        worldGroup.rotation.y = 0;
        window.Campus3D.focusView('all');
      } else {
        return;
      }

      event.preventDefault();
      renderUserUpdate();
      scheduleRender();
    });

    container.addEventListener('mousedown', function (e) {
      isDragging = true;
      previousMousePosition = { x: e.clientX, y: e.clientY };
    });

    window.addEventListener('mouseup', function () {
      isDragging = false;
    });

    container.addEventListener('mousemove', function (e) {
      const rect = container.getBoundingClientRect();
      mouseParallax.x = ((e.clientX - rect.left) / width) * 2 - 1;
      mouseParallax.y = -(((e.clientY - rect.top) / height) * 2 - 1);

      if (isDragging) {
        const deltaX = e.clientX - previousMousePosition.x;
        const deltaY = e.clientY - previousMousePosition.y;

        worldGroup.rotation.y += deltaX * 0.008;
        destCamPos.y = Math.max(8, Math.min(38, destCamPos.y - deltaY * 0.05));

        previousMousePosition = { x: e.clientX, y: e.clientY };
        renderUserUpdate();
      }
    });

    // Touch support for mobile devices
    container.addEventListener('touchstart', function (e) {
      if (e.touches.length === 1) {
        isDragging = true;
        previousMousePosition = { x: e.touches[0].clientX, y: e.touches[0].clientY };
      }
    }, { passive: true });

    window.addEventListener('touchend', function () {
      isDragging = false;
    });

    container.addEventListener('touchmove', function (e) {
      if (isDragging && e.touches.length === 1) {
        const deltaX = e.touches[0].clientX - previousMousePosition.x;
        const deltaY = e.touches[0].clientY - previousMousePosition.y;

        worldGroup.rotation.y += deltaX * 0.01;
        destCamPos.y = Math.max(8, Math.min(38, destCamPos.y - deltaY * 0.06));

        previousMousePosition = { x: e.touches[0].clientX, y: e.touches[0].clientY };
        renderUserUpdate();
      }
    }, { passive: true });

    // Responsive Canvas Resize
    let resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        width = container.clientWidth;
        height = container.clientHeight;
        if (width > 0 && height > 0) {
          camera.aspect = width / height;
          camera.updateProjectionMatrix();
          renderer.setSize(width, height);
          if (prefersReducedMotion || isMobile) renderer.render(scene, camera);
        }
      }, 150);
    });

    // -------------------------------------------------------------
    // 9. ANIMATION LOOP WITH THROTTLING & BATTERY SAVING
    // -------------------------------------------------------------
    let isVisible = isMobile;
    let isTabFocused = !document.hidden;
    let frameRequest = 0;
    function scheduleRender() {
      if ((prefersReducedMotion || isMobile) && !isAutoRotate) return;
      if (!frameRequest && isVisible && isTabFocused) {
        frameRequest = requestAnimationFrame(renderLoop);
      }
    }

    function updateRenderActivity() {
      if (isVisible && isTabFocused) {
        scheduleRender();
      } else if (frameRequest) {
        cancelAnimationFrame(frameRequest);
        frameRequest = 0;
      }
    }

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function (entries) {
        isVisible = entries[0].isIntersecting;
        updateRenderActivity();
      }, { threshold: 0.1 });
      observer.observe(container);
    } else {
      isVisible = true;
    }

    document.addEventListener('visibilitychange', function () {
      isTabFocused = !document.hidden;
      updateRenderActivity();
    });

    updateRotationButton();
    if (prefersReducedMotion || isMobile) renderer.render(scene, camera);

    let pulseScale = 1;
    let pulseDirection = 1;
    let lastTime = 0;
    const targetFps = isMobile ? 30 : 60;
    const frameInterval = 1000 / targetFps;

    function renderLoop(currentTime) {
      frameRequest = 0;

      if (!isVisible || !isTabFocused) return;

      const elapsed = currentTime - lastTime;
      if (elapsed < frameInterval) {
        scheduleRender();
        return;
      }
      lastTime = currentTime - (elapsed % frameInterval);

      // Auto rotation
      if (isAutoRotate && !isDragging) {
        worldGroup.rotation.y += 0.0035;
      }

      // Orbital rings rotation
      if (!prefersReducedMotion) {
        ring1.rotation.z += 0.005;
        ring2.rotation.z -= 0.006;

        // Wi-Fi wave pulsation on Yadav PG roof
        pulseScale += 0.015 * pulseDirection;
        if (pulseScale > 1.8) {
          pulseScale = 0.8;
        }
        wifiRing.scale.set(pulseScale, pulseScale, 1);
        wifiRingMat.opacity = Math.max(0, 1 - (pulseScale - 0.8) / 1.0);
      }

      // Smooth camera position interpolation
      camera.position.x += (destCamPos.x + mouseParallax.x * 2.5 - camera.position.x) * 0.06;
      camera.position.y += (destCamPos.y + mouseParallax.y * 1.8 - camera.position.y) * 0.06;
      camera.position.z += (destCamPos.z - camera.position.z) * 0.06;

      curTarget.x += (destTarget.x - curTarget.x) * 0.06;
      curTarget.y += (destTarget.y - curTarget.y) * 0.06;
      curTarget.z += (destTarget.z - curTarget.z) * 0.06;

      camera.lookAt(curTarget.x, curTarget.y, curTarget.z);
      renderer.render(scene, camera);
      scheduleRender();
    }

    scheduleRender();
  }

  // -------------------------------------------------------------
  // 10. INTERACTIVE 3D CARD TILT WITH SPECULAR GLARE
  // -------------------------------------------------------------
  function init3DCardTilt() {
    const cards = document.querySelectorAll('.property-card');
    const supportsHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!cards.length || !supportsHover || prefersReducedMotion) return;

    cards.forEach(card => {
      // Add specular glare element if not present
      let glare = card.querySelector('.card-specular-glare');
      if (!glare) {
        glare = document.createElement('div');
        glare.className = 'card-specular-glare';
        card.appendChild(glare);
      }

      card.addEventListener('mousemove', function (e) {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = ((y - centerY) / centerY) * -9; // Max 9 deg
        const rotateY = ((x - centerX) / centerX) * 9;

        card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) scale3d(1.02, 1.02, 1.02)`;

        // Position specular glare
        glare.style.opacity = '1';
        glare.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255, 255, 255, 0.22) 0%, transparent 60%)`;
      });

      card.addEventListener('mouseleave', function () {
        card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
        glare.style.opacity = '0';
      });
    });
  }

  // -------------------------------------------------------------
  // 11. INITIALIZATION DISPATCHER
  // -------------------------------------------------------------
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(function () {
        initCampusHero();
        init3DCardTilt();
      }, 100);
    });
  } else {
    setTimeout(function () {
      initCampusHero();
      init3DCardTilt();
    }, 100);
  }
})();
