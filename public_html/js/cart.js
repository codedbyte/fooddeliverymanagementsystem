// public_html/js/cart.js

const CART_STORAGE_KEY = 'cart';
const DELIVERY_FEE = 200.00; // Example flat delivery fee

// Get cart from local storage
function getCart() {
    const cartJson = localStorage.getItem(CART_STORAGE_KEY);
    return cartJson ? JSON.parse(cartJson) : { items: [], restaurant_id: null };
}

// Save cart to local storage
function saveCart(cart) {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    updateCartCount(); // Update any cart counters on the page
}

// Add item to cart
function addToCart(item) {
    let cart = getCart();

    // If cart is not empty and new item is from a different restaurant, clear cart
    if (cart.items.length > 0 && cart.restaurant_id !== null && cart.restaurant_id !== item.restaurant_id) {
        if (!confirm("Your cart contains items from a different restaurant. Do you want to clear your cart and add this item?")) {
            return; // User cancelled
        }
        cart = { items: [], restaurant_id: null }; // Clear cart
    }

    // Set restaurant ID for the cart if it's empty
    if (cart.restaurant_id === null) {
        cart.restaurant_id = item.restaurant_id;
    }

    const existingItem = cart.items.find(cartItem => cartItem.id === item.id);

    if (existingItem) {
        existingItem.quantity += item.quantity;
    } else {
        cart.items.push(item);
    }
    saveCart(cart);
}

// Remove item from cart
function removeFromCart(itemId) {
    let cart = getCart();
    cart.items = cart.items.filter(item => item.id !== itemId);
    if (cart.items.length === 0) {
        cart.restaurant_id = null; // Reset restaurant ID if cart is empty
    }
    saveCart(cart);
}

// Update item quantity
function updateCartQuantity(itemId, quantity) {
    let cart = getCart();
    const itemIndex = cart.items.findIndex(item => item.id === itemId);

    if (itemIndex > -1) {
        if (quantity <= 0) {
            removeFromCart(itemId); // Remove if quantity is 0 or less
        } else {
            cart.items[itemIndex].quantity = quantity;
            saveCart(cart);
        }
    }
}

// Calculate cart subtotal
function getCartSubtotal() {
    return getCart().items.reduce((total, item) => total + (item.price * item.quantity), 0);
}

// Calculate total including delivery fee
function getCartTotal() {
    const subtotal = getCartSubtotal();
    return subtotal > 0 ? subtotal + DELIVERY_FEE : 0;
}

// Update cart item count in navbar
function updateCartCount() {
    const cartItemCountElement = document.getElementById('cartItemCount');
    if (cartItemCountElement) {
        const cart = getCart();
        const totalItems = cart.items.reduce((count, item) => count + item.quantity, 0);
        cartItemCountElement.textContent = totalItems;
    }
}

// Call on page load to initialize cart count
document.addEventListener('DOMContentLoaded', updateCartCount);