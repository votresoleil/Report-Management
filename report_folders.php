<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'folders';
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$search = $_GET['search'] ?? '';
$months = [];

// Show folder overview - years with reports
$years = [];
$stmt = $pdo->query("SELECT DISTINCT report_year FROM reports WHERE status = 'active' ORDER BY report_year DESC");
$yearRows = $stmt->fetchAll();
foreach ($yearRows as $row) {
    $y = $row['report_year'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE status = 'active' AND report_year = ?");
    $stmt->execute([$y]);
    $years[$y] = $stmt->fetch()['count'];
}

if ($year && !$month) {
    // Show months for specific year
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE status = 'active' AND report_year = ? AND report_month = ?");
        $stmt->execute([$year, $m]);
        $count = $stmt->fetch()['count'];
        if ($count > 0) {
            $months[$m] = $count;
        }
    }
}

if ($year && $month) {
    // Show reports for specific month and year
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name
        FROM reports r
        JOIN users u ON r.uploaded_by = u.user_id
        WHERE r.status = 'active' AND r.report_year = ? AND r.report_month = ? AND r.report_title LIKE ?
        ORDER BY r.report_id DESC
    ");
    $stmt->execute([$year, $month, "%$search%"]);
    $reports = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Folders</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="main-layout">

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="header-section">
            <h2>Report Folders</h2>
            <div class="icons-right">
                <i class="fas fa-users"></i>
                <i class="fas fa-bell"></i>
                <i class="fas fa-user"></i>
            </div>
        </div>
        <div class="content-section">
            <div class="folders-container" id="yearsContainer">
            <?php foreach ($years as $y => $count): ?>
                <div class="folder-card" data-year="<?= $y ?>">
                    <i class="fas fa-folder"></i>
                    <h3><?= $y ?></h3>
                    <p><?= $count ?> reports</p>
                </div>
            <?php endforeach; ?>
            <?php if (empty($years)): ?>
                <div class="no-reports" style="grid-column: 1 / -1;">
                    <i class="fas fa-file-alt"></i>
                    <p>No reports found.</p>
                </div>
            <?php endif; ?>
            </div>
            <div id="monthsPanel" style="display: none;">
                <h3 id="monthsTitle"></h3>
                <div class="folders-container" id="monthsContainer">
                </div>
            </div>
        </div>

        <?php if ($year && $month): ?>
        <div id="monthModal" class="active">
            <div class="modal-box large">
                <div class="modal-header">
                    <h2>Reports for <?= $month ? date('F Y', mktime(0,0,0,$month,1,$year)) : '' ?></h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search reports..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <button class="close-btn" id="closeMonthModal" onclick="window.location.href='?year=<?= $year ?>'">&times;</button>
                </div>
                <div class="modal-content">
                    <div class="reports" id="reportsList">
                        <?php if (empty($reports)): ?>
                            <div class="no-reports">
                                <i class="fas fa-file-alt"></i>
                                <p>No reports found for this month.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <div class="report-card">
                                    <div class="report-info">
                                        <i class="fas fa-file-alt"></i>
                                        <span><?= htmlspecialchars($r['report_title']) ?></span>
                                    </div>
                                    <div class="report-actions">
                                        <button class="view-btn" data-path="<?= htmlspecialchars($r['local_path']) ?>" data-title="<?= htmlspecialchars($r['report_title']) ?>" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (isAdmin()): ?>
                                            <a href="#" class="archive-btn" data-id="<?= $r['report_id'] ?>" title="Archive" onclick="return false;">
                                                <i class="fas fa-file-archive"></i>
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
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<div id="archiveModal">
    <div class="modal-box">
        <h2>Confirm Archive</h2>
        <p>Are you sure you want to archive this report?</p>
        <div style="text-align: center; margin-top: 20px;">
            <button id="confirmArchive" class="btn-primary" style="width: 100px;">Archive</button>
            <button id="cancelArchive" class="btn-primary" style="width: 100px; margin-left: 10px; background: #ccc; color: #333;">Cancel</button>
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
const archiveModal = document.getElementById('archiveModal');
const confirmArchive = document.getElementById('confirmArchive');
const cancelArchive = document.getElementById('cancelArchive');

let archiveId = null;


const monthModal = document.getElementById('monthModal');
if (monthModal) {
    const closeMonthModal = document.getElementById('closeMonthModal');
    closeMonthModal.addEventListener('click', () => {
        window.location.href = 'report_folders.php';
    });

    monthModal.addEventListener('click', (e) => {
        if(e.target === monthModal){
            window.location.href = '?year=<?= $year ?>';
        }
    });
}

document.addEventListener('click', (e) => {
    if (e.target.closest('.archive-btn')) {
        const btn = e.target.closest('.archive-btn');
        archiveId = btn.dataset.id;
        archiveModal.classList.add('active');
    }
});

confirmArchive.addEventListener('click', () => {
    if (archiveId) {
        window.location.href = `archive_report.php?id=${archiveId}`;
    }
});

cancelArchive.addEventListener('click', () => {
    archiveModal.classList.remove('active');
    archiveId = null;
});

archiveModal.addEventListener('click', (e) => {
    if (e.target === archiveModal) {
        archiveModal.classList.remove('active');
        archiveId = null;
    }
});

const searchInput = document.getElementById('searchInput');
const reportsList = document.getElementById('reportsList');
const isAdmin = <?= isAdmin() ? 'true' : 'false' ?>;

if (searchInput && reportsList) {
    searchInput.addEventListener('input', function() {
        const query = this.value;
        const year = <?= json_encode($year) ?>;
        const month = <?= json_encode($month) ?>;
        fetch(`search_reports.php?search=${encodeURIComponent(query)}&year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                reportsList.innerHTML = '';
                if (data.length === 0) {
                    reportsList.innerHTML = '<div class="no-reports"><i class="fas fa-file-alt"></i><p>No reports found for this month.</p></div>';
                } else {
                    data.forEach(r => {
                        let actions = `
                            <button class="view-btn" data-path="${r.local_path}" data-title="${r.report_title}" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                        if (isAdmin) {
                            actions += `
                                <button class="archive-btn" data-id="${r.report_id}" title="Archive">
                                    <i class="fas fa-archive"></i>
                                </button>
                                <a href="delete_report.php?id=${r.report_id}" class="danger" title="Delete" onclick="return confirm('Delete?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            `;
                        }
                        const card = `
                            <div class="report-card">
                                <div class="report-info">
                                    <i class="fas fa-file-alt"></i>
                                    <span>${r.report_title}</span>
                                </div>
                                <div class="report-actions">
                                    ${actions}
                                </div>
                            </div>
                        `;
                        reportsList.innerHTML += card;
                    });
                }
            });
    });
}

