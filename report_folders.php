<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'folders';
$month = $_GET['month'] ?? null;
$search = $_GET['search'] ?? '';

// Show folder overview
$folders = [];
for ($m = 1; $m <= 12; $m++) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE status = 'active' AND report_month = ?");
    $stmt->execute([$m]);
    $folders[$m] = $stmt->fetch()['count'];
}

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
        <div class="header-section">
            <h2>Report Folders</h2>
            <div class="icons-right">
                <i class="fas fa-users"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>
        <div class="content-section">
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

        <div id="monthModal" class="<?= $month ? 'active' : '' ?>">
            <div class="modal-box large">
                <div class="modal-header">
                    <h2>Reports for <?= $month ? date('F', mktime(0,0,0,$month,1)) : '' ?></h2>
                    <form method="GET" action="report_folders.php" class="search-box">
                        <input type="hidden" name="month" value="<?= $month ?>">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search reports..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </form>
                    <button class="close-btn" id="closeMonthModal">&times;</button>
                </div>
                <div class="modal-content">
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
                                            <button class="archive-btn" data-id="<?= $r['report_id'] ?>" title="Archive">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                            <a href="delete_report.php?id=<?= $r['report_id'] ?>" class="danger" title="Delete" onclick="return confirm('Delete?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="archiveModal">
    <div class="modal-box">
        <h2>Confirm Archive</h2>
        <p>Are you sure you want to archive this report?</p>
        <div style="text-align: center; margin-top: 20px;">
            <button id="confirmArchive" class="btn-primary" style="width: 100px;">Archive</button>
            <button id="cancelArchive" class="btn-primary" style="width: 100px; margin-left: 10px; background: #ccc; color: #333;">Cancel</button>
        </div>
    </div>
</div>

<script>
const monthModal = document.getElementById('monthModal');
const closeMonthModal = document.getElementById('closeMonthModal');
const archiveModal = document.getElementById('archiveModal');
const confirmArchive = document.getElementById('confirmArchive');
const cancelArchive = document.getElementById('cancelArchive');

let archiveId = null;

closeMonthModal.addEventListener('click', () => {
    window.location.href = 'report_folders.php';
});

monthModal.addEventListener('click', (e) => {
    if(e.target === monthModal){
        window.location.href = 'report_folders.php';
    }
});

document.addEventListener('click', (e) => {
    if (e.target.closest('.archive-btn')) {
        const btn = e.target.closest('.archive-btn');
        archiveId = btn.dataset.id;
        archiveModal.classList.add('active');
    }
});

confirmArchive.addEventListener('click', () => {
    if (archiveId) {
        window.location.href = `archive_report.php?id=${archiveId}`;
    }
});

cancelArchive.addEventListener('click', () => {
    archiveModal.classList.remove('active');
    archiveId = null;
});

archiveModal.addEventListener('click', (e) => {
    if (e.target === archiveModal) {
        archiveModal.classList.remove('active');
        archiveId = null;
    }
});
</script>

</body>
</html>