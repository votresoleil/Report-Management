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

// Activities
$stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY start_date");
$stmt->execute([$_SESSION['user_id']]);
$activities = $stmt->fetchAll();

$selected_date = null;
if (isset($_SESSION['selected_date'])) {
    $selected_date = $_SESSION['selected_date'];
    unset($_SESSION['selected_date']);
}

// Group activities by date
$activitiesByDate = [];
$dateStatus = [];
foreach ($activities as $act) {
    $date = $act['start_date'];
    if (!isset($activitiesByDate[$date])) {
        $activitiesByDate[$date] = [];
    }
    $activitiesByDate[$date][] = $act;
}

// Determine status for each date
foreach ($activitiesByDate as $date => $acts) {
    $hasCompleted = false;
    $hasInProgress = false;
    $hasUndone = false;
    foreach ($acts as $act) {
        if ($act['status'] === 'completed') {
            $hasCompleted = true;
        } elseif ($act['status'] === 'in-progress') {
            $hasInProgress = true;
        } else {
            $hasUndone = true;
        }
    }
    if ($hasCompleted && !$hasInProgress && !$hasUndone) {
        $dateStatus[$date] = 'green';
    } elseif ($hasInProgress) {
        $dateStatus[$date] = 'yellow';
    } else {
        $dateStatus[$date] = 'red';
    }
}

$show_activity_modal = isset($_SESSION['activity_added']);
if ($show_activity_modal) {
    unset($_SESSION['activity_added']);
}

// Calendar data
$calendarMonth = date('m');
$calendarYear = date('Y');
$firstDay = mktime(0,0,0,$calendarMonth,1,$calendarYear);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay);
$calendar = [];
for ($i = 0; $i < $startDay; $i++) {
    $calendar[] = '';
}
for ($day = 1; $day <= $daysInMonth; $day++) {
    $calendar[] = $day;
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

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-section">
            <h2>Main Dashboard</h2>
            <div class="icons-right">
                <i class="fas fa-users"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>

        <div class="calendar-section">
            <div class="calendar">
            <div class="calendar-view">
                <h3><?= date('F Y') ?></h3>
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Sun</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th>Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $weeks = array_chunk($calendar, 7);
                        foreach ($weeks as $week):
                        ?>
                        <tr>
                            <?php foreach ($week as $day): ?>
                            <?php if ($day): ?>
                                <td class="day <?= $day == date('j') ? 'today' : '' ?>" data-date="<?= $calendarYear ?>-<?= str_pad($calendarMonth, 2, '0', STR_PAD_LEFT) ?>-<?= str_pad($day, 2, '0', STR_PAD_LEFT) ?>" onclick="selectDate('<?= $calendarYear ?>-<?= str_pad($calendarMonth, 2, '0', STR_PAD_LEFT) ?>-<?= str_pad($day, 2, '0', STR_PAD_LEFT) ?>', this)">
                                    <?= $day ?>
                                    <?php if (isset($dateStatus[$calendarYear . '-' . str_pad($calendarMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT)])): ?>
                                        <span class="status-dot <?= $dateStatus[$calendarYear . '-' . str_pad($calendarMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT)] ?>"></span>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td></td>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="upcoming-activities">
                <div class="activity-header">
                    <h3 id="activity-date">Select a date</h3>
                    <button id="addActivityBtn" class="add-activity-btn"><i class="fas fa-plus"></i> Add Activity</button>
                </div>
                <div id="activity-list">
                    <p>Click on a date to view or add activities.</p>
                </div>
            </div>
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

<div id="addActivityModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Add Activity</h2>
            <button class="close-btn" id="closeAddActivityModal">&times;</button>
        </div>
        <div class="modal-content">
            <form method="POST" action="add_activity.php">
                <input type="hidden" name="start_date" id="start_date">
                <input type="text" name="title" placeholder="Name of Activity" required>
                <textarea name="description" placeholder="Description"></textarea>
                <input type="date" name="deadline" required>
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
const activities = <?= json_encode($activities) ?>;
let selectedDate = null;

function selectDate(date, element) {
    // Remove selected class from all days
    document.querySelectorAll('.day').forEach(td => td.classList.remove('selected'));
    // Add selected class to clicked day
    element.classList.add('selected');

    selectedDate = date;
    document.getElementById('activity-date').textContent = 'Activities for ' + new Date(date).toDateString();
    const list = document.getElementById('activity-list');
    list.innerHTML = '';
    const dayActivities = activities.filter(a => a.start_date === date);
    if (dayActivities.length === 0) {
        list.innerHTML = '<p>No activities for this date.</p>';
    } else {
        dayActivities.forEach(act => {
            const statusDot = act.status === 'completed' ? 'green' : act.status === 'in-progress' ? 'yellow' : 'red';
            const checkIcon = act.status !== 'completed' ? `<a href="update_activity.php?id=${act.id}&status=completed" class="check-icon"><i class="fas fa-check-circle"></i></a>` : '';
            const div = document.createElement('div');
            div.className = 'activity-item';
            div.innerHTML = `<div class="activity-content"><span class="status-dot ${statusDot}"></span><h4>${act.title}</h4><p>${act.description}</p><p>Deadline: ${act.deadline}</p></div>${checkIcon}`;
            list.appendChild(div);
        });
    }
}

const uploadSuccessModal = document.getElementById('uploadSuccessModal');
const closeUploadModal = document.getElementById('closeUploadModal');
const addActivityModal = document.getElementById('addActivityModal');
const addActivityBtn = document.getElementById('addActivityBtn');
const closeAddActivityModal = document.getElementById('closeAddActivityModal');
const activitySuccessModal = document.getElementById('activitySuccessModal');
const closeActivityModal = document.getElementById('closeActivityModal');
const selectDateModal = document.getElementById('selectDateModal');
const closeSelectDateModal = document.getElementById('closeSelectDateModal');

closeUploadModal.addEventListener('click', () => {
    uploadSuccessModal.classList.remove('active');
});

uploadSuccessModal.addEventListener('click', (e) => {
    if(e.target === uploadSuccessModal){
        uploadSuccessModal.classList.remove('active');
    }
});

addActivityBtn.addEventListener('click', () => {
    if (selectedDate) {
        document.getElementById('start_date').value = selectedDate;
        addActivityModal.classList.add('active');
    } else {
        selectDateModal.classList.add('active');
    }
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

closeSelectDateModal.addEventListener('click', () => {
    selectDateModal.classList.remove('active');
});

selectDateModal.addEventListener('click', (e) => {
    if(e.target === selectDateModal){
        selectDateModal.classList.remove('active');
    }
});

<?php if ($selected_date): ?>
selectDate('<?= $selected_date ?>', document.querySelector('[data-date="<?= $selected_date ?>"]'));
<?php endif; ?>
</script>

</body>
</html>
