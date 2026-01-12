<?php
// This file contains the sidebar and logout modal
if (!isset($active_view)) $active_view = 'main';
?>
<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <img src="img/NEECO_banner.png" alt="Company Logo" class="app-logo">
            <span class="role-text"><?= isset($_SESSION['role']) && $_SESSION['role'] == 'admin' ? 'Administrator' : 'Assistant' ?></span>
        </div>
        <!-- <h1>Report & Activity Management System</h1> -->
        <ul>
            <li><a href="dashboard.php" class="<?= $active_view === 'main' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Main Dashboard</a></li>
            <li><a href="report_folders.php" class="<?= $active_view === 'folders' ? 'active' : '' ?>"><i class="fas fa-folder"></i> Report Folders</a></li>
            <li><a href="calendar.php" class="<?= $active_view === 'calendar' ? 'active' : '' ?>"><i class="fas fa-calendar"></i> Activity Calendar</a></li>
            <li><a href="archives.php" class="<?= $active_view === 'archives' ? 'active' : '' ?>"><i class="fas fa-archive"></i> Archives</a></li>
            <li><a href="activity_log.php" class="<?= $active_view === 'logs' ? 'active' : '' ?>"><i class="fas fa-history"></i> Activity Log</a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <button id="logoutBtn" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>
</aside>

<div id="logoutModal">
    <div class="modal-box">
        <h2>Confirm Logout</h2>
        <p>Are you sure you want to log out?</p>
        <div class="actions">
            <button class="cancel" id="cancelLogout"><i class="fas fa-times"></i> Cancel</button>
            <form method="POST" action="logout.php">
                <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </div>
    </div>
</div>

<script>
const logoutBtn = document.getElementById('logoutBtn');
const logoutModal = document.getElementById('logoutModal');
const cancelBtn = document.getElementById('cancelLogout');

logoutBtn.addEventListener('click', () => {
    logoutModal.classList.add('active');
});

cancelBtn.addEventListener('click', () => {
    logoutModal.classList.remove('active');
});

logoutModal.addEventListener('click', (e) => {
    if(e.target === logoutModal){
        logoutModal.classList.remove('active');
    }
});
</script>