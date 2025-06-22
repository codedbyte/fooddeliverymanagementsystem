// public_html/js/checkout.js
document.addEventListener('DOMContentLoaded', async () => {
    // Immediately update the cart count in the navbar
    if (typeof updateCartCount === 'function') {
        await updateCartCount();
    }

    const deliveryAddressInput = document.getElementById('deliveryAddress');
    const phoneNumberInput = document.getElementById('phoneNumber');
    const deliveryNotesInput = document.getElementById('deliveryNotes');
    const checkoutCartItemsList = document.getElementById('checkoutCartItems');
    const checkoutSubtotalSpan = document.getElementById('checkoutSubtotal');
    const checkoutDeliveryFeeSpan = document.getElementById('checkoutDeliveryFee');
    const checkoutTotalSpan = document.getElementById('checkoutTotal');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const checkoutMessage = document.getElementById('checkoutMessage');

    // Load customer's default address/phone if logged in and display cart summary
    loadCustomerInfo();
    displayCheckoutSummary();

    async function loadCustomerInfo() {
        try {
            const response = await fetchData('/fooddeliverymanagementsystem/backend/auth.php?action=check_customer_session');
            if (response.loggedIn && response.user_id) {
                // If you have a user profile API, you can fetch and pre-fill details
                // For now, we assume the user will enter them manually
            } else {
                alert('Please log in to proceed with checkout.');
                window.location.href = 'login.html';
            }
        } catch (error) {
            console.error('Error loading customer info:', error);
            // Allow checkout but user must fill details
        }
    }

    async function displayCheckoutSummary() {
        const cart = await getCart(); // Fetch cart from database

        if (cart.items.length === 0) {
            alert('Your cart is empty. Please add items before checking out.');
            window.location.href = 'restaurants.html';
            return;
        }

        checkoutCartItemsList.innerHTML = '';
        cart.items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                ${item.name} x ${item.quantity}
                <span class="badge bg-primary rounded-pill">KSh ${(item.price * item.quantity).toFixed(2)}</span>
            `;
            checkoutCartItemsList.appendChild(li);
        });

        const subtotal = getCartSubtotal(cart);
        checkoutSubtotalSpan.textContent = subtotal.toFixed(2);
        checkoutDeliveryFeeSpan.textContent = DELIVERY_FEE.toFixed(2);
        checkoutTotalSpan.textContent = (subtotal + DELIVERY_FEE).toFixed(2);
    }

    placeOrderBtn.addEventListener('click', async () => {
        const deliveryAddress = deliveryAddressInput.value.trim();
        const phoneNumber = phoneNumberInput.value.trim();
        const deliveryNotes = deliveryNotesInput.value.trim();
        const cart = await getCart(); // Get fresh cart data before placing order
        const totalAmount = getCartTotal(cart);

        if (!deliveryAddress || !phoneNumber) {
            checkoutMessage.textContent = 'Please provide your delivery address and phone number.';
            checkoutMessage.classList.remove('d-none');
            return;
        }
        if (cart.items.length === 0) {
            checkoutMessage.textContent = 'Your cart is empty. Cannot place order.';
            checkoutMessage.classList.remove('d-none');
            return;
        }

        // Disable button to prevent double submission
        placeOrderBtn.disabled = true;
        placeOrderBtn.textContent = 'Placing Order...';
        checkoutMessage.classList.add('d-none'); // Hide previous messages

        try {
            // Step 1: Place the order (status 'pending', payment_status 'pending')
            const orderPayload = {
                action: 'place_order',
                restaurant_id: cart.restaurant_id,
                delivery_address: deliveryAddress,
                phone_number: phoneNumber, // This will be the user's phone, which might be different from M-Pesa number
                delivery_notes: deliveryNotes,
                total_amount: totalAmount,
                items: cart.items
            };
            const orderResponse = await postData('/fooddeliverymanagementsystem/backend/order_api.php', orderPayload);

            if (orderResponse.success) {
                const orderId = orderResponse.order_id;
                // Step 2: Initiate M-Pesa STK Push
                const mpesaPayload = {
                    action: 'stk_push',
                    phone_number: phoneNumber, // Use the customer's phone number for M-Pesa
                    amount: totalAmount,
                    order_id: orderId
                };
                const mpesaResponse = await postData('/fooddeliverymanagementsystem/backend/mpesa_api.php', mpesaPayload);

                if (mpesaResponse.success) {
                    // M-Pesa STK push initiated. Clear cart and redirect.
                    localStorage.removeItem(CART_STORAGE_KEY);
                    alert('Order placed successfully! Please complete the M-Pesa payment on your phone.');
                    window.location.href = `confirmation.html?order_id=${orderId}`;
                } else {
                    // M-Pesa initiation failed. Inform user and possibly cancel order or allow retry.
                    // You might want to update the order status in DB to 'payment_failed' here.
                    checkoutMessage.textContent = 'Order placed, but M-Pesa payment initiation failed: ' + (mpesaResponse.message || 'Unknown error.');
                    checkoutMessage.classList.remove('d-none');
                    // Optionally: provide a link to track order and retry payment later
                }
            } else {
                checkoutMessage.textContent = 'Failed to place order: ' + (orderResponse.message || 'Unknown error.');
                checkoutMessage.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Checkout error:', error);
            checkoutMessage.textContent = 'An unexpected error occurred during checkout.';
            checkoutMessage.classList.remove('d-none');
        } finally {
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = 'Place Order';
        }
    });
});