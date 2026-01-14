<?php
require 'config/db.php';
require 'config/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

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
    echo json_encode(['success' => true, 'message' => 'Report deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Report not found.']);
}
exit;
