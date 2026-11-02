document.addEventListener('DOMContentLoaded', () => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const forms = document.querySelectorAll('.form, .login-card, .reg-card, .admin-card');
    if (!reducedMotion) {
        forms.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(12px)';
            requestAnimationFrame(() => {
                card.style.transition = 'opacity 400ms ease, transform 400ms ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
                card.style.transitionDelay = `${index * 80}ms`;
            });
        });
    }

    document.querySelectorAll('input[type="password"]').forEach((input) => {
        const wrapper = input.closest('.input-wrap, .auth-password-wrap');
        if (!wrapper) return;
        if (wrapper.querySelector('.pwd-toggle, .password-toggle-btn')) return;

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'password-toggle-btn';
        toggle.setAttribute('aria-label', 'Toggle password visibility');
        toggle.innerHTML = '<i class="bx bx-show"></i>';
        toggle.style.position = 'absolute';
        toggle.style.right = '12px';
        toggle.style.top = '50%';
        toggle.style.transform = 'translateY(-50%)';
        toggle.style.border = 'none';
        toggle.style.background = 'transparent';
        toggle.style.color = '#64748b';
        toggle.style.cursor = 'pointer';
        toggle.style.fontSize = '18px';
        wrapper.style.position = 'relative';
        wrapper.appendChild(toggle);
        input.style.paddingRight = '44px';

        toggle.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = toggle.querySelector('i');
            icon.className = isPassword ? 'bx bx-hide' : 'bx bx-show';
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });

    document.querySelectorAll('form[data-password-match]').forEach((form) => {
        const password = form.querySelector('input[name="password"]');
        const confirmation = form.querySelector('input[name="confirm_password"]');
        const feedback = form.querySelector('[data-password-feedback]');
        if (!password || !confirmation || !feedback) return;

        const validateMatch = () => {
            const matches = !confirmation.value || password.value === confirmation.value;
            confirmation.setCustomValidity(matches ? '' : 'Passwords do not match.');
            confirmation.setAttribute('aria-invalid', String(!matches));
            confirmation.classList.toggle('is-invalid', !matches);
            feedback.textContent = matches || !confirmation.value ? '' : 'Passwords do not match yet.';
            feedback.classList.toggle('is-error', !matches && Boolean(confirmation.value));
        };

        password.addEventListener('input', validateMatch);
        confirmation.addEventListener('input', validateMatch);
        form.addEventListener('submit', (event) => {
            validateMatch();
            if (!form.checkValidity()) return;

            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                if (submitButton instanceof HTMLInputElement) {
                    submitButton.value = 'Creating account...';
                } else {
                    submitButton.textContent = 'Creating account...';
                }
            }
        });
    });
});
