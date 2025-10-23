<?php
// includes/order_helper.php
// Helper functions for order management

/**
 * Generate a unique order number in format: TJH-YYYYMMDD-XXXX
 * Where XXXX is a 4-digit sequential number for the day
 */
function generateOrderNumber($db)
{
    $today = date('Ymd'); // Format: 20250121
    $prefix = "TJH-{$today}-";

    // Get the highest order number for today
    $stmt = $db->prepare("
        SELECT order_number 
        FROM pending_delivery 
        WHERE order_number LIKE ? 
        ORDER BY order_number DESC 
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $lastOrder = $stmt->fetchColumn();

    if ($lastOrder) {
        // Extract the sequential number from the last order
        $lastNumber = (int)substr($lastOrder, -4);
        $nextNumber = $lastNumber + 1;
    } else {
        // First order of the day
        $nextNumber = 1;
    }

    // Format with leading zeros (4 digits)
    $sequential = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

    return $prefix . $sequential;
}

/**
 * Format order number for display
 * Converts TJH-20250121-0001 to TJH-2025-01-21-0001 for better readability
 */
function formatOrderNumber($orderNumber)
{
    if (!$orderNumber) return 'N/A';

    // Check if it matches our format: TJH-YYYYMMDD-XXXX
    if (preg_match('/^(TJH-)(\d{4})(\d{2})(\d{2})-(\d{4})$/', $orderNumber, $matches)) {
        return $matches[1] . $matches[2] . '-' . $matches[3] . '-' . $matches[4] . '-' . $matches[5];
    }

    return $orderNumber;
}

/**
 * Get order details by order number
 */
function getOrderByNumber($db, $orderNumber)
{
    $stmt = $db->prepare("
        SELECT pd.*, u.firstname, u.lastname, u.email, u.phonenumber
        FROM pending_delivery pd
        JOIN users u ON pd.user_id = u.id
        WHERE pd.order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get order items by order ID
 */
function getOrderItems($db, $orderId)
{
    $stmt = $db->prepare("
        SELECT pdi.*, p.name as product_name
        FROM pending_delivery_items pdi
        JOIN products p ON pdi.product_id = p.id
        WHERE pdi.pending_delivery_id = ?
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
