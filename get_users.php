<?php
require 'config/db.php';
require 'config/auth.php';

$stmt = $pdo->prepare("SELECT user_id, username, full_name, role, status FROM users ORDER BY full_name");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
?>