<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pow - Available Animals</title>
    <link rel="stylesheet" href="../stiluri/homepage.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <style>
        .animals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .animal-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: left;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .animal-card h3 {
            margin: 10px 0;
            color: #333;
            text-align: center;
            font-size: 1.5em;
        }
        .animal-info {
            margin: 15px 0;
        }
        .animal-info p {
            margin: 8px 0;
            color: #666;
            display: flex;
            justify-content: space-between;
        }
        .animal-info p strong {
            color: #333;
        }
        .view-details {
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            transition: background-color 0.3s;
        }
        .view-details:hover {
            background-color: #45a049;
        }
        .adoption-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            text-align: center;
            margin: 10px 0;
        }
        .available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .not-available {
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="logo">Pow</div>
        <a href="profile.html" class="profile-icon">
            <img src="../stiluri/imagini/profileicon.png" alt="Profile" />
        </a>
    </header>

    <div class="animals-container">
        <h1>Available <?php echo ucfirst($_GET['type']); ?>s</h1>
        
        <div class="animals-grid">
            <?php
            require_once('../config/database.php');
            require_once('../models/Animal.php');

            $type = $_GET['type'] ?? 'dog';
            $animals = Animal::getAnimalsByType($type);

            foreach ($animals as $animal) {
                echo '<div class="animal-card">';
                echo '<h3>' . htmlspecialchars($animal['name']) . '</h3>';
                
                // Adoption Status
                $statusClass = $animal['available_for_adoption'] == 1 ? 'available' : 'not-available';
                $statusText = $animal['available_for_adoption'] == 1 ? 'Available for Adoption' : 'Not Available';
                echo '<div class="adoption-status ' . $statusClass . '">' . $statusText . '</div>';
                
                echo '<div class="animal-info">';
                if (!empty($animal['breed'])) {
                    echo '<p><strong>Breed:</strong> ' . htmlspecialchars($animal['breed']) . '</p>';
                }
                if (!empty($animal['age'])) {
                    echo '<p><strong>Age:</strong> ' . htmlspecialchars($animal['age']) . ' years</p>';
                }
                if (!empty($animal['gender'])) {
                    echo '<p><strong>Gender:</strong> ' . htmlspecialchars($animal['gender']) . '</p>';
                }
                if (!empty($animal['health_status'])) {
                    echo '<p><strong>Health Status:</strong> ' . htmlspecialchars($animal['health_status']) . '</p>';
                }
                if (!empty($animal['description'])) {
                    echo '<p><strong>Description:</strong> ' . htmlspecialchars($animal['description']) . '</p>';
                }
                if (!empty($animal['adoption_address'])) {
                    echo '<p><strong>Location:</strong> ' . htmlspecialchars($animal['adoption_address']) . '</p>';
                }
                echo '</div>';
                
                echo '<a href="animal_details.php?id=' . htmlspecialchars($animal['id']) . '" class="view-details">View Details</a>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html> 