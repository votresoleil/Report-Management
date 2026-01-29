<?php
require 'config/db.php';
require 'config/auth.php';

$active_view = 'logs';


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;


$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM activity_logs");
$total_stmt->execute();
$total_logs = $total_stmt->fetch()['total'];

$total_pages = ceil($total_logs / $per_page);

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name
    FROM activity_logs a
    JOIN users u ON a.user_id = u.user_id
    ORDER BY a.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute();
$logs = $stmt->fetchAll();


$action_map = [
    'ADD_ACTIVITY' => 'ADD',
    'UPDATE_ACTIVITY' => 'UPDATE',
    'ARCHIVE' => 'ARCHIVE',
    'RESTORE_REPORT' => 'RESTORE',
    'UPLOAD' => 'UPLOAD',
    'DELETE' => 'DELETE'
];


$status_map = [
    'ADD_ACTIVITY' => 'IN PROGRESS',
    'DELETE' => 'DELETED',
    'ARCHIVE' => 'ARCHIVED',
    'RESTORE_REPORT' => 'RESTORED',
    'UPLOAD' => 'UPLOADED'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log - Report Management</title>

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
        <?php $page_title = 'Activity Log'; include 'header.php'; ?>
        <div class="content-section">
            <?php if (empty($logs)): ?>
                <p>No activities found.</p>
            <?php else: ?>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $action = $action_map[$log['action']] ?? $log['action'];
                            if ($log['action'] == 'UPDATE_ACTIVITY') {
                                $pos = strpos($log['description'], 'to ');
                                $status = $pos !== false ? substr($log['description'], $pos + 3) : $log['description'];
                            } else {
                                $status = $status_map[$log['action']] ?? 'UNKNOWN';
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($log['full_name']) ?></td>
                                <td><?= htmlspecialchars($action) ?></td>
                                <td><?= htmlspecialchars($status) ?></td>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><button class="delete-btn danger" data-id="<?= htmlspecialchars($log['log_id']) ?>"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="page-btn">Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="page-btn">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="deleteModal">
    <div class="modal-box">
        <h2>Confirm Delete</h2>
        <p>Are you sure you want to delete this activity log entry? This action cannot be undone.</p>
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

<script>
const deleteModal = document.getElementById('deleteModal');
const confirmDelete = document.getElementById('confirmDelete');
const cancelDelete = document.getElementById('cancelDelete');
const successNotification = document.getElementById('successNotification');
const successMessage = document.getElementById('successMessage');

let deleteId = null;

document.addEventListener('click', (e) => {
    if (e.target.closest('.delete-btn')) {
        const btn = e.target.closest('.delete-btn');
        deleteId = btn.dataset.id;
        deleteModal.classList.add('active');
    }
});

confirmDelete.addEventListener('click', () => {
    if (deleteId) {
        fetch(`delete_activity.php?id=${deleteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = data.message;
                    successNotification.classList.add('active');
                    deleteModal.classList.remove('active');
                    // Remove the deleted row
                    const row = document.querySelector(`.delete-btn[data-id="${deleteId}"]`).closest('tr');
                    if (row) row.remove();
                    // Auto-hide after 3 seconds
                    setTimeout(() => {
                        successNotification.classList.remove('active');
                    }, 3000);
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('Error deleting activity log.'));
    }
});

cancelDelete.addEventListener('click', () => {
    deleteModal.classList.remove('active');
    deleteId = null;
});

deleteModal.addEventListener('click', (e) => {
    if (e.target === deleteModal) {
        deleteModal.classList.remove('active');
        deleteId = null;
    }
});
</script>

</body>
</html>