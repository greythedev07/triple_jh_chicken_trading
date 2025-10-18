<?php
// Fix synchronization and move delivered orders to proper history tables
// This script moves completed orders from active tables to history tables

require_once('config.php');

try {
    // Find orders that are delivered in to_be_delivered but still in pending_delivery
    $stmt = $db->prepare("
        SELECT tbd.*, pd.*
        FROM to_be_delivered tbd
        JOIN pending_delivery pd ON pd.id = tbd.pending_delivery_id
        WHERE tbd.status = 'delivered'
        AND pd.status != 'delivered'
    ");
    $stmt->execute();
    $deliveredOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($deliveredOrders)) {
        echo "Found " . count($deliveredOrders) . " delivered orders to move to history.\n";

        foreach ($deliveredOrders as $order) {
            $db->beginTransaction();

            try {
                // Clean up active tables
                $deleteItems = $db->prepare("DELETE FROM pending_delivery_items WHERE pending_delivery_id = ?");
                $deleteItems->execute([$order['pending_delivery_id']]);

                $deletePending = $db->prepare("DELETE FROM pending_delivery WHERE id = ?");
                $deletePending->execute([$order['pending_delivery_id']]);

                $deleteTbdItems = $db->prepare("DELETE FROM to_be_delivered_items WHERE to_be_delivered_id = ?");
                $deleteTbdItems->execute([$order['id']]);

                $deleteTbd = $db->prepare("DELETE FROM to_be_delivered WHERE id = ?");
                $deleteTbd->execute([$order['id']]);

                $db->commit();
                echo "Moved order #{$order['pending_delivery_id']} to history.\n";
            } catch (Exception $e) {
                $db->rollBack();
                echo "Error moving order #{$order['pending_delivery_id']}: " . $e->getMessage() . "\n";
            }
        }

        echo "Completed moving delivered orders to history.\n";
    } else {
        echo "No delivered orders found that need to be moved.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
