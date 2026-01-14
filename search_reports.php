<?php
require 'config/db.php';
require 'config/auth.php';

$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$status = $_GET['status'] ?? 'active';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$query = "
    SELECT r.*, u.full_name, r.uploaded_at
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.status = ? AND (r.report_title LIKE ? OR r.file_name LIKE ?)
";
$params = [$status, "%$search%", "%$search%"];

if ($year) {
    $query .= " AND r.report_year = ?";
    $params[] = $year;
}

if ($month) {
    $query .= " AND r.report_month = ?";
    $params[] = $month;
}

$query .= $month ? " ORDER BY r.report_year DESC" : " ORDER BY r.report_id DESC";

$query .= " LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($query);
// Bind parameters
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex++, $param);
}
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$countQuery = "
    SELECT COUNT(*) as total
    FROM reports r
    WHERE r.status = ? AND r.report_title LIKE ?
";
$countParams = [$status, "%$search%"];

if ($year) {
    $countQuery .= " AND r.report_year = ?";
    $countParams[] = $year;
}

if ($month) {
    $countQuery .= " AND r.report_month = ?";
    $countParams[] = $month;
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];

echo json_encode(['reports' => $reports, 'total' => $total]);
?>