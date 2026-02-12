# Triple JH Chicken Trading — Delivery Management System

## 1. Overview

This project is a web-based delivery management system for a chicken trading business. It supports three roles:

- Customer (places orders)
- Driver (picks up and delivers orders)
- Admin (manages products, orders, drivers, payments, and analytics)

The system is implemented in PHP and uses a MySQL/MariaDB database accessed via PDO.

## 2. Technology Stack

- Backend: PHP (PDO)
- Database: MySQL / MariaDB
- Frontend: HTML, CSS, JavaScript
- UI Library: Bootstrap 5 + Bootstrap Icons
- Alerts/Dialogs: SweetAlert2 (used in admin dashboard)
- Local dev environment: XAMPP (Apache + MySQL)

## 3. Project Entry Points (Pages)

### 3.1 Customer pages

- `index.php`
  - Public landing page.
- `dashboard.php`
  - Customer dashboard (requires login).
  - Product browsing, searching, sorting, and pagination.
- `carts/cart.php`
  - Customer cart management.
- `checkout/checkout.php`
  - Checkout UI.
- `orders/orders.php`
  - Order tracking and history.
- `useraccounts/login.php`, `useraccounts/registration.php`, `useraccounts/settings.php`
  - Account management.

### 3.2 Driver pages

- `drivers/driver_login.php`
- `driver_dashboard.php`
  - Driver dashboard (requires driver login).
  - Pending pickups, ongoing deliveries, delivery history.
- `drivers/driver_profile.php`, `drivers/driver_settings.php`

### 3.3 Admin pages

- `adminaccounts/admin_login.php`
- `admin_dashboard.php`
  - Admin dashboard (requires admin session).
  - Product management, order management, driver management, payments, and analytics.

## 4. Authentication & Sessions

The system uses PHP sessions for role authentication:

- Customer session key: `$_SESSION['user_id']`
- Driver session key: `$_SESSION['driver_id']`
- Admin session key: `$_SESSION['admin_id']`

Each dashboard page checks the appropriate session key and redirects to the correct login page if not authenticated.

## 5. Configuration

### 5.1 Database connection

- File: `config.php`
- This file defines DB connection parameters and initializes a global `$db` PDO instance.

Fields:

- `$db_host` (default `localhost`)
- `$db_port` (default `3306`)
- `$db_name` (default `commissioned_app_database`)
- `$db_user` (default `root`)
- `$db_pass` (default empty)

`config.php` provides a function `checkDatabaseConnection()` that:

- Builds a DSN (`mysql:host=...;port=...;dbname=...;charset=utf8mb4`)
- Enables PDO exceptions
- Returns `$db`

### 5.2 File storage

Uploads are stored under `uploads/`. Common subfolders include:

- `uploads/deliveries/` (delivery proof images)
- `uploads/pickups/` (pickup proof images)
- `uploads/items/` (product/parent product images)
- `uploads/qr_codes/` (GCash QR images)

## 6. Database

### 6.1 Source of truth

- File: `database_setup.sql`
- This is a phpMyAdmin dump containing:
  - Table definitions
  - Indexes
  - Foreign keys
  - Sample data

### 6.2 Core tables (high level)

- `users`
  - Customer accounts.
- `admins`
  - Admin accounts.
- `admin_keys`
  - Admin registration keys.
- `drivers`
  - Driver accounts.
- `parent_products`
  - Top-level product grouping.
- `products`
  - Product variants that can link to a `parent_products` row.
- `cart`
  - Customer cart items.
- `pending_delivery` / `pending_delivery_items`
  - Orders not yet completed. Also used for current order status and the order’s total amount.
- `to_be_delivered` / `to_be_delivered_items`
  - Orders that have been picked up and are in the delivery pipeline.
- `history_of_delivery` / `history_of_delivery_items`
  - Completed deliveries and their items.
- `gcash_qr_codes`
  - QR codes used for GCash payments.

### 6.3 Order lifecycle (data flow)

At a high level:

1. Customer checks out
   - Creates rows in `pending_delivery` and `pending_delivery_items`.
2. Admin verifies (especially for GCash) and assigns a driver
   - Updates `pending_delivery.driver_id` and `pending_delivery.status`.
3. Driver pickup process
   - Creates row in `to_be_delivered` (and possibly items tracking) and updates pending status.
4. Driver delivery completion
   - Creates rows in `history_of_delivery` and `history_of_delivery_items` and finalizes statuses.

(Exact transitions can be verified by reading `checkout/checkout_process.php`, `admin/assign_driver.php`, `drivers/driver_pickup_process.php`, and `drivers/driver_delivery_process.php`.)

## 7. Analytics System (Weekly)

### 7.1 Purpose

Admin analytics is based on a weekly rollup stored in a dedicated table:

- `weekly_analytics`

The admin dashboard’s sales summary is sourced from `weekly_analytics` (not recalculated directly in the dashboard view).

### 7.2 Table: `weekly_analytics`

