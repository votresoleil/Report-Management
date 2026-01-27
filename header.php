<?php
// Upcoming activities for notifications
$twoDaysFromNow = date('Y-m-d', strtotime('+2 days'));
$stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? AND start_date <= ? AND start_date >= ? AND status != 'completed' ORDER BY start_date");
$stmt->execute([$_SESSION['user_id'], $twoDaysFromNow, date('Y-m-d')]);
$upcomingActivities = $stmt->fetchAll();
?>
<div class="header-section">
    <h2><?php echo $page_title ?? 'Page'; ?></h2>
    <div class="icons-right">
        <i class="fas fa-users" onclick="showUsersModal()"></i>
        <div class="bell-container">
            <i class="fas fa-bell" onclick="showNotificationsModal()"></i>
            <?php if (count($upcomingActivities) > 0): ?>
                <span class="notification-badge"><?php echo count($upcomingActivities); ?></span>
            <?php endif; ?>
        </div>
        <i class="fas fa-user" onclick="showUserInfoModal()"></i>
    </div>
</div>

<!-- Modals for header icons -->
<div id="usersModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>System Users</h2>
            <button class="close-btn" id="closeUsersModal">&times;</button>
        </div>
        <div class="modal-content">
            <div id="usersList">
                <p>Loading users...</p>
            </div>
        </div>
    </div>
</div>

<div id="userInfoModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>User Information</h2>
            <button class="close-btn" id="closeUserInfoModal">&times;</button>
        </div>
        <div class="modal-content">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        </div>
    </div>
</div>

<script>
// Header modals
const usersModal = document.getElementById('usersModal');
const closeUsersModal = document.getElementById('closeUsersModal');
const userInfoModal = document.getElementById('userInfoModal');
const closeUserInfoModal = document.getElementById('closeUserInfoModal');
const notificationsModal = document.getElementById('notificationsModal');
const closeNotificationsModal = document.getElementById('closeNotificationsModal');

function showUsersModal() {
    fetch('get_users.php')
        .then(response => response.json())
        .then(users => {
            const list = document.getElementById('usersList');
            if (users.length === 0) {
                list.innerHTML = '<p>No users found.</p>';
            } else {
                let html = '<table class="users-table"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th></tr></thead><tbody>';
                users.forEach(user => {
                    const statusClass = user.status === 'active' ? 'active' : 'inactive';
                    html += `<tr><td>${user.full_name}</td><td>${user.username}</td><td>${user.role}</td><td class="${statusClass}">${user.status}</td></tr>`;
                });
                html += '</tbody></table>';
                list.innerHTML = html;
            }
            usersModal.classList.add('active');
        })
        .catch(() => {
            document.getElementById('usersList').innerHTML = '<p>Error loading users.</p>';
            usersModal.classList.add('active');
        });
}

function showUserInfoModal() {
    userInfoModal.classList.add('active');
}

function showNotificationsModal() {
    notificationsModal.classList.add('active');
}

closeUsersModal.addEventListener('click', () => {
    usersModal.classList.remove('active');
});

usersModal.addEventListener('click', (e) => {
    if(e.target === usersModal){
        usersModal.classList.remove('active');
    }
});

closeUserInfoModal.addEventListener('click', () => {
    userInfoModal.classList.remove('active');
});

userInfoModal.addEventListener('click', (e) => {
    if(e.target === userInfoModal){
        userInfoModal.classList.remove('active');
    }
});

closeNotificationsModal.addEventListener('click', () => {
    notificationsModal.classList.remove('active');
});

notificationsModal.addEventListener('click', (e) => {
    if(e.target === notificationsModal){
        notificationsModal.classList.remove('active');
    }
});
</script>

<div id="notificationsModal">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Upcoming Activities</h2>
            <button class="close-btn" id="closeNotificationsModal">&times;</button>
        </div>
        <div class="modal-content">
            <?php if (empty($upcomingActivities)): ?>
                <p>No upcoming activities within the next 2 days.</p>
            <?php else: ?>
                <ul class="notifications-list">
                    <?php foreach ($upcomingActivities as $act): ?>
                        <?php
                        $daysDiff = (strtotime($act['start_date']) - time()) / (60*60*24);
                        $urgency = $daysDiff <= 1 ? 'urgent' : ($daysDiff <= 2 ? 'warning' : 'normal');
                        ?>
                        <li class="notification-item <?php echo $urgency; ?>">
                            <div class="notification-content">
                                <h4><?php echo htmlspecialchars($act['title']); ?></h4>
                                <p><?php echo htmlspecialchars($act['description']); ?></p>
                                <small>Due: <?php echo date('M d, Y', strtotime($act['start_date'])); ?> (<?php echo round($daysDiff, 1); ?> days)</small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>