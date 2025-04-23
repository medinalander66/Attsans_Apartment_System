import { showError } from './utils.js';

export function initLoginForm() {
    const form = document.getElementById('login-form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('action', 'login'); // ← ADD THIS LINE
    
        try {
            const res = await fetch('backend.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.href = 'propertiesSection.html';
            } else {
                showError('Invalid Username or Password.');
            }
        } catch (err) {
            showError('Something went wrong.');
            console.error(err);
        }
    });
}

export function initSignupForm() {
    const form = document.getElementById('signup-details');
    if (!form) return;

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('signup') === 'success') {
        const msg = document.getElementById('signup-success-msg');
        if (msg) msg.style.display = 'block';
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('action', 'signup');

        try {
            const res = await fetch('backend.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                window.location.href = "index.html?signup=success";
            } else {
                showError(data.error || 'Something went wrong.');
            }
        } catch (err) {
            showError('Network error. Please try again later.');
        }
    });
}


export function handleSignupSuccessMessage() {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = document.getElementById('signup-success-msg');
    
    if (urlParams.get('signup') === 'success' && msg) {
        msg.style.display = 'block';
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}
