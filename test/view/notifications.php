<?php
require_once '../utils/auth_middleware.php';
$user = checkAuth();

session_start();
require_once '../controllers/NotificationController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$controller = new NotificationController();
$result = $controller->getNotifications();

if (isset($_POST['mark_read'])) {
    $controller->markAsRead($_POST['notification_id']);
    header('Location: notifications.php');
    exit;
}

if (isset($_POST['mark_all_read'])) {
    $controller->markAllAsRead();
    header('Location: notifications.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Pow</title>
    <link rel="stylesheet" href="../stiluri/styles.css">
    <link rel="stylesheet" href="../stiluri/notifications.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="homepage.php" class="back-btn">
                ← Back
            </a>
        </div>
        <div class="logo">Pow</div>
        <div class="nav-right">
            <a href="profile.php" class="profile-icon">
                <img src="../stiluri/imagini/profileicon.png" alt="Profile">
            </a>
        </div>
    </header>

    <div class="notifications-container">
        <h1>Notifications</h1>
        
        <?php if ($result['success'] && !empty($result['notifications'])): ?>
            <form method="POST" style="text-align: right;">
                <button type="submit" name="mark_all_read" class="mark-all-read">
                    Mark all as read
                </button>
            </form>

            <?php foreach ($result['notifications'] as $notification): ?>
                <div class="notification-card <?php echo $notification['IS_READ'] ? '' : 'unread'; ?>">
                    <div class="notification-content">
                        <p class="notification-message">
                            <?php echo htmlspecialchars($notification['MESSAGE']); ?>
                        </p>
                        <p class="notification-date">
                            <?php echo date('F j, Y, g:i a', strtotime($notification['CREATED_AT'])); ?>
                        </p>
                    </div>

                    <?php if (!$notification['IS_READ']): ?>
                        <div class="notification-actions">
                            <form method="POST">
                                <input type="hidden" name="notification_id" 
                                       value="<?php echo $notification['ID']; ?>">
                                <button type="submit" name="mark_read" class="mark-read-btn">
                                    Mark as read
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <h2>No notifications yet</h2>
                <p>When you receive notifications, they will appear here.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hamburger menu functionality
            const hamburger = document.querySelector('.hamburger');
            hamburger.addEventListener('click', function() {
                this.classList.toggle('active');
                // Add your sidebar toggle logic here
            });
        });
    </script>
</body>
</html> 