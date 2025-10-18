<?php
require_once('../config.php');
$stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
