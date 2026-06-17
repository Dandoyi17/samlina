// drivers_login.js - client logic for driver login page
document.addEventListener('DOMContentLoaded', () => {
    const SIMULATE_AUTH = true; // set to false to let form POST to `driver_login.php`
    const demoCredentials = [{ username: 'driver1', password: 'password123', id: 'D-1001' }];

    // Elements
    const yearEl = document.getElementById('year');
    const hamburger = document.getElementById('hamburger');
    const topNav = document.getElementById('topNav');

    const loginForm = document.getElementById('loginForm');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const remember = document.getElementById('remember');
    const loginMessage = document.getElementById('loginMessage');
    const demoBtn = document.getElementById('demoBtn');
    const loginBtn = document.getElementById('loginBtn');

    // Small helper to set button loading state
    function setButtonLoading(on, text) {
        if (!loginBtn) return;
        if (on) {
            loginBtn.disabled = true;
            loginBtn.setAttribute('aria-busy', 'true');
            loginBtn.dataset.origText = loginBtn.querySelector('.btn-text') ? loginBtn.querySelector('.btn-text').textContent : loginBtn.textContent;
            const spinner = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
            const txt = text || 'Signing in…';
            // keep icon if present, replace button text
            const iconSpan = loginBtn.querySelector('.btn-icon') ? loginBtn.querySelector('.btn-icon').outerHTML : '';
            loginBtn.innerHTML = iconSpan + '<span class="btn-text">' + spinner + ' ' + txt + '</span>';
        } else {
            loginBtn.disabled = false;
            loginBtn.setAttribute('aria-busy', 'false');
            const orig = loginBtn.dataset.origText || 'Sign in';
            const iconSpan = '<span class="btn-icon" aria-hidden="true"><i class="fas fa-sign-in-alt"></i></span>';
            loginBtn.innerHTML = iconSpan + '<span class="btn-text">' + orig + '</span>';
            delete loginBtn.dataset.origText;
        }
    }

    // init
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    // hamburger toggle
    if (hamburger && topNav) {
        hamburger.addEventListener('click', () => {
            const open = topNav.classList.toggle('open');
            topNav.setAttribute('aria-hidden', String(!open));
        });
    }

    // show / hide password
    if (togglePassword && password) {
        togglePassword.addEventListener('click', () => {
            const t = password;
            if (t.type === 'password') {
                t.type = 'text';
                togglePassword.innerHTML = '<i class="far fa-eye-slash"></i>';
            } else {
                t.type = 'password';
                togglePassword.innerHTML = '<i class="far fa-eye"></i>';
            }
        });
    }

    // restore remembered username
    if (localStorage.getItem('driver_remember') && username) {
        username.value = localStorage.getItem('driver_remember');
        if (remember) remember.checked = true;
    }

    // demo login (guarded)
    if (demoBtn) {
        demoBtn.addEventListener('click', () => {
            username.value = demoCredentials[0].username;
            password.value = demoCredentials[0].password;
            loginForm.dispatchEvent(new Event('submit', { cancelable: true }));
        });
    }

    // form submit
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            loginMessage.textContent = '';
            // basic client validation
            if (!username.value.trim() || !password.value.trim()) {
                e.preventDefault();
                loginMessage.textContent = 'Username and password are required.';
                loginMessage.style.color = '#b71c1c';
                return false;
            }

            // set loading state to avoid double submits
            setButtonLoading(true);

            // fallback: if nothing happens within 10s re-enable the button (safe demo)
            const fallback = setTimeout(() => setButtonLoading(false), 10000);

            if (SIMULATE_AUTH) {
                e.preventDefault();
                // simple check against demo credentials
                const found = demoCredentials.find(d => (d.username === username.value || d.id === username.value) && d.password === password.value);
                if (!found) {
                    clearTimeout(fallback);
                    setButtonLoading(false);
                    loginMessage.textContent = 'Invalid login — try demo credentials or your account.';
                    loginMessage.style.color = '#b71c1c';
                    return false;
                }
                // remember?
                if (remember && remember.checked) localStorage.setItem('driver_remember', username.value);
                else localStorage.removeItem('driver_remember');

                // simulate setting a login token and redirect to dashboard
                localStorage.setItem('driverLoggedIn', found.id || found.username);
                loginMessage.textContent = 'Login successful — redirecting to driver dashboard...';
                loginMessage.style.color = '#0b7a47';
                setTimeout(() => {
                    window.location.href = 'drivers-dashboard.php'; // change this to your real dashboard path
                }, 700);
                return false;
            }

            // If SIMULATE_AUTH = false, the form will POST to driver_login.php (action attribute).
            // On successful server-side auth, server should redirect to drivers-dashboard.php.
            // Keep the button disabled while the request completes.
            return true;
        });
    }
});