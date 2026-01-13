<?php
require 'config/db.php';
require 'config/auth.php';

$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, r.uploaded_at
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.status = 'active' AND r.report_title LIKE ?
    ORDER BY r.report_id DESC
    LIMIT 5
");
$stmt->execute(["%$search%"]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($reports);
?>