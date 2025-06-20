<?php
require_once '../utils/auth_middleware.php';
$user = checkAuth();

require_once '../controllers/PetController.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$controller = new PetController();
$pets = $controller->getUserPets($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posted Pets - Pow</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Josefin Sans', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background-color: #ff5a00;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .back-button:hover {
            background-color: #ff7a30;
        }

        .back-icon {
            font-size: 1.2em;
        }

        .container {
            max-width: 1200px;
            margin: 80px auto 20px;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .pet-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .pet-card:hover {
            transform: translateY(-5px);
        }

        .pet-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .pet-info {
            padding: 15px;
            text-align: center;
        }

        .pet-name {
            font-size: 1.2em;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            width: 100%;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .no-pets {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.2em;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <a href="profile.php" class="back-button">
        <span class="back-icon">←</span>
        <span>Back</span>
    </a>

    <div class="container">
        <h1>My Posted Pets</h1>

        <div class="pets-grid">
            <?php if (!empty($pets)): ?>
                <?php foreach ($pets as $pet): ?>
                    <div class="pet-card">
                        <?php 
                        $imageUrl = $controller->getDefaultPetImage($pet['ID'], $pet['SPECIES']);
                        if (!empty($imageUrl)): 
                        ?>
                        <img src="<?php echo $imageUrl; ?>" 
                             alt="<?php echo htmlspecialchars($pet['NAME'] ?? 'Pet'); ?>" 
                             class="pet-image">
                        <?php else: ?>
                        <div class="pet-image" style="background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                            <span style="color: #999;"><?php echo htmlspecialchars($pet['NAME'] ?? 'Pet'); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="pet-info">
                            <h3 class="pet-name"><?php echo htmlspecialchars($pet['NAME'] ?? 'Unnamed Pet'); ?></h3>
                            <button class="delete-btn" onclick="deletePet(<?php echo (int)$pet['ID']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-pets">
                    <p>You haven't posted any pets yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deletePet(petId) {
            if (confirm('Are you sure you want to delete this pet?')) {
                fetch('../controllers/PetController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete&pet_id=' + petId,
                    credentials: 'same-origin'
                })
                .then(async response => {
                    const text = await response.text();
                    try {
                        const data = JSON.parse(text);
                        if (!response.ok) {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        }
                        return data;
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Server returned invalid JSON. Check console for details.');
                    }
                })
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error details:', error);
                    alert('Error deleting pet: ' + error.message);
                });
            }
        }
    </script>
</body>
</html> 