<?php
require '../config/db.php';
require '../config/auth.php';

// Get activity ID from URL
$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

// Handle status update via GET
if ($id && $status) {
    $stmt = $pdo->prepare("UPDATE activities SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'UPDATE_ACTIVITY', 'Updated activity ID: ' . $id . ' to ' . $status]);

    $stmt = $pdo->prepare("SELECT start_date FROM activities WHERE id = ?");
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
    $activityMonth = date('m', strtotime($activity['start_date']));
    $activityYear = date('Y', strtotime($activity['start_date']));
    header("Location: ../dashboard/dashboard.php?month=$activityMonth&year=$activityYear");
    exit;
}

// Fetch activity data for the form
$activity = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([$id]);
    $activity = $stmt->fetch();
}

// Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $regulatory_agency = $_POST['regulatory_agency'];
    $report_details = $_POST['report_details'];
    $concern_department = $_POST['concern_department'];
    $deadline_date = $_POST['deadline_date'];
    $status = $_POST['status'];

    // Update the activity
    $stmt = $pdo->prepare("UPDATE activities SET title = ?, description = ?, start_date = ?, regulatory_agency = ?, report_details = ?, concern_department = ?, deadline_date = ?, status = ? WHERE id = ?");
    $result = $stmt->execute([$title, $description, $start_date, $regulatory_agency, $report_details, $concern_department, $deadline_date, $status, $id]);
    
    if (!$result) {
        $_SESSION['error'] = 'Failed to update activity. Please try again.';
        header("Location: ../dashboard/dashboard.php");
        exit;
    }

    $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->execute([$_SESSION['user_id'], 'UPDATE_ACTIVITY', 'Updated activity ID: ' . $id]);

    $_SESSION['activity_updated'] = true;
    $activityMonth = date('m', strtotime($start_date));
    $activityYear = date('Y', strtotime($start_date));
    header("Location: ../dashboard/dashboard.php?month=$activityMonth&year=$activityYear");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Activity - Report Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/NEECO_banner.png">
</head>
<body>
<div class="main-layout">
    <?php include '../dashboard/sidebar.php'; ?>
    <main class="main-content">
        <?php $page_title = 'Update Activity'; include '../dashboard/header.php'; ?>
        <div class="form-section">
            <div class="form-container">
                <?php if (!$activity): ?>
                    <div class="error-message">
                        <p>Activity not found or you do not have permission to edit it.</p>
                        <a href="../dashboard/dashboard.php" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 30px;">Back to Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="form-header">
                        <h2><i class="fas fa-edit"></i> Update Activity</h2>
                        <a href="../dashboard/dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?= $activity['id'] ?>">
                        
                        <div class="form-group">
                            <label for="title"><i class="fas fa-heading"></i> Activity Name</label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($activity['title']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Description</label>
                            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($activity['description']) ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="<?= $activity['start_date'] ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="deadline_date"><i class="fas fa-calendar-check"></i> Deadline Date</label>
                                <input type="date" id="deadline_date" name="deadline_date" value="<?= $activity['deadline_date'] ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="regulatory_agency"><i class="fas fa-building"></i> Regulatory Agency</label>
                            <input type="text" id="regulatory_agency" name="regulatory_agency" value="<?= htmlspecialchars($activity['regulatory_agency']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="concern_department"><i class="fas fa-sitemap"></i> Concern Department</label>
                            <input type="text" id="concern_department" name="concern_department" value="<?= htmlspecialchars($activity['concern_department']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="report_details"><i class="fas fa-file-alt"></i> Report Details</label>
                            <textarea id="report_details" name="report_details" rows="3"><?= htmlspecialchars($activity['report_details']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status"><i class="fas fa-flag"></i> Status</label>
                            <select id="status" name="status">
                                <option value="pending" <?= $activity['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in-progress" <?= $activity['status'] === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $activity['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Activity</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<style>
.form-section {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.form-container {
    background: #fff;
    border-radius: 22px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eaf7f1;
}

.form-header h2 {
    margin: 0;
    color: #023020;
    font-size: 24px;
}

.form-header h2 i {
    margin-right: 10px;
    color: #1f8f5f;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #eaf7f1;
    color: #023020;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: #d0ede3;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    color: #023020;
    font-weight: 500;
}

.form-group label i {
    color: #1f8f5f;
    width: 20px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 14px 15px;
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    background: #f9f9f9;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
    font-family: 'Poppins', Tahoma, sans-serif;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #1f8f5f;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(31, 143, 95, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    margin-top: 30px;
    text-align: center;
}

.form-actions .btn-primary {
    width: auto;
    padding: 14px 40px;
}

.error-message {
    text-align: center;
    padding: 40px;
}

.error-message p {
    color: #666;
    margin-bottom: 20px;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-container {
        padding: 20px;
    }
}
</style>

</body>
</html>
