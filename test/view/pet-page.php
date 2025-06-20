<?php
require_once '../utils/auth_middleware.php';
$user = checkAuth();

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

    // Obținem toate animalele pentru hartă
    $allPets = $controller->getAllPetsWithCoordinates();
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
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC9pBmG3InVXEsgC5Hee4KPpU8n87dNNzQ"></script>
    <style>
        #map {
            width: 100%;
            height: 500px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <header class="navbar">
        <a href="lista-animale.php" class="back-btn">
            Back
        </a>

        <a href="homepage.php" class="logo">Pow</a>

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
            <button class="message-btn" onclick="window.location.href='messages.php?pet_id=<?php echo $pet_id; ?>&owner_id=<?php echo $pet['OWNER_ID']; ?>'">
                <?php echo $user->id === $pet['OWNER_ID'] ? 'Test Messages' : 'Message Owner'; ?>
            </button>
        </div>
    </section>

    <section class="location">
        <h2>Location Address</h2>
        <p><?php echo htmlspecialchars($pet['ADOPTION_ADDRESS']); ?></p>
        <div id="map"></div>
    </section>

    <script>
        function initMap() {
            const currentPet = {
                lat: <?php echo $pet['LATITUDE']; ?>,
                lng: <?php echo $pet['LONGITUDE']; ?>
            };

            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: currentPet,
                styles: [
                    {
                        "featureType": "poi",
                        "elementType": "labels",
                        "stylers": [{ "visibility": "off" }]
                    }
                ]
            });

            // Marker pentru animalul curent
            const currentMarker = new google.maps.Marker({
                position: currentPet,
                map: map,
                title: "<?php echo htmlspecialchars($pet['NAME']); ?>'s Location",
                icon: {
                    url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
                }
            });

            // Cerc pentru animalul curent
            const circle = new google.maps.Circle({
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#FF0000",
                fillOpacity: 0.15,
                map: map,
                center: currentPet,
                radius: 500
            });

            // Adăugăm toate celelalte animale
            const allPets = <?php echo json_encode($allPets); ?>;
            const markers = [];
            const bounds = new google.maps.LatLngBounds();

            allPets.forEach(pet => {
                if (pet.ID != <?php echo $pet_id; ?>) {
                    const position = {
                        lat: parseFloat(pet.LATITUDE),
                        lng: parseFloat(pet.LONGITUDE)
                    };

                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: pet.NAME,
                        icon: {
                            url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                        }
                    });

                    // Info window pentru fiecare animal
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px;">
                                <h3>${pet.NAME}</h3>
                                <p>${pet.SPECIES}</p>
                                <a href="pet-page.php?id=${pet.ID}" style="color: blue;">Vezi detalii</a>
                            </div>
                        `
                    });

                    marker.addListener("click", () => {
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker);
                    bounds.extend(position);
                }
            });

            // Adăugăm listener pentru zoom
            map.addListener("zoom_changed", () => {
                const zoom = map.getZoom();
                markers.forEach(marker => {
                    marker.setVisible(zoom <= 12);
                });
            });

            // Inițial ascundem markerii dacă zoom-ul este mare
            const initialZoom = map.getZoom();
            markers.forEach(marker => {
                marker.setVisible(initialZoom <= 12);
            });
        }

        // Inițializăm harta când se încarcă pagina
        window.onload = initMap;
    </script>
</body>
</html>
