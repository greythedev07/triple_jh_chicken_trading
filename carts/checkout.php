<?php
// checkout/checkout.php - DISPLAYS ONLY SELECTED ITEMS
session_start();
require_once('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../useraccounts/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($user_query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get GCash QR codes (if available)
$gcash_query = "SELECT * FROM gcash_qr_codes WHERE is_active = 1 ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($gcash_query);
$stmt->execute();
$gcash_qr = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Triple JH Chicken Trading</title>
    <link rel="icon" href="../img/logo.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/footer_header.css">
    <style>
        body { background-color: #ffe4c1; font-family: "Inter", sans-serif; }
        .checkout-container { background: #fff5e2; border-radius: 15px; padding: 2rem; margin-top: 100px; margin-bottom: 50px; }
        .section-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .order-item { padding: 15px; border-bottom: 1px solid #f0f0f0; }
        .order-item:last-child { border-bottom: none; }
        .payment-method { cursor: pointer; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; transition: all 0.3s; }
        .payment-method:hover { border-color: #ff6b26; background: #fff8f0; }
        .payment-method.selected { border-color: #ff6b26; background: #fff8f0; }
        .btn-amber {
            background: linear-gradient(180deg, #ffb347, #ff6b26);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 12px 30px;
            font-weight: 500;
        }
        .btn-amber:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 107, 38, 0.3); }
        .gcash-details { display: none; }
        .gcash-details.show { display: block; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../carts/cart.php">
            <i class="fas fa-arrow-left me-2"></i> Back to Cart
        </a>
    </div>
</nav>

<div class="container checkout-container">
    <h2 class="mb-4 fw-bold" style="color: #6d3209;">
        <i class="fas fa-shopping-bag me-2"></i>Checkout
    </h2>

    <div class="row">
        <div class="col-lg-8">
            <!-- Delivery Information -->
            <div class="section-card">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-map-marker-alt me-2"></i>Delivery Information
                </h5>
                <form id="checkout-form">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['phonenumber'] ?? '') ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Delivery Address *</label>
                        <textarea name="delivery_address" class="form-control" rows="3" required 
                                  placeholder="Enter complete delivery address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        <small class="text-muted">Please provide house/unit number, street, barangay, and city</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Landmark (Optional)</label>
                        <input type="text" name="landmark" class="form-control" 
                               placeholder="e.g., Near 7-Eleven, Beside Jollibee"
                               value="<?= htmlspecialchars($user['landmark'] ?? '') ?>">
                    </div>
                </form>
            </div>

            <!-- Payment Method -->
            <div class="section-card">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-credit-card me-2"></i>Payment Method
                </h5>
                
                <div class="payment-method mb-3" data-method="cod">
                    <div class="d-flex align-items-center">
                        <input type="radio" name="payment_method" value="cod" id="cod" class="me-3" checked>
                        <label for="cod" class="mb-0 flex-grow-1" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-money-bill-wave fa-2x text-success me-3"></i>
                                <div>
                                    <div class="fw-semibold">Cash on Delivery</div>
                                    <small class="text-muted">Pay when you receive your order</small>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="payment-method" data-method="gcash">
                    <div class="d-flex align-items-center">
                        <input type="radio" name="payment_method" value="gcash" id="gcash" class="me-3">
                        <label for="gcash" class="mb-0 flex-grow-1" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-mobile-alt fa-2x text-primary me-3"></i>
                                <div>
                                    <div class="fw-semibold">GCash Payment</div>
                                    <small class="text-muted">Pay via GCash QR code</small>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- GCash Details (Hidden by default) -->
                <div class="gcash-details mt-3 p-3 bg-light rounded">
                    <h6 class="fw-bold mb-3">GCash Payment Instructions</h6>
                    
                    <?php if ($gcash_qr): ?>
                    <div class="text-center mb-3">
                        <img src="../<?= htmlspecialchars($gcash_qr['qr_code_path']) ?>" 
                             alt="GCash QR" 
                             class="img-fluid" 
                             style="max-width: 200px; border: 2px solid #0066ff; border-radius: 10px;">
                        <p class="mt-2 fw-semibold">Scan to Pay</p>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">GCash Reference Number *</label>
                        <input type="text" name="gcash_reference" class="form-control" 
                               placeholder="Enter GCash reference number">
                        <small class="text-muted">Enter the reference number from your GCash transaction</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">GCash Phone Number *</label>
                        <input type="text" name="gcash_phone" class="form-control" 
                               placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11">
                        <small class="text-muted">Your GCash-registered mobile number</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Your order will be processed after admin verification of payment</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="section-card" style="position: sticky; top: 100px;">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-receipt me-2"></i>Order Summary
                </h5>

                <div id="order-items-container">
                    <!-- Items will be loaded here by JavaScript -->
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span class="fw-semibold" id="checkout-subtotal">₱0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                    <span>Delivery Fee:</span>
                    <span class="fw-semibold text-success">FREE</span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">Total:</span>
                    <span class="fw-bold fs-4" style="color: #d63384;" id="checkout-total">₱0.00</span>
                </div>

                <button type="button" id="place-order-btn" class="btn btn-amber w-100 py-3" disabled>
                    <i class="fas fa-check-circle me-2"></i>Place Order
                </button>

                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i> Your order is secure
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Get selected items from sessionStorage
    const selectedCartIds = JSON.parse(sessionStorage.getItem('selectedCartItems') || '[]');

    if (selectedCartIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Items Selected',
            text: 'Please select items from your cart first',
            confirmButtonColor: '#ff6b26'
        }).then(() => {
            window.location.href = '../carts/cart.php';
        });
    }

    // Fetch ONLY selected cart items
    async function loadSelectedItems() {
        try {
            const formData = new FormData();
            formData.append('cart_ids', selectedCartIds.join(','));

            const response = await fetch('../checkout/get_selected_items.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                displayOrderItems(data.items);
                updateOrderTotal(data.total);
                document.getElementById('place-order-btn').disabled = false;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading items:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load selected items',
                confirmButtonColor: '#ff6b26'
            }).then(() => {
                window.location.href = '../carts/cart.php';
            });
        }
    }

    function displayOrderItems(items) {
        const container = document.getElementById('order-items-container');
        
        if (!items || items.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No items</p>';
            return;
        }

        let html = '';
        items.forEach(item => {
            html += `
                <div class="order-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${item.product_name}</div>
                            <small class="text-muted">₱${parseFloat(item.price).toFixed(2)} × ${item.quantity}</small>
                        </div>
                        <div class="fw-semibold">₱${(item.price * item.quantity).toFixed(2)}</div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function updateOrderTotal(total) {
        document.getElementById('checkout-subtotal').textContent = '₱' + parseFloat(total).toFixed(2);
        document.getElementById('checkout-total').textContent = '₱' + parseFloat(total).toFixed(2);
    }

    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;

            // Update selected state
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');

            // Show/hide GCash details
            const gcashDetails = document.querySelector('.gcash-details');
            if (radio.value === 'gcash') {
                gcashDetails.classList.add('show');
                // Make GCash fields required
                document.querySelector('[name="gcash_reference"]').required = true;
                document.querySelector('[name="gcash_phone"]').required = true;
            } else {
                gcashDetails.classList.remove('show');
                // Make GCash fields optional
                document.querySelector('[name="gcash_reference"]').required = false;
                document.querySelector('[name="gcash_phone"]').required = false;
            }
        });
    });

    // Place order handler
    document.getElementById('place-order-btn').addEventListener('click', async function() {
        const form = document.getElementById('checkout-form');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        formData.append('payment_method', paymentMethod);
        formData.append('selected_items', selectedCartIds.join(','));

        // Validate GCash fields if needed
        if (paymentMethod === 'gcash') {
            const gcashRef = formData.get('gcash_reference');
            const gcashPhone = formData.get('gcash_phone');

            if (!gcashRef || !gcashPhone) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please fill in GCash payment details',
                    confirmButtonColor: '#ff6b26'
                });
                return;
            }
        }

        // Show loading
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            const response = await fetch('../checkout/checkout_process.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Clear selected items from sessionStorage
                sessionStorage.removeItem('selectedCartItems');

                Swal.fire({
                    icon: 'success',
                    title: 'Order Placed!',
                    text: `Order #${data.order_number} has been placed successfully`,
                    confirmButtonColor: '#ff6b26'
                }).then(() => {
                    window.location.href = '../orders/orders.php';
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Checkout error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Checkout Failed',
                text: error.message || 'An error occurred while placing your order',
                confirmButtonColor: '#ff6b26'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // Load items on page load
    loadSelectedItems();
</script>
</body>
</html>
