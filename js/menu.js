export function initMenuUpdate() {
    fetch('backend.php?check_login_status=1')
        .then(response => response.json())
        .then(data => {
            const loginItem = document.getElementById('menu-content-1');
            const signupItem = document.getElementById('menu-content-2');
            if (!loginItem || !signupItem) return;

            if (data.logged_in) {
                loginItem.style.display = 'block';
                signupItem.style.display = 'none';

                const loginLink = loginItem.querySelector('a');
                loginLink.textContent = 'Logout';
                loginLink.href = '#';
                loginLink.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetch('backend.php?action=logout').then(response => {
                        if (response.ok) {
                            window.location.href = 'index.html';
                        } else {
                            alert('Logout failed. Please try again.');
                        }
                    });
                });
            } else {
                loginItem.style.display = 'block';
                signupItem.style.display = 'block';
                loginItem.querySelector('a').textContent = 'Login';
                loginItem.querySelector('a').href = 'index.html';
                signupItem.querySelector('a').textContent = 'Create an Account';
                signupItem.querySelector('a').href = 'signup.html';
            }
        });
}
