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
    SELECT r.*, u.full_name, r.uploaded_at
    FROM reports r
    JOIN users u ON r.uploaded_by = u.user_id
    WHERE r.status = 'active' AND r.report_title LIKE ?
    ORDER BY r.report_id DESC
    LIMIT 5
");
$stmt->execute(["%$search%"]);
$data['recent_reports'] = $stmt->fetchAll();

// Activities
$stmt = $pdo->prepare("SELECT a.*, u.full_name FROM activities a JOIN users u ON a.user_id = u.user_id ORDER BY start_date");
$stmt->execute([]);
$activities = $stmt->fetchAll();

// Upcoming activities (within 2 days)
$twoDaysFromNow = date('Y-m-d', strtotime('+2 days'));
$stmt = $pdo->prepare("SELECT a.*, u.full_name FROM activities a JOIN users u ON a.user_id = u.user_id WHERE a.start_date <= ? AND a.start_date >= ? AND a.status != 'completed' ORDER BY a.start_date");
$stmt->execute([$twoDaysFromNow, date('Y-m-d')]);
$upcomingActivities = $stmt->fetchAll();

$selected_date = null;
if (isset($_SESSION['selected_date'])) {
    $selected_date = $_SESSION['selected_date'];
    unset($_SESSION['selected_date']);
}

$calendarMonth = $_GET['month'] ?? date('m');
$calendarYear = $_GET['year'] ?? date('Y');

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
    if ($hasUndone || $hasInProgress) {
        $dateStatus[$date] = 'red';
    }
    // No dot if all activities are completed
}

$show_activity_modal = isset($_SESSION['activity_added']);
if ($show_activity_modal) {
    unset($_SESSION['activity_added']);
}

