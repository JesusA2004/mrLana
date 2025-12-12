document.addEventListener('DOMContentLoaded', () => {
    // --- Inputs y botones ojo ---
    const pairs = [
        {
            inputId: 'update_password_current_password',
            toggleId: 'toggle-current-password',
        },
        {
            inputId: 'update_password_password',
            toggleId: 'toggle-new-password',
        },
        {
            inputId: 'update_password_password_confirmation',
            toggleId: 'toggle-confirm-password',
        },
    ];

    pairs.forEach(({ inputId, toggleId }) => {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);

        if (!input || !toggle) return;

        toggle.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            // micro animación
            toggle.classList.add('scale-110');
            setTimeout(() => toggle.classList.remove('scale-110'), 120);
        });
    });

    // --- Barra de fuerza de nueva contraseña ---
    const passwordInput = document.getElementById('update_password_password');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');

    // regex base: mínimo 8, 1 mayúscula, 1 número
    const baseRegex = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', () => {
            const value = passwordInput.value || '';
            const score = calcScore(value);

            strengthBar.classList.remove('bg-red-500', 'bg-yellow-400', 'bg-green-500');

            if (value.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent =
                    'Mínimo 8 caracteres, al menos 1 mayúscula y 1 número.';
                strengthText.className = 'text-xs text-slate-400';
                return;
            }

            switch (score) {
                case 0:
                case 1:
                    strengthBar.style.width = '33%';
                    strengthBar.classList.add('bg-red-500');
                    strengthText.textContent = 'Contraseña muy débil.';
                    strengthText.className = 'text-xs text-red-400';
                    break;

                case 2:
                    strengthBar.style.width = '66%';
                    strengthBar.classList.add('bg-yellow-400');
                    strengthText.textContent = 'Contraseña aceptable, puedes mejorarla.';
                    strengthText.className = 'text-xs text-amber-300';
                    break;

                default:
                    strengthBar.style.width = '100%';
                    strengthBar.classList.add('bg-green-500');

                    if (baseRegex.test(value)) {
                        strengthText.textContent = 'Contraseña segura ✔';
                        strengthText.className = 'text-xs text-emerald-300';
                    } else {
                        strengthText.textContent =
                            'Casi listo. Asegúrate de tener 1 mayúscula y 1 número.';
                        strengthText.className = 'text-xs text-amber-300';
                    }
                    break;
            }
        });
    }

    // --- Validación en cliente al enviar el formulario ---
    const form = document.getElementById('update-password-form');
    const currentInput = document.getElementById('update_password_current_password');
    const confirmInput = document.getElementById('update_password_password_confirmation');
    const clientErrors = document.getElementById('password-client-errors');
    const submitButton = document.getElementById('update-password-submit');

    if (form && currentInput && passwordInput && confirmInput && clientErrors && submitButton) {
        form.addEventListener('submit', (e) => {
            const errors = [];

            // limpiamos estados visuales previos
            [currentInput, passwordInput, confirmInput].forEach((el) => {
                el.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
            });
            clientErrors.innerHTML = '';

            const current = currentInput.value.trim();
            const pwd = passwordInput.value.trim();
            const confirm = confirmInput.value.trim();

            // campos vacíos
            if (!current) {
                errors.push('La contraseña actual es obligatoria.');
                markInvalid(currentInput);
            }

            if (!pwd) {
                errors.push('La nueva contraseña es obligatoria.');
                markInvalid(passwordInput);
            }

            if (!confirm) {
                errors.push('La confirmación de contraseña es obligatoria.');
                markInvalid(confirmInput);
            }

            // si ya hay algo en pwd, validamos regla base
            if (pwd && !baseRegex.test(pwd)) {
                errors.push('La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.');
                markInvalid(passwordInput);
            }

            // coincidencia nueva contraseña vs confirmación
            if (pwd && confirm && pwd !== confirm) {
                errors.push('La nueva contraseña y la confirmación no coinciden.');
                markInvalid(passwordInput);
                markInvalid(confirmInput);
            }

            if (errors.length > 0) {
                e.preventDefault(); // no mandamos nada al servidor

                clientErrors.innerHTML = errors
                    .map((msg) => `<p>• ${msg}</p>`)
                    .join('');

                clientErrors.classList.add('mt-2');

                // pequeña animación tipo "shake" suave usando translate-x
                form.classList.add('transition-transform', 'duration-150');
                form.classList.add('translate-x-0');
                form.classList.add('motion-safe:animate-none'); // por si tienes animaciones

                form.classList.add('shake-temp');
                setTimeout(() => form.classList.remove('shake-temp'), 200);

                return;
            }

            // opcional: deshabilitamos botón para evitar doble submit
            submitButton.setAttribute('disabled', 'disabled');
            submitButton.classList.add('opacity-70', 'cursor-not-allowed');
        });
    }

    function calcScore(value) {
        let score = 0;
        if (value.length >= 8) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++; // extra por símbolo
        return score;
    }

    function markInvalid(input) {
        input.classList.add('border-red-500', 'ring-1', 'ring-red-500');
    }
});
