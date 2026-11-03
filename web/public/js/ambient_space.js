/**
 * ==============================================================================
 * 🌌 AMBIENT FUTURISTIC CYBER-SPACE MATRIX
 * ==============================================================================
 * Engineered by Yuvraj for Yadav Living
 * Lightweight interactive starfield & data constellation network (30 FPS, ~0.1% CPU)
 * ==============================================================================
 */

(function () {
  'use strict';

  function initAmbientSpace() {
    const canvas = document.getElementById('ambient-matrix-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let width = (canvas.width = window.innerWidth);
    let height = (canvas.height = window.innerHeight);

    // Particle count: 35 on mobile, 60 on desktop
    const count = window.innerWidth < 768 ? 30 : 60;
    const particles = [];
    const colors = ['#6366f1', '#38bdf8', '#818cf8', '#f59e0b', '#3b82f6'];

    for (let i = 0; i < count; i++) {
      particles.push({
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.45,
        vy: (Math.random() - 0.5) * 0.45,
        radius: Math.random() * 1.8 + 0.6,
        color: colors[Math.floor(Math.random() * colors.length)],
        alpha: Math.random() * 0.6 + 0.2,
        pulseSpeed: Math.random() * 0.02 + 0.01,
        pulseAngle: Math.random() * Math.PI * 2
      });
    }

    // Mouse tracking for subtle magnetic parallax
    let mouse = { x: -9999, y: -9999 };
    window.addEventListener('mousemove', function (e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
    }, { passive: true });

    window.addEventListener('resize', function () {
      width = canvas.width = window.innerWidth;
      height = canvas.height = window.innerHeight;
    }, { passive: true });

    let isVisible = true;
    let isTabFocused = true;

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function (entries) {
        isVisible = entries[0].isIntersecting;
      }, { threshold: 0.05 });
      observer.observe(canvas);
    }

    document.addEventListener('visibilitychange', function () {
      isTabFocused = !document.hidden;
    });

    let lastTime = 0;
    const fpsInterval = 1000 / 30; // 30 FPS for minimal CPU/GPU

    function draw(currentTime) {
      requestAnimationFrame(draw);

      if (!isVisible || !isTabFocused) return;

      const elapsed = currentTime - lastTime;
      if (elapsed < fpsInterval) return;
      lastTime = currentTime - (elapsed % fpsInterval);

      ctx.clearRect(0, 0, width, height);

      // Connect close particles with subtle cybernetic lines
      const maxDistance = 110;
      for (let i = 0; i < count; i++) {
        for (let j = i + 1; j < count; j++) {
          const dx = particles[i].x - particles[j].x;
          const dy = particles[i].y - particles[j].y;
          const dist = Math.sqrt(dx * dx + dy * dy);

          if (dist < maxDistance) {
            const lineAlpha = (1 - dist / maxDistance) * 0.16;
            ctx.beginPath();
            ctx.strokeStyle = `rgba(99, 102, 241, ${lineAlpha})`;
            ctx.lineWidth = 0.65;
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.stroke();
          }
        }
      }

      // Update and draw particles
      for (let i = 0; i < count; i++) {
        const p = particles[i];

        p.x += p.vx;
        p.y += p.vy;

        // Wrap around boundaries
        if (p.x < -10) p.x = width + 10;
        else if (p.x > width + 10) p.x = -10;
        if (p.y < -10) p.y = height + 10;
        else if (p.y > height + 10) p.y = -10;

        // Subtle mouse push
        const mdx = p.x - mouse.x;
        const mdy = p.y - mouse.y;
        const mdist = Math.sqrt(mdx * mdx + mdy * mdy);
        if (mdist < 90 && mdist > 0) {
          const force = (90 - mdist) / 90 * 0.8;
          p.x += (mdx / mdist) * force;
          p.y += (mdy / mdist) * force;
        }

        // Pulse alpha
        p.pulseAngle += p.pulseSpeed;
        const currentAlpha = p.alpha + Math.sin(p.pulseAngle) * 0.2;

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = Math.max(0.1, Math.min(0.85, currentAlpha));
        ctx.shadowColor = p.color;
        ctx.shadowBlur = p.radius * 3;
        ctx.fill();
        ctx.shadowBlur = 0;
        ctx.globalAlpha = 1.0;
      }
    }

    requestAnimationFrame(draw);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAmbientSpace);
  } else {
    initAmbientSpace();
  }
})();
