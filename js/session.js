export function checkUserSession() {
    fetch('backend.php?check_login_status=1')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                window.location.href = "index.html";
            }
        })
        .catch(error => console.error('Session check failed:', error));
}

export function preloadUserInfo() {
    if (!document.getElementById('first_name')) return;
    fetch('backend.php?action=get_user_info')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                window.location.href = "index.html";
            } else {
                document.getElementById('first_name').value = data.first_name;
                document.getElementById('last_name').value = data.last_name;
                document.getElementById('email').value = data.email;
                document.getElementById('phone_number').value = data.phone_number;
            }
        })
        .catch(err => console.error('Error fetching user data:', err));
}

export function submitInquiryForm(formId, errorContainerId, backendUrl) {
    const form = document.getElementById(formId);
    const errorDiv = document.getElementById(errorContainerId);

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';

        const formData = new FormData(form);
        formData.set('action', 'submit_inquiry'); // Ensure correct action value

        // Basic checks
        if (!formData.get('gov_id').name) {
            return showError('Please upload your government-issued ID.');
        }
        if (!formData.get('move_in_date')) {
            return showError('Please select a move-in date.');
        }
        if (!formData.get('agree_rules')) {
            return showError('You must agree to the Rules and Regulations.');
        }

        fetch(backendUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Inquiry submitted successfully!');
                form.reset();
            } else {
                showError(data.message);
            }
        })
        .catch(err => {
            console.error('Submission error:', err);
            showError('An error occurred during submission.');
        });

        function showError(message) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    });
}