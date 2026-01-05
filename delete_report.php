<?php
require 'config/db.php';
require 'config/auth.php';

if (!isAdmin()) die("Access denied.");

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT local_path FROM reports WHERE report_id=?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if ($file) {
    unlink($file['local_path']);
    $pdo->prepare("DELETE FROM reports WHERE report_id=?")->execute([$id]);

    $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, description)
        VALUES (?,?,?)
    ")->execute([
        $_SESSION['user_id'],
        'DELETE',
        'Deleted report ID ' . $id
    ]);
}

header("Location: dashboard.php");
