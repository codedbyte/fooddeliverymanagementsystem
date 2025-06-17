// delivery_driver/js/driver_auth.js
document.addEventListener('DOMContentLoaded', () => {
    const driverLoginForm = document.getElementById('driverLoginForm');
    const loginMessage = document.getElementById('loginMessage');
    const driverLogoutBtn = document.getElementById('driverLogoutBtn');
    const driverUsernameSpan = document.getElementById('driverUsername');

    // Check if on a page that requires driver authentication
    const requiresAuth = !document.location.pathname.includes('index.html'); // Assuming index.html is the login page

    if (requiresAuth) {
        checkDriverSession(); // Call this on every page load that requires auth
    }

    if (driverLoginForm) {
        driverLoginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = driverLoginForm.username.value;
            const password = driverLoginForm.password.value;

            try {
                const response = await postData('../backend/auth.php', { action: 'driver_login', username, password }); // postData from utils.js
                if (response.success) {
                    window.location.href = 'dashboard.html'; // Redirect to dashboard on success
                } else {
                    loginMessage.textContent = response.message || 'Login failed.';
                    loginMessage.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Driver login error:', error);
                loginMessage.textContent = 'An error occurred during login.';
                loginMessage.classList.remove('d-none');
            }
        });
    }

    if (driverLogoutBtn) {
        driverLogoutBtn.addEventListener('click', async () => {
            try {
                const response = await postData('../backend/auth.php', { action: 'driver_logout' });
                if (response.success) {
                    window.location.href = 'index.html'; // Redirect to driver login page
                } else {
                    alert('Logout failed: ' + (response.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Driver logout error:', error);
                alert('An error occurred during logout.');
            }
        });
    }

    async function checkDriverSession() {
        try {
            const response = await fetchData('../backend/auth.php?action=check_driver_session'); // fetchData from utils.js
            if (!response.loggedIn) {
                window.location.href = 'index.html'; // Redirect if not logged in
            } else {
                if (driverUsernameSpan) {
                    driverUsernameSpan.textContent = response.username || 'Driver!';
                }
            }
        } catch (error) {
            console.error('Driver session check error:', error);
            window.location.href = 'index.html'; // Assume not logged in on error
        }
    }
});