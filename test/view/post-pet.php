<?php
require_once '../config/database.php';
require_once '../model/ImageHandler.php';
session_start();

// Verificăm dacă utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    // Salvăm URL-ul curent pentru a reveni după login
    $_SESSION['return_to'] = 'post-pet.php';
    header('Location: login.php');
    exit;
}

// Procesăm formularul dacă a fost trimis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = getConnection();
        
        // Pregătim query-ul pentru inserare
        $query = "INSERT INTO pets (
            name, species, breed, age, gender, health_status, description,
            available_for_adoption, adoption_address, owner_id, personality_description,
            activity_description, diet_description, household_activity,
            household_environment, other_pets, color, marime, spayed_neutered,
            time_at_current_home, reason_for_rehoming, flea_treatment,
            current_owner_description
        ) VALUES (
            :name, :species, :breed, :age, :gender, :health_status, :description,
            1, :adoption_address, :owner_id, :personality_description,
            :activity_description, :diet_description, :household_activity,
            :household_environment, :other_pets, :color, :marime, :spayed_neutered,
            :time_at_current_home, :reason_for_rehoming, :flea_treatment,
            :current_owner_description
        ) RETURNING id INTO :inserted_id";
        
        $stmt = oci_parse($conn, $query);
        
        // Bind parameters
        oci_bind_by_name($stmt, ":name", $_POST['name']);
        oci_bind_by_name($stmt, ":species", $_POST['species']);
        oci_bind_by_name($stmt, ":breed", $_POST['breed']);
        oci_bind_by_name($stmt, ":age", $_POST['age']);
        oci_bind_by_name($stmt, ":gender", $_POST['gender']);
        oci_bind_by_name($stmt, ":health_status", $_POST['health_status']);
        oci_bind_by_name($stmt, ":description", $_POST['description']);
        oci_bind_by_name($stmt, ":adoption_address", $_POST['adoption_address']);
        oci_bind_by_name($stmt, ":owner_id", $_SESSION['user_id']);
        oci_bind_by_name($stmt, ":personality_description", $_POST['personality_description']);
        oci_bind_by_name($stmt, ":activity_description", $_POST['activity_description']);
        oci_bind_by_name($stmt, ":diet_description", $_POST['diet_description']);
        oci_bind_by_name($stmt, ":household_activity", $_POST['household_activity']);
        oci_bind_by_name($stmt, ":household_environment", $_POST['household_environment']);
        oci_bind_by_name($stmt, ":other_pets", $_POST['other_pets']);
        oci_bind_by_name($stmt, ":color", $_POST['color']);
        oci_bind_by_name($stmt, ":marime", $_POST['marime']);
        $spayed_neutered = isset($_POST['spayed_neutered']) ? 1 : 0;
        oci_bind_by_name($stmt, ":spayed_neutered", $spayed_neutered);
        oci_bind_by_name($stmt, ":time_at_current_home", $_POST['time_at_current_home']);
        oci_bind_by_name($stmt, ":reason_for_rehoming", $_POST['reason_for_rehoming']);
        $flea_treatment = isset($_POST['flea_treatment']) ? 1 : 0;
        oci_bind_by_name($stmt, ":flea_treatment", $flea_treatment);
        oci_bind_by_name($stmt, ":current_owner_description", $_POST['current_owner_description']);
        
        // Bind the output parameter for the inserted ID
        $inserted_id = 0;
        oci_bind_by_name($stmt, ":inserted_id", $inserted_id, -1, SQLT_INT);
        
        // Executăm query-ul
        $result = oci_execute($stmt);
        
        if ($result) {
            // Procesăm imaginile încărcate folosind ImageHandler
            if (isset($_FILES['pet_images']) && !empty($_FILES['pet_images']['name'][0])) {
                $imageHandler = new ImageHandler($conn);
                $uploadResult = $imageHandler->uploadPetImages($inserted_id, $_FILES['pet_images']);
                
                if (!$uploadResult['success']) {
                    // Log errors but continue
                    foreach ($uploadResult['errors'] as $error) {
                        error_log("Image upload error: " . $error);
                    }
                }
            }
            
            header('Location: pet-page.php?id=' . $inserted_id);
            exit;
        }
        
    } catch (Exception $e) {
        $error = "A apărut o eroare la salvarea datelor: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Pet for Adoption - Pow</title>
    <link rel="stylesheet" href="../stiluri/post-pet.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
</head>
<body>
    

    <main class="post-pet-container">
        <h1>Post Your Pet for Adoption</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="post-pet-form">
            <div class="form-section">
                <h2>Basic Information</h2>
                <div class="form-group">
                    <label for="name">Pet's Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="species">Species:</label>
                    <select id="species" name="species" required>
                        <option value="">Select Species</option>
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                        <option value="Bird">Bird</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="breed">Breed:</label>
                    <input type="text" id="breed" name="breed" required>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label for="age">Age (years):</label>
                        <input type="number" id="age" name="age" min="0" step="0.5" required>
                    </div>

                    <div class="form-group half">
                        <label for="gender">Gender:</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="color">Color:</label>
                    <input type="text" id="color" name="color" required>
                </div>

                <div class="form-group">
                    <label for="marime">Size:</label>
                    <select id="marime" name="marime" required>
                        <option value="">Select Size</option>
                        <option value="Small">Small</option>
                        <option value="Medium">Medium</option>
                        <option value="Large">Large</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h2>Health & Care</h2>
                <div class="form-group">
                    <label for="health_status">Health Status:</label>
                    <textarea id="health_status" name="health_status" required></textarea>
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="spayed_neutered" name="spayed_neutered">
                    <label for="spayed_neutered">Spayed/Neutered</label>
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="flea_treatment" name="flea_treatment">
                    <label for="flea_treatment">Flea Treatment</label>
                </div>
            </div>

            <div class="form-section">
                <h2>Personality & Behavior</h2>
                <div class="form-group">
                    <label for="personality_description">Personality Description:</label>
                    <textarea id="personality_description" name="personality_description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="activity_description">Activity Level & Exercise Needs:</label>
                    <textarea id="activity_description" name="activity_description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="diet_description">Diet & Feeding Habits:</label>
                    <textarea id="diet_description" name="diet_description" required></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2>Current Living Situation</h2>
                <div class="form-group">
                    <label for="household_activity">Household Activity Level:</label>
                    <select id="household_activity" name="household_activity" required>
                        <option value="">Select Activity Level</option>
                        <option value="Low">Low</option>
                        <option value="Moderate">Moderate</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="household_environment">Living Environment:</label>
                    <textarea id="household_environment" name="household_environment" required></textarea>
                </div>

                <div class="form-group">
                    <label for="other_pets">Other Pets in Household:</label>
                    <textarea id="other_pets" name="other_pets" required></textarea>
                </div>

                <div class="form-group">
                    <label for="time_at_current_home">Time at Current Home:</label>
                    <input type="text" id="time_at_current_home" name="time_at_current_home" required>
                </div>
            </div>

            <div class="form-section">
                <h2>Additional Information</h2>
                <div class="form-group">
                    <label for="description">General Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="reason_for_rehoming">Reason for Rehoming:</label>
                    <textarea id="reason_for_rehoming" name="reason_for_rehoming" required></textarea>
                </div>

                <div class="form-group">
                    <label for="current_owner_description">Message from Current Owner:</label>
                    <textarea id="current_owner_description" name="current_owner_description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="adoption_address">Adoption Location:</label>
                    <input type="text" id="adoption_address" name="adoption_address" required>
                </div>
            </div>

            <div class="form-section">
                <h2>Pet Photos</h2>
                <div class="form-group">
                    <label for="pet_images">Upload Photos (Multiple):</label>
                    <input type="file" id="pet_images" name="pet_images[]" multiple accept="image/*" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">Post for Adoption</button>
            </div>
        </form>
    </main>
</body>
</html> 