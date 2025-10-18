<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

if ($cart_id > 0) {
  $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
  $stmt->execute([$cart_id, $user_id]);
}

header("Location: cart.php");
exit;
