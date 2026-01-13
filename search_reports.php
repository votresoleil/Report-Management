<?php
require 'config/db.php';
require 'config/auth.php';

$search = $_GET['search'] ?? '';
$month = $_GET['month'] ?? null;

$query = "
    SELECT r.*, u.full_name, r.uploaded_at
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.status = 'active' AND r.report_title LIKE ?
";
$params = ["%$search%"];

if ($month) {
    $query .= " AND r.report_month = ?";
    $params[] = $month;
}

$query .= $month ? " ORDER BY r.report_year DESC" : " ORDER BY r.report_id DESC";

if (!$month) {
    $query .= " LIMIT 5";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($reports);
?>