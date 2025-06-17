document.addEventListener('DOMContentLoaded', () => {
    const adminLoginForm = document.getElementById('adminLoginForm');
    const loginMessage = document.getElementById('loginMessage');
    const adminLogoutBtn = document.getElementById('adminLogoutBtn');

    // Check if on a page that requires admin authentication
    const requiresAuth = !document.location.pathname.includes('index.html'); // Assuming index.html is the login page

    if (requiresAuth) {
        checkAdminSession(); // Call this on every page load that requires auth
    }

    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = adminLoginForm.username.value;
            const password = adminLoginForm.password.value;

            try {
                const response = await postData('../backend/auth.php', { action: 'admin_login', username, password }); // postData from utils.js
                if (response.success) {
                    window.location.href = 'dashboard.html'; // Redirect to dashboard on success
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

    if (adminLogoutBtn) {
        adminLogoutBtn.addEventListener('click', async () => {
            try {
                const response = await postData('../backend/auth.php', { action: 'admin_logout' });
                if (response.success) {
                    window.location.href = 'index.html'; // Redirect to admin login page
                } else {
                    alert('Logout failed: ' + (response.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Logout error:', error);
                alert('An error occurred during logout.');
            }
        });
    }

    async function checkAdminSession() {
        try {
            const response = await fetchData('../backend/auth.php?action=check_admin_session'); // fetchData from utils.js
            if (!response.loggedIn) {
                window.location.href = 'index.html'; // Redirect if not logged in
            }
            // Optionally, update UI with admin name
        } catch (error) {
            console.error('Session check error:', error);
            window.location.href = 'index.html'; // Assume not logged in on error
        }
    }
});