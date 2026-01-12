<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'archives';
$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.status = 'archived' AND r.report_title LIKE ?
    ORDER BY r.report_year DESC, r.report_month DESC
");
$stmt->execute(["%$search%"]);
$reports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archives</title>

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
            <h2>Archives</h2>
            <div class="icons-right">
                <i class="fas fa-users"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>
        <div class="content-section">
            <div class="reports">
            <?php if (empty($reports)): ?>
                <p class="empty-state">No reports found.</p>
            <?php else: ?>
                <?php
                $current = '';
                foreach ($reports as $r):
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
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        </div>
    </main>
</div>

</body>
</html>