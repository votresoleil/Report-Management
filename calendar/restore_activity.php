<?php
require '../config/db.php';
require '../config/auth.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE activities SET archived = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'RESTORE_ACTIVITY', 'Restored activity ID: ' . $id]);

    $_SESSION['activity_restored'] = true;
    header("Location: ../dashboard/dashboard.php");
    exit;
} else {
    header("Location: ../dashboard/dashboard.php");
    exit;
}
