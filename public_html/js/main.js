// public_html/js/main.js
document.addEventListener('DOMContentLoaded', () => {
    // Function to load featured restaurants on index.html
    const featuredRestaurantsSection = document.getElementById('featuredRestaurants');
    if (featuredRestaurantsSection) {
        loadFeaturedRestaurants();
    }

    async function loadFeaturedRestaurants() {
        try {
            const response = await fetchData('/fooddeliverymanagementsystem/backend/restaurant_api.php?action=get_all'); // Using fetchData from utils.js
            if (response.success && response.data.length > 0) {
                featuredRestaurantsSection.innerHTML = ''; // Clear placeholder
                const featuredCount = Math.min(response.data.length, 3); // Display up to 3 featured
                for (let i = 0; i < featuredCount; i++) {
                    const restaurant = response.data[i];
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
                    featuredRestaurantsSection.insertAdjacentHTML('beforeend', restaurantCard);
                }
            } else {
                featuredRestaurantsSection.innerHTML = '<p class="col-12 text-center">No featured restaurants available at the moment.</p>';
            }
        } catch (error) {
            console.error('Error loading featured restaurants:', error);
            featuredRestaurantsSection.innerHTML = '<p class="col-12 text-center text-danger">Failed to load restaurants. Please try again later.</p>';
        }
    }
});