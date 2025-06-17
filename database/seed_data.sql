-- database/seed_data.sql

-- Insert sample users (1 Admin, 1 Customer, 1 Driver)
-- IMPORTANT: In a real application, 'password' column should store hashed passwords!
-- Using plain text here for easy setup, but use PHP's password_hash() for security.
INSERT INTO users (username, email, password, phone_number, role, status, address) VALUES
('admin', 'admin@example.com', 'adminpass', '254712345678', 'admin', 'active', 'Admin Office, Nairobi'),
('customer1', 'customer1@example.com', 'customerpass', '254722334455', 'customer', 'active', '123 Main St, Eldoret'),
('driver1', 'driver1@example.com', 'driverpass', '254733445566', 'driver', 'on-duty', '456 Delivery Rd, Eldoret');

-- Insert sample Restaurants
INSERT INTO restaurants (name, description, address, phone_number, email) VALUES
('Home of Burgers', 'Delicious gourmet burgers and fries.', 'Burger House, Eldoret', '0700111222', 'burgers@example.com'),
('Mayai Pasua', 'Local delicacy specializing in boiled eggs with kachumbari.', 'Kachumbari Street, Eldoret', '0711222333', 'mayai@example.com'),
('Pizzainn', 'Freshly baked pizzas with a variety of toppings.', 'Pizza Avenue, Eldoret', '0722333444', 'pizza@example.com'),
('Coffee on Me', 'Premium coffee, pastries, and light bites.', 'Coffee Lane, Eldoret', '0733444555', 'coffee@example.com');

-- Insert sample Menu Items for Home of Burgers (assuming Home of Burgers is ID 1)
INSERT INTO menu_items (restaurant_id, name, description, price, category, is_available) VALUES
((SELECT id FROM restaurants WHERE name = 'Home of Burgers'), 'Classic Beef Burger', 'Juicy beef patty with lettuce, tomato, cheese.', 650.00, 'Burgers', TRUE),
((SELECT id FROM restaurants WHERE name = 'Home of Burgers'), 'Chicken Burger', 'Grilled chicken breast with special sauce.', 600.00, 'Burgers', TRUE),
((SELECT id FROM restaurants WHERE name = 'Home of Burgers'), 'French Fries', 'Crispy golden fries.', 200.00, 'Sides', TRUE),
((SELECT id FROM restaurants WHERE name = 'Home of Burgers'), 'Coca-Cola', 'Chilled Coca-Cola (300ml).', 100.00, 'Drinks', TRUE);

-- Insert sample Menu Items for Mayai Pasua (assuming Mayai Pasua is ID 2)
INSERT INTO menu_items (restaurant_id, name, description, price, category, is_available) VALUES
((SELECT id FROM restaurants WHERE name = 'Mayai Pasua'), 'Mayai Pasua (Small)', '2 boiled eggs with small kachumbari.', 100.00, 'Eggs', TRUE),
((SELECT id FROM restaurants WHERE name = 'Mayai Pasua'), 'Mayai Pasua (Large)', '4 boiled eggs with large kachumbari.', 200.00, 'Eggs', TRUE),
((SELECT id FROM restaurants WHERE name = 'Mayai Pasua'), 'Kachumbari Extra', 'Extra serving of fresh kachumbari.', 50.00, 'Sides', TRUE);

-- Insert sample Menu Items for Pizzainn (assuming Pizzainn is ID 3)
INSERT INTO menu_items (restaurant_id, name, description, price, category, is_available) VALUES
((SELECT id FROM restaurants WHERE name = 'Pizzainn'), 'Margherita Pizza (Medium)', 'Classic cheese and tomato pizza.', 800.00, 'Pizzas', TRUE),
((SELECT id FROM restaurants WHERE name = 'Pizzainn'), 'Pepperoni Pizza (Large)', 'Loaded with pepperoni.', 1200.00, 'Pizzas', TRUE),
((SELECT id FROM restaurants WHERE name = 'Pizzainn'), 'Chicken Tikka Pizza (Medium)', 'Spicy chicken tikka toppings.', 950.00, 'Pizzas', TRUE);

-- Insert sample Menu Items for Coffee on Me (assuming Coffee on Me is ID 4)
INSERT INTO menu_items (restaurant_id, name, description, price, category, is_available) VALUES
((SELECT id FROM restaurants WHERE name = 'Coffee on Me'), 'Latte', 'Espresso with steamed milk.', 300.00, 'Coffee', TRUE),
((SELECT id FROM restaurants WHERE name = 'Coffee on Me'), 'Cappuccino', 'Espresso with foamed milk.', 300.00, 'Coffee', TRUE),
((SELECT id FROM restaurants WHERE name = 'Coffee on Me'), 'Croissant', 'Freshly baked buttery croissant.', 150.00, 'Pastries', TRUE);

-- You can add more complex sample orders, order items, deliveries, and payments later if needed for testing.
-- For example, an order placed by customer1 for a burger from Home of Burgers:
-- INSERT INTO orders (user_id, restaurant_id, delivery_address, total_amount, status, payment_status) VALUES
-- ((SELECT id FROM users WHERE username = 'customer1'), (SELECT id FROM restaurants WHERE name = 'Home of Burgers'), '123 Main St, Eldoret', 850.00, 'pending', 'pending');
-- And then link order_items to that order.