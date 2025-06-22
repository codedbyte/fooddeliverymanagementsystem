// public_html/js/auth.js
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginMessage = document.getElementById('loginMessage');
    const registerMessage = document.getElementById('registerMessage');
    const authButton = document.getElementById('authButton');
    const logoutButton = document.getElementById('logoutButton');

    // Check session status on page load (for all pages with auth elements)
    checkCustomerSession();

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = loginForm.email.value;
            const password = loginForm.password.value;

            try {
                const response = await postData('/fooddeliverymanagementsystem/backend/auth.php', { action: 'customer_login', email, password });
                if (response.success) {
                    window.location.href = 'restaurants.html'; // Redirect to restaurants after login
                } else {
                    loginMessage.textContent = response.message || 'Login failed.';
                    loginMessage.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Login error:', error);
                loginMessage.textContent = 'An error occurred during login.';
                loginMessage.classList.remove('d-none');
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = registerForm.username.value;
            const email = registerForm.email.value;
            const password = registerForm.password.value;
            const confirmPassword = registerForm.confirmPassword.value;
            const phoneNumber = registerForm.phoneNumber.value;
            const address = registerForm.address.value;

            if (password !== confirmPassword) {
                registerMessage.textContent = 'Passwords do not match.';
                registerMessage.classList.remove('d-none');
                return;
            }

            try {
                const response = await postData('/fooddeliverymanagementsystem/backend/auth.php', {
                    action: 'customer_register',
                    username,
                    email,
                    password,
                    phone_number: phoneNumber,
                    address
                });
                if (response.success) {
                    alert('Registration successful! You can now log in.');
                    window.location.href = 'login.html'; // Redirect to login page
                } else {
                    registerMessage.textContent = response.message || 'Registration failed.';
                    registerMessage.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Registration error:', error);
                registerMessage.textContent = 'An error occurred during registration.';
                registerMessage.classList.remove('d-none');
            }
        });
    }

    if (logoutButton) {
        logoutButton.addEventListener('click', async () => {
            try {
                const response = await postData('/fooddeliverymanagementsystem/backend/auth.php', { action: 'customer_logout' });
                if (response.success) {
                    // Clear cart on logout
                    localStorage.removeItem('cart');
                    updateCartCount(); // Update navbar cart count
                    window.location.href = 'index.html'; // Redirect to home page
                } else {
                    alert('Logout failed: ' + (response.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Logout error:', error);
                alert('An error occurred during logout.');
            }
        });
    }

    async function checkCustomerSession() {
        try {
            const response = await fetchData('/fooddeliverymanagementsystem/backend/auth.php?action=check_customer_session');
            if (response.loggedIn) {
                if (authButton) authButton.classList.add('d-none');
                if (logoutButton) logoutButton.classList.remove('d-none');
            } else {
                if (authButton) authButton.classList.remove('d-none');
                if (logoutButton) logoutButton.classList.add('d-none');
            }
        } catch (error) {
            console.error('Session check error:', error);
            // Default to not logged in if API call fails
            if (authButton) authButton.classList.remove('d-none');
            if (logoutButton) logoutButton.classList.add('d-none');
        }
    }
});