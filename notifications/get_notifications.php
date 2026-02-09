<?php
require '../config/db.php';
require '../config/auth.php';

header('Content-Type: application/json');

// Get unread notifications count
$countStmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$countStmt->execute([$_SESSION['user_id']]);
$unreadCount = $countStmt->fetch()['unread_count'];

// Get recent notifications (last 10)
$notifStmt = $pdo->prepare("
    SELECT n.*, u.full_name as sender_name 
    FROM notifications n 
    LEFT JOIN users u ON n.sender_id = u.user_id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC 
    LIMIT 10
");
$notifStmt->execute([$_SESSION['user_id']]);
$notifications = $notifStmt->fetchAll();

echo json_encode([
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);