let selectedYear = null;

document.querySelectorAll('.folder-card[data-year]').forEach(card => {
    card.addEventListener('click', function() {
        const year = this.dataset.year;
        if (selectedYear === year) {
            // Close
            selectedYear = null;
            document.getElementById('monthsPanel').style.display = 'none';
            document.querySelectorAll('.folder-card[data-year]').forEach(c => c.classList.remove('selected'));
        } else {
            // Open
            selectedYear = year;
            document.querySelectorAll('.folder-card[data-year]').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('monthsTitle').textContent = `Months for ${year}`;
            fetchMonths(year);
        }
    });
});

function fetchMonths(year) {
    // Fetch months for the year
    fetch(`get_months.php?year=${year}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('monthsContainer');
            container.innerHTML = '';
            for (const [m, count] of Object.entries(data)) {
                const card = document.createElement('div');
                card.className = 'folder-card';
                card.innerHTML = `
                    <a href="?year=${year}&month=${m}">
                        <i class="fas fa-folder"></i>
                        <h3>${new Date(year, m-1, 1).toLocaleString('default', { month: 'long' })}</h3>
                        <p>${count} reports</p>
                    </a>
                `;
                container.appendChild(card);
            }
            document.getElementById('monthsPanel').style.display = 'block';
        });
}

// Check if year is in URL and open panel
const urlParams = new URLSearchParams(window.location.search);
const yearParam = urlParams.get('year');
if (yearParam) {
    selectedYear = yearParam;
    const yearCard = document.querySelector(`.folder-card[data-year="${yearParam}"]`);
    if (yearCard) {
        yearCard.classList.add('selected');
        fetchMonths(yearParam);
    }
}

const previewModal = document.getElementById('previewModal');
const closePreviewModal = document.getElementById('closePreviewModal');
const documentPreview = document.getElementById('documentPreview');
const downloadLink = document.getElementById('downloadLink');
const previewMessage = document.getElementById('previewMessage');

document.addEventListener('click', (e) => {
    if (e.target.closest('.view-btn')) {
        const btn = e.target.closest('.view-btn');
        const path = btn.dataset.path;
        const title = btn.dataset.title;
        const ext = path.split('.').pop().toLowerCase();
        if (['pdf', 'jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
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
</script>

</body>
</html>