$calendarMonth = $_GET['month'] ?? date('m');
$calendarYear = $_GET['year'] ?? date('Y');
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
    <title>Dashboard - Report Management</title>

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
        <?php $page_title = 'Main Dashboard'; include 'header.php'; ?>

        <div class="calendar-section">
            <div class="calendar">
            <div class="calendar-view">
                            <div class="calendar-nav">
                                <button onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                <select id="yearSelect" onchange="changeYear()">
                                    <?php for($y = date('Y')-5; $y <= date('Y')+5; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == $calendarYear ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                                <button onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <h3><?= date('F Y', mktime(0,0,0,$calendarMonth,1,$calendarYear)) ?></h3>
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
                                            <td class="day <?= $day == date('j') && $calendarMonth == date('m') && $calendarYear == date('Y') ? 'today' : '' ?>" data-date="<?= $calendarYear ?>-<?= str_pad($calendarMonth, 2, '0', STR_PAD_LEFT) ?>-<?= str_pad($day, 2, '0', STR_PAD_LEFT) ?>" onclick="selectDate('<?= $calendarYear ?>-<?= str_pad($calendarMonth, 2, '0', STR_PAD_LEFT) ?>-<?= str_pad($day, 2, '0', STR_PAD_LEFT) ?>', this)">
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

        <div class="recent-reports">
            <div class="panel-header" style="display: flex; align-items: center;">
                <h3>Recently Added Reports</h3>
                <div class="search-box" style="margin: 0 auto;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search reports...">
                </div>
                <button id="uploadReportBtn" class="add-activity-btn"><i class="fas fa-upload"></i> Upload Report</button>
            </div>
            <div class="reports-list">
                <?php if (empty($data['recent_reports'])): ?>
                    <div class="no-reports">
                        <i class="fas fa-file-alt"></i>
                        <p>No recent reports found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($data['recent_reports'] as $r): ?>
                        <div class="report-card">
                            <div class="report-info">
                                <i class="fas fa-file-alt"></i>
                                <div>
                                    <span class="report-title"><?= htmlspecialchars($r['report_title']) ?></span>
                                    <span class="report-meta">Uploaded by <?= htmlspecialchars($r['full_name']) ?> on <?= date('M d, Y', strtotime($r['uploaded_at'])) ?> | Type: <?= strtoupper(pathinfo($r['local_path'], PATHINFO_EXTENSION)) ?></span>
                                </div>
                            </div>
                            <div class="report-actions">
                                <button class="view-btn" data-path="<?= htmlspecialchars($r['local_path']) ?>" data-title="<?= htmlspecialchars($r['report_title']) ?>" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
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

<div id="uploadReportModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Upload Report</h2>
            <button class="close-btn" id="closeUploadReportModal">&times;</button>
        </div>
        <div class="modal-content">
            <form action="upload_report.php" method="POST" enctype="multipart/form-data">
                <label for="title">Report Title</label>
                <input type="text" id="title" name="title" placeholder="Enter report title" required>
                <label for="report">Select File</label>
                <input type="file" id="report" name="report" required>
                <label for="month">Month</label>
                <select id="month" name="month" required>
                    <?php for ($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <label for="day">Day</label>
                <select id="day" name="day" required>
                    <?php for ($d=1; $d<=31; $d++): ?>
                        <option value="<?= $d ?>"><?= $d ?></option>
                    <?php endfor; ?>
                </select>
                <label for="year">Year</label>
                <input type="number" id="year" name="year" value="<?= date('Y') ?>" required>
                <button type="submit" class="btn-primary">Upload</button>
            </form>
        </div>
    </div>
</div>

<div id="previewModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Preview Document</h2>
            <button class="close-btn" id="closePreviewModal">&times;</button>
        </div>
        <div class="modal-content">
            <iframe id="documentPreview" src="" width="100%" height="500px" style="border: none;"></iframe>
            <div id="previewMessage" style="display: none; text-align: center; padding: 20px;">Preview not available for this file type. Please use the download button.</div>
            <div style="text-align: center; margin-top: 10px;">
                <a id="downloadLink" href="" download><button class="btn-primary">Download</button></a>
            </div>
        </div>
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
            const newStatus = act.status === 'completed' ? 'in-progress' : 'completed';
            const checkIcon = `<a href="update_activity.php?id=${act.id}&status=${newStatus}" class="check-btn ${act.status === 'completed' ? 'completed' : ''}"><i class="fas fa-check"></i></a>`;
            const div = document.createElement('div');
            div.className = 'activity-item';
            div.innerHTML = `<div class="activity-content"><span class="status-dot ${statusDot}"></span><h4>${act.title} by ${act.full_name}</h4><p>${act.description}</p></div>${checkIcon}`;
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

const uploadReportModal = document.getElementById('uploadReportModal');
const uploadReportBtn = document.getElementById('uploadReportBtn');
const closeUploadReportModal = document.getElementById('closeUploadReportModal');

uploadReportBtn.addEventListener('click', () => {
    uploadReportModal.classList.add('active');
});

closeUploadReportModal.addEventListener('click', () => {
    uploadReportModal.classList.remove('active');
});

uploadReportModal.addEventListener('click', (e) => {
    if(e.target === uploadReportModal){
        uploadReportModal.classList.remove('active');
    }
});

const previewModal = document.getElementById('previewModal');
const closePreviewModal = document.getElementById('closePreviewModal');
const documentPreview = document.getElementById('documentPreview');
const downloadLink = document.getElementById('downloadLink');

document.addEventListener('click', (e) => {
    if (e.target.closest('.view-btn')) {
        const btn = e.target.closest('.view-btn');
        const path = btn.dataset.path;
        const title = btn.dataset.title;
        const ext = path.split('.').pop().toLowerCase();
        if (['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'].includes(ext)) {
            documentPreview.src = path;
            documentPreview.style.display = 'block';
            previewMessage.style.display = 'none';
            downloadLink.href = path;
            downloadLink.download = title + '.' + ext;
            previewModal.classList.add('active');
        } else if (ext === 'docx') {
            // Convert DOCX to PDF for preview
            fetch(`convert_to_pdf.php?path=${encodeURIComponent(path)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.pdf_path) {
                        documentPreview.src = data.pdf_path;
                        documentPreview.style.display = 'block';
                        previewMessage.style.display = 'none';
                    } else {
                        documentPreview.style.display = 'none';
                        previewMessage.style.display = 'block';
                    }
                    downloadLink.href = path;
                    downloadLink.download = title + '.' + ext;
                    previewModal.classList.add('active');
                })
                .catch(() => {
                    documentPreview.style.display = 'none';
                    previewMessage.style.display = 'block';
                    downloadLink.href = path;
                    downloadLink.download = title + '.' + ext;
                    previewModal.classList.add('active');
                });
        } else {
            documentPreview.style.display = 'none';
            previewMessage.style.display = 'block';
            downloadLink.href = path;
            downloadLink.download = title + '.' + ext;
            previewModal.classList.add('active');
        }
    }
});

closePreviewModal.addEventListener('click', () => {
    previewModal.classList.remove('active');
    documentPreview.src = '';
    documentPreview.style.display = 'block';
    previewMessage.style.display = 'none';
});

previewModal.addEventListener('click', (e) => {
    if(e.target === previewModal){
        previewModal.classList.remove('active');
        documentPreview.src = '';
        documentPreview.style.display = 'block';
        previewMessage.style.display = 'none';
    }
});

document.querySelector('.calendar-section').addEventListener('click', function(e) {
    if (!e.target.closest('.day')) {
        document.querySelectorAll('.day').forEach(td => td.classList.remove('selected'));
        selectedDate = null;
        document.getElementById('activity-date').textContent = 'Select a date';
        document.getElementById('activity-list').innerHTML = '<p>Click on a date to view or add activities.</p>';
    }
});

function changeMonth(delta) {
    let month = parseInt('<?= $calendarMonth ?>');
    let year = parseInt('<?= $calendarYear ?>');
    month += delta;
    if (month < 1) { month = 12; year--; }
    if (month > 12) { month = 1; year++; }
    const url = new URL(window.location);
    url.searchParams.set('month', month.toString().padStart(2,'0'));
    url.searchParams.set('year', year);
    window.location.href = url.toString();
}

function changeYear() {
    const year = document.getElementById('yearSelect').value;
    const url = new URL(window.location);
    url.searchParams.set('year', year);
    window.location.href = url.toString();
}

const searchInput = document.getElementById('searchInput');
const reportsList = document.querySelector('.reports-list');

searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    if (query === '') {
        // Reload recent reports
        location.reload();
    } else {
        fetch(`search_reports.php?search=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                reportsList.innerHTML = '';
                if (data.reports.length === 0) {
                    reportsList.innerHTML = '<div class="no-reports"><i class="fas fa-file-alt"></i><p>No reports found.</p></div>';
                } else {
                    data.reports.forEach(r => {
                        const ext = r.local_path.split('.').pop().toLowerCase();
                        const card = `
                            <div class="report-card">
                                <div class="report-info">
                                    <i class="fas fa-file-alt"></i>
                                    <div>
                                        <span class="report-title">${r.report_title}</span>
                                        <span class="report-meta">Uploaded by ${r.full_name} on ${new Date(r.uploaded_at).toLocaleDateString()} | Type: ${ext.toUpperCase()}</span>
                                    </div>
                                </div>
                                <div class="report-actions">
                                    <button class="view-btn" data-path="${r.local_path}" data-title="${r.report_title}"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                        `;
                        reportsList.innerHTML += card;
                    });
                }
            });
    }
});

<?php if ($selected_date): ?>
selectDate('<?= $selected_date ?>', document.querySelector('[data-date="<?= $selected_date ?>"]'));
<?php endif; ?>
</script>

</body>
</html>
