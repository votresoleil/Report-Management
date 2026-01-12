<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'logs';

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name
    FROM activity_logs a
    JOIN users u ON a.user_id = u.user_id
    ORDER BY a.created_at DESC
    LIMIT 50
");
$stmt->execute();
$logs = $stmt->fetchAll();
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
        <h2>Activity Log</h2>
        <div class="logs">
            <?php if (empty($logs)): ?>
                <p>No activities found.</p>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-entry">
                        <span class="log-user"><?= htmlspecialchars($log['full_name']) ?></span>
                        <span class="log-action"><?= htmlspecialchars($log['action']) ?></span>
                        <span class="log-desc"><?= htmlspecialchars($log['description']) ?></span>
                        <span class="log-time"><?= htmlspecialchars($log['created_at']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>