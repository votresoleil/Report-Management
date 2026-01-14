<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'calendar';

$stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY start_date");
$stmt->execute([$_SESSION['user_id']]);
$activities = $stmt->fetchAll();

$show_activity_modal = isset($_SESSION['activity_added']);
if ($show_activity_modal) {
    unset($_SESSION['activity_added']);
}

// Activity stats
$totalActivities = count($activities);
$completed = count(array_filter($activities, fn($a) => $a['status'] === 'completed'));
$inProgress = count(array_filter($activities, fn($a) => $a['status'] === 'in-progress'));
$pending = count(array_filter($activities, fn($a) => $a['status'] === 'pending'));

// Upcoming deadlines (next 5)
$upcoming = array_filter($activities, fn($a) => strtotime($a['start_date']) >= time());
usort($upcoming, fn($a, $b) => strtotime($a['start_date']) - strtotime($b['start_date']));
$upcomingDeadlines = array_slice($upcoming, 0, 5);
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
        <div class="calendar-section">
            <div class="calendar">
                <div class="left-column">
                    <div class="activity-summary">
                        <h3>Activity Summary</h3>
                        <div class="stats">
                            <div class="stat">
                                <h4>Total Activities</h4>
                                <p><?= $totalActivities ?></p>
                            </div>
                            <div class="stat">
                                <h4>Completed</h4>
                                <p><?= $completed ?></p>
                            </div>
                            <div class="stat">
                                <h4>In Progress</h4>
                                <p><?= $inProgress ?></p>
                            </div>
                            <div class="stat">
                                <h4>Pending</h4>
                                <p><?= $pending ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="activity-details">
                        <div class="activity-header">
                            <h3>Activity Details</h3>
                            <button id="addActivityBtn" class="add-activity-btn"><i class="fas fa-plus"></i> Add Activity</button>
                        </div>
                        <div class="activity-list">
                            <?php if (empty($activities)): ?>
                                <p>No activities found.</p>
                            <?php else: ?>
                                <?php foreach ($activities as $act): ?>
                                    <div class="activity-item">
                                        <span class="status-dot <?= $act['status'] === 'completed' ? 'green' : ($act['status'] === 'in-progress' ? 'yellow' : 'red') ?>"></span>
                                        <div class="activity-info">
                                            <h4><?= htmlspecialchars($act['title']) ?></h4>
                                            <p>Date: <?= $act['start_date'] ?></p>
                                            <p><?= htmlspecialchars($act['description']) ?></p>
                                        </div>
                                        <a href="update_activity.php?id=<?= $act['id'] ?>&status=<?= $act['status'] === 'completed' ? 'in-progress' : 'completed' ?>" class="check-btn <?= $act['status'] === 'completed' ? 'completed' : '' ?>"><i class="fas fa-check"></i></a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="right-column">
                    <div class="upcoming-deadlines">
                        <h3>Upcoming Deadlines</h3>
                        <div class="deadline-list">
                            <?php if (empty($upcomingDeadlines)): ?>
                                <p>No upcoming deadlines.</p>
                            <?php else: ?>
                                <?php foreach ($upcomingDeadlines as $act): ?>
                                    <div class="deadline-item">
                                        <span class="date"><?= $act['start_date'] ?></span>
                                        <h4><?= htmlspecialchars($act['title']) ?></h4>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        </div>
    </main>
</div>

<div id="addActivityModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Add Activity</h2>
            <button class="close-btn" id="closeAddActivityModal">&times;</button>
        </div>
        <div class="modal-content">
            <form method="POST" action="add_activity.php">
                <input type="text" name="title" placeholder="Name of Activity" required>
                <textarea name="description" placeholder="Description"></textarea>
                <input type="date" name="start_date" required>
                <button type="submit" class="btn-primary">Add</button>
            </form>
        </div>
    </div>
</div>

<div id="activitySuccessModal" class="<?= $show_activity_modal ? 'active' : '' ?>">
    <div class="modal-box">
        <h2>Success!</h2>
        <p>Activity added successfully to the calendar!</p>
        <button class="btn-primary" id="closeActivityModal">OK</button>
    </div>
</div>

<div id="selectDateModal">
    <div class="modal-box">
        <h2>Notice</h2>
        <p>Please select a date first.</p>
        <button class="btn-primary" id="closeSelectDateModal">OK</button>
    </div>
</div>

<script>
const addActivityModal = document.getElementById('addActivityModal');
const addActivityBtn = document.getElementById('addActivityBtn');
const closeAddActivityModal = document.getElementById('closeAddActivityModal');
const activitySuccessModal = document.getElementById('activitySuccessModal');
const closeActivityModal = document.getElementById('closeActivityModal');

addActivityBtn.addEventListener('click', () => {
    addActivityModal.classList.add('active');
});

closeAddActivityModal.addEventListener('click', () => {
    addActivityModal.classList.remove('active');
});

addActivityModal.addEventListener('click', (e) => {
    if(e.target === addActivityModal){
        addActivityModal.classList.remove('active');
    }
});

closeActivityModal.addEventListener('click', () => {
    activitySuccessModal.classList.remove('active');
});

activitySuccessModal.addEventListener('click', (e) => {
    if(e.target === activitySuccessModal){
        activitySuccessModal.classList.remove('active');
    }
});
</script>

</body>
</html>