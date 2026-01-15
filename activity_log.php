<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'logs';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM activity_logs");
$total_stmt->execute();
$total_logs = $total_stmt->fetch()['total'];

$total_pages = ceil($total_logs / $per_page);

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name
    FROM activity_logs a
    JOIN users u ON a.user_id = u.user_id
    ORDER BY a.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute();
$logs = $stmt->fetchAll();

// Action mapping
$action_map = [
    'ADD_ACTIVITY' => 'ADD',
    'UPDATE_ACTIVITY' => 'UPDATE',
    'ARCHIVE' => 'ARCHIVE',
    'RESTORE_REPORT' => 'RESTORE',
    'UPLOAD' => 'UPLOAD',
    'DELETE' => 'DELETE'
];

// Status mapping
$status_map = [
    'ADD_ACTIVITY' => 'IN PROGRESS',
    'DELETE' => 'DELETED',
    'ARCHIVE' => 'ARCHIVED',
    'RESTORE_REPORT' => 'RESTORED',
    'UPLOAD' => 'UPLOADED'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-section">
            <h2>Activity Log</h2>
            <div class="icons-right">
                <i class="fas fa-users"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>
        <div class="content-section">
            <?php if (empty($logs)): ?>
                <p>No activities found.</p>
            <?php else: ?>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $action = $action_map[$log['action']] ?? $log['action'];
                            if ($log['action'] == 'UPDATE_ACTIVITY') {
                                $pos = strpos($log['description'], 'to ');
                                $status = $pos !== false ? substr($log['description'], $pos + 3) : $log['description'];
                            } else {
                                $status = $status_map[$log['action']] ?? 'UNKNOWN';
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($log['full_name']) ?></td>
                                <td><?= htmlspecialchars($action) ?></td>
                                <td><?= htmlspecialchars($status) ?></td>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="page-btn">Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="page-btn">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>