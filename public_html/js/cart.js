// public_html/js/cart.js

const DELIVERY_FEE = 200.00; // Example flat delivery fee

// Get cart from the database via API
async function getCart() {
    try {
        const response = await fetchData('/fooddeliverymanagementsystem/backend/cart_api.php?action=get_cart');
        if (response.success) {
            return response.data;
        }
        // If user is not logged in or an error occurs, return an empty cart structure
        return { items: [], restaurant_id: null };
    } catch (error) {
        console.error("Failed to fetch cart:", error);
        return { items: [], restaurant_id: null };
    }
}

// Add item to cart in the database
async function addToCart(item) {
    await postData('/fooddeliverymanagementsystem/backend/cart_api.php', {
        action: 'add_to_cart',
        id: item.id,
        quantity: item.quantity
    });
    await updateCartCount();
}

// Remove item from cart in the database
async function removeFromCart(itemId) {
    await postData('/fooddeliverymanagementsystem/backend/cart_api.php', {
        action: 'remove_item',
        id: itemId
    });
    await updateCartCount();
}

// Update item quantity in the database
async function updateCartQuantity(itemId, quantity) {
    if (quantity <= 0) {
        await removeFromCart(itemId);
    } else {
        await postData('/fooddeliverymanagementsystem/backend/cart_api.php', {
            action: 'update_quantity',
            id: itemId,
            quantity: quantity
        });
        await updateCartCount();
    }
}


/**
 * Calculates the subtotal of all items in the cart.
 */
function getCartSubtotal(cart) {
    return cart.items.reduce((total, item) => total + (item.price * item.quantity), 0);
}

/**
 * Calculates the total cost including delivery fee.
 */
function getCartTotal(cart) {
    const subtotal = getCartSubtotal(cart);
    return subtotal > 0 ? subtotal + DELIVERY_FEE : 0;
}

/**
 * Updates the cart count in the navbar.
 */
async function updateCartCount() {
    const cart = await getCart();
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        const totalItems = cart.items.reduce((count, item) => count + item.quantity, 0);
        cartCountElement.textContent = totalItems;
    }
}

// --- Cart Page Rendering Logic ---
document.addEventListener('DOMContentLoaded', async () => {
    // Run this code only on the cart page
    if (document.getElementById('cartItemsList')) {
        await renderCartPage();
    }
    // Always update cart count on any page load
    await updateCartCount();
});

async function renderCartPage() {
    const cart = await getCart();
    const cartItemsList = document.getElementById('cartItemsList');
    const cartSubtotalSpan = document.getElementById('cartSubtotal');
    const cartDeliveryFeeSpan = document.getElementById('cartDeliveryFee');
    const cartTotalSpan = document.getElementById('cartTotal');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const cartSummary = document.getElementById('cartSummary');

    cartItemsList.innerHTML = ''; // Clear existing items

    if (cart.items.length === 0) {
        if(emptyCartMessage) {
            cartItemsList.appendChild(emptyCartMessage);
            emptyCartMessage.classList.remove('d-none');
        }
        if (cartSummary) cartSummary.classList.add('d-none');
    } else {
        if (emptyCartMessage) emptyCartMessage.classList.add('d-none');
        if (cartSummary) cartSummary.classList.remove('d-none');

        cart.items.forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'card mb-3';
            itemDiv.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">${item.name}</h5>
                        <strong>KSh ${(parseFloat(item.price) * item.quantity).toFixed(2)}</strong>
                    </div>
                    <p class="card-text">Price: KSh ${parseFloat(item.price).toFixed(2)}</p>
                    <div class="d-flex justify-content-start align-items-center mt-2">
                        <label class="me-3">Quantity:</label>
                        <input type="number" class="form-control form-control-sm item-quantity-input" value="${item.quantity}" min="1" data-item-id="${item.id}" style="width: 80px;">
                        <button class="btn btn-sm btn-outline-danger ms-auto remove-item-btn" data-item-id="${item.id}">Remove</button>
                    </div>
                </div>
            `;
            cartItemsList.appendChild(itemDiv);
        });

        // Update totals in the summary card
        const subtotal = getCartSubtotal(cart);
        cartSubtotalSpan.textContent = subtotal.toFixed(2);
        cartDeliveryFeeSpan.textContent = DELIVERY_FEE.toFixed(2);
        cartTotalSpan.textContent = getCartTotal(cart).toFixed(2);
    }
    
    attachCartPageEventListeners();
}

function attachCartPageEventListeners() {
    // For remove buttons
    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const itemId = e.target.dataset.itemId;
            await removeFromCart(itemId);
            await renderCartPage(); // Re-render the cart
        });
    });

    // For quantity inputs
    document.querySelectorAll('.item-quantity-input').forEach(input => {
        input.addEventListener('change', async (e) => {
            const itemId = e.target.dataset.itemId;
            const newQuantity = parseInt(e.target.value, 10);
            await updateCartQuantity(itemId, newQuantity);
            await renderCartPage(); // Re-render the cart
        });
    });
}