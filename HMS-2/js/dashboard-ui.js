document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const societySidebar = document.querySelector('.sidebar');
    const societyToggle = document.querySelector('.sidebarBtn');
    const societyBackdrop = document.getElementById('societyNavBackdrop');
    const pgToggle = document.getElementById('pgNavToggle');
    const pgBackdrop = document.getElementById('pgNavBackdrop');

    const societyMobileQuery = window.matchMedia('(max-width: 700px)');
    const setSocietyNavOpen = (isOpen) => {
        if (!societySidebar || !societyToggle) return;
        const isMobile = societyMobileQuery.matches;
        societySidebar.classList.toggle('active', isMobile ? isOpen : !isOpen);
        body.classList.toggle('society-nav-open', isMobile && isOpen);
        societyToggle.setAttribute('aria-expanded', String(isOpen));
        societyToggle.setAttribute('aria-label', isOpen ? 'Close dashboard navigation' : 'Open dashboard navigation');
        const icon = societyToggle.querySelector('i');
        icon?.classList.toggle('bx-menu-alt-right', isOpen);
        icon?.classList.toggle('bx-menu', !isOpen);
    };

    const closeSocietyNav = () => {
        setSocietyNavOpen(false);
        societyToggle?.focus();
    };

    if (societySidebar && societyToggle) {
        let isMobileSociety = societyMobileQuery.matches;
        const getSocietyNavOpen = () => societyMobileQuery.matches
            ? societySidebar.classList.contains('active')
            : !societySidebar.classList.contains('active');

        societyToggle.addEventListener('click', () => {
            const shouldOpen = !getSocietyNavOpen();
            setSocietyNavOpen(shouldOpen);
            if (shouldOpen && societyMobileQuery.matches) {
                societySidebar.querySelector('.nav-links a')?.focus();
            }
        });
        societyBackdrop?.addEventListener('click', closeSocietyNav);
        societySidebar.querySelectorAll('.nav-links a').forEach((link) => {
            link.addEventListener('click', () => {
                if (societyMobileQuery.matches) closeSocietyNav();
            });
        });

        const handleSocietyBreakpoint = () => {
            const isMobile = societyMobileQuery.matches;
            if (isMobile === isMobileSociety) return;
            isMobileSociety = isMobile;
            societySidebar.classList.remove('active');
            body.classList.remove('society-nav-open');
            setSocietyNavOpen(!isMobile);
        };
        if (societyMobileQuery.addEventListener) {
            societyMobileQuery.addEventListener('change', handleSocietyBreakpoint);
        } else {
            societyMobileQuery.addListener(handleSocietyBreakpoint);
        }
        window.addEventListener('resize', handleSocietyBreakpoint, { passive: true });
        setSocietyNavOpen(!societyMobileQuery.matches);
    }

    const setPgNavOpen = (isOpen) => {
        body.classList.toggle('pg-nav-open', isOpen);
        pgToggle?.setAttribute('aria-expanded', String(isOpen));
    };

    if (pgToggle && pgBackdrop) {
        pgToggle.addEventListener('click', () => {
            setPgNavOpen(!body.classList.contains('pg-nav-open'));
        });
        pgBackdrop.addEventListener('click', () => setPgNavOpen(false));
        document.querySelectorAll('.admin-sidebar a').forEach((link) => {
            link.addEventListener('click', () => setPgNavOpen(false));
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (societyMobileQuery.matches && body.classList.contains('society-nav-open')) closeSocietyNav();
            setPgNavOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (!window.matchMedia('(max-width: 760px)').matches) setPgNavOpen(false);
    }, { passive: true });

    const societyScene = document.getElementById('canvas-3d-container');
    if (societyScene && !window.matchMedia('(max-width: 768px)').matches) {
        const sceneScript = document.createElement('script');
        sceneScript.src = '../../js/3d_society.js';
        sceneScript.onerror = () => console.error('Unable to load the Yadav Living 3D dashboard scene.');

        const threeScript = document.createElement('script');
        threeScript.src = '../../js/vendor/three.min.js';
        threeScript.onload = () => document.body.appendChild(sceneScript);
        threeScript.onerror = () => console.error('Unable to load the Three.js dashboard renderer.');
        document.body.appendChild(threeScript);
    }

    const animatedEls = document.querySelectorAll('[data-animate]');

    if (!animatedEls.length || !('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const delay = Number(el.dataset.delay || 0);
                el.style.transition = 'opacity 500ms ease, transform 500ms ease';
                el.style.transitionDelay = `${delay}ms`;
                el.style.opacity = '1';
                el.style.transform = 'translateY(0) scale(1)';
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.14 });

    animatedEls.forEach((el) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(18px) scale(0.98)';
        observer.observe(el);
    });

});
