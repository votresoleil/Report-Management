<?php
require 'config/db.php';
require 'config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO activities (user_id, title, description, start_date, deadline) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['title'],
        $_POST['description'],
        $_POST['start_date'],
        $_POST['deadline']
    ]);

    // Log
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'ADD_ACTIVITY', 'Added activity: ' . $_POST['title']]);

    $_SESSION['activity_added'] = true;
    $_SESSION['selected_date'] = $_POST['start_date'];
    header('Location: dashboard.php');
    exit;
}
?>