<?php
require_once '../controllers/NotificationController.php';
$notificationController = new NotificationController();
$unreadCount = $notificationController->getUnreadCount();
?>

<header class="navbar">
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="logo">Pow</div>
    <div class="nav-right">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="post-pet.php" class="add-pet-btn">
                <span class="plus-icon">+</span>
                <span>Add</span>
            </a>
            <a href="adoption-requests.php" class="requests-btn">
                <span>Requests</span>
            </a>
        <?php else: ?>
            <a href="adoption-status.php" class="requests-btn">
                <span>My Adoptions</span>
            </a>
        <?php endif; ?>
        
        <a href="notifications.php" class="notifications-icon">
            <img src="../stiluri/imagini/notification-icon.png" alt="Notifications">
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="profile.php" class="profile-icon">
            <img src="../stiluri/imagini/profileicon.png" alt="Profile">
        </a>
    </div>
</header> 