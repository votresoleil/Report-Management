<?php
require 'config/db.php';
require 'config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $regulatory_agency = $_POST['regulatory_agency'];
    $report_details = $_POST['report_details'];
    $concern_department = $_POST['concern_department'];
    $deadline_date = $_POST['deadline_date'];

    $stmt = $pdo->prepare("UPDATE activities SET title = ?, regulatory_agency = ?, report_details = ?, concern_department = ?, deadline_date = ? WHERE id = ?");
    $stmt->execute([$title, $regulatory_agency, $report_details, $concern_department, $deadline_date, $id]);

    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'UPDATE_ACTIVITY', 'Updated activity ID: ' . $id]);

    $_SESSION['activity_updated'] = true;
    header('Location: dashboard.php');
    exit;
}

$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if ($id && $status) {
    $stmt = $pdo->prepare("UPDATE activities SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'UPDATE_ACTIVITY', 'Updated activity ID: ' . $id . ' to ' . $status]);

    header('Location: dashboard.php?view=calendar');
    exit;
}
?>