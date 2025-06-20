<?php
require_once '../utils/auth_middleware.php';
$user = checkAuth();

session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../stiluri/lista-animale.css" />
    <title>Pow - Pet Adoption</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
  </head>
  <body>
    <header>
      <div class="header-content">
        <a href="homepage.php" class="back-btn">Back</a>
        <a href="homepage.php" class="logo"><h1>Pow</h1></a>
        <a href="profile.php" class="profile-button">
          <img src="../stiluri/imagini/profileicon.png" alt="profile-button" />
        </a>
      </div>
    </header>
    <div class="wrapper">
      <div class="main-container">
        <h2>Filters:</h2>
        <form method="GET">
          <div class="filters">
            <div class="filter-group">
              <label for="type">Type:</label>
              <select id="type" name="type">
                <option value="">Select...</option>
                <option value="cat" <?php echo ($_GET['type'] ?? '') === 'cat' ? 'selected' : ''; ?>>Cat</option>
                <option value="dog" <?php echo ($_GET['type'] ?? '') === 'dog' ? 'selected' : ''; ?>>Dog</option>
                <option value="bird" <?php echo ($_GET['type'] ?? '') === 'bird' ? 'selected' : ''; ?>>Bird</option>
                <option value="fish" <?php echo ($_GET['type'] ?? '') === 'fish' ? 'selected' : ''; ?>>Fish</option>
                <option value="reptile" <?php echo ($_GET['type'] ?? '') === 'reptile' ? 'selected' : ''; ?>>Reptile</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="age">Age:</label>
              <select id="age" name="age">
                <option value="">Select..</option>
                <option value="young">Young</option>
                <option value="adult">Adult</option>
                <option value="senior">Senior</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="weight">Weight:</label>
              <select id="weight" name="weight">
                <option value="">Select..</option>
                <option value="small">Small</option>
                <option value="medium">Medium</option>
                <option value="large">Large</option>
              </select>
            </div>
            <div class="filter-group">
              <label for="sex">Sex:</label>
              <select id="sex" name="sex">
                <option value="">Select..</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>
          <div class="filter-buttons">
            <button type="submit" class="filter-btn">Filter</button>
            <button type="reset" class="reset-btn" onclick="window.location.href='lista-animale.php'">Reset</button>
          </div>
        </form>

        <div class="cards-container">
          <?php
          require_once('../config/database.php');
          require_once('../models/Animal.php');

          $type = $_GET['type'] ?? null;
          $gender = $_GET['sex'] ?? null;
          $age = $_GET['age'] ?? null;
          $size = $_GET['weight'] ?? null;
          
          $animals = Animal::filterAnimals($type, $gender, $age, $size);
          
          if (empty($animals)) {
              echo '<div class="no-results">No animals found matching your criteria.</div>';
          } else {
              $conn = getConnection();
              
              foreach ($animals as $animal) {
                  $query = "SELECT url FROM media WHERE pet_id = :pet_id AND type = 'photo' AND ROWNUM = 1";
                  $stmt = oci_parse($conn, $query);
                  oci_bind_by_name($stmt, ":pet_id", $animal['id']);
                  oci_execute($stmt);
                  $image = oci_fetch_assoc($stmt);
                  oci_free_statement($stmt);

                  echo '<a class="card-link" href="pet-page.php?id=' . htmlspecialchars($animal['id']) . '">';
                  echo '<div class="card">';
                  echo '<h3>' . htmlspecialchars($animal['name']) . '</h3>';
                  
                  if ($image && isset($image['URL'])) {
                      echo '<img src="../' . htmlspecialchars($image['URL']) . '" alt="' . htmlspecialchars($animal['name']) . '" />';
                  } else {
                      echo '<img src="../stiluri/imagini/' . strtolower($animal['species']) . '.png" alt="' . htmlspecialchars($animal['species']) . '" />';
                  }
                  
                  echo '<p>Type: ' . htmlspecialchars($animal['species']) . '</p>';
                  
                  if (!empty($animal['age'])) {
                      echo '<p>Age: ' . htmlspecialchars($animal['age']) . ' years</p>';
                  }
                  
                  if (!empty($animal['breed'])) {
                      echo '<p>Breed: ' . htmlspecialchars($animal['breed']) . '</p>';
                  }
                  
                  if (!empty($animal['gender'])) {
                      echo '<p>Sex: ' . htmlspecialchars($animal['gender']) . '</p>';
                  }
                  
                  $statusClass = $animal['available_for_adoption'] ? 'available' : 'not-available';
                  $statusText = $animal['available_for_adoption'] ? 'Available for Adoption' : 'Not Available';
                  echo '<div class="adoption-status ' . $statusClass . '">' . $statusText . '</div>';
                  
                  echo '</div>';
                  echo '</a>';
              }
              
              oci_close($conn);
          }
          ?>
        </div>
      </div>
    </div>
  </body>
</html>