Columns:

- `id`
- `week_start_date` (date)
- `week_end_date` (date)
- `total_sales` (decimal)
- `total_orders` (int)
- `total_products_sold` (int)
- `created_at` / `updated_at`

Constraints:

- Unique key on (`week_start_date`, `week_end_date`)
- Indexes on week start/end

### 7.3 Week boundaries

The “current week” is defined as:

- Monday (start)
- Sunday (end)

Implementation uses:

- `date('Y-m-d', strtotime('monday this week'))`
- `date('Y-m-d', strtotime('sunday this week'))`

### 7.4 Updating weekly analytics

- Endpoint: `admin/update_analytics.php`
- Access: admin-only (`$_SESSION['admin_id']`)
- Output: JSON

Logic:

- Aggregates completed deliveries for the current week from `history_of_delivery`.
- Computes:
  - `total_orders`: count of completed delivery records
  - `total_sales`: sum of (`history_of_delivery_items.price * quantity`) grouped per delivery
  - `total_products_sold`: sum of delivered quantities
- Upserts the totals into `weekly_analytics` for the current week.

### 7.5 Reading weekly analytics in the admin dashboard

- Endpoint: `admin/get_sales_summary.php`
- Access: admin-only

Behavior:

- Ensures a `weekly_analytics` row exists for the current week (creates a zero row if missing).
- Returns the most recent row, formatted for the sales summary UI.

## 8. Weekly Analytics Export

### 8.1 Feature

Admins can export a single week’s analytics data (exact `weekly_analytics` row) as:

- CSV
- PDF (landscape table)

### 8.2 Endpoint

- `admin/export_weekly_analytics.php`

Query parameters:

- `date=YYYY-MM-DD` (required)
- `match=start|end` (required)
- `format=csv|pdf` (required)
- `check=1` (optional)
  - If set, endpoint returns JSON to validate that a matching row exists, without forcing a download.

Response:

- CSV download (`text/csv`) or PDF download (`application/pdf`).
- On missing data: HTTP 404 JSON error.

### 8.3 PDF format notes

The PDF is generated without external libraries using a simple PDF object writer and renders a single-row table in landscape orientation.

## 9. Admin Features (Summary)

Within `admin_dashboard.php` and `/admin` endpoints:

- Products:
  - Add/edit/delete products and parent products.
- Orders:
  - View pending orders.
  - Assign drivers.
  - Cancel/delete orders.
- Drivers:
  - View drivers and toggle active status.
- Payments:
  - GCash verification flow.
- Analytics:
  - Weekly analytics update.
  - Sales summary, top products, order distribution.
  - Weekly export.
- Admin key management:
  - Generate and delete registration keys.

## 10. Driver Features (Summary)

In `driver_dashboard.php` and `/drivers` endpoints:

- View assigned pickups (pending pickup list)
- Confirm pickup with proof image
- View ongoing deliveries
- Complete delivery with proof image and payment collection
- View delivery history

## 11. Customer Features (Summary)

- Browse products (with sorting + search)
- Manage cart
- Checkout
  - Payment method selection (COD/GCash)
- Track orders and view order history
- Manage account settings

## 12. Error Handling & Logging

- DB connection errors are logged via `error_log()` in `config.php`.
- Several endpoints return JSON errors with appropriate HTTP status codes (401/403/404/500).

## 13. Local Development Setup (Recommended)

1. Install XAMPP.
2. Copy the project folder into `xampp/htdocs/`.
3. Start Apache and MySQL.
4. Create database `commissioned_app_database`.
5. Import `database_setup.sql` via phpMyAdmin.
6. Update DB credentials in `config.php` if needed.
7. Access:
   - Customer: `http://localhost/triple_jh_chicken_trading/`
   - Admin: `http://localhost/triple_jh_chicken_trading/adminaccounts/admin_login.php`
   - Driver: `http://localhost/triple_jh_chicken_trading/drivers/driver_login.php`

## 14. Where to Look in Code (Maintenance Map)

- DB connection: `config.php`
- Order number generation and order helper utilities: `includes/order_helper.php`
- Customer catalog logic: `dashboard.php`
- Cart logic:
  - `carts/add_to_cart.php`
  - `carts/update_cart.php`
  - `carts/remove_from_cart.php`
- Checkout logic:
  - `checkout/checkout_process.php`
- Admin order management:
  - `admin/fetch_pending_deliveries.php`
  - `admin/assign_driver.php`
  - `admin/cancel_order_admin.php`
- Driver workflows:
  - `drivers/driver_pickup_process.php`
  - `drivers/driver_delivery_process.php`
- Analytics:
  - `admin/update_analytics.php`
  - `admin/get_sales_summary.php`
  - `admin/export_weekly_analytics.php`

## 15. Security Notes (Operational)

- Protect `config.php` and ensure it is not exposed for download.
- Ensure `uploads/` is writable but not executable.
- Keep admin keys private; rotate them periodically.

