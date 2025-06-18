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
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-left {
            flex: 1;
        }

        .logo {
            flex: 1;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #ff5a00;
        }

        .nav-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }

        .back-btn {
            background-color: #ff5a00;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .back-btn:hover {
            background-color: #e65100;
        }

        .profile-icon img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s;
        }

        .notification-card:hover {
            transform: translateY(-2px);
        }

        .notification-card.unread {
            border-left: 4px solid #ffc107;
        }

        .pet-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .notification-date {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .mark-read-btn {
            background: none;
            border: none;
            color: #ffc107;
            cursor: pointer;
            padding: 5px 10px;
            font-size: 14px;
        }

        .mark-read-btn:hover {
            text-decoration: underline;
        }

        .mark-all-read {
            background: #ffc107;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .mark-all-read:hover {
            background: #e5ac00;
        }

        .no-notifications {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
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