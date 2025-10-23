<!DOCTYPE html>
<html>

<head>
    <title>Test Delivery Process - Triple JH Chicken Trading</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .info {
            color: #0c5460;
            background: #d1ecf1;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }

        h1,
        h2 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #1e7e34;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .step {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .step-number {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🧪 Test Delivery Process - Triple JH Chicken Trading</h1>

        <?php
        require_once('config.php');

        try {
            echo "<div class='info'>This tool will help you test the complete delivery process to ensure order history works correctly.</div>";

            // Check current system state
            echo "<div class='step'>";
            echo "<h2><span class='step-number'>1</span>Current System State</h2>";

            $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $totalDrivers = $db->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
            $totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $totalPending = $db->query("SELECT COUNT(*) FROM pending_delivery")->fetchColumn();
            $totalTbd = $db->query("SELECT COUNT(*) FROM to_be_delivered")->fetchColumn();
            $totalHistory = $db->query("SELECT COUNT(*) FROM history_of_delivery")->fetchColumn();

            echo "<table>";
            echo "<tr><th>Component</th><th>Count</th><th>Status</th></tr>";
            echo "<tr><td>Users</td><td>" . $totalUsers . "</td><td>" . ($totalUsers > 0 ? "✅ Ready" : "❌ Need users") . "</td></tr>";
            echo "<tr><td>Drivers</td><td>" . $totalDrivers . "</td><td>" . ($totalDrivers > 0 ? "✅ Ready" : "❌ Need drivers") . "</td></tr>";
            echo "<tr><td>Products</td><td>" . $totalProducts . "</td><td>" . ($totalProducts > 0 ? "✅ Ready" : "❌ Need products") . "</td></tr>";
            echo "<tr><td>Pending Deliveries</td><td>" . $totalPending . "</td><td>" . ($totalPending > 0 ? "✅ Has orders" : "⚠️ No orders") . "</td></tr>";
            echo "<tr><td>To Be Delivered</td><td>" . $totalTbd . "</td><td>" . ($totalTbd > 0 ? "✅ Has pickups" : "⚠️ No pickups") . "</td></tr>";
            echo "<tr><td>History of Delivery</td><td>" . $totalHistory . "</td><td>" . ($totalHistory > 0 ? "✅ Has history" : "❌ No history") . "</td></tr>";
            echo "</table>";
            echo "</div>";

            // Check for orders that can be tested
            echo "<div class='step'>";
            echo "<h2><span class='step-number'>2</span>Available Orders for Testing</h2>";

            $availableOrders = $db->query("
                SELECT pd.*, CONCAT(u.firstname, ' ', u.lastname) AS customer_name, d.name AS driver_name
                FROM pending_delivery pd
                LEFT JOIN users u ON pd.user_id = u.id
                LEFT JOIN drivers d ON pd.driver_id = d.id
                WHERE pd.status NOT IN ('delivered', 'cancelled', 'Cancelled')
                ORDER BY pd.date_requested DESC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);

            if (count($availableOrders) > 0) {
                echo "<p><strong>Found " . count($availableOrders) . " orders available for testing:</strong></p>";
                echo "<table>";
                echo "<tr><th>Order ID</th><th>Customer</th><th>Status</th><th>Driver</th><th>Amount</th><th>Action</th></tr>";
                foreach ($availableOrders as $order) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($order['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['status']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['driver_name'] ?? 'Not assigned') . "</td>";
                    echo "<td>₱" . number_format($order['total_amount'], 2) . "</td>";
                    echo "<td>";
                    if ($order['driver_id']) {
                        echo "<span style='color: green;'>✅ Ready for pickup</span>";
                    } else {
                        echo "<span style='color: orange;'>⚠️ Needs driver assignment</span>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ No orders available for testing. You need to create some orders first.</div>";
            }
            echo "</div>";

            // Testing steps
            echo "<div class='step'>";
            echo "<h2><span class='step-number'>3</span>Testing Steps</h2>";

            if ($totalUsers == 0) {
                echo "<div class='error'>❌ <strong>Step 1:</strong> Create a customer account first</div>";
                echo "<p>• Go to: <a href='index.php' target='_blank'>Home Page</a></p>";
                echo "<p>• Register a new customer account</p>";
            } else {
                echo "<div class='success'>✅ <strong>Step 1:</strong> Customer accounts exist</div>";
            }

            if ($totalDrivers == 0) {
                echo "<div class='error'>❌ <strong>Step 2:</strong> Create a driver account</div>";
                echo "<p>• Go to: <a href='drivers/driver_login.php' target='_blank'>Driver Login</a></p>";
                echo "<p>• Register a new driver account</p>";
            } else {
                echo "<div class='success'>✅ <strong>Step 2:</strong> Driver accounts exist</div>";
            }

            if ($totalProducts == 0) {
                echo "<div class='error'>❌ <strong>Step 3:</strong> Add products</div>";
                echo "<p>• Go to: <a href='adminaccounts/admin_login.php' target='_blank'>Admin Login</a></p>";
                echo "<p>• Add some products to the catalog</p>";
            } else {
                echo "<div class='success'>✅ <strong>Step 3:</strong> Products exist</div>";
            }

            if ($totalPending == 0) {
                echo "<div class='error'>❌ <strong>Step 4:</strong> Create an order</div>";
                echo "<p>• Login as customer</p>";
                echo "<p>• Add products to cart</p>";
                echo "<p>• Complete checkout</p>";
            } else {
                echo "<div class='success'>✅ <strong>Step 4:</strong> Orders exist</div>";
            }

            echo "<div class='info'>";
            echo "<strong>Step 5:</strong> Assign driver to order<br>";
            echo "• Login as admin<br>";
            echo "• Go to pending deliveries<br>";
            echo "• Assign a driver to an order<br>";
            echo "</div>";

            echo "<div class='info'>";
            echo "<strong>Step 6:</strong> Driver pickup<br>";
            echo "• Login as driver<br>";
            echo "• Go to pickup tab<br>";
            echo "• Confirm pickup with photo<br>";
            echo "</div>";

            echo "<div class='info'>";
            echo "<strong>Step 7:</strong> Complete delivery<br>";
            echo "• Go to delivering tab<br>";
            echo "• Complete delivery with payment<br>";
            echo "• Check if order appears in history<br>";
            echo "</div>";
            echo "</div>";

            // Quick test buttons
            echo "<div class='step'>";
            echo "<h2><span class='step-number'>4</span>Quick Test Actions</h2>";
            echo "<p>Use these links to quickly test different parts of the system:</p>";
            echo "<a href='index.php' class='btn'>🏠 Customer Home</a>";
            echo "<a href='drivers/driver_login.php' class='btn'>🚚 Driver Login</a>";
            echo "<a href='adminaccounts/admin_login.php' class='btn'>👨‍💼 Admin Login</a>";
            echo "<a href='orders/orders.php' class='btn btn-success'>📋 Check Order History</a>";
            echo "<a href='debug_order_history.php' class='btn btn-warning'>🔍 Debug Again</a>";
            echo "</div>";

            // Summary
            echo "<div class='step'>";
            echo "<h2><span class='step-number'>5</span>Expected Result</h2>";
            echo "<div class='success'>";
            echo "<strong>After completing a delivery, you should see:</strong><br>";
            echo "• Order appears in customer's order history<br>";
            echo "• Order shows correct order number and payment method<br>";
            echo "• No more 'undefined array key' errors<br>";
            echo "• Complete order details in history<br>";
            echo "</div>";
            echo "</div>";
        } catch (PDOException $e) {
            echo "<div class='error'><strong>❌ Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>

        <hr>
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn">🏠 Go to Home</a>
            <a href="debug_order_history.php" class="btn">🔍 Debug Again</a>
        </div>

        <div class="info" style="margin-top: 20px;">
            <strong>📝 Note:</strong> After testing, you can safely delete this file (test_delivery_process.php) for security.
        </div>
    </div>
</body>

</html>