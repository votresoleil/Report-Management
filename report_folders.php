<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'folders';
$month = $_GET['month'] ?? null;
$search = $_GET['search'] ?? '';

if ($month) {
    // Show reports for specific month
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name
        FROM reports r
        JOIN users u ON r.uploaded_by = u.user_id
        WHERE r.status = 'active' AND r.report_month = ? AND r.report_title LIKE ?
        ORDER BY r.report_year DESC
    ");
    $stmt->execute([$month, "%$search%"]);
    $reports = $stmt->fetchAll();
} else {
    // Show folder overview
    $folders = [];
    for ($m = 1; $m <= 12; $m++) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE status = 'active' AND report_month = ?");
        $stmt->execute([$m]);
        $folders[$m] = $stmt->fetch()['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Folders</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="main-top-bar">
            <h2>Report Folders</h2>
            <div class="search-center">
                <form method="GET" action="report_folders.php" class="search-box">
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search reports..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </form>
            </div>
            <div class="icons-right">
                <i class="fas fa-users"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>
        <?php if ($month): ?>
            <h2>Reports for <?= date('F', mktime(0,0,0,$month,1)) ?></h2>
            <a href="report_folders.php">Back to Folders</a>
            <div class="reports">
                <?php if (empty($reports)): ?>
                    <p class="empty-state">No reports found for this month.</p>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <div class="report-card">
                            <div class="report-info">
                                <i class="fas fa-file-alt"></i>
                                <span><?= htmlspecialchars($r['report_title']) ?></span>
                            </div>
                            <div class="report-actions">
                                <a href="<?= htmlspecialchars($r['local_path']) ?>" target="_blank" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (isAdmin()): ?>
                                    <a href="archive_report.php?id=<?= $r['report_id'] ?>" title="Archive">
                                        <i class="fas fa-archive"></i>
                                    </a>
                                    <a href="delete_report.php?id=<?= $r['report_id'] ?>" class="danger" title="Delete" onclick="return confirm('Delete?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="folders-container">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <div class="folder-card">
                        <a href="?month=<?= $m ?>">
                            <i class="fas fa-folder"></i>
                            <h3><?= date('F', mktime(0,0,0,$m,1)) ?></h3>
                            <p><?= $folders[$m] ?> reports</p>
                        </a>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>