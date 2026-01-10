<?php
require 'config/db.php';
require 'config/auth.php';

if (!isAdmin()) {
    die("Access denied.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid ID.");
}

$stmt = $pdo->prepare("UPDATE reports SET status = 'archived' WHERE report_id = ?");
$stmt->execute([$id]);

// Log activity
$log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
$log->execute([$_SESSION['user_id'], 'ARCHIVE', 'Archived report ID: ' . $id]);

header('Location: dashboard.php?view=folders');
exit;
?>