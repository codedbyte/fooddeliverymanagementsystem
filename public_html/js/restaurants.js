// public_html/js/restaurants.js
document.addEventListener('DOMContentLoaded', () => {
    const restaurantsList = document.getElementById('restaurantsList');

    if (restaurantsList) { // Ensure we are on the restaurants page
        loadRestaurants();
    }

    async function loadRestaurants() {
        try {
            const response = await fetchData('/fooddeliverymanagementsystem/backend/restaurant_api.php?action=get_all');
            if (response.success && response.data.length > 0) {
                restaurantsList.innerHTML = ''; // Clear loading message
                response.data.forEach(restaurant => {
                    const restaurantCard = `
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <img src="${restaurant.image_url || 'https://via.placeholder.com/300x200?text=Restaurant'}" class="card-img-top" alt="${restaurant.name}">
                                <div class="card-body">
                                    <h5 class="card-title">${restaurant.name}</h5>
                                    <p class="card-text">${restaurant.description}</p>
                                    <a href="menu.html?restaurant_id=${restaurant.id}" class="btn btn-primary">View Menu</a>
                                </div>
                            </div>
                        </div>
                    `;
                    restaurantsList.insertAdjacentHTML('beforeend', restaurantCard);
                });
            } else {
                restaurantsList.innerHTML = '<p class="col-12 text-center">No restaurants available at the moment.</p>';
            }
        } catch (error) {
            console.error('Error loading restaurants:', error);
            restaurantsList.innerHTML = '<p class="col-12 text-center text-danger">Failed to load restaurants. Please try again later.</p>';
        }
    }
});