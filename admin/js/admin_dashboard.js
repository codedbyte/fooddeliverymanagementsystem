document.addEventListener('DOMContentLoaded', () => {
    const totalOrdersEl = document.getElementById('totalOrders');
    const pendingOrdersEl = document.getElementById('pendingOrders');
    const registeredUsersEl = document.getElementById('registeredUsers');

    async function loadDashboardStats() {
        try {
            const response = await fetchData('../backend/admin_api.php?action=get_dashboard_stats');
            if (response.success) {
                totalOrdersEl.textContent = response.data.totalOrders;
                pendingOrdersEl.textContent = response.data.pendingOrders;
                registeredUsersEl.textContent = response.data.registeredUsers;
            } else {
                console.error('Failed to load dashboard stats:', response.message);
                // Display error message on dashboard
            }
        } catch (error) {
            console.error('Error fetching dashboard stats:', error);
        }
    }

    // Call on dashboard load
    if (totalOrdersEl) { // Check if elements exist (i.e., we are on dashboard.html)
        loadDashboardStats();
    }
});