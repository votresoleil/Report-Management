<?php
require 'config/db.php';
require 'config/auth.php';

$view = $_GET['view'] ?? 'main';
$search = $_GET['search'] ?? '';

$data = [];

if ($view === 'main') {

    $currentMonth = date('m');
    $currentYear = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reports WHERE report_month = ? AND report_year = ? AND status = 'active'");
    $stmt->execute([$currentMonth, $currentYear]);
    $data['total_this_month'] = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name
        FROM reports r
        JOIN users u ON r.uploaded_by = u.user_id
        WHERE r.status = 'active'
        ORDER BY r.report_id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $data['recent_reports'] = $stmt->fetchAll();

} elseif ($view === 'folders') {
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name
        FROM reports r
        JOIN users u ON r.uploaded_by = u.user_id
        WHERE r.status = 'active' AND r.report_title LIKE ?
        ORDER BY r.report_year DESC, r.report_month DESC
    ");
    $stmt->execute(["%$search%"]);
    $reports = $stmt->fetchAll();
    $data['reports'] = $reports;

} elseif ($view === 'archives') {
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name
        FROM reports r
        JOIN users u ON r.uploaded_by = u.user_id
        WHERE r.status = 'archived' AND r.report_title LIKE ?
        ORDER BY r.report_year DESC, r.report_month DESC
    ");
    $stmt->execute(["%$search%"]);
    $reports = $stmt->fetchAll();
    $data['reports'] = $reports;

} elseif ($view === 'calendar') {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY start_date");
    $stmt->execute([$_SESSION['user_id']]);
    $data['activities'] = $stmt->fetchAll();

} elseif ($view === 'logs') {
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name
        FROM activity_logs a
        JOIN users u ON a.user_id = u.user_id
        ORDER BY a.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $data['logs'] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">

    <aside class="sidebar">
        <div>
            <div class="sidebar-header">
                <img src="img/NEECO_banner.png" alt="Company Logo" class="app-logo">
                <div class="user-info">
                    <?= htmlspecialchars($_SESSION['name']) ?>
                </div>
            </div>
            <!-- <h1>Report & Activity Management System</h1> -->
            <ul>
                <li><a href="?view=main" class="<?= $view === 'main' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Main Dashboard</a></li>
                <li><a href="?view=folders" class="<?= $view === 'folders' ? 'active' : '' ?>"><i class="fas fa-folder"></i> Report Folders</a></li>
                <li><a href="?view=calendar" class="<?= $view === 'calendar' ? 'active' : '' ?>"><i class="fas fa-calendar"></i> Activity Calendar</a></li>
                <li><a href="?view=archives" class="<?= $view === 'archives' ? 'active' : '' ?>"><i class="fas fa-archive"></i> Archives</a></li>
                <li><a href="?view=logs" class="<?= $view === 'logs' ? 'active' : '' ?>"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
           
            <button id="logoutBtn" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </aside>

   
    <div id="logoutModal">
        <div class="modal-box">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to log out?</p>
            <div class="actions">
                <button class="cancel" id="cancelLogout"><i class="fas fa-times"></i> Cancel</button>
                <form method="POST" action="logout.php">
                    <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </div>
        </div>
    </div>


    <main class="main-content">
        
        <?php if ($view === 'main'): ?>
            <div class="main-top-bar">
                <h2>Main Dashboard</h2>
                <div class="search-center">
                    <form method="GET" action="dashboard.php" class="search-box">
                        <input type="hidden" name="view" value="main">
                        <i class="fas fa-search"></i>
                        <input type="text"
                               name="search"
                               placeholder="Search reports..."
                               value="<?= htmlspecialchars($search) ?>">
                    </form>
                </div>
                <div class="icons-right">
                    <i class="fas fa-users"></i>
                    <i class="fas fa-bell"></i>
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Uploaded This Month</h3>
                    <p><?= $data['total_this_month'] ?></p>
                </div>

            </div>
            <div class="upload-section">
                <h3>Quick Upload</h3>
                <form action="upload_report.php" method="POST" enctype="multipart/form-data">
                    <input type="text" name="title" placeholder="Report Title" required>
                    <input type="file" name="report" required>
                    <select name="month" required>
                        <?php for ($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <input type="number" name="year" value="<?= date('Y') ?>" required>
                    <button type="submit">Upload</button>
                </form>
            </div>
            <div class="recent-reports">
                <h3>Recently Added Reports</h3>
                <?php if (empty($data['recent_reports'])): ?>
                    <p>No recent reports.</p>
                <?php else: ?>
                    <?php foreach ($data['recent_reports'] as $r): ?>
                        <div class="report-card">
                            <div class="report-info">
                                <i class="fas fa-file-alt"></i>
                                <span><?= htmlspecialchars($r['report_title']) ?></span>
                            </div>
                            <div class="report-actions">
                                <a href="<?= htmlspecialchars($r['local_path']) ?>" target="_blank" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php elseif ($view === 'folders' || $view === 'archives'): ?>
            <h2><?= $view === 'folders' ? 'Report Folders' : 'Archives' ?></h2>
            <div class="reports">
                <?php if (empty($data['reports'])): ?>
                    <p class="empty-state">No reports found.</p>
                <?php else: ?>
                    <?php
                    $current = '';
                    foreach ($data['reports'] as $r):
                        $group = date("F Y", strtotime($r['report_year'] . '-' . $r['report_month'] . '-01'));
                        if ($group !== $current):
                            echo "<h3 class='group-title'>$group</h3>";
                            $current = $group;
                        endif;
                    ?>
                    <div class="report-card">
                        <div class="report-info">
                            <i class="fas fa-file-alt"></i>
                            <span><?= htmlspecialchars($r['report_title']) ?></span>
                        </div>
                        <div class="report-actions">
                            <a href="<?= htmlspecialchars($r['local_path']) ?>" target="_blank" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($view === 'folders' && isAdmin()): ?>
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
        <?php elseif ($view === 'calendar'): ?>
            <h2>Activity Calendar</h2>
            <div class="calendar">
                <div class="add-activity">
                    <h3>Add Activity</h3>
                    <form method="POST" action="add_activity.php">
                        <input type="text" name="title" placeholder="Title" required>
                        <textarea name="description" placeholder="Description"></textarea>
                        <input type="date" name="start_date" required>
                        <input type="date" name="deadline" required>
                        <button type="submit">Add</button>
                    </form>
                </div>
                <div class="calendar-view">
                    <h3>Upcoming Activities</h3>
                    <?php if (empty($data['activities'])): ?>
                        <p>No activities.</p>
                    <?php else: ?>
                        <?php foreach ($data['activities'] as $act): ?>
                            <div class="activity-item">
                                <h4><?= htmlspecialchars($act['title']) ?></h4>
                                <p><?= htmlspecialchars($act['description']) ?></p>
                                <p>Start: <?= $act['start_date'] ?> | Deadline: <?= $act['deadline'] ?> | Status: <?= $act['status'] ?></p>
                                <?php if ($act['status'] !== 'completed'): ?>
                                    <a href="update_activity.php?id=<?= $act['id'] ?>&status=completed">Mark Complete</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($view === 'logs'): ?>
            <h2>Activity Log</h2>
            <div class="logs">
                <?php if (empty($data['logs'])): ?>
                    <p>No activities found.</p>
                <?php else: ?>
                    <?php foreach ($data['logs'] as $log): ?>
                        <div class="log-entry">
                            <span class="log-user"><?= htmlspecialchars($log['full_name']) ?></span>
                            <span class="log-action"><?= htmlspecialchars($log['action']) ?></span>
                            <span class="log-desc"><?= htmlspecialchars($log['description']) ?></span>
                            <span class="log-time"><?= htmlspecialchars($log['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
const logoutBtn = document.getElementById('logoutBtn');
const logoutModal = document.getElementById('logoutModal');
const cancelBtn = document.getElementById('cancelLogout');

logoutBtn.addEventListener('click', () => {
    logoutModal.classList.add('active');
});

cancelBtn.addEventListener('click', () => {
    logoutModal.classList.remove('active');
});

logoutModal.addEventListener('click', (e) => {
    if(e.target === logoutModal){
        logoutModal.classList.remove('active');
    }
});
</script>

</body>
</html>
