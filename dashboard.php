<?php
require 'config/db.php';
require 'config/auth.php';

$search = $_GET['search'] ?? '';

$data = [];

$currentMonth = date('m');
$currentYear = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reports WHERE report_month = ? AND report_year = ? AND status = 'active'");
$stmt->execute([$currentMonth, $currentYear]);
$data['total_this_month'] = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.status = 'active' AND r.report_title LIKE ?
    ORDER BY r.report_id DESC
    LIMIT 5
");
$stmt->execute(["%$search%"]);
$data['recent_reports'] = $stmt->fetchAll();
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

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="main-top-bar">
            <h2>Main Dashboard</h2>
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
            <div class="panel-header">
                <h3>Recently Added Reports</h3>
                <form method="GET" action="dashboard.php" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           name="search"
                           placeholder="Search reports..."
                           value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
            <div class="reports-list">
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
        </div>
    </main>
</div>

<div id="uploadSuccessModal" class="<?= isset($_GET['uploaded']) ? 'active' : '' ?>">
    <div class="modal-box">
        <h2>Success!</h2>
        <p>File uploaded successfully!</p>
        <button class="btn-primary" id="closeUploadModal">OK</button>
    </div>
</div>

<script>
const uploadSuccessModal = document.getElementById('uploadSuccessModal');
const closeUploadModal = document.getElementById('closeUploadModal');

closeUploadModal.addEventListener('click', () => {
    uploadSuccessModal.classList.remove('active');
});

uploadSuccessModal.addEventListener('click', (e) => {
    if(e.target === uploadSuccessModal){
        uploadSuccessModal.classList.remove('active');
    }
});
</script>

</body>
</html>
