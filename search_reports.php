<?php
require 'config/db.php';
require 'config/auth.php';

$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$status = $_GET['status'] ?? 'active';

$query = "
    SELECT r.*, u.full_name, r.uploaded_at
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.status = ? AND r.report_title LIKE ?
";
$params = [$status, "%$search%"];

if ($year) {
    $query .= " AND r.report_year = ?";
    $params[] = $year;
}

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