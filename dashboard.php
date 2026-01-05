<?php
require 'config/db.php';
require 'config/auth.php';

$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.report_title LIKE ?
    ORDER BY r.report_year DESC, r.report_month DESC
");
$stmt->execute(["%$search%"]);
$reports = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<!-- Header -->
<header class="dash-header">
    <h1>Dashboard</h1>
    <span class="user">
        👋 <?= htmlspecialchars($_SESSION['name']) ?>
    </span>
</header>

<!-- Main Container -->
<div class="dash-container">

    <!-- Search -->
    <form method="GET" action="dashboard.php" class="search-box">
        <i class="fas fa-search"></i>
        <input type="text"
               name="search"
               placeholder="Search reports..."
               value="<?= htmlspecialchars($search) ?>">
    </form>

    <!-- Reports -->
    <div class="reports">

        <?php if (empty($reports)): ?>
            <p class="empty-state">No reports found.</p>
        <?php else: ?>

            <?php
            $current = '';

            foreach ($reports as $r):
                $group = date(
                    "F Y",
                    strtotime($r['report_year'] . '-' . $r['report_month'] . '-01')
                );

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
                    <a href="<?= htmlspecialchars($r['local_path']) ?>" target="_blank"
                       title="View report">
                        <i class="fas fa-eye"></i>
                    </a>

                    <?php if (isAdmin()): ?>
                        <a href="delete_report.php?id=<?= $r['report_id'] ?>"
                           class="danger"
                           title="Delete report"
                           onclick="return confirm('Are you sure you want to delete this report?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>
