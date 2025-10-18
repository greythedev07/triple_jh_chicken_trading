<?php
require_once('../config.php');
$id = $_POST['id'] ?? 0;
$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
$ok = $stmt->execute([$id]);
echo json_encode(['status' => $ok ? 'success' : 'error']);
