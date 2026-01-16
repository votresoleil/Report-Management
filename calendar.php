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
    <title>Activity Calendar - Report Management</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="icon" href="img/NEECO_banner.png">
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
                <div class="activity-summary">
                    <div class="stats">
                        <div class="stat-card">
                            <i class="fas fa-tasks total-icon"></i>
                            <h3>Total Activities</h3>
                            <p><?= $totalActivities ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-check-circle completed-icon"></i>
                            <h3>Completed</h3>
                            <p><?= $completed ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-clock in-progress-icon"></i>
                            <h3>In Progress</h3>
                            <p><?= $inProgress ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-exclamation-triangle pending-icon"></i>
                            <h3>Pending</h3>
                            <p><?= $pending ?></p>
                        </div>
                    </div>
                </div>
                <div class="upcoming-deadlines">
                    <h3>Upcoming Deadlines</h3>
                    <div class="deadline-list">
                        <?php if (empty($upcomingDeadlines)): ?>
                            <p>No upcoming deadlines.</p>
                        <?php else: ?>
                            <?php foreach ($upcomingDeadlines as $act): ?>
                                <div class="deadline-item">
                                    <div class="deadline-content">
                                        <h4><?= htmlspecialchars($act['title']) ?></h4>
                                        <p><?= htmlspecialchars($act['description']) ?> - Due: <?= $act['start_date'] ?></p>
                                    </div>
                                    <i class="fas fa-eye view-activity" data-id="<?= $act['id'] ?>"></i>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                                    <button class="check-btn <?= $act['status'] === 'completed' ? 'completed' : '' ?>" data-id="<?= $act['id'] ?>" data-status="<?= $act['status'] === 'completed' ? 'in-progress' : 'completed' ?>" data-title="<?= htmlspecialchars($act['title']) ?>"><i class="fas fa-check"></i></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

<div id="confirmStatusModal">
    <div class="modal-box">
        <h2>Confirm Action</h2>
        <p id="confirmMessage">Mark as Done?</p>
        <div style="text-align: center; margin-top: 20px;">
            <button id="confirmStatus" class="btn-primary" style="width: 100px;">Mark as Done</button>
            <button id="cancelStatus" class="btn-primary" style="width: 100px; margin-left: 10px; background: #ccc; color: #333;">Cancel</button>
        </div>
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

const confirmStatusModal = document.getElementById('confirmStatusModal');
const cancelStatus = document.getElementById('cancelStatus');
const confirmStatus = document.getElementById('confirmStatus');
const confirmMessage = document.getElementById('confirmMessage');
let currentUpdateUrl = '';

document.addEventListener('click', (e) => {
    if (e.target.closest('.check-btn')) {
        const btn = e.target.closest('.check-btn');
        const id = btn.dataset.id;
        const status = btn.dataset.status;
        const title = btn.dataset.title;
        const action = status === 'completed' ? 'Mark as Done' : 'Mark as In Progress';
        confirmMessage.textContent = `Are you sure you want to ${action.toLowerCase()} "${title}"?`;
        document.getElementById('confirmStatus').textContent = action;
        currentUpdateUrl = `update_activity.php?id=${id}&status=${status}`;
        confirmStatusModal.classList.add('active');
    }
});

cancelStatus.addEventListener('click', () => {
    confirmStatusModal.classList.remove('active');
});

confirmStatus.addEventListener('click', () => {
    window.location.href = currentUpdateUrl;
});

confirmStatusModal.addEventListener('click', (e) => {
    if (e.target === confirmStatusModal) {
        confirmStatusModal.classList.remove('active');
    }
});
</script>

</body>
</html>