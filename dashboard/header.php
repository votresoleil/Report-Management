<?php

require '../config/db.php';

// Get unread notification count
$notifCountStmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$notifCountStmt->execute([$_SESSION['user_id']]);
$unreadCount = $notifCountStmt->fetch()['unread_count'];
?>
<div class="header-section">
    <h2><?php echo $page_title ?? 'Page'; ?></h2>
    <div class="icons-right">
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <i class="fas fa-users" style="cursor: pointer;" onclick="showUsersModal()"></i>
        <?php endif; ?>
        <div class="bell-container">
            <i class="fas fa-bell" style="cursor: pointer;" onclick="showNotificationsModal()"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?php echo $unreadCount; ?></span>
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
            <h2>Notifications</h2>
            <div class="header-actions">
                <button class="mark-all-read" onclick="markAllNotificationsRead()">Mark all as read</button>
                <button class="close-btn" onclick="closeNotificationsModal()">&times;</button>
            </div>
        </div>
        <div class="modal-content">
            <div id="notificationsList" class="notifications-list-container">
                <p>Loading notifications...</p>
            </div>
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
            fetch('../notifications/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    updateNotificationBadge(data.unread_count);
                    renderNotifications(data.notifications);
                    notificationsModal.classList.add('active');
                })
                .catch(() => {
                    document.getElementById('notificationsList').innerHTML = '<p>Error loading notifications.</p>';
                    notificationsModal.classList.add('active');
                });
        }
    };

    window.markAllNotificationsRead = function() {
        fetch('../notifications/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetch('../notifications/get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        updateNotificationBadge(data.unread_count);
                        renderNotifications(data.notifications);
                    });
            }
        });
    };

    function updateNotificationBadge(count) {
        const badge = document.querySelector('.bell-container .notification-badge');
        if (count > 0) {
            if (badge) {
                badge.textContent = count;
            } else {
                const bellContainer = document.querySelector('.bell-container');
                const badgeEl = document.createElement('span');
                badgeEl.className = 'notification-badge';
                badgeEl.textContent = count;
                bellContainer.appendChild(badgeEl);
            }
        } else if (badge) {
            badge.remove();
        }
    }

    function renderNotifications(notifications) {
        const list = document.getElementById('notificationsList');
        if (!notifications || notifications.length === 0) {
            list.innerHTML = `
                <div class="notifications-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            `;
            return;
        }
        
        let html = '<ul class="activity-notifications-list">';
        notifications.forEach(notif => {
            const timeAgo = formatTimeAgo(notif.created_at);
            html += `
                <li class="notification-item ${notif.is_read ? '' : 'unread'}" data-id="${notif.notification_id}" onclick="markNotificationRead(${notif.notification_id})">
                    <div class="notification-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="notification-content">
                        <h4>${notif.title}</h4>
                        <p>${notif.message}</p>
                        <small>${timeAgo}</small>
                    </div>
                    ${notif.link ? `<a href="${notif.link}" class="notification-link" onclick="event.stopPropagation();">View</a>` : ''}
                </li>
            `;
        });
        html += '</ul>';
        list.innerHTML = html;
    }

    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
        
        return date.toLocaleDateString();
    }

    window.markNotificationRead = function(notificationId) {
        fetch('../notifications/mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetch('../notifications/get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        updateNotificationBadge(data.unread_count);
                        renderNotifications(data.notifications);
                    });
            }
        });
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
