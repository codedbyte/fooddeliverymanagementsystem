// public_html/js/menu.js
document.addEventListener('DOMContentLoaded', () => {
    const restaurantNameElement = document.getElementById('restaurantName');
    const restaurantDescriptionElement = document.getElementById('restaurantDescription');
    const menuItemsList = document.getElementById('menuItemsList');

    const urlParams = new URLSearchParams(window.location.search);
    const restaurantId = urlParams.get('restaurant_id');

    if (!restaurantId) {
        menuItemsList.innerHTML = '<p class="col-12 text-center text-danger">No restaurant selected.</p>';
        return;
    }

    loadRestaurantDetailsAndMenu(restaurantId);

    async function loadRestaurantDetailsAndMenu(id) {
        try {
            // Fetch restaurant details
            const restaurantResponse = await fetchData(`/fooddeliverymanagementsystem/backend/restaurant_api.php?action=get_single&id=${id}`);
            if (restaurantResponse.success && restaurantResponse.data) {
                restaurantNameElement.textContent = restaurantResponse.data.name;
                restaurantDescriptionElement.textContent = restaurantResponse.data.description;
            } else {
                restaurantNameElement.textContent = 'Restaurant Not Found';
                restaurantDescriptionElement.textContent = '';
                menuItemsList.innerHTML = '<p class="col-12 text-center text-danger">Restaurant details could not be loaded.</p>';
                return;
            }

            // Fetch menu items for this restaurant
            const menuResponse = await fetchData(`/fooddeliverymanagementsystem/backend/menu_api.php?action=get_by_restaurant&restaurant_id=${id}`);
            if (menuResponse.success && menuResponse.data.length > 0) {
                menuItemsList.innerHTML = ''; // Clear loading message
                menuResponse.data.forEach(item => {
                    const menuItemCard = `
                        <div class="col-md-6 mb-4">
                            <div class="card menu-item-card">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="${item.image_url || 'https://via.placeholder.com/150x150?text=Food+Item'}" class="img-fluid rounded-start" alt="${item.name}">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title">${item.name}</h5>
                                            <p class="card-text">${item.description}</p>
                                            <p class="card-text fw-bold">KSh ${parseFloat(item.price).toFixed(2)}</p>
                                            <button class="btn btn-primary btn-add-to-cart"
                                                    data-item-id="${item.id}"
                                                    data-item-name="${item.name}"
                                                    data-item-price="${item.price}"
                                                    data-restaurant-id="${restaurantId}"
                                                    ${item.is_available ? '' : 'disabled'}>
                                                ${item.is_available ? 'Add to Cart' : 'Unavailable'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    menuItemsList.insertAdjacentHTML('beforeend', menuItemCard);
                });
                attachAddToCartListeners();
            } else {
                menuItemsList.innerHTML = '<p class="col-12 text-center">No menu items found for this restaurant.</p>';
            }
        } catch (error) {
            console.error('Error loading restaurant details or menu:', error);
            menuItemsList.innerHTML = '<p class="col-12 text-center text-danger">Failed to load menu. Please try again later.</p>';
        }
    }

    function attachAddToCartListeners() {
        document.querySelectorAll('.btn-add-to-cart').forEach(button => {
            button.addEventListener('click', async (e) => {
                const itemId = e.target.dataset.itemId;
                const itemName = e.target.dataset.itemName;
                const itemPrice = parseFloat(e.target.dataset.itemPrice);
                const restaurantId = e.target.dataset.restaurantId;

                // Check if user is logged in
                const sessionResponse = await fetchData('/fooddeliverymanagementsystem/backend/auth.php?action=check_customer_session');
                if (!sessionResponse.loggedIn) {
                    alert('You must be logged in to add items to your cart.');
                    window.location.href = 'login.html';
                    return;
                }
                
                await addToCart({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    restaurant_id: restaurantId,
                    quantity: 1
                });
                alert(`${itemName} added to cart!`);
                await updateCartCount(); // Update the cart count in the navbar
            });
        });
    }
});