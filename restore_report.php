<?php
require 'config/db.php';
require 'config/auth.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid request.");
}

$stmt = $pdo->prepare("UPDATE reports SET status = 'active' WHERE report_id = ?");
$stmt->execute([$id]);

// Log
$log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
$log->execute([$_SESSION['user_id'], 'RESTORE_REPORT', 'Restored report ID: ' . $id]);

header('Location: archives.php');
exit;
?>