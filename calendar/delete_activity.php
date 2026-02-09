<?php
require '../config/db.php';
require '../config/auth.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'DELETE_ACTIVITY', 'Deleted activity ID: ' . $id]);

    $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['activity_deleted'] = true;
    header("Location: ../dashboard/dashboard.php");
    exit;
} else {
    header("Location: ../dashboard/dashboard.php");
    exit;
}
