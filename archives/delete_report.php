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
    // New approach: delete by file path
    if (file_exists($path)) {
        unlink($path);
    }
    $pdo->prepare("DELETE FROM reports WHERE local_path = ?")->execute([$path]);
    
    $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, description)
        VALUES (?,?,?)
    ")->execute([
        $_SESSION['user_id'],
        'DELETE',
        'Deleted report: ' . basename($path)
    ]);
    echo json_encode(['success' => true, 'message' => 'Report deleted successfully.']);
} elseif ($id) {
    // Old approach: delete by ID
    $stmt = $pdo->prepare("SELECT local_path FROM reports WHERE report_id=?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();

    if ($file) {
        if (file_exists($file['local_path'])) {
            unlink($file['local_path']);
        }
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
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID or path.']);
}
exit;
?>
