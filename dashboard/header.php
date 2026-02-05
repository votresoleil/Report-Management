<?php

$twoDaysFromNow = date('Y-m-d', strtotime('+2 days'));
$stmt = $pdo->prepare("SELECT a.*, u.full_name FROM activities a JOIN users u ON a.user_id = u.user_id WHERE a.start_date <= ? AND a.start_date >= ? AND a.status != 'completed' ORDER BY a.start_date");
$stmt->execute([$twoDaysFromNow, date('Y-m-d')]);
$upcomingActivities = $stmt->fetchAll();
?>
<div class="header-section">
    <h2><?php echo $page_title ?? 'Page'; ?></h2>
    <div class="icons-right">
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <i class="fas fa-users" style="cursor: pointer;" onclick="showUsersModal()"></i>
        <?php endif; ?>
        <div class="bell-container">
            <i class="fas fa-bell" style="cursor: pointer;" onclick="showNotificationsModal()"></i>
            <?php if (count($upcomingActivities) > 0): ?>
                <span class="notification-badge"><?php echo count($upcomingActivities); ?></span>
            <?php endif; ?>
        </div>
        <i class="fas fa-user" style="cursor: pointer;" onclick="showUserInfoModal()"></i>
    </div>
</div>


<?php if ($_SESSION['role'] == 'admin'): ?>
<div id="usersModal" class="modal-overlay">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>System Users</h2>
            <div class="header-actions">
                <button class="close-btn" onclick="closeUsersModal()">&times;</button>
            </div>
        </div>
        <div class="modal-content">
            <div id="usersList">
                <p>Loading users...</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="userInfoModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>User Information</h2>
            <button class="close-btn" onclick="closeUserInfoModal()">&times;</button>
        </div>
        <div class="modal-content">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        </div>
    </div>
</div>

<div id="notificationsModal" class="modal-overlay">
    <div class="modal-box large">
        <div class="modal-header">
            <h2>Upcoming Activities</h2>
            <button class="close-btn" onclick="closeNotificationsModal()">&times;</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const usersModal = document.getElementById('usersModal');
    const userInfoModal = document.getElementById('userInfoModal');
    const notificationsModal = document.getElementById('notificationsModal');

    // Show functions
    window.showUsersModal = function() {
        if (!usersModal) return;
        fetch('../users/get_users.php')
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
    };

    window.showUserInfoModal = function() {
        if (userInfoModal) {
            userInfoModal.classList.add('active');
        }
    };

    window.showNotificationsModal = function() {
        if (notificationsModal) {
            notificationsModal.classList.add('active');
        }
    };

    // Close functions
    window.closeUsersModal = function() {
        if (usersModal) {
            usersModal.classList.remove('active');
        }
    };

    window.closeUserInfoModal = function() {
        if (userInfoModal) {
            userInfoModal.classList.remove('active');
        }
    };

    window.closeNotificationsModal = function() {
        if (notificationsModal) {
            notificationsModal.classList.remove('active');
        }
    };

    // Close on background click
    if (usersModal) {
        usersModal.addEventListener('click', function(e) {
            if (e.target === usersModal) {
                usersModal.classList.remove('active');
            }
        });
    }

    if (userInfoModal) {
        userInfoModal.addEventListener('click', function(e) {
            if (e.target === userInfoModal) {
                userInfoModal.classList.remove('active');
            }
        });
    }

    if (notificationsModal) {
        notificationsModal.addEventListener('click', function(e) {
            if (e.target === notificationsModal) {
                notificationsModal.classList.remove('active');
            }
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (usersModal) usersModal.classList.remove('active');
            if (userInfoModal) userInfoModal.classList.remove('active');
            if (notificationsModal) notificationsModal.classList.remove('active');
        }
    });

    // Handle addUserBtn if it exists
    if (typeof addUserBtn !== 'undefined' && addUserBtn) {
        addUserBtn.addEventListener('click', () => {
            if (typeof addUserModal !== 'undefined') {
                addUserModal.classList.add('active');
            }
        });
    }

    if (typeof closeAddUserModal !== 'undefined' && closeAddUserModal) {
        closeAddUserModal.addEventListener('click', () => {
            if (typeof addUserModal !== 'undefined') {
                addUserModal.classList.remove('active');
            }
        });
    }

    if (typeof addUserModal !== 'undefined' && addUserModal) {
        addUserModal.addEventListener('click', (e) => {
            if (e.target === addUserModal) {
                addUserModal.classList.remove('active');
            }
        });
    }

    if (typeof cancelAddUser !== 'undefined' && cancelAddUser) {
        cancelAddUser.addEventListener('click', () => {
            if (typeof addUserModal !== 'undefined' && typeof userForm !== 'undefined') {
                addUserModal.classList.remove('active');
                userForm.reset();
            }
        });
    }

    if (typeof userForm !== 'undefined' && userForm) {
        userForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(userForm);
            fetch('../users/add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User added successfully!');
                    userForm.reset();
                    if (typeof addUserModal !== 'undefined') {
                        addUserModal.classList.remove('active');
                    }
                    if (typeof showUsersModal === 'function') {
                        showUsersModal();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => {
                alert('An error occurred while adding the user.');
            });
        });
    }
});
</script>
