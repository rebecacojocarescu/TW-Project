<?php
require_once '../config/database.php';
session_start();

// Verifică dacă utilizatorul este logat
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';

// Procesează actualizarea datelor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = getConnection();
        
        // Verifică parola actuală dacă a fost furnizată o parolă nouă
        if (!empty($_POST['new_password'])) {
            $check_pwd_query = "SELECT password FROM users WHERE id = :id";
            $stmt = oci_parse($conn, $check_pwd_query);
            oci_bind_by_name($stmt, ":id", $userId);
            oci_execute($stmt);
            $user_data = oci_fetch_assoc($stmt);
            
            if (!password_verify($_POST['current_password'], $user_data['PASSWORD'])) {
                throw new Exception("Current password is incorrect");
            }
            
            $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }
        
        // Actualizează datele utilizatorului
        $query = "UPDATE users SET 
                 name = :name,
                 surname = :surname,
                 email = :email,
                 location = :location" .
                 (!empty($_POST['new_password']) ? ", password = :password" : "") .
                 " WHERE id = :id";
                 
        $stmt = oci_parse($conn, $query);
        
        // Bind parametrii
        oci_bind_by_name($stmt, ":name", $_POST['name']);
        oci_bind_by_name($stmt, ":surname", $_POST['surname']);
        oci_bind_by_name($stmt, ":email", $_POST['email']);
        oci_bind_by_name($stmt, ":location", $_POST['location']);
        oci_bind_by_name($stmt, ":id", $userId);
        
        if (!empty($_POST['new_password'])) {
            oci_bind_by_name($stmt, ":password", $password);
        }
        
        if (oci_execute($stmt)) {
            $message = "Profile updated successfully!";
        } else {
            $e = oci_error($stmt);
            throw new Exception($e['message']);
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Încarcă datele utilizatorului
try {
    $conn = getConnection();
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ":id", $userId);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
} catch (Exception $e) {
    $message = "Error loading user data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - Pow</title>
    <link rel="stylesheet" href="../stiluri/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <button class="menu-button">☰</button>
        <a href="homepage.php" style="text-decoration: none; color: inherit;"><h1>Pow</h1></a>
        <button class="profile-button">
            <img src="../stiluri/imagini/profileicon.png" alt="Profile">
        </button>
    </header>

    <div class="title-bar">
        <h2>YOUR PROFILE</h2>
    </div>

    <div class="profile-container">
        <img src="../stiluri/imagini/profileicon.png" alt="Profile Icon" class="profile-icon">
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="name" placeholder="name" 
                       value="<?php echo htmlspecialchars($user['NAME'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <input type="text" name="surname" placeholder="surname" 
                       value="<?php echo htmlspecialchars($user['SURNAME'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <input type="email" name="email" placeholder="email adress" 
                       value="<?php echo htmlspecialchars($user['EMAIL'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <input type="text" name="location" placeholder="location" 
                       value="<?php echo htmlspecialchars($user['LOCATION'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <input type="password" name="current_password" placeholder="current password">
            </div>

            <div class="form-group">
                <input type="password" name="new_password" placeholder="new password">
            </div>

            <button type="submit" class="save-button">Save Changes</button>
        </form>
    </div>

    <div class="adopted-pets">
        <h2>ADOPTED PETS</h2>
        <div class="pet-circles">
            <?php
            // Obține imaginile pentru formularele aprobate folosind funcția PL/SQL
            $query = "DECLARE
                        v_result SYS_REFCURSOR;
                     BEGIN
                        :result := get_approved_pet_image(:user_id);
                     END;";
            
            $stmt = oci_parse($conn, $query);
            
            // Creează descriptor pentru cursor
            $result = oci_new_cursor($conn);
            
            // Bind parametrii
            oci_bind_by_name($stmt, ":result", $result, -1, SQLT_RSET);
            oci_bind_by_name($stmt, ":user_id", $userId);
            
            // Execută query-ul
            oci_execute($stmt);
            oci_execute($result);

            // Afișează imaginile
            while ($row = oci_fetch_assoc($result)) {
                echo '<div class="pet-circle">';
                echo '<img src="../' . htmlspecialchars($row['IMAGE_URL']) . '" 
                           alt="' . htmlspecialchars($row['PET_NAME']) . '"
                           title="' . htmlspecialchars($row['PET_NAME']) . '">';
                echo '</div>';
            }
            
            // Eliberează resursele
            oci_free_statement($result);
            oci_free_statement($stmt);
            oci_close($conn);
            ?>
        </div>
    </div>
</body>
</html> 