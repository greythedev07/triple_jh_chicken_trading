<?php
header('Content-Type: application/json');
require_once('../config.php');

try {
    // Validate required parameters
    if (empty($_POST['id']) || !isset($_POST['is_parent'])) {
        throw new Exception('Missing required parameters');
    }

    $id = (int)$_POST['id'];
    $isParent = $_POST['is_parent'] === 'true';
    $deleteChildren = isset($_POST['delete_children']) && $_POST['delete_children'] === 'true';

    if ($isParent) {
        // Handle parent product deletion
        if ($deleteChildren) {
            // Delete all child products first
            $deleteChildrenStmt = $db->prepare("DELETE FROM products WHERE parent_id = ?");
            $deleteChildrenStmt->execute([$id]);
        } else {
            // Check if parent has any active children
            $checkChildren = $db->prepare("SELECT COUNT(*) FROM products WHERE parent_id = ? AND is_active = 1");
            $checkChildren->execute([$id]);
            $hasActiveChildren = $checkChildren->fetchColumn() > 0;

            if ($hasActiveChildren) {
                throw new Exception('Cannot delete parent product with active variants. Please delete or deactivate all variants first.');
            }
        }

        // Get image path before deleting
        $getImage = $db->prepare("SELECT image FROM parent_products WHERE id = ?");
        $getImage->execute([$id]);
        $imagePath = $getImage->fetchColumn();

        // Delete the parent product
        $deleteParent = $db->prepare("DELETE FROM parent_products WHERE id = ?");
        $result = $deleteParent->execute([$id]);

        // If deletion was successful, delete the associated image
        if ($result && $imagePath && file_exists('../' . $imagePath)) {
            @unlink('../' . $imagePath);
        }

        $message = 'Product' . ($deleteChildren ? ' and all its variants' : '') . ' deleted successfully';
    } else {
        // Handle child product deletion
        // For child products, we'll just mark them as inactive instead of deleting
        $updateChild = $db->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $result = $updateChild->execute([$id]);

        // If you want to permanently delete instead of deactivating, use:
        // $deleteChild = $db->prepare("DELETE FROM products WHERE id = ?");
        // $result = $deleteChild->execute([$id]);

        $message = 'Product variant deleted successfully';
    }

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => $message
        ]);
    } else {
        throw new Exception('Failed to delete product');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
