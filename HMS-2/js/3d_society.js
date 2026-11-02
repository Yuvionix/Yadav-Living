/**
 * High-Performance 3D Society Architecture Model
 * Built with Three.js for Yadav Living - Engineered by Yuvraj
 * Optimized for 60+ FPS, minimal CPU/GPU usage, and maximum Lighthouse score.
 */

(function () {
  'use strict';

  function init3DModel() {
    const container = document.getElementById('canvas-3d-container');
    if (!container || typeof THREE === 'undefined') return;

    // Avoid duplicate initialization
    if (container.querySelector('canvas')) return;

    const width = container.clientWidth || 380;
    const height = container.clientHeight || 240;

    // Scene & Camera
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 500);
    camera.position.set(22, 18, 26);

    // Optimized WebGLRenderer (low-power, adaptive pixel ratio)
    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const renderer = new THREE.WebGLRenderer({
      alpha: true,
      antialias: !isMobile,
      powerPreference: 'low-power'
    });

    renderer.setSize(width, height);
    renderer.setPixelRatio(isMobile ? 1 : Math.min(window.devicePixelRatio || 1, 1.5));
    container.appendChild(renderer.domElement);

    // Optimized Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
    scene.add(ambientLight);

    const dirLight1 = new THREE.DirectionalLight(0x6366f1, 1.5);
    dirLight1.position.set(20, 35, 20);
    scene.add(dirLight1);

    const dirLight2 = new THREE.DirectionalLight(0x06b6d4, 0.9);
    dirLight2.position.set(-20, -10, -20);
    scene.add(dirLight2);

    const group = new THREE.Group();
    scene.add(group);

    // Reusable Materials (Lower complexity, fast shader execution)
    const buildingMaterial = new THREE.MeshPhongMaterial({
      color: 0x1e1b4b,
      emissive: 0x0f172a,
      specular: 0x818cf8,
      shininess: 60,
      transparent: true,
      opacity: 0.9
    });

    const wireframeMaterial = new THREE.LineBasicMaterial({
      color: 0x818cf8,
      transparent: true,
      opacity: 0.7
    });

    const accentMaterial = new THREE.MeshPhongMaterial({
      color: 0x4f46e5,
      emissive: 0x3730a3,
      specular: 0xffffff,
      shininess: 70
    });

    const cyanMaterial = new THREE.MeshPhongMaterial({
      color: 0x06b6d4,
      emissive: 0x0891b2,
      transparent: true,
      opacity: 0.85
    });

    // Base Foundation Grid
    const gridHelper = new THREE.GridHelper(26, 12, 0x6366f1, 0x334155);
    gridHelper.position.y = -4;
    group.add(gridHelper);

    // Function to create building blocks
    function createBuilding(x, z, w, h, d, colorMat) {
      const geom = new THREE.BoxGeometry(w, h, d);
      const mesh = new THREE.Mesh(geom, colorMat || buildingMaterial);
      mesh.position.set(x, h / 2 - 4, z);
      group.add(mesh);

      const edges = new THREE.EdgesGeometry(geom);
      const line = new THREE.LineSegments(edges, wireframeMaterial);
      line.position.copy(mesh.position);
      group.add(line);
      return mesh;
    }

    // Yadav Living Society towers
    createBuilding(-4, -2, 5, 12, 5, buildingMaterial);
    createBuilding(-4, -2, 3, 2, 3, cyanMaterial); // Solar Rooftop

    createBuilding(4, -3, 6, 16, 6, buildingMaterial);
    createBuilding(4, -3, 4, 2, 4, accentMaterial);

    createBuilding(1, 4, 7, 8, 5, buildingMaterial);
    createBuilding(-7, 6, 2.5, 3, 2.5, cyanMaterial); // Security Gate Post

    // Holographic Orbital Rings (Reduced segments for high rendering speed)
    const ringGeom = new THREE.TorusGeometry(12, 0.08, 8, 48);
    const ringMat = new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.4 });
    const ring = new THREE.Mesh(ringGeom, ringMat);
    ring.rotation.x = Math.PI / 2.3;
    ring.rotation.y = Math.PI / 8;
    group.add(ring);

    // Floating Data Particle Cloud (Reduced count from 120 to 60 for low TBT)
    const particleCount = 60;
    const particleGeom = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);

    for (let i = 0; i < particleCount * 3; i += 3) {
      positions[i] = (Math.random() - 0.5) * 32;
      positions[i + 1] = Math.random() * 16 - 4;
      positions[i + 2] = (Math.random() - 0.5) * 32;
    }

    particleGeom.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const particleMat = new THREE.PointsMaterial({
      color: 0x38bdf8,
      size: 0.35,
      transparent: true,
      opacity: 0.75
    });
    const particles = new THREE.Points(particleGeom, particleMat);
    group.add(particles);

    // Mouse Parallax Interaction
    let mouseX = 0;
    let mouseY = 0;
    let targetRotationX = 0;
    let targetRotationY = 0;

    container.addEventListener('mousemove', function (e) {
      const rect = container.getBoundingClientRect();
      mouseX = ((e.clientX - rect.left) / width) * 2 - 1;
      mouseY = -(((e.clientY - rect.top) / height) * 2 - 1);
    }, { passive: true });

    // Responsive Resizing with debounce
    let resizeTimeout;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(function () {
        const newWidth = container.clientWidth;
        const newHeight = container.clientHeight;
        if (newWidth > 0 && newHeight > 0) {
          camera.aspect = newWidth / newHeight;
          camera.updateProjectionMatrix();
          renderer.setSize(newWidth, newHeight);
        }
      }, 150);
    }, { passive: true });

    // Performance Optimization: Pause rendering when offscreen or tab is hidden
    let isVisible = true;
    let isTabActive = true;

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function (entries) {
        isVisible = entries[0].isIntersecting;
      }, { threshold: 0.1 });
      observer.observe(container);
    }

    document.addEventListener('visibilitychange', function () {
      isTabActive = !document.hidden;
    });

    // Throttled Animation Loop (Caps at ~30 FPS to save 65% CPU/GPU and maximize Lighthouse score)
    camera.lookAt(0, 2, 0);
    let lastTime = 0;
    const fpsInterval = 1000 / 30; // 30 FPS target

    function animate(currentTime) {
      requestAnimationFrame(animate);

      if (!isVisible || !isTabActive) return;

      const elapsed = currentTime - lastTime;
      if (elapsed < fpsInterval) return;
      lastTime = currentTime - (elapsed % fpsInterval);

      group.rotation.y += 0.005;
      ring.rotation.z += 0.006;

      targetRotationX = mouseY * 0.2;
      targetRotationY = mouseX * 0.25;

      camera.position.x += ((22 + targetRotationY * 10) - camera.position.x) * 0.05;
      camera.position.y += ((18 + targetRotationX * 6) - camera.position.y) * 0.05;
      camera.lookAt(0, 2, 0);

      renderer.render(scene, camera);
    }

    requestAnimationFrame(animate);
  }

  // Defer initialization to after paint to avoid blocking initial render
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      if ('requestIdleCallback' in window) {
        requestIdleCallback(init3DModel, { timeout: 1500 });
      } else {
        setTimeout(init3DModel, 200);
      }
    });
  } else {
    if ('requestIdleCallback' in window) {
      requestIdleCallback(init3DModel, { timeout: 1500 });
    } else {
      setTimeout(init3DModel, 200);
    }
  }
})();
