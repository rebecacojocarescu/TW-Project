<?php
session_start();
require_once '../controllers/PetController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$controller = new PetController();
try {
    $result = $controller->showPetDetails($pet_id);
    if (isset($result['error'])) {
        throw new Exception($result['error']);
    }
    $pet = $result['pet'];
    $media = $result['media'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pet['NAME']); ?> - Pow</title>
    <link rel="stylesheet" href="../stiluri/pet-page.css" />
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
</head>

<body>
    <header class="navbar">
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="logo">Pow</div>

        <a href="profile.php" class="profile-icon">
            <img src="../stiluri/imagini/profileicon.png" alt="Profile" />
        </a>
    </header>

    <section class="pet-banner">
        <h1><?php echo htmlspecialchars($pet['NAME']); ?></h1>
    </section>

    <section class="pet-photos">
        <div class="photo-container">
            <?php if (!empty($media)): ?>
                <?php foreach ($media as $image): ?>
                    <img src="../<?php echo htmlspecialchars($image['URL']); ?>" alt="<?php echo htmlspecialchars($pet['NAME']); ?> photo" class="pet-photo">
                <?php endforeach; ?>
            <?php else: ?>
                <img src="../stiluri/imagini/<?php echo strtolower($pet['SPECIES']); ?>.png" alt="<?php echo htmlspecialchars($pet['NAME']); ?>" class="pet-photo">
            <?php endif; ?>
        </div>
    </section>

    <section class="pet-info">
        <div class="description">
            <h2><?php echo htmlspecialchars($pet['NAME']); ?> Description</h2>
        </div>
        <div class="info-section">
            <h2>Personality:</h2>
            <p><?php echo nl2br(htmlspecialchars($pet['PERSONALITY_DESCRIPTION'])); ?></p>
        </div>

        <div class="info-section">
            <h2>Activity:</h2>
            <p><?php echo nl2br(htmlspecialchars($pet['ACTIVITY_DESCRIPTION'])); ?></p>
        </div>

        <div class="info-section">
            <h2>Diet:</h2>
            <p><?php echo nl2br(htmlspecialchars($pet['DIET_DESCRIPTION'])); ?></p>
        </div>
    </section>

    <section class="profile-summary">
        <h2 class="section-title">Current Home Life</h2>
        <div class="profile-row">
            <span class="label">Household Activity</span>
            <span class="value"><?php echo htmlspecialchars($pet['HOUSEHOLD_ACTIVITY']); ?></span>
        </div>
        <div class="profile-row">
            <span class="label">Household Environment</span>
            <span class="value"><?php echo htmlspecialchars($pet['HOUSEHOLD_ENVIRONMENT']); ?></span>
        </div>
        <div class="profile-row">
            <span class="label">Other Pets</span>
            <span class="value"><?php echo htmlspecialchars($pet['OTHER_PETS']); ?></span>
        </div>

        <h2 class="section-title"><?php echo htmlspecialchars($pet['NAME']); ?> Profile Summary</h2>
        <div class="profile-row"><span class="label">Colour:</span><span class="value"><?php echo htmlspecialchars($pet['COLOR']); ?></span></div>
        <div class="profile-row"><span class="label">Breed:</span><span class="value"><?php echo htmlspecialchars($pet['BREED']); ?></span></div>
        <div class="profile-row"><span class="label">Sex:</span><span class="value"><?php echo htmlspecialchars($pet['GENDER']); ?></span></div>
        <div class="profile-row"><span class="label">Size:</span><span class="value"><?php echo htmlspecialchars($pet['MARIME']); ?></span></div>
        <div class="profile-row"><span class="label">Age:</span><span class="value"><?php echo htmlspecialchars($pet['AGE']); ?> years</span></div>
        <div class="profile-row"><span class="label">Spayed/Neutred:</span><span class="value"><?php echo $pet['SPAYED_NEUTERED'] ? 'Yes' : 'No'; ?></span></div>
        <div class="profile-row"><span class="label">Time at current home:</span><span class="value"><?php echo htmlspecialchars($pet['TIME_AT_CURRENT_HOME']); ?></span></div>
        <div class="profile-row"><span class="label">Reason for rehoming:</span><span class="value"><?php echo htmlspecialchars($pet['REASON_FOR_REHOMING']); ?></span></div>
        <div class="profile-row"><span class="label">Flea treatment:</span><span class="value"><?php echo $pet['FLEA_TREATMENT'] ? 'Yes' : 'No'; ?></span></div>
    </section>

    <section class="owner-description">
        <div class="owner-description-content">
            <h2 class="section-title">Current Owner's Description</h2>
            <p><?php echo nl2br(htmlspecialchars($pet['CURRENT_OWNER_DESCRIPTION'])); ?></p>
        </div>
        <div class="adopt-section">
            <?php
            if (!empty($media)) {
                echo '<img src="../' . htmlspecialchars($media[0]['URL']) . '" class="cat-image" alt="' . htmlspecialchars($pet['NAME']) . '">';
            } else {
                echo '<img src="../stiluri/imagini/' . strtolower($pet['SPECIES']) . '.png" class="cat-image" alt="' . htmlspecialchars($pet['SPECIES']) . '">';
            }
            ?>
            <button class="adopt-btn" onclick="window.location.href='formular.php?pet_id=<?php echo $pet_id; ?>'">Adopt Me</button>
        </div>
    </section>

    <section class="location">
        <h2>Location Address</h2>
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM!5e0!3m2!1sen!2s!4v1!5m2!1sen!2s&q=<?php echo urlencode($pet['ADOPTION_ADDRESS']); ?>&ll=<?php echo $pet['LATITUDE']; ?>,<?php echo $pet['LONGITUDE']; ?>" 
            width="1000" 
            height="500" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </section>
</body>
</html>
