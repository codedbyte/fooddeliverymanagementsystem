// public_html/js/order_tracking.js
document.addEventListener('DOMContentLoaded', () => {
    const recentOrdersList = document.getElementById('recentOrdersList');
    const trackingDetailsCard = document.getElementById('trackingDetailsCard');
    const trackingOrderIdSpan = document.getElementById('trackingOrderId');
    const currentOrderStatusSpan = document.getElementById('currentOrderStatus');
    const driverInfoElement = document.getElementById('driverInfo');
    const mapElement = document.getElementById('map');

    // Order status steps for visual progress bar
    const orderStatusSteps = ['pending', 'processing', 'assigned', 'picked_up', 'on_the_way', 'delivered'];

    loadCustomerOrders();

    async function loadCustomerOrders() {
        try {
            const sessionResponse = await fetchData('/fooddeliverymanagementsystem/backend/auth.php?action=check_customer_session');
            if (!sessionResponse.loggedIn) {
                recentOrdersList.innerHTML = '<p class="text-danger">Please log in to view your orders.</p>';
                return;
            }
            const userId = sessionResponse.user_id; // Get logged-in user's ID

            const response = await fetchData(`/fooddeliverymanagementsystem/backend/order_api.php?action=get_user_orders&user_id=${userId}`);
            if (response.success && response.data.length > 0) {
                recentOrdersList.innerHTML = ''; // Clear loading message
                response.data.forEach(order => {
                    const orderItem = `
                        <div class="card mb-2">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Order #${order.id}</strong> from ${order.restaurant_name}<br>
                                    Status: <span class="badge bg-info text-dark">${order.status.replace(/_/g, ' ')}</span>
                                    Total: KSh ${parseFloat(order.total_amount).toFixed(2)}
                                </div>
                                <button class="btn btn-sm btn-outline-primary track-order-btn" data-order-id="${order.id}">Track</button>
                            </div>
                        </div>
                    `;
                    recentOrdersList.insertAdjacentHTML('beforeend', orderItem);
                });
                attachTrackOrderListeners();
            } else {
                recentOrdersList.innerHTML = '<p>You have no recent orders.</p>';
            }
        } catch (error) {
            console.error('Error loading customer orders:', error);
            recentOrdersList.innerHTML = '<p class="text-danger">Failed to load your orders. Please try again later.</p>';
        }
    }

    function attachTrackOrderListeners() {
        document.querySelectorAll('.track-order-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const orderId = e.target.dataset.orderId;
                displayOrderTracking(orderId);
            });
        });
    }

    async function displayOrderTracking(orderId) {
        try {
            const response = await fetchData(`/fooddeliverymanagementsystem/backend/order_api.php?action=get_order_details_for_tracking&order_id=${orderId}`); // You'll need this API
            if (response.success && response.data) {
                const order = response.data;
                trackingOrderIdSpan.textContent = order.id;
                currentOrderStatusSpan.textContent = order.status.replace(/_/g, ' ');

                // Update progress bar
                orderStatusSteps.forEach(step => {
                    const stepElement = document.getElementById(`step_${step}`);
                    if (stepElement) {
                        stepElement.classList.remove('active');
                        // Add active class to current and preceding steps
                        if (orderStatusSteps.indexOf(step) <= orderStatusSteps.indexOf(order.status)) {
                            stepElement.classList.add('active');
                        }
                    }
                });

                // Display driver info if assigned
                if (order.driver_id && order.driver_name && order.driver_phone) {
                    driverInfoElement.querySelector('span:nth-child(1)').textContent = order.driver_name;
                    driverInfoElement.querySelector('span:nth-child(2)').textContent = order.driver_phone;
                    driverInfoElement.classList.remove('d-none');
                } else {
                    driverInfoElement.classList.add('d-none');
                }

                // Show tracking card
                trackingDetailsCard.classList.remove('d-none');

                // If you have Google Maps integration:
                // if (mapElement && order.current_latitude && order.current_longitude && order.delivery_latitude && order.delivery_longitude) {
                //     initMap(mapElement, order.current_latitude, order.current_longitude, order.delivery_latitude, order.delivery_longitude);
                // } else if (mapElement) {
                //     mapElement.innerHTML = '<p class="text-center text-muted">Map tracking not available for this order yet.</p>';
                // }

            } else {
                alert('Failed to load order tracking details: ' + (response.message || 'Order not found.'));
                trackingDetailsCard.classList.add('d-none');
            }
        } catch (error) {
            console.error('Error fetching order tracking details:', error);
            alert('An error occurred while fetching tracking details.');
            trackingDetailsCard.classList.add('d-none');
        }
    }

    // Function to initialize Google Map (if integrated)
    // function initMap(mapDiv, driverLat, driverLng, destLat, destLng) {
    //     const directionsService = new google.maps.DirectionsService();
    //     const directionsRenderer = new google.maps.DirectionsRenderer();
    //     const map = new google.maps.Map(mapDiv, {
    //         zoom: 12,
    //         center: { lat: driverLat, lng: driverLng }
    //     });
    //     directionsRenderer.setMap(map);

    //     const request = {
    //         origin: { lat: driverLat, lng: driverLng },
    //         destination: { lat: destLat, lng: destLng },
    //         travelMode: google.maps.TravelMode.DRIVING
    //     };

    //     directionsService.route(request, function(result, status) {
    //         if (status == 'OK') {
    //             directionsRenderer.setDirections(result);
    //         } else {
    //             mapDiv.innerHTML = '<p class="text-center text-danger">Could not load map directions.</p>';
    //         }
    //     });
    // }
});