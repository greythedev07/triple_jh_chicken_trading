<?php
// carts/cart.php
session_start();
// Note: We go up one level (../) to reach config.php in the root
require_once('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../useraccounts/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cartItems = [];
$totalPrice = 0;

try {
    // Query to get cart items with product and parent product details
    $query = "
        SELECT
            c.id as cart_id,
            c.quantity,
            c.price as item_price,
            p.id as product_id,
            p.name as product_name,
            p.description,
            p.weight,
            p.image as product_image,
            p.stock,
            p.parent_id,
            pp.id as parent_product_id,
            pp.name as parent_name,
            pp.image as parent_image,
            pp.description as parent_description
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN parent_products pp ON p.parent_id = pp.id
        WHERE c.user_id = ?
        ORDER BY COALESCE(pp.name, p.name), p.name
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error fetching cart: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Triple JH Chicken Trading</title>
    <link rel="icon" href="../img/logo.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/footer_header.css">
    <style>
        body { background-color: #ffe4c1; font-family: "Inter", sans-serif; }
        .cart-container { background: #fff5e2; border-radius: 15px; padding: 2rem; margin-top: 100px; margin-bottom: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(241, 143, 1, 0.2);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .cart-item-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .cart-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
            border: 1px solid #f0f0f0;
        }
        .cart-item-details {
            flex: 1;
        }
        .cart-item-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .cart-item-variant {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .cart-item-price {
            font-weight: 600;
            color: #d63384;
            font-size: 1.1em;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            margin: 0 10px;
        }
        .btn-amber {
            background: linear-gradient(180deg, #ffb347, #ff6b26);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-amber:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 38, 0.3);
            text-decoration: none;
        }
        .btn-outline-amber {
            border: 1px solid #ff6b26;
            color: #ff6b26;
            background: white;
        }
        .btn-outline-amber:hover {
            background-color: #fff8f0;
            color: #e65c00;
        }
        .stock-status {
            font-size: 0.85em;
            margin-top: 5px;
        }
        .in-stock {
            color: #28a745;
        }
        .low-stock {
            color: #ffc107;
        }
        .out-of-stock {
            color: #dc3545;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../dashboard.php"> <i class="fas fa-arrow-left me-2"></i> Back to Shop</a>
    </div>
</nav>

<div class="container cart-container">
    <h2 class="mb-4 fw-bold" style="color: #6d3209;">Your Shopping Cart</h2>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-basket fa-4x mb-3 text-muted"></i>
            <h4>Your cart is empty</h4>
            <a href="../dashboard.php" class="btn btn-amber mt-3 px-4">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php
                $groupedItems = [];

                // Group items by parent product for better organization
                foreach ($cartItems as $item) {
                    $parentId = $item['parent_id'] ?? 'no_parent';
                    $groupedItems[$parentId]['parent'] = [
                        'id' => $item['parent_product_id'],
                        'name' => $item['parent_name'],
                        'image' => !empty($item['parent_image']) ? "../" . $item['parent_image'] : '../img/products/placeholder.jpg',
                        'product_image' => !empty($item['parent_image']) ? "../" . $item['parent_image'] : '../img/products/placeholder.jpg',
                    ];
                    $groupedItems[$parentId]['items'][] = $item;
                }

                // Display grouped items
                foreach ($groupedItems as $parentId => $group):
                    $parent = $group['parent'];
                    $items = $group['items'];

                    if (count($items) > 1): // Only show parent header if there are multiple variants
                ?>
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($parent['image']) ?>" alt="<?= htmlspecialchars($parent['name']) ?>"
                             class="me-2" style="width: 30px; height: 30px; object-fit: over; border-radius: 4px;">
                        <h6 class="mb-0"><?= htmlspecialchars($parent['name']) ?></h6>
                    </div>
                </div>
                <?php
                    endif;

                    // Display each variant
                    foreach ($items as $item):
                        $subtotal = $item['quantity'] * $item['item_price'];
                        $totalPrice += $subtotal;

                        // Use product image if available, otherwise fall back to parent image (same as get_product_details.php)
                        $imgSrc = !empty($parent['product_image']) ? $parent['product_image'] : $parent['image'];

                        // Stock status
                        $stockStatus = '';
                        $stockClass = '';
                        if ($item['stock'] <= 0) {
                            $stockStatus = 'Out of Stock';
                            $stockClass = 'out-of-stock';
                        } elseif ($item['stock'] < 5) {
                            $stockStatus = "Only {$item['stock']} left";
                            $stockClass = 'low-stock';
                        } else {
                            $stockStatus = 'In Stock';
                            $stockClass = 'in-stock';
                        }
                ?>
                <div class="cart-item" id="cart-item-<?= $item['cart_id'] ?>">
                    <div class="form-check mb-2">
                        <input class="form-check-input item-checkbox" type="checkbox" value="<?= $item['cart_id'] ?>" id="item-<?= $item['cart_id'] ?>" checked>
                        <label class="form-check-label" for="item-<?= $item['cart_id'] ?>">
                            Select this item
                        </label>
                    </div>
                    <div class="cart-item-header">
                        <img src="<?= htmlspecialchars($imgSrc) ?>"
                             class="cart-img"
                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                             onerror="this.src='../img/products/placeholder.jpg'">

                        <div class="cart-item-details">
                            <h5 class="cart-item-title mb-1">
                                <?= htmlspecialchars($item['parent_name'] ?? $item['product_name']) ?>
                            </h5>

                            <?php if (!empty($item['parent_name']) && $item['parent_name'] !== $item['product_name']): ?>
                                <div class="cart-item-variant text-muted small mb-1">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                    <?php if (!empty($item['weight'])): ?>
                                        <span class="text-muted">(<?= htmlspecialchars($item['weight']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!empty($item['weight'])): ?>
                                <div class="cart-item-variant text-muted small mb-1">
                                    <?= htmlspecialchars($item['weight']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="cart-item-price">
                                ₱<?= number_format($item['item_price'], 2) ?>
                            </div>

                            <div class="stock-status <?= $stockClass ?>">
                                <i class="fas <?= $stockClass === 'out-of-stock' ? 'fa-times-circle' : 'fa-check-circle' ?>"></i>
                                <?= $stockStatus ?>
                            </div>
                        </div>

                        <div class="text-end" style="min-width: 100px;">
                            <div class="fw-bold fs-5 text-end">
                                ₱<?= number_format($subtotal, 2) ?>
                            </div>
                            <form method="POST" action="remove_from_cart.php" class="d-inline" style="display: inline-block;">
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                <button type="submit" class="btn btn-link text-danger p-0" style="font-size: 0.85rem;">
                                    <i class="fas fa-trash-alt me-1"></i> Remove
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <form method="POST" action="update_cart.php" class="quantity-form">
                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                            <div class="quantity-controls">
                                <button type="button" class="btn btn-sm btn-outline-secondary quantity-decrease"
                                        data-cart-id="<?= $item['cart_id'] ?>"
                                        <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number"
                                       name="quantity"
                                       class="form-control form-control-sm quantity-input"
                                       value="<?= $item['quantity'] ?>"
                                       min="1"
                                       max="<?= $item['stock'] ?>"
                                       data-cart-id="<?= $item['cart_id'] ?>"
                                       <?= $item['stock'] <= 0 ? 'disabled' : '' ?>>
                                <button type="button" class="btn btn-sm btn-outline-secondary quantity-increase"
                                        data-cart-id="<?= $item['cart_id'] ?>"
                                        <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </form>

                        <div>
                            <span class="text-muted me-2">Subtotal:</span>
                            <span class="fw-bold">₱<?= number_format($subtotal, 2) ?></span>
                        </div>
                    </div>
                </div>
                <?php
                    endforeach; // End items loop
                endforeach; // End grouped items loop
                ?>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4" style="background: white; border-radius: 15px; position: sticky; top: 100px;">
                    <h4 class="fw-bold mb-4">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Selected Items:</span>
                        <span class="fw-bold" id="selected-count">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span class="fw-bold" id="subtotal-price">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Delivery Fee:</span>
                        <span class="fw-bold">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total:</span>
                        <span class="fw-bold" id="total-price">₱0.00</span>
                    </div>
                    <button id="checkout-btn" class="btn btn-primary w-100 py-2" disabled>Proceed to Checkout</button>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="select-all">
                        <label class="form-check-label" for="select-all">
                            Select all items
                        </label>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Initialize cart items data
    const cartItemsData = {
        <?php foreach ($cartItems as $item): ?>
            <?= $item['cart_id'] ?>: {
                price: <?= $item['item_price'] ?>,
                quantity: <?= $item['quantity'] ?>
            },
        <?php endforeach; ?>
    };

    // Function to update order summary
    function updateOrderSummary() {
        let selectedCount = 0;
        let subtotal = 0;

        // Get all checked items
        document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
            const cartId = checkbox.value;
            if (cartItemsData[cartId]) {
                selectedCount++;
                subtotal += cartItemsData[cartId].price * cartItemsData[cartId].quantity;
            }
        });

        // Update UI
        document.getElementById('selected-count').textContent = selectedCount + ' item' + (selectedCount !== 1 ? 's' : '');
        document.getElementById('subtotal-price').textContent = '₱' + subtotal.toFixed(2);
        document.getElementById('total-price').textContent = '₱' + subtotal.toFixed(2);

        // Enable/disable checkout button
        document.getElementById('checkout-btn').disabled = selectedCount === 0;
    }

    // Handle select all checkbox
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateOrderSummary();
    });

    // Handle individual item checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-checkbox')) {
            const allChecked = document.querySelectorAll('.item-checkbox:not(:checked)').length === 0;
            document.getElementById('select-all').checked = allChecked;
            updateOrderSummary();
        }
    });

    // Handle checkout button click
    document.getElementById('checkout-btn').addEventListener('click', function() {
        const selectedItems = [];
        document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
            selectedItems.push(checkbox.value);
        });

        if (selectedItems.length > 0) {
            // Store selected items in sessionStorage
            sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedItems));
            // Redirect to checkout
            window.location.href = 'checkout/checkout.php';
        }
    });

    // Initialize order summary on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateOrderSummary();
    });

    // Function to update cart badge
    function updateCartBadge() {
        fetch('../carts/get_cart_count.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    const cartBadge = document.querySelector('.cart-badge');
                    const cartLink = document.querySelector('a[href*="cart.php"], a[href*="cart/"]');

                    if (!cartLink) {
                        console.error('Cart link not found');
                        return;
                    }

                    if (data.count > 0) {
                        if (!cartBadge) {
                            // Create badge if it doesn't exist
                            const badge = document.createElement('span');
                            badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge';
                            badge.textContent = data.count;
                            badge.style.fontSize = '0.6rem';
                            badge.style.padding = '0.25em 0.5em';
                            cartLink.appendChild(badge);
                        } else {
                            // Update existing badge
                            cartBadge.textContent = data.count;
                            cartBadge.style.display = 'inline-block';
                        }
                    } else if (cartBadge) {
                        // Hide badge if count is 0
                        cartBadge.style.display = 'none';
                        // If cart is empty, redirect to empty cart page
                        if (window.location.pathname.includes('cart.php')) {
                            window.location.href = 'cart.php';
                        }
                    }
                }
            })
            .catch(error => console.error('Error updating cart badge:', error));
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Handle quantity changes with debounce
        let updateTimeout;
        const handleQuantityChange = (input) => {
            const cartId = input.dataset.cartId;
            const quantity = parseInt(input.value);
            const min = parseInt(input.min) || 1;
            const max = parseInt(input.max) || Infinity;

            // Validate input
            if (isNaN(quantity) || quantity < min) {
                input.value = min;
            } else if (quantity > max) {
                input.value = max;
            }

            // Clear any pending update
            clearTimeout(updateTimeout);
            position: 'top-end',
            showConfirmButton: false,
            showCloseButton: true,
            timer: type === 'success' ? 3000 : 5000,
            timerProgressBar: true,
            showClass: {
                popup: 'swal2-show',
                backdrop: 'swal2-backdrop-show',
                icon: 'swal2-icon-show'
            },
            hideClass: {
                popup: 'swal2-hide',
                backdrop: 'swal2-backdrop-hide',
                icon: 'swal2-icon-hide'
            }
        });
    }

    // Initial update of cart badge
    updateCartBadge();
});
</script>
</body>
</html>
