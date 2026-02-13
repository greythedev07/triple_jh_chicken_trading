<?php
// carts/cart.php - WITH SELECTIVE CHECKOUT + ORIGINAL IMAGE LOGIC
session_start();
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
            transition: all 0.3s ease;
        }
        .cart-item.selected {
            border-color: #ff6b26;
            box-shadow: 0 4px 12px rgba(255, 107, 38, 0.25);
            background: #fff8f0;
        }
        .cart-item-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .item-checkbox {
            width: 22px;
            height: 22px;
            margin-right: 15px;
            cursor: pointer;
            accent-color: #ff6b26;
            flex-shrink: 0;
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
            padding: 10px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-amber:hover:not(:disabled) {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 38, 0.4);
        }
        .btn-amber:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-outline-amber {
            border: 2px solid #ff6b26;
            color: #ff6b26;
            background: white;
            border-radius: 20px;
            padding: 10px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-outline-amber:hover {
            background-color: #ff6b26;
            color: white;
        }
        .stock-status {
            font-size: 0.85em;
            margin-top: 5px;
            font-weight: 500;
        }
        .in-stock { color: #28a745; }
        .low-stock { color: #ffc107; }
        .out-of-stock { color: #dc3545; }
        .select-all-section {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 2px solid rgba(241, 143, 1, 0.3);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .select-all-checkbox {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: #ff6b26;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../dashboard.php">
            <i class="fas fa-arrow-left me-2"></i> Back to Shop
        </a>
    </div>
</nav>

<div class="container cart-container">
    <h2 class="mb-4 fw-bold" style="color: #6d3209;">
        <i class="fas fa-shopping-cart me-2"></i>Your Shopping Cart
    </h2>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-basket fa-4x mb-3 text-muted"></i>
            <h4>Your cart is empty</h4>
            <p class="text-muted">Add some delicious chicken products to get started!</p>
            <a href="../dashboard.php" class="btn btn-amber mt-3 px-4">
                <i class="fas fa-store me-2"></i>Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Select All Section -->
                <div class="select-all-section">
                    <div class="d-flex align-items-center">
                        <input type="checkbox" id="select-all" class="select-all-checkbox me-3">
                        <label for="select-all" class="mb-0 fw-semibold" style="cursor: pointer;">
                            <i class="fas fa-check-circle me-2"></i>Select All Items
                        </label>
                    </div>
                </div>

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

                    // Show parent header if multiple variants
                    if (count($items) > 1 && $parent['name']):
                ?>
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($parent['image']) ?>"
                             alt="<?= htmlspecialchars($parent['name']) ?>"
                             class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($parent['name']) ?></h6>
                    </div>
                </div>
                <?php
                    endif;

                    // Display each item
                    foreach ($items as $item):
                        $subtotal = $item['quantity'] * $item['item_price'];
                        $totalPrice += $subtotal;

                        // ORIGINAL WORKING IMAGE LOGIC - Use product image if available, otherwise fall back to parent image
                        $imgSrc = !empty($parent['product_image']) ? $parent['product_image'] : $parent['image'];

                        // Stock status
                        $stockClass = 'in-stock';
                        $stockText = 'In Stock';
                        $stockIcon = 'fa-check-circle';
                        if ($item['stock'] <= 0) {
                            $stockClass = 'out-of-stock';
                            $stockText = 'Out of Stock';
                            $stockIcon = 'fa-times-circle';
                        } elseif ($item['stock'] < 10) {
                            $stockClass = 'low-stock';
                            $stockText = 'Only ' . $item['stock'] . ' left!';
                            $stockIcon = 'fa-exclamation-circle';
                        }
                ?>
                <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>">
                    <div class="cart-item-header">
                        <!-- CHECKBOX -->
                        <input type="checkbox"
                               class="item-checkbox"
                               value="<?= $item['cart_id'] ?>"
                               data-cart-id="<?= $item['cart_id'] ?>"
                               <?= $item['stock'] <= 0 ? 'disabled title="Out of stock"' : '' ?>>

                        <img src="<?= htmlspecialchars($imgSrc) ?>"
                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                             class="cart-img"
                             onerror="this.src='../img/products/placeholder.jpg'">

                        <div class="cart-item-details">
                            <div class="cart-item-title">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </div>

                            <?php if ($item['parent_name']): ?>
                            <div class="cart-item-variant">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($item['parent_name']) ?>
                            </div>
                            <?php endif; ?>

                            <div class="cart-item-price">
                                ₱<?= number_format($item['item_price'], 2) ?>
                                <?php if ($item['weight']): ?>
                                <span class="text-muted" style="font-size: 0.9em;"> / <?= htmlspecialchars($item['weight']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="stock-status <?= $stockClass ?>">
                                <i class="fas <?= $stockIcon ?>"></i> <?= $stockText ?>
                            </div>

                            <?php if ($item['stock'] <= 0): ?>
                            <div class="alert alert-danger mt-2 mb-0 py-2" style="font-size: 0.85em;">
                                <i class="fas fa-exclamation-triangle"></i> This item cannot be selected for checkout
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="quantity-controls">
                            <button class="btn btn-sm btn-outline-secondary"
                                    onclick="updateQuantity(<?= $item['cart_id'] ?>, -1)"
                                    <?= $item['stock'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number"
                                   class="form-control quantity-input"
                                   value="<?= $item['quantity'] ?>"
                                   min="1"
                                   max="<?= $item['stock'] ?>"
                                   data-cart-id="<?= $item['cart_id'] ?>"
                                   onchange="handleQuantityChange(this)"
                                   <?= $item['stock'] <= 0 ? 'disabled' : '' ?>>
                            <button class="btn btn-sm btn-outline-secondary"
                                    onclick="updateQuantity(<?= $item['cart_id'] ?>, 1)"
                                    <?= $item['stock'] <= 0 || $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div class="text-end">
                            <div class="fw-bold" style="font-size: 1.1em; color: #d63384;">
                                Subtotal: ₱<span class="item-subtotal" data-cart-id="<?= $item['cart_id'] ?>"><?= number_format($subtotal, 2) ?></span>
                            </div>
                            <button class="btn btn-sm btn-outline-danger mt-2"
                                    onclick="removeFromCart(<?= $item['cart_id'] ?>)">
                                <i class="fas fa-trash me-1"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <?php
                    endforeach;
                endforeach;
                ?>
            </div>

            <!-- ORDER SUMMARY SIDEBAR -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm" style="position: sticky; top: 100px;">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4 fw-bold">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h5>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-3 pb-2 border-bottom">
                                <span class="text-muted">
                                    <i class="fas fa-check-square me-2"></i>Selected Items:
                                </span>
                                <span class="fw-semibold" id="selected-count">0 items</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 pb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-semibold" id="subtotal-price">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pt-2 border-top">
                                <span class="fw-bold fs-5">Total:</span>
                                <span class="fw-bold fs-4" style="color: #d63384;" id="total-price">₱0.00</span>
                            </div>
                        </div>

                        <button id="checkout-btn" class="btn btn-amber w-100 py-3 mb-2" disabled>
                            <i class="fas fa-shopping-bag me-2"></i>Proceed to Checkout
                        </button>

                        <a href="../dashboard.php" class="btn btn-outline-amber w-100 py-2">
                            <i class="fas fa-store me-2"></i>Continue Shopping
                        </a>

                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i> Secure Checkout
                            </small>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded">
                            <h6 class="fw-bold mb-2" style="font-size: 0.9em;">
                                <i class="fas fa-credit-card me-2"></i>Payment Methods
                            </h6>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fas fa-money-bill-wave text-success"></i>
                                <span style="font-size: 0.85em;">Cash on Delivery</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-mobile-alt text-primary"></i>
                                <span style="font-size: 0.85em;">GCash Payment</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Cart items data from PHP
    const cartItemsData = {
        <?php
        foreach ($cartItems as $item) {
            echo "{$item['cart_id']}: {";
            echo "price: {$item['item_price']},";
            echo "quantity: {$item['quantity']},";
            echo "stock: {$item['stock']}";
            echo "},\n";
        }
        ?>
    };

    // Format price helper
    function formatPrice(price) {
        return '₱' + parseFloat(price).toFixed(2);
    }

    // Update order summary based on selected items
    function updateOrderSummary() {
        let selectedCount = 0;
        let subtotal = 0;

        const checkboxes = document.querySelectorAll('.item-checkbox');

        checkboxes.forEach(checkbox => {
            const cartItem = checkbox.closest('.cart-item');

            if (checkbox.checked && !checkbox.disabled) {
                const cartId = checkbox.value;
                if (cartItemsData[cartId]) {
                    selectedCount++;
                    subtotal += cartItemsData[cartId].price * cartItemsData[cartId].quantity;
                }
                cartItem.classList.add('selected');
            } else {
                cartItem.classList.remove('selected');
            }
        });

        // Update UI elements
        document.getElementById('selected-count').textContent = selectedCount + ' item' + (selectedCount !== 1 ? 's' : '');
        document.getElementById('subtotal-price').textContent = formatPrice(subtotal);
        document.getElementById('total-price').textContent = formatPrice(subtotal);

        // Enable/disable checkout button
        const checkoutBtn = document.getElementById('checkout-btn');
        checkoutBtn.disabled = selectedCount === 0;

        // Update select all checkbox state
        const selectAllCheckbox = document.getElementById('select-all');
        const enabledCheckboxes = Array.from(checkboxes).filter(cb => !cb.disabled);
        const checkedCount = enabledCheckboxes.filter(cb => cb.checked).length;

        selectAllCheckbox.checked = checkedCount === enabledCheckboxes.length && enabledCheckboxes.length > 0;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < enabledCheckboxes.length;
    }

    // Select all checkbox handler
    document.getElementById('select-all')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox:not([disabled])');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateOrderSummary();
    });

    // Individual checkbox change handler
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-checkbox')) {
            updateOrderSummary();
        }
    });

    // Checkout button handler
    document.getElementById('checkout-btn')?.addEventListener('click', function() {
        const selectedItems = [];
        document.querySelectorAll('.item-checkbox:checked:not([disabled])').forEach(checkbox => {
            selectedItems.push(checkbox.value);
        });

        if (selectedItems.length > 0) {
            // Store in sessionStorage for checkout page
            sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedItems));
            window.location.href = '../checkout/checkout.php';
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'No Items Selected',
                text: 'Please select at least one item to proceed to checkout',
                confirmButtonColor: '#ff6b26'
            });
        }
    });

    // Quantity update function
    function updateQuantity(cartId, change) {
        const input = document.querySelector(`input[data-cart-id="${cartId}"]`);
        if (!input) return;

        let newQty = parseInt(input.value) + change;
        const minQty = parseInt(input.min);
        const maxStock = parseInt(input.max);

        if (newQty < minQty) newQty = minQty;
        if (newQty > maxStock) {
            Swal.fire({
                icon: 'warning',
                title: 'Stock Limit',
                text: `Only ${maxStock} items available`,
                confirmButtonColor: '#ff6b26',
                timer: 2000
            });
            return;
        }

        input.value = newQty;
        handleQuantityChange(input);
    }

    // Handle quantity changes (with debounce)
    let updateTimeout;
    function handleQuantityChange(input) {
        const cartId = input.dataset.cartId;
        const quantity = parseInt(input.value);
        const min = parseInt(input.min) || 1;
        const max = parseInt(input.max) || Infinity;

        // Validate
        if (isNaN(quantity) || quantity < min) {
            input.value = min;
        } else if (quantity > max) {
            input.value = max;
            Swal.fire({
                icon: 'warning',
                title: 'Stock Limit',
                text: `Only ${max} items available`,
                confirmButtonColor: '#ff6b26',
                timer: 2000
            });
        }

        // Update local data
        if (cartItemsData[cartId]) {
            cartItemsData[cartId].quantity = parseInt(input.value);
        }

        // Update subtotal display
        const subtotalEl = document.querySelector(`.item-subtotal[data-cart-id="${cartId}"]`);
        if (subtotalEl && cartItemsData[cartId]) {
            const newSubtotal = cartItemsData[cartId].price * parseInt(input.value);
            subtotalEl.textContent = newSubtotal.toFixed(2);
        }

        updateOrderSummary();

        // Debounced server update
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(() => {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('quantity', input.value);

            fetch('update_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'success') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: data.message,
                        confirmButtonColor: '#ff6b26'
                    });
                }
            })
            .catch(error => console.error('Error:', error));
        }, 500);
    }

    // Remove from cart function
    function removeFromCart(cartId) {
        Swal.fire({
            title: 'Remove Item?',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('cart_id', cartId);

                fetch('remove_from_cart.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remove item from DOM
                        const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
                        if (cartItem) cartItem.remove();

                        // Remove from data
                        delete cartItemsData[cartId];

                        updateOrderSummary();

                        Swal.fire({
                            icon: 'success',
                            title: 'Removed',
                            text: 'Item removed from cart',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Reload if cart empty
                        if (Object.keys(cartItemsData).length === 0) {
                            setTimeout(() => window.location.reload(), 2000);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#ff6b26'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred',
                        confirmButtonColor: '#ff6b26'
                    });
                });
            }
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Check all available items by default
        document.querySelectorAll('.item-checkbox:not([disabled])').forEach(checkbox => {
            checkbox.checked = true;
        });

        // Initial summary update
        updateOrderSummary();
    });
</script>
</body>
</html>
