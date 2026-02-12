# API / Endpoint Reference

This project does not use a single centralized router. Instead, it uses multiple PHP scripts that function as page controllers or JSON endpoints.

## 1. Authentication & Role Guard

- Customer pages generally require `$_SESSION['user_id']`.
- Driver pages generally require `$_SESSION['driver_id']`.
- Admin endpoints under `/admin` generally require `$_SESSION['admin_id']`.

## 2. Admin JSON Endpoints (`/admin/*`)

### Analytics

- `POST /admin/update_analytics.php`
  - Admin-only
  - Updates (creates/updates) the current week’s row in `weekly_analytics`.
  - Returns JSON.

- `GET /admin/get_sales_summary.php`
  - Admin-only
  - Returns the most recent `weekly_analytics` row for dashboard summary.

- `GET /admin/export_weekly_analytics.php?date=YYYY-MM-DD&match=start|end&format=csv|pdf[&check=1]`
  - Admin-only
  - Exports exactly one `weekly_analytics` row.
  - `check=1` returns JSON validation.

### Orders

- `GET /admin/fetch_pending_deliveries.php`
- `GET /admin/fetch_order_details.php`
- `POST /admin/assign_driver.php`
- `POST /admin/cancel_order_admin.php`
- `POST /admin/delete_pending_delivery.php`

### Payments

- `GET /admin/fetch_gcash_orders.php`
- `POST /admin/verify_gcash_payment.php`

### Products

- `GET /admin/fetch_products_hierarchical.php`
- `GET /admin/fetch_product.php`
- `POST /admin/add_parent_product.php`
- `POST /admin/add_child_product.php`
- `POST /admin/add_product.php`
- `POST /admin/edit_product.php`
- `POST /admin/delete_product.php`

### Drivers

- `GET /admin/fetch_drivers.php`
- `POST /admin/toggle_driver_status.php`
- `GET /admin/view_driver_profile.php`

### Users

- `GET /admin/fetch_users.php`
- `GET /admin/get_user_details.php`

### Admin key management

- `GET /admin/fetch_admin_keys.php`
- `POST /admin/generate_admin_key.php`
- `POST /admin/delete_admin_key.php`

### Dashboard stats and charts

- `GET /admin/get_stats.php`
- `GET /admin/get_recent_orders.php`
- `GET /admin/get_low_stock.php`
- `GET /admin/get_top_products.php`
- `GET /admin/get_order_status_distribution.php`
- `GET /admin/fetch_analytics_data.php`

### Deprecated / disabled

- `POST /admin/test_weekly_reset.php`
  - Disabled and returns HTTP 410.

## 3. Driver Endpoints (`/drivers/*`)

- `POST /drivers/driver_login_process.php`
- `POST /drivers/driver_register_process.php`
- `POST /drivers/driver_pickup_process.php`
- `POST /drivers/driver_delivery_process.php`
- `POST /drivers/driver_settings_process.php`

## 4. Customer Endpoints

### Cart

- `POST /carts/add_to_cart.php`
- `POST /carts/update_cart.php`
- `POST /carts/remove_from_cart.php`

### Checkout

- `POST /checkout/checkout_process.php`

### Orders

- `POST /orders/cancel_order.php`

### Auth

- `POST /useraccounts/login_process.php`
- `POST /useraccounts/register_process.php`

