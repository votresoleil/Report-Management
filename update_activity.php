<?php
require 'config/db.php';
require 'config/auth.php';

$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if (!$id || !$status) {
    die("Invalid request.");
}

$stmt = $pdo->prepare("UPDATE activities SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

// Log
$log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
$log->execute([$_SESSION['user_id'], 'UPDATE_ACTIVITY', 'Updated activity ID: ' . $id . ' to ' . $status]);

header('Location: dashboard.php?view=calendar');
exit;
?>