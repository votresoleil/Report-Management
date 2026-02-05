<?php
require '../config/db.php';
require '../config/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$path = $_GET['path'] ?? null;
$id = $_GET['id'] ?? null;

if ($path) {
    // New approach: archive by file path
    $stmt = $pdo->prepare("UPDATE reports SET status = 'archived' WHERE local_path = ?");
    $stmt->execute([$path]);
    
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'ARCHIVE', 'Archived report: ' . basename($path)]);
    
    echo json_encode(['success' => true, 'message' => 'Report archived successfully.']);
} elseif ($id) {
    // Old approach: archive by ID
    $stmt = $pdo->prepare("UPDATE reports SET status = 'archived' WHERE report_id = ?");
    $stmt->execute([$id]);
    
    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'ARCHIVE', 'Archived report ID: ' . $id]);
    
    echo json_encode(['success' => true, 'message' => 'Report archived successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID or path.']);
}
exit;
?>
