document.addEventListener('DOMContentLoaded', () => {
    const restaurantForm = document.getElementById('restaurantForm');
    const restaurantIdInput = document.getElementById('restaurantId');
    const restaurantNameInput = document.getElementById('restaurantName');
    const restaurantDescriptionInput = document.getElementById('restaurantDescription');
    const saveRestaurantBtn = document.getElementById('saveRestaurantBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const restaurantsTableBody = document.getElementById('restaurantsTableBody');

    let editingRestaurantId = null;

    async function loadRestaurants() {
        try {
            const response = await fetchData('../backend/restaurant_api.php?action=get_all');
            if (response.success) {
                restaurantsTableBody.innerHTML = ''; // Clear existing
                response.data.forEach(restaurant => {
                    const row = restaurantsTableBody.insertRow();
                    row.innerHTML = `
                        <td>${restaurant.id}</td>
                        <td>${restaurant.name}</td>
                        <td>${restaurant.description}</td>
                        <td>
                            <button class="btn btn-sm btn-info edit-btn" data-id="${restaurant.id}" data-name="${restaurant.name}" data-desc="${restaurant.description}">Edit</button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${restaurant.id}">Delete</button>
                        </td>
                    `;
                });
                attachRestaurantEventListeners();
            } else {
                console.error('Failed to load restaurants:', response.message);
                restaurantsTableBody.innerHTML = `<tr><td colspan="4">${response.message}</td></tr>`;
            }
        } catch (error) {
            console.error('Error fetching restaurants:', error);
            restaurantsTableBody.innerHTML = `<tr><td colspan="4">Error loading restaurants.</td></tr>`;
        }
    }

    function attachRestaurantEventListeners() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                editingRestaurantId = e.target.dataset.id;
                restaurantNameInput.value = e.target.dataset.name;
                restaurantDescriptionInput.value = e.target.dataset.desc;
                saveRestaurantBtn.textContent = 'Update Restaurant';
                cancelEditBtn.classList.remove('d-none');
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const restaurantId = e.target.dataset.id;
                if (confirm('Are you sure you want to delete this restaurant?')) {
                    try {
                        const response = await postData('../backend/restaurant_api.php', { action: 'delete', id: restaurantId });
                        if (response.success) {
                            alert('Restaurant deleted successfully!');
                            loadRestaurants(); // Reload the list
                        } else {
                            alert('Error deleting restaurant: ' + (response.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error deleting restaurant:', error);
                        alert('An error occurred during deletion.');
                    }
                }
            });
        });
    }

    restaurantForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = restaurantNameInput.value;
        const description = restaurantDescriptionInput.value;

        let action = 'add';
        let payload = { name, description };
        if (editingRestaurantId) {
            action = 'update';
            payload.id = editingRestaurantId;
        }

        try {
            const response = await postData('../backend/restaurant_api.php', { action, ...payload });
            if (response.success) {
                alert('Restaurant saved successfully!');
                restaurantForm.reset();
                editingRestaurantId = null;
                saveRestaurantBtn.textContent = 'Save Restaurant';
                cancelEditBtn.classList.add('d-none');
                loadRestaurants(); // Reload the list
            } else {
                alert('Error saving restaurant: ' + (response.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving restaurant:', error);
            alert('An error occurred during saving.');
        }
    });

    cancelEditBtn.addEventListener('click', () => {
        restaurantForm.reset();
        editingRestaurantId = null;
        saveRestaurantBtn.textContent = 'Save Restaurant';
        cancelEditBtn.classList.add('d-none');
    });

    // Initial load
    if (restaurantsTableBody) {
        loadRestaurants();
    }
});