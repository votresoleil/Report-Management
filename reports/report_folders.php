<?php
require '../config/db.php';
require '../config/auth.php';

$active_view = 'folders';
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$folder = $_GET['folder'] ?? null;
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$months = [];

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
    // Get folders for this year/month
    $basePath = "uploads/$year/$month/";
    $folders = [];
    if (is_dir($basePath)) {
        $items = scandir($basePath);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_dir($basePath . $item)) {
                $folders[] = $item;
            }
        }
    }
    
    // Build folder path for breadcrumb
    $folderPath = '';
    if ($folder) {
        $folderPath = $folder;
    }
    
    // Get reports
    $reportDir = $basePath . $folderPath;
    $reports = [];
    if (is_dir($reportDir)) {
        $files = scandir($reportDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && !is_dir($reportDir . '/' . $file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $reports[] = [
                    'file_name' => $file,
                    'report_title' => preg_replace('/^\d+_\d+_/', '', $file), // Remove timestamp prefix
                    'local_path' => $reportDir . '/' . $file,
                    'file_type' => $ext
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Folders - Report Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/NEECO_banner.png">
</head>
<body>

<div class="main-layout">

    <?php include '../dashboard/sidebar.php'; ?>

    <main class="main-content">
        <?php $page_title = 'Report Folders'; include '../dashboard/header.php'; ?>
        <div class="panel-header">
            <h3>Upload Options</h3>
            <div style="display: flex; gap: 10px;">
                <button id="createFolderBtn" class="add-activity-btn"><i class="fas fa-folder-plus"></i> Create New Folder</button>
                <button id="uploadReportBtn" class="add-activity-btn"><i class="fas fa-upload"></i> Upload Reports</button>
            </div>
        </div>
        <div class="content-section">
            <div class="folders-container" id="yearsContainer">
            <?php foreach ($years as $y => $count): ?>
                <div class="folder-card" onclick="showMonths(<?= $y ?>); return false;" data-year="<?= $y ?>">
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
                    <h2>
                        <?php 
                        $monthName = date('F Y', mktime(0,0,0,$month,1,$year));
                        if ($folder) {
                            echo $monthName . ' / ' . str_replace('/', ' / ', $folder);
                        } else {
                            echo $monthName;
                        }
                        ?>
                    </h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search reports..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <button class="close-btn" id="closeMonthModal" onclick="window.location.href='?year=<?= $year ?>'">&times;</button>
                </div>
                <div class="modal-content">
                    <!-- Subfolders -->
                    <?php if (!empty($folders)): ?>
                    <div class="subfolders-section">
                        <h4 style="margin-bottom: 10px;">Folders</h4>
                        <div class="folders-container" id="subfoldersContainer">
                            <?php foreach ($folders as $f): ?>
                            <div class="folder-card subfolder" data-year="<?= $year ?>" data-month="<?= $month ?>" data-folder="<?= htmlspecialchars($f) ?>">
                                <i class="fas fa-folder"></i>
                                <h3><?= htmlspecialchars($f) ?></h3>
                                <p>Click to view</p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reports -->
                    <div class="reports-section" style="<?= !empty($folders) ? 'margin-top: 20px;' : '' ?>">
                        <h4 style="margin-bottom: 10px;">Reports</h4>
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
                                                <button class="archive-btn" data-path="<?= htmlspecialchars($r['local_path']) ?>" title="Archive">
                                                    <i class="fas fa-file-archive"></i>
                                                </button>
                                                <button class="delete-btn danger" data-path="<?= htmlspecialchars($r['local_path']) ?>" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
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

<div id="deleteModal">
    <div class="modal-box">
        <h2>Confirm Delete</h2>
        <p>Are you sure you want to delete this report? This action cannot be undone.</p>
        <div style="text-align: center; margin-top: 20px;">
            <button id="confirmDelete" class="btn-primary" style="width: 100px; background: #c0392b;">Delete</button>
            <button id="cancelDelete" class="btn-primary" style="width: 100px; margin-left: 10px; background: #ccc; color: #333;">Cancel</button>
        </div>
    </div>
</div>

<div id="successNotification">
    <div class="modal-box">
        <p id="successMessage"></p>
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

<div id="createFolderModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Create New Folder</h2>
            <button class="close-btn" id="closeCreateFolderModal">&times;</button>
        </div>
        <div class="modal-content">
            <form action="../reports/create_folder.php" method="POST" enctype="multipart/form-data" id="createFolderForm">
                <label for="folder_title">Folder Title</label>
                <input type="text" id="folder_title" name="title" placeholder="Enter folder title (e.g., Weekly Reports)" required>
                <label for="folder_month">Month</label>
                <select id="folder_month" name="month" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <label for="folder_year">Year</label>
                <select id="folder_year" name="year" required>
                    <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <label for="folder_path">Folder (Optional) Sub folder path</label>
                <input type="text" id="folder_path" name="path" placeholder="e.g., Weekly Reports/Week 1">
                <button type="submit" class="btn-primary">Create Folder</button>
            </form>
        </div>
    </div>
</div>

<div id="uploadReportModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Upload Reports</h2>
            <button class="close-btn" id="closeUploadReportModal">&times;</button>
        </div>
        <div class="modal-content">
            <form action="../reports/upload_report.php" method="POST" enctype="multipart/form-data" id="uploadReportForm">
                <label for="upload_year">Year</label>
                <select id="upload_year" name="year" required>
                    <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <label for="upload_month">Month</label>
                <select id="upload_month" name="month" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <label for="upload_folder">Folder (Optional)</label>
                <input type="text" id="upload_folder" name="folder" placeholder="Subfolder path (e.g., Weekly Reports/Week 1)">
                <label for="reports">Select Files</label>
                <input type="file" id="reports" name="reports[]" multiple required>
                <p style="font-size: 12px; color: #666; margin-top: 5px;">You can select multiple files at once</p>
                <button type="submit" class="btn-primary">Upload Reports</button>
            </form>
        </div>
    </div>
</div>

<script>
// Global function to show months for a year
function showMonths(year) {
    console.log('Fetching months for year:', year);
    
    // Update selected state
    document.querySelectorAll('.folder-card[data-year]').forEach(c => c.classList.remove('selected'));
    const yearCard = document.querySelector(`.folder-card[data-year="${year}"]`);
    if (yearCard) {
        yearCard.classList.add('selected');
    }
    
    // Update title
    document.getElementById('monthsTitle').textContent = `Months for ${year}`;
    
    // Fetch months
    fetch(`get_months.php?year=${year}`)
        .then(response => response.json())
        .then(data => {
            console.log('Months data:', data);
            const container = document.getElementById('monthsContainer');
            container.innerHTML = '';
            
            if (data.months.length === 0) {
                container.innerHTML = '<div class="no-reports" style="grid-column: 1 / -1;"><i class="fas fa-folder"></i><p>No months found for this year.</p></div>';
            } else {
                data.months.forEach(m => {
                    const monthName = new Date(year, m.month - 1, 1).toLocaleString('default', { month: 'long' });
                    const card = document.createElement('div');
                    card.className = 'folder-card';
                    card.innerHTML = `
                        <a href="?year=${year}&month=${m.month}">
                            <i class="fas fa-folder"></i>
                            <h3>${monthName}</h3>
                            <p>${m.count || 0} items</p>
                        </a>
                    `;
                    container.appendChild(card);
                });
            }
            
            document.getElementById('monthsPanel').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching months:', error);
        });
}

document.addEventListener('DOMContentLoaded', function() {
const archiveModal = document.getElementById('archiveModal');
const confirmArchive = document.getElementById('confirmArchive');
const cancelArchive = document.getElementById('cancelArchive');
const deleteModal = document.getElementById('deleteModal');
const confirmDelete = document.getElementById('confirmDelete');
const cancelDelete = document.getElementById('cancelDelete');
const successNotification = document.getElementById('successNotification');
const successMessage = document.getElementById('successMessage');

let archivePath = null;
let deletePath = null;

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
        archivePath = btn.dataset.path;
        archiveModal.classList.add('active');
    }
    if (e.target.closest('.delete-btn')) {
        const btn = e.target.closest('.delete-btn');
        deletePath = btn.dataset.path;
        deleteModal.classList.add('active');
    }
});

confirmArchive.addEventListener('click', () => {
    if (archivePath) {
        fetch(`../archives/archive_report.php?path=${encodeURIComponent(archivePath)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = data.message;
                    successNotification.classList.add('active');
                    archiveModal.classList.remove('active');
                    setTimeout(() => {
                        successNotification.classList.remove('active');
                        location.reload();
                    }, 3000);
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('Error archiving report.'));
    }
});

cancelArchive.addEventListener('click', () => {
    archiveModal.classList.remove('active');
    archivePath = null;
});

archiveModal.addEventListener('click', (e) => {
    if (e.target === archiveModal) {
        archiveModal.classList.remove('active');
        archivePath = null;
    }
});

confirmDelete.addEventListener('click', () => {
    if (deletePath) {
        fetch(`../archives/delete_report.php?path=${encodeURIComponent(deletePath)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = data.message;
                    successNotification.classList.add('active');
                    deleteModal.classList.remove('active');
                    setTimeout(() => {
                        successNotification.classList.remove('active');
                        location.reload();
                    }, 3000);
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('Error deleting report.'));
    }
});

cancelDelete.addEventListener('click', () => {
    deleteModal.classList.remove('active');
    deletePath = null;
});

deleteModal.addEventListener('click', (e) => {
    if (e.target === deleteModal) {
        deleteModal.classList.remove('active');
        deletePath = null;
    }
});

// Global function to toggle months for a year
function toggleMonths(year) {
    const monthsPanel = document.getElementById('monthsPanel');
    const yearCard = document.querySelector(`.folder-card[data-year="${year}"]`);
    
    // Check if this year is already selected
    const isSelected = yearCard && yearCard.classList.contains('selected');
    
    // Remove selected class from all year cards
    document.querySelectorAll('.folder-card[data-year]').forEach(c => c.classList.remove('selected'));
    
    if (isSelected) {
        // Hide months panel if already selected
        monthsPanel.style.display = 'none';
    } else {
        // Show months for this year
        if (yearCard) {
            yearCard.classList.add('selected');
        }
        
        // Update title
        document.getElementById('monthsTitle').textContent = `Months for ${year}`;
        
        // Fetch months from the server (scans filesystem)
        fetch(`get_months.php?year=${year}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('monthsContainer');
                container.innerHTML = '';
                
                if (data.months.length === 0) {
                    container.innerHTML = '<div class="no-reports" style="grid-column: 1 / -1;"><i class="fas fa-folder"></i><p>No months found for this year.</p></div>';
                } else {
                    data.months.forEach(m => {
                        const monthName = new Date(year, m.month - 1, 1).toLocaleString('default', { month: 'long' });
                        const card = document.createElement('div');
                        card.className = 'folder-card';
                        card.innerHTML = `
                            <a href="?year=${year}&month=${m.month}">
                                <i class="fas fa-folder"></i>
                                <h3>${monthName}</h3>
                                <p>${m.count || 0} items</p>
                            </a>
                        `;
                        container.appendChild(card);
                    });
                }
                
                monthsPanel.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching months:', error);
            });
    }
}

// Check if year is in URL and open panel
const urlParams = new URLSearchParams(window.location.search);
const yearParam = urlParams.get('year');
if (yearParam) {
    toggleMonths(yearParam);
}

// Handle subfolder clicks
document.addEventListener('click', (e) => {
    if (e.target.closest('.subfolder')) {
        const btn = e.target.closest('.subfolder');
        const year = btn.dataset.year;
        const month = btn.dataset.month;
        const folder = btn.dataset.folder;
        window.location.href = `?year=${year}&month=${month}&folder=${encodeURIComponent(folder)}`;
    }
});

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

// Create Folder Modal
const createFolderModal = document.getElementById('createFolderModal');
const createFolderBtn = document.getElementById('createFolderBtn');
const closeCreateFolderModal = document.getElementById('closeCreateFolderModal');

if (createFolderBtn && createFolderModal) {
    createFolderBtn.addEventListener('click', () => {
        createFolderModal.classList.add('active');
    });
}

if (closeCreateFolderModal && createFolderModal) {
    closeCreateFolderModal.addEventListener('click', () => {
        createFolderModal.classList.remove('active');
    });
}

if (createFolderModal) {
    createFolderModal.addEventListener('click', (e) => {
        if(e.target === createFolderModal){
            createFolderModal.classList.remove('active');
        }
    });
}

// Upload Report Modal
const uploadReportModal = document.getElementById('uploadReportModal');
const uploadReportBtn = document.getElementById('uploadReportBtn');
const closeUploadReportModal = document.getElementById('closeUploadReportModal');

if (uploadReportBtn && uploadReportModal) {
    uploadReportBtn.addEventListener('click', () => {
        uploadReportModal.classList.add('active');
    });
}

if (closeUploadReportModal && uploadReportModal) {
    closeUploadReportModal.addEventListener('click', () => {
        uploadReportModal.classList.remove('active');
    });
}

if (uploadReportModal) {
    uploadReportModal.addEventListener('click', (e) => {
        if(e.target === uploadReportModal){
            uploadReportModal.classList.remove('active');
        }
    });
}

});
</script>

</body>
</html>
