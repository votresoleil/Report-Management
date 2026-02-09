<?php
require '../config/db.php';
require '../config/auth.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE activities SET archived = 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'ARCHIVE_ACTIVITY', 'Archived activity ID: ' . $id]);

    $_SESSION['activity_archived'] = true;
    header("Location: ../dashboard/dashboard.php");
    exit;
} else {
    header("Location: ../dashboard/dashboard.php");
    exit;
}
