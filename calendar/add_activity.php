<?php
require '../config/db.php';
require '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO activities (user_id, title, description, start_date, regulatory_agency, report_details, concern_department, deadline_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['title'],
        $_POST['description'],
        $_POST['start_date'],
        $_POST['regulatory_agency'],
        $_POST['report_details'],
        $_POST['concern_department'],
        $_POST['deadline_date']
    ]);
    $activity_id = $pdo->lastInsertId();
    
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'ADD_ACTIVITY', 'Added activity: ' . $_POST['title']]);

    // Get current user info
    $userStmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $currentUser = $userStmt->fetch();
    
    // Create notifications for all other users
    $notificationTitle = 'New Activity Added';
    $notificationMessage = $currentUser['full_name'] . ' added a new activity: "' . $_POST['title'] . '" for ' . date('F j, Y', strtotime($_POST['start_date']));
    $notificationLink = '../dashboard/dashboard.php?month=' . date('m', strtotime($_POST['start_date'])) . '&year=' . date('Y', strtotime($_POST['start_date']));
    
    // Get all users except the current user
    $allUsers = $pdo->prepare("SELECT user_id FROM users WHERE user_id != ?");
    $allUsers->execute([$_SESSION['user_id']]);
    $users = $allUsers->fetchAll();
    
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, title, message, link) VALUES (?, ?, 'activity_added', ?, ?, ?)");
    foreach ($users as $user) {
        $notifStmt->execute([
            $user['user_id'],
            $_SESSION['user_id'],
            $notificationTitle,
            $notificationMessage,
            $notificationLink
        ]);
    }

    $_SESSION['activity_added'] = true;
    $_SESSION['selected_date'] = $_POST['start_date'];
    $activityMonth = date('m', strtotime($_POST['start_date']));
    $activityYear = date('Y', strtotime($_POST['start_date']));
    header("Location: ../dashboard/dashboard.php?month=$activityMonth&year=$activityYear");
    exit;
}
?>