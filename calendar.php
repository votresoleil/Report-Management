<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'calendar';

$stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY start_date");
$stmt->execute([$_SESSION['user_id']]);
$activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Calendar</title>

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
            <h2>Activity Calendar</h2>
            <div class="icons-right">
                <i class="fas fa-users"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>
        <div class="content-section">
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
                <?php if (empty($activities)): ?>
                    <p>No activities.</p>
                <?php else: ?>
                    <?php foreach ($activities as $act): ?>
                        <div class="activity-item">
                            <div class="activity-content">
                                <span class="status-dot <?= $act['status'] === 'completed' ? 'green' : ($act['status'] === 'in-progress' ? 'yellow' : 'red') ?>"></span>
                                <h4><?= htmlspecialchars($act['title']) ?></h4>
                                <p><?= htmlspecialchars($act['description']) ?></p>
                                <p>Start: <?= $act['start_date'] ?> | Deadline: <?= $act['deadline'] ?></p>
                            </div>
                            <?php if ($act['status'] !== 'completed'): ?>
                                <a href="update_activity.php?id=<?= $act['id'] ?>&status=completed" class="check-icon"><i class="fas fa-check-circle"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </main>
</div>

</body>
</html>