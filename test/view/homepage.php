<?php
session_start();
require_once '../controllers/NotificationController.php';
require_once '../utils/auth_middleware.php';
$notificationController = new NotificationController();
$unreadCount = $notificationController->getUnreadCount();
$user = checkAuth();
?>
<!DOCTYPE html>
<html lang = "en">
    <head>
         <meta charset="UTF-8" />
         <meta name="viewport" content="width=device-width, initial-scale=1.0" />
         <title>Pow - Pet Adoption</title>
         <link rel="stylesheet" href="../stiluri/homepage.css" />
          <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
          <style>
            .notify-btn {
                display: flex;
                align-items: center;
                gap: 5px;
                background-color: #ff5a00;
                color: white;
                padding: 8px 15px;
                border-radius: 20px;
                text-decoration: none;
                position: relative;
            }

            .notify-btn:hover {
                background-color: #e65100;
            }

            .notify-count {
                position: absolute;
                top: -5px;
                right: -5px;
                background-color: #ffc107;
                color: black;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 12px;
                min-width: 16px;
                text-align: center;
            }
         </style>
    </head>

    <body>
        <header class="navbar">
            <div class="nav-left">
                <a href="post-pet.php" class="add-pet-btn">
                    <span class="plus-icon">+</span>
                    <span>Add</span>
                </a>
                <a href="adoption-requests.php" class="requests-btn">
                    <span>Requests</span>
                </a>
                <?php if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']): ?>
                <a href="adoption-status.php" class="requests-btn">
                    <span>My Adoptions</span>
                </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="notifications.php" class="notify-btn">
                    <span>Notify</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notify-count"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>

            <div class="logo">Pow</div>

            <div class="nav-right">
                <a href="profile.php" class="profile-icon">
                    <img src="../stiluri/imagini/profileicon.png" alt="Profile" />
                </a>
            </div>
        </header>

        <section class = "welcome">
            <h1 class = "wel">Welcome to Pow! </h1>
            <h2 class = "choose">Choose your pet</h2>

            <div class="pet-options">
                <div class="pet-card">
                    <a href="lista-animale.php?type=cat">
                        <img src="../stiluri/imagini/cat.png" alt="cat">
                    </a>
                    <span>cat</span>
                </div>
                <div class="pet-card">
                    <a href="lista-animale.php?type=dog">
                        <img src="../stiluri/imagini/dog.png" alt="dog">
                    </a>
                     <span>dog</span>
                </div>
            <div class="pet-card">
                <a href="lista-animale.php?type=bird">
                    <img src="../stiluri/imagini/bird.png" alt="bird">
                </a>
                <span>bird</span>
            </div>
            <div class="pet-card">
                <a href="lista-animale.php?type=fish">
                    <img src="../stiluri/imagini/fish.png" alt="fish">
                </a>
                <span>fish</span>
            </div>
            <div class="pet-card">
                <a href="lista-animale.php?type=reptile">
                    <img src="../stiluri/imagini/reptilian.png" alt="reptilian">
                </a>
                <span>reptilian</span>
            </div>
        </div>
        </section>

        <section class = "why-adopt">
             <h2>Why Adopt a Pet?</h2>
             <div class="why-content">
                <ul>
                    <li><span>Save a life</span> - Give a homeless animal a second chance and a loving home.</li>
                    <li><span>Reduce overpopulation</span> - Help shelters free up space for other animals in need.</li>
                    <li><span>More affordable</span> -Adoption is often cheaper than buying from breeders or pet stores.</li>
                    <li><span>Emotionally rewarding</span> - Pets offer unconditional love, companionship, and emotional support.</li>
                    <li><span>Make a difference</span> - Be part of a compassionate, responsible community of pet lovers.</li>
                </ul>
                <img src="../stiluri/imagini/dog-woman.png" alt="woman with dog" />
             </div>
        </section>

         <section class="comics">
            <div class="meme1"><img src="../stiluri/imagini/meme1.png" alt=""></div>
            <div class="meme"><img src="../stiluri/imagini/meme2.png" alt=""></div>
            <div class="meme"><img src="../stiluri/imagini/meme3.png" alt=""></div>
        </section>

        <section class="oui">
            <div class="split-section">
                <div class="left-side"></div>
                <div class="right-side">
                    <h3>Get a Pet!</h3>
                </div>
                <img src="../stiluri/imagini/happydog.png" class="dog-image" alt="happy dog" />
            </div>
        </section>

    </body>
</html>