<?php
session_start();
require_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: cart.php');
  exit;
}

if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

try {
  // Step 1: Clamp quantity to available stock
  $q = $db->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
  $q->execute([$cart_id, $user_id]);
  $available = (int)$q->fetchColumn();
  if ($available <= 0) {
    $quantity = 1;
  } else if ($quantity > $available) {
    $quantity = $available;
  }

  // Step 2: Update cart quantity
  $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
  $stmt->execute([$quantity, $cart_id, $user_id]);
} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}

header('Location: cart.php');
exit;
