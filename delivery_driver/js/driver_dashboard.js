// delivery_driver/js/driver_dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    const assignedOrdersList = document.getElementById('assignedOrdersList');
    const noOrdersMessage = document.getElementById('noOrdersMessage');
    const driverUsernameSpan = document.getElementById('driverUsername'); // To display driver's username

    async function loadAssignedOrders() {
        try {
            // First, get the driver's ID from the session (via an auth check or direct API)
            const sessionResponse = await fetchData('../backend/auth.php?action=check_driver_session');
            if (!sessionResponse.loggedIn) {
                window.location.href = 'index.html'; // Ensure driver is logged in
                return;
            }
            const driverId = sessionResponse.driver_id;
            if (driverUsernameSpan) {
                driverUsernameSpan.textContent = sessionResponse.username || 'Driver!';
            }

            const response = await fetchData(`../backend/order_api.php?action=get_driver_assigned_orders&driver_id=${driverId}`);
            if (response.success && response.data.length > 0) {
                assignedOrdersList.innerHTML = ''; // Clear previous orders
                noOrdersMessage.classList.add('d-none');
                response.data.forEach(order => {
                    const orderCard = `
                        <div class="col-md-6 mb-4">
                            <div class="card order-card">
                                <div class="card-header">Order ID: ${order.id}</div>
                                <div class="card-body">
                                    <p class="order-details"><strong>Customer:</strong> ${order.customer_name}</p>
                                    <p class="order-details"><strong>Delivery Address:</strong> ${order.delivery_address}</p>
                                    <p class="order-details"><strong>Restaurant:</strong> ${order.restaurant_name}</p>
                                    <p class="order-details"><strong>Total Amount:</strong> KSh ${parseFloat(order.total_amount).toFixed(2)}</p>
                                    <p class="order-details"><strong>Current Status:</strong> <span class="badge bg-primary text-white">${order.status.replace(/_/g, ' ')}</span></p>
                                    <p class="order-details"><strong>Payment Status:</strong> <span class="badge bg-success text-white">${order.payment_status}</span></p>

                                    <h5>Items:</h5>
                                    <ul>
                                        ${order.items.map(item => `<li>${item.quantity}x ${item.name} (KSh ${parseFloat(item.price_at_order).toFixed(2)} each)</li>`).join('')}
                                    </ul>

                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <button class="btn btn-sm btn-warning btn-status-update" data-order-id="${order.id}" data-new-status="picked_up" ${order.status === 'assigned' ? '' : 'disabled'}>Picked Up</button>
                                            <button class="btn btn-sm btn-info btn-status-update" data-order-id="${order.id}" data-new-status="on_the_way" ${order.status === 'picked_up' ? '' : 'disabled'}>On the Way</button>
                                            <button class="btn btn-sm btn-success btn-status-update" data-order-id="${order.id}" data-new-status="delivered" ${order.status === 'on_the_way' ? '' : 'disabled'}>Delivered</button>
                                        </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                    `;
                    assignedOrdersList.insertAdjacentHTML('beforeend', orderCard);
                });
                attachStatusUpdateListeners();
            } else {
                assignedOrdersList.innerHTML = ''; // Clear any cards
                noOrdersMessage.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error fetching assigned orders:', error);
            assignedOrdersList.innerHTML = `<div class="col-12"><div class="alert alert-danger" role="alert">Error loading orders. Please try again.</div></div>`;
            noOrdersMessage.classList.add('d-none');
        }
    }

    async function updateOrderStatus(orderId, newStatus) {
        if (!confirm(`Are you sure you want to mark Order ${orderId} as '${newStatus.replace(/_/g, ' ')}'?`)) {
            return;
        }
        try {
            const response = await postData('../backend/order_api.php', {
                action: 'update_status',
                order_id: orderId,
                status: newStatus
            });
            if (response.success) {
                alert(`Order ${orderId} status updated to '${newStatus}'.`);
                loadAssignedOrders(); // Reload orders to update UI
            } else {
                alert('Failed to update order status: ' + (response.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            alert('An error occurred while updating order status.');
        }
    }

    function attachStatusUpdateListeners() {
        document.querySelectorAll('.btn-status-update').forEach(button => {
            button.addEventListener('click', (e) => {
                const orderId = e.target.dataset.orderId;
                const newStatus = e.target.dataset.newStatus;
                updateOrderStatus(orderId, newStatus);
            });
        });
        // Attach event listeners for map button if implemented
    }

    // Initial load
    if (assignedOrdersList) { // Check if elements exist (i.e., we are on dashboard.html)
        loadAssignedOrders();
        // Optional: Periodically refresh orders
        // setInterval(loadAssignedOrders, 60000); // Refresh every 60 seconds
    }
});