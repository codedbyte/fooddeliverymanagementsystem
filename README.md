# 🍽️ Food Delivery Management System

A comprehensive web-based application for managing a food delivery service. It supports three user roles — **customers**, **restaurant administrators**, and **delivery drivers** — all powered by a **PHP backend** and **MySQL** database.

---

## 📁 Project Structure

```
fooddeliverymanagementsystem/
├── public_html/        # Customer-facing frontend
├── admin/              # Admin dashboard
├── delivery_driver/    # Driver dashboard
├── backend/            # PHP backend logic and APIs
├── database/           # SQL schema and migrations
└── logs/               # PHP error logs
```

* **`public_html/`** – Customers can browse restaurants, view menus, place orders, and track deliveries.
* **`admin/`** – Restaurant admins manage menus, orders, restaurants, and delivery drivers.
* **`delivery_driver/`** – Drivers manage and view assigned deliveries.
* **`backend/`** – Core PHP files handling business logic, database access, and API endpoints.
* **`database/`** – Contains `schema.sql` for initial DB setup.
* **`logs/`** – Check `php_errors.log` here during debugging.

---

## 🛠️ Getting Started (Localhost Setup with XAMPP)

### ✅ Prerequisites

* XAMPP (or similar stack like WAMP/LAMP)
* Web browser (Chrome, Firefox, etc.)
* Code editor (e.g., VS Code, Sublime Text)

---

### 📂 Step 1: Database Setup

1. Launch **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Create a new database:

   * Click **Databases**.
   * Name it: `fooddeliverymanagementsystem`.
   * Click **Create**.
4. Import the database schema:

   * Select the new database.
   * Go to **Import** tab.
   * Upload the `database/schema.sql` file.
   * Click **Go** to import tables.

---

### ⚙️ Step 2: Configure Project

1. Copy the project folder into:

   ```
   C:/xampp/htdocs/fooddeliverymanagementsystem
   ```

2. Open `backend/config.php` and configure your database credentials:

   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Default XAMPP install has no password
   define('DB_NAME', 'fooddeliverymanagementsystem');
   ```

---

### 🚀 Step 3: Run the Application

Open your browser and go to:

* **Customer Portal:**
  [http://localhost/fooddeliverymanagementsystem/public\_html/](http://localhost/fooddeliverymanagementsystem/public_html/)

* **Admin Dashboard:**
  [http://localhost/fooddeliverymanagementsystem/admin/](http://localhost/fooddeliverymanagementsystem/admin/)

* **Driver Dashboard:**
  [http://localhost/fooddeliverymanagementsystem/delivery\_driver/](http://localhost/fooddeliverymanagementsystem/delivery_driver/)

---

## 🌟 Potential Future Enhancements

* **Real-Time Driver Tracking** — Use WebSockets + Google Maps for live updates.
* **User Reviews & Ratings** — Allow customers to review restaurants and dishes.
* **Advanced Filtering** — Search/filter by cuisine, ratings, delivery time, etc.
* **Profile Management** — Enable user updates for password, address, etc.
* **Payment Gateway Integration** — Enhance M-Pesa support and add Stripe/PayPal.
* **Modern Frontend Framework** — Migrate UI to Vue.js or React for better UX.

---

## 🐞 Known Bug: Order Placement Failure

* **Issue:**
  Clicking “Place Order” on the checkout page fails with:
  `Failed to place order due to a server error.`

* **File Affected:**
  `backend/order_api.php`

* **Symptoms:**

  * No detailed error in logs (`php_errors.log` is silent).
  * Likely failing during the database transaction phase.
  * `try-catch` block catches the error without specifics.

* **Attempts Made:**

  * Verified `$_SESSION` and `$_POST` inputs.
  * Ensured proper types in `bind_param()` calls.
  * Sanitized inputs and confirmed foreign key constraints.

* **Next Steps:**

  * Add granular error logging inside the `catch` block.
  * Use `mysqli_error()` or `PDO::errorInfo()` where applicable.
  * Enable PHP's `display_errors` in `php.ini` or at runtime for more verbosity.
  * Log SQL statements and debug binding values during order insertion.

---

## ✅ Summary

This project provides a solid base for a scalable food delivery platform. With further debugging and enhancements, it can serve as a fully functional commercial product.


