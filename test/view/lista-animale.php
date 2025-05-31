<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../stiluri/lista-animale.css" />
    <title>Pow - Pet Adoption</title>
  </head>
  <body>
    <header>
      <div class="header-content">
        <button class="menu-button">☰</button>
        <a href="homepage.php"><h1>Pow</h1></a>
        <button class="profile-button">
          <img src="../stiluri/imagini/profileicon.png" alt="profile-button" />
        </button>
      </div>
    </header>
    <div class="wrapper">
      <div class="main-container">
        <h2>Filters:</h2>
        <form method="GET" action="">
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

          // Get all filter parameters
          $type = $_GET['type'] ?? null;
          $gender = $_GET['sex'] ?? null;
          $age = $_GET['age'] ?? null;
          $size = $_GET['weight'] ?? null;
          
          // Get filtered animals
          $animals = Animal::filterAnimals($type, $gender, $age, $size);
          
          if (empty($animals)) {
              echo '<div class="no-results">No animals found matching your criteria.</div>';
          } else {
              foreach ($animals as $animal) {
                  echo '<a class="card-link" href="pet-page.php?id=' . htmlspecialchars($animal['id']) . '">';
                  echo '<div class="card">';
                  echo '<h3>' . htmlspecialchars($animal['name']) . '</h3>';
                  echo '<img src="../stiluri/imagini/' . strtolower($animal['species']) . '.png" alt="' . htmlspecialchars($animal['species']) . '" />';
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
          }
          ?>
        </div>
      </div>
    </div>
  </body>
</html>